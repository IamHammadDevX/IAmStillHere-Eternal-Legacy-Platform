<?php
require_once __DIR__ . '/_automation_helpers.php';
try{
 if($_SERVER['REQUEST_METHOD']!=='GET'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
 $db=automation_db(); $owner=automation_require_auth(); $page=max(1,(int)($_GET['page']??1)); $limit=min(50,max(1,(int)($_GET['limit']??20))); $offset=($page-1)*$limit;
 $count=$db->prepare('SELECT COUNT(*) FROM automation_rules WHERE owner_id=:u AND deleted_at IS NULL');$count->execute(['u'=>$owner]);$total=(int)$count->fetchColumn();
 $s=$db->prepare('SELECT * FROM automation_rules WHERE owner_id=:u AND deleted_at IS NULL ORDER BY COALESCE(next_run_at, updated_at) ASC, id DESC LIMIT :limit OFFSET :offset');
 $s->bindValue(':u',$owner,PDO::PARAM_INT);$s->bindValue(':limit',$limit,PDO::PARAM_INT);$s->bindValue(':offset',$offset,PDO::PARAM_INT);$s->execute();
 $rules=[];
 foreach($s->fetchAll(PDO::FETCH_ASSOC) as $row){$a=$db->prepare('SELECT id,action_type,payload,status FROM automation_actions WHERE rule_id=:id ORDER BY id');$a->execute(['id'=>$row['id']]);$actions=$a->fetchAll(PDO::FETCH_ASSOC);foreach($actions as &$x){$x['id']=(int)$x['id'];$x['payload']=$x['payload']?json_decode($x['payload'],true):[];} $row['actions']=$actions;$rules[]=automation_format($row);} 
 ApiResponse::success(['automations'=>$rules,'pagination'=>['current_page'=>$page,'per_page'=>$limit,'total_items'=>$total,'total_pages'=>(int)ceil($total/$limit)]],'Automations loaded.');
}catch(Throwable $e){Logger::error('Automation list failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to load automations.');}
?>
