<?php
require_once __DIR__ . '/_helpers.php';
$owner=privacy_auth();try{$db=privacy_db();$s=$db->prepare("SELECT id,title,scheduled_date,status FROM scheduled_events WHERE user_id=:u AND status IN ('scheduled','published','cancelled') ORDER BY scheduled_date DESC LIMIT 100");$s->execute(['u'=>$owner]);ApiResponse::success(['events'=>$s->fetchAll(PDO::FETCH_ASSOC)]);}catch(Throwable $e){ApiResponse::serverError('Unable to load events');}
