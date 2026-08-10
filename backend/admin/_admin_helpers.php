<?php
require_once __DIR__ . '/../../config/config.php';
require_once BACKEND_PATH . '/helpers/ApiResponse.php';
require_once BACKEND_PATH . '/helpers/SessionHelper.php';
require_once BACKEND_PATH . '/helpers/CsrfHelper.php';
require_once BACKEND_PATH . '/helpers/Logger.php';

function admin_db(): PDO { return (new Database())->getConnection(); }
function admin_require(): void { if(!SessionHelper::isAuthenticated() || !SessionHelper::isAdmin()){ ApiResponse::forbidden('Admin access required.'); exit; } }
function admin_input(): array { $d=json_decode(file_get_contents('php://input'),true); return is_array($d)?$d:$_POST; }
function admin_csrf(array $data): void { if(!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))){ ApiResponse::forbidden('Invalid CSRF token.'); exit; } }
function admin_table(PDO $db,string $table): bool { $s=$db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:t');$s->execute(['t'=>$table]);return (int)$s->fetchColumn()>0; }
function admin_count(PDO $db,string $sql,array $p=[]): int { try{$s=$db->prepare($sql);$s->execute($p);return (int)$s->fetchColumn();}catch(Throwable $e){return 0;} }
function admin_sum(PDO $db,string $sql,array $p=[]): int { try{$s=$db->prepare($sql);$s->execute($p);return (int)($s->fetchColumn()?:0);}catch(Throwable $e){return 0;} }
function admin_rows(PDO $db,string $sql,array $p=[]): array { try{$s=$db->prepare($sql);$s->execute($p);return $s->fetchAll(PDO::FETCH_ASSOC);}catch(Throwable $e){return [];} }
function admin_badge(string $s): string { return in_array($s,['active','published','completed','delivered','indexed'],true)?'success':(in_array($s,['failed','suspended','cancelled','deleted'],true)?'danger':'secondary'); }
