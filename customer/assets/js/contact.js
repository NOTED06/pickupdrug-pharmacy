$(document).ready(function() {
    $('#contactForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize() + '&submit_contact=1';
        
        $.ajax({
            url: '',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('.btn-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
            },
            success: function() {
                if(typeof showToast === 'function') {
                    showToast('Thank you for contacting us! We will get back to you soon.', 'success', 'Message Sent');
                } else {
                    alert('Thank you for contacting us! We will get back to you soon.');
                }
                $('#contactForm')[0].reset();
                $('.btn-submit').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Message');
            },
            error: function() {
                if(typeof showToast === 'function') {
                    showToast('There was an error sending your message. Please try again.', 'error', 'Error');
                } else {
                    alert('There was an error sending your message. Please try again.');
                }
                $('.btn-submit').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Message');
            }
        });
    });
    
    
    $('#phone').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 0) {
            if (value.length <= 3) {
                $(this).val('(' + value);
            } else if (value.length <= 6) {
                $(this).val('(' + value.slice(0, 3) + ') ' + value.slice(3));
            } else {
                $(this).val('(' + value.slice(0, 3) + ') ' + value.slice(3, 6) + '-' + value.slice(6, 10));
            }
        }
    });
});
