<?php
require_once __DIR__ . '/_friend_helpers.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $db=friends_connection(); if(!SessionHelper::isAuthenticated() || !friends_require_active($db)){ApiResponse::unauthorized();exit;}
    $me=(int)SessionHelper::getUserId(); $edges=$db->query("SELECT user_id,friend_id FROM friendships WHERE status='accepted'")->fetchAll(PDO::FETCH_ASSOC); $graph=[];
    foreach($edges as $e){$a=(int)$e['user_id'];$b=(int)$e['friend_id'];$graph[$a][$b]=true;$graph[$b][$a]=true;}
    $familyRows=$db->query("SELECT user_id,family_member_id FROM family_members WHERE status='active' AND approved=1")->fetchAll(PDO::FETCH_ASSOC); $family=[];
    foreach($familyRows as $e){$a=(int)$e['user_id'];$b=(int)$e['family_member_id'];$family[$a][$b]=true;$family[$b][$a]=true;}
    $blocked=$db->query("SELECT user_id,friend_id FROM friendships WHERE status='blocked'")->fetchAll(PDO::FETCH_ASSOC); $blockedPairs=[]; foreach($blocked as $e){$blockedPairs[(int)$e['user_id'].'-'.(int)$e['friend_id']]=true;$blockedPairs[(int)$e['friend_id'].'-'.(int)$e['user_id']]=true;}
    $pending=$db->query("SELECT sender_id,receiver_id FROM friend_requests WHERE status='pending'")->fetchAll(PDO::FETCH_ASSOC); $pendingPairs=[]; foreach($pending as $e){$pendingPairs[(int)$e['sender_id'].'-'.(int)$e['receiver_id']]=true;$pendingPairs[(int)$e['receiver_id'].'-'.(int)$e['sender_id']]=true;}
    $users=$db->query("SELECT id,username,full_name,profile_photo,is_memorial FROM users WHERE status='active' AND id<>".$me)->fetchAll(PDO::FETCH_ASSOC); $myFriends=array_keys($graph[$me]??[]); $myFamily=array_keys($family[$me]??[]); $rows=[];
    foreach($users as $u){$id=(int)$u['id'];$key=$me.'-'.$id;if(isset($graph[$me][$id])||isset($blockedPairs[$key])||isset($pendingPairs[$key]))continue;$mutual=count(array_intersect($myFriends,array_keys($graph[$id]??[])));$sharedFamily=count(array_intersect($myFamily,array_keys($family[$id]??[])));$fof=$mutual>0;if(!$mutual && !$sharedFamily)continue;$reason=$mutual?'Mutual friends':($sharedFamily?'Shared family connection':'Friends of friends');$score=($mutual*100)+($sharedFamily*50)+($fof?10:0);$safe=friends_safe_user($u);$safe['reason']=$reason;$safe['connection_count']=$mutual?:$sharedFamily;$safe['_score']=$score;$rows[]=$safe;}
    usort($rows,fn($a,$b)=>$b['_score']<=>$a['_score']); $rows=array_slice($rows,0,8); foreach($rows as &$r)unset($r['_score']); unset($r); ApiResponse::success(['suggestions'=>$rows],'Suggestions loaded.');
} catch(Throwable $e){Logger::error('Friend suggestions failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to load suggestions.');}
?>
