<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($data['email'] ?? '');

    if (empty($email)) {
        throw new Exception('Email is required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name, status FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Don't reveal if email exists or not (security)
        echo json_encode([
            'success' => true,
            'message' => 'If this email exists, you will receive a password reset code.'
        ]);
        exit;
    }

    if ($user['status'] !== 'active') {
        throw new Exception('Account is not active');
    }

    // Generate reset code and token
    $resetCode = EmailHelper::generateVerificationCode();
    $resetToken = bin2hex(random_bytes(32));

    // Delete any existing reset requests for this user
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user['id']]);

    // Create new reset request
    $stmt = $conn->prepare("
        INSERT INTO password_resets (user_id, email, reset_token, reset_code)
        VALUES (:user_id, :email, :token, :code)
    ");
    
    $stmt->execute([
        'user_id' => $user['id'],
        'email' => $email,
        'token' => $resetToken,
        'code' => $resetCode
    ]);

    // Send email
    $emailSent = EmailHelper::sendPasswordResetEmail(
        $email,
        $user['full_name'],
        $resetCode,
        $resetToken
    );

    if (!$emailSent) {
        throw new Exception('Failed to send reset email. Please try again.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Password reset code sent to your email',
        'token' => $resetToken
    ]);

} catch (Exception $e) {
    error_log("Password reset request error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>