<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

try {
    $userId = intval($_GET['user_id'] ?? 0);

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $root = getSafeFamilyUser($conn, $userId);
    if (!$root) {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
        exit;
    }

    $viewerId = is_logged_in() ? intval($_SESSION['user_id']) : 0;
    $viewerRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

    $users = loadTreeUsers($conn);
    $edges = loadFamilyEdges($conn, $users);

    if (!isset($users[$userId])) {
        $users[$userId] = $root;
    }

    $tree = buildFamilyTreeNode($conn, $userId, $users, $edges, $viewerId, $viewerRole, [], 0);

    echo json_encode([
        'success' => true,
        'root' => $tree,
        'meta' => [
            'max_depth' => 4,
            'relationship_model' => 'accepted two-way family_members rows',
            'empty' => empty($edges[$userId])
        ]
    ]);
} catch (Exception $e) {
    error_log('Family Tree Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

function getSafeFamilyUser(PDO $conn, int $userId): ?array
{
    $stmt = $conn->prepare("SELECT id, full_name, profile_photo, updated_at FROM users WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function loadTreeUsers(PDO $conn): array
{
    $stmt = $conn->query("SELECT id, full_name, profile_photo, updated_at FROM users WHERE status = 'active'");
    $users = [];

    foreach ($stmt->fetchAll() as $user) {
        $users[intval($user['id'])] = $user;
    }

    return $users;
}

function loadFamilyEdges(PDO $conn, array $users): array
{
    $stmt = $conn->query("
        SELECT user_id, family_member_id, relationship, added_at
        FROM family_members
        WHERE status = 'active'
          AND approved = 1
        ORDER BY added_at ASC, id ASC
    ");

    $edges = [];
    $seen = [];

    foreach ($stmt->fetchAll() as $row) {
        $from = intval($row['user_id']);
        $to = intval($row['family_member_id']);

        if ($from === $to || !isset($users[$from]) || !isset($users[$to])) {
            continue;
        }

        $key = $from . ':' . $to;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;

        $edges[$from][] = [
            'user_id' => $to,
            'relationship' => normalizeRelationshipLabel($row['relationship'] ?? ''),
            'relationship_group' => getRelationshipGroup($row['relationship'] ?? '')
        ];
    }

    return $edges;
}

function buildFamilyTreeNode(PDO $conn, int $userId, array $users, array $edges, int $viewerId, ?string $viewerRole, array $path, int $depth): array
{
    $user = $users[$userId];
    $activity = getLatestVisibleFamilyTreeActivity($conn, $userId, $viewerId, $viewerRole, $user['updated_at'] ?? null);

    $node = [
        'id' => intval($user['id']),
        'name' => $user['full_name'] ?: 'Unknown',
        'profile_photo' => $user['profile_photo'] ?: 'default-profile.png',
        'profile_url' => '/frontend/profile.php?user_id=' . intval($user['id']),
        'profile_access' => true,
        'recent_activity' => $activity['recent_activity'],
        'latest_activity_type' => $activity['latest_activity_type'],
        'latest_activity_label' => $activity['latest_activity_label'],
        'latest_activity_at' => $activity['latest_activity_at'],
        'cycle' => false,
        'max_depth_reached' => false,
        'branches' => createEmptyBranches()
    ];

    if (isset($path[$userId])) {
        $node['cycle'] = true;
        return $node;
    }

    if ($depth >= 4) {
        $node['max_depth_reached'] = true;
        return $node;
    }

    $path[$userId] = true;
    $children = $edges[$userId] ?? [];
    $branchSeen = [];

    foreach ($children as $edge) {
        $childId = intval($edge['user_id']);
        $branchKey = $edge['relationship_group'] . ':' . $childId;

        if (!isset($users[$childId]) || isset($branchSeen[$branchKey])) {
            continue;
        }
        $branchSeen[$branchKey] = true;

        $child = buildFamilyTreeNode($conn, $childId, $users, $edges, $viewerId, $viewerRole, $path, $depth + 1);
        $child['relationship'] = $edge['relationship'];
        $child['relationship_group'] = $edge['relationship_group'];

        $node['branches'][$edge['relationship_group']][] = $child;
    }

    return $node;
}

function createEmptyBranches(): array
{
    return [
        'grandparents' => [],
        'parents' => [],
        'partners' => [],
        'siblings' => [],
        'children' => [],
        'grandchildren' => [],
        'other' => []
    ];
}

function normalizeRelationshipLabel(?string $relationship): string
{
    $relationship = trim((string) $relationship);
    return $relationship !== '' ? $relationship : 'Family';
}

function getRelationshipGroup(?string $relationship): string
{
    $value = strtolower(trim((string) $relationship));

    if (preg_match('/grand\s*father|grand\s*mother|grandparent/', $value)) {
        return 'grandparents';
    }
    if (preg_match('/grand\s*son|grand\s*daughter|grandchild/', $value)) {
        return 'grandchildren';
    }
    if (preg_match('/father|mother|parent/', $value)) {
        return 'parents';
    }
    if (preg_match('/son|daughter|child/', $value)) {
        return 'children';
    }
    if (preg_match('/spouse|partner|wife|husband/', $value)) {
        return 'partners';
    }
    if (preg_match('/brother|sister|sibling/', $value)) {
        return 'siblings';
    }

    return 'other';
}

function getLatestVisibleFamilyTreeActivity(PDO $conn, int $memberId, int $viewerId, ?string $viewerRole, ?string $profileUpdatedAt): array
{
    $activities = [];

    if (!empty($profileUpdatedAt)) {
        $activities[] = [
            'type' => 'profile',
            'label' => 'Updated profile',
            'at' => $profileUpdatedAt
        ];
    }

    $visiblePrivacyLevels = getVisiblePrivacyLevelsForFamilyTreeActivity($conn, $memberId, $viewerId, $viewerRole);
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

function getVisiblePrivacyLevelsForFamilyTreeActivity(PDO $conn, int $ownerId, int $viewerId, ?string $viewerRole): array
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
