<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $search_term = $_GET['q'] ?? '';

    if (empty($search_term)) {
        echo json_encode(['success' => false, 'message' => 'Search term is required']);
        exit;
    }

    $search_pattern = '%' . $search_term . '%';

    $stmt = $conn->prepare("
        SELECT id, username, full_name, email, role, is_memorial, profile_photo
        FROM users 
        WHERE (username LIKE :search OR full_name LIKE :search OR email LIKE :search)
        AND role != 'admin'
        AND status = 'active'
        LIMIT 10
    ");
    
    $stmt->execute(['search' => $search_pattern]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>