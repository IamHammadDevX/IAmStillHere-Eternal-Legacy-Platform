<?php

require_once __DIR__ . '/../helpers/SessionHelper.php';

class PrivacyService
{
    private const TYPES = ['public','family','friends','specific_people','private','release_date','release_event'];

    public static function allowedTypes(): array { return self::TYPES; }

    public static function getRule(PDO $db, string $resourceType, int $resourceId): ?array
    {
        $s = $db->prepare('SELECT * FROM privacy_rules WHERE resource_type=:type AND resource_id=:id LIMIT 1');
        $s->execute(['type'=>$resourceType,'id'=>$resourceId]);
        $rule = $s->fetch(PDO::FETCH_ASSOC);
        if (!$rule) return null;
        $u = $db->prepare('SELECT user_id FROM privacy_rule_users WHERE privacy_rule_id=:id ORDER BY user_id');
        $u->execute(['id'=>$rule['id']]);
        $rule['user_ids'] = array_map('intval', $u->fetchAll(PDO::FETCH_COLUMN));
        return $rule;
    }

    public static function canView(PDO $db, string $resourceType, int $resourceId, int $ownerId, ?int $viewerId, string $fallbackPrivacy = 'private', ?int $folderId = null): bool
    {
        if ($viewerId !== null && $viewerId === $ownerId) return true;
        if ($viewerId !== null) { $blocked=$db->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='blocked' LIMIT 1"); $blocked->execute(['a'=>$ownerId,'b'=>$viewerId]); if ($blocked->fetchColumn()) return false; }
        $rule = self::getRule($db, $resourceType, $resourceId);
        if (!$rule && $folderId !== null) $rule = self::getRule($db, 'memory_folder', $folderId);
        $type = $rule['visibility_type'] ?? $fallbackPrivacy;
        if ($type === 'release_date') return $rule && !empty($rule['release_at']) && strtotime($rule['release_at'] . ' UTC') <= time();
        if ($type === 'release_event') {
            if (!$rule || empty($rule['release_event_id'])) return false;
            $s=$db->prepare("SELECT status FROM scheduled_events WHERE id=:id LIMIT 1");$s->execute(['id'=>$rule['release_event_id']]);
            return $s->fetchColumn() === 'published';
        }
        if ($type === 'public') return true;
        if ($viewerId === null) return false;
        if ($type === 'private') return false;
        if ($type === 'specific_people') return in_array($viewerId, $rule['user_ids'] ?? [], true);
        if ($type === 'family') {
            $s=$db->prepare("SELECT id FROM family_members WHERE ((user_id=:owner AND family_member_id=:viewer) OR (user_id=:viewer AND family_member_id=:owner)) AND status='active' AND approved=1 LIMIT 1");$s->execute(['owner'=>$ownerId,'viewer'=>$viewerId]); return (bool)$s->fetchColumn();
        }
        if ($type === 'friends') {
            $blocked=$db->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='blocked' LIMIT 1");$blocked->execute(['a'=>$ownerId,'b'=>$viewerId]);if($blocked->fetchColumn())return false;
            $s=$db->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='accepted' LIMIT 1");$s->execute(['a'=>$ownerId,'b'=>$viewerId]);return (bool)$s->fetchColumn();
        }
        return false;
    }

    public static function validateUsers(PDO $db, array $ids, int $ownerId): array
    {
        $ids=array_values(array_unique(array_map('intval',$ids)));if(in_array($ownerId,$ids,true))throw new InvalidArgumentException('Owner cannot be a specific audience member.');if(!$ids)return [];
        $marks=implode(',',array_fill(0,count($ids),'?'));$s=$db->prepare("SELECT id FROM users WHERE id IN ($marks) AND status='active'");$s->execute($ids);$valid=array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN));
        foreach($ids as $id){if(!in_array($id,$valid,true))throw new InvalidArgumentException('Specific audience contains an invalid user.');$b=$db->prepare("SELECT id FROM friendships WHERE ((user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)) AND status='blocked' LIMIT 1");$b->execute([$ownerId,$id,$id,$ownerId]);if($b->fetchColumn())throw new InvalidArgumentException('Blocked users cannot be selected.');}return $valid;
    }
}
