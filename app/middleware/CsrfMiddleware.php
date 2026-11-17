<?php

class CsrfMiddleware {
    
    
    public static function token() {
        return SecurityHelper::generateCsrfToken();
    }
    
    
    public static function field() {
        return SecurityHelper::csrfField();
    }
    
    
    public static function validate($throwException = true) {
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        return SecurityHelper::validateCsrf($throwException);
    }
    
    
    public static function isEnabled() {
        return defined('CSRF_ENABLED') && CSRF_ENABLED;
    }
}
