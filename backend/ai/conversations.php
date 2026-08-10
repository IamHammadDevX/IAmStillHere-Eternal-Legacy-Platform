<?php
require_once __DIR__ . '/_avatar_helpers.php';

try {
    if (!ai_method('GET')) exit;
    $db = ai_db();
    $viewer = ai_require_user($db);
    if ($viewer === null) exit;
    $owner = (int) ($_GET['owner_id'] ?? 0);
    $limit = max(1, min(50, (int) ($_GET['limit'] ?? 20)));
    if ($owner <= 0) { ApiResponse::validation(['owner_id' => 'Valid owner_id is required.']); exit; }
    ApiResponse::success(['conversations' => ai_avatar_service($db)->listConversations($owner, $viewer, $limit)], 'Conversations loaded.');
} catch (Throwable $e) {
    ai_avatar_safe_error($e, 'conversations');
}
