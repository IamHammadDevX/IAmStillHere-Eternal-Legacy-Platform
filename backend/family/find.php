<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $user_id = intval($_GET['user_id'] ?? 0);

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT fm.id, fm.user_id, fm.family_member_id, fm.relationship, fm.added_at, fm.status,
               u.full_name AS member_name, u.profile_photo AS member_picture, u.updated_at AS member_updated_at
        FROM family_members fm
        JOIN users u ON fm.family_member_id = u.id
        WHERE fm.user_id = :user_id AND fm.status = 'active'
        ORDER BY fm.added_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $members = $stmt->fetchAll();

    $viewer_id = is_logged_in() ? intval($_SESSION['user_id']) : 0;
    $viewer_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

    foreach ($members as &$member) {
        $member_activity = getLatestVisibleFamilyActivity(
            $conn,
            intval($member['family_member_id']),
            $viewer_id,
            $viewer_role,
            $member['member_updated_at'] ?? null
        );

        $member['recent_activity'] = $member_activity['recent_activity'];
        $member['latest_activity_type'] = $member_activity['latest_activity_type'];
        $member['latest_activity_label'] = $member_activity['latest_activity_label'];
        $member['latest_activity_at'] = $member_activity['latest_activity_at'];

        unset($member['member_updated_at']);
    }
    unset($member);

    echo json_encode(['success' => true, 'members' => $members]);
} catch (Exception $e) {
    error_log("Family Find Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

function getLatestVisibleFamilyActivity(PDO $conn, int $memberId, int $viewerId, ?string $viewerRole, ?string $profileUpdatedAt): array
{
    $activities = [];

    if (!empty($profileUpdatedAt)) {
        $activities[] = [
            'type' => 'profile',
            'label' => 'Updated profile',
            'at' => $profileUpdatedAt
        ];
    }

    $visiblePrivacyLevels = getVisiblePrivacyLevelsForUserActivity($conn, $memberId, $viewerId, $viewerRole);
    $privacyPlaceholders = implode(',', array_fill(0, count($visiblePrivacyLevels), '?'));

    $memoryStmt = $conn->prepare("
        SELECT upload_date
        FROM memories
        WHERE user_id = ?
          AND status = 'active'
          AND privacy_level IN ($privacyPlaceholders)
        ORDER BY upload_date DESC
        LIMIT 1
    ");
    $memoryStmt->execute(array_merge([$memberId], $visiblePrivacyLevels));
    $memory = $memoryStmt->fetch();
    if ($memory && !empty($memory['upload_date'])) {
        $activities[] = [
            'type' => 'memory',
            'label' => 'Added a memory',
            'at' => $memory['upload_date']
        ];
    }

    $milestoneStmt = $conn->prepare("
        SELECT created_at
        FROM milestones
        WHERE user_id = ?
          AND status = 'active'
          AND privacy_level IN ($privacyPlaceholders)
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $milestoneStmt->execute(array_merge([$memberId], $visiblePrivacyLevels));
    $milestone = $milestoneStmt->fetch();
    if ($milestone && !empty($milestone['created_at'])) {
        $activities[] = [
            'type' => 'milestone',
            'label' => 'Added a milestone',
            'at' => $milestone['created_at']
        ];
    }

    usort($activities, function ($a, $b) {
        return strtotime($b['at']) <=> strtotime($a['at']);
    });

    $latest = $activities[0] ?? null;
    $latestAt = $latest['at'] ?? null;

    return [
        'recent_activity' => $latestAt ? strtotime($latestAt) >= strtotime('-7 days') : false,
        'latest_activity_type' => $latest['type'] ?? null,
        'latest_activity_label' => $latest['label'] ?? null,
        'latest_activity_at' => $latestAt
    ];
}

function getVisiblePrivacyLevelsForUserActivity(PDO $conn, int $ownerId, int $viewerId, ?string $viewerRole): array
{
    if ($viewerId > 0) {
        if ($viewerId === $ownerId || $viewerRole === 'admin') {
            return ['public', 'family', 'private'];
        }

        $familyCheck = $conn->prepare("
            SELECT id
            FROM family_members
            WHERE user_id = :owner_id
              AND family_member_id = :viewer_id
              AND status = 'active'
        ");
        $familyCheck->execute([
            'owner_id' => $ownerId,
            'viewer_id' => $viewerId
        ]);

        if ($familyCheck->fetch()) {
            return ['public', 'family'];
        }
    }

    return ['public'];
}
?>
