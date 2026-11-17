

window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }
});

const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');

if (hamburger && navMenu) {
    hamburger.addEventListener('click', function() {
        navMenu.classList.toggle('active');
        
        
        const spans = hamburger.querySelectorAll('span');
        if (navMenu.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    });
}

const navLinks = document.querySelectorAll('.nav-menu a');
navLinks.forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768 && navMenu && hamburger) {
            navMenu.classList.remove('active');
            const spans = hamburger.querySelectorAll('span');
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    });
});

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        }
    });
});

const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        
        console.log('Form submitted:', data);
        
        
        alert('Thank you for your message! We will get back to you soon.');
        
        
        this.reset();
    });
}

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll('.feature-card, .services-content, .about-grid, .contact-grid');
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

document.documentElement.style.visibility = 'visible';

const chatbotButton = document.querySelector('.chatbot-button');
const chatbotMessage = document.querySelector('.chatbot-message');

if (chatbotButton && chatbotMessage) {
    chatbotButton.addEventListener('click', function() {
        
        const messages = [
            "How can I help you today?",
            "Check out our features section!",
            "Need help logging in?",
            "Our support team is available 24/7."
        ];
        const randomMsg = messages[Math.floor(Math.random() * messages.length)];
        
        chatbotMessage.style.animation = 'none';
        chatbotMessage.offsetHeight; 
        chatbotMessage.style.animation = 'popInMessage 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards';
        chatbotMessage.textContent = randomMsg;
    });
    
    
    setTimeout(() => {
        if (chatbotMessage) {
            chatbotMessage.style.opacity = '0';
            chatbotMessage.style.transform = 'translateY(10px)';
            chatbotMessage.style.transition = 'all 0.5s ease';
        }
    }, 8000);
}

window.toggleChatbot = function() {
    
    let widget = document.getElementById('chatbotWidget');

    
    if (!widget) {
        const toggleBtn = document.querySelector('.chatbot-toggle');
        if (toggleBtn) {
            
            toggleBtn.click();
            return;
        }
        
        console.warn('Chatbot widget not found (no #chatbotWidget or .chatbot-toggle).');
        return;
    }

    widget.classList.toggle('active');

    
    if (widget.classList.contains('active')) {
        setTimeout(() => {
            const input = widget.querySelector('input');
            if (input) input.focus();
        }, 300);
    }
};

document.addEventListener('click', (e) => {
    const widget = document.getElementById('chatbotWidget');
    if (widget && widget.classList.contains('active') && 
        !widget.contains(e.target) && 
        !e.target.closest('.chatbot-button')) {
        widget.classList.remove('active');
    }
});

window.toggleLoginModal = function() {
    const modal = document.getElementById('loginModal');
    if (!modal) return;
    modal.classList.toggle('active');
    
    if (modal.classList.contains('active')) {
        document.body.style.overflow = 'hidden'; 
    } else {
        document.body.style.overflow = '';
        
        resetGoogleLoginState();
    }
};

window.resetGoogleLoginState = function() {
    var fallback = document.getElementById('g_id_signin');
    var customBtn = document.querySelector('.google-signin-btn');
    if (fallback) {
        fallback.innerHTML = '';
        fallback.style.display = 'none';
    }
    if (customBtn) {
        customBtn.style.display = 'flex';
    }
    
    if (typeof googleButtonRendered !== 'undefined') {
        window.googleButtonRendered = false;
    }
};

const loginModal = document.getElementById('loginModal');
if (loginModal) {
    loginModal.addEventListener('click', (e) => {
        if (e.target.id === 'loginModal') {
            toggleLoginModal();
        }
    });
}

window.handleGoogleSignIn = function(response) {
    
    console.log("Google Sign-In callback fired");
    console.log("Response object:", response);
    console.log("Response type:", typeof response);
    console.log("Response keys:", response ? Object.keys(response) : 'null/undefined');
    console.log("Credential exists:", response ? (!!response.credential) : false);
    console.log("Credential type:", response ? typeof response.credential : 'N/A');
    console.log("Credential length:", response && response.credential ? response.credential.length : 0);
    
    
    if (!response || !response.credential) {
        console.error('Google Sign-In: No credential in response', response);
        alert('Google Sign-In failed: No credential received from Google. Please try again.');
        location.reload();
        return;
    }
    
    console.log("Google Sign-In successful, processing...");
    
    
    const modal = document.getElementById('loginModal');
    let originalContent = '';
    
    if (modal) {
        const modalContainer = modal.querySelector('.modal-container');
        originalContent = modalContainer.innerHTML;
        modalContainer.innerHTML = `
            <div style="text-align: center; padding: 60px 40px;">
                <div class="spinner" style="display: inline-block; width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #0d9488; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 20px; color: #666;">Signing you in...</p>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
    }
    
    
    fetch('backend/auth/google-login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'include',
        body: 'credential=' + encodeURIComponent(response.credential)
    })
    .then(res => {
        console.log('Fetch response status:', res.status, res.statusText);
        return res.json();
    })
    .then(data => {
        console.log('Google login response:', data);
        if (data.success) {
            console.log('Authentication successful:', data);
            
            
            if (modal) {
                const modalContainer = modal.querySelector('.modal-container');
                modalContainer.innerHTML = `
                    <div style="text-align: center; padding: 50px 40px;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <h3 style="color: #1f2937; font-size: 1.3rem; margin-bottom: 10px; font-weight: 600;">
                            ${data.isNewUser ? 'Account Created!' : 'Welcome Back!'}
                        </h3>
                        <p style="color: #6b7280; font-size: 0.95rem; margin-bottom: 20px;">
                            ${data.isNewUser ? 'Your account has been successfully created.' : 'Logging you in...'}
                        </p>
                        <div style="width: 100%; height: 4px; background: #f3f4f6; border-radius: 2px; overflow: hidden;">
                            <div style="width: 0%; height: 100%; background: linear-gradient(90deg, #0d9488, #14b8a6); animation: progress 1s ease-out forwards;"></div>
                        </div>
                    </div>
                    <style>
                        @keyframes progress {
                            to { width: 100%; }
                        }
                    </style>
                `;
            }
            
            
            setTimeout(() => {
                window.location.href = './';
            }, 1500);
        } else {
            console.error('Authentication failed:', data.message);
            alert(data.message || 'Authentication failed. Please try again.');
            
            if (modal && originalContent) {
                modal.querySelector('.modal-container').innerHTML = originalContent;
            }
        }
    })
    .catch(error => {
        console.error('Error during Google Sign-In:', error);
        alert('An error occurred during sign-in. Please try again.');
        
        if (modal) {
            location.reload();
        }
    });
};

window.isSignUpMode = false;

window.toggleAuthMode = function() {
    
    console.log('Auth mode toggle disabled - using Google Sign-In only');
    return;
    
    const modalHeader = document.querySelector('.modal-header h2');
    const modalDesc = document.querySelector('.modal-header p');
    const submitBtn = document.querySelector('.modal-form button[type="submit"]');
    const footerLink = document.querySelector('.modal-footer p');
    const form = document.querySelector('.modal-form');
    
    if (!window.isSignUpMode) {
        
        window.isSignUpMode = true;
        modalHeader.innerText = 'Create Account';
        modalDesc.innerText = 'Join PharmaCare today';
        submitBtn.innerText = 'Sign Up';
        footerLink.innerHTML = 'Already have an account? <a href="#" onclick="toggleAuthMode()">Login</a>';
        
        
        const emailGroup = form.querySelector('.form-group');
        if (!form.querySelector('input[name="firstname"]')) {
            const nameFields = document.createElement('div');
            nameFields.className = 'form-group name-fields';
            nameFields.innerHTML = `
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label>First Name</label>
                        <input type="text" name="firstname" autocomplete="given-name" placeholder="First name" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Last Name</label>
                        <input type="text" name="lastname" autocomplete="family-name" placeholder="Last name" required>
                    </div>
                </div>
            `;
            form.insertBefore(nameFields, emailGroup);
        }
        
        
        const passwordInput = form.querySelector('input[type="password"]');
        if (passwordInput) {
            passwordInput.setAttribute('autocomplete', 'new-password');
        }
    } else {
        
        window.isSignUpMode = false;
        modalHeader.innerText = 'Welcome Back';
        modalDesc.innerText = 'Login to access your pharmacy dashboard';
        submitBtn.innerText = 'Login';
        footerLink.innerHTML = 'Don\'t have an account? <a href="#" onclick="toggleAuthMode()">Sign up</a>';
        
        
        const nameFields = form.querySelector('.name-fields');
        if (nameFields) {
            nameFields.remove();
        }
        
        
        const passwordInput = form.querySelector('input[type="password"]');
        if (passwordInput) {
            passwordInput.setAttribute('autocomplete', 'current-password');
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const modalForm = document.querySelector('.modal-form');
    if (modalForm) {
        modalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;
            submitBtn.innerText = 'Please wait...';
            
            const email = this.querySelector('input[type="email"]').value;
            const password = this.querySelector('input[type="password"]').value;
            
            if (window.isSignUpMode) {
                
                const firstname = this.querySelector('input[name="firstname"]').value;
                const lastname = this.querySelector('input[name="lastname"]').value;
                
                fetch('http://localhost/pickupdrug/backend/auth/customer-signup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        firstname: firstname,
                        lastname: lastname,
                        email: email,
                        password: password
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Account created successfully! Redirecting...');
                        window.location.href = 'http://localhost/pickupdrug/';
                    } else {
                        alert(data.message || 'Signup failed. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalText;
                });
            } else {
                
                fetch('http://localhost/pickupdrug/backend/auth/customer-login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Login response:', data);
                    if (data.success) {
                        window.location.href = 'http://localhost/pickupdrug/';
                    } else {
                        alert(data.message || 'Login failed. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalText;
                });
            }
        });
    }
});

const character = document.getElementById('aiCharacter');
const adviceText = document.getElementById('aiAdviceText');
const pill = document.getElementById('medicinePill');

let isFever = true;

const healthyPhrases = [
    "I feel AMAZING! 🌟",
    "Ready to help! 💊",
    "Systems 100% ⚡",
    "Let's go! 🚀",
    "Healthy & Happy! ❤️"
];

const feverPhrases = [
    "System overheating... 🤒",
    "Need medicine... 💊",
    "Feeling dizzy... 🌀",
    "Temperature high... 🌡️",
    "Help me... 🚑"
];

if (adviceText && character && pill) {
    
    setInterval(() => {
        const phrases = isFever ? feverPhrases : healthyPhrases;
        const randomPhrase = phrases[Math.floor(Math.random() * phrases.length)];
        
        
        adviceText.style.opacity = '0';
        
        setTimeout(() => {
            adviceText.textContent = randomPhrase;
            
            adviceText.style.opacity = '1';
        }, 500);
    }, 3000);

    
    const pills = document.querySelectorAll('.draggable-pill');
    
    pills.forEach(pill => {
        pill.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', 'medicine');
            pill.style.opacity = '0.5';
        });

        pill.addEventListener('dragend', () => {
            pill.style.opacity = '1'; 
            if (pill.classList.contains('capsule-5')) pill.style.opacity = '0.6'; 
        });
    });

    character.addEventListener('dragover', (e) => {
        e.preventDefault(); 
        if (isFever) {
            character.style.transform = 'scale(1.1)'; 
        }
    });

    character.addEventListener('dragleave', () => {
        character.style.transform = '';
    });

    character.addEventListener('drop', (e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('text/plain');
        
        if (type === 'medicine' && isFever) {
            cureCharacter();
        }
        character.style.transform = '';
    });
}

const visualCards = document.querySelectorAll('.visual-card');

visualCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const rotateX = ((y - centerY) / centerY) * -5; 
        const rotateY = ((x - centerX) / centerX) * 5;

        card.style.transform = `scale(1.05) perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = ''; 
        
        card.style.transform = ''; 
    });
});

function cureCharacter() {
    isFever = false;
    character.classList.remove('fever');
    
    
    adviceText.textContent = "Ahhh, that's better! 💖";
    adviceText.style.color = "#28a745"; 
    
    
    
}

function makeSick() {
    isFever = true;
    character.classList.add('fever');
    adviceText.textContent = "Oh no... fever returning... 🤒";
    adviceText.style.color = "#FF3B30";
}

const productSlider = document.getElementById('productSlider');
let isDown = false;
let startX;
let scrollLeft;

if (productSlider) {
    productSlider.addEventListener('mousedown', (e) => {
        isDown = true;
        productSlider.classList.add('active');
        startX = e.pageX - productSlider.offsetLeft;
        scrollLeft = productSlider.scrollLeft;
    });

    productSlider.addEventListener('mouseleave', () => {
        isDown = false;
        productSlider.classList.remove('active');
    });

    productSlider.addEventListener('mouseup', () => {
        isDown = false;
        productSlider.classList.remove('active');
    });

    productSlider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - productSlider.offsetLeft;
        const walk = (x - startX) * 2; 
        productSlider.scrollLeft = scrollLeft - walk;
    });
}

function moveSlider(direction) {
    const slider = document.getElementById('productSlider');
    const scrollAmount = 300; 
    
    if (direction === 1) {
        slider.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    } else {
        slider.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const filterTags = document.querySelectorAll('.filter-tag');
    const productCards = document.querySelectorAll('.product-card');

    filterTags.forEach(tag => {
        tag.addEventListener('click', function() {
            
            filterTags.forEach(t => t.classList.remove('active'));
            
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');

            productCards.forEach(card => {
                if (filterValue === 'all') {
                    card.style.display = 'flex';
                } else {
                    if (card.getAttribute('data-category') === filterValue) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    });
});
