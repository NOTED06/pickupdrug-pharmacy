

let cartState = {
    items: [],
    hasRxProducts: false,
    prescriptionUploaded: false,
    prescriptionFile: null
};

$(document).ready(function() {
    console.log('[Cart] Initializing cart list page');
    
    
    initPaymentMethods();
    initPrescriptionUpload();
    initCheckoutForm();
    initUrgentToggle();
    initCartSelection();
    initQuantityControls();
    initRemoveButtons();
    setMinimumPickupTime();
    

    loadCartSummary();
});

window.loadCartSummary = loadCartSummary;
window.updateCartBadge = updateCartBadge;

function loadCartSummary() {
    console.log('[Cart] Loading cart summary');
    
    $.ajax({
        url: _base_url_ + 'ajax.php?action=get_cart_summary',
        method: 'GET',
        dataType: 'json',
        success: function(resp) {
            console.log('[Cart] Cart summary loaded:', resp);
            
            if (resp && resp.status === 'success') {
                cartState.items = resp.items || [];
                cartState.hasRxProducts = resp.has_rx_products || false;
                
                renderCartItems(resp.items);
                updateTotals(resp.subtotal, resp.tax, resp.total);
                restoreCartSelection(); 
            } else {
                showError('Failed to load cart');
            }
        },
        error: function(xhr, status, error) {
            console.error('[Cart] Error loading cart:', error);
            showError('Network error. Please refresh the page.');
        }
    });
}

function renderCartItems(items) {
    const container = $('#cart-items-summary');
    container.empty();
    
    if (!items || items.length === 0) {
        container.html(`
            <div class="empty-cart-message">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
            </div>
        `);
        return;
    }
    
    items.forEach(function(item) {
        const imgPath = item.image_path ? _base_url_ + item.image_path : _base_url_ + 'uploads/no-image-available.png';
        const rxBadge = item.is_rx ? '<span class="rx-badge"><i class="fas fa-prescription"></i> Rx</span>' : '';
        
        const itemHtml = `
            <div class="cart-summary-item" 
                 data-cart-id="${item.cart_id}" 
                 data-product-id="${item.product_id}" 
                 data-item-total="${item.total}"
                 data-is-rx="${item.is_rx ? '1' : '0'}">
                <label class="item-checkbox-wrapper">
                    <input type="checkbox" class="cart-item-checkbox" 
                           data-cart-id="${item.cart_id}" 
                           data-product-id="${item.product_id}" 
                           data-price="${item.total}">
                    <span class="checkbox-custom"></span>
                </label>
                <div class="item-image-box">
                    <img src="${imgPath}" alt="${item.name}" 
                         onerror="this.src='${_base_url_}uploads/no-image-available.png'">
                </div>
                <div class="item-details">
                    <h4 class="item-name">${item.name}</h4>
                    <p class="item-category">${item.category || 'General'} • <span class="item-unit-price">₱${parseFloat(item.price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></p>
                    ${rxBadge}
                    <div class="item-quantity-controls">
                        <button type="button" class="qty-btn qty-decrease" data-cart-id="${item.cart_id}" data-quantity="${item.quantity}">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="qty-display" data-cart-id="${item.cart_id}" value="${item.quantity}" min="0" max="999">
                        <button type="button" class="qty-btn qty-increase" data-cart-id="${item.cart_id}" data-quantity="${item.quantity}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="item-actions">
                    <span class="item-price">₱${parseFloat(item.total || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    <button type="button" class="item-remove" data-cart-id="${item.cart_id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
        container.append(itemHtml);
    });
}

function updateTotals(subtotal, tax, total) {
    $('#summary-subtotal').text('₱' + parseFloat(subtotal || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#summary-tax').text('₱' + parseFloat(tax || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#summary-total').text('₱' + parseFloat(total || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#checkout-total-amount').val(total);
}

function confirmRemoveCartItem(cartId, onConfirm, onCancel) {
    const overlay = document.getElementById('confirmOverlay');
    const popup = document.getElementById('confirmPopup');
    const title = document.querySelector('.confirm-title');
    const message = document.querySelector('.confirm-message');
    const confirmBtn = document.querySelector('.confirm-btn-confirm');
    const confirmBtnText = document.getElementById('confirmBtnText');
    
    if (overlay && popup && confirmBtn) {
        if (title) title.textContent = 'Remove Item?';
        if (message) message.textContent = 'Are you sure you want to remove this item from your cart?';
        if (confirmBtnText) confirmBtnText.textContent = 'Remove';
        
        let isConfirmed = false;
        window.onCancelRemovalCallback = function() {
            if (!isConfirmed && typeof onCancel === 'function') {
                onCancel();
            }
        };
        
        confirmBtn.onclick = function() {
            isConfirmed = true;
            window.onCancelRemovalCallback = null;
            if (typeof closeConfirmPopup === 'function') {
                closeConfirmPopup();
            } else {
                popup.classList.add('closing');
                setTimeout(() => { overlay.classList.remove('active'); }, 200);
            }
            if (typeof onConfirm === 'function') {
                onConfirm();
            } else {
                removeCartItem(cartId);
            }
        };
        
        popup.classList.remove('closing');
        overlay.classList.add('active');
    } else {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            if (typeof onConfirm === 'function') {
                onConfirm();
            } else {
                removeCartItem(cartId);
            }
        } else {
            if (typeof onCancel === 'function') {
                onCancel();
            }
        }
    }
}

let qtyThrottle = {};
function initQuantityControls() {
    
    $(document).off('click.cartCancel', '.confirm-btn-cancel').on('click.cartCancel', '.confirm-btn-cancel', function(e) {
        e.preventDefault();
        if (typeof closeConfirmPopup === 'function') {
            closeConfirmPopup();
        } else {
            const overlay = document.getElementById('confirmOverlay');
            const popup = document.getElementById('confirmPopup');
            if (popup) popup.classList.add('closing');
            setTimeout(() => { if (overlay) overlay.classList.remove('active'); }, 200);
            if (typeof window.onCancelRemovalCallback === 'function') {
                window.onCancelRemovalCallback();
                window.onCancelRemovalCallback = null;
            }
        }
    });

    
    $(document).on('click', '.qty-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        const cartId = btn.data('cart-id');
        const currentQty = parseInt(btn.data('quantity')) || 1;
        const isIncrease = btn.hasClass('qty-increase');
        
        
        if (!isIncrease && currentQty <= 1) {
            confirmRemoveCartItem(cartId, function() {
                removeCartItem(cartId);
            });
            return;
        }
        
        const newQty = isIncrease ? currentQty + 1 : currentQty - 1;
        
        
        const now = Date.now();
        if (qtyThrottle[cartId] && (now - qtyThrottle[cartId] < 400)) {
            return;
        }
        qtyThrottle[cartId] = now;
        
        
        const $qtyTarget = btn.closest('.item-details').find('.qty-display');
        if ($qtyTarget.is('input')) {
            $qtyTarget.val(newQty);
        } else {
            $qtyTarget.text(newQty);
        }
        btn.data('quantity', newQty);
        btn.siblings('.qty-btn').data('quantity', newQty);
        
        
        updateCartQuantity(cartId, newQty);
    });

    
    $(document).on('change', 'input.qty-display', function(e) {
        const input = $(this);
        const cartId = input.data('cart-id');
        const prevQty = parseInt(input.siblings('.qty-btn').data('quantity'), 10) || 1;
        let val = parseInt(input.val(), 10);
        
        if (isNaN(val) || val <= 0) {
            confirmRemoveCartItem(
                cartId,
                function() {
                    removeCartItem(cartId);
                },
                function() {
                    input.val(prevQty);
                    input.siblings('.qty-btn').data('quantity', prevQty);
                }
            );
            return;
        }
        
        input.val(val);
        input.siblings('.qty-btn').data('quantity', val);
        updateCartQuantity(cartId, val);
    });
}

function updateCartQuantity(cartId, quantity) {
    $.ajax({
        url: _base_url_ + 'ajax.php?action=update_cart_qty',
        method: 'POST',
        data: { cart_id: cartId, id: cartId, quantity: quantity, qty: quantity },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                
                loadCartSummary();
                updateCartBadge();
                if (typeof loadCartDrawer === 'function') {
                    loadCartDrawer();
                }
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            } else {
                alert_toast(resp.msg || 'Failed to update quantity', 'error');
                loadCartSummary(); 
            }
        },
        error: function() {
            alert_toast('Network error', 'error');
            loadCartSummary();
        }
    });
}

function initRemoveButtons() {
    $(document).on('click', '.item-remove', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        const cartId = btn.data('cart-id');
        
        confirmRemoveCartItem(cartId, function() {
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            removeCartItem(cartId);
        });
    });
}

function removeCartItem(cartId) {
    
    const saved = sessionStorage.getItem('cartSelection');
    if (saved) {
        try {
            let selectedIds = JSON.parse(saved);
            selectedIds = selectedIds.filter(id => id != cartId);
            sessionStorage.setItem('cartSelection', JSON.stringify(selectedIds));
        } catch(e) {}
    }
    
    
    const $itemElement = $(`.cart-summary-item[data-cart-id="${cartId}"]`);
    if ($itemElement.length > 0) {
        $itemElement.fadeOut(200, function() {
            $(this).remove();
            if ($('.cart-summary-item').length === 0) {
                $('#cart-items-summary').html(`
                    <div class="empty-cart-message">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Your cart is empty</p>
                    </div>
                `);
            }
            if (typeof updateSelectionSummary === 'function') {
                updateSelectionSummary();
            }
        });
    }
    
    $.ajax({
        url: _base_url_ + 'ajax.php?action=delete_cart',
        method: 'POST',
        data: { id: cartId, cart_id: cartId },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                alert_toast('Item removed from cart', 'success');
                loadCartSummary();
                updateCartBadge();
                if (typeof loadCartDrawer === 'function') {
                    loadCartDrawer();
                }
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            } else {
                alert_toast(resp.msg || 'Failed to remove item', 'error');
                loadCartSummary();
            }
        },
        error: function() {
            alert_toast('Network error', 'error');
            loadCartSummary();
        }
    });
}

function updatePrescriptionRequirement() {
    const prescriptionSection = $('.form-section').has('#prescription-section-title');
    const prescriptionAlert = $('#prescription-alert');
    const uploadArea = $('#upload-area');
    const placeOrderBtn = $('.btn-pay');
    
    if (cartState.hasRxProducts) {
        
        prescriptionSection.show();
        prescriptionAlert.show();
        uploadArea.addClass('required');
        $('#prescription-file').prop('required', true);
        $('#prescription-section-title').html('<i class="fa-solid fa-file-prescription"></i> Prescription Upload <span style="color: #ef4444; font-weight: 700;">(Required)</span>');
        $('#prescription-required-input').val('1');
        
        
        disableEwalletForRx(true);
        
        
        updateCheckoutButton();
    } else {
        
        prescriptionSection.hide();
        prescriptionAlert.hide();
        uploadArea.removeClass('required');
        $('#prescription-file').prop('required', false);
        $('#prescription-required-input').val('0');
        
        
        disableEwalletForRx(false);
        
        
        placeOrderBtn.prop('disabled', false).removeClass('disabled');
    }
}

function disableEwalletForRx(disable) {
    const ewalletRadio = $('input[name="payment_method"][value="ewallet"]');
    const ewalletOption = ewalletRadio.closest('.payment-option');
    const rxBanner = $('.rx-payment-notice');
    const rxBadge = ewalletOption.find('.rx-disabled-badge');
    
    if (disable) {
        ewalletRadio.prop('disabled', true);
        ewalletOption.css({'opacity': '0.45', 'pointer-events': 'none', 'position': 'relative'}).addClass('disabled-payment');
        rxBanner.slideDown(300);
        rxBadge.show();
        
        
        if (ewalletRadio.is(':checked')) {
            $('input[name="payment_method"][value="cod"]').prop('checked', true).trigger('change');
        }
    } else {
        ewalletRadio.prop('disabled', false);
        ewalletOption.css({'opacity': '', 'pointer-events': '', 'position': ''}).removeClass('disabled-payment');
        rxBanner.slideUp(200);
        rxBadge.hide();
    }
}

function updateCheckoutButton() {
    const placeOrderBtn = $('.btn-pay');
    
    if (cartState.hasRxProducts && !cartState.prescriptionUploaded) {
        placeOrderBtn.prop('disabled', true).addClass('disabled');
        placeOrderBtn.find('i:last').removeClass('fa-arrow-right').addClass('fa-lock');
    } else {
        placeOrderBtn.prop('disabled', false).removeClass('disabled');
        placeOrderBtn.find('i:last').removeClass('fa-lock').addClass('fa-arrow-right');
    }
}

function initPrescriptionUpload() {
    
    $('#upload-area').on('click', function(e) {
        if (e.target.id !== 'prescription-file') {
            $('#prescription-file').trigger('click');
        }
    });
    
    
    $('#prescription-file').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        
        if (file.size > 5 * 1024 * 1024) {
            alert_toast('File size must be less than 5MB', 'error');
            return;
        }
        
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            alert_toast('Only JPG, PNG, or PDF files are allowed', 'error');
            return;
        }
        
        cartState.prescriptionFile = file;
        uploadPrescription(file);
    });
}

function uploadPrescription(file) {
    const formData = new FormData();
    formData.append('prescription_file', file);
    
    
    $('#upload-placeholder').hide();
    $('#upload-success').show();
    $('#filename-display').text(file.name);
    $('#validation-status')
        .removeClass('matched partial mismatch')
        .addClass('validating')
        .html('<i class="fas fa-spinner fa-spin"></i> Uploading...')
        .show();
    
    console.log('[Prescription] Uploading to:', _base_url_ + 'ajax.php?action=upload_cart_prescription');
    console.log('[Prescription] File:', file.name, file.size, file.type);
    
    
    $('#validation-status')
        .removeClass('matched partial mismatch')
        .addClass('validating')
        .html('<i class="fas fa-spinner fa-spin"></i> Validating prescription with AI...')
        .show();
    
    $.ajax({
        url: _base_url_ + 'ajax.php?action=upload_cart_prescription',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 120000, 
        success: function(resp) {
            console.log('[Prescription] Response:', resp);
            if (resp.status === 'success') {
                cartState.prescriptionUploaded = true;
                
                
                if (resp.validation_status) {
                    $('#prescription_validation').val(resp.validation_status);
                }
                if (resp.validation) {
                    $('#prescription_details').val(JSON.stringify(resp.validation));
                }
                
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#prescription-preview').attr('src', e.target.result).show();
                    };
                    reader.readAsDataURL(file);
                }
                
                
                let statusClass = 'matched';
                let statusIcon = 'check-circle';
                let statusMsg = resp.msg || 'Prescription Uploaded Successfully';
                
                if (resp.validation_status === 'partial') {
                    statusClass = 'matched';
                    statusIcon = 'check-circle';
                    statusMsg = 'Prescription Verified (Cart item matched)';
                } else if (resp.validation_status === 'matched') {
                    statusClass = 'matched';
                    statusIcon = 'check-circle';
                    statusMsg = 'Perfect Match! Prescription verified';
                } else if (resp.validation_status === 'mismatch') {
                    statusClass = 'mismatch';
                    statusIcon = 'times-circle';
                    statusMsg = 'Mismatch - ' + (resp.notes || 'Prescription does not match your cart items');
                }
                
                
                let detailsHtml = '';
                if (resp.prescribed_medicines && resp.prescribed_medicines.length > 0) {
                    detailsHtml = '<div style="font-size: 0.75rem; margin-top: 5px; color: #64748b;">';
                    detailsHtml += '<strong>Detected:</strong> ' + resp.prescribed_medicines.join(', ');
                    detailsHtml += '</div>';
                }
                
                $('#validation-status')
                    .removeClass('validating mismatch partial matched')
                    .addClass(statusClass)
                    .html('<i class="fas fa-' + statusIcon + '"></i> ' + statusMsg + detailsHtml)
                    .show();
                
                $('#upload-area').addClass('active');
                
                
                updateCheckoutButton();
                
                alert_toast(resp.msg || 'Prescription uploaded successfully', 'success');
            } else {
                cartState.prescriptionUploaded = false;
                $('#prescription_validation').val('');
                $('#prescription_details').val('');
                
                
                let errorMsg = resp.msg || 'Upload failed';
                let detailsHtml = '';
                
                if (resp.validation_status === 'invalid') {
                    
                    detailsHtml += '<div style="font-size: 0.75rem; margin-top: 5px; color: #fbbf24;">';
                    detailsHtml += '<i class="fas fa-exclamation-triangle"></i> ' + (resp.reason || 'Please upload a clear photo of a medical prescription.');
                    detailsHtml += '</div>';
                } else if (resp.validation_status === 'mismatch') {
                    if (resp.prescribed_medicines && resp.prescribed_medicines.length > 0) {
                        detailsHtml += '<div style="font-size: 0.75rem; margin-top: 5px;">';
                        detailsHtml += '<strong>Prescription contains:</strong> ' + resp.prescribed_medicines.join(', ');
                        detailsHtml += '</div>';
                    }
                    if (resp.cart_products && resp.cart_products.length > 0) {
                        detailsHtml += '<div style="font-size: 0.75rem; margin-top: 3px;">';
                        detailsHtml += '<strong>Your cart has:</strong> ' + resp.cart_products.join(', ');
                        detailsHtml += '</div>';
                    }
                    if (resp.notes) {
                        detailsHtml += '<div style="font-size: 0.75rem; margin-top: 3px; font-style: italic;">';
                        detailsHtml += resp.notes;
                        detailsHtml += '</div>';
                    }
                } else if (resp.reason) {
                    
                    detailsHtml += '<div style="font-size: 0.75rem; margin-top: 5px; color: #f87171;">';
                    detailsHtml += resp.reason;
                    detailsHtml += '</div>';
                }
                
                $('#validation-status')
                    .removeClass('validating matched partial')
                    .addClass('mismatch')
                    .html('<i class="fas fa-times-circle"></i> ' + errorMsg + detailsHtml)
                    .show();
                
                alert_toast(errorMsg, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('[Prescription] AJAX Error:', status, error);
            console.error('[Prescription] Response Text:', xhr.responseText);
            console.error('[Prescription] Status Code:', xhr.status);
            
            cartState.prescriptionUploaded = false;
            $('#prescription_validation').val('');
            $('#prescription_details').val('');
            
            let errorMsg = 'Network error';
            if (xhr.status === 0) {
                errorMsg = 'Connection failed - check server';
            } else if (xhr.status === 413) {
                errorMsg = 'File too large';
            } else if (xhr.status === 500) {
                errorMsg = 'Server error - check PHP logs';
            } else if (status === 'parsererror') {
                errorMsg = 'Invalid response from server';
                console.error('[Prescription] Raw response:', xhr.responseText);
            } else if (status === 'timeout') {
                errorMsg = 'Validation timed out - please try again';
            }
            
            $('#validation-status')
                .removeClass('validating matched partial')
                .addClass('mismatch')
                .html('<i class="fas fa-times-circle"></i> ' + errorMsg)
                .show();
            
            alert_toast(errorMsg, 'error');
        }
    });
}

function initCartSelection() {
    
    $(document).on('click', '#select-all-items', function() {
        $('.cart-item-checkbox').prop('checked', true);
        
        $('.cart-item-checkbox').each(function() {
            const cartId = $(this).data('cart-id');
            $(`.cart-drawer-item-checkbox[data-cart-id="${cartId}"]`).prop('checked', true);
        });
        updateSelectionSummary();
        saveCartSelection();
        
        if (typeof updateCartDrawerSelection === 'function') {
            updateCartDrawerSelection();
        }
    });
    
    
    $(document).on('click', '#clear-selection', function() {
        $('.cart-item-checkbox').prop('checked', false);
        
        $('.cart-item-checkbox').each(function() {
            const cartId = $(this).data('cart-id');
            $(`.cart-drawer-item-checkbox[data-cart-id="${cartId}"]`).prop('checked', false);
        });
        updateSelectionSummary();
        saveCartSelection();
        
        if (typeof updateCartDrawerSelection === 'function') {
            updateCartDrawerSelection();
        }
    });
    
    
    $(document).on('change', '.cart-item-checkbox', function() {
        const cartId = $(this).data('cart-id');
        const isChecked = $(this).is(':checked');
        
        const $drawerCheckbox = $(`.cart-drawer-item-checkbox[data-cart-id="${cartId}"]`);
        if ($drawerCheckbox.length > 0) {
            $drawerCheckbox.prop('checked', isChecked);
            
            if (typeof updateCartItemSelectedState === 'function') {
                updateCartItemSelectedState($drawerCheckbox);
            }
        }
        updateSelectionSummary();
        saveCartSelection();
        
        if (typeof updateCartDrawerSelection === 'function') {
            updateCartDrawerSelection();
        }
    });
}

function updateSelectionSummary() {
    const allCheckboxes = $('.cart-item-checkbox');
    const selected = $('.cart-item-checkbox:checked');
    const count = selected.length;
    let selectedTotal = 0;
    let selectedHasRx = false;
    
    
    allCheckboxes.each(function() {
        const $item = $(this).closest('.cart-summary-item');
        if ($(this).is(':checked')) {
            $item.show();
        } else {
            $item.hide();
        }
    });
    
    
    selected.each(function() {
        selectedTotal += parseFloat($(this).data('price')) || 0;
        
        const cartItem = $(this).closest('.cart-summary-item');
        if (cartItem.data('is-rx') == '1' || cartItem.data('is-rx') === 1) {
            selectedHasRx = true;
        }
    });
    
    $('#selected-items-count').text(count);
    $('#selected-items-total').text('₱' + selectedTotal.toLocaleString('en-US', {minimumFractionDigits: 2}));
    
    
    const tax_rate = 0; 
    const tax = selectedTotal * tax_rate;
    const total = selectedTotal + tax;
    
    $('#summary-subtotal').text('₱' + selectedTotal.toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#summary-tax').text('₱' + tax.toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#summary-total').text('₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#checkout-total-amount').val(total.toFixed(2));
    
    
    disableEwalletForRx(selectedHasRx);
    
    
    if (count === 0) {
        const prescriptionSection = $('.form-section').has('#prescription-section-title');
        prescriptionSection.hide();
        $('#prescription-alert').hide();
        $('#upload-area').removeClass('required');
        $('#prescription-file').prop('required', false);
        $('#prescription-required-input').val('0');
        $('.btn-pay').prop('disabled', true).addClass('disabled');
    } else if (selectedHasRx) {
        const prescriptionSection = $('.form-section').has('#prescription-section-title');
        prescriptionSection.show();
        $('#prescription-alert').show();
        $('#upload-area').addClass('required');
        $('#prescription-file').prop('required', true);
        $('#prescription-section-title').html('<i class="fa-solid fa-file-prescription"></i> Prescription Upload <span style="color: #ef4444; font-weight: 700;">(Required)</span>');
        $('#prescription-required-input').val('1');
        
        if (!cartState.prescriptionUploaded) {
            $('.btn-pay').prop('disabled', true).addClass('disabled');
        } else {
            $('.btn-pay').prop('disabled', false).removeClass('disabled');
        }
    } else {
        
        const prescriptionSection = $('.form-section').has('#prescription-section-title');
        prescriptionSection.hide();
        $('#prescription-alert').hide();
        $('#upload-area').removeClass('required');
        $('#prescription-file').prop('required', false);
        $('#prescription-required-input').val('0');
        
        $('.btn-pay').prop('disabled', false).removeClass('disabled');
    }
}

function saveCartSelection() {
    const selectedIds = [];
    $('.cart-item-checkbox:checked').each(function() {
        
        selectedIds.push($(this).data('cart-id'));
    });
    sessionStorage.setItem('cartSelection', JSON.stringify(selectedIds));
}

function restoreCartSelection() {
    
    $('.cart-item-checkbox').prop('checked', false);
    
    const saved = sessionStorage.getItem('cartSelection');
    if (saved) {
        try {
            const selectedIds = JSON.parse(saved);
            
            const existingCartIds = [];
            $('.cart-item-checkbox').each(function() {
                existingCartIds.push($(this).data('cart-id'));
            });
            
            
            const validIds = [];
            selectedIds.forEach(function(id) {
                const checkbox = $(`.cart-item-checkbox[data-cart-id="${id}"]`);
                if (checkbox.length > 0) {
                    checkbox.prop('checked', true);
                    validIds.push(id);
                }
            });
            
            
            if (validIds.length !== selectedIds.length) {
                sessionStorage.setItem('cartSelection', JSON.stringify(validIds));
            }
        } catch (e) {
            $('.cart-item-checkbox').prop('checked', true);
        }
    } else {
        
        $('.cart-item-checkbox').prop('checked', true);
        saveCartSelection();
    }
    updateSelectionSummary();
}

function updateCartBadge() {
    $.ajax({
        url: _base_url_ + 'ajax.php?action=get_cart_count',
        method: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                $('#cartBadge, .cart-count').text(resp.count);
            }
        }
    });
}

function initPaymentMethods() {
    
    var selectedProvider = '';

    $('input[name="payment_method"]').on('change', function() {
        $('.payment-option').removeClass('active');
        $(this).closest('.payment-option').addClass('active');
        
        
        const isOnline = $(this).val() === 'ewallet';
        const reminder = $('#onlinePaymentReminder');
        const checkbox = $('#acknowledgePaymentReminder');
        const payBtn = $('.btn-pay');
        const hint = $('#acknowledgeHint');
        
        if (isOnline) {
            reminder.slideDown(300);
            
            checkbox.prop('checked', false);
            payBtn.prop('disabled', true).addClass('btn-disabled-reminder');
            hint.removeClass('hidden');
        } else {
            reminder.slideUp(200);
            checkbox.prop('checked', false);
            payBtn.prop('disabled', false).removeClass('btn-disabled-reminder');
            hint.addClass('hidden');
            
            selectedProvider = '';
            $('#payment_method_selected').val('');
            $('.ewallet-selected-tag').remove();
        }
    });

    
    $('#ewallet-label').on('click', function(e) {
        
        if (selectedProvider && $('#ewallet-radio').is(':checked')) {
            return;
        }
        
        e.preventDefault();
        
        $('#ewalletPopupOverlay').addClass('active');
    });

    
    $(document).on('click', '.ewallet-choice', function() {
        selectedProvider = $(this).data('provider');
        var providerName = selectedProvider === 'gcash' ? 'GCash' : 'Maya';

        
        $('#payment_method_selected').val(selectedProvider);

        
        $('#ewallet-radio').prop('checked', true).trigger('change');

        
        $('.ewallet-selected-tag').remove();
        var tagClass = selectedProvider === 'gcash' ? 'gcash' : 'maya';
        $('#ewallet-name').html('GCash / Maya <span class="ewallet-selected-tag ' + tagClass + '"><i class="fa-solid fa-check" style="font-size:0.6rem;"></i> ' + providerName + '</span>');

        
        $('#ewallet-redirect-msg').text('You will be redirected to ' + providerName + ' to complete payment securely.');

        
        $('#ewalletPopupOverlay').removeClass('active');
    });

    
    $('#ewalletPopupCancel').on('click', function() {
        $('#ewalletPopupOverlay').removeClass('active');
    });

    
    $('#ewalletPopupOverlay').on('click', function(e) {
        if (e.target.id === 'ewalletPopupOverlay') {
            $(this).removeClass('active');
        }
    });
    
    
    $(document).on('change', '#acknowledgePaymentReminder', function() {
        const payBtn = $('.btn-pay');
        const hint = $('#acknowledgeHint');
        if ($(this).is(':checked')) {
            payBtn.prop('disabled', false).removeClass('btn-disabled-reminder');
            hint.addClass('hidden');
        } else {
            payBtn.prop('disabled', true).addClass('btn-disabled-reminder');
            hint.removeClass('hidden');
        }
    });
    
    $('input[name="payment_method"]:checked').trigger('change');
}

function setMinimumPickupTime() {
    const pickupInput = $('#pickup_time');
    
    
    const STORE_OPEN_HOUR = 8;  
    const STORE_CLOSE_HOUR = 21; 
    const MIN_ADVANCE_MINUTES = 20; 
    
    const now = new Date();
    const minTime = new Date(now.getTime() + (MIN_ADVANCE_MINUTES * 60 * 1000));
    
    
    let pickupHour = minTime.getHours();
    let pickupDate = new Date(minTime);
    
    
    if (pickupHour < STORE_OPEN_HOUR) {
        pickupDate.setHours(STORE_OPEN_HOUR, 0, 0, 0);
    }
    
    else if (pickupHour >= STORE_CLOSE_HOUR) {
        pickupDate.setDate(pickupDate.getDate() + 1);
        pickupDate.setHours(STORE_OPEN_HOUR, 0, 0, 0);
    }
    
    
    const minDateTime = formatDateTimeLocal(pickupDate);
    
    
    pickupInput.attr('min', minDateTime);
    
    
    if (!pickupInput.val()) {
        pickupInput.val(minDateTime);
    }
    
    
    pickupInput.off('change input').on('change input', function() {
        const selectedValue = $(this).val();
        if (!selectedValue) return;
        
        const selectedDateTime = new Date(selectedValue);
        const now = new Date();
        const selectedHour = selectedDateTime.getHours();
        
        
        if (selectedDateTime < now) {
            alert_toast('Cannot select past time. Please choose a future time.', 'error');
            $(this).val(minDateTime);
            return;
        }
        
        
        if (selectedHour < STORE_OPEN_HOUR || selectedHour >= STORE_CLOSE_HOUR) {
            alert_toast('Store hours: 8:00 AM - 9:00 PM. Please select a time within business hours.', 'warning');
            
            
            selectedDateTime.setHours(STORE_OPEN_HOUR, 0, 0, 0);
            
            
            if (selectedDateTime < now) {
                selectedDateTime.setDate(selectedDateTime.getDate() + 1);
            }
            
            $(this).val(formatDateTimeLocal(selectedDateTime));
            return;
        }
        
        
        const minRequiredTime = new Date(now.getTime() + (MIN_ADVANCE_MINUTES * 60 * 1000));
        
        if (selectedDateTime < minRequiredTime) {
            alert_toast('Pickup must be at least 20 minutes from now', 'warning');
            $(this).val(minDateTime);
            return;
        }
    });
}

function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function initUrgentToggle() {
    var $wrapper = $('#urgent-click-target, #urgent-order-wrapper');
    var $checkbox = $('#is_urgent');
    var $icon = $('#urgent-bolt-icon');
    var $checkmark = $('#urgent-visual-check');
    var $modal = $('#urgentConfirmModal');

    function updateUrgentStyle(isChecked) {
        if (typeof isChecked === 'undefined') {
            isChecked = $checkbox.is(':checked');
        }
        if (isChecked) {
            $wrapper.css({
                'background': 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)',
                'border-color': '#d97706',
                'box-shadow': '0 0 0 3px rgba(217,119,6,0.15)'
            });
            $checkmark.css({
                'background': '#059669',
                'border-color': '#059669'
            }).find('i').css('color', '#ffffff');
            $icon.css('color', '#b45309');
        } else {
            $wrapper.css({
                'background': 'linear-gradient(135deg, #fffbeb 0%, #fef9c3 100%)',
                'border-color': '#fbbf24',
                'box-shadow': 'none'
            });
            $checkmark.css({
                'background': '#f9fafb',
                'border-color': '#d1d5db'
            }).find('i').css('color', 'transparent');
            $icon.css('color', '#d97706');
        }
    }

    window.confirmUrgentOrder = function() {
        $checkbox.prop('checked', true).trigger('change');
        updateUrgentStyle(true);
        $modal.css('display', 'none');
    };

    window.cancelUrgentOrder = function() {
        $modal.css('display', 'none');
    };

    window.handleUrgentToggle = function() {
        if ($checkbox.is(':checked')) {
            $checkbox.prop('checked', false).trigger('change');
            updateUrgentStyle(false);
        } else {
            $modal.css('display', 'flex');
        }
    };

    
    $modal.off('click').on('click', function(e) {
        if ($(e.target).is('#urgentConfirmModal')) {
            window.cancelUrgentOrder();
        }
    });

    $checkbox.off('change.urgent').on('change.urgent', function() {
        updateUrgentStyle($(this).is(':checked'));
    });

    
    $(document).off('click', '#urgent-click-target, #urgent-order-wrapper').on('click', '#urgent-click-target, #urgent-order-wrapper', function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.handleUrgentToggle();
    });

    
    updateUrgentStyle($checkbox.is(':checked'));
}

function initCheckoutForm() {
    $('#checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        if (window.profileMissing && window.profileMissing.length > 0) {
            alert_toast('Cannot proceed to checkout. Please complete your profile details: ' + window.profileMissing.join(', '), 'error');
            if ($('.profile-warning-box').length) {
                $('html, body').animate({
                    scrollTop: $('.profile-warning-box').offset().top - 100
                }, 500);
            }
            return false;
        }
        
        
        let selectedHasRx = false;
        $('.cart-item-checkbox:checked').each(function() {
            const cartItem = $(this).closest('.cart-summary-item');
            if (cartItem.data('is-rx') == '1' || cartItem.data('is-rx') === 1) {
                selectedHasRx = true;
            }
        });
        if (selectedHasRx && !cartState.prescriptionUploaded) {
            alert_toast('Please upload your prescription before placing order', 'error');
            return false;
        }
        
        
        const selectedPayment = $('input[name="payment_method"]:checked').val();
        if (selectedPayment === 'ewallet') {
            const acknowledged = $('#acknowledgePaymentReminder').is(':checked');
            if (!acknowledged) {
                alert_toast('Please read and agree to the online payment reminder before placing your order.', 'error');
                $('#onlinePaymentReminder')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        }
        
        
        const pickupDateTime = $('#pickup_time').val();
        if (pickupDateTime) {
            const selectedDateTime = new Date(pickupDateTime);
            const now = new Date();
            const selectedHour = selectedDateTime.getHours();
            
            
            if (selectedDateTime < now) {
                alert_toast('Cannot place order with past pickup time. Please select a future time.', 'error');
                return false;
            }
            
            
            const STORE_OPEN_HOUR = 8;
            const STORE_CLOSE_HOUR = 21;
            if (selectedHour < STORE_OPEN_HOUR || selectedHour >= STORE_CLOSE_HOUR) {
                alert_toast('Store hours: 8:00 AM - 9:00 PM. Please select a valid time.', 'error');
                return false;
            }
            
            
            const minTime = new Date(now.getTime() + (20 * 60 * 1000)); 
            if (selectedDateTime < minTime) {
                alert_toast('Pickup must be at least 20 minutes from now.', 'error');
                return false;
            }
        }
        
        
        const submitBtn = $('.btn-pay');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        
        const formData = new FormData(this);
        
        
        if (pickupDateTime) {
            const dateTimeParts = pickupDateTime.split('T');
            formData.set('pickup_date', dateTimeParts[0]);
            formData.set('pickup_time', dateTimeParts[1] || '00:00');
        }
        
        
        const selectedItems = [];
        $('.cart-item-checkbox:checked').each(function() {
            selectedItems.push($(this).data('cart-id'));
        });
        formData.append('selected_items_param', selectedItems.join(','));
        
        
        formData.delete('is_urgent');
        if ($('#is_urgent').is(':checked')) {
            formData.append('is_urgent', '1');
        }
        
        
        var selectedPay = $('input[name="payment_method"]:checked').val();
        if (selectedPay === 'ewallet') {
            var provider = $('#payment_method_selected').val();
            if (!provider) {
                alert_toast('Please select GCash or Maya first.', 'error');
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-lock"></i> Place Order <i class="fa-solid fa-arrow-right"></i>');
                return false;
            }
            formData.set('payment_method_selected', provider);
        }
        
        
        
        $.ajax({
            url: _base_url_ + 'ajax.php?action=place_order',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    
                    if (resp.paymongo_checkout && resp.checkout_url) {
                        alert_toast('Redirecting to GCash for payment...', 'success');
                        
                        setTimeout(function() {
                            window.location.href = resp.checkout_url;
                        }, 1000);
                    } else if (resp.paymongo_checkout === false && resp.paymongo_error) {
                        
                        alert_toast(resp.msg || 'Order created but payment failed. Please try again from orders page.', 'warning');
                        setTimeout(function() {
                            window.location.href = _base_url_ + 'index.php?p=customer/orders&order_id=' + resp.order_id;
                        }, 2000);
                    } else {
                        
                        alert_toast('Order placed successfully!', 'success');
                        setTimeout(function() {
                            window.location.href = _base_url_ + 'index.php?p=customer/orders';
                        }, 1500);
                    }
                } else {
                    alert_toast(resp.msg || 'Failed to place order', 'error');
                    submitBtn.prop('disabled', false).html('<i class="fa-solid fa-lock"></i> Place Order <i class="fa-solid fa-arrow-right"></i>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Place order error:', {xhr, status, error});
                let errorMsg = 'Network error. Please try again.';
                
                
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    errorMsg = xhr.responseJSON.msg;
                } else if (xhr.responseText) {
                    try {
                        const resp = JSON.parse(xhr.responseText);
                        if (resp.msg) errorMsg = resp.msg;
                    } catch(e) {
                        
                        if (xhr.status === 500) {
                            errorMsg = 'Server error. Please check the logs.';
                        } else if (xhr.status === 401 || xhr.status === 403) {
                            errorMsg = 'Session expired. Please login again.';
                            setTimeout(() => window.location.reload(), 2000);
                        }
                    }
                }
                
                alert_toast(errorMsg, 'error');
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-lock"></i> Place Order <i class="fa-solid fa-arrow-right"></i>');
            }
        });
        
        return false;
    });
}

function showError(message) {
    $('#cart-items-summary').html(`
        <div class="cart-error-message">
            <i class="fas fa-exclamation-circle"></i>
            <p>${message}</p>
        </div>
    `);
}

