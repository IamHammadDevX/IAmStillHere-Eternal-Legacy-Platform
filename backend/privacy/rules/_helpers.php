<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/ApiResponse.php';
require_once __DIR__ . '/../../helpers/SessionHelper.php';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../services/PrivacyService.php';
function privacy_db(): PDO { return (new Database())->getConnection(); }
function privacy_data(): array { $d=json_decode(file_get_contents('php://input'),true);return is_array($d)?$d:$_POST; }
function privacy_auth(): int { $id=SessionHelper::getUserId();if($id===null){ApiResponse::unauthorized();exit;}return $id; }
function privacy_csrf(array $d): void { if(!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($d))){ApiResponse::forbidden('Invalid CSRF token');exit;} }
