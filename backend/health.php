<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers/RequestContext.php';

header('Content-Type: application/json');

$databaseStatus = 'unavailable';
$httpStatus = 200;

try {
    $database = new Database();
    $connection = $database->getConnection();
    $connection->query('SELECT 1');
    $databaseStatus = 'ok';
} catch (Throwable $exception) {
    $httpStatus = 503;
}

http_response_code($httpStatus);

echo json_encode([
    'success' => $httpStatus === 200,
    'data' => [
        'application' => 'IamAlwaysHere',
        'status' => $httpStatus === 200 ? 'ok' : 'degraded',
        'database' => $databaseStatus,
        'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
        'environment' => getenv('APP_ENV') ?: 'local',
        'timestamp' => gmdate('c'),
    ],
    'message' => $httpStatus === 200 ? 'Health check passed' : 'Health check degraded',
    'errors' => [],
    'request_id' => RequestContext::getRequestId(),
]);
