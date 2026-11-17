

'use strict';

$(document).ready(function() {
    
    const urlParams = new URLSearchParams(window.location.search);
    const urlCategory = urlParams.get('category');
    let currentCategory = urlCategory || 'all';

    
    if (urlCategory) {
        $('.cat-item').removeClass('active');
        $(`.cat-item[data-category="${urlCategory}"]`).addClass('active');
        $('.mob-pill').removeClass('active');
        $(`.mob-pill[data-category="${urlCategory}"]`).addClass('active');
    }
    let currentSort = 'featured';
    let allProducts = [];

    
    
    $(document).on('click', '.glass-card', function(e) {
        
        if ($(e.target).closest('.quick-action-btn, .btn-add').length) return;
        const productId = $(this).data('product-id');
        if (productId) openProductModal(productId);
    });

    $(document).on('click', '.quick-action-btn[data-action="wishlist"]', function(e) {
        e.stopPropagation();
        const productId = $(this).data('product-id');
        toggleWishlistBtn(this, productId);
    });

    $(document).on('click', '.quick-action-btn[data-action="quickview"]', function(e) {
        e.stopPropagation();
        const productId = $(this).data('product-id');
        openProductModal(productId);
    });

    $(document).on('click', '.btn-add', function(e) {
        e.stopPropagation();
        const productId = $(this).data('product-id');
        addToCartFromCard(productId, this);
    });

    
    
    $(document).on('click', '.qty-btn[data-action="qty-increase"]', function(e) {
        e.stopPropagation();
        const productId = $(this).data('product-id');
        const maxQty = parseInt($(this).data('max')) || 99;
        const input = document.getElementById('qty-' + productId);
        if (input) {
            let currentVal = parseInt(input.value) || 1;
            if (currentVal < maxQty) {
                input.value = currentVal + 1;
            }
        }
    });

    
    $(document).on('click', '.qty-btn[data-action="qty-decrease"]', function(e) {
        e.stopPropagation();
        const productId = $(this).data('product-id');
        const input = document.getElementById('qty-' + productId);
        if (input) {
            let currentVal = parseInt(input.value) || 1;
            if (currentVal > 1) {
                input.value = currentVal - 1;
            }
        }
    });

    
    $(document).on('click', '.qty-input', function(e) {
        e.stopPropagation();
    });

    
    
    $(document).on('keydown', '.glass-card', function(e) {
        if (e.key === 'Enter' && e.target === this) {
            const productId = $(this).data('product-id');
            if (productId) openProductModal(productId);
        }
    });

    
    loadProducts();

    
    $('.cat-item').on('click', function() {
        const category = $(this).data('category');
        currentCategory = category;
        
        
        $('.cat-item').removeClass('active');
        $(this).addClass('active');
        
        
        $('.mob-pill').removeClass('active');
        $(`.mob-pill[data-category="${category}"]`).addClass('active');
        
        filterProducts();
    });

    
    $('.mob-pill').on('click', function() {
        const category = $(this).data('category');
        currentCategory = category;
        
        
        $('.mob-pill').removeClass('active');
        $(this).addClass('active');
        
        
        $('.cat-item').removeClass('active');
        $(`.cat-item[data-category="${category}"]`).addClass('active');
        
        filterProducts();
    });

    
    $('#sortSelect').on('change', function() {
        currentSort = $(this).val();
        filterProducts();
    });

    
    function loadProducts() {
        $('#loadingState').show();
        $('#productsGrid').hide();
        $('#emptyState').hide();

        $.ajax({
            url: BASE_URL + 'ajax.php?action=get_all_products_catalog',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    allProducts = response.data;
                    
                    
                    $('#allCount').text(allProducts.length);
                    
                    
                    filterProducts();
                } else {
                    showEmptyState();
                }
                $('#loadingState').hide();
            },
            error: function() {
                console.error('Failed to load products');
                showEmptyState();
                $('#loadingState').hide();
            }
        });
    }

    
    function filterProducts() {
        let filtered = [...allProducts];

        
        if (currentCategory !== 'all') {
            filtered = filtered.filter(p => p.category_id == currentCategory);
        }

        
        filtered = sortProducts(filtered, currentSort);

        
        const categoryName = currentCategory === 'all' ? 'All' : 
            $(`.cat-item[data-category="${currentCategory}"] span:not(.cat-badge)`).text() || 'Filtered';
        $('#itemCount').text(`${filtered.length} ${categoryName}`);

        
        displayProducts(filtered);
    }

    
    function sortProducts(products, sortType) {
        switch(sortType) {
            case 'price_low':
                return products.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
            case 'price_high':
                return products.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
            case 'newest':
                return products.sort((a, b) => new Date(b.date_created) - new Date(a.date_created));
            case 'name':
                return products.sort((a, b) => a.name.localeCompare(b.name));
            case 'featured':
            default:
                return products;
        }
    }

    
    function displayProducts(products) {
        const grid = $('#productsGrid');
        
        if (products.length === 0) {
            showEmptyState();
            grid.hide();
            return;
        }

        grid.show();
        $('#emptyState').hide();

        
        if (products.length > 100) {
            
            grid.empty();
            
            
            let batchSize = 20;
            let currentIndex = 0;
            
            function renderBatch() {
                const fragment = document.createDocumentFragment();
                const endIndex = Math.min(currentIndex + batchSize, products.length);
                
                for (let i = currentIndex; i < endIndex; i++) {
                    const card = createProductCard(products[i])[0];
                    
                    card.classList.remove('animating-in', 'animating-out', 'hidden-item');
                    
                    fragment.appendChild(card);
                }
                
                grid[0].appendChild(fragment);
                currentIndex = endIndex;
                
                if (currentIndex < products.length) {
                    requestAnimationFrame(renderBatch);
                } else {
                    
                    loadWishlistStates();
                }
            }
            
            renderBatch();
        } else {
            
            grid.empty();
            
            products.forEach((product) => {
                const card = createProductCard(product);
                
                card.removeClass('animating-in animating-out hidden-item');
                grid.append(card);
            });
            
            
            loadWishlistStates();
        }
    }

    
    function createProductCard(product) {
        const stock = parseInt(product.available) || 0;
        const isOutOfStock = stock <= 0;
        const isLowStock = stock > 0 && stock <= 5;
        const isPrescription = product.is_prescription == 1;
        const isFdaApproved = product.fda_approved == 1;
        const isNew = new Date(product.date_created) > new Date(Date.now() - 7*24*60*60*1000); 

        const imagePath = product.image_path || BASE_URL + 'uploads/product_placeholder.png';
        const price = parseFloat(product.price).toFixed(2);

        
        let badgesHTML = '';
        if (isNew && !isOutOfStock) {
            badgesHTML += '<span class="badge badge-new">New</span>';
        }
        if (isPrescription) {
            badgesHTML += '<span class="badge badge-rx"><i class="fas fa-prescription"></i> Rx</span>';
        }
        if (isFdaApproved) {
            badgesHTML += '<span class="badge badge-fda"><i class="fas fa-check"></i> FDA</span>';
        }
        if (isOutOfStock) {
            badgesHTML += '<span class="badge badge-out-of-stock">Out of Stock</span>';
        } else if (isLowStock) {
            badgesHTML += '<span class="badge badge-low-stock">' + stock + ' Left</span>';
        }

        return $(`
            <div class="glass-card ${isOutOfStock ? 'out-of-stock' : ''}" 
                 data-product-id="${product.id}"
                 tabindex="0"
                 role="article"
                 aria-label="${product.name} - ₱${price}">
                <div class="product-image-wrapper">
                    <img src="${imagePath}" 
                         alt="${product.name}"
                         loading="lazy"
                         onerror="this.src='${BASE_URL}uploads/product_placeholder.png'">
                    
                    <div class="badge-container">
                        ${badgesHTML}
                    </div>
                    
                    <div class="quick-actions">
                        <button class="quick-action-btn" data-action="wishlist" data-product-id="${product.id}" title="Add to Wishlist" aria-label="Add ${product.name} to wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="quick-action-btn" data-action="quickview" data-product-id="${product.id}" title="Quick View" aria-label="Quick view ${product.name}">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="product-details">
                    <span class="product-category-label">${product.category_name || 'General'}</span>
                    <h3 class="product-name">${product.name}</h3>
                    <div class="stock-status">
                        <span class="status-dot ${stock > 0 ? 'available' : 'out-of-stock'}"></span>
                        <span class="status-text">${stock > 0 ? stock.toLocaleString() + ' in stock' : 'Out of stock'}</span>
                    </div>
                    <div class="product-footer">
                        <span class="product-price">₱${price}</span>
                        ${isOutOfStock ? `
                        <button class="btn-add" disabled title="Out of Stock">
                            <i class="fas fa-ban"></i>
                        </button>
                        ` : `
                        <div class="cart-controls">
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn" data-action="qty-decrease" data-product-id="${product.id}">−</button>
                                <input type="number" class="qty-input" id="qty-${product.id}" value="1" min="1" max="${Math.min(stock, 99)}" data-product-id="${product.id}">
                                <button type="button" class="qty-btn" data-action="qty-increase" data-product-id="${product.id}" data-max="${Math.min(stock, 99)}">+</button>
                            </div>
                            <button class="btn-add" data-action="add-to-cart" data-product-id="${product.id}" title="Add to Cart">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                        `}
                    </div>
                </div>
            </div>
        `);
    }

    function showEmptyState() {
        $('#productsGrid').hide();
        $('#emptyState').show();
    }
});

const WISHLIST_KEY = 'pickupdrug_wishlist';

function getWishlist() {
    return JSON.parse(localStorage.getItem(WISHLIST_KEY) || '[]');
}

function toggleWishlistBtn(btn, productId) {
    let wishlist = getWishlist();
    
    if(wishlist.includes(productId)) {
        
        wishlist = wishlist.filter(id => id !== productId);
        btn.classList.remove('active');
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-solid');
            icon.classList.add('fa-regular');
        }
        showToast('Removed from wishlist', 'info');
    } else {
        
        wishlist.push(productId);
        btn.classList.add('active');
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid');
        }
        showToast('Added to wishlist ❤️', 'success');
    }
    
    localStorage.setItem(WISHLIST_KEY, JSON.stringify(wishlist));
}

function toggleWishlist(productId, event) {
    if (event) event.stopPropagation();
    const btn = event ? (event.target.closest('.quick-action-btn') || event.target.closest('.wishlist-btn')) : null;
    if (btn) {
        toggleWishlistBtn(btn, productId);
    }
}

function loadWishlistStates() {
    const wishlist = getWishlist();
    document.querySelectorAll('.quick-action-btn[data-action="wishlist"]').forEach(btn => {
        const productId = parseInt(btn.dataset.productId);
        if(wishlist.includes(productId)) {
            btn.classList.add('active');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
            }
        } else {
            btn.classList.remove('active');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
            }
        }
    });
}

function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast-notification');
    if(existingToast) existingToast.remove();
    
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = `<span style="color:${colors[type]}">${icons[type]}</span> ${message}`;
    toast.style.cssText = 'position:fixed;bottom:105px;right:25px;background:white;padding:12px 20px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.15);z-index:100000;display:flex;align-items:center;gap:8px;font-size:14px;animation:slideIn 0.3s ease;max-width:calc(100vw - 50px);box-sizing:border-box;';
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function addToCartFromCard(productId, btn) {
    const $btn = $(btn);
    const productCard = $btn.closest('.glass-card');
    const productImage = productCard.find('.product-image-wrapper img')[0];
    
    
    const qtyInput = document.getElementById('qty-' + productId);
    const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
    
    
    if (productImage) {
        flyToCart(productImage);
    }
    
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        url: BASE_URL + 'ajax.php?action=add_to_cart',
        method: 'POST',
        data: { 
            product_id: productId,
            quantity: quantity
        },
        success: function(response) {
            let resStatus = response;
            let cartId = null;
            if (typeof response === 'object' && response !== null) {
                resStatus = response.status === 'success' ? 1 : (response.status === 'out_of_stock' ? 2 : 0);
                cartId = response.cart_id;
            } else {
                try {
                    const parsed = JSON.parse(response);
                    if (parsed && parsed.status) {
                        resStatus = parsed.status === 'success' ? 1 : (parsed.status === 'out_of_stock' ? 2 : 0);
                        cartId = parsed.cart_id;
                    }
                } catch(e) {}
            }
            if (resStatus == 1) {
                if (cartId) {
                    try {
                        let selected = [];
                        const saved = sessionStorage.getItem('cartSelection');
                        if (saved) selected = JSON.parse(saved);
                        if (!selected.includes(cartId)) {
                            selected.push(cartId);
                        }
                        sessionStorage.setItem('cartSelection', JSON.stringify(selected));
                    } catch(e){}
                }
                showToast(`Added ${quantity} item${quantity > 1 ? 's' : ''} to cart!`, 'success');
                updateCartCount();
                
                if (qtyInput) qtyInput.value = 1;
            } else if (resStatus == 2) {
                showToast('Product is out of stock!', 'error');
            } else {
                showToast('Failed to add to cart', 'error');
            }
        },
        error: function() {
            showToast('Error adding to cart', 'error');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-cart-plus"></i>');
        }
    });
}

function flyToCart(image) {
    
    const cartIcon = document.querySelector('#cartIconLink') || 
                     document.querySelector('.cart-count') || 
                     document.querySelector('.fa-shopping-cart') || 
                     document.querySelector('[href*="cart"]') ||
                     document.querySelector('.nav-icon-btn svg[viewBox*="24"]');
    
    if (!cartIcon || !image) {
        console.warn('Cart icon or image not found for fly animation', {cartIcon, image});
        return;
    }
    
    console.log('Starting fly-to-cart animation');
    
    const imageRect = image.getBoundingClientRect();
    const cartRect = cartIcon.getBoundingClientRect();
    
    console.log('Animation coordinates:', {imageRect, cartRect});
    
    const flyingImg = document.createElement('img');
    flyingImg.src = image.src;
    flyingImg.style.cssText = `
        position: fixed;
        top: ${imageRect.top}px;
        left: ${imageRect.left}px;
        width: ${imageRect.width}px;
        height: ${imageRect.height}px;
        z-index: 9999;
        pointer-events: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    `;
    
    document.body.appendChild(flyingImg);
    
    setTimeout(() => {
        flyingImg.style.top = cartRect.top + 'px';
        flyingImg.style.left = cartRect.left + 'px';
        flyingImg.style.width = '30px';
        flyingImg.style.height = '30px';
        flyingImg.style.opacity = '0';
        flyingImg.style.transform = 'scale(0.3)';
    }, 10);
    
    setTimeout(() => flyingImg.remove(), 900);
}

function updateCartCount() {
    $.ajax({
        url: BASE_URL + 'ajax.php?action=get_cart_count',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            
            let count = 0;
            if (typeof response === 'object' && response.count !== undefined) {
                count = parseInt(response.count) || 0;
            } else if (typeof response === 'number') {
                count = parseInt(response) || 0;
            } else if (typeof response === 'string') {
                count = parseInt(response) || 0;
            }
            
            
            const badges = $('.cart-count, .cart-badge, #cartBadge, #cartCount');
            if (badges.length) {
                badges.text(count);
                if (count > 0) {
                    badges.show().removeClass('d-none');
                } else {
                    badges.show(); 
                }
            }
            
            
            if (typeof window.updateCartBadge === 'function') {
                window.updateCartBadge();
            }
            
            console.log('Cart count updated:', count);
        },
        error: function(xhr, status, error) {
            console.error('Failed to update cart count:', error);
        }
    });
}

function openProductModal(productId) {
    console.log('Opening modal for product:', productId);
    
    $.ajax({
        url: BASE_URL + 'ajax.php?action=get_product_details',
        method: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(response) {
            console.log('Product details response:', response);
            
            if (response.status === 'success') {
                const product = response.data;
                const stock = parseInt(product.available) || 0;
                const isOutOfStock = stock <= 0;
                const isLowStock = stock > 0 && stock <= 10;

                
                $('#modalProductImage').attr('src', product.image_path);
                $('#modalProductCategory').text(product.category_name || 'General');
                $('#modalProductName').text(product.name);
                $('#modalProductDescription').text(product.description || 'No description available.');
                $('#modalProductPrice').text('₱' + parseFloat(product.price).toFixed(2));
                
                
                let stockText = stock + ' units available';
                let stockClass = 'in-stock';
                if (isOutOfStock) {
                    stockText = 'Out of Stock';
                    stockClass = 'out-of-stock';
                } else if (isLowStock) {
                    stockText = 'Only ' + stock + ' left!';
                    stockClass = 'low-stock';
                }
                $('#modalProductStock').text(stockText).attr('class', 'modal-stock ' + stockClass);
                
                
                if (isOutOfStock) {
                    $('#modalProductBadge').text('OUT OF STOCK').show();
                } else if (isLowStock) {
                    $('#modalProductBadge').text('LOW STOCK').show();
                } else {
                    $('#modalProductBadge').hide();
                }

                
                const $modalQtyInput = $('#modalQtyInput');
                $modalQtyInput.val(1);
                $modalQtyInput.attr('max', Math.min(stock, 99));

                
                const $addBtn = $('#modalAddToCart');
                $addBtn.off('click').on('click', function() {
                    if (isOutOfStock) {
                        showToast('This product is out of stock!', 'error');
                        return;
                    }
                    addToCartFromModal(productId);
                });

                if (isOutOfStock) {
                    $addBtn.prop('disabled', true);
                } else {
                    $addBtn.prop('disabled', false);
                }

                
                const modal = $('#productModal');
                modal.addClass('active');
                
                console.log('Modal opened successfully');
            } else {
                console.error('Failed to get product details:', response);
                showToast('Failed to load product details', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading product:', error, xhr.responseText);
            showToast('Error loading product details', 'error');
        }
    });
}

function closeProductModal() {
    const modal = $('#productModal');
    modal.removeClass('active');
}

$(document).on('click', '#productModal', function(e) {
    if (e.target.id === 'productModal') {
        closeProductModal();
    }
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductModal();
    }
});

$(document).on('click', '[data-action="modal-qty-increase"]', function(e) {
    e.stopPropagation();
    const input = document.getElementById('modalQtyInput');
    if (input) {
        let currentVal = parseInt(input.value) || 1;
        const maxQty = parseInt(input.max) || 99;
        if (currentVal < maxQty) {
            input.value = currentVal + 1;
        }
    }
});

$(document).on('click', '[data-action="modal-qty-decrease"]', function(e) {
    e.stopPropagation();
    const input = document.getElementById('modalQtyInput');
    if (input) {
        let currentVal = parseInt(input.value) || 1;
        if (currentVal > 1) {
            input.value = currentVal - 1;
        }
    }
});

function addToCartFromModal(productId) {
    const $btn = $('#modalAddToCart');
    const modalImage = document.getElementById('modalProductImage');
    const modalQtyInput = document.getElementById('modalQtyInput');
    const quantity = modalQtyInput ? parseInt(modalQtyInput.value) || 1 : 1;
    
    
    if (modalImage) {
        flyToCart(modalImage);
    }
    
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

    $.ajax({
        url: BASE_URL + 'ajax.php?action=add_to_cart',
        method: 'POST',
        data: { 
            product_id: productId,
            quantity: quantity
        },
        success: function(response) {
            let resStatus = response;
            let cartId = null;
            if (typeof response === 'object' && response !== null) {
                resStatus = response.status === 'success' ? 1 : (response.status === 'out_of_stock' ? 2 : 0);
                cartId = response.cart_id;
            } else {
                try {
                    const parsed = JSON.parse(response);
                    if (parsed && parsed.status) {
                        resStatus = parsed.status === 'success' ? 1 : (parsed.status === 'out_of_stock' ? 2 : 0);
                        cartId = parsed.cart_id;
                    }
                } catch(e) {}
            }
            if (resStatus == 1) {
                if (cartId) {
                    try {
                        let selected = [];
                        const saved = sessionStorage.getItem('cartSelection');
                        if (saved) selected = JSON.parse(saved);
                        if (!selected.includes(cartId)) {
                            selected.push(cartId);
                        }
                        sessionStorage.setItem('cartSelection', JSON.stringify(selected));
                    } catch(e){}
                }
                showToast(`Added ${quantity} item${quantity > 1 ? 's' : ''} to cart!`, 'success');
                updateCartCount();
                
                if (modalQtyInput) modalQtyInput.value = 1;
                closeProductModal();
            } else if (resStatus == 2) {
                showToast('Product is out of stock!', 'error');
            } else {
                showToast('Failed to add to cart', 'error');
            }
        },
        error: function() {
            showToast('Error adding to cart', 'error');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg> Add to Cart');
        }
    });
}

function toggleModalWishlist() {
    const $btn = $('.modal-actions .btn-wishlist');
    const $icon = $btn.find('i');
    
    if ($icon.hasClass('fa-regular')) {
        $icon.removeClass('fa-regular').addClass('fa-solid');
        showToast('success', 'Added to wishlist!');
    } else {
        $icon.removeClass('fa-solid').addClass('fa-regular');
        showToast('info', 'Removed from wishlist');
    }
}

function updateCartCount() {
    $.ajax({
        url: BASE_URL + 'ajax.php?action=get_cart_count',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            
            let count = 0;
            if (response && response.status === 'success' && response.count !== undefined) {
                count = parseInt(response.count) || 0;
            } else if (typeof response === 'number') {
                count = parseInt(response) || 0;
            }
            
            
            const badges = $('.cart-count, .cart-badge, #cartBadge, #cartCount, #cart-count');
            if (badges.length) {
                badges.text(count);
                if (count > 0) {
                    badges.show().removeClass('d-none');
                } else {
                    badges.show(); 
                }
            }
            
            
            if (typeof window.updateCartBadge === 'function') {
                window.updateCartBadge();
            }
            
            console.log('Cart count updated:', count);
        },
        error: function(xhr, status, error) {
            console.error('Failed to update cart count:', error);
        }
    });
}

