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
    $milestone_id = intval($data['milestone_id'] ?? 0);

    if (!$milestone_id) {
        throw new Exception('Milestone ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get milestone details
    $stmt = $conn->prepare("SELECT user_id FROM milestones WHERE id = :id");
    $stmt->execute(['id' => $milestone_id]);
    $milestone = $stmt->fetch();

    if (!$milestone) {
        throw new Exception('Milestone not found');
    }

    // Check permissions
    $isOwner = ($milestone['user_id'] == $_SESSION['user_id']);
    $isAdmin = ($_SESSION['user_role'] === 'admin');

    if (!$isOwner && !$isAdmin) {
        throw new Exception('You do not have permission to delete this milestone');
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM milestones WHERE id = :id");
    $stmt->execute(['id' => $milestone_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Milestone deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete milestone error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>