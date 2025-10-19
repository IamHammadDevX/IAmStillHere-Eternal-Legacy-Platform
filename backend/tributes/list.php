<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$memorial_user_id = sanitize_input($_GET['memorial_user_id'] ?? '');

if (empty($memorial_user_id)) {
    echo json_encode(['success' => false, 'message' => 'Memorial user ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $userCheck = $conn->prepare("SELECT id, is_memorial FROM users WHERE id = :id AND status = 'active'");
    $userCheck->execute(['id' => $memorial_user_id]);
    $memorialUser = $userCheck->fetch();

    if (!$memorialUser || !$memorialUser['is_memorial']) {
        echo json_encode(['success' => false, 'message' => 'Memorial page not found or not accessible']);
        exit;
    }

    $canViewMemorial = false;

    if (is_admin() || (is_logged_in() && $_SESSION['user_id'] == $memorial_user_id)) {
        $canViewMemorial = true;
    } else {
        $publicContentCheck = $conn->prepare("
            SELECT COUNT(*) as count FROM (
                SELECT id FROM memories WHERE user_id = :user_id AND privacy_level = 'public' AND status = 'active'
                UNION
                SELECT id FROM milestones WHERE user_id = :user_id AND privacy_level = 'public' AND status = 'active'
                UNION
                SELECT id FROM scheduled_events WHERE user_id = :user_id AND privacy_level = 'public' AND status IN ('scheduled', 'published')
            ) AS public_content
        ");
        $publicContentCheck->execute(['user_id' => $memorial_user_id]);
        $publicCount = $publicContentCheck->fetch()['count'];

        if ($publicCount > 0) {
            $canViewMemorial = true;
        } elseif (is_logged_in()) {
            $familyCheck = $conn->prepare("SELECT id FROM family_members WHERE user_id = :owner_id AND family_member_id = :viewer_id AND status = 'active'");
            $familyCheck->execute(['owner_id' => $memorial_user_id, 'viewer_id' => $_SESSION['user_id']]);

            if ($familyCheck->fetch()) {
                $familyContentCheck = $conn->prepare("
                    SELECT COUNT(*) as count FROM (
                        SELECT id FROM memories WHERE user_id = :user_id AND privacy_level IN ('public', 'family') AND status = 'active'
                        UNION
                        SELECT id FROM milestones WHERE user_id = :user_id AND privacy_level IN ('public', 'family') AND status = 'active'
                        UNION
                        SELECT id FROM scheduled_events WHERE user_id = :user_id AND privacy_level IN ('public', 'family') AND status IN ('scheduled', 'published')
                    ) AS family_content
                ");
                $familyContentCheck->execute(['user_id' => $memorial_user_id]);
                $familyCount = $familyContentCheck->fetch()['count'];

                if ($familyCount > 0) {
                    $canViewMemorial = true;
                }
            }
        }
    }

    if (!$canViewMemorial) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to view this memorial']);
        exit;
    }

    $approval_condition = "is_approved = true";
    if (is_admin()) {
        $approval_condition = "1=1";
    }

    $stmt = $conn->prepare("
    SELECT 
        t.id,
        t.memorial_user_id,
        t.author_id,
        t.author_name,
        t.author_email,
        t.message,
        t.status,
        t.created_at,
        u.profile_photo,
        u.full_name AS registered_user_name
    FROM tributes t
    LEFT JOIN users u ON t.author_id = u.id
    WHERE t.memorial_user_id = :memorial_user_id 
    AND t.status = 'active'
    ORDER BY t.created_at DESC
");
    $stmt->execute(['memorial_user_id' => $memorial_user_id]);

    $tributes = $stmt->fetchAll();

    echo json_encode(['success' => true, 'tributes' => $tributes]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
