<?php
require_once __DIR__ . '/_folder_helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed', [], 405); exit; }
$ownerId=folder_require_auth();$data=folder_input();folder_require_csrf($data);$memoryId=(int)($data['memory_id']??0);$folderId=(int)($data['folder_id']??0);
try{$db=folder_connection();$s=$db->prepare("SELECT id FROM memories WHERE id=:id AND user_id=:u AND status='active'");$s->execute(['id'=>$memoryId,'u'=>$ownerId]);if(!$s->fetchColumn()){ApiResponse::notFound('Memory not found');exit;}
 if($folderId>0&&!folder_find($db,$folderId,$ownerId)){ApiResponse::notFound('Folder not found');exit;}
 $db->prepare('UPDATE memories SET folder_id=:f, privacy_override=:o WHERE id=:id AND user_id=:u')->execute(['f'=>$folderId?:null,'o'=>array_key_exists('privacy_override',$data)?(int)(bool)$data['privacy_override']:0,'id'=>$memoryId,'u'=>$ownerId]);ApiResponse::success([], 'Memory moved');
}catch(Throwable $e){error_log('Move memory error: '.$e->getMessage());ApiResponse::serverError('Unable to move memory');}
