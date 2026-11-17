

(function(window, $) {
    'use strict';

    
    
    
    if (!window.base_url && window.BASE_URL) {
        window.base_url = window.BASE_URL;
    }
    if (!window.base_url && window._base_url_) {
        window.base_url = window._base_url_;
    }
    if (!window.base_url) {
        
        console.warn('[GlobalActionHandler] window.base_url not defined, attempting to derive from page');
        window.base_url = window.location.origin + '/pickupdrug/';
    }

    
    
    

    const ActionGuard = {
        
        inFlight: new WeakMap(),
        
        
        lastClickTime: {},
        
        
        rateLimits: {
            'add-to-cart': 500,
            'remove-from-cart': 300,
            'open-modal': 300,
            'close-modal': 200,
            'toggle-menu': 200,
            'navigation': 300,
            'filter': 200,
            'qty-change': 100  
        },

        
        canExecute(element, actionType) {
            
            if (this.inFlight.get(element)) {
                console.log(`[ActionGuard] Blocked: Request in flight for`, element);
                return false;
            }

            
            const rateLimit = this.rateLimits[actionType] || 300;
            const now = Date.now();
            const lastClick = this.lastClickTime[actionType] || 0;
            
            if (now - lastClick < rateLimit) {
                console.log(`[ActionGuard] Rate limited: ${actionType} (${now - lastClick}ms < ${rateLimit}ms)`);
                return false;
            }

            return true;
        },

        
        lock(element, actionType) {
            this.inFlight.set(element, true);
            this.lastClickTime[actionType] = Date.now();
            
            
            element.disabled = true;
            element.setAttribute('aria-busy', 'true');
            element.classList.add('action-loading');
            
            
            const skipSpinnerActions = ['qty-change', 'qty-increase', 'qty-decrease', 'modal-qty-increase', 'modal-qty-decrease'];
            if (element.tagName === 'BUTTON' && !skipSpinnerActions.includes(actionType)) {
                const originalContent = element.innerHTML;
                element.dataset.originalContent = originalContent;
                
                
                if (!element.querySelector('.action-spinner')) {
                    element.innerHTML = `<span class="action-spinner"></span>${originalContent}`;
                }
            }
        },

        
        unlock(element) {
            this.inFlight.delete(element);
            
            element.disabled = false;
            element.removeAttribute('aria-busy');
            element.classList.remove('action-loading');
            
            
            if (element.dataset.originalContent) {
                element.innerHTML = element.dataset.originalContent;
                delete element.dataset.originalContent;
            }
        }
    };

    
    
    

    const AjaxHelper = {
        
        request(options) {
            const defaults = {
                method: 'POST',
                timeout: 15000,
                dataType: 'json'
            };

            const config = { ...defaults, ...options };

            return new Promise((resolve, reject) => {
                const startTime = Date.now();
                
                $.ajax({
                    url: config.url,
                    method: config.method,
                    data: config.data,
                    dataType: config.dataType,
                    timeout: config.timeout,
                    
                    success: function(response) {
                        const duration = Date.now() - startTime;
                        console.log(`[AJAX] Success (${duration}ms):`, config.url, response);
                        
                        
                        const standardResponse = {
                            ok: true,
                            status: response.status || 'success',
                            message: response.msg || response.message || 'Success',
                            data: response
                        };
                        
                        resolve(standardResponse);
                    },
                    
                    error: function(xhr, status, error) {
                        const duration = Date.now() - startTime;
                        console.error(`[AJAX] Error (${duration}ms):`, config.url, {xhr, status, error});
                        
                        let errorMessage = 'Request failed';
                        
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. Please try again.';
                        } else if (xhr.status === 0) {
                            errorMessage = 'No connection. Please check your internet.';
                        } else if (xhr.status >= 500) {
                            errorMessage = 'Server error. Please try again later.';
                        } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                            errorMessage = xhr.responseJSON.msg;
                        }
                        
                        reject({
                            ok: false,
                            status: 'error',
                            message: errorMessage,
                            xhr: xhr,
                            error: error
                        });
                    }
                });
            });
        }
    };

    
    
    

    const QuantityThrottler = {
        pending: {},
        timeout: null,
        debounceDelay: 400,

        
        change(cartId, newQuantity, callback) {
            
            this.pending[cartId] = { quantity: newQuantity, callback };
            
            
            clearTimeout(this.timeout);
            
            
            this.timeout = setTimeout(() => {
                this.flush();
            }, this.debounceDelay);
        },

        
        flush() {
            const changes = { ...this.pending };
            this.pending = {};
            
            Object.keys(changes).forEach(cartId => {
                const { quantity, callback } = changes[cartId];
                if (callback) callback(parseInt(cartId), quantity);
            });
        }
    };

    
    
    

    const ActionHandlers = {
        
        'add-to-cart': function(element, data) {
            const productId = data.productId || element.dataset.productId;
            
            
            const qtyInput = document.getElementById('qty-' + productId);
            const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : (data.quantity || element.dataset.quantity || 1);
            
            if (!productId) {
                console.error('[Action] Missing product ID');
                return Promise.reject({ message: 'Invalid product' });
            }

            
            const productCard = element.closest('.glass-card') || element.closest('.product-card');
            const productImage = productCard ? (productCard.querySelector('.product-image-wrapper img') || productCard.querySelector('.product-image img')) : null;
            
            
            if (productImage && typeof flyToCart === 'function') {
                flyToCart(productImage);
            }

            return AjaxHelper.request({
                url: window.base_url + 'ajax.php?action=add_to_cart',
                data: {
                    product_id: productId,
                    quantity: quantity
                }
            }).then(response => {
                const cartId = response.data ? response.data.cart_id : null;
                if (cartId) {
                    try {
                        let selected = [];
                        const saved = sessionStorage.getItem('cartSelection');
                        if (saved) selected = JSON.parse(saved);
                        if (!selected.includes(cartId)) {
                            selected.push(cartId);
                        }
                        sessionStorage.setItem('cartSelection', JSON.stringify(selected));
                    } catch(e){}
                }
                
                if (qtyInput) {
                    qtyInput.value = 1;
                }
                
                
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
                
                
                if (typeof showToast === 'function') {
                    showToast(`Added ${quantity} item${quantity > 1 ? 's' : ''} to cart`, 'success');
                }
                
                return response;
            });
        },

        
        'qty-change': function(element, data) {
            const cartId = data.cartId || element.dataset.cartId;
            const delta = parseInt(data.delta || element.dataset.delta) || 0;
            const currentQty = parseInt(element.closest('[data-quantity]')?.dataset.quantity || data.quantity || element.dataset.quantity || 1);
            const newQty = currentQty + delta;
            
            if (newQty <= 0) {
                if (typeof removeFromCart === 'function') {
                    removeFromCart(cartId);
                }
                return Promise.resolve({ ok: true });
            }
            
            
            const qtyDisplay = element.closest('.cart-drawer-item')?.querySelector('.cart-drawer-qty span');
            if (qtyDisplay) {
                qtyDisplay.textContent = newQty;
            }
            
            
            return new Promise((resolve) => {
                QuantityThrottler.change(cartId, newQty, (id, qty) => {
                    if (typeof updateCartQty === 'function') {
                        updateCartQty(id, qty);
                        resolve({ ok: true });
                    }
                });
            });
        },

        
        'remove-from-cart': function(element, data) {
            const cartId = data.cartId || element.dataset.cartId;
            
            if (typeof removeFromCart === 'function') {
                removeFromCart(cartId);
            }
            
            return Promise.resolve({ ok: true });
        },

        
        'toggle-cart': function(element, data) {
            if (typeof toggleCart === 'function') {
                toggleCart();
            }
            return Promise.resolve({ ok: true });
        },

        
        'toggle-user-menu': function(element, data) {
            if (typeof toggleUserMenu === 'function') {
                toggleUserMenu();
            }
            return Promise.resolve({ ok: true });
        },

        
        'open-modal': function(element, data) {
            const modalId = data.modalId || element.dataset.modalId;
            const modal = document.getElementById(modalId);
            
            if (modal) {
                $(modal).modal('show');
            }
            
            return Promise.resolve({ ok: true });
        },

        
        'close-modal': function(element, data) {
            const modal = element.closest('.modal');
            if (modal) {
                $(modal).modal('hide');
            }
            return Promise.resolve({ ok: true });
        },

        
        'navigate': function(element, data) {
            const url = data.url || element.dataset.url || element.href;
            if (url) {
                window.location.href = url;
            }
            return Promise.resolve({ ok: true });        },

        
        'view-product': function(element, data) {
            const productId = data.productId || element.dataset.productId;
            if (typeof viewProduct === 'function') {
                viewProduct(productId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'filter-category': function(element, data) {
            const categoryId = data.categoryId || element.dataset.categoryId;
            console.log('[filter-category] Triggered with category ID:', categoryId);
            
            if (typeof filterByCategory === 'function') {
                filterByCategory(categoryId);
            } else {
                console.warn('[filter-category] filterByCategory function not found');
                
                window.location.href = window.base_url + '?p=customer/categories&category=' + categoryId;
            }
            return Promise.resolve({ ok: true });
        },

        
        'toggle-wishlist': function(element, data) {
            const productId = data.productId || element.dataset.productId;
            if (typeof toggleWishlist === 'function') {
                toggleWishlist(productId, event);
            }
            return Promise.resolve({ ok: true });
        },

        
        'close-product-modal': function(element, data) {
            if (typeof closeProductModal === 'function') {
                closeProductModal();
            }
            return Promise.resolve({ ok: true });
        },

        
        'add-to-cart-modal': function(element, data) {
            if (typeof addToCartFromModal === 'function') {
                addToCartFromModal();
            }
            return Promise.resolve({ ok: true });
        },

        
        'qty-increase': function(element, data) {
            const productId = data.productId || element.dataset.productId;
            const maxQty = parseInt(element.dataset.max) || 99;
            const input = document.getElementById('qty-' + productId);
            if (input) {
                let currentVal = parseInt(input.value) || 1;
                if (currentVal < maxQty) {
                    input.value = currentVal + 1;
                }
            }
            return Promise.resolve({ ok: true });
        },

        
        'qty-decrease': function(element, data) {
            const productId = data.productId || element.dataset.productId;
            const input = document.getElementById('qty-' + productId);
            if (input) {
                let currentVal = parseInt(input.value) || 1;
                if (currentVal > 1) {
                    input.value = currentVal - 1;
                }
            }
            return Promise.resolve({ ok: true });
        },

        
        'modal-qty-increase': function(element, data) {
            const input = document.getElementById('modalQtyInput');
            if (input) {
                let currentVal = parseInt(input.value) || 1;
                const maxQty = parseInt(input.max) || 99;
                if (currentVal < maxQty) {
                    input.value = currentVal + 1;
                }
            }
            return Promise.resolve({ ok: true });
        },

        
        'modal-qty-decrease': function(element, data) {
            const input = document.getElementById('modalQtyInput');
            if (input) {
                let currentVal = parseInt(input.value) || 1;
                if (currentVal > 1) {
                    input.value = currentVal - 1;
                }
            }
            return Promise.resolve({ ok: true });
        },

        
        'qty-change': function(element, data) {
            const cartId = data.cartId || element.dataset.cartId;
            const delta = parseInt(data.delta || element.dataset.delta) || 0;
            const currentQty = parseInt(data.quantity || element.dataset.quantity || element.closest('[data-quantity]')?.dataset.quantity) || 1;
            const newQty = currentQty + delta;
            if (newQty <= 0) {
                if (typeof removeFromCart === 'function') {
                    removeFromCart(cartId);
                }
                return Promise.resolve({ ok: true });
            }
            if (typeof updateCartQty === 'function') {
                updateCartQty(cartId, newQty);
            }
            return Promise.resolve({ ok: true });
        },

        
        'remove-from-cart': function(element, data) {
            const cartId = data.cartId || element.dataset.cartId;
            if (typeof removeFromCart === 'function') {
                removeFromCart(cartId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'clear-recent': function(element, data) {
            if (typeof clearRecentlyViewed === 'function') {
                clearRecentlyViewed();
            }
            return Promise.resolve({ ok: true });
        },

        
        'slide-nav': function(element, data) {
            const slideIndex = data.slideIndex || element.dataset.slideIndex;
            if (typeof currentSlide === 'function') {
                currentSlide(parseInt(slideIndex));
            }
            return Promise.resolve({ ok: true });
        },

        
        'view-image': function(element, data) {
            const imagePath = data.imagePath || element.dataset.imagePath;
            if (typeof viewImage === 'function') {
                viewImage(imagePath);
            }
            return Promise.resolve({ ok: true });
        },

        
        'open-prescription-upload': function(element, data) {
            if (typeof openPrescriptionUpload === 'function') {
                openPrescriptionUpload();
            }
            return Promise.resolve({ ok: true });
        },

        
        'delete-prescription': function(element, data) {
            const prescriptionId = data.prescriptionId || element.dataset.prescriptionId;
            if (typeof deletePrescription === 'function') {
                deletePrescription(prescriptionId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'open-customize-modal': function(element, data) {
            const prescriptionId = data.prescriptionId || element.dataset.prescriptionId;
            const notes = data.notes || element.dataset.notes || '';
            if (typeof openCustomizeModal === 'function') {
                openCustomizeModal(prescriptionId, notes);
            }
            return Promise.resolve({ ok: true });
        },

        
        'save-customization': function(element, data) {
            if (typeof saveCustomization === 'function') {
                saveCustomization();
            }
            return Promise.resolve({ ok: true });
        },

        
        'handle-prescription-upload': function(element, data) {
            if (typeof handlePrescriptionUpload === 'function') {
                handlePrescriptionUpload(event);
            }
            return Promise.resolve({ ok: true });
        },

        
        'trigger-file-input': function(element, data) {
            const targetId = data.targetId || element.dataset.targetId;
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.click();
            }
            return Promise.resolve({ ok: true });
        },

        
        'pause-reminder': function(element, data) {
            const reminderId = data.reminderId || element.dataset.reminderId;
            if (typeof pauseReminder === 'function') {
                pauseReminder(reminderId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'resume-reminder': function(element, data) {
            const reminderId = data.reminderId || element.dataset.reminderId;
            if (typeof resumeReminder === 'function') {
                resumeReminder(reminderId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'complete-reminder': function(element, data) {
            const reminderId = data.reminderId || element.dataset.reminderId;
            if (typeof completeReminder === 'function') {
                completeReminder(reminderId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'delete-reminder': function(element, data) {
            const reminderId = data.reminderId || element.dataset.reminderId;
            if (typeof deleteReminder === 'function') {
                deleteReminder(reminderId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'scroll-to': function(element, data) {
            const selector = data.selector || element.dataset.selector;
            const targetElement = document.querySelector(selector);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth', block: data.block || 'start' });
            }
            return Promise.resolve({ ok: true });
        },

        
        'open-window': function(element, data) {
            const url = data.url || element.dataset.url;
            const target = data.target || element.dataset.target || '_blank';
            if (url) {
                window.open(url, target);
            }
            return Promise.resolve({ ok: true });
        },

        
        'remove-element': function(element, data) {
            const targetId = data.targetId || element.dataset.targetId;
            const targetElement = targetId ? document.getElementById(targetId) : element.closest(data.closest || element.dataset.closest);
            if (targetElement) {
                targetElement.remove();
            }
            return Promise.resolve({ ok: true });
        },

        
        'hide-element': function(element, data) {
            const targetId = data.targetId || element.dataset.targetId;
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.style.display = 'none';
            }
            return Promise.resolve({ ok: true });        
        },

        
        'scroll-to-prescription-upload': function(element, data) {
            const label = document.querySelector('[for=prescription_upload]');
            if (label) {
                label.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function() {
                    const uploadArea = document.getElementById('upload-area');
                    if (uploadArea) {
                        uploadArea.click();
                    }
                }, 800);
            }
            return Promise.resolve({ ok: true });
        },

        
        'mark-all-read': function(element, data) {
            console.log('[Action] mark-all-read triggered');
            
            
            if (typeof markAllRead === 'function') {
                markAllRead();
                return Promise.resolve({ ok: true });
            }
            
            
            return AjaxHelper.request({
                url: window.base_url + 'ajax.php?action=mark_all_notifications_read',
                method: 'POST',
                dataType: 'json'
            }).then(response => {
                if (response.status === 'success') {
                    
                    const badge = document.getElementById('notificationCount');
                    if (badge) {
                        badge.style.display = 'none';
                        badge.textContent = '0';
                    }
                    
                    
                    document.querySelectorAll('.notification-item, .notif-item').forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    if (typeof showToast === 'function') {
                        showToast('All notifications marked as read', 'success');
                    }
                }
                return response;
            });
        },

        
        'close-confirm-popup': function(element, data) {
            if (typeof closeConfirmPopup === 'function') {
                closeConfirmPopup();
            }
            return Promise.resolve({ ok: true });
        },

        
        'view-product-from-search': function(element, data) {
            const productId = data.productId || element.dataset.productId;
            if (typeof viewProductFromSearch === 'function') {
                viewProductFromSearch(productId);
            }
            return Promise.resolve({ ok: true });
        },

        
        'toggle-notifications': function(element, data) {
            if (typeof toggleNotifications === 'function') {
                toggleNotifications();
            }
            return Promise.resolve({ ok: true });
        },

        
        'toggle-mobile-menu': function(element, data) {
            if (typeof toggleMobileMenu === 'function') {
                toggleMobileMenu();
            }
            return Promise.resolve({ ok: true });
        },

        
        'remove-toast': function(element, data) {
            const toast = element.closest('.notification-toast, .toast');
            if (toast) {
                $(toast).fadeOut(200, function() {
                    $(this).remove();
                });
            } else if (typeof removeToast === 'function') {
                removeToast(element.parentElement);
            }
            return Promise.resolve({ ok: true });
        }
    };

    
    
    

    
    function handleGlobalClick(event) {
        const element = event.target.closest('[data-action]');
        
        if (!element) return;
        
        
        const actionType = element.dataset.action;
        const handler = ActionHandlers[actionType];
        
        if (!handler) {
            console.warn(`[Action] No handler for: ${actionType}`);
            return;
        }

        
        if (!ActionGuard.canExecute(element, actionType)) {
            event.preventDefault();
            return;
        }

        
        if (element.tagName === 'BUTTON' || element.tagName === 'A') {
            event.preventDefault();
        }

        
        ActionGuard.lock(element, actionType);

        
        const actionData = { ...element.dataset };

        
        Promise.resolve(handler(element, actionData))
            .then(response => {
                console.log(`[Action] Success: ${actionType}`, response);
                ActionGuard.unlock(element);
            })
            .catch(error => {
                console.error(`[Action] Failed: ${actionType}`, error);
                ActionGuard.unlock(element);
                
                
                if (typeof showToast === 'function') {
                    showToast(error.message || 'Action failed', 'error');
                }
            });
    }

    
    
    

    function initialize() {
        
        document.addEventListener('click', handleGlobalClick, true);
        
        console.log('[GlobalActionHandler] Initialized with handlers:', Object.keys(ActionHandlers));
        
        
        window.ActionGuard = ActionGuard;
        window.ActionHandlers = ActionHandlers;
        window.AjaxHelper = AjaxHelper;
    }

    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

})(window, jQuery);
