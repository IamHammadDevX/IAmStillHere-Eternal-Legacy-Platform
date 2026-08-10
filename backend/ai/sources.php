<?php
require_once __DIR__ . '/_ai_helpers.php';

try {
    if (!ai_method('GET')) exit;
    $db = ai_db();
    $user = ai_require_user($db);
    if ($user === null) exit;

    $status = [];
    foreach ((new AIKnowledgeService($db))->sourceStatus($user) as $row) {
        $status[$row['resource_type'] . ':' . (int) $row['resource_id']] = $row;
    }

    $items = [];
    $profile = $db->prepare("SELECT id,bio,updated_at FROM users WHERE id=:id AND status='active' LIMIT 1");
    $profile->execute(['id' => $user]);
    $profileRow = $profile->fetch(PDO::FETCH_ASSOC);
    if ($profileRow && trim((string) $profileRow['bio']) !== '') {
        $items[] = ['resource_type' => 'profile', 'resource_id' => $user, 'title' => 'Profile bio', 'source_date' => $profileRow['updated_at']];
    }

    $queries = [
        'memory' => "SELECT id AS resource_id,title,memory_date AS source_date FROM memories WHERE user_id=:user AND status='active' ORDER BY upload_date DESC,id DESC LIMIT 50",
        'milestone' => "SELECT id AS resource_id,title,milestone_date AS source_date FROM milestones WHERE user_id=:user AND status='active' ORDER BY milestone_date DESC,id DESC LIMIT 50",
        'post' => "SELECT id AS resource_id,LEFT(body,80) AS title,created_at AS source_date FROM posts WHERE user_id=:user AND status='active' AND deleted_at IS NULL ORDER BY created_at DESC,id DESC LIMIT 50",
        'journey' => "SELECT id AS resource_id,title,start_date AS source_date FROM journeys WHERE owner_id=:user AND status='published' AND deleted_at IS NULL ORDER BY created_at DESC,id DESC LIMIT 50",
    ];

    foreach ($queries as $type => $sql) {
        $s = $db->prepare($sql);
        $s->execute(['user' => $user]);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = ['resource_type' => $type, 'resource_id' => (int) $row['resource_id'], 'title' => $row['title'] ?: ucfirst($type), 'source_date' => $row['source_date']];
        }
    }

    foreach ($items as &$item) {
        $key = $item['resource_type'] . ':' . (int) $item['resource_id'];
        $item['ingestion_status'] = $status[$key]['ingestion_status'] ?? 'not_enabled';
        $item['ai_enabled'] = isset($status[$key]) ? (int) $status[$key]['ai_enabled'] === 1 : false;
        $item['last_error_code'] = $status[$key]['last_error_code'] ?? null;
    }

    ApiResponse::success(['sources' => $items], 'AI sources loaded.');
} catch (Throwable $e) {
    ai_safe_error($e, 'sources');
}
