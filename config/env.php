<?php

function env($key, $default = null) {
    
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    
    
    return $default;
}

function loadEnv($path = null) {
    if ($path === null) {
        $path = dirname(__DIR__) . '/.env';
    }
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            
            $_ENV[$key] = $value;
            
            
            putenv("$key=$value");
        }
    }
    
    return true;
}

loadEnv();
