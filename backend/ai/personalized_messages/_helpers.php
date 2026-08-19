<?php
require_once __DIR__ . '/../_ai_helpers.php';
require_once __DIR__ . '/../../services/AIPersonalizedMessageService.php';
function ai_pm_service(PDO $db): AIPersonalizedMessageService { return new AIPersonalizedMessageService($db); }
function ai_pm_require_owner(array $data, int $viewer): bool {
    $profileOwner = (int)($data['owner_id'] ?? 0);
    if ($profileOwner <= 0 || $profileOwner !== $viewer) {
        ApiResponse::forbidden('Only the profile owner can manage AI messages.');
        return false;
    }
    return true;
}
function ai_pm_error(Throwable $e,string $op): void {
    $c=$e->getMessage();
    if($c==='ai_message_forbidden'){ApiResponse::forbidden();return;}
    if($c==='ai_rate_limited'){ApiResponse::send(false,[],'Please wait before generating again.',[],429);return;}
    if($c==='ai_message_no_context'){ApiResponse::validation(['knowledge'=>'Build AI knowledge first.']);return;}
    if($c==='ai_api_key_missing'){ApiResponse::serverError('AI configuration is missing.');return;}
    if($e instanceof InvalidArgumentException){ApiResponse::validation(['input'=>$e->getMessage()]);return;}
    Logger::error('AI personalized message failed',['operation'=>$op,'error_code'=>preg_match('/^ai_[a-z_]+$/',$c)?$c:'ai_internal_error']);
    ApiResponse::serverError('Personalized message could not be completed.');
}
