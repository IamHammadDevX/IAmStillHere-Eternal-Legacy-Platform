<?php

if (PHP_SAPI !== 'cli') {
    echo "CLI only\n";
    exit(1);
}

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../services/AIAvatarService.php';

class FakeAvatarEmbeddingProvider implements EmbeddingProviderInterface
{
    public function embed(array $texts): array
    {
        return array_map(static function (string $text): array {
            $career = stripos($text, 'career') !== false ? 1.0 : 0.1;
            $school = stripos($text, 'school') !== false ? 1.0 : 0.1;
            return [$career, $school, 0.5];
        }, $texts);
    }
}

class FakeAvatarChatProvider implements ChatProviderInterface
{
    public array $lastMessages = [];
    public bool $fail = false;

    public function chat(array $messages, array $options = []): array
    {
        if ($this->fail) throw new RuntimeException('ai_provider_error');
        $this->lastMessages = $messages;
        return [
            'answer' => 'I found approved knowledge about career and school life.',
            'model' => 'fake-chat',
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 8, 'total_tokens' => 18],
        ];
    }
}

function av_assert(bool $condition, string $name): void
{
    if (!$condition) throw new RuntimeException($name);
    echo "PASS {$name}\n";
}

function av_insert_user(PDO $db, string $name, string $role = 'client'): int
{
    $suffix = bin2hex(random_bytes(4));
    $s = $db->prepare("INSERT INTO users(username,email,password_hash,full_name,bio,role,status) VALUES(:u,:e,:p,:n,:b,:r,'active')");
    $s->execute([
        'u' => 'ai_avatar_' . $suffix,
        'e' => 'ai_avatar_' . $suffix . '@example.test',
        'p' => password_hash('Password123!', PASSWORD_BCRYPT),
        'n' => $name,
        'b' => $name . ' career teacher school volunteer.',
        'r' => $role,
    ]);
    return (int) $db->lastInsertId();
}

function av_add_source(PDO $db, int $owner, string $type, int $resource, string $title, string $text, string $date = '2001-01-01'): int
{
    $s = $db->prepare("INSERT INTO ai_sources(user_id,resource_type,resource_id,title,extracted_text,source_date,ingestion_status,ai_enabled,consented_at,content_hash) VALUES(:u,:t,:r,:title,:text,:date,'indexed',1,UTC_TIMESTAMP(),:hash)");
    $s->execute(['u' => $owner, 't' => $type, 'r' => $resource, 'title' => $title, 'text' => $text, 'date' => $date, 'hash' => hash('sha256', $text)]);
    $source = (int) $db->lastInsertId();
    $db->prepare("INSERT INTO ai_chunks(source_id,user_id,chunk_index,chunk_text,chunk_hash,embedding,metadata_json) VALUES(:s,:u,0,:text,:hash,:embedding,:meta)")
        ->execute(['s' => $source, 'u' => $owner, 'text' => $text, 'hash' => hash('sha256', $text), 'embedding' => json_encode([1, 0.2, 0.5]), 'meta' => json_encode(['title' => $title])]);
    return $source;
}

$db = (new Database())->getConnection();
$ids = [];

try {
    $_SESSION = [];
    $owner = av_insert_user($db, 'AI Avatar Owner');
    $family = av_insert_user($db, 'AI Avatar Family');
    $blocked = av_insert_user($db, 'AI Avatar Blocked');
    $ids = [$owner, $family, $blocked];

    $db->prepare("INSERT INTO family_members(user_id,family_member_id,relationship,status,approved) VALUES(:o,:f,'Grandchild','active',1)")
        ->execute(['o' => $owner, 'f' => $family]);
    $db->prepare("INSERT INTO friendships(user_id,friend_id,status) VALUES(:o,:b,'blocked')")
        ->execute(['o' => $owner, 'b' => $blocked]);

    $db->prepare("INSERT INTO posts(user_id,body,privacy_level,status) VALUES(:u,'Public career post','public','active')")
        ->execute(['u' => $owner]);
    $publicPost = (int) $db->lastInsertId();
    $db->prepare("INSERT INTO posts(user_id,body,privacy_level,status) VALUES(:u,'Private school post','private','active')")
        ->execute(['u' => $owner]);
    $privatePost = (int) $db->lastInsertId();

    av_add_source($db, $owner, 'post', $publicPost, 'Public Career', 'Career accomplishments public approved.');
    av_add_source($db, $owner, 'post', $privatePost, 'Private School', 'Private school hidden detail.');

    $_SESSION['user_id'] = $owner;
    $_SESSION['user_role'] = 'client';
    $ownerChat = new FakeAvatarChatProvider();
    $ownerService = new AIAvatarService($db, new AIKnowledgeService($db, new FakeAvatarEmbeddingProvider()), $ownerChat);
    $ownerResult = $ownerService->ask($owner, $owner, 'Summarize career.');
    av_assert((int) $ownerResult['conversation_id'] > 0, 'owner_chat_created');
    av_assert(strpos($ownerChat->lastMessages[1]['content'], 'Private school hidden detail') !== false, 'owner_private_context_allowed');

    $_SESSION['user_id'] = $family;
    $familyChat = new FakeAvatarChatProvider();
    $familyService = new AIAvatarService($db, new AIKnowledgeService($db, new FakeAvatarEmbeddingProvider()), $familyChat);
    $familyResult = $familyService->ask($owner, $family, 'Summarize career.');
    av_assert(strpos($familyChat->lastMessages[1]['content'], 'Career accomplishments public approved') !== false, 'family_public_context_allowed');
    av_assert(strpos($familyChat->lastMessages[1]['content'], 'Private school hidden detail') === false, 'family_private_context_excluded');
    av_assert(count($familyService->messages((int) $familyResult['conversation_id'], $family)) === 2, 'conversation_history_works');
    $familyService->deleteConversation((int) $familyResult['conversation_id'], $family);
    av_assert(count($familyService->listConversations($owner, $family)) === 0, 'delete_conversation_works');

    $_SESSION['ai_avatar_last_' . $family] = time();
    try {
        $familyService->ask($owner, $family, 'Another question');
        av_assert(false, 'rate_limit_enforced');
    } catch (RuntimeException $e) {
        av_assert($e->getMessage() === 'ai_rate_limited', 'rate_limit_enforced');
    }

    $_SESSION['user_id'] = $blocked;
    try {
        $familyService->ask($owner, $blocked, 'Can I see?');
        av_assert(false, 'blocked_denied');
    } catch (RuntimeException $e) {
        av_assert($e->getMessage() === 'ai_avatar_forbidden', 'blocked_denied');
    }

    $failing = new FakeAvatarChatProvider();
    $failing->fail = true;
    $_SESSION['user_id'] = $owner;
    $_SESSION['ai_avatar_last_' . $owner] = 0;
    try {
        (new AIAvatarService($db, new AIKnowledgeService($db, new FakeAvatarEmbeddingProvider()), $failing))->ask($owner, $owner, 'Provider fail');
        av_assert(false, 'provider_failure_safe');
    } catch (RuntimeException $e) {
        av_assert($e->getMessage() === 'ai_provider_error', 'provider_failure_safe');
    }
} finally {
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ($marks)")->execute($ids);
    }
}
