<?php
// Application Configuration

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('FRONTEND_PATH', BASE_PATH . '/frontend');
define('BACKEND_PATH', BASE_PATH . '/backend');
define('DATA_PATH', BASE_PATH . '/data');
define('UPLOAD_PATH', DATA_PATH . '/uploads');

// Define base URLs
define('BASE_URL', '/');
define('ASSETS_URL', BASE_URL . 'frontend/');

// Upload settings
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/avi', 'video/mov', 'video/wmv']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Privacy levels
define('PRIVACY_PUBLIC', 'public');
define('PRIVACY_FAMILY', 'family');
define('PRIVACY_PRIVATE', 'private');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_CLIENT', 'client');
define('ROLE_VISITOR', 'visitor');

// Pagination
define('ITEMS_PER_PAGE', 12);

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once BASE_PATH . '/config/database.php';

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? ROLE_VISITOR;
}

function is_admin() {
    return get_user_role() === ROLE_ADMIN;
}

function is_client() {
    return get_user_role() === ROLE_CLIENT;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function format_date($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M j, Y', $timestamp);
}

function get_file_icon($file_type) {
    if (strpos($file_type, 'image') !== false) return 'bi-image';
    if (strpos($file_type, 'video') !== false) return 'bi-film';
    if (strpos($file_type, 'pdf') !== false) return 'bi-file-pdf';
    return 'bi-file-earmark';
}
