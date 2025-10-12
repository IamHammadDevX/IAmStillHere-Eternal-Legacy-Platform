<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$username = sanitize_input($data['username'] ?? '');
$email = sanitize_input($data['email'] ?? '');
$password = $data['password'] ?? '';
$full_name = sanitize_input($data['full_name'] ?? '');
$date_of_birth = sanitize_input($data['date_of_birth'] ?? '');

if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if (strlen($password) < PASSWORD_MIN_LENGTH) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $checkStmt->execute(['username' => $username, 'email' => $email]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, date_of_birth, role) VALUES (:username, :email, :password_hash, :full_name, :date_of_birth, 'client')");
    
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $password_hash,
        'full_name' => $full_name,
        'date_of_birth' => $date_of_birth ?: null
    ]);
    
    $user_id = $conn->lastInsertId();
    
    $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (:user_id, 'registration', :ip)");
    $logStmt->execute([
        'user_id' => $user_id,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. You can now login.',
        'user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during registration']);
}
