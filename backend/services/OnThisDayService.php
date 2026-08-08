<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/PrivacyService.php';

class OnThisDayService
{
    private PDO $db;
    private int $viewerId;

    public function __construct(PDO $db, int $viewerId)
    {
        $this->db = $db;
        $this->viewerId = $viewerId;
    }

    public function list(int $limit = 10, int $page = 1, ?DateTimeImmutable $today = null): array
    {
        $limit = min(50, max(1, $limit));
        $page = max(1, $page);
        $today = $today ?: new DateTimeImmutable('today', new DateTimeZone('UTC'));
        $items = [];
        $seenSources = [];

        $this->addMemories($items, $seenSources, $today);
        $this->addMilestones($items, $today);
        $this->addPosts($items, $today);
        $this->addJourneyItems($items, $seenSources, $today);

        usort($items, static function (array $a, array $b): int {
            if ($a['years_ago'] !== $b['years_ago']) {
                return $a['years_ago'] <=> $b['years_ago'];
            }
            return strcmp($b['original_date'], $a['original_date']);
        });

        $total = count($items);
        $offset = ($page - 1) * $limit;

        return [
            'items' => array_slice($items, $offset, $limit),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
            'date' => $today->format('Y-m-d'),
            'leap_day_behavior' => 'Feb 29 content appears only on Feb 29.',
        ];
    }

    private function addMemories(array &$items, array &$seenSources, DateTimeImmutable $today): void
    {
        $stmt = $this->db->prepare("SELECT id,user_id,title,description,file_path,video_thumbnail_path,file_type,privacy_level,memory_date,folder_id FROM memories WHERE user_id=:viewer AND status='active' AND memory_date IS NOT NULL AND MONTH(memory_date)=:m AND DAY(memory_date)=:d AND memory_date < :today ORDER BY memory_date DESC LIMIT 200");
        $stmt->execute(['viewer' => $this->viewerId, 'm' => (int) $today->format('n'), 'd' => (int) $today->format('j'), 'today' => $today->format('Y-m-d')]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $memory) {
            if (!PrivacyService::canView($this->db, 'memory', (int) $memory['id'], (int) $memory['user_id'], $this->viewerId, (string) $memory['privacy_level'], $memory['folder_id'] !== null ? (int) $memory['folder_id'] : null)) {
                continue;
            }
            $key = 'memory:' . (int) $memory['id'];
            $seenSources[$key] = true;
            $items[] = $this->formatItem('memory', (int) $memory['id'], $memory['title'], $memory['description'] ?? '', $memory['memory_date'], $this->memoryThumbnail($memory), 'profile.php#memories-tab', $today);
        }
    }

    private function addMilestones(array &$items, DateTimeImmutable $today): void
    {
        $stmt = $this->db->prepare("SELECT id,user_id,title,description,privacy_level,milestone_date,category FROM milestones WHERE user_id=:viewer AND status='active' AND MONTH(milestone_date)=:m AND DAY(milestone_date)=:d AND milestone_date < :today ORDER BY milestone_date DESC LIMIT 200");
        $stmt->execute(['viewer' => $this->viewerId, 'm' => (int) $today->format('n'), 'd' => (int) $today->format('j'), 'today' => $today->format('Y-m-d')]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $milestone) {
            if (!PrivacyService::canView($this->db, 'milestone', (int) $milestone['id'], (int) $milestone['user_id'], $this->viewerId, (string) $milestone['privacy_level'])) {
                continue;
            }
            $items[] = $this->formatItem('milestone', (int) $milestone['id'], $milestone['title'], $milestone['description'] ?? '', $milestone['milestone_date'], null, 'profile.php#timeline-tab', $today);
        }
    }

    private function addPosts(array &$items, DateTimeImmutable $today): void
    {
        $stmt = $this->db->prepare("SELECT p.id,p.user_id,p.body,p.privacy_level,p.created_at,MIN(pm.file_path) AS file_path,MIN(pm.media_type) AS media_type FROM posts p LEFT JOIN post_media pm ON pm.post_id=p.id WHERE p.user_id=:viewer AND p.status='active' AND MONTH(p.created_at)=:m AND DAY(p.created_at)=:d AND DATE(p.created_at) < :today GROUP BY p.id,p.user_id,p.body,p.privacy_level,p.created_at ORDER BY p.created_at DESC LIMIT 200");
        $stmt->execute(['viewer' => $this->viewerId, 'm' => (int) $today->format('n'), 'd' => (int) $today->format('j'), 'today' => $today->format('Y-m-d')]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $post) {
            if (!PrivacyService::canView($this->db, 'post', (int) $post['id'], (int) $post['user_id'], $this->viewerId, (string) $post['privacy_level'])) {
                continue;
            }
            $title = mb_substr(trim((string) $post['body']), 0, 80) ?: 'Post';
            $items[] = $this->formatItem('post', (int) $post['id'], $title, (string) $post['body'], substr((string) $post['created_at'], 0, 10), $this->postThumbnail($post), 'profile.php#posts-tab', $today);
        }
    }

    private function addJourneyItems(array &$items, array $seenSources, DateTimeImmutable $today): void
    {
        $stmt = $this->db->prepare("SELECT ji.*,j.owner_id,j.privacy_level AS journey_privacy,j.status AS journey_status,j.deleted_at AS journey_deleted FROM journey_items ji INNER JOIN journeys j ON j.id=ji.journey_id WHERE ji.status='approved' AND ji.deleted_at IS NULL AND ji.item_date IS NOT NULL AND MONTH(ji.item_date)=:m AND DAY(ji.item_date)=:d AND ji.item_date < :today ORDER BY ji.item_date DESC, ji.id DESC LIMIT 200");
        $stmt->execute(['m' => (int) $today->format('n'), 'd' => (int) $today->format('j'), 'today' => $today->format('Y-m-d')]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            if (!$this->canViewJourneyItem($item)) {
                continue;
            }
            if (($item['item_type'] === 'memory' || $item['item_type'] === 'milestone') && !empty($item['source_id'])) {
                $sourceKey = $item['item_type'] . ':' . (int) $item['source_id'];
                if (isset($seenSources[$sourceKey])) {
                    continue;
                }
            }
            $items[] = $this->formatItem('journey', (int) $item['journey_id'], $item['title'], $item['description'] ?? '', $item['item_date'], null, 'profile.php#journeys-tab', $today, (int) $item['id']);
        }
    }

    private function canViewJourneyItem(array $item): bool
    {
        if ($item['journey_deleted'] !== null || $item['journey_status'] !== 'published') {
            return false;
        }
        if (!PrivacyService::canView($this->db, 'journey', (int) $item['journey_id'], (int) $item['owner_id'], $this->viewerId, (string) $item['journey_privacy'])) {
            return false;
        }
        if ($item['item_type'] === 'memory' && !empty($item['source_id'])) {
            $stmt = $this->db->prepare("SELECT id,user_id,privacy_level,folder_id,status FROM memories WHERE id=:id LIMIT 1");
            $stmt->execute(['id' => (int) $item['source_id']]);
            $memory = $stmt->fetch(PDO::FETCH_ASSOC);
            return $memory && $memory['status'] === 'active' && PrivacyService::canView($this->db, 'memory', (int) $memory['id'], (int) $memory['user_id'], $this->viewerId, (string) $memory['privacy_level'], $memory['folder_id'] !== null ? (int) $memory['folder_id'] : null);
        }
        if ($item['item_type'] === 'milestone' && !empty($item['source_id'])) {
            $stmt = $this->db->prepare("SELECT id,user_id,privacy_level,status FROM milestones WHERE id=:id LIMIT 1");
            $stmt->execute(['id' => (int) $item['source_id']]);
            $milestone = $stmt->fetch(PDO::FETCH_ASSOC);
            return $milestone && $milestone['status'] === 'active' && PrivacyService::canView($this->db, 'milestone', (int) $milestone['id'], (int) $milestone['user_id'], $this->viewerId, (string) $milestone['privacy_level']);
        }
        return true;
    }

    private function formatItem(string $type, int $id, string $title, string $preview, string $date, ?string $thumbnail, string $url, DateTimeImmutable $today, ?int $journeyItemId = null): array
    {
        $years = max(1, (int) (new DateTimeImmutable($date, new DateTimeZone('UTC')))->diff($today)->y);
        return [
            'id' => $id,
            'journey_item_id' => $journeyItemId,
            'source_type' => $type,
            'title' => mb_substr($title, 0, 120),
            'preview' => mb_substr(trim(strip_tags($preview)), 0, 180),
            'thumbnail_url' => $thumbnail,
            'original_date' => $date,
            'years_ago' => $years,
            'url' => $url,
        ];
    }

    private function memoryThumbnail(array $memory): ?string
    {
        if (!empty($memory['video_thumbnail_path'])) {
            return 'http://localhost/IAmStillHere/data/uploads/' . ltrim((string) $memory['video_thumbnail_path'], '/');
        }
        if (str_starts_with((string) $memory['file_type'], 'image/')) {
            return 'http://localhost/IAmStillHere/data/uploads/photos/' . rawurlencode((string) $memory['file_path']);
        }
        return null;
    }

    private function postThumbnail(array $post): ?string
    {
        if (empty($post['file_path']) || ($post['media_type'] ?? '') !== 'image') {
            return null;
        }
        return 'http://localhost/IAmStillHere/data/uploads/photos/' . rawurlencode((string) $post['file_path']);
    }
}
