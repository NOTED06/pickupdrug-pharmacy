<?php

?>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/categories.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/categories.css') ?: time(); ?>">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<div class="container catalog-layout">
    
    
    <aside class="sidebar hidden-mobile">
        <div class="sidebar-header">
            <h2>Catalog</h2>
            <p class="subtitle">Select Category</p>
        </div>
        
        <div class="categories-list">
            <div class="cat-item active" data-category="all">
                <i class="fa-solid fa-layer-group"></i> 
                <span>All Products</span>
                <span class="cat-badge" id="allCount">0</span>
            </div>
            
            <div class="category-divider"></div>
            
            <?php 
            
            $categories = $conn->query("SELECT * FROM category_list WHERE delete_flag = 0 AND status = 1 ORDER BY name ASC");
            while($cat = $categories->fetch_assoc()):
                
                $count = $conn->query("SELECT COUNT(*) as total FROM product_list WHERE category_id = {$cat['id']} AND delete_flag = 0")->fetch_assoc()['total'];
            ?>
            <div class="cat-item" data-category="<?php echo $cat['id']; ?>">
                <i class="fa-solid fa-pills"></i>
                <span><?php echo $cat['name']; ?></span>
                <span class="cat-badge"><?php echo $count; ?></span>
            </div>
            <?php endwhile; ?>
        </div>
    </aside>

    
    <main class="products-section">
        
        
        <div class="mobile-filters show-mobile">
            <button class="mob-pill active" data-category="all">All</button>
            <?php 
            $categories->data_seek(0); 
            while($cat = $categories->fetch_assoc()): 
            ?>
            <button class="mob-pill" data-category="<?php echo $cat['id']; ?>">
                <?php echo $cat['name']; ?>
            </button>
            <?php endwhile; ?>
        </div>

        
        <div class="products-header">
            <div class="header-info">
                <span class="showing-text">
                    Showing <span class="count-highlight" id="itemCount">All</span> Products
                </span>
            </div>
            <div class="header-actions">
                <span class="sort-label">Sort:</span>
                <select class="sort-select" id="sortSelect">
                    <option value="featured">Featured</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="newest">Newest</option>
                    <option value="name">Name: A-Z</option>
                </select>
            </div>
        </div>

        
        <div class="products-grid" id="productsGrid">
            
        </div>

        
        <div class="loading-state" id="loadingState">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading products...</p>
        </div>

        
        <div class="empty-state" id="emptyState" style="display: none;">
            <i class="fas fa-box-open"></i>
            <h3>No Products Found</h3>
            <p>Try selecting a different category or check back later.</p>
        </div>
    </main>
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
                    <button class="modal-add-cart" id="modalAddToCart">
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
    
    const BASE_URL = typeof _base_url_ !== 'undefined' ? _base_url_ : '<?php echo base_url ?>';
</script>
<script src="<?php echo base_url ?>customer/assets/js/categories.js?v=<?php echo @filemtime(__DIR__ . '/assets/js/categories.js') ?: time(); ?>"></script>
