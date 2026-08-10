<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (is_logged_in()) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        echo json_encode(['logged_in' => false, 'message' => 'Session expired']);
        exit;
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id, username, full_name, role, status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active') {
            session_destroy();
            echo json_encode(['logged_in' => false, 'message' => 'Account unavailable']);
            exit;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        echo json_encode([
            'logged_in' => true,
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ]);
    } catch (Throwable $e) {
        error_log('Session check failed: ' . $e->getMessage());
        echo json_encode(['logged_in' => false]);
    }
} else {
    echo json_encode(['logged_in' => false]);
}
