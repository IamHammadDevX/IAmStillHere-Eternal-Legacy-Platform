<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $user_id = intval($_GET['user_id'] ?? 0);

    if (!$user_id) {
        throw new Exception('User ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check session for privacy
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $logged_in_user_id = $_SESSION['user_id'] ?? null;
    $is_owner = ($logged_in_user_id == $user_id);
    $is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

    // Build privacy conditions
    $privacy_conditions = "status = 'scheduled'";
    
    if (!$is_owner && !$is_admin) {
        // Not owner or admin - show only public events
        $privacy_conditions .= " AND privacy_level = 'public'";
    } else if ($is_owner || $is_admin) {
        // Owner or admin - show all events
        $privacy_conditions .= " AND 1=1";
    }

    $stmt = $conn->prepare("
        SELECT id, user_id, event_type, title, message, scheduled_date, privacy_level, status, created_at
        FROM scheduled_events 
        WHERE user_id = :user_id AND $privacy_conditions
        ORDER BY scheduled_date ASC
    ");
    
    $stmt->execute(['user_id' => $user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
    ]);

} catch (Exception $e) {
    error_log("List events error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading events',
        'error' => $e->getMessage()
    ]);
}
?>