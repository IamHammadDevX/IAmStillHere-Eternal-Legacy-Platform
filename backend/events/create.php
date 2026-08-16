<?php
require_once __DIR__ . '/_event_helpers.php';
header('Content-Type: application/json');
function event_create_response(bool $success,string $message,int $status=200,array $extra=[]):void{http_response_code($status);echo json_encode(array_merge(['success'=>$success,'message'=>$message],$extra));exit;}
if(!SessionHelper::isAuthenticated())event_create_response(false,'Unauthorized',401);
if($_SERVER['REQUEST_METHOD']!=='POST')event_create_response(false,'Method not allowed',405);
$contentType=(string)($_SERVER['CONTENT_TYPE']??'');
$data=strpos($contentType,'application/json')!==false?(json_decode(file_get_contents('php://input'),true)?:[]):$_POST;
$csrf=CsrfHelper::getTokenFromRequest($data);
if(!CsrfHelper::validate($csrf))event_create_response(false,'Invalid security token. Refresh the page and try again.',403);
$title=trim((string)($data['title']??''));$message=trim((string)($data['message']??''));$scheduledDate=trim((string)($data['scheduled_date']??''));$eventType=trim((string)($data['event_type']??'message'))?:'message';$privacy=trim((string)($data['privacy_level']??'public'));
if($title===''||$message===''||$scheduledDate==='')event_create_response(false,'Title, message, and scheduled date are required.',422);
if(mb_strlen($title)>255)event_create_response(false,'Title must be 255 characters or fewer.',422);
if(!in_array($privacy,PrivacyService::allowedTypes(),true))event_create_response(false,'Invalid privacy option.',422);
$timestamp=strtotime($scheduledDate);if($timestamp===false||$timestamp<=time())event_create_response(false,'Scheduled date must be in the future.',422);
$media=['path'=>null,'mime'=>null,'type'=>null];
try{$ownerId=(int)SessionHelper::getUserId();if(isset($_FILES['media']))$media=event_store_media($_FILES['media'],$ownerId);$db=event_db();$statement=$db->prepare('INSERT INTO scheduled_events (user_id,title,message,media_path,media_mime,media_type,scheduled_date,event_type,privacy_level) VALUES (:user,:title,:message,:path,:mime,:media_type,:scheduled,:event_type,:privacy)');$statement->execute(['user'=>$ownerId,'title'=>$title,'message'=>$message,'path'=>$media['path'],'mime'=>$media['mime'],'media_type'=>$media['type'],'scheduled'=>$scheduledDate,'event_type'=>$eventType,'privacy'=>$privacy]);event_create_response(true,'Event scheduled successfully',201,['event_id'=>(int)$db->lastInsertId()]);}
catch(InvalidArgumentException $error){event_create_response(false,$error->getMessage(),422);}
catch(Throwable $error){if(!empty($media['path'])){$file=event_media_file($media['path']);if($file&&is_file($file))@unlink($file);}error_log('Create event error: '.$error->getMessage());event_create_response(false,'Unable to schedule the event.',500);}
