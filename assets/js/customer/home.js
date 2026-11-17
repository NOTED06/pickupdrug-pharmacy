

(function() {
    if (!window.BASE_URL) {
        if (window.base_url) window.BASE_URL = window.base_url;
        else if (window._base_url_) window.BASE_URL = window._base_url_;
        else {
            console.warn('[Home.js] window.BASE_URL not defined');
            window.BASE_URL = window.location.origin + '/pickupdrug/';
        }
    }
})();

let slideIndex = 1;
let slideTimer;

function showSlide(n) {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    
    if (slides.length === 0) return;
    
    if (n > slides.length) slideIndex = 1;
    if (n < 1) slideIndex = slides.length;
    
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    slides[slideIndex - 1].classList.add('active');
    if (dots[slideIndex - 1]) {
        dots[slideIndex - 1].classList.add('active');
    }
    
    
    clearTimeout(slideTimer);
    slideTimer = setTimeout(() => changeSlide(1), 5000);
}

function changeSlide(n) {
    showSlide(slideIndex += n);
}

function currentSlide(n) {
    showSlide(slideIndex = n);
}

document.addEventListener('DOMContentLoaded', function() {
    showSlide(1);
});

function filterByCategory(categoryId) {
    console.log('[filterByCategory] Navigating to categories page with category ID:', categoryId);
    
    window.location.href = window.BASE_URL + '?p=customer/categories&category=' + encodeURIComponent(categoryId);
}

function getSkeletonHTML() {
    return `
        <div class="glass-card skeleton-card">
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

function addToCart(productId, quantity = 1, event = null) {
    if (event) {
        event.stopPropagation();
    }
    
    
    const productCard = document.querySelector(`[data-product-id="${productId}"]`) || 
                        event?.target?.closest('.product-card') ||
                        event?.target?.closest('.glass-card');
    
    const isPrescription = productCard?.querySelector('.prescription-badge, .badge-rx');
    
    if (isPrescription) {
        showToast('This product requires a prescription. Please upload your prescription first.', 'warning');
        return;
    }
    
    
    if (event) {
        flyToCart(event);
    }
    
    
    fetch(window.BASE_URL + 'ajax.php?action=add_to_cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.text())
    .then(data => {
        console.log('[addToCart] Response:', data);
        
        let result;
        try {
            result = JSON.parse(data);
        } catch(e) {
            
            result = { status: data.trim() === '1' ? 'success' : 'failed' };
            if (data.trim() === '2') {
                result.message = 'Product is out of stock';
            }
        }
        
        if (result.status === 'success' || data.trim() === '1') {
            const cartId = result.cart_id;
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
            const toastMsg = (result && result.msg) ? result.msg : (quantity > 1 ? `Added ${quantity} items to cart!` : 'Product added to cart!');
            showToast(toastMsg, 'success');
            updateCartCount();
        } else if (data.trim() === '2') {
            showToast('Product is out of stock', 'warning');
        } else {
            showToast(result.message || 'Failed to add to cart', 'error');
        }
    })
    .catch(error => {
        console.error('[addToCart] Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
    });
}

function flyToCart(eventOrImage) {
    
    let productImage;
    
    if (eventOrImage instanceof HTMLImageElement) {
        
        productImage = eventOrImage;
    } else if (eventOrImage && eventOrImage.target) {
        
        const button = eventOrImage.target.closest('button') || eventOrImage.target;
        const productCard = button?.closest('.product-card') || button?.closest('.glass-card');
        productImage = productCard?.querySelector('img');
    } else {
        console.warn('[flyToCart] Invalid argument:', eventOrImage);
        return;
    }
    
    
    const cartIcon = document.querySelector('#cartBadge')?.parentElement || 
                     document.querySelector('.cart-link') || 
                     document.querySelector('[data-action="toggle-cart"]') ||
                     document.querySelector('[data-cart-icon]');
    
    if (!productImage || !cartIcon) {
        console.warn('[flyToCart] Missing elements:', { productImage: !!productImage, cartIcon: !!cartIcon });
        return;
    }
    
    
    const flyingImage = productImage.cloneNode(true);
    flyingImage.style.position = 'fixed';
    flyingImage.style.zIndex = '10000';
    flyingImage.style.width = '80px';
    flyingImage.style.height = '80px';
    flyingImage.style.objectFit = 'contain';
    flyingImage.style.borderRadius = '10px';
    flyingImage.style.boxShadow = '0 4px 15px rgba(0,0,0,0.2)';
    flyingImage.style.pointerEvents = 'none';
    flyingImage.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
    
    
    const imgRect = productImage.getBoundingClientRect();
    const cartRect = cartIcon.getBoundingClientRect();
    
    
    flyingImage.style.left = imgRect.left + 'px';
    flyingImage.style.top = imgRect.top + 'px';
    
    document.body.appendChild(flyingImage);
    
    
    requestAnimationFrame(() => {
        flyingImage.style.left = cartRect.left + 'px';
        flyingImage.style.top = cartRect.top + 'px';
        flyingImage.style.width = '20px';
        flyingImage.style.height = '20px';
        flyingImage.style.opacity = '0';
        flyingImage.style.transform = 'rotate(360deg)';
    });
    
    
    setTimeout(() => {
        flyingImage.remove();
        
        cartIcon.style.animation = 'cartShake 0.5s ease';
        setTimeout(() => {
            cartIcon.style.animation = '';
        }, 500);
    }, 800);
}

function viewProduct(productId, event = null) {
    if (event) {
        event.stopPropagation();
    }
    
    
    saveToRecentlyViewed(productId);
    
    
    fetch(window.BASE_URL + 'ajax.php?action=get_product_details', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('[viewProduct] Response:', data);
            
            if (data.status === 'success' && data.product) {
                openProductModal(data.product);
            } else if (data.status === 'success' && data.data) {
                openProductModal(data.data);
            } else if (data.id) {
                
                openProductModal(data);
            } else {
                console.error('[viewProduct] Unexpected response format:', data);
                showToast('Failed to load product details', 'error');
            }
        })
        .catch(error => {
            console.error('[viewProduct] Error:', error);
            showToast('Something went wrong loading product', 'error');
        });
}

function openProductModal(product) {
    const modal = document.getElementById('productModal');
    if (!modal) {
        console.warn('[Modal] Product modal element not found');
        return;
    }
    
    
    modal.dataset.productId = product.id;
    
    
    const modalImage = document.getElementById('modalProductImage');
    const modalName = document.getElementById('modalProductName');
    const modalCategory = document.getElementById('modalProductCategory');
    const modalDescription = document.getElementById('modalProductDescription');
    const modalPrice = document.getElementById('modalProductPrice');
    const modalQtyInput = document.getElementById('modalQtyInput');
    const modalStock = document.getElementById('modalProductStock');
    const modalAddCart = modal.querySelector('.modal-add-cart');
    
    
    if (modalImage) {
        
        const imagePath = product.image_path || product.image || '';
        modalImage.src = imagePath.startsWith('http') ? imagePath : 
                         (imagePath ? window.BASE_URL + imagePath : window.BASE_URL + 'uploads/products/default.png');
    }
    if (modalName) modalName.textContent = product.name || 'Unknown Product';
    if (modalCategory) modalCategory.textContent = product.category_name || 'General';
    if (modalDescription) modalDescription.textContent = product.description || 'No description available';
    if (modalPrice) modalPrice.textContent = '₱' + parseFloat(product.price || 0).toFixed(2);
    if (modalQtyInput) modalQtyInput.value = 1;
    
    
    const modalBrand = document.getElementById('modalProductBrand');
    const modalDosage = document.getElementById('modalProductDosage');
    const modalUnit = document.getElementById('modalProductUnit');
    
    if (modalBrand) {
        if (product.brand) {
            modalBrand.querySelector('span').textContent = product.brand;
            modalBrand.style.display = 'inline-flex';
        } else {
            modalBrand.style.display = 'none';
        }
    }
    if (modalDosage) {
        if (product.dose) {
            modalDosage.querySelector('span').textContent = product.dose;
            modalDosage.style.display = 'inline-flex';
        } else {
            modalDosage.style.display = 'none';
        }
    }
    if (modalUnit) {
        if (product.unit) {
            modalUnit.querySelector('span').textContent = product.unit;
            modalUnit.style.display = 'inline-flex';
        } else {
            modalUnit.style.display = 'none';
        }
    }
    
    
    const stockQuantity = parseInt(product.quantity || product.available || 0);
    if (modalStock) {
        if (stockQuantity <= 0) {
            modalStock.innerHTML = '<span style="color: #ef4444;">Out of Stock</span>';
            if (modalAddCart) modalAddCart.disabled = true;
        } else if (stockQuantity <= 5) {
            modalStock.innerHTML = `<span style="color: #f59e0b;">Only ${stockQuantity} left in stock</span>`;
            if (modalAddCart) modalAddCart.disabled = false;
        } else {
            modalStock.innerHTML = `<span style="color: #10b981;">${stockQuantity} in stock</span>`;
            if (modalAddCart) modalAddCart.disabled = false;
        }
    }
    
    
    const badgeContainer = modal.querySelector('.modal-badge');
    if (product.prescription_required == 1) {
        badgeContainer.innerHTML = '<i class="fas fa-prescription"></i> Prescription Required';
        badgeContainer.style.display = 'inline-block';
    } else {
        badgeContainer.style.display = 'none';
    }
    
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function addToCartFromModal() {
    
    const modal = document.getElementById('productModal');
    if (!modal) {
        console.warn('[Modal] Cannot add to cart - modal not found');
        return;
    }
    
    const productId = modal.dataset.productId;
    const qtyInput = document.getElementById('modalQtyInput');
    const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
    
    if (!productId) {
        console.warn('[Modal] Cannot add to cart - no product ID');
        showToast('Unable to add product to cart', 'error');
        return;
    }
    
    
    const modalImage = document.getElementById('modalProductImage');
    if (modalImage && typeof flyToCart === 'function') {
        flyToCart(modalImage);
    }
    
    
    closeProductModal();
    
    
    addToCart(productId, quantity);
}

function modalQtyChange(change) {
    const input = document.getElementById('modalQtyInput');
    if (!input) {
        console.warn('[Modal] Quantity input not found');
        return;
    }
    let newVal = parseInt(input.value) + change;
    if (newVal < 1) newVal = 1;
    if (newVal > 99) newVal = 99;
    input.value = newVal;
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('productModal');
    if (modal && e.target === modal) {
        closeProductModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductModal();
    }
});

const RECENTLY_VIEWED_KEY = 'pickupdrug_recently_viewed';
const MAX_RECENTLY_VIEWED = 10;

function saveToRecentlyViewed(productId) {
    let recentlyViewed = JSON.parse(localStorage.getItem(RECENTLY_VIEWED_KEY) || '[]');
    
    
    recentlyViewed = recentlyViewed.filter(id => id !== productId);
    
    
    recentlyViewed.unshift(productId);
    
    
    recentlyViewed = recentlyViewed.slice(0, MAX_RECENTLY_VIEWED);
    
    localStorage.setItem(RECENTLY_VIEWED_KEY, JSON.stringify(recentlyViewed));
}

function getRecentlyViewed() {
    return JSON.parse(localStorage.getItem(RECENTLY_VIEWED_KEY) || '[]');
}

function renderRecentlyViewed() {
    const container = document.getElementById('recentlyViewedGrid');
    const section = document.querySelector('.recently-viewed-section');
    
    if (!container || !section) return;
    
    const recentIds = getRecentlyViewed();
    
    if (recentIds.length === 0) {
        section.style.display = 'none';
        return;
    }
    
    section.style.display = 'block';
    
    
    fetch(window.BASE_URL + 'ajax.php?action=get_products_by_ids', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ids=' + recentIds.join(',')
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.products) {
            container.innerHTML = data.products.map(product => `
                <div class="recent-product-card" onclick="viewProduct(${product.id})">
                    <img src="${window.BASE_URL}uploads/products/${product.image || 'default.png'}" 
                         alt="${product.name}" 
                         class="recent-product-img">
                    <div class="recent-product-name">${product.name}</div>
                    <div class="recent-product-price">₱${parseFloat(product.price).toFixed(2)}</div>
                </div>
            `).join('');
        }
    })
    .catch(error => {
        console.error('Error loading recently viewed:', error);
    });
}

function clearRecentlyViewed() {
    localStorage.removeItem(RECENTLY_VIEWED_KEY);
    const section = document.querySelector('.recently-viewed-section');
    if (section) section.style.display = 'none';
    showToast('Browsing history cleared', 'success');
}

document.addEventListener('DOMContentLoaded', function() {
    renderRecentlyViewed();
});

const WISHLIST_KEY = 'pickupdrug_wishlist';

function getWishlist() {
    return JSON.parse(localStorage.getItem(WISHLIST_KEY) || '[]');
}

function toggleWishlist(productId, event = null) {
    if (event) {
        event.stopPropagation();
    }
    
    let wishlist = getWishlist();
    const index = wishlist.indexOf(productId);
    
    const btn = document.querySelector(`[data-wishlist-id="${productId}"]`);
    
    if (index > -1) {
        
        wishlist.splice(index, 1);
        if (btn) btn.classList.remove('active');
        showToast('Removed from wishlist', 'info');
    } else {
        
        wishlist.push(productId);
        if (btn) btn.classList.add('active');
        showToast('Added to wishlist', 'success');
    }
    
    localStorage.setItem(WISHLIST_KEY, JSON.stringify(wishlist));
}

function loadWishlistStates() {
    const wishlist = getWishlist();
    document.querySelectorAll('[data-wishlist-id]').forEach(btn => {
        const productId = parseInt(btn.dataset.wishlistId);
        if (wishlist.includes(productId)) {
            btn.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadWishlistStates();
});

function showToast(message, type = 'info') {
    
    if (typeof window.showToastNotification === 'function') {
        window.showToastNotification(message, type);
        return;
    }
    
    
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `custom-pill-toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${getToastIcon(type)}"></i>
        <span>${message}</span>
    `;
    
    toast.style.cssText = `
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 12px 20px !important;
        background: ${getToastBackground(type)} !important;
        color: white !important;
        border-radius: 10px !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
        animation: slideIn 0.3s ease !important;
        margin-bottom: 10px !important;
        flex-basis: auto !important;
        height: auto !important;
        min-height: unset !important;
        width: fit-content !important;
        max-width: min(360px, calc(100vw - 55px)) !important;
        box-sizing: border-box !important;
        pointer-events: auto !important;
        font-size: 14px !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        font-weight: 500 !important;
        line-height: 1.4 !important;
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
        position: fixed;
        bottom: 105px;
        right: 25px;
        z-index: 100000;
        display: flex;
        flex-direction: column-reverse;
        align-items: flex-end;
        pointer-events: none;
    `;
    document.body.appendChild(container);
    return container;
}

function getToastIcon(type) {
    switch (type) {
        case 'success': return 'check-circle';
        case 'error': return 'times-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

function getToastBackground(type) {
    switch (type) {
        case 'success': return '#10b981';
        case 'error': return '#ef4444';
        case 'warning': return '#f59e0b';
        default: return '#3b82f6';
    }
}

function updateCartCount() {
    fetch(window.BASE_URL + 'ajax.php?action=get_cart_count')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const badges = document.querySelectorAll('.cart-badge, .cart-count, [data-cart-count]');
                badges.forEach(badge => {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                });
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

function increaseQty(productId, event) {
    if (event) event.stopPropagation();
    const input = document.querySelector(`[data-qty-input="${productId}"]`);
    if (input) {
        let val = parseInt(input.value) + 1;
        if (val > 99) val = 99;
        input.value = val;
    }
}

function decreaseQty(productId, event) {
    if (event) event.stopPropagation();
    const input = document.querySelector(`[data-qty-input="${productId}"]`);
    if (input) {
        let val = parseInt(input.value) - 1;
        if (val < 1) val = 1;
        input.value = val;
    }
}

function goToCategory(categoryId) {
    window.location.href = window.BASE_URL + '?p=customer/categories&category=' + encodeURIComponent(categoryId);
}

document.addEventListener('DOMContentLoaded', function() {
    
    updateCartCount();
    
    
    if (!document.getElementById('toastAnimations')) {
        const style = document.createElement('style');
        style.id = 'toastAnimations';
        style.textContent = `
            @keyframes slideIn {
                from { opacity: 0; transform: translateX(50px); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes slideOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(50px); }
            }
        `;
        document.head.appendChild(style);
    }
});
