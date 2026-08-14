<?php
require_once __DIR__ . '/../config/config.php'; require_once __DIR__ . '/../helpers/ApiResponse.php'; require_once __DIR__ . '/../helpers/SessionHelper.php'; require_once __DIR__ . '/../services/WorldEventsProvider.php';
try { if(!SessionHelper::isAuthenticated()){ApiResponse::unauthorized();exit;} $raw=trim((string)($_GET['years']??'')); $years=array_values(array_filter(array_map('intval',explode(',',$raw)),fn($y)=>$y>=1&&$y<=2100)); if(!$years){ApiResponse::success(['events'=>[]]);exit;} ApiResponse::success(['events'=>(new WorldEventsProvider())->eventsForYears($years)]); } catch(Throwable $e){ ApiResponse::serverError('Unable to load world events.'); }
?>
