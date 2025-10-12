<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (is_logged_in()) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        echo json_encode(['logged_in' => false, 'message' => 'Session expired']);
        exit;
    }
    
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['user_role']
        ]
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
