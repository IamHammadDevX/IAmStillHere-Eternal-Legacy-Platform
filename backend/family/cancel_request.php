<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $request_id = intval($data['request_id'] ?? 0);

    if (!$request_id) {
        throw new Exception('Request ID required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Delete the request
    $stmt = $conn->prepare("DELETE FROM family_requests WHERE id = :id AND status = 'pending'");
    $stmt->execute(['id' => $request_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Request cancelled']);
    } else {
        throw new Exception('Request not found or already processed');
    }

} catch (Exception $e) {
    error_log("Cancel request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>