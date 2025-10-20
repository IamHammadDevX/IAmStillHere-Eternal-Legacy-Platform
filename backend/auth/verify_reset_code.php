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

    if (empty($token) || empty($code)) {
        throw new Exception('Token and code are required');
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
        throw new Exception('Invalid or expired reset code');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Code verified successfully',
        'token' => $token
    ]);

} catch (Exception $e) {
    error_log("Verify reset code error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>