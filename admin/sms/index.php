<?php
require_once('../../initialize.php');
require_once('../../classes/HttpSmsGateway.php');

if(!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    redirect('admin/login.php');
}

$smsGateway = new HttpSmsGateway($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_settings') {
            
            $api_key = $_POST['api_key'] ?? '';
            $phone_id = $_POST['phone_id'] ?? '';
            
            $conn->query("REPLACE INTO settings (setting_name, setting_value) VALUES ('httpsms_api_key', '$api_key')");
            $conn->query("REPLACE INTO settings (setting_name, setting_value) VALUES ('httpsms_phone_id', '$phone_id')");
            
            $_SESSION['flash_message'] = 'Settings saved successfully!';
            redirect('admin/sms/');
            
        } else if ($_POST['action'] === 'test_sms') {
            
            $test_number = $_POST['test_number'];
            $test_message = $_POST['test_message'] ?? 'This is a test message from PickupDrug SMS System.';
            
            $result = $smsGateway->sendSMS($test_number, $test_message);
            
            if ($result['success']) {
                $_SESSION['flash_message'] = 'Test SMS sent successfully! Message ID: ' . $result['message_id'];
            } else {
                $_SESSION['flash_error'] = 'Failed to send SMS: ' . $result['error'];
            }
            redirect('admin/sms/');
        }
    }
}

$api_key_result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'httpsms_api_key'");
$phone_id_result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'httpsms_phone_id'");

$current_api_key = $api_key_result && $api_key_result->num_rows > 0 ? $api_key_result->fetch_assoc()['setting_value'] : '';
$current_phone_id = $phone_id_result && $phone_id_result->num_rows > 0 ? $phone_id_result->fetch_assoc()['setting_value'] : '';

$phone_status = null;
if (!empty($current_api_key) && !empty($current_phone_id)) {
    $phone_status = $smsGateway->getPhoneStatus();
}

$stats = $smsGateway->getStatistics();

$logs = $smsGateway->getSMSLogs(20);

$page_title = "SMS Gateway Settings";
include('../../admin/inc/header.php');
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>SMS Gateway Settings</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">SMS Gateway</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php if(isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $_SESSION['flash_message'] ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $_SESSION['flash_error'] ?>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['sent_today'] ?></h3>
                            <p>Sent Today</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $stats['failed_today'] ?></h3>
                            <p>Failed Today</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['sent_this_month'] ?></h3>
                            <p>Sent This Month</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box <?= $phone_status && $phone_status['success'] && $phone_status['online'] ? 'bg-success' : 'bg-warning' ?>">
                        <div class="inner">
                            <h3><?= $phone_status && $phone_status['success'] && $phone_status['online'] ? 'Online' : 'Offline' ?></h3>
                            <p>Phone Status</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">HttpSms Configuration</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Setup Instructions:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Download HttpSms app on your Android phone: <a href="https://github.com/NdoleStudio/httpsms/releases" target="_blank">GitHub Releases</a></li>
                                <li>Create account at <a href="https://httpsms.com" target="_blank">httpsms.com</a></li>
                                <li>Get your API Key from dashboard</li>
                                <li>Add your phone number in the app and get Phone ID</li>
                                <li>Enter both values below and save</li>
                            </ol>
                        </div>
                        
                        <div class="form-group">
                            <label for="api_key">API Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="api_key" name="api_key" 
                                   value="<?= htmlspecialchars($current_api_key) ?>" 
                                   placeholder="uk_xxxxxxxxxxx" required>
                            <small class="form-text text-muted">Get this from your HttpSms dashboard</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_id">Phone ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone_id" name="phone_id" 
                                   value="<?= htmlspecialchars($current_phone_id) ?>" 
                                   placeholder="+639171234567" required>
                            <small class="form-text text-muted">Your phone number in international format (+639XXXXXXXXX)</small>
                        </div>
                        
                        <?php if ($phone_status && $phone_status['success']): ?>
                        <div class="alert alert-<?= $phone_status['online'] ? 'success' : 'warning' ?>">
                            <h5><i class="icon fas fa-mobile-alt"></i> Phone Status</h5>
                            <ul class="mb-0">
                                <li>Status: <strong><?= $phone_status['online'] ? 'Online' : 'Offline' ?></strong></li>
                                <li>Last Seen: <?= $phone_status['last_seen'] ?></li>
                                <li>Messages Sent: <?= $phone_status['messages_sent'] ?></li>
                                <li>Battery: <?= $phone_status['battery'] ?>%</li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>

            
            <?php if (!empty($current_api_key) && !empty($current_phone_id)): ?>
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Send Test SMS</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="test_sms">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="test_number">Recipient Phone Number</label>
                            <input type="text" class="form-control" id="test_number" name="test_number" 
                                   placeholder="+639171234567" required>
                        </div>
                        <div class="form-group">
                            <label for="test_message">Message</label>
                            <textarea class="form-control" id="test_message" name="test_message" rows="3">This is a test message from PickupDrug SMS System.</textarea>
                            <small class="form-text text-muted">Maximum 160 characters for standard SMS</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Send Test SMS
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent SMS Logs</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No SMS logs yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td><?= htmlspecialchars($log['from_number']) ?></td>
                                    <td><?= htmlspecialchars($log['to_number']) ?></td>
                                    <td><?= htmlspecialchars(substr($log['message'], 0, 50)) ?><?= strlen($log['message']) > 50 ? '...' : '' ?></td>
                                    <td>
                                        <?php if ($log['status'] === 'sent'): ?>
                                            <span class="badge badge-success">Sent</span>
                                        <?php elseif ($log['status'] === 'failed'): ?>
                                            <span class="badge badge-danger" title="<?= htmlspecialchars($log['error_message'] ?? '') ?>">Failed</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($log['sent_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include('../../admin/inc/footer.php'); ?>
