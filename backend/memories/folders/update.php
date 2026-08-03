<?php
require_once __DIR__ . '/_folder_helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed', [], 405); exit; }
$ownerId=folder_require_auth(); $data=folder_input(); folder_require_csrf($data); $id=(int)($data['folder_id']??0);
try { $db=folder_connection(); $folder=folder_find($db,$id,$ownerId); if(!$folder){ApiResponse::notFound('Folder not found');exit;}
    $fields=[];$params=['id'=>$id,'u'=>$ownerId];
    if(array_key_exists('name',$data)){ $name=folder_name($data['name']); if($name===''||mb_strlen($name)>150){ApiResponse::validation(['name'=>'Invalid folder name']);exit;} $fields[]='name=:n';$params['n']=$name; }
    if(array_key_exists('description',$data)){ $fields[]='description=:d';$params['d']=trim((string)$data['description']) ?: null; }
    if(array_key_exists('privacy_level',$data)){ $privacy=folder_privacy($data['privacy_level']); if($privacy===null){ApiResponse::validation(['privacy_level'=>'Invalid privacy level']);exit;} $fields[]='privacy_level=:v';$params['v']=$privacy; }
    if(array_key_exists('parent_folder_id',$data)){ $parent=(int)$data['parent_folder_id']; if($parent>0 && (!folder_find($db,$parent,$ownerId)||folder_is_descendant($db,$parent,$id,$ownerId))){ApiResponse::validation(['parent_folder_id'=>'Invalid or circular parent folder']);exit;} $fields[]='parent_folder_id=:p';$params['p']=$parent?:null; }
    if(!$fields){ApiResponse::validation(['folder'=>'No changes supplied']);exit;}
    $db->prepare('UPDATE memory_folders SET '.implode(',',$fields).' WHERE id=:id AND user_id=:u')->execute($params); ApiResponse::success([], 'Folder updated');
} catch(Throwable $e){error_log('Folder update error: '.$e->getMessage());ApiResponse::serverError('Unable to update folder');}
