<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$user_id = sanitize_input($_GET['user_id'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $privacy_conditions = "privacy_level = 'public'";
    
    if (is_logged_in()) {
        if ($_SESSION['user_id'] == $user_id) {
            $privacy_conditions = "1=1";
        } elseif (is_admin()) {
            $privacy_conditions = "1=1";
        } else {
            $familyCheck = $conn->prepare("SELECT id FROM family_members WHERE user_id = :owner_id AND family_member_id = :viewer_id AND status = 'active'");
            $familyCheck->execute(['owner_id' => $user_id, 'viewer_id' => $_SESSION['user_id']]);
            if ($familyCheck->fetch()) {
                $privacy_conditions = "privacy_level IN ('public', 'family')";
            }
        }
    }
    
    $stmt = $conn->prepare("SELECT * FROM memories WHERE user_id = :user_id AND status = 'active' AND $privacy_conditions ORDER BY upload_date DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $memories = $stmt->fetchAll();
    
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM memories WHERE user_id = :user_id AND status = 'active' AND $privacy_conditions");
    $countStmt->execute(['user_id' => $user_id]);
    $total = $countStmt->fetch()['total'];
    
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
