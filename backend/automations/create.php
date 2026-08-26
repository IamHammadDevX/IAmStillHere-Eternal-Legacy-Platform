<?php
ob_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/_automation_helpers.php';

function automation_create_json($success, $data, $message, $errors, $status)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => (bool) $success,
        'data' => $data,
        'message' => $message,
        'errors' => $errors,
    ));
    exit;
}

function automation_create_utc($value)
{
    if (!$value) return null;
    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        return null;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        automation_create_json(false, array(), 'Method not allowed.', array(), 405);
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;

    if (empty($_SESSION['user_id'])) {
        automation_create_json(false, array(), 'Unauthorized.', array(), 401);
    }
    if (!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))) {
        automation_create_json(false, array(), 'Invalid CSRF token.', array(), 403);
    }

    $owner = (int) $_SESSION['user_id'];
    $title = mb_substr(trim((string) (isset($data['title']) ? $data['title'] : '')), 0, 255);
    $description = mb_substr(trim((string) (isset($data['description']) ? $data['description'] : '')), 0, 2000);
    $trigger = (string) (isset($data['trigger_type']) ? $data['trigger_type'] : 'specific_datetime');
    $status = (string) (isset($data['status']) ? $data['status'] : 'scheduled');
    $allowedTriggers = array('specific_datetime', 'birthday', 'anniversary', 'custom_recurring', 'linked_milestone_event');

    if ($title === '') automation_create_json(false, array(), 'Validation failed.', array('title' => 'Title required.'), 422);
    if (!in_array($trigger, $allowedTriggers, true)) automation_create_json(false, array(), 'Validation failed.', array('trigger_type' => 'Invalid trigger.'), 422);
    if (!in_array($status, array('draft', 'scheduled'), true)) $status = 'scheduled';

    $triggerAt = automation_create_utc(isset($data['trigger_datetime']) ? $data['trigger_datetime'] : null);
    $month = (int) (isset($data['recurring_month']) ? $data['recurring_month'] : 0);
    $day = (int) (isset($data['recurring_day']) ? $data['recurring_day'] : 0);
    $linkedType = isset($data['linked_resource_type']) ? $data['linked_resource_type'] : null;
    $linkedId = (int) (isset($data['linked_resource_id']) ? $data['linked_resource_id'] : 0);

    if ($trigger === 'specific_datetime') {
        if (!$triggerAt || ($status === 'scheduled' && strtotime($triggerAt) <= time())) {
            automation_create_json(false, array(), 'Validation failed.', array('trigger_datetime' => 'Choose a future date and time.'), 422);
        }
    } elseif (in_array($trigger, array('birthday', 'anniversary', 'custom_recurring'), true)) {
        if (!checkdate($month, $day, (int) gmdate('Y'))) {
            automation_create_json(false, array(), 'Validation failed.', array('recurring_date' => 'Valid recurring month and day required.'), 422);
        }
        $year = (int) gmdate('Y');
        $triggerAt = null;
        for ($i = 0; $i < 4; $i++) {
            if (checkdate($month, $day, $year + $i)) {
                $candidate = new DateTimeImmutable(sprintf('%04d-%02d-%02d 09:00:00', $year + $i, $month, $day), new DateTimeZone('UTC'));
                if ($candidate->getTimestamp() > time()) { $triggerAt = $candidate->format('Y-m-d H:i:s'); break; }
            }
        }
    } elseif ($trigger === 'linked_milestone_event') {
        if (!in_array($linkedType, array('milestone', 'event'), true) || $linkedId <= 0) {
            automation_create_json(false, array(), 'Validation failed.', array('linked_resource' => 'Choose a valid event or milestone.'), 422);
        }
    }

    $db = (new Database())->getConnection();
    $actions = isset($data['actions']) && is_array($data['actions']) ? $data['actions'] : array();
    try {
        $safeActions = automation_validate_actions($db, $actions, $owner);
    } catch (InvalidArgumentException $validationError) {
        automation_create_json(false, array(), 'Validation failed.', array('actions' => $validationError->getMessage()), 422);
    }

    if ($status === 'scheduled' && !$triggerAt) {
        automation_create_json(false, array(), 'Validation failed.', array('next_run_at' => 'A valid future trigger is required.'), 422);
    }

    $db->beginTransaction();
    $rule = $db->prepare('INSERT INTO automation_rules(owner_id,title,description,trigger_type,trigger_datetime,recurring_month,recurring_day,linked_resource_type,linked_resource_id,timezone,next_run_at,status) VALUES(:owner,:title,:description,:trigger,:trigger_at,:month,:day,:linked_type,:linked_id,:timezone,:next_run,:status)');
    $rule->execute(array(
        'owner' => $owner, 'title' => $title, 'description' => $description !== '' ? $description : null,
        'trigger' => $trigger, 'trigger_at' => $triggerAt, 'month' => $month ?: null, 'day' => $day ?: null,
        'linked_type' => $trigger === 'linked_milestone_event' ? $linkedType : null,
        'linked_id' => $trigger === 'linked_milestone_event' ? $linkedId : null,
        'timezone' => 'UTC', 'next_run' => $triggerAt, 'status' => $status,
    ));
    $ruleId = (int) $db->lastInsertId();
    $insertAction = $db->prepare('INSERT INTO automation_actions(rule_id,action_type,payload) VALUES(:rule,:type,:payload)');
    foreach ($safeActions as $action) {
        $insertAction->execute(array('rule' => $ruleId, 'type' => $action['action_type'], 'payload' => json_encode($action['payload'])));
    }
    $db->commit();
    automation_create_json(true, array('automation_id' => $ruleId), 'Automation created.', array(), 201);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    Logger::error('Automation create failed', array('error' => $e->getMessage()));
    automation_create_json(false, array(), 'Unable to create automation.', array(), 500);
}
