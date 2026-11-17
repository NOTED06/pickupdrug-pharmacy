function update_item(cart_id = '', qty = 0){
    start_loader()
    $.ajax({
        url:_base_url_+'classes/Master.php?f=update_cart',
        method:'POST',
        data: {cart_id : cart_id, qty :qty},
        dataType:'json',
        error:err=>{
            console.log(err)
            alert_toast("An error occurred.",'error')
            end_loader()
        },
        success:function(resp){
            if(resp.status == 'success'){
                
                if(typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge();
                }
                location.reload()
            }else{
                alert_toast("An error occurred.",'error')
            }
            end_loader()
        }
    })
}

function calculateSelectedTotal() {
    let total = 0;
    let count = 0;
    
    $('.item-select:checked').each(function() {
        const item = $(this).closest('.cart-item');
        const price = parseFloat(item.attr('data-price'));
        const quantity = parseFloat(item.attr('data-quantity'));
        total += price * quantity;
        count++;
    });
    
    $('#selected-total').text(total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#selected-count').text(count);
    
    
    const checkoutBtn = $('#checkout-btn');
    if (count > 0) {
        checkoutBtn.prop('disabled', false);
    } else {
        checkoutBtn.prop('disabled', true);
    }
}

function proceedToCheckout() {
    
    const selectedItems = [];
    $('.item-select:checked').each(function() {
        const itemId = $(this).data('item-id');
        if (itemId) {
            selectedItems.push(itemId);
        }
    });
    
    if (selectedItems.length === 0) {
        alert_toast('Please select items to checkout', 'warning');
        return;
    }
    
    
    window.location.href = './?p=customer/checkout&selected_items=' + selectedItems.join(',');
}

$(function(){
    
    console.log('Initializing cart events...');
    
    
    $(document).on('change', '#select-all', function() {
        console.log('Select all clicked');
        const isChecked = $(this).is(':checked');
        $('.item-select').prop('checked', isChecked).trigger('change');
    });

    
    $(document).on('change', '.item-select', function() {
        console.log('Item select changed');
        const item = $(this).closest('.cart-item');
        const isChecked = $(this).is(':checked');
        
        item.toggleClass('selected', isChecked);
        
        
        const totalItems = $('.item-select').length;
        const checkedItems = $('.item-select:checked').length;
        
        const selectAllCheckbox = $('#select-all');
        if (checkedItems === 0) {
            selectAllCheckbox.prop('indeterminate', false);
            selectAllCheckbox.prop('checked', false);
        } else if (checkedItems === totalItems) {
            selectAllCheckbox.prop('indeterminate', false);
            selectAllCheckbox.prop('checked', true);
        } else {
            selectAllCheckbox.prop('indeterminate', true);
            selectAllCheckbox.prop('checked', false);
        }
        
        calculateSelectedTotal();
    });

    
    $(document).on('click', '.add-qty', function(){
        var item = $(this).closest('.cart-item')
        var qty = parseFloat(item.find('.qty').val())
        var id = item.attr('data-id')
        var max = item.attr('data-max')
        if(qty == max)
            qty = max;
        else
            qty += 1;
        item.find('.qty').val(qty)
        
        item.attr('data-quantity', qty);
        update_item(id, qty)
    })
    
    function confirmDeleteCartItem(id, item, onCancel) {
        var productName = item ? (item.find('.item-name').text() || 'this item') : 'this item';
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
                delete_cart(id);
            };
            
            popup.classList.remove('closing');
            overlay.classList.add('active');
        } else {
            Swal.fire({
                title: 'Remove Item?',
                text: `Are you sure you want to remove ${productName} from your cart?`,
                icon: 'warning',
                width: '300px',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#f3f4f6',
                confirmButtonText: 'Remove',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'compact-swal',
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    delete_cart(id);
                } else if (typeof onCancel === 'function') {
                    onCancel();
                }
            });
        }
    }

    $(document).on('click', '.minus-qty', function(){
        var item = $(this).closest('.cart-item');
        var qty = parseFloat(item.find('.qty').val()) || 1;
        var id = item.attr('data-id');
        if(qty <= 1) {
            confirmDeleteCartItem(id, item);
            return;
        }
        qty -= 1;
        item.find('.qty').val(qty);
        
        item.attr('data-quantity', qty);
        update_item(id, qty);
    });

    $(document).on('change', '.cart-item .qty', function(){
        var item = $(this).closest('.cart-item');
        var id = item.attr('data-id');
        var qty = parseFloat($(this).val());
        var prevQty = parseFloat(item.attr('data-quantity')) || 1;
        var max = parseFloat(item.attr('data-max')) || 999;
        
        if (isNaN(qty) || qty <= 0) {
            confirmDeleteCartItem(id, item, function() {
                item.find('.qty').val(prevQty);
            });
            return;
        }
        if (qty > max) qty = max;
        $(this).val(qty);
        item.attr('data-quantity', qty);
        update_item(id, qty);
    });
    
    $(document).on('click', '.del-item', function(){
        var item = $(this).closest('.cart-item');
        var id = item.attr('data-id');
        confirmDeleteCartItem(id, item);
    });

    
    $(document).on('click', '#clear-all-btn', function(){
        var itemCount = $('.cart-item').length;
        
        Swal.fire({
            title: 'Clear Cart?',
            text: `Remove all ${itemCount} items?`,
            icon: 'warning',
            width: '300px',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#f3f4f6',
            confirmButtonText: 'Clear All',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'compact-swal',
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                clear_all_cart();
            }
        });
    })

    
    $(document).on('click', '#checkout-btn', function() {
        if ($(this).prop('disabled')) return;
        
        const selectedItems = [];
        let totalAmount = 0;
        let itemCount = 0;
        
        $('.item-select:checked').each(function() {
            const item = $(this).closest('.cart-item');
            const price = parseFloat(item.attr('data-price'));
            const quantity = parseFloat(item.attr('data-quantity'));
            
            selectedItems.push($(this).attr('data-item-id'));
            totalAmount += price * quantity;
            itemCount++;
        });
        
        if (selectedItems.length === 0) {
            alert_toast("Please select at least one item to checkout.", 'error');
            return;
        }
        
        
        openCheckoutModal(selectedItems, totalAmount, itemCount);
    });

    
    calculateSelectedTotal();
    
    
    console.log('Found ' + $('.item-select').length + ' checkboxes');
})

function delete_cart($id){
    Swal.fire({
        title: 'Deleting...',
        html: 'Please wait',
        width: '250px',
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
            popup: 'compact-swal'
        },
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url:_base_url_+"classes/Master.php?f=delete_cart",
        method:"POST",
        data:{id: $id},
        dataType:"json",
        error:err=>{
            console.log(err)
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Delete failed',
                width: '280px',
                confirmButtonColor: '#14b8a6',
                customClass: {
                    popup: 'compact-swal'
                }
            });
        },
        success:function(resp){
            if(typeof resp== 'object' && resp.status == 'success'){
                
                if(typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge();
                }
                location.reload();
            }else{
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to remove item.',
                    width: '320px',
                    confirmButtonColor: '#14b8a6',
                    customClass: {
                        popup: 'compact-swal'
                    }
                });
            }
        }
    })
}

function clear_all_cart(){
    start_loader();
    $.ajax({
        url:_base_url_+"classes/Master.php?f=clear_cart",
        method:"POST",
        dataType:"json",
        error:function(err){
            console.error('Clear cart AJAX error:', err);
            console.error('Status:', err.status);
            console.error('Response Text:', err.responseText);
            alert_toast('Failed to clear cart', 'error');
            end_loader();
        },
        success:function(resp){
            console.log('Clear cart response:', resp);
            if(typeof resp== 'object' && resp.status == 'success'){
                
            if(typeof window.updateCartBadge === 'function') {
                window.updateCartBadge();
            }
            alert_toast('Cart cleared successfully', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            alert_toast(resp.error || 'Failed to clear cart', 'error');
            end_loader();
        }
    }
})
}

function openCheckoutModal(selectedItems, totalAmount, itemCount) {
    
    $('#modal-item-count').text(itemCount);
    $('#modal-total-amount').text('₱' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#checkout-total-amount').val(totalAmount);
    $('#checkout-selected-items').val(selectedItems.join(','));
    
    
    checkPrescriptionRequirement(selectedItems);
    
    
    $('#checkoutModal').addClass('active');
    $('body').css('overflow', 'hidden');
}

function checkPrescriptionRequirement(selectedItems) {
    $.ajax({
        url: _base_url_ + 'ajax.php?action=check_prescription_requirement',
        method: 'POST',
        data: {cart_ids: selectedItems.join(',')},
        dataType: 'json',
        success: function(resp) {
            const hasRxProducts = resp.has_rx_products;
            const hasPrescriptions = window.hasPrescriptions || false;
            
            $('#has-rx-products').val(hasRxProducts ? '1' : '0');
            
            
            if (hasRxProducts) {
                $('#modal-prescription-label').text('(Required)');
                $('#modal-prescription-hint').text('Required:');
                $('#modal-upload-container').css({
                    'background': 'rgba(220, 38, 38, 0.05)',
                    'border': '2px solid rgba(220, 38, 38, 0.3)'
                });
                $('#modal-upload-container').find('.fa-cloud-upload-alt').css('color', '#dc2626');
                $('.btn-checkout').prop('disabled', false).text('Place Order');
            } else {
                $('#modal-prescription-label').text('(Optional)');
                $('#modal-prescription-hint').text('Optional but recommended:');
                $('#modal-upload-container').css({
                    'background': 'rgba(13, 148, 136, 0.05)',
                    'border': '2px dashed rgba(13, 148, 136, 0.3)'
                });
                $('#modal-upload-container').find('.fa-cloud-upload-alt').css('color', '#0d9488');
                $('.btn-checkout').prop('disabled', false).text('Place Order');
            }
        }
    });
}

function loadAvailableBranches() {
    $.ajax({
        url: _base_url_ + 'ajax.php?action=get_available_branches',
        method: 'POST',
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success' && resp.branches) {
                const branchSelect = $('#pickup_branch');
                
                
                branchSelect.find('option:not(:first)').remove();
                
                
                $('#branch-availability-warning').remove();
                
                if (resp.branches.length === 0) {
                    
                    branchSelect.prop('disabled', true);
                    $('.checkout-submit-btn').prop('disabled', true);
                    
                    branchSelect.after(`
                        <div id="branch-availability-warning" class="checkout-form-hint" style="background: #fee2e2; border: 1px solid #dc2626; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem; color: #991b1b;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            <strong>No Branch Available:</strong> No store has all selected items in stock. Please adjust your cart.
                        </div>
                    `);
                } else {
                    
                    resp.branches.forEach(function(branch) {
                        branchSelect.append(`
                            <option value="${branch.id}" 
                                    data-name="${branch.name}"
                                    data-address="${branch.address}"
                                    data-phone="${branch.phone || 'N/A'}">
                                ${branch.name}
                            </option>
                        `);
                    });
                    
                    branchSelect.prop('disabled', false);
                    $('.checkout-submit-btn').prop('disabled', false);
                    
                    
                    if (resp.branches.length < 3) {
                        branchSelect.after(`
                            <div id="branch-availability-warning" class="checkout-form-hint" style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem; color: #92400e;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <strong>Limited Availability:</strong> Only ${resp.branches.length} store(s) have all your selected items in stock.
                            </div>
                        `);
                    }
                }
            }
        },
        error: function() {
            
            console.error('Failed to load available branches');
        }
    });
}

function checkBranchAvailability(selectedItems) {
    $.ajax({
        url: _base_url_ + 'ajax.php?action=check_branch_availability',
        method: 'POST',
        data: { item_ids: selectedItems.join(',') },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                const branchSelect = $('#pickup_branch');
                const branchInfo = $('#branch-availability-info');
                
                
                branchInfo.remove();
                
                if (resp.restricted_branch) {
                    
                    branchSelect.val(resp.restricted_branch.id);
                    branchSelect.prop('disabled', true);
                    branchSelect.trigger('change');
                    
                    
                    branchSelect.after(`
                        <div id="branch-availability-info" class="checkout-form-hint" style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 6px; padding: 0.5rem; margin-top: 0.5rem; color: #92400e;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <strong>Store Location Fixed:</strong> Selected items are only available at <strong>${resp.restricted_branch.name}</strong>.
                        </div>
                    `);
                } else if (resp.available_branches && resp.available_branches.length > 0) {
                    
                    branchSelect.prop('disabled', false);
                    
                    
                    branchSelect.find('option').each(function() {
                        const optionValue = $(this).val();
                        if (optionValue && !resp.available_branches.includes(parseInt(optionValue))) {
                            $(this).prop('disabled', true).hide();
                        } else {
                            $(this).prop('disabled', false).show();
                        }
                    });
                    
                    
                    branchSelect.after(`
                        <div id="branch-availability-info" class="checkout-form-hint" style="background: #dbeafe; border: 1px solid #3b82f6; border-radius: 6px; padding: 0.5rem; margin-top: 0.5rem; color: #1e40af;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                            <strong>Limited Availability:</strong> Selected items are available at ${resp.available_branches.length} store location(s).
                        </div>
                    `);
                } else {
                    
                    branchSelect.prop('disabled', false);
                    branchSelect.find('option').prop('disabled', false).show();
                }
            }
        },
        error: function() {
            
            $('#pickup_branch').prop('disabled', false);
            $('#pickup_branch').find('option').prop('disabled', false).show();
        }
    });
}

function closeCheckoutModal() {
    $('#checkoutModal').removeClass('active');
    $('body').css('overflow', '');
    $('#checkout-form')[0].reset();
    $('#branch-info-display').removeClass('show');
    $('#branch-availability-warning').remove();
    $('#branch-availability-info').remove();
}

function checkSelectionBranchAvailability() {
    const selectedItems = [];
    $('.item-select:checked').each(function() {
        selectedItems.push($(this).val());
    });
    
    if (selectedItems.length === 0) {
        $('#checkout-btn').prop('disabled', true);
        $('#cart-branch-warning').remove();
        return;
    }
    
    
    $.ajax({
        url: 'ajax.php?action=check_branch_availability',
        method: 'POST',
        data: { item_ids: selectedItems.join(',') },
        dataType: 'json',
        success: function(resp) {
            
            $('#cart-branch-warning').remove();
            
            if (resp.status === 'success') {
                if (resp.restricted_branch) {
                    
                    const warningHtml = `
                        <div id="cart-branch-warning" style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 0.8rem; margin: 1rem 0; color: #92400e; font-size: 0.85rem; animation: slideDown 0.3s ease;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <strong>Store Location Notice:</strong> Selected items are only available at <strong>${resp.restricted_branch.name}</strong>. You must pick up from this location.
                        </div>
                    `;
                    $('#checkout-btn').before(warningHtml);
                    $('#checkout-btn').prop('disabled', false);
                    
                } else if (resp.available_branches && resp.available_branches.length > 0 && resp.available_branches.length < 3) {
                    
                    const warningHtml = `
                        <div id="cart-branch-warning" style="background: #dbeafe; border: 1px solid #3b82f6; border-radius: 8px; padding: 0.8rem; margin: 1rem 0; color: #1e40af; font-size: 0.85rem; animation: slideDown 0.3s ease;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                            <strong>Limited Availability:</strong> Selected items are available at ${resp.available_branches.length} store location(s) only.
                        </div>
                    `;
                    $('#checkout-btn').before(warningHtml);
                    $('#checkout-btn').prop('disabled', false);
                    
                } else {
                    
                    $('#checkout-btn').prop('disabled', false);
                }
            } else {
                
                $('#checkout-btn').prop('disabled', false);
            }
        },
        error: function() {
            
            $('#cart-branch-warning').remove();
            $('#checkout-btn').prop('disabled', false);
        }
    });
}

$(document).on('change', '#pickup_branch', function() {
    const selectedOption = $(this).find('option:selected');
    const branchName = selectedOption.data('name');
    const branchAddress = selectedOption.data('address');
    const branchPhone = selectedOption.data('phone');
    const branchId = $(this).val();
    
    if (branchId) {
        $('#modal-branch-name').text(branchName);
        $('#modal-branch-address').text(branchAddress);
        $('#modal-branch-phone').text(branchPhone);
        $('#branch-info-display').addClass('show');
        
        
        verifyBranchStock(branchId);
    } else {
        $('#branch-info-display').removeClass('show');
        $('.checkout-submit-btn').prop('disabled', true);
        $('#stock-warning').remove();
    }
});

function verifyBranchStock(branchId) {
    $.ajax({
        url: _base_url_ + 'ajax.php?action=check_branch_stock',
        method: 'POST',
        data: { branch_id: branchId },
        dataType: 'json',
        success: function(resp) {
            
            $('#stock-warning').remove();
            
            if (resp.status === 'success') {
                if (resp.available) {
                    
                    $('.checkout-submit-btn').prop('disabled', false);
                } else {
                    
                    $('.checkout-submit-btn').prop('disabled', true);
                    
                    let unavailableList = '<ul style="margin: 0.5rem 0 0 1.5rem; padding: 0; list-style: disc;">';
                    resp.unavailable_products.forEach(function(product) {
                        unavailableList += `<li><strong>${product.name}</strong>: Need ${product.requested}, only ${product.available} available</li>`;
                    });
                    unavailableList += '</ul>';
                    
                    $('#pickup_branch').after(`
                        <div id="stock-warning" class="checkout-form-hint" style="background: #fee2e2; border: 1px solid #dc2626; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem; color: #991b1b;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            <strong>Insufficient Stock:</strong> This branch doesn't have enough stock for:
                            ${unavailableList}
                            <div style="margin-top: 0.5rem; font-style: italic;">Please select a different branch or adjust quantities.</div>
                        </div>
                    `);
                }
            } else {
                
                $('.checkout-submit-btn').prop('disabled', false);
            }
        },
        error: function() {
            
            console.error('Failed to verify branch stock');
            $('.checkout-submit-btn').prop('disabled', false);
        }
    });
}

let modalPrescriptionFile = null;
let modalPrescriptionValidation = null;

$('#modal-upload-area').click(function() {
    $('#modal_prescription_upload').click();
});

$('#modal_prescription_upload').change(function(e) {
    const file = e.target.files[0];
    if (file) {
        handleModalPrescriptionUpload(file);
    }
});

$('#modal-remove-prescription').click(function() {
    modalPrescriptionFile = null;
    modalPrescriptionValidation = null;
    $('#modal_prescription_upload').val('');
    $('#modal-upload-preview').hide();
    $('#modal-upload-area').show();
    $('#modal-prescription-validation').val('');
    $('#modal-prescription-details').val('');
});

function handleModalPrescriptionUpload(file) {
    
    if (!file.type.startsWith('image/')) {
        alert_toast('Please upload a valid image file', 'error');
        return;
    }
    
    
    if (file.size > 5 * 1024 * 1024) {
        alert_toast('File size must be less than 5MB', 'error');
        return;
    }
    
    modalPrescriptionFile = file;
    
    
    const reader = new FileReader();
    reader.onload = function(e) {
        $('#modal-prescription-preview').attr('src', e.target.result);
        $('#modal-prescription-filename').text(file.name);
        $('#modal-upload-area').hide();
        $('#modal-upload-preview').show();
        $('#modal-validation-status').html('<i class="fas fa-spinner fa-spin"></i> Validating with AI...').css({
            'background': '#fef3c7',
            'color': '#92400e'
        });
        
        
        validateModalPrescriptionWithCart(e.target.result);
    };
    reader.readAsDataURL(file);
}

function validateModalPrescriptionWithCart(imageDataUrl) {
    
    const cartProducts = [];
    $('.item-select:checked').each(function() {
        const item = $(this).closest('.cart-item');
        const productName = item.find('.item-name').text().trim();
        if (productName) {
            cartProducts.push(productName);
        }
    });
    
    if (cartProducts.length === 0) {
        $('#modal-validation-status').html('<i class="fas fa-exclamation-circle"></i> No items selected').css({
            'background': '#fecaca',
            'color': '#991b1b'
        });
        return;
    }
    
    console.log('Selected cart products for AI validation:', cartProducts);
    
    
    const base64Data = imageDataUrl.split(',')[1];
    
    
    $.ajax({
        url: _base_url_ + 'ajax.php?action=validate_prescription_cart',
        method: 'POST',
        data: {
            image_data: base64Data,
            cart_products: JSON.stringify(cartProducts)
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.validation) {
                modalPrescriptionValidation = response.validation;
                $('#modal-prescription-validation').val(response.validation.validation);
                $('#modal-prescription-details').val(JSON.stringify(response.validation));
                
                const status = response.validation.validation;
                let statusHtml = '';
                let statusColor = '';
                
                if (status === 'matched') {
                    statusHtml = '<i class="fas fa-check-circle"></i> Perfect Match!';
                    statusColor = {'background': '#d1fae5', 'color': '#065f46'};
                } else if (status === 'partial') {
                    statusHtml = '<i class="fas fa-exclamation-triangle"></i> Partial Match';
                    statusColor = {'background': '#fed7aa', 'color': '#92400e'};
                } else if (status === 'mismatch') {
                    statusHtml = '<i class="fas fa-times-circle"></i> Mismatch';
                    statusColor = {'background': '#fecaca', 'color': '#991b1b'};
                } else {
                    statusHtml = '<i class="fas fa-question-circle"></i> Invalid';
                    statusColor = {'background': '#e5e7eb', 'color': '#374151'};
                }
                
                $('#modal-validation-status').html(statusHtml).css(statusColor);
                
                
                if (response.validation.notes) {
                    $('#modal-validation-status').next('.validation-notes').remove(); 
                    const notesDiv = $('<div>').addClass('validation-notes').css({
                        'margin-top': '5px',
                        'padding': '6px',
                        'background': 'rgba(255,255,255,0.9)',
                        'border-radius': '4px',
                        'font-size': '0.7rem',
                        'text-align': 'left',
                        'line-height': '1.3'
                    }).html('<strong>Details:</strong> ' + response.validation.notes);
                    $('#modal-validation-status').after(notesDiv);
                }
            } else {
                $('#modal-validation-status').html('<i class="fas fa-exclamation-circle"></i> Validation failed').css({
                    'background': '#fecaca',
                    'color': '#991b1b'
                });
            }
        },
        error: function() {
            $('#modal-validation-status').html('<i class="fas fa-exclamation-circle"></i> Error - Try again').css({
                'background': '#fecaca',
                'color': '#991b1b'
            });
        }
    });
}

$(document).on('submit', '#checkout-form', function(e) {
    e.preventDefault();
    
    
    const hasRxProducts = $('#has-rx-products').val() === '1';
    const prescriptionUploaded = $('#modal-prescription-validation').val() !== '';
    
    
    if (hasRxProducts && !prescriptionUploaded) {
        
        if (!confirm('You have prescription medications in your cart but no prescription uploaded. The order will be held pending prescription verification. Continue?')) {
            return false;
        }
    }
    
    start_loader();
    
    const formData = new FormData(this);
    
    
    if (modalPrescriptionFile) {
        formData.append('prescription_image', modalPrescriptionFile);
    }
    
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=place_order',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        error: err => {
            console.log(err);
            alert_toast("An error occurred while placing your order.", 'error');
            end_loader();
        },
        success: function(resp) {
            if (resp.status == 'success') {
                alert_toast("Order placed successfully! You will receive a notification when your order is ready for pickup.", 'success');
                
                if(typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge();
                }
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                alert_toast(resp.msg || "An error occurred while placing your order.", 'error');
                end_loader();
            }
        }
    });
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#checkoutModal').hasClass('active')) {
        closeCheckoutModal();
    }
});

$(document).on('click', '#checkoutModal', function(e) {
    if (e.target.id === 'checkoutModal') {
        closeCheckoutModal();
    }
});

