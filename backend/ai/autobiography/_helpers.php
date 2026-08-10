<?php

require_once __DIR__ . '/../_ai_helpers.php';
require_once __DIR__ . '/../../services/AIAutobiographyService.php';

function ai_autobio_service(PDO $db): AIAutobiographyService { return new AIAutobiographyService($db); }

function ai_autobio_safe_error(Throwable $e, string $operation): void
{
    $code = $e->getMessage();
    if ($code === 'ai_autobiography_forbidden') { ApiResponse::notFound('Autobiography not available.'); return; }
    if ($code === 'ai_autobiography_insufficient_context') { ApiResponse::validation(['context' => 'Not enough approved knowledge for this section.']); return; }
    if ($code === 'ai_api_key_missing') { ApiResponse::serverError('AI configuration is missing.'); return; }
    if ($code === 'ai_rate_limited') { ApiResponse::send(false, [], 'Please wait before trying again.', [], 429); return; }
    Logger::error('AI autobiography operation failed', ['operation' => $operation, 'error_code' => preg_match('/^ai_[a-z_]+$/', $code) ? $code : 'ai_internal_error']);
    ApiResponse::serverError('Autobiography could not be completed.');
}
