<?php

if (isset($_SESSION['userdata']['login_type']) && $_SESSION['userdata']['login_type'] == 2) {
    
    echo '<script>window.location.href = "' . base_url . '?p=customer/home";</script>';
    exit;
}
?>
<style>

.contact {
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}
.footer {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}
body {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}
</style>

<script src="https://accounts.google.com/gsi/client" async defer></script>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<nav class="navbar" id="navbar">
    <div class="container nav-container">
        <div class="logo">
            <a href="<?php echo base_url ?>"><?php echo htmlspecialchars($_settings->info('name')) ?: 'PharmaCare'; ?></a>
        </div>
        <ul class="nav-menu" id="navMenu">
            <li><a href="#hero">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#products">Products</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><button class="btn-login" onclick="toggleLoginModal()">Login</button></li>
        </ul>
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</nav>

    
    <section class="hero" id="hero">
        <div class="floating-capsules">
            
            <div class="capsule capsule-1 draggable-pill" draggable="true">
                <div class="capsule-half top"></div>
                <div class="capsule-half bottom"></div>
            </div>
            <div class="capsule capsule-2 draggable-pill" draggable="true">
                <div class="capsule-half top"></div>
                <div class="capsule-half bottom"></div>
            </div>
            <div class="capsule capsule-3 draggable-pill" draggable="true">
                <div class="capsule-half top"></div>
                <div class="capsule-half bottom"></div>
            </div>
            <div class="capsule capsule-4 draggable-pill" draggable="true">
                <div class="capsule-half top"></div>
                <div class="capsule-half bottom"></div>
            </div>
            <div class="capsule capsule-5 draggable-pill" draggable="true">
                <div class="capsule-half top"></div>
                <div class="capsule-half bottom"></div>
            </div>
            <div class="capsule capsule-6 draggable-pill" draggable="true">
                <div class="capsule-half top"></div>
                <div class="capsule-half bottom"></div>
            </div>
        </div>
        <div class="container hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Your Health, Delivered.</h1>
                <p class="hero-subtitle">Order medicines online, pick up at your nearest store. Fast, convenient, and hassle-free healthcare right at your fingertips.</p>
                <div class="hero-buttons">
                    <a href="javascript:void(0)" onclick="toggleLoginModal()" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Shop Now
                    </a>
                    <a href="#products" class="btn btn-secondary">
                        <i class="fas fa-pills"></i> Browse Products
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-visual">
                    <div class="ai-capsule-container">
                        <div class="ai-capsule-character fever" id="aiCharacter"> 
                            <div class="capsule-glow"></div>
                            
                            
                            <div class="character-hair"></div>
                            <div class="character-ears">
                                <div class="ear left"></div>
                                <div class="ear right"></div>
                            </div>

                            <div class="capsule-top"></div> 
                            <div class="capsule-bottom"></div> 
                            
                            <div class="doctor-coat-detail">
                                <div class="coat-collar"></div>
                                <div class="tie"></div>
                            </div>

                            <div class="capsule-face">
                                <div class="capsule-eyes">
                                    <div class="eye left"></div>
                                    <div class="eye right"></div>
                                </div>
                                <div class="capsule-mouth"></div>
                            </div>
                            
                            <div class="sweat-drop s1"></div>
                            <div class="sweat-drop s2"></div>
                        </div>
                        
                        <div class="ai-advice-bubble" id="aiBubble">
                            <p id="aiAdviceText">I don't feel so good... 🤒</p>
                        </div>

                        
                        <div class="medicine-pill draggable-pill" id="medicinePill" draggable="true" title="Drag me to the character!">
                            <div class="pill-half blue"></div>
                            <div class="pill-half white"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Why Shop With Us?</h2>
                <p>Experience healthcare shopping made easy and convenient</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <circle cx="24" cy="24" r="18" stroke="currentColor" stroke-width="2" fill="none"/>
                            <path d="M24 14V24L30 30" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Fast Pickup</h3>
                    <p>Order online and pick up at your nearest branch within hours. No more waiting in long queues!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <path d="M12 16H36L33 36H15L12 16Z" stroke="currentColor" stroke-width="2" fill="none"/>
                            <circle cx="18" cy="40" r="2" fill="currentColor"/>
                            <circle cx="30" cy="40" r="2" fill="currentColor"/>
                            <path d="M12 16L10 8H6" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Easy Ordering</h3>
                    <p>Browse thousands of products, add to cart, and checkout in minutes. Simple and hassle-free!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <path d="M24 4L6 12V22C6 33 14 43 24 46C34 43 42 33 42 22V12L24 4Z" stroke="currentColor" stroke-width="2" fill="none"/>
                            <path d="M16 24L22 30L32 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Genuine Products</h3>
                    <p>100% authentic medicines sourced directly from licensed manufacturers. Your safety is our priority.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <circle cx="24" cy="20" r="8" stroke="currentColor" stroke-width="2" fill="none"/>
                            <path d="M10 42C10 34 16 28 24 28C32 28 38 34 38 42" stroke="currentColor" stroke-width="2"/>
                            <path d="M32 14L36 10M36 14L32 10" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>AI Health Assistant</h3>
                    <p>Chat with our AI-powered assistant for medicine recommendations and health tips 24/7.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <rect x="8" y="8" width="32" height="32" rx="4" stroke="currentColor" stroke-width="2" fill="none"/>
                            <path d="M16 20H32M16 28H26" stroke="currentColor" stroke-width="2"/>
                            <circle cx="32" cy="32" r="6" fill="currentColor" opacity="0.3"/>
                            <path d="M30 32L32 34L36 30" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Order Tracking</h3>
                    <p>Real-time notifications when your order is confirmed, ready for pickup, and completed.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <path d="M24 8L28 16L38 18L30 26L32 36L24 32L16 36L18 26L10 18L20 16L24 8Z" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <h3>Best Prices</h3>
                    <p>Clear pricing across our available pharmacy products.</p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="services" id="services">
        <div class="container">
            <div class="services-content">
                <div class="services-text">
                    <h2>How It Works</h2>
                    <p>Getting your medicines has never been easier. Follow these simple steps to order and pick up your healthcare essentials.</p>
                    <ul class="services-list">
                        <li>🔍 Browse our wide selection of medicines</li>
                        <li>🛒 Add items to your cart</li>
                        <li>📍 Choose your nearest pickup branch</li>
                        <li>✅ Place your order securely</li>
                        <li>📱 Receive notification when ready</li>
                        <li>🏪 Pick up at your convenience</li>
                    </ul>
                </div>
                <div class="services-visual">
                    <div class="visual-card card-1">
                        <h3>Browse</h3>
                        <p>Explore thousands of medicines and healthcare products.</p>
                    </div>
                    <div class="visual-card card-2">
                        <h3>Order</h3>
                        <p>Add to cart and checkout in just a few clicks.</p>
                    </div>
                    <div class="visual-card card-3">
                        <h3>Get Notified</h3>
                        <p>We'll alert you when your order is ready for pickup.</p>
                    </div>
                    <div class="visual-card card-4">
                        <h3>Pick Up</h3>
                        <p>Visit your chosen branch and collect your order hassle-free.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="products" id="products">
        <div class="container">
            <div class="product-slider-container">
                <button class="slider-btn prev" onclick="moveSlider(-1)">&#10094;</button>
                <div class="product-slider" id="productSlider">
                    <?php 
                    $products = $conn->query("SELECT p.*, c.name as category_name, 
                                             COALESCE(p.is_prescription, 0) as is_prescription,
                                             COALESCE(p.fda_approved, 1) as fda_approved,
                                             (SELECT SUM(quantity) FROM stock_list WHERE product_id = p.id) as stock 
                                             FROM product_list p 
                                             INNER JOIN category_list c ON p.category_id = c.id 
                                             WHERE p.delete_flag = 0 ORDER BY RAND() LIMIT 10");
                    
                    while($row = $products->fetch_assoc()):
                        $stock = $row['stock'] > 0 ? $row['stock'] : 0;
                        $isPrescription = isset($row['is_prescription']) && $row['is_prescription'] == 1;
                        $isFdaApproved = !isset($row['fda_approved']) || $row['fda_approved'] == 1;
                    ?>
                    <div class="product-card" data-category="<?php echo $row['category_id'] ?>">
                        <div class="product-img">
                            <img src="<?php echo validate_image($row['image_path']) ?>" alt="<?php echo $row['name'] ?>">
                            
                            <?php if($isPrescription): ?>
                            <span class="prescription-badge"><i class="fas fa-prescription"></i> Rx</span>
                            <?php endif; ?>
                            
                            <?php if($isFdaApproved): ?>
                            <span class="fda-badge"><i class="fas fa-check-circle"></i> FDA</span>
                            <?php endif; ?>
                            
                            <div class="price-tag">₱<?php echo number_format($row['price'], 2) ?></div>
                            <button class="landing-view-icon" onclick="viewLandingProduct(<?php echo $row['id'] ?>)" title="View Details">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <div class="product-info">
                            <h3><?php echo $row['name'] ?></h3>
                            <p class="brand"><?php echo $row['brand'] ?></p>
                            <div class="stock-status">
                                <span class="status-dot <?php echo $stock > 0 ? 'available' : 'out-of-stock' ?>"></span>
                                <span class="status-text"><?php echo $stock > 0 ? number_format($stock) . ' in stock' : 'Out of stock' ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <button class="slider-btn next" onclick="moveSlider(1)">&#10095;</button>
            </div>
        </div>
    </section>

    
    <section class="about" id="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-image">
                    <div class="about-visual">
                        <div class="instruction-container">
                            
                            <div class="phone-mockup">
                                <div class="phone-notch"></div>
                                <div class="screen-content">
                                    
                                    <div class="chat-header">
                                        <div class="ai-status-dot"></div>
                                        <span>AI Assistant</span>
                                    </div>
                                    
                                    <div class="chat-feed">
                                        
                                        <div class="message user-msg">
                                            <p>I have a high fever and headache 🤒</p>
                                        </div>
                                        
                                        
                                        <div class="ai-processing">
                                            <div class="ai-avatar-small"></div>
                                            <div class="typing-indicator">
                                                <span></span><span></span><span></span>
                                            </div>
                                        </div>

                                        
                                        <div class="message ai-msg">
                                            <div class="prescription-card">
                                                <div class="pill-icon-2d"></div>
                                                <div class="rx-text">
                                                    <strong>Paracetamol 500mg</strong>
                                                    <small>Consult a Doctor</small>
                                                </div>
                                                <div class="check-mark">✓</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="instruction-steps">
                                <div class="step-badge step-1">
                                    <span class="step-num">1</span>
                                    <span class="step-text">Describe Symptoms</span>
                                </div>
                                <div class="step-badge step-2">
                                    <span class="step-num">2</span>
                                    <span class="step-text">AI Analysis</span>
                                </div>
                                <div class="step-badge step-3">
                                    <span class="step-num">3</span>
                                    <span class="step-text">Get Treatment</span>
                                </div>
                            </div>
                            
                            
                            <div class="hand-cursor"></div>
                        </div>
                    </div>
                </div>
                <div class="about-content">
                    <h2>Your Trusted Health Partner</h2>
                    <p>We're committed to making healthcare accessible for everyone. Order online anytime and conveniently pick up your authentic medicines at our trusted pharmacy.</p>
                    <p>Our friendly staff and AI-powered assistant are here to help you find the right products, answer your questions, and ensure you get the care you deserve.</p>
                    <?php
                    
                    $stat_branches = 1;
                    $stat_branches_label = 'Pharmacy';
                    try {
                        $b_q = $conn->query("SELECT COUNT(*) as cnt FROM branch_list WHERE status = 1");
                        if ($b_q && $b_row = $b_q->fetch_assoc()) {
                            $b_cnt = (int)$b_row['cnt'];
                            if ($b_cnt > 0) {
                                $stat_branches = $b_cnt;
                                $stat_branches_label = ($b_cnt === 1) ? 'Pharmacy' : 'Branches';
                            }
                        }
                    } catch (Exception $e) {}

                    $stat_products = 0;
                    try {
                        $p_q = $conn->query("SELECT COUNT(*) as cnt FROM product_list WHERE delete_flag = 0");
                        if ($p_q && $p_row = $p_q->fetch_assoc()) {
                            $stat_products = (int)$p_row['cnt'];
                        }
                    } catch (Exception $e) {}

                    if ($stat_products >= 1000) {
                        $stat_products_display = number_format($stat_products / 1000, 1) . 'K+';
                    } elseif ($stat_products >= 10) {
                        $stat_products_display = $stat_products . '+';
                    } else {
                        $stat_products_display = (string)$stat_products;
                    }

                    $stat_customers = 0;
                    try {
                        $c_q = $conn->query("SELECT COUNT(*) as cnt FROM customer_list");
                        if ($c_q && $c_row = $c_q->fetch_assoc()) {
                            $stat_customers = (int)$c_row['cnt'];
                        }
                    } catch (Exception $e) {}

                    if ($stat_customers >= 1000) {
                        $stat_customers_display = number_format($stat_customers / 1000, 1) . 'K+';
                    } elseif ($stat_customers >= 10) {
                        $stat_customers_display = $stat_customers . '+';
                    } else {
                        $stat_customers_display = (string)$stat_customers;
                    }
                    ?>
                    <div class="stats">
                        <div class="stat-item">
                            <h3><?php echo $stat_branches; ?></h3>
                            <p><?php echo htmlspecialchars($stat_branches_label); ?></p>
                        </div>
                        <div class="stat-item">
                            <h3><?php echo $stat_products_display; ?></h3>
                            <p>Products</p>
                        </div>
                        <div class="stat-item">
                            <h3><?php echo $stat_customers_display; ?></h3>
                            <p>Happy Customers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="contact" id="contact">
        <div class="container">
            <div class="section-header">
                <h2>Get in touch</h2>
                <p>Have questions? We're here to help.</p>
            </div>
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="info-item">
                        <h4>Email</h4>
                        <p><?php echo htmlspecialchars($_settings->info('email') ?: 'support@pharmacare.com'); ?></p>
                    </div>
                    <div class="info-item">
                        <h4>Phone</h4>
                        <p><?php echo htmlspecialchars($_settings->info('phone') ?: $_settings->info('mobile')); ?></p>
                    </div>
                    <div class="info-item">
                        <h4>Address</h4>
                        <p><?php echo nl2br(htmlspecialchars($_settings->info('address') ?: 'Contact us for location')); ?></p>
                    </div>
                </div>
                <form class="contact-form" id="contactForm" method="POST" action="<?php echo base_url; ?>?p=contact">
                    <div class="form-group">
                        <input type="text" id="contactName" name="name" placeholder="Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" id="contactEmail" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="text" id="contactPhone" name="phone" placeholder="Phone (optional)">
                    </div>
                    <div class="form-group">
                        <input type="text" id="contactSubject" name="subject" placeholder="Subject (optional)">
                    </div>
                    <div class="form-group">
                        <textarea id="contactMessage" name="message" rows="5" placeholder="Message" required></textarea>
                    </div>
                    <input type="hidden" name="submit_contact" value="1">
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo htmlspecialchars($_settings->info('name') ?: 'PharmaCare'); ?></h3>
                    <p>Your trusted online pharmacy. Quality medicines, fast pickup, and caring service.</p>
                </div>
                <div class="footer-section">
                    <h4>Shop</h4>
                    <ul>
                        <li><a href="#products">All Products</a></li>
                        <li><a href="#features">Why Choose Us</a></li>
                        <li><a href="#services">How It Works</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                        <li><a href="<?php echo base_url; ?>?p=contact">Send Inquiry</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Get In Touch</h4>
                    <ul>
                        <li><a href="mailto:<?php echo htmlspecialchars($_settings->info('email')); ?>"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($_settings->info('email') ?: 'Email Us'); ?></a></li>
                        <li><a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $_settings->info('phone')); ?>"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($_settings->info('phone') ?: 'Call Us'); ?></a></li>
                        <li><a href="#contact"><i class="fas fa-map-marker-alt"></i> Find a Branch Near You</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($_settings->info('short_name') ?: 'PharmaCare'); ?>. All rights reserved. | Your Health, Our Priority</p>
            </div>
        </div>
    </footer>

    
    <div id="landingProductModal" class="landing-product-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeLandingProductModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="landingModalProductImage" src="" alt="">
                </div>
                <div class="modal-info">
                    <span class="modal-badge" id="landingModalProductBadge">NEW</span>
                    <span class="modal-category" id="landingModalProductCategory"></span>
                    <h2 class="modal-title" id="landingModalProductName"></h2>
                    <p class="modal-description" id="landingModalProductDescription"></p>
                    <div class="modal-price" id="landingModalProductPrice"></div>
                    <div class="modal-actions">
                        <button class="modal-login-btn" onclick="redirectToLogin()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10 17 15 12 10 7"></polyline>
                                <line x1="15" y1="12" x2="3" y2="12"></line>
                            </svg>
                            Login to Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <?php include(__DIR__ . '/Chatbot/chatbot_widget.php'); ?>

    
    <div class="modal-overlay" id="loginModal">
        <div class="modal-container" style="max-width: 420px;">
            <button class="modal-close" onclick="toggleLoginModal()">✕</button>
            <div class="modal-header" style="text-align: center; padding: 25px 30px 15px;">
                <h2 style="margin-bottom: 8px; color: #1f2937; font-size: 1.5rem; font-weight: 600;">Welcome to <?php echo htmlspecialchars($_settings->info('name') ?: 'PharmaCare'); ?></h2>
                <p style="color: #6b7280; font-size: 0.88rem; margin: 0; line-height: 1.4;">Choose your sign-in method</p>
            </div>
            
            
            <div style="padding: 0 30px;">
                <div class="login-tabs" style="display: flex; border-bottom: 2px solid #e5e7eb; margin-bottom: 20px;">
                    <button type="button" class="login-tab active" data-tab="google" onclick="switchLoginTab('google')" style="flex: 1; padding: 12px; border: none; background: none; cursor: pointer; font-weight: 500; color: #dc3545; border-bottom: 2px solid #dc3545; margin-bottom: -2px; transition: all 0.3s;">
                        <i class="fab fa-google" style="margin-right: 5px;"></i> Google
                    </button>
                    <button type="button" class="login-tab" data-tab="email" onclick="switchLoginTab('email')" style="flex: 1; padding: 12px; border: none; background: none; cursor: pointer; font-weight: 500; color: #6b7280; transition: all 0.3s;">
                        <i class="fas fa-envelope" style="margin-right: 5px;"></i> Email Code
                    </button>
                </div>
            </div>
            
            <div style="padding: 0 30px 30px;">
                
                <div id="googleLoginTab" class="login-tab-content" style="text-align: center;">
                    
                    
                    
                    
                    <div id="googleRecaptchaContainer" style="margin-bottom: 15px; display: flex; justify-content: center;">
                        <div class="g-recaptcha" id="googleRecaptchaWidget" data-sitekey="6Lc_R14sAAAAAPaChqHoU7tFVJc3-Ygm-ItBuu1w" data-callback="onGoogleRecaptchaSuccess" data-expired-callback="onGoogleRecaptchaExpired"></div>
                    </div>
                    <div id="googleRecaptchaError" style="color: #dc3545; font-size: 13px; margin-bottom: 10px; display: none;">Please complete the security check first</div>
                    
                    
                    <button onclick="triggerGoogleSignIn()" class="google-signin-btn" id="googleSignInBtn" disabled style="
                        width: 100%;
                        max-width: 280px;
                        height: 44px;
                        background: #e5e7eb;
                        border: 1.5px solid #dadce0;
                        border-radius: 6px;
                        font-size: 15px;
                        font-weight: 500;
                        color: #9ca3af;
                        cursor: not-allowed;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        transition: all 0.2s ease;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                        margin: 0 auto;
                    ">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Sign in with Google</span>
                    </button>
                    
                    
                    <div id="g_id_signin" style="display: none;"></div>
                    
                    <p style="font-size: 0.8rem; color: #9ca3af; margin-top: 15px;">
                        Fast and secure login with your Google account
                    </p>
                </div>
                
                
                <div id="emailLoginTab" class="login-tab-content" style="display: none;">
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; background: #f3f4f6; padding: 5px; border-radius: 6px;">
                        <button type="button" onclick="setEmailAction('login')" id="emailActionLogin" class="email-action-btn" style="flex: 1; padding: 8px; border: none; background: white; border-radius: 4px; cursor: pointer; font-weight: 500; color: #1f2937; transition: all 0.2s;">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </button>
                        <button type="button" onclick="setEmailAction('register')" id="emailActionRegister" class="email-action-btn" style="flex: 1; padding: 8px; border: none; background: none; border-radius: 4px; cursor: pointer; font-weight: 500; color: #6b7280; transition: all 0.2s;">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </div>
                    
                    
                    <div id="emailStep1">
                        <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 15px; text-align: center;" id="emailActionText">
                            We'll send a verification code to your email
                        </p>
                        <div id="emailVerifyMsg"></div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 0.9rem;">Email Address</label>
                            <input type="email" id="verifyEmail" placeholder="your@email.com" style="
                                width: 100%;
                                padding: 12px 15px;
                                border: 1.5px solid #d1d5db;
                                border-radius: 8px;
                                font-size: 1rem;
                                transition: all 0.2s;
                                box-sizing: border-box;
                            " onfocus="this.style.borderColor='#dc3545'; this.style.boxShadow='0 0 0 3px rgba(220,53,69,0.1)';"
                               onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';">
                        </div>
                        
                        <div id="emailRecaptchaContainer" style="margin-bottom: 15px; display: flex; justify-content: center;">
                            <div class="g-recaptcha" data-sitekey="6Lc_R14sAAAAAPaChqHoU7tFVJc3-Ygm-ItBuu1w" data-callback="onEmailRecaptchaSuccess" data-expired-callback="onEmailRecaptchaExpired"></div>
                        </div>
                        <div id="emailRecaptchaError" style="color: #dc3545; font-size: 13px; margin-bottom: 10px; text-align: center; display: none;">Please complete the security check</div>
                        <button onclick="sendVerificationCode()" id="sendCodeBtn" disabled style="
                            width: 100%;
                            padding: 12px;
                            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                            color: white;
                            border: none;
                            border-radius: 8px;
                            font-size: 1rem;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s;
                        ">
                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Send Verification Code
                        </button>
                    </div>
                    
                    
                    <div id="emailStep2" style="display: none;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                <i class="fas fa-envelope-open-text" style="font-size: 24px; color: white;"></i>
                            </div>
                            <p style="font-size: 0.9rem; color: #374151; margin: 0;">
                                Code sent to <strong id="maskedEmail"></strong>
                            </p>
                        </div>
                        <div id="codeVerifyMsg"></div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 0.9rem;">Enter 6-Digit Code</label>
                            <input type="text" id="verifyCode" maxlength="6" placeholder="000000" style="
                                width: 100%;
                                padding: 15px;
                                border: 2px solid #d1d5db;
                                border-radius: 8px;
                                font-size: 1.5rem;
                                text-align: center;
                                letter-spacing: 8px;
                                font-weight: 600;
                                box-sizing: border-box;
                            " onfocus="this.style.borderColor='#dc3545';" onblur="this.style.borderColor='#d1d5db';"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <button onclick="verifyCode()" id="verifyCodeBtn" style="
                            width: 100%;
                            padding: 12px;
                            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                            color: white;
                            border: none;
                            border-radius: 8px;
                            font-size: 1rem;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s;
                            margin-bottom: 10px;
                        ">
                            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> Verify & Sign In
                        </button>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button onclick="backToEmail()" style="background: none; border: none; color: #6b7280; cursor: pointer; font-size: 0.85rem;">
                                <i class="fas fa-arrow-left"></i> Change Email
                            </button>
                            <button onclick="resendCode()" id="resendBtn" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 0.85rem;">
                                <i class="fas fa-redo"></i> Resend Code
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="text-align: center; padding: 15px 30px 20px; border-top: 1px solid #f3f4f6;">
                <p style="font-size: 0.8rem; color: #9ca3af; margin: 0; display: flex; align-items: center; justify-content: center; gap: 5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Secure login • Your data is protected</span>
                </p>
            </div>
        </div>
    </div>
</div>

<script>

let verificationEmail = '';
let currentEmailAction = 'login'; 
let emailRecaptchaToken = ''; 
let googleRecaptchaToken = ''; 

function onEmailRecaptchaSuccess(token) {
    emailRecaptchaToken = token;
    document.getElementById('sendCodeBtn').disabled = false;
    document.getElementById('emailRecaptchaError').style.display = 'none';
}

function onEmailRecaptchaExpired() {
    emailRecaptchaToken = '';
    document.getElementById('sendCodeBtn').disabled = true;
}

function onGoogleRecaptchaSuccess(token) {
    googleRecaptchaToken = token;
    var btn = document.getElementById('googleSignInBtn');
    btn.disabled = false;
    btn.style.background = 'white';
    btn.style.color = '#3c4043';
    btn.style.cursor = 'pointer';
    document.getElementById('googleRecaptchaError').style.display = 'none';
}

function onGoogleRecaptchaExpired() {
    googleRecaptchaToken = '';
    var btn = document.getElementById('googleSignInBtn');
    btn.disabled = true;
    btn.style.background = '#e5e7eb';
    btn.style.color = '#9ca3af';
    btn.style.cursor = 'not-allowed';
}

function setEmailAction(action) {
    currentEmailAction = action;
    
    
    const loginBtn = document.getElementById('emailActionLogin');
    const registerBtn = document.getElementById('emailActionRegister');
    
    if (action === 'login') {
        loginBtn.style.background = 'white';
        loginBtn.style.color = '#1f2937';
        registerBtn.style.background = 'none';
        registerBtn.style.color = '#6b7280';
        document.getElementById('emailActionText').textContent = 'We\'ll send a verification code to your email';
    } else {
        loginBtn.style.background = 'none';
        loginBtn.style.color = '#6b7280';
        registerBtn.style.background = 'white';
        registerBtn.style.color = '#1f2937';
        document.getElementById('emailActionText').textContent = 'Create a new account with your email';
    }
    
    
    document.getElementById('emailVerifyMsg').innerHTML = '';
}

function switchLoginTab(tab) {
    
    document.querySelectorAll('.login-tab').forEach(btn => {
        btn.style.color = '#6b7280';
        btn.style.borderBottom = '2px solid transparent';
        btn.classList.remove('active');
    });
    
    const activeBtn = document.querySelector(`.login-tab[data-tab="${tab}"]`);
    if (activeBtn) {
        activeBtn.style.color = '#dc3545';
        activeBtn.style.borderBottom = '2px solid #dc3545';
        activeBtn.classList.add('active');
    }
    
    
    document.getElementById('googleLoginTab').style.display = tab === 'google' ? 'block' : 'none';
    document.getElementById('emailLoginTab').style.display = tab === 'email' ? 'block' : 'none';
    
    
    if (tab === 'email') {
        currentEmailAction = 'login'; 
        setEmailAction('login');
        document.getElementById('emailStep1').style.display = 'block';
        document.getElementById('emailStep2').style.display = 'none';
        document.getElementById('emailVerifyMsg').innerHTML = '';
    }
}

function sendVerificationCode() {
    const email = document.getElementById('verifyEmail').value.trim();
    const msgDiv = document.getElementById('emailVerifyMsg');
    const btn = document.getElementById('sendCodeBtn');
    
    
    if (!emailRecaptchaToken) {
        document.getElementById('emailRecaptchaError').style.display = 'block';
        return;
    }
    
    if (!email) {
        msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> Please enter your email address</div>';
        return;
    }
    
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> Please enter a valid email address</div>';
        return;
    }
    
    verificationEmail = email;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    $.ajax({
        url: base_url + 'classes/EmailVerification.php?f=send_code',
        method: 'POST',
        data: { 
            email: email, 
            type: 'customer', 
            action: currentEmailAction,
            'g-recaptcha-response': emailRecaptchaToken
        },
        dataType: 'json',
        success: function(resp) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Send Verification Code';
            
            if (resp.status === 'success') {
                document.getElementById('maskedEmail').textContent = resp.email_masked;
                document.getElementById('emailStep1').style.display = 'none';
                document.getElementById('emailStep2').style.display = 'block';
                document.getElementById('verifyCode').value = '';
                
                document.getElementById('codeVerifyMsg').innerHTML = '';
            } else if (resp.status === 'bot') {
                msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-robot"></i> Security verification failed. Please try again.</div>';
                
                if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
                emailRecaptchaToken = '';
                btn.disabled = true;
            } else {
                msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> ' + (resp.msg || 'Email service provider error. Please try again later.') + '</div>';
                if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
                emailRecaptchaToken = '';
                btn.disabled = true;
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Send Verification Code';
            msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> Network error. Please try again.</div>';
        }
    });
}

function verifyCode() {
    const code = document.getElementById('verifyCode').value.trim();
    const msgDiv = document.getElementById('codeVerifyMsg');
    const btn = document.getElementById('verifyCodeBtn');
    
    if (!code || code.length !== 6) {
        msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> Please enter the 6-digit code</div>';
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    
    $.ajax({
        url: base_url + 'classes/EmailVerification.php?f=verify_code',
        method: 'POST',
        data: { email: verificationEmail, code: code, type: 'customer', action: currentEmailAction },
        dataType: 'json',
        success: function(resp) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 8px;"></i> Verify & Sign In';
            
            if (resp.status === 'success') {
                msgDiv.innerHTML = '<div style="background: #d1fae5; color: #059669; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-check-circle"></i> ' + resp.msg + ' Redirecting...</div>';
                setTimeout(function() {
                    window.location.href = base_url + (resp.redirect || 'customer/');
                }, 1500);
            } else {
                msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> ' + resp.msg + '</div>';
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 8px;"></i> Verify & Sign In';
            msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> Network error. Please try again.</div>';
        }
    });
}

function backToEmail() {
    document.getElementById('emailStep1').style.display = 'block';
    document.getElementById('emailStep2').style.display = 'none';
    document.getElementById('emailVerifyMsg').innerHTML = '';
}

function resendCode() {
    const btn = document.getElementById('resendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    $.ajax({
        url: base_url + 'classes/EmailVerification.php?f=resend_code',
        method: 'POST',
        data: { email: verificationEmail, type: 'customer' },
        dataType: 'json',
        success: function(resp) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo"></i> Resend Code';
            
            const msgDiv = document.getElementById('codeVerifyMsg');
            if (resp.status === 'success') {
                msgDiv.innerHTML = '<div style="background: #d1fae5; color: #059669; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-check-circle"></i> New code sent!</div>';
            } else {
                msgDiv.innerHTML = '<div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 0.9rem;"><i class="fas fa-exclamation-circle"></i> ' + resp.msg + '</div>';
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo"></i> Resend Code';
        }
    });
}
</script>

<style>
    
    .google-signin-btn:hover {
        background: #f8f9fa !important;
        border-color: #d1d5db !important;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        transform: translateY(-1px);
    }
    
    .google-signin-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
    }
    
    .modal-container {
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    
    .product-img {
        position: relative;
    }
    
    
    .prescription-badge {
        position: absolute;
        top: 6px;
        left: 6px;
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 0.55rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);
        display: flex;
        align-items: center;
        gap: 3px;
        z-index: 5;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }

    .prescription-badge i {
        font-size: 0.5rem;
    }

    
    .fda-badge {
        position: absolute;
        top: 30px;
        left: 6px;
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 0.5rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3);
        display: flex;
        align-items: center;
        gap: 2px;
        z-index: 5;
        letter-spacing: 0.2px;
    }

    .fda-badge i {
        font-size: 0.45rem;
    }
    
    .landing-view-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        background: var(--primary-color, #0d9488);
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 5;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    }
    
    .product-img:hover .landing-view-icon {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.05);
    }
    
    .landing-view-icon:hover {
        background: var(--primary-dark, #0f766e);
        box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
    }
    
    .landing-view-icon svg {
        width: 20px;
        height: 20px;
        fill: white;
    }
    
    
    .landing-product-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(10px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease;
    }
    
    .landing-product-modal.active {
        display: flex;
    }
    
    .landing-product-modal .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 900px;
        width: 90%;
        max-height: 85vh;
        overflow: hidden;
        position: relative;
        animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
    }
    
    .landing-product-modal .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .landing-product-modal .modal-close:hover {
        background: white;
        transform: rotate(90deg);
    }
    
    .landing-product-modal .modal-close svg {
        width: 20px;
        height: 20px;
        stroke: #1f2937;
    }
    
    .landing-product-modal .modal-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        height: 100%;
    }
    
    .landing-product-modal .modal-image {
        background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }
    
    .landing-product-modal .modal-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        max-height: 400px;
        border-radius: 16px;
    }
    
    .landing-product-modal .modal-info {
        padding: 40px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .landing-product-modal .modal-badge {
        display: inline-block;
        background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        width: fit-content;
    }
    
    .landing-product-modal .modal-category {
        color: #0d9488;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .landing-product-modal .modal-title {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
        line-height: 1.2;
    }
    
    .landing-product-modal .modal-description {
        font-size: 16px;
        line-height: 1.6;
        color: #6b7280;
        margin: 0;
    }
    
    .landing-product-modal .modal-price {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .landing-product-modal .modal-actions {
        display: flex;
        gap: 12px;
        margin-top: auto;
        padding-top: 20px;
    }
    
    .landing-product-modal .modal-login-btn {
        flex: 1;
        background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
        color: white;
        border: none;
        padding: 16px 32px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    }
    
    .landing-product-modal .modal-login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(13, 148, 136, 0.4);
    }
    
    .landing-product-modal .modal-login-btn svg {
        width: 20px;
        height: 20px;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media (max-width: 768px) {
        .landing-product-modal .modal-content {
            width: 95%;
            max-height: 90vh;
        }
        
        .landing-product-modal .modal-body {
            grid-template-columns: 1fr;
        }
        
        .landing-product-modal .modal-image {
            padding: 30px;
            max-height: 300px;
        }
        
        .landing-product-modal .modal-info {
            padding: 30px;
        }
        
        .landing-product-modal .modal-title {
            font-size: 24px;
        }
        
        .landing-product-modal .modal-price {
            font-size: 28px;
        }
    }
</style>

<script>
    const base_url = "<?php echo base_url ?>";
    
    
    window.googleButtonRendered = false; 
    
    function triggerGoogleSignIn() {
        
        if (!googleRecaptchaToken) {
            document.getElementById('googleRecaptchaError').style.display = 'block';
            return;
        }
        
        
        if (typeof google !== 'undefined' && google.accounts) {
            google.accounts.id.initialize({
                client_id: '503382365754-atupfo4jmk6r4jitd6ilh5j6nd91ehtk.apps.googleusercontent.com',
                callback: handleGoogleSignIn,
                auto_select: false,
                cancel_on_tap_outside: true,
                use_fedcm_for_prompt: false,
                itp_support: true
            });
            
            
            google.accounts.id.prompt((notification) => {
                console.log('Google prompt notification:', notification.getMomentType(), 
                    'isNotDisplayed:', notification.isNotDisplayed(), 
                    'isSkippedMoment:', notification.isSkippedMoment());
                    
                if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                    console.log('Google One Tap not available, showing fallback button');
                    
                    if (!googleButtonRendered) {
                        googleButtonRendered = true;
                        
                        var fallbackContainer = document.getElementById('g_id_signin');
                        if (fallbackContainer) {
                            fallbackContainer.innerHTML = '';
                            google.accounts.id.renderButton(
                                fallbackContainer,
                                { 
                                    theme: 'outline', 
                                    size: 'large',
                                    type: 'standard',
                                    text: 'signin_with',
                                    width: Math.min(320, window.innerWidth - 80)
                                }
                            );
                            fallbackContainer.style.display = 'flex';
                            fallbackContainer.style.justifyContent = 'center';
                        }
                        var customBtn = document.querySelector('.google-signin-btn');
                        if (customBtn) customBtn.style.display = 'none';
                    }
                }
            });
        } else {
            alert('Google Sign-In is not loaded yet. Please wait a moment and try again.');
        }
    }

    
    let currentProductId = null;

    function viewLandingProduct(productId) {
        currentProductId = productId;
        
        
        $.ajax({
            url: 'ajax.php?action=get_product_details',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(product) {
                if(product.status === 'success') {
                    const modal = document.getElementById('landingProductModal');
                    if(modal) {
                        $('#landingModalProductImage').attr('src', product.data.image_path);
                        $('#landingModalProductImage').attr('alt', product.data.name);
                        $('#landingModalProductCategory').text(product.data.category_name);
                        $('#landingModalProductName').text(product.data.name);
                        $('#landingModalProductDescription').text(product.data.description);
                        $('#landingModalProductPrice').text('₱' + product.data.price);
                        
                        
                        modal.classList.add('active');
                        document.body.style.overflow = 'hidden';
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

    function closeLandingProductModal() {
        const modal = document.getElementById('landingProductModal');
        if(modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        currentProductId = null;
    }

    
    document.addEventListener('click', function(e) {
        if(e.target.id === 'landingProductModal') {
            closeLandingProductModal();
        }
    });

    
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') {
            const modal = document.getElementById('landingProductModal');
            if(modal && modal.classList.contains('active')) {
                closeLandingProductModal();
            }
        }
    });

    function redirectToLogin() {
        closeLandingProductModal();
        toggleLoginModal();
    }
</script>
<script src="<?php echo base_url ?>assets/js/landing.js?v=<?php echo time(); ?>"></script>
