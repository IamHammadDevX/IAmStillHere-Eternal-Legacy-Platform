<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$bio = sanitize_input($_POST['bio'] ?? '');
$date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $updates = [];
    $params = ['user_id' => $_SESSION['user_id']];

    // ✅ Bio and DOB
    if (!empty($bio)) {
        $updates[] = "bio = :bio";
        $params['bio'] = $bio;
    }

    if (!empty($date_of_birth)) {
        $updates[] = "date_of_birth = :date_of_birth";
        $params['date_of_birth'] = $date_of_birth;
    }

    // ✅ Profile Photo
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            echo json_encode(['success' => false, 'message' => 'Invalid profile photo type']);
            exit;
        }

        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
        $filepath = UPLOAD_PATH . '/photos/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $updates[] = "profile_photo = :profile_photo";
            $params['profile_photo'] = $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save profile photo']);
            exit;
        }
    }

    // ✅ Cover Photo
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_photo'];
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            echo json_encode(['success' => false, 'message' => 'Invalid cover photo type']);
            exit;
        }

        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'cover_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
        $filepath = UPLOAD_PATH . '/photos/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $updates[] = "cover_photo = :cover_photo";
            $params['cover_photo'] = $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save cover photo']);
            exit;
        }
    }

    // ✅ Update DB
    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes to update']);
        exit;
    }

    // ✅ Fetch latest updated data for frontend
    $stmt = $conn->prepare("SELECT full_name, bio, date_of_birth, profile_photo, cover_photo FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'full_name' => $user['full_name'],
            'bio' => $user['bio'],
            'date_of_birth' => $user['date_of_birth'],
            'profile_photo' => !empty($user['profile_photo']) 
                ? '/IAmStillHere/data/uploads/photos/' . $user['profile_photo'] 
                : '/frontend/images/default-profile.png',
            'cover_photo' => !empty($user['cover_photo']) 
                ? '/IAmStillHere/data/uploads/photos/' . $user['cover_photo'] 
                : ''
        ]
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
