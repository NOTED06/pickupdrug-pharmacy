<?php

?>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link rel="stylesheet" href="<?php echo base_url ?>assets/css/global-action-states.css">

<link rel="stylesheet" href="<?php echo base_url ?>assets/css/shared/topbar-nav.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/shared/topbar-nav.css') ?: time(); ?>">

<?php

$system_logo = validate_image($_settings->info('logo'));
$system_name = $_settings->info('name') ?: 'PickUpDrug';
$short_name = $_settings->info('short_name') ?: 'PD';

$cart_count = 0;
if ($_settings->userdata('id') != '' && $_settings->userdata('login_type') == 2) {
    $customer_id = $_settings->userdata('id');
    $cart_count = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total FROM cart_list WHERE customer_id = '{$customer_id}'")->fetch_assoc()['total'];
}
?>

<nav class="customer-navbar">
    
    <a href="<?php echo base_url ?>?p=customer/home" class="nav-brand">
        <div class="logo-box" style="background: transparent; box-shadow: none; overflow: hidden;">
            <img src="<?php echo $system_logo ?>" alt="<?php echo $system_name ?>" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <span class="brand-text"><?php echo $system_name ?></span>
    </a>

    
    <div class="nav-actions">
        
        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search medicines..." id="navSearchInput" autocomplete="off">
            <div class="search-icon-fixed"><i class="fa-solid fa-magnifying-glass"></i></div>
            <div class="search-results-dropdown" id="searchResultsDropdown"></div>
        </div>
        
        <?php if($_settings->userdata('id') != '' && $_settings->userdata('login_type') == 2): ?>
        
        <div class="nav-group notification-icon">
            <button class="nav-icon-btn" id="notificationIcon" type="button">
                <i class="fa-regular fa-bell"></i>
                <span class="badge notification-count" id="notificationCount" style="display: none;">0</span>
            </button>
            <div class="dropdown-menu" id="notificationDropdown">
                <div class="notif-header">
                    <h4>Notifications</h4>
                    <span data-action="mark-all-read">Mark all read</span>
                </div>
                <div class="notif-list" id="notificationList">
                    <div class="notif-item">
                        <div class="notif-icon bg-red-100 text-red-500">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </div>
                        <div class="notif-content">
                            <p class="notif-title">Prescription Required</p>
                            <p class="notif-text">Please upload Rx for Antibiotics.</p>
                        </div>
                    </div>
                    <div class="notif-item">
                        <div class="notif-icon bg-green-100 text-green-600">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <div class="notif-content">
                            <p class="notif-title">Order Ready for Pickup</p>
                            <p class="notif-text">Order #20251226 is ready.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <button class="nav-icon-btn" data-action="toggle-cart" type="button">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="badge cart-count" id="cartBadge"><?php echo $cart_count ?></span>
        </button>
        
        
        <div class="user-menu">
            <button class="nav-icon-btn active" data-action="toggle-user-menu">
                <i class="fa-regular fa-user"></i>
            </button>
            
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($_settings->userdata('firstname') ?? '', 0, 1) . substr($_settings->userdata('lastname') ?? '', 0, 1)); ?>
                    </div>
                    <div class="user-name"><?php echo ($_settings->userdata('firstname') ?? '') . ' ' . ($_settings->userdata('lastname') ?? ''); ?></div>
                    <div class="user-email"><?php echo $_settings->userdata('email') ?? ''; ?></div>
                </div>
                <div class="dropdown-links">
                    <a href="<?php echo base_url ?>?p=customer/profile" class="dropdown-item">
                        <i class="fa-regular fa-user"></i> My Account
                    </a>
                    <a href="<?php echo base_url ?>?p=orders" class="dropdown-item">
                        <i class="fa-solid fa-box-open"></i> My Orders
                    </a>
                    <a href="<?php echo base_url ?>?p=customer/prescriptions" class="dropdown-item">
                        <i class="fa-solid fa-prescription-bottle-medical"></i> My Prescriptions
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo base_url ?>classes/Login.php?f=logout_customer" class="dropdown-item logout">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>

<div id="cart-overlay" data-action="toggle-cart"></div>

<div id="cart-drawer">
    <div class="cart-drawer-header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(13, 148, 136, 0.1); color: #0d9488; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <div>
                <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin: 0; font-family: 'Orbitron', sans-serif;">Your Cart</h2>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0;" id="cartItemCount">0 items</p>
            </div>
        </div>
        <button data-action="toggle-cart" style="width: 40px; height: 40px; border-radius: 12px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fef2f2'; this.style.color='#ef4444'" onmouseout="this.style.background='#f3f4f6'; this.style.color='#1f2937'">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div id="cartSelectionBar" class="cart-selection-bar" style="display: none;">
        <div class="cart-selection-actions">
            <button type="button" id="selectAllCartItems" class="cart-selection-btn">
                <i class="fa-solid fa-check-double"></i> All
            </button>
            <button type="button" id="clearCartSelection" class="cart-selection-btn">
                <i class="fa-solid fa-times"></i> Clear
            </button>
        </div>
        <div class="cart-selection-summary">
            <span class="cart-selected-count"><span id="selectedCartCount">0</span> selected</span>
            <span class="cart-selected-total" id="selectedCartTotal">₱0.00</span>
        </div>
    </div>

    <div class="cart-drawer-items" id="cartDrawerItems">
        <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
            <i class="fa-solid fa-cart-shopping" style="font-size: 3rem; opacity: 0.3; margin-bottom: 16px;"></i>
            <p>Your cart is empty</p>
        </div>
    </div>

    <div class="cart-drawer-footer">
        <div class="cart-drawer-total">
            <div id="cartDrawerFooterAll" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">
                <span style="font-size: 0.875rem; color: #6b7280;">All Items</span>
                <span style="font-size: 0.875rem; color: #6b7280; font-family: 'Rajdhani', sans-serif;" id="cartDrawerTotalAll">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 1.125rem; font-weight: bold; color: #1f2937;">Selected Total</span>
                <span style="font-size: 1.5rem; font-weight: bold; color: #0d9488; font-family: 'Orbitron', sans-serif;" id="cartDrawerTotal">₱0.00</span>
            </div>
        </div>
        <button class="cart-drawer-checkout" data-action="navigate" data-url="<?php echo base_url ?>?p=customer/cart_list">
            View Cart & Checkout <i class="fa-solid fa-arrow-right"></i>
        </button>
        <p style="text-align: center; font-size: 0.75rem; color: #9ca3af; margin-top: 12px;">🔒 Secure checkout</p>
    </div>
</div>

<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-popup" id="confirmPopup">
        <div class="confirm-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 class="confirm-title"></h3>
        <p class="confirm-message"></p>
        <div class="confirm-actions">
            <button class="confirm-btn confirm-btn-cancel" data-action="close-confirm-popup">
                <i class="fa-solid fa-times"></i> Cancel
            </button>
            <button class="confirm-btn confirm-btn-confirm">
                <i class="fa-solid fa-trash"></i> <span id="confirmBtnText">Remove</span>
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="prescriptionUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title"><i class="fas fa-upload mr-2"></i>Upload Prescription</h5>
                <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <form id="prescriptionUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Prescription Image/Document</label>
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="prescriptionFile" 
                                   name="prescription_file" 
                                   accept="image/*,.pdf"
                                   onchange="handleFileSelect(event)"
                                   required>
                            <label class="custom-file-label" for="prescriptionFile" id="fileName">Choose file</label>
                        </div>
                        <small class="form-text text-muted">Accepted: JPG, PNG, PDF (Max 5MB)</small>
                    </div>
                    
                    <div id="topBarPreviewContainer" class="text-center mb-3" style="display: none;"></div>
                    
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea class="form-control" 
                                  name="notes" 
                                  rows="3" 
                                  placeholder="Add any additional information about your prescription..."></textarea>
                    </div>
                    
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input" id="autoCreateReminder" name="auto_create_reminder" value="1">
                        <label class="custom-control-label" for="autoCreateReminder">
                            Automatically create medication reminders from this prescription
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e5e7eb;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" data-action="handle-prescription-upload" style="background: #0d9488; border: none;">
                    <i class="fas fa-upload mr-1"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var origin = window.location.origin;
        var base = (origin.indexOf('localhost') !== -1 || origin.indexOf('127.0.0.1') !== -1) ? origin + '/pickupdrug/' : origin + '/';
        window.base_url = base;
        window.BASE_URL = base;
        window._base_url_ = base;
    })();
</script>

<script src="<?php echo base_url ?>assets/js/global-action-handler.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/global-action-handler.js') ?: time(); ?>"></script>

<script src="<?php echo base_url ?>assets/js/shared/topbar-nav.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/shared/topbar-nav.js') ?: time(); ?>"></script>
