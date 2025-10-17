<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $user_id = intval($data['user_id'] ?? 0);
    $family_member_id = intval($data['family_member_id'] ?? 0);
    $relationship = sanitize_input($data['relationship'] ?? '');

    if (!$user_id || !$family_member_id || empty($relationship)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Prevent duplicate entries
    $check = $conn->prepare("SELECT id FROM family_members WHERE user_id = :user_id AND family_member_id = :family_member_id");
    $check->execute(['user_id' => $user_id, 'family_member_id' => $family_member_id]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Family member already exists']);
        exit;
    }

    // Insert new member
    $stmt = $conn->prepare("
        INSERT INTO family_members (user_id, family_member_id, relationship, status)
        VALUES (:user_id, :family_member_id, :relationship, 'active')
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'family_member_id' => $family_member_id,
        'relationship' => $relationship
    ]);

    echo json_encode(['success' => true, 'message' => 'Family member added successfully']);
} catch (Exception $e) {
    error_log("❌ Family Add Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
