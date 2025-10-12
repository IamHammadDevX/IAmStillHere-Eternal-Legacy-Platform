<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$title = sanitize_input($_POST['title'] ?? '');
$description = sanitize_input($_POST['description'] ?? '');
$memory_date = sanitize_input($_POST['memory_date'] ?? '');
$privacy_level = sanitize_input($_POST['privacy_level'] ?? 'public');

if (empty($title) || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Title and file are required']);
    exit;
}

$file = $_FILES['file'];
$file_type = $file['type'];
$file_size = $file['size'];

$allowed_types = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES, ALLOWED_DOCUMENT_TYPES);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

if ($file_size > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds maximum allowed']);
    exit;
}

$upload_dir = '';
if (in_array($file_type, ALLOWED_IMAGE_TYPES)) {
    $upload_dir = UPLOAD_PATH . '/photos/';
} elseif (in_array($file_type, ALLOWED_VIDEO_TYPES)) {
    $upload_dir = UPLOAD_PATH . '/videos/';
} else {
    $upload_dir = UPLOAD_PATH . '/documents/';
}

$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = uniqid('memory_') . '.' . $file_ext;
$file_path = $upload_dir . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO memories (user_id, title, description, file_path, file_type, file_size, privacy_level, memory_date) VALUES (:user_id, :title, :description, :file_path, :file_type, :file_size, :privacy_level, :memory_date)");
    
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'title' => $title,
        'description' => $description,
        'file_path' => $new_filename,
        'file_type' => $file_type,
        'file_size' => $file_size,
        'privacy_level' => $privacy_level,
        'memory_date' => $memory_date ?: null
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Memory uploaded successfully', 'memory_id' => $conn->lastInsertId()]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    if (file_exists($file_path)) unlink($file_path);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
