<?php
require_once __DIR__ . '/_friend_helpers.php';
try { $c=friends_connection(); if(!SessionHelper::isAuthenticated()){ApiResponse::unauthorized();exit;} $target=(int)($_GET['user_id']??0); if($target<=0||!friends_active($c,$target)){ApiResponse::notFound('User not found.');exit;} ApiResponse::success(friends_status($c,SessionHelper::getUserId(),$target),'Friend status loaded.'); } catch(Throwable $e){Logger::error('Friend status failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to load friend status.');}
?>
