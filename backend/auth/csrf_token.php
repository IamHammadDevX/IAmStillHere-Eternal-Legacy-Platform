<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/RequestContext.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

ApiResponse::success([
    'csrf_token' => CsrfHelper::getToken(),
], 'CSRF token ready');
