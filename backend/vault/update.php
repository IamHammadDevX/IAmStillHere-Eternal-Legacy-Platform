<?php
require_once __DIR__ . '/_vault_helpers.php';
try{
    if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
    $db=vault_db();
    $actor=vault_require_auth();
    $d=vault_input();
    vault_require_csrf($d);
    vault_require_verified();

    $id=(int)($d['document_id']??0);
    $doc=vault_require_document_access($db,$id,$actor);
    if((int)$doc['owner_id']!==$actor){ApiResponse::forbidden('Only owner can update vault document.');exit;}

    $rawName=trim((string)($d['display_name']??''));
    if($rawName===''){ApiResponse::validation(['display_name'=>'Document name is required.']);exit;}
    $name=vault_safe_name($rawName);

    $folder=(int)($d['folder_id']??0);
    if(!vault_folder_owned($db,$folder,$actor)){ApiResponse::validation(['folder_id'=>'Folder not found.']);exit;}

    $s=$db->prepare('UPDATE vault_documents SET display_name=:name, folder_id=:folder, updated_at=NOW() WHERE id=:id AND owner_id=:owner AND deleted_at IS NULL');
    $s->execute(['name'=>$name,'folder'=>$folder?:null,'id'=>$id,'owner'=>$actor]);
    if($s->rowCount()<1){ApiResponse::serverError('Vault document was not updated.');exit;}

    vault_log($db,$actor,$actor,$id,'rename');
    ApiResponse::success(['document_id'=>$id,'display_name'=>$name,'folder_id'=>$folder?:null],'Vault document renamed.');
}catch(Throwable $e){Logger::error('Vault update failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to update vault document.');}
?>
