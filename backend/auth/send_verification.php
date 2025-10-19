<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = sanitize_input($data['username'] ?? '');
    $email = sanitize_input($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $full_name = sanitize_input($data['full_name'] ?? '');
    $date_of_birth = sanitize_input($data['date_of_birth'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        throw new Exception('All required fields must be filled');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        throw new Exception('Username already exists');
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already registered');
    }

    // Generate verification code
    $verificationCode = EmailHelper::generateVerificationCode();
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Delete any existing verification for this email
    $stmt = $conn->prepare("DELETE FROM email_verifications WHERE email = :email");
    $stmt->execute(['email' => $email]);

    // Store verification data
    $stmt = $conn->prepare("
        INSERT INTO email_verifications (email, verification_code, full_name, username, password_hash, date_of_birth)
        VALUES (:email, :code, :full_name, :username, :password_hash, :dob)
    ");
    
    $stmt->execute([
        'email' => $email,
        'code' => $verificationCode,
        'full_name' => $full_name,
        'username' => $username,
        'password_hash' => $passwordHash,
        'dob' => $date_of_birth ?: null
    ]);

    // Send verification email
    $emailSent = EmailHelper::sendVerificationEmail($email, $full_name, $verificationCode);

    if (!$emailSent) {
        throw new Exception('Failed to send verification email. Please try again.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Verification code sent to your email',
        'email' => $email
    ]);

} catch (Exception $e) {
    error_log("Send verification error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>