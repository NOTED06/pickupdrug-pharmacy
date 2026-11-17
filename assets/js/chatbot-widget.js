

(function() {
    'use strict';
    
    
    const config = window.ChatbotConfig || {};
    const chatbotBaseUrl = config.baseUrl || '';
    const isUserLoggedIn = config.isLoggedIn || false;
    const systemName = config.systemName || 'PharmaCare';
    
    
    const chatbotWidget = document.getElementById('chatbotWidget');
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotInput = document.getElementById('chatbotInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const quickReplies = document.querySelectorAll('.quick-reply-btn');
    const typingIndicator = document.getElementById('typingIndicator');
    const chatbotBadge = document.getElementById('chatbotBadge');
    const headerCloseBtn = document.getElementById('chatbotHeaderClose');
    const imageUploadInput = document.getElementById('imageUploadInput');
    const imagePreviewContainer = document.querySelector('.image-preview-container');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewRemove = document.querySelector('.image-preview-remove');
    const suggestionsContainer = document.getElementById('chatbotSuggestions');
    
    let selectedImageFile = null;
    let conversationHistory = [];
    let searchTimeout = null;
    
    
    window.toggleChatbot = toggleChatbot;
    
    
    function toggleChatbot() {
        chatbotWidget.classList.toggle('active');
        if (chatbotWidget.classList.contains('active')) {
            chatbotBadge.style.display = 'none';
            chatbotInput.focus();
        }
    }
    
    chatbotToggle.addEventListener('click', function(e) {
        
        if (chatbotWidget.getAttribute('data-just-dragged') === 'true') {
            chatbotWidget.removeAttribute('data-just-dragged');
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        toggleChatbot();
    });
    
    
    if (headerCloseBtn) {
        headerCloseBtn.addEventListener('click', function() {
            chatbotWidget.classList.remove('active');
        });
    }
    
    
    let isDragging = false;
    let startX, startY;
    let initialX, initialY;
    let hasMoved = false;
    const DRAG_THRESHOLD = 5;
    
    function initDrag(e) {
        isDragging = true;
        hasMoved = false;
        chatbotWidget.classList.add('is-dragging');
        
        const rect = chatbotWidget.getBoundingClientRect();
        initialX = rect.left;
        initialY = rect.top;
        
        if (e.type === 'touchstart') {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        } else {
            startX = e.clientX;
            startY = e.clientY;
        }
        
        e.preventDefault();
    }
    
    function doDrag(e) {
        if (!isDragging) return;
        
        let currentX, currentY;
        if (e.type === 'touchmove') {
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
        } else {
            currentX = e.clientX;
            currentY = e.clientY;
        }
        
        const deltaX = currentX - startX;
        const deltaY = currentY - startY;
        
        if (Math.abs(deltaX) > DRAG_THRESHOLD || Math.abs(deltaY) > DRAG_THRESHOLD) {
            hasMoved = true;
        }
        
        if (hasMoved) {
            let newX = initialX + deltaX;
            let newY = initialY + deltaY;
            
            const widgetRect = chatbotWidget.getBoundingClientRect();
            const maxX = window.innerWidth - widgetRect.width;
            const maxY = window.innerHeight - widgetRect.height;
            
            newX = Math.max(0, Math.min(newX, maxX));
            newY = Math.max(0, Math.min(newY, maxY));
            
            chatbotWidget.style.right = 'auto';
            chatbotWidget.style.bottom = 'auto';
            chatbotWidget.style.left = newX + 'px';
            chatbotWidget.style.top = newY + 'px';
        }
    }
    
    function endDrag() {
        if (hasMoved) {
            chatbotWidget.setAttribute('data-just-dragged', 'true');
            setTimeout(() => {
                chatbotWidget.removeAttribute('data-just-dragged');
            }, 100);
        }
        isDragging = false;
        hasMoved = false;
        chatbotWidget.classList.remove('is-dragging');
    }
    
    chatbotToggle.addEventListener('mousedown', initDrag);
    document.addEventListener('mousemove', doDrag);
    document.addEventListener('mouseup', endDrag);
    
    chatbotToggle.addEventListener('touchstart', initDrag, { passive: false });
    document.addEventListener('touchmove', doDrag, { passive: false });
    document.addEventListener('touchend', endDrag);
    
    
    function addUserMessage(message) {
        const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const messageHtml = `
            <div class="chat-message user">
                <div class="message-wrapper">
                    <div class="message-content">${escapeHtml(message)}</div>
                    <span class="message-time">${time}</span>
                </div>
            </div>
        `;
        chatbotMessages.insertAdjacentHTML('beforeend', messageHtml);
        scrollToBottom();
    }
    
    function addBotMessage(message, type = '') {
        const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const typeClass = type ? ` ${type}` : '';
        
        
        let formattedMessage = formatMessage(message);
        
        const messageHtml = `
            <div class="chat-message bot">
                <div class="message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="message-wrapper">
                    <div class="message-content${typeClass}">${formattedMessage}</div>
                    <span class="message-time">${time}</span>
                </div>
            </div>
        `;
        chatbotMessages.insertAdjacentHTML('beforeend', messageHtml);
        scrollToBottom();
    }
    
    function addBotMessageWithFollowUp(message, followUpOptions = [], type = '') {
        const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const typeClass = type ? ` ${type}` : '';
        
        let formattedMessage = formatMessage(message);
        
        let followUpHtml = '';
        if (followUpOptions.length > 0) {
            followUpHtml = '<div class="chat-follow-up-options">';
            followUpOptions.forEach(option => {
                followUpHtml += `<button class="follow-up-btn" data-query="${escapeHtml(option)}">${escapeHtml(option)}</button>`;
            });
            followUpHtml += '</div>';
        }
        
        const messageHtml = `
            <div class="chat-message bot">
                <div class="message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="message-wrapper">
                    <div class="message-content${typeClass}">${formattedMessage}${followUpHtml}</div>
                    <span class="message-time">${time}</span>
                </div>
            </div>
        `;
        chatbotMessages.insertAdjacentHTML('beforeend', messageHtml);
        
        
        const lastMessage = chatbotMessages.lastElementChild;
        const followUpBtns = lastMessage.querySelectorAll('.follow-up-btn');
        followUpBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const query = this.getAttribute('data-query');
                chatbotInput.value = query;
                sendMessage();
            });
        });
        
        scrollToBottom();
    }
    
    function formatMessage(message) {
        
        message = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        message = message.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        message = message.replace(/\n/g, '<br>');
        return message;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showTyping() {
        typingIndicator.style.display = 'block';
        scrollToBottom();
    }
    
    function hideTyping() {
        typingIndicator.style.display = 'none';
    }
    
    function scrollToBottom() {
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
    
    
    if (imageUploadInput) {
        imageUploadInput.addEventListener('change', handleImageSelect);
    }
    
    if (imagePreviewRemove) {
        imagePreviewRemove.addEventListener('click', removeSelectedImage);
    }
    
    function handleImageSelect(e) {
        const file = e.target.files[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                addBotMessage('Please select a valid image file.', 'warning');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                addBotMessage('Image size must be less than 5MB.', 'warning');
                return;
            }
            
            selectedImageFile = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.classList.add('active');
            };
            reader.readAsDataURL(file);
        }
    }
    
    function removeSelectedImage() {
        selectedImageFile = null;
        imagePreview.src = '';
        imagePreviewContainer.classList.remove('active');
        imageUploadInput.value = '';
    }
    
    
    async function sendMessage() {
        const message = chatbotInput.value.trim();
        
        if (!message && !selectedImageFile) return;
        
        
        if (suggestionsContainer) {
            suggestionsContainer.classList.remove('show');
        }
        
        if (message) {
            addUserMessage(message);
        }
        
        if (selectedImageFile) {
            const img = document.createElement('img');
            img.src = imagePreview.src;
            img.style.maxWidth = '200px';
            img.style.borderRadius = '8px';
            
            const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            const imageMessageHtml = `
                <div class="chat-message user">
                    <div class="message-wrapper">
                        <div class="message-content">
                            <img src="${imagePreview.src}" style="max-width: 200px; border-radius: 8px;">
                            ${message ? '<br>' + escapeHtml(message) : ''}
                        </div>
                        <span class="message-time">${time}</span>
                    </div>
                </div>
            `;
            if (!message) {
                chatbotMessages.insertAdjacentHTML('beforeend', imageMessageHtml);
                scrollToBottom();
            }
        }
        
        chatbotInput.value = '';
        sendMessageBtn.disabled = true;
        showTyping();
        
        conversationHistory.push({
            role: 'user',
            content: message || 'Image uploaded for analysis'
        });
        
        try {
            const formData = new FormData();
            formData.append('action', 'chat');
            formData.append('message', message);
            formData.append('history', JSON.stringify(conversationHistory));
            formData.append('is_logged_in', isUserLoggedIn ? '1' : '0');
            
            if (selectedImageFile) {
                formData.append('image', selectedImageFile);
            }
            
            const response = await fetch(chatbotBaseUrl + 'Chatbot/chatbot.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            hideTyping();
            
            if (data.success) {
                const followUpOptions = data.follow_up || [];
                if (followUpOptions.length > 0) {
                    addBotMessageWithFollowUp(data.response, followUpOptions, data.type || '');
                } else {
                    addBotMessage(data.response, data.type || '');
                }
                conversationHistory.push({
                    role: 'assistant',
                    content: data.response
                });
            } else {
                addBotMessage(data.error || 'Sorry, something went wrong. Please try again.', 'danger');
            }
        } catch (error) {
            hideTyping();
            addBotMessage('Sorry, I couldn\'t connect to the server. Please check your connection.', 'danger');
            console.error('Chatbot error:', error);
        }
        
        removeSelectedImage();
        sendMessageBtn.disabled = false;
        chatbotInput.focus();
    }
    
    
    sendMessageBtn.addEventListener('click', sendMessage);
    
    chatbotInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    
    quickReplies.forEach(btn => {
        btn.addEventListener('click', function() {
            const message = this.getAttribute('data-message');
            chatbotInput.value = message;
            sendMessage();
        });
    });
    
    
    if (suggestionsContainer) {
        chatbotInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            if (query.length < 2) {
                suggestionsContainer.classList.remove('show');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        });
        
        chatbotInput.addEventListener('blur', function() {
            setTimeout(() => {
                suggestionsContainer.classList.remove('show');
            }, 200);
        });
    }
    
    async function fetchSuggestions(query) {
        suggestionsContainer.innerHTML = '<div class="suggestion-loading">Searching...</div>';
        suggestionsContainer.classList.add('show');
        
        try {
            const response = await fetch(chatbotBaseUrl + 'Chatbot/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=search&query=' + encodeURIComponent(query)
            });
            
            const data = await response.json();
            
            if (data.success && data.results && data.results.length > 0) {
                let html = '';
                data.results.forEach(product => {
                    html += `
                        <div class="suggestion-item" data-name="${escapeHtml(product.name)}">
                            <div class="suggestion-name">${escapeHtml(product.name)}</div>
                            <div class="suggestion-brand">${escapeHtml(product.brand || '')}</div>
                            <div class="suggestion-price">₱${parseFloat(product.price).toFixed(2)}</div>
                        </div>
                    `;
                });
                suggestionsContainer.innerHTML = html;
                
                suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const productName = this.getAttribute('data-name');
                        chatbotInput.value = 'Tell me about ' + productName;
                        suggestionsContainer.classList.remove('show');
                        sendMessage();
                    });
                });
            } else {
                suggestionsContainer.innerHTML = '<div class="suggestion-empty">No products found</div>';
            }
        } catch (error) {
            suggestionsContainer.classList.remove('show');
            console.error('Suggestion error:', error);
        }
    }
    
    
    setTimeout(function() {
        addBotMessage('👋 Hello! I\'m your ' + systemName + ' AI Assistant. How can I help you today?');
    }, 500);
    
})();
