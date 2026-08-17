<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

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
if (!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }

$title = sanitize_input($data['title'] ?? '');
$description = sanitize_input($data['description'] ?? '');
$milestone_date = sanitize_input($data['milestone_date'] ?? '');
$category = sanitize_input($data['category'] ?? '');
$privacy_level = sanitize_input($data['privacy_level'] ?? 'public');
$parent_id = (int)($data['parent_id'] ?? 0);

if (empty($title) || empty($milestone_date)) {
    echo json_encode(['success' => false, 'message' => 'Title and date are required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($parent_id > 0) {
        $parentCheck = $conn->prepare("SELECT id FROM milestones WHERE id = :id AND user_id = :user_id AND status = 'active' LIMIT 1");
        $parentCheck->execute(['id' => $parent_id, 'user_id' => $_SESSION['user_id']]);
        if (!$parentCheck->fetch()) { echo json_encode(['success'=>false,'message'=>'Parent timeline item not found']); exit; }
    }
    
    $stmt = $conn->prepare("INSERT INTO milestones (user_id, parent_id, title, description, milestone_date, category, privacy_level) VALUES (:user_id, :parent_id, :title, :description, :milestone_date, :category, :privacy_level)");
    
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'parent_id' => $parent_id ?: null,
        'title' => $title,
        'description' => $description,
        'milestone_date' => $milestone_date,
        'category' => $category,
        'privacy_level' => $privacy_level
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Milestone created successfully', 'milestone_id' => $conn->lastInsertId()]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
