<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = sanitize_input($data['email'] ?? '');
    $code = sanitize_input($data['code'] ?? '');

    if (empty($email) || empty($code)) {
        throw new Exception('Email and verification code are required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Find verification record
    $stmt = $conn->prepare("
        SELECT * FROM email_verifications 
        WHERE email = :email 
        AND verification_code = :code 
        AND expires_at > NOW()
    ");
    
    $stmt->execute([
        'email' => $email,
        'code' => $code
    ]);

    $verification = $stmt->fetch();

    if (!$verification) {
        throw new Exception('Invalid or expired verification code');
    }

    // Create user account
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password_hash, full_name, date_of_birth, role, status)
        VALUES (:username, :email, :password_hash, :full_name, :date_of_birth, 'client', 'active')
    ");

    $stmt->execute([
        'username' => $verification['username'],
        'email' => $verification['email'],
        'password_hash' => $verification['password_hash'],
        'full_name' => $verification['full_name'],
        'date_of_birth' => $verification['date_of_birth']
    ]);

    $user_id = $conn->lastInsertId();

    // Delete verification record
    $stmt = $conn->prepare("DELETE FROM email_verifications WHERE email = :email");
    $stmt->execute(['email' => $email]);

    // Create session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $verification['username'];
    $_SESSION['full_name'] = $verification['full_name'];
    $_SESSION['user_role'] = 'client';

    echo json_encode([
        'success' => true,
        'message' => 'Account verified successfully',
        'user' => [
            'id' => $user_id,
            'username' => $verification['username'],
            'full_name' => $verification['full_name']
        ]
    ]);

} catch (Exception $e) {
    error_log("Verify code error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>