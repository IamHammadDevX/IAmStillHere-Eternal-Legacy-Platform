<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Pending notifications (next hour)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM scheduled_events 
        WHERE status = 'scheduled'
        AND scheduled_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
        AND (notified = 0 OR notified IS NULL)
    ");
    $stmt->execute();
    $pending = $stmt->fetch()['count'];

    // Sent today
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM scheduled_events 
        WHERE notified = 1
        AND DATE(notified_at) = CURDATE()
    ");
    $stmt->execute();
    $sent_today = $stmt->fetch()['count'];

    // Upcoming events (next 24 hours)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM scheduled_events 
        WHERE status = 'scheduled'
        AND scheduled_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $upcoming = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'pending' => $pending,
            'sent_today' => $sent_today,
            'upcoming' => $upcoming,
            'failed' => 0 // You can track this if you add a failed_notifications table
        ]
    ]);

} catch (Exception $e) {
    error_log("Notification stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>