<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/folders/_folder_helpers.php';
require_once __DIR__ . '/../services/PrivacyService.php';

header('Content-Type: application/json');

$user_id = sanitize_input($_GET['user_id'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(100, intval($_GET['per_page'] ?? ITEMS_PER_PAGE)));
$offset = ($page - 1) * $limit;
$folder_id = max(0, intval($_GET['folder_id'] ?? 0));
$memory_id = max(0, intval($_GET['memory_id'] ?? 0));

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($folder_id > 0) {
        $folderStmt = $conn->prepare('SELECT * FROM memory_folders WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $folderStmt->execute(['id' => $folder_id]);
        $folder = $folderStmt->fetch(PDO::FETCH_ASSOC);
        if (!$folder || (int) $folder['user_id'] !== (int) $user_id || !folder_can_view($conn, $folder, SessionHelper::getUserId())) {
            echo json_encode(['success' => false, 'message' => 'Folder not found or not visible']); exit;
        }
    }

    // PrivacyService is the single source of truth. Keep only the public SQL
    // shortcut for guests; authenticated viewers must reach canView() so
    // friends, family, specific people, release rules, and blocks are handled
    // consistently for every media type.
    $privacy_conditions = is_logged_in() ? '1=1' : "privacy_level = 'public'";
    
    $stmt = $conn->prepare("SELECT * FROM memories WHERE user_id = :user_id AND status = 'active' AND $privacy_conditions AND (:folder_id = 0 OR folder_id = :folder_id) AND (:memory_id = 0 OR id = :memory_id) ORDER BY upload_date DESC");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':folder_id', $folder_id, PDO::PARAM_INT);
    $stmt->bindValue(':memory_id', $memory_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $visibleMemories = array_values(array_filter($stmt->fetchAll(), static function (array $memory) use ($conn) { return PrivacyService::canView($conn, 'memory', (int) $memory['id'], (int) $memory['user_id'], SessionHelper::getUserId(), (string) $memory['privacy_level'], isset($memory['folder_id']) ? (int) $memory['folder_id'] : null); }));
    $total = count($visibleMemories);
    $memories = array_slice($visibleMemories, $offset, $limit);
    
    echo json_encode([
        'success' => true,
        'memories' => $memories,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total
        ]
    ]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
