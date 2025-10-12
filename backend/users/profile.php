<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$user_id = sanitize_input($_GET['user_id'] ?? '');

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, username, full_name, email, bio, date_of_birth, date_of_passing, profile_photo, cover_photo, is_memorial, created_at FROM users WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $user_id]);
    $profile = $stmt->fetch();
    
    if ($profile) {
        echo json_encode(['success' => true, 'profile' => $profile]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
