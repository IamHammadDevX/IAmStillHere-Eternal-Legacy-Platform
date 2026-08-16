<?php
require_once __DIR__ . '/_event_helpers.php';
try {
    $eventId = (int)($_GET['event_id'] ?? 0);
    $db = event_db();
    $statement = $db->prepare("SELECT * FROM scheduled_events WHERE id=:id AND status IN ('scheduled','published','cancelled') LIMIT 1");
    $statement->execute(['id'=>$eventId]);
    $event = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$event || empty($event['media_path']) || !event_can_view($db, $event, SessionHelper::getUserId())) { http_response_code(404); exit; }
    $file = event_media_file((string)$event['media_path']);
    if (!$file || !is_file($file)) { http_response_code(404); exit; }
    header('Content-Type: ' . ($event['media_mime'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: inline; filename="event-media-' . $eventId . '"');
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($file);
} catch (Throwable $error) { http_response_code(404); }
