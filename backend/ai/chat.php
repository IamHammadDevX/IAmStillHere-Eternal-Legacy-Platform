<?php
require_once __DIR__ . '/_avatar_helpers.php';

try {
    if (!ai_method('POST')) exit;
    $db = ai_db();
    $viewer = ai_require_user($db);
    if ($viewer === null) exit;
    $data = ai_input();
    if (!ai_require_csrf($data)) exit;
    $owner = (int) ($data['owner_id'] ?? 0);
    $question = (string) ($data['question'] ?? '');
    $conversationId = isset($data['conversation_id']) ? (int) $data['conversation_id'] : null;
    if ($owner <= 0 || trim($question) === '') { ApiResponse::validation(['question' => 'Question is required.']); exit; }
    $result = ai_avatar_service($db)->ask($owner, $viewer, $question, $conversationId);
    ApiResponse::success($result, 'Answer ready.');
} catch (InvalidArgumentException $e) {
    ApiResponse::validation(['question' => $e->getMessage()]);
} catch (Throwable $e) {
    ai_avatar_safe_error($e, 'chat');
}
