<?php

return [
    
    
    'enable_new_routing' => false,
    
    
    'modules' => [
        'product' => [
            'enabled' => false,
            'description' => 'Product management (save, delete, get)',
            'migrated_at' => null,
            'controller' => 'ProductController'
        ],
        
        'category' => [
            'enabled' => false,
            'description' => 'Category management',
            'migrated_at' => null,
            'controller' => 'CategoryController'
        ],
        
        'cart' => [
            'enabled' => false,
            'description' => 'Shopping cart operations',
            'migrated_at' => null,
            'controller' => 'CartController'
        ],
        
        'order' => [
            'enabled' => false,
            'description' => 'Order processing and management',
            'migrated_at' => null,
            'controller' => 'OrderController'
        ],
        
        'branch' => [
            'enabled' => false,
            'description' => 'Branch management and operations',
            'migrated_at' => null,
            'controller' => 'BranchController'
        ],
        
        'inventory' => [
            'enabled' => false,
            'description' => 'Stock and inventory management',
            'migrated_at' => null,
            'controller' => 'InventoryController'
        ],
        
        'form' => [
            'enabled' => false,
            'description' => 'Forms management',
            'migrated_at' => null,
            'controller' => 'FormController'
        ],
        
        'inquiry' => [
            'enabled' => false,
            'description' => 'Customer inquiries',
            'migrated_at' => null,
            'controller' => 'InquiryController'
        ],
        
        'notification' => [
            'enabled' => false,
            'description' => 'Notification system',
            'migrated_at' => null,
            'controller' => 'NotificationController'
        ]
    ],
    
    
    'action_overrides' => [
        
    ],
    
    
    'development' => [
        'enable_debug_logging' => true,
        'log_routing_decisions' => true,
        'show_deprecation_warnings' => false
    ],
    
    
    'testing' => [
        'enabled' => false,
        'allow_route_override' => false, 
        'compare_responses' => false     
    ],
    
    
    'monitoring' => [
        'enabled' => false,
        'log_response_times' => false,
        'alert_on_degradation' => false,
        'max_acceptable_degradation_percent' => 10
    ],
    
    
    'rollback' => [
        'auto_rollback_on_error' => true,
        'keep_master_php_backup' => true,
        'rollback_window_days' => 90
    ]
];
