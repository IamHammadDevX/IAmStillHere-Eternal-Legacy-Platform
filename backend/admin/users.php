<?php
require_once __DIR__ . '/_admin_helpers.php';
try{
 admin_require(); $conn=admin_db();
 if($_SERVER['REQUEST_METHOD']==='GET'){
  $page=max(1,(int)($_GET['page']??1));$limit=min(50,max(5,(int)($_GET['limit']??10)));$offset=($page-1)*$limit;$q=trim((string)($_GET['q']??''));$status=trim((string)($_GET['status']??''));
  $where='1=1';$p=[]; if($q!==''){$where.=' AND (username LIKE :q OR full_name LIKE :q OR email LIKE :q)';$p['q']='%'.$q.'%';} if(in_array($status,['active','suspended','deleted'],true)){$where.=' AND status=:status';$p['status']=$status;}
  $c=$conn->prepare("SELECT COUNT(*) FROM users WHERE $where");$c->execute($p);$total=(int)$c->fetchColumn();
  $s=$conn->prepare("SELECT id,username,email,full_name,role,status,created_at,last_login,
   (SELECT COALESCE(SUM(m.file_size),0) FROM memories m WHERE m.user_id=users.id AND m.status='active') + (SELECT COALESCE(SUM(pm.file_size),0) FROM post_media pm INNER JOIN posts p ON p.id=pm.post_id WHERE p.user_id=users.id AND p.status='active') AS memory_storage_bytes,
   (SELECT COALESCE(SUM(apm.total_tokens),0) FROM ai_personalized_messages apm WHERE apm.owner_id=users.id AND apm.deleted_at IS NULL) AS ai_message_tokens,
   (SELECT COALESCE(SUM(aa.total_tokens),0) FROM ai_autobiographies aa WHERE aa.owner_id=users.id AND aa.deleted_at IS NULL) AS ai_autobiography_tokens,
   (SELECT COALESCE(SUM(am.total_tokens),0) FROM ai_messages am WHERE COALESCE(am.viewer_id,am.owner_id)=users.id) AS ai_avatar_tokens,
   (SELECT COALESCE(SUM(apm.total_tokens),0) FROM ai_personalized_messages apm WHERE apm.owner_id=users.id AND apm.deleted_at IS NULL) + (SELECT COALESCE(SUM(aa.total_tokens),0) FROM ai_autobiographies aa WHERE aa.owner_id=users.id AND aa.deleted_at IS NULL) + (SELECT COALESCE(SUM(am.total_tokens),0) FROM ai_messages am WHERE COALESCE(am.viewer_id,am.owner_id)=users.id) AS ai_tokens_used
   FROM users WHERE $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");foreach($p as $k=>$v)$s->bindValue(':'.$k,$v);$s->bindValue(':limit',$limit,PDO::PARAM_INT);$s->bindValue(':offset',$offset,PDO::PARAM_INT);$s->execute();
  ApiResponse::success(['users'=>$s->fetchAll(PDO::FETCH_ASSOC),'pagination'=>['page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)]],'Users loaded.');exit;
 }
 if($_SERVER['REQUEST_METHOD']==='PUT'){
  $data=admin_input();admin_csrf($data);$user_id=(int)($data['user_id']??0);$status=(string)($data['status']??'');
  if(!in_array($status,['active','suspended'],true)){ApiResponse::validation(['status'=>'Invalid status.']);exit;}
  $check=$conn->prepare('SELECT role FROM users WHERE id=:id LIMIT 1');$check->execute(['id'=>$user_id]);$role=$check->fetchColumn();if(!$role){ApiResponse::notFound('User not found.');exit;}if($role==='admin'){ApiResponse::forbidden('Admin users cannot be changed here.');exit;}
  $stmt=$conn->prepare('UPDATE users SET status=:status WHERE id=:id');$stmt->execute(['status'=>$status,'id'=>$user_id]);
  ApiResponse::success([],'User status updated.');exit;
 }
 ApiResponse::send(false,[],'Method not allowed.',[],405);
}catch(Throwable $e){Logger::error('Admin users failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to manage users.');}
?>
