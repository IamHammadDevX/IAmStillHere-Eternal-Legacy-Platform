<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/Logger.php';

const VAULT_VERIFY_SECONDS = 900;
const VAULT_MAX_FILE_SIZE = 25_000_000;
const VAULT_ALLOWED_EXTENSIONS = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','rtf','odt','jpg','jpeg','png'];
const VAULT_ALLOWED_MIME_TYPES = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','text/plain','application/rtf','application/vnd.oasis.opendocument.text','image/jpeg','image/png'];

function vault_db(): PDO { return (new Database())->getConnection(); }
function vault_input(): array { $d=json_decode(file_get_contents('php://input'), true); return is_array($d) ? $d : $_POST; }
function vault_user_id(): ?int { return SessionHelper::getUserId(); }
function vault_require_auth(): int { $id=vault_user_id(); if($id===null){ApiResponse::unauthorized();exit;} return $id; }
function vault_require_csrf(array $data): void { if(!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))){ApiResponse::forbidden('Invalid CSRF token.');exit;} }
function vault_verified(): bool { return isset($_SESSION['vault_verified_at']) && (time() - (int)$_SESSION['vault_verified_at']) <= VAULT_VERIFY_SECONDS; }
function vault_verified_until(): ?string { return vault_verified() ? gmdate('c', (int)$_SESSION['vault_verified_at'] + VAULT_VERIFY_SECONDS) : null; }
function vault_require_verified(): void { if(!vault_verified()){ApiResponse::forbidden('Vault re-authentication required.');exit;} }
function vault_path_is_public(string $path): bool { $real=str_replace('\\', '/', realpath($path) ?: $path); $base=str_replace('\\', '/', realpath(BASE_PATH) ?: BASE_PATH); $public=str_replace('\\', '/', realpath(dirname(BASE_PATH)) ?: dirname(BASE_PATH)); $publicName=strtolower(basename($public)); return str_starts_with($real, $base) || stripos($real, '/htdocs/') !== false || stripos($real, '/public_html/') !== false || (in_array($publicName, ['htdocs','public_html'], true) && str_starts_with($real, $public)); }
function vault_storage_dir(): string { $path=getenv('VAULT_STORAGE_PATH') ?: dirname(BASE_PATH) . DIRECTORY_SEPARATOR . 'vault_storage'; if(!is_dir($path)) @mkdir($path,0770,true); if(vault_path_is_public($path)) throw new RuntimeException('Vault storage path must be outside public web root.'); return $path; }
function vault_key_file(): string { $path=getenv('VAULT_KEY_PATH') ?: dirname(BASE_PATH) . DIRECTORY_SEPARATOR . 'vault_keys' . DIRECTORY_SEPARATOR . 'master.key'; $dir=dirname($path); if(!is_dir($dir)) @mkdir($dir,0770,true); if(vault_path_is_public($dir)) throw new RuntimeException('Vault key path must be outside public web root and repository.'); if(!is_file($path)){ $key=base64_encode(random_bytes(32)); if(@file_put_contents($path,$key,LOCK_EX)===false) throw new RuntimeException('Vault key unavailable.'); @chmod($path,0600); } return $path; }
function vault_master_key(): string { $raw=trim((string)@file_get_contents(vault_key_file())); $key=base64_decode($raw,true); if($key===false || strlen($key)!==32) throw new RuntimeException('Invalid vault key.'); return $key; }
function vault_safe_name(string $name): string { $name=trim($name); $name=preg_replace('/[\x00-\x1F\x7F\\\/]+/',' ',$name); return mb_substr($name ?: 'Untitled',0,180); }
function vault_can_access_owner(PDO $db, int $ownerId, int $actorId): bool { if($ownerId===$actorId) return true; $s=$db->prepare("SELECT id FROM vault_permissions WHERE owner_id=:owner AND authorized_user_id=:actor AND role='legal_counsel' AND status='active' LIMIT 1"); $s->execute(['owner'=>$ownerId,'actor'=>$actorId]); return (bool)$s->fetchColumn(); }
function vault_document(PDO $db, int $id): ?array { $s=$db->prepare('SELECT * FROM vault_documents WHERE id=:id AND deleted_at IS NULL LIMIT 1'); $s->execute(['id'=>$id]); $r=$s->fetch(PDO::FETCH_ASSOC); return $r?:null; }
function vault_require_document_access(PDO $db, int $id, int $actor): array { $doc=vault_document($db,$id); if(!$doc || !vault_can_access_owner($db,(int)$doc['owner_id'],$actor)){ApiResponse::notFound('Vault document not found.');exit;} return $doc; }
function vault_owner_from_request(PDO $db, array $data, int $actor): int { $owner=(int)($data['owner_id'] ?? $_GET['owner_id'] ?? $actor); if(!vault_can_access_owner($db,$owner,$actor)){ApiResponse::forbidden('Vault access denied.');exit;} return $owner; }
function vault_log(PDO $db, int $owner, ?int $actor, ?int $doc, string $action): void { $s=$db->prepare('INSERT INTO vault_access_logs(owner_id,actor_user_id,document_id,action,ip_address,user_agent) VALUES(:owner,:actor,:doc,:action,:ip,:ua)'); $s->execute(['owner'=>$owner,'actor'=>$actor,'doc'=>$doc,'action'=>$action,'ip'=>$_SERVER['REMOTE_ADDR']??null,'ua'=>mb_substr($_SERVER['HTTP_USER_AGENT']??'',0,255)]); }
function vault_folder_owned(PDO $db, int $folderId, int $owner): bool { if($folderId<=0)return true; $s=$db->prepare('SELECT id FROM vault_folders WHERE id=:id AND owner_id=:owner AND deleted_at IS NULL LIMIT 1'); $s->execute(['id'=>$folderId,'owner'=>$owner]); return (bool)$s->fetchColumn(); }
function vault_is_descendant(PDO $db, int $candidateParent, int $folderId, int $owner): bool { $seen=[]; $cur=$candidateParent; while($cur>0&&!isset($seen[$cur])){ if($cur===$folderId)return true; $seen[$cur]=true; $s=$db->prepare('SELECT parent_folder_id FROM vault_folders WHERE id=:id AND owner_id=:owner AND deleted_at IS NULL'); $s->execute(['id'=>$cur,'owner'=>$owner]); $p=$s->fetchColumn(); $cur=$p? (int)$p : 0; } return false; }
function vault_format_doc(array $d): array { return ['id'=>(int)$d['id'],'owner_id'=>(int)$d['owner_id'],'folder_id'=>$d['folder_id']!==null?(int)$d['folder_id']:null,'display_name'=>$d['display_name'],'original_filename'=>$d['original_filename'],'mime_type'=>$d['mime_type'],'file_size'=>(int)$d['file_size'],'sha256'=>$d['plaintext_sha256'],'created_at'=>$d['created_at'],'updated_at'=>$d['updated_at']]; }
function vault_format_folder(array $f): array { return ['id'=>(int)$f['id'],'owner_id'=>(int)$f['owner_id'],'parent_folder_id'=>$f['parent_folder_id']!==null?(int)$f['parent_folder_id']:null,'name'=>$f['name'],'created_at'=>$f['created_at'],'updated_at'=>$f['updated_at']]; }
?>

