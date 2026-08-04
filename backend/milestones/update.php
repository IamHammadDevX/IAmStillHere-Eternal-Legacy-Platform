<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed', [], 405); exit; }
if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
$data=json_decode(file_get_contents('php://input'),true);$data=is_array($data)?$data:[];
if(!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))){ApiResponse::forbidden('Invalid CSRF token');exit;}
$id=(int)($data['milestone_id']??0);$title=trim((string)($data['title']??''));$description=trim((string)($data['description']??''));$date=trim((string)($data['milestone_date']??''));$category=trim((string)($data['category']??''));$privacy=(string)($data['privacy_level']??'public');
if($id<=0||$title===''||mb_strlen($title)>255||mb_strlen($description)>10000||$date===''||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)){ApiResponse::validation(['milestone'=>'Valid title, description, and date required']);exit;}
if(!in_array($privacy,['public','family','friends','specific_people','private','release_date','release_event'],true)){ApiResponse::validation(['privacy_level'=>'Invalid privacy level']);exit;}
try{$db=(new Database())->getConnection();$s=$db->prepare('SELECT id,user_id,status FROM milestones WHERE id=:id LIMIT 1');$s->execute(['id'=>$id]);$m=$s->fetch(PDO::FETCH_ASSOC);$viewer=SessionHelper::getUserId();if(!$m||$m['status']!=='active'){ApiResponse::notFound('Milestone not found');exit;}if((int)$m['user_id']!==$viewer&&!SessionHelper::isAdmin()){ApiResponse::forbidden('Only the owner can edit this milestone');exit;}$legacy=in_array($privacy,['public','family','private'],true)?$privacy:'private';$u=$db->prepare('UPDATE milestones SET title=:title,description=:description,milestone_date=:date,category=:category,privacy_level=:privacy WHERE id=:id');$u->execute(['title'=>$title,'description'=>$description?:null,'date'=>$date,'category'=>$category?:null,'privacy'=>$legacy,'id'=>$id]);ApiResponse::success(['milestone_id'=>$id],'Milestone updated');}catch(Throwable $e){error_log('Milestone update error: '.$e->getMessage());ApiResponse::serverError('Unable to update milestone');}
