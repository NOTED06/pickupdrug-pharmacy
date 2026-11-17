<?php

function renderProductCard($product = []) {
    $defaults = [
        'id' => 0,
        'name' => 'Product Name',
        'brand' => 'Brand',
        'price' => 0,
        'image' => '',
        'stock' => 0,
        'category' => '',
        'badges' => ['prescription' => false, 'fda' => false, 'new' => false, 'sale' => false]
    ];
    
    $product = array_merge($defaults, $product);
    $imageUrl = !empty($product['image']) ? validate_image($product['image']) : 'assets/img/no-image.jpg';
    ?>
    <div class="product-card" data-id="<?= $product['id'] ?>">
        <div class="product-card-image-wrapper">
            <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-card-image">
            
            
            <div class="product-badges">
                <?php if ($product['badges']['prescription']): ?>
                    <span class="badge-rx">℞ Prescription</span>
                <?php endif; ?>
                <?php if ($product['badges']['fda']): ?>
                    <span class="badge-fda">FDA Approved</span>
                <?php endif; ?>
                <?php if ($product['badges']['new']): ?>
                    <span class="badge-new">New</span>
                <?php endif; ?>
                <?php if ($product['badges']['sale']): ?>
                    <span class="badge-sale">Sale</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-card-body">
            <h5 class="product-name"><?= htmlspecialchars($product['name']) ?></h5>
            <p class="product-brand"><?= htmlspecialchars($product['brand']) ?></p>
            
            <?php if (!empty($product['category'])): ?>
                <p class="product-category">
                    <i class="fas fa-layer-group"></i> <?= htmlspecialchars($product['category']) ?>
                </p>
            <?php endif; ?>
            
            <div class="product-footer">
                <span class="product-price">₱<?= number_format($product['price'], 2) ?></span>
                
                <?php if ($product['stock'] > 0): ?>
                    <span class="product-stock text-success">
                        <i class="fas fa-check-circle"></i> <?= $product['stock'] ?> in stock
                    </span>
                <?php else: ?>
                    <span class="product-stock text-danger">
                        <i class="fas fa-times-circle"></i> Out of stock
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>
