<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../services/PrivacyService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed', [], 405); exit; }
if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$data = is_array($data) ? $data : [];
if (!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))) { ApiResponse::forbidden('Invalid CSRF token'); exit; }

$id = (int) ($data['memory_id'] ?? 0);
$title = trim((string) ($data['title'] ?? ''));
$description = trim((string) ($data['description'] ?? ''));
$memoryDate = trim((string) ($data['memory_date'] ?? ''));
$privacy = (string) ($data['privacy_level'] ?? 'public');
$allowed = ['public','family','friends','specific_people','private','release_date','release_event'];
if ($id <= 0 || $title === '' || mb_strlen($title) > 255 || mb_strlen($description) > 10000) { ApiResponse::validation(['memory' => 'Valid memory ID, title, and description are required.']); exit; }
if ($memoryDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $memoryDate)) { ApiResponse::validation(['memory_date' => 'Invalid memory date.']); exit; }
if (!in_array($privacy, $allowed, true)) { ApiResponse::validation(['privacy_level' => 'Invalid privacy level.']); exit; }

try {
    $db = (new Database())->getConnection();
    $s = $db->prepare('SELECT id,user_id,status FROM memories WHERE id=:id LIMIT 1');
    $s->execute(['id' => $id]);
    $memory = $s->fetch(PDO::FETCH_ASSOC);
    $viewer = SessionHelper::getUserId();
    if (!$memory || $memory['status'] !== 'active') { ApiResponse::notFound('Memory not found.'); exit; }
    if ((int) $memory['user_id'] !== $viewer && !SessionHelper::isAdmin()) { ApiResponse::forbidden('Only the owner can edit this memory.'); exit; }

    $folderId = (int) ($data['folder_id'] ?? 0);
    if ($folderId > 0) {
        $folder = $db->prepare('SELECT id FROM memory_folders WHERE id=:id AND user_id=:user_id AND deleted_at IS NULL LIMIT 1');
        $folder->execute(['id' => $folderId, 'user_id' => $memory['user_id']]);
        if (!$folder->fetchColumn()) { ApiResponse::validation(['folder_id' => 'Folder not found.']); exit; }
    }
    $legacy = in_array($privacy, ['public','family','private'], true) ? $privacy : 'private';
    $update = $db->prepare('UPDATE memories SET title=:title, description=:description, memory_date=:memory_date, folder_id=:folder_id, privacy_level=:privacy_level, privacy_override=1 WHERE id=:id');
    $update->execute(['title'=>$title,'description'=>$description ?: null,'memory_date'=>$memoryDate ?: null,'folder_id'=>$folderId ?: null,'privacy_level'=>$legacy,'id'=>$id]);
    ApiResponse::success(['memory_id'=>$id], 'Memory updated.');
} catch (Throwable $e) { error_log('Memory update error: '.$e->getMessage()); ApiResponse::serverError('Unable to update memory.'); }
