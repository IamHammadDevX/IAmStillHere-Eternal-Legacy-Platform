<?php
require_once __DIR__ . '/_helpers.php';
try{if(!ai_method('POST'))exit;$db=ai_db();$owner=ai_require_user($db);if($owner===null)exit;$data=ai_input();if(!ai_require_csrf($data)||!ai_pm_require_owner($data,$owner))exit;ApiResponse::success(['message'=>ai_pm_service($db)->cancel($owner,(int)($data['message_id']??0))],'Message cancelled.');}catch(Throwable $e){ai_pm_error($e,'cancel');}
