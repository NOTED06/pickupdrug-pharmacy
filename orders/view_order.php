<?php 
require_once(__DIR__ . '/../config.php');

$customer_id = intval($_settings->userdata('id'));
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($order_id > 0 && $customer_id > 0){
  
  $stmt = $conn->prepare("
      SELECT o.*, 
             o.pickup_date,     
             o.pickup_time,
             o.status,
             o.date_created
      FROM `order_list` o 
      WHERE o.id = ? 
        AND o.customer_id = ?
  ");
  $stmt->bind_param('ii', $order_id, $customer_id);
  $stmt->execute();
  $qry = $stmt->get_result();

    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }else{
        echo '<div class="p-4 text-center">
            <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
            <h5>Order Not Found</h5>
            <p class="text-muted">You do not have permission to view this order or it does not exist.</p>
            <button type="button" class="btn btn-secondary rounded-pill px-4 mt-2" data-dismiss="modal">Close</button>
        </div>';
        exit;
    }
}else{
    echo '<div class="p-4 text-center">
        <i class="fas fa-lock text-warning fa-3x mb-3"></i>
        <h5>Access Denied</h5>
        <p class="text-muted">Please log in to view order details.</p>
        <button type="button" class="btn btn-secondary rounded-pill px-4 mt-2" data-dismiss="modal">Close</button>
    </div>';
    exit;
}

$gt = 0;
$order_items_for_total = $conn->query("SELECT o.*, p.price FROM `order_items` o inner join product_list p on o.product_id = p.id where order_id = '{$id}' ");
while($row = $order_items_for_total->fetch_assoc()){
    $gt += $row['price'] * $row['quantity'];
}
?>

<style>
:root {
    --primary: #0d9488;
    --primary-light: #14b8a6;
    --primary-dark: #0f766e;
    --primary-bg: rgba(13, 148, 136, 0.08);
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --text-dark: #1f2937;
    --text-muted: #6b7280;
    --border: #e5e7eb;
    --bg-light: #f9fafb;
}

#uni_modal .modal-footer { display: none !important; }
#uni_modal .modal-dialog { max-width: 720px !important; }
#uni_modal .modal-body { padding: 0 !important; max-height: 85vh; overflow-y: auto; }
#uni_modal .modal-content { border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }

.order-modal-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 24px 28px;
    color: white;
    position: relative;
    overflow: hidden;
}

.order-modal-header::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    position: relative;
    z-index: 1;
}

.order-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.order-title-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.order-title h4 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
}

.order-title p {
    margin: 4px 0 0;
    font-size: 0.85rem;
    opacity: 0.85;
}

.btn-close-modal {
    background: rgba(255,255,255,0.15);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-close-modal:hover {
    background: rgba(255,255,255,0.25);
    transform: rotate(90deg);
}

.order-summary-bar {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
    position: relative;
    z-index: 1;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-item span:first-child {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.8;
    margin-bottom: 4px;
}

.summary-item span:last-child {
    font-weight: 700;
    font-size: 1rem;
}

.status-timeline-section {
    padding: 20px 28px;
    background: var(--bg-light);
    border-bottom: 1px solid var(--border);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.timeline-header h5 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.current-status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-pending { background: #fef3c7; color: #92400e; }
.badge-packed { background: #dbeafe; color: #1e40af; }
.badge-ready { background: #d1fae5; color: #065f46; }
.badge-paid { background: #ccfbf1; color: var(--primary-dark); }
.badge-cancelled { background: #fee2e2; color: #991b1b; }

.rx-validation-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 18px 20px;
    margin-bottom: 18px;
}
.rx-validation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.rx-validation-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}
.rx-validation-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.rx-validation-badge.matched { background: #d1fae5; color: #065f46; }
.rx-validation-badge.partial { background: #fed7aa; color: #92400e; }
.rx-validation-badge.mismatch { background: #fecaca; color: #991b1b; }
.rx-validation-badge.invalid { background: #e5e7eb; color: #374151; }
.rx-validation-summary {
    background: #f9fafb;
    border-radius: 12px;
    padding: 12px;
    font-size: 0.85rem;
    color: var(--text-dark);
    margin-bottom: 12px;
}
.rx-validation-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.rx-validation-list {
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px;
}
.rx-validation-list h6 {
    margin: 0 0 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-dark);
}
.rx-validation-list ul {
    margin: 0;
    padding-left: 18px;
    font-size: 0.82rem;
    color: var(--text-muted);
}
.rx-validation-list li {
    margin-bottom: 4px;
}

.timeline-track {
    display: flex;
    justify-content: space-between;
    position: relative;
    padding: 0 10px;
}

.timeline-track::before {
    content: "";
    position: absolute;
    top: 20px;
    left: 30px;
    right: 30px;
    height: 3px;
    background: #e5e7eb;
    border-radius: 2px;
}

.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: 3px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    font-size: 0.9rem;
    color: #9ca3af;
}

.timeline-step.active .step-icon {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
    box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.2);
}

.timeline-step.completed .step-icon {
    background: var(--success);
    border-color: var(--success);
    color: white;
}

.step-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #9ca3af;
    margin-top: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.timeline-step.active .step-label,
.timeline-step.completed .step-label {
    color: var(--text-dark);
}

.order-content {
    padding: 24px 28px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.info-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;
}

.info-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.1);
}

.info-card.highlight {
    background: linear-gradient(135deg, var(--primary-bg) 0%, white 100%);
    border-color: rgba(13, 148, 136, 0.3);
}

.info-card-icon {
    width: 36px;
    height: 36px;
    background: var(--primary-bg);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    margin-bottom: 12px;
    font-size: 1rem;
}

.info-card-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    font-weight: 600;
    margin-bottom: 4px;
}

.info-card-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-dark);
}

.branch-card {
    grid-column: span 2;
    background: linear-gradient(135deg, #f0fdfa 0%, white 100%);
    border: 2px dashed rgba(13, 148, 136, 0.3);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.branch-icon-wrap {
    width: 50px;
    height: 50px;
    background: var(--primary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.branch-details h5 {
    margin: 0 0 8px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-dark);
}

.branch-detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.branch-detail-item i {
    color: var(--primary);
    width: 16px;
}

.items-section {
    margin-top: 24px;
}

.items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.items-header h5 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.items-count {
    background: var(--primary-bg);
    color: var(--primary);
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px;
    transition: all 0.2s;
}

.item-card:hover {
    border-color: var(--primary);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.item-image {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid var(--border);
    flex-shrink: 0;
}

.item-info {
    flex: 1;
    min-width: 0;
}

.item-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-dark);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-meta {
    display: flex;
    gap: 12px;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.item-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.item-qty-price {
    background: var(--primary-bg);
    padding: 6px 12px;
    border-radius: 8px;
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--primary);
    font-weight: 600;
}

.item-total {
    text-align: right;
    flex-shrink: 0;
}

.item-total-label {
    font-size: 0.7rem;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-bottom: 2px;
}

.item-total-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
}

.grand-total-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 14px;
    padding: 20px 24px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}

.grand-total-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.total-icon {
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.grand-total-label {
    font-size: 0.8rem;
    opacity: 0.9;
    margin-bottom: 2px;
}

.grand-total-value {
    font-size: 1.8rem;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.cancel-section {
    margin-top: 20px;
    padding: 16px;
    background: #fef2f2;
    border-radius: 12px;
    border: 1px solid #fecaca;
}

.btn-cancel-order {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: center;
}

.btn-cancel-order:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

.cancel-timer {
    text-align: center;
    font-size: 0.8rem;
    color: #991b1b;
    margin-top: 10px;
}

@media (max-width: 768px) {
    .order-modal-header { padding: 20px; }
    .order-content { padding: 20px; }
    .info-grid { grid-template-columns: 1fr; }
    .branch-card { grid-column: span 1; flex-direction: column; }
    .order-summary-bar { flex-wrap: wrap; gap: 12px; }
    .timeline-track { flex-wrap: wrap; gap: 10px; justify-content: center; }
    .timeline-track::before { display: none; }
    .item-card { flex-wrap: wrap; }
    .item-total { width: 100%; text-align: left; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border); }
    .grand-total-card { flex-direction: column; text-align: center; gap: 12px; }
}

.btn-save-image {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: #ffffff !important;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s ease;
    backdrop-filter: blur(4px);
    white-space: nowrap;
}
.btn-save-image:hover {
    background: rgba(255, 255, 255, 0.35);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}
.btn-save-image:active {
    transform: translateY(0);
}
.modal-footer-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
    gap: 12px;
    flex-wrap: wrap;
}
.btn-save-image-bottom {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #ffffff !important;
    border: none;
    padding: 11px 22px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
    transition: all 0.2s ease;
}
.btn-save-image-bottom:hover {
    background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
    box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35);
    transform: translateY(-1px);
}
.btn-close-modal-bottom {
    background: #f3f4f6;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    padding: 11px 20px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}
.btn-close-modal-bottom:hover {
    background: #e5e7eb;
    color: #1f2937;
}
@media (max-width: 576px) {
    .btn-save-image .btn-text {
        display: none;
    }
    .btn-save-image {
        padding: 8px 10px;
    }
    .modal-footer-actions {
        flex-direction: column;
    }
    .btn-save-image-bottom, .btn-close-modal-bottom {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="order-modal-wrapper" id="orderModalWrapper">
    
    <div class="order-modal-header">
        <div class="header-top">
            <div class="order-title">
                <div class="order-title-icon">📦</div>
                <div>
                    <h4>Order Details</h4>
                    <p><?= htmlspecialchars($_settings->info('name')) ?> • Order #<?= isset($code) ? $code : 'N/A' ?></p>
                </div>
            </div>
            <div class="header-actions no-export" style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="btn-close-modal" id="closeModalBtn" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="order-summary-bar">
            <div class="summary-item">
                <span>Order Date</span>
                <span><?= isset($date_created) ? date("M d, Y", strtotime($date_created)) : 'N/A' ?></span>
            </div>
            <div class="summary-item">
                <span>Time</span>
                <span><?= isset($date_created) ? date("h:i A", strtotime($date_created)) : 'N/A' ?></span>
            </div>
            <div class="summary-item">
                <span>Total Amount</span>
                <span>₱<?= format_num($gt, 2) ?></span>
            </div>
        </div>
    </div>
    
    
    <div class="status-timeline-section">
        <div class="timeline-header">
            <h5><i class="fa fa-route"></i> Order Progress</h5>
            <?php 
            $status = isset($status) ? $status : '';
            $statusClass = '';
            $statusText = '';
            switch($status){
                case 0: $statusClass = 'badge-pending'; $statusText = '⏳ Pending'; break;
                case 1: $statusClass = 'badge-packed'; $statusText = '📦 Packed'; break;
                case 2: $statusClass = 'badge-ready'; $statusText = '✅ Ready for Pickup'; break;
                case 3: $statusClass = 'badge-paid'; $statusText = '💰 Paid & Completed'; break;
                case 4: $statusClass = 'badge-cancelled'; $statusText = '❌ Cancelled'; break;
                default: $statusClass = 'badge-pending'; $statusText = '❓ Unknown';
            }
            ?>
            <span class="current-status-badge <?= $statusClass ?>"><?= $statusText ?></span>
        </div>
        
        <div class="timeline-track">
            <div class="timeline-step <?= $status >= 0 && $status != 4 ? ($status == 0 ? 'active' : 'completed') : '' ?>">
                <div class="step-icon"><i class="fa fa-clock"></i></div>
                <span class="step-label">Pending</span>
            </div>
            <div class="timeline-step <?= $status >= 1 && $status != 4 ? ($status == 1 ? 'active' : 'completed') : '' ?>">
                <div class="step-icon"><i class="fa fa-box"></i></div>
                <span class="step-label">Packed</span>
            </div>
            <div class="timeline-step <?= $status >= 2 && $status != 4 ? ($status == 2 ? 'active' : 'completed') : '' ?>">
                <div class="step-icon"><i class="fa fa-store"></i></div>
                <span class="step-label">Ready</span>
            </div>
            <div class="timeline-step <?= $status >= 3 && $status != 4 ? 'completed' : '' ?>">
                <div class="step-icon"><i class="fa fa-check"></i></div>
                <span class="step-label">Completed</span>
            </div>
        </div>
    </div>
    
    
    <div class="order-content">
        <?php if(isset($status) && $status == 4): ?>
        <div class="alert alert-danger mb-3 d-flex align-items-start" style="background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; color:#991b1b; padding:14px 18px;">
            <i class="fa fa-ban mr-3 mt-1" style="font-size:1.3rem;"></i>
            <div>
                <strong style="font-size:1rem; display:block;">This order has been cancelled.</strong>
                <?php if(!empty($cancellation_reason)): ?>
                    <p style="margin:4px 0 0; font-size:0.9rem;"><strong>Reason:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $cancellation_reason))) ?></p>
                <?php endif; ?>
                <?php if(!empty($cancellation_notes)): ?>
                    <p style="margin:2px 0 0; font-size:0.85rem; color:#7f1d1d;"><strong>Notes:</strong> <?= htmlspecialchars($cancellation_notes) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="info-grid">
            
            <div class="info-card">
                <div class="info-card-icon"><i class="fa fa-barcode"></i></div>
                <div class="info-card-label">Order Code</div>
                <div class="info-card-value">
                    <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 6px; font-size: 0.9rem;"><?= isset($code) ? $code : 'N/A' ?></code>
                </div>
            </div>
            
            
            <div class="info-card highlight">
                <div class="info-card-icon"><i class="fa fa-calendar-check"></i></div>
                <div class="info-card-label">Pickup Schedule</div>
                <div class="info-card-value">
                    <?php if(isset($pickup_date) && !empty($pickup_date)): ?>
                        <?= date("M d, Y", strtotime($pickup_date)) ?>
                        <?php if(isset($pickup_time) && !empty($pickup_time)): ?>
                            <span style="color: var(--primary); margin-left: 8px;">
                                <i class="fa fa-clock"></i> <?= date("h:i A", strtotime($pickup_time)) ?>
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-style: italic;">Not scheduled yet</span>
                    <?php endif; ?>
                </div>
            </div>
            
            
            <div class="branch-card">
                <div class="branch-icon-wrap">
                    <i class="fa fa-store"></i>
                </div>
                <div class="branch-details">
                    <h5><?= $_settings->info('name') ?></h5>
                    <div class="branch-detail-item">
                        <i class="fa fa-map-marker-alt"></i>
                        <?= $_settings->info('address') ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $display_validation = $prescription_validation ?? '';
        if ($display_validation === 'pending_review') {
            $display_validation = 'mismatch';
        }

        $show_validation = !empty($display_validation) && $display_validation !== 'pending';
        $validation_details = [];
        $validation_summary = '';

        if ($show_validation && !empty($prescription_details)) {
            $decoded_details = json_decode($prescription_details, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_details)) {
                $validation_details = $decoded_details;
            }
        }

        if ($show_validation) {
            if (!empty($validation_details['notes'])) {
                $validation_summary = $validation_details['notes'];
            } else {
                if ($display_validation === 'matched') {
                    $validation_summary = 'All prescribed medicines are in the cart with no extra items detected.';
                } elseif ($display_validation === 'partial') {
                    $validation_summary = 'Some items match, but there are missing or extra items in the cart.';
                } elseif ($display_validation === 'mismatch') {
                    $summary_parts = [];
                    if (!empty($validation_details['missing_from_cart'])) {
                        $summary_parts[] = 'Missing from cart: ' . implode(', ', $validation_details['missing_from_cart']);
                    }
                    if (!empty($validation_details['extra_in_cart'])) {
                        $summary_parts[] = 'Extra in cart: ' . implode(', ', $validation_details['extra_in_cart']);
                    }
                    $validation_summary = !empty($summary_parts) ? implode('. ', $summary_parts) . '.' : 'Prescription does not match the cart items.';
                } elseif ($display_validation === 'invalid') {
                    $validation_summary = 'The uploaded image does not appear to be a valid prescription.';
                }
            }
        }
        ?>

        <?php if ($show_validation): ?>
        <div class="rx-validation-card">
            <div class="rx-validation-header">
                <div class="rx-validation-title">
                    <i class="fa fa-file-medical"></i> Prescription Validation
                </div>
                <span class="rx-validation-badge <?= htmlspecialchars($display_validation) ?>">
                    <?= htmlspecialchars($display_validation) ?>
                </span>
            </div>
            <?php if (!empty($validation_summary)): ?>
            <div class="rx-validation-summary">
                <?= htmlspecialchars($validation_summary) ?>
            </div>
            <?php endif; ?>
            <div class="rx-validation-grid">
                <div class="rx-validation-list">
                    <h6>Prescribed Medicines</h6>
                    <ul>
                        <?php if (!empty($validation_details['prescribed_medicines'])): ?>
                            <?php foreach ($validation_details['prescribed_medicines'] as $med): ?>
                                <li><?= htmlspecialchars($med) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No medicines detected.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="rx-validation-list">
                    <h6>Matched Cart Products</h6>
                    <ul>
                        <?php if (!empty($validation_details['matched_products'])): ?>
                            <?php foreach ($validation_details['matched_products'] as $prod): ?>
                                <li><?= htmlspecialchars($prod) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No matched products.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php if (!empty($validation_details['missing_from_cart'])): ?>
                <div class="rx-validation-list">
                    <h6>Missing From Cart</h6>
                    <ul>
                        <?php foreach ($validation_details['missing_from_cart'] as $med): ?>
                            <li><?= htmlspecialchars($med) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if (!empty($validation_details['extra_in_cart'])): ?>
                <div class="rx-validation-list">
                    <h6>Extra In Cart</h6>
                    <ul>
                        <?php foreach ($validation_details['extra_in_cart'] as $prod): ?>
                            <li><?= htmlspecialchars($prod) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        
        <div class="items-section">
            <div class="items-header">
                <h5><i class="fa fa-shopping-bag"></i> Order Items</h5>
                <?php 
                $order_items = $conn->query("SELECT o.*, p.name as product, p.brand as brand, p.price, cc.name as category, p.image_path FROM `order_items` o inner join product_list p on o.product_id = p.id inner join category_list cc on p.category_id = cc.id where order_id = '{$id}' ");
                ?>
                <span class="items-count"><?= $order_items->num_rows ?> item<?= $order_items->num_rows > 1 ? 's' : '' ?></span>
            </div>
            
            <div class="items-list">
                <?php 
                $order_items->data_seek(0);
                while($row = $order_items->fetch_assoc()):
                ?>
                <div class="item-card">
                    <img src="<?= validate_image($row['image_path']) ?>" alt="<?= $row['product'] ?>" class="item-image">
                    <div class="item-info">
                        <div class="item-name"><?= $row['product'] ?></div>
                        <div class="item-meta">
                            <span><i class="fa fa-tag"></i> <?= $row['brand'] ?></span>
                            <span><i class="fa fa-folder"></i> <?= $row['category'] ?></span>
                        </div>
                        <div class="item-qty-price">
                            <i class="fa fa-times"></i> <?= format_num($row['quantity'], 0) ?> × ₱<?= format_num($row['price'], 2) ?>
                        </div>
                    </div>
                    <div class="item-total">
                        <div class="item-total-label">Subtotal</div>
                        <div class="item-total-value">₱<?= format_num($row['price'] * $row['quantity'], 2) ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <?php if($order_items->num_rows <= 0): ?>
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <i class="fa fa-shopping-cart fa-2x" style="opacity: 0.3; margin-bottom: 10px;"></i>
                    <p>No items found in this order</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        
        <div class="grand-total-card">
            <div class="grand-total-left">
                <div class="total-icon">💰</div>
                <div>
                    <div class="grand-total-label">Grand Total</div>
                    <div class="grand-total-value">₱<?= format_num($gt, 2) ?></div>
                </div>
            </div>
        </div>
        
        
        <?php if(isset($status) && $status == 0): ?>
        <div class="cancel-section" id="cancelSection" style="display: none;">
            <button class="btn-cancel-order" id="cancelOrderBtn" data-order-id="<?= $id ?>" data-order-date="<?= $date_created ?>">
                <i class="fa fa-times-circle"></i>
                Cancel Order
            </button>
            <div class="cancel-timer" id="cancelTimer"></div>
        </div>
        <?php endif; ?>

        
        <div class="modal-footer-actions no-export">
            <button type="button" class="btn-save-image-bottom" id="saveOrderImageBtnBottom">
                <i class="fa-solid fa-camera"></i> Save as Image
            </button>
            <button type="button" class="btn-close-modal-bottom" data-dismiss="modal">
                <i class="fa-solid fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    $('#closeModalBtn, .btn-close-modal-bottom').on('click', function() {
        $('#uni_modal').modal('hide');
    });
    
    
    $('#saveOrderImageBtnBottom').on('click', function(e) {
        e.preventDefault();
        saveOrderDetailsAsImage();
    });

    
    <?php if(isset($status) && $status == 0): ?>
    checkCancellationWindow();
    <?php endif; ?>
});

function saveOrderDetailsAsImage() {
    const $btn = $('#saveOrderImageBtnBottom');
    const bottomOrigHtml = $btn.html();
    
    $btn.prop('disabled', true);
    $btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Generating Image...');
    
    function performCapture() {
        const modalElement = document.getElementById('orderModalWrapper');
        if (!modalElement) {
            resetButtons();
            if (typeof alert_toast === 'function') {
                alert_toast('Could not find order details to capture', 'error');
            }
            return;
        }
        
        const modalBody = modalElement.closest('.modal-body');
        const prevOverflow = modalBody ? modalBody.style.overflow : '';
        const prevMaxHeight = modalBody ? modalBody.style.maxHeight : '';
        
        
        if (modalBody) {
            modalBody.style.overflow = 'visible';
            modalBody.style.maxHeight = 'none';
        }
        
        
        $('.no-export, .btn-close-modal, #cancelSection').addClass('d-none');
        
        
        const images = modalElement.querySelectorAll('img');
        const imgPromises = Array.from(images).map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = resolve;
                img.onerror = resolve;
            });
        });
        
        Promise.all(imgPromises).then(() => {
            return html2canvas(modalElement, {
                scale: 2, 
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                scrollX: 0,
                scrollY: 0,
                windowWidth: modalElement.scrollWidth || 720
            });
        }).then(canvas => {
            
            if (modalBody) {
                modalBody.style.overflow = prevOverflow;
                modalBody.style.maxHeight = prevMaxHeight;
            }
            $('.no-export, .btn-close-modal, #cancelSection').removeClass('d-none');
            resetButtons();
            
            const orderCode = '<?= isset($code) ? htmlspecialchars($code) : "order" ?>';
            const fileName = `Order_${orderCode}_Details.png`;
            
            
            try {
                canvas.toBlob(blob => {
                    if (!blob) {
                        downloadCanvas(canvas, fileName);
                        return;
                    }
                    const file = new File([blob], fileName, { type: 'image/png' });
                    if (navigator.canShare && navigator.canShare({ files: [file] }) && /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)) {
                        navigator.share({
                            files: [file],
                            title: `Order #${orderCode} Details`,
                            text: `Order #${orderCode} Details`
                        }).catch(() => {
                            downloadCanvas(canvas, fileName);
                        });
                    } else {
                        downloadCanvas(canvas, fileName);
                    }
                }, 'image/png');
            } catch(e) {
                downloadCanvas(canvas, fileName);
            }
            
            if (typeof showToast === 'function') {
                showToast('Order Details saved as image!', 'success');
            } else if (typeof alert_toast === 'function') {
                alert_toast('Order Details saved as image!', 'success');
            }
        }).catch(err => {
            if (modalBody) {
                modalBody.style.overflow = prevOverflow;
                modalBody.style.maxHeight = prevMaxHeight;
            }
            $('.no-export, .btn-close-modal, #cancelSection').removeClass('d-none');
            resetButtons();
            console.error('[html2canvas] Error generating image:', err);
            if (typeof alert_toast === 'function') {
                alert_toast('Failed to save image. Please try again.', 'error');
            }
        });
    }
    
    function resetButtons() {
        $btn.prop('disabled', false);
        $('#saveOrderImageBtnBottom').html(bottomOrigHtml);
    }
    
    function downloadCanvas(canvas, fileName) {
        const link = document.createElement('a');
        link.download = fileName;
        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        setTimeout(() => {
            if (link.parentNode) link.parentNode.removeChild(link);
        }, 200);
    }
    
    if (typeof html2canvas === 'undefined') {
        const script = document.createElement('script');
        script.src = '<?= base_url ?>plugins/html2canvas.min.js';
        script.onload = performCapture;
        script.onerror = function() {
            
            const cdn = document.createElement('script');
            cdn.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            cdn.onload = performCapture;
            cdn.onerror = function() {
                resetButtons();
                if (typeof alert_toast === 'function') {
                    alert_toast('Failed to load image capture library', 'error');
                }
            };
            document.head.appendChild(cdn);
        };
        document.head.appendChild(script);
    } else {
        performCapture();
    }
}

function checkCancellationWindow() {
    const section = $('#cancelSection');
    const cancelBtn = $('#cancelOrderBtn');
    const timerEl = $('#cancelTimer');
    const orderId = cancelBtn.data('order-id');
    const orderDateStr = cancelBtn.data('order-date');

    const orderDate = new Date(orderDateStr.replace(' ', 'T'));
    if (isNaN(orderDate.getTime())) return;
    
    
    const cutoffDate = new Date(orderDate.getTime() + (15 * 60 * 1000)); 

    const now = new Date();
    const isCancellable = now < cutoffDate;
    
    if (isCancellable) {
        section.show();
        
        const formatTime = (ms) => {
            const totalSeconds = Math.floor(ms / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        };
        
        const updateTimer = () => {
            const ms = cutoffDate.getTime() - new Date().getTime();
            if (ms <= 0) {
                clearInterval(timerInterval);
                section.hide();
                timerEl.html('⏱️ <strong style="color: #991b1b;">Cancellation window expired</strong>');
                cancelBtn.attr('disabled', true).html('<i class="fa fa-ban"></i> Cannot Cancel');
                return;
            }
            timerEl.html(`⏱️ Time remaining to cancel: <strong>${formatTime(ms)}</strong>`);
        };

        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);

        cancelBtn.off('click').on('click', function() {
            var proceedCancellation = function() {
                var postData = 'order_id=' + encodeURIComponent(orderId) + '&reason=' + encodeURIComponent('Cancelled by customer');
                if (typeof _csrf_token_ !== 'undefined') {
                    postData += '&csrf_token=' + encodeURIComponent(_csrf_token_);
                }
                var cancelUrl = (typeof _base_url_ !== 'undefined' ? _base_url_ : '') + 'ajax.php?action=customer_cancel_order';
                $.ajax({
                    url: cancelUrl,
                    method: 'POST',
                    data: postData,
                    dataType: 'json',
                    beforeSend: function() {
                        cancelBtn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cancelling...');
                    },
                    success: function(resp) {
                        if(resp.status == 'success') {
                            if(typeof showToast === 'function') {
                                showToast('Order cancelled successfully', 'success');
                            } else if(typeof alert_toast === 'function') {
                                alert_toast('Order cancelled successfully', 'success');
                            }
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            if(typeof showToast === 'function') {
                                showToast(resp.msg || 'Cancellation failed', 'error');
                            } else if(typeof alert_toast === 'function') {
                                alert_toast(resp.msg || 'Cancellation failed', 'error');
                            }
                            cancelBtn.attr('disabled', false).html('<i class="fa fa-times-circle"></i> Cancel Order');
                        }
                    },
                    error: function() {
                        if(typeof showToast === 'function') {
                            showToast('An error occurred', 'error');
                        } else if(typeof alert_toast === 'function') {
                            alert_toast('An error occurred', 'error');
                        }
                        cancelBtn.attr('disabled', false).html('<i class="fa fa-times-circle"></i> Cancel Order');
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Cancel Order?',
                    text: 'Are you sure you want to cancel this order? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fa fa-times-circle"></i> Yes, cancel order',
                    cancelButtonText: 'Keep Order'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        proceedCancellation();
                    }
                });
            } else if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                proceedCancellation();
            }
        });
    }
}
</script>
