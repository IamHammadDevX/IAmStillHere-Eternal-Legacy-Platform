<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10; $offset = ($page - 1) * $limit;
        $total = (int)$conn->query('SELECT COUNT(*) FROM activity_log')->fetchColumn();
        $stmt = $conn->prepare('SELECT a.id,u.username,a.action,a.details,a.ip_address,a.created_at FROM activity_log a LEFT JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit',$limit,PDO::PARAM_INT); $stmt->bindValue(':offset',$offset,PDO::PARAM_INT); $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'logs'=>$logs,'pagination'=>['page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)]]);
    }

    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Log a new activity (useful for recording admin/user actions)
        $data = json_decode(file_get_contents('php://input'), true);

        $user_id = intval($data['user_id'] ?? 0);
        $action = sanitize_input($data['action'] ?? '');
        $details = sanitize_input($data['details'] ?? '');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        if (empty($action)) {
            echo json_encode(['success' => false, 'message' => 'Action is required']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
            VALUES (:user_id, :action, :details, :ip_address, NOW())
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip_address
        ]);

        echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
    }

    else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log('Activity Log Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred', 'error' => $e->getMessage()]);
}
?>
