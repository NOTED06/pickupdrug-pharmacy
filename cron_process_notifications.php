<?php

$lockFile = __DIR__ . '/logs/notification_queue.lock';
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    
    if (time() - $lockTime > 300) {
        unlink($lockFile);
    } else {
        
        exit(0);
    }
}

@mkdir(__DIR__ . '/logs', 0755, true);
file_put_contents($lockFile, date('Y-m-d H:i:s'));

register_shutdown_function(function() use ($lockFile) {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
});

require_once(__DIR__ . '/initialize.php');
require_once(__DIR__ . '/classes/DBConnection.php');
require_once(__DIR__ . '/classes/HttpSmsGateway.php');
require_once(__DIR__ . '/customer/classes/EmailIntegration.php');

$db = new DBConnection();
$conn = $db->conn;

$isCli = (php_sapi_name() === 'cli');
if ($isCli) echo "[" . date('Y-m-d H:i:s') . "] Starting notification queue processor...\n";

$table_check = $conn->query("SHOW TABLES LIKE 'notification_queue'");
if ($table_check->num_rows == 0) {
    if ($isCli) echo "Notification queue table does not exist. Nothing to process.\n";
    exit(0);
}

$pending = $conn->query("SELECT * FROM `notification_queue` WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 20");

if (!$pending || $pending->num_rows == 0) {
    if ($isCli) echo "No pending notifications to process.\n";
    exit(0);
}

if ($isCli) echo "Found {$pending->num_rows} pending notifications.\n";

$smsGateway = new HttpSmsGateway($conn);
$sent = 0;
$failed = 0;

while ($item = $pending->fetch_assoc()) {
    $payload = json_decode($item['payload'], true);
    $success = false;
    $error_msg = '';
    
    if ($isCli) echo "Processing [{$item['id']}] {$item['notification_type']} to {$item['recipient_type']}... ";
    
    try {
        if ($item['notification_type'] === 'sms') {
            $result = $smsGateway->sendSMS($payload['phone'], $payload['message']);
            $success = $result['success'] ?? false;
            if (!$success) {
                $error_msg = $result['message'] ?? 'Unknown error';
            }
        } elseif ($item['notification_type'] === 'email') {
            
            if (isset($payload['type']) && $payload['type'] === 'status_update') {
                $success = EmailIntegration::sendOrderStatusEmail($payload['customer_id'], $payload['order_id'], $payload['status']);
            } else {
                $success = EmailIntegration::sendOrderConfirmationEmail($payload['customer_id'], $payload['order_id']);
            }
            if (!$success) {
                $error_msg = 'Email send failed';
            }
        }
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
    
    
    $new_status = $success ? 'sent' : ($item['attempts'] >= 2 ? 'failed' : 'pending');
    $stmt = $conn->prepare("UPDATE `notification_queue` SET status = ?, attempts = attempts + 1, processed_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $new_status, $item['id']);
    $stmt->execute();
    
    if ($success) {
        if ($isCli) echo "SENT\n";
        $sent++;
    } else {
        if ($isCli) echo "FAILED ({$error_msg})\n";
        $failed++;
    }
    
    
    usleep(100000); 
}

if ($isCli) {
    echo "\n=== Processing Complete ===\n";
    echo "Sent: {$sent}\n";
    echo "Failed: {$failed}\n";
}

$followup_table = $conn->query("SHOW TABLES LIKE 'urgent_order_followups'");
if ($followup_table && $followup_table->num_rows > 0) {
    $followups = $conn->query("SELECT f.*, o.status as order_status 
        FROM `urgent_order_followups` f 
        INNER JOIN `order_list` o ON f.order_id = o.id 
        WHERE f.is_processed = 0 AND f.followup_at <= NOW() 
        LIMIT 10");
    
    $followup_count = 0;
    if ($followups && $followups->num_rows > 0) {
        require_once(__DIR__ . '/classes/Master.php');
        $Master = new Master();
        
        while ($fu = $followups->fetch_assoc()) {
            
            if ((int)$fu['order_status'] === 0) {
                
                $Master->send_admin_notification(
                    "⏰ URGENT Follow-up: Order #{$fu['order_code']}",
                    "Urgent order #{$fu['order_code']} from {$fu['customer_name']} is still PENDING after 15 minutes! Please process this order immediately.",
                    $fu['order_id'],
                    'urgent'
                );
                
                
                $admin_phone_query = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'httpsms_phone_id'");
                if ($admin_phone_query && $admin_phone_query->num_rows > 0) {
                    $admin_phone = $admin_phone_query->fetch_assoc()['setting_value'];
                    if (!empty($admin_phone)) {
                        try {
                            $followup_sms = "⏰ URGENT FOLLOW-UP: Order #{$fu['order_code']} from {$fu['customer_name']} is STILL PENDING after 15 min! Please process immediately.";
                            $smsGateway->sendSMS($admin_phone, $followup_sms);
                        } catch (Exception $e) {
                            error_log("Urgent followup SMS error: " . $e->getMessage());
                        }
                    }
                }
                
                $followup_count++;
                if ($isCli) echo "Sent urgent follow-up for Order #{$fu['order_code']}\n";
            }
            
            
            $conn->query("UPDATE `urgent_order_followups` SET is_processed = 1 WHERE id = {$fu['id']}");
        }
    }
    
    if ($isCli && $followup_count > 0) {
        echo "Urgent follow-ups sent: {$followup_count}\n";
    }
}

if ($isCli) {
    echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
}
?>
