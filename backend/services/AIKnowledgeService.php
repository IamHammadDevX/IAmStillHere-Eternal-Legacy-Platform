<?php

require_once __DIR__ . '/EmbeddingProviderInterface.php';
require_once __DIR__ . '/OpenAIEmbeddingProvider.php';
require_once __DIR__ . '/PrivacyService.php';
require_once __DIR__ . '/AIKnowledgeSupportTrait.php';

class AIKnowledgeService
{
    use AIKnowledgeSupportTrait;
    public const RESOURCE_TYPES = ['profile', 'memory', 'milestone', 'post', 'journey', 'journey_item'];
    private PDO $db;
    private EmbeddingProviderInterface $provider;

    public function __construct(PDO $db, ?EmbeddingProviderInterface $provider = null)
    {
        $this->db = $db;
        $this->provider = $provider ?? new OpenAIEmbeddingProvider();
    }

    private function canViewSource(array $source,int $viewerId): bool
    {
        $owner=(int)$source['user_id'];$id=(int)$source['resource_id'];$type=$source['resource_type'];
        if($type==='profile'){$s=$this->db->prepare("SELECT id FROM users WHERE id=:id AND status='active'");$s->execute(['id'=>$owner]);return $owner===$viewerId&&(bool)$s->fetchColumn();}
        if($type==='memory')return $this->canViewMemory($id,$owner,$viewerId);
        if(in_array($type,['milestone','post','journey'],true))return $this->canViewStandard($type,$id,$owner,$viewerId);
        return $this->canViewJourneyItem($id,$owner,$viewerId);
    }

    private function sourceId(int $ownerId,string $type,int $resourceId): int
    {
        $s=$this->db->prepare('SELECT id FROM ai_sources WHERE user_id=:u AND resource_type=:t AND resource_id=:r LIMIT 1');
        $s->execute(['u'=>$ownerId,'t'=>$type,'r'=>$resourceId]);
        return (int)$s->fetchColumn();
    }

    public function approveSource(int $ownerId, string $type, int $resourceId): array
    {
        $resource = $this->loadOwnedResource($ownerId, $type, $resourceId);
        $statement = $this->db->prepare("INSERT INTO ai_sources
            (user_id,resource_type,resource_id,title,source_date,ingestion_status,ai_enabled,consented_at,deleted_at)
            VALUES(:user_id,:resource_type,:resource_id,:title,:source_date,'pending',1,UTC_TIMESTAMP(),NULL)
            ON DUPLICATE KEY UPDATE title=VALUES(title),source_date=VALUES(source_date),ingestion_status='pending',ai_enabled=1,consented_at=UTC_TIMESTAMP(),deleted_at=NULL,last_error_code=NULL");
        $statement->execute(['user_id'=>$ownerId,'resource_type'=>$type,'resource_id'=>$resourceId,'title'=>$resource['title'],'source_date'=>$resource['source_date']]);
        $sourceId = (int) ($this->db->lastInsertId() ?: $this->sourceId($ownerId, $type, $resourceId));
        $this->queue($sourceId, true);
        return ['source_id'=>$sourceId,'status'=>'pending'];
    }

    public function disableSource(int $ownerId, string $type, int $resourceId): void
    {
        $sourceId = $this->sourceId($ownerId, $type, $resourceId);
        if ($sourceId <= 0) return;
        $ownsTransaction=!$this->db->inTransaction(); if($ownsTransaction)$this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE ai_sources SET ai_enabled=0,ingestion_status='disabled',deleted_at=UTC_TIMESTAMP(),extracted_text=NULL WHERE id=:id AND user_id=:user_id")->execute(['id'=>$sourceId,'user_id'=>$ownerId]);
            $this->db->prepare('DELETE FROM ai_chunks WHERE source_id=:id')->execute(['id'=>$sourceId]);
            $this->db->prepare("UPDATE ai_ingestion_jobs SET status='failed',error_code='consent_revoked' WHERE source_id=:id AND status IN ('pending','processing')")->execute(['id'=>$sourceId]);
            if($ownsTransaction)$this->db->commit();
        } catch (Throwable $e) { if ($ownsTransaction&&$this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    public function reindex(int $ownerId, int $sourceId): void
    {
        $s=$this->db->prepare('SELECT id FROM ai_sources WHERE id=:id AND user_id=:user_id AND ai_enabled=1 AND deleted_at IS NULL LIMIT 1');
        $s->execute(['id'=>$sourceId,'user_id'=>$ownerId]);
        if (!$s->fetchColumn()) throw new InvalidArgumentException('Source not found.');
        $this->db->prepare("UPDATE ai_sources SET ingestion_status='pending',last_error_code=NULL WHERE id=:id")->execute(['id'=>$sourceId]);
        $this->queue($sourceId, true);
    }

    public function processSource(int $sourceId): string
    {
        $s=$this->db->prepare('SELECT * FROM ai_sources WHERE id=:id AND ai_enabled=1 AND deleted_at IS NULL LIMIT 1');$s->execute(['id'=>$sourceId]);$source=$s->fetch(PDO::FETCH_ASSOC);
        if (!$source) throw new RuntimeException('ai_source_unavailable');
        $resource=$this->loadOwnedResource((int)$source['user_id'],(string)$source['resource_type'],(int)$source['resource_id']);
        $text=$this->normalizeText($resource['text']);
        if ($text==='') throw new RuntimeException('ai_source_empty');
        $hash=hash('sha256',$text);
        if ($source['content_hash']===$hash && $source['ingestion_status']==='indexed') return 'unchanged';
        $chunks=$this->chunk($text);$vectors=[];
        foreach(array_chunk($chunks,20) as $batch){$vectors=array_merge($vectors,$this->provider->embed($batch));}
        if(count($vectors)!==count($chunks))throw new RuntimeException('ai_embedding_count_mismatch');

        $ownsTransaction=!$this->db->inTransaction(); if($ownsTransaction)$this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM ai_chunks WHERE source_id=:id')->execute(['id'=>$sourceId]);
            $insert=$this->db->prepare('INSERT INTO ai_chunks(source_id,user_id,chunk_index,chunk_text,chunk_hash,embedding,metadata_json) VALUES(:source,:user_id,:idx,:text,:hash,:embedding,:metadata)');
            foreach($chunks as $index=>$chunk){$insert->execute(['source'=>$sourceId,'user_id'=>$source['user_id'],'idx'=>$index,'text'=>$chunk,'hash'=>hash('sha256',$chunk),'embedding'=>json_encode($vectors[$index]),'metadata'=>json_encode(['resource_type'=>$source['resource_type'],'resource_id'=>(int)$source['resource_id'],'title'=>$resource['title'],'source_date'=>$resource['source_date']],JSON_UNESCAPED_SLASHES)]);}
            $this->db->prepare("UPDATE ai_sources SET title=:title,extracted_text=:text,source_date=:source_date,content_hash=:hash,ingestion_status='indexed',last_error_code=NULL WHERE id=:id AND ai_enabled=1")->execute(['title'=>$resource['title'],'text'=>$text,'source_date'=>$resource['source_date'],'hash'=>$hash,'id'=>$sourceId]);
            if($ownsTransaction)$this->db->commit();
        }catch(Throwable $e){if($ownsTransaction&&$this->db->inTransaction())$this->db->rollBack();throw $e;}
        return 'indexed';
    }

    public function searchForUser(int $ownerId, int $viewerId, string $query, int $limit = 5): array
    {
        $query=$this->normalizeText($query);if($query==='')throw new InvalidArgumentException('Query is required.');$limit=max(1,min(20,$limit));
        if($viewerId!==$ownerId && $this->blocked($ownerId,$viewerId)) return [];
        $queryVector=$this->provider->embed([$query])[0]??[];
        $s=$this->db->prepare("SELECT c.*,s.resource_type,s.resource_id,s.title,s.source_date FROM ai_chunks c INNER JOIN ai_sources s ON s.id=c.source_id WHERE s.user_id=:owner AND s.ai_enabled=1 AND s.ingestion_status='indexed' AND s.deleted_at IS NULL");$s->execute(['owner'=>$ownerId]);
        $results=[];
        foreach($s->fetchAll(PDO::FETCH_ASSOC) as $row){if(!$this->canViewSource($row,$viewerId))continue;$vector=json_decode($row['embedding'],true);if(!is_array($vector))continue;$row['score']=$this->cosine($queryVector,$vector);unset($row['embedding'],$row['user_id'],$row['chunk_hash']);$row['resource_id']=(int)$row['resource_id'];$row['source_id']=(int)$row['source_id'];$row['chunk_index']=(int)$row['chunk_index'];$results[]=$row;}
        usort($results,static fn(array $a,array $b):int=>$b['score']<=>$a['score']);return array_slice($results,0,$limit);
    }

    public function sourceStatus(int $ownerId): array
    {
        $s=$this->db->prepare("SELECT id,resource_type,resource_id,title,source_date,ingestion_status,ai_enabled,content_hash,last_error_code,created_at,updated_at FROM ai_sources WHERE user_id=:user_id AND deleted_at IS NULL ORDER BY updated_at DESC,id DESC");$s->execute(['user_id'=>$ownerId]);return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadOwnedResource(int $ownerId,string $type,int $id): array
    {
        if(!in_array($type,self::RESOURCE_TYPES,true)||$id<=0)throw new InvalidArgumentException('Unsupported source.');
        if($type==='profile'){$s=$this->db->prepare("SELECT id,bio,updated_at FROM users WHERE id=:id AND id=:owner AND status='active'");$s->execute(['id'=>$id,'owner'=>$ownerId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('Source not found.');return ['title'=>'Profile bio','text'=>(string)$r['bio'],'source_date'=>$r['updated_at']];}
        if($type==='memory'){$s=$this->db->prepare("SELECT * FROM memories WHERE id=:id AND user_id=:owner AND status='active'");$s->execute(['id'=>$id,'owner'=>$ownerId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('Source not found.');$text=trim($r['title']."\n".($r['description']??''));if($this->isDocument($r['file_type']??''))$text.="\n".$this->extractMemoryDocument($r);return ['title'=>$r['title'],'text'=>$text,'source_date'=>$r['memory_date']?:$r['upload_date']];}
        if($type==='milestone'){$s=$this->db->prepare("SELECT * FROM milestones WHERE id=:id AND user_id=:owner AND status='active'");$s->execute(['id'=>$id,'owner'=>$ownerId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('Source not found.');return ['title'=>$r['title'],'text'=>trim($r['title']."\n".($r['description']??'')."\n".($r['category']??'')),'source_date'=>$r['milestone_date']];}
        if($type==='post'){$s=$this->db->prepare("SELECT * FROM posts WHERE id=:id AND user_id=:owner AND status='active' AND deleted_at IS NULL");$s->execute(['id'=>$id,'owner'=>$ownerId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('Source not found.');return ['title'=>'Post','text'=>$r['body'],'source_date'=>$r['created_at']];}
        if($type==='journey'){$s=$this->db->prepare("SELECT * FROM journeys WHERE id=:id AND owner_id=:owner AND status='published' AND deleted_at IS NULL");$s->execute(['id'=>$id,'owner'=>$ownerId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('Source not found.');return ['title'=>$r['title'],'text'=>trim($r['title']."\n".($r['description']??'')),'source_date'=>$r['start_date']?:$r['created_at']];}
        $s=$this->db->prepare("SELECT ji.* FROM journey_items ji INNER JOIN journeys j ON j.id=ji.journey_id WHERE ji.id=:id AND (ji.contributor_id=:owner OR j.owner_id=:owner) AND ji.status='approved' AND ji.deleted_at IS NULL AND j.status='published' AND j.deleted_at IS NULL");$s->execute(['id'=>$id,'owner'=>$ownerId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('Source not found.');return ['title'=>$r['title'],'text'=>trim($r['title']."\n".($r['description']??'')),'source_date'=>$r['item_date']?:$r['created_at']];
    }
}
