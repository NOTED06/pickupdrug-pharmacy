

$(function(){
    
    $('#order-form').submit(function(e){
        e.preventDefault();
        
        
        
        const blockCheckout = window.blockCheckout || false;
        if(blockCheckout) {
            if(typeof alert_toast === 'function') {
                alert_toast('Please upload a prescription before placing your order.', 'error');
            } else {
                alert('Please upload a prescription before placing your order.');
            }
            return false;
        }
        
        const totalAmount = $('input[name="total_amount"]').val();
        
        
        if(typeof window.validatePayment === 'function') {
            if(!window.validatePayment()) {
                return false;
            }
        }
        
        if(!totalAmount || parseFloat(totalAmount) <= 0) {
            if(typeof alert_toast === 'function') {
                alert_toast('Invalid order total', 'error');
            } else {
                alert('Invalid order total');
            }
            return;
        }
        
        
        const submitBtn = $('#place-order-btn');
        const originalText = submitBtn.find('span').text();
        
        submitBtn.prop('disabled', true);
        submitBtn.find('span').text('Processing Order...');
        
        if(typeof start_loader === 'function') {
            start_loader();
        }
        
        
        const formData = new FormData(this);
        
        
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=place_order',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'text',
            timeout: 30000,
            error: function(xhr, status, error) {
                console.log('AJAX Error Details:');
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('Response Text:', xhr.responseText);
                console.log('Status Code:', xhr.status);
                
                let errorMessage = 'An error occurred while placing your order.';
                
                if(status === 'timeout') {
                    errorMessage = 'Request timed out. Please check your connection and try again.';
                } else if(xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please contact support.';
                } else if(xhr.status === 404) {
                    errorMessage = 'Order service not found. Please contact support.';
                } else if(xhr.responseText) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = xhr.responseText;
                    const errorText = tempDiv.textContent || tempDiv.innerText || '';
                    if(errorText.includes('Fatal error') || errorText.includes('Parse error')) {
                        errorMessage = 'Server configuration error. Please contact support.';
                    }
                }
                
                if(typeof alert_toast === 'function') {
                    alert_toast(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
                
                submitBtn.prop('disabled', false);
                submitBtn.find('span').text(originalText);
                
                if(typeof end_loader === 'function') {
                    end_loader();
                }
            },
            success: function(response) {
                console.log('Raw Response:', response);
                
                let resp;
                
                try {
                    resp = JSON.parse(response);
                } catch(e) {
                    console.log('JSON Parse Error:', e);
                    
                    const jsonMatch = response.match(/\{[^}]*"status"[^}]*\}/);
                    if(jsonMatch) {
                        try {
                            resp = JSON.parse(jsonMatch[0]);
                        } catch(e2) {
                            console.log('JSON extraction failed:', e2);
                            resp = null;
                        }
                    } else {
                        resp = null;
                    }
                }
                
                if(resp && resp.status) {
                    if(resp.status === 'success') {
                        submitBtn.find('span').text('Order Placed Successfully!');
                        
                        const successMessage = resp.msg || 'Order placed successfully!';
                        
                        if(typeof alert_toast === 'function') {
                            alert_toast(successMessage, 'success');
                        } else {
                            alert(successMessage);
                        }
                        
                        setTimeout(() => {
                            window.location.href = './';
                        }, 2000);
                        
                    } else {
                        const errorMessage = resp.msg || 'An error occurred while placing your order';
                        
                        if(typeof alert_toast === 'function') {
                            alert_toast(errorMessage, 'error');
                        } else {
                            alert(errorMessage);
                        }
                        
                        submitBtn.prop('disabled', false);
                        submitBtn.find('span').text(originalText);
                    }
                } else {
                    console.log('Invalid response format:', response);
                    
                    if(response.includes('Fatal error') || response.includes('Parse error')) {
                        if(typeof alert_toast === 'function') {
                            alert_toast('Server configuration error. Please contact support.', 'error');
                        } else {
                            alert('Server configuration error. Please contact support.');
                        }
                    } else {
                        if(typeof alert_toast === 'function') {
                            alert_toast('Invalid response from server. Please try again.', 'error');
                        } else {
                            alert('Invalid response from server. Please try again.');
                        }
                    }
                    
                    submitBtn.prop('disabled', false);
                    submitBtn.find('span').text(originalText);
                }
                
                if(typeof end_loader === 'function') {
                    end_loader();
                }
            }
        });
    });
    
    
    let formSubmitted = false;
    $('#order-form').on('submit', function() {
        if(formSubmitted) {
            return false;
        }
        formSubmitted = true;
        
        setTimeout(() => {
            formSubmitted = false;
        }, 5000);
    });
    
    
    let prescriptionFile = null;
    let prescriptionValidation = null;
    
    
    $('#upload-area').click(function() {
        $('#prescription_upload').click();
    });
    
    
    $('#prescription_upload').change(function(e) {
        const file = e.target.files[0];
        if (file) {
            handlePrescriptionUpload(file);
        }
    });
    
    
    $('#remove-prescription').click(function() {
        prescriptionFile = null;
        prescriptionValidation = null;
        $('#prescription_upload').val('');
        $('#upload-preview').hide();
        $('#upload-area').show();
        $('#prescription_validation').val('');
        $('#prescription_details').val('');
    });
    
    function handlePrescriptionUpload(file) {
        
        if (!file.type.startsWith('image/')) {
            alert_toast('Please upload a valid image file', 'error');
            return;
        }
        
        
        if (file.size > 5 * 1024 * 1024) {
            alert_toast('File size must be less than 5MB', 'error');
            return;
        }
        
        prescriptionFile = file;
        
        
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#prescription-preview').attr('src', e.target.result);
            $('#prescription-filename').text(file.name);
            $('#upload-area').hide();
            $('#upload-preview').show();
            $('#validation-status').html('<i class="fas fa-spinner fa-spin"></i> Validating with AI...').css({
                'background': '#fef3c7',
                'color': '#92400e'
            });
            
            
            validatePrescriptionWithCart(e.target.result);
        };
        reader.readAsDataURL(file);
    }
    
    
    window.validatePrescriptionWithCart = function(imageDataUrl) {
        
        const cartProducts = window.cartProducts || [];
        
        
        const base64Data = imageDataUrl.split(',')[1];
        
        
        $.ajax({
            url: '../ajax.php?action=validate_prescription_cart',
            method: 'POST',
            data: {
                image_data: base64Data,
                cart_products: JSON.stringify(cartProducts)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.validation) {
                    prescriptionValidation = response.validation;
                    $('#prescription_validation').val(response.validation.validation);
                    $('#prescription_details').val(JSON.stringify(response.validation));
                    
                    const status = response.validation.validation;
                    let statusHtml = '';
                    let statusColor = {};
                    
                    if (status === 'matched') {
                        statusHtml = '<i class="fas fa-check-circle"></i> Perfect Match! Prescription matches your cart items';
                        statusColor = {'background': '#d1fae5', 'color': '#065f46'};
                    } else if (status === 'partial') {
                        statusHtml = '<i class="fas fa-exclamation-triangle"></i> Partial Match - Some differences detected';
                        statusColor = {'background': '#fed7aa', 'color': '#92400e'};
                    } else if (status === 'mismatch') {
                        statusHtml = '<i class="fas fa-times-circle"></i> Mismatch - Prescription doesn\'t match cart';
                        statusColor = {'background': '#fecaca', 'color': '#991b1b'};
                    } else {
                        statusHtml = '<i class="fas fa-question-circle"></i> Invalid prescription image';
                        statusColor = {'background': '#e5e7eb', 'color': '#374151'};
                    }
                    
                    $('#validation-status').html(statusHtml).css(statusColor);
                    
                    
                    if (response.validation.notes) {
                        const notesDiv = $('<div>').css({
                            'margin-top': '10px',
                            'padding': '10px',
                            'background': 'rgba(255,255,255,0.8)',
                            'border-radius': '8px',
                            'font-size': '0.85rem',
                            'text-align': 'left'
                        }).html('<strong>Details:</strong><br>' + response.validation.notes);
                        $('#validation-status').after(notesDiv);
                    }
                } else {
                    $('#validation-status').html('<i class="fas fa-exclamation-circle"></i> Validation failed').css({
                        'background': '#fecaca',
                        'color': '#991b1b'
                    });
                }
            },
            error: function() {
                $('#validation-status').html('<i class="fas fa-exclamation-circle"></i> Validation error - Please try again').css({
                    'background': '#fecaca',
                    'color': '#991b1b'
                });
            }
        });
    };

    
    var $urgentCheckbox = $('#is_urgent');
    var $urgentModal = $('#urgentConfirmModal');
    var isUrgentConfirmed = false;

    window.confirmUrgentOrder = function() {
        isUrgentConfirmed = true;
        $urgentCheckbox.prop('checked', true).trigger('change');
        if (typeof updateCheckoutUrgentVisual === 'function') {
            updateCheckoutUrgentVisual(true);
        }
        $urgentModal.css('display', 'none');
    };

    window.cancelUrgentOrder = function() {
        isUrgentConfirmed = false;
        $urgentCheckbox.prop('checked', false).trigger('change');
        if (typeof updateCheckoutUrgentVisual === 'function') {
            updateCheckoutUrgentVisual(false);
        }
        $urgentModal.css('display', 'none');
    };

    if ($urgentModal.length > 0) {
        $urgentModal.on('click', function(e) {
            if ($(e.target).is('#urgentConfirmModal')) {
                window.cancelUrgentOrder();
            }
        });

        $urgentCheckbox.on('click', function(e) {
            if ($(this).is(':checked')) {
                if (!isUrgentConfirmed) {
                    e.preventDefault();
                    $(this).prop('checked', false);
                    $urgentModal.css('display', 'flex');
                    return false;
                }
                isUrgentConfirmed = false;
            } else {
                isUrgentConfirmed = false;
            }
        });
    }
});
