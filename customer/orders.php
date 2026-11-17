<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/orders.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/orders.css') ?: time(); ?>">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css') ?: time(); ?>">
<style>

.action-buttons button[disabled] {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.btn-followup {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    font-size: 0.9rem;
    font-weight: 700;
    font-family: var(--font-body);
    border: 2px solid #0d9488 !important;
    color: #0d9488 !important;
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%) !important;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.25s ease;
    white-space: nowrap;
    box-shadow: 0 2px 6px rgba(13, 148, 136, 0.1);
    position: relative;
    z-index: 1;
}
.btn-followup:hover {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
    color: #fff !important;
    border-color: #0f766e !important;
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    transform: translateY(-2px);
}
.btn-followup:disabled {
    opacity: 0.55;
    pointer-events: none;
    transform: none !important;
    background: #e5e7eb !important;
    border-color: #d1d5db !important;
    color: #6b7280 !important;
    box-shadow: none !important;
    cursor: not-allowed;
}
.btn-followup.btn-sent {
    border-color: #059669 !important;
    color: #059669 !important;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
}

.btn-cancel-order {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    font-size: 0.9rem;
    font-weight: 700;
    font-family: var(--font-body);
    border: 2px solid #ef4444 !important;
    color: #ef4444 !important;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%) !important;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.25s ease;
    white-space: nowrap;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.1);
    position: relative;
    z-index: 1;
}
.btn-cancel-order:hover {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    color: #fff !important;
    border-color: #dc2626 !important;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    transform: translateY(-2px);
}
.btn-cancel-order:disabled {
    opacity: 0.45;
    pointer-events: none;
    transform: none !important;
    background: #9ca3af !important;
    border-color: #9ca3af !important;
    color: #fff !important;
}

.cancel-timer {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    min-width: 58px;
    text-align: center;
}
.cancel-timer:empty {
    display: none;
}
.cancel-timer.warning {
    background: #fef3c7;
    color: #92400e;
}
.cancel-timer.critical {
    background: #fee2e2;
    color: #991b1b;
    animation: pulse-critical 1s infinite;
}
@keyframes pulse-critical {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

@media (max-width: 768px) {
    .btn-followup,
    .btn-cancel-order {
        width: 100%;
        justify-content: center;
    }
}

.order-confirm-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease;
}
.order-confirm-overlay.active {
    display: flex;
}
.order-confirm-popup {
    background: white;
    border-radius: 16px;
    padding: 28px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
}
.order-confirm-popup.closing {
    animation: popOut 0.2s ease forwards;
}
.order-confirm-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}
.order-confirm-icon.icon-followup {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    box-shadow: 0 4px 20px rgba(13, 148, 136, 0.3);
}
.order-confirm-icon.icon-cancel {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
}
.order-confirm-icon.icon-success {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
}
.order-confirm-icon.icon-error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
}
.order-confirm-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    text-align: center;
    margin-bottom: 12px;
}
.order-confirm-message {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.95rem;
    color: #6b7280;
    text-align: center;
    margin-bottom: 24px;
    line-height: 1.5;
}
.order-confirm-input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.95rem;
    resize: vertical;
    min-height: 70px;
    margin-bottom: 20px;
    transition: border-color 0.2s;
}
.order-confirm-input:focus {
    outline: none;
    border-color: #0d9488;
}
.order-confirm-input-label {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.85rem;
    color: #9ca3af;
    text-align: left;
    margin-bottom: 6px;
    display: block;
}
.order-confirm-actions {
    display: flex;
    gap: 12px;
}
.order-confirm-btn {
    flex: 1;
    padding: 12px 20px;
    border-radius: 10px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.order-confirm-btn-cancel {
    background: #f3f4f6;
    color: #4b5563;
}
.order-confirm-btn-cancel:hover {
    background: #e5e7eb;
    transform: translateY(-1px);
}
.order-confirm-btn-followup {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: white;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
}
.order-confirm-btn-followup:hover {
    box-shadow: 0 6px 20px rgba(13, 148, 136, 0.4);
    transform: translateY(-1px);
}
.order-confirm-btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
}
.order-confirm-btn-danger:hover {
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    transform: translateY(-1px);
}
.order-confirm-btn-success {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: white;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
@keyframes popOut { from { transform: scale(1); opacity: 1; } to { transform: scale(0.8); opacity: 0; } }

.payment-banner {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    animation: slideDown 0.4s ease;
    position: relative;
}
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.payment-banner-icon { font-size: 1.6rem; flex-shrink: 0; margin-top: 2px; }
.payment-banner-content { flex: 1; }
.payment-banner-content strong { font-size: 1rem; display: block; margin-bottom: 4px; }
.payment-banner-content p { font-size: 0.85rem; margin: 0; line-height: 1.4; }
.payment-banner-close { position: absolute; top: 12px; right: 14px; background: none; border: none; cursor: pointer; font-size: 1rem; opacity: 0.5; transition: opacity 0.2s; }
.payment-banner-close:hover { opacity: 1; }
.payment-success { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1px solid #6ee7b7; }
.payment-success .payment-banner-icon { color: #059669; }
.payment-success strong { color: #065f46; }
.payment-success p { color: #047857; }
.payment-success .payment-banner-close { color: #065f46; }
.payment-failed { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 1px solid #fca5a5; }
.payment-failed .payment-banner-icon { color: #dc2626; }
.payment-failed strong { color: #991b1b; }
.payment-failed p { color: #b91c1c; }
.payment-failed .payment-banner-close { color: #991b1b; }
.payment-pending { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fcd34d; }
.payment-pending .payment-banner-icon { color: #d97706; }
.payment-pending strong { color: #92400e; }
.payment-pending p { color: #b45309; }
.payment-pending .payment-banner-close { color: #92400e; }

.pay-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.3px;
    margin-left: 6px;
    white-space: nowrap;
}
.pay-badge-paid { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.pay-badge-failed { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.pay-badge-pending_payment, .pay-badge-pending_verification, .pay-badge-unpaid { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }

.cancel-timer-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: #f59e0b;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 20px;
    margin-left: 10px;
    white-space: nowrap;
    animation: fadeIn 0.3s ease;
}
.cancel-timer-badge.expired {
    color: #ef4444;
    background: #fef2f2;
    border-color: #fecaca;
}
</style>

<?php

$payment_status_msg = isset($_GET['payment']) ? $_GET['payment'] : '';
$payment_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_order_code = '';
$payment_provider = 'GCash/Maya';
if ($payment_order_id > 0) {
    $pstmt = $conn->prepare("SELECT code, payment_method FROM order_list WHERE id = ?");
    $pstmt->bind_param('i', $payment_order_id);
    $pstmt->execute();
    $presult = $pstmt->get_result();
    if ($presult->num_rows > 0) {
        $prow = $presult->fetch_assoc();
        $payment_order_code = $prow['code'];
        if (!empty($prow['payment_method'])) {
            $pm = strtolower($prow['payment_method']);
            if ($pm === 'gcash') $payment_provider = 'GCash';
            elseif ($pm === 'maya') $payment_provider = 'Maya';
        }
    }
    $pstmt->close();
}
?>

<div class="orders-container">
    <div class="container-inner">
        
        
        <div class="orders-header">
            <div>
                <h1 class="page-title">Order <span class="text-accent">History</span></h1>
                <p class="page-subtitle">Track, manage, and review your medicine orders</p>
            </div>
            <div class="header-controls">
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search Order ID..." id="orderSearch">
                </div>
                <button class="icon-btn"><i class="fa-solid fa-filter"></i></button>
            </div>
        </div>

        <?php if ($payment_status_msg === 'success' && $payment_order_code): ?>
        
        <div class="payment-banner payment-success" id="paymentBanner">
            <div class="payment-banner-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="payment-banner-content">
                <strong>Payment Successful!</strong>
                <p>Your <?= htmlspecialchars($payment_provider) ?> payment for Order <strong>#<?= htmlspecialchars($payment_order_code) ?></strong> has been confirmed. Thank you!</p>
            </div>
            <button class="payment-banner-close" onclick="dismissPaymentBanner(this)"><i class="fa-solid fa-times"></i></button>
        </div>
        <?php elseif ($payment_status_msg === 'cancelled' && $payment_order_code): ?>
        
        <div class="payment-banner payment-failed" id="paymentBanner">
            <div class="payment-banner-icon"><i class="fa-solid fa-ban"></i></div>
            <div class="payment-banner-content">
                <strong>Order Cancelled</strong>
                <p>Your <?= htmlspecialchars($payment_provider) ?> payment for Order <strong>#<?= htmlspecialchars($payment_order_code) ?></strong> was not completed. Your order has been <strong>automatically cancelled</strong>. You may place a new order anytime.</p>
            </div>
            <button class="payment-banner-close" onclick="dismissPaymentBanner(this)"><i class="fa-solid fa-times"></i></button>
        </div>
        <?php elseif ($payment_status_msg === 'failed' && $payment_order_code): ?>
        <div class="payment-banner payment-failed" id="paymentBanner">
            <div class="payment-banner-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            <div class="payment-banner-content">
                <strong>Payment Failed</strong>
                <p>The payment for Order <strong>#<?= htmlspecialchars($payment_order_code) ?></strong> was not completed. Your order has been cancelled.</p>
            </div>
            <button class="payment-banner-close" onclick="dismissPaymentBanner(this)"><i class="fa-solid fa-times"></i></button>
        </div>
        <?php elseif ($payment_status_msg === 'pending'): ?>
        <div class="payment-banner payment-pending" id="paymentBanner">
            <div class="payment-banner-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="payment-banner-content">
                <strong>Payment Processing</strong>
                <p>Your payment is still being verified. This may take a moment.</p>
            </div>
            <button class="payment-banner-close" onclick="dismissPaymentBanner(this)"><i class="fa-solid fa-times"></i></button>
        </div>
        <?php endif; ?>

        
        <div class="filter-pills">
            <button class="filter-pill active" data-status="all">All Orders</button>
            <button class="filter-pill" data-status="0">Pending</button>
            <button class="filter-pill" data-status="2">Ready to Pickup</button>
            <button class="filter-pill" data-status="3">Completed</button>
            <button class="filter-pill" data-status="4">Cancelled</button>
        </div>

        
        <div class="orders-list">
            <?php 
            $i = 1;
            $customer_id = intval($_settings->userdata('id'));
            
            $stmt = $conn->prepare("SELECT * FROM order_list WHERE customer_id = ? ORDER BY ABS(UNIX_TIMESTAMP(date_created)) DESC");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $orders = $stmt->get_result();
            
            if($orders->num_rows > 0) {
                while($row = $orders->fetch_assoc()) {
                    
                    $order_id = intval($row['id']);
                    $stmt_items = $conn->prepare("SELECT oi.*, p.name, p.image_path 
                                          FROM order_items oi 
                                          INNER JOIN product_list p ON oi.product_id = p.id 
                                          WHERE oi.order_id = ? 
                                          LIMIT 3");
                    $stmt_items->bind_param("i", $order_id);
                    $stmt_items->execute();
                    $items = $stmt_items->get_result();
                    
                    $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM order_items WHERE order_id = ?");
                    $stmt_count->bind_param("i", $order_id);
                    $stmt_count->execute();
                    $item_count = $stmt_count->get_result();
                    $total_items = $item_count->fetch_assoc()['total'];
                    $stmt_count->close();
                    
                    
                    $status_class = '';
                    $status_text = '';
                    $status_icon = '';
                    switch($row['status']){
                        case 0:
                            $status_class = 'status-pending';
                            $status_text = 'Pending Approval';
                            $status_icon = 'fa-clock';
                            break;
                        case 1:
                            $status_class = 'status-packed';
                            $status_text = 'Packed';
                            $status_icon = 'fa-box';
                            break;
                        case 2:
                            $status_class = 'status-ready';
                            $status_text = 'Ready for Pickup';
                            $status_icon = 'fa-check-circle';
                            break;
                        case 3:
                            $status_class = 'status-paid';
                            $status_text = 'Completed';
                            $status_icon = 'fa-money-bill-wave';
                            break;
                        case 4:
                            $status_class = 'status-cancelled';
                            $status_text = 'Cancelled';
                            $status_icon = 'fa-times-circle';
                            break;
                    }

                    $rx_status = $row['prescription_validation'] ?? '';
                    if ($rx_status === 'pending_review') {
                        $rx_status = 'mismatch';
                    }
                    $rx_label_map = [
                        'matched' => 'Match',
                        'partial' => 'Partial',
                        'mismatch' => 'Mismatch',
                        'invalid' => 'Invalid'
                    ];
                    $rx_icon_map = [
                        'matched' => 'fa-check-circle',
                        'partial' => 'fa-exclamation-triangle',
                        'mismatch' => 'fa-times-circle',
                        'invalid' => 'fa-question-circle'
                    ];
                    $show_rx_status = !empty($rx_status) && $rx_status !== 'pending';
                    $rx_label = $rx_label_map[$rx_status] ?? 'Status';
                    $rx_icon = $rx_icon_map[$rx_status] ?? 'fa-file-medical';
            ?>
            <div class="order-card" data-status="<?= $row['status'] ?>" data-id="<?= $row['id'] ?>" data-code="<?= $row['code'] ?>" data-created="<?= strtotime($row['date_created']) ?>">
                <div class="card-header">
                    <div class="order-meta">
                        <span class="order-id">ORDER <span class="id-code">#<?= $row['code'] ?></span></span>
                        <?php if(!empty($row['is_urgent'])): ?>
                            <span class="badge badge-danger ml-1" style="font-size:0.75rem; background:#dc2626; color:#fff; padding:3px 8px; border-radius:6px; font-weight:700; display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-bolt"></i> URGENT</span>
                        <?php endif; ?>
                        <span class="divider">•</span>
                        <span class="order-date"><?= date("M d, Y", strtotime($row['date_created'])) ?></span>
                        <?php
                        $pay_method = $row['payment_method'] ?? 'cash';
                        $pay_status = $row['payment_status'] ?? 'unpaid';
                        if (in_array($pay_method, ['gcash', 'maya'])):
                            $pay_badge_class = 'pay-badge-' . $pay_status;
                            $pay_badge_icon = 'fa-mobile-screen';
                            $pay_badge_label = strtoupper($pay_method);
                            if ($pay_status === 'paid') {
                                $pay_badge_icon = 'fa-circle-check';
                                $pay_badge_label .= ' PAID';
                            } elseif ($pay_status === 'failed') {
                                $pay_badge_icon = 'fa-circle-xmark';
                                $pay_badge_label .= ' FAILED';
                            } else {
                                $pay_badge_icon = 'fa-clock';
                                $pay_badge_label .= ' PENDING';
                            }
                        ?>
                        <span class="pay-status-badge <?= $pay_badge_class ?>">
                            <i class="fa-solid <?= $pay_badge_icon ?>"></i> <?= $pay_badge_label ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="order-status-group">
                        <div class="order-status <?= $status_class ?>">
                            <span class="status-dot"></span>
                            <span class="status-text"><?= strtoupper($status_text) ?></span>
                        </div>
                        <?php if ($show_rx_status): ?>
                            <div class="rx-validation-chip <?= htmlspecialchars($rx_status) ?>">
                                <i class="fa-solid <?= htmlspecialchars($rx_icon) ?>"></i>
                                RX <?= strtoupper($rx_label) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="order-preview">
                        <div class="item-thumbnails">
                            <?php 
                            $thumb_count = 0;
                            while($item = $items->fetch_assoc()): 
                                $thumb_count++;
                            ?>
                            <div class="thumb-box">
                                <?php if(!empty($item['image_path'])): ?>
                                    <img src="<?= validate_image($item['image_path']) ?>" alt="<?= $item['name'] ?>">
                                <?php else: ?>
                                    <i class="fa-solid fa-pills"></i>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                            <?php if($total_items > 3): ?>
                            <div class="more-items">+<?= ($total_items - 3) ?> more</div>
                            <?php endif; ?>
                        </div>
                        <div class="order-summary">
                            <h4 class="order-title">
                                <?php 
                                $items->data_seek(0);
                                $first = $items->fetch_assoc();
                                echo $first ? $first['name'] : 'No items';
                                
                                if (isset($stmt_items) && $stmt_items) {
                                    $stmt_items->close();
                                }
                                ?>
                            </h4>
                            <p class="item-count">
                                <?php if($total_items > 1): ?>
                                + <?= ($total_items - 1) ?> other item<?= ($total_items - 1) > 1 ? 's' : '' ?>
                                <?php else: ?>
                                <?= $total_items ?> Item
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="order-actions">
                        <div class="amount-section">
                            <p class="amount-label">Total Amount</p>
                            <p class="amount-value">₱<?= format_num($row['total_amount'],2) ?></p>
                        </div>
                        <div class="action-buttons">
                            <?php 
                            
                            $invoice_disabled = ($row['status'] == 0 || $row['status'] == 4) ? 'disabled' : '';
                            $invoice_title = ($row['status'] == 0) ? 'title="Invoice not available for pending orders"' : (($row['status'] == 4) ? 'title="Invoice not available for cancelled orders"' : '');
                            
                            
                            $can_followup = in_array($row['status'], [0, 1]);
                            
                            
                            $can_cancel = false;
                            $cancel_time_left = 0;
                            $cancel_expired = false;
                            if ($row['status'] == 0) {
                                $order_created = strtotime($row['date_created']);
                                $now = time();
                                $elapsed_seconds = $now - $order_created;
                                $cancel_limit_seconds = 15 * 60; 
                                
                                if ($elapsed_seconds < $cancel_limit_seconds) {
                                    $can_cancel = true;
                                    $cancel_time_left = $cancel_limit_seconds - $elapsed_seconds;
                                } else {
                                    $cancel_expired = true;
                                }
                            }
                            ?>
                            <?php if ($can_followup): ?>
                            <button class="btn-followup" data-id="<?= $row['id'] ?>" data-code="<?= $row['code'] ?>"><i class="fa-solid fa-bell"></i> Follow Up</button>
                            <?php elseif ($row['status'] == 4): ?>
                            <button class="btn-followup" disabled title="Cannot follow up on cancelled orders"><i class="fa-solid fa-bell-slash"></i> Follow Up</button>
                            <?php endif; ?>
                            <?php if ($can_cancel): ?>
                            <button class="btn-cancel-order" data-id="<?= $row['id'] ?>" data-code="<?= $row['code'] ?>" data-expires="<?= $order_created + $cancel_limit_seconds ?>">
                                <i class="fa-solid fa-times"></i> Cancel
                                <span class="cancel-timer" data-seconds="<?= $cancel_time_left ?>"></span>
                            </button>
                            <?php elseif ($row['status'] == 4): ?>
                            <button class="btn-cancel-order" disabled title="Order is cancelled"><i class="fa-solid fa-ban"></i> Cancelled</button>
                            <?php elseif ($cancel_expired): ?>
                            <button class="btn-cancel-order" disabled title="Cancellation window expired (15 minutes)">
                                <i class="fa-solid fa-times"></i> Cancel Expired
                            </button>
                            <?php endif; ?>
                            <button class="btn-secondary btn-invoice" data-id="<?= $row['id'] ?>" <?= $invoice_disabled ?> <?= $invoice_title ?>><i class="fa-solid fa-file-invoice"></i> Invoice</button>
                            <button class="btn-primary view-order" data-id="<?= $row['id'] ?>">View Details</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                }
                if (isset($stmt) && $stmt) {
                    $stmt->close();
                }
            } else {
            ?>
            
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-shopping-basket"></i>
                </div>
                <h3>No Orders Yet</h3>
                <p>When you place an order, it will appear here. Browse our catalog and start shopping for your medical needs.</p>
                <a href="./?p=customer/categories" class="btn-browse">
                    <i class="fa-solid fa-pills"></i>
                    Browse Products
                </a>
            </div>
            <?php } ?>
        </div>

    </div>
</div>

<div class="order-confirm-overlay" id="orderConfirmOverlay">
    <div class="order-confirm-popup" id="orderConfirmPopup">
        <div class="order-confirm-icon" id="orderConfirmIcon">
            <i id="orderConfirmIconI" class="fa-solid fa-bell"></i>
        </div>
        <h3 class="order-confirm-title" id="orderConfirmTitle"></h3>
        <p class="order-confirm-message" id="orderConfirmMessage"></p>
        <div id="orderConfirmInputWrap" style="display:none;">
            <span class="order-confirm-input-label">Reason for cancellation (optional)</span>
            <textarea class="order-confirm-input" id="orderConfirmInput" placeholder="Enter your reason here..."></textarea>
        </div>
        <div class="order-confirm-actions" id="orderConfirmActions">
            <button class="order-confirm-btn order-confirm-btn-cancel" id="orderConfirmCancel">
                <i class="fa-solid fa-times"></i> Cancel
            </button>
            <button class="order-confirm-btn" id="orderConfirmOk">
                <i id="orderConfirmOkIcon" class="fa-solid fa-bell"></i> <span id="orderConfirmOkText">Confirm</span>
            </button>
        </div>
    </div>
</div>

<script src="<?php echo base_url ?>customer/assets/js/orders.js?v=<?php echo @filemtime(__DIR__ . '/assets/js/orders.js') ?: time(); ?>"></script>
<?php if ($payment_status_msg === 'success' && $payment_order_code): ?>
<script>
$(document).ready(function(){
    
    if (typeof showOrderPopup === 'function') {
        showOrderPopup({
            icon: 'fa-circle-check',
            iconClass: 'icon-success',
            title: 'Payment Successful!',
            message: 'Your <?= htmlspecialchars($payment_provider) ?> payment for Order <strong>#<?= htmlspecialchars($payment_order_code) ?></strong> has been received. Your order will be prepared shortly.',
            okText: 'Great!',
            okClass: 'order-confirm-btn-success',
            okIcon: 'fa-check',
            hideCancel: true
        });
    }
    
    if (window.history.replaceState) {
        window.history.replaceState(null, '', window.location.pathname + '?p=customer/orders');
    }
});
</script>
<?php endif; ?>
