<?php

class ServiceProvider {
    
    
    private static $instance = null;
    
    
    private $services = [];
    
    
    private $instances = [];
    
    
    private function __construct() {
        $this->registerDefaultServices();
    }
    
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    
    public function register($name, $resolver, $singleton = true) {
        $this->services[$name] = [
            'resolver' => $resolver,
            'singleton' => $singleton
        ];
    }
    
    
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new Exception("Service '{$name}' not found.");
        }
        
        $service = $this->services[$name];
        
        
        if ($service['singleton'] && isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        
        $instance = call_user_func($service['resolver']);
        
        
        if ($service['singleton']) {
            $this->instances[$name] = $instance;
        }
        
        return $instance;
    }
    
    
    public function has($name) {
        return isset($this->services[$name]);
    }
    
    
    private function registerDefaultServices() {
        
        $this->register('db', function() {
            require_once(__DIR__ . '/../../classes/DBConnection.php');
            return new DBConnection();
        }, true);
        
        
        $this->register('settings', function() {
            global $_settings;
            return $_settings;
        }, true);
        
        
        $this->register('security', function() {
            require_once(__DIR__ . '/../helpers/SecurityHelper.php');
            return new SecurityHelper();
        }, true);
        
        
        $this->register('validation', function() {
            require_once(__DIR__ . '/../helpers/ValidationHelper.php');
            return new ValidationHelper();
        }, true);
    }
    
    
    public function createController($controllerClass) {
        
        
        return new $controllerClass();
    }
}
