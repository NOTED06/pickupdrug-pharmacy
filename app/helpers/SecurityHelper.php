<?php

class SecurityHelper {
    
    
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    
    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    
    
    public static function validateCsrf($throwException = true) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        $valid = !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
        
        if (!$valid && $throwException) {
            http_response_code(403);
            throw new Exception('CSRF token validation failed');
        }
        
        return $valid;
    }
    
    
    public static function escape($string, $encoding = 'UTF-8') {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, $encoding);
    }
    
    
    public static function e($string) {
        return self::escape($string);
    }
    
    
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        $input = trim($input);
        $input = strip_tags($input);
        return $input;
    }
    
    
    public static function sanitizeEmail($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    
    public static function sanitizeUrl($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    
    
    public static function sanitizeFilename($filename) {
        
        $filename = basename($filename);
        
        
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        
        $filename = preg_replace('/_+/', '_', $filename);
        
        return $filename;
    }
    
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    
    public static function passwordNeedsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
    
    
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    
    public static function validateUpload($file, $options = []) {
        $result = ['valid' => true, 'error' => ''];
        
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
            ];
            $result['valid'] = false;
            $result['error'] = $errors[$file['error']] ?? 'Unknown upload error';
            return $result;
        }
        
        
        if (isset($options['max_size']) && $file['size'] > $options['max_size']) {
            $result['valid'] = false;
            $result['error'] = 'File is too large';
            return $result;
        }
        
        
        if (isset($options['allowed_types'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $options['allowed_types'])) {
                $result['valid'] = false;
                $result['error'] = 'File type not allowed';
                return $result;
            }
        }
        
        
        if (isset($options['max_width']) || isset($options['max_height'])) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo !== false) {
                if (isset($options['max_width']) && $imageInfo[0] > $options['max_width']) {
                    $result['valid'] = false;
                    $result['error'] = 'Image width exceeds maximum';
                    return $result;
                }
                if (isset($options['max_height']) && $imageInfo[1] > $options['max_height']) {
                    $result['valid'] = false;
                    $result['error'] = 'Image height exceeds maximum';
                    return $result;
                }
            }
        }
        
        return $result;
    }
    
    
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    
    public static function configureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
    }
    
    
    public static function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    
    public static function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    
    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    
    public static function getClientIp() {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        return 'UNKNOWN';
    }
}
