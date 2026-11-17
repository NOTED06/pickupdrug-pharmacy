<?php

require_once(__DIR__ . '/services/ServiceProvider.php');

class Router {
    
    
    private $serviceProvider;
    
    
    private $routes = [];
    
    
    private $useLegacyRouting = true;
    
    
    public function __construct() {
        $this->serviceProvider = ServiceProvider::getInstance();
        $this->registerRoutes();
    }
    
    
    private function registerRoutes() {
        
        $this->routes['save_product'] = ['controller' => 'ProductController', 'method' => 'store'];
        $this->routes['delete_product'] = ['controller' => 'ProductController', 'method' => 'destroy'];
        $this->routes['get_product'] = ['controller' => 'ProductController', 'method' => 'show'];
        
        
        $this->routes['save_category'] = ['controller' => 'CategoryController', 'method' => 'store'];
        $this->routes['delete_category'] = ['controller' => 'CategoryController', 'method' => 'destroy'];
        
        
        $this->routes['add_to_cart'] = ['controller' => 'CartController', 'method' => 'add'];
        $this->routes['update_cart'] = ['controller' => 'CartController', 'method' => 'update'];
        $this->routes['delete_cart'] = ['controller' => 'CartController', 'method' => 'remove'];
        $this->routes['clear_cart'] = ['controller' => 'CartController', 'method' => 'clear'];
        
        
        $this->routes['place_order'] = ['controller' => 'OrderController', 'method' => 'place'];
        $this->routes['update_order_status'] = ['controller' => 'OrderController', 'method' => 'updateStatus'];
        $this->routes['delete_order'] = ['controller' => 'OrderController', 'method' => 'delete'];
        $this->routes['cancel_order'] = ['controller' => 'OrderController', 'method' => 'cancel'];
        $this->routes['admin_cancel_order'] = ['controller' => 'OrderController', 'method' => 'adminCancel'];
        
        
        $this->routes['save_branch'] = ['controller' => 'BranchController', 'method' => 'store'];
        $this->routes['get_branch'] = ['controller' => 'BranchController', 'method' => 'show'];
        $this->routes['get_all_branches'] = ['controller' => 'BranchController', 'method' => 'index'];
        $this->routes['get_active_branches'] = ['controller' => 'BranchController', 'method' => 'active'];
        $this->routes['toggle_branch'] = ['controller' => 'BranchController', 'method' => 'toggle'];
        $this->routes['delete_branch'] = ['controller' => 'BranchController', 'method' => 'destroy'];
        $this->routes['get_branch_stats'] = ['controller' => 'BranchController', 'method' => 'statistics'];
        $this->routes['can_branch_accept_orders'] = ['controller' => 'BranchController', 'method' => 'canAcceptOrders'];
        
        
        $this->routes['save_users'] = ['controller' => 'UserController', 'method' => 'store'];
        $this->routes['delete_users'] = ['controller' => 'UserController', 'method' => 'destroy'];
        $this->routes['get_customers'] = ['controller' => 'UserController', 'method' => 'customers'];
        $this->routes['get_customer_details'] = ['controller' => 'UserController', 'method' => 'customerDetails'];
        $this->routes['delete_customer'] = ['controller' => 'UserController', 'method' => 'deleteCustomer'];
        $this->routes['deactivate_customer'] = ['controller' => 'UserController', 'method' => 'deactivateCustomer'];
        $this->routes['reactivate_customer'] = ['controller' => 'UserController', 'method' => 'reactivateCustomer'];
        
        
        $this->routes['update_settings_info'] = ['controller' => 'SystemSettingsController', 'method' => 'updateSettings'];
        $this->routes['get_settings'] = ['controller' => 'SystemSettingsController', 'method' => 'getSettings'];
        $this->routes['get_setting'] = ['controller' => 'SystemSettingsController', 'method' => 'getSetting'];
    }
    
    
    public function dispatch($action) {
        
        if (isset($this->routes[$action])) {
            
            if (!$this->useLegacyRouting) {
                return $this->dispatchToController($action);
            }
            
            
            $controllerFile = __DIR__ . '/controllers/' . $this->routes[$action]['controller'] . '.php';
            if (file_exists($controllerFile)) {
                
                return $this->dispatchToController($action);
            }
        }
        
        
        return $this->dispatchToLegacy($action);
    }
    
    
    private function dispatchToController($action) {
        $route = $this->routes[$action];
        $controllerClass = $route['controller'];
        $method = $route['method'];
        
        
        $controllerFile = __DIR__ . '/controllers/' . $controllerClass . '.php';
        if (!file_exists($controllerFile)) {
            return json_encode([
                'status' => 'failed',
                'msg' => 'Controller not found'
            ]);
        }
        
        require_once($controllerFile);
        
        
        $controller = $this->serviceProvider->createController($controllerClass);
        
        
        if (!method_exists($controller, $method)) {
            return json_encode([
                'status' => 'failed',
                'msg' => 'Method not found'
            ]);
        }
        
        return $controller->$method();
    }
    
    
    private function dispatchToLegacy($action) {
        require_once(__DIR__ . '/../classes/Master.php');
        $Master = new Master();
        
        
        if (!method_exists($Master, $action)) {
            return json_encode([
                'status' => 'failed',
                'msg' => 'Action not found'
            ]);
        }
        
        return $Master->$action();
    }
    
    
    public function enableNewRouting() {
        $this->useLegacyRouting = false;
    }
    
    
    public function enableLegacyRouting() {
        $this->useLegacyRouting = true;
    }
}
