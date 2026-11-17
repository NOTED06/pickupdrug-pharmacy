<?php

class AuthMiddleware {
    
    
    public static function check($redirectUrl = null) {
        if (!self::isAuthenticated()) {
            if ($redirectUrl) {
                self::redirect($redirectUrl);
            }
            return false;
        }
        return true;
    }
    
    
    public static function checkAdmin($redirectUrl = 'admin/login.php') {
        if (!self::isAuthenticated() || !self::isAdmin()) {
            self::redirect($redirectUrl);
            return false;
        }
        return true;
    }
    
    
    public static function checkCustomer($redirectUrl = 'index.php') {
        if (!self::isAuthenticated() || !self::isCustomer()) {
            self::redirect($redirectUrl);
            return false;
        }
        return true;
    }
    
    
    public static function checkRole($roles, $redirectUrl = '404.html') {
        if (!self::isAuthenticated()) {
            self::redirect('admin/login.php');
            return false;
        }
        
        $roles = is_array($roles) ? $roles : [$roles];
        $userRole = self::getUserRole();
        
        if (!in_array($userRole, $roles)) {
            self::redirect($redirectUrl);
            return false;
        }
        
        return true;
    }
    
    
    public static function isAuthenticated() {
        return isset($_SESSION['userdata']) && !empty($_SESSION['userdata']['id']);
    }
    
    
    public static function isAdmin() {
        return isset($_SESSION['login_type']) && $_SESSION['login_type'] == 1;
    }
    
    
    public static function isCustomer() {
        return isset($_SESSION['login_type']) && $_SESSION['login_type'] == 2;
    }
    
    
    public static function getUserRole() {
        if (self::isAdmin()) {
            
            $type = $_SESSION['userdata']['type'] ?? 1;
            return $type == 1 ? 'admin' : 'staff';
        }
        if (self::isCustomer()) {
            return 'customer';
        }
        return null;
    }
    
    
    public static function getUserId() {
        return $_SESSION['userdata']['id'] ?? null;
    }
    
    
    public static function getUserData($key = null) {
        if ($key) {
            return $_SESSION['userdata'][$key] ?? null;
        }
        return $_SESSION['userdata'] ?? [];
    }
    
    
    public static function setUserData($key, $value) {
        $_SESSION['userdata'][$key] = $value;
    }
    
    
    public static function isGuest() {
        return !self::isAuthenticated();
    }
    
    
    private static function redirect($url) {
        $baseUrl = defined('base_url') ? base_url : '/';
        $fullUrl = (strpos($url, 'http') === 0) ? $url : $baseUrl . $url;
        
        header("Location: {$fullUrl}");
        exit;
    }
    
    
    public static function logout() {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    
    public static function login($userData, $loginType) {
        
        unset($userData['password']);
        
        $_SESSION['userdata'] = $userData;
        $_SESSION['login_type'] = $loginType;
        
        
        SecurityHelper::regenerateSession();
    }
}
