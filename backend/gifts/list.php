<?php
require_once __DIR__ . '/_gift_helpers.php';
try{$db=gifts_db();$user=gifts_user();$admin=SessionHelper::isAdmin() && isset($_GET['admin']);$orders=gifts_service($db)->list($user,$admin);ApiResponse::success(['orders'=>$orders],'Gift orders loaded.');}catch(Throwable $e){Logger::error('Gift list failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to load gift orders.');}
?>
