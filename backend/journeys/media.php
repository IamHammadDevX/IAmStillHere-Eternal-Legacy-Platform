<?php
require_once __DIR__ . '/_journey_helpers.php';
try {
    $db=journeys_db(); $itemId=(int)($_GET['item_id']??0); $q=$db->prepare('SELECT ji.*, j.owner_id FROM journey_items ji INNER JOIN journeys j ON j.id=ji.journey_id WHERE ji.id=:id AND ji.deleted_at IS NULL LIMIT 1'); $q->execute(['id'=>$itemId]); $item=$q->fetch(PDO::FETCH_ASSOC);
    $journey=$item?journeys_find($db,(int)$item['journey_id']):null; $viewer=journeys_user_id();
    if (!$item || !$journey || !$item['media_path'] || !journeys_can_view($db,$journey,$viewer) || ($item['status']!=='approved' && !journeys_can_manage($db,$journey,(int)($viewer??0)))) { http_response_code(404); exit; }
    $file=UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $item['media_path']); if (!is_file($file)) { http_response_code(404); exit; }
    header('Content-Type: '.($item['media_mime']?:'application/octet-stream')); header('Content-Length: '.filesize($file)); header('X-Content-Type-Options: nosniff'); readfile($file);
} catch (Throwable $e) { http_response_code(404); }
