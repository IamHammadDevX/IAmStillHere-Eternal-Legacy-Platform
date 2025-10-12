<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$title = sanitize_input($data['title'] ?? '');
$message = sanitize_input($data['message'] ?? '');
$scheduled_date = sanitize_input($data['scheduled_date'] ?? '');
$event_type = sanitize_input($data['event_type'] ?? 'message');
$privacy_level = sanitize_input($data['privacy_level'] ?? 'public');

if (empty($title) || empty($message) || empty($scheduled_date)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strtotime($scheduled_date) <= time()) {
    echo json_encode(['success' => false, 'message' => 'Scheduled date must be in the future']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO scheduled_events (user_id, title, message, scheduled_date, event_type, privacy_level) VALUES (:user_id, :title, :message, :scheduled_date, :event_type, :privacy_level)");
    
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'title' => $title,
        'message' => $message,
        'scheduled_date' => $scheduled_date,
        'event_type' => $event_type,
        'privacy_level' => $privacy_level
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Event scheduled successfully', 'event_id' => $conn->lastInsertId()]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
