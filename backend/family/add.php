<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';
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

    // Can't add yourself
    if ($user_id === $family_member_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot add yourself as a family member']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if already family members
    $check = $conn->prepare("SELECT id FROM family_members WHERE user_id = :user_id AND family_member_id = :family_member_id AND status = 'active'");
    $check->execute(['user_id' => $user_id, 'family_member_id' => $family_member_id]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already family members']);
        exit;
    }

    // Check if request already exists
    $checkRequest = $conn->prepare("SELECT id, status FROM family_requests WHERE user_id = :user_id AND requester_id = :requester_id");
    $checkRequest->execute(['user_id' => $family_member_id, 'requester_id' => $user_id]);
    $existingRequest = $checkRequest->fetch();

    if ($existingRequest) {
        if ($existingRequest['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'Request already sent and pending approval']);
            exit;
        } else {
            // Delete old rejected request
            $deleteOld = $conn->prepare("DELETE FROM family_requests WHERE id = :id");
            $deleteOld->execute(['id' => $existingRequest['id']]);
        }
    }

    // Get family member details
    $stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = :id");
    $stmt->execute(['id' => $family_member_id]);
    $familyMember = $stmt->fetch();

    if (!$familyMember) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Get requester details
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $requester = $stmt->fetch();

    // Create pending request
    $stmt = $conn->prepare("
        INSERT INTO family_requests (user_id, requester_id, relationship, status)
        VALUES (:user_id, :requester_id, :relationship, 'pending')
    ");
    
    $stmt->execute([
        'user_id' => $family_member_id,
        'requester_id' => $user_id,
        'relationship' => $relationship
    ]);

    $request_id = $conn->lastInsertId();

    // Send email notification
    $emailSent = EmailHelper::sendFamilyRequestEmail(
        $familyMember['email'],
        $familyMember['full_name'],
        $requester['full_name'],
        $relationship,
        $request_id
    );

    if (!$emailSent) {
        error_log("Failed to send family request email to: " . $familyMember['email']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Family request sent! Waiting for approval.',
        'email_sent' => $emailSent
    ]);

} catch (Exception $e) {
    error_log("Family Add Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>