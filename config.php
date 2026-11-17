<?php

ob_start();
ini_set('date.timezone','Asia/Manila');
date_default_timezone_set('Asia/Manila');

require_once('initialize.php');

if (file_exists('config/database.php')) {
    require_once('config/database.php');              
}
if (file_exists('app/helpers/SecurityHelper.php')) {
    require_once('app/helpers/SecurityHelper.php');   
}

require_once('classes/ErrorHandler.php');
require_once('classes/DBConnection.php');         
if(!class_exists('SystemSettings')){
	require_once('classes/SystemSettings.php');
}

ErrorHandler::init();

if (class_exists('SecurityHelper')) {
    SecurityHelper::configureSession();
} else {
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (class_exists('SecurityHelper')) {
    SecurityHelper::generateCsrfToken();
}

if (!isset($_settings) && class_exists('SystemSettings')) {
    $_settings = new SystemSettings();
    $_settings->load_system_info();
} elseif (isset($_settings)) {
    
    $_settings->load_system_info();
}

$session_timeout = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

$db = new DBConnection;
$conn = $db->conn;

if (class_exists('Database')) {
    $pdo = Database::getConnection();
} else {
    $pdo = null; 
}

if($conn){
    $table_check = $conn->query("SHOW TABLES LIKE 'deleted_items'");
    if(!$table_check || $table_check->num_rows == 0){
        $conn->query("CREATE TABLE IF NOT EXISTS `deleted_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `table_name` VARCHAR(100) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `item_data` TEXT DEFAULT NULL,
            `deleted_by` INT(11) DEFAULT NULL,
            `deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_table_item` (`table_name`, `item_id`),
            INDEX `idx_deleted_at` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    $col_check = $conn->query("SHOW COLUMNS FROM `order_list` LIKE 'deleted_at'");
    if(!$col_check || $col_check->num_rows == 0){
        $conn->query("ALTER TABLE `order_list` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL");
    }
    $rx_expiry_check = $conn->query("SHOW COLUMNS FROM `customer_prescriptions` LIKE 'expires_at'");
    if($rx_expiry_check && $rx_expiry_check->num_rows == 0){
        $conn->query("ALTER TABLE `customer_prescriptions` ADD COLUMN `expires_at` DATE NULL AFTER `approved_at`");
    }
}

function redirect($url=''){
	if(!empty($url))
	echo '<script>location.href="'.base_url .$url.'"</script>';
}

function validate_image($file){
	global $_settings;
	if(!empty($file)){
		
		$ex = explode("?",$file);
		$file = $ex[0];
		$ts = isset($ex[1]) ? "?".$ex[1] : '';
		if(is_file(base_app.$file)){
			return base_url.$file.$ts;
		}else{
			
			if(is_file(base_app.'uploads/logo.png')){
				return base_url.'uploads/logo.png';
			}
			return base_url.'dist/img/no-image-available.png';
		}
	}else{
		
		if(is_file(base_app.'uploads/logo.png')){
			return base_url.'uploads/logo.png';
		}
		return base_url.'dist/img/no-image-available.png';
	}
}

function format_num($number = '' , $decimal = ''){
    if(is_numeric($number)){
        $ex = explode(".",$number);
        $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
        if(is_numeric($decimal)){
            return number_format($number,$decimal);
        }else{
            return number_format($number,$decLen);
        }
    }else{
        return "Invalid Input";
    }
}

function isMobileDevice(){
    $aMobileUA = array(
        '/iphone/i' => 'iPhone', 
        '/ipod/i' => 'iPod', 
        '/ipad/i' => 'iPad', 
        '/android/i' => 'Android', 
        '/blackberry/i' => 'BlackBerry', 
        '/webos/i' => 'Mobile'
    );

    
    foreach($aMobileUA as $sMobileKey => $sMobileOS){
        if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
    }
    
    return false;
}

function csrf_token() {
    if (class_exists('SecurityHelper')) {
        return SecurityHelper::generateCsrfToken();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    if (class_exists('SecurityHelper')) {
        return SecurityHelper::csrfField();
    }
    
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function validate_csrf() {
    if (class_exists('SecurityHelper')) {
        try {
            return SecurityHelper::validateCsrf(true);
        } catch (Exception $e) {
            http_response_code(403);
            die(json_encode(['status' => 'error', 'msg' => 'CSRF token validation failed']));
        }
    }
    
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function get_post($key, $default = '', $filter = FILTER_SANITIZE_SPECIAL_CHARS) {
    if (!isset($_POST[$key])) {
        return $default;
    }
    $value = $_POST[$key];
    if ($filter !== false) {
        return filter_var($value, $filter);
    }
    return $value;
}

function get_get($key, $default = '', $filter = FILTER_SANITIZE_SPECIAL_CHARS) {
    if (!isset($_GET[$key])) {
        return $default;
    }
    $value = $_GET[$key];
    if ($filter !== false) {
        return filter_var($value, $filter);
    }
    return $value;
}

function escape_output($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

ob_end_flush();
?>
