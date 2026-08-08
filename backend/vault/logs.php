<?php
require_once __DIR__ . '/_vault_helpers.php';
try{ if($_SERVER['REQUEST_METHOD']!=='GET'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;} $db=vault_db(); $actor=vault_require_auth(); $owner=vault_owner_from_request($db,[], $actor); $s=$db->prepare('SELECT id,actor_user_id,document_id,action,created_at FROM vault_access_logs WHERE owner_id=:owner ORDER BY created_at DESC,id DESC LIMIT 100'); $s->execute(['owner'=>$owner]); ApiResponse::success(['logs'=>$s->fetchAll(PDO::FETCH_ASSOC)],'Vault logs loaded.'); }catch(Throwable $e){Logger::error('Vault logs failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to load vault logs.');}
?>
