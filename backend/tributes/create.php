<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$memorial_user_id = intval($data['memorial_user_id'] ?? 0);
$author_name = sanitize_input($data['author_name'] ?? '');
$author_email = sanitize_input($data['author_email'] ?? '');
$message = sanitize_input($data['message'] ?? '');

if (empty($memorial_user_id) || empty($author_name) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!empty($author_email) && !filter_var($author_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $userCheck = $conn->prepare("SELECT id, is_memorial FROM users WHERE id = :id AND status = 'active' AND is_memorial = true");
    $userCheck->execute(['id' => $memorial_user_id]);
    if (!$userCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Memorial page not found or not accessible']);
        exit;
    }

    $publicContentCheck = $conn->prepare("
        SELECT COUNT(*) as count FROM (
            SELECT id FROM memories WHERE user_id = :user_id AND privacy_level = 'public' AND status = 'active'
            UNION
            SELECT id FROM milestones WHERE user_id = :user_id AND privacy_level = 'public' AND status = 'active'
            UNION
            SELECT id FROM scheduled_events WHERE user_id = :user_id AND privacy_level = 'public' AND status IN ('scheduled', 'published')
        ) AS public_content
    ");
    $publicContentCheck->execute(['user_id' => $memorial_user_id]);
    $publicCount = $publicContentCheck->fetch()['count'];

    if ($publicCount == 0 && !is_admin()) {
        echo json_encode(['success' => false, 'message' => 'This memorial does not accept public tributes']);
        exit;
    }

    // Check if user is logged in
    $author_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $stmt = $conn->prepare("
    INSERT INTO tributes (memorial_user_id, author_id, author_name, author_email, message, status) 
    VALUES (:memorial_user_id, :author_id, :author_name, :author_email, :message, 'active')
");

    $stmt->execute([
        'memorial_user_id' => $memorial_user_id,
        'author_id' => $author_id,
        'author_name' => $author_name,
        'author_email' => $author_email,
        'message' => $message
    ]);

    echo json_encode(['success' => true, 'message' => 'Tribute added successfully', 'tribute_id' => $conn->lastInsertId()]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred' . $e->getMessage()]);
}
