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
function automation_require_csrf(array $data): void { if(!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))){ApiResponse::forbidden('Invalid CSRF token.');exit;} }
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
function automation_validate_rule(PDO $db, array $data, int $owner, bool $partial=false): array {
    $title=automation_clean((string)($data['title']??''));
    $desc=automation_clean((string)($data['description']??''),2000);
    $trigger=(string)($data['trigger_type']??'specific_datetime');
    $status=(string)($data['status']??'scheduled');
    if($title==='') ApiResponse::validation(['title'=>'Title required.']);
    if(!in_array($trigger,AUTOMATION_TRIGGER_TYPES,true)) ApiResponse::validation(['trigger_type'=>'Invalid trigger.']);
    if(!in_array($status,['draft','scheduled'],true)) $status='scheduled';
    $next=automation_next_run($data);
    if($status==='scheduled' && !$next) ApiResponse::validation(['next_run_at'=>'Valid future trigger time/date required.']);
    if($next && strtotime($next)<=time() && $trigger==='specific_datetime') ApiResponse::validation(['trigger_datetime'=>'Trigger must be in the future.']);
    $linkedType=$data['linked_resource_type']??null; $linkedId=(int)($data['linked_resource_id']??0);
    if($trigger==='linked_milestone_event'){
        if(!in_array($linkedType,['milestone','event'],true)||$linkedId<=0) ApiResponse::validation(['linked_resource'=>'Valid milestone/event required.']);
        $found = false;
        if($linkedType==='milestone'){$s=$db->prepare('SELECT id FROM milestones WHERE id=:id AND user_id=:u LIMIT 1');$s->execute(['id'=>$linkedId,'u'=>$owner]);$found=(bool)$s->fetchColumn();}
        else{$s=$db->prepare('SELECT id,scheduled_date FROM scheduled_events WHERE id=:id AND user_id=:u LIMIT 1');$s->execute(['id'=>$linkedId,'u'=>$owner]);$row=$s->fetch(PDO::FETCH_ASSOC);$found=(bool)$row; if($row && empty($data['trigger_datetime'])) $next=automation_utc_datetime($row['scheduled_date']);}
        if(!$found) ApiResponse::validation(['linked_resource'=>'Linked resource not found.']);
    } else { $linkedType=null; $linkedId=0; }
    $actions=$data['actions']??[]; if(!is_array($actions)||!count($actions)) ApiResponse::validation(['actions'=>'At least one action required.']);
    $safeActions=[];
    foreach($actions as $a){ $type=(string)($a['action_type']??''); if(!in_array($type,AUTOMATION_ACTION_TYPES,true)) continue; $payload=is_array($a['payload']??null)?$a['payload']:[]; $safeActions[]=['action_type'=>$type,'payload'=>$payload]; }
    if(!count($safeActions)) ApiResponse::validation(['actions'=>'Valid action required.']);
    return compact('title','desc','trigger','status','next','linkedType','linkedId','safeActions');
}
function automation_format(array $r): array { $r['id']=(int)$r['id'];$r['owner_id']=(int)$r['owner_id'];$r['retry_count']=(int)$r['retry_count'];$r['max_retries']=(int)$r['max_retries'];return $r; }
?>

