<?php

if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    
    $cron_key = 'pickupdrug_cron_2025'; 
    
    if (!isset($_GET['cron_key']) || $_GET['cron_key'] !== $cron_key) {
        die('Unauthorized access. This script can only be run via CLI or with valid cron_key.');
    }
}

require_once(__DIR__ . '/initialize.php');
require_once(__DIR__ . '/classes/MedicationReminderManager.php');

$log_file = __DIR__ . '/logs/medication_reminders.log';
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

$start_time = microtime(true);
$timestamp = date('Y-m-d H:i:s');
log_message("=== Medication Reminder Cron Started at {$timestamp} ===");

try {
    
    $reminderManager = new MedicationReminderManager();
    
    
    $current_time = date('H:i:00');
    log_message("Checking for reminders due at: {$current_time}");
    
    
    $time_plus_10 = date('H:i:00', strtotime('+10 minutes'));
    $time_plus_5 = date('H:i:00', strtotime('+5 minutes'));
    
    log_message("Also checking advance reminders for: {$time_plus_10} (10min) and {$time_plus_5} (5min)");
    
    
    $due_reminders = $reminderManager->getDueReminders($current_time);
    
    
    $advance_10 = $reminderManager->getAdvanceReminders($time_plus_10, 10);
    
    
    $advance_5 = $reminderManager->getAdvanceReminders($time_plus_5, 5);
    
    
    $all_reminders = array_merge($due_reminders, $advance_10, $advance_5);
    
    if (empty($all_reminders)) {
        log_message("No reminders due at this time.");
    } else {
        log_message("Found " . count($all_reminders) . " reminder(s) to send.");
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($all_reminders as $reminder) {
            $customer_name = $reminder['firstname'] . ' ' . $reminder['lastname'];
            $advance_min = isset($reminder['advance_minutes']) ? $reminder['advance_minutes'] : 0;
            
            if($advance_min > 0) {
                log_message("Processing ADVANCE ({$advance_min}min) reminder for {$customer_name}: {$reminder['medicine_name']} ({$reminder['dosage']})");
            } else {
                log_message("Processing reminder for {$customer_name}: {$reminder['medicine_name']} ({$reminder['dosage']})");
            }
            
            
            $result = $reminderManager->sendReminder($reminder, $advance_min);
            
            if ($result['success']) {
                $sent_count++;
                log_message("✓ SMS sent successfully to {$reminder['contact']}. Message ID: {$result['message_id']}");
            } else {
                $failed_count++;
                log_message("✗ Failed to send SMS to {$reminder['contact']}: {$result['error']}");
            }
            
            
            sleep(2);
        }
        
        log_message("Summary: {$sent_count} sent, {$failed_count} failed");
    }
    
    
    $execution_time = round(microtime(true) - $start_time, 2);
    log_message("=== Cron completed in {$execution_time} seconds ===\n");
    
} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
}

function log_message($message) {
    global $log_file;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    
    if (php_sapi_name() === 'cli') {
        echo $log_entry;
    }
}

if (php_sapi_name() !== 'cli') {
    echo json_encode([
        'success' => true,
        'message' => 'Cron job executed',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
