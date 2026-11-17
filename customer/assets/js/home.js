
let currentSlideIndex = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function showSlide(n) {
    if (n >= slides.length) currentSlideIndex = 0;
    if (n < 0) currentSlideIndex = slides.length - 1;
    
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    slides[currentSlideIndex].classList.add('active');
    dots[currentSlideIndex].classList.add('active');
}

function changeSlide(n) {
    currentSlideIndex += n;
    showSlide(currentSlideIndex);
}

function currentSlide(n) {
    currentSlideIndex = n;
    showSlide(currentSlideIndex);
}

setInterval(() => {
    currentSlideIndex++;
    showSlide(currentSlideIndex);
}, 5000);

function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const search = document.getElementById('searchFilter').value;
    
    
    const container = document.getElementById('productsContainer');
    container.innerHTML = getSkeletonHTML(8);
    
    
    $.ajax({
        url: './ajax.php?action=filter_products',
        method: 'POST',
        data: {
            category_id: category,
            search: search
        },
        dataType: 'html',
        success: function(resp) {
            container.innerHTML = resp;
            
            if(typeof loadWishlistStates === 'function') {
                loadWishlistStates();
            }
        },
        error: function(xhr, status, error) {
            console.error('Filter products error:', error);
            console.error('Response:', xhr.responseText);
            container.innerHTML = '<div class="no-results"><h3>Error Loading Products</h3><p>Please try again later.</p></div>';
        }
    });
}

function getSkeletonHTML(count) {
    let html = '';
    for(let i = 0; i < count; i++) {
        html += `
            <div class="skeleton-card">
                <div class="skeleton-image"></div>
                <div class="skeleton-content">
                    <div class="skeleton-line short"></div>
                    <div class="skeleton-line medium"></div>
                    <div class="skeleton-line"></div>
                    <div class="skeleton-btn"></div>
                </div>
            </div>
        `;
    }
    return html;
}

function clearFilters() {
    document.getElementById('filterForm').reset();
    applyFilters();
}

let searchTimeout;
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyFilters();
            }, 500);
        });
    }
    
    if(categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            applyFilters();
        });
    }
});

function setView(view) {
    const container = document.getElementById('productsContainer');
    const buttons = document.querySelectorAll('.view-btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.closest('.view-btn').classList.add('active');
    
    if(view === 'list') {
        container.classList.add('list-view');
    } else {
        container.classList.remove('list-view');
    }
}

function addToCart(productId, quantity = 1, event = null) {
    console.log('Adding to cart - Product ID:', productId, 'Qty:', quantity);
    
    
    const button = event ? event.target.closest('.btn-add-to-cart') : null;
    const productCard = button ? button.closest('.product-card') : null;
    const productImage = productCard ? productCard.querySelector('.product-image img') : null;
    
    
    if(productImage) {
        flyToCart(productImage);
    }
    
    $.ajax({
        url: 'ajax.php?action=add_to_cart',
        method: 'POST',
        data: { product_id: productId, quantity: quantity || 1 },
        success: function(resp) {
            console.log('Add to cart response:', resp);
            if(resp == 1 || (typeof resp === 'object' && resp.status === 'success')) {
                
                console.log('Cart item added successfully, updating count...');
                if(typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge();
                } else {
                    updateCartCount();
                }
                const msg = quantity > 1 ? `Added ${quantity} items to cart!` : 'Product added to cart!';
                if(typeof showToast === 'function') {
                    showToast(msg, 'success');
                }
            } else {
                console.error('Failed to add to cart. Response:', resp);
                alert('Failed to add item to cart. Please try again.');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            alert('Error adding to cart: ' + error);
        }
    });
}

function flyToCart(image) {
    
    const cartIcon = document.querySelector('#cartIconLink') || document.querySelector('.fa-shopping-cart');
    if(!cartIcon || !image) return;
    
    
    const imageRect = image.getBoundingClientRect();
    const cartRect = cartIcon.getBoundingClientRect();
    
    
    const flyingImg = document.createElement('img');
    flyingImg.src = image.src;
    flyingImg.className = 'flying-to-cart';
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
    
    
    if(cartIcon) {
        cartIcon.style.animation = 'cartShake 0.5s ease';
        setTimeout(() => {
            cartIcon.style.animation = '';
        }, 500);
    }
    
    
    setTimeout(() => {
        flyingImg.remove();
    }, 800);
}

let currentProductId = null;

function viewProduct(productId) {
    currentProductId = productId;
    
    
    $.ajax({
        url: 'ajax.php?action=get_product_details',
        method: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(product) {
            if(product.status === 'success') {
                
                saveToRecentlyViewed(product.data);
                
                
                const modal = document.getElementById('productModal');
                if(modal) {
                    $('#modalProductImage').attr('src', product.data.image_path);
                    $('#modalProductImage').attr('alt', product.data.name);
                    $('#modalProductCategory').text(product.data.category_name);
                    $('#modalProductName').text(product.data.name);
                    
                    
                    const stock = parseInt(product.data.available);
                    const stockEl = $('#modalProductStock');
                    if(stock <= 0) {
                        stockEl.html('<span class="text-danger"><i class="fas fa-times-circle"></i> Out of Stock</span>');
                    } else if(stock <= 5) {
                        stockEl.html(`<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Only ${stock} left in stock!</span>`);
                    } else {
                        stockEl.html(`<span class="text-success"><i class="fas fa-check-circle"></i> In Stock: ${stock}</span>`);
                    }

                    $('#modalProductDescription').text(product.data.description);
                    $('#modalProductPrice').text('₱' + product.data.price);
                    
                    
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                } else {
                    console.error('Modal element not found');
                }
            } else {
                alert('Product not found');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Failed to load product details');
        }
    });
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    if(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    currentProductId = null;
}

function addToCartFromModal() {
    if(currentProductId) {
        const modalImage = document.getElementById('modalProductImage');
        const qtyInput = document.getElementById('modalQtyInput');
        const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
        if(modalImage) {
            flyToCart(modalImage);
        }
        addToCart(currentProductId, quantity, null);
        setTimeout(() => {
            closeProductModal();
        }, 300);
    }
}

document.addEventListener('click', function(e) {
    if(e.target.id === 'productModal') {
        closeProductModal();
    }
});

document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
        const modal = document.getElementById('productModal');
        if(modal && modal.classList.contains('active')) {
            closeProductModal();
        }
    }
});

function updateCartCount() {
    console.log('Updating cart count...');
    $.ajax({
        url: 'ajax.php?action=get_cart_count',
        success: function(resp) {
            console.log('Cart count response:', resp);
            try {
                const data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                const count = data.count || 0;
                console.log('Setting cart count to:', count);
                $('.cart-count').text(count);
            } catch(e) {
                console.error('Error parsing cart count:', e);
                $('.cart-count').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('Cart count error:', error);
        }
    });
}

const RECENTLY_VIEWED_KEY = 'pickupdrug_recently_viewed';
const MAX_RECENT_ITEMS = 10;

function saveToRecentlyViewed(product) {
    let recentItems = JSON.parse(localStorage.getItem(RECENTLY_VIEWED_KEY) || '[]');
    
    
    recentItems = recentItems.filter(item => item.id !== product.id);
    
    
    recentItems.unshift({
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.image_path,
        timestamp: Date.now()
    });
    
    
    recentItems = recentItems.slice(0, MAX_RECENT_ITEMS);
    
    localStorage.setItem(RECENTLY_VIEWED_KEY, JSON.stringify(recentItems));
    renderRecentlyViewed();
}

function renderRecentlyViewed() {
    const recentItems = JSON.parse(localStorage.getItem(RECENTLY_VIEWED_KEY) || '[]');
    const section = document.getElementById('recentlyViewedSection');
    const grid = document.getElementById('recentlyViewedGrid');
    
    if(recentItems.length === 0) {
        section.style.display = 'none';
        return;
    }
    
    section.style.display = 'block';
    grid.innerHTML = recentItems.map(item => `
        <div class="recent-product-card" onclick="viewProduct(${item.id})">
            <img src="${item.image}" alt="${item.name}" class="recent-product-img" onerror="this.src='uploads/no-image.png'">
            <div class="recent-product-name">${item.name}</div>
            <div class="recent-product-price">₱${parseFloat(item.price).toLocaleString('en-PH', {minimumFractionDigits: 2})}</div>
        </div>
    `).join('');
}

function clearRecentlyViewed() {
    if(confirm('Clear your recently viewed history?')) {
        localStorage.removeItem(RECENTLY_VIEWED_KEY);
        renderRecentlyViewed();
    }
}

const WISHLIST_KEY = 'pickupdrug_wishlist';

function getWishlist() {
    return JSON.parse(localStorage.getItem(WISHLIST_KEY) || '[]');
}

function toggleWishlist(productId, event) {
    event.stopPropagation();
    const btn = event.target.closest('.wishlist-btn');
    let wishlist = getWishlist();
    
    if(wishlist.includes(productId)) {
        
        wishlist = wishlist.filter(id => id !== productId);
        btn.classList.remove('active');
        showToast('Removed from wishlist', 'info');
    } else {
        
        wishlist.push(productId);
        btn.classList.add('active');
        showToast('Added to wishlist ❤️', 'success');
    }
    
    localStorage.setItem(WISHLIST_KEY, JSON.stringify(wishlist));
}

function loadWishlistStates() {
    const wishlist = getWishlist();
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        const productId = parseInt(btn.dataset.productId);
        if(wishlist.includes(productId)) {
            btn.classList.add('active');
        }
    });
}

function showToast(message, type = 'success', title = '') {
    
    const existingToast = document.querySelector('.toast-notification');
    if(existingToast) existingToast.remove();
    
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    
    const defaultTitles = {
        success: 'Success!',
        error: 'Error!',
        warning: 'Warning!',
        info: 'Info'
    };
    
    const toastTitle = title || defaultTitles[type] || '';
    
    
    const bgColors = {
        success: 'linear-gradient(135deg, #0d9488 0%, #14b8a6 100%)',
        error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        warning: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
        info: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'
    };
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 1.2rem;">${icons[type] || icons.info}</span>
            <div>
                ${toastTitle ? `<div style="font-weight: 600; font-size: 0.9rem;">${toastTitle}</div>` : ''}
                <div style="font-size: 0.85rem; opacity: 0.95;">${message}</div>
            </div>
        </div>
    `;
    toast.style.cssText = `
        position: fixed;
        bottom: 105px;
        right: 25px;
        background: ${bgColors[type] || bgColors.info};
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 100000;
        width: fit-content;
        min-width: 220px;
        max-width: min(360px, calc(100vw - 55px));
        box-sizing: border-box;
        animation: toastSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'toastSlideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

const toastStyleSheet = document.createElement('style');
toastStyleSheet.textContent = `
    @keyframes toastSlideIn {
        from { opacity: 0; transform: translateX(40px) scale(0.95); }
        to { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toastSlideOut {
        from { opacity: 1; transform: translateX(0) scale(1); }
        to { opacity: 0; transform: translateX(40px) scale(0.95); }
    }
`;
document.head.appendChild(toastStyleSheet);

document.addEventListener('DOMContentLoaded', function() {
    renderRecentlyViewed();
    loadWishlistStates();
});

