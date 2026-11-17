
<div class="email-prefs-widget" style="margin-top: 20px;">
    <div class="card" style="border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <div class="card-header" style="background: linear-gradient(135deg, #16a085 0%, #0d9488 100%); color: white; border-radius: 12px 12px 0 0; padding: 20px;">
            <h5 class="mb-0" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-envelope-open-text"></i>
                Email Notifications
            </h5>
        </div>
        <div class="card-body" style="padding: 20px;">
            <?php
            
            $customer_id = $_settings->userdata('id');
            $prefs_query = $conn->query("SELECT * FROM customer_email_preferences WHERE customer_id = '{$customer_id}'");
            $has_preferences = $prefs_query && $prefs_query->num_rows > 0;
            $preferences = $has_preferences ? $prefs_query->fetch_assoc() : null;
            
            
            $enabled_count = 0;
            $total_count = 6; 
            
            if ($preferences) {
                $enabled_count = ($preferences['order_confirmations'] ?? 1) +
                               ($preferences['status_updates'] ?? 1) +
                               ($preferences['prescription_updates'] ?? 1) +
                               ($preferences['general_notifications'] ?? 1) +
                               ($preferences['promotional_emails'] ?? 0) +
                               ($preferences['reminders'] ?? 1);
            } else {
                $enabled_count = 5; 
            }
            ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="notification-status">
                        <?php if ($has_preferences): ?>
                            <p class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Email notifications are configured</strong>
                            </p>
                            <p class="text-muted mb-3">
                                You have <strong><?php echo $enabled_count; ?></strong> of <?php echo $total_count; ?> notification types enabled.
                            </p>
                        <?php else: ?>
                            <p class="mb-2">
                                <i class="fas fa-info-circle text-primary"></i>
                                <strong>Default email settings active</strong>
                            </p>
                            <p class="text-muted mb-3">
                                Most notifications are enabled by default. Customize your preferences anytime.
                            </p>
                        <?php endif; ?>
                        
                        <div class="notification-types">
                            <div class="row">
                                <div class="col-6">
                                    <div class="notification-item" style="margin-bottom: 8px;">
                                        <i class="fas fa-shopping-cart <?php echo (!$preferences || $preferences['order_confirmations']) ? 'text-success' : 'text-muted'; ?>"></i>
                                        <span class="<?php echo (!$preferences || $preferences['order_confirmations']) ? 'text-success' : 'text-muted'; ?>">Order confirmations</span>
                                    </div>
                                    <div class="notification-item" style="margin-bottom: 8px;">
                                        <i class="fas fa-truck <?php echo (!$preferences || $preferences['status_updates']) ? 'text-success' : 'text-muted'; ?>"></i>
                                        <span class="<?php echo (!$preferences || $preferences['status_updates']) ? 'text-success' : 'text-muted'; ?>">Status updates</span>
                                    </div>
                                    <div class="notification-item" style="margin-bottom: 8px;">
                                        <i class="fas fa-prescription-bottle <?php echo (!$preferences || $preferences['prescription_updates']) ? 'text-success' : 'text-muted'; ?>"></i>
                                        <span class="<?php echo (!$preferences || $preferences['prescription_updates']) ? 'text-success' : 'text-muted'; ?>">Prescription updates</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="notification-item" style="margin-bottom: 8px;">
                                        <i class="fas fa-bell <?php echo (!$preferences || $preferences['general_notifications']) ? 'text-success' : 'text-muted'; ?>"></i>
                                        <span class="<?php echo (!$preferences || $preferences['general_notifications']) ? 'text-success' : 'text-muted'; ?>">General notifications</span>
                                    </div>
                                    <div class="notification-item" style="margin-bottom: 8px;">
                                        <i class="fas fa-clock <?php echo (!$preferences || $preferences['reminders']) ? 'text-success' : 'text-muted'; ?>"></i>
                                        <span class="<?php echo (!$preferences || $preferences['reminders']) ? 'text-success' : 'text-muted'; ?>">Medication reminders</span>
                                    </div>
                                    <div class="notification-item" style="margin-bottom: 8px;">
                                        <i class="fas fa-percent <?php echo ($preferences && $preferences['promotional_emails']) ? 'text-success' : 'text-muted'; ?>"></i>
                                        <span class="<?php echo ($preferences && $preferences['promotional_emails']) ? 'text-success' : 'text-muted'; ?>">Promotional emails</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="preference-circle" style="width: 80px; height: 80px; border-radius: 50%; background: conic-gradient(#16a085 0deg <?php echo ($enabled_count / $total_count) * 360; ?>deg, #e5e7eb <?php echo ($enabled_count / $total_count) * 360; ?>deg 360deg); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; flex-direction: column; font-weight: 600; color: #16a085;">
                            <div style="font-size: 18px;"><?php echo $enabled_count; ?></div>
                            <div style="font-size: 10px;">of <?php echo $total_count; ?></div>
                        </div>
                    </div>
                    
                    <a href="<?php echo base_url ?>?p=customer/email-preferences" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-cog"></i> Configure
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
}

.notification-item i {
    width: 16px;
    text-align: center;
}
</style>