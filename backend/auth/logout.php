<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (:user_id, 'logout', :ip)");
        $logStmt->execute([
            'user_id' => $user_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

session_destroy();

echo json_encode(['success' => true, 'message' => 'Logout successful']);
