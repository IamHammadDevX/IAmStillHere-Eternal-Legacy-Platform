<?php
require_once __DIR__ . '/_admin_helpers.php';
try{
 if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
 admin_require();$db=admin_db();$data=admin_input();admin_csrf($data);$type=(string)($data['type']??'');$id=(int)($data['id']??0);
 if($type==='automation'){$s=$db->prepare("UPDATE automation_rules SET status='scheduled',retry_count=0,last_error=NULL,next_run_at=UTC_TIMESTAMP() WHERE id=:id AND status='failed' AND deleted_at IS NULL");$s->execute(['id'=>$id]);if($s->rowCount()<1){ApiResponse::notFound('Failed automation not found.');exit;}ApiResponse::success([],'Automation queued for retry.');exit;}
 if($type==='ai_ingestion'){$s=$db->prepare("UPDATE ai_ingestion_jobs SET status='pending',attempts=0,error_code=NULL,available_at=UTC_TIMESTAMP() WHERE id=:id AND status='failed'");$s->execute(['id'=>$id]);if($s->rowCount()<1){ApiResponse::notFound('Failed AI job not found.');exit;}ApiResponse::success([],'AI job queued for retry.');exit;}
 ApiResponse::validation(['type'=>'Invalid retry type.']);
}catch(Throwable $e){Logger::error('Admin retry failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to retry job.');}
?>
