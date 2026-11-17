<?php

function getWishlistIds() {
    return []; 
}
?>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/home.css?v=<?php echo filemtime('assets/css/customer/home.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<?php  ?>

<div class="main-layout">

<section class="welcome-header">
    <div class="welcome-content">
        <div class="avatar">
            <?php 
            $avatar_path = $_settings->userdata('avatar');
            if($avatar_path && file_exists($avatar_path)): ?>
                <img src="<?php echo validate_image($avatar_path); ?>" alt="User Avatar">
            <?php else: ?>
                <?php echo strtoupper(substr($_settings->userdata('firstname'), 0, 1)); ?>
            <?php endif; ?>
        </div>
        <?php 
        
        $welcome_content = '';
        if(is_file(base_app.'welcome.html')) {
            $welcome_content = file_get_contents(base_app.'welcome.html');
        }
        
        
        if(!empty($welcome_content)): 
        ?>
            <div class="welcome-dynamic-content">
                <?php echo html_entity_decode($welcome_content); ?>
            </div>
        <?php else: ?>
            <h1 class="welcome-title">Welcome, <?php echo $_settings->userdata('firstname'); ?>!</h1>
            <p class="welcome-subtitle">Discover our latest pharmaceutical products and healthcare essentials</p>
        <?php endif; ?>
    </div>
</section>

<div class="slideshow-container" id="productSlideshow">
        <?php
        
        $slideshow_products = $conn->query("SELECT p.*, c.name as category_name 
                                           FROM product_list p 
                                           LEFT JOIN category_list c ON p.category_id = c.id 
                                           WHERE p.delete_flag = 0 
                                           ORDER BY p.date_created DESC 
                                           LIMIT 5");
        $slide_count = 0;
        while($product = $slideshow_products->fetch_assoc()):
            $slide_count++;
        ?>
        <div class="slide <?php echo $slide_count == 1 ? 'active' : ''; ?>">
            <div class="slide-inner">
                <div class="slide-text slide-content">
                    <span class="slide-label">✨ NEW ARRIVAL</span>
                    <h2 class="slide-title"><?php echo $product['name']; ?></h2>
                    <div class="slide-meta">
                        <?php if(!empty($product['brand'])): ?>
                        <span class="slide-brand"><?php echo htmlspecialchars($product['brand']); ?></span>
                        <?php endif; ?>
                        <?php if(!empty($product['dose'])): ?>
                        <span class="slide-dose"><?php echo htmlspecialchars($product['dose']); ?></span>
                        <?php endif; ?>
                        <?php if(!empty($product['unit'])): ?>
                        <span class="slide-unit">Per <?php echo htmlspecialchars($product['unit']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="slide-description"><?php echo substr($product['description'], 0, 120); ?>...</p>
                    <div class="slide-price">₱<?php echo number_format($product['price'], 2); ?></div>
                    <div class="slide-actions">
                        <button class="btn-view" data-action="view-product" data-product-id="<?php echo $product['id']; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            View Details
                        </button>
                    </div>
                </div>
                <div class="slide-visual slide-content">
                    <div class="scene">
                        <div class="cube">
                            <div class="face front">
                                <img src="<?php echo validate_image($product['image_path']); ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="face back">
                                <img src="<?php echo validate_image($product['image_path']); ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="face right">
                                <img src="<?php echo validate_image($product['image_path']); ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="face left">
                                <img src="<?php echo validate_image($product['image_path']); ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="face top"></div>
                            <div class="face bottom"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        
        <div class="slideshow-dots">
            <?php for($i = 0; $i < $slide_count; $i++): ?>
            <span class="dot <?php echo $i == 0 ? 'active' : ''; ?>" data-action="slide-nav" data-slide-index="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
</div>

<section class="categories-section">
    <div class="categories-header">
        <div class="categories-header-text">
            <span>Browse Categories</span>
            <h2>Shop by <span>Category</span></h2>
        </div>
        <a href="<?php echo base_url ?>?p=customer/categories" class="btn-view-all" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">View All</a>
    </div>
    
    <div class="marquee-container">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0 20px 10px; border-bottom: 2px solid #e5e7eb; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
                <h3 style="font-size: 1.1rem; color: #1f2937; margin: 0; font-weight: 600;">All Products</h3>
            </div>
        </div>
        <div class="marquee-content">
            <?php
            
            $cat_query = $conn->query("SELECT c.*, COUNT(p.id) as product_count 
                                       FROM category_list c 
                                       LEFT JOIN product_list p ON p.category_id = c.id AND p.delete_flag = 0
                                       WHERE c.delete_flag = 0
                                       GROUP BY c.id 
                                       ORDER BY c.name ASC");
            
            
            $category_icons = [
                'vitamins' => 'fa-pills',
                'supplements' => 'fa-capsules',
                'pain relief' => 'fa-hand-holding-medical',
                'first aid' => 'fa-kit-medical',
                'personal care' => 'fa-pump-soap',
                'baby care' => 'fa-baby',
                'health devices' => 'fa-heartbeat',
                'skin care' => 'fa-spa',
                'oral care' => 'fa-tooth',
                'eye care' => 'fa-eye',
                'respiratory' => 'fa-lungs',
                'digestive' => 'fa-stomach',
                'diabetes' => 'fa-syringe',
                'heart health' => 'fa-heart',
                'immunity' => 'fa-shield-virus',
                'default' => 'fa-prescription-bottle-medical'
            ];
            
            $categories_arr = [];
            while($cat = $cat_query->fetch_assoc()) {
                $categories_arr[] = $cat;
            }
            
            
            $all_categories = array_merge($categories_arr, $categories_arr);
            
            foreach($all_categories as $cat):
                $cat_name_lower = strtolower($cat['name']);
                $icon = $category_icons['default'];
                foreach($category_icons as $key => $ico) {
                    if(strpos($cat_name_lower, $key) !== false) {
                        $icon = $ico;
                        break;
                    }
                }
            ?>
            <div class="category-card" data-action="filter-category" data-category-id="<?php echo $cat['id']; ?>">
                <div class="category-icon-bg">
                    <i class="fas <?php echo $icon; ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                <p>Health & Wellness</p>
                <span class="product-count"><?php echo $cat['product_count']; ?> Products</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="products-section">
    <div class="products-grid" id="productsContainer">
        
        <?php
        $products = $conn->query("SELECT p.*, c.name as category_name,
                                 (COALESCE((SELECT SUM(quantity) FROM `stock_list` where product_id = p.id and (expiration IS NULL or date(expiration) > '".date("Y-m-d")."') ), 0) - COALESCE((SELECT SUM(oi.quantity) FROM `order_items` oi INNER JOIN `order_list` ol ON oi.order_id = ol.id WHERE oi.product_id = p.id AND ol.status != 4), 0)) as `available`,
                                 COALESCE(p.is_prescription, 0) as is_prescription,
                                 COALESCE(p.fda_approved, 1) as fda_approved
                                 FROM product_list p 
                                 LEFT JOIN category_list c ON p.category_id = c.id 
                                 WHERE p.delete_flag = 0 
                                 ORDER BY p.date_created DESC 
                                 LIMIT 12");
        while($product = $products->fetch_assoc()):
            $stock = intval($product['available']);
            $isOutOfStock = $stock <= 0;
            $isLowStock = $stock > 0 && $stock <= 5;
            $isPrescription = isset($product['is_prescription']) && $product['is_prescription'] == 1;
                $isFdaApproved = !isset($product['fda_approved']) || $product['fda_approved'] == 1;
                $isNew = strtotime($product['date_created']) > strtotime('-7 days');
            ?>
            <div class="glass-card" data-product-id="<?php echo $product['id']; ?>">
                <div class="product-image-wrapper">
                    <img src="<?php echo validate_image($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    
                    <div class="badge-container">
                        <?php if($isNew && !$isOutOfStock): ?>
                        <span class="badge badge-new">New</span>
                        <?php endif; ?>
                        <?php if($isPrescription): ?>
                        <span class="badge badge-rx"><i class="fas fa-prescription"></i> Rx</span>
                        <?php endif; ?>
                        <?php if($isFdaApproved): ?>
                        <span class="badge badge-fda"><i class="fas fa-check"></i> FDA</span>
                        <?php endif; ?>
                        <?php if($isOutOfStock): ?>
                        <span class="badge badge-out-of-stock">Out of Stock</span>
                        <?php elseif($isLowStock): ?>
                        <span class="badge badge-low-stock"><?php echo $stock; ?> Left</span>
                        <?php endif; ?>
                    </div>
                    
                    
                    <div class="quick-actions">
                        <button class="quick-action-btn <?php echo in_array($product['id'], getWishlistIds()) ? 'active' : ''; ?>" 
                                data-action="toggle-wishlist" 
                                data-product-id="<?php echo $product['id']; ?>" 
                                title="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="quick-action-btn" data-action="view-product" data-product-id="<?php echo $product['id']; ?>" title="Quick View">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="product-details">
                    <span class="product-category-label"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <?php if(!empty($product['brand'])): ?>
                    <span class="product-brand"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['brand']); ?></span>
                    <?php endif; ?>
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="product-meta">
                        <?php if(!empty($product['dose'])): ?>
                        <span class="product-dosage"><i class="fas fa-prescription-bottle"></i> <?php echo htmlspecialchars($product['dose']); ?></span>
                        <?php endif; ?>
                        <?php if(!empty($product['unit'])): ?>
                        <span class="product-unit"><i class="fas fa-box"></i> Per <?php echo htmlspecialchars($product['unit']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="stock-status">
                        <span class="status-dot <?php echo $stock > 0 ? 'available' : 'out-of-stock' ?>"></span>
                        <span class="status-text"><?php echo $stock > 0 ? number_format($stock) . ' in stock' : 'Out of stock' ?></span>
                    </div>
                    <div class="product-footer">
                        <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                        <?php if(!$isOutOfStock): ?>
                        <div class="cart-controls">
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn" data-action="qty-decrease" data-product-id="<?php echo $product['id']; ?>">−</button>
                                <input type="number" class="qty-input" id="qty-<?php echo $product['id']; ?>" value="1" min="1" max="<?php echo min($stock, 99); ?>" data-product-id="<?php echo $product['id']; ?>">
                                <button type="button" class="qty-btn" data-action="qty-increase" data-product-id="<?php echo $product['id']; ?>" data-max="<?php echo min($stock, 99); ?>">+</button>
                            </div>
                            <button class="btn-add" data-action="add-to-cart" data-product-id="<?php echo $product['id']; ?>" title="Add to Cart">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <button class="btn-add" disabled title="Out of Stock">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="<?php echo base_url ?>?p=customer/categories" class="btn-view-all" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">View All Products</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="recently-viewed-section" id="recentlyViewedSection" style="display: none;">
        <div class="recently-viewed-header">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Recently Viewed
            </h3>
            <button class="btn-clear-history" data-action="clear-recent">
                <i class="fas fa-trash-alt"></i> Clear History
            </button>
        </div>
        <div class="recently-viewed-grid" id="recentlyViewedGrid">
            
        </div>
    </div>
</div>

<div id="productModal" class="product-modal">
        <div class="modal-content">
            <button class="modal-close" data-action="close-product-modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="modalProductImage" src="" alt="">
                </div>
                <div class="modal-info">
                    <span class="modal-badge" id="modalProductBadge">NEW</span>
                    <span class="modal-category" id="modalProductCategory"></span>
                    <h2 class="modal-title" id="modalProductName"></h2>
                    <div class="modal-product-meta" id="modalProductMeta">
                        <span class="meta-brand" id="modalProductBrand" style="display:none;"><i class="fas fa-tag"></i> <span></span></span>
                        <span class="meta-dosage" id="modalProductDosage" style="display:none;"><i class="fas fa-prescription-bottle"></i> <span></span></span>
                        <span class="meta-unit" id="modalProductUnit" style="display:none;"><i class="fas fa-box"></i> Per <span></span></span>
                    </div>
                    <div class="modal-stock" id="modalProductStock"></div>
                    <p class="modal-description" id="modalProductDescription"></p>
                    <div class="modal-price" id="modalProductPrice"></div>
                    <div class="modal-quantity">
                        <span class="modal-qty-label">Quantity:</span>
                        <div class="quantity-controls modal-qty-controls">
                            <button type="button" class="qty-btn" data-action="modal-qty-decrease">−</button>
                            <input type="number" class="qty-input" id="modalQtyInput" value="1" min="1" max="99">
                            <button type="button" class="qty-btn" data-action="modal-qty-increase">+</button>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button class="modal-add-cart" data-action="add-to-cart-modal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
</div>

<script>

(function() {
    var origin = window.location.origin;
    var base = (origin.indexOf('localhost') !== -1 || origin.indexOf('127.0.0.1') !== -1) ? origin + '/pickupdrug/' : origin + '/';
    window.base_url = base;
    window.BASE_URL = base;
    window._base_url_ = base;
})();
</script>

<script src="<?php echo base_url ?>assets/js/customer/home.js?v=<?php echo filemtime('assets/js/customer/home.js'); ?>"></script>

<script>

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewProductId = urlParams.get('view_product');
    
    if(viewProductId && typeof viewProduct === 'function') {
        setTimeout(function() {
            viewProduct(parseInt(viewProductId));
            const newUrl = window.location.pathname + '?p=customer/home';
            window.history.replaceState({}, '', newUrl);
        }, 500);
    }
});
</script>

</div>
