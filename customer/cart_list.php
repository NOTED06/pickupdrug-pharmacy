<?php 
if($_settings->userdata('id') == '' || $_settings->userdata('login_type') != 2){
    echo "<script>alert('You dont have access for this page'); location.replace('./');</script>";
}

$customer_id = intval($_settings->userdata('id'));
$stmt = $conn->prepare("SELECT * FROM customer_list WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result ? $result->fetch_assoc() : null;
$stmt->close();

$profile_missing_details = [];
if (empty(trim($customer['firstname'] ?? ''))) $profile_missing_details[] = 'First Name';
if (empty(trim($customer['middlename'] ?? ''))) $profile_missing_details[] = 'Middle Name';
if (empty(trim($customer['lastname'] ?? ''))) $profile_missing_details[] = 'Last Name';
if (empty(trim($customer['gender'] ?? ''))) $profile_missing_details[] = 'Gender';

$cust_email = trim($customer['email'] ?? '');
if (empty($cust_email) || !filter_var($cust_email, FILTER_VALIDATE_EMAIL)) {
    $profile_missing_details[] = 'Valid Email Address';
}

$cust_contact = trim($customer['contact'] ?? '');
if (empty($cust_contact) || !preg_match('/^09\d{9}$/', $cust_contact)) {
    $profile_missing_details[] = '11-digit Phone Number starting with 09';
}

$cust_bdate = trim($customer['birthdate'] ?? '');
$cust_age = 0;
if (!empty($cust_bdate)) {
    $b_date = DateTime::createFromFormat('Y-m-d', $cust_bdate);
    if ($b_date) {
        $cust_age = (new DateTime())->diff($b_date)->y;
    }
}
if (empty($cust_bdate) || $cust_age < 18) {
    $profile_missing_details[] = 'Birth Date (must be at least 18 years old)';
}

$has_missing_profile = !empty($profile_missing_details);

$stmt = $conn->prepare("SELECT COUNT(*) as prescription_count 
                                    FROM customer_prescriptions 
                                    WHERE customer_id = ? 
                                    AND status = 'pending'");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$prescription_check = $stmt->get_result();
$prescription_data = $prescription_check->fetch_assoc();
$has_prescriptions = $prescription_data['prescription_count'] > 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as rx_count FROM cart_list c 
    INNER JOIN product_list p ON c.product_id = p.id 
    WHERE c.customer_id = ? AND COALESCE(p.is_prescription, 0) = 1");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$rx_result = $stmt->get_result();
$has_rx_products = ($rx_result->fetch_assoc()['rx_count'] > 0);
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_list WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_count = $cart_result->fetch_assoc()['count'];
$stmt->close();
?>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/cart_list.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<?php if ($cart_count == 0): ?>

<div style="text-align: center; padding: 4rem 2rem; padding-top: 120px; min-height: 60vh; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <i class="fa-solid fa-cart-shopping" style="font-size: 4rem; color: #9ca3af; margin-bottom: 1rem;"></i>
    <h2 style="font-family: Orbitron, sans-serif; font-size: 1.5rem; color: #1f2937; margin-bottom: 0.5rem;">Your Cart is Empty</h2>
    <p style="color: #6b7280; margin-bottom: 2rem;">Add some products to get started!</p>
    <a href="<?php echo base_url ?>?p=customer/categories" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #0d9488; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s;">
        <i class="fa-solid fa-store"></i> Browse Products
    </a>
</div>
<?php else: ?>

<div class="checkout-wrapper" style="display: grid; grid-template-columns: 1fr; min-height: 100vh; padding-top: 100px;">
    <style>
        @media (min-width: 1024px) { 
            .checkout-container { 
                grid-template-columns: 58% 42% !important; 
            } 
        }
        @media (max-width: 1023px) {
            .checkout-container {
                display: flex !important;
                flex-direction: column-reverse !important;
            }
            .summary-panel {
                order: -1 !important;
                min-height: auto !important;
                padding-top: 20px !important;
            }
        }
    </style>
    
    <div class="checkout-container" style="display: grid;">
        
        
        <div style="padding: 3rem 5%;">
            
            
            <div class="checkout-breadcrumb">
                <span class="active"><i class="fa-solid fa-shopping-cart"></i> Cart</span>
                <i class="fa-solid fa-chevron-right"></i>
                <span class="active">Information</span>
                <i class="fa-solid fa-chevron-right"></i>
                <span>Payment</span>
            </div>

            <?php if($has_missing_profile): ?>
            
            <div class="profile-warning-box" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #f59e0b; border-left: 5px solid #d97706; border-radius: 12px; padding: 18px 20px; margin-bottom: 2rem;">
                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%; background: #fef3c7; border: 2px solid #f59e0b; display: flex; align-items: center; justify-content: center; color: #d97706; font-size: 1.25rem;">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 6px; color: #92400e; font-size: 1.05rem; font-weight: 700;">
                            Action Required Before Proceeding to Checkout
                        </h4>
                        <p style="margin: 0 0 10px; color: #78350f; font-size: 0.9rem;">
                            All personal details must be complete and valid before you can place an order. Please update your profile details:
                        </p>
                        <ul style="margin: 0 0 14px; padding-left: 20px; color: #991b1b; font-size: 0.875rem; font-weight: 600; line-height: 1.6;">
                            <?php foreach($profile_missing_details as $item): ?>
                                <li><i class="fas fa-times-circle" style="color: #dc2626; margin-right: 6px;"></i> <?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo base_url ?>?p=customer/profile" class="btn" style="background: #0d9488; color: white; padding: 9px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.875rem; box-shadow: 0 2px 6px rgba(13, 148, 136, 0.3);">
                            <i class="fas fa-user-gear"></i> Update Profile Details Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form id="checkout-form" enctype="multipart/form-data">
                
                <?php 
                
                require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
                echo SecurityHelper::csrfField(); 
                ?>
                <input type="hidden" name="customer_id" value="<?php echo $customer_id ?>">
                <input type="hidden" id="checkout-total-amount" name="total_amount" value="0">
                <input type="hidden" id="prescription-required-input" name="prescription_required" value="0">
                <input type="hidden" id="prescription_validation" name="prescription_validation" value="">
                <input type="hidden" id="prescription_details" name="prescription_details" value="">
                
                
                <div class="form-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2 class="section-title">Contact Information</h2>
                        <p style="font-size: 0.75rem; color: var(--muted);">
                            Logged in as <strong><?php echo $customer['firstname'] . ' ' . $customer['lastname'] ?></strong>
                        </p>
                    </div>
                    
                    <div class="input-group">
                        <input type="email" id="customer_email" name="customer_email" class="input-field" 
                               placeholder=" " value="<?php echo htmlspecialchars($customer['email'] ?? '') ?>" readonly style="background-color: #f3f4f6; cursor: not-allowed; color: #4b5563;">
                        <label for="customer_email" class="input-label">Email Address (Read-only)</label>
                    </div>
                    <small style="font-size: 0.75rem; color: #6b7280; display: block; margin-top: 4px; margin-bottom: 12px;"><i class="fas fa-lock text-secondary mr-1"></i> Email address cannot be edited</small>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="email_updates" name="email_updates" value="1" style="width: 16px; height: 16px;">
                        <label for="email_updates" style="font-size: 0.875rem; color: var(--muted);">
                            Email me with order updates
                        </label>
                    </div>
                </div>

                
                <div class="form-section" style="margin-top: 2.5rem;">
                    <h2 class="section-title">
                        <i class="fa-solid fa-store"></i> Pickup Information
                    </h2>
                    
                    
                    <?php 
                    $store_name = $_settings->info('name') ?: 'Store';
                    $store_address = $_settings->info('address') ?: '';
                    $store_phone = $_settings->info('phone') ?: ($_settings->info('mobile') ?: '');
                    $store_email = $_settings->info('email') ?: '';
                    ?>
                    <div class="branch-info-card" style="display: flex; gap: 15px; padding: 16px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #86efac; border-radius: 12px; margin-bottom: 1rem;">
                        <div class="branch-icon-box" style="flex-shrink: 0; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #0d9488; color: white; border-radius: 10px; font-size: 1.1rem;">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 4px; font-weight: 700; color: #1f2937; font-size: 1rem;"><?php echo htmlspecialchars($store_name); ?></h4>
                            <?php if(!empty($store_address)): ?>
                            <p style="margin: 0 0 4px; font-size: 0.85rem; color: #555;"><i class="fa-solid fa-location-dot" style="margin-right: 5px; color: #0d9488;"></i><?php echo htmlspecialchars($store_address); ?></p>
                            <?php endif; ?>
                            <?php if(!empty($store_phone)): ?>
                            <p style="margin: 0 0 4px; font-size: 0.85rem; color: #555;"><i class="fa-solid fa-phone" style="margin-right: 5px; color: #0d9488;"></i><?php echo htmlspecialchars($store_phone); ?></p>
                            <?php endif; ?>
                            <?php if(!empty($store_email)): ?>
                            <p style="margin: 0; font-size: 0.85rem; color: #555;"><i class="fa-solid fa-envelope" style="margin-right: 5px; color: #0d9488;"></i><?php echo htmlspecialchars($store_email); ?></p>
                            <?php endif; ?>
                            <span class="branch-status-badge" style="display: inline-block; margin-top: 6px; font-size: 0.75rem; padding: 3px 10px; background: #0d9488; color: white; border-radius: 20px;">
                                <i class="fa-solid fa-clock"></i> Open: 8:00 AM - 9:00 PM
                            </span>
                        </div>
                    </div>

                    
                    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; margin-top: 1rem;">
                        <div class="input-group">
                            <input type="text" id="pickup_name" name="pickup_name" class="input-field" 
                                   placeholder=" " value="<?php echo $customer['firstname'] . ' ' . $customer['lastname'] ?>" required>
                            <label for="pickup_name" class="input-label">Who is picking up?</label>
                        </div>
                        
                        <div class="input-group">
                            <input type="datetime-local" id="pickup_time" name="pickup_time" class="input-field has-value" required>
                            <label class="input-label" style="top: -10px; left: 8px; font-size: 0.75rem; color: var(--primary);">Pickup Date & Time</label>
                        </div>
                    </div>
                </div>

                
                <div class="form-section" style="margin-top: 2.5rem;">
                    <h2 id="prescription-section-title" class="section-title" style="color: #ef4444;">
                        <i class="fa-solid fa-file-prescription"></i> Prescription Upload <span style="color: #ef4444; font-weight: 700;">(Required)</span>
                    </h2>
                    
                    
                    <div id="prescription-alert" class="alert-box alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <h4>Verification Needed</h4>
                            <p>Your cart contains restricted items. Please upload a valid prescription.</p>
                        </div>
                    </div>

                    
                    <div id="upload-area" class="upload-area required">
                        <input type="file" id="prescription-file" name="prescription_file" 
                               accept="image/*,.pdf" required style="display: none;">
                        
                        
                        <div id="upload-placeholder">
                            <div class="upload-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <p style="font-weight: 600; color: var(--foreground); font-size: 0.875rem; margin-bottom: 4px;">
                                Click to Upload or Drag File Here
                            </p>
                            <p style="font-size: 0.75rem; color: var(--muted);">
                                JPG, PNG or PDF (Max 5MB)
                            </p>
                        </div>

                        
                        <div id="upload-success" style="display: none;">
                            <div class="upload-success-icon">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <img id="prescription-preview" class="upload-preview" style="display: none;">
                            <p style="font-weight: 600; color: var(--foreground); font-size: 0.875rem; margin-bottom: 4px;" id="filename-display">
                                filename.jpg
                            </p>
                            <p style="font-size: 0.75rem; color: var(--success); font-weight: 600; margin-bottom: 8px;">
                                Prescription Uploaded
                            </p>
                            
                            
                            <div id="validation-status" class="validation-status" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                
                <div class="form-section" style="margin-top: 2.5rem;">
                    <h2 class="section-title">
                        <i class="fa-solid fa-credit-card"></i> Payment
                    </h2>
                    
                    <p class="security-badge">
                        <i class="fa-solid fa-lock lock-icon"></i> All transactions are secure and encrypted.
                    </p>
                    
                    <div class="payment-methods">
                        
                        
                        <div class="rx-payment-notice" style="<?php echo $has_rx_products ? 'display: flex;' : 'display: none;'; ?> gap: 12px; padding: 14px 16px; margin-bottom: 1rem; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 1px solid #fca5a5; border-left: 4px solid #dc2626; border-radius: 10px;">
                            <div style="flex-shrink: 0; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #dc2626; color: white; border-radius: 50%; font-size: 16px;">
                                <i class="fas fa-prescription"></i>
                            </div>
                            <div style="flex: 1;">
                                <h6 style="margin: 0 0 6px; color: #991b1b; font-weight: 700; font-size: 0.9rem;">Prescription (Rx) Order</h6>
                                <p style="margin: 0; color: #7f1d1d; font-size: 0.82rem; line-height: 1.5;">
                                    <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                                    Your cart contains <strong>prescription medications</strong>. Online payments (GCash/Maya) are <strong>not available</strong> for Rx orders. Please use <strong>Cash on Pickup</strong>.
                                </p>
                                <p style="margin: 6px 0 0; color: #7f1d1d; font-size: 0.82rem; line-height: 1.5;">
                                    <i class="fas fa-file-medical" style="margin-right: 4px;"></i>
                                    Please bring your <strong>valid prescription</strong> when picking up your order.
                                </p>
                            </div>
                        </div>

                        
                        <div class="payment-option <?php echo $has_rx_products ? 'disabled-payment' : ''; ?>" id="ewallet-option" <?php echo $has_rx_products ? 'style="opacity: 0.45; pointer-events: none; position: relative;"' : ''; ?>>
                            <div class="rx-disabled-badge" style="<?php echo $has_rx_products ? '' : 'display: none;'; ?> position: absolute; top: 8px; right: 12px; background: #dc2626; color: white; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; font-weight: 600; z-index: 2;">
                                <i class="fas fa-ban" style="margin-right: 3px;"></i> Not available for Rx
                            </div>
                            <label class="payment-radio-label" id="ewallet-label">
                                <input type="radio" name="payment_method" value="ewallet" id="ewallet-radio" <?php echo $has_rx_products ? 'disabled' : ''; ?>>
                                <input type="hidden" name="payment_method_selected" id="payment_method_selected" value="">
                                <div class="payment-info">
                                    <span class="payment-name" id="ewallet-name">GCash / Maya</span>
                                    <div class="payment-icons">
                                        <span class="payment-badge gcash">GCash</span>
                                        <span class="payment-badge maya">Maya</span>
                                    </div>
                                </div>
                            </label>
                            <div class="payment-details" id="ewallet-details" style="text-align: center;">
                                <div style="width: 48px; height: 48px; margin: 0 auto 12px; background: white; 
                                            border: 1px solid #e5e7eb; border-radius: 8px; display: flex; 
                                            align-items: center; justify-content: center; color: var(--primary); 
                                            box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <i class="fa-solid fa-mobile-screen" style="font-size: 1.25rem;"></i>
                                </div>
                                <p id="ewallet-redirect-msg" style="font-size: 0.875rem; color: var(--muted);">
                                    Choose your payment provider below.
                                </p>
                                <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 6px;">
                                    <i class="fas fa-shield-alt" style="margin-right: 4px;"></i> Powered by PayMongo
                                </p>
                            </div>
                        </div>

                        
                        <div class="payment-option active">
                            <label class="payment-radio-label">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <div class="payment-info">
                                    <span class="payment-name">Cash on Pickup</span>
                                </div>
                            </label>
                            <div class="payment-details" style="text-align: center;">
                                <p style="font-size: 0.875rem; color: var(--muted);">
                                    Pay at the counter when you claim your items.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                
                <style>
                
                .online-payment-reminder {
                    display: flex;
                    gap: 15px;
                    padding: 18px 20px;
                    margin: 15px 0 20px;
                    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
                    border: 1px solid #f59e0b;
                    border-left: 5px solid #d97706;
                    border-radius: 12px;
                    animation: reminderFadeIn 0.4s ease;
                }
                @keyframes reminderFadeIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .online-payment-reminder .reminder-icon {
                    flex-shrink: 0;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f59e0b;
                    color: white;
                    border-radius: 50%;
                    font-size: 18px;
                }
                .online-payment-reminder .reminder-content {
                    flex: 1;
                }
                .online-payment-reminder .reminder-content h6 {
                    margin: 0 0 10px;
                    color: #92400e;
                    font-weight: 700;
                    font-size: 0.95rem;
                }
                .online-payment-reminder .reminder-content p {
                    margin: 0 0 10px;
                    color: #78350f;
                    font-size: 0.85rem;
                    line-height: 1.6;
                }
                .online-payment-reminder .reminder-content p:last-child {
                    margin-bottom: 0;
                }
                .online-payment-reminder .reminder-content p i {
                    margin-right: 6px;
                    width: 16px;
                    text-align: center;
                }
                .online-payment-reminder .reminder-rx {
                    background: rgba(220, 38, 38, 0.08);
                    border-left: 3px solid #dc2626;
                    padding: 10px 14px;
                    border-radius: 6px;
                    color: #991b1b !important;
                }
                .online-payment-reminder .reminder-general {
                    background: rgba(13, 148, 136, 0.06);
                    border-left: 3px solid #0d9488;
                    padding: 10px 14px;
                    border-radius: 6px;
                }
                .online-payment-reminder .reminder-note {
                    font-style: italic;
                    opacity: 0.85;
                    font-size: 0.82rem !important;
                }
                
                .reminder-acknowledge {
                    margin-top: 14px;
                    padding-top: 14px;
                    border-top: 1px dashed #d97706;
                }
                .acknowledge-label {
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                    cursor: pointer;
                    padding: 12px 16px;
                    background: #fff;
                    border: 2px solid #e5e7eb;
                    border-radius: 10px;
                    transition: all 0.3s ease;
                    margin: 0;
                    user-select: none;
                }
                .acknowledge-label:hover {
                    border-color: #f59e0b;
                    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15);
                }
                .acknowledge-label input[type="checkbox"] {
                    position: absolute;
                    opacity: 0;
                    pointer-events: none;
                }
                .acknowledge-checkmark {
                    flex-shrink: 0;
                    width: 26px;
                    height: 26px;
                    border: 2px solid #d1d5db;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.25s ease;
                    background: #f9fafb;
                    margin-top: 2px;
                }
                .acknowledge-checkmark i {
                    font-size: 13px;
                    color: transparent;
                    transition: color 0.2s ease;
                }
                .acknowledge-label input[type="checkbox"]:checked ~ .acknowledge-checkmark {
                    background: #059669;
                    border-color: #059669;
                    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.35);
                }
                .acknowledge-label input[type="checkbox"]:checked ~ .acknowledge-checkmark i {
                    color: #fff;
                }
                .acknowledge-label input[type="checkbox"]:checked ~ .acknowledge-text {
                    color: #065f46;
                }
                .acknowledge-text {
                    font-size: 0.85rem;
                    color: #78350f;
                    line-height: 1.55;
                    transition: color 0.2s ease;
                }
                .acknowledge-hint {
                    font-size: 0.8rem;
                    color: #dc2626;
                    margin: 8px 0 0;
                    animation: pulseHint 2s ease-in-out infinite;
                }
                .acknowledge-hint.hidden {
                    display: none;
                }
                @keyframes pulseHint {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.5; }
                }
                
                .urgent-label {
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                    padding: 14px 16px;
                    background: linear-gradient(135deg, #fffbeb 0%, #fef9c3 100%);
                    border: 2px solid #fbbf24;
                    border-radius: 10px;
                    cursor: pointer;
                    margin: 0;
                    user-select: none;
                    transition: all 0.3s ease;
                }
                .urgent-label:hover {
                    border-color: #f59e0b;
                    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
                }
                .urgent-label input[type="checkbox"] {
                    position: absolute;
                    opacity: 0;
                    pointer-events: none;
                }
                .urgent-checkmark {
                    flex-shrink: 0;
                    width: 26px;
                    height: 26px;
                    border: 2px solid #d1d5db;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.25s ease;
                    background: #f9fafb;
                    margin-top: 2px;
                }
                .urgent-checkmark i {
                    font-size: 13px;
                    color: transparent;
                    transition: color 0.2s ease;
                }
                .urgent-label input[type="checkbox"]:checked ~ .urgent-checkmark {
                    background: #059669;
                    border-color: #059669;
                    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.35);
                }
                .urgent-label input[type="checkbox"]:checked ~ .urgent-checkmark i {
                    color: #fff;
                }
                .urgent-label input[type="checkbox"]:checked ~ .urgent-text .urgent-title {
                    color: #065f46;
                }
                .urgent-text {
                    flex: 1;
                }
                .urgent-title {
                    font-weight: 700;
                    color: #92400e;
                    font-size: 0.9rem;
                    display: block;
                    transition: color 0.2s ease;
                }
                .urgent-description {
                    margin: 4px 0 0;
                    font-size: 0.78rem;
                    color: #78350f;
                    line-height: 1.4;
                }
                .btn-pay.btn-disabled-reminder {
                    opacity: 0.5 !important;
                    cursor: not-allowed !important;
                    transform: none !important;
                    background: #9ca3af !important;
                    box-shadow: none !important;
                    pointer-events: none;
                }
                @media (max-width: 576px) {
                    .online-payment-reminder {
                        flex-direction: column;
                        align-items: flex-start;
                        padding: 14px;
                    }
                    .online-payment-reminder .reminder-icon {
                        width: 32px;
                        height: 32px;
                        font-size: 14px;
                    }
                    .acknowledge-label {
                        padding: 10px 12px;
                        gap: 10px;
                    }
                }
                </style>
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
                <?php  ?>
                
                <div class="form-section" style="margin-top: 2rem;">
                    <div class="urgent-label" id="urgent-click-target" style="cursor: pointer;">
                        <input type="checkbox" id="is_urgent" name="is_urgent" value="1">
                        <span class="urgent-checkmark" id="urgent-visual-check"><i class="fas fa-check"></i></span>
                        <div class="urgent-text">
                            <span class="urgent-title">
                                <i class="fas fa-bolt" id="urgent-bolt-icon" style="margin-right: 4px; color: #d97706;"></i> Mark as Urgent Order
                            </span>
                            <p class="urgent-description">
                                Check this if you need your order prepared as soon as possible. The pharmacy will prioritize your order.
                            </p>
                        </div>
                    </div>
                </div>

                
                <div style="margin-top: 2.5rem; display: flex; flex-direction: column-reverse; gap: 1rem;">
                    <a href="<?php echo base_url ?>?p=customer/categories" class="btn-back">
                        <i class="fa-solid fa-chevron-left"></i> Continue Shopping
                    </a>
                    <?php if($has_missing_profile): ?>
                    <a href="<?php echo base_url ?>?p=customer/profile" class="btn-pay" style="text-align: center; text-decoration: none; background: #f59e0b; border-color: #d97706; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-user-edit"></i>
                        Complete Details to Checkout
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <button type="submit" class="btn-pay">
                        <i class="fa-solid fa-lock"></i>
                        Place Order
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <?php endif; ?>
                </div>
                
            </form>
        </div>

        
        <div class="summary-panel">
            <div style="position: sticky; top: 1.5rem;">
                
                <h3 class="summary-title">Order Summary</h3>

                
                <div class="cart-selection-controls">
                    <div class="selection-actions">
                        <button type="button" id="select-all-items" class="selection-btn">
                            <i class="fa-solid fa-check-double"></i> Select All
                        </button>
                        <button type="button" id="clear-selection" class="selection-btn">
                            <i class="fa-solid fa-times"></i> Clear
                        </button>
                    </div>
                    <div class="selection-summary">
                        <span class="selected-count"><span id="selected-items-count">0</span> selected</span>
                        <span class="selected-total" id="selected-items-total">₱0.00</span>
                    </div>
                </div>

                
                <div id="cart-items-summary" class="cart-items-summary">
                    
                    <div class="loading-items" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: #0d9488;"></i>
                        <p style="margin-top: 10px; color: #6b7280; font-size: 0.875rem;">Loading cart items...</p>
                    </div>
                </div>

                
                <div class="summary-totals">
                    <div class="summary-row">
                        <span class="label">Subtotal</span>
                        <span class="value" id="summary-subtotal">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Pickup</span>
                        <span class="value free">Free</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Tax</span>
                        <span class="value" id="summary-tax">₱0.00</span>
                    </div>
                </div>

                
                <div class="summary-total">
                    <span class="label">Total</span>
                    <div class="value">
                        <span class="currency">PHP</span>
                        <span class="amount" id="summary-total">₱0.00</span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<div class="ewallet-popup-overlay" id="ewalletPopupOverlay">
    <div class="ewallet-popup">
        <div class="ewallet-popup-header">
            <div class="ewallet-popup-icon">
                <i class="fa-solid fa-mobile-screen"></i>
            </div>
            <h3 class="ewallet-popup-title">Choose Payment Method</h3>
            <p class="ewallet-popup-subtitle">Select your preferred e-wallet to continue</p>
        </div>
        <div class="ewallet-popup-grid">
            <button type="button" class="ewallet-choice" data-provider="gcash">
                <div class="ewallet-choice-badge" style="background: #0066ff;">G</div>
                <div class="ewallet-choice-info">
                    <span class="ewallet-choice-name">GCash</span>
                    <span class="ewallet-choice-desc">Pay via GCash e-wallet</span>
                </div>
                <i class="fa-solid fa-chevron-right ewallet-choice-arrow"></i>
            </button>
            <button type="button" class="ewallet-choice" data-provider="maya">
                <div class="ewallet-choice-badge" style="background: #00b14f;">M</div>
                <div class="ewallet-choice-info">
                    <span class="ewallet-choice-name">Maya</span>
                    <span class="ewallet-choice-desc">Pay via Maya e-wallet</span>
                </div>
                <i class="fa-solid fa-chevron-right ewallet-choice-arrow"></i>
            </button>
        </div>
        <button type="button" class="ewallet-popup-cancel" id="ewalletPopupCancel">
            <i class="fa-solid fa-times"></i> Cancel
        </button>
    </div>
</div>

<style>

.ewallet-popup-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
}
.ewallet-popup-overlay.active {
    display: flex;
}
.ewallet-popup {
    background: white;
    border-radius: 16px;
    padding: 28px;
    max-width: 380px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: ewalletPopIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes ewalletPopIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.ewallet-popup-header {
    text-align: center;
    margin-bottom: 24px;
}
.ewallet-popup-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    box-shadow: 0 4px 20px rgba(13,148,136,0.3);
}
.ewallet-popup-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 6px;
}
.ewallet-popup-subtitle {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}
.ewallet-popup-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}
.ewallet-choice {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    transition: all 0.25s ease;
    text-align: left;
    width: 100%;
}
.ewallet-choice:hover {
    border-color: #0d9488;
    background: #f0fdfa;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13,148,136,0.15);
}
.ewallet-choice:active {
    transform: translateY(0);
}
.ewallet-choice-badge {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.ewallet-choice-info {
    flex: 1;
}
.ewallet-choice-name {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
}
.ewallet-choice-desc {
    display: block;
    font-size: 0.78rem;
    color: #6b7280;
    margin-top: 2px;
}
.ewallet-choice-arrow {
    color: #9ca3af;
    font-size: 0.9rem;
    transition: color 0.2s;
}
.ewallet-choice:hover .ewallet-choice-arrow {
    color: #0d9488;
}
.ewallet-popup-cancel {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: #f3f4f6;
    color: #4b5563;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.ewallet-popup-cancel:hover {
    background: #e5e7eb;
}

.ewallet-selected-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    color: white;
    margin-left: 8px;
    vertical-align: middle;
}

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

<?php endif; ?>

<script>

window._base_url_ = '<?php echo base_url ?>';
window.profileMissing = <?php echo json_encode($profile_missing_details); ?>;
</script>
<script src="<?php echo base_url ?>customer/assets/js/cart_list.js?v=<?php echo time(); ?>"></script>
