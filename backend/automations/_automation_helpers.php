<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../services/NotificationService.php';

const AUTOMATION_TRIGGER_TYPES = ['specific_datetime','birthday','anniversary','custom_recurring','linked_milestone_event'];
const AUTOMATION_ACTION_TYPES = ['email','wall_post','notification'];
const AUTOMATION_STATUSES = ['draft','scheduled','processing','completed','failed','cancelled'];

function automation_db(): PDO { return (new Database())->getConnection(); }
function automation_input(): array { $d=json_decode(file_get_contents('php://input'), true); return is_array($d)?$d:$_POST; }
function automation_user(): ?int { return SessionHelper::getUserId(); }
function automation_require_auth(): int { if(!SessionHelper::isAuthenticated()){ApiResponse::unauthorized();exit;} return (int)SessionHelper::getUserId(); }
function automation_require_csrf(array $data) { if(!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))){ApiResponse::forbidden('Invalid CSRF token.');exit;} }
function automation_clean(string $v, int $max=255): string { return mb_substr(trim($v),0,$max); }
function automation_utc_datetime(?string $value): ?string { if(!$value)return null; try { return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'); } catch(Throwable $e){ return null; } }
function automation_next_run(array $data): ?string {
    $type=(string)($data['trigger_type']??'');
    if($type==='specific_datetime') return automation_utc_datetime($data['trigger_datetime']??null);
    if(in_array($type,['birthday','anniversary','custom_recurring'],true)){
        $m=(int)($data['recurring_month']??0); $d=(int)($data['recurring_day']??0);
        if($m<1||$m>12||$d<1||$d>31) return null;
        $year=(int)gmdate('Y');
        for($i=0;$i<3;$i++){
            if(checkdate($m,$d,$year+$i)){
                $dt=new DateTimeImmutable(sprintf('%04d-%02d-%02d 09:00:00',$year+$i,$m,$d), new DateTimeZone('UTC'));
                if($dt->getTimestamp()>time()) return $dt->format('Y-m-d H:i:s');
            }
        }
    }
    if($type==='linked_milestone_event') return automation_utc_datetime($data['trigger_datetime']??null);
    return null;
}
function automation_validate_actions(PDO $db, array $actions, int $owner): array {
    if (!count($actions)) throw new InvalidArgumentException('Select at least one action.');
    $safeActions=[];
    foreach($actions as $action){
        $type=(string)($action['action_type']??'');
        if(!in_array($type,AUTOMATION_ACTION_TYPES,true)) throw new InvalidArgumentException('Unknown automation action.');
        $payload=is_array($action['payload']??null)?$action['payload']:[];
        $message=automation_clean((string)($payload['message']??''),10000);
        if($type==='notification'){
            $recipientId=(int)($payload['recipient_user_id']??0);
            if($recipientId<=0) throw new InvalidArgumentException('Choose a user for the in-app notification.');
            $recipient=$db->prepare("SELECT id,COALESCE(NULLIF(full_name,''),username) AS recipient_name FROM users WHERE id=:id AND status='active' AND role<>'admin' LIMIT 1");
            $recipient->execute(['id'=>$recipientId]);
            $recipientRow=$recipient->fetch(PDO::FETCH_ASSOC);
            if(!$recipientRow) throw new InvalidArgumentException('The notification recipient is unavailable.');
            if($recipientId!==$owner){
                $blocked=$db->prepare("SELECT id FROM friendships WHERE ((user_id=:owner AND friend_id=:recipient) OR (user_id=:recipient AND friend_id=:owner)) AND status='blocked' LIMIT 1");
                $blocked->execute(['owner'=>$owner,'recipient'=>$recipientId]);
                if($blocked->fetchColumn()) throw new InvalidArgumentException('The notification recipient is not available.');
            }
            $payload=['message'=>$message,'recipient_user_id'=>$recipientId,'recipient_name'=>(string)$recipientRow['recipient_name']];
        } elseif($type==='email'){
            $recipientEmail=trim((string)($payload['recipient_email']??''));
            if(!filter_var($recipientEmail,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid recipient email address.');
            $recipientName=automation_clean(strip_tags((string)($payload['recipient_name']??'')),255);
            $payload=['message'=>$message,'recipient_email'=>$recipientEmail,'recipient_name'=>$recipientName];
        } else {
            $body=automation_clean((string)($payload['body']??$message),10000);
            $privacy=(string)($payload['privacy_level']??'private');
            if(!in_array($privacy,['public','family','friends','specific_people','private'],true)) $privacy='private';
            $payload=['body'=>$body,'privacy_level'=>$privacy];
        }
        $safeActions[]=['action_type'=>$type,'payload'=>$payload];
    }
    return $safeActions;
}
function automation_validate_rule(PDO $db, array $data, int $owner, bool $partial=false): array {
    $title=automation_clean((string)($data['title']??''));
    $desc=automation_clean((string)($data['description']??''),2000);
    $trigger=(string)($data['trigger_type']??'specific_datetime');
    $status=(string)($data['status']??'scheduled');
    if($title===''){ApiResponse::validation(['title'=>'Title required.']);exit;}
    if(!in_array($trigger,AUTOMATION_TRIGGER_TYPES,true)){ApiResponse::validation(['trigger_type'=>'Invalid trigger.']);exit;}
    if(!in_array($status,['draft','scheduled'],true)) $status='scheduled';
    $next=automation_next_run($data);
    if($status==='scheduled' && !$next){ApiResponse::validation(['next_run_at'=>'Valid future trigger time/date required.']);exit;}
    if($next && strtotime($next)<=time() && $trigger==='specific_datetime'){ApiResponse::validation(['trigger_datetime'=>'Trigger must be in the future.']);exit;}
    $linkedType=$data['linked_resource_type']??null;
    $linkedId=(int)($data['linked_resource_id']??0);
    if($trigger==='linked_milestone_event'){
        if(!in_array($linkedType,['milestone','event'],true)||$linkedId<=0){ApiResponse::validation(['linked_resource'=>'Valid milestone/event required.']);exit;}
        $found=false;
        if($linkedType==='milestone'){
            $statement=$db->prepare('SELECT id FROM milestones WHERE id=:id AND user_id=:u LIMIT 1');
            $statement->execute(['id'=>$linkedId,'u'=>$owner]);
            $found=(bool)$statement->fetchColumn();
        } else {
            $statement=$db->prepare('SELECT id,scheduled_date FROM scheduled_events WHERE id=:id AND user_id=:u LIMIT 1');
            $statement->execute(['id'=>$linkedId,'u'=>$owner]);
            $row=$statement->fetch(PDO::FETCH_ASSOC);
            $found=(bool)$row;
            if($row && empty($data['trigger_datetime'])) $next=automation_utc_datetime($row['scheduled_date']);
        }
        if(!$found){ApiResponse::validation(['linked_resource'=>'Linked resource not found.']);exit;}
    } else {
        $linkedType=null;
        $linkedId=0;
    }
    $actions=$data['actions']??[];
    if(!is_array($actions)){ApiResponse::validation(['actions'=>'Actions must be a list.']);exit;}
    try{$safeActions=automation_validate_actions($db,$actions,$owner);}
    catch(InvalidArgumentException $e){ApiResponse::validation(['actions'=>$e->getMessage()]);exit;}
    return compact('title','desc','trigger','status','next','linkedType','linkedId','safeActions');
}

function automation_format(array $r): array { $r['id']=(int)$r['id'];$r['owner_id']=(int)$r['owner_id'];$r['retry_count']=(int)$r['retry_count'];$r['max_retries']=(int)$r['max_retries'];return $r; }
?>

