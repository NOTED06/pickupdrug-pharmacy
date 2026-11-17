<?php

class Database {
    private static $pdo = null;
    private static $mysqli = null;
    
    
    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=utf8mb4",
                    DB_SERVER,
                    DB_NAME
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                self::$pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
                
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                
                if (APP_DEBUG) {
                    throw $e;
                } else {
                    throw new Exception("Database connection failed. Please contact administrator.");
                }
            }
        }
        
        return self::$pdo;
    }
    
    
    public static function getLegacyConnection() {
        if (self::$mysqli === null) {
            self::$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            if (self::$mysqli->connect_error) {
                error_log("MySQLi Connection Error: " . self::$mysqli->connect_error);
                throw new Exception("Database connection failed.");
            }
            
            self::$mysqli->set_charset("utf8mb4");
        }
        
        return self::$mysqli;
    }
    
    
    public static function testConnection() {
        try {
            $pdo = self::getConnection();
            $pdo->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    
    public static function closeConnections() {
        self::$pdo = null;
        
        if (self::$mysqli !== null) {
            self::$mysqli->close();
            self::$mysqli = null;
        }
    }
}

if (!function_exists('db_connect')) {
    
    function db_connect() {
        return Database::getConnection();
    }
}

if (!function_exists('db_connect_legacy')) {
    
    function db_connect_legacy() {
        return Database::getLegacyConnection();
    }
}
