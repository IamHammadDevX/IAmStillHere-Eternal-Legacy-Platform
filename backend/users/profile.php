<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = intval($_GET['user_id']);
$viewer_id = intval($_SESSION['user_id']);
$viewer_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? ROLE_VISITOR);

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT id, full_name, email, bio, date_of_birth, date_of_passing,
               profile_photo, cover_photo, is_memorial, status
        FROM users
        WHERE id = :user_id
          AND status = 'active'
    ");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if (!canViewProfile($conn, $user_id, $viewer_id, $viewer_role)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not allowed to view this profile']);
        exit;
    }

    if ($viewer_id !== $user_id && $viewer_role !== ROLE_ADMIN) {
        unset($user['email']);
    }
    unset($user['status']);

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
    error_log('Profile load error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading profile'
    ]);
}

function canViewProfile(PDO $conn, int $profileUserId, int $viewerId, string $viewerRole): bool
{
    if ($viewerId === $profileUserId || $viewerRole === ROLE_ADMIN) {
        return true;
    }

    $stmt = $conn->prepare("
        SELECT id
        FROM family_members
        WHERE user_id = :profile_user_id
          AND family_member_id = :viewer_id
          AND status = 'active'
          AND approved = 1
        LIMIT 1
    ");
    $stmt->execute([
        'profile_user_id' => $profileUserId,
        'viewer_id' => $viewerId
    ]);

    return (bool) $stmt->fetch();
}
?>
