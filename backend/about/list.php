<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../services/PrivacyService.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';

header('Content-Type: application/json');

$ownerId = (int) ($_GET['user_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = min(25, max(1, (int) ($_GET['limit'] ?? 10)));

if ($ownerId < 1) {
    echo json_encode(['success' => false, 'message' => 'User ID required.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $viewerId = SessionHelper::getUserId();
    $items = [];

    $memories = $db->prepare('SELECT id, user_id, title, description, memory_date, upload_date, privacy_level, folder_id FROM memories WHERE user_id=:owner AND status=\'active\' ORDER BY upload_date DESC');
    $memories->execute(['owner' => $ownerId]);
    foreach ($memories->fetchAll(PDO::FETCH_ASSOC) as $memory) {
        if (!PrivacyService::canView($db, 'memory', (int) $memory['id'], $ownerId, $viewerId, (string) $memory['privacy_level'], isset($memory['folder_id']) ? (int) $memory['folder_id'] : null)) continue;
        $items[] = [
            'type' => 'memory', 'id' => (int) $memory['id'], 'title' => (string) $memory['title'],
            'description' => (string) ($memory['description'] ?? ''),
            'item_date' => $memory['memory_date'] ?: $memory['upload_date'],
        ];
    }

    $milestones = $db->prepare('SELECT id, user_id, title, description, milestone_date, privacy_level FROM milestones WHERE user_id=:owner AND status=\'active\' ORDER BY milestone_date DESC');
    $milestones->execute(['owner' => $ownerId]);
    foreach ($milestones->fetchAll(PDO::FETCH_ASSOC) as $milestone) {
        if (!PrivacyService::canView($db, 'milestone', (int) $milestone['id'], $ownerId, $viewerId, (string) $milestone['privacy_level'])) continue;
        $items[] = [
            'type' => 'milestone', 'id' => (int) $milestone['id'], 'title' => (string) $milestone['title'],
            'description' => (string) ($milestone['description'] ?? ''), 'item_date' => $milestone['milestone_date'],
        ];
    }

    $eventWhere = "status IN ('scheduled','published','cancelled')";
    if ($viewerId !== $ownerId && !is_admin()) $eventWhere .= " AND privacy_level='public'";
    $events = $db->prepare("SELECT id, title, message, scheduled_date FROM scheduled_events WHERE user_id=:owner AND {$eventWhere} ORDER BY scheduled_date DESC");
    $events->execute(['owner' => $ownerId]);
    foreach ($events->fetchAll(PDO::FETCH_ASSOC) as $event) {
        $items[] = [
            'type' => 'event', 'id' => (int) $event['id'], 'title' => (string) $event['title'],
            'description' => (string) ($event['message'] ?? ''), 'item_date' => $event['scheduled_date'],
        ];
    }

    usort($items, static function (array $a, array $b): int {
        return strcmp((string) ($b['item_date'] ?? ''), (string) ($a['item_date'] ?? ''));
    });
    $total = count($items);
    $offset = ($page - 1) * $limit;
    echo json_encode([
        'success' => true,
        'data' => [
            'items' => array_slice($items, $offset, $limit),
            'pagination' => ['current_page' => $page, 'total_pages' => max(1, (int) ceil($total / $limit)), 'total_items' => $total],
        ],
    ]);
} catch (Throwable $error) {
    error_log('About journal list failed: ' . $error->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to load the life journal.']);
}
