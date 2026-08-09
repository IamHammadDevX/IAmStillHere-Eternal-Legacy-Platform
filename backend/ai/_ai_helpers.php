<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../services/AIKnowledgeService.php';

function ai_db(): PDO{return (new Database())->getConnection();}
function ai_input(): array{$data=json_decode(file_get_contents('php://input'),true);return is_array($data)?$data:[];}
function ai_require_user(PDO $db): ?int{$id=SessionHelper::getUserId();if($id===null){ApiResponse::unauthorized();return null;}$s=$db->prepare("SELECT id FROM users WHERE id=:id AND status='active'");$s->execute(['id'=>$id]);if(!$s->fetchColumn()){ApiResponse::forbidden();return null;}return $id;}
function ai_require_csrf(array $data): bool{if(CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data)))return true;ApiResponse::forbidden('Invalid request token.');return false;}
function ai_method(string $method): bool{if($_SERVER['REQUEST_METHOD']===$method)return true;ApiResponse::send(false,[],'Method not allowed.',[],405);return false;}
function ai_safe_error(Throwable $e,string $operation): void{Logger::error('AI knowledge operation failed',['operation'=>$operation,'error_code'=>preg_match('/^ai_[a-z_]+$/',$e->getMessage())?$e->getMessage():'ai_internal_error']);ApiResponse::serverError('AI knowledge operation could not be completed.');}
