<?php
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../posts/_post_helpers.php';
try {
    if (!ai_method('GET')) exit;
    $db = ai_db();
    $viewer = ai_require_user($db);
    if ($viewer === null) exit;
    $owner = (int)($_GET['owner_id'] ?? 0);
    if ($owner <= 0) { ApiResponse::validation(['owner_id' => 'Valid profile owner is required.']); exit; }
    if (!posts_can_view_profile($db, $owner)) { ApiResponse::forbidden('You are not allowed to view these scheduled messages.'); exit; }
    $service = ai_pm_service($db);
    $messages = $viewer === $owner ? $service->list($owner) : $service->listScheduledForViewer($owner);
    ApiResponse::success(['messages' => $messages, 'can_manage' => $viewer === $owner], 'Messages loaded.');
} catch (Throwable $e) { ai_pm_error($e, 'list'); }
