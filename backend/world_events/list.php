<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';

if (!SessionHelper::isAuthenticated()) {
    ApiResponse::unauthorized();
    exit;
}

// Legacy compatibility endpoint. Automatic world-event injection is retired.
ApiResponse::success(['events' => []], 'World-event suggestions are disabled.');