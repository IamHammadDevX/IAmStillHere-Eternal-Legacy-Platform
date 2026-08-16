<?php
require_once __DIR__ . '/_media_helpers.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $db=journeys_db(); $uid=journeys_require_auth(); if ($uid===null) exit;
    if (!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($_POST))) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }
    $id=(int)($_POST['journey_id']??0); $journey=$id?journeys_find($db,$id):null;
    if (!$journey) { ApiResponse::notFound('Journey not found.'); exit; }
    if (!journeys_can_manage($db,$journey,$uid)) { ApiResponse::forbidden('Only the journey owner can change its cover.'); exit; }
    $media=journey_upload_file($_FILES['media']??[], 'covers');
    $db->prepare('UPDATE journeys SET cover_image=:path,cover_media_type=:type WHERE id=:id')->execute(['path'=>$media['path'],'type'=>$media['kind'],'id'=>$id]);
    ApiResponse::success(['journey'=>journeys_format($db, journeys_find($db,$id))], 'Journey cover updated.');
} catch (Throwable $e) { Logger::error('Journey cover upload failed',['error'=>$e->getMessage()]); ApiResponse::serverError($e instanceof InvalidArgumentException?$e->getMessage():'Unable to upload journey cover.'); }
