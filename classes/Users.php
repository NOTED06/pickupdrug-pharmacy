<?php
require_once('../config.php');
Class Users extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function save_users(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			return "Invalid security token. Please refresh and try again.";
		}
		
		
		$id = $_POST['id'] ?? '';
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';
		$firstname = $_POST['firstname'] ?? '';
		$lastname = $_POST['lastname'] ?? '';
		$type = $_POST['type'] ?? '';
		
		
		if(empty($username) || empty($firstname) || empty($lastname)){
			return "Username, First Name, and Last Name are required.";
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `users` WHERE `username` = ?" . (!empty($id) ? " AND id != ?" : ""));
		if(!empty($id)) {
			$stmt->bind_param('si', $username, $id);
		} else {
			$stmt->bind_param('s', $username);
		}
		$stmt->execute();
		$username_check = $stmt->get_result();
		
		if($username_check->num_rows > 0){
			return 2; 
		}
		
		
		$fields = array();
		$values = array();
		$types = '';
		$params = array();
		
		
		$fields[] = 'username'; $values[] = '?'; $types .= 's'; $params[] = $username;
		$fields[] = 'firstname'; $values[] = '?'; $types .= 's'; $params[] = $firstname;
		$fields[] = 'lastname'; $values[] = '?'; $types .= 's'; $params[] = $lastname;
		if(!empty($type)) {
			$fields[] = 'type'; $values[] = '?'; $types .= 'i'; $params[] = $type;
		}
		
		
		if(!empty($password)){
			$fields[] = 'password';
			$values[] = '?';
			$types .= 's';
			$params[] = password_hash($password, PASSWORD_DEFAULT);
		}
		
		
		if(empty($id)){
			
			$sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
			$stmt = $this->conn->prepare($sql);
			$stmt->bind_param($types, ...$params);
			
			if($stmt->execute()){
				$id = $this->conn->insert_id;
				$this->settings->set_flashdata('success','User Details successfully saved.');
			} else {
				return "Database Error: " . $this->conn->error;
			}
		} else {
			
			$set_parts = array();
			foreach($fields as $field){
				$set_parts[] = "$field = ?";
			}
			$types .= 'i';
			$params[] = $id;
			
			$sql = "UPDATE users SET " . implode(', ', $set_parts) . " WHERE id = ?";
			$stmt = $this->conn->prepare($sql);
			$stmt->bind_param($types, ...$params);
			
			if($stmt->execute()){
				$this->settings->set_flashdata('success','User Details successfully updated.');
			} else {
				return "Database Error: " . $this->conn->error;
			}
		}
		
		
		foreach($_POST as $k => $v){
			if($k != 'id' && $this->settings->userdata('id') == $id){
				$this->settings->set_userdata($k,$v);
			}
		}
		
		
		if(!empty($_FILES['img']['tmp_name'])){
			$upload_result = $this->processAvatarUpload($id);
			if($upload_result !== true){
				
				$this->settings->set_flashdata('warning', 'User saved but avatar upload failed: ' . $upload_result);
			}
		}
		
		return 1;
	}
	
	private function processAvatarUpload($id){
		
		if(!is_dir(base_app."uploads/avatars")){
			if(!mkdir(base_app."uploads/avatars", 0755, true)){
				return "Failed to create uploads directory";
			}
		}
		
		
		if($_FILES['img']['error'] !== UPLOAD_ERR_OK){
			return "File upload error: " . $_FILES['img']['error'];
		}
		
		
		if($_FILES['img']['size'] > 5 * 1024 * 1024){
			return "File too large. Maximum size is 5MB";
		}
		
		
		$file_extension = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
		$allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
		
		if(!in_array($file_extension, $allowed_extensions)){
			return "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
		}
		
		
		if(!extension_loaded('gd')){
			
			$fname = "uploads/avatars/$id." . $file_extension;
			
			
			$old_files = glob(base_app."uploads/avatars/$id.*");
			foreach($old_files as $old_file){
				if(is_file($old_file)){
					unlink($old_file);
				}
			}
			
			
			if(!move_uploaded_file($_FILES['img']['tmp_name'], base_app.$fname)){
				return "Failed to save image file";
			}
			
		} else {
			
			
			
			if(function_exists('finfo_open')){
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime_type = finfo_file($finfo, $_FILES['img']['tmp_name']);
				finfo_close($finfo);
			} else {
				
				switch($file_extension){
					case 'jpg':
					case 'jpeg':
						$mime_type = 'image/jpeg';
						break;
					case 'png':
						$mime_type = 'image/png';
						break;
					case 'gif':
						$mime_type = 'image/gif';
						break;
					default:
						$mime_type = 'unknown';
				}
			}
			
			
			$accepted_types = array(
				'image/jpeg',
				'image/jpg', 
				'image/png',
				'image/gif'
			);
			
			if(!in_array($mime_type, $accepted_types)){
				return "Invalid file type. Only JPG, PNG, and GIF images are allowed. Detected: " . $mime_type;
			}
			
			
			switch($mime_type){
				case 'image/jpeg':
					$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name']);
					break;
				case 'image/png':
					$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name']);
					break;
				case 'image/gif':
					$uploadfile = imagecreatefromgif($_FILES['img']['tmp_name']);
					break;
				default:
					return "Unsupported image format";
			}
			
			if(!$uploadfile){
				return "Failed to process image. File may be corrupted";
			}
			
			
			$temp = imagescale($uploadfile, 200, 200);
			if(!$temp){
				imagedestroy($uploadfile);
				return "Failed to resize image";
			}
			
			
			$fname = "uploads/avatars/$id.png";
			
			
			$old_files = glob(base_app."uploads/avatars/$id.*");
			foreach($old_files as $old_file){
				if(is_file($old_file)){
					unlink($old_file);
				}
			}
			
			
			$upload = imagepng($temp, base_app.$fname, 9);
			
			
			imagedestroy($uploadfile);
			imagedestroy($temp);
			
			if(!$upload){
				return "Failed to save image file";
			}
		}
		
		
		$avatar_path = $fname . "?v=" . time();
		$update_query = "UPDATE `users` SET `avatar` = '" . $this->conn->real_escape_string($avatar_path) . "' WHERE id = '{$id}'";
		
		if(!$this->conn->query($update_query)){
			return "Failed to update avatar in database: " . $this->conn->error;
		}
		
		
		if($this->settings->userdata('id') == $id){
			$this->settings->set_userdata('avatar', $avatar_path);
		}
		
		return true;
	}

	
	private function log_deletion($table_name, $item_id, $item_data = null){
		try {
			$create_table = "CREATE TABLE IF NOT EXISTS `deleted_items` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`table_name` VARCHAR(100) NOT NULL,
				`item_id` INT(11) NOT NULL,
				`item_data` TEXT DEFAULT NULL,
				`deleted_by` INT(11) DEFAULT NULL,
				`deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				INDEX `idx_table_item` (`table_name`, `item_id`),
				INDEX `idx_deleted_at` (`deleted_at`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
			$this->conn->query($create_table);
			$user_data = $this->settings->userdata('id');
			$user_id = ($user_data !== null && $user_data !== false) ? (int)$user_data : 0;
			$item_data_json = '';
			if ($item_data) {
				$enc = @json_encode($item_data, JSON_UNESCAPED_UNICODE);
				$item_data_json = is_string($enc) ? $enc : '';
			}
			$stmt = $this->conn->prepare("INSERT INTO `deleted_items` (`table_name`, `item_id`, `item_data`, `deleted_by`) VALUES (?, ?, ?, ?)");
			if ($stmt) {
				$stmt->bind_param('sisi', $table_name, $item_id, $item_data_json, $user_id);
				@$stmt->execute();
				$stmt->close();
			}
		} catch (Throwable $e) {
			
		}
	}

	public function delete_users(){
		$resp = array('status' => 'failed', 'msg' => 'An error occurred.');
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf(false)) {
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		if($id <= 0){
			$resp['msg'] = 'Invalid user ID.';
			return json_encode($resp);
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `users` WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$res = $stmt->get_result();
		if($res->num_rows == 0){
			$resp['msg'] = 'User not found.';
			return json_encode($resp);
		}
		$user_row = $res->fetch_assoc();
		$this->log_deletion('users', $id, $user_row);
		
		
		$col_check = $this->conn->query("SHOW COLUMNS FROM `users` LIKE 'delete_flag'");
		if(!$col_check || $col_check->num_rows == 0){
			@$this->conn->query("ALTER TABLE `users` ADD COLUMN `delete_flag` TINYINT(1) NOT NULL DEFAULT 0");
		}
		$stmt = $this->conn->prepare("UPDATE `users` SET `delete_flag` = 1 WHERE id = ?");
		$stmt->bind_param('i', $id);
		$ok = $stmt->execute();
		
		if($ok && $this->conn->affected_rows > 0){
			$this->settings->set_flashdata('success', 'User successfully deleted.');
			$resp['status'] = 'success';
			$resp['msg'] = 'User successfully deleted.';
		} else {
			
			$stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
			$stmt->bind_param('i', $id);
			$ok = $stmt->execute();
			if($ok){
				$this->settings->set_flashdata('success', 'User successfully deleted.');
				$resp['status'] = 'success';
				$resp['msg'] = 'User successfully deleted.';
			} else {
				$resp['msg'] = $this->conn->error ?: 'Failed to delete user.';
			}
		}
		return json_encode($resp);
	}
	
	function registration(){
        $resp = array();
        
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        require_once(__DIR__ . '/../app/helpers/ValidationHelper.php');
        
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
        $middlename = isset($_POST['middlename']) ? trim($_POST['middlename']) : '';
        $lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
        $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
        $birthdate = isset($_POST['birthdate']) ? $_POST['birthdate'] : '';
        $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $city = isset($_POST['city']) ? trim($_POST['city']) : '';
        $zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '';
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        
        if(empty($firstname)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'First name is required.';
            return json_encode($resp);
        }
        
        if(empty($lastname)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Last name is required.';
            return json_encode($resp);
        }
        
        if(empty($email)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Email is required.';
            return json_encode($resp);
        }
        
        
        if(!ValidationHelper::email($email)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Please enter a valid email address.';
            return json_encode($resp);
        }
        
        
        if(!empty($contact)) {
            
            $clean_contact = preg_replace('/[\s\-\(\)]/', '', $contact);
            if(!preg_match('/^09\d{9}$/', $clean_contact)) {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Please enter a valid Philippine mobile number (e.g., 09123456789).';
                return json_encode($resp);
            }
        }
        
        
        if(empty($id) && empty($password)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Password is required.';
            return json_encode($resp);
        }
        
        
        if(!empty($password) && strlen($password) < 8) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Password must be at least 8 characters long.';
            return json_encode($resp);
        }
        
        
        if(!empty($password)) {
            $_POST['password'] = password_hash($password, PASSWORD_DEFAULT);
        } else {
            unset($_POST['password']);
        }
		
		
		$main_field = ['firstname', 'middlename', 'lastname', 'gender', 'birthdate', 'age', 'contact', 'email', 'address', 'city', 'zip_code', 'status', 'password'];
		$data = "";
		
		
		if(isset($birthdate) && !empty($birthdate)) {
			
			$birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
			if(!$birthdate_obj || $birthdate_obj->format('Y-m-d') !== $birthdate) {
				$resp['status'] = 'failed';
				$resp['msg'] = 'Invalid birthdate format.';
				return json_encode($resp);
			}
			
			
			$today = new DateTime();
			$calculated_age = $today->diff($birthdate_obj)->y;
			
			if($calculated_age < 18) {
				$resp['status'] = 'failed';
				$resp['msg'] = 'You must be at least 18 years old to proceed.';
				return json_encode($resp);
			} elseif($calculated_age > 120) {
				$resp['status'] = 'failed';
				$resp['msg'] = 'Invalid birthdate. Age cannot exceed 120 years.';
				return json_encode($resp);
			}
			
			
			$age = $calculated_age;
			$_POST['age'] = $age; 
			
			
			$birthdate = $this->conn->real_escape_string($birthdate);
		} else {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Birthdate is required.';
			return json_encode($resp);
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `customer_list` WHERE email = '{$email}' ".($id > 0 ? " and id!='{$id}'" : "")." ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Email already exists.';
			return json_encode($resp);
		}
		
		
		foreach($_POST as $k => $v){
			$v = $this->conn->real_escape_string($v);
			if(in_array($k, $main_field)){
				if(!empty($data)) $data .= ", ";
				$data .= " `{$k}` = '{$v}' ";
			}
		}
		
		
		if(empty($id)){
			$sql = "INSERT INTO `customer_list` set {$data} ";
		}else{
			$sql = "UPDATE `customer_list` set {$data} where id = '{$id}' ";
		}
		
		$save = $this->conn->query($sql);
		if($save){
			$uid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			$resp['uid'] = $uid;
			if(!empty($id))
				$resp['msg'] = 'User Details has been updated successfully';
			else
				$resp['msg'] = 'Your Account has been created successfully';

			
			if(isset($_FILES['img']) && $_FILES['img']['error'] == 0){
				
				
				$upload_dir = "uploads/customers";
				if(!is_dir(base_app . $upload_dir)){
					mkdir(base_app . $upload_dir, 0777, true);
				}
				
				
				$file_ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
				if(in_array($file_ext, ['jpg', 'jpeg', 'png'])){
					
					
					$filename = $uid . '.' . $file_ext;
					$file_path = $upload_dir . '/' . $filename;
					$full_path = base_app . $file_path;
					
					
					if(file_exists($full_path)){
						unlink($full_path);
					}
					
					
					if(move_uploaded_file($_FILES['img']['tmp_name'], $full_path)){
						
						$avatar_path = $file_path . '?v=' . time();
						$stmt_avatar = $this->conn->prepare("UPDATE `customer_list` SET `avatar` = ? WHERE id = ?"); $stmt_avatar->bind_param('si', $avatar_path, $uid); $stmt_avatar->execute();
					} else {
						$resp['msg'] .= " - Failed to upload image";
					}
					
				} else {
					$resp['msg'] .= " - Only JPG, JPEG, PNG files allowed";
				}
			}
			
			
			if(!empty($uid) && $this->settings->userdata('login_type') != 1){
				$stmt_user = $this->conn->prepare("SELECT * FROM `customer_list` WHERE id = ?"); $stmt_user->bind_param('i', $uid); $stmt_user->execute(); $user = $stmt_user->get_result();
				if($user->num_rows > 0){
					$res = $user->fetch_array();
					foreach($res as $k => $v){
						if(!is_numeric($k) && $k != 'password'){
							$this->settings->set_userdata($k, $v);
						}
					}
					$this->settings->set_userdata('login_type', '2');
				}
			}
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = $this->conn->error;
			$resp['sql'] = $sql;
		}
		
		if($resp['status'] == 'success' && isset($resp['msg']))
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}

	
	private function checkCustomerAssociations($customer_id) {
		$associations = array();
		$has_associations = false;
		
		
		
		$tables_to_check = array(
			'orders' => 'customer_id',           
			'transactions' => 'customer_id',     
			'bookings' => 'customer_id',         
			'reservations' => 'customer_id',     
			'payments' => 'customer_id',         
			'reviews' => 'customer_id',          
			'cart_items' => 'customer_id',       
			'wishlist' => 'customer_id',         
			'order_list' => 'customer_id',       
			'sales' => 'customer_id',            
			'invoices' => 'customer_id',         
			'purchase_history' => 'customer_id', 
			'support_tickets' => 'customer_id',  
			'subscriptions' => 'customer_id',    
			
		);
		
		foreach($tables_to_check as $table => $column) {
			
			$table_exists = $this->conn->query("SHOW TABLES LIKE '$table'");
			if($table_exists && $table_exists->num_rows > 0) {
				
				$check_query = $this->conn->query("SELECT COUNT(*) as count FROM `$table` WHERE `$column` = '$customer_id'");
				if($check_query) {
					$result = $check_query->fetch_assoc();
					if($result['count'] > 0) {
						$has_associations = true;
						$associations[] = $table . ' (' . $result['count'] . ' records)';
					}
				}
			}
		}
		
		return array(
			'has_associations' => $has_associations,
			'association_types' => $associations
		);
	}

	
	public function delete_customer(){
		$resp = array('status' => 'failed', 'msg' => '');
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf(false)) {
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		if($id <= 0){
			$resp['msg'] = 'Invalid customer ID.';
			return json_encode($resp);
		}
		
		
		$check_transactions = $this->checkCustomerAssociations($id);
		if($check_transactions['has_associations']) {
			$resp['msg'] = 'Cannot delete customer. Customer has existing records in: ' . implode(', ', $check_transactions['association_types']) . '. Please remove or transfer these records first, or consider deactivating the customer account instead.';
			return json_encode($resp);
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM customer_list WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$res = $stmt->get_result();
		if($res->num_rows == 0){
			$resp['msg'] = 'Customer not found.';
			return json_encode($resp);
		}
		$customer_row = $res->fetch_assoc();
		$this->log_deletion('customer_list', $id, $customer_row);
		
		
		$col_check = $this->conn->query("SHOW COLUMNS FROM `customer_list` LIKE 'deleted_at'");
		if(!$col_check || $col_check->num_rows == 0){
			@$this->conn->query("ALTER TABLE `customer_list` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL");
		}
		$stmt = $this->conn->prepare("UPDATE customer_list SET status = 0, deleted_at = NOW() WHERE id = ?");
		$stmt->bind_param('i', $id);
		$qry = $stmt->execute();
		
		if($qry){
			$this->settings->set_flashdata('success','Customer has been deleted and moved to Deleted Items.');
			$resp['status'] = 'success';
			$resp['msg'] = 'Customer has been deleted successfully.';
		} else {
			$resp['msg'] = 'Database error: ' . $this->conn->error;
		}
		return json_encode($resp);
	}

	
	public function deactivate_customer(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$resp = array();
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid customer ID.';
			return json_encode($resp);
		}
		
		
		
		
		
		$stmt = $this->conn->prepare("UPDATE customer_list SET status = 0, deleted_at = NOW() WHERE id = ?");
		$stmt->bind_param('i', $id);
		$qry = $stmt->execute();
		
		if($qry){
			$this->settings->set_flashdata('success','Customer has been deactivated successfully.');
			$resp['status'] = 'success';
			$resp['msg'] = 'Customer account has been deactivated successfully. All customer data has been preserved.';
		} else {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Database error: ' . $this->conn->error;
		}
		
		return json_encode($resp);
	}

	
	public function reactivate_customer(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$resp = array();
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid customer ID.';
			return json_encode($resp);
		}
		
		$stmt = $this->conn->prepare("UPDATE customer_list SET status = 1 WHERE id = ?");
		$stmt->bind_param('i', $id);
		$qry = $stmt->execute();
		
		if($qry){
			$this->settings->set_flashdata('success','Customer has been reactivated successfully.');
			$resp['status'] = 'success';
			$resp['msg'] = 'Customer account has been reactivated successfully.';
		} else {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Database error: ' . $this->conn->error;
		}
		
		return json_encode($resp);
	}

	
	public function get_customer_details(){
		$resp = array();
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid customer ID.';
			return json_encode($resp);
		}
	
	$stmt = $this->conn->prepare("SELECT * FROM customer_list WHERE id = ?"); $stmt->bind_param('i', $id); $stmt->execute(); $customer_query = $stmt->get_result();
	if($customer_query && $customer_query->num_rows > 0) {
		$customer = $customer_query->fetch_assoc();
		$associations = $this->checkCustomerAssociations($id);
		
		$resp['status'] = 'success';
		$resp['data'] = $customer;
		$resp['associations'] = $associations;
	} else {
		$resp['status'] = 'failed';
		$resp['msg'] = 'Customer not found.';
	}
	
	return json_encode($resp);
}
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'Users.php') {
    header('Content-Type: application/json');
    $users = new Users();
    $action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);

    switch ($action) {
	case 'save':
		echo $users->save_users();
		break;
	case 'delete':
		echo $users->delete_users();
		break;
	case 'registration':
		echo $users->registration();
		break;
	case 'delete_customer':
		echo $users->delete_customer();
		break;
	case 'deactivate_customer':
		echo $users->deactivate_customer();
		break;
	case 'reactivate_customer':
		echo $users->reactivate_customer();
		break;
	case 'get_customer_details':
		echo $users->get_customer_details();
		break;
	default:
		
		echo json_encode(['status' => 'error', 'msg' => 'Invalid action']);
		break;
    }
    exit;
}