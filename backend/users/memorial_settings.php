<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$is_memorial = isset($data['is_memorial']) ? (bool)$data['is_memorial'] : false;
$date_of_passing = sanitize_input($data['date_of_passing'] ?? '');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $updates = [];
    $params = ['user_id' => $_SESSION['user_id']];
    
    $updates[] = "is_memorial = :is_memorial";
    $params['is_memorial'] = $is_memorial;
    
    if (!empty($date_of_passing)) {
        $updates[] = "date_of_passing = :date_of_passing";
        $params['date_of_passing'] = $date_of_passing;
    } else {
        $updates[] = "date_of_passing = NULL";
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (:user_id, 'memorial_settings_updated', :details, :ip)");
    $logStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'details' => json_encode(['is_memorial' => $is_memorial, 'date_of_passing' => $date_of_passing]),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Memorial settings saved successfully']);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
