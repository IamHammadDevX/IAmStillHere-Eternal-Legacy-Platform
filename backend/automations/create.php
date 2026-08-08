<?php
require_once __DIR__ . '/_automation_helpers.php';
try{
 if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
 $db=automation_db(); $owner=automation_require_auth(); $data=automation_input(); automation_require_csrf($data);
 $v=automation_validate_rule($db,$data,$owner);
 $db->beginTransaction();
 $s=$db->prepare('INSERT INTO automation_rules(owner_id,title,description,trigger_type,trigger_datetime,recurring_month,recurring_day,linked_resource_type,linked_resource_id,timezone,next_run_at,status) VALUES(:owner,:title,:description,:trigger,:trigger_dt,:month,:day,:linked_type,:linked_id,:tz,:next,:status)');
 $s->execute(['owner'=>$owner,'title'=>$v['title'],'description'=>$v['desc']?:null,'trigger'=>$v['trigger'],'trigger_dt'=>automation_utc_datetime($data['trigger_datetime']??null),'month'=>(int)($data['recurring_month']??0)?:null,'day'=>(int)($data['recurring_day']??0)?:null,'linked_type'=>$v['linkedType'],'linked_id'=>$v['linkedId']?:null,'tz'=>'UTC','next'=>$v['next'],'status'=>$v['status']]);
 $id=(int)$db->lastInsertId();
 $a=$db->prepare('INSERT INTO automation_actions(rule_id,action_type,payload) VALUES(:rule,:type,:payload)');
 foreach($v['safeActions'] as $action){$a->execute(['rule'=>$id,'type'=>$action['action_type'],'payload'=>json_encode($action['payload'])]);}
 $db->commit(); ApiResponse::success(['automation_id'=>$id],'Automation created.',201);
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();Logger::error('Automation create failed',['error'=>$e->getMessage()]);ApiResponse::serverError($e instanceof RuntimeException?$e->getMessage():'Unable to create automation.');}
?>
