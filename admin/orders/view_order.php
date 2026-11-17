<?php
require_once('../../config.php');

if (class_exists('SecurityHelper')) {
    SecurityHelper::generateCsrfToken();
}

if(isset($_GET['id']) && $_GET['id'] > 0){
    
    $stmt = $conn->prepare("
        SELECT o.*, 
               o.pickup_date, 
               o.pickup_time,
               c.firstname,
               c.lastname,
               c.email,
               c.contact,
               b.branch_name,
               b.branch_address
        FROM `order_list` o 
        LEFT JOIN `customer_list` c ON o.customer_id = c.id
        LEFT JOIN `branch_list` b ON o.branch_id = b.id
        WHERE o.id = ? AND (o.deleted_at IS NULL OR o.deleted_at = '')
    ");
    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();
    $qry = $stmt->get_result();
    
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<style>
    
    #uni_modal .modal-footer #submit {
        display: none !important;
    }
    #uni_modal .modal-footer .btn-secondary {
        background: #4b5563 !important;
        color: #fff !important;
        border: none !important;
        border-radius: 6px !important;
        padding: 6px 20px !important;
        font-weight: 600 !important;
    }
    .payment-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }
    .pay-badge-paid { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .pay-badge-unpaid { background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; }
    .pay-badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .pay-badge-failed { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .product-logo{
        width: 3em;
        height: 3em;
        object-fit: cover;
        object-position: center center;
    }
    .order-info-label {
        font-size: 0.85em;
        color: #6c757d;
        margin-bottom: 0;
    }
    .order-info-value {
        font-weight: 500;
        font-size: 1em;
    }
    .cart-item {
        padding: 0.5rem 1rem;
    }
    @keyframes pulse-urgent {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    #updateStatusModal .modal-dialog {
        max-width: 400px !important;
        width: 92% !important;
        margin: 1.75rem auto !important;
    }
    #cancelOrderModal .modal-dialog {
        max-width: 480px !important;
        width: 92% !important;
        margin: 1.75rem auto !important;
    }
    #pickupModal .modal-dialog {
        max-width: 440px !important;
        width: 92% !important;
        margin: 1.75rem auto !important;
    }
    #updateStatusModal .modal-content,
    #cancelOrderModal .modal-content,
    #pickupModal .modal-content {
        border-radius: 12px !important;
        border: none !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25) !important;
        overflow: hidden !important;
    }
</style>
<div class="container-fluid printout">
    <div class="row mb-3 align-items-center d-print-none">
        <div class="col-md-6">
            <h5 class="m-0 text-secondary">Reference: <span class="text-dark"><?= isset($code) ? $code : '' ?></span>
            <?php if(!empty($is_urgent)): ?>
                <span class="badge badge-danger ml-2" style="font-size: 0.75em; vertical-align: middle; animation: pulse-urgent 1.5s ease-in-out infinite;"><i class="fas fa-bolt"></i> URGENT ORDER</span>
            <?php endif; ?>
            </h5>
        </div>
        <div class="col-md-6 text-right">
             <?php if(isset($status) && $status < 4): ?>
                <?php 
                
                $update_disabled = ($status == 3) ? 'disabled title="Cannot update a completed order"' : '';
                
                $cancel_disabled = ($status == 3) ? 'disabled title="Cannot cancel a completed order"' : '';
                ?>
                <button class="btn btn-info btn-sm btn-flat" type="button" id="update_status" <?= $update_disabled ?>><i class="fa fa-edit"></i> Update Status</button>
                <button class="btn btn-warning btn-sm btn-flat" type="button" id="cancel_order" <?= $cancel_disabled ?>><i class="fa fa-ban"></i> Cancel Order</button>
            <?php endif; ?>
            <button class="btn btn-navy btn-sm btn-flat" type="button" id="print"><i class="fa fa-print"></i> Print</button>
            <?php if(isset($status) && ($status == 3 || $status == 4)): ?>
            <button class="btn btn-danger btn-sm btn-flat" type="button" id="delete_data"><i class="fa fa-trash"></i> Delete</button>
            <?php endif; ?>
        </div>
    </div>
    <hr class="d-print-none">

    <?php if(isset($status) && $status == 4): ?>
    <div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-start shadow-sm" style="border-radius: 8px; border-left: 4px solid #dc3545; background-color: #fef2f2; color: #7f1d1d !important;">
        <i class="fa fa-ban fa-lg mt-1 mr-2 text-danger"></i>
        <div>
            <strong class="text-danger" style="font-size: 1rem;">Order Cancelled</strong>
            <?php if(!empty($date_cancelled)): ?>
                <span class="small ml-2" style="color: #6b7280 !important;">(<?= date("M d, Y h:i A", strtotime($date_cancelled)) ?>)</span>
            <?php endif; ?>
            <?php if(!empty($cancellation_reason)): ?>
                <div class="mt-1 small" style="color: #1f2937 !important;">
                    <strong style="color: #111827 !important;">Reason:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $cancellation_reason))) ?>
                </div>
            <?php endif; ?>
            <?php if(!empty($cancellation_notes)): ?>
                <div class="small mt-1" style="color: #4b5563 !important;">
                    <strong style="color: #111827 !important;">Notes:</strong> <?= htmlspecialchars($cancellation_notes) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="mb-2">
                <p class="order-info-label"><i class="fa fa-calendar mr-1"></i>Order Date</p>
                <div class="order-info-value"><?= isset($date_created) ? date("F d, Y h:i A", strtotime($date_created)) : 'N/A' ?></div>
            </div>
            
            <?php if(isset($firstname) && isset($lastname)): ?>
            <div class="mb-2">
                <p class="order-info-label"><i class="fa fa-user mr-1"></i>Customer</p>
                <div class="order-info-value">
                    <strong><?= htmlspecialchars($firstname . ' ' . $lastname) ?></strong>
                    <?php if(isset($contact) && !empty($contact)): ?>
                    <br><small class="text-muted"><i class="fa fa-phone mr-1"></i><?= htmlspecialchars($contact) ?></small>
                    <?php endif; ?>
                    <?php if(isset($email) && !empty($email)): ?>
                    <br><small class="text-muted"><i class="fa fa-envelope mr-1"></i><?= htmlspecialchars($email) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php
            $pay_method = strtolower($payment_method ?? 'cash');
            $pay_status = strtolower($payment_status ?? '');
            if (empty($pay_status)) {
                $pay_status = ($pay_method === 'cash') ? 'unpaid' : 'pending';
            }
            if ($pay_status === 'pending_payment' || $pay_status === 'pending_verification') {
                $pay_status = 'pending';
            }
            
            $method_labels = [
                'gcash' => ['name' => 'GCash', 'icon' => 'fa-mobile-alt', 'color' => '#007dfe'],
                'maya'  => ['name' => 'Maya', 'icon' => 'fa-wallet', 'color' => '#00d632'],
                'cash'  => ['name' => 'Cash on Pickup', 'icon' => 'fa-money-bill-wave', 'color' => '#4b5563'],
            ];
            $cur_method = $method_labels[$pay_method] ?? ['name' => ucfirst($pay_method), 'icon' => 'fa-credit-card', 'color' => '#4b5563'];
            
            $status_classes = [
                'paid' => 'pay-badge-paid',
                'unpaid' => 'pay-badge-unpaid',
                'pending' => 'pay-badge-pending',
                'failed' => 'pay-badge-failed'
            ];
            $status_icons = [
                'paid' => 'fa-check-circle',
                'unpaid' => 'fa-clock',
                'pending' => 'fa-hourglass-half',
                'failed' => 'fa-times-circle'
            ];
            $badge_class = $status_classes[$pay_status] ?? 'pay-badge-unpaid';
            $badge_icon = $status_icons[$pay_status] ?? 'fa-clock';
            ?>
            <div class="mb-2">
                <p class="order-info-label"><i class="fa fa-credit-card mr-1"></i>Payment Information</p>
                <div class="order-info-value mt-1">
                    <span class="badge badge-light border px-2 py-1 mr-1" style="font-size:0.85em; font-weight:600;">
                        <i class="fas <?= $cur_method['icon'] ?> mr-1" style="color: <?= $cur_method['color'] ?>;"></i><?= $cur_method['name'] ?>
                    </span>
                    <span class="payment-badge <?= $badge_class ?>">
                        <i class="fas <?= $badge_icon ?>"></i> <?= strtoupper($pay_status) ?>
                    </span>
                    <?php if(!empty($paymongo_payment_id)): ?>
                        <div class="small text-muted mt-1 font-monospace">
                            <i class="fas fa-hashtag mr-1"></i>Ref: <?= htmlspecialchars($paymongo_payment_id) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="mb-2">
                <p class="order-info-label">Status</p>
                <div class="order-info-value">
                <?php 
                $status = isset($status) ? $status : '';
                    switch($status){
                        case 0:
                            echo '<span class="badge badge-secondary bg-gradient-secondary px-3 rounded-pill">Pending</span>';
                            break;
                        case 1:
                            echo '<span class="badge badge-primary bg-gradient-primary px-3 rounded-pill">Packed</span>';
                            break;
                        case 2:
                            echo '<span class="badge badge-warning bg-gradient-warning px-3 rounded-pill">Ready for Pick Up</span>';
                            break;
                        case 3:
                            echo '<span class="badge badge-teal bg-gradient-teal px-3 rounded-pill">Done</span>';
                            break;
                        case 4:
                            echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill">Cancelled</span>';
                            break;
                        default:
                            echo '<span class="badge badge-light bg-gradient-light border dark px-3 rounded-pill">N/A</span>';
                            break;
                    }
                ?>
                </div>
            </div>
            
            <div class="mb-2">
                <p class="order-info-label"><i class="fa fa-calendar-check mr-1"></i>Scheduled Pickup</p>
                <div class="pl-0">
                    <?php if(isset($pickup_date) && !empty($pickup_date)): ?>
                        <div class="d-flex flex-column">
                            <div>
                                <strong class="text-danger"><?= date("F d, Y", strtotime($pickup_date)) ?></strong>
                                <?php if(isset($pickup_time) && !empty($pickup_time)): ?>
                                <span class="text-danger ml-1">@ <?= date("h:i A", strtotime($pickup_time)) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php
                            $pickup_datetime = strtotime($pickup_date . ' ' . $pickup_time);
                            $now = time();
                            $time_diff = $pickup_datetime - $now;
                            $days_until = floor($time_diff / (60 * 60 * 24));
                            
                            if($time_diff > 0): 
                            ?>
                                <small class="text-muted">
                                    (In <?= $days_until > 0 ? $days_until . " days" : "less than a day" ?>)
                                </small>
                            <?php endif; ?>
                        </div>
                        <?php $pickup_disabled = (isset($status) && ($status == 3 || $status == 4 || $status == 5)) ? 'disabled title="Cannot modify schedule for this order"' : ''; ?>
                        <button type="button" class="btn btn-xs btn-outline-danger mt-1 d-print-none" id="set_pickup_btn" <?= $pickup_disabled ?>>
                            <i class="fa fa-edit mr-1"></i>Modify
                        </button>
                    <?php else: ?>
                        <div class="text-muted mb-1"><em>Not scheduled</em></div>
                        <?php $pickup_disabled = (isset($status) && ($status == 3 || $status == 4 || $status == 5)) ? 'disabled title="Cannot set schedule for this order"' : ''; ?>
                        <button type="button" class="btn btn-xs btn-danger d-print-none" id="set_pickup_btn" <?= $pickup_disabled ?>>
                            <i class="fa fa-calendar-plus mr-1"></i>Set Schedule
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $display_branch = '';
            $display_address = '';
            if(!empty($branch_name)){
                $display_branch = $branch_name;
                $display_address = $branch_address ?? '';
            } elseif(!empty($delivery_address)){
                $parts = explode(' on ', $delivery_address);
                $display_branch = trim(str_ireplace('PICKUP:', '', $parts[0]));
            }
            if(empty($display_branch)){
                $display_branch = $_settings->info('name');
                $display_address = $_settings->info('address');
            }
            ?>
            <div class="mb-2">
                <p class="order-info-label"><i class="fa fa-map-marker-alt mr-1"></i>Pickup Branch</p>
                <div class="order-info-value mt-1">
                    <strong class="text-dark"><i class="fa fa-store mr-1 text-teal" style="color: #0d9488;"></i><?= htmlspecialchars($display_branch) ?></strong>
                    <?php if(!empty($display_address)): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($display_address) ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            
            $has_prescription = false;
            $prescription_validation = null;
            $prescription_details = null;
            $prescription_image = null;
            
            if (isset($id)) {
                $stmt = $conn->prepare("SELECT prescription_image, prescription_validation, prescription_details FROM order_list WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $prescription_query = $stmt->get_result();
                if ($prescription_query && $prescription_query->num_rows > 0) {
                    $prescription_data = $prescription_query->fetch_assoc();
                    $prescription_image = $prescription_data['prescription_image'];
                    if (!empty($prescription_data['prescription_validation']) && $prescription_data['prescription_validation'] !== 'pending') {
                        $has_prescription = true;
                        $prescription_validation = $prescription_data['prescription_validation'];
                        $prescription_details = $prescription_data['prescription_details'];
                    }
                    
                    if (empty($prescription_image) && isset($customer_id)) {
                        $stmt2 = $conn->prepare("SELECT image_path FROM customer_prescriptions WHERE customer_id = ? ORDER BY uploaded_at DESC LIMIT 1");
                        if($stmt2){
                            $stmt2->bind_param('i', $customer_id);
                            $stmt2->execute();
                            $res = $stmt2->get_result();
                            if($res && $res->num_rows > 0){
                                $row = $res->fetch_assoc();
                                $prescription_image = $row['image_path'];
                            }
                            $stmt2->close();
                        }
                    }
                    $stmt->close();
                }
            }
            
            if ($has_prescription):
            ?>
            <div class="mb-2">
                <p class="order-info-label"><i class="fas fa-prescription mr-1"></i>Prescription Status</p>
                <div class="order-info-value">
                    <?php
                    switch($prescription_validation) {
                        case 'matched':
                            echo '<span class="badge badge-success bg-gradient-success px-3 rounded-pill"><i class="fas fa-check-circle"></i> Perfect Match</span>';
                            break;
                        case 'partial':
                            echo '<span class="badge badge-warning bg-gradient-warning px-3 rounded-pill"><i class="fas fa-exclamation-triangle"></i> Partial Match</span>';
                            break;
                        case 'mismatch':
                            echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill"><i class="fas fa-times-circle"></i> Mismatch</span>';
                            break;
                        case 'invalid':
                            echo '<span class="badge badge-secondary bg-gradient-secondary px-3 rounded-pill"><i class="fas fa-question-circle"></i> Invalid</span>';
                            break;
                        default:
                            echo '<span class="badge badge-light bg-gradient-light border dark px-3 rounded-pill">Pending Review</span>';
                            break;
                    }
                    ?>
                    <button type="button" class="btn btn-xs btn-info mt-1 d-print-none" id="view_prescription_btn">
                        <i class="fas fa-file-prescription mr-1"></i>View Details
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <p class="order-info-label mb-2">Order Items</p>
            <div class="list-group" id="item_list">
                <?php 
                $gt = 0;
                $today = date("Y-m-d");
                $stmt = $conn->prepare("
                    SELECT 
                        o.*, 
                        p.name as product, 
                        p.brand as brand, 
                        p.price, 
                        p.dose,
                        p.unit,
                        cc.name as category, 
                        p.image_path, 
                        COALESCE((
                            SELECT SUM(quantity) 
                            FROM stock_list 
                            WHERE product_id = p.id 
                            AND (expiration IS NULL OR DATE(expiration) > ?)
                        ), 0) as available 
                    FROM order_items o 
                    INNER JOIN product_list p ON o.product_id = p.id 
                    INNER JOIN category_list cc ON p.category_id = cc.id 
                    WHERE o.order_id = ?
                ");
                $stmt->bind_param("si", $today, $id);
                $stmt->execute();
                $order_items = $stmt->get_result();
                while($row = $order_items->fetch_assoc()):
                    $gt += $row['price'] * $row['quantity'];
                ?>
                <div class="list-group-item cart-item">
                    <div class="d-flex w-100 align-items-center">
                        <div class="mr-3">
                            <img src="<?= validate_image($row['image_path']) ?>" alt="" class="img-thumbnail border p-0 product-logo">
                        </div>
                        <div class="flex-grow-1">
                            <h6 class='mb-0 font-weight-bold'><?= htmlspecialchars($row['product']) ?></h6>
                            <small class="text-muted">
                                <?= htmlspecialchars($row['brand']) ?> | <?= htmlspecialchars($row['category']) ?>
                                <?php if(!empty($row['dose'])): ?> | <span class="text-secondary"><?= htmlspecialchars($row['dose']) ?></span><?php endif; ?>
                            </small>
                            <div class="text-muted small">
                                <?= format_num($row['quantity'],0) ?> <?= !empty($row['unit']) ? htmlspecialchars($row['unit']) . '(s)' : 'item(s)' ?> x ₱<?= format_num($row['price'],2) ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <h6 class="mb-0"><b>₱<?= format_num($row['price'] * $row['quantity'], 2) ?></b></h6>
                        </div>
                    </div>
                </div>
                <?php 
                endwhile; 
                if (isset($stmt) && $stmt) {
                    $stmt->close();
                }
                ?>
            </div>
            <?php if(isset($order_items) && $order_items && $order_items->num_rows <= 0): ?>
                <h6 class="text-center text-muted mt-3">Order Items is empty.</h6>
            <?php endif; ?>
            <div class="d-flex justify-content-end py-3">
                <div class="col-auto">
                    <h4 class="text-primary"><b>Total: <?= format_num($gt,2) ?></b></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px !important; width: 92% !important; margin: 1.75rem auto !important;">
        <div class="modal-content">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);">
                <h6 class="modal-title text-white font-weight-bold d-flex align-items-center mb-0" id="updateStatusModalLabel">
                    <i class="fas fa-clipboard-check mr-2" style="font-size: 1.1rem;"></i>Update Order Status
                </h6>
                <button type="button" class="close text-white p-2 m-0" data-dismiss="modal" aria-label="Close" style="opacity: 0.9; text-shadow: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="update-status-form">
                <input type="hidden" name="id" value="<?= isset($id) ? $id : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '' ?>">
                <div class="modal-body p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <span class="text-muted small">Order Reference:</span>
                        <span class="font-weight-bold text-dark"><?= isset($code) ? $code : '' ?></span>
                    </div>
                    <div class="form-group mb-2">
                        <label for="order_status_select" class="font-weight-600 text-sm text-secondary mb-2">
                            Select New Status <span class="text-danger">*</span>
                        </label>
                        <select class="custom-select" name="status" id="order_status_select" required="required" style="border-radius: 6px; font-weight: 500; height: calc(2.25rem + 4px);">
                            <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>⏳ Pending</option>
                            <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>📦 Packed</option>
                            <option value="2" <?= isset($status) && $status == 2 ? 'selected' : '' ?>>🛍️ Ready for Pick Up</option>
                            <option value="3" <?= isset($status) && $status == 3 ? 'selected' : '' ?>>✅ Done</option>
                        </select>
                    </div>
                    <small class="text-muted d-block mt-3" style="font-size: 0.8rem; line-height: 1.4;">
                        <i class="fas fa-info-circle text-info mr-1"></i>Customer receives an instant SMS & email notification when the status is updated.
                    </small>
                </div>
                <div class="modal-footer py-2 px-4" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-secondary px-3 shadow-sm" data-dismiss="modal" style="border-radius: 6px; font-weight: 500;">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-info text-white px-4 font-weight-bold shadow-sm" style="background-color: #17a2b8; border-color: #17a2b8; border-radius: 6px;">
                        <i class="fas fa-save mr-1"></i>Save Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelOrderModal" tabindex="-1" role="dialog" aria-labelledby="cancelOrderModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px !important; width: 92% !important; margin: 1.75rem auto !important;">
        <div class="modal-content">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                <h6 class="modal-title text-dark font-weight-bold d-flex align-items-center mb-0" id="cancelOrderModalLabel">
                    <i class="fas fa-ban mr-2" style="font-size: 1.1rem;"></i>Cancel Order
                </h6>
                <button type="button" class="close text-dark p-2 m-0" data-dismiss="modal" aria-label="Close" style="opacity: 0.9; text-shadow: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="cancel-order-form">
                <div class="modal-body p-4 bg-white">
                    <div class="p-3 mb-3 d-flex align-items-start" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 6px;">
                        <i class="fas fa-exclamation-triangle text-warning mt-1 mr-2"></i>
                        <div class="small text-dark font-weight-500" style="line-height: 1.4;">
                            Cancelling this order will restore reserved medicine quantities back into available inventory and notify the customer.
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="cancel_reason" class="font-weight-600 text-sm text-secondary mb-1">
                            Cancellation Reason <span class="text-danger">*</span>
                        </label>
                        <select class="custom-select" id="cancel_reason" name="reason" required style="border-radius: 6px; height: calc(2.25rem + 4px);">
                            <option value="">-- Select cancellation reason --</option>
                            <option value="no_prescription">No Prescription Uploaded</option>
                            <option value="invalid_prescription">Invalid / Expired Prescription</option>
                            <option value="out_of_stock">Out of Stock</option>
                            <option value="customer_request">Customer Request</option>
                            <option value="payment_issue">Payment Issue</option>
                            <option value="other">Other Reason</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="cancel_notes" class="font-weight-600 text-sm text-secondary mb-1">
                            Additional Notes <small class="text-muted">(Optional)</small>
                        </label>
                        <textarea class="form-control" id="cancel_notes" name="notes" rows="3" placeholder="Provide additional details or explanation for the customer..." style="border-radius: 6px; font-size: 0.9rem; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-4" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-secondary px-3 shadow-sm" data-dismiss="modal" style="border-radius: 6px; font-weight: 500;">Close</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark px-4 font-weight-bold shadow-sm" style="background-color: #ffc107; border-color: #ffc107; border-radius: 6px;">
                        <i class="fas fa-ban mr-1"></i>Cancel Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="pickupModal" tabindex="-1" role="dialog" aria-labelledby="pickupModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 440px !important; width: 92% !important; margin: 1.75rem auto !important;">
        <div class="modal-content">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);">
                <h6 class="modal-title text-white font-weight-bold d-flex align-items-center mb-0" id="pickupModalLabel">
                    <i class="fas fa-calendar-alt mr-2" style="font-size: 1.1rem;"></i>Set Pickup Schedule
                </h6>
                <button type="button" class="close text-white p-2 m-0" data-dismiss="modal" aria-label="Close" style="opacity: 0.9; text-shadow: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="pickup-schedule-form">
                <input type="hidden" name="id" value="<?= isset($id) ? $id : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '' ?>">
                <div class="modal-body p-4 bg-white">
                    <div class="small text-muted mb-3 pb-2 border-bottom">
                        <i class="fas fa-clock text-danger mr-1"></i>Configure pickup date and preferred collection time.
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group mb-2">
                                <label for="pickup_date" class="font-weight-600 text-sm text-secondary mb-1">
                                    Pickup Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="pickup_date" name="pickup_date" min="<?= date('Y-m-d') ?>" value="<?= isset($pickup_date) && !empty($pickup_date) ? $pickup_date : '' ?>" required style="border-radius: 6px;">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-2">
                                <label for="pickup_time" class="font-weight-600 text-sm text-secondary mb-1">
                                    Pickup Time <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control" id="pickup_time" name="pickup_time" value="<?= isset($pickup_time) && !empty($pickup_time) ? date('H:i', strtotime($pickup_time)) : '' ?>" required style="border-radius: 6px;">
                            </div>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-3" style="font-size: 0.8rem; line-height: 1.4;">
                        <i class="fas fa-paper-plane text-danger mr-1"></i>Customer will automatically receive an SMS & email with the updated collection time and branch details.
                    </small>
                </div>
                <div class="modal-footer py-2 px-4" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-secondary px-3 shadow-sm" data-dismiss="modal" style="border-radius: 6px; font-weight: 500;">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger text-white px-4 font-weight-bold shadow-sm" style="background-color: #dc3545; border-color: #dc3545; border-radius: 6px;">
                        <i class="fas fa-calendar-check mr-1"></i>Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<noscript id="print-header">
    <div>
        <div class="d-flex w-100 align-items-center">
            <div class="col-2 text-center">
                <img src="<?= validate_image($_settings->info('logo')) ?>" alt="" class="rounded-circle border" style="width: 5em;height: 5em;object-fit:cover;object-position:center center">
            </div>
            <div class="col-8">
                <div style="line-height:1em">
                    <div class="text-center font-weight-bold"><large><?= $_settings->info('name') ?></large></div>
                    <div class="text-center font-weight-bold"><large>Order Details</large></div>
                </div>
            </div>
        </div>
        <hr>
    </div>
</noscript>
<script>
    function print_t(){
        var h = $('head').clone()
        var el = ""
        $('.printout').map(function(){
            var p = $(this).clone()
                p.find('.btn').remove()
                p.find('.d-print-none').remove()
                p.find('.card').addClass('border')
                p.removeClass('col-lg-8 col-md-10 col-sm-12 col-xs-12')
                p.addClass('col-12')
            el += p[0].outerHTML
        })
        var ph = $($('noscript#print-header').html()).clone()
        h.find('title').text("Order Details - Print View")
        var nw = window.open("", "_blank", "width="+($(window).width() * .8)+",left="+($(window).width() * .1)+",height="+($(window).height() * .8)+",top="+($(window).height() * .1))
        if(!nw) {
            alert_toast("Please allow popups to print order details.", "warning");
            return;
        }
        try {
            nw.document.querySelector('head').innerHTML = h.html()
            nw.document.querySelector('body').innerHTML = ph[0].outerHTML
            nw.document.querySelector('body').innerHTML += el
            nw.document.close()
            start_loader()
            setTimeout(() => {
                try { nw.print() } catch(e) {}
                setTimeout(() => {
                    try { nw.close() } catch(e) {}
                    end_loader()
                }, 200);
            }, 300);
        } catch(e) {
            console.error("Print Error:", e);
            end_loader();
        }
    }
    function delete_order($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_order",
            method:"POST",
            data:{id: $id, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
            dataType:"json",
            error:function(xhr, status, err){
                var msg = "An error occurred.";
                try {
                    if (xhr && xhr.responseText) { var r = JSON.parse(xhr.responseText); if (r && (r.msg || r.error)) msg = r.msg || r.error; }
                } catch(e) {}
                alert_toast(msg, 'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    alert_toast(resp.msg || 'Order deleted.', 'success');
                    setTimeout(function(){ location.href = './?page=maintenance'; }, 800);
                }else{
                    alert_toast((resp && (resp.msg || resp.error)) || "An error occurred.", 'error');
                    end_loader();
                }
            }
        });
    }
    $(function(){
        // Ensure child modals are top-level on body so they are never trapped inside uni_modal or clipped
        $('body > #updateStatusModal, body > #cancelOrderModal, body > #pickupModal').remove();
        $('#updateStatusModal, #cancelOrderModal, #pickupModal').appendTo('body');

        // Unconditionally clean up uni_modal footer
        $('#uni_modal #submit').hide();
        $('#uni_modal .modal-footer [data-dismiss="modal"], #uni_modal .modal-footer .btn-secondary').text('Close');

        // Delegated click handlers (prevent duplicate bindings and survive DOM updates)
        $(document).off('click', '#print').on('click', '#print', function(e){
            e.preventDefault();
            print_t();
        });

        $(document).off('click', '#delete_data').on('click', '#delete_data', function(e){
            e.preventDefault();
            _conf("Are you sure to delete this order permanently?","delete_order", ["<?= isset($id) ? $id :'' ?>"]);
        });

        $(document).off('click', '#update_status').on('click', '#update_status', function(e){
            e.preventDefault();
            $('#updateStatusModal').modal('show');
        });
        
        $(document).off('click', '#set_pickup_btn').on('click', '#set_pickup_btn', function(e){
            e.preventDefault();
            $('#pickupModal').modal('show');
        });
        
        $(document).off('click', '#cancel_order').on('click', '#cancel_order', function(e){
            e.preventDefault();
            $('#cancelOrderModal').modal('show');
        });

        // Form Submit: Update Order Status
        $(document).off('submit', '#update-status-form').on('submit', '#update-status-form', function(e){
            e.preventDefault();
            var _this = $(this);
            var formData = new FormData(this);
            if (!formData.get('csrf_token') && typeof _csrf_token_ !== 'undefined') {
                formData.append('csrf_token', _csrf_token_);
            }
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=update_order_status",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                    alert_toast("An error occurred: " + error, 'error');
                    end_loader();
                },
                success: function(resp) {
                    if(typeof resp == 'object' && resp.status == 'success'){
                        alert_toast("Order status updated successfully!", 'success');
                        $('#updateStatusModal').modal('hide');
                        setTimeout(function(){
                            location.reload();
                        }, 500);
                    } else if(resp.status == 'failed' && !!resp.msg){
                        alert_toast(resp.msg, 'error');
                        end_loader();
                    } else {
                        alert_toast("An error occurred", 'error');
                        end_loader();
                    }
                }
            });
        });
        
        // Form Submit: Cancel Order
        $(document).off('submit', '#cancel-order-form').on('submit', '#cancel-order-form', function(e){
            e.preventDefault();
            var reason = $('#cancel_reason').val();
            
            if(!reason) {
                alert_toast("Please select a cancellation reason", 'warning');
                return false;
            }
            
            start_loader();
            $.ajax({
                url: _base_url_+"classes/Master.php?f=admin_cancel_order",
                data: { 
                    id: '<?= isset($id) ? $id : '' ?>',
                    reason: reason,
                    notes: $('#cancel_notes').val(),
                    csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''
                },
                method: 'POST',
                dataType: 'json',
                error: function(err){
                    console.log(err);
                    alert_toast("An error occurred", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        alert_toast("Order cancelled successfully", 'success');
                        $('#cancelOrderModal').modal('hide');
                        setTimeout(function(){
                            location.reload();
                        }, 1000);
                    } else {
                        alert_toast(resp.msg || "An error occurred", 'error');
                        end_loader();
                    }
                }
            });
        });
        
        // Form Submit: Set/Modify Pickup Schedule
        $(document).off('submit', '#pickup-schedule-form').on('submit', '#pickup-schedule-form', function(e){
            e.preventDefault();
            var pickupDate = $('#pickup_date').val();
            var pickupTime = $('#pickup_time').val();
            var selectedDateTime = new Date(pickupDate + 'T' + pickupTime);
            var now = new Date();
            
            if(isNaN(selectedDateTime.getTime()) || selectedDateTime < now) {
                alert_toast("Pickup date and time must be in the future", 'warning');
                return false;
            }
            
            start_loader();
            var formData = new FormData(this);
            if (!formData.get('csrf_token') && typeof _csrf_token_ !== 'undefined') {
                formData.append('csrf_token', _csrf_token_);
            }
            $.ajax({
                url: _base_url_+"classes/Master.php?f=set_pickup_schedule",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                statusCode: {
                    403: function() {
                        alert_toast("Access denied", 'error');
                        end_loader();
                    }
                },
                error: function(err){
                    console.log("AJAX Error:", err);
                    alert_toast("An error occurred: " + (err.statusText || 'Unknown error'), 'error');
                    end_loader();
                },
                success: function(resp){
                    end_loader();
                    if(typeof resp == 'object' && resp.status == 'success'){
                        alert_toast("Pickup schedule saved successfully", 'success');
                        $('#pickupModal').modal('hide');
                        setTimeout(function(){
                             window.location.href = window.location.href;
                        }, 800);
                    } else {
                        alert_toast((resp && (resp.msg || resp.error)) || "An error occurred", 'error');
                    }
                }
            });
        });
        
        // View prescription details (only if button exists)
        <?php if ($has_prescription): ?>
        $('#view_prescription_btn').click(function(){
            <?php if (isset($prescription_details) && !empty($prescription_details)): ?>
            const details = <?= json_encode($prescription_details) ?>;
            <?php else: ?>
            const details = {};
            <?php endif; ?>
            const validation = '<?= isset($prescription_validation) ? htmlspecialchars($prescription_validation, ENT_QUOTES) : '' ?>';
            const prescriptionImage = <?= isset($prescription_image) && !empty($prescription_image) ? json_encode($prescription_image) : '""' ?>;
            const baseUrl = '<?= base_url ?>';
            
            let statusBadge = '';
            let statusClass = '';
            
            switch(validation) {
                case 'matched':
                    statusBadge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Perfect Match</span>';
                    statusClass = 'alert-success';
                    break;
                case 'partial':
                    statusBadge = '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Partial Match</span>';
                    statusClass = 'alert-warning';
                    break;
                case 'mismatch':
                    statusBadge = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Mismatch</span>';
                    statusClass = 'alert-danger';
                    break;
                case 'invalid':
                    statusBadge = '<span class="badge badge-secondary"><i class="fas fa-question-circle"></i> Invalid</span>';
                    statusClass = 'alert-secondary';
                    break;
                default:
                    statusBadge = '<span class="badge badge-light border">Pending</span>';
                    statusClass = 'alert-info';
            }
            
            try {
                const detailsObj = details ? JSON.parse(details) : {};
                const escapeHtml = (value) => {
                    if (value === null || value === undefined) return '';
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };
                
                // Debug logging
                console.log('Prescription Image:', prescriptionImage);
                console.log('Base URL:', baseUrl);
                
                const normalizedImage = prescriptionImage ? prescriptionImage.replace(/^\/+/, '') : '';
                const prescriptionImageUrl = normalizedImage ? baseUrl + normalizedImage : '';
                const prescriptionImageAttr = escapeHtml(prescriptionImageUrl);
                
                console.log('Normalized Image:', normalizedImage);
                console.log('Final URL:', prescriptionImageUrl);
                
                let summaryText = detailsObj.notes || '';
                
                if (!summaryText) {
                    if (validation === 'matched') {
                        summaryText = 'All prescribed medicines are in the cart with no extra items detected.';
                    } else if (validation === 'partial') {
                        summaryText = 'Some items match, but there are missing or extra items in the cart.';
                    } else if (validation === 'mismatch') {
                        const summaryParts = [];
                        if (detailsObj.missing_from_cart && detailsObj.missing_from_cart.length > 0) {
                            summaryParts.push('Missing from cart: ' + detailsObj.missing_from_cart.join(', '));
                        }
                        if (detailsObj.extra_in_cart && detailsObj.extra_in_cart.length > 0) {
                            summaryParts.push('Extra in cart: ' + detailsObj.extra_in_cart.join(', '));
                        }
                        summaryText = summaryParts.length > 0 ? summaryParts.join('. ') + '.' : 'Prescription does not match the cart items.';
                    } else if (validation === 'invalid') {
                        summaryText = 'The uploaded image does not appear to be a valid prescription.';
                    }
                }
                
                let modalContent = `
                    <div class="alert ${statusClass}">
                        <h5>Prescription Validation Status: ${statusBadge}</h5>
                    </div>
                    ${prescriptionImageUrl ? `
                        <div class="mb-3 text-center">
                            <h6><strong>Uploaded Prescription:</strong></h6>
                            <div class="border rounded p-2" style="background-color: #f8f9fa;">
                                <img src="${prescriptionImageAttr}" alt="Prescription" class="img-fluid" style="max-width: 100%; max-height: 500px; cursor: pointer;" onclick="window.open('${prescriptionImageAttr}', '_blank')">
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle"></i> Click image to view full size in new tab</small>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    ${summaryText ? `
                        <div class="mb-3">
                            <h6><strong>Match Summary:</strong></h6>
                            <div class="alert alert-light">${escapeHtml(summaryText)}</div>
                        </div>
                    ` : ''}
                    <div class="row">
                        <div class="col-md-6">
                            <h6><strong>Prescribed Medicines:</strong></h6>
                            <ul class="list-group">
                                ${detailsObj.prescribed_medicines && detailsObj.prescribed_medicines.length > 0 ? 
                                    detailsObj.prescribed_medicines.map(med => `<li class="list-group-item">${escapeHtml(med)}</li>`).join('') : 
                                    '<li class="list-group-item text-muted">None detected</li>'}
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>Matched Cart Products:</strong></h6>
                            <ul class="list-group">
                                ${detailsObj.matched_products && detailsObj.matched_products.length > 0 ? 
                                    detailsObj.matched_products.map(prod => `<li class="list-group-item text-success"><i class="fas fa-check"></i> ${escapeHtml(prod)}</li>`).join('') : 
                                    '<li class="list-group-item text-muted">None matched</li>'}
                            </ul>
                        </div>
                    </div>
                    ${detailsObj.missing_from_cart && detailsObj.missing_from_cart.length > 0 ? `
                        <div class="mt-3">
                            <h6><strong>Missing from Cart:</strong></h6>
                            <ul class="list-group">
                                ${detailsObj.missing_from_cart.map(med => `<li class="list-group-item text-warning"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(med)}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    ${detailsObj.extra_in_cart && detailsObj.extra_in_cart.length > 0 ? `
                        <div class="mt-3">
                            <h6><strong>Extra in Cart (Not Prescribed):</strong></h6>
                            <ul class="list-group">
                                ${detailsObj.extra_in_cart.map(prod => `<li class="list-group-item text-danger"><i class="fas fa-times"></i> ${escapeHtml(prod)}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    ${detailsObj.admin_note ? `
                        <div class="mt-3">
                            <h6><strong>Admin Review Note:</strong></h6>
                            <div class="alert alert-warning">${escapeHtml(detailsObj.admin_note)}</div>
                        </div>
                    ` : ''}
                    ${detailsObj.confidence !== undefined ? `
                        <div class="mt-3">
                            <h6><strong>Confidence:</strong> ${(detailsObj.confidence * 100).toFixed(0)}%</h6>
                            <div class="progress">
                                <div class="progress-bar ${detailsObj.confidence >= 0.8 ? 'bg-success' : detailsObj.confidence >= 0.5 ? 'bg-warning' : 'bg-danger'}" 
                                     role="progressbar" 
                                     style="width: ${(detailsObj.confidence * 100)}%" 
                                     aria-valuenow="${(detailsObj.confidence * 100)}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    ` : ''}
                `;
                
                uni_modal('Prescription Validation Details', '', 'modal-xl', '', {
                    content: modalContent
                });
            } catch(e) {
                console.error('Error parsing prescription details:', e);
                alert_toast('Error loading prescription details', 'error');
            }
        });
        <?php endif; ?>
    })
