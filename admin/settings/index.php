<?php

require_once(__DIR__ . '/../../config.php');

if(!isset($_SESSION['userdata']['id']) || $_SESSION['userdata']['type'] != 1){
    header("Location: ../login.php");
    exit;
}

$page_title = "Settings Center";

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'api';

function mask_sensitive($value, $show_chars = 4) {
    if(empty($value)) return '';
    $length = strlen($value);
    if($length <= $show_chars * 2) {
        return str_repeat('•', $length);
    }
    return substr($value, 0, $show_chars) . str_repeat('•', $length - ($show_chars * 2)) . substr($value, -$show_chars);
}

$settings_list = [
    
    'gemini_api_key', 
    
    'smtp_host', 'smtp_port', 'smtp_email', 'smtp_password', 'smtp_encryption', 'smtp_from_name',
    
    'sms_gateway_type', 'sms_enabled',
    'semaphore_api_key', 'semaphore_sender_name',
    'twilio_account_sid', 'twilio_auth_token', 'twilio_phone_number',
    
    'smsgate_local_url', 'smsgate_local_username', 'smsgate_local_password',
    'smsgate_public_url', 'smsgate_cloud_url', 'smsgate_cloud_token', 'smsgate_device_id',
    
    'httpsms_api_url', 'httpsms_username', 'httpsms_password',
    
    'reminder_enabled', 'reminder_sms_enabled', 'reminder_email_enabled',
    'reminder_advance_hours', 'reminder_daily_limit', 'reminder_cron_interval',
    
    'email_verification_enabled', 'otp_expiry_minutes', 'otp_length', 'max_otp_attempts',
    
    'facebook_page_access_token', 'facebook_verify_token', 'facebook_app_secret',
    
    'api_encryption_key'
];

$current_settings = [];
foreach($settings_list as $field) {
    $current_settings[$field] = $_settings->info($field) ?? '';
}
?>

<style>
.settings-nav .nav-link {
    border-radius: 0;
    color: #495057;
    border: none;
    padding: 15px 20px;
    font-weight: 500;
}
.settings-nav .nav-link.active {
    background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
    color: white;
}
.settings-nav .nav-link:hover:not(.active) {
    background: #f8f9fa;
}
.settings-nav .nav-link i {
    margin-right: 8px;
    width: 20px;
}
.settings-card {
    border: none;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    border-radius: 10px;
}
.settings-card .card-header {
    border-radius: 10px 10px 0 0;
    padding: 15px 20px;
}
.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.form-section h6 {
    color: #0d9488;
    margin-bottom: 15px;
    font-weight: 600;
}
.security-badge {
    display: inline-flex;
    align-items: center;
    background: #d1fae5;
    color: #059669;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.security-badge i {
    margin-right: 5px;
}
.test-result {
    padding: 10px;
    border-radius: 5px;
    margin-top: 10px;
    display: none;
}
.test-result.success {
    background: #d1fae5;
    color: #059669;
}
.test-result.error {
    background: #fee2e2;
    color: #dc2626;
}
.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}
.status-indicator.active { background: #10b981; }
.status-indicator.inactive { background: #ef4444; }
.status-indicator.warning { background: #f59e0b; }
.input-group-text.toggle-password {
    cursor: pointer;
}
.masked-value {
    font-family: monospace;
    color: #6b7280;
}
</style>

<div class="container-fluid">
    <div class="row">
        
        <div class="col-md-3">
            <div class="card settings-card">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs"></i> Settings Center</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="nav flex-column settings-nav">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'api' ? 'active' : ''; ?>" href="?page=settings&tab=api">
                                <i class="fas fa-key"></i> API Keys
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'smtp' ? 'active' : ''; ?>" href="?page=settings&tab=smtp">
                                <i class="fas fa-envelope"></i> SMTP / Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'sms' ? 'active' : ''; ?>" href="?page=settings&tab=sms">
                                <i class="fas fa-sms"></i> SMS Gateway
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'reminders' ? 'active' : ''; ?>" href="?page=settings&tab=reminders">
                                <i class="fas fa-bell"></i> Medication Reminders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'email_verification' ? 'active' : ''; ?>" href="?page=settings&tab=email_verification">
                                <i class="fas fa-user-check"></i> Email Verification
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'security' ? 'active' : ''; ?>" href="?page=settings&tab=security">
                                <i class="fas fa-shield-alt"></i> Security
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            
            <div class="card settings-card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-heartbeat"></i> Service Status</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <span class="status-indicator <?php echo !empty($current_settings['gemini_api_key']) ? 'active' : 'inactive'; ?>"></span>
                            Gemini AI
                        </li>
                        <li class="mb-2">
                            <span class="status-indicator <?php echo !empty($current_settings['smtp_email']) ? 'active' : 'inactive'; ?>"></span>
                            SMTP Email
                        </li>
                        <li class="mb-2">
                            <span class="status-indicator <?php echo !empty($current_settings['semaphore_api_key']) || !empty($current_settings['twilio_account_sid']) ? 'active' : 'inactive'; ?>"></span>
                            SMS Gateway
                        </li>
                        <li class="mb-2">
                            <span class="status-indicator <?php echo ($current_settings['reminder_enabled'] ?? '0') == '1' ? 'active' : 'inactive'; ?>"></span>
                            Reminders
                        </li>
                        <li class="mb-0">
                            <span class="status-indicator <?php echo ($current_settings['email_verification_enabled'] ?? '1') == '1' ? 'active' : 'inactive'; ?>"></span>
                            Email Verification
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        
        <div class="col-md-9">
            <form id="settings-form" autocomplete="off">
                <?php 
                
                require_once(__DIR__ . '/../../app/helpers/SecurityHelper.php');
                echo SecurityHelper::csrfField();
                ?>
                <input type="hidden" name="settings_tab" value="<?php echo $active_tab; ?>">
                
                <?php if($active_tab == 'api'): ?>
                
                <div class="card settings-card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-key"></i> API Keys Configuration
                            <span class="security-badge float-right"><i class="fas fa-lock"></i> Encrypted Storage</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Security:</strong> All API keys are encrypted using AES-256 before storing in database.
                        </div>
                        
                        
                        <div class="form-section">
                            <h6><i class="fas fa-robot"></i> Google Gemini AI (Chatbot)</h6>
                            <div class="form-group">
                                <label>API Key <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" 
                                           name="gemini_api_key" 
                                           id="gemini_api_key"
                                           class="form-control" 
                                           placeholder="AIzaSy..."
                                           value="<?php echo $current_settings['gemini_api_key']; ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text toggle-password" data-target="gemini_api_key">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-external-link-alt"></i> Get free API key: 
                                    <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
                                </small>
                            </div>
                            <button type="button" class="btn btn-info btn-sm" onclick="testAPI('gemini')">
                                <i class="fas fa-flask"></i> Test Connection
                            </button>
                            <div id="gemini-result" class="test-result"></div>
                        </div>
                    </div>
                </div>
                
                <?php elseif($active_tab == 'smtp'): ?>
                
                <div class="card settings-card">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope"></i> SMTP / Email Configuration
                            <span class="security-badge float-right"><i class="fas fa-lock"></i> Password Encrypted</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Gmail Users:</strong> You MUST use an <strong>App Password</strong>, not your regular Gmail password!
                            <br><small>
                                1. Enable 2FA: <a href="https://myaccount.google.com/security" target="_blank">Google Security</a>
                                2. Create App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a>
                            </small>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-server"></i> SMTP Server Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>SMTP Host <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               name="smtp_host" 
                                               class="form-control" 
                                               placeholder="smtp.gmail.com"
                                               value="<?php echo $current_settings['smtp_host'] ?: 'smtp.gmail.com'; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Port <span class="text-danger">*</span></label>
                                        <select name="smtp_port" class="form-control">
                                            <option value="587" <?php echo ($current_settings['smtp_port'] ?? '587') == '587' ? 'selected' : ''; ?>>587 (TLS)</option>
                                            <option value="465" <?php echo ($current_settings['smtp_port'] ?? '') == '465' ? 'selected' : ''; ?>>465 (SSL)</option>
                                            <option value="25" <?php echo ($current_settings['smtp_port'] ?? '') == '25' ? 'selected' : ''; ?>>25 (No encryption)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Encryption</label>
                                        <select name="smtp_encryption" class="form-control">
                                            <option value="tls" <?php echo ($current_settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo ($current_settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="" <?php echo ($current_settings['smtp_encryption'] ?? '') == '' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-user-lock"></i> Authentication</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Address <span class="text-danger">*</span></label>
                                        <input type="email" 
                                               name="smtp_email" 
                                               class="form-control" 
                                               placeholder="your-pharmacy@gmail.com"
                                               value="<?php echo $current_settings['smtp_email']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>App Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   name="smtp_password" 
                                                   id="smtp_password"
                                                   class="form-control" 
                                                   placeholder="xxxx xxxx xxxx xxxx"
                                                   value="<?php echo $current_settings['smtp_password']; ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="smtp_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <small class="text-muted">16-character Gmail App Password</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-id-card"></i> Sender Information</h6>
                            <div class="form-group">
                                <label>From Name</label>
                                <input type="text" 
                                       name="smtp_from_name" 
                                       class="form-control" 
                                       placeholder="PickupDrug Pharmacy"
                                       value="<?php echo $current_settings['smtp_from_name'] ?: ($_settings->info('name') ?? 'PickupDrug'); ?>">
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-info" onclick="testAPI('smtp')">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                        <div id="smtp-result" class="test-result"></div>
                    </div>
                </div>
                
                <?php elseif($active_tab == 'sms'): ?>
                
                <div class="card settings-card">
                    <div class="card-header bg-gradient-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-sms"></i> SMS Gateway Configuration
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <strong>Important:</strong> Self-hosted SMS (HttpSMS) will NOT work on free hosting! 
                            Use <strong>Semaphore</strong> (Philippines) or <strong>Twilio</strong> (Global) instead.
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-toggle-on"></i> SMS Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Enable SMS Notifications</label>
                                        <select name="sms_enabled" class="form-control">
                                            <option value="1" <?php echo ($current_settings['sms_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                            <option value="0" <?php echo ($current_settings['sms_enabled'] ?? '') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gateway Provider <span class="text-danger">*</span></label>
                                        <select name="sms_gateway_type" id="sms_gateway_type" class="form-control" onchange="toggleSMSFields()">
                                            <option value="semaphore" <?php echo ($current_settings['sms_gateway_type'] ?? 'semaphore') == 'semaphore' ? 'selected' : ''; ?>>
                                                Semaphore (Philippines - Recommended)
                                            </option>
                                            <option value="twilio" <?php echo ($current_settings['sms_gateway_type'] ?? '') == 'twilio' ? 'selected' : ''; ?>>
                                                Twilio (Global)
                                            </option>
                                            <option value="httpsms" <?php echo ($current_settings['sms_gateway_type'] ?? '') == 'httpsms' ? 'selected' : ''; ?>>
                                                HttpSMS (Self-hosted - Local Only)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div id="semaphore-settings" class="form-section gateway-section">
                            <h6><img src="https://semaphore.co/favicon.ico" width="20" class="mr-2"> Semaphore Settings</h6>
                            <p class="text-muted small">
                                Sign up at <a href="https://semaphore.co/" target="_blank">semaphore.co</a> - 
                                Affordable SMS for Philippines (₱0.50-1.00/SMS)
                            </p>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>API Key <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   name="semaphore_api_key" 
                                                   id="semaphore_api_key"
                                                   class="form-control"
                                                   value="<?php echo $current_settings['semaphore_api_key']; ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="semaphore_api_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Sender Name</label>
                                        <input type="text" 
                                               name="semaphore_sender_name" 
                                               class="form-control" 
                                               placeholder="PickupDrug"
                                               maxlength="11"
                                               value="<?php echo $current_settings['semaphore_sender_name'] ?: 'PickupDrug'; ?>">
                                        <small class="text-muted">Max 11 characters</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div id="twilio-settings" class="form-section gateway-section" style="display:none;">
                            <h6><img src="https://www.twilio.com/assets/icons/twilio-icon-512.png" width="20" class="mr-2"> Twilio Settings</h6>
                            <p class="text-muted small">
                                Sign up at <a href="https://www.twilio.com/try-twilio" target="_blank">twilio.com</a> - 
                                Global SMS provider ($0.0075/SMS)
                            </p>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Account SID <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               name="twilio_account_sid" 
                                               class="form-control" 
                                               placeholder="AC..."
                                               value="<?php echo $current_settings['twilio_account_sid']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Auth Token <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   name="twilio_auth_token" 
                                                   id="twilio_auth_token"
                                                   class="form-control"
                                                   value="<?php echo $current_settings['twilio_auth_token']; ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="twilio_auth_token">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Phone Number <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="twilio_phone_number" 
                                       class="form-control" 
                                       placeholder="+1234567890"
                                       value="<?php echo $current_settings['twilio_phone_number']; ?>">
                            </div>
                        </div>
                        
                        
                        <div id="httpsms-settings" class="form-section gateway-section" style="display:none;">
                            <h6><i class="fas fa-mobile-alt"></i> SMS Gate Settings (Self-hosted Android)</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>SMS Gate App:</strong> Uses your Android phone to send SMS.
                                <br><small>System tries: <strong>Local → Public → Cloud</strong> (auto-fallback when phone offline)</small>
                            </div>
                            
                            
                            <div class="form-section" style="background: #e8f5e9;">
                                <h6><i class="fas fa-home"></i> Local Server (Same Network)</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Local URL</label>
                                            <input type="text" 
                                                   name="smsgate_local_url" 
                                                   class="form-control" 
                                                   placeholder="http://192.168.137.46:8080"
                                                   value="<?php echo $current_settings['smsgate_local_url'] ?? $current_settings['httpsms_api_url'] ?? ''; ?>">
                                            <small class="text-muted">From SMS Gate app → Local address</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Username</label>
                                            <input type="text" 
                                                   name="smsgate_local_username" 
                                                   class="form-control"
                                                   placeholder="sms"
                                                   value="<?php echo $current_settings['smsgate_local_username'] ?? $current_settings['httpsms_username'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Password</label>
                                            <input type="password" 
                                                   name="smsgate_local_password" 
                                                   class="form-control"
                                                   placeholder="sq_5wZZt"
                                                   value="<?php echo $current_settings['smsgate_local_password'] ?? $current_settings['httpsms_password'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="form-section" style="background: #fff3e0;">
                                <h6><i class="fas fa-globe"></i> Public Server (Phone's Public IP)</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Public URL</label>
                                            <input type="text" 
                                                   name="smsgate_public_url" 
                                                   class="form-control" 
                                                   placeholder="http://61.245.13.109:8080"
                                                   value="<?php echo $current_settings['smsgate_public_url'] ?? ''; ?>">
                                            <small class="text-muted">From SMS Gate app → Public address (requires port forwarding)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="form-section" style="background: #e3f2fd;">
                                <h6><i class="fas fa-cloud"></i> Cloud Server (Works Anywhere - Queues when offline)</h6>
                                <div class="alert alert-success small mb-3">
                                    <i class="fas fa-check-circle"></i> <strong>Recommended!</strong> Messages are queued when phone is offline and sent when it reconnects.
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Cloud API URL</label>
                                            <input type="text" 
                                                   name="smsgate_cloud_url" 
                                                   class="form-control"
                                                   placeholder="https://api.sms-gate.app/mobile/v1"
                                                   value="<?php echo $current_settings['smsgate_cloud_url'] ?? 'https://api.sms-gate.app/mobile/v1'; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Private Token <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       name="smsgate_cloud_token" 
                                                       id="smsgate_cloud_token"
                                                       class="form-control"
                                                       placeholder="Your private token from app SETTINGS"
                                                       value="<?php echo $current_settings['smsgate_cloud_token'] ?? ''; ?>">
                                                <div class="input-group-append">
                                                    <span class="input-group-text toggle-password" data-target="smsgate_cloud_token">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <small class="text-muted">From SMS Gate app → Settings → Private Token</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Device ID</label>
                                            <input type="text" 
                                                   name="smsgate_device_id" 
                                                   class="form-control"
                                                   placeholder="vQdWQABYkxt8FfZm2LcHI"
                                                   value="<?php echo $current_settings['smsgate_device_id'] ?? ''; ?>">
                                            <small class="text-muted">From SMS Gate app → Settings → Device ID</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button" class="btn btn-info btn-sm" onclick="testSMSGate()">
                                    <i class="fas fa-vial"></i> Check Server Status
                                </button>
                                <a href="<?php echo base_url; ?>test_smsgate.php" target="_blank" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Open Full Test Page
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6><i class="fas fa-paper-plane"></i> Send Test SMS</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" id="test_phone_number" class="form-control" placeholder="09171234567 or +639171234567">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-success" onclick="sendTestSMS()">
                                                <i class="fas fa-sms"></i> Send Test
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Enter phone number to test SMS delivery</small>
                                </div>
                            </div>
                        </div>
                        
                        <div id="sms-result" class="test-result mt-3" style="display:none;"></div>
                    </div>
                </div>
                
                <?php elseif($active_tab == 'reminders'): ?>
                
                <div class="card settings-card">
                    <div class="card-header bg-gradient-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bell"></i> Medication Reminder Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Medication reminders help customers remember to take their medicines on time via SMS and/or Email.
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-toggle-on"></i> General Settings</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Enable Reminders</label>
                                        <select name="reminder_enabled" class="form-control">
                                            <option value="1" <?php echo ($current_settings['reminder_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                            <option value="0" <?php echo ($current_settings['reminder_enabled'] ?? '') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>SMS Reminders</label>
                                        <select name="reminder_sms_enabled" class="form-control">
                                            <option value="1" <?php echo ($current_settings['reminder_sms_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                            <option value="0" <?php echo ($current_settings['reminder_sms_enabled'] ?? '') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Email Reminders</label>
                                        <select name="reminder_email_enabled" class="form-control">
                                            <option value="1" <?php echo ($current_settings['reminder_email_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                            <option value="0" <?php echo ($current_settings['reminder_email_enabled'] ?? '') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-clock"></i> Timing Settings</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Advance Notice (Hours)</label>
                                        <input type="number" 
                                               name="reminder_advance_hours" 
                                               class="form-control" 
                                               min="0" max="24"
                                               value="<?php echo $current_settings['reminder_advance_hours'] ?: '0'; ?>">
                                        <small class="text-muted">Hours before scheduled time</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Daily SMS Limit</label>
                                        <input type="number" 
                                               name="reminder_daily_limit" 
                                               class="form-control" 
                                               min="1" max="1000"
                                               value="<?php echo $current_settings['reminder_daily_limit'] ?: '100'; ?>">
                                        <small class="text-muted">Max reminders per day</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Cron Interval (Minutes)</label>
                                        <select name="reminder_cron_interval" class="form-control">
                                            <option value="5" <?php echo ($current_settings['reminder_cron_interval'] ?? '15') == '5' ? 'selected' : ''; ?>>Every 5 minutes</option>
                                            <option value="15" <?php echo ($current_settings['reminder_cron_interval'] ?? '15') == '15' ? 'selected' : ''; ?>>Every 15 minutes</option>
                                            <option value="30" <?php echo ($current_settings['reminder_cron_interval'] ?? '') == '30' ? 'selected' : ''; ?>>Every 30 minutes</option>
                                            <option value="60" <?php echo ($current_settings['reminder_cron_interval'] ?? '') == '60' ? 'selected' : ''; ?>>Every hour</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-terminal"></i> Cron Job Setup</h6>
                            <p class="text-muted">Add this cron job to your server to send reminders automatically:</p>
                            <div class="bg-dark text-light p-3 rounded">
                                <code>*/15 * * * * php <?php echo base_app; ?>cron_medication_reminders.php >> <?php echo base_app; ?>logs/cron.log 2>&1</code>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="testAPI('reminder')">
                                <i class="fas fa-play"></i> Run Cron Manually
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php elseif($active_tab == 'email_verification'): ?>
                
                <div class="card settings-card">
                    <div class="card-header bg-gradient-purple text-white" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);">
                        <h5 class="mb-0">
                            <i class="fas fa-user-check"></i> Email Verification Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Email verification sends OTP codes to customers during registration and password reset.
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-toggle-on"></i> Verification Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Verification</label>
                                        <select name="email_verification_enabled" class="form-control">
                                            <option value="1" <?php echo ($current_settings['email_verification_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Required</option>
                                            <option value="0" <?php echo ($current_settings['email_verification_enabled'] ?? '') == '0' ? 'selected' : ''; ?>>Disabled (Not recommended)</option>
                                        </select>
                                        <small class="text-muted">Require email verification for new customers</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-key"></i> OTP Settings</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>OTP Length</label>
                                        <select name="otp_length" class="form-control">
                                            <option value="4" <?php echo ($current_settings['otp_length'] ?? '6') == '4' ? 'selected' : ''; ?>>4 digits</option>
                                            <option value="6" <?php echo ($current_settings['otp_length'] ?? '6') == '6' ? 'selected' : ''; ?>>6 digits (Recommended)</option>
                                            <option value="8" <?php echo ($current_settings['otp_length'] ?? '') == '8' ? 'selected' : ''; ?>>8 digits</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>OTP Expiry (Minutes)</label>
                                        <input type="number" 
                                               name="otp_expiry_minutes" 
                                               class="form-control" 
                                               min="1" max="60"
                                               value="<?php echo $current_settings['otp_expiry_minutes'] ?: '10'; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Max Attempts</label>
                                        <input type="number" 
                                               name="max_otp_attempts" 
                                               class="form-control" 
                                               min="1" max="10"
                                               value="<?php echo $current_settings['max_otp_attempts'] ?: '3'; ?>">
                                        <small class="text-muted">Before lockout</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-info" onclick="testAPI('email_verification')">
                            <i class="fas fa-paper-plane"></i> Send Test OTP
                        </button>
                        <div id="email_verification-result" class="test-result"></div>
                    </div>
                </div>
                
                <?php elseif($active_tab == 'security'): ?>
                
                <div class="card settings-card">
                    <div class="card-header bg-gradient-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt"></i> Security Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Security Features Enabled:</strong>
                            <ul class="mb-0 mt-2">
                                <li>All API keys encrypted with AES-256-CBC</li>
                                <li>Passwords hashed with bcrypt</li>
                                <li>CSRF protection enabled</li>
                                <li>SQL injection prevention (prepared statements)</li>
                                <li>XSS protection (input sanitization)</li>
                            </ul>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-key"></i> Encryption Key</h6>
                            <p class="text-muted">The encryption key is used to encrypt sensitive data like API keys before storing in database.</p>
                            <div class="form-group">
                                <label>Current Encryption Key</label>
                                <div class="input-group">
                                    <input type="password" 
                                           name="api_encryption_key" 
                                           id="api_encryption_key"
                                           class="form-control" 
                                           value="<?php echo $current_settings['api_encryption_key'] ?: bin2hex(random_bytes(16)); ?>"
                                           readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text toggle-password" data-target="api_encryption_key">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                        <button type="button" class="btn btn-warning" onclick="regenerateKey()">
                                            <i class="fas fa-sync"></i> Regenerate
                                        </button>
                                    </div>
                                </div>
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Warning: Regenerating the key will invalidate all saved API keys!
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6><i class="fas fa-history"></i> Security Log</h6>
                            <p class="text-muted">Recent security-related events:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>IP Address</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        
                                        $table_check = $conn->query("SHOW TABLES LIKE 'security_logs'");
                                        $logs = null;
                                        if($table_check && $table_check->num_rows > 0) {
                                            $logs = $conn->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 10");
                                        }
                                        if($logs && $logs->num_rows > 0):
                                            while($log = $logs->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['event']); ?></td>
                                            <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                            <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No security events logged</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    <a href="<?php echo base_url; ?>API_SETUP_GUIDE.md" target="_blank" class="btn btn-outline-info btn-lg">
                        <i class="fas fa-book"></i> Setup Guide
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

$(document).on('click', '.toggle-password', function() {
    const target = $(this).data('target');
    const input = $('#' + target);
    const icon = $(this).find('i');
    
    if(input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

function toggleSMSFields() {
    const type = $('#sms_gateway_type').val();
    $('.gateway-section').hide();
    $('#' + type + '-settings').show();
    
    
    if(type === 'httpsms') {
        toggleSMSGateServer();
    }
}

function toggleSMSGateServer() {
    const serverType = $('#smsgate_server_type').val();
    if(serverType === 'cloud') {
        $('#smsgate-cloud').show();
        $('#smsgate-local').hide();
    } else {
        $('#smsgate-cloud').hide();
        $('#smsgate-local').show();
    }
}

$(document).ready(function() {
    toggleSMSFields();
});

$('#settings-form').submit(function(e) {
    e.preventDefault();
    start_loader();
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=save_secure_settings',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        dataType: 'json',
        error: function(err) {
            console.log(err);
            alert_toast("An error occurred", 'error');
            end_loader();
        },
        success: function(resp) {
            if(resp.status === 'success') {
                alert_toast(resp.msg || 'Settings saved successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                alert_toast(resp.msg || 'Failed to save settings', 'error');
            }
            end_loader();
        }
    });
});

function testSMSGate() {
    
    const resultDiv = $('#sms-result');
    resultDiv.removeClass('success error').html('<i class="fas fa-spinner fa-spin"></i> Testing SMS Gate servers...').show();
    
    let data = $('#settings-form').serialize();
    data += '&check_status=1';
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=test_smsgate_status',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(resp) {
            let html = '<div style="text-align:left;">';
            html += '<h6><i class="fas fa-server"></i> Server Status:</h6>';
            html += '<ul style="list-style:none;padding-left:0;">';
            
            if(resp.local) {
                html += '<li>' + (resp.local.online ? '✅' : '❌') + ' <strong>LOCAL:</strong> ' + (resp.local.url || 'Not configured') + ' - ' + (resp.local.online ? 'Online' : resp.local.error || 'Offline') + '</li>';
            }
            if(resp.public) {
                html += '<li>' + (resp.public.online ? '✅' : '❌') + ' <strong>PUBLIC:</strong> ' + (resp.public.url || 'Not configured') + ' - ' + (resp.public.online ? 'Online' : resp.public.error || 'Offline') + '</li>';
            }
            if(resp.cloud) {
                html += '<li>' + (resp.cloud.online ? '✅' : '❌') + ' <strong>CLOUD:</strong> ' + (resp.cloud.url || 'Not configured') + ' - ' + (resp.cloud.online ? 'Online' : resp.cloud.error || 'Offline') + '</li>';
            }
            
            html += '</ul>';
            
            if(resp.recommendation) {
                html += '<p class="mt-2"><strong>💡 Recommendation:</strong> ' + resp.recommendation + '</p>';
            }
            
            html += '</div>';
            
            if(resp.status === 'success') {
                resultDiv.addClass('success').html(html);
            } else {
                resultDiv.addClass('error').html(html);
            }
        },
        error: function(xhr) {
            resultDiv.addClass('error').html('<i class="fas fa-times-circle"></i> Connection failed: ' + xhr.statusText);
        }
    });
}

function testAPI(type) {
    const resultDiv = $('#' + type + '-result');
    resultDiv.removeClass('success error').html('<i class="fas fa-spinner fa-spin"></i> Testing...').show();
    
    let data = $('#settings-form').serialize();
    
    if(type === 'sms') {
        const phone = prompt('Enter test phone number (format: +639171234567):');
        if(!phone) {
            resultDiv.hide();
            return;
        }
        data += '&test_phone=' + encodeURIComponent(phone);
    }
    
    if(type === 'email_verification') {
        const email = prompt('Enter test email address:');
        if(!email) {
            resultDiv.hide();
            return;
        }
        data += '&test_email=' + encodeURIComponent(email);
    }
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=test_' + type + '_api',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(resp) {
            if(resp.status === 'success') {
                resultDiv.addClass('success').html('<i class="fas fa-check-circle"></i> ' + (resp.msg || 'Test successful!'));
            } else {
                resultDiv.addClass('error').html('<i class="fas fa-times-circle"></i> ' + (resp.msg || 'Test failed'));
            }
        },
        error: function() {
            resultDiv.addClass('error').html('<i class="fas fa-times-circle"></i> Connection failed');
        }
    });
}

function sendTestSMS() {
    const phone = $('#test_phone_number').val().trim();
    const resultDiv = $('#sms-result');
    
    if(!phone) {
        alert_toast('Please enter a phone number', 'warning');
        return;
    }
    
    resultDiv.removeClass('success error').html('<i class="fas fa-spinner fa-spin"></i> Sending test SMS to ' + phone + '...').show();
    
    let data = $('#settings-form').serialize();
    data += '&test_phone=' + encodeURIComponent(phone);
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=test_sms',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(resp) {
            if(resp.status === 'success') {
                resultDiv.addClass('success').html('<i class="fas fa-check-circle"></i> ' + (resp.msg || 'SMS sent successfully! Check your phone.'));
            } else {
                resultDiv.addClass('error').html('<i class="fas fa-times-circle"></i> ' + (resp.msg || 'SMS send failed'));
            }
        },
        error: function(xhr) {
            console.log(xhr.responseText);
            resultDiv.addClass('error').html('<i class="fas fa-times-circle"></i> Connection failed. Check console for details.');
        }
    });
}

function regenerateKey() {
    if(!confirm('WARNING: Regenerating the encryption key will invalidate all saved API keys! You will need to re-enter them. Continue?')) {
        return;
    }
    
    
    const newKey = Array.from(crypto.getRandomValues(new Uint8Array(16)))
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
    
    $('#api_encryption_key').val(newKey).attr('readonly', false);
    alert_toast('New key generated. Click Save Settings to apply.', 'warning');
}
</script>
