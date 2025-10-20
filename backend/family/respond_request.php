<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $request_id = intval($data['request_id'] ?? 0);
    $action = sanitize_input($data['action'] ?? '');

    if (!$request_id || !in_array($action, ['accept', 'reject'])) {
        throw new Exception('Invalid parameters');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get request details
    $stmt = $conn->prepare("
        SELECT fr.*, u1.full_name as requester_name, u2.full_name as user_name
        FROM family_requests fr
        JOIN users u1 ON fr.requester_id = u1.id
        JOIN users u2 ON fr.user_id = u2.id
        WHERE fr.id = :id AND fr.status = 'pending'
    ");
    $stmt->execute(['id' => $request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception('Request not found or already processed');
    }

    if ($action === 'accept') {
        // Add to family_members table (both directions)
        $stmt = $conn->prepare("
            INSERT INTO family_members (user_id, family_member_id, relationship, status, approved)
            VALUES (:user1, :user2, :rel, 'active', 1)
            ON DUPLICATE KEY UPDATE status = 'active', approved = 1
        ");
        
        // Add requester to user's family
        $stmt->execute([
            'user1' => $request['user_id'],
            'user2' => $request['requester_id'],
            'rel' => $request['relationship']
        ]);

        // Add user to requester's family (reverse relationship)
        $reverseRel = getReverseRelationship($request['relationship']);
        $stmt->execute([
            'user1' => $request['requester_id'],
            'user2' => $request['user_id'],
            'rel' => $reverseRel
        ]);

        // Update request status
        $stmt = $conn->prepare("
            UPDATE family_requests 
            SET status = 'accepted', responded_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $request_id]);

        echo json_encode([
            'success' => true,
            'message' => "You and {$request['requester_name']} are now family members!"
        ]);

    } else {
        // Reject request
        $stmt = $conn->prepare("
            UPDATE family_requests 
            SET status = 'rejected', responded_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $request_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Family request declined'
        ]);
    }

} catch (Exception $e) {
    error_log("Respond request error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getReverseRelationship($relationship) {
    $reverseMap = [
        'Father' => 'Son/Daughter',
        'Mother' => 'Son/Daughter',
        'Son' => 'Father/Mother',
        'Daughter' => 'Father/Mother',
        'Brother' => 'Brother/Sister',
        'Sister' => 'Brother/Sister',
        'Grandfather' => 'Grandson/Granddaughter',
        'Grandmother' => 'Grandson/Granddaughter',
        'Grandson' => 'Grandfather/Grandmother',
        'Granddaughter' => 'Grandfather/Grandmother',
        'Uncle' => 'Nephew/Niece',
        'Aunt' => 'Nephew/Niece',
        'Nephew' => 'Uncle/Aunt',
        'Niece' => 'Uncle/Aunt',
        'Friend' => 'Friend'
    ];

    return $reverseMap[$relationship] ?? 'Family';
}
?>