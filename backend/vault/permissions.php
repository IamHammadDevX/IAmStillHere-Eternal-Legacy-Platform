<?php
require_once __DIR__ . '/_vault_helpers.php';
try{
    if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
    $db=vault_db(); $actor=vault_require_auth(); $d=vault_input(); vault_require_csrf($d); vault_require_verified();
    $identifier=trim((string)($d['user_identifier']??$d['user_id']??'')); $action=(string)($d['action']??'grant');
    if($identifier===''){ApiResponse::validation(['user_identifier'=>'Valid user required.']);exit;}
    if(ctype_digit($identifier)){
        $u=$db->prepare("SELECT id FROM users WHERE id=:id AND status='active' LIMIT 1");
        $u->execute(['id'=>(int)$identifier]);
    } else {
        $u=$db->prepare("SELECT id FROM users WHERE status='active' AND (username=:value OR full_name=:value) LIMIT 1");
        $u->execute(['value'=>$identifier]);
    }
    $target=(int)$u->fetchColumn();
    if($target<=0||$target===$actor){ApiResponse::validation(['user_identifier'=>'Active different user required.']);exit;}
    if($action==='grant'){
        $s=$db->prepare("INSERT INTO vault_permissions(owner_id,authorized_user_id,role,status,revoked_at) VALUES(:owner,:user,'legal_counsel','active',NULL) ON DUPLICATE KEY UPDATE status='active', role='legal_counsel', revoked_at=NULL");
        $s->execute(['owner'=>$actor,'user'=>$target]); vault_log($db,$actor,$actor,null,'permission_change'); ApiResponse::success([],'Vault permission granted.'); exit;
    }
    if($action==='revoke'){
        $s=$db->prepare("UPDATE vault_permissions SET status='revoked', revoked_at=NOW() WHERE owner_id=:owner AND authorized_user_id=:user");
        $s->execute(['owner'=>$actor,'user'=>$target]); vault_log($db,$actor,$actor,null,'permission_change'); ApiResponse::success([],'Vault permission revoked.'); exit;
    }
    ApiResponse::validation(['action'=>'Invalid action.']);
}catch(Throwable $e){Logger::error('Vault permission failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to update vault permission.');}
?>
