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

    $stmt = $conn->prepare("
        SELECT 
            se.id,
            se.title,
            se.event_type,
            se.scheduled_date,
            u.full_name as user_name,
            (SELECT COUNT(DISTINCT fm.family_member_id) 
             FROM family_members fm 
             WHERE (fm.user_id = se.user_id OR fm.family_member_id = se.user_id) 
             AND fm.status = 'active' 
             AND fm.approved = 1) as family_count
        FROM scheduled_events se
        JOIN users u ON se.user_id = u.id
        WHERE se.status = 'scheduled'
        AND se.scheduled_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
        AND (se.notified = 0 OR se.notified IS NULL)
        AND se.privacy_level != 'private'
        ORDER BY se.scheduled_date ASC
    ");
    
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'events' => $events
    ]);

} catch (Exception $e) {
    error_log("Upcoming notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>