<?php
require_once __DIR__ . '/_media_helpers.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $db=journeys_db(); $uid=journeys_require_auth(); if ($uid===null) exit;
    if (!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($_POST))) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }
    $id=(int)($_POST['journey_id']??0); $journey=$id?journeys_find($db,$id):null;
    if (!$journey || !journeys_can_view($db,$journey,$uid)) { ApiResponse::notFound('Journey not found.'); exit; }
    if (!journeys_can_contribute($db,$journey,$uid)) { ApiResponse::forbidden('Only accepted participants can add media.'); exit; }
    $title=trim((string)($_POST['title']??'')); $description=trim((string)($_POST['description']??'')); $date=trim((string)($_POST['item_date']??''));
    if ($title==='' || mb_strlen($title)>255 || mb_strlen($description)>JOURNEY_ITEM_NOTE_MAX) throw new InvalidArgumentException('Add a title and a shorter description.');
    if ($date!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) throw new InvalidArgumentException('Invalid media date.');
    $media=journey_upload_file($_FILES['media']??[], 'items'); $status=journeys_can_manage($db,$journey,$uid)?'approved':'pending';
    $q=$db->prepare("INSERT INTO journey_items (journey_id,contributor_id,item_type,title,description,item_date,media_path,media_mime,status) VALUES (:j,:u,'media',:title,:description,:date,:path,:mime,:status)");
    $q->execute(['j'=>$id,'u'=>$uid,'title'=>$title,'description'=>$description?:null,'date'=>$date?:null,'path'=>$media['path'],'mime'=>$media['mime'],'status'=>$status]);
    if ($status==='pending') NotificationService::createOnce($db,(int)$journey['owner_id'],$uid,NotificationService::TYPE_JOURNEY_INVITATION,'journey',$id,'submitted a journey photo or video.');
    ApiResponse::success(['item_id'=>(int)$db->lastInsertId(),'status'=>$status], $status==='approved'?'Media added to journey.':'Media sent for approval.',201);
} catch (Throwable $e) { Logger::error('Journey media upload failed',['error'=>$e->getMessage()]); ApiResponse::serverError($e instanceof InvalidArgumentException?$e->getMessage():'Unable to upload journey media.'); }
