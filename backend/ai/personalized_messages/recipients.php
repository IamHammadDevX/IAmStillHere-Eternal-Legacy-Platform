<?php
require_once __DIR__ . '/_helpers.php';
try{if(!ai_method('GET'))exit;$db=ai_db();$owner=ai_require_user($db);if($owner===null)exit;ApiResponse::success(['recipients'=>ai_pm_service($db)->recipients($owner)],'Recipients loaded.');}catch(Throwable $e){ai_pm_error($e,'recipients');}
