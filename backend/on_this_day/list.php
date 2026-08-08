<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../services/OnThisDayService.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ApiResponse::send(false, [], 'Method not allowed.', [], 405);
        exit;
    }
    if (!SessionHelper::isAuthenticated()) {
        ApiResponse::unauthorized();
        exit;
    }

    $db = (new Database())->getConnection();
    $service = new OnThisDayService($db, (int) SessionHelper::getUserId());
    $limit = (int) ($_GET['limit'] ?? 8);
    $page = (int) ($_GET['page'] ?? 1);
    $today = null;
    if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date'])) {
        $today = new DateTimeImmutable((string) $_GET['date'], new DateTimeZone('UTC'));
    }

    ApiResponse::success($service->list($limit, $page, $today), 'On This Day loaded.');
} catch (Throwable $exception) {
    Logger::error('On This Day failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to load On This Day.');
}
