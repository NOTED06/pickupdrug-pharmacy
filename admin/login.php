<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('inc/header.php') ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo $_settings->info('name') ?></title>
    
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <link rel="stylesheet" href="<?= base_url ?>plugins/fontawesome-free/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 0 20px;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .card-header {
            background: #fff;
            padding: 30px 30px 20px;
            text-align: center;
            border-bottom: none;
            display: block !important;
        }
        
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px white inset !important;
        }
        .brand-logo {
            font-size: 40px;
            color: #000;
            margin-bottom: 10px;
        }
        .card-title {
            font-size: 24px;
            font-weight: 700;
            color: #111;
            margin: 0;
            float: none !important;
            display: block !important;
            text-align: center !important;
        }
        .card-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
        .card-body {
            padding: 0 30px 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 5px;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            line-height: 1.5;
            color: #111;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            color: #111;
            background-color: #fff;
            border-color: #000;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        .btn-black {
            background-color: #000;
            color: #fff;
            border: 1px solid #000;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-black:hover {
            background-color: #333;
            border-color: #333;
        }
        .website-link {
            text-align: center;
            margin-top: 20px;
        }
        .website-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        .website-link a:hover {
            color: #000;
            text-decoration: underline;
        }
        .input-group-text {
            background: transparent;
            border-left: 0;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="card">
            <div class="card-header">
                <div class="brand-logo">
                    <i class="fas fa-cube"></i>
                </div>
                <h1 class="card-title">Admin Login</h1>
                <p class="card-subtitle">Sign in to manage your store</p>
            </div>
            <div class="card-body">
                <form id="login-frm" action="" method="post">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="username" placeholder="Enter your username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="6Lc_R14sAAAAAPaChqHoU7tFVJc3-Ygm-ItBuu1w" data-callback="onRecaptchaSuccess" data-expired-callback="onRecaptchaExpired"></div>
                        <div id="recaptcha-error" style="color: #dc3545; font-size: 13px; margin-top: 5px; display: none;">Please complete the security check</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-black" id="submitBtn" disabled>Sign In</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="website-link">
            <a href="<?php echo base_url ?>">
                <i class="fas fa-arrow-left mr-1"></i> Back to Website
            </a>
        </div>
    </div>

    
    <script src="<?= base_url ?>plugins/jquery/jquery.min.js"></script>
    
    <script src="<?= base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script src="<?= base_url ?>dist/js/adminlte.min.js"></script>

    <script>
        var recaptchaToken = '';
        
        function onRecaptchaSuccess(token) {
            recaptchaToken = token;
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('recaptcha-error').style.display = 'none';
        }
        
        function onRecaptchaExpired() {
            recaptchaToken = '';
            document.getElementById('submitBtn').disabled = true;
        }
        
        $(document).ready(function(){
            end_loader();
            $('#login-frm').submit(function(e){
                e.preventDefault()
                
                
                if(!recaptchaToken) {
                    document.getElementById('recaptcha-error').style.display = 'block';
                    return;
                }
                
                start_loader()
                if($('.err_msg').length > 0)
                    $('.err_msg').remove()
                
                var formData = $(this).serialize() + '&g-recaptcha-response=' + recaptchaToken;
                
                $.ajax({
                    url:_base_url_+'classes/Login.php?f=login',
                    method:'POST',
                    data:formData,
                    error:err=>{
                        console.log(err)
                    },
                    success:function(resp){
                        if(resp){
                            resp = JSON.parse(resp)
                            if(resp.status == 'success'){
                                location.replace(_base_url_+'admin');
                            }else if(resp.status == 'incorrect'){
                                var _frm = $('#login-frm')
                                var _msg = "<div class='alert alert-danger text-white err_msg'><i class='fa fa-exclamation-triangle'></i> Incorrect username or password</div>"
                                _frm.prepend(_msg)
                                _frm.find('input').addClass('is-invalid')
                                $('[name="username"]').focus()
                                
                                if(typeof grecaptcha !== 'undefined') grecaptcha.reset();
                                recaptchaToken = '';
                                document.getElementById('submitBtn').disabled = true;
                            }else if(resp.status == 'bot'){
                                var _frm = $('#login-frm')
                                var _msg = "<div class='alert alert-danger text-white err_msg'><i class='fa fa-robot'></i> Security verification failed. Please try again.</div>"
                                _frm.prepend(_msg)
                                if(typeof grecaptcha !== 'undefined') grecaptcha.reset();
                                recaptchaToken = '';
                                document.getElementById('submitBtn').disabled = true;
                            }
                            end_loader()
                        }
                    }
                })
            })
        })
    </script>
</body>
</html>