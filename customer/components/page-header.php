<?php

function renderPageHeader($config = []) {
    $defaults = [
        'icon' => 'fas fa-home',
        'title' => 'Page Title',
        'description' => '',
        'stats' => []
    ];
    
    $config = array_merge($defaults, $config);
    ?>
    <div class="page-header-banner">
        <h1>
            <i class="<?= htmlspecialchars($config['icon']) ?>"></i>
            <?= htmlspecialchars($config['title']) ?>
        </h1>
        <?php if (!empty($config['description'])): ?>
            <p><?= htmlspecialchars($config['description']) ?></p>
        <?php endif; ?>
        
        <?php if (!empty($config['stats'])): ?>
            <div class="header-stats">
                <?php foreach ($config['stats'] as $stat): ?>
                    <span class="stat-badge">
                        <i class="<?= htmlspecialchars($stat['icon']) ?> mr-1"></i>
                        <span id="<?= isset($stat['id']) ? htmlspecialchars($stat['id']) : '' ?>"><?= htmlspecialchars($stat['value']) ?></span>
                        <?= htmlspecialchars($stat['label']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
