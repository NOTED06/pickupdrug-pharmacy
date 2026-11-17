<?php
if(!defined('DB_SERVER')){
    require_once("../initialize.php");
}
class DBConnection{

    private $host = DB_SERVER;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $database = DB_NAME;
    
    public $conn;
    
    public function __construct(){

        if (!isset($this->conn)) {
            try {
                
                
                $this->conn = @new mysqli($this->host, $this->username, $this->password, $this->database);
                if (!$this->conn || $this->conn->connect_error) {
                    
                    if ($this->host === 'localhost') {
                        $this->conn = @new mysqli('127.0.0.1', $this->username, $this->password, $this->database);
                    }
                    if (!$this->conn || $this->conn->connect_error) {
                        $err = $this->conn ? $this->conn->connect_error : 'Failed to instantiate mysqli';
                        throw new Exception($err);
                    }
                }
                
                @$this->conn->query("SET time_zone = '+08:00'");
            } catch (Throwable $e) {
                error_log("Database connection failed: " . $e->getMessage());
                echo 'Cannot connect to database server: ' . $e->getMessage();
                exit;
            }
        }    
        
    }
    public function __destruct(){
        if ($this->conn instanceof mysqli) {
            try {
                
                if (@$this->conn->thread_id) {
                    $this->conn->close();
                }
            } catch (Throwable $e) {
                
            }
        }
    }
}
?>