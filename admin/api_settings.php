<?php

require_once('../config.php');

if(!isset($_SESSION['userdata']['id'])){
    header("Location: login.php");
    exit;
}

$page_title = "API Settings";
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">🔑 API Configuration</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Important:</strong> These settings configure external services (Chatbot, Email, SMS). 
            <a href="<?php echo base_url ?>API_SETUP_GUIDE.md" target="_blank" class="alert-link">View Setup Guide</a>
        </div>

        <form id="api-settings-form">
            
            
            <div class="card">
                <div class="card-header bg-gradient-primary">
                    <h5 class="mb-0"><i class="fas fa-robot"></i> Google Gemini AI (Chatbot)</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>API Key <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="gemini_api_key" 
                               class="form-control" 
                               placeholder="AIzaSy..." 
                               value="<?php echo $_settings->info('gemini_api_key') ?? ''; ?>">
                        <small class="form-text text-muted">
                            Get free API key: <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
                        </small>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-info" onclick="testGeminiAPI()">
                            <i class="fas fa-flask"></i> Test Connection
                        </button>
                        <span id="gemini-status"></span>
                    </div>
                </div>
            </div>

            
            <div class="card mt-3">
                <div class="card-header bg-gradient-success">
                    <h5 class="mb-0"><i class="fas fa-envelope"></i> SMTP Email Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Gmail Users:</strong> Use an <strong>App Password</strong>, not your regular Gmail password!<br>
                        <small>
                            1. Enable 2FA: <a href="https://myaccount.google.com/security" target="_blank">Google Security</a><br>
                            2. Create App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a>
                        </small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SMTP Host</label>
                                <input type="text" 
                                       name="smtp_host" 
                                       class="form-control" 
                                       placeholder="smtp.gmail.com" 
                                       value="<?php echo $_settings->info('smtp_host') ?? 'smtp.gmail.com'; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SMTP Port</label>
                                <input type="number" 
                                       name="smtp_port" 
                                       class="form-control" 
                                       placeholder="587" 
                                       value="<?php echo $_settings->info('smtp_port') ?? '587'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address (Username) <span class="text-danger">*</span></label>
                        <input type="email" 
                               name="smtp_email" 
                               class="form-control" 
                               placeholder="your-pharmacy@gmail.com" 
                               value="<?php echo $_settings->info('smtp_email') ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>App Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               name="smtp_password" 
                               class="form-control" 
                               placeholder="xxxx xxxx xxxx xxxx" 
                               value="<?php echo $_settings->info('smtp_password') ?? ''; ?>">
                        <small class="form-text text-muted">
                            16-character App Password from Google (spaces optional)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-info" onclick="testSMTP()">
                            <i class="fas fa-flask"></i> Send Test Email
                        </button>
                        <span id="smtp-status"></span>
                    </div>
                </div>
            </div>

            
            <div class="card mt-3">
                <div class="card-header bg-gradient-warning">
                    <h5 class="mb-0"><i class="fas fa-sms"></i> SMS Gateway Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Free Hosting Warning:</strong> Self-hosted SMS won't work on free hosting!<br>
                        <small>Use Semaphore (Philippines) or Twilio instead.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>SMS Gateway Type <span class="text-danger">*</span></label>
                        <select name="sms_gateway_type" class="form-control" onchange="toggleSMSGatewayFields(this.value)">
                            <option value="semaphore" <?php echo ($_settings->info('sms_gateway_type') == 'semaphore' ? 'selected' : ''); ?>>
                                Semaphore (Recommended for Philippines)
                            </option>
                            <option value="twilio" <?php echo ($_settings->info('sms_gateway_type') == 'twilio' ? 'selected' : ''); ?>>
                                Twilio (Global)
                            </option>
                            <option value="httpsms" <?php echo ($_settings->info('sms_gateway_type') == 'httpsms' ? 'selected' : ''); ?>>
                                HttpSMS (Self-hosted - Local only)
                            </option>
                        </select>
                    </div>
                    
                    
                    <div id="semaphore-fields" class="gateway-fields">
                        <div class="form-group">
                            <label>Semaphore API Key <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="semaphore_api_key" 
                                   class="form-control" 
                                   placeholder="Your Semaphore API key" 
                                   value="<?php echo $_settings->info('semaphore_api_key') ?? ''; ?>">
                            <small class="form-text text-muted">
                                Get API key: <a href="https://semaphore.co/" target="_blank">Semaphore.co</a>
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Sender Name</label>
                            <input type="text" 
                                   name="semaphore_sender_name" 
                                   class="form-control" 
                                   placeholder="PickupDrug" 
                                   value="<?php echo $_settings->info('semaphore_sender_name') ?? 'PickupDrug'; ?>">
                        </div>
                    </div>
                    
                    
                    <div id="twilio-fields" class="gateway-fields" style="display: none;">
                        <div class="form-group">
                            <label>Account SID <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="twilio_account_sid" 
                                   class="form-control" 
                                   placeholder="AC..." 
                                   value="<?php echo $_settings->info('twilio_account_sid') ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Auth Token <span class="text-danger">*</span></label>
                            <input type="password" 
                                   name="twilio_auth_token" 
                                   class="form-control" 
                                   value="<?php echo $_settings->info('twilio_auth_token') ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="twilio_phone_number" 
                                   class="form-control" 
                                   placeholder="+1234567890" 
                                   value="<?php echo $_settings->info('twilio_phone_number') ?? ''; ?>">
                        </div>
                    </div>
                    
                    
                    <div id="httpsms-fields" class="gateway-fields" style="display: none;">
                        <div class="form-group">
                            <label>API URL</label>
                            <input type="text" 
                                   name="httpsms_api_url" 
                                   class="form-control" 
                                   placeholder="http://192.168.1.100:8080" 
                                   value="<?php echo $_settings->info('httpsms_api_url') ?? ''; ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" 
                                           name="httpsms_username" 
                                           class="form-control" 
                                           value="<?php echo $_settings->info('httpsms_username') ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" 
                                           name="httpsms_password" 
                                           class="form-control" 
                                           value="<?php echo $_settings->info('httpsms_password') ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-info" onclick="testSMS()">
                            <i class="fas fa-flask"></i> Send Test SMS
                        </button>
                        <span id="sms-status"></span>
                    </div>
                </div>
            </div>

            
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save API Settings
                </button>
                <a href="<?php echo base_url ?>API_SETUP_GUIDE.md" target="_blank" class="btn btn-info btn-lg">
                    <i class="fas fa-book"></i> View Setup Guide
                </a>
            </div>
        </form>
    </div>
</div>

<script>

function toggleSMSGatewayFields(type) {
    document.querySelectorAll('.gateway-fields').forEach(el => el.style.display = 'none');
    document.getElementById(type + '-fields').style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    const gatewayType = document.querySelector('select[name="sms_gateway_type"]').value;
    toggleSMSGatewayFields(gatewayType);
});

$('#api-settings-form').submit(function(e) {
    e.preventDefault();
    start_loader();
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=save_api_settings',
        data: new FormData($(this)[0]),
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        dataType: 'json',
        error: err => {
            console.log(err);
            alert_toast("An error occurred", 'error');
            end_loader();
        },
        success: function(resp) {
            if (typeof resp == 'object' && resp.status == 'success') {
                location.reload();
            } else if (resp.status == 'failed' && !!resp.msg) {
                var el = $('<div>');
                el.addClass("alert alert-danger err-msg").text(resp.msg);
                $('#api-settings-form').prepend(el);
                el.show('slow');
                $("html, body").animate({ scrollTop: 0 }, "fast");
                end_loader();
            } else {
                alert_toast("An error occurred", 'error');
                end_loader();
                console.log(resp);
            }
        }
    });
});

function testGeminiAPI() {
    const apiKey = document.querySelector('input[name="gemini_api_key"]').value;
    if (!apiKey) {
        alert('Please enter Gemini API key first');
        return;
    }
    
    $('#gemini-status').html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Testing...</span>');
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=test_gemini_api',
        method: 'POST',
        data: { api_key: apiKey },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                $('#gemini-status').html('<span class="text-success"><i class="fas fa-check-circle"></i> Connected!</span>');
            } else {
                $('#gemini-status').html('<span class="text-danger"><i class="fas fa-times-circle"></i> ' + resp.msg + '</span>');
            }
        },
        error: function() {
            $('#gemini-status').html('<span class="text-danger"><i class="fas fa-times-circle"></i> Connection failed</span>');
        }
    });
}

function testSMTP() {
    $('#smtp-status').html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Sending test email...</span>');
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=test_smtp',
        method: 'POST',
        data: $('#api-settings-form').serialize(),
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                $('#smtp-status').html('<span class="text-success"><i class="fas fa-check-circle"></i> Email sent! Check your inbox.</span>');
            } else {
                $('#smtp-status').html('<span class="text-danger"><i class="fas fa-times-circle"></i> ' + resp.msg + '</span>');
            }
        },
        error: function() {
            $('#smtp-status').html('<span class="text-danger"><i class="fas fa-times-circle"></i> Test failed</span>');
        }
    });
}

function testSMS() {
    $('#sms-status').html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Sending test SMS...</span>');
    
    const testPhone = prompt('Enter test phone number (format: +639171234567):');
    if (!testPhone) return;
    
    const formData = $('#api-settings-form').serialize() + '&test_phone=' + encodeURIComponent(testPhone);
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=test_sms',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                $('#sms-status').html('<span class="text-success"><i class="fas fa-check-circle"></i> SMS sent! Check your phone.</span>');
            } else {
                $('#sms-status').html('<span class="text-danger"><i class="fas fa-times-circle"></i> ' + resp.msg + '</span>');
            }
        },
        error: function() {
            $('#sms-status').html('<span class="text-danger"><i class="fas fa-times-circle"></i> Test failed</span>');
        }
    });
}
</script>
