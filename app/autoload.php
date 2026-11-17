<?php

spl_autoload_register(function ($class) {
    
    $baseDir = __DIR__ . '/';
    
    
    $namespaces = [
        'Controllers\\' => 'controllers/',
        'Models\\' => 'models/',
        'Services\\' => 'services/',
        'Helpers\\' => 'helpers/',
        'Middleware\\' => 'middleware/'
    ];
    
    
    foreach ($namespaces as $prefix => $dir) {
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        
        
        $relativeClass = substr($class, $len);
        
        
        $file = $baseDir . $dir . str_replace('\\', '/', $relativeClass) . '.php';
        
        
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    
    
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }
    
    
    
    $controllerFile = $baseDir . 'controllers/' . $class . '.php';
    if (file_exists($controllerFile)) {
        require $controllerFile;
        return;
    }
    
    
    $modelFile = $baseDir . 'models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require $modelFile;
        return;
    }
    
    
    $serviceFile = $baseDir . 'services/' . $class . '.php';
    if (file_exists($serviceFile)) {
        require $serviceFile;
        return;
    }
    
    
    $helperFile = $baseDir . 'helpers/' . $class . '.php';
    if (file_exists($helperFile)) {
        require $helperFile;
        return;
    }
});

function loadAppDependencies() {
    
    if (file_exists(__DIR__ . '/Router.php')) {
        require_once __DIR__ . '/Router.php';
    }
    
    
    if (file_exists(__DIR__ . '/services/ServiceProvider.php')) {
        require_once __DIR__ . '/services/ServiceProvider.php';
    }
    
    
    if (file_exists(__DIR__ . '/controllers/BaseController.php')) {
        require_once __DIR__ . '/controllers/BaseController.php';
    }
    
    
    if (file_exists(__DIR__ . '/models/BaseModel.php')) {
        require_once __DIR__ . '/models/BaseModel.php';
    }
}

loadAppDependencies();
