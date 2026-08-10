<?php
require_once __DIR__ . '/../../config/config.php';
require_once BACKEND_PATH . '/helpers/ApiResponse.php';
require_once BACKEND_PATH . '/helpers/SessionHelper.php';
require_once BACKEND_PATH . '/helpers/CsrfHelper.php';
require_once BACKEND_PATH . '/helpers/Logger.php';
require_once BACKEND_PATH . '/services/GiftService.php';

function gifts_db(): PDO { return (new Database())->getConnection(); }
function gifts_input(): array { $d=json_decode(file_get_contents('php://input'),true); return is_array($d)?$d:$_POST; }
function gifts_user(): int { if(!SessionHelper::isAuthenticated()){ApiResponse::unauthorized();exit;} return (int)SessionHelper::getUserId(); }
function gifts_csrf(array $data): void { if(!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))){ApiResponse::forbidden('Invalid CSRF token.');exit;} }
function gifts_service(PDO $db): GiftService { return new GiftService($db); }
