<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = intval($data['user_id'] ?? 0);
    $family_member_id = intval($data['family_member_id'] ?? 0);

    if (!$user_id || !$family_member_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        UPDATE family_members 
        SET status = 'removed'
        WHERE user_id = :user_id AND family_member_id = :family_member_id
    ");
    $stmt->execute(['user_id' => $user_id, 'family_member_id' => $family_member_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Family member removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No matching active member found']);
    }
} catch (Exception $e) {
    error_log("❌ Family Remove Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
