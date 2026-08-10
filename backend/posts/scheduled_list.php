<?php
require_once __DIR__ . '/_scheduled_helpers.php';
try{$db=posts_connection();$owner=scheduled_post_require_owner($db);$s=$db->prepare("SELECT * FROM scheduled_wall_posts WHERE owner_id=:owner AND deleted_at IS NULL ORDER BY created_at DESC,id DESC LIMIT 50");$s->execute(['owner'=>$owner]);ApiResponse::success(['scheduled_posts'=>array_map('scheduled_post_format',$s->fetchAll(PDO::FETCH_ASSOC))],'Scheduled posts loaded.');}catch(Throwable $e){ApiResponse::serverError('Unable to load scheduled posts.');}
?>
