<?php
// This file can be accessed via browser for manual testing
// In production, you should protect this with authentication

// Display output as text
header('Content-Type: text/plain');

echo "=== Event Notification Trigger ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Include the cron job
require_once __DIR__ . '/send_event_notifications.php';

echo "\n=== Completed ===\n";
?>