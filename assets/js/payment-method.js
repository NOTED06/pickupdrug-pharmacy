

(function() {
    'use strict';
    
    
    const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
    const qrPaymentSection = document.getElementById('qrPaymentSection');
    const receiptUploadArea = document.getElementById('receiptUploadArea');
    const receiptInput = document.getElementById('receiptInput');
    const receiptPreview = document.getElementById('receiptPreview');
    const receiptPreviewImg = document.getElementById('receiptPreviewImg');
    const receiptFilename = document.getElementById('receiptFilename');
    const receiptRemoveBtn = document.getElementById('receiptRemoveBtn');
    const paymentMethodDisplay = document.getElementById('paymentMethodDisplay');
    const qrCodeImage = document.getElementById('qrCodeImage');
    
    let selectedPaymentMethod = 'cash';
    let selectedReceiptFile = null;
    
    
    function init() {
        
        paymentOptions.forEach(option => {
            option.addEventListener('change', handlePaymentMethodChange);
        });
        
        
        initAcknowledgeCheckbox();
        
        
        if (receiptUploadArea) {
            receiptUploadArea.addEventListener('click', () => receiptInput.click());
            receiptUploadArea.addEventListener('dragover', handleDragOver);
            receiptUploadArea.addEventListener('dragleave', handleDragLeave);
            receiptUploadArea.addEventListener('drop', handleDrop);
        }
        
        if (receiptInput) {
            receiptInput.addEventListener('change', handleReceiptSelect);
        }
        
        if (receiptRemoveBtn) {
            receiptRemoveBtn.addEventListener('click', removeReceipt);
        }
        
        
        const defaultOption = document.querySelector('input[name="payment_method"]:checked');
        if (defaultOption) {
            selectedPaymentMethod = defaultOption.value;
        }
    }
    
    
    function handlePaymentMethodChange(e) {
        selectedPaymentMethod = e.target.value;
        
        if (selectedPaymentMethod === 'gcash' || selectedPaymentMethod === 'maya') {
            showQRPayment(selectedPaymentMethod);
        } else {
            hideQRPayment();
        }
        
        
        const hiddenInput = document.getElementById('selectedPaymentMethod');
        if (hiddenInput) {
            hiddenInput.value = selectedPaymentMethod;
        }
    }
    
    
    function toggleOnlinePaymentReminder(show) {
        const reminder = document.getElementById('onlinePaymentReminder');
        if (reminder) {
            reminder.style.display = show ? 'flex' : 'none';
        }
        
        const checkbox = document.getElementById('acknowledgePaymentReminder');
        const placeOrderBtn = document.getElementById('place-order-btn');
        
        if (show) {
            
            if (checkbox) checkbox.checked = false;
            
            if (placeOrderBtn) {
                placeOrderBtn.disabled = true;
                placeOrderBtn.classList.add('btn-disabled-reminder');
            }
            updateAcknowledgeHint(true);
        } else {
            
            if (checkbox) checkbox.checked = false;
            if (placeOrderBtn) {
                placeOrderBtn.disabled = false;
                placeOrderBtn.classList.remove('btn-disabled-reminder');
            }
            updateAcknowledgeHint(false);
        }
    }
    
    
    function updateAcknowledgeHint(show) {
        const hint = document.getElementById('acknowledgeHint');
        if (hint) {
            hint.classList.toggle('hidden', !show);
        }
    }
    
    
    function initAcknowledgeCheckbox() {
        const checkbox = document.getElementById('acknowledgePaymentReminder');
        if (!checkbox) return;
        
        checkbox.addEventListener('change', function() {
            const placeOrderBtn = document.getElementById('place-order-btn');
            if (!placeOrderBtn) return;
            
            if (this.checked) {
                placeOrderBtn.disabled = false;
                placeOrderBtn.classList.remove('btn-disabled-reminder');
                updateAcknowledgeHint(false);
            } else {
                placeOrderBtn.disabled = true;
                placeOrderBtn.classList.add('btn-disabled-reminder');
                updateAcknowledgeHint(true);
            }
        });
    }
    
    
    function showQRPayment(method) {
        if (!qrPaymentSection) return;
        
        qrPaymentSection.classList.add('show');
        toggleOnlinePaymentReminder(true);
        
        
        if (paymentMethodDisplay) {
            paymentMethodDisplay.textContent = method === 'gcash' ? 'GCash' : 'Maya';
        }
        
        
        if (qrCodeImage) {
            const qrSrc = qrCodeImage.getAttribute('data-' + method + '-qr');
            if (qrSrc) {
                qrCodeImage.src = qrSrc;
            }
        }
        
        
        if (receiptInput) {
            receiptInput.setAttribute('required', 'required');
        }
    }
    
    
    function hideQRPayment() {
        if (!qrPaymentSection) return;
        
        qrPaymentSection.classList.remove('show');
        toggleOnlinePaymentReminder(false);
        
        
        if (receiptInput) {
            receiptInput.removeAttribute('required');
        }
        
        
        removeReceipt();
    }
    
    
    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        receiptUploadArea.classList.add('dragover');
    }
    
    
    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        receiptUploadArea.classList.remove('dragover');
    }
    
    
    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        receiptUploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    }
    
    
    function handleReceiptSelect(e) {
        const file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    }
    
    
    function handleFile(file) {
        
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file (JPG, PNG, etc.)');
            return;
        }
        
        
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }
        
        selectedReceiptFile = file;
        
        
        const reader = new FileReader();
        reader.onload = function(e) {
            if (receiptPreviewImg) {
                receiptPreviewImg.src = e.target.result;
            }
            if (receiptFilename) {
                receiptFilename.textContent = file.name;
            }
            if (receiptPreview) {
                receiptPreview.classList.add('show');
            }
            if (receiptUploadArea) {
                receiptUploadArea.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
    
    
    function removeReceipt() {
        selectedReceiptFile = null;
        
        if (receiptInput) {
            receiptInput.value = '';
        }
        if (receiptPreview) {
            receiptPreview.classList.remove('show');
        }
        if (receiptUploadArea) {
            receiptUploadArea.style.display = 'block';
        }
        if (receiptPreviewImg) {
            receiptPreviewImg.src = '';
        }
    }
    
    
    function validatePayment() {
        if (selectedPaymentMethod === 'gcash' || selectedPaymentMethod === 'maya') {
            
            const checkbox = document.getElementById('acknowledgePaymentReminder');
            if (checkbox && !checkbox.checked) {
                alert('Please read and agree to the online payment reminder before placing your order.');
                
                const reminder = document.getElementById('onlinePaymentReminder');
                if (reminder) reminder.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            if (!selectedReceiptFile) {
                alert('Please upload your payment receipt screenshot');
                return false;
            }
        }
        return true;
    }
    
    
    window.validatePayment = validatePayment;
    window.getSelectedPaymentMethod = function() {
        return selectedPaymentMethod;
    };
    window.getSelectedReceiptFile = function() {
        return selectedReceiptFile;
    };
    
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
