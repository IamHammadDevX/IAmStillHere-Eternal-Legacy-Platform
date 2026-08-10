<?php
require_once __DIR__ . '/_gift_helpers.php';
try{if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}$db=gifts_db();$user=gifts_user();$data=gifts_input();gifts_csrf($data);$order=gifts_service($db)->cancel($user,(int)($data['order_id']??0));ApiResponse::success(['order'=>$order],'Gift order cancelled.');}catch(InvalidArgumentException $e){ApiResponse::validation(['gift'=>$e->getMessage()]);}catch(Throwable $e){Logger::error('Gift cancel failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to cancel gift order.');}
?>
