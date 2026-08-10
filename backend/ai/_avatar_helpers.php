<?php

require_once __DIR__ . '/_ai_helpers.php';
require_once __DIR__ . '/../services/AIAvatarService.php';

function ai_avatar_service(PDO $db): AIAvatarService { return new AIAvatarService($db); }

function ai_avatar_safe_error(Throwable $e, string $operation): void
{
    $code = $e->getMessage();
    if ($code === 'ai_avatar_forbidden') { ApiResponse::notFound('AI Avatar chat not available.'); return; }
    if ($code === 'ai_rate_limited') { ApiResponse::send(false, [], 'Please wait before sending another message.', [], 429); return; }
    Logger::error('AI avatar operation failed', ['operation' => $operation, 'error_code' => preg_match('/^ai_[a-z_]+$/', $code) ? $code : 'ai_internal_error']);
    ApiResponse::serverError('AI Avatar could not be completed.');
}
