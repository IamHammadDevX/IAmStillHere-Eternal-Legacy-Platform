<?php
require_once __DIR__ . '/_journey_helpers.php';
try {
    $db=journeys_db(); $id=(int)($_GET['journey_id']??0); $journey=$id?journeys_find($db,$id):null;
    if (!$journey || !$journey['cover_image'] || !journeys_can_view($db,$journey,journeys_user_id())) { http_response_code(404); exit; }
    $file=UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $journey['cover_image']); if (!is_file($file)) { http_response_code(404); exit; }
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream'; header('Content-Type: '.$mime); header('Content-Length: '.filesize($file)); header('X-Content-Type-Options: nosniff'); readfile($file);
} catch (Throwable $e) { http_response_code(404); }
