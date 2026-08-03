<?php
require_once __DIR__ . '/_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }

    $profileUserId = (int) ($_GET['user_id'] ?? 0);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(25, max(1, (int) ($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if ($profileUserId <= 0) { ApiResponse::validation(['user_id' => 'Valid user_id is required.']); exit; }
    if (!posts_can_view_profile($connection, $profileUserId)) { ApiResponse::forbidden('You are not allowed to view this profile.'); exit; }

    $privacy = posts_visible_privacy_condition($connection, $profileUserId);
    $count = $connection->prepare("SELECT COUNT(*) AS total FROM posts p WHERE p.user_id = :user_id AND p.status = 'active' AND {$privacy}");
    $count->execute(['user_id' => $profileUserId]);
    $total = (int) ($count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $connection->prepare(
        "SELECT p.*, u.full_name AS author_name, u.profile_photo AS author_profile_photo
         FROM posts p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.user_id = :user_id AND p.status = 'active' AND {$privacy}
         ORDER BY p.created_at DESC, p.id DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':user_id', $profileUserId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $posts = array_map(fn($post) => posts_format_post($connection, $post), $stmt->fetchAll(PDO::FETCH_ASSOC));
    ApiResponse::success(['posts' => $posts, 'pagination' => ['current_page' => $page, 'per_page' => $limit, 'total_items' => $total, 'total_pages' => (int) ceil($total / $limit)]], 'Posts loaded.');
} catch (Throwable $e) {
    Logger::error('Post list failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Unable to load posts.');
}
?>
