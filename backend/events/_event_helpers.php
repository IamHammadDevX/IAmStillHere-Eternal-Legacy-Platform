<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../services/PrivacyService.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

function event_db(): PDO { return (new Database())->getConnection(); }

function event_can_view(PDO $db, array $event, ?int $viewerId): bool
{
    if ($viewerId !== null && ((int)$event['user_id'] === $viewerId || SessionHelper::isAdmin())) return true;
    return PrivacyService::canView($db, 'scheduled_event', (int)$event['id'], (int)$event['user_id'], $viewerId, (string)$event['privacy_level']);
}

function event_store_media(array $file, int $ownerId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['path'=>null,'mime'=>null,'type'=>null];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('The event media upload did not complete.');
    if (($file['size'] ?? 0) < 1 || (int)$file['size'] > 25 * 1024 * 1024) throw new InvalidArgumentException('Event media must be smaller than 25 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file((string)$file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','video/mp4'=>'mp4','video/webm'=>'webm','video/quicktime'=>'mov'];
    if (!isset($allowed[$mime])) throw new InvalidArgumentException('Use a JPG, PNG, WEBP, GIF, MP4, WEBM, or MOV file.');
    $type = strpos($mime, 'image/') === 0 ? 'image' : 'video';
    $relative = 'events/' . $ownerId . '/' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $directory = dirname($target);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Event media storage is unavailable.');
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) throw new RuntimeException('Unable to save event media.');
    return ['path'=>$relative,'mime'=>$mime,'type'=>$type];
}

function event_media_file(?string $relative): ?string
{
    if (!$relative || strpos($relative, 'events/') !== 0) return null;
    $base = realpath(UPLOAD_PATH . DIRECTORY_SEPARATOR . 'events');
    $path = realpath(UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    if (!$base || !$path || strpos(str_replace('\\','/',$path), rtrim(str_replace('\\','/',$base),'/') . '/') !== 0) return null;
    return $path;
}
