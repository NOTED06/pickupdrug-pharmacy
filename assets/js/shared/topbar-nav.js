

function toggleCart() {
    const drawer = document.getElementById('cart-drawer');
    const overlay = document.getElementById('cart-overlay');
    const isOpen = drawer.classList.contains('open');
    
    if (!isOpen) {
        loadCartDrawer();
    }
    
    drawer.classList.toggle('open');
    overlay.classList.toggle('open');
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

function markAllRead() {
    console.log('[markAllRead] Marking all notifications as read...');
    
    
    $.ajax({
        url: window.base_url + 'ajax.php?action=mark_all_notifications_read',
        method: 'POST',
        dataType: 'json',
        success: function(resp) {
            console.log('[markAllRead] Response:', resp);
            if (resp.status === 'success') {
                
                const badge = document.getElementById('notificationCount');
                if (badge) {
                    badge.style.display = 'none';
                    badge.textContent = '0';
                }
                
                
                const notifItems = document.querySelectorAll('.notification-item, .notif-item');
                notifItems.forEach(item => {
                    item.classList.remove('unread');
                });
                
                
                const footer = document.getElementById('notificationFooter');
                if (footer) {
                    footer.innerHTML = '<a href="' + window.base_url + '?p=customer/notifications">View all notifications</a>';
                }
                
                
                if (typeof showToast === 'function') {
                    showToast('All notifications marked as read', 'success');
                } else if (typeof alert_toast === 'function') {
                    alert_toast('All notifications marked as read', 'success');
                }
                
                
                if (typeof loadNotifications === 'function') {
                    loadNotifications();
                }
            } else {
                console.error('[markAllRead] Failed:', resp.msg);
                if (typeof showToast === 'function') {
                    showToast(resp.msg || 'Failed to mark notifications as read', 'error');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('[markAllRead] AJAX error:', error);
            if (typeof showToast === 'function') {
                showToast('Error marking notifications as read', 'error');
            }
        }
    });
}

document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userDropdown && !userMenu.contains(event.target)) {
        userDropdown.classList.remove('show');
    }
});

$(document).ready(function() {
    let searchTimeout;
    const searchInput = $('#navSearchInput');
    const searchDropdown = $('#searchResultsDropdown');
    
    console.log('Search initialized. Input found:', searchInput.length, 'Dropdown found:', searchDropdown.length);
    
    
    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        console.log('Search input event fired. Query:', query);
        
        if(query.length < 1) {
            searchDropdown.removeClass('active').empty();
            return;
        }
        
        
        searchDropdown.html(`
            <div class="search-bot-greeting">
                <div class="search-bot-avatar">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="search-bot-message">
                    Searching for "${query}"...
                </div>
            </div>
            <div class="search-loading">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        `).addClass('active');
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: window.BASE_URL + 'ajax.php?action=search_products',
                method: 'POST',
                data: { query: query },
                dataType: 'json',
                success: function(response) {
                    console.log('Search response:', response);
                    if(response.status === 'success' && response.data.length > 0) {
                        let html = `
                            <div class="search-bot-greeting">
                                <div class="search-bot-avatar">
                                    <i class="fas fa-pills"></i>
                                </div>
                                <div class="search-bot-message">
                                    Found ${response.data.length} medicine${response.data.length > 1 ? 's' : ''} for "${query}". Click to view details:
                                </div>
                            </div>
                        `;
                        response.data.forEach(function(product) {
                            const stockBadge = product.available > 0 
                                ? `<span style="color: #10b981; font-size: 0.7rem;"><i class="fas fa-check-circle"></i> In Stock</span>`
                                : `<span style="color: #ef4444; font-size: 0.7rem;"><i class="fas fa-times-circle"></i> Out</span>`;
                            
                            html += `
                                <div class="search-result-item" data-action="view-product-from-search" data-product-id="${product.id}">
                                    <img src="${product.image_path}" alt="${product.name}" class="search-result-image">
                                    <div class="search-result-info">
                                        <div class="search-result-name">${product.name}</div>
                                        <div class="search-result-category">${product.category_name}</div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                                            <div class="search-result-price">₱${parseFloat(product.price).toFixed(2)}</div>
                                            ${stockBadge}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        searchDropdown.html(html);
                    } else {
                        searchDropdown.html(`
                            <div class="search-bot-greeting">
                                <div class="search-bot-avatar">
                                    <i class="fas fa-pills"></i>
                                </div>
                                <div class="search-bot-message">
                                    Sorry, I couldn't find any medicines matching "${query}". Try different keywords!
                                </div>
                            </div>
                            <div class="search-no-results">
                                <i class="fas fa-search"></i>
                                <p>No products found</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Search AJAX error:', error, xhr.responseText);
                    searchDropdown.html(`
                        <div class="search-bot-greeting">
                            <div class="search-bot-avatar">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="search-bot-message">
                                Oops! Something went wrong. Please try again.
                            </div>
                        </div>
                    `);
                }
            });
        }, 300);
    });
    
    
    $(document).on('click', function(e) {
        if(!$(e.target).closest('.search-container').length) {
            searchDropdown.removeClass('active').empty();
        }
    });
    
    
    searchInput.on('keypress', function(e) {
        if(e.which === 13) {
            const query = $(this).val().trim();
            if(query) {
                window.location.href = window.BASE_URL + '?p=customer/home&search=' + encodeURIComponent(query);
            }
        }
    });
    
    
    updateCartCount();
    setInterval(updateCartCount, 10000);
});

function viewProductFromSearch(productId) {
    
    if(typeof viewProduct === 'function') {
        viewProduct(productId);
        $('#searchResultsDropdown').removeClass('active').empty();
        $('#navSearchInput').val('');
    } else {
        
        window.location.href = window.BASE_URL + '?p=customer/home&view_product=' + productId;
    }
}

function updateCartCount() {
    var currentCount = parseInt($('#cartBadge').text()) || 0;
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=get_cart_count',
        success: function(resp) {
            try {
                const data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                const count = data.count || 0;
                $('#cartBadge').text(count);
                
                if(count > currentCount) {
                    $('#cartBadge').addClass('cart-pulse');
                    setTimeout(function() {
                        $('#cartBadge').removeClass('cart-pulse');
                    }, 1000);
                }
            } catch(e) {
                console.error('Error parsing cart count:', e);
            }
        }
    });
}

function loadCartDrawer() {
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=get_cart_list',
        method: 'GET',
        success: function(resp) {
            try {
                const data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                const items = data.items || [];
                const total = data.total || 0;
                const count = items.length;
                
                $('#cartItemCount').text(count + ' item' + (count !== 1 ? 's' : ''));
                $('#cartDrawerTotalAll').text('₱' + parseFloat(total).toFixed(2));
                $('#cartDrawerTotal').text('₱' + parseFloat(total).toFixed(2));
                
                if (items.length === 0) {
                    $('#cartSelectionBar').hide();
                    $('.cart-drawer-checkout').prop('disabled', true);
                    $('#cartDrawerItems').html(`
                        <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                            <i class="fa-solid fa-cart-shopping" style="font-size: 3rem; opacity: 0.3; margin-bottom: 16px;"></i>
                            <p>Your cart is empty</p>
                        </div>
                    `);
                } else {
                    let itemsHtml = '';
                    items.forEach(function(item) {
                        const imageUrl = item.image ? window.BASE_URL + item.image : window.BASE_URL + 'dist/img/no-image-available.png';
                        const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                        itemsHtml += `
                            <div class="cart-drawer-item" data-cart-id="${item.id}" data-product-id="${item.product_id}" data-item-total="${itemTotal.toFixed(2)}">
                                <label class="cart-item-checkbox-wrapper">
                                    <input type="checkbox" class="cart-drawer-item-checkbox" 
                                           data-cart-id="${item.id}" 
                                           data-product-id="${item.product_id}"
                                           data-price="${itemTotal.toFixed(2)}">
                                    <span class="cart-checkbox-custom"></span>
                                </label>
                                <div class="cart-drawer-item-img">
                                    <img src="${imageUrl}" alt="${item.name}" onerror="this.src='${window.BASE_URL}dist/img/no-image-available.png'">
                                </div>
                                <div class="cart-drawer-item-info">
                                    <h4 class="cart-drawer-item-name">${item.name}</h4>
                                    <p class="cart-drawer-item-price">₱${parseFloat(item.price).toFixed(2)} × ${item.quantity}</p>
                                    <div class="cart-drawer-qty">
                                        <button data-action="qty-change" data-cart-id="${item.id}" data-delta="-1" data-quantity="${item.quantity}">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        <span style="width: 32px; text-align: center; font-weight: 600; color: #1f2937; font-size: 0.875rem;">${item.quantity}</span>
                                        <button data-action="qty-change" data-cart-id="${item.id}" data-delta="1" data-quantity="${item.quantity}">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                        <button class="delete-btn" data-action="remove-from-cart" data-cart-id="${item.id}">
                                            <i class="fa-solid fa-trash" style="font-size: 0.75rem;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#cartDrawerItems').html(itemsHtml);
                    
                    $('#cartSelectionBar').show();
                    $('.cart-drawer-checkout').prop('disabled', false);
                    restoreCartDrawerSelection();
                }
            } catch(e) {
                console.error('Error loading cart:', e);
                $('#cartDrawerItems').html(`
                    <div style="text-align: center; padding: 60px 20px; color: #ef4444;">
                        <i class="fa-solid fa-exclamation-triangle" style="font-size: 3rem; opacity: 0.3; margin-bottom: 16px;"></i>
                        <p>Error loading cart</p>
                    </div>
                `);
            }
        },
        error: function() {
            $('#cartDrawerItems').html(`
                <div style="text-align: center; padding: 60px 20px; color: #ef4444;">
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 3rem; opacity: 0.3; margin-bottom: 16px;"></i>
                    <p>Error loading cart</p>
                </div>
            `);
        }
    });
}

var drawerQtyThrottle = {};

function updateCartQty(cartId, newQty) {
    if (newQty < 1) {
        removeFromCart(cartId);
        return;
    }
    
    
    const now = Date.now();
    if (drawerQtyThrottle[cartId] && (now - drawerQtyThrottle[cartId] < 300)) {
        return;
    }
    drawerQtyThrottle[cartId] = now;
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=update_cart_qty',
        method: 'POST',
        data: { id: cartId, cart_id: cartId, quantity: newQty, qty: newQty },
        dataType: 'json',
        success: function(resp) {
            if (resp && resp.status === 'failed') {
                if (typeof showToast === 'function') {
                    showToast(resp.msg || 'Cannot update quantity', 'error');
                } else if (typeof alert_toast === 'function') {
                    alert_toast(resp.msg || 'Cannot update quantity', 'error');
                }
            }
            loadCartDrawer();
            updateCartCount();
            if (typeof loadCartSummary === 'function') {
                loadCartSummary();
            }
        },
        error: function() {
            loadCartDrawer();
        }
    });
}

var pendingRemovalCartId = null;

function confirmRemoveSingleItem() {
    if (!pendingRemovalCartId) return;
    
    const cartIdToRemove = pendingRemovalCartId;
    
    
    const saved = sessionStorage.getItem('cartSelection');
    if (saved) {
        try {
            let selectedIds = JSON.parse(saved);
            selectedIds = selectedIds.filter(id => id != cartIdToRemove);
            sessionStorage.setItem('cartSelection', JSON.stringify(selectedIds));
        } catch(e) {}
    }
    
    
    const $summaryItem = $(`.cart-summary-item[data-cart-id="${cartIdToRemove}"]`);
    if ($summaryItem.length > 0) {
        $summaryItem.fadeOut(200, function() {
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
        url: window.BASE_URL + 'ajax.php?action=delete_cart',
        method: 'POST',
        data: { id: cartIdToRemove },
        success: function(resp) {
            closeConfirmPopup();
            loadCartDrawer();
            updateCartCount();
            if (typeof loadCartSummary === 'function') {
                loadCartSummary();
            }
            if (typeof updateCartBadge === 'function') {
                updateCartBadge();
            }
            pendingRemovalCartId = null;
            
            if (typeof showToast === 'function') {
                showToast('Item removed from cart', 'success');
            } else if (typeof alert_toast === 'function') {
                alert_toast('Item removed from cart', 'success');
            }
        },
        error: function() {
            closeConfirmPopup();
            if (typeof loadCartSummary === 'function') {
                loadCartSummary();
            }
            pendingRemovalCartId = null;
            
            if (typeof showToast === 'function') {
                showToast('Error removing item', 'error');
            } else if (typeof alert_toast === 'function') {
                alert_toast('Error removing item', 'error');
            }
        }
    });
}

function removeFromCart(cartId) {
    pendingRemovalCartId = cartId;
    showConfirmPopup(1, true);
}

function confirmClearCart() {
    const selectedItems = [];
    $('.cart-drawer-item-checkbox:checked').each(function() {
        selectedItems.push($(this).data('cart-id'));
    });
    
    if (selectedItems.length === 0) {
        closeConfirmPopup();
        return;
    }
    
    const itemCount = selectedItems.length;
    
    
    const saved = sessionStorage.getItem('cartSelection');
    if (saved) {
        try {
            let selectedIds = JSON.parse(saved);
            selectedIds = selectedIds.filter(id => !selectedItems.includes(id));
            sessionStorage.setItem('cartSelection', JSON.stringify(selectedIds));
        } catch(e) {}
    }
    
    
    selectedItems.forEach(function(cartId) {
        $(`.cart-summary-item[data-cart-id="${cartId}"]`).remove();
    });
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
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=delete_selected_cart',
        method: 'POST',
        data: { cart_ids: JSON.stringify(selectedItems) },
        success: function(resp) {
            closeConfirmPopup();
            loadCartDrawer();
            updateCartCount();
            if (typeof loadCartSummary === 'function') {
                loadCartSummary();
            }
            if (typeof updateCartBadge === 'function') {
                updateCartBadge();
            }
            
            const message = itemCount === 1 ? '1 item removed' : `${itemCount} items removed`;
            if (typeof showToast === 'function') {
                showToast(message, 'success');
            } else if (typeof alert_toast === 'function') {
                alert_toast(message, 'success');
            }
        },
        error: function() {
            closeConfirmPopup();
            if (typeof loadCartSummary === 'function') {
                loadCartSummary();
            }
            if (typeof showToast === 'function') {
                showToast('Error removing items', 'error');
            } else if (typeof alert_toast === 'function') {
                alert_toast('Error removing items', 'error');
            }
        }
    });
}

function showConfirmPopup(count, isSingleRemoval = false) {
    const overlay = document.getElementById('confirmOverlay');
    const popup = document.getElementById('confirmPopup');
    const title = document.querySelector('.confirm-title');
    const message = document.querySelector('.confirm-message');
    const confirmBtn = document.querySelector('.confirm-btn-confirm');
    const confirmBtnText = document.getElementById('confirmBtnText');
    
    if (isSingleRemoval) {
        title.textContent = 'Remove Item?';
        message.textContent = 'Are you sure you want to remove this item from your cart?';
        confirmBtnText.textContent = 'Remove';
        confirmBtn.onclick = confirmRemoveSingleItem;
    } else if (count === 1) {
        title.textContent = 'Remove 1 Item?';
        message.textContent = 'Are you sure you want to remove this item from your cart?';
        confirmBtnText.textContent = 'Remove';
        confirmBtn.onclick = confirmClearCart;
    } else {
        title.textContent = `Remove ${count} Items?`;
        message.textContent = `Are you sure you want to remove ${count} selected items from your cart?`;
        confirmBtnText.textContent = `Remove ${count} Items`;
        confirmBtn.onclick = confirmClearCart;
    }
    
    popup.classList.remove('closing');
    overlay.classList.add('active');
}

function closeConfirmPopup() {
    const overlay = document.getElementById('confirmOverlay');
    const popup = document.getElementById('confirmPopup');
    if (popup) popup.classList.add('closing');
    setTimeout(() => {
        if (overlay) overlay.classList.remove('active');
    }, 200);
    if (typeof window.onCancelRemovalCallback === 'function') {
        window.onCancelRemovalCallback();
        window.onCancelRemovalCallback = null;
    }
}

document.addEventListener('click', function(e) {
    if (e.target.id === 'confirmOverlay') {
        closeConfirmPopup();
    }
});

function initCartDrawerSelection() {
    
    $(document).off('click', '#selectAllCartItems').on('click', '#selectAllCartItems', function() {
        $('.cart-drawer-item-checkbox').prop('checked', true);
        
        $('.cart-drawer-item-checkbox').each(function() {
            const cartId = $(this).data('cart-id');
            $(`.cart-item-checkbox[data-cart-id="${cartId}"]`).prop('checked', true);
        });
        updateCartDrawerSelection();
        saveCartDrawerSelection();
        $('.cart-drawer-item-checkbox').each(function() {
            updateCartItemSelectedState($(this));
        });
        
        if (typeof updateSelectionSummary === 'function') {
            updateSelectionSummary();
        }
    });
    
    
    $(document).off('click', '#clearCartSelection').on('click', '#clearCartSelection', function() {
        const selectedCount = $('.cart-drawer-item-checkbox:checked').length;
        if (selectedCount === 0) {
            if (typeof showToast === 'function') {
                showToast('No items selected to clear', 'warning');
            }
            return;
        }
        showConfirmPopup(selectedCount);
    });
    
    
    $(document).off('change', '.cart-drawer-item-checkbox').on('change', '.cart-drawer-item-checkbox', function() {
        const cartId = $(this).data('cart-id');
        const isChecked = $(this).is(':checked');
        
        const $mainCheckbox = $(`.cart-item-checkbox[data-cart-id="${cartId}"]`);
        if ($mainCheckbox.length > 0) {
            $mainCheckbox.prop('checked', isChecked);
        }
        updateCartDrawerSelection();
        saveCartDrawerSelection();
        updateCartItemSelectedState($(this));
        
        if (typeof updateSelectionSummary === 'function') {
            updateSelectionSummary();
        }
    });
}

function updateCartDrawerSelection() {
    const selectedCheckboxes = $('.cart-drawer-item-checkbox:checked');
    const count = selectedCheckboxes.length;
    let total = 0;
    
    selectedCheckboxes.each(function() {
        const price = parseFloat($(this).data('price')) || 0;
        total += price;
    });
    
    $('#selectedCartCount').text(count);
    $('#selectedCartTotal').text('₱' + total.toFixed(2));
    $('#cartDrawerTotal').text('₱' + total.toFixed(2));
}

function updateCartItemSelectedState($checkbox) {
    const $item = $checkbox.closest('.cart-drawer-item');
    if ($checkbox.is(':checked')) {
        $item.addClass('is-selected');
    } else {
        $item.removeClass('is-selected');
    }
}

function saveCartDrawerSelection() {
    const selectedIds = [];
    $('.cart-drawer-item-checkbox:checked').each(function() {
        selectedIds.push($(this).data('cart-id'));
    });
    
    sessionStorage.setItem('cartSelection', JSON.stringify(selectedIds));
}

function restoreCartDrawerSelection() {
    const savedSelection = sessionStorage.getItem('cartSelection');
    
    $('.cart-drawer-item-checkbox').prop('checked', false);
    
    if (savedSelection) {
        try {
            const selectedIds = JSON.parse(savedSelection);
            
            selectedIds.forEach(function(cartId) {
                const $checkbox = $(`.cart-drawer-item-checkbox[data-cart-id="${cartId}"]`);
                if ($checkbox.length > 0) {
                    $checkbox.prop('checked', true);
                    updateCartItemSelectedState($checkbox);
                }
            });
            
            updateCartDrawerSelection();
        } catch (e) {
            console.error('Failed to restore cart selection:', e);
            sessionStorage.removeItem('cartSelection');
        }
    } else {
        
        $('.cart-drawer-item-checkbox').prop('checked', true).each(function() {
            updateCartItemSelectedState($(this));
        });
        updateCartDrawerSelection();
        saveCartDrawerSelection();
    }
}

function getSelectedCartItems() {
    const selectedItems = [];
    $('.cart-drawer-item-checkbox:checked').each(function() {
        const $item = $(this).closest('.cart-drawer-item');
        selectedItems.push({
            cartId: $(this).data('cart-id'),
            productId: $(this).data('product-id'),
            total: parseFloat($item.data('item-total')) || 0
        });
    });
    return selectedItems;
}

$(document).ready(function() {
    initCartDrawerSelection();
});

$(function(){
    
    $(window).scroll(function() {
        if ($(window).scrollTop() > 50) {
            $('.customer-navbar').addClass('scrolled');
        } else {
            $('.customer-navbar').removeClass('scrolled');
        }
    });
    
    $('#search_report').click(function(){
        uni_modal("Search Request Report","report/search.php");
    });
    
    $('#navbarResponsive').on('show.bs.collapse', function () {
        $('#mainNav').addClass('navbar-shrink');
    });
    
    $('#navbarResponsive').on('hidden.bs.collapse', function () {
        if($('body').offset.top == 0)
            $('#mainNav').removeClass('navbar-shrink');
    });
    
    
    loadNotifications();
    updateCartCount();
    
    
    
    checkExpiredPayments();
    
    
    setInterval(function() {
        checkNewNotifications();
        updateCartCount();
    }, 10000);
    
    
    setInterval(checkExpiredPayments, 20000);
    
    
    $('#notificationIcon').mouseenter(function() {
        loadNotifications();
    });

    $('#notificationIcon').click(function(e){
        e.stopPropagation();
        $('#notificationDropdown').toggleClass('show');
        
        if($('#notificationDropdown').hasClass('show')) {
            loadNotifications();
        }
    });
    
    
    $(document).click(function(e){
        if(!$(e.target).closest('.notification-icon').length){
            $('#notificationDropdown').removeClass('show');
        }
    });
    
    
    $(document).on('click', '#markAllRead', function(e){
        e.preventDefault();
        markAllAsRead();
    });
    
    
    $(document).on('click', '.delete-notification', function(e){
        e.stopPropagation();
        var notifId = $(this).data('id');
        deleteNotification(notifId);
    });
    
    
    const profileBtn = document.getElementById('userProfileBtn');
    if(profileBtn) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if(!profileBtn.contains(e.target)) {
                profileBtn.classList.remove('show');
            }
        });
        
        const dropdown = profileBtn.querySelector('.user-dropdown');
        if(dropdown) {
            dropdown.addEventListener('click', function(e) {
                if(e.target.tagName === 'A' || e.target.closest('a')) {
                    return;
                }
                e.stopPropagation();
            });
        }
    }
});

function initNotifications() {
    loadNotifications();
    setInterval(loadNotifications, 30000);
    
    
}

function loadNotifications(){
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=get_notifications',
        method: 'GET',
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success'){
                displayNotifications(resp.data, resp.unread_count);
            } else {
                console.log('Failed to load notifications:', resp.msg);
            }
        },
        error: function(xhr, status, error){
            console.log('Error loading notifications:', error);
        }
    });
}

function displayNotifications(notifications, unreadCount) {
    
    if(unreadCount > 0){
        $('#notificationCount').text(unreadCount > 99 ? '99+' : unreadCount).show();
        $('#notificationIcon').addClass('has-new-notifications');
        setTimeout(function() {
            $('#notificationIcon').removeClass('has-new-notifications');
        }, 3000);
    } else {
        $('#notificationCount').hide();
    }
    
    
    if(notifications.length > 0){
        var html = '';
        
        notifications.forEach(function(notif){
            var unreadClass = notif.is_read == 0 ? 'unread' : '';
            var iconClass = getNotificationIcon(notif.title);
            
            html += '<div class="notification-item ' + unreadClass + '" data-id="' + notif.id + '">';
            html += '  <div class="notification-icon-wrapper">';
            html += '    <i class="' + iconClass + '"></i>';
            html += '  </div>';
            html += '  <div class="notification-content">';
            html += '    <div class="notification-title">' + escapeHtml(notif.title) + '</div>';
            html += '    <div class="notification-text">' + escapeHtml(notif.message) + '</div>';
            html += '    <div class="notification-footer-info">';
            html += '      <span class="notification-time">' + notif.time_ago + '</span>';
            if(notif.order_id) {
                var orderUrl = (window.BASE_URL || window.base_url || './') + '?p=orders&order_id=' + notif.order_id;
                html += '      <a href="' + orderUrl + '" class="view-order">View Order</a>';
            }
            html += '    </div>';
            html += '  </div>';
            html += '  <button class="delete-notification" data-id="' + notif.id + '" title="Delete">';
            html += '    <i class="fas fa-times"></i>';
            html += '  </button>';
            html += '</div>';
        });
        
        $('#notificationList').html(html);
        
        
        $('.delete-notification').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var notifId = $(this).data('id');
            var item = $(this).closest('.notification-item');
            $.ajax({
                url: (window.BASE_URL || window.base_url || '') + 'ajax.php?action=delete_notification',
                method: 'POST',
                data: {id: notifId},
                dataType: 'json',
                success: function(resp){
                    if(resp.status == 'success'){
                        item.slideUp(200, function(){
                            $(this).remove();
                            loadNotifications();
                        });
                    }
                }
            });
        });

        
        $('.notification-item').click(function(e){
            if($(e.target).closest('.delete-notification').length) {
                return;
            }
            var notifId = $(this).data('id');
            if($(this).hasClass('unread')) {
                markAsRead(notifId);
                $(this).removeClass('unread');
            }
            
            var orderLink = $(this).find('.view-order').attr('href');
            if(orderLink) {
                window.location.href = orderLink;
            }
        });
        
        if(unreadCount > 0) {
            $('#notificationFooter').html(
                '<a href="./?p=customer/notifications">View all notifications</a> | ' +
                '<a href="#" id="markAllRead">Mark all as read</a>'
            );
        } else {
            $('#notificationFooter').html('<a href="./?p=customer/notifications">View all notifications</a>');
        }
        
    } else {
        $('#notificationList').html(
            '<div class="no-notifications">' +
            '  <i class="fas fa-bell-slash" style="font-size: 40px; color: #ddd; margin-bottom: 10px;"></i>' +
            '  <p>No notifications yet</p>' +
            '</div>'
        );
        $('#notificationFooter').html('<a href="./?p=customer/notifications">View all notifications</a>');
    }
}

var _lastToastedNotifId = null;

function checkNewNotifications() {
    var currentCount = parseInt($('#notificationCount').text()) || 0;
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=get_notifications',
        method: 'GET',
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success'){
                var newCount = resp.unread_count || 0;
                
                if(newCount > 0){
                    $('#notificationCount').text(newCount > 99 ? '99+' : newCount).show();
                    
                    
                    if(resp.data && resp.data.length > 0) {
                        var latestNotif = resp.data[0];
                        var latestId = latestNotif.id || null;
                        
                        
                        if(latestId && latestId !== _lastToastedNotifId && newCount > currentCount) {
                            _lastToastedNotifId = latestId;
                            showNotificationToast(latestNotif.title, latestNotif.message);
                            $('#notificationIcon').addClass('has-new-notifications');
                            setTimeout(function() {
                                $('#notificationIcon').removeClass('has-new-notifications');
                            }, 3000);
                        }
                    }
                    
                    displayNotifications(resp.data, newCount);
                } else {
                    $('#notificationCount').hide();
                }
            }
        },
        error: function(){
            
        }
    });
}

function showNotificationToast(title, message) {
    if($('#notification-toast-container').length === 0) {
        $('body').append('<div id="notification-toast-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999;"></div>');
    }
    
    var toastId = 'toast-' + Date.now();
    var toastHtml = `
        <div id="${toastId}" class="notification-toast" style="
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 12px 16px;
            margin-bottom: 10px;
            min-width: 280px;
            max-width: 350px;
            border-left: 4px solid #0d9488;
            animation: slideInRight 0.3s ease;
            cursor: pointer;
        ">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <div style="
                    width: 32px;
                    height: 32px;
                    background: #e0f2fe;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                ">
                    <i class="fas fa-bell" style="color: #0d9488; font-size: 14px;"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: 13px; color: #1e293b; margin-bottom: 2px;">${escapeHtml(title)}</div>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.3;">${escapeHtml(message).substring(0, 80)}${message.length > 80 ? '...' : ''}</div>
                </div>
                <button data-action="remove-toast" style="
                    background: none;
                    border: none;
                    color: #94a3b8;
                    cursor: pointer;
                    padding: 0;
                    font-size: 16px;
                ">&times;</button>
            </div>
        </div>
    `;
    
    $('#notification-toast-container').append(toastHtml);
    
    setTimeout(function() {
        $('#' + toastId).fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
    
    $('#' + toastId).click(function() {
        $('#notificationDropdown').addClass('show');
        loadNotifications();
        $(this).fadeOut(200, function() { $(this).remove(); });
    });
}

function markAsRead(notifId){
    $.ajax({
        url: 'classes/Master.php?f=mark_notification_read',
        method: 'POST',
        data: {id: notifId},
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success') {
                var currentCount = parseInt($('#notificationCount').text()) || 0;
                if(currentCount > 0) {
                    currentCount--;
                    if(currentCount > 0) {
                        $('#notificationCount').text(currentCount > 99 ? '99+' : currentCount);
                    } else {
                        $('#notificationCount').hide();
                    }
                }
            }
        }
    });
}

function markAllAsRead(){
    $.ajax({
        url: 'classes/Master.php?f=mark_all_notifications_read',
        method: 'POST',
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success') {
                $('.notification-item').removeClass('unread');
                $('#notificationCount').hide();
                $('#notificationFooter').html('<a href="./?p=customer/notifications">View all notifications</a>');
                showToast('All notifications marked as read', 'success');
            }
        }
    });
}

function deleteNotification(notifId){
    if(!confirm('Delete this notification?')) {
        return;
    }
    
    $.ajax({
        url: 'classes/Master.php?f=delete_notification',
        method: 'POST',
        data: {id: notifId},
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success') {
                $('.notification-item[data-id="' + notifId + '"]').fadeOut(300, function(){
                    $(this).remove();
                    
                    if($('.notification-item').length == 0) {
                        $('#notificationList').html(
                            '<div class="no-notifications">' +
                            '  <i class="fas fa-bell-slash" style="font-size: 40px; color: #ddd; margin-bottom: 10px;"></i>' +
                            '  <p>No notifications yet</p>' +
                            '</div>'
                        );
                    }
                });
                
                loadNotifications();
            }
        }
    });
}

function getNotificationIcon(title) {
    if(title.includes('Pending')) return 'fas fa-clock text-warning';
    if(title.includes('Packed')) return 'fas fa-box text-info';
    if(title.includes('Ready')) return 'fas fa-check-circle text-success';
    if(title.includes('Completed')) return 'fas fa-check-double text-success';
    if(title.includes('Cancelled')) return 'fas fa-times-circle text-danger';
    return 'fas fa-bell text-primary';
}

function escapeHtml(text) {
    if (text == null) {
        return '';
    }
    text = String(text);
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function showToast(message, type) {
    var colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6', danger: '#ef4444' };
    var icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ', danger: '✕' };
    var bg = colors[type] || colors.info;
    var icon = icons[type] || icons.info;

    // Remove any existing topbar toast
    $('.topbar-toast-msg').remove();

    var toast = $('<div class="topbar-toast-msg"></div>').css({
        position: 'fixed',
        bottom: '105px',
        right: '25px',
        background: bg,
        color: 'white',
        padding: '12px 20px',
        borderRadius: '10px',
        boxShadow: '0 4px 20px rgba(0,0,0,0.2)',
        zIndex: 100001,
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
        fontSize: '14px',
        fontWeight: '500',
        width: 'fit-content',
        maxWidth: 'min(360px, calc(100vw - 55px))',
        boxSizing: 'border-box',
        opacity: 0,
        transform: 'translateX(40px)'
    }).html('<span>' + icon + '</span><span>' + message + '</span>');

    $('body').append(toast);
    toast.animate({ opacity: 1 }, 300).css('transform', 'translateX(0)');

    setTimeout(function(){
        toast.animate({ opacity: 0 }, 300, function(){ $(this).remove(); });
    }, 3000);
}

// ========================================
// CART DROPDOWN FUNCTIONS
// ========================================

function loadCartItems() {
    $.ajax({
        url: 'classes/Master.php?f=get_cart_items',
        method: 'GET',
        dataType: 'json',
        success: function(resp) {
            if(resp.status == 'success') {
                displayCartItems(resp.data);
            } else {
                console.log('Failed to load cart items:', resp.msg);
            }
        },
        error: function(xhr, status, error) {
            console.log('Error loading cart items:', error);
        }
    });
}

function displayCartItems(items) {
    var $cartDropdownList = $('#cartDropdownList');
    $cartDropdownList.empty();
    
    if(items.length > 0) {
        items.forEach(function(item) {
            var itemHtml = 
                '<a href="./?p=product&id=' + item.product_id + '" class="cart-dropdown-item">' +
                '  <img src="' + escapeHtml(item.image) + '" alt="" class="cart-item-img">' +
                '  <div class="cart-item-info">' +
                '    <div class="cart-item-name">' + escapeHtml(item.product_name) + '</div>' +
                '    <div class="cart-item-price">$' + parseFloat(item.price).toFixed(2) + '</div>' +
                '  </div>' +
                '</a>';
                
            $cartDropdownList.append(itemHtml);
        });
        
        $('.cart-dropdown').show();
    } else {
        $('#cartDropdownList').html(
            '<div class="cart-dropdown-empty">' +
            '  <i class="fas fa-shopping-cart"></i>' +
            '  <p>Your cart is empty</p>' +
            '</div>'
        );
    }
}

// ========================================
// PRESCRIPTION UPLOAD MODAL FUNCTIONS
// ========================================

function openPrescriptionUploadModal(event) {
    if(event) event.preventDefault();
    $('#prescriptionUploadModal').modal('show');
}

function handlePrescriptionUpload() {
    const form = document.getElementById('prescriptionUploadForm');
    const formData = new FormData(form);
    
    $.ajax({
        url: 'ajax.php?action=upload_prescription',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            if(resp.status == 'success') {
                showToast('success', resp.msg || 'Prescription uploaded successfully!');
                $('#prescriptionUploadModal').modal('hide');
                form.reset();
                $('#topBarPreviewContainer').hide().html('');
            } else {
                showToast('error', resp.msg || 'Upload failed. Please try again.');
            }
        },
        error: function(xhr, status, error) {
            console.error('Upload error:', error);
            showToast('error', 'An error occurred during upload.');
        }
    });
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    const previewContainer = $('#topBarPreviewContainer');
    const fileNameDisplay = $('#fileName');
    
    if(!file) {
        previewContainer.hide().html('');
        fileNameDisplay.text('');
        return;
    }
    
    fileNameDisplay.text(file.name);
    
    if(file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewContainer.html('<img src="' + e.target.result + '" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">').show();
        };
        reader.readAsDataURL(file);
    } else if(file.type === 'application/pdf') {
        previewContainer.html('<i class="fas fa-file-pdf" style="font-size: 100px; color: #ef4444;"></i><p>PDF file selected</p>').show();
    }
}

// Initial load
$(document).ready(function() {
    loadCartItems();
});

/**
 * Silently check if any of the customer's pending_payment orders have
 * expired on PayMongo, and auto-cancel them on the server.
 */
function checkExpiredPayments() {
    if (typeof window.BASE_URL === 'undefined') return;
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=check_pending_payment',
        method: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp && resp.restored && resp.restored > 0) {
                window.location.href = window.BASE_URL + 'index.php?p=customer/checkout&payment=failed';
                return;
            }

            if (resp && resp.cancelled && resp.cancelled > 0) {
                loadNotifications();
                checkNewNotifications();
            }
        },
        error: function() {}
    });
}
