<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
    exit;
}

$user_id = intval($_GET['user_id']);

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT id, full_name, email, bio, date_of_birth, date_of_passing, 
               profile_photo, cover_photo, is_memorial
        FROM users 
        WHERE id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Build image URLs
    $baseUrl = 'http://localhost/IAmStillHere/data/uploads/photos/';

    $user['profile_photo'] = !empty($user['profile_photo'])
        ? $baseUrl . $user['profile_photo']
        : null;

    $user['cover_photo'] = !empty($user['cover_photo'])
        ? $baseUrl . $user['cover_photo']
        : null;

    echo json_encode([
        'success' => true,
        'profile' => $user
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading profile',
        'error' => $e->getMessage()
    ]);
}
?>
