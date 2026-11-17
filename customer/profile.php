<?php 
if($_settings->userdata('id') != '' && $_settings->userdata('id') != 2){
	$qry = $conn->query("SELECT * FROM `customer_list` where id = '{$_settings->userdata('id')}'");
	if($qry->num_rows > 0){
		foreach($qry->fetch_array() as $k => $v){
			if(!is_numeric($k)){
				$$k = $v;
			}
		}
	}
}else{
	echo "<script>alert('You dont have access for this page'); location.replace('./');</script>";
	exit;
}

if(isset($_POST['firstname'])){
	$customer_id = $_settings->userdata('id');
	
	
	$firstname = trim($conn->real_escape_string($_POST['firstname']));
	$middlename = trim($conn->real_escape_string($_POST['middlename'] ?? ''));
	$lastname = trim($conn->real_escape_string($_POST['lastname']));
	$contact = trim($_POST['contact'] ?? '');
	$gender = trim($_POST['gender'] ?? '');
	$birthdate = trim($_POST['birthdate'] ?? '');

	
	$curr_email_query = $conn->query("SELECT email FROM customer_list WHERE id = '{$customer_id}'");
	$curr_email_data = $curr_email_query ? $curr_email_query->fetch_assoc() : null;
	$email = $curr_email_data ? $curr_email_data['email'] : ($conn->real_escape_string($_POST['email'] ?? ''));

	
	if(empty($firstname)){
		$_settings->set_flashdata('error','First Name is required.');
		redirect('?p=customer/profile');
		exit;
	}
	if(empty($middlename)){
		$_settings->set_flashdata('error','Middle Name is required.');
		redirect('?p=customer/profile');
		exit;
	}
	if(empty($lastname)){
		$_settings->set_flashdata('error','Last Name is required.');
		redirect('?p=customer/profile');
		exit;
	}
	if(empty($gender) || !in_array($gender, ['Male', 'Female'])){
		$_settings->set_flashdata('error','Please select your Gender.');
		redirect('?p=customer/profile');
		exit;
	}

	
	if(empty($contact)){
		$_settings->set_flashdata('error','Phone number is required.');
		redirect('?p=customer/profile');
		exit;
	}
	if(!preg_match('/^09\d{9}$/', $contact)){
		$_settings->set_flashdata('error','Phone number must be an 11-digit number starting with 09 (e.g., 09123456789).');
		redirect('?p=customer/profile');
		exit;
	}

	
	if(empty($birthdate)){
		$_settings->set_flashdata('error','Birth Date is required.');
		redirect('?p=customer/profile');
		exit;
	}
	$birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
	if(!$birthdate_obj || $birthdate_obj->format('Y-m-d') !== $birthdate){
		$_settings->set_flashdata('error','Invalid birth date format.');
		redirect('?p=customer/profile');
		exit;
	}
	$today = new DateTime();
	if($birthdate_obj > $today){
		$_settings->set_flashdata('error','Birth date cannot be in the future.');
		redirect('?p=customer/profile');
		exit;
	}
	$calc_age = $today->diff($birthdate_obj)->y;
	if($calc_age < 18){
		$_settings->set_flashdata('error','You must be at least 18 years old to proceed.');
		redirect('?p=customer/profile');
		exit;
	}
	if($calc_age > 120){
		$_settings->set_flashdata('error','Invalid birth date. Age cannot exceed 120 years.');
		redirect('?p=customer/profile');
		exit;
	}

	
	$avatar_path = isset($avatar) ? $avatar : '';
	if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
		$fname = 'uploads/avatars/'.$customer_id.'.png';
		$dir_path = base_app.$fname;
		$upload = $_FILES['img']['tmp_name'];
		$type = mime_content_type($upload);
		$allowed = array('image/png','image/jpeg');
		if(!in_array($type,$allowed)){
			$_settings->set_flashdata('error','Image format not supported.');
			redirect('?p=customer/profile');
			exit;
		}
		
		if(!is_dir(base_app.'uploads/avatars')){
			mkdir(base_app.'uploads/avatars', 0755, true);
		}
		$new_height = 200;
		$new_width = 200;
		list($width, $height) = getimagesize($upload);
		$t_image = imagecreatetruecolor($new_width, $new_height);
		imagealphablending($t_image, false);
		imagesavealpha($t_image, true);
		$gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
		imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		if($gdImg){
			if(is_file($dir_path))
				unlink($dir_path);
			$uploaded_img = imagepng($t_image,$dir_path);
			imagedestroy($gdImg);
			imagedestroy($t_image);
			$avatar_path = $fname;
		}else{
			$_settings->set_flashdata('error','Image upload failed.');
			redirect('?p=customer/profile');
			exit;
		}
	}
	
	
	$sql = "UPDATE customer_list SET 
			firstname = ?,
			middlename = ?,
			lastname = ?,
			email = ?,
			contact = ?,
			gender = ?,
			birthdate = ?,
			avatar = ?
			WHERE id = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('ssssssssi', $firstname, $middlename, $lastname, $email, $contact, $gender, $birthdate, $avatar_path, $customer_id);
	
	if($stmt && $stmt->execute()){
		
		$_SESSION['userdata']['firstname'] = $firstname;
		$_SESSION['userdata']['middlename'] = $middlename;
		$_SESSION['userdata']['lastname'] = $lastname;
		$_SESSION['userdata']['contact'] = $contact;
		$_SESSION['userdata']['gender'] = $gender;
		$_SESSION['userdata']['birthdate'] = $birthdate;
		if(!empty($avatar_path)) {
			$_SESSION['userdata']['avatar'] = $avatar_path;
		}
		
		$_settings->set_flashdata('success','Profile updated successfully! All details are verified.');
	}else{
		$_settings->set_flashdata('error','Failed to update profile: ' . $conn->error);
	}
	
	redirect('?p=customer/profile');
	exit;
}
?>
<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/profile.css?v=1.0">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<?php

$missing_profile_fields = [];
if(empty(trim($firstname ?? ''))) $missing_profile_fields[] = 'First Name';
if(empty(trim($middlename ?? ''))) $missing_profile_fields[] = 'Middle Name';
if(empty(trim($lastname ?? ''))) $missing_profile_fields[] = 'Last Name';
if(empty(trim($email ?? '')) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $missing_profile_fields[] = 'Email Address';

$user_contact = trim($contact ?? '');
if(empty($user_contact) || !preg_match('/^09\d{9}$/', $user_contact)) {
    $missing_profile_fields[] = '11-digit Phone Number starting with 09';
}

if(empty($gender) || !in_array($gender, ['Male', 'Female'])) {
    $missing_profile_fields[] = 'Gender';
}

$user_birthdate = trim($birthdate ?? '');
$user_age = 0;
if(!empty($user_birthdate)){
    $b_obj = DateTime::createFromFormat('Y-m-d', $user_birthdate);
    if($b_obj){
        $user_age = (new DateTime())->diff($b_obj)->y;
    }
}
if(empty($user_birthdate) || $user_age < 18){
    $missing_profile_fields[] = 'Birth Date (must be at least 18 years old)';
}

$profile_incomplete = !empty($missing_profile_fields);
?>

<section class="py-3" style="padding-top: 100px !important;">
    <div class="container">
        
        <?php if($profile_incomplete): ?>
        
        <div class="alert alert-warning alert-dismissible fade show mb-4" style="border-left: 5px solid #f59e0b; background: #fffbeb; border-radius: 12px; padding: 18px 24px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);">
            <h5 class="alert-heading mb-2" style="color: #92400e; font-weight: 700;">
                <i class="fas fa-exclamation-triangle text-warning mr-2"></i> 
                Action Required: Complete Your Profile Before Proceeding to Checkout
            </h5>
            <p class="mb-2" style="color: #78350f; font-size: 0.95rem;">
                All personal details must be complete and valid before you can proceed to checkout or place an order:
            </p>
            <ul class="mb-2" style="color: #991b1b; font-size: 0.9rem; font-weight: 600;">
                <?php foreach($missing_profile_fields as $item): ?>
                    <li><i class="fas fa-times-circle text-danger mr-1"></i> <?php echo htmlspecialchars($item); ?></li>
                <?php endforeach; ?>
            </ul>
            <small style="color: #6b7280;"><i class="fas fa-shield-alt mr-1"></i> This information is required for legal compliance, order notifications, and verification at pickup.</small>
        </div>
        <?php else: ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" style="border-left: 5px solid #10b981; background: #f0fdf4; border-radius: 12px; padding: 14px 20px;">
            <i class="fas fa-check-circle text-success mr-2"></i>
            <strong>Profile Complete &amp; Verified!</strong> You are eligible to proceed to checkout.
        </div>
        <?php endif; ?>
        
        
        <div class="page-header-banner">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage and protect your account information</p>
            <div class="header-stats">
                <span class="stat-badge"><i class="fas fa-shield-alt mr-1"></i> Account Secure</span>
                <?php if(!$profile_incomplete): ?>
                <span class="stat-badge" style="background: rgba(16, 185, 129, 0.4);"><i class="fas fa-check mr-1"></i> Checkout Ready</span>
                <?php else: ?>
                <span class="stat-badge" style="background: rgba(239, 68, 68, 0.4);"><i class="fas fa-exclamation-triangle mr-1"></i> Details Required</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-card">
            <div class="profile-header">
                <h3><i class="fas fa-edit text-primary mr-2"></i> Edit Information</h3>
                <p>Update your personal details below (all details are required before checkout)</p>
            </div>
            <div class="profile-body">
                <form id="register-form" action="" method="post">
                    <input type="hidden" name="id" value="<?= isset($id) ? $id : '' ?>">
                    <div class="row">
                        
                        <div class="col-md-8">
                            
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label form-label">First Name <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="firstname" id="firstname" value="<?= htmlspecialchars($firstname ?? '') ?>" required placeholder="Enter your first name">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label form-label">Middle Name <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="middlename" id="middlename" value="<?= htmlspecialchars($middlename ?? '') ?>" required placeholder="Enter your middle name">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label form-label">Last Name <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="lastname" id="lastname" value="<?= htmlspecialchars($lastname ?? '') ?>" required placeholder="Enter your last name">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label form-label">Email</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($email ?? '') ?>" readonly style="background-color: #f3f4f6 !important; cursor: not-allowed; color: #4b5563;" title="Email address cannot be changed">
                                    </div>
                                    <small class="text-muted d-block mt-1"><i class="fas fa-lock text-secondary mr-1"></i> Email address cannot be edited.</small>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label form-label">Phone Number <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="tel" class="form-control <?= (empty($contact) || !preg_match('/^09\d{9}$/', $contact)) ? 'border-warning' : '' ?>" name="contact" id="contact" value="<?= htmlspecialchars($contact ?? '') ?>" required maxlength="11" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX (11 digits)" inputmode="numeric">
                                    <small class="text-muted d-block mt-1" id="contact-hint"><i class="fas fa-info-circle mr-1"></i> Requires 11 digits starting with 09 (e.g. 09123456789).</small>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label form-label">Gender <span class="text-danger">*</span></label>
                                <div class="col-sm-9 d-flex align-items-center">
                                    <div class="custom-control custom-radio mr-4">
                                        <input class="custom-control-input" type="radio" id="genderMale" name="gender" value="Male" <?= isset($gender) && $gender == 'Male' ? "checked" : '' ?> required>
                                        <label class="custom-control-label" for="genderMale" style="cursor: pointer; font-weight: 500;">Male</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="genderFemale" name="gender" value="Female" <?= isset($gender) && $gender == 'Female' ? "checked" : '' ?> required>
                                        <label class="custom-control-label" for="genderFemale" style="cursor: pointer; font-weight: 500;">Female</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label form-label">Birth Date <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" name="birthdate" id="birthdate" value="<?= htmlspecialchars($birthdate ?? '') ?>" required max="<?= date('Y-m-d', strtotime('-18 years')) ?>" min="<?= date('Y-m-d', strtotime('-120 years')) ?>">
                                    <small class="text-muted mt-1 d-block">
                                        Age: <span id="calculated-age" style="font-weight: 700;">--</span> years old 
                                        <span id="age-status" style="margin-left: 8px;"></span>
                                    </small>
                                    <small class="text-muted d-block"><i class="fas fa-info-circle mr-1"></i> Must be at least 18 years old to proceed to checkout.</small>
                                </div>
                            </div>

                            <div class="form-group row mt-4">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="submit" class="btn btn-save" style="background: #0d9488; color: white; padding: 10px 30px; font-weight: 600; border-radius: 8px;">Save</button>
                                </div>
                            </div>
                        </div>

                        
                        <div class="col-md-4 right-border">
                            <div class="avatar-wrapper">
                                <img src="<?php echo validate_image(isset($avatar) ? $avatar : '') ?>" alt="Profile Picture" id="cimg">
                                <div class="custom-file text-center" style="max-width: 200px;">
                                    <input type="file" class="custom-file-input" id="customFile" name="img" onchange="displayImg(this,$(this))" accept="image/png, image/jpeg" style="display:none;">
                                    <button type="button" class="btn btn-select-img" data-action="trigger-file-input" data-target-id="customFile">Select Image</button>
                                </div>
                                <div class="text-muted mt-3 small text-center">
                                    File size: maximum 1 MB<br>
                                    File extension: .JPEG, .PNG
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    window.DEFAULT_AVATAR = "<?php echo validate_image('') ?>";
</script>
<script src="<?php echo base_url ?>customer/assets/js/profile.js?v=<?php echo time(); ?>"></script>
