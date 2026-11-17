$(function(){
    
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right','');

    window.dismissPaymentBanner = function(button) {
        const banner = button.closest('.payment-banner');
        if (banner) banner.style.display = 'none';
        const url = new URL(window.location.href);
        url.searchParams.delete('payment');
        url.searchParams.delete('order_id');
        window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
    };

    
    $(document).on('click', '.view-order', function(){
        var orderId = $(this).attr('data-id');
        
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right','');
        function openModal() {
            if (typeof uni_modal === 'function') {
                var modalUrl = (typeof _base_url_ !== 'undefined' ? _base_url_ : '') + "orders/view_order.php?id=" + orderId;
                uni_modal("Order Details", modalUrl, 'modal-lg');
            } else {
                
                setTimeout(openModal, 200);
            }
        }
        openModal();
    });

    
    $(document).on('click', '.btn-invoice:not([disabled])', function(){
        var orderId = $(this).attr('data-id');
        if (!orderId) return;
        
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right','');
        function openInvoice() {
            if (typeof uni_modal === 'function') {
                var modalUrl = (typeof _base_url_ !== 'undefined' ? _base_url_ : '') + "orders/view_order.php?id=" + orderId + "&invoice=1";
                uni_modal("Order Invoice", modalUrl, 'modal-lg');
            } else {
                setTimeout(openInvoice, 200);
            }
        }
        openInvoice();
    });

    
    $(document).on('hidden.bs.modal', '#uni_modal', function() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right','');
    });

    
    $('.filter-pill').click(function(){
        $('.filter-pill').removeClass('active');
        $(this).addClass('active');
        
        const status = $(this).attr('data-status');
        
        if(status === 'all'){
            $('.order-card').fadeIn(300);
        } else {
            $('.order-card').hide();
            $(`.order-card[data-status="${status}"]`).fadeIn(300);
        }
    })
    
    
    $('#orderSearch').on('keyup', function(){
        const searchTerm = $(this).val().toLowerCase();
        
        $('.order-card').each(function(){
            const orderCode = $(this).find('.id-code').text().toLowerCase();
            const orderTitle = $(this).find('.order-title').text().toLowerCase();
            
            if(orderCode.includes(searchTerm) || orderTitle.includes(searchTerm)){
                $(this).fadeIn(300);
            } else {
                $(this).hide();
            }
        });
    });

    
    var _popupResolve = null;
    var _closePopupTimeout = null;
    
    function showOrderPopup(opts) {
        var overlay = document.getElementById('orderConfirmOverlay');
        var popup = document.getElementById('orderConfirmPopup');
        var icon = document.getElementById('orderConfirmIcon');
        var iconI = document.getElementById('orderConfirmIconI');
        var title = document.getElementById('orderConfirmTitle');
        var message = document.getElementById('orderConfirmMessage');
        var inputWrap = document.getElementById('orderConfirmInputWrap');
        var input = document.getElementById('orderConfirmInput');
        var cancelBtn = document.getElementById('orderConfirmCancel');
        var okBtn = document.getElementById('orderConfirmOk');
        var okIcon = document.getElementById('orderConfirmOkIcon');
        var okText = document.getElementById('orderConfirmOkText');
        
        if (!overlay || !popup) {
            return Promise.resolve({ confirmed: false });
        }
        
        clearTimeout(_closePopupTimeout);
        
        
        icon.className = 'order-confirm-icon ' + (opts.iconClass || 'icon-followup');
        iconI.className = opts.icon || 'fa-solid fa-bell';
        title.textContent = opts.title || '';
        message.innerHTML = opts.message || '';
        input.value = '';
        
        inputWrap.style.display = opts.showInput ? 'block' : 'none';
        cancelBtn.style.display = opts.hideCancel ? 'none' : '';
        
        okBtn.className = 'order-confirm-btn ' + (opts.okClass || 'order-confirm-btn-followup');
        okIcon.className = opts.okIcon || 'fa-solid fa-bell';
        okText.textContent = opts.okText || 'Confirm';
        
        popup.classList.remove('closing');
        overlay.classList.add('active');
        
        return new Promise(function(resolve) {
            _popupResolve = resolve;
            
            okBtn.onclick = function() {
                closeOrderPopup();
                resolve({ confirmed: true, value: input.value });
            };
            cancelBtn.onclick = function() {
                closeOrderPopup();
                resolve({ confirmed: false });
            };
        });
    }
    
    function closeOrderPopup() {
        var overlay = document.getElementById('orderConfirmOverlay');
        var popup = document.getElementById('orderConfirmPopup');
        if (!overlay || !popup) return;
        popup.classList.add('closing');
        clearTimeout(_closePopupTimeout);
        _closePopupTimeout = setTimeout(function() {
            overlay.classList.remove('active');
            popup.classList.remove('closing');
        }, 200);
    }
    
    
    window.showOrderPopup = showOrderPopup;
    window.closeOrderPopup = closeOrderPopup;
    
    
    $(document).on('click', '#orderConfirmOverlay', function(e) {
        if (e.target.id === 'orderConfirmOverlay') {
            closeOrderPopup();
            if (_popupResolve) {
                _popupResolve({ confirmed: false });
                _popupResolve = null;
            }
        }
    });

    
    $(document).on('click', '.btn-followup', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var btn = $(this);
        var orderId = btn.attr('data-id');
        var orderCode = btn.attr('data-code');
        
        if (!orderId) return;
        
        showOrderPopup({
            iconClass: 'icon-followup',
            icon: 'fa-solid fa-bell',
            title: 'Follow Up Order?',
            message: 'Send a follow-up notification for <strong>Order #' + orderCode + '</strong>? The pharmacy will be notified to check your order.',
            okClass: 'order-confirm-btn-followup',
            okIcon: 'fa-solid fa-bell',
            okText: 'Follow Up'
        }).then(function(result) {
            if (!result.confirmed) return;
            closeOrderPopup();
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
            
            var postData = 'order_id=' + encodeURIComponent(orderId);
            if (typeof _csrf_token_ !== 'undefined') {
                postData += '&csrf_token=' + encodeURIComponent(_csrf_token_);
            }
            
            $.ajax({
                url: _base_url_ + 'ajax.php?action=follow_up_order',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        showOrderPopup({
                            iconClass: 'icon-success',
                            icon: 'fa-solid fa-check',
                            title: 'Sent!',
                            message: resp.msg,
                            hideCancel: true,
                            okClass: 'order-confirm-btn-success',
                            okIcon: 'fa-solid fa-check',
                            okText: 'OK'
                        }).then(function() { closeOrderPopup(); });
                        
                        btn.html('<i class="fa-solid fa-check"></i> Sent').addClass('btn-sent');
                        setTimeout(function() {
                            btn.prop('disabled', false).html('<i class="fa-solid fa-bell"></i> Follow Up').removeClass('btn-sent');
                        }, 30000);
                    } else {
                        showOrderPopup({
                            iconClass: 'icon-error',
                            icon: 'fa-solid fa-exclamation',
                            title: 'Oops!',
                            message: resp.msg || 'Failed to send follow-up.',
                            hideCancel: true,
                            okClass: 'order-confirm-btn-success',
                            okIcon: 'fa-solid fa-check',
                            okText: 'OK'
                        }).then(function() { closeOrderPopup(); });
                        btn.prop('disabled', false).html('<i class="fa-solid fa-bell"></i> Follow Up');
                    }
                },
                error: function() {
                    showOrderPopup({
                        iconClass: 'icon-error',
                        icon: 'fa-solid fa-wifi',
                        title: 'Network Error',
                        message: 'Could not reach the server. Please try again.',
                        hideCancel: true,
                        okClass: 'order-confirm-btn-success',
                        okIcon: 'fa-solid fa-check',
                        okText: 'OK'
                    }).then(function() { closeOrderPopup(); });
                    btn.prop('disabled', false).html('<i class="fa-solid fa-bell"></i> Follow Up');
                }
            });
        });
    });

    
    $(document).on('click', '.btn-cancel-order', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var btn = $(this);
        var orderId = btn.attr('data-id');
        var orderCode = btn.attr('data-code');
        
        if (!orderId) return;
        
        showOrderPopup({
            iconClass: 'icon-cancel',
            icon: 'fa-solid fa-times',
            title: 'Cancel Order?',
            message: 'Are you sure you want to cancel <strong>Order #' + orderCode + '</strong>?',
            showInput: true,
            okClass: 'order-confirm-btn-danger',
            okIcon: 'fa-solid fa-trash',
            okText: 'Cancel Order'
        }).then(function(result) {
            if (!result.confirmed) return;
            closeOrderPopup();
            
            var reason = result.value || '';
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cancelling...');
            
            var postData = 'order_id=' + encodeURIComponent(orderId) + '&reason=' + encodeURIComponent(reason);
            if (typeof _csrf_token_ !== 'undefined') {
                postData += '&csrf_token=' + encodeURIComponent(_csrf_token_);
            }
            
            $.ajax({
                url: _base_url_ + 'ajax.php?action=customer_cancel_order',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        
                        btn.removeClass('btn-cancel-order')
                           .prop('disabled', true)
                           .html('<i class="fa-solid fa-ban"></i> Cancelled');
                        var card = btn.closest('.order-card');
                        if (card.length) {
                            card.find('.cancel-timer-badge').remove();
                            card.find('.order-status')
                                .attr('class', 'order-status status-cancelled')
                                .html('<span class="status-dot"></span><span class="status-text">CANCELLED</span>');
                            card.attr('data-status', '4');
                        }
                        if (typeof alert_toast === 'function') {
                            alert_toast(resp.msg || 'Order cancelled successfully.', 'success');
                        }
                        showOrderPopup({
                            iconClass: 'icon-success',
                            icon: 'fa-solid fa-check',
                            title: 'Cancelled',
                            message: resp.msg || 'Order cancelled successfully.',
                            hideCancel: true,
                            okClass: 'order-confirm-btn-success',
                            okIcon: 'fa-solid fa-check',
                            okText: 'OK'
                        }).then(function() { 
                            closeOrderPopup(); 
                            location.reload();
                        });
                        
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    } else {
                        showOrderPopup({
                            iconClass: 'icon-error',
                            icon: 'fa-solid fa-exclamation',
                            title: 'Oops!',
                            message: resp.msg || 'Failed to cancel order.',
                            hideCancel: true,
                            okClass: 'order-confirm-btn-success',
                            okIcon: 'fa-solid fa-check',
                            okText: 'OK'
                        }).then(function() { closeOrderPopup(); });
                        btn.prop('disabled', false).html('<i class="fa-solid fa-times"></i> Cancel');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cancel order error:', status, error);
                    showOrderPopup({
                        iconClass: 'icon-error',
                        icon: 'fa-solid fa-wifi',
                        title: 'Network Error',
                        message: 'Could not reach the server. Please try again.',
                        hideCancel: true,
                        okClass: 'order-confirm-btn-success',
                        okIcon: 'fa-solid fa-check',
                        okText: 'OK'
                    }).then(function() { closeOrderPopup(); });
                    btn.prop('disabled', false).html('<i class="fa-solid fa-times"></i> Cancel');
                }
            });
        });
    });

    
    function checkPendingOrderTimers() {
        var nowSec = Math.floor(Date.now() / 1000);
        var CANCEL_LIMIT = 15 * 60; 
        
        $('.order-card[data-status="0"]').each(function() {
            var card = $(this);
            var orderId = card.attr('data-id');
            var created = parseInt(card.attr('data-created'), 10);
            
            if (!created || !orderId) return;
            
            var elapsed = nowSec - created;
            var remaining = CANCEL_LIMIT - elapsed;
            
            
            var timerBadge = card.find('.cancel-timer-badge');
            if (remaining > 0) {
                var mins = Math.floor(remaining / 60);
                var secs = remaining % 60;
                var timeStr = mins + ':' + (secs < 10 ? '0' : '') + secs;
                
                if (timerBadge.length === 0) {
                    card.find('.order-status').after('<span class="cancel-timer-badge"><i class="fa-solid fa-clock"></i> ' + timeStr + ' left to cancel</span>');
                } else {
                    timerBadge.html('<i class="fa-solid fa-clock"></i> ' + timeStr + ' left to cancel');
                }
            } else {
                
                if (timerBadge.length) {
                    timerBadge.html('<i class="fa-solid fa-clock"></i> Time exceeded');
                    timerBadge.addClass('expired');
                }
            }
            
            
            var cancelBtn = card.find('.btn-cancel-order:not([disabled])');
            if (cancelBtn.length) {
                var cancelTimer = cancelBtn.find('.cancel-timer');
                if (remaining > 0) {
                    var mins = Math.floor(remaining / 60);
                    var secs = remaining % 60;
                    var timeStr = mins + ':' + (secs < 10 ? '0' : '') + secs;
                    cancelTimer.text('(' + timeStr + ')');
                    
                    
                    cancelTimer.removeClass('warning critical');
                    if (remaining <= 60) {
                        cancelTimer.addClass('critical');
                    } else if (remaining <= 180) {
                        cancelTimer.addClass('warning');
                    }
                } else {
                    
                    cancelBtn.prop('disabled', true)
                        .attr('title', 'Cancellation window expired (15 minutes)')
                        .html('<i class="fa-solid fa-times"></i> Cancel Expired');
                }
            }
        });
    }
    
    
    checkPendingOrderTimers();
    setInterval(checkPendingOrderTimers, 1000);

    
    var urlParams = new URLSearchParams(window.location.search);
    var targetOrderId = urlParams.get('order_id');
    if (targetOrderId) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('order_id');
        window.history.replaceState({}, document.title, currentUrl.pathname + currentUrl.search + currentUrl.hash);
        
        function autoOpenOrder() {
            if (typeof uni_modal === 'function') {
                var modalUrl = (typeof _base_url_ !== 'undefined' ? _base_url_ : '') + "orders/view_order.php?id=" + targetOrderId;
                uni_modal("Order Details", modalUrl, 'modal-lg');
            } else {
                setTimeout(autoOpenOrder, 200);
            }
        }
        setTimeout(autoOpenOrder, 500);
    }

});
