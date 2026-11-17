<?php

if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    
    $cron_key = 'pickupdrug_cron_2025'; 
    
    if (!isset($_GET['cron_key']) || $_GET['cron_key'] !== $cron_key) {
        die('Unauthorized access. This script can only be run via CLI or with valid cron_key.');
    }
}

require_once(__DIR__ . '/initialize.php');
require_once(__DIR__ . '/classes/DBConnection.php');
require_once(__DIR__ . '/classes/Master.php');

$log_file = __DIR__ . '/logs/pickup_reminders.log';
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry; 
}

$start_time = microtime(true);
$timestamp = date('Y-m-d H:i:s');
log_message("=== Pickup Reminder Cron Started at {$timestamp} ===");

try {
    
    $Master = new Master();
    
    
    log_message("Checking for missed pickups...");
    $result = $Master->process_missed_pickups();
    
    if ($result) {
        $data = json_decode($result, true);
        if (isset($data['status']) && $data['status'] === 'success') {
            log_message("Success: " . $data['msg']);
            log_message("Processed: " . $data['processed'] . " orders");
            log_message("Notified: " . $data['notified'] . " customers");
        } else {
            log_message("Warning: " . ($data['msg'] ?? 'Unknown error'));
        }
    } else {
        log_message("No result returned from process_missed_pickups");
    }
    
    
    $execution_time = microtime(true) - $start_time;
    log_message(sprintf("Execution completed in %.2f seconds", $execution_time));
    
} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
}

log_message("=== Pickup Reminder Cron Finished ===\n");
