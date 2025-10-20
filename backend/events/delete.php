<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $event_id = intval($data['event_id'] ?? 0);

    if (!$event_id) {
        throw new Exception('Event ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get event details
    $stmt = $conn->prepare("SELECT user_id FROM scheduled_events WHERE id = :id");
    $stmt->execute(['id' => $event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        throw new Exception('Event not found');
    }

    // Check permissions
    $isOwner = ($event['user_id'] == $_SESSION['user_id']);
    $isAdmin = ($_SESSION['user_role'] === 'admin');

    if (!$isOwner && !$isAdmin) {
        throw new Exception('You do not have permission to delete this event');
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM scheduled_events WHERE id = :id");
    $stmt->execute(['id' => $event_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Event deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete event error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>