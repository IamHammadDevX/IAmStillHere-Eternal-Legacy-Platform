<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/RequestContext.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../services/NotificationService.php';

function notifications_connection(): PDO
{
    $database = new Database();
    return $database->getConnection();
}

function notifications_json_input(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function notifications_require_user(): ?int
{
    if (!SessionHelper::isAuthenticated()) {
        ApiResponse::unauthorized();
        return null;
    }

    return SessionHelper::getUserId();
}

function notifications_require_csrf(array $data): bool
{
    return CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data));
}

function notifications_format(array $row): array
{
    $row['id'] = (int) $row['id'];
    $row['recipient_user_id'] = (int) $row['recipient_user_id'];
    $row['actor_user_id'] = $row['actor_user_id'] !== null ? (int) $row['actor_user_id'] : null;
    $row['related_resource_id'] = $row['related_resource_id'] !== null ? (int) $row['related_resource_id'] : null;
    $row['is_read'] = (bool) $row['is_read'];
    $row['actor_name'] = $row['actor_name'] ?: 'Someone';
    $row['actor_profile_photo'] = $row['actor_profile_photo'] ?: null;
    $row['link'] = NotificationService::linkFor($row);
    return $row;
}