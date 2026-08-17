<?php
require_once __DIR__ . '/_scheduled_helpers.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::send(false, [], 'Method not allowed.', [], 405);
        exit;
    }

    $db = posts_connection();
    $owner = scheduled_post_require_owner($db);
    $data = scheduled_post_input();
    if (!posts_require_csrf($data)) {
        ApiResponse::forbidden('Invalid CSRF token.');
        exit;
    }

    $id = (int)($data['scheduled_post_id'] ?? 0);
    $body = posts_sanitize_body((string)($data['body'] ?? '')); 
    if ($body === '') {
        ApiResponse::validation(['body' => 'Post text is required.']);
        exit;
    }
    if (mb_strlen($body) > POST_BODY_MAX_LENGTH) {
        ApiResponse::validation(['body' => 'Post cannot exceed 5000 characters.']);
        exit;
    }

    $rowStmt = $db->prepare("SELECT automation_rule_id,media_file_path,media_file_type,media_file_size,media_type FROM scheduled_wall_posts WHERE id=:id AND owner_id=:owner AND status='scheduled' LIMIT 1");
    $rowStmt->execute(['id' => $id, 'owner' => $owner]);
    $row = $rowStmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        ApiResponse::notFound('Scheduled post not found.');
        exit;
    }
    $ruleId = (int)$row['automation_rule_id'];

    $privacy = (string)($data['privacy_level'] ?? 'private');
    if (!in_array($privacy, ['public', 'family', 'friends', 'specific_people', 'private', 'release_date', 'release_event'], true)) {
        $privacy = 'private';
    }

    [$trigger, $at, $linkedType, $linkedId] = scheduled_post_validate_trigger($db, $owner, $data);
    $recMonth = null;
    $recDay = null;
    if (in_array($trigger, ['birthday', 'anniversary', 'custom_recurring'], true) && $at) {
        $dt = new DateTimeImmutable($at, new DateTimeZone('UTC'));
        $recMonth = (int)$dt->format('n');
        $recDay = (int)$dt->format('j');
    }

    $db->beginTransaction();
    $db->prepare("UPDATE scheduled_wall_posts SET body=:body,privacy_level=:privacy,trigger_type=:trigger,trigger_at=:at,linked_resource_type=:lt,linked_resource_id=:lid,updated_at=UTC_TIMESTAMP() WHERE id=:id AND owner_id=:owner AND status='scheduled'")
        ->execute(['body' => $body, 'privacy' => $privacy, 'trigger' => $trigger, 'at' => $at, 'lt' => $linkedType, 'lid' => $linkedId ?: null, 'id' => $id, 'owner' => $owner]);

    $payload = [
        'body' => $body,
        'privacy_level' => scheduled_post_legacy_privacy($privacy),
        'scheduled_wall_post_id' => $id,
        'media_file_path' => $row['media_file_path'],
        'media_file_type' => $row['media_file_type'],
        'media_file_size' => (int)($row['media_file_size'] ?? 0),
        'media_type' => $row['media_type'],
    ];

    $db->prepare("UPDATE automation_rules SET trigger_type=:trigger, trigger_datetime=:at, recurring_month=:rm, recurring_day=:rd, linked_resource_type=:lt, linked_resource_id=:lid, next_run_at=:at, updated_at=UTC_TIMESTAMP() WHERE id=:rule AND owner_id=:owner AND status='scheduled'")
        ->execute(['trigger' => $trigger, 'at' => $at, 'rm' => $recMonth, 'rd' => $recDay, 'lt' => $linkedType, 'lid' => $linkedId ?: null, 'rule' => $ruleId, 'owner' => $owner]);
    $db->prepare("UPDATE automation_actions SET payload=:payload WHERE rule_id=:rule AND action_type='wall_post'")
        ->execute(['payload' => json_encode($payload, JSON_UNESCAPED_SLASHES), 'rule' => $ruleId]);

    $db->commit();
    ApiResponse::success([], 'Scheduled post updated.');
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    if ($e instanceof InvalidArgumentException) {
        ApiResponse::validation(['schedule' => $e->getMessage()]);
    } else {
        ApiResponse::serverError('Unable to update scheduled post.');
    }
}
?>
