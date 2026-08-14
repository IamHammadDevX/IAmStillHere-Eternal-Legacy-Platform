<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../services/WorldEventsProvider.php';

try {
    if (!SessionHelper::isAuthenticated()) {
        ApiResponse::unauthorized();
        exit;
    }

    $raw = trim((string) ($_GET['years'] ?? ''));
    $years = array_values(array_filter(
        array_map('intval', explode(',', $raw)),
        static fn (int $year): bool => $year >= 1 && $year <= 2100
    ));

    if (!$years) {
        ApiResponse::success(['events' => []]);
        exit;
    }

    $provider = new WorldEventsProvider();
    ApiResponse::success(['events' => $provider->eventsForYears($years)]);
} catch (Throwable $e) {
    ApiResponse::serverError('Unable to load world events.');
}
