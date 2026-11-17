<?php

date_default_timezone_set('Asia/Manila');

define('APP_DEBUG', true);

if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

$is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if (!$is_https && !empty($host) && strpos($host, 'localhost') === false && $host !== '127.0.0.1') {
    header("Location: https://" . $host . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

require_once('config/env.php');

if(!defined('base_url')) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    
    if (strpos($host, 'localhost') !== false || $host === '127.0.0.1') {
        define('base_url', $scheme . '://' . $host . '/pickupdrug/');
    } else {
        
        define('base_url', $scheme . '://' . $host . '/');
    }
}
if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );

if(!defined('DB_SERVER')) define('DB_SERVER', env('DB_HOST', 'localhost'));
if(!defined('DB_USERNAME')) define('DB_USERNAME', env('DB_USER', 'root'));
if(!defined('DB_PASSWORD')) define('DB_PASSWORD', env('DB_PASS', ''));
if(!defined('DB_NAME')) define('DB_NAME', env('DB_NAME', 'pickupdrug'));

if(!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', env('SESSION_LIFETIME', 1800)); 
if(!defined('APP_DEBUG')) define('APP_DEBUG', env('APP_DEBUG', false));
if(!defined('CSRF_ENABLED')) define('CSRF_ENABLED', env('CSRF_ENABLED', true));
?>