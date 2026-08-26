<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/_profile_visibility.php';
header('Content-Type: application/json');

if (!is_logged_in()) { http_response_code(401); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit; }
if (!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($_POST))) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh and try again.']); exit; }

$bio = sanitize_input($_POST['bio'] ?? '');
$dateOfBirth = sanitize_input($_POST['date_of_birth'] ?? '');
$requestedUsername = strtolower(trim((string) ($_POST['username'] ?? '')));
$hasPublicProfileSections = array_key_exists('public_profile_sections', $_POST);
$publicProfileSections = $hasPublicProfileSections
    ? validate_public_profile_sections_input($_POST['public_profile_sections'])
    : null;

try {
    $conn = (new Database())->getConnection();
    $conn->beginTransaction();
    $userStatement = $conn->prepare('SELECT id, username, username_changed_at FROM users WHERE id = :id FOR UPDATE');
    $userStatement->execute(['id' => (int) $_SESSION['user_id']]);
    $currentUser = $userStatement->fetch(PDO::FETCH_ASSOC);
    if (!$currentUser) { throw new RuntimeException('User account not found.'); }

    $updates = [];
    $params = ['user_id' => (int) $_SESSION['user_id']];
    if ($bio !== '') { $updates[] = 'bio = :bio'; $params['bio'] = $bio; }
    if ($dateOfBirth !== '') { $updates[] = 'date_of_birth = :date_of_birth'; $params['date_of_birth'] = $dateOfBirth; }
    if ($hasPublicProfileSections) {
        $updates[] = 'public_profile_sections = :public_profile_sections';
        $params['public_profile_sections'] = json_encode($publicProfileSections);
    }

    if ($requestedUsername !== '' && $requestedUsername !== strtolower((string) $currentUser['username'])) {
        if (!preg_match('/^[a-z0-9._]{3,30}$/', $requestedUsername)) {
            throw new InvalidArgumentException('Username must be 3–30 characters and use only letters, numbers, dots, or underscores.');
        }
        if (!empty($currentUser['username_changed_at'])) {
            $nextAllowed = (new DateTimeImmutable($currentUser['username_changed_at'], new DateTimeZone('UTC')))->modify('+15 days');
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            if ($now < $nextAllowed) {
                throw new InvalidArgumentException('Username can be changed again on ' . $nextAllowed->format('M j, Y') . '.');
            }
        }
        $taken = $conn->prepare('SELECT id FROM users WHERE username = :username AND id <> :user_id LIMIT 1');
        $taken->execute(['username' => $requestedUsername, 'user_id' => (int) $_SESSION['user_id']]);
        if ($taken->fetch()) { throw new InvalidArgumentException('That username is already taken.'); }
        $updates[] = 'username = :username';
        $updates[] = 'username_changed_at = UTC_TIMESTAMP()';
        $params['username'] = $requestedUsername;
    }

    foreach (['profile_photo' => 'profile', 'cover_photo' => 'cover'] as $field => $prefix) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) { continue; }
        $file = $_FILES[$field];
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES, true)) { throw new InvalidArgumentException('Invalid ' . str_replace('_', ' ', $field) . ' type.'); }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $prefix . '_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/photos/' . $filename)) { throw new RuntimeException('Failed to save ' . str_replace('_', ' ', $field) . '.'); }
        $updates[] = $field . ' = :' . $field;
        $params[$field] = $filename;
    }

    if (!$updates) { throw new InvalidArgumentException('No changes to update.'); }
    $statement = $conn->prepare('UPDATE users SET ' . implode(', ', $updates) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :user_id');
    $statement->execute($params);
    $result = $conn->prepare('SELECT username, username_changed_at, bio, date_of_birth, profile_photo, cover_photo, public_profile_sections FROM users WHERE id = :id');
    $result->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $result->fetch(PDO::FETCH_ASSOC);
    $conn->commit();

    $_SESSION['username'] = $user['username'];
    $nextAllowed = !empty($user['username_changed_at']) ? (new DateTimeImmutable($user['username_changed_at'], new DateTimeZone('UTC')))->modify('+15 days')->format('Y-m-d') : null;
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'user' => [
            'username' => $user['username'],
            'username_next_change_at' => $nextAllowed,
            'bio' => $user['bio'],
            'date_of_birth' => $user['date_of_birth'],
            'profile_photo' => $user['profile_photo'] ? '/data/uploads/photos/' . $user['profile_photo'] : '/frontend/images/default-profile.png',
            'cover_photo' => $user['cover_photo'] ? '/data/uploads/photos/' . $user['cover_photo'] : '',
            'public_profile_sections' => normalize_public_profile_sections($user['public_profile_sections'] ?? null),
        ],
    ]);
} catch (InvalidArgumentException $exception) {
    if (isset($conn) && $conn->inTransaction()) { $conn->rollBack(); }
    http_response_code(422); echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    if (isset($conn) && $conn->inTransaction()) { $conn->rollBack(); }
    error_log($exception->getMessage()); http_response_code(500); echo json_encode(['success' => false, 'message' => 'Unable to update profile.']);
}