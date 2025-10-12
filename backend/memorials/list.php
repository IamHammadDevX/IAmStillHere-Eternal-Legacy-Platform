<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, u.date_of_birth, u.date_of_passing, u.bio, u.profile_photo, u.cover_photo
        FROM users u
        WHERE u.is_memorial = true 
        AND u.status = 'active'
        AND EXISTS (
            SELECT 1 FROM (
                SELECT user_id FROM memories WHERE user_id = u.id AND privacy_level = 'public' AND status = 'active'
                UNION
                SELECT user_id FROM milestones WHERE user_id = u.id AND privacy_level = 'public' AND status = 'active'
            ) AS public_content
        )
        ORDER BY u.created_at DESC
    ");
    
    $stmt->execute();
    $memorials = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'memorials' => $memorials]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
