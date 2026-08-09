<?php

trait AIKnowledgeSupportTrait
{
    private function canViewMemory(int $id,int $owner,int $viewer): bool
    {
        $s=$this->db->prepare('SELECT * FROM memories WHERE id=:id AND user_id=:owner');$s->execute(['id'=>$id,'owner'=>$owner]);$r=$s->fetch(PDO::FETCH_ASSOC);
        return $r&&$r['status']==='active'&&PrivacyService::canView($this->db,'memory',$id,$owner,$viewer,$r['privacy_level'],$r['folder_id']?(int)$r['folder_id']:null);
    }

    private function canViewStandard(string $type,int $id,int $owner,int $viewer): bool
    {
        $table=['milestone'=>'milestones','post'=>'posts','journey'=>'journeys'][$type];$ownerCol=$type==='journey'?'owner_id':'user_id';
        $s=$this->db->prepare("SELECT * FROM $table WHERE id=:id AND $ownerCol=:owner");$s->execute(['id'=>$id,'owner'=>$owner]);$r=$s->fetch(PDO::FETCH_ASSOC);
        if(!$r||($r['deleted_at']??null)!==null)return false;
        if($type==='journey'&&$r['status']!=='published')return false;
        if($type!=='journey'&&$r['status']!=='active')return false;
        return PrivacyService::canView($this->db,$type,$id,$owner,$viewer,$r['privacy_level']);
    }

    private function canViewJourneyItem(int $id,int $owner,int $viewer): bool
    {
        $s=$this->db->prepare("SELECT ji.*,j.privacy_level journey_privacy,j.status journey_status,j.owner_id journey_owner FROM journey_items ji INNER JOIN journeys j ON j.id=ji.journey_id WHERE ji.id=:id AND ji.status='approved' AND ji.deleted_at IS NULL AND j.deleted_at IS NULL");$s->execute(['id'=>$id]);$item=$s->fetch(PDO::FETCH_ASSOC);
        if(!$item||$item['journey_status']!=='published'||!PrivacyService::canView($this->db,'journey',(int)$item['journey_id'],(int)$item['journey_owner'],$viewer,$item['journey_privacy']))return false;
        if($item['item_type']==='memory'){$q=$this->db->prepare('SELECT user_id FROM memories WHERE id=:id');$q->execute(['id'=>$item['source_id']]);return $this->canViewMemory((int)$item['source_id'],(int)$q->fetchColumn(),$viewer);}
        if($item['item_type']==='milestone'){$q=$this->db->prepare('SELECT user_id FROM milestones WHERE id=:id');$q->execute(['id'=>$item['source_id']]);return $this->canViewStandard('milestone',(int)$item['source_id'],(int)$q->fetchColumn(),$viewer);}
        return true;
    }

    private function queue(int $sourceId,bool $force=false): void
    {
        if($force)$this->db->prepare("UPDATE ai_ingestion_jobs SET status='failed',error_code='superseded' WHERE source_id=:id AND status='pending'")->execute(['id'=>$sourceId]);
        $this->db->prepare("INSERT INTO ai_ingestion_jobs(source_id,status,available_at) VALUES(:id,'pending',UTC_TIMESTAMP())")->execute(['id'=>$sourceId]);
    }

    private function normalizeText(string $text): string
    {
        $text=strip_tags($text);$text=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',' ',$text)??'';
        $text=preg_replace('/[ \t]+/u',' ',$text)??$text;$text=preg_replace('/\R{3,}/u',"\n\n",$text)??$text;return trim($text);
    }

    private function chunk(string $text,int $max=1200,int $overlap=150): array
    {
        $chunks=[];$length=mb_strlen($text);$start=0;
        while($start<$length){$take=min($max,$length-$start);$part=mb_substr($text,$start,$take);if($start+$take<$length){$newline=mb_strrpos($part,"\n");$sentence=mb_strrpos($part,'. ');$break=max($newline===false?0:$newline,$sentence===false?0:$sentence);if($break>(int)($max*.55))$part=mb_substr($part,0,$break+1);}$part=trim($part);if($part!==''&&!in_array($part,$chunks,true))$chunks[]=$part;if($start+$take>=$length)break;$start+=max(1,mb_strlen($part)-$overlap);}
        return $chunks;
    }

    private function isDocument(string $mime): bool{return $mime==='text/plain'||$mime==='application/pdf'||str_contains($mime,'wordprocessingml');}

    private function extractMemoryDocument(array $memory): string
    {
        $path=UPLOAD_PATH.DIRECTORY_SEPARATOR.'documents'.DIRECTORY_SEPARATOR.basename((string)$memory['file_path']);if(!is_file($path)||!is_readable($path))throw new RuntimeException('ai_document_unreadable');$mime=(string)$memory['file_type'];
        if($mime==='text/plain')return (string)file_get_contents($path);
        if(str_contains($mime,'wordprocessingml')){if(!class_exists('ZipArchive'))throw new RuntimeException('ai_docx_unsupported');$zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('ai_docx_extract_failed');$xml=$zip->getFromName('word/document.xml');$zip->close();if($xml===false)throw new RuntimeException('ai_docx_extract_failed');return html_entity_decode(strip_tags(str_replace(['</w:p>','</w:tr>'],"\n",$xml)),ENT_QUOTES|ENT_XML1,'UTF-8');}
        if($mime==='application/pdf')return $this->extractSimplePdf($path);return '';
    }

    private function extractSimplePdf(string $path): string
    {
        $raw=(string)file_get_contents($path);preg_match_all('/\(([^()]*(?:\\\\.[^()]*)*)\)\s*Tj/s',$raw,$matches);$text='';foreach($matches[1]??[] as $item)$text.=stripcslashes($item)."\n";if(trim($text)==='')throw new RuntimeException('ai_pdf_extract_unsupported');return $text;
    }

    private function blocked(int $a,int $b): bool{$s=$this->db->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='blocked' LIMIT 1");$s->execute(['a'=>$a,'b'=>$b]);return(bool)$s->fetchColumn();}
    private function cosine(array $a,array $b): float{if(count($a)!==count($b)||!$a)return 0.0;$dot=0.0;$na=0.0;$nb=0.0;foreach($a as $i=>$v){$x=(float)$v;$y=(float)$b[$i];$dot+=$x*$y;$na+=$x*$x;$nb+=$y*$y;}return $na>0&&$nb>0?$dot/(sqrt($na)*sqrt($nb)):0.0;}
}
