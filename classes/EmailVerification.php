<?php

require_once(__DIR__ . '/../config.php');

if (!defined('RECAPTCHA_SECRET_KEY')) {
    define('RECAPTCHA_SECRET_KEY', env('RECAPTCHA_SECRET_KEY', '6Lc_R14sAAAAAK0GoWTQlz0Ee4FB67iQ1FPzpiRL'));
}

class EmailVerification extends DBConnection {
    protected $settings;
    protected $smtp_host;
    protected $smtp_port;
    protected $smtp_username;
    protected $smtp_password;
    protected $from_name;
    private $otp_expiry_minutes = 10;
    
    public function __construct() {
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
        
        
        $this->smtp_host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtp_port = getenv('SMTP_PORT') ?: 587;
        $this->smtp_username = getenv('SMTP_USERNAME') ?: '';
        $this->smtp_password = getenv('SMTP_PASSWORD') ?: '';
        $this->from_name = getenv('SMTP_FROM_NAME') ?: 'PickupDrug';
        
        
        $this->loadSmtpSettings();
    }
    
    
    private function loadSmtpSettings() {
        
        $qry = $this->conn->query("SELECT meta_field, meta_value FROM system_info WHERE meta_field IN ('smtp_host', 'smtp_port', 'smtp_email', 'smtp_password', 'name')");
        if ($qry && $qry->num_rows > 0) {
            while ($row = $qry->fetch_assoc()) {
                
                if ($row['meta_field'] === 'smtp_host' && empty($this->smtp_host) && !empty($row['meta_value'])) {
                    $this->smtp_host = $row['meta_value'];
                }
                if ($row['meta_field'] === 'smtp_port' && empty($this->smtp_port) && !empty($row['meta_value'])) {
                    $this->smtp_port = $row['meta_value'];
                }
                if ($row['meta_field'] === 'smtp_email' && empty($this->smtp_username) && !empty($row['meta_value'])) {
                    $this->smtp_username = $row['meta_value'];
                }
                if ($row['meta_field'] === 'smtp_password' && empty($this->smtp_password) && !empty($row['meta_value'])) {
                    $this->smtp_password = $row['meta_value'];
                }
                if ($row['meta_field'] === 'name' && !empty($row['meta_value'])) {
                    $this->from_name = $row['meta_value'];
                }
            }
        }
    }
    
    
    public function sendEmail($to_email, $subject, $body_html) {
        
        if (!empty($this->smtp_username) && !empty($this->smtp_password)) {
            return $this->sendNotificationWithSmtp($to_email, '', $subject, $body_html);
        }
        
        
        return $this->sendNotificationWithPhpMail($to_email, '', $subject, $body_html);
    }
    
    
    private function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    
    private function verifyRecaptcha($token) {
        if (empty($token)) {
            return false;
        }
        
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            
            error_log("reCAPTCHA verification failed: Could not connect to Google");
            return true;
        }
        
        $response = json_decode($result, true);
        return isset($response['success']) && $response['success'] === true;
    }
    
    
    public function send_verification_code() {
        $resp = array('status' => 'failed');
        
        try {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $type = isset($_POST['type']) ? trim($_POST['type']) : 'customer'; 
            $action = isset($_POST['action']) ? trim($_POST['action']) : 'login'; 
            $recaptchaToken = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
            
            
            if (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== 'YOUR_RECAPTCHA_SECRET_KEY') {
                if (!$this->verifyRecaptcha($recaptchaToken)) {
                    $resp['status'] = 'bot';
                    $resp['msg'] = 'Security verification failed. Please try again.';
                    return json_encode($resp);
                }
            }
            
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $resp['msg'] = 'Please enter a valid email address.';
                return json_encode($resp);
            }
            
            
            if ($type === 'admin') {
                
                $resp['msg'] = 'Email verification is only available for customers. Admin users please use regular login.';
                return json_encode($resp);
            }
            
            $stmt = $this->conn->prepare("SELECT id, firstname, lastname, email FROM customer_list WHERE email = ?");
            
            if (!$stmt) {
                $resp['msg'] = 'Database error. Please try again.';
                return json_encode($resp);
            }
            
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            
            if ($action === 'login' && $result->num_rows == 0) {
                $resp['msg'] = 'Email address not found in our records.';
                return json_encode($resp);
            }
            
            
            if ($action === 'register' && $result->num_rows > 0) {
                $resp['msg'] = 'This email is already registered. Please log in instead.';
                return json_encode($resp);
            }
            
            $user = $result->fetch_assoc();
            
            
            $otp = $this->generateOTP();
            $expiry = date('Y-m-d H:i:s', strtotime("+{$this->otp_expiry_minutes} minutes"));
            
            
            $this->createOtpTable();
            
            
            $delete_stmt = $this->conn->prepare("DELETE FROM email_verification_codes WHERE email = ?");
            $delete_stmt->bind_param('s', $email);
            $delete_stmt->execute();
            
            
            $insert_stmt = $this->conn->prepare("INSERT INTO email_verification_codes (email, code, user_type, action_type, expires_at) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param('sssss', $email, $otp, $type, $action, $expiry);
            
            if (!$insert_stmt->execute()) {
                $resp['msg'] = 'Failed to generate verification code.';
                return json_encode($resp);
            }
            
            
            $username = isset($user['firstname']) ? $user['firstname'] : 'User';
            $emailSent = $this->sendOtpEmail($email, $username, $otp);
            
            if ($emailSent) {
                $resp['status'] = 'success';
                $resp['msg'] = 'Verification code sent to your email.';
                $resp['email_masked'] = $this->maskEmail($email);
                $resp['action'] = $action; 
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Email Service Provider Error: Unable to deliver verification email. Please verify your address or try again later.';
            }
            
        } catch (Exception $e) {
            $resp['msg'] = 'Error: ' . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    
    public function verify_code() {
        $resp = array('status' => 'failed');
        
        try {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';
            $type = isset($_POST['type']) ? trim($_POST['type']) : 'customer';
            
            if (empty($email) || empty($code)) {
                $resp['msg'] = 'Email and verification code are required.';
                return json_encode($resp);
            }
            
            
            $stmt = $this->conn->prepare("SELECT action_type FROM email_verification_codes WHERE email = ? AND code = ? AND user_type = ? AND expires_at > NOW()");
            if (!$stmt) {
                
                $stmt = $this->conn->prepare("SELECT 'login' as action_type FROM email_verification_codes WHERE email = ? AND code = ? AND user_type = ? AND expires_at > NOW()");
            }
            $stmt->bind_param('sss', $email, $code, $type);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $resp['msg'] = 'Invalid or expired verification code.';
                return json_encode($resp);
            }
            
            $verification_data = $result->fetch_assoc();
            $action_type = isset($verification_data['action_type']) ? $verification_data['action_type'] : 'login';
            
            
            $delete_stmt = $this->conn->prepare("DELETE FROM email_verification_codes WHERE email = ?");
            $delete_stmt->bind_param('s', $email);
            $delete_stmt->execute();
            
            
            if ($action_type === 'register') {
                
                $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : explode('@', $email)[0];
                $lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
                
                
                $password = isset($_POST['password']) ? trim($_POST['password']) : bin2hex(random_bytes(6));
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                
                $check_stmt = $this->conn->prepare("SELECT id FROM customer_list WHERE email = ?");
                $check_stmt->bind_param('s', $email);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $resp['msg'] = 'This email is already registered. Please log in instead.';
                    return json_encode($resp);
                }
                
                
                $insert_stmt = $this->conn->prepare("INSERT INTO customer_list (firstname, lastname, email, password, date_created, status) VALUES (?, ?, ?, ?, NOW(), 1)");
                if (!$insert_stmt) {
                    $resp['msg'] = 'Database error during account creation.';
                    return json_encode($resp);
                }
                
                $insert_stmt->bind_param('ssss', $firstname, $lastname, $email, $hashed_password);
                if (!$insert_stmt->execute()) {
                    $resp['msg'] = 'Failed to create account. ' . $this->conn->error;
                    return json_encode($resp);
                }
                
                
                $user_stmt = $this->conn->prepare("SELECT * FROM customer_list WHERE email = ?");
                $user_stmt->bind_param('s', $email);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();
            } else {
                
                $user_stmt = $this->conn->prepare("SELECT * FROM customer_list WHERE email = ?");
                $user_stmt->bind_param('s', $email);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                
                if ($user_result->num_rows == 0) {
                    $resp['msg'] = 'User not found.';
                    return json_encode($resp);
                }
                
                $user = $user_result->fetch_assoc();
            }
            
            
            if (isset($user) && !empty($user)) {
                
                foreach ($user as $k => $v) {
                    if (!is_numeric($k) && $k !== 'password') {
                        $this->settings->set_userdata($k, $v);
                    }
                }
                
                
                $this->settings->set_userdata('login_type', 2);
                
                
                session_regenerate_id(true);
                
                $resp['status'] = 'success';
                $resp['msg'] = ($action_type === 'register' ? 'Account created and ' : '') . 'Verification successful!';
                $resp['redirect'] = '?p=customer/home';
            } else {
                $resp['msg'] = 'Login failed. Please try again.';
            }
            
        } catch (Exception $e) {
            $resp['msg'] = 'Error: ' . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    
    public function resend_code() {
        return $this->send_verification_code();
    }
    
    
    private function createOtpTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `email_verification_codes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(255) NOT NULL,
            `code` VARCHAR(10) NOT NULL,
            `user_type` VARCHAR(20) DEFAULT 'customer',
            `action_type` VARCHAR(20) DEFAULT 'login',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `expires_at` DATETIME NOT NULL,
            INDEX `idx_email` (`email`),
            INDEX `idx_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
        
        
        $check_col = $this->conn->query("SHOW COLUMNS FROM email_verification_codes LIKE 'action_type'");
        if ($check_col && $check_col->num_rows == 0) {
            $this->conn->query("ALTER TABLE email_verification_codes ADD COLUMN `action_type` VARCHAR(20) DEFAULT 'login'");
        }
    }
    
    
    private function sendOtpEmail($to_email, $name, $otp) {
        
        if (empty($this->smtp_username) || empty($this->smtp_password)) {
            
            return $this->sendOtpWithPhpMail($to_email, $name, $otp);
        }
        
        
        return $this->sendOtpWithSmtp($to_email, $name, $otp);
    }
    
    
    private function sendOtpWithSmtp($to_email, $name, $otp) {
        try {
            
            $subject = "{$this->from_name} - Your Verification Code";
            $body = $this->getEmailTemplate($name, $otp);
            
            
            $socket = @fsockopen('ssl://' . $this->smtp_host, 465, $errno, $errstr, 30);
            
            if (!$socket) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return $this->sendOtpWithPhpMail($to_email, $name, $otp);
            }
            
            
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "EHLO localhost\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "AUTH LOGIN\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($this->smtp_username) . "\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($this->smtp_password) . "\r\n");
            $response = $this->smtpRead($socket);
            
            if (strpos($response, '235') === false) {
                fclose($socket);
                error_log("SMTP Auth failed: $response");
                return $this->sendOtpWithPhpMail($to_email, $name, $otp);
            }
            
            
            $this->smtpSend($socket, "MAIL FROM:<{$this->smtp_username}>\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "RCPT TO:<{$to_email}>\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "DATA\r\n");
            $this->smtpRead($socket);
            
            
            $headers = "From: {$this->from_name} <{$this->smtp_username}>\r\n";
            $headers .= "To: {$name} <{$to_email}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $this->smtpSend($socket, $headers . $body . "\r\n.\r\n");
            $response = $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "QUIT\r\n");
            fclose($socket);
            
            return strpos($response, '250') !== false;
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return $this->sendOtpWithPhpMail($to_email, $name, $otp);
        }
    }
    
    protected function smtpSend($socket, $data) {
        fwrite($socket, $data);
    }
    
    protected function smtpRead($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
    
    
    private function sendOtpWithPhpMail($to_email, $name, $otp) {
        $subject = "{$this->from_name} - Your Verification Code";
        $body = $this->getEmailTemplate($name, $otp);
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->from_name} <noreply@pickupdrug.com>\r\n";
        
        return @mail($to_email, $subject, $body, $headers);
    }
    
    
    private function getEmailTemplate($name, $otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-box { background: white; border: 2px dashed #dc3545; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
                .otp-code { font-size: 36px; font-weight: bold; color: #dc3545; letter-spacing: 8px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 5px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0;'>🔐 Verification Code</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$name}</strong>,</p>
                    <p>You requested a verification code to sign in to your {$this->from_name} account.</p>
                    
                    <div class='otp-box'>
                        <p style='margin:0 0 10px 0;'>Your verification code is:</p>
                        <div class='otp-code'>{$otp}</div>
                    </div>
                    
                    <p>This code will expire in <strong>{$this->otp_expiry_minutes} minutes</strong>.</p>
                    
                    <div class='warning'>
                        ⚠️ <strong>Security Notice:</strong> Never share this code with anyone. {$this->from_name} staff will never ask for your verification code.
                    </div>
                </div>
                <div class='footer'>
                    <p>If you didn't request this code, please ignore this email or contact support.</p>
                    <p>&copy; " . date('Y') . " {$this->from_name}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    
    private function maskEmail($email) {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];
        
        $masked_name = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 3));
        
        return $masked_name . '@' . $domain;
    }
    
    
    public function test_smtp() {
        $resp = array('status' => 'failed');
        
        try {
            $test_email = isset($_POST['smtp_email']) ? trim($_POST['smtp_email']) : '';
            $test_password = isset($_POST['smtp_password']) ? trim($_POST['smtp_password']) : '';
            
            if (empty($test_email) || empty($test_password)) {
                $resp['msg'] = 'Email and password are required.';
                return json_encode($resp);
            }
            
            
            $socket = @fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 10);
            
            if (!$socket) {
                $resp['msg'] = "Cannot connect to Gmail SMTP: $errstr";
                return json_encode($resp);
            }
            
            
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "EHLO localhost\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "AUTH LOGIN\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($test_email) . "\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($test_password) . "\r\n");
            $response = $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "QUIT\r\n");
            fclose($socket);
            
            if (strpos($response, '235') !== false) {
                $resp['status'] = 'success';
                $resp['msg'] = 'SMTP connection successful! Email settings are working.';
            } else {
                $resp['msg'] = 'Authentication failed. Check your App Password.';
            }
            
        } catch (Exception $e) {
            $resp['msg'] = 'Error: ' . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    
    public function sendNotificationEmail($to_email, $to_name, $subject, $body_html) {
        
        if (empty($this->smtp_username) || empty($this->smtp_password)) {
            
            return $this->sendNotificationWithPhpMail($to_email, $to_name, $subject, $body_html);
        }
        
        return $this->sendNotificationWithSmtp($to_email, $to_name, $subject, $body_html);
    }
    
    
    private function sendNotificationWithSmtp($to_email, $to_name, $subject, $body_html) {
        try {
            
            $socket = @fsockopen('ssl://' . $this->smtp_host, 465, $errno, $errstr, 30);
            
            if (!$socket) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return $this->sendNotificationWithPhpMail($to_email, $to_name, $subject, $body_html);
            }
            
            
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "EHLO localhost\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "AUTH LOGIN\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($this->smtp_username) . "\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($this->smtp_password) . "\r\n");
            $response = $this->smtpRead($socket);
            
            if (strpos($response, '235') === false) {
                fclose($socket);
                error_log("SMTP Auth failed: $response");
                return $this->sendNotificationWithPhpMail($to_email, $to_name, $subject, $body_html);
            }
            
            
            $this->smtpSend($socket, "MAIL FROM:<{$this->smtp_username}>\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "RCPT TO:<{$to_email}>\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "DATA\r\n");
            $this->smtpRead($socket);
            
            
            $headers = "From: {$this->from_name} <{$this->smtp_username}>\r\n";
            $headers .= "To: {$to_name} <{$to_email}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $this->smtpSend($socket, $headers . $body_html . "\r\n.\r\n");
            $response = $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "QUIT\r\n");
            fclose($socket);
            
            return strpos($response, '250') !== false;
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return $this->sendNotificationWithPhpMail($to_email, $to_name, $subject, $body_html);
        }
    }
    
    
    private function sendNotificationWithPhpMail($to_email, $to_name, $subject, $body_html) {
        $headers = "From: {$this->from_name} <noreply@pickupdrug.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return @mail($to_email, $subject, $body_html, $headers);
    }
    
    
    public function getPrescriptionApprovalTemplate($customer_name, $medications = []) {
        $system_name = $this->from_name;
        
        $med_list_html = '';
        if (!empty($medications)) {
            $med_list_html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
            $med_list_html .= '<tr style="background: #0d9488; color: white;"><th style="padding: 10px; text-align: left;">Medicine</th><th style="padding: 10px; text-align: left;">Dosage</th><th style="padding: 10px; text-align: left;">Schedule</th></tr>';
            foreach ($medications as $med) {
                $med_list_html .= '<tr style="border-bottom: 1px solid #e5e7eb;">';
                $med_list_html .= '<td style="padding: 10px;">' . htmlspecialchars($med['medicine_name'] ?? '') . '</td>';
                $med_list_html .= '<td style="padding: 10px;">' . htmlspecialchars($med['dosage'] ?? '') . '</td>';
                $med_list_html .= '<td style="padding: 10px;">' . htmlspecialchars($med['times'] ?? '') . ' for ' . ($med['duration_days'] ?? '7') . ' days</td>';
                $med_list_html .= '</tr>';
            }
            $med_list_html .= '</table>';
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #0d9488, #14b8a6); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">✅ Prescription Approved</h1>
                </div>
                <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <p style="color: #374151; font-size: 16px;">Dear <strong>' . htmlspecialchars($customer_name) . '</strong>,</p>
                    <p style="color: #374151; font-size: 16px;">Great news! Your prescription has been <strong style="color: #0d9488;">approved</strong> by our pharmacy team.</p>
                    ' . ($med_list_html ? '<h3 style="color: #0d9488; margin-top: 25px;">📋 Your Medication Schedule</h3>' . $med_list_html : '') . '
                    <div style="background: #f0fdfa; border-left: 4px solid #0d9488; padding: 15px; margin: 20px 0;">
                        <p style="margin: 0; color: #0d9488; font-weight: bold;">💊 What\'s Next?</p>
                        <ul style="color: #374151; margin: 10px 0 0 0; padding-left: 20px;">
                            <li>You can now order your prescribed medications</li>
                            <li>Visit your nearest branch for pickup</li>
                            <li>You\'ll receive SMS reminders for your medication schedule</li>
                        </ul>
                    </div>
                    <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">Thank you for choosing ' . htmlspecialchars($system_name) . '. Stay healthy!</p>
                </div>
                <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;">
                    <p>' . htmlspecialchars($system_name) . ' - Your Trusted Healthcare Partner</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    
    public function getPrescriptionRejectionTemplate($customer_name, $reason = '') {
        $system_name = $this->from_name;
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #dc2626, #ef4444); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">⚠️ Prescription Status Update</h1>
                </div>
                <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <p style="color: #374151; font-size: 16px;">Dear <strong>' . htmlspecialchars($customer_name) . '</strong>,</p>
                    <p style="color: #374151; font-size: 16px;">We regret to inform you that your prescription could not be approved at this time.</p>
                    ' . (!empty($reason) ? '
                    <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0;">
                        <p style="margin: 0; color: #dc2626; font-weight: bold;">📝 Reason:</p>
                        <p style="color: #374151; margin: 10px 0 0 0;">' . htmlspecialchars($reason) . '</p>
                    </div>' : '') . '
                    <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                        <p style="margin: 0; color: #92400e; font-weight: bold;">💡 What You Can Do:</p>
                        <ul style="color: #374151; margin: 10px 0 0 0; padding-left: 20px;">
                            <li>Upload a clearer image of your prescription</li>
                            <li>Ensure the prescription is valid and not expired</li>
                            <li>Contact our support team for assistance</li>
                        </ul>
                    </div>
                    <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">If you have any questions, please don\'t hesitate to contact us.</p>
                </div>
                <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;">
                    <p>' . htmlspecialchars($system_name) . ' - Your Trusted Healthcare Partner</p>
                </div>
            </div>
        </body>
        </html>';
    }
}

if (isset($_GET['f']) && basename($_SERVER['SCRIPT_FILENAME']) === 'EmailVerification.php') {
    header('Content-Type: application/json');
    $emailVerification = new EmailVerification();
    $f = $_GET['f'];
    
    switch ($f) {
        case 'send_code':
            echo $emailVerification->send_verification_code();
            break;
        case 'verify_code':
            echo $emailVerification->verify_code();
            break;
        case 'resend_code':
            echo $emailVerification->resend_code();
            break;
        case 'test_smtp':
            echo $emailVerification->test_smtp();
            break;
        default:
            echo json_encode(['status' => 'failed', 'msg' => 'Invalid action']);
    }
    exit;
}
