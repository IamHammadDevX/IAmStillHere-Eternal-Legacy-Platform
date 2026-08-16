<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../services/PrivacyService.php';
require_once __DIR__ . '/../services/NotificationService.php';

const JOURNEY_TITLE_MAX = 180;
const JOURNEY_DESC_MAX = 5000;
const JOURNEY_ITEM_NOTE_MAX = 1000;

function journeys_db(): PDO { return (new Database())->getConnection(); }
function journeys_input(): array { $d=json_decode(file_get_contents('php://input'), true); return is_array($d)?$d:[]; }
function journeys_csrf(array $data): bool { return CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data)); }
function journeys_user_id(): ?int { return SessionHelper::getUserId(); }
function journeys_require_auth(): ?int { $id=journeys_user_id(); if($id===null) ApiResponse::unauthorized(); return $id; }
function journeys_require_active(PDO $db, int $id): bool { $s=$db->prepare("SELECT id FROM users WHERE id=:id AND status='active' LIMIT 1"); $s->execute(['id'=>$id]); return (bool)$s->fetchColumn(); }
function journeys_is_blocked(PDO $db, int $a, int $b): bool { $s=$db->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='blocked' LIMIT 1"); $s->execute(['a'=>$a,'b'=>$b]); return (bool)$s->fetchColumn(); }
function journeys_are_friends(PDO $db, int $a, int $b): bool { $s=$db->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='accepted' LIMIT 1"); $s->execute(['a'=>$a,'b'=>$b]); return (bool)$s->fetchColumn(); }
function journeys_are_family(PDO $db, int $owner, int $viewer): bool { $s=$db->prepare("SELECT id FROM family_members WHERE user_id=:owner AND family_member_id=:viewer AND status='active' AND approved=1 LIMIT 1"); $s->execute(['owner'=>$owner,'viewer'=>$viewer]); return (bool)$s->fetchColumn(); }
function journeys_connected(PDO $db, int $owner, int $target): bool { return $owner!==$target && !journeys_is_blocked($db,$owner,$target) && (journeys_are_friends($db,$owner,$target) || journeys_are_family($db,$owner,$target)); }
function journeys_find(PDO $db, int $id): ?array { $s=$db->prepare("SELECT j.*, u.full_name AS owner_name, u.profile_photo AS owner_photo FROM journeys j INNER JOIN users u ON u.id=j.owner_id WHERE j.id=:id AND j.deleted_at IS NULL LIMIT 1"); $s->execute(['id'=>$id]); $r=$s->fetch(PDO::FETCH_ASSOC); return $r?:null; }
function journeys_is_participant(PDO $db, int $journeyId, int $userId): bool { $s=$db->prepare("SELECT id FROM journey_participants WHERE journey_id=:j AND user_id=:u AND status='accepted' LIMIT 1"); $s->execute(['j'=>$journeyId,'u'=>$userId]); return (bool)$s->fetchColumn(); }
function journeys_can_view(PDO $db, array $journey, ?int $viewer): bool { if($viewer!==null && ((int)$journey['owner_id']===$viewer || SessionHelper::isAdmin())) return true; if($viewer!==null && journeys_is_blocked($db,(int)$journey['owner_id'],$viewer)) return false; if(($journey['status'] ?? '') !== 'published') return $viewer!==null && journeys_is_participant($db,(int)$journey['id'],$viewer); return PrivacyService::canView($db,'journey',(int)$journey['id'],(int)$journey['owner_id'],$viewer,(string)$journey['privacy_level']); }
function journeys_can_manage(PDO $db, array $journey, int $viewer): bool { return (int)$journey['owner_id']===$viewer || SessionHelper::isAdmin(); }
function journeys_can_contribute(PDO $db, array $journey, int $viewer): bool { return journeys_can_manage($db,$journey,$viewer) || journeys_is_participant($db,(int)$journey['id'],$viewer); }
function journeys_clean_status(string $s): string { return in_array($s,['draft','published','archived'],true)?$s:'draft'; }
function journeys_clean_privacy(string $p): string { return in_array($p,PrivacyService::allowedTypes(),true)?$p:'private'; }
function journeys_legacy_privacy(string $p): string { return in_array($p,['public','family','private'],true)?$p:'private'; }
function journeys_validate_dates(string $start, string $end): array { if($start!=='' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$start)) throw new InvalidArgumentException('Invalid start date.'); if($end!=='' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)) throw new InvalidArgumentException('Invalid end date.'); if($start!=='' && $end!=='' && $end<$start) throw new InvalidArgumentException('End date must be after start date.'); return [$start?:null,$end?:null]; }
function journeys_public_photo(?string $p): string { return $p ? '/data/uploads/photos/' . rawurlencode($p) : '/frontend/images/default-profile.png'; }
function journeys_cover_url(array $journey): ?string { return !empty($journey['cover_image']) ? '/backend/journeys/cover.php?journey_id=' . (int)$journey['id'] : null; }
function journeys_media_url(array $item): ?string { return !empty($item['media_path']) ? '/backend/journeys/media.php?item_id=' . (int)$item['id'] : null; }
function journeys_format(PDO $db, array $j): array { $viewer=journeys_user_id(); $c=$db->prepare("SELECT COUNT(*) FROM journey_participants WHERE journey_id=:id AND status='accepted'"); $c->execute(['id'=>$j['id']]); return ['id'=>(int)$j['id'],'owner_id'=>(int)$j['owner_id'],'owner_name'=>$j['owner_name']??'Unknown','title'=>$j['title'],'description'=>$j['description'],'start_date'=>$j['start_date'],'end_date'=>$j['end_date'],'cover_image'=>$j['cover_image'],'cover_media_type'=>$j['cover_media_type']??null,'cover_url'=>journeys_cover_url($j),'privacy_level'=>$j['privacy_level'],'status'=>$j['status'],'created_at'=>$j['created_at'],'updated_at'=>$j['updated_at'],'participant_count'=>(int)$c->fetchColumn(),'can_manage'=>$viewer!==null&&journeys_can_manage($db,$j,$viewer),'can_contribute'=>$viewer!==null&&journeys_can_contribute($db,$j,$viewer)]; }
function journeys_item_visible(PDO $db, array $item, ?int $viewer): bool { if($item['item_type']==='memory'){ $s=$db->prepare('SELECT * FROM memories WHERE id=:id AND status=\'active\' LIMIT 1'); $s->execute(['id'=>$item['source_id']]); $m=$s->fetch(PDO::FETCH_ASSOC); return $m && PrivacyService::canView($db,'memory',(int)$m['id'],(int)$m['user_id'],$viewer,(string)$m['privacy_level'],isset($m['folder_id'])?(int)$m['folder_id']:null); } if($item['item_type']==='milestone'){ $s=$db->prepare('SELECT * FROM milestones WHERE id=:id AND status=\'active\' LIMIT 1'); $s->execute(['id'=>$item['source_id']]); $m=$s->fetch(PDO::FETCH_ASSOC); return $m && PrivacyService::canView($db,'milestone',(int)$m['id'],(int)$m['user_id'],$viewer,(string)$m['privacy_level']); } return true; }
function journeys_format_item(PDO $db, array $item): array { return ['id'=>(int)$item['id'],'item_type'=>$item['item_type'],'source_id'=>$item['source_id']? (int)$item['source_id']:null,'title'=>$item['title'],'description'=>$item['description'],'item_date'=>$item['item_date'],'status'=>$item['status'],'contributor_id'=>(int)$item['contributor_id'],'contributor_name'=>$item['contributor_name']??'Unknown','media_mime'=>$item['media_mime']??null,'media_url'=>journeys_media_url($item)]; }
?>
