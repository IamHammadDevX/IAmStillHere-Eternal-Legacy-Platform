<?php
require_once __DIR__ . '/_automation_helpers.php';
try{
 if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
 $db=automation_db(); $owner=automation_require_auth(); $data=automation_input(); automation_require_csrf($data); $id=(int)($data['automation_id']??0);
 $own=$db->prepare('SELECT * FROM automation_rules WHERE id=:id AND owner_id=:u AND deleted_at IS NULL LIMIT 1');$own->execute(['id'=>$id,'u'=>$owner]); if(!$own->fetch()){ApiResponse::notFound('Automation not found.');exit;}
 $v=automation_validate_rule($db,$data,$owner);
 $db->beginTransaction();
 $s=$db->prepare('UPDATE automation_rules SET title=:title,description=:description,trigger_type=:trigger,trigger_datetime=:trigger_dt,recurring_month=:month,recurring_day=:day,linked_resource_type=:linked_type,linked_resource_id=:linked_id,next_run_at=:next,status=:status,last_error=NULL WHERE id=:id AND owner_id=:owner');
 $s->execute(['title'=>$v['title'],'description'=>$v['desc']?:null,'trigger'=>$v['trigger'],'trigger_dt'=>automation_utc_datetime($data['trigger_datetime']??null),'month'=>(int)($data['recurring_month']??0)?:null,'day'=>(int)($data['recurring_day']??0)?:null,'linked_type'=>$v['linkedType'],'linked_id'=>$v['linkedId']?:null,'next'=>$v['next'],'status'=>$v['status'],'id'=>$id,'owner'=>$owner]);
 $db->prepare('DELETE FROM automation_actions WHERE rule_id=:id')->execute(['id'=>$id]);
 $a=$db->prepare('INSERT INTO automation_actions(rule_id,action_type,payload) VALUES(:rule,:type,:payload)');foreach($v['safeActions'] as $action){$a->execute(['rule'=>$id,'type'=>$action['action_type'],'payload'=>json_encode($action['payload'])]);}
 $db->commit(); ApiResponse::success(['automation_id'=>$id],'Automation updated.');
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();Logger::error('Automation update failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to update automation.');}
?>
