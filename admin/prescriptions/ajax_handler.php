<?php

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once('../../config.php');

while (ob_get_level()) {
    ob_end_clean();
}

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (function_exists('validate_csrf')) {
    $csrf_valid = validate_csrf();
    if (!$csrf_valid) {
        
        error_log("CSRF validation failed. POST token: " . (isset($_POST['csrf_token']) ? substr($_POST['csrf_token'], 0, 10) . '...' : 'missing') . " | Session token: " . (isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 10) . '...' : 'missing'));
        die(json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']));
    }
} else {
    
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die(json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']));
    }
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    if($action == 'set_medication_schedule') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $product_id = intval($_POST['product_id'] ?? 0);
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $medicine_name = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $frequency = trim($_POST['frequency'] ?? '');
        $times = $_POST['times'] ?? '[]';
        $duration_days = intval($_POST['duration_days'] ?? 7);
        $instructions = trim($_POST['instructions'] ?? '');
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        
        
        if (!$customer_id || !$medicine_name || !$dosage) {
            die(json_encode(['success' => false, 'message' => 'Missing required fields']));
        }
        
        if (!$duration_days || $duration_days < 1) {
            $duration_days = 7;
        }
        
        
        $times_array = json_decode($times, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($times_array) || empty($times_array)) {
            $times_array = ['08:00'];
        }
        $times_str = implode(', ', array_map(function($t) { return date('g:i A', strtotime($t)); }, $times_array));
        
        
        $customer_info = null;
        $stmt = $conn->prepare("SELECT firstname, lastname, contact, email FROM customer_list WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $r = $stmt->get_result();
            $customer_info = $r ? $r->fetch_assoc() : null;
            $stmt->close();
        }
        
        $reminderManager = null;
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$duration_days} days"));
        
        try {
            $reminder_file = __DIR__ . '/../../classes/MedicationReminderManager.php';
            if (file_exists($reminder_file)) {
                require_once($reminder_file);
                if (class_exists('MedicationReminderManager')) {
                    $reminderManager = new MedicationReminderManager();
                    
                    if ($reminder_id > 0) {
                        
                        $res = $reminderManager->updateReminder($reminder_id, [
                            'medicine_name' => $medicine_name,
                            'dosage' => $dosage,
                            'frequency' => $frequency,
                            'reminder_times' => $times_array,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'instructions' => $instructions,
                            'status' => 'active'
                        ]);
                        if (!$res['success']) {
                            error_log("updateReminder error: " . ($res['error'] ?? ''));
                        }
                    } else {
                        
                        $exist = $conn->query("SELECT id FROM medication_reminders WHERE customer_id=" . (int)$customer_id . " AND created_from_order=" . (int)$order_id . " AND medicine_name='" . $conn->real_escape_string($medicine_name) . "' AND status IN ('active','paused') LIMIT 1");
                        if ($exist && $exist->num_rows > 0) {
                            $row = $exist->fetch_assoc();
                            $reminderManager->updateReminder((int)$row['id'], [
                                'medicine_name' => $medicine_name,
                                'dosage' => $dosage,
                                'frequency' => $frequency,
                                'reminder_times' => $times_array,
                                'start_date' => $start_date,
                                'end_date' => $end_date,
                                'instructions' => $instructions,
                                'status' => 'active'
                            ]);
                        } else {
                            $reminderManager->createReminder([
                                'customer_id' => $customer_id,
                                'created_from_order' => $order_id > 0 ? $order_id : null,
                                'order_id' => $order_id > 0 ? $order_id : null,
                                'product_id' => $product_id > 0 ? $product_id : null,
                                'medicine_name' => $medicine_name,
                                'dosage' => $dosage,
                                'frequency' => $frequency,
                                'reminder_times' => $times_array,
                                'start_date' => $start_date,
                                'end_date' => $end_date,
                                'instructions' => $instructions,
                                'notify_customer' => 1,
                                'skip_immediate_sms' => 1
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("MedicationReminderManager error: " . $e->getMessage());
        }
        
        
        try {
            if ($customer_info && !empty($customer_info['contact'])) {
                require_once(__DIR__ . '/../../classes/HttpSmsGateway.php');
                $smsGateway = new HttpSmsGateway($conn);
                $sms_message = "PickupDrug: Hi {$customer_info['firstname']}! Medication schedule: {$medicine_name} {$dosage} {$times_str} for {$duration_days} days";
                $smsGateway->sendSMS($customer_info['contact'], $sms_message);
            }
        } catch (Exception $e) { error_log("SMS error: " . $e->getMessage()); }
        
        
        try {
            $ntitle = "Medication Schedule Saved";
            $nmsg = "Your medication schedule for {$medicine_name} ({$dosage}) has been set. Reminder times: {$times_str}. Duration: {$duration_days} days.";
            $oid = $order_id > 0 ? $order_id : 0;
            $nstmt = $conn->prepare("INSERT INTO notifications (customer_id, title, message, order_id, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            if ($nstmt) {
                $nstmt->bind_param('issi', $customer_id, $ntitle, $nmsg, $oid);
                $nstmt->execute();
                $nstmt->close();
            }
        } catch (Exception $e) { error_log("Notification insert error: " . $e->getMessage()); }
        
        
        try {
            if ($customer_info && !empty($customer_info['email']) && $reminderManager) {
                $cname = trim(($customer_info['firstname'] ?? '') . ' ' . ($customer_info['lastname'] ?? ''));
                $reminderManager->sendScheduleSavedEmail($customer_info['email'], $cname, $medicine_name, $dosage, $times_str, $duration_days, $instructions);
            }
        } catch (Exception $e) { error_log("Email error: " . $e->getMessage()); }
        
        die(json_encode(['success' => true, 'message' => 'Schedule saved. Customer notified (in-app, SMS, and email).']));
    }
    
    if($action == 'delete_schedule') {
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        
        if ($reminder_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM medication_reminders WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $reminder_id);
                    $stmt->execute();
                    die(json_encode(['success' => true, 'message' => 'Schedule deleted']));
                }
            } catch (Exception $e) {
                error_log("Delete schedule error: " . $e->getMessage());
            }
        }
        
        die(json_encode(['success' => false, 'message' => 'Failed to delete schedule']));
    }
    
    if($action == 'create_new_reminder') {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $medicine_name = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $frequency = trim($_POST['frequency'] ?? '');
        $times = $_POST['times'] ?? '[]';
        $duration_days = intval($_POST['duration_days'] ?? 7);
        $instructions = trim($_POST['instructions'] ?? '');
        
        
        if (!$customer_id || !$medicine_name || !$dosage) {
            die(json_encode(['success' => false, 'message' => 'Missing required fields']));
        }
        
        if (!$duration_days || $duration_days < 1) {
            $duration_days = 7;
        }
        
        
        $times_array = json_decode($times, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($times_array) || empty($times_array)) {
            $times_array = ['08:00'];
        }
        $times_str = implode(', ', array_map(function($t) { return date('g:i A', strtotime($t)); }, $times_array));
        
        
        $customer_info = null;
        $stmt = $conn->prepare("SELECT firstname, lastname, contact, email FROM customer_list WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $r = $stmt->get_result();
            $customer_info = $r ? $r->fetch_assoc() : null;
            $stmt->close();
        }
        
        if (!$customer_info) {
            die(json_encode(['success' => false, 'message' => 'Customer not found']));
        }
        
        $reminderManager = null;
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$duration_days} days"));
        
        try {
            $reminder_file = __DIR__ . '/../../classes/MedicationReminderManager.php';
            if (file_exists($reminder_file)) {
                require_once($reminder_file);
                if (class_exists('MedicationReminderManager')) {
                    $reminderManager = new MedicationReminderManager();
                    
                    $result = $reminderManager->createReminder([
                        'customer_id' => $customer_id,
                        'created_from_order' => null,
                        'order_id' => null,
                        'product_id' => null,
                        'medicine_name' => $medicine_name,
                        'dosage' => $dosage,
                        'frequency' => $frequency,
                        'reminder_times' => $times_array,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'instructions' => $instructions,
                        'notify_customer' => 1,
                        'skip_immediate_sms' => 1
                    ]);
                    
                    if (!$result['success']) {
                        die(json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to create reminder']));
                    }
                }
            }
        } catch (Exception $e) {
            error_log("MedicationReminderManager error: " . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error creating reminder: ' . $e->getMessage()]));
        }
        
        
        try {
            if ($customer_info && !empty($customer_info['contact'])) {
                require_once(__DIR__ . '/../../classes/HttpSmsGateway.php');
                $smsGateway = new HttpSmsGateway($conn);
                $sms_message = "PickupDrug: Hi {$customer_info['firstname']}! New medication reminder: {$medicine_name} {$dosage} {$times_str} for {$duration_days} days";
                $smsGateway->sendSMS($customer_info['contact'], $sms_message);
            }
        } catch (Exception $e) { error_log("SMS error: " . $e->getMessage()); }
        
        
        try {
            $ntitle = "New Medication Reminder";
            $nmsg = "A new medication reminder for {$medicine_name} ({$dosage}) has been set. Reminder times: {$times_str}. Duration: {$duration_days} days.";
            $nstmt = $conn->prepare("INSERT INTO notifications (customer_id, title, message, order_id, is_read, created_at) VALUES (?, ?, ?, 0, 0, NOW())");
            if ($nstmt) {
                $nstmt->bind_param('iss', $customer_id, $ntitle, $nmsg);
                $nstmt->execute();
                $nstmt->close();
            }
        } catch (Exception $e) { error_log("Notification insert error: " . $e->getMessage()); }
        
        
        try {
            if ($customer_info && !empty($customer_info['email']) && $reminderManager) {
                $cname = trim(($customer_info['firstname'] ?? '') . ' ' . ($customer_info['lastname'] ?? ''));
                $reminderManager->sendScheduleSavedEmail($customer_info['email'], $cname, $medicine_name, $dosage, $times_str, $duration_days, $instructions);
            }
        } catch (Exception $e) { error_log("Email error: " . $e->getMessage()); }
        
        die(json_encode(['success' => true, 'message' => 'New reminder created successfully! Customer notified (in-app, SMS, and email).']));
    }
    
    die(json_encode($response));
    
} catch (Exception $e) {
    error_log("Prescriptions AJAX error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
}
