<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $memory_id = intval($data['memory_id'] ?? 0);

    if (!$memory_id) {
        throw new Exception('Memory ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get memory details
    $stmt = $conn->prepare("SELECT user_id, file_path, file_type FROM memories WHERE id = :id");
    $stmt->execute(['id' => $memory_id]);
    $memory = $stmt->fetch();

    if (!$memory) {
        throw new Exception('Memory not found');
    }

    // Check permissions (owner or admin)
    $isOwner = ($memory['user_id'] == $_SESSION['user_id']);
    $isAdmin = ($_SESSION['user_role'] === 'admin');

    if (!$isOwner && !$isAdmin) {
        throw new Exception('You do not have permission to delete this memory');
    }

    // Determine file directory
    $fileName = $memory['file_path'];
    $fileType = strtolower($memory['file_type']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $filePath = '';
    if (strpos($fileType, 'image') !== false || in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])) {
        $filePath = __DIR__ . '/../../data/uploads/photos/' . $fileName;
    } elseif (strpos($fileType, 'video') !== false || in_array($fileExt, ['mp4', 'avi', 'mkv', 'mov', 'webm', 'mpeg', '3gp', 'flv', 'wmv'])) {
        $filePath = __DIR__ . '/../../data/uploads/videos/' . $fileName;
    } elseif (strpos($fileType, 'audio') !== false || in_array($fileExt, ['mp3', 'wav', 'aac', 'ogg', 'flac', 'm4a'])) {
        $filePath = __DIR__ . '/../../data/uploads/audio/' . $fileName;
    } else {
        $filePath = __DIR__ . '/../../data/uploads/documents/' . $fileName;
    }

    // Delete file from server
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM memories WHERE id = :id");
    $stmt->execute(['id' => $memory_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Memory deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete memory error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>