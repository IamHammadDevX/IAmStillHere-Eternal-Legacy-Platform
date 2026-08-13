<?php
if (PHP_SAPI === 'cli') { ini_set('session.save_path', sys_get_temp_dir()); }
require_once __DIR__ . '/../automations/_automation_helpers.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';

function automation_worker_next_recurring(array $rule): ?string {
    if (!in_array($rule['trigger_type'], ['birthday','anniversary','custom_recurring'], true)) return null;
    $m=(int)$rule['recurring_month']; $d=(int)$rule['recurring_day']; if($m<1||$d<1)return null;
    $year=(int)gmdate('Y');
    for($i=0;$i<4;$i++){
        if(checkdate($m,$d,$year+$i)){
            $dt=new DateTimeImmutable(sprintf('%04d-%02d-%02d 09:00:00',$year+$i,$m,$d), new DateTimeZone('UTC'));
            if($dt->getTimestamp()>time()+60) return $dt->format('Y-m-d H:i:s');
        }
    }
    return null;
}

function automation_worker_run_action(PDO $db, array $rule, array $action): void {
    $payload=$action['payload'] ? json_decode($action['payload'], true) : [];
    if(!is_array($payload)) $payload=[];
    if($action['action_type']==='notification'){
        $message=mb_substr((string)($payload['message']??($rule['description']??('Automation ready: '. $rule['title']))),0,10000);
        $event=$db->prepare("SELECT id FROM scheduled_events WHERE user_id=:user AND event_type='automation_notification' AND title=:title AND scheduled_date=:scheduled LIMIT 1");
        $event->execute(['user'=>(int)$rule['owner_id'],'title'=>$rule['title'],'scheduled'=>$rule['next_run_at']]);
        $eventId=(int)$event->fetchColumn();
        if($eventId<=0){
            $insert=$db->prepare("INSERT INTO scheduled_events (user_id,event_type,title,message,scheduled_date,privacy_level,status,notified,notified_at) VALUES (:user,'automation_notification',:title,:message,:scheduled,'private','scheduled',0,NULL)");
            $insert->execute(['user'=>(int)$rule['owner_id'],'title'=>$rule['title'],'message'=>$message,'scheduled'=>$rule['next_run_at']]);
            $eventId=(int)$db->lastInsertId();
        }
        NotificationService::createOnce($db,(int)$rule['owner_id'],null,NotificationService::TYPE_SCHEDULED_EVENT_STATUS,'scheduled_event',$eventId,$message);
        $db->prepare("UPDATE scheduled_events SET status='published', notified=1, notified_at=NOW() WHERE id=:id")->execute(['id'=>$eventId]);
        return;
    }
    if($action['action_type']==='wall_post'){
        $scheduledId=(int)($payload['scheduled_wall_post_id']??0);
        if($scheduledId>0){$lock=$db->prepare("UPDATE scheduled_wall_posts SET status='processing' WHERE id=:id AND owner_id=:owner AND status='scheduled'");$lock->execute(['id'=>$scheduledId,'owner'=>(int)$rule['owner_id']]);if($lock->rowCount()<1)return;}
        $body=trim((string)($payload['body']??$rule['description']??$rule['title']));
        if($body==='') throw new RuntimeException('Wall post body empty.');
        $privacy=in_array(($payload['privacy_level']??'private'),['public','family','private'],true)?$payload['privacy_level']:'private';
        $s=$db->prepare('INSERT INTO posts(user_id,body,privacy_level) VALUES(:u,:body,:privacy)');
        $s->execute(['u'=>(int)$rule['owner_id'],'body'=>$body,'privacy'=>$privacy]);
        $postId=(int)$db->lastInsertId();
        if(!empty($payload['media_file_path']) && in_array(($payload['media_type']??''),['image','video'],true)){$m=$db->prepare('INSERT INTO post_media(post_id,file_path,file_type,file_size,media_type) VALUES(:p,:path,:type,:size,:media)');$m->execute(['p'=>$postId,'path'=>$payload['media_file_path'],'type'=>$payload['media_file_type']??'','size'=>(int)($payload['media_file_size']??0),'media'=>$payload['media_type']]);}
        if($scheduledId>0)$db->prepare("UPDATE scheduled_wall_posts SET status='published', published_post_id=:post, updated_at=UTC_TIMESTAMP() WHERE id=:id")->execute(['post'=>$postId,'id'=>$scheduledId]);
        return;
    }    if($action['action_type']==='email'){
        $u=$db->prepare('SELECT email,full_name FROM users WHERE id=:id LIMIT 1');$u->execute(['id'=>(int)$rule['owner_id']]);$user=$u->fetch(PDO::FETCH_ASSOC);
        if(!$user) throw new RuntimeException('Owner not found.');
        $event=['title'=>$rule['title'],'event_type'=>$rule['trigger_type'],'scheduled_date'=>$rule['next_run_at'],'message'=>$payload['message']??$rule['description']??'','user_id'=>(int)$rule['owner_id']];
        if(!EmailHelper::sendEventNotificationEmail($user['email'],$user['full_name']?:'User',$event,$user['full_name']?:'User')) throw new RuntimeException('Email send failed.');
        return;
    }
    throw new RuntimeException('Unknown action.');
}

try{
    $db=automation_db();
    $limit=max(1,min(25,(int)($argv[1]??10)));
    $claim=$db->prepare("SELECT * FROM automation_rules WHERE status='scheduled' AND deleted_at IS NULL AND next_run_at IS NOT NULL AND next_run_at <= UTC_TIMESTAMP() ORDER BY next_run_at ASC,id ASC LIMIT :limit");
    $claim->bindValue(':limit',$limit,PDO::PARAM_INT);$claim->execute();$rules=$claim->fetchAll(PDO::FETCH_ASSOC);
    $processed=0;$failed=0;$skipped=0;
    foreach($rules as $rule){
        $lock=$db->prepare("UPDATE automation_rules SET status='processing' WHERE id=:id AND status='scheduled'");$lock->execute(['id'=>$rule['id']]);
        if($lock->rowCount()<1){$skipped++;continue;}
        $actions=$db->prepare("SELECT * FROM automation_actions WHERE rule_id=:id AND status='active' ORDER BY id");$actions->execute(['id'=>$rule['id']]);$all=$actions->fetchAll(PDO::FETCH_ASSOC);
        $ruleFailed=false;$error=null;
        foreach($all as $action){
            $key='automation-'.$rule['id'].'-'.$action['id'].'-'.gmdate('YmdHi',strtotime($rule['next_run_at']));
            try{
                $run=$db->prepare("INSERT INTO automation_runs(rule_id,action_id,idempotency_key,status) VALUES(:r,:a,:k,'processing')");$run->execute(['r'=>$rule['id'],'a'=>$action['id'],'k'=>$key]);
            }catch(Throwable $e){$skipped++;continue;}
            try{automation_worker_run_action($db,$rule,$action);$payloadDone=$action['payload']?json_decode($action['payload'],true):[];if(is_array($payloadDone)&&!empty($payloadDone['personalized_message_id'])){$db->prepare("UPDATE ai_personalized_messages SET status='sent', updated_at=UTC_TIMESTAMP() WHERE id=:id AND status='scheduled'")->execute(['id'=>(int)$payloadDone['personalized_message_id']]);}$db->prepare("UPDATE automation_runs SET status='completed',completed_at=CURRENT_TIMESTAMP WHERE idempotency_key=:k")->execute(['k'=>$key]);}
            catch(Throwable $e){$ruleFailed=true;$error=mb_substr($e->getMessage(),0,500);$db->prepare("UPDATE automation_runs SET status='failed',error_message=:e,completed_at=CURRENT_TIMESTAMP WHERE idempotency_key=:k")->execute(['e'=>$error,'k'=>$key]);}
        }
        $next=automation_worker_next_recurring($rule);
        if($ruleFailed){$failed++;$db->prepare("UPDATE automation_rules SET status=IF(retry_count+1>=max_retries,'failed','scheduled'), retry_count=retry_count+1, last_error=:e WHERE id=:id")->execute(['e'=>$error,'id'=>$rule['id']]);}
        elseif($next){$processed++;$db->prepare("UPDATE automation_rules SET status='scheduled', next_run_at=:next, retry_count=0,last_error=NULL WHERE id=:id")->execute(['next'=>$next,'id'=>$rule['id']]);}
        else{$processed++;$db->prepare("UPDATE automation_rules SET status='completed', retry_count=0,last_error=NULL WHERE id=:id")->execute(['id'=>$rule['id']]);}
    }
    $result=['processed'=>$processed,'failed'=>$failed,'skipped'=>$skipped,'checked'=>count($rules)];
    if(PHP_SAPI==='cli') echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL; else ApiResponse::success($result,'Automations processed.');
}catch(Throwable $e){Logger::error('Automation worker failed',['error'=>$e->getMessage()]); if(PHP_SAPI==='cli'){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);} ApiResponse::serverError('Unable to process automations.');}
?>
