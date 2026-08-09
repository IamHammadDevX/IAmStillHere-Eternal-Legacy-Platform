<?php
require_once __DIR__ . '/_ai_helpers.php';
try{if(!ai_method('GET'))exit;$db=ai_db();$user=ai_require_user($db);if($user===null)exit;ApiResponse::success(['sources'=>(new AIKnowledgeService($db))->sourceStatus($user)],'AI source status loaded.');}catch(Throwable $e){ai_safe_error($e,'status');}
