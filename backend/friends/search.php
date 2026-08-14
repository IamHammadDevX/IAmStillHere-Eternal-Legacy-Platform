<?php
require_once __DIR__ . '/_friend_helpers.php';
try {
 if ($_SERVER['REQUEST_METHOD'] !== 'GET') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
 $db=friends_connection(); if(!SessionHelper::isAuthenticated() || !friends_require_active($db)){ApiResponse::unauthorized();exit;}
 $me=(int)SessionHelper::getUserId(); $q=trim((string)($_GET['q']??'')); if(mb_strlen($q)<2){ApiResponse::validation(['q'=>'Enter at least 2 characters.']);exit;} $like='%'.$q.'%';
 $st=$db->prepare("SELECT id,username,email,full_name,profile_photo,is_memorial FROM users WHERE status='active' AND id<>:me AND (username LIKE :a OR email LIKE :b OR full_name LIKE :c) ORDER BY username LIMIT 20"); $st->execute(['me'=>$me,'a'=>$like,'b'=>$like,'c'=>$like]); $out=[]; while($u=$st->fetch(PDO::FETCH_ASSOC)){ $x=friends_safe_user($u); $x['relationship']=friends_status($db,$me,(int)$u['id']); $out[]=$x; } ApiResponse::success(['users'=>$out]);
} catch(Throwable $e){Logger::error('Friend search failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to search users.');}
?>
