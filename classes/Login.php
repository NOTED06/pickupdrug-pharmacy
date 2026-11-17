<?php
require_once '../config.php';

if (!defined('RECAPTCHA_SECRET_KEY')) {
    define('RECAPTCHA_SECRET_KEY', env('RECAPTCHA_SECRET_KEY', '6Lc_R14sAAAAAK0GoWTQlz0Ee4FB67iQ1FPzpiRL'));
}

class Login extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
		ini_set('display_error', 1);
	}
	
	public function __destruct(){
		parent::__destruct();
	}
	
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}
	
	public function login(){
		
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';
		$recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
		
		
		if (empty($username) || empty($password)) {
			return json_encode(array('status'=>'incorrect','msg'=>'Username and Password are required'));
		}
		
		
		if (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== 'YOUR_RECAPTCHA_SECRET_KEY') {
			$recaptchaResult = $this->verifyRecaptcha($recaptchaToken);
			if (!$recaptchaResult) {
				return json_encode(array('status'=>'bot','msg'=>'Security verification failed'));
			}
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows > 0){
			$res = $result->fetch_array();
			if(isset($res['delete_flag']) && (int)$res['delete_flag'] === 1){
				return json_encode(array('status'=>'incorrect','msg'=>'Incorrect Username or Password'));
			}
			
			
			$stored_password = $res['password'];
			
			
			$password_valid = false;
			
			if(strlen($stored_password) === 32 && ctype_xdigit($stored_password)) {
				
				$password_valid = (md5($password) === $stored_password);
			} else {
				
				$password_valid = password_verify($password, $stored_password);
			}
			
			if($password_valid) {
				
				foreach($res as $k => $v){
					if(!is_numeric($k) && $k !== 'password'){
						$this->settings->set_userdata($k,$v);
					}
				}
				$this->settings->set_userdata('login_type',1);
				
				
				session_regenerate_id(true);
				
				
				if(strlen($stored_password) === 32 && ctype_xdigit($stored_password)) {
					$new_hash = password_hash($password, PASSWORD_DEFAULT);
					$update_stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
					$update_stmt->bind_param('si', $new_hash, $res['id']);
					$update_stmt->execute();
				}
				
				return json_encode(array('status'=>'success'));
			} else {
				return json_encode(array('status'=>'incorrect','msg'=>'Incorrect Username or Password'));
			}
		} else {
			return json_encode(array('status'=>'incorrect','msg'=>'Incorrect Username or Password'));
		}
	}
	
	public function logout(){
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	
	function login_customer(){
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        
        $resp = array();
        if (empty($email) || empty($password)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Email and Password are required';
            return json_encode($resp);
        }
        
        
        $stmt = $this->conn->prepare("SELECT * FROM customer_list WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $res = $result->fetch_array();
            
            
            $stored_password = $res['password'];
            
            
            $password_valid = false;
            
            if(strlen($stored_password) === 32 && ctype_xdigit($stored_password)) {
                
                $password_valid = (md5($password) === $stored_password);
            } else {
                
                $password_valid = password_verify($password, $stored_password);
            }
            
            if($password_valid) {
                
                foreach($res as $k => $v){
                    if(!is_numeric($k) && $k !== 'password'){
                        $this->settings->set_userdata($k,$v);
                    }
                }
                $this->settings->set_userdata('login_type',2);
                
                
                session_regenerate_id(true);
                
                
                if(strlen($stored_password) === 32 && ctype_xdigit($stored_password)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $this->conn->prepare("UPDATE customer_list SET password = ? WHERE id = ?");
                    $update_stmt->bind_param('si', $new_hash, $res['id']);
                    $update_stmt->execute();
                }
                
                $resp['status'] = 'success';
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Incorrect Email or Password';
            }
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Incorrect Email or Password';
        }
        
        if($this->conn->error){
            $resp['status'] = 'failed';
            $resp['msg'] = 'A database error occurred. Please try again.';
            error_log('login_customer DB error: ' . $this->conn->error);
        }
        
        return json_encode($resp);
    }
	
	public function logout_customer(){
		if($this->settings->sess_des()){
			redirect('?');
		}
	}
	
	function register_client(){
		
		$email = $_POST['email'] ?? '';
		$password = $_POST['password'] ?? '';
		$firstname = $_POST['firstname'] ?? '';
		$lastname = $_POST['lastname'] ?? '';
		$contact = $_POST['contact'] ?? '';
		$address = $_POST['address'] ?? '';
		
		
		if (empty($email) || empty($password) || empty($firstname) || empty($lastname)) {
			return json_encode(array('status'=>'failed','msg'=>'Please fill in all required fields.'));
		}
		
		
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return json_encode(array('status'=>'failed','msg'=>'Invalid email format.'));
		}
		
		
		$check_stmt = $this->conn->prepare("SELECT id FROM customer_list WHERE email = ?");
		$check_stmt->bind_param('s', $email);
		$check_stmt->execute();
		$check_result = $check_stmt->get_result();
		
		if($check_result->num_rows > 0){
			return json_encode(array('status'=>'failed','msg'=>'Email already exists.'));
		}
		
		
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		
		
		$insert_stmt = $this->conn->prepare("INSERT INTO customer_list (firstname, lastname, email, password, contact, address) VALUES (?, ?, ?, ?, ?, ?)");
		$insert_stmt->bind_param('ssssss', $firstname, $lastname, $email, $hashed_password, $contact, $address);
		
		if($insert_stmt->execute()){
			$id = $this->conn->insert_id;
			
			
			$this->settings->set_userdata('id', $id);
			$this->settings->set_userdata('firstname', $firstname);
			$this->settings->set_userdata('lastname', $lastname);
			$this->settings->set_userdata('email', $email);
			$this->settings->set_userdata('contact', $contact);
			$this->settings->set_userdata('address', $address);
			$this->settings->set_userdata('login_type', 2);
			
			
			session_regenerate_id(true);
			
			return json_encode(array('status'=>'success'));
		}else{
			return json_encode(array('status'=>'failed','msg'=>'Registration failed. Please try again.'));
		}
	}
	
	private function verifyRecaptcha($token) {
		if (empty($token)) {
			return false;
		}
		
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$data = [
			'secret' => RECAPTCHA_SECRET_KEY,
			'response' => $token,
			'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
		];
		
		$options = [
			'http' => [
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($data),
				'timeout' => 10
			]
		];
		
		$context = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);
		
		if ($result === false) {
			
			error_log("reCAPTCHA verification failed: Could not connect to Google");
			return false;
		}
		
		$response = json_decode($result, true);
		return isset($response['success']) && $response['success'] === true;
	}
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'Login.php') {
    $action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
    $auth = new Login();
    switch ($action) {
        case 'login':
            echo $auth->login();
            break;
        case 'logout':
            echo $auth->logout();
            break;
        case 'login_customer':
            echo $auth->login_customer();
            break;
        case 'logout_customer':
            echo $auth->logout_customer();
            break;
        case 'register_client':
            echo $auth->register_client();
            break;
        default:
            echo $auth->index();
            break;
    }
    exit;
}