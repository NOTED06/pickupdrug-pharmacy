<?php

require_once('../inc/sess_auth.php');
require_once('../classes/DBConnection.php');

if($_settings->userdata('login_type') != 2){
    redirect('admin/?p=login');
}

$customer_id = $_settings->userdata('id');

if ($_POST) {
    $db = new DBConnection();
    $conn = $db->conn;
    
    
    $conn->query("CREATE TABLE IF NOT EXISTS customer_email_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        order_confirmations TINYINT DEFAULT 1,
        status_updates TINYINT DEFAULT 1,
        prescription_updates TINYINT DEFAULT 1,
        general_notifications TINYINT DEFAULT 1,
        promotional_emails TINYINT DEFAULT 0,
        reminders TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_customer (customer_id),
        FOREIGN KEY (customer_id) REFERENCES customer_list(id) ON DELETE CASCADE
    )");
    
    
    $order_confirmations = isset($_POST['order_confirmations']) ? 1 : 0;
    $status_updates = isset($_POST['status_updates']) ? 1 : 0;
    $prescription_updates = isset($_POST['prescription_updates']) ? 1 : 0;
    $general_notifications = isset($_POST['general_notifications']) ? 1 : 0;
    $promotional_emails = isset($_POST['promotional_emails']) ? 1 : 0;
    $reminders = isset($_POST['reminders']) ? 1 : 0;
    
    
    $stmt = $conn->prepare("INSERT INTO customer_email_preferences 
        (customer_id, order_confirmations, status_updates, prescription_updates, general_notifications, promotional_emails, reminders) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        order_confirmations = VALUES(order_confirmations),
        status_updates = VALUES(status_updates), 
        prescription_updates = VALUES(prescription_updates),
        general_notifications = VALUES(general_notifications),
        promotional_emails = VALUES(promotional_emails),
        reminders = VALUES(reminders),
        updated_at = CURRENT_TIMESTAMP");
    
    $stmt->bind_param('iiiiiii', $customer_id, $order_confirmations, $status_updates, $prescription_updates, $general_notifications, $promotional_emails, $reminders);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Email preferences updated successfully!'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to update email preferences.'];
    }
    
    header('Location: email-preferences.php');
    exit;
}

$db = new DBConnection();
$conn = $db->conn;

$prefs_query = $conn->prepare("SELECT * FROM customer_email_preferences WHERE customer_id = ?");
$prefs_query->bind_param('i', $customer_id);
$prefs_query->execute();
$prefs_result = $prefs_query->get_result();

$preferences = null;
if ($prefs_result->num_rows > 0) {
    $preferences = $prefs_result->fetch_assoc();
}

$customer_query = $conn->prepare("SELECT email FROM customer_list WHERE id = ?");
$customer_query->bind_param('i', $customer_id);
$customer_query->execute();
$customer_result = $customer_query->get_result();
$customer = $customer_result->fetch_assoc();
?>

<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/email-preferences.css">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<div class="content py-5" style="padding-top: 100px !important;">
    <div class="container">
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show mb-4">
                <i class="fas <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo $_SESSION['flash_message']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        
        <div class="page-header-banner">
            <div class="header-content">
                <div>
                    <h1>
                        <i class="fas fa-envelope-open-text"></i>
                        Email Preferences
                    </h1>
                    <p>Manage your email notification settings</p>
                    <div class="header-stats">
                        <span class="email-badge">
                            <i class="fas fa-at me-1"></i>
                            <?php echo htmlspecialchars($customer['email']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="preferences-card">
            <div class="card-body">
                <form method="POST" id="preferencesForm">
                    <div class="preferences-section">
                        <h4 class="section-title">
                            <i class="fas fa-shopping-cart text-primary"></i>
                            Order Notifications
                        </h4>
                        <p class="section-description">Receive updates about your orders</p>
                        
                        <div class="preference-item">
                            <div class="preference-content">
                                <div class="preference-icon">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <div class="preference-text">
                                    <h5>Order Confirmations</h5>
                                    <p>Get notified when your order is successfully placed</p>
                                </div>
                            </div>
                            <div class="preference-toggle">
                                <label class="switch">
                                    <input type="checkbox" name="order_confirmations" 
                                           <?php echo (!$preferences || $preferences['order_confirmations']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="preference-item">
                            <div class="preference-content">
                                <div class="preference-icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="preference-text">
                                    <h5>Status Updates</h5>
                                    <p>Receive notifications when your order status changes</p>
                                </div>
                            </div>
                            <div class="preference-toggle">
                                <label class="switch">
                                    <input type="checkbox" name="status_updates" 
                                           <?php echo (!$preferences || $preferences['status_updates']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="preferences-section">
                        <h4 class="section-title">
                            <i class="fas fa-prescription-bottle text-success"></i>
                            Prescription Notifications
                        </h4>
                        <p class="section-description">Updates about your prescription uploads and approvals</p>
                        
                        <div class="preference-item">
                            <div class="preference-content">
                                <div class="preference-icon">
                                    <i class="fas fa-file-medical-alt"></i>
                                </div>
                                <div class="preference-text">
                                    <h5>Prescription Updates</h5>
                                    <p>Get notified about prescription approval status</p>
                                </div>
                            </div>
                            <div class="preference-toggle">
                                <label class="switch">
                                    <input type="checkbox" name="prescription_updates" 
                                           <?php echo (!$preferences || $preferences['prescription_updates']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="preferences-section">
                        <h4 class="section-title">
                            <i class="fas fa-bell text-info"></i>
                            General Notifications
                        </h4>
                        <p class="section-description">System notifications and important updates</p>
                        
                        <div class="preference-item">
                            <div class="preference-content">
                                <div class="preference-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="preference-text">
                                    <h5>General Notifications</h5>
                                    <p>Account updates, security notices, and system messages</p>
                                </div>
                            </div>
                            <div class="preference-toggle">
                                <label class="switch">
                                    <input type="checkbox" name="general_notifications" 
                                           <?php echo (!$preferences || $preferences['general_notifications']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="preference-item">
                            <div class="preference-content">
                                <div class="preference-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="preference-text">
                                    <h5>Medication Reminders</h5>
                                    <p>Reminders for medication refills and pickups</p>
                                </div>
                            </div>
                            <div class="preference-toggle">
                                <label class="switch">
                                    <input type="checkbox" name="reminders" 
                                           <?php echo (!$preferences || $preferences['reminders']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="preferences-section">
                        <h4 class="section-title">
                            <i class="fas fa-tags text-warning"></i>
                            Marketing Communications
                        </h4>
                        <p class="section-description">Promotional offers and newsletters</p>
                        
                        <div class="preference-item">
                            <div class="preference-content">
                                <div class="preference-icon">
                                    <i class="fas fa-percent"></i>
                                </div>
                                <div class="preference-text">
                                    <h5>Promotional Emails</h5>
                                    <p>Product updates and recommendations</p>
                                </div>
                            </div>
                            <div class="preference-toggle">
                                <label class="switch">
                                    <input type="checkbox" name="promotional_emails" 
                                           <?php echo ($preferences && $preferences['promotional_emails']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-2"></i>
                            Save Preferences
                        </button>
                        <button type="button" class="btn-reset" onclick="resetForm()">
                            <i class="fas fa-undo me-2"></i>
                            Reset to Default
                        </button>
                    </div>
                </form>
            </div>
        </div>

        
        <div class="test-email-card">
            <div class="card-body">
                <h4 class="section-title">
                    <i class="fas fa-paper-plane text-primary"></i>
                    Test Email Configuration
                </h4>
                <p class="section-description">Send a test email to verify your notifications are working</p>
                
                <button type="button" class="btn-test-email" onclick="sendTestEmail()">
                    <i class="fas fa-envelope me-2"></i>
                    Send Test Email
                </button>
                
                <div id="testEmailResult" class="mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
function resetForm() {
    if (confirm('Reset all preferences to default values?')) {
        
        $('input[name="order_confirmations"]').prop('checked', true);
        $('input[name="status_updates"]').prop('checked', true);
        $('input[name="prescription_updates"]').prop('checked', true);
        $('input[name="general_notifications"]').prop('checked', true);
        $('input[name="promotional_emails"]').prop('checked', false);
        $('input[name="reminders"]').prop('checked', true);
    }
}

function sendTestEmail() {
    const btn = $('.btn-test-email');
    const originalText = btn.html();
    
    btn.prop('disabled', true);
    btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...');
    
    $.ajax({
        url: 'classes/CustomerEmailService.php?f=send_general_notification',
        method: 'POST',
        data: {
            customer_id: <?php echo $customer_id; ?>,
            subject: 'Test Email - Your notifications are working!',
            message: 'This is a test email to confirm that your email notifications are configured correctly. If you received this email, everything is working properly!',
            type: 'success'
        },
        dataType: 'json',
        success: function(resp) {
            const resultDiv = $('#testEmailResult');
            
            if (resp.status === 'success') {
                resultDiv.html('<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + resp.msg + '</div>');
            } else {
                resultDiv.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + resp.msg + '</div>');
            }
            
            resultDiv.show();
            
            
            setTimeout(() => {
                resultDiv.fadeOut();
            }, 5000);
        },
        error: function() {
            $('#testEmailResult').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Failed to send test email. Please try again.</div>').show();
        },
        complete: function() {
            btn.prop('disabled', false);
            btn.html(originalText);
        }
    });
}

$('input[type="checkbox"]').change(function() {
    const formData = $('#preferencesForm').serialize();
    
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: formData,
        success: function() {
            
            const indicator = $('<span class="save-indicator"><i class="fas fa-check text-success"></i></span>');
            $('.btn-save').append(indicator);
            setTimeout(() => indicator.fadeOut(function() { $(this).remove(); }), 2000);
        }
    });
});
</script>
