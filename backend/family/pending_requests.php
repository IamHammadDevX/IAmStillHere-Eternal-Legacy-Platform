<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $user_id = intval($_GET['user_id'] ?? 0);

    if (!$user_id) {
        throw new Exception('User ID required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT fr.*, u.full_name as requester_name, u.profile_photo
        FROM family_requests fr
        JOIN users u ON fr.requester_id = u.id
        WHERE fr.user_id = :user_id AND fr.status = 'pending'
        ORDER BY fr.created_at DESC
    ");
    
    $stmt->execute(['user_id' => $user_id]);
    $requests = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'count' => count($requests)
    ]);

} catch (Exception $e) {
    error_log("Pending requests error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>