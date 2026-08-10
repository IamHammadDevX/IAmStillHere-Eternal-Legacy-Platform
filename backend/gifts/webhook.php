<?php
require_once __DIR__ . '/_gift_helpers.php';
try{if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}$body=file_get_contents('php://input')?:'';$sig=$_SERVER['HTTP_X_PHOOLWALA_SIGNATURE']??$_SERVER['HTTP_X_SIGNATURE']??'';$db=gifts_db();$service=gifts_service($db);if(!$service->verifyWebhook($body,$sig)){ApiResponse::forbidden('Invalid webhook signature.');exit;}$payload=json_decode($body,true);if(!is_array($payload)){ApiResponse::validation(['payload'=>'Invalid payload.']);exit;}$service->updateFromWebhook($payload);ApiResponse::success([],'Webhook processed.');}catch(Throwable $e){Logger::error('Gift webhook failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to process webhook.');}
?>
