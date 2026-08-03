<?php
require_once __DIR__ . '/_folder_helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed', [], 405); exit; }
$ownerId=folder_require_auth();$data=folder_input();folder_require_csrf($data);$id=(int)($data['folder_id']??0);
try{$db=folder_connection();$folder=folder_find($db,$id,$ownerId);if(!$folder){ApiResponse::notFound('Folder not found');exit;}
 $s=$db->prepare("SELECT (SELECT COUNT(*) FROM memories WHERE folder_id=:id AND status='active') + (SELECT COUNT(*) FROM memory_folders WHERE parent_folder_id=:id2 AND deleted_at IS NULL) AS total");$s->execute(['id'=>$id,'id2'=>$id]);if((int)$s->fetchColumn()>0){ApiResponse::validation(['folder'=>'Folder must be empty before deletion']);exit;}
 $db->prepare('UPDATE memory_folders SET deleted_at=CURRENT_TIMESTAMP WHERE id=:id AND user_id=:u')->execute(['id'=>$id,'u'=>$ownerId]);ApiResponse::success([], 'Folder deleted');
}catch(Throwable $e){error_log('Folder delete error: '.$e->getMessage());ApiResponse::serverError('Unable to delete folder');}
