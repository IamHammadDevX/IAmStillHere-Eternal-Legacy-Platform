<?php
require_once __DIR__ . '/_folder_helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed', [], 405); exit; }
$ownerId = folder_require_auth(); $data = folder_input(); folder_require_csrf($data);
$name = folder_name($data['name'] ?? ''); $description = trim((string) ($data['description'] ?? ''));
$privacy = folder_privacy($data['privacy_level'] ?? 'private'); $parent = (int) ($data['parent_folder_id'] ?? 0);
if ($name === '' || mb_strlen($name) > 150) { ApiResponse::validation(['name' => 'Name is required and must be 150 characters or fewer']); exit; }
if ($privacy === null) { ApiResponse::validation(['privacy_level' => 'Invalid privacy level']); exit; }
try { $db = folder_connection(); if ($parent > 0 && !folder_find($db, $parent, $ownerId)) { ApiResponse::validation(['parent_folder_id' => 'Parent folder not found']); exit; }
    $stmt = $db->prepare('INSERT INTO memory_folders (user_id,parent_folder_id,name,description,privacy_level) VALUES (:u,:p,:n,:d,:v)');
    $stmt->execute(['u'=>$ownerId,'p'=>$parent ?: null,'n'=>$name,'d'=>$description ?: null,'v'=>$privacy]);
    ApiResponse::success(['folder_id'=>(int)$db->lastInsertId()], 'Folder created', 201);
} catch (Throwable $e) { error_log('Folder create error: '.$e->getMessage()); ApiResponse::serverError('Unable to create folder'); }
