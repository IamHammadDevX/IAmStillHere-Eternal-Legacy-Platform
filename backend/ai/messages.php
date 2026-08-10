<?php
require_once __DIR__ . '/_avatar_helpers.php';

try {
    if (!ai_method('GET')) exit;
    $db = ai_db();
    $viewer = ai_require_user($db);
    if ($viewer === null) exit;
    $conversationId = (int) ($_GET['conversation_id'] ?? 0);
    if ($conversationId <= 0) { ApiResponse::validation(['conversation_id' => 'Valid conversation_id is required.']); exit; }
    ApiResponse::success(['messages' => ai_avatar_service($db)->messages($conversationId, $viewer)], 'Messages loaded.');
} catch (Throwable $e) {
    ai_avatar_safe_error($e, 'messages');
}
