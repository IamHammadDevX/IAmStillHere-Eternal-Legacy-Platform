<?php
require_once __DIR__ . '/_helpers.php';

try {
    if (!ai_method('POST')) exit;
    $db = ai_db();
    $owner = ai_require_user($db);
    if ($owner === null) exit;
    $data = ai_input();
    if (!ai_require_csrf($data)) exit;
    $title = (string) ($data['title'] ?? '');
    $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
    ApiResponse::success(ai_autobio_service($db)->save($owner, $title, $sections), 'Draft saved.');
} catch (InvalidArgumentException $e) {
    ApiResponse::validation(['input' => $e->getMessage()]);
} catch (Throwable $e) {
    ai_autobio_safe_error($e, 'save');
}
