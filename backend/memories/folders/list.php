<?php
require_once __DIR__ . '/_folder_helpers.php';

$ownerId = (int) ($_GET['user_id'] ?? 0);
$viewerId = SessionHelper::getUserId();
if ($ownerId <= 0) { ApiResponse::validation(['user_id' => 'User ID is required']); exit; }

try {
    $db = folder_connection();
    $search = trim((string) ($_GET['search'] ?? ''));
    $sortMap = ['name' => 'f.name', 'created_at' => 'f.created_at', 'updated_at' => 'f.updated_at', 'memory_count' => 'memory_count'];
    $sort = $sortMap[$_GET['sort'] ?? 'updated_at'] ?? 'f.updated_at';
    $direction = strtolower((string) ($_GET['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
    $stmt = $db->prepare("SELECT f.*, COUNT(m.id) AS memory_count
        FROM memory_folders f LEFT JOIN memories m ON m.folder_id = f.id AND m.status = 'active'
        WHERE f.user_id = :owner AND f.deleted_at IS NULL AND f.name LIKE :search
        GROUP BY f.id ORDER BY {$sort} {$direction}, f.id ASC");
    $stmt->execute(['owner' => $ownerId, 'search' => '%' . $search . '%']);
    $folders = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $folder) {
        if (folder_can_view($db, $folder, $viewerId)) {
            $folder['id'] = (int) $folder['id'];
            $folder['parent_folder_id'] = $folder['parent_folder_id'] === null ? null : (int) $folder['parent_folder_id'];
            $folder['memory_count'] = (int) $folder['memory_count'];
            $folders[] = $folder;
        }
    }
    ApiResponse::success(['folders' => $folders]);
} catch (Throwable $e) { error_log('Folder list error: ' . $e->getMessage()); ApiResponse::serverError('Unable to load folders'); }
