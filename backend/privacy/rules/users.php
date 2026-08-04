<?php
require_once __DIR__ . '/_helpers.php';
$owner=privacy_auth();$q=trim((string)($_GET['q']??''));
try{$db=privacy_db();$s=$db->prepare("SELECT id,full_name,username,profile_photo FROM users WHERE status='active' AND id<>:owner AND (full_name LIKE :q OR username LIKE :q) AND NOT EXISTS (SELECT 1 FROM friendships f WHERE ((f.user_id=:a AND f.friend_id=users.id) OR (f.user_id=users.id AND f.friend_id=:b)) AND f.status='blocked') ORDER BY full_name LIMIT 50");$s->execute(['owner'=>$owner,'q'=>'%'.$q.'%','a'=>$owner,'b'=>$owner]);ApiResponse::success(['users'=>$s->fetchAll(PDO::FETCH_ASSOC)]);}catch(Throwable $e){ApiResponse::serverError('Unable to load users');}
