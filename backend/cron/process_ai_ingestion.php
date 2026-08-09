<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only.');}
ini_set('session.save_path',sys_get_temp_dir());
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../services/AIKnowledgeService.php';

$processed=0;$failed=0;$skipped=0;
try{
    $db=(new Database())->getConnection();$limit=max(1,min(25,(int)($argv[1]??10)));
    $s=$db->prepare("SELECT id,source_id,attempts,max_attempts FROM ai_ingestion_jobs WHERE status='pending' AND available_at<=UTC_TIMESTAMP() ORDER BY id LIMIT :limit");$s->bindValue(':limit',$limit,PDO::PARAM_INT);$s->execute();$jobs=$s->fetchAll(PDO::FETCH_ASSOC);$service=new AIKnowledgeService($db);
    foreach($jobs as $job){$claim=$db->prepare("UPDATE ai_ingestion_jobs SET status='processing',locked_at=UTC_TIMESTAMP(),attempts=attempts+1 WHERE id=:id AND status='pending'");$claim->execute(['id'=>$job['id']]);if($claim->rowCount()!==1){$skipped++;continue;}$db->prepare("UPDATE ai_sources SET ingestion_status='processing' WHERE id=:id AND ai_enabled=1")->execute(['id'=>$job['source_id']]);
        try{$result=$service->processSource((int)$job['source_id']);$db->prepare("UPDATE ai_ingestion_jobs SET status='completed',completed_at=UTC_TIMESTAMP(),error_code=NULL WHERE id=:id")->execute(['id'=>$job['id']]);$processed++;}
        catch(Throwable $e){$code=preg_match('/^ai_[a-z_]+$/',$e->getMessage())?$e->getMessage():'ai_ingestion_failed';$attempt=(int)$job['attempts']+1;$retry=$attempt<(int)$job['max_attempts'];$db->prepare("UPDATE ai_ingestion_jobs SET status=:status,error_code=:code,available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL :delay SECOND),locked_at=NULL WHERE id=:id")->execute(['status'=>$retry?'pending':'failed','code'=>$code,'delay'=>min(900,30*(2**max(0,$attempt-1))),'id'=>$job['id']]);$db->prepare("UPDATE ai_sources SET ingestion_status=:status,last_error_code=:code WHERE id=:source")->execute(['status'=>$retry?'pending':'failed','code'=>$code,'source'=>$job['source_id']]);Logger::warning('AI ingestion job failed',['job_id'=>(int)$job['id'],'source_id'=>(int)$job['source_id'],'error_code'=>$code]);$failed++;}
    }
    echo json_encode(['checked'=>count($jobs),'processed'=>$processed,'failed'=>$failed,'skipped'=>$skipped],JSON_PRETTY_PRINT).PHP_EOL;
}catch(Throwable $e){Logger::error('AI ingestion worker failed',['error_code'=>'ai_worker_failed']);fwrite(STDERR,"AI ingestion worker failed.\n");exit(1);}
