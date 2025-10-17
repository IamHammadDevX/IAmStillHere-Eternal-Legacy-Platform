<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $user_id = intval($_GET['user_id'] ?? 0);

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT fm.id, fm.user_id, fm.family_member_id, fm.relationship, fm.added_at, fm.status,
               u.full_name AS member_name, u.profile_photo AS member_picture
        FROM family_members fm
        JOIN users u ON fm.family_member_id = u.id
        WHERE fm.user_id = :user_id AND fm.status = 'active'
        ORDER BY fm.added_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $members = $stmt->fetchAll();

    echo json_encode(['success' => true, 'members' => $members]);
} catch (Exception $e) {
    error_log("❌ Family Find Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
?>
