<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->query("SELECT id, username, email, full_name, role, status, created_at, last_login FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'users' => $users]);
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = intval($data['user_id'] ?? 0);
        $status = sanitize_input($data['status'] ?? '');
        
        if (!in_array($status, ['active', 'suspended', 'deleted'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'User status updated']);
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
