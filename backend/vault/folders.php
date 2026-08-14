<?php
require_once __DIR__ . '/_vault_helpers.php';
try{
    if($_SERVER['REQUEST_METHOD']!=='POST'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
    $db=vault_db(); $actor=vault_require_auth(); $d=vault_input(); vault_require_csrf($d); vault_require_verified();
    $action=(string)($d['action']??'create');
    if($action==='create'){
        $name=trim((string)($d['name']??'')); if($name===''){ApiResponse::validation(['name'=>'Folder name is required.']);exit;} $name=vault_safe_name($name); $parent=(int)($d['parent_folder_id']??0);
        if(!vault_folder_owned($db,$parent,$actor)){ApiResponse::validation(['parent_folder_id'=>'Parent folder not found.']);exit;}
        $s=$db->prepare('INSERT INTO vault_folders(owner_id,parent_folder_id,name) VALUES(:owner,:parent,:name)');
        $s->execute(['owner'=>$actor,'parent'=>$parent?:null,'name'=>$name]); vault_log($db,$actor,$actor,null,'folder_create');
        ApiResponse::success(['folder_id'=>(int)$db->lastInsertId()],'Vault folder created.',201); exit;
    }
    if($action==='update'){
        $id=(int)($d['folder_id']??0); $name=trim((string)($d['name']??'')); if($name===''){ApiResponse::validation(['name'=>'Folder name is required.']);exit;} $name=vault_safe_name($name); $parent=(int)($d['parent_folder_id']??0);
        if(!$id||!vault_folder_owned($db,$id,$actor)||!vault_folder_owned($db,$parent,$actor)||$parent===$id||vault_is_descendant($db,$parent,$id,$actor)){ApiResponse::validation(['folder'=>'Invalid folder hierarchy.']);exit;}
        $s=$db->prepare('UPDATE vault_folders SET name=:name,parent_folder_id=:parent WHERE id=:id AND owner_id=:owner');
        $s->execute(['name'=>$name,'parent'=>$parent?:null,'id'=>$id,'owner'=>$actor]); vault_log($db,$actor,$actor,null,'folder_update'); ApiResponse::success([],'Vault folder updated.'); exit;
    }
    if($action==='delete'){
        $id=(int)($d['folder_id']??0); if(!$id||!vault_folder_owned($db,$id,$actor)){ApiResponse::notFound('Folder not found.');exit;}
        $child=$db->prepare('SELECT id FROM vault_folders WHERE parent_folder_id=:id AND deleted_at IS NULL LIMIT 1'); $child->execute(['id'=>$id]);
        $doc=$db->prepare('SELECT id FROM vault_documents WHERE folder_id=:id AND deleted_at IS NULL LIMIT 1'); $doc->execute(['id'=>$id]);
        if($child->fetchColumn()||$doc->fetchColumn()){ApiResponse::validation(['folder'=>'Cannot delete this folder yet. Move or delete its documents and subfolders first.']);exit;}
        $s=$db->prepare('UPDATE vault_folders SET deleted_at=NOW() WHERE id=:id AND owner_id=:owner'); $s->execute(['id'=>$id,'owner'=>$actor]); vault_log($db,$actor,$actor,null,'folder_delete'); ApiResponse::success([],'Vault folder deleted.'); exit;
    }
    ApiResponse::validation(['action'=>'Invalid action.']);
}catch(Throwable $e){Logger::error('Vault folder failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to update vault folder.');}
?>
