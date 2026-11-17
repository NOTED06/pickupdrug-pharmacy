<?php 
require_once('../../config.php');
if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $_settings->conn->prepare("SELECT * FROM customer_list where id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result();
    if($user->num_rows > 0){
        foreach($user->fetch_array() as $k =>$v){
            if(!is_numeric($k))
                $$k = $v;
        }
    }
    $stmt->close();
}
?>
<div class="container-fluid">
	<div id="msg"></div>
	<form action="" id="update-form">	
		<?php
		require_once('../../app/helpers/SecurityHelper.php');
		echo SecurityHelper::csrfField();
		?>
		<input type="hidden" name="id" value="<?= isset($id) ? $id : '' ?>">
		<div class="row">
			<div class="col-md-6">
				<div class="form-group mb-2">
					<label for="firstname" class="control-label text-sm">First Name</label>
					<input type="text" class="form-control form-control-sm rounded-0" required name="firstname" id="firstname" value="<?= isset($firstname) ? $firstname : '' ?>">
				</div>
				<div class="form-group mb-2">
					<label for="middlename" class="control-label text-sm">Middle Name</label>
					<input type="text" class="form-control form-control-sm rounded-0" name="middlename" id="middlename" value="<?= isset($middlename) ? $middlename : '' ?>">
				</div>
				<div class="form-group mb-2">
					<label for="lastname" class="control-label text-sm">Last Name</label>
					<input type="text" class="form-control form-control-sm rounded-0" required name="lastname" id="lastname" value="<?= isset($lastname) ? $lastname : '' ?>">
				</div>
				<div class="row">
					<div class="col-8">
						<div class="form-group mb-2">
							<label for="gender" class="control-label text-sm">Gender</label>
							<select class="custom-select custom-select-sm rounded-0" required name="gender" id="gender">
								<option value="">Select Gender</option>
								<option <?= isset($gender) && $gender == 'Male' ? "selected" : '' ?> value="Male">Male</option>
								<option <?= isset($gender) && $gender == 'Female' ? "selected" : '' ?> value="Female">Female</option>
							</select>
						</div>
					</div>
					<div class="col-4">
						<div class="form-group mb-2">
							<label for="age" class="control-label text-sm">Age</label>
							<input type="number" class="form-control form-control-sm rounded-0 text-center" readonly name="age" id="age" value="<?= isset($age) ? $age : '' ?>" placeholder="Auto">
						</div>
					</div>
				</div>
				<div class="form-group mb-2">
					<label for="birthdate" class="control-label text-sm">Birthdate</label>
					<input type="date" class="form-control form-control-sm rounded-0" required name="birthdate" id="birthdate" value="<?= isset($birthdate) ? $birthdate : '' ?>" max="<?= date('Y-m-d') ?>">
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group mb-2">
					<label for="email" class="control-label text-sm">Email</label>
					<input type="email" class="form-control form-control-sm rounded-0" required name="email" id="email" value="<?= isset($email) ? $email : '' ?>">
				</div>
				<div class="form-group mb-2">
					<label for="contact" class="control-label text-sm">Contact</label>
					<input type="text" class="form-control form-control-sm rounded-0" required name="contact" id="contact" value="<?= isset($contact) ? $contact : '' ?>">
				</div>
				<div class="form-group mb-2">
					<label for="address" class="control-label text-sm">Address</label>
					<textarea rows="2" class="form-control form-control-sm rounded-0" name="address" id="address"><?= isset($address) ? $address : '' ?></textarea>
				</div>
				<div class="row">
					<div class="col-6">
						<div class="form-group mb-2">
							<label for="city" class="control-label text-sm">City</label>
							<input type="text" class="form-control form-control-sm rounded-0" name="city" id="city" value="<?= isset($city) ? $city : '' ?>">
						</div>
					</div>
					<div class="col-6">
						<div class="form-group mb-2">
							<label for="zip_code" class="control-label text-sm">Zip Code</label>
							<input type="text" class="form-control form-control-sm rounded-0" name="zip_code" id="zip_code" value="<?= isset($zip_code) ? $zip_code : '' ?>">
						</div>
					</div>
				</div>
				<div class="form-group mb-2">
					<label for="password" class="control-label text-sm">New Password</label>
					<div class="input-group input-group-sm">
						<input type="password" class="form-control form-control-sm rounded-0" name="password" id="password" placeholder="Leave blank to keep current">
						<div class="input-group-append">
							<button tabindex="-1" class="btn btn-outline-secondary btn-sm rounded-0 pass_view" type="button"><i class="fa fa-eye-slash"></i></button>
						</div>
					</div>
				</div>
				<div class="form-group mb-2">
					<label for="cpassword" class="control-label text-sm">Confirm Password</label>
					<div class="input-group input-group-sm">
						<input type="password" class="form-control form-control-sm rounded-0" id="cpassword" placeholder="Confirm new password">
						<div class="input-group-append">
							<button tabindex="-1" class="btn btn-outline-secondary btn-sm rounded-0 pass_view" type="button"><i class="fa fa-eye-slash"></i></button>
						</div>
					</div>
				</div>
				<div class="form-group mb-2">
					<label for="" class="control-label text-sm">Avatar</label>
					<div class="custom-file custom-file-sm">
						<input type="file" class="custom-file-input rounded-0" id="customFile" name="img" onchange="displayImg(this,$(this))" accept="image/png, image/jpeg">
						<label class="custom-file-label rounded-0" for="customFile">Choose file</label>
					</div>
				</div>
				<div class="form-group d-flex justify-content-center">
					<img src="<?php echo validate_image(isset($avatar) ? $avatar : '') ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
				</div>
			</div>
		</div>
	</form>
</div>
<style>
	img#cimg{
		height: 10vh;
		width: 10vh;
		object-fit: cover;
		border-radius: 100% 100%;
	}
	
	
	#age {
		background-color: #f8f9fa !important;
		cursor: not-allowed;
		color: #6c757d;
		font-weight: 500;
	}
	
	#age::-webkit-outer-spin-button,
	#age::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
	}
	
	#age[type=number] {
		-moz-appearance: textfield;
	}
	
	
	.form-control.is-invalid {
		border-color: #dc3545;
		box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
	}
	
	
	#birthdate {
		position: relative;
	}
</style>
<script>
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }else{
			$('#cimg').attr('src', "<?php echo validate_image(isset($avatar) ? $avatar :'') ?>");
		}
	}
	
	
	function calculateAge(birthDate) {
		const today = new Date();
		const birth = new Date(birthDate);
		let age = today.getFullYear() - birth.getFullYear();
		const monthDiff = today.getMonth() - birth.getMonth();
		
		if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
			age--;
		}
		
		return age;
	}
	
	$(document).ready(function(){
		
		$('#birthdate').on('change', function() {
			const birthDate = $(this).val();
			if (birthDate) {
				const age = calculateAge(birthDate);
				$('#age').val(age);
				
				
				if (age < 13) {
					$(this).addClass('is-invalid');
					$('#age').addClass('is-invalid');
				} else {
					$(this).removeClass('is-invalid');
					$('#age').removeClass('is-invalid');
				}
			} else {
				$('#age').val('');
				$(this).removeClass('is-invalid');
				$('#age').removeClass('is-invalid');
			}
		});
		
		
		const existingBirthdate = $('#birthdate').val();
		if (existingBirthdate) {
			const age = calculateAge(existingBirthdate);
			$('#age').val(age);
		}
		
		
		$('.pass_view').click(function(){
			var input = $(this).closest('.input-group').find('input')
			var type = input.attr('type')
			if(type == 'password'){
				$(this).html('<i class="fa fa-eye"></i>')
				input.attr('type','text')
			}else{
				$(this).html('<i class="fa fa-eye-slash"></i>')
				input.attr('type','password')
			}
		})
		
		
		$('#customFile').on('change', function() {
			var fileName = $(this).val().split('\\').pop();
			if (fileName) {
				$(this).next('.custom-file-label').html(fileName);
			} else {
				$(this).next('.custom-file-label').html('Choose file');
			}
		});
		
		
		$('#age').on('keydown', function(e) {
			e.preventDefault();
			return false;
		});
	});
	
	$('#update-form').submit(function(e){
		e.preventDefault();
		
		var _this = $(this);
		var el = $('<div class="alert alert-danger"></div>');
		el.hide();
		$('#msg .alert').remove();
		
		
		const birthDate = $('#birthdate').val();
		if (!birthDate) {
			el.text('Birthdate is required.');
			$('#msg').prepend(el);
			el.show('slow');
			return false;
		}
		
		
		const age = calculateAge(birthDate);
		if(age < 13) {
			el.text('Customer must be at least 13 years old.');
			$('#msg').prepend(el);
			el.show('slow');
			return false;
		}
		
		if(age > 120) {
			el.text('Please verify the birthdate. Calculated age seems unrealistic.');
			$('#msg').prepend(el);
			el.show('slow');
			return false;
		}
		
		
		$('#age').val(age);
		
		
		const password = $('#password').val();
		const cpassword = $('#cpassword').val();
		
		if(password !== '' || cpassword !== '') {
			if(password !== cpassword) {
				el.text('Password confirmation does not match.');
				$('#msg').prepend(el);
				el.show('slow');
				return false;
			}
			
			
			if(password.length > 0 && password.length < 8) {
				el.text('Password must be at least 8 characters long.');
				$('#msg').prepend(el);
				el.show('slow');
				return false;
			}
		}
		
		start_loader()
		$.ajax({
            url:_base_url_+"classes/Users.php?f=registration",
            method:'POST',
            type:'POST',
            data:new FormData($(this)[0]),
            dataType:'json',
            cache:false,
            processData:false,
            contentType: false,
            error:err=>{
                console.log(err)
                alert('An error occurred')
                end_loader()
            },
            success:function(resp){
                if(resp.status == 'success'){
                    var successEl = $('<div class="alert alert-success"></div>');
                    successEl.text(resp.msg);
                    $('#msg').prepend(successEl);
                    successEl.show('slow');
                    
                    setTimeout(function(){
                        location.reload();
                    }, 2000);
                }else if(!!resp.msg){
                    el.text(resp.msg);
                    $('#msg').prepend(el);
                    el.show('slow');
                }else{
                    alert('An error occurred')
                    console.log(resp)
                }
                end_loader()
            }
        })
	})
</script>