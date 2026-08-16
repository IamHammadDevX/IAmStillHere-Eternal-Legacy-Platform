<?php
require_once __DIR__ . '/_journey_helpers.php';

function journey_upload_file(array $file, string $scope): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Choose a photo or video to upload.');
    if (($file['size'] ?? 0) < 1 || (int)$file['size'] > 25 * 1024 * 1024) throw new InvalidArgumentException('Media must be smaller than 25 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file((string)$file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','video/mp4'=>'mp4','video/webm'=>'webm','video/quicktime'=>'mov'];
    if (!isset($allowed[$mime])) throw new InvalidArgumentException('Use a JPG, PNG, WEBP, GIF, MP4, WEBM, or MOV file.');
    $kind = str_starts_with($mime, 'image/') ? 'image' : 'video';
    $directory = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'journeys' . DIRECTORY_SEPARATOR . $scope;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Journey media storage is unavailable.');
    $relative = 'journeys/' . $scope . '/' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) throw new RuntimeException('Unable to save the uploaded media.');
    return ['path'=>$relative, 'mime'=>$mime, 'kind'=>$kind];
}
