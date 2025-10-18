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
$file_type = $file['type'] ?? '';
$file_size = $file['size'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validate file type
$is_valid = in_array($file_type, ALLOWED_FILE_TYPES) || in_array($file_ext, ALLOWED_EXTENSIONS);

if (!$is_valid) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type: ' . $file_ext]);
    exit;
}

if ($file_size > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds maximum allowed (100MB)']);
    exit;
}

// Determine upload directory based on file type
$upload_dir = '';
$subdirectory = '';
$needs_conversion = false;
$original_ext = $file_ext;

if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff'])) {
    $subdirectory = 'photos';
    $upload_dir = UPLOAD_PATH . '/photos/';
} elseif (in_array($file_ext, ['mp4', 'avi', 'mov', 'mkv', 'webm', 'mpeg', 'mpg', '3gp', 'flv', 'wmv'])) {
    $subdirectory = 'videos';
    $upload_dir = UPLOAD_PATH . '/videos/';
    
} elseif (in_array($file_ext, ['mp3', 'wav', 'aac', 'ogg', 'flac', 'm4a'])) {
    $subdirectory = 'audio';
    $upload_dir = UPLOAD_PATH . '/audio/';
    
} else {
    $subdirectory = 'documents';
    $upload_dir = UPLOAD_PATH . '/documents/';
}

// Create directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

$new_filename = uniqid('memory_') . '.' . $file_ext;
$file_path = $upload_dir . $new_filename;

$temp_file = $file['tmp_name'];

if ($needs_conversion) {
    $temp_filename = uniqid('temp_') . '.' . $original_ext;
    $temp_path = $upload_dir . $temp_filename;
    
    if (!move_uploaded_file($temp_file, $temp_path)) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }
    
    // Clean up original file
    if (file_exists($temp_path)) {
        unlink($temp_path);
    }
    
    // Update file type after conversion
    if ($subdirectory === 'videos') {
        $file_type = 'video/mp4';
    } elseif ($subdirectory === 'audio') {
        $file_type = 'audio/mpeg';
    }
    
    // Get new file size
    $file_size = filesize($file_path);
    
} else {
    // No conversion needed, just move file
    if (!move_uploaded_file($temp_file, $file_path)) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO memories (user_id, title, description, file_path, file_type, file_size, privacy_level, memory_date, status) 
        VALUES (:user_id, :title, :description, :file_path, :file_type, :file_size, :privacy_level, :memory_date, 'active')
    ");
    
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
    
    echo json_encode([
        'success' => true, 
        'message' => $needs_conversion ? 'Memory uploaded and converted successfully' : 'Memory uploaded successfully',
        'memory_id' => $conn->lastInsertId(),
        'subdirectory' => $subdirectory,
        'filename' => $new_filename,
        'converted' => $needs_conversion
    ]);
    
} catch (Exception $e) {
    error_log('Memory upload error: ' . $e->getMessage());
    if (file_exists($file_path)) unlink($file_path);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>