<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $token = sanitize_input($data['token'] ?? '');
    $code = sanitize_input($data['code'] ?? '');
    $new_password = $data['new_password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';

    if (empty($token) || empty($code) || empty($new_password)) {
        throw new Exception('All fields are required');
    }

    if ($new_password !== $confirm_password) {
        throw new Exception('Passwords do not match');
    }

    if (strlen($new_password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Verify reset request
    $stmt = $conn->prepare("
        SELECT * FROM password_resets 
        WHERE reset_token = :token 
        AND reset_code = :code 
        AND expires_at > NOW()
        AND used = 0
    ");
    
    $stmt->execute([
        'token' => $token,
        'code' => $code
    ]);

    $reset = $stmt->fetch();

    if (!$reset) {
        throw new Exception('Invalid or expired reset request');
    }

    // Update password
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("UPDATE users SET password_hash = :password WHERE id = :id");
    $stmt->execute([
        'password' => $password_hash,
        'id' => $reset['user_id']
    ]);

    // Mark reset request as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = :id");
    $stmt->execute(['id' => $reset['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully'
    ]);

} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>