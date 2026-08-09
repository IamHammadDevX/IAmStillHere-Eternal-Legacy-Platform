<?php
require_once __DIR__ . '/_ai_helpers.php';
try{if(!ai_method('POST'))exit;$db=ai_db();$user=ai_require_user($db);if($user===null)exit;$data=ai_input();if(!ai_require_csrf($data))exit;$sourceId=(int)($data['source_id']??0);(new AIKnowledgeService($db))->reindex($user,$sourceId);ApiResponse::success(['source_id'=>$sourceId],'Source queued for re-indexing.',202);}catch(InvalidArgumentException $e){ApiResponse::notFound('Source not found.');}catch(Throwable $e){ai_safe_error($e,'reindex');}
