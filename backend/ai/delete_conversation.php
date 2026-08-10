<?php
require_once __DIR__ . '/_avatar_helpers.php';

try {
    if (!ai_method('POST')) exit;
    $db = ai_db();
    $viewer = ai_require_user($db);
    if ($viewer === null) exit;
    $data = ai_input();
    if (!ai_require_csrf($data)) exit;
    $conversationId = (int) ($data['conversation_id'] ?? 0);
    $ownerId = (int) ($data['owner_id'] ?? 0);
    if ($conversationId <= 0 && $ownerId <= 0) { ApiResponse::validation(['conversation_id' => 'Valid conversation_id or owner_id is required.']); exit; }
    if ($conversationId > 0) {
        ai_avatar_service($db)->deleteConversation($conversationId, $viewer);
    } else {
        ai_avatar_service($db)->deleteConversationsForOwner($ownerId, $viewer);
    }
    ApiResponse::success([], 'Conversation deleted.');
} catch (Throwable $e) {
    ai_avatar_safe_error($e, 'delete_conversation');
}