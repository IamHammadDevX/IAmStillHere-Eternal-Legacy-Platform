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
    $tribute_id = intval($data['tribute_id'] ?? 0);

    if (!$tribute_id) {
        throw new Exception('Tribute ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get tribute details
    $stmt = $conn->prepare("SELECT memorial_user_id, author_id FROM tributes WHERE id = :id");
    $stmt->execute(['id' => $tribute_id]);
    $tribute = $stmt->fetch();

    if (!$tribute) {
        throw new Exception('Tribute not found');
    }

    // Check permissions (memorial owner, tribute author, or admin)
    $isMemorialOwner = ($tribute['memorial_user_id'] == $_SESSION['user_id']);
    $isAuthor = ($tribute['author_id'] == $_SESSION['user_id']);
    $isAdmin = ($_SESSION['user_role'] === 'admin');

    if (!$isMemorialOwner && !$isAuthor && !$isAdmin) {
        throw new Exception('You do not have permission to delete this tribute');
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM tributes WHERE id = :id");
    $stmt->execute(['id' => $tribute_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Tribute deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete tribute error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>