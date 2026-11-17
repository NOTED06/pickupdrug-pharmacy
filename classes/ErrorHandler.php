<?php

class ErrorHandler {
    private static $logFile = 'logs/app.log';
    private static $isProduction = false;
    
    
    public static function init() {
        
        
        self::$isProduction = (getenv('APP_ENV') === 'production') || 
                              (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'localhost') === false);
        
        
        if (self::$isProduction) {
            
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL);
        } else {
            
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
        
        
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }
    
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_STRICT => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER DEPRECATED'
        ];
        
        $errorType = $errorTypes[$errno] ?? 'UNKNOWN ERROR';
        $message = "[$errorType] $errstr in $errfile on line $errline";
        
        
        self::log($message);
        
        
        if (self::$isProduction) {
            
            if (in_array($errno, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
                echo "An error occurred. Please try again later.";
                exit(1);
            }
        } else {
            
            echo "<div style='padding:10px;margin:10px;border:1px solid #d00;background:#fee;color:#900;'>";
            echo "<strong>$errorType:</strong> $errstr<br>";
            echo "<strong>File:</strong> $errfile<br>";
            echo "<strong>Line:</strong> $errline<br>";
            echo "</div>";
        }
        
        
        return true;
    }
    
    
    public static function handleException($exception) {
        $message = "Uncaught Exception: " . $exception->getMessage() . 
                   " in " . $exception->getFile() . 
                   " on line " . $exception->getLine() . "\n" .
                   "Stack trace:\n" . $exception->getTraceAsString();
        
        
        self::log($message);
        
        
        if (self::$isProduction) {
            
            http_response_code(500);
            echo "An unexpected error occurred. Please try again later.";
        } else {
            
            echo "<div style='padding:10px;margin:10px;border:1px solid #d00;background:#fee;color:#900;'>";
            echo "<h3>Uncaught Exception</h3>";
            echo "<strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "<br>";
            echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "<br>";
            echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
            echo "<strong>Stack Trace:</strong><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
        
        exit(1);
    }
    
    
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            self::log($message);
            
            if (self::$isProduction) {
                
                ob_clean();
                http_response_code(500);
                echo "A critical error occurred. Please try again later.";
            }
            
        }
    }
    
    
    private static function log($message) {
        
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        
        $logEntry = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
        
        
        error_log($logEntry, 3, self::$logFile);
        
        
        error_log($message);
    }
    
    
    public static function logMessage($message, $level = 'INFO') {
        self::log("[$level] $message");
    }
    
    
    public static function logDatabaseError($query, $error) {
        $message = "Database Error: $error\nQuery: $query";
        self::log($message);
    }
    
    
    public static function isProduction() {
        return self::$isProduction;
    }
}

function log_error($message, $level = 'ERROR') {
    ErrorHandler::logMessage($message, $level);
}

function log_info($message) {
    ErrorHandler::logMessage($message, 'INFO');
}

function log_warning($message) {
    ErrorHandler::logMessage($message, 'WARNING');
}

function log_debug($message) {
    if (!ErrorHandler::isProduction()) {
        ErrorHandler::logMessage($message, 'DEBUG');
    }
}
?>
