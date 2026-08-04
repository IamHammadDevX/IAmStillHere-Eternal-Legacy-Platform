<?php
require_once __DIR__ . '/_helpers.php';
$owner=privacy_auth();$type=trim((string)($_GET['resource_type']??''));$id=(int)($_GET['resource_id']??0);
if($type===''||$id<=0){ApiResponse::validation(['resource'=>'Resource type and ID are required']);exit;}
try{$db=privacy_db();$r=PrivacyService::getRule($db,$type,$id);if($r&&((int)$r['created_by']!==$owner&&!SessionHelper::isAdmin())){ApiResponse::forbidden();exit;}ApiResponse::success(['rule'=>$r]);}catch(Throwable $e){error_log('Privacy rule list: '.$e->getMessage());ApiResponse::serverError('Unable to load privacy rule');}
