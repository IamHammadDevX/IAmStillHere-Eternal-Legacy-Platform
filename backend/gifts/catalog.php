<?php
require_once __DIR__ . '/_gift_helpers.php';
try{$db=gifts_db();$user=gifts_user();$catalog=gifts_service($db)->catalog();ApiResponse::success(['catalog'=>$catalog],'Gift catalog loaded.');}catch(Throwable $e){Logger::error('Gift catalog failed',['error'=>$e->getMessage()]);ApiResponse::serverError($e->getMessage()==='gift_provider_not_configured'?'Gift provider not configured.':'Unable to load gift catalog.');}
?>
