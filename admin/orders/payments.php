<?php

if($_settings->userdata('login_type') != 1){
    echo "<script>alert('Access denied'); location.replace('./home');</script>";
    exit;
}

if(isset($_POST['verify_payment'])) {
    
    if (!function_exists('validate_csrf') || !validate_csrf()) {
        $_settings->set_flashdata('error', 'Invalid security token. Please refresh the page.');
        header('Location: ?page=orders/payments');
        exit;
    }
    
    $order_id = intval($_POST['order_id']);
    $action = isset($_POST['action']) ? $_POST['action'] : ''; 
    $admin_id = $_settings->userdata('id');
    
    if($action === 'approve') {
        $stmt = $conn->prepare("UPDATE order_list SET payment_status = 'paid', payment_verified_at = NOW(), payment_verified_by = ? WHERE id = ?");
        $stmt->bind_param('ii', $admin_id, $order_id);
        
        if($stmt->execute()) {
            
            $stmt_order = $conn->prepare("SELECT o.code, o.customer_id, c.firstname FROM order_list o INNER JOIN customer_list c ON o.customer_id = c.id WHERE o.id = ?");
            $stmt_order->bind_param("i", $order_id);
            $stmt_order->execute();
            $order_result = $stmt_order->get_result();
            if($order_result && $order_data = $order_result->fetch_assoc()) {
                $stmt_order->close();
                
                $notification_title = "Payment Verified ✓";
                $notification_message = "Your payment for Order #{$order_data['code']} has been verified. Thank you!";
                
                $notif_stmt = $conn->prepare("INSERT INTO notifications (customer_id, title, message, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                $notif_stmt->bind_param('issi', $order_data['customer_id'], $notification_title, $notification_message, $order_id);
                $notif_stmt->execute();
            }
            
            $_settings->set_flashdata('success', 'Payment verified successfully!');
        } else {
            $_settings->set_flashdata('error', 'Failed to verify payment.');
        }
    } elseif($action === 'reject') {
        $reject_reason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : 'Payment receipt rejected by admin';
        $reject_reason = $conn->real_escape_string($reject_reason);
        
        $stmt = $conn->prepare("UPDATE order_list SET payment_status = 'failed', payment_verified_at = NOW(), payment_verified_by = ? WHERE id = ?");
        $stmt->bind_param('ii', $admin_id, $order_id);
        
        if($stmt->execute()) {
            
            $stmt_order = $conn->prepare("SELECT o.code, o.customer_id, c.firstname FROM order_list o INNER JOIN customer_list c ON o.customer_id = c.id WHERE o.id = ?");
            $stmt_order->bind_param("i", $order_id);
            $stmt_order->execute();
            $order_result = $stmt_order->get_result();
            if($order_result && $order_data = $order_result->fetch_assoc()) {
                $stmt_order->close();
                
                $notification_title = "Payment Issue";
                $notification_message = "There was an issue with your payment for Order #{$order_data['code']}. Reason: {$reject_reason}. Please contact us or resubmit payment.";
                
                $notif_stmt = $conn->prepare("INSERT INTO notifications (customer_id, title, message, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                $notif_stmt->bind_param('issi', $order_data['customer_id'], $notification_title, $notification_message, $order_id);
                $notif_stmt->execute();
            }
            
            $_settings->set_flashdata('warning', 'Payment rejected. Customer has been notified.');
        } else {
            $_settings->set_flashdata('error', 'Failed to reject payment.');
        }
    }
    
    header('Location: ?page=orders/payments');
    exit;
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$where_clause = "";

switch($filter) {
    case 'pending':
        $where_clause = "WHERE o.payment_status = 'pending_verification'";
        break;
    case 'paid':
        $where_clause = "WHERE o.payment_status = 'paid'";
        break;
    case 'failed':
        $where_clause = "WHERE o.payment_status = 'failed'";
        break;
    case 'all':
        $where_clause = "WHERE o.payment_method IN ('gcash', 'maya')";
        break;
    default:
        $where_clause = "WHERE o.payment_status = 'pending_verification'";
}

$payments_query = $conn->query("
    SELECT o.*, 
           c.firstname, c.lastname, c.email, c.contact
    FROM order_list o
    INNER JOIN customer_list c ON o.customer_id = c.id
    {$where_clause}
    ORDER BY o.date_created DESC
");
?>

<style>
.payment-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    overflow: hidden;
}

.payment-card-header {
    padding: 15px 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.payment-card-body {
    padding: 20px;
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
}

.receipt-image-container {
    text-align: center;
}

.receipt-image {
    max-width: 180px;
    max-height: 250px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: pointer;
    transition: transform 0.2s;
}

.receipt-image:hover {
    transform: scale(1.05);
}

.payment-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.payment-detail-item {
    background: #f8fafc;
    padding: 12px 15px;
    border-radius: 8px;
}

.payment-detail-item label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
    display: block;
}

.payment-detail-item .value {
    font-weight: 600;
    color: #1f2937;
}

.payment-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 10px;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

.btn-verify {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-verify:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-reject {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-reject:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.paid {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.failed {
    background: #fee2e2;
    color: #dc2626;
}

.method-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.method-badge.gcash {
    background: #dbeafe;
    color: #1d4ed8;
}

.method-badge.maya {
    background: #d1fae5;
    color: #065f46;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-tab {
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.filter-tab.active {
    background: #0d9488;
    color: white;
}

.filter-tab:not(.active) {
    background: #f1f5f9;
    color: #4b5563;
}

.filter-tab:not(.active):hover {
    border-color: #0d9488;
    color: #0d9488;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.empty-state i {
    font-size: 64px;
    color: #9ca3af;
    margin-bottom: 20px;
}

.empty-state h4 {
    color: #4b5563;
    margin-bottom: 10px;
}

.empty-state p {
    color: #9ca3af;
}

.receipt-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.receipt-modal.show {
    display: flex;
}

.receipt-modal img {
    max-width: 90%;
    max-height: 90%;
    border-radius: 8px;
}

.receipt-modal-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 30px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .payment-card-body {
        grid-template-columns: 1fr;
    }
    
    .payment-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid">
    <div class="content-header">
        <h3><i class="fas fa-credit-card text-primary"></i> Payment Verification</h3>
        <p class="text-muted">Verify GCash and Maya payment receipts</p>
    </div>
    
    
    <div class="filter-tabs">
        <a href="?page=orders/payments&filter=pending" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Pending Verification
        </a>
        <a href="?page=orders/payments&filter=paid" class="filter-tab <?= $filter === 'paid' ? 'active' : '' ?>">
            <i class="fas fa-check-circle"></i> Verified
        </a>
        <a href="?page=orders/payments&filter=failed" class="filter-tab <?= $filter === 'failed' ? 'active' : '' ?>">
            <i class="fas fa-times-circle"></i> Rejected
        </a>
        <a href="?page=orders/payments&filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
            <i class="fas fa-list"></i> All E-Wallet
        </a>
    </div>
    
    <?php if($payments_query && $payments_query->num_rows > 0): ?>
        <?php while($payment = $payments_query->fetch_assoc()): ?>
        <div class="payment-card">
            <div class="payment-card-header">
                <div>
                    <strong>Order #<?= $payment['code'] ?></strong>
                    <span class="method-badge <?= $payment['payment_method'] ?>"><?= strtoupper($payment['payment_method']) ?></span>
                </div>
                <div>
                    <?php
                    $status_class = 'pending';
                    $status_text = 'Pending';
                    if($payment['payment_status'] === 'paid') {
                        $status_class = 'paid';
                        $status_text = 'Verified';
                    } elseif($payment['payment_status'] === 'failed') {
                        $status_class = 'failed';
                        $status_text = 'Rejected';
                    } elseif($payment['payment_status'] === 'pending_verification') {
                        $status_class = 'pending';
                        $status_text = 'Awaiting Verification';
                    }
                    ?>
                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                </div>
            </div>
            
            <div class="payment-card-body">
                <div class="receipt-image-container">
                    <?php if(!empty($payment['payment_receipt'])): ?>
                    <img src="<?= base_url . $payment['payment_receipt'] ?>" alt="Payment Receipt" class="receipt-image" 
                         onclick="showReceiptModal('<?= base_url . $payment['payment_receipt'] ?>')">
                    <p class="mt-2 text-muted small">Click to enlarge</p>
                    <?php else: ?>
                    <div style="padding: 40px; background: #f3f4f6; border-radius: 8px;">
                        <i class="fas fa-receipt" style="font-size: 48px; color: #9ca3af;"></i>
                        <p class="text-muted mt-2">No receipt uploaded</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="payment-details">
                    <div class="payment-detail-item">
                        <label>Customer</label>
                        <div class="value"><?= htmlspecialchars($payment['firstname'] . ' ' . $payment['lastname']) ?></div>
                    </div>
                    <div class="payment-detail-item">
                        <label>Contact</label>
                        <div class="value"><?= htmlspecialchars($payment['contact'] ?: $payment['email']) ?></div>
                    </div>
                    <div class="payment-detail-item">
                        <label>Amount</label>
                        <div class="value" style="font-size: 18px; color: #0d9488;">₱<?= number_format($payment['total_amount'], 2) ?></div>
                    </div>
                    <div class="payment-detail-item">
                        <label>Order Date</label>
                        <div class="value"><?= date('M d, Y h:i A', strtotime($payment['date_created'])) ?></div>
                    </div>
                    <div class="payment-detail-item">
                        <label>Pickup Location</label>
                        <div class="value"><?= htmlspecialchars($_settings->info('name')) ?></div>
                    </div>
                    <div class="payment-detail-item">
                        <label>Pickup Date/Time</label>
                        <div class="value">
                            <?php if(!empty($payment['pickup_date'])): ?>
                            <?= date('M d, Y', strtotime($payment['pickup_date'])) ?> at <?= date('g:i A', strtotime($payment['pickup_time'])) ?>
                            <?php else: ?>
                            N/A
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if($payment['payment_status'] === 'pending_verification'): ?>
                    <div class="payment-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="verify_payment" value="1">
                            <input type="hidden" name="order_id" value="<?= $payment['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn-verify" onclick="return confirm('Verify this payment as received?')">
                                <i class="fas fa-check"></i> Verify Payment
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="verify_payment" value="1">
                            <input type="hidden" name="order_id" value="<?= $payment['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn-reject" onclick="return confirm('Reject this payment? Customer will be notified.')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                        
                        <a href="?page=orders/view_order&id=<?= $payment['id'] ?>" class="btn btn-outline-primary">
                            <i class="fas fa-eye"></i> View Order
                        </a>
                    </div>
                    <?php elseif($payment['payment_verified_at']): ?>
                    <div class="payment-detail-item" style="grid-column: 1 / -1;">
                        <label>Verified</label>
                        <div class="value">
                            <?= date('M d, Y h:i A', strtotime($payment['payment_verified_at'])) ?>
                            <?php if($payment['payment_status'] === 'paid'): ?>
                            <span class="text-success"><i class="fas fa-check-circle"></i> Approved</span>
                            <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle"></i> Rejected</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h4>No payments found</h4>
        <p>There are no <?= $filter !== 'all' ? $filter : '' ?> e-wallet payments to display.</p>
    </div>
    <?php endif; ?>
</div>

<div class="receipt-modal" id="receiptModal" onclick="hideReceiptModal()">
    <span class="receipt-modal-close">&times;</span>
    <img id="receiptModalImage" src="" alt="Receipt">
</div>

<script>
function showReceiptModal(src) {
    document.getElementById('receiptModalImage').src = src;
    document.getElementById('receiptModal').classList.add('show');
}

function hideReceiptModal() {
    document.getElementById('receiptModal').classList.remove('show');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideReceiptModal();
    }
});
</script>
