<?php
require_once __DIR__ . '/_helpers.php';

try {
    if (!ai_method('GET')) exit;
    $db = ai_db();
    $viewer = ai_require_user($db);
    if ($viewer === null) exit;
    $owner = (int) ($_GET['owner_id'] ?? 0);
    if ($owner <= 0) { ApiResponse::validation(['owner_id' => 'Owner is required.']); exit; }
    ApiResponse::success(ai_autobio_service($db)->view($owner, $viewer), 'Autobiography loaded.');
} catch (Throwable $e) {
    ai_autobio_safe_error($e, 'view');
}
