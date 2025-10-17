<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Accept either GET or POST data
    $input = json_decode(file_get_contents("php://input"), true);
    $user_id = $_GET['id'] ?? $input['id'] ?? null;
    $username = $_GET['username'] ?? $input['username'] ?? null;
    $email = $_GET['email'] ?? $input['email'] ?? null;

    if (empty($user_id) && empty($username) && empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please provide user ID, username, or email']);
        exit;
    }

    // Build dynamic query
    $query = "SELECT id, username, full_name, email, role, status, is_memorial, created_at 
              FROM users 
              WHERE 1=1 ";
    $params = [];

    if (!empty($user_id)) {
        $query .= "AND id = :id ";
        $params['id'] = $user_id;
    } elseif (!empty($username)) {
        $query .= "AND username = :username ";
        $params['username'] = $username;
    } elseif (!empty($email)) {
        $query .= "AND email = :email ";
        $params['email'] = $email;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

} catch (Exception $e) {
    error_log("Find user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
