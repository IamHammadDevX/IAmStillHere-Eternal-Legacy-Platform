<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/RequestContext.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

function friends_connection(): PDO { $db = new Database(); return $db->getConnection(); }
function friends_input(): array { $d=json_decode(file_get_contents('php://input'), true); return is_array($d)?$d:[]; }
function friends_csrf(array $data): bool { return CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data)); }

function friends_active(PDO $conn, int $id): bool {
    $s=$conn->prepare("SELECT id FROM users WHERE id=:id AND status='active' LIMIT 1");
    $s->execute(['id'=>$id]); return (bool)$s->fetch();
}
function friends_require_active(PDO $conn): bool { $id=SessionHelper::getUserId(); return $id!==null && friends_active($conn,$id); }

function friends_are_blocked(PDO $conn, int $a, int $b): bool {
    $s=$conn->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='blocked' LIMIT 1");
    $s->execute(['a'=>$a,'b'=>$b]); return (bool)$s->fetch();
}
function friends_are_friends(PDO $conn, int $a, int $b): bool {
    $s=$conn->prepare("SELECT id FROM friendships WHERE user_id=:a AND friend_id=:b AND status='accepted' LIMIT 1");
    $s->execute(['a'=>$a,'b'=>$b]); return (bool)$s->fetch();
}
function friends_pending(PDO $conn, int $a, int $b): ?array {
    $s=$conn->prepare("SELECT * FROM friend_requests WHERE ((sender_id=:a AND receiver_id=:b) OR (sender_id=:b AND receiver_id=:a)) AND status='pending' ORDER BY id DESC LIMIT 1");
    $s->execute(['a'=>$a,'b'=>$b]); $r=$s->fetch(PDO::FETCH_ASSOC); return $r?:null;
}
function friends_status(PDO $conn, int $viewer, int $target): array {
    if ($viewer === $target) return ['state'=>'self'];
    if (friends_are_blocked($conn,$viewer,$target)) return ['state'=>'blocked'];
    if (friends_are_friends($conn,$viewer,$target)) return ['state'=>'friends'];
    $p=friends_pending($conn,$viewer,$target);
    if ($p) return ['state'=>((int)$p['sender_id']===$viewer?'pending_sent':'pending_received'), 'request_id'=>(int)$p['id']];
    return ['state'=>'none'];
}
function friends_safe_user(array $u): array {
    return [
        'id'=>(int)$u['id'],
        'username'=>$u['username'] ?? '',
        'full_name'=>$u['full_name'] ?? '',
        'profile_photo'=>$u['profile_photo'] ?? null,
        'is_memorial'=>(int)($u['is_memorial'] ?? 0)
    ];
}
function friends_can_view_profile(PDO $conn, int $profileUserId): bool {
    $viewer=SessionHelper::getUserId(); if($viewer===null) return false;
    if($viewer===$profileUserId || SessionHelper::isAdmin()) return true;
    if(friends_are_blocked($conn,$viewer,$profileUserId)) return false;
    if(friends_are_friends($conn,$viewer,$profileUserId)) return true;
    $s=$conn->prepare("SELECT id FROM family_members WHERE user_id=:owner AND family_member_id=:viewer AND status='active' AND approved=1 LIMIT 1");
    $s->execute(['owner'=>$profileUserId,'viewer'=>$viewer]); return (bool)$s->fetch();
}
?>
