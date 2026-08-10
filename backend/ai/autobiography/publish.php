<?php
require_once __DIR__ . '/_helpers.php';

try {
    if (!ai_method('POST')) exit;
    $db = ai_db();
    $owner = ai_require_user($db);
    if ($owner === null) exit;
    $data = ai_input();
    if (!ai_require_csrf($data)) exit;
    ApiResponse::success(ai_autobio_service($db)->publish($owner, (bool) ($data['publish'] ?? false)), 'Publish state updated.');
} catch (Throwable $e) {
    ai_autobio_safe_error($e, 'publish');
}
