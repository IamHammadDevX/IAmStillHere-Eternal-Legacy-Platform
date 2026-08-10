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

    $body = trim((string)($data['body'] ?? ''));
    if ($body === '') {
        ApiResponse::validation(['body' => 'Post text is required.']);
        exit;
    }
    if (mb_strlen($body) > POST_BODY_MAX_LENGTH) {
        ApiResponse::validation(['body' => 'Post cannot exceed 5000 characters.']);
        exit;
    }

    $privacy = (string)($data['privacy_level'] ?? 'private');
    if (!in_array($privacy, ['public', 'family', 'friends', 'specific_people', 'private', 'release_date', 'release_event'], true)) {
        $privacy = 'private';
    }

    [$trigger, $at, $linkedType, $linkedId] = scheduled_post_validate_trigger($db, $owner, $data);
    [$mediaPath, $mediaTypeText, $mediaSize, $mediaType] = scheduled_post_store_media($db, $_FILES['media'] ?? null);
    $recMonth = null;
    $recDay = null;
    if (in_array($trigger, ['birthday', 'anniversary', 'custom_recurring'], true) && $at) {
        $dt = new DateTimeImmutable($at, new DateTimeZone('UTC'));
        $recMonth = (int)$dt->format('n');
        $recDay = (int)$dt->format('j');
    }

    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO scheduled_wall_posts(owner_id,body,privacy_level,trigger_type,trigger_at,linked_resource_type,linked_resource_id,media_file_path,media_file_type,media_file_size,media_type,status) VALUES(:owner,:body,:privacy,:trigger,:at,:lt,:lid,:mp,:mft,:mfs,:mt,'scheduled')");
    $stmt->execute([
        'owner' => $owner,
        'body' => $body,
        'privacy' => $privacy,
        'trigger' => $trigger,
        'at' => $at,
        'lt' => $linkedType,
        'lid' => $linkedId ?: null,
        'mp' => $mediaPath,
        'mft' => $mediaTypeText,
        'mfs' => $mediaSize,
        'mt' => $mediaType,
    ]);
    $scheduledId = (int)$db->lastInsertId();

    $ruleStmt = $db->prepare("INSERT INTO automation_rules(owner_id,title,description,trigger_type,trigger_datetime,recurring_month,recurring_day,linked_resource_type,linked_resource_id,next_run_at,status) VALUES(:owner,:title,'Scheduled wall post',:trigger,:at,:rm,:rd,:lt,:lid,:at,'scheduled')");
    $ruleStmt->execute([
        'owner' => $owner,
        'title' => 'Scheduled wall post',
        'trigger' => $trigger,
        'at' => $at,
        'rm' => $recMonth,
        'rd' => $recDay,
        'lt' => $linkedType,
        'lid' => $linkedId ?: null,
    ]);
    $ruleId = (int)$db->lastInsertId();

    $payload = [
        'body' => $body,
        'privacy_level' => scheduled_post_legacy_privacy($privacy),
        'scheduled_wall_post_id' => $scheduledId,
        'media_file_path' => $mediaPath,
        'media_file_type' => $mediaTypeText,
        'media_file_size' => $mediaSize,
        'media_type' => $mediaType,
    ];
    $db->prepare('INSERT INTO automation_actions(rule_id,action_type,payload) VALUES(:rule,"wall_post",:payload)')
        ->execute(['rule' => $ruleId, 'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES)]);
    $db->prepare('UPDATE scheduled_wall_posts SET automation_rule_id=:rule WHERE id=:id')
        ->execute(['rule' => $ruleId, 'id' => $scheduledId]);

    $db->commit();
    ApiResponse::success(['scheduled_post_id' => $scheduledId, 'automation_rule_id' => $ruleId], 'Wall post scheduled.', 201);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    Logger::error('Schedule wall post failed', ['error' => $e->getMessage()]);
    if ($e instanceof InvalidArgumentException) {
        ApiResponse::validation(['schedule' => $e->getMessage()]);
    } else {
        ApiResponse::serverError($e instanceof RuntimeException ? $e->getMessage() : 'Unable to schedule post.');
    }
}
?>
