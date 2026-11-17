<?php 
if($_settings->userdata('id') == '' || $_settings->userdata('login_type') != 2){
    echo "<script>alert('You dont have access for this page'); location.replace('./');</script>";
    exit; 
}

$customer_id = $_settings->userdata('id');

$profile_check = $conn->query("SELECT * FROM customer_list WHERE id = '{$customer_id}'");
$profile_data = $profile_check ? $profile_check->fetch_assoc() : [];

$missing_profile_items = [];
if (empty(trim($profile_data['firstname'] ?? ''))) $missing_profile_items[] = 'First Name';
if (empty(trim($profile_data['middlename'] ?? ''))) $missing_profile_items[] = 'Middle Name';
if (empty(trim($profile_data['lastname'] ?? ''))) $missing_profile_items[] = 'Last Name';
if (empty(trim($profile_data['gender'] ?? ''))) $missing_profile_items[] = 'Gender';

$customer_email = trim($profile_data['email'] ?? '');
$has_email = !empty($customer_email) && filter_var($customer_email, FILTER_VALIDATE_EMAIL);
if (!$has_email) $missing_profile_items[] = 'Valid Email Address';

$customer_contact = trim($profile_data['contact'] ?? '');
$has_contact = !empty($customer_contact) && preg_match('/^09\d{9}$/', $customer_contact);
if (!$has_contact) $missing_profile_items[] = '11-digit Phone Number starting with 09';

$customer_birthdate = trim($profile_data['birthdate'] ?? '');
$has_birthdate = false;
if (!empty($customer_birthdate)) {
    $b_date = DateTime::createFromFormat('Y-m-d', $customer_birthdate);
    if ($b_date) {
        $age = (new DateTime())->diff($b_date)->y;
        if ($age >= 18) {
            $has_birthdate = true;
        }
    }
}
if (!$has_birthdate) $missing_profile_items[] = 'Birth Date (must be at least 18 years old)';

$missing_profile_info = !empty($missing_profile_items);

$where_clause = "";
if (isset($_GET['selected_items']) && !empty($_GET['selected_items'])) {
    $selected_items = explode(',', $_GET['selected_items']);
    $selected_items = array_map('intval', $selected_items);
    $selected_items = array_filter($selected_items);
    if (!empty($selected_items)) {
        $where_clause = "AND c.id IN (" . implode(',', $selected_items) . ")";
    }
}

$rx_check = $conn->query("SELECT COUNT(*) as rx_count 
                          FROM cart_list c 
                          INNER JOIN product_list p ON c.product_id = p.id 
                          WHERE c.customer_id = '{$customer_id}' 
                          AND COALESCE(p.is_prescription, 0) = 1 
                          $where_clause");
$rx_data = $rx_check->fetch_assoc();
$has_rx_products = $rx_data['rx_count'] > 0;

$prescription_check = $conn->query("SELECT id, image_path, expires_at
                                    FROM customer_prescriptions
                                    WHERE customer_id = '{$customer_id}'
                                    AND status = 'approved'
                                    AND (expires_at IS NULL OR expires_at >= CURDATE())
                                    ORDER BY approved_at DESC
                                    LIMIT 1");
$valid_prescription = $prescription_check && $prescription_check->num_rows > 0
    ? $prescription_check->fetch_assoc()
    : null;
$block_checkout = $has_rx_products && !$valid_prescription;
?>

<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/checkout.css?v=<?php echo filemtime('assets/css/customer/checkout.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<link rel="stylesheet" href="<?php echo base_url ?>assets/css/payment-method.css?v=<?php echo filemtime('assets/css/payment-method.css'); ?>">

<section class="modern-section">
    <div class="container">
        <?php if (($_GET['payment'] ?? '') === 'failed'): ?>
        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <span>Your online payment expired or failed. Your items were returned to the cart; please review checkout and try again.</span>
        </div>
        <?php endif; ?>
        <div class="content-header">
            <h3><b>Checkout - Store Pickup</b></h3>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-11 col-sm-12">
                <div class="modern-card shadow">
                    <div class="card-body p-3">
                        <div class="container-fluid">
                            <?php 
                            $gt = 0;
                            $selected_items = [];
                            $where_clause = "";
                            
                            
                            if (isset($_GET['selected_items']) && !empty($_GET['selected_items'])) {
                                $selected_items = explode(',', $_GET['selected_items']);
                                $selected_items = array_map('intval', $selected_items); 
                                $selected_items = array_filter($selected_items); 
                                
                                if (!empty($selected_items)) {
                                    $where_clause = "AND c.id IN (" . implode(',', $selected_items) . ")";
                                }
                            }
                            
                            
                            $cart = $conn->query("SELECT c.*, p.name as product, p.brand as brand, p.price, cc.name as category, p.image_path FROM `cart_list` c inner join product_list p on c.product_id = p.id inner join category_list cc on p.category_id = cc.id where customer_id = '{$_settings->userdata('id')}' $where_clause");
                            ?>
                            
                            <?php if($cart->num_rows > 0): ?>
                            
            
            <?php if($has_rx_products): ?>
            <div class="prescription-warning">
                <h5>
                    <i class="fas fa-prescription"></i>
                    Valid Prescription Required
                </h5>
                <p>
                    <strong>⚠️ Your cart contains prescription medications.</strong><br>
                    Upload your prescription below. The system will automatically validate the document and cart match.
                </p>
                <button type="button" class="btn-upload-rx" data-action="scroll-to-prescription-upload">
                    <i class="fas fa-arrow-down"></i>
                    Scroll to Upload Section
                </button>
            </div>
            <?php endif; ?>
            
            
            <?php if($missing_profile_info): ?>
            <div class="prescription-warning" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b;">
                <h5 style="color: #92400e;">
                    <i class="fas fa-user-edit"></i>
                    Action Required: Complete Your Profile Before Proceeding to Checkout
                </h5>
                <p style="color: #78350f;">
                    <strong>⚠️ All personal details must be complete and valid before placing an order:</strong><br>
                    <?php foreach($missing_profile_items as $item): ?>
                        ❌ <strong><?= htmlspecialchars($item) ?></strong><br>
                    <?php endforeach; ?>
                </p>
                <a href="./?p=customer/profile" class="btn-upload-rx" style="background: #f59e0b; text-decoration: none; display: inline-block;">
                    <i class="fas fa-user-cog"></i>
                    Update My Profile Now
                </a>
            </div>
            <?php endif; ?>
            
                            <div class="selection-info">
                                <i class="fa fa-check-circle me-2"></i>
                                Checking out <?= $cart->num_rows ?> selected item(s) from your cart
                            </div>
                            
                            
                            <div class="order-summary">
                                <h4>Order Summary</h4>
                                <div class="products-list">
                                    <?php while($row = $cart->fetch_assoc()): 
                                        $gt += $row['price'] * $row['quantity'];
                                    ?>
                                    <div class="product-item">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <img src="<?= validate_image($row['image_path']) ?>" alt="<?= $row['product'] ?>" class="product-logo">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="product-name"><?= $row['product'] ?></div>
                                                <div class="product-details">Brand: <?= $row['brand'] ?></div>
                                                <div class="product-details">Category: <?= $row['category'] ?></div>
                                                <div class="product-details">Quantity: <?= $row['quantity'] ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="product-price">₱<?= format_num($row['price'] * $row['quantity'], 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                
                                <div class="total-section">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Total Amount:</h5>
                                        <h5 class="mb-0">₱<?= format_num($gt, 2) ?></h5>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="checkout-form">
                                <h4>Store Pickup Information</h4>
                                <form action="" id="order-form" enctype="multipart/form-data">
                                    <?php 
                                    require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
                                    echo SecurityHelper::csrfField(); 
                                    ?>
                                    <input type="hidden" name="total_amount" value="<?= $gt ?>">
                                    <?php if (!empty($selected_items)): ?>
                                    <input type="hidden" name="selected_items_param" value="<?= implode(',', $selected_items) ?>">
                                    <?php endif; ?>
                                    
                                    
                                    <div class="form-group">
                                        <label class="control-label"><i class="fas fa-store"></i> Pickup Store</label>
                                        <?php 
                                        $sys_store_name = $_settings->info('name') ?: 'Store';
                                        $sys_store_address = $_settings->info('address') ?: '';
                                        $sys_store_phone = $_settings->info('phone') ?: ($_settings->info('mobile') ?: '');
                                        $sys_store_email = $_settings->info('email') ?: '';
                                        ?>
                                        <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px 16px; margin-top:10px;">
                                            <p style="margin:0; font-size:0.9rem;"><i class="fas fa-map-marker-alt text-success mr-1"></i> <strong><?= htmlspecialchars($sys_store_name) ?></strong></p>
                                            <?php if(!empty($sys_store_address)): ?>
                                            <p style="margin:4px 0 0; font-size:0.85rem; color:#555;"><i class="fas fa-road mr-1"></i> <?= htmlspecialchars($sys_store_address) ?></p>
                                            <?php endif; ?>
                                            <?php if(!empty($sys_store_phone)): ?>
                                            <p style="margin:4px 0 0; font-size:0.85rem; color:#555;"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($sys_store_phone) ?></p>
                                            <?php endif; ?>
                                            <?php if(!empty($sys_store_email)): ?>
                                            <p style="margin:4px 0 0; font-size:0.85rem; color:#555;"><i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($sys_store_email) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-hint">
                                            <strong>Store Pickup:</strong> FREE - Pick up your order at <?= htmlspecialchars($sys_store_name) ?>. You will receive a notification when your order is ready.
                                        </div>
                                    </div>

                                     <div class="form-group">
                                        <label for="pickup_date" class="control-label">Pickup Date</label>
                                        <input type="date" name="pickup_date" id="pickup_date" class="form-select" required min="<?= date('Y-m-d') ?>">
                                        <div class="form-hint">
                                            <strong>Note:</strong> Please select a date for when you'd like to pick up your order.
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="pickup_time" class="control-label">Pickup Time</label>
                                        <select name="pickup_time" id="pickup_time" class="form-select" required>
                                            <option value="">Select Time</option>
                                            <option value="09:00">9:00 AM</option>
                                            <option value="09:30">9:30 AM</option>
                                            <option value="10:00">10:00 AM</option>
                                            <option value="10:30">10:30 AM</option>
                                            <option value="11:00">11:00 AM</option>
                                            <option value="11:30">11:30 AM</option>
                                            <option value="12:00">12:00 PM</option>
                                            <option value="12:30">12:30 PM</option>
                                            <option value="13:00">1:00 PM</option>
                                            <option value="13:30">1:30 PM</option>
                                            <option value="14:00">2:00 PM</option>
                                            <option value="14:30">2:30 PM</option>
                                            <option value="15:00">3:00 PM</option>
                                            <option value="15:30">3:30 PM</option>
                                            <option value="16:00">4:00 PM</option>
                                            <option value="16:30">4:30 PM</option>
                                            <option value="17:00">5:00 PM</option>
                                            <option value="17:30">5:30 PM</option>
                                        </select>
                                        <div class="form-hint">
                                            <strong>Note:</strong> Store hours are typically 9:00 AM - 5:30 PM. Please select a convenient time within operating hours.
                                        </div>
                                    </div>
                                    
                                    
                                    <?php if($has_rx_products): ?>
                                    <div class="rx-pickup-reminder" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 5px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                                        <h5 style="color: #92400e; margin-bottom: 10px;">
                                            <i class="fas fa-prescription" style="margin-right: 8px;"></i>
                                            Important: Bring Your Prescription!
                                        </h5>
                                        <p style="color: #78350f; margin-bottom: 8px;">
                                            <i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i>
                                            <strong>Your order contains prescription (Rx) medications.</strong> You MUST bring a valid, original prescription when picking up your order.
                                        </p>
                                        <ul style="color: #78350f; margin: 0; padding-left: 20px;">
                                            <li>Bring the <strong>original prescription</strong> (not a photocopy) issued by a licensed physician.</li>
                                            <li>The prescription must match the Rx items in your order.</li>
                                            <li>Expired or invalid prescriptions will result in <strong>order rejection</strong> for Rx items.</li>
                                            <li>Our pharmacist will verify your prescription at the time of pickup.</li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    
                                    <div class="payment-method-section">
                                        <h4><i class="fas fa-credit-card"></i> Payment Method</h4>
                                        
                                        <?php if($has_rx_products): ?>
                                        <div class="rx-payment-notice" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; color: #991b1b; font-size: 0.9rem;">
                                            <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
                                            <strong>Note:</strong> Online payments (GCash/Maya) are not available for orders containing prescription (Rx) medications. 
                                            Please pay with <strong>Cash on Pickup</strong> after prescription verification.
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="payment-options">
                                            
                                            <div class="payment-option">
                                                <input type="radio" name="payment_method" id="payment_cash" value="cash" checked>
                                                <label for="payment_cash" class="payment-option-label">
                                                    <div class="payment-icon-placeholder cash">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </div>
                                                    <span class="payment-name">Cash on Pickup</span>
                                                    <span class="payment-desc">Pay when you pick up</span>
                                                </label>
                                            </div>
                                            
                                            
                                            <div class="payment-option <?= $has_rx_products ? 'payment-disabled' : '' ?>">
                                                <input type="radio" name="payment_method" id="payment_gcash" value="gcash" <?= $has_rx_products ? 'disabled' : '' ?>>
                                                <label for="payment_gcash" class="payment-option-label" <?= $has_rx_products ? 'style="opacity: 0.5; cursor: not-allowed;" title="Not available for prescription orders"' : '' ?>>
                                                    <div class="payment-icon-placeholder gcash">
                                                        <i class="fas fa-mobile-alt"></i>
                                                    </div>
                                                    <span class="payment-name">GCash</span>
                                                    <span class="payment-desc"><?= $has_rx_products ? 'Not available for Rx orders' : 'Scan QR & upload receipt' ?></span>
                                                </label>
                                            </div>
                                            
                                            
                                            <div class="payment-option <?= $has_rx_products ? 'payment-disabled' : '' ?>">
                                                <input type="radio" name="payment_method" id="payment_maya" value="maya" <?= $has_rx_products ? 'disabled' : '' ?>>
                                                <label for="payment_maya" class="payment-option-label" <?= $has_rx_products ? 'style="opacity: 0.5; cursor: not-allowed;" title="Not available for prescription orders"' : '' ?>>
                                                    <div class="payment-icon-placeholder maya">
                                                        <i class="fas fa-wallet"></i>
                                                    </div>
                                                    <span class="payment-name">Maya</span>
                                                    <span class="payment-desc"><?= $has_rx_products ? 'Not available for Rx orders' : 'Scan QR & upload receipt' ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" name="payment_method_selected" id="selectedPaymentMethod" value="cash">
                                        
                                        
                                        <div class="online-payment-reminder" id="onlinePaymentReminder" style="display: none;">
                                            <div class="reminder-icon">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div class="reminder-content">
                                                <h6>Important Reminder for Online Payments</h6>
                                                <p class="reminder-general">
                                                    <i class="fas fa-info-circle"></i>
                                                    For <strong>all online payments</strong>: If you accidentally purchase the wrong product, 
                                                    the amount paid for the incorrect item will be <strong>deducted and refunded</strong> to your e-wallet account. 
                                                    Please note that the <strong>refund will be processed at the time of pick up</strong>.
                                                </p>
                                                <p class="reminder-note">
                                                    <i class="fas fa-shield-alt"></i>
                                                    By proceeding, you acknowledge that the pharmacy reserves the right to adjust or cancel items 
                                                    and issue refunds at the time of pickup as necessary.
                                                </p>
                                                
                                                <div class="reminder-acknowledge">
                                                    <label class="acknowledge-label" for="acknowledgePaymentReminder">
                                                        <input type="checkbox" id="acknowledgePaymentReminder" name="acknowledge_payment_reminder">
                                                        <span class="acknowledge-checkmark"><i class="fas fa-check"></i></span>
                                                        <span class="acknowledge-text">
                                                            I have read and understood the above reminder. I agree that my payment may be subject to 
                                                            <strong>deductions or refunds at the time of pickup</strong> if I purchase the wrong product or if my prescription does not match.
                                                        </span>
                                                    </label>
                                                    <p class="acknowledge-hint" id="acknowledgeHint">
                                                        <i class="fas fa-arrow-up"></i> You must agree to the above before placing your order.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        
                                        <div class="qr-payment-section" id="qrPaymentSection">
                                            <div class="qr-payment-header">
                                                <h5>Pay via <span id="paymentMethodDisplay">GCash</span></h5>
                                                <p>Scan the QR code below using your e-wallet app</p>
                                            </div>
                                            
                                            <div class="qr-code-container">
                                                <div class="payment-amount-display">
                                                    <div class="label">Amount to Pay</div>
                                                    <div class="amount">₱<?= format_num($gt, 2) ?></div>
                                                </div>
                                                
                                                <div class="qr-code-wrapper">
                                                    <?php
                                                    
                                                    $gcash_qr = '';
                                                    $maya_qr = '';
                                                    
                                                    
                                                    $qr_settings = $conn->query("SELECT meta_field, meta_value FROM system_info WHERE meta_field IN ('gcash_qr', 'maya_qr')");
                                                    if ($qr_settings) {
                                                        while ($qr = $qr_settings->fetch_assoc()) {
                                                            if ($qr['meta_field'] === 'gcash_qr') $gcash_qr = $qr['meta_value'];
                                                            if ($qr['meta_field'] === 'maya_qr') $maya_qr = $qr['meta_value'];
                                                        }
                                                    }
                                                    ?>
                                                    <?php if (!empty($gcash_qr) || !empty($maya_qr)): ?>
                                                    <img id="qrCodeImage" src="<?= base_url . $gcash_qr ?>" alt="Payment QR Code" 
                                                         data-gcash-qr="<?= base_url . $gcash_qr ?>" 
                                                         data-maya-qr="<?= base_url . $maya_qr ?>">
                                                    <?php else: ?>
                                                    <div class="qr-code-placeholder">
                                                        <i class="fas fa-qrcode"></i>
                                                        <span>QR Code not set</span>
                                                        <small>Admin: Upload in Settings</small>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="payment-instructions">
                                                    <h6><i class="fas fa-list-ol"></i> How to Pay:</h6>
                                                    <ol>
                                                        <li>Open your <strong id="walletAppName">GCash</strong> app</li>
                                                        <li>Tap <strong>"Scan QR"</strong> or <strong>"Pay QR"</strong></li>
                                                        <li>Scan the QR code above</li>
                                                        <li>Enter the exact amount: <strong>₱<?= format_num($gt, 2) ?></strong></li>
                                                        <li>Complete the payment</li>
                                                        <li><strong>Take a screenshot</strong> of the receipt</li>
                                                        <li>Upload the screenshot below</li>
                                                    </ol>
                                                </div>
                                            </div>
                                            
                                            
                                            <div class="receipt-upload-section">
                                                <h6><i class="fas fa-receipt"></i> Upload Payment Receipt (Required)</h6>
                                                
                                                <div class="receipt-upload-area" id="receiptUploadArea">
                                                    <input type="file" name="payment_receipt" id="receiptInput" accept="image/*">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <p>Click or drag receipt screenshot here</p>
                                                    <span class="hint">JPG, PNG up to 5MB</span>
                                                </div>
                                                
                                                <div class="receipt-preview" id="receiptPreview">
                                                    <div class="receipt-preview-content">
                                                        <img id="receiptPreviewImg" src="" alt="Receipt Preview">
                                                        <div class="receipt-info">
                                                            <div class="filename" id="receiptFilename"></div>
                                                            <span class="status uploaded">
                                                                <i class="fas fa-check-circle"></i> Ready to submit
                                                            </span>
                                                        </div>
                                                        <button type="button" class="receipt-remove-btn" id="receiptRemoveBtn">
                                                            <i class="fas fa-times"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="form-group">
                                        <label for="prescription_upload" class="control-label">
                                            <i class="fas fa-prescription"></i> Upload Prescription <?= $has_rx_products ? '(Required)' : '(Optional)' ?>
                                        </label>
                                        <?php if($has_rx_products): ?>
                                        <div style="background: rgba(220, 38, 38, 0.05); border: 2px solid rgba(220, 38, 38, 0.3); border-radius: 12px; padding: 20px; text-align: center;">
                                        <?php else: ?>
                                        <div style="background: rgba(13, 148, 136, 0.05); border: 2px dashed rgba(13, 148, 136, 0.3); border-radius: 12px; padding: 20px; text-align: center;">
                                        <?php endif; ?>
                                            <input type="file" name="prescription_upload" id="prescription_upload" accept="image/*" class="form-control-file" style="display: none;">
                                            <div id="upload-area" style="cursor: pointer;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: <?= $has_rx_products ? '#dc2626' : '#0d9488' ?>; margin-bottom: 10px;"></i>
                                                <p style="margin: 10px 0; font-weight: 600; color: #374151;">Click to upload or drag prescription here</p>
                                                <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">AI will automatically verify if the prescription matches your cart items</p>
                                            </div>
                                            <div id="upload-preview" style="display: none; margin-top: 15px;">
                                                <img id="prescription-preview" src="" alt="Prescription Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                <div style="margin-top: 10px;">
                                                    <p id="prescription-filename" style="font-weight: 600; color: #374151; margin: 5px 0;"></p>
                                                    <div id="validation-status" style="padding: 8px 16px; border-radius: 8px; display: inline-block; margin-top: 5px;">
                                                        <i class="fas fa-spinner fa-spin"></i> Validating with AI...
                                                    </div>
                                                    <button type="button" id="remove-prescription" class="btn btn-sm btn-danger" style="display: block; margin: 10px auto;">
                                                        <i class="fas fa-times"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-hint" style="margin-top: 10px;">
                                            <i class="fas fa-info-circle text-primary"></i>
                                            <strong><?= $has_rx_products ? 'Required:' : 'Optional but recommended:' ?></strong> Upload your prescription for AI verification. The system will check if it matches your cart items for safety.
                                        </div>
                                        <input type="hidden" name="prescription_validation" id="prescription_validation" value="">
                                        <input type="hidden" name="prescription_details" id="prescription_details" value="">
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        
                                        <div class="mb-3 text-left" id="checkout-urgent-wrapper" style="background: #fffbeb; border: 1px solid #fbbf24; border-radius: 10px; padding: 12px 16px; cursor: pointer;">
                                            <div class="custom-control custom-checkbox" style="pointer-events: none;">
                                                <input type="checkbox" class="custom-control-input" id="is_urgent" name="is_urgent" value="1">
                                                <label class="custom-control-label" for="is_urgent" style="cursor:pointer;">
                                                    <strong><i class="fas fa-bolt text-warning" id="checkout-urgent-icon"></i> Mark as Urgent Order</strong>
                                                    <br><small class="text-muted">Select this if you need your order prepared as soon as possible. The pharmacy will be notified immediately.</small>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <?php if($block_checkout): ?>
                                        <div class="alert alert-warning mb-3">
                                            <i class="fas fa-prescription"></i>
                                            <strong>Checkout blocked:</strong> Upload a valid prescription that matches the prescription items in your cart. You may still message the pharmacy.
                                        </div>
                                        <button type="submit" class="place-order-btn" id="place-order-btn" disabled style="opacity:.6; cursor:not-allowed;">
                                            <span>Valid Prescription Required</span>
                                        </button>
                                        <?php elseif($missing_profile_info): ?>
                                        <div class="alert alert-warning mb-3">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>Cannot place order:</strong> All profile details must be completed before checkout.
                                            <?php foreach($missing_profile_items as $item): ?>
                                                <br>❌ <?= htmlspecialchars($item) ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <a href="./?p=customer/profile" class="place-order-btn" style="text-decoration: none; display: inline-block; background: #f59e0b;">
                                            <i class="fas fa-user-cog"></i> Complete Profile to Order
                                        </a>
                                        <?php else: ?>
                                        <button type="submit" class="place-order-btn" id="place-order-btn">
                                            <span>Place Pickup Order</span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <a href="./?p=customer/cart_list" class="btn btn-outline-secondary">
                                            <i class="fa fa-arrow-left me-2"></i>Back to Cart
                                        </a>
                                    </div>
                                </form>
                            </div>
                            
                            <?php else: ?>
                            <div class="empty-cart">
                                <h5>No items selected for checkout</h5>
                                <p>Please go back to your cart and select items to checkout.</p>
                                <a href="./?p=customer/cart_list" class="btn btn-primary">
                                    <i class="fa fa-shopping-cart me-2"></i>Go to Cart
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<style>

.urgent-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 999999;
    padding: 16px;
    box-sizing: border-box;
}
.urgent-modal-card {
    background: #ffffff;
    border-radius: 20px;
    max-width: 480px;
    width: 100%;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
    animation: urgentModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
@keyframes urgentModalPop {
    0% { transform: scale(0.92); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.urgent-modal-icon-badge {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 3px solid #f59e0b;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d97706;
    font-size: 28px;
    box-shadow: 0 4px 15px rgba(217, 119, 6, 0.25);
}
.urgent-modal-heading {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 6px;
}
.urgent-modal-tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    background: #fffbeb;
    color: #b45309;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
    border: 1px solid #fcd34d;
}
.urgent-modal-text {
    font-size: 0.95rem;
    color: #4b5563;
    line-height: 1.5;
    margin: 0 0 16px;
}
.urgent-modal-info-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    text-align: left;
    margin-bottom: 22px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.urgent-info-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.82rem;
    color: #334155;
    line-height: 1.4;
}
.urgent-info-row i {
    font-size: 14px;
    margin-top: 2px;
    flex-shrink: 0;
}
.urgent-modal-buttons {
    display: flex;
    gap: 10px;
}
.btn-urgent-cancel {
    flex: 1;
    padding: 12px 16px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-urgent-cancel:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.btn-urgent-confirm {
    flex: 1.2;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.btn-urgent-confirm:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(217, 119, 6, 0.45);
}
@media (max-width: 480px) {
    .urgent-modal-buttons {
        flex-direction: column-reverse;
    }
}
</style>

<div id="urgentConfirmModal" class="urgent-modal-backdrop">
    <div class="urgent-modal-card">
        <div class="urgent-modal-icon-badge">
            <i class="fas fa-bolt"></i>
        </div>
        <h3 class="urgent-modal-heading">Confirm Urgent Order</h3>
        <div class="urgent-modal-tag">Priority Preparation Request</div>
        <p class="urgent-modal-text">
            Is this medication <strong>urgently needed</strong> for acute symptoms, immediate pain relief, or critical health care?
        </p>
        <div class="urgent-modal-info-box">
            <div class="urgent-info-row">
                <i class="fas fa-clock" style="color: #d97706;"></i>
                <span>Our pharmacy staff will prioritize packing your items immediately ahead of standard orders.</span>
            </div>
            <div class="urgent-info-row">
                <i class="fas fa-store-alt" style="color: #0d9488;"></i>
                <span>Please proceed to pick up your order promptly once marked ready.</span>
            </div>
        </div>
        <div class="urgent-modal-buttons">
            <button type="button" class="btn-urgent-cancel" onclick="cancelUrgentOrder()">
                No, Standard Order
            </button>
            <button type="button" class="btn-urgent-confirm" onclick="confirmUrgentOrder()">
                <i class="fas fa-bolt"></i> Yes, Proceed as Urgent
            </button>
        </div>
    </div>
</div>
</section>

<script>

window.blockCheckout = <?= $block_checkout ? 'true' : 'false' ?>;
window.cartProducts = [];
<?php 
mysqli_data_seek($cart, 0);
while($row = $cart->fetch_assoc()): 
?>
window.cartProducts.push(<?= json_encode($row['name']) ?>);
<?php endwhile; ?>

function handleCheckoutUrgentToggle() {
    var $checkbox = $('#is_urgent');
    var $modal = $('#urgentConfirmModal');
    if ($checkbox.is(':checked')) {
        $checkbox.prop('checked', false).trigger('change');
        updateCheckoutUrgentVisual(false);
    } else {
        $modal.css('display', 'flex');
    }
}

function confirmUrgentOrder() {
    var $checkbox = $('#is_urgent');
    $checkbox.prop('checked', true).trigger('change');
    updateCheckoutUrgentVisual(true);
    $('#urgentConfirmModal').css('display', 'none');
}

function cancelUrgentOrder() {
    var $checkbox = $('#is_urgent');
    $checkbox.prop('checked', false).trigger('change');
    updateCheckoutUrgentVisual(false);
    $('#urgentConfirmModal').css('display', 'none');
}

function updateCheckoutUrgentVisual(isChecked) {
    var $wrapper = $('#checkout-urgent-wrapper');
    var $icon = $('#checkout-urgent-icon');
    if (isChecked) {
        $wrapper.css({
            'background': '#fef3c7',
            'border-color': '#d97706',
            'box-shadow': '0 0 0 3px rgba(217,119,6,0.15)'
        });
        $icon.removeClass('text-warning').css('color', '#b45309');
    } else {
        $wrapper.css({
            'background': '#fffbeb',
            'border-color': '#fbbf24',
            'box-shadow': 'none'
        });
        $icon.css('color', '').addClass('text-warning');
    }
}

$(document).ready(function() {
    $('#checkout-urgent-wrapper').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        handleCheckoutUrgentToggle();
    });

    $('#urgentConfirmModal').on('click', function(e) {
        if ($(e.target).is('#urgentConfirmModal')) {
            cancelUrgentOrder();
        }
    });
});
</script>

<script src="<?php echo base_url ?>assets/js/customer/checkout.js?v=<?php echo time(); ?>"></script>

<script src="<?php echo base_url ?>assets/js/payment-method.js?v=<?php echo time(); ?>"></script>
