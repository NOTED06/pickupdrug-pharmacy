<?php 
require_once('./config.php');

if($_POST){
    
    if(!validate_csrf()) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Invalid security token. Please refresh and try again.';
        echo json_encode($resp);
        exit;
    }
    
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $middlename = $conn->real_escape_string($_POST['middlename']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $birthdate = $_POST['birthdate']; 
    $email = $conn->real_escape_string($_POST['email']);
    $contact = trim($_POST['contact']);
    
    
    if(empty($contact) || !preg_match('/^09\d{9}$/', $contact)) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Contact number must be an 11-digit number starting with 09 (e.g., 09123456789).';
    } else {
        $contact = $conn->real_escape_string($contact);
    }
    
    
    if(empty($birthdate)) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Birthdate is required.';
    } else {
        $birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
        if(!$birthdate_obj) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid birthdate format.';
        } else {
            
            $today = new DateTime();
            $age = $today->diff($birthdate_obj)->y;
            
            if($age < 18) {
                $resp['status'] = 'failed';
                $resp['msg'] = 'You must be at least 18 years old to register.';
            } elseif($age > 120) {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Invalid birthdate. Age cannot exceed 120 years.';
            } else {
                
                $birthdate = $conn->real_escape_string($birthdate);
            }
        }
    }
    
    
    $password = $_POST['password'];
    if(!isset($resp['status']) || $resp['status'] != 'failed') {
        if(strlen($password) < 8) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Password must be at least 8 characters long.';
        } elseif(!preg_match('/[A-Za-z]/', $password)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Password must contain at least one letter.';
        } elseif(!preg_match('/[0-9]/', $password)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Password must contain at least one number.';
        } else {
             
             $password = password_hash($password, PASSWORD_DEFAULT);
        }
    }
    
    
    if(!isset($resp['status']) || $resp['status'] != 'failed') {
        
        $check_email = $conn->query("SELECT email FROM customer_list WHERE email = '$email'");
        if($check_email->num_rows > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = 'Email already exists in the database.';
        } else {
            
            $fname = '';
            if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
                $fname = 'uploads/customers/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
                $move = move_uploaded_file($_FILES['img']['tmp_name'],'./'. $fname);
                if(!$move){
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'Failed to upload image.';
                }
            }
            
            if(!isset($resp['status']) || $resp['status'] != 'failed'){
                $sql = "INSERT INTO customer_list (`firstname`,`middlename`,`lastname`,`gender`,`birthdate`,`contact`,`email`,`password`,`avatar`) VALUES ('$firstname','$middlename','$lastname','$gender','$birthdate','$contact','$email','$password','$fname')";
                $save = $conn->query($sql);
                if($save){
                    $resp['status'] = 'success';
                    $resp['msg'] = 'Account has been created successfully.';
                } else {
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'An error occurred while saving your data. Error: '.$conn->error;
                }
            }
        }
    }
    
    if($_POST['id'] != ''){
        $resp['action'] = 'update';
    }
    
    
    if(isset($_POST['ajax'])){
        echo json_encode($resp);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Pickup Drug System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #B3182D;
            --primary-hover: #9a1426;
            --primary-light: #f8e7ea;
            --secondary-color: #2D4A87;
            --accent-color: #E85A4F;
            --text-dark: #1f2937;
            --text-gray: #6b7280;
            --border-color: #e5e7eb;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --shadow-light: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-medium: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-large: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, 
                #B3182D 0%, 
                #D4336A 25%, 
                #2D4A87 50%, 
                #4A6FA5 75%, 
                #6B8EC7 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 80px 80px;
            opacity: 0.4;
            animation: float 25s infinite linear;
            z-index: 1;
        }

        .bg-element {
            position: absolute;
            border-radius: 50%;
            z-index: 2;
        }

        .bg-element-1 {
            top: 15%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(212, 51, 106, 0.15) 0%, transparent 60%);
            animation: float-reverse 20s infinite ease-in-out;
        }

        .bg-element-2 {
            bottom: 20%;
            right: -5%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(74, 111, 165, 0.12) 0%, transparent 70%);
            animation: pulse-slow 8s infinite ease-in-out alternate;
        }

        .bg-element-3 {
            top: 10%;
            right: 15%;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 60%);
            animation: float-up 18s infinite ease-in-out;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-60px, -60px) rotate(360deg); }
        }

        @keyframes float-reverse {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -20px) scale(1.1); }
            100% { transform: translate(0, 0) scale(1); }
        }

        @keyframes pulse-slow {
            0% { transform: scale(1); opacity: 0.3; }
            100% { transform: scale(1.3); opacity: 0.1; }
        }

        @keyframes float-up {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
            100% { transform: translateY(0px); }
        }

        
        .main-container {
            width: 100%;
            max-width: 680px;
            z-index: 10;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 
                        0 0 0 1px rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 28px;
            position: relative;
        }

        
        .brand-section {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary-color), #D4336A);
            border-radius: 14px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .brand-logo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%); }
            100% { transform: translateX(100%) translateY(100%); }
        }

        .brand-logo i {
            color: white;
            font-size: 24px;
            z-index: 2;
        }

        .brand-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .brand-subtitle {
            color: var(--text-gray);
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 20px;
        }

        
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 6px;
            font-size: 13px;
        }

        .form-control, .custom-select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 400;
            background: white;
            color: var(--text-dark);
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control:focus, .custom-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(179, 24, 45, 0.1);
        }

        .input-group .form-control {
            border-radius: 8px 0 0 8px;
            border-right: none;
        }

        .input-group .btn {
            border-radius: 0 8px 8px 0;
            border: 2px solid var(--border-color);
            border-left: none;
            background: #f8f9fa;
            color: var(--text-gray);
            padding: 5px 10px;
            transition: all 0.2s ease;
        }

        .input-group .btn:hover {
            background: rgba(179, 24, 45, 0.05);
            color: var(--primary-color);
        }

        .input-group .form-control:focus + .btn {
            border-color: var(--primary-color);
        }

        
        .birthdate-input {
            font-family: 'Inter', sans-serif;
        }

        .birthdate-input::-webkit-calendar-picker-indicator {
            opacity: 0.7;
            cursor: pointer;
        }

        .birthdate-input::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        
        .password-requirements {
            margin-top: 8px;
            font-size: 11px;
            color: var(--text-gray);
        }

        .requirement {
            display: flex;
            align-items: center;
            margin: 2px 0;
            transition: all 0.2s ease;
        }

        .requirement i {
            width: 12px;
            margin-right: 6px;
            font-size: 10px;
        }

        .requirement.valid {
            color: #10b981;
        }

        .requirement.invalid {
            color: #ef4444;
        }

        
        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            height: 3px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak .strength-fill {
            width: 33%;
            background: #ef4444;
        }

        .strength-medium .strength-fill {
            width: 66%;
            background: #f59e0b;
        }

        .strength-strong .strength-fill {
            width: 100%;
            background: #10b981;
        }

        .strength-text {
            font-size: 11px;
            font-weight: 500;
        }

        .strength-weak .strength-text {
            color: #ef4444;
        }

        .strength-medium .strength-text {
            color: #f59e0b;
        }

        .strength-strong .strength-text {
            color: #10b981;
        }

        
        .profile-upload-section {
            text-align: center;
            margin-bottom: 16px;
        }

        .profile-upload-container {
            position: relative;
            display: inline-block;
            margin-bottom: 12px;
        }

        .profile-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            object-fit: cover;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-medium);
        }

        .profile-preview:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-large);
        }

        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }

        .profile-upload-container:hover .upload-overlay {
            opacity: 1;
        }

        .upload-overlay i {
            color: white;
            font-size: 18px;
        }

        .profile-upload-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .upload-text {
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 8px;
        }

        .upload-hint {
            font-size: 11px;
            color: var(--text-gray);
            margin-top: 4px;
            opacity: 0.8;
        }

        
        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .login-link {
            color: var(--text-gray);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .login-link:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        .submit-btn {
            padding: 10px 28px;
            background: linear-gradient(135deg, var(--primary-color), #D4336A);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
            min-width: 130px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(179, 24, 45, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        
        .alert {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 500;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        
        @media (max-width: 768px) {
            .main-container {
                max-width: 95%;
            }
            
            .register-container {
                padding: 24px 20px;
                border-radius: 16px;
            }

            .brand-title {
                font-size: 22px;
            }

            .brand-subtitle {
                font-size: 13px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 15px;
                align-items: flex-start;
                padding-top: 30px;
            }

            .main-container {
                max-width: 100%;
            }

            .register-container {
                padding: 20px 16px;
                border-radius: 14px;
            }

            .form-actions {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }

            .submit-btn {
                width: 100%;
                min-width: unset;
            }

            .profile-preview {
                width: 70px;
                height: 70px;
            }
        }

        
        @media (max-height: 700px) {
            body {
                padding: 15px;
                align-items: flex-start;
                padding-top: 20px;
            }
            
            .brand-section {
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 14px;
            }

            .profile-upload-section {
                margin-bottom: 14px;
            }

            .form-actions {
                margin-top: 20px;
                padding-top: 16px;
            }
        }

        
        @media (max-height: 600px) {
            .brand-section {
                margin-bottom: 16px;
            }

            .brand-logo {
                width: 48px;
                height: 48px;
                margin-bottom: 12px;
            }

            .brand-logo i {
                font-size: 20px;
            }

            .brand-title {
                font-size: 20px;
                margin-bottom: 4px;
            }

            .brand-subtitle {
                font-size: 12px;
                margin-bottom: 16px;
            }

            .form-group {
                margin-bottom: 12px;
            }
        }

        
        .form-control:focus, .custom-select:focus {
            transform: translateY(-1px);
            box-shadow: 0 0 0 3px rgba(179, 24, 45, 0.1), 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        
        .form-control, .custom-select, .submit-btn, .login-link {
            transition: all 0.2s ease;
        }

        
        .form-control.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .form-control.is-valid {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        
        .birthdate-error {
            color: #ef4444;
            font-size: 11px;
            margin-top: 4px;
            display: flex;
            align-items: center;
        }

        .birthdate-error i {
            margin-right: 4px;
            font-size: 10px;
        }

        
        .row.g-2 {
            margin-left: -4px;
            margin-right: -4px;
        }

        .row.g-2 > * {
            padding-left: 4px;
            padding-right: 4px;
        }

        
        @media (max-width: 576px) {
            .row.g-2 .col-6 {
                flex: 0 0 auto;
                width: 50%;
            }
        }

        
        .form-group:has(.is-invalid) {
            position: relative;
        }

        .form-group:has(.is-invalid)::after {
            content: '';
            position: absolute;
            inset: -2px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            pointer-events: none;
        }
    </style>
</head>

<body>
    
    <div class="bg-element bg-element-1"></div>
    <div class="bg-element bg-element-2"></div>
    <div class="bg-element bg-element-3"></div>
    
    <div class="main-container">
        <div class="register-container">
            
            <div class="brand-section">
                <div class="brand-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="brand-title">Create Account</h1>
                <p class="brand-subtitle">Join our community today</p>
            </div>

            <?php if(isset($resp) && !isset($_POST['ajax'])): ?>
                <div class="alert <?= $resp['status'] == 'success' ? 'alert-success' : 'alert-danger' ?>">
                    <?= $resp['msg'] ?>
                </div>
                <?php if($resp['status'] == 'success'): ?>
                    <script>
                        setTimeout(function(){
                            location.href = './';
                        }, 2000);
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <form id="register-form" action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="ajax" value="1">
                
                <div class="row g-3">
                    <div class="col-lg-6 col-md-6">
                        <div class="form-group">
                            <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" required name="firstname" id="firstname" placeholder="Enter your first name">
                        </div>
                        <div class="form-group">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middlename" id="middlename" placeholder="Enter your middle name">
                        </div>
                        <div class="form-group">
                            <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" required name="lastname" id="lastname" placeholder="Enter your last name">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="custom-select" required name="gender" id="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="birthdate" class="form-label">Birthdate <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control birthdate-input" required name="birthdate" id="birthdate">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" required name="email" id="email" placeholder="Enter your email address">
                        </div>
                        <div class="form-group">
                            <label for="contact" class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" required name="contact" id="contact" maxlength="11" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX (11 digits)" inputmode="numeric">
                            <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> 11-digit number starting with 09</small>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" required name="password" id="password" placeholder="Create a password" minlength="8">
                                <button tabindex="-1" class="btn btn-outline-secondary pass_view" type="button">
                                    <i class="fa fa-eye-slash"></i>
                                </button>
                            </div>
                            <div class="password-requirements">
                                <div class="requirement" id="req-length">
                                    <i class="fas fa-times"></i>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="requirement" id="req-letter">
                                    <i class="fas fa-times"></i>
                                    <span>Contains a letter</span>
                                </div>
                                <div class="requirement" id="req-number">
                                    <i class="fas fa-times"></i>
                                    <span>Contains a number</span>
                                </div>
                            </div>
                            <div class="password-strength" id="password-strength" style="display: none;">
                                <div class="strength-bar">
                                    <div class="strength-fill"></div>
                                </div>
                                <div class="strength-text">Password Strength</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cpassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" required id="cpassword" placeholder="Confirm your password" minlength="8">
                                <button tabindex="-1" class="btn btn-outline-secondary pass_view" type="button">
                                    <i class="fa fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="profile-upload-section">
                    <div class="profile-upload-container" onclick="document.getElementById('customFile').click();">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='%23f1f5f9'/%3E%3Cpath d='M50 30c8.284 0 15 6.716 15 15s-6.716 15-15 15-15-6.716-15-15 6.716-15 15-15zm0 35c12.15 0 22 7.85 22 17.5V85H28v-2.5C28 72.85 37.85 65 50 65z' fill='%23cbd5e1'/%3E%3C/svg%3E" 
                             alt="Profile Picture" 
                             id="cimg" 
                             class="profile-preview">
                        <div class="upload-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                        <input type="file" 
                               class="profile-upload-input" 
                               id="customFile" 
                               name="img" 
                               accept="image/png,image/jpeg,image/jpg"
                               style="display: none;">
                    </div>
                    <div class="upload-text">Click to upload profile picture</div>
                    <div class="upload-hint">(Optional - JPG, PNG only)</div>
                </div>

                <div class="form-actions">
                    <a href="login.php" class="login-link">
                        <i class="fas fa-sign-in-alt"></i> Already have an account?
                    </a>
                    <button type="submit" class="submit-btn" id="submit-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script>
        
        function validatePassword(password) {
            const requirements = {
                length: password.length >= 8,
                letter: /[A-Za-z]/.test(password),
                number: /[0-9]/.test(password)
            };
            
            
            updateRequirement('req-length', requirements.length);
            updateRequirement('req-letter', requirements.letter);
            updateRequirement('req-number', requirements.number);
            
            
            let strength = 0;
            if (requirements.length) strength++;
            if (requirements.letter) strength++;
            if (requirements.number) strength++;
            if (password.length >= 12) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            
            updateStrengthIndicator(strength, password.length > 0);
            
            return requirements.length && requirements.letter && requirements.number;
        }
        
        function updateRequirement(id, valid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            
            if (valid) {
                element.classList.add('valid');
                element.classList.remove('invalid');
                icon.className = 'fas fa-check';
            } else {
                element.classList.add('invalid');
                element.classList.remove('valid');
                icon.className = 'fas fa-times';
            }
        }
        
        function updateStrengthIndicator(strength, show) {
            const strengthDiv = document.getElementById('password-strength');
            const strengthBar = strengthDiv.querySelector('.strength-bar');
            const strengthText = strengthDiv.querySelector('.strength-text');
            
            if (!show) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            strengthBar.className = 'strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak Password';
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Medium Password';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong Password';
            }
        }

        
        function validateBirthdate(birthdate) {
            if (!birthdate) return false;
            
            const birthdateObj = new Date(birthdate);
            const today = new Date();
            const age = Math.floor((today - birthdateObj) / (365.25 * 24 * 60 * 60 * 1000));
            
            
            if (isNaN(birthdateObj.getTime())) return false;
            if (birthdateObj > today) return false; 
            if (age < 18) return false; 
            if (age > 120) return false; 
            
            return true;
        }

        
        function displayImg(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    if(typeof showToast === 'function') {
                        showToast('Please select a valid image file (JPG, PNG only)', 'warning', 'Invalid File');
                    } else {
                        alert('Please select a valid image file (JPG, PNG only)');
                    }
                    input.value = '';
                    resetProfileImage();
                    return;
                }
                
                
                if (file.size > 5 * 1024 * 1024) {
                    if(typeof showToast === 'function') {
                        showToast('File size must be less than 5MB', 'warning', 'File Too Large');
                    } else {
                        alert('File size must be less than 5MB');
                    }
                    input.value = '';
                    resetProfileImage();
                    return;
                }
                
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#cimg').attr('src', e.target.result);
                }
                reader.readAsDataURL(file);
            } else {
                resetProfileImage();
            }
        }

        function resetProfileImage() {
            
            $('#cimg').attr('src', "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='%23f1f5f9'/%3E%3Cpath d='M50 30c8.284 0 15 6.716 15 15s-6.716 15-15 15-15-6.716-15-15 6.716-15 15-15zm0 35c12.15 0 22 7.85 22 17.5V85H28v-2.5C28 72.85 37.85 65 50 65z' fill='%23cbd5e1'/%3E%3C/svg%3E");
        }

        $(document).ready(function(){
            
            $('#password').on('input', function() {
                const password = $(this).val();
                validatePassword(password);
            });

            
            $('#birthdate').on('change input', function() {
                const birthdate = $(this).val();
                const birthdateInput = $(this);
                
                if (validateBirthdate(birthdate)) {
                    birthdateInput.removeClass('is-invalid').addClass('is-valid');
                } else {
                    birthdateInput.removeClass('is-valid').addClass('is-invalid');
                }
            });

            
            $('.pass_view').click(function(){
                var input = $(this).siblings('input')
                var type = input.attr('type')
                if(type == 'password'){
                    $(this).html('<i class="fa fa-eye"></i>')
                    input.attr('type','text').focus()
                }else{
                    $(this).html('<i class="fa fa-eye-slash"></i>')
                    input.attr('type','password').focus()
                }
            })
            
            
            $('#register-form').submit(function(e){
                e.preventDefault()
                var _this = $(this)
                var submitBtn = $('#submit-btn')
                var originalBtnText = submitBtn.html()
                var el = $('<div>')
                    el.addClass('alert alert-danger')
                    el.hide()
                $('.alert').remove()
                
                
                const birthdate = $('#birthdate').val();
                if(!validateBirthdate(birthdate)){
                    el.text('Please enter a valid birthdate. You must be at least 18 years old.')
                    _this.prepend(el)
                    el.show('slow')
                    $('html, body').animate({scrollTop: 0}, 300)
                    return false;
                }
                
                
                if($('#password').val() != $('#cpassword').val()){
                    el.text('Password does not match')
                    _this.prepend(el)
                    el.show('slow')
                    $('html, body').animate({scrollTop: 0}, 300)
                    return false;
                }
                
                
                const password = $('#password').val();
                if(password.length < 8){
                    el.text('Password must be at least 8 characters long')
                    _this.prepend(el)
                    el.show('slow')
                    $('html, body').animate({scrollTop: 0}, 300)
                    return false;
                }
                
                if(!/[A-Za-z]/.test(password)){
                    el.text('Password must contain at least one letter')
                    _this.prepend(el)
                    el.show('slow')
                    $('html, body').animate({scrollTop: 0}, 300)
                    return false;
                }
                
                if(!/[0-9]/.test(password)){
                    el.text('Password must contain at least one number')
                    _this.prepend(el)
                    el.show('slow')
                    $('html, body').animate({scrollTop: 0}, 300)
                    return false;
                }
                
                
                if(_this[0].checkValidity() == false){
                    _this[0].reportValidity();
                    return false;
                }
                
                
                submitBtn.prop('disabled', true)
                submitBtn.html('<span class="spinner"></span>Creating Account...')
                
                
                $.ajax({
                    url: _this.attr('action'),
                    data: new FormData(_this[0]),
                    cache: false,
                    contentType: false,
                    processData: false,
                    method: 'POST',
                    type: 'POST',
                    dataType: 'json',
                    error: function(err){
                        console.log(err)
                        el.removeClass('alert-success').addClass('alert-danger')
                        el.text('An error occurred while processing your request.')
                        _this.prepend(el)
                        el.show('slow')
                        $('html, body').animate({scrollTop: 0}, 300)
                        
                        
                        submitBtn.prop('disabled', false)
                        submitBtn.html(originalBtnText)
                    },
                    success: function(resp){
                        if(typeof resp === 'object' && resp.status == 'success'){
                            el.removeClass('alert-danger').addClass('alert-success')
                            el.text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $('html, body').animate({scrollTop: 0}, 300)
                            
                            
                            _this[0].reset()
                            $('#cimg').attr('src', "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='%23f1f5f9'/%3E%3Cpath d='M50 30c8.284 0 15 6.716 15 15s-6.716 15-15 15-15-6.716-15-15 6.716-15 15-15zm0 35c12.15 0 22 7.85 22 17.5V85H28v-2.5C28 72.85 37.85 65 50 65z' fill='%23cbd5e1'/%3E%3C/svg%3E")
                            
                            
                            $('.requirement').removeClass('valid invalid').addClass('invalid');
                            $('.requirement i').attr('class', 'fas fa-times');
                            $('#password-strength').hide();
                            
                            
                            $('#birthdate').removeClass('is-valid is-invalid');
                            
                            
                            setTimeout(function(){
                                location.href = './';
                            }, 2000);
                        } else {
                            el.removeClass('alert-success').addClass('alert-danger')
                            el.text(resp.msg || 'An error occurred while creating your account.')
                            _this.prepend(el)
                            el.show('slow')
                            $('html, body').animate({scrollTop: 0}, 300)
                        }
                        
                        
                        submitBtn.prop('disabled', false)
                        submitBtn.html(originalBtnText)
                    }
                })
            })

            
            $('#customFile').on('change', function() {
                displayImg(this);
            });

            
            $('.form-control, .custom-select').on('focus', function() {
                $(this).parent().addClass('focused');
            }).on('blur', function() {
                $(this).parent().removeClass('focused');
            });

            
            $('.submit-btn').on('click', function(e) {
                var btn = $(this);
                var ripple = $('<span class="ripple"></span>');
                
                btn.append(ripple);
                
                setTimeout(function() {
                    ripple.remove();
                }, 600);
            });

            
            $('#contact').on('input', function() {
                let val = $(this).val().replace(/[^0-9]/g, '');
                if (val.length > 11) val = val.substring(0, 11);
                $(this).val(val);
                if (val.length === 11 && val.startsWith('09')) {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                } else if (val.length > 0 && !val.startsWith('09')) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            
            const maxDate = new Date();
            maxDate.setFullYear(maxDate.getFullYear() - 18);
            $('#birthdate').attr('max', maxDate.toISOString().split('T')[0]);
            
            
            const minDate = new Date();
            minDate.setFullYear(minDate.getFullYear() - 120);
            $('#birthdate').attr('min', minDate.toISOString().split('T')[0]);
        })

        
        document.addEventListener("DOMContentLoaded", function() {
            removeCredit();
            setInterval(removeCredit, 500);

            function removeCredit() {
                document.querySelectorAll("div").forEach(el => {
                    if (el.innerText && el.innerText.includes("Developed by oretnom23")) {
                        el.remove();
                    }
                });
            }
        });
    </script>
</body>
</html>