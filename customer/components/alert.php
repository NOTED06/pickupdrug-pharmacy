<?php

function renderAlert($config = []) {
    $defaults = [
        'type' => 'info', 
        'title' => '',
        'message' => 'Alert message',
        'dismissible' => true,
        'icon' => true,
        'id' => 'alert-' . uniqid()
    ];
    
    $config = array_merge($defaults, $config);
    
    $colors = [
        'success' => ['bg' => '#d1fae5', 'border' => '#10b981', 'text' => '#065f46', 'icon' => 'fa-check-circle'],
        'error' => ['bg' => '#fee2e2', 'border' => '#ef4444', 'text' => '#991b1b', 'icon' => 'fa-times-circle'],
        'warning' => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e', 'icon' => 'fa-exclamation-triangle'],
        'info' => ['bg' => '#dbeafe', 'border' => '#3b82f6', 'text' => '#1e40af', 'icon' => 'fa-info-circle']
    ];
    
    $color = $colors[$config['type']] ?? $colors['info'];
    ?>
    <div id="<?= htmlspecialchars($config['id']) ?>" class="custom-alert" 
         style="background: <?= $color['bg'] ?>; 
                border: 1px solid <?= $color['border'] ?>; 
                border-radius: 8px; 
                padding: 0.8rem; 
                margin: 1rem 0; 
                color: <?= $color['text'] ?>; 
                display: flex; 
                align-items: start; 
                gap: 0.75rem;
                animation: slideIn 0.3s ease;">
        
        <?php if ($config['icon']): ?>
            <div class="alert-icon" style="flex-shrink: 0;">
                <i class="fas <?= $color['icon'] ?>" style="font-size: 1.2rem;"></i>
            </div>
        <?php endif; ?>
        
        <div class="alert-content" style="flex: 1;">
            <?php if (!empty($config['title'])): ?>
                <div class="alert-title" style="font-weight: 600; margin-bottom: 0.25rem; font-size: 0.95rem;">
                    <?= htmlspecialchars($config['title']) ?>
                </div>
            <?php endif; ?>
            <div class="alert-message" style="font-size: 0.875rem; line-height: 1.5;">
                <?= htmlspecialchars($config['message']) ?>
            </div>
        </div>
        
        <?php if ($config['dismissible']): ?>
            <button class="alert-close" 
                    data-action="remove-element" 
                    data-target-id="<?= htmlspecialchars($config['id']) ?>" 
                    style="background: none; 
                           border: none; 
                           cursor: pointer; 
                           color: <?= $color['text'] ?>; 
                           opacity: 0.6; 
                           padding: 0; 
                           flex-shrink: 0;">
                <i class="fas fa-times"></i>
            </button>
        <?php endif; ?>
    </div>
    
    <style>
        @keyframes slideIn {
            from { transform: translateX(-10px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
    <?php
}

function renderInlineAlert($message, $type = 'error') {
    $colors = [
        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'icon' => 'fa-exclamation-circle'],
        'success' => ['bg' => '#d1fae5', 'text' => '#065f46', 'icon' => 'fa-check-circle'],
        'warning' => ['bg' => '#fef3c7', 'text' => '#92400e', 'icon' => 'fa-exclamation-triangle']
    ];
    
    $color = $colors[$type] ?? $colors['error'];
    ?>
    <div class="inline-alert" 
         style="background: <?= $color['bg'] ?>; 
                color: <?= $color['text'] ?>; 
                padding: 0.5rem; 
                border-radius: 6px; 
                font-size: 0.8rem; 
                margin-top: 0.25rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;">
        <i class="fas <?= $color['icon'] ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
    <?php
}
?>
