<?php
require_once __DIR__ . '/_post_helpers.php';

function scheduled_post_input(): array { return $_POST ?: posts_json_input(); }
function scheduled_post_require_owner(PDO $db): int { if(!SessionHelper::isAuthenticated()){ApiResponse::unauthorized();exit;} if(!posts_require_active_account($db)){ApiResponse::forbidden('Active account required.');exit;} return (int)SessionHelper::getUserId(); }
function scheduled_post_utc(?string $value): ?string { if(!$value)return null; try{return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}catch(Throwable $e){return null;} }
function scheduled_post_legacy_privacy(string $privacy): string { return in_array($privacy,['public','family','private'],true)?$privacy:'private'; }
function scheduled_post_validate_trigger(PDO $db, int $owner, array $data): array {
    $trigger=(string)($data['trigger_type']??'specific_datetime');
    if(!in_array($trigger,['specific_datetime','birthday','anniversary','custom_recurring','linked_milestone_event'],true)) $trigger='specific_datetime';
    $at=scheduled_post_utc((string)($data['trigger_at']??''));
    $linkedType=$data['linked_resource_type']??null; $linkedId=(int)($data['linked_resource_id']??0);
    if($trigger==='linked_milestone_event'){
        if(!in_array($linkedType,['milestone','event'],true)||$linkedId<=0) throw new InvalidArgumentException('Valid linked milestone/event required.');
        if($linkedType==='milestone'){$s=$db->prepare('SELECT milestone_date FROM milestones WHERE id=:id AND user_id=:u LIMIT 1');$s->execute(['id'=>$linkedId,'u'=>$owner]);$date=$s->fetchColumn(); if(!$date) throw new InvalidArgumentException('Linked milestone not found.'); $at=$at?:scheduled_post_utc($date.' 09:00:00');}
        else{$s=$db->prepare('SELECT scheduled_date FROM scheduled_events WHERE id=:id AND user_id=:u LIMIT 1');$s->execute(['id'=>$linkedId,'u'=>$owner]);$date=$s->fetchColumn(); if(!$date) throw new InvalidArgumentException('Linked event not found.'); $at=$at?:scheduled_post_utc((string)$date);}
    } else {$linkedType=null;$linkedId=0;}
    if(!$at && in_array($trigger,['specific_datetime','linked_milestone_event'],true)) throw new InvalidArgumentException('Future schedule time required.');
    if($at && strtotime($at)<=time() && $trigger==='specific_datetime') throw new InvalidArgumentException('Schedule time must be in the future.');
    return [$trigger,$at,$linkedType,$linkedId];
}
function scheduled_post_store_media(PDO $db, ?array $file): array {
    if(!$file || empty($file['name'])) return [null,null,null,null];
    $fileType=$file['type']??''; $fileSize=(int)($file['size']??0); $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    $isImage=in_array($fileType,ALLOWED_IMAGE_TYPES,true)||in_array($ext,['jpg','jpeg','png','gif','webp','bmp','svg','tiff'],true);
    $isVideo=in_array($fileType,ALLOWED_VIDEO_TYPES,true)||in_array($ext,['mp4','avi','mov','mkv','webm','mpeg','mpg','3gp','flv','wmv'],true);
    if(!$isImage&&!$isVideo) throw new RuntimeException('Only image or video media is allowed.');
    if($fileSize>MAX_FILE_SIZE) throw new RuntimeException('File size exceeds maximum allowed.');
    $mediaType=$isVideo?'video':'image'; $dir=UPLOAD_PATH.'/'.($isVideo?'videos':'photos').'/'; if(!is_dir($dir))mkdir($dir,0775,true);
    $name=uniqid('scheduled_post_').'.'.$ext; if(!move_uploaded_file($file['tmp_name'],$dir.$name)) throw new RuntimeException('Media upload failed.');
    return [$name,$fileType,$fileSize,$mediaType];
}
function scheduled_post_format(array $r): array { foreach(['id','owner_id','automation_rule_id','published_post_id','linked_resource_id'] as $k)$r[$k]=$r[$k]!==null?(int)$r[$k]:null; return $r; }
