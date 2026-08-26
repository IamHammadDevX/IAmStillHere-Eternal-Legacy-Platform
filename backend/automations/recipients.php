<?php
require_once __DIR__ . '/_automation_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ApiResponse::send(false, [], 'Method not allowed.', [], 405);
        exit;
    }

    $db = automation_db();
    $owner = automation_require_auth();
    $query = automation_clean((string)($_GET['q'] ?? ''), 100);
    if (mb_strlen($query) < 2 && !ctype_digit($query)) {
        ApiResponse::validation(['q' => 'Enter at least 2 characters.']);
        exit;
    }

    $pattern = '%' . $query . '%';
    $exactId = ctype_digit($query) ? (int)$query : 0;
    $statement = $db->prepare(
        "SELECT u.id,u.username,u.full_name,u.profile_photo
         FROM users u
         WHERE u.status='active'
           AND u.role<>'admin'
           AND (u.id=:exact_id OR u.username LIKE :username_search OR u.full_name LIKE :name_search OR u.email LIKE :email_search)
           AND NOT EXISTS (
               SELECT 1 FROM friendships f
               WHERE ((f.user_id=:owner_one AND f.friend_id=u.id) OR (f.user_id=u.id AND f.friend_id=:owner_two))
                 AND f.status='blocked'
           )
         ORDER BY (u.id=:order_id) DESC,u.full_name ASC,u.username ASC
         LIMIT 10"
    );
    $statement->execute([
        'exact_id'=>$exactId,
        'username_search'=>$pattern,
        'name_search'=>$pattern,
        'email_search'=>$pattern,
        'owner_one'=>$owner,
        'owner_two'=>$owner,
        'order_id'=>$exactId,
    ]);
    $users=[];
    foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $user){
        $users[]=[
            'id'=>(int)$user['id'],
            'username'=>(string)$user['username'],
            'full_name'=>(string)$user['full_name'],
            'profile_photo'=>!empty($user['profile_photo'])?'/data/uploads/photos/'.rawurlencode($user['profile_photo']):'/frontend/images/default-profile.png',
        ];
    }
    ApiResponse::success(['users'=>$users],'Recipients loaded.');
} catch(Throwable $e) {
    Logger::error('Automation recipient search failed',['error'=>$e->getMessage()]);
    ApiResponse::serverError('Unable to search recipients.');
}
