<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../services/AIKnowledgeService.php';

class FakeEmbeddingProvider implements EmbeddingProviderInterface
{
    public function embed(array $texts): array{return array_map(static fn($t)=>[(float)mb_strlen($t),(float)substr_count(strtolower($t),'family')+1.0,1.0],$texts);}
}
class FailingEmbeddingProvider implements EmbeddingProviderInterface{public function embed(array $texts): array{throw new RuntimeException('ai_provider_unavailable');}}

function ok(bool $condition,string $name,array &$results): void{$results[$name]=$condition?'PASS':'FAIL';if(!$condition)throw new RuntimeException('Test failed: '.$name);}

$db=(new Database())->getConnection();$results=[];$db->beginTransaction();
try{
    $tag=bin2hex(random_bytes(4));$hash=password_hash('TestPassword1!',PASSWORD_DEFAULT);
    $u=$db->prepare("INSERT INTO users(username,email,password_hash,full_name,status) VALUES(:username,:email,:hash,:name,'active')");
    foreach(['owner','viewer','blocked'] as $name){$u->execute(['username'=>'ai_'.$name.$tag,'email'=>'ai_'.$name.$tag.'@example.test','hash'=>$hash,'name'=>'AI '.ucfirst($name)]);${$name}=(int)$db->lastInsertId();}
    $db->prepare("INSERT INTO friendships(user_id,friend_id,status) VALUES(:a,:b,'blocked')")->execute(['a'=>$owner,'b'=>$blocked]);
    $db->prepare("UPDATE users SET bio='Family history profile biography' WHERE id=:id")->execute(['id'=>$owner]);
    $db->prepare("INSERT INTO memories(user_id,title,description,file_path,file_type,privacy_level,status) VALUES(:u,'Family memory','A public family picnic','none.jpg','image/jpeg','public','active')")->execute(['u'=>$owner]);$memory=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO milestones(user_id,title,description,milestone_date,privacy_level,status) VALUES(:u,'Graduation','Finished school','2020-01-01','public','active')")->execute(['u'=>$owner]);$milestone=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO posts(user_id,body,privacy_level,status) VALUES(:u,'Private family journal','private','active')")->execute(['u'=>$owner]);$post=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO journeys(owner_id,title,description,privacy_level,status) VALUES(:u,'Family trip','Shared holiday','public','published')")->execute(['u'=>$owner]);$journey=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO journey_items(journey_id,contributor_id,item_type,title,description,status) VALUES(:j,:u,'event','Family dinner','Approved moment','approved')")->execute(['j'=>$journey,'u'=>$owner]);$item=(int)$db->lastInsertId();

    $service=new AIKnowledgeService($db,new FakeEmbeddingProvider());
    foreach([['profile',$owner],['memory',$memory],['milestone',$milestone],['post',$post],['journey',$journey],['journey_item',$item]] as [$type,$id]){$source=$service->approveSource($owner,$type,$id);$service->processSource($source['source_id']);}
    ok(count($service->sourceStatus($owner))===6,'supported_sources_indexed',$results);
    $duplicate=$service->approveSource($owner,'memory',$memory);$service->processSource($duplicate['source_id']);$count=$db->prepare("SELECT COUNT(*) FROM ai_sources WHERE user_id=:u AND resource_type='memory' AND resource_id=:r");$count->execute(['u'=>$owner,'r'=>$memory]);ok((int)$count->fetchColumn()===1,'duplicate_source_prevented',$results);
    $viewerResults=$service->searchForUser($owner,$viewer,'family',20); ok(count($viewerResults)===4,'private_and_profile_hidden',$results);
    ok(count($service->searchForUser($owner,$blocked,'family',20))===0,'blocked_viewer_denied',$results);
    ok(count($service->searchForUser($owner,$owner,'family',20))===6,'owner_access',$results);
    $before=$db->query("SELECT content_hash FROM ai_sources WHERE resource_type='post' AND resource_id=$post")->fetchColumn();$db->prepare("UPDATE posts SET body='Changed private family journal' WHERE id=:id")->execute(['id'=>$post]);$sourceId=(int)$db->query("SELECT id FROM ai_sources WHERE resource_type='post' AND resource_id=$post")->fetchColumn();$service->reindex($owner,$sourceId);$service->processSource($sourceId);$after=$db->query("SELECT content_hash FROM ai_sources WHERE id=$sourceId")->fetchColumn();ok($before!==$after,'changed_source_reindexed',$results);
    $db->prepare("UPDATE posts SET status='deleted',deleted_at=UTC_TIMESTAMP() WHERE id=:id")->execute(['id'=>$post]);
    $visible=$service->searchForUser($owner,$owner,'family',20);ok(!in_array('post',array_column($visible,'resource_type'),true),'deleted_source_excluded',$results);
    $service->disableSource($owner,'memory',$memory);$chunks=$db->query("SELECT COUNT(*) FROM ai_chunks WHERE source_id=(SELECT id FROM ai_sources WHERE resource_type='memory' AND resource_id=$memory)")->fetchColumn();ok((int)$chunks===0,'consent_revocation_removes_chunks',$results);
    try{(new OpenAIEmbeddingProvider('', 'test'))->embed(['x']);ok(false,'missing_key_safe',$results);}catch(RuntimeException $e){ok($e->getMessage()==='ai_api_key_missing','missing_key_safe',$results);}
    try{(new AIKnowledgeService($db,new FailingEmbeddingProvider()))->searchForUser($owner,$owner,'x');ok(false,'provider_failure_safe',$results);}catch(RuntimeException $e){ok($e->getMessage()==='ai_provider_unavailable','provider_failure_safe',$results);}
    echo json_encode($results,JSON_PRETTY_PRINT).PHP_EOL;
}finally{if($db->inTransaction())$db->rollBack();}
