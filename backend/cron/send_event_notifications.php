<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';

// This script should be run via cron job every hour or every 30 minutes

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get current time
    $currentTime = date('Y-m-d H:i:s');
    
    // Get events happening in the next hour that haven't been notified yet
    $stmt = $conn->prepare("
        SELECT id, user_id, event_type, title, message, scheduled_date, privacy_level
        FROM scheduled_events 
        WHERE status = 'scheduled'
        AND scheduled_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
        AND (notified = 0 OR notified IS NULL)
        ORDER BY scheduled_date ASC
    ");
    
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $logFile = __DIR__ . '/../../data/logs/event_notifications.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    foreach ($events as $event) {
        try {
            // Get user details
            $userStmt = $conn->prepare("SELECT full_name FROM users WHERE id = :id");
            $userStmt->execute(['id' => $event['user_id']]);
            $user = $userStmt->fetch();

            if (!$user) {
                continue;
            }

            // Get family members based on privacy level
            $familyQuery = "
                SELECT DISTINCT u.id, u.full_name, u.email
                FROM users u
                INNER JOIN family_members fm ON (
                    (fm.user_id = :user_id AND fm.family_member_id = u.id)
                    OR
                    (fm.family_member_id = :user_id AND fm.user_id = u.id)
                )
                WHERE fm.status = 'active' 
                AND fm.approved = 1
                AND u.status = 'active'
            ";

            // If event is public, also include approved family members
            // If private, only the user (which we won't notify)
            if ($event['privacy_level'] === 'private') {
                // Don't send notifications for private events
                // Mark as notified
                $updateStmt = $conn->prepare("UPDATE scheduled_events SET notified = 1, notified_at = NOW() WHERE id = :id");
                $updateStmt->execute(['id' => $event['id']]);
                continue;
            }

            $familyStmt = $conn->prepare($familyQuery);
            $familyStmt->execute(['user_id' => $event['user_id']]);
            $familyMembers = $familyStmt->fetchAll(PDO::FETCH_ASSOC);

            $emailsSent = 0;
            $emailsFailed = 0;

            // Send email to each family member
            foreach ($familyMembers as $member) {
                $emailSent = EmailHelper::sendEventNotificationEmail(
                    $member['email'],
                    $member['full_name'],
                    $event,
                    $user['full_name']
                );

                if ($emailSent) {
                    $emailsSent++;
                } else {
                    $emailsFailed++;
                }

                // Log individual email
                file_put_contents(
                    $logFile,
                    "[" . date('Y-m-d H:i:s') . "] Event #{$event['id']} - Email " . 
                    ($emailSent ? "SENT" : "FAILED") . " to {$member['email']}\n",
                    FILE_APPEND
                );
            }

            // Mark event as notified
            $updateStmt = $conn->prepare("
                UPDATE scheduled_events 
                SET notified = 1, notified_at = NOW() 
                WHERE id = :id
            ");
            $updateStmt->execute(['id' => $event['id']]);

            // Log summary
            file_put_contents(
                $logFile,
                "[" . date('Y-m-d H:i:s') . "] Event #{$event['id']} '{$event['title']}' - " .
                "Sent: $emailsSent, Failed: $emailsFailed, Total Family: " . count($familyMembers) . "\n",
                FILE_APPEND
            );

        } catch (Exception $e) {
            error_log("Error processing event #{$event['id']}: " . $e->getMessage());
            file_put_contents(
                $logFile,
                "[" . date('Y-m-d H:i:s') . "] ERROR - Event #{$event['id']}: {$e->getMessage()}\n",
                FILE_APPEND
            );
        }
    }

    // Log completion
    $eventCount = count($events);
    file_put_contents(
        $logFile,
        "[" . date('Y-m-d H:i:s') . "] Cron job completed - Processed $eventCount events\n\n",
        FILE_APPEND
    );

    echo json_encode([
        'success' => true,
        'message' => "Processed $eventCount events",
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Event notification cron error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>