<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "
        SELECT 
            u.id, 
            u.full_name, 
            u.date_of_birth, 
            u.date_of_passing, 
            u.bio, 
            u.profile_photo, 
            u.cover_photo
        FROM users u
        WHERE u.is_memorial = 1
          AND u.status = 'active'
          AND (
              EXISTS (
                  SELECT 1 FROM memories 
                  WHERE memories.user_id = u.id 
                    AND memories.privacy_level = 'public'
                    AND memories.status = 'active'
              )
              OR EXISTS (
                  SELECT 1 FROM milestones 
                  WHERE milestones.user_id = u.id 
                    AND milestones.privacy_level = 'public'
                    AND milestones.status = 'active'
              )
          )
        ORDER BY u.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $memorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'memorials' => $memorials]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
