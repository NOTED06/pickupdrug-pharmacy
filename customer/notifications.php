<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/notifications.css">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<div class="content py-5" style="padding-top: 100px !important;">
    <div class="container">
        
        <div class="page-header-banner">
            <div class="header-content">
                <div>
                    <h1>
                        <i class="fas fa-bell"></i>
                        My Notifications
                    </h1>
                    <p>Stay updated with your orders and account activity</p>
                    <div class="header-stats">
                        <span class="stat-badge">
                            <i class="fas fa-envelope mr-1"></i>
                            <span id="unreadCount">0</span> Unread
                        </span>
                    </div>
                </div>
                <button class="btn-mark-read" id="mark-all-read">
                    <i class="fas fa-check-double"></i>
                    Mark all as read
                </button>
            </div>
        </div>
        
        <div class="notifications-card">
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="notification-list">
                    <?php 
                    $customer_id = $_settings->userdata('id');
                    $notifications = $conn->query("SELECT n.*, 
                                     CASE 
                                         WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 1 
                                         THEN 'Just now'
                                         WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 
                                         THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' minutes ago')
                                         WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 
                                         THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' hours ago')
                                         ELSE CONCAT(TIMESTAMPDIFF(DAY, n.created_at, NOW()), ' days ago')
                                     END as time_ago
                              FROM `notifications` n
                              WHERE n.customer_id = {$customer_id}
                              ORDER BY n.created_at DESC");
                    
                    if($notifications->num_rows > 0):
                        while($row = $notifications->fetch_assoc()):
                            $is_read = $row['is_read'] == 1 ? '' : 'unread';
                            $icon_class = 'fa-bell text-primary bg-primary-light';
                            $bg_class = 'bg-light';
                            
                            
                            if(stripos($row['title'], 'success') !== false || stripos($row['title'], 'approved') !== false || stripos($row['title'], 'completed') !== false || stripos($row['title'], 'ready') !== false) {
                                $icon_class = 'fa-check-circle text-success';
                                $bg_class = 'bg-success-light';
                            } elseif(stripos($row['title'], 'warning') !== false || stripos($row['title'], 'pending') !== false || stripos($row['title'], 'packed') !== false) {
                                $icon_class = 'fa-box text-info';
                                $bg_class = 'bg-info-light';
                            } elseif(stripos($row['title'], 'error') !== false || stripos($row['title'], 'cancelled') !== false || stripos($row['title'], 'declined') !== false) {
                                $icon_class = 'fa-times-circle text-danger';
                                $bg_class = 'bg-danger-light';
                            }
                            
                            
                            $icon_style = "";
                            if(stripos($bg_class, 'success') !== false) $icon_style = "background-color: #d1e7dd;";
                            else if(stripos($bg_class, 'info') !== false) $icon_style = "background-color: #cff4fc;";
                            else if(stripos($bg_class, 'danger') !== false) $icon_style = "background-color: #f8d7da;";
                            else $icon_style = "background-color: #e2e3e5;";
                    ?>
                    <div class="list-group-item list-group-item-action notification-list-item <?php echo $is_read ?>" data-id="<?php echo $row['id'] ?>">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="d-flex align-items-center w-100">
                                <div class="notification-icon-large mr-3" style="<?php echo $icon_style ?>">
                                    <i class="fa <?php echo $icon_class ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-0">
                                        <h6 class="mb-0 font-weight-bold text-dark"><?php echo $row['title'] ?></h6>
                                        <small class="notification-time text-nowrap ml-2">
                                            <i class="far fa-clock mr-1"></i> 
                                            <?php echo $row['time_ago'] ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 text-muted" style="font-size: 0.85rem; line-height: 1.3;"><?php echo $row['message'] ?></p>
                                    <?php if(isset($row['order_id']) && !empty($row['order_id'])): ?>
                                        <a href="./?p=orders&order_id=<?php echo $row['order_id'] ?>" class="btn btn-xs btn-outline-info mt-1 rounded-pill px-2 py-0" style="font-size: 0.75rem;">
                                            View Order <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="notification-actions ml-2 d-flex flex-column">
                                <?php if($row['is_read'] == 0): ?>
                                <button class="btn btn-sm btn-link text-primary mark-read-btn p-0 mb-1" title="Mark as read" data-toggle="tooltip">
                                    <i class="fas fa-circle" style="font-size: 8px;"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-link text-danger delete-notif-btn p-0" title="Delete" data-toggle="tooltip">
                                    <i class="far fa-trash-alt" style="font-size: 0.85rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="mb-4">
                            <span class="fa-stack fa-2x">
                                <i class="fas fa-circle fa-stack-2x text-light"></i>
                                <i class="far fa-bell fa-stack-1x text-muted"></i>
                            </span>
                        </div>
                        <h3>No notifications yet</h3>
                        <p class="text-muted">We'll let you know when something important happens.</p>
                        <a href="./" class="btn btn-primary mt-3 rounded-pill px-4">Go to Home</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url ?>customer/assets/js/notifications.js"></script>

