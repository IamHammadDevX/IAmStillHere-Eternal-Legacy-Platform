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
        SELECT fr.*, u.full_name as user_name, u.profile_photo
        FROM family_requests fr
        JOIN users u ON fr.user_id = u.id
        WHERE fr.requester_id = :requester_id
        ORDER BY fr.created_at DESC
    ");
    
    $stmt->execute(['requester_id' => $user_id]);
    $requests = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'count' => count(array_filter($requests, fn($r) => $r['status'] === 'pending'))
    ]);

} catch (Exception $e) {
    error_log("Sent requests error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>