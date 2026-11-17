function displayImg(input, _this) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#cimg').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        $('#cimg').attr('src', window.DEFAULT_AVATAR || '');
    }
}

function calculateAge(birthDate) {
    if (!birthDate) return 0;
    
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
}

function updateAge() {
    const birthDate = $('#birthdate').val();
    if (!birthDate) {
        $('#calculated-age').text('--').css('color', '');
        $('#age-status').empty();
        $('#birthdate').removeClass('is-valid is-invalid');
        return;
    }
    
    const age = calculateAge(birthDate);
    $('#calculated-age').text(age);
    
    if (age < 18) {
        $('#calculated-age').css('color', '#dc2626');
        $('#age-status').html('<span class="badge" style="background:#dc2626; color:white; padding:3px 8px; border-radius:4px; font-size:0.75rem;"><i class="fas fa-times-circle mr-1"></i> Under 18 (Must be at least 18 years old)</span>');
        $('#birthdate').addClass('is-invalid').removeClass('is-valid');
    } else {
        $('#calculated-age').css('color', '#16a34a');
        $('#age-status').html('<span class="badge" style="background:#16a34a; color:white; padding:3px 8px; border-radius:4px; font-size:0.75rem;"><i class="fas fa-check-circle mr-1"></i> Eligible (18+)</span>');
        $('#birthdate').addClass('is-valid').removeClass('is-invalid');
    }
}

$(document).ready(function(){
    end_loader();
    
    
    updateAge();
    
    
    $('#birthdate').on('change input', updateAge);
    
    
    $('#contact').on('input', function() {
        let val = $(this).val().replace(/[^0-9]/g, '');
        if (val.length > 11) {
            val = val.substring(0, 11);
        }
        $(this).val(val);
        
        if (val.length === 0) {
            $(this).removeClass('is-valid is-invalid');
            $('#contact-hint').html('<i class="fas fa-info-circle mr-1"></i> Requires 11 digits starting with 09 (e.g. 09123456789).').css('color', '');
        } else if (!val.startsWith('09')) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            $('#contact-hint').html('<span style="color:#dc2626;"><i class="fas fa-times-circle mr-1"></i> Phone number must start with 09</span>');
        } else if (val.length < 11) {
            $(this).removeClass('is-valid is-invalid');
            $('#contact-hint').html('<span style="color:#d97706;"><i class="fas fa-exclamation-circle mr-1"></i> ' + val.length + '/11 digits entered (must be 11 digits)</span>');
        } else if (val.length === 11 && val.startsWith('09')) {
            $(this).addClass('is-valid').removeClass('is-invalid');
            $('#contact-hint').html('<span style="color:#16a34a;"><i class="fas fa-check-circle mr-1"></i> Valid 11-digit phone number</span>');
        }
    });
    
    
    $('#customFile').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName || 'Choose profile picture');
    });
    
    
    $('#register-form').submit(function(e){
        var _this = $(this);
        var el = $('<div>').addClass('alert alert-danger err_msg').hide();
        $('.err_msg').remove();
        
        
        const firstname = $('#firstname').val().trim();
        const middlename = $('#middlename').val().trim();
        const lastname = $('#lastname').val().trim();
        const contact = $('#contact').val().trim();
        const gender = $('input[name="gender"]:checked').val();
        const birthdate = $('#birthdate').val();
        
        if (!firstname) {
            e.preventDefault();
            el.text('First Name is required.');
            _this.prepend(el);
            el.show('slow');
            $('html, body').scrollTop(0);
            $('#firstname').focus();
            return false;
        }
        
        if (!middlename) {
            e.preventDefault();
            el.text('Middle Name is required.');
            _this.prepend(el);
            el.show('slow');
            $('html, body').scrollTop(0);
            $('#middlename').focus();
            return false;
        }
        
        if (!lastname) {
            e.preventDefault();
            el.text('Last Name is required.');
            _this.prepend(el);
            el.show('slow');
            $('html, body').scrollTop(0);
            $('#lastname').focus();
            return false;
        }
        
        
        const contactRegex = /^09\d{9}$/;
        if (!contactRegex.test(contact)) {
            e.preventDefault();
            el.text('Phone number must be an 11-digit number starting with 09 (e.g., 09123456789).');
            _this.prepend(el);
            el.show('slow');
            $('html, body').scrollTop(0);
            $('#contact').focus();
            return false;
        }
        
        
        if (!gender) {
            e.preventDefault();
            el.text('Please select your gender.');
            _this.prepend(el);
            el.show('slow');
            $('html, body').scrollTop(0);
            return false;
        }
        
        
        if (!birthdate) {
            e.preventDefault();
            el.text('Birth Date is required.');
            _this.prepend(el);
            el.show('slow');
            $('html, body').scrollTop(0);
            $('#birthdate').focus();
            return false;
        }
        
        const age = calculateAge(birthdate);
        if (age < 18) {
            e.preventDefault();
            el.text('You must be at least 18 years old to proceed.');
            _this.prepend(el);
            el.show('slow');
            $('html, body').scrollTop(0);
            $('#birthdate').focus();
            return false;
        }
        
        if(_this[0].checkValidity() == false){
            e.preventDefault();
            _this[0].reportValidity();
            return false;
        }
        
        
        start_loader();
        return true;
    });
    
});
