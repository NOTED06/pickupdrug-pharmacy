<?php

require_once('DBConnection.php');
require_once('HttpSmsGateway.php');
require_once('EmailVerification.php');

class MedicationReminderManager extends DBConnection {
    private $smsGateway;
    private $emailService;
    
    public function __construct() {
        parent::__construct();
        $this->smsGateway = new HttpSmsGateway($this->conn);
        $this->emailService = new EmailVerification();
        $this->createTablesIfNotExist();
    }
    
    
    private function createTablesIfNotExist() {
        $sql_file = __DIR__ . '/../database/create_medication_reminders.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $queries = explode(';', $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && strpos($query, '--') !== 0) {
                    try {
                        $this->conn->query($query);
                    } catch (Exception $e) {
                        
                        if (!in_array($this->conn->errno, [1050, 1060, 1061])) {
                            error_log("MedicationReminderManager SQL Error: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        $t = $this->conn->query("SHOW TABLES LIKE 'medication_reminders'");
        if ($t && $t->num_rows > 0) {
            
            foreach (['order_id' => 'INT NULL', 'product_id' => 'INT NULL'] as $col => $def) {
                $c = $this->conn->query("SHOW COLUMNS FROM medication_reminders LIKE '{$col}'");
                if (!$c || $c->num_rows == 0) {
                    @$this->conn->query("ALTER TABLE medication_reminders ADD COLUMN `{$col}` {$def} DEFAULT NULL");
                }
            }
            
            
            $new_columns = [
                'timing_food' => "VARCHAR(50) NULL COMMENT 'before_food, after_food, with_food, empty_stomach'",
                'timing_time_of_day' => "VARCHAR(50) NULL COMMENT 'morning, afternoon, evening, night'",
                'timing_interval' => "VARCHAR(50) NULL COMMENT 'every_4_hours, every_6_hours, every_8_hours, every_12_hours'",
                'interactions_medicines' => "TEXT NULL COMMENT 'Other medicines to avoid'",
                'interactions_alcohol' => "TINYINT(1) DEFAULT 0 COMMENT '0=ok, 1=avoid alcohol'",
                'interactions_supplements' => "TEXT NULL COMMENT 'Supplements/herbal meds to avoid'",
                'side_effects' => "TEXT NULL COMMENT 'Common side effects to watch for'",
                'duration_days' => "INT NULL COMMENT 'Total days to take medication'",
                'special_conditions' => "TEXT NULL COMMENT 'pregnancy, kidney_problems, liver_problems, children, etc.'",
                'missed_dose_instructions' => "TEXT NULL COMMENT 'What to do if dose is missed'"
            ];
            
            foreach ($new_columns as $col => $def) {
                $c = $this->conn->query("SHOW COLUMNS FROM medication_reminders LIKE '{$col}'");
                if (!$c || $c->num_rows == 0) {
                    @$this->conn->query("ALTER TABLE medication_reminders ADD COLUMN `{$col}` {$def}");
                }
            }
        }
    }
    
    
    public function createReminder($data) {
        $customer_id = (int)$data['customer_id'];
        $medicine_name = $this->conn->real_escape_string($data['medicine_name']);
        $dosage = $this->conn->real_escape_string($data['dosage']);
        $frequency = $this->conn->real_escape_string($data['frequency']);
        $reminder_times = is_array($data['reminder_times']) ? json_encode($data['reminder_times']) : $data['reminder_times'];
        $start_date = $this->conn->real_escape_string($data['start_date']);
        $end_date = !empty($data['end_date']) ? "'" . $this->conn->real_escape_string($data['end_date']) . "'" : 'NULL';
        $instructions = !empty($data['instructions']) ? "'" . $this->conn->real_escape_string($data['instructions']) . "'" : 'NULL';
        $prescription_image = !empty($data['prescription_image']) ? "'" . $this->conn->real_escape_string($data['prescription_image']) . "'" : 'NULL';
        $created_from_order = !empty($data['created_from_order']) ? (int)$data['created_from_order'] : 'NULL';
        $order_id = !empty($data['order_id']) ? (int)$data['order_id'] : ($created_from_order !== 'NULL' ? $created_from_order : 'NULL');
        $product_id = isset($data['product_id']) && $data['product_id'] !== '' && $data['product_id'] !== null ? (int)$data['product_id'] : 'NULL';
        $notify_customer = isset($data['notify_customer']) ? (int)$data['notify_customer'] : 1;
        $skip_immediate_sms = !empty($data['skip_immediate_sms']);
        
        
        $timing_food = !empty($data['timing_food']) ? "'" . $this->conn->real_escape_string($data['timing_food']) . "'" : 'NULL';
        $timing_time_of_day = !empty($data['timing_time_of_day']) ? "'" . $this->conn->real_escape_string($data['timing_time_of_day']) . "'" : 'NULL';
        $timing_interval = !empty($data['timing_interval']) ? "'" . $this->conn->real_escape_string($data['timing_interval']) . "'" : 'NULL';
        $interactions_medicines = !empty($data['interactions_medicines']) ? "'" . $this->conn->real_escape_string($data['interactions_medicines']) . "'" : 'NULL';
        $interactions_alcohol = isset($data['interactions_alcohol']) ? (int)$data['interactions_alcohol'] : 0;
        $interactions_supplements = !empty($data['interactions_supplements']) ? "'" . $this->conn->real_escape_string($data['interactions_supplements']) . "'" : 'NULL';
        $side_effects = !empty($data['side_effects']) ? "'" . $this->conn->real_escape_string($data['side_effects']) . "'" : 'NULL';
        $duration_days = !empty($data['duration_days']) ? (int)$data['duration_days'] : 'NULL';
        $special_conditions = !empty($data['special_conditions']) ? "'" . $this->conn->real_escape_string($data['special_conditions']) . "'" : 'NULL';
        $missed_dose_instructions = !empty($data['missed_dose_instructions']) ? "'" . $this->conn->real_escape_string($data['missed_dose_instructions']) . "'" : 'NULL';
        
        $query = "INSERT INTO medication_reminders 
                  (customer_id, medicine_name, dosage, frequency, reminder_times, start_date, end_date, 
                   instructions, prescription_image, created_from_order, order_id, product_id, notify_customer, status,
                   timing_food, timing_time_of_day, timing_interval, interactions_medicines, interactions_alcohol,
                   interactions_supplements, side_effects, duration_days, special_conditions, missed_dose_instructions)
                  VALUES 
                  ($customer_id, '$medicine_name', '$dosage', '$frequency', '$reminder_times', '$start_date', 
                   $end_date, $instructions, $prescription_image, $created_from_order, $order_id, $product_id, $notify_customer, 'active',
                   $timing_food, $timing_time_of_day, $timing_interval, $interactions_medicines, $interactions_alcohol,
                   $interactions_supplements, $side_effects, $duration_days, $special_conditions, $missed_dose_instructions)";
        
        if ($this->conn->query($query)) {
            $reminder_id = $this->conn->insert_id;
            
            
            if ($notify_customer && !$skip_immediate_sms) {
                $customer = $this->getCustomerInfo($customer_id);
                if ($customer && !empty($customer['contact'])) {
                    $times_display = implode(', ', json_decode($reminder_times, true));
                    $message = "PickupDrug: Medication reminder set for {$medicine_name} ({$dosage}). ";
                    $message .= "You'll receive reminders at: {$times_display}. ";
                    if (!empty($data['end_date'])) {
                        $message .= "Until: " . date('M d, Y', strtotime($data['end_date']));
                    }
                    $this->smsGateway->sendSMS($customer['contact'], $message);
                }
            }
            
            return [
                'success' => true,
                'reminder_id' => $reminder_id,
                'message' => 'Medication reminder created successfully'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create reminder: ' . $this->conn->error
        ];
    }
    
    
    public function updateReminder($reminder_id, $data, $send_notifications = true) {
        $reminder_id = (int)$reminder_id;
        
        
        $existing_reminder = $this->getReminder($reminder_id);
        if (!$existing_reminder) {
            return ['success' => false, 'error' => 'Reminder not found'];
        }
        
        $updates = [];
        
        $allowed_fields = ['medicine_name', 'dosage', 'frequency', 'start_date', 'end_date', 
                           'instructions', 'status', 'notify_customer', 'timing_food', 'timing_time_of_day',
                           'timing_interval', 'interactions_medicines', 'interactions_alcohol', 
                           'interactions_supplements', 'side_effects', 'duration_days', 'special_conditions',
                           'missed_dose_instructions'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'end_date' && empty($data[$field])) {
                    $updates[] = "`{$field}` = NULL";
                } else if (in_array($field, ['notify_customer', 'interactions_alcohol'])) {
                    $updates[] = "`{$field}` = " . (int)$data[$field];
                } else if ($field === 'duration_days') {
                    if (empty($data[$field])) {
                        $updates[] = "`{$field}` = NULL";
                    } else {
                        $updates[] = "`{$field}` = " . (int)$data[$field];
                    }
                } else {
                    $value = $this->conn->real_escape_string($data[$field]);
                    $updates[] = "`{$field}` = '{$value}'";
                }
            }
        }
        
        if (isset($data['reminder_times'])) {
            $times = is_array($data['reminder_times']) ? json_encode($data['reminder_times']) : $data['reminder_times'];
            $updates[] = "`reminder_times` = '{$times}'";
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $query = "UPDATE medication_reminders SET " . implode(', ', $updates) . " WHERE id = {$reminder_id}";
        
        if ($this->conn->query($query)) {
            
            if ($send_notifications && (!isset($data['notify_customer']) || $data['notify_customer'] == 1)) {
                $this->sendReminderUpdateNotifications($reminder_id, $data);
            }
            
            return ['success' => true, 'message' => 'Reminder updated successfully'];
        }
        
        return ['success' => false, 'error' => 'Failed to update: ' . $this->conn->error];
    }
    
    
    private function sendReminderUpdateNotifications($reminder_id, $updated_data) {
        $reminder = $this->getReminder($reminder_id);
        if (!$reminder) {
            return;
        }
        
        $customer = $this->getCustomerInfo($reminder['customer_id']);
        if (!$customer) {
            return;
        }
        
        
        $medicine_name = $updated_data['medicine_name'] ?? $reminder['medicine_name'];
        $dosage = $updated_data['dosage'] ?? $reminder['dosage'];
        $reminder_times = $updated_data['reminder_times'] ?? $reminder['reminder_times'];
        $duration_days = $updated_data['duration_days'] ?? null;
        $instructions = $updated_data['instructions'] ?? $reminder['instructions'] ?? '';
        
        
        $times_array = is_array($reminder_times) ? $reminder_times : json_decode($reminder_times, true);
        $times_str = implode(', ', array_map(function($t) { 
            return date('g:i A', strtotime($t)); 
        }, $times_array));
        
        
        if (!$duration_days && $reminder['start_date'] && $reminder['end_date']) {
            $start = new DateTime($reminder['start_date']);
            $end = new DateTime($reminder['end_date']);
            $duration_days = $start->diff($end)->days;
        }
        
        
        if (!empty($customer['contact'])) {
            $sms_message = "PickupDrug: Your medication reminder for {$medicine_name} ({$dosage}) has been UPDATED. ";
            $sms_message .= "New reminder times: {$times_str}. ";
            if ($duration_days) {
                $sms_message .= "Duration: {$duration_days} days. ";
            }
            $sms_message .= "Please check your account for details.";
            $this->smsGateway->sendSMS($customer['contact'], $sms_message);
        }
        
        
        if (!empty($customer['email'])) {
            $customer_name = trim(($customer['firstname'] ?? '') . ' ' . ($customer['lastname'] ?? ''));
            $this->sendReminderUpdatedEmail($customer['email'], $customer_name, $medicine_name, $dosage, $times_str, $duration_days, $instructions);
        }
        
        
        try {
            $ntitle = "Medication Reminder Updated";
            $nmsg = "Your medication reminder for {$medicine_name} ({$dosage}) has been updated. New reminder times: {$times_str}.";
            if ($duration_days) {
                $nmsg .= " Duration: {$duration_days} days.";
            }
            
            $order_id = $reminder['order_id'] ?? 0;
            $stmt = $this->conn->prepare("INSERT INTO notifications (customer_id, title, message, order_id, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            if ($stmt) {
                $stmt->bind_param('issi', $reminder['customer_id'], $ntitle, $nmsg, $order_id);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Notification insert error: " . $e->getMessage());
        }
    }
    
    
    public function sendReminderUpdatedEmail($to_email, $to_name, $medicine_name, $dosage, $times_str, $duration_days, $instructions = '') {
        if (empty($to_email)) return false;
        $body = $this->getReminderUpdatedEmailTemplate($to_name, $medicine_name, $dosage, $times_str, $duration_days, $instructions);
        $subject = '🔄 Medication Reminder Updated: ' . $medicine_name;
        $this->emailService->sendNotificationEmail($to_email, $to_name, $subject, $body);
        return true;
    }
    
    
    public function getReminderUpdatedEmailTemplate($customer_name, $medicine_name, $dosage, $times_str, $duration_days, $instructions = '') {
        $instructions = $instructions ?: 'Take as prescribed';
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Medication Reminder Updated</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f7f9;">
            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td align="center" style="padding: 40px 20px;">
                        <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <tr>
                                <td style="padding: 30px 40px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px 12px 0 0; text-align: center;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px;">🔄 Medication Reminder Updated</h1>
                                    <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">Your medication schedule has been updated</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 40px;">
                                    <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">Hello <strong>' . htmlspecialchars($customer_name) . '</strong>,</p>
                                    <p style="margin: 0 0 25px 0; font-size: 15px; color: #6b7280; line-height: 1.6;">Your medication reminder has been updated. Here are the new details:</p>
                                    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #eff6ff; border-radius: 10px; border: 1px solid #bfdbfe;">
                                        <tr><td style="padding: 15px; border-bottom: 1px solid #bfdbfe;"><strong>Medicine:</strong> ' . htmlspecialchars($medicine_name) . '</td></tr>
                                        <tr><td style="padding: 15px; border-bottom: 1px solid #bfdbfe;"><strong>Dosage:</strong> ' . htmlspecialchars($dosage) . '</td></tr>
                                        <tr><td style="padding: 15px; border-bottom: 1px solid #bfdbfe;"><strong>Updated Reminder Times:</strong> ' . htmlspecialchars($times_str) . '</td></tr>
                                        ' . ($duration_days ? '<tr><td style="padding: 15px; border-bottom: 1px solid #bfdbfe;"><strong>Duration:</strong> ' . (int)$duration_days . ' days</td></tr>' : '') . '
                                        <tr><td style="padding: 15px;"><strong>Instructions:</strong> ' . htmlspecialchars($instructions) . '</td></tr>
                                    </table>
                                    <p style="margin: 25px 0 0 0; font-size: 14px; color: #6b7280;">You will receive SMS and in-app reminders at the updated scheduled times. View your schedules in your account.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 25px 40px; background-color: #f9fafb; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb; text-align: center;">
                                    <p style="margin: 0; font-size: 14px; font-weight: bold; color: #2563eb;">PickupDrug / Rose Pharmacy</p>
                                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #9ca3af;">Your trusted healthcare partner</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
    
    
    public function deleteReminder($reminder_id) {
        $reminder_id = (int)$reminder_id;
        
        
        $query = "UPDATE medication_reminders SET status = 'cancelled' WHERE id = {$reminder_id}";
        
        if ($this->conn->query($query)) {
            return ['success' => true, 'message' => 'Reminder cancelled successfully'];
        }
        
        return ['success' => false, 'error' => 'Failed to cancel reminder'];
    }
    
    
    public function getPendingReminders() {
        $current_time = date('H:i:00');
        $current_date = date('Y-m-d');
        
        $query = "SELECT r.*, c.contact, c.email, c.firstname 
                  FROM medication_reminders r
                  INNER JOIN customer_list c ON r.customer_id = c.id
                  WHERE r.status = 'active'
                  AND r.notify_customer = 1
                  AND (r.end_date IS NULL OR r.end_date >= '{$current_date}')
                  AND r.start_date <= '{$current_date}'
                  LIMIT 100";
        
        $result = $this->conn->query($query);
        $reminders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $reminders[] = $row;
            }
        }
        
        return $reminders;
    }
    
    
    public function getReminder($reminder_id) {
        $reminder_id = (int)$reminder_id;
        $query = "SELECT * FROM medication_reminders WHERE id = {$reminder_id}";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['reminder_times'] = json_decode($row['reminder_times'], true);
            return $row;
        }
        
        return null;
    }
    
    
    public function getCustomerReminders($customer_id, $status = 'active') {
        $customer_id = (int)$customer_id;
        $where = "WHERE customer_id = {$customer_id}";
        
        if (!empty($status)) {
            $status = $this->conn->real_escape_string($status);
            $where .= " AND status = '{$status}'";
        }
        
        $query = "SELECT * FROM medication_reminders {$where} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $reminders = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['reminder_times'] = json_decode($row['reminder_times'], true);
                $reminders[] = $row;
            }
        }
        
        return $reminders;
    }
    
    
    public function getDueReminders($current_time = null) {
        if ($current_time === null) {
            $current_time = date('H:i:00'); 
        }
        
        $current_date = date('Y-m-d');
        
        $query = "SELECT r.*, c.contact, c.email, c.firstname, c.lastname 
                  FROM medication_reminders r
                  INNER JOIN customer_list c ON r.customer_id = c.id
                  WHERE r.status = 'active'
                  AND r.notify_customer = 1
                  AND r.start_date <= '{$current_date}'
                  AND (r.end_date IS NULL OR r.end_date >= '{$current_date}')
                  AND JSON_CONTAINS(r.reminder_times, '\"{$current_time}\"')";
        
        $result = $this->conn->query($query);
        $reminders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                
                $check_sent = $this->conn->query("SELECT id FROM reminder_logs 
                                                   WHERE reminder_id = {$row['id']} 
                                                   AND DATE(scheduled_time) = '{$current_date}'
                                                   AND TIME(scheduled_time) = '{$current_time}'
                                                   AND status = 'sent'");
                
                if ($check_sent->num_rows === 0) {
                    $row['reminder_times'] = json_decode($row['reminder_times'], true);
                    $reminders[] = $row;
                }
            }
        }
        
        return $reminders;
    }
    
    
    public function getAdvanceReminders($future_time, $advance_minutes = 10) {
        $current_date = date('Y-m-d');
        
        
        $search_time = substr($future_time, 0, 5) . ':00';
        
        $query = "SELECT r.*, c.contact, c.email, c.firstname, c.lastname 
                  FROM medication_reminders r
                  INNER JOIN customer_list c ON r.customer_id = c.id
                  WHERE r.status = 'active'
                  AND r.notify_customer = 1
                  AND r.start_date <= '{$current_date}'
                  AND (r.end_date IS NULL OR r.end_date >= '{$current_date}')
                  AND JSON_CONTAINS(r.reminder_times, '\"{$search_time}\"')";
        
        $result = $this->conn->query($query);
        $reminders = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                
                $advance_key = "advance_{$advance_minutes}_{$search_time}";
                $check_sent = $this->conn->query("SELECT id FROM reminder_logs 
                                                   WHERE reminder_id = {$row['id']} 
                                                   AND DATE(scheduled_time) = '{$current_date}'
                                                   AND advance_type = '{$advance_key}'
                                                   AND status = 'sent'");
                
                if ($check_sent->num_rows === 0) {
                    $row['reminder_times'] = json_decode($row['reminder_times'], true);
                    $row['advance_minutes'] = $advance_minutes;
                    $row['scheduled_time_actual'] = $search_time;
                    $reminders[] = $row;
                }
            }
        }
        
        return $reminders;
    }
    
    
    public function sendReminder($reminder, $advance_minutes = 0) {
        $scheduled_time = date('Y-m-d H:i:00');
        
        
        if ($advance_minutes > 0) {
            
            $actual_time = isset($reminder['scheduled_time_actual']) ? 
                           date('g:i A', strtotime($reminder['scheduled_time_actual'])) : 
                           date('g:i A', strtotime("+{$advance_minutes} minutes"));
            
            $message = "⏰ PickupDrug: {$advance_minutes}min REMINDER! Take {$reminder['medicine_name']} ({$reminder['dosage']}) at {$actual_time}";
            if (!empty($reminder['instructions'])) {
                $message .= ". {$reminder['instructions']}";
            }
        } else {
            
            $message = "💊 PickupDrug: TIME TO TAKE {$reminder['medicine_name']} ({$reminder['dosage']}) NOW!";
            if (!empty($reminder['instructions'])) {
                $message .= " {$reminder['instructions']}";
            }
        }
        
        
        $result = $this->smsGateway->sendSMS($reminder['contact'], $message);
        
        
        if (!empty($reminder['email'])) {
            $customer_name = $reminder['firstname'] . ' ' . $reminder['lastname'];
            $emailBody = $this->getMedicationReminderEmailTemplate(
                $customer_name,
                $reminder['medicine_name'],
                $reminder['dosage'],
                $reminder['instructions'] ?? '',
                $advance_minutes
            );
            
            $subject = $advance_minutes > 0 
                ? "⏰ Medication Reminder: Take {$reminder['medicine_name']} in {$advance_minutes} minutes"
                : "💊 Time to take {$reminder['medicine_name']} NOW!";
            
            $this->emailService->sendNotificationEmail(
                $reminder['email'],
                $customer_name,
                $subject,
                $emailBody
            );
        }
        
        
        $status = $result['success'] ? 'sent' : 'failed';
        $message_id = $result['message_id'] ?? null;
        $error = $result['error'] ?? null;
        
        
        $advance_type = null;
        if ($advance_minutes > 0 && isset($reminder['scheduled_time_actual'])) {
            $advance_type = "advance_{$advance_minutes}_{$reminder['scheduled_time_actual']}";
        }
        
        $this->logReminder(
            $reminder['id'],
            $reminder['customer_id'],
            $scheduled_time,
            $status,
            $message_id,
            $error,
            $advance_type
        );
        
        return $result;
    }
    
    
    private function logReminder($reminder_id, $customer_id, $scheduled_time, $status, $message_id = null, $error = null, $advance_type = null) {
        $reminder_id = (int)$reminder_id;
        $customer_id = (int)$customer_id;
        $scheduled_time = $this->conn->real_escape_string($scheduled_time);
        $status = $this->conn->real_escape_string($status);
        $message_id = $message_id ? "'" . $this->conn->real_escape_string($message_id) . "'" : 'NULL';
        $error = $error ? "'" . $this->conn->real_escape_string($error) . "'" : 'NULL';
        $advance_type_sql = $advance_type ? "'" . $this->conn->real_escape_string($advance_type) . "'" : 'NULL';
        
        $query = "INSERT INTO reminder_logs (reminder_id, customer_id, scheduled_time, status, sms_message_id, error_message, advance_type)
                  VALUES ({$reminder_id}, {$customer_id}, '{$scheduled_time}', '{$status}', {$message_id}, {$error}, {$advance_type_sql})";
        
        $this->conn->query($query);
    }
    
    
    public function getReminderLogs($customer_id, $limit = 50) {
        $customer_id = (int)$customer_id;
        
        $query = "SELECT rl.*, mr.medicine_name, mr.dosage 
                  FROM reminder_logs rl
                  INNER JOIN medication_reminders mr ON rl.reminder_id = mr.id
                  WHERE rl.customer_id = {$customer_id}
                  ORDER BY rl.sent_at DESC
                  LIMIT {$limit}";
        
        $result = $this->conn->query($query);
        $logs = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
    
    
    private function getCustomerInfo($customer_id) {
        $customer_id = (int)$customer_id;
        $query = "SELECT id, firstname, lastname, contact, email FROM customer_list WHERE id = {$customer_id}";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    
    public function pauseReminder($reminder_id) {
        return $this->updateReminder($reminder_id, ['status' => 'paused']);
    }
    
    
    public function resumeReminder($reminder_id) {
        return $this->updateReminder($reminder_id, ['status' => 'active']);
    }
    
    
    public function completeReminder($reminder_id) {
        return $this->updateReminder($reminder_id, ['status' => 'completed']);
    }
    
    
    public function getStatistics($customer_id = null) {
        $where = $customer_id ? "WHERE customer_id = " . (int)$customer_id : "";
        
        $stats = [];
        
        
        $query = "SELECT COUNT(*) as count FROM medication_reminders {$where} AND status = 'active'";
        if ($customer_id) {
            $query = str_replace('AND', 'WHERE', $query);
        }
        $result = $this->conn->query($query);
        $stats['active_reminders'] = $result->fetch_assoc()['count'];
        
        
        $today = date('Y-m-d');
        $log_where = $customer_id ? "WHERE customer_id = " . (int)$customer_id . " AND" : "WHERE";
        $query = "SELECT COUNT(*) as count FROM reminder_logs {$log_where} DATE(sent_at) = '{$today}' AND status = 'sent'";
        $result = $this->conn->query($query);
        $stats['sent_today'] = $result->fetch_assoc()['count'];
        
        
        $query = "SELECT COUNT(*) as count FROM reminder_logs {$log_where} WEEK(sent_at) = WEEK(NOW()) AND status = 'sent'";
        $result = $this->conn->query($query);
        $stats['sent_this_week'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
    
    
    public function createFromPrescription($customer_id, $medicines, $order_id = null) {
        $created = [];
        $errors = [];
        
        foreach ($medicines as $medicine) {
            $data = [
                'customer_id' => $customer_id,
                'medicine_name' => $medicine['name'],
                'dosage' => $medicine['dosage'] ?? '1 tablet',
                'frequency' => $medicine['frequency'] ?? 'twice_daily',
                'reminder_times' => $medicine['reminder_times'] ?? ['09:00:00', '21:00:00'],
                'start_date' => date('Y-m-d'),
                'end_date' => isset($medicine['duration_days']) ? date('Y-m-d', strtotime('+' . $medicine['duration_days'] . ' days')) : null,
                'instructions' => $medicine['instructions'] ?? 'Take as prescribed',
                'prescription_image' => $medicine['image_path'] ?? null,
                'created_from_order' => $order_id,
                'notify_customer' => 1
            ];
            
            $result = $this->createReminder($data);
            
            if ($result['success']) {
                $created[] = $result['reminder_id'];
            } else {
                $errors[] = $result['error'];
            }
        }
        
        return [
            'success' => count($created) > 0,
            'created_count' => count($created),
            'reminder_ids' => $created,
            'errors' => $errors
        ];
    }
    
    
    public function getMedicationReminderEmailTemplate($customer_name, $medicine_name, $dosage, $instructions, $advance_minutes = 0) {
        $timing_text = $advance_minutes > 0 
            ? "in {$advance_minutes} minutes" 
            : "now";
        
        $reminder_message = $advance_minutes > 0
            ? "This is a friendly reminder that it's almost time to take your medication."
            : "It's time to take your medication. Please don't forget!";
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Medication Reminder</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f7f9;">
            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td align="center" style="padding: 40px 20px;">
                        <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="padding: 30px 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px 12px 0 0; text-align: center;">
                                    <table role="presentation" style="width: 100%;">
                                        <tr>
                                            <td align="center">
                                                <div style="width: 70px; height: 70px; background-color: rgba(255, 255, 255, 0.2); border-radius: 50%; display: inline-block; line-height: 70px;">
                                                    <span style="font-size: 36px;">💊</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top: 15px;">
                                                <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: bold;">Medication Reminder</h1>
                                                <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">Time to take your medicine ' . $timing_text . '</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px;">
                                    <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">Hello <strong>' . htmlspecialchars($customer_name) . '</strong>,</p>
                                    <p style="margin: 0 0 30px 0; font-size: 15px; color: #6b7280; line-height: 1.6;">' . $reminder_message . '</p>
                                    
                                    <!-- Medicine Info Card -->
                                    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f0fdf4; border-radius: 10px; border: 1px solid #bbf7d0;">
                                        <tr>
                                            <td style="padding: 25px;">
                                                <table role="presentation" style="width: 100%;">
                                                    <tr>
                                                        <td style="padding-bottom: 15px; border-bottom: 1px solid #bbf7d0;">
                                                            <p style="margin: 0; font-size: 12px; color: #059669; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Medicine</p>
                                                            <p style="margin: 5px 0 0 0; font-size: 20px; color: #065f46; font-weight: bold;">' . htmlspecialchars($medicine_name) . '</p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding-top: 15px; padding-bottom: 15px; border-bottom: 1px solid #bbf7d0;">
                                                            <p style="margin: 0; font-size: 12px; color: #059669; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Dosage</p>
                                                            <p style="margin: 5px 0 0 0; font-size: 16px; color: #374151;">' . htmlspecialchars($dosage) . '</p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding-top: 15px;">
                                                            <p style="margin: 0; font-size: 12px; color: #059669; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Instructions</p>
                                                            <p style="margin: 5px 0 0 0; font-size: 16px; color: #374151;">' . htmlspecialchars($instructions) . '</p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- Health Tips -->
                                    <table role="presentation" style="width: 100%; margin-top: 25px;">
                                        <tr>
                                            <td style="background-color: #fef3c7; border-radius: 8px; padding: 15px;">
                                                <p style="margin: 0; font-size: 14px; color: #92400e;">
                                                    <strong>💡 Tip:</strong> Take your medication with water and follow your doctor\'s instructions for best results.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- CTA Button -->
                                    <table role="presentation" style="width: 100%; margin-top: 30px;">
                                        <tr>
                                            <td align="center">
                                                <p style="margin: 0; font-size: 14px; color: #6b7280;">Need to refill your prescription?</p>
                                                <a href="' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/pickupdrug/customer/prescriptions.php" style="display: inline-block; margin-top: 15px; padding: 14px 30px; background-color: #10b981; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px;">Visit Rose Pharmacy</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="padding: 25px 40px; background-color: #f9fafb; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb;">
                                    <table role="presentation" style="width: 100%;">
                                        <tr>
                                            <td align="center">
                                                <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #059669;">Rose Pharmacy - MedPick</p>
                                                <p style="margin: 0 0 5px 0; font-size: 13px; color: #9ca3af;">Your trusted healthcare partner since 2025</p>
                                                <p style="margin: 10px 0 0 0; font-size: 12px; color: #9ca3af;">To manage your medication reminders, visit your account settings.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
    
    
    public function getScheduleSavedEmailTemplate($customer_name, $medicine_name, $dosage, $times_str, $duration_days, $instructions = '') {
        $instructions = $instructions ?: 'Take as prescribed';
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Medication Schedule Saved</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f7f9;">
            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td align="center" style="padding: 40px 20px;">
                        <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <tr>
                                <td style="padding: 30px 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px 12px 0 0; text-align: center;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px;">✅ Medication Schedule Saved</h1>
                                    <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">You will receive SMS reminders at the times below</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 40px;">
                                    <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151;">Hello <strong>' . htmlspecialchars($customer_name) . '</strong>,</p>
                                    <p style="margin: 0 0 25px 0; font-size: 15px; color: #6b7280; line-height: 1.6;">Your medication schedule has been set. Here are the details:</p>
                                    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f0fdf4; border-radius: 10px; border: 1px solid #bbf7d0;">
                                        <tr><td style="padding: 15px; border-bottom: 1px solid #bbf7d0;"><strong>Medicine:</strong> ' . htmlspecialchars($medicine_name) . '</td></tr>
                                        <tr><td style="padding: 15px; border-bottom: 1px solid #bbf7d0;"><strong>Dosage:</strong> ' . htmlspecialchars($dosage) . '</td></tr>
                                        <tr><td style="padding: 15px; border-bottom: 1px solid #bbf7d0;"><strong>Reminder times:</strong> ' . htmlspecialchars($times_str) . '</td></tr>
                                        <tr><td style="padding: 15px; border-bottom: 1px solid #bbf7d0;"><strong>Duration:</strong> ' . (int)$duration_days . ' days</td></tr>
                                        <tr><td style="padding: 15px;"><strong>Instructions:</strong> ' . htmlspecialchars($instructions) . '</td></tr>
                                    </table>
                                    <p style="margin: 25px 0 0 0; font-size: 14px; color: #6b7280;">You will receive SMS and in-app reminders at the scheduled times. View your schedules in your account.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 25px 40px; background-color: #f9fafb; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb; text-align: center;">
                                    <p style="margin: 0; font-size: 14px; font-weight: bold; color: #059669;">PickupDrug / Rose Pharmacy</p>
                                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #9ca3af;">Your trusted healthcare partner</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
    
    
    public function sendScheduleSavedEmail($to_email, $to_name, $medicine_name, $dosage, $times_str, $duration_days, $instructions = '') {
        if (empty($to_email)) return false;
        $body = $this->getScheduleSavedEmailTemplate($to_name, $medicine_name, $dosage, $times_str, $duration_days, $instructions);
        $subject = '✅ Medication Schedule Saved: ' . $medicine_name;
        $this->emailService->sendNotificationEmail($to_email, $to_name, $subject, $body);
        return true;
    }
}
?>
