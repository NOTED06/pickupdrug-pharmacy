<?php

$fb_settings = [
    'fb_page_access_token' => '',
    'fb_verify_token' => 'pickupdrug_verify_2025',
    'fb_app_secret' => ''
];

$query = $conn->query("SELECT meta_field, meta_value FROM system_info WHERE meta_field LIKE 'fb_%'");
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $fb_settings[$row['meta_field']] = $row['meta_value'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fb_settings'])) {
    $page_token = $conn->real_escape_string($_POST['fb_page_access_token'] ?? '');
    $verify_token = $conn->real_escape_string($_POST['fb_verify_token'] ?? 'pickupdrug_verify_2025');
    $app_secret = $conn->real_escape_string($_POST['fb_app_secret'] ?? '');
    
    
    $settings_to_save = [
        'fb_page_access_token' => $page_token,
        'fb_verify_token' => $verify_token,
        'fb_app_secret' => $app_secret
    ];
    
    foreach ($settings_to_save as $field => $value) {
        $check = $conn->query("SELECT id FROM system_info WHERE meta_field = '$field'");
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE system_info SET meta_value = '$value' WHERE meta_field = '$field'");
        } else {
            $conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('$field', '$value')");
        }
    }
    
    $_settings->set_flashdata('success', 'Facebook Chatbot settings saved successfully!');
    header('Location: ./?page=chatbot');
    exit;
}

$logs = $conn->query("SELECT * FROM chatbot_logs ORDER BY date_created DESC LIMIT 50");

$webhook_url = base_url . 'api/facebook_webhook.php';
?>

<style>
    .settings-card {
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .settings-card .card-header {
        background: linear-gradient(135deg, #1877f2 0%, #0d65d9 100%);
        border: none;
        padding: 20px 25px;
    }
    .settings-card .card-header h3 {
        color: white;
        margin: 0;
        font-size: 18px;
    }
    .webhook-url {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        word-break: break-all;
        border: 2px dashed #dee2e6;
    }
    .log-table {
        font-size: 0.85rem;
    }
    .log-message {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .setup-step {
        padding: 15px;
        background: #e8f4fd;
        border-left: 4px solid #1877f2;
        margin-bottom: 15px;
        border-radius: 0 8px 8px 0;
    }
    .setup-step h6 {
        color: #1877f2;
        margin-bottom: 8px;
        font-weight: 600;
    }
    .setup-step p {
        margin-bottom: 0;
        font-size: 13px;
        color: #495057;
    }
    .command-list {
        background: #f0f9ff;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid #bae6fd;
    }
    .command-list ul {
        margin: 0;
        padding-left: 20px;
    }
    .command-list li {
        margin-bottom: 5px;
    }
    .command-list code {
        background: #0ea5e9;
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-connected {
        background: #d1fae5;
        color: #065f46;
    }
    .status-disconnected {
        background: #fee2e2;
        color: #991b1b;
    }
    .form-control:focus {
        border-color: #1877f2;
        box-shadow: 0 0 0 0.2rem rgba(24, 119, 242, 0.25);
    }
</style>

<div class="card settings-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fab fa-facebook-messenger mr-2"></i> Facebook Chatbot Settings
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            
            <div class="col-lg-6 mb-4">
                <h5 class="mb-3"><i class="fas fa-cog text-primary"></i> Configuration</h5>
                
                
                <div class="alert <?= !empty($fb_settings['fb_page_access_token']) ? 'alert-success' : 'alert-warning' ?> mb-3">
                    <strong>Status:</strong> 
                    <?php if (!empty($fb_settings['fb_page_access_token'])): ?>
                        <span class="status-badge status-connected ml-2">
                            <i class="fas fa-check-circle"></i> Connected
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-disconnected ml-2">
                            <i class="fas fa-times-circle"></i> Not Configured
                        </span>
                    <?php endif; ?>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-key text-warning"></i> Page Access Token</label>
                        <textarea class="form-control" name="fb_page_access_token" rows="3" 
                                  placeholder="Paste your Facebook Page Access Token here"><?= htmlspecialchars($fb_settings['fb_page_access_token']) ?></textarea>
                        <small class="text-muted">Get this from Facebook Developer Console → Messenger Settings</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-shield-alt text-info"></i> Verify Token</label>
                        <input type="text" class="form-control" name="fb_verify_token" 
                               value="<?= htmlspecialchars($fb_settings['fb_verify_token'] ?: 'pickupdrug_verify_2025') ?>">
                        <small class="text-muted">Use this exact token when setting up the webhook in Facebook</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock text-danger"></i> App Secret (Optional)</label>
                        <input type="password" class="form-control" name="fb_app_secret" 
                               value="<?= htmlspecialchars($fb_settings['fb_app_secret']) ?>" 
                               placeholder="Enter your App Secret">
                        <small class="text-muted">Found in your Facebook App Settings → Basic</small>
                    </div>
                    
                    <button type="submit" name="save_fb_settings" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Save Settings
                    </button>
                </form>
                
                <hr class="my-4">
                
                <h5 class="mb-3"><i class="fas fa-link text-success"></i> Webhook URL</h5>
                <div class="webhook-url" id="webhookUrl">
                    <?= $webhook_url ?>
                </div>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="copyWebhookUrl()">
                    <i class="fas fa-copy"></i> Copy URL
                </button>
                <button class="btn btn-sm btn-outline-secondary mt-2" onclick="testWebhook()">
                    <i class="fas fa-vial"></i> Test Webhook
                </button>
            </div>
            
            
            <div class="col-lg-6 mb-4">
                <h5 class="mb-3"><i class="fas fa-book text-primary"></i> Setup Guide</h5>
                
                <div class="setup-step">
                    <h6><i class="fas fa-1 mr-1"></i> Create Facebook App</h6>
                    <p>Go to <a href="https://developers.facebook.com" target="_blank">Facebook Developers</a> → My Apps → Create App → Business</p>
                </div>
                
                <div class="setup-step">
                    <h6><i class="fas fa-2 mr-1"></i> Add Messenger Product</h6>
                    <p>In your app dashboard, click "Add Products" → Find "Messenger" → Click "Set Up"</p>
                </div>
                
                <div class="setup-step">
                    <h6><i class="fas fa-3 mr-1"></i> Connect Your Page</h6>
                    <p>Under Access Tokens, click "Add or Remove Pages" and connect your Facebook Page</p>
                </div>
                
                <div class="setup-step">
                    <h6><i class="fas fa-4 mr-1"></i> Generate Token</h6>
                    <p>Click "Generate Token" next to your page, copy it and paste above</p>
                </div>
                
                <div class="setup-step">
                    <h6><i class="fas fa-5 mr-1"></i> Configure Webhook</h6>
                    <p>
                        In Messenger Settings, go to "Webhooks" section:<br>
                        • <strong>Callback URL:</strong> <code style="font-size: 11px;"><?= $webhook_url ?></code><br>
                        • <strong>Verify Token:</strong> <code><?= htmlspecialchars($fb_settings['fb_verify_token'] ?: 'pickupdrug_verify_2025') ?></code><br>
                        • <strong>Subscriptions:</strong> Check "messages"
                    </p>
                </div>
                
                <div class="command-list mt-3">
                    <strong><i class="fas fa-terminal"></i> Bot Commands:</strong>
                    <ul class="mt-2">
                        <li><code>sales today</code> - Today's sales</li>
                        <li><code>sales this month</code> - Monthly sales</li>
                        <li><code>orders</code> - Orders summary</li>
                        <li><code>prescriptions</code> - Rx status</li>
                        <li><code>top products</code> - Best sellers</li>
                        <li><code>customers</code> - Customer count</li>
                        <li><code>help</code> - All commands</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <hr>
        
        
        <h5 class="mb-3"><i class="fas fa-history text-secondary"></i> Recent Chat Logs</h5>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover log-table">
                <thead class="bg-dark text-white">
                    <tr>
                        <th width="150">Date/Time</th>
                        <th width="150">Recipient</th>
                        <th>Message Sent</th>
                        <th width="80">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('M j, g:i A', strtotime($log['date_created'])) ?></td>
                            <td><code title="<?= htmlspecialchars($log['recipient_id']) ?>"><?= substr($log['recipient_id'], 0, 12) ?>...</code></td>
                            <td class="log-message" title="<?= htmlspecialchars($log['message']) ?>">
                                <?= htmlspecialchars(substr($log['message'], 0, 60)) ?><?= strlen($log['message']) > 60 ? '...' : '' ?>
                            </td>
                            <td>
                                <?php if (empty($log['error'])): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Sent</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" title="<?= htmlspecialchars($log['error']) ?>"><i class="fas fa-times"></i> Failed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No chat logs yet. Send a message to your Facebook Page to test!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyWebhookUrl() {
    var url = '<?= $webhook_url ?>';
    navigator.clipboard.writeText(url).then(function() {
        alert_toast('Webhook URL copied to clipboard!', 'success');
    }).catch(function() {
        
        var temp = document.createElement('textarea');
        temp.value = url;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert_toast('Webhook URL copied to clipboard!', 'success');
    });
}

function testWebhook() {
    fetch('<?= $webhook_url ?>?hub_mode=subscribe&hub_verify_token=<?= urlencode($fb_settings['fb_verify_token'] ?: 'pickupdrug_verify_2025') ?>&hub_challenge=test123')
        .then(response => response.text())
        .then(data => {
            if (data === 'test123') {
                alert_toast('✅ Webhook is working correctly!', 'success');
            } else {
                alert_toast('⚠️ Webhook returned unexpected response: ' + data, 'warning');
            }
        })
        .catch(error => {
            alert_toast('❌ Webhook test failed: ' + error, 'error');
        });
}
</script>
