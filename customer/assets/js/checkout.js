

$(function(){
    
    $('#order-form').submit(function(e){
        e.preventDefault();
        
        
        const blockCheckout = window.blockCheckout || false;
        if(blockCheckout) {
            if(typeof alert_toast === 'function') {
                alert_toast('A system-validated prescription is required before checkout.', 'error');
            } else {
                alert('A system-validated prescription is required before checkout.');
            }
            return false;
        }
        
        const totalAmount = $('input[name="total_amount"]').val();
        
        
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
        
        
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=place_order',
            method: 'POST',
            data: $(this).serialize(),
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
                        
                        if (resp.paymongo_checkout && resp.checkout_url) {
                            setTimeout(() => {
                                window.location.href = resp.checkout_url;
                            }, 1000);
                        } else {
                            setTimeout(() => {
                                window.location.href = './?p=orders';
                            }, 2000);
                        }
                        
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
            
            
            validatePrescriptionWithCart(file);
        };
        reader.readAsDataURL(file);
    }
    
    function validatePrescriptionWithCart(file) {
        const formData = new FormData();
        formData.append('prescription_file', file);
        formData.append('csrf_token', $('input[name="csrf_token"]').val() || '');

        $.ajax({
            url: _base_url_ + 'ajax.php?action=upload_cart_prescription',
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    prescriptionValidation = response.validation || {};
                    $('#prescription_validation').val('matched');
                    $('#prescription_details').val(JSON.stringify(response.validation || {}));
                    $('#validation-status').html('<i class="fas fa-check-circle"></i> Valid prescription — checkout unlocked').css({
                        'background': '#d1fae5',
                        'color': '#065f46'
                    });
                    window.blockCheckout = false;
                    $('#place-order-btn').prop('disabled', false).css({'opacity': '1', 'cursor': 'pointer'}).find('span').text('Place Pickup Order');
                } else {
                    const reason = response.reason || response.msg || 'Validation failed';
                    $('#validation-status').html('<i class="fas fa-exclamation-circle"></i> ' + $('<div>').text(reason).html()).css({
                        'background': '#fecaca',
                        'color': '#991b1b'
                    });
                    window.blockCheckout = true;
                }
            },
            error: function() {
                $('#validation-status').html('<i class="fas fa-exclamation-circle"></i> Validation error - Please try again').css({
                    'background': '#fecaca',
                    'color': '#991b1b'
                });
            }
        });
    }
});
