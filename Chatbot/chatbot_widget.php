<?php

$chatbot_system_name = isset($_settings) ? ($_settings->info('name') ?: 'PharmaCare') : 'PharmaCare';
?>

<style>

:root {
    --chat-primary: #0d9488;
    --chat-primary-dark: #0f766e;
    --chat-primary-light: #14b8a6;
    --chat-accent: #f59e0b;
    --chat-success: #10b981;
    --chat-warning: #f59e0b;
    --chat-danger: #ef4444;
    --chat-info: #3b82f6;
    --chat-bg-light: #f8fafb;
    --chat-bg-dark: #1f2937;
    --chat-text-dark: #1f2937;
    --chat-text-light: #f9fafb;
}

@media (prefers-color-scheme: dark) {
    .chatbot-widget.dark-mode .chatbot-container {
        background: var(--chat-bg-dark);
    }
    .chatbot-widget.dark-mode .chatbot-messages {
        background: #111827;
    }
    .chatbot-widget.dark-mode .chat-message.bot .message-content {
        background: #374151;
        color: var(--chat-text-light);
    }
    .chatbot-widget.dark-mode .chatbot-input {
        background: var(--chat-bg-dark);
        border-top-color: #374151;
    }
    .chatbot-widget.dark-mode .chatbot-input input {
        background: #374151;
        border-color: #4b5563;
        color: var(--chat-text-light);
    }
    .chatbot-widget.dark-mode .quick-replies {
        background: var(--chat-bg-dark);
        border-top-color: #374151;
    }
    .chatbot-widget.dark-mode .quick-reply-btn {
        background: #374151;
        color: var(--chat-primary-light);
        border-color: var(--chat-primary);
    }
}

.chatbot-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 99999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    
    touch-action: none;
    user-select: none;
}

.chatbot-widget.is-dragging {
    transition: none !important;
}

.chatbot-widget.is-dragging .chatbot-toggle,
.chatbot-widget.is-dragging .chatbot-header {
    cursor: grabbing !important;
}

.chatbot-toggle,
.chatbot-header {
    cursor: grab;
}

.chatbot-toggle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    border: none;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(13, 148, 136, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
}

.chatbot-toggle:hover {
    transform: scale(1.1) translateY(-4px);
    box-shadow: 0 12px 30px rgba(13, 148, 136, 0.5);
}

.chatbot-toggle svg {
    width: 28px;
    height: 28px;
    fill: white;
    z-index: 2;
    animation: floatIcon 3s ease-in-out infinite;
}

@keyframes floatIcon {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}

.chatbot-toggle .close-icon {
    display: none;
}

.chatbot-widget.active .chatbot-toggle .chat-icon {
    display: none;
}

.chatbot-widget.active .chatbot-toggle .close-icon {
    display: block;
}

.chatbot-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--chat-danger);
    color: white;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
    border: 2px solid white;
}

.chatbot-toggle::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: var(--chat-primary);
    z-index: -1;
    opacity: 0.6;
    animation: pulseRing 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

@keyframes pulseRing {
    0% {
        transform: scale(0.95);
        opacity: 0.7;
    }
    50% {
        opacity: 0.3;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

.chatbot-container {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 400px;
    max-width: calc(100vw - 40px);
    height: 550px;
    max-height: calc(100vh - 120px);
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 50px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0, 0, 0, 0.05);
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.chatbot-widget.active .chatbot-container {
    display: flex;
}

.chatbot-header {
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.chatbot-header-avatar {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.chatbot-header-avatar svg {
    width: 28px;
    height: 28px;
    fill: white;
}

.chatbot-header-info {
    flex: 1;
}

.chatbot-header-info h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: -0.2px;
}

.chatbot-header-info p {
    margin: 4px 0 0;
    font-size: 12px;
    opacity: 0.95;
    font-weight: 400;
}

.online-status {
    display: flex;
    align-items: center;
    gap: 6px;
}

.online-dot {
    width: 8px;
    height: 8px;
    background: #4ade80;
    border-radius: 50%;
    animation: blink 1.5s infinite;
    box-shadow: 0 0 8px rgba(74, 222, 128, 0.6);
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.ai-badge {
    font-size: 10px;
    background: rgba(255, 255, 255, 0.25);
    padding: 3px 10px;
    border-radius: 12px;
    margin-left: 5px;
    backdrop-filter: blur(4px);
}

.chatbot-header-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chatbot-header-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.chatbot-header-close svg {
    width: 14px;
    height: 14px;
    fill: white;
}

.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: linear-gradient(180deg, #f8fafb 0%, #f1f5f9 100%);
}

.chatbot-messages::-webkit-scrollbar {
    width: 6px;
}

.chatbot-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chatbot-messages::-webkit-scrollbar-thumb {
    background: rgba(13, 148, 136, 0.3);
    border-radius: 3px;
}

.chatbot-messages::-webkit-scrollbar-thumb:hover {
    background: rgba(13, 148, 136, 0.5);
}

.chat-message {
    display: flex;
    gap: 10px;
    max-width: 85%;
    animation: fadeIn 0.3s ease;
    margin-bottom: 4px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-message.user {
    align-self: flex-end;
    margin-left: auto;
    flex-direction: row-reverse;
}

.chat-message.bot {
    align-self: flex-start;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: auto;
}

.chat-message.bot .message-avatar {
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.3);
}

.chat-message.bot .message-avatar svg {
    width: 20px;
    height: 20px;
    fill: white;
}

.message-wrapper {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-width: 90%;
}

.message-content {
    max-width: 100%;
    padding: 10px 15px;
    border-radius: 15px;
    font-size: 15px;
    line-height: 1.4;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.chat-message.bot .message-content {
    background: white;
    color: var(--chat-text-dark);
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.chat-message.user .message-content {
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    color: white;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.3);
}

.message-content.success { 
    border-left: 4px solid var(--chat-success); 
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}
.message-content.warning { 
    border-left: 4px solid var(--chat-warning); 
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}
.message-content.danger { 
    border-left: 4px solid var(--chat-danger); 
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}
.message-content.info { 
    border-left: 4px solid var(--chat-info); 
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.message-content strong {
    font-weight: 600;
    color: var(--chat-primary-dark);
}

.message-content.user strong {
    color: white;
}

.message-time {
    font-size: 11px;
    color: #9ca3af;
    padding: 0 4px;
}

.chat-message.user .message-time {
    text-align: right;
}

.typing-indicator {
    display: flex;
    gap: 5px;
    padding: 8px 0;
}

.typing-indicator span {
    width: 10px;
    height: 10px;
    background: var(--chat-primary);
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}

.typing-text {
    font-size: 12px;
    color: #6b7280;
    margin-top: 6px;
}

.typing-text::after {
    content: '';
    animation: dots 1.5s infinite;
}

@keyframes dots {
    0% { content: ''; }
    25% { content: '.'; }
    50% { content: '..'; }
    75% { content: '...'; }
    100% { content: ''; }
}

.quick-replies {
    padding: 8px 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.quick-reply-btn {
    padding: 5px 10px;
    background: white;
    border: 1.5px solid var(--chat-primary);
    border-radius: 16px;
    color: var(--chat-primary);
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.quick-reply-btn:hover {
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    color: white;
    border-color: transparent;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(13, 148, 136, 0.25);
}

.quick-reply-btn:active {
    transform: translateY(0);
}

.chat-follow-up-options {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed rgba(13, 148, 136, 0.3);
}

.follow-up-btn {
    padding: 6px 14px;
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
    border: 1.5px solid var(--chat-primary);
    border-radius: 18px;
    color: var(--chat-primary-dark);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.follow-up-btn:hover {
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35);
}

.follow-up-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(13, 148, 136, 0.25);
}

@media (prefers-color-scheme: dark) {
    .chatbot-widget.dark-mode .follow-up-btn {
        background: linear-gradient(135deg, #134e4a 0%, #0f766e 100%);
        color: #ccfbf1;
        border-color: var(--chat-primary-light);
    }
    .chatbot-widget.dark-mode .follow-up-btn:hover {
        background: linear-gradient(135deg, var(--chat-primary) 0%, #0d9488 100%);
        color: white;
    }
}

.chatbot-input {
    display: flex;
    gap: 10px;
    padding: 14px 16px;
    background: white;
    border-top: 1px solid #e5e7eb;
    flex-wrap: wrap;
}

.image-preview-container {
    width: 100%;
    display: none;
    margin-bottom: 10px;
    position: relative;
}

.image-preview-container.active {
    display: block;
}

.image-preview {
    max-width: 150px;
    max-height: 100px;
    border-radius: 8px;
    border: 2px solid var(--chat-primary);
    object-fit: cover;
}

.image-preview-remove {
    position: absolute;
    top: -8px;
    left: 142px;
    width: 24px;
    height: 24px;
    background: var(--chat-danger);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
}

.image-preview-remove:hover {
    background: #dc2626;
}

.chatbot-input-row {
    display: flex;
    gap: 8px;
    width: 100%;
    align-items: center;
}

.image-upload-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--chat-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.image-upload-btn:hover {
    background: var(--chat-primary);
}

.image-upload-btn:hover svg {
    fill: white;
}

.image-upload-btn svg {
    width: 20px;
    height: 20px;
    fill: var(--chat-primary);
    transition: fill 0.2s;
}

.image-upload-btn input[type="file"] {
    display: none;
}

.chatbot-input input {
    flex: 1;
    padding: 12px 18px;
    border: 2px solid #e5e7eb;
    border-radius: 24px;
    outline: none;
    font-size: 14px;
    transition: all 0.2s;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.chatbot-input input:focus {
    border-color: var(--chat-primary);
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}

.chatbot-input input::placeholder {
    color: #9ca3af;
}

.chatbot-input button {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.3);
}

.chatbot-input button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4);
}

.chatbot-input button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.chatbot-input button svg {
    width: 20px;
    height: 20px;
    fill: white;
}

.chatbot-suggestions {
    position: absolute;
    bottom: 70px;
    left: 15px;
    right: 15px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    max-height: 250px;
    overflow-y: auto;
    display: none;
    z-index: 10;
}

.chatbot-suggestions.show {
    display: block;
}

.suggestion-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f2f5;
    cursor: pointer;
    transition: background-color 0.2s;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: #f0f2f5;
}

.suggestion-name {
    font-weight: 600;
    color: #1c1e21;
    font-size: 14px;
    margin-bottom: 3px;
}

.suggestion-brand {
    color: #65676b;
    font-size: 12px;
    margin-bottom: 2px;
}

.suggestion-price {
    color: var(--chat-primary);
    font-size: 13px;
    font-weight: 600;
}

.suggestion-cart-btn {
    background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-left: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(13, 148, 136, 0.3);
}

.suggestion-cart-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 10px rgba(13, 148, 136, 0.4);
}

.suggestion-cart-btn:active {
    transform: scale(0.95);
}

.suggestion-loading {
    padding: 15px;
    text-align: center;
    color: #65676b;
    font-size: 13px;
}

.suggestion-empty {
    padding: 15px;
    text-align: center;
    color: #65676b;
    font-size: 13px;
}

@media (max-width: 768px) {
    .chatbot-widget {
        bottom: 20px;
        right: 20px;
    }
    
    .chatbot-toggle {
        width: 56px;
        height: 56px;
    }
    
    .chatbot-container {
        width: calc(100vw - 16px);
        right: -12px;
        bottom: 64px;
    }
}

@media (max-width: 480px) {
    .chatbot-widget {
        bottom: 16px;
        right: 16px;
    }
    
    .chatbot-toggle {
        width: 50px;
        height: 50px;
    }
    
    .chatbot-container {
        width: calc(100vw - 12px);
        height: calc(100vh - 90px);
        right: -10px;
        bottom: 58px;
        border-radius: 12px;
    }
    
    .chatbot-header h4 {
        font-size: 0.85rem;
    }
    
    .quick-reply-btn {
        font-size: 0.8rem;
        padding: 6px 10px;
    }
}

@media (max-width: 360px) {
    .chatbot-widget {
        bottom: 12px;
        right: 12px;
    }
    
    .chatbot-toggle {
        width: 46px;
        height: 46px;
    }
    
    .chatbot-container {
        width: calc(100vw - 8px);
        height: calc(100vh - 80px);
        right: -8px;
        bottom: 54px;
    }
}
</style>

<div class="chatbot-widget" id="chatbotWidget">
    
    <button class="chatbot-toggle" onclick="toggleChatbot()" title="Chat with us">
        <svg class="chat-icon" viewBox="0 0 24 24">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
            <path d="M7 9h10v2H7zm0-3h10v2H7zm0 6h7v2H7z"/>
        </svg>
        <svg class="close-icon" viewBox="0 0 24 24">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
        <span class="chatbot-badge" id="chatBadge" style="display: none;">1</span>
    </button>
    
    
    <div class="chatbot-container">
        
        <div class="chatbot-header">
            <div class="chatbot-header-avatar">
                <svg viewBox="0 0 24 24">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                </svg>
            </div>
            <div class="chatbot-header-info">
                <h4>💊 <?php echo htmlspecialchars($chatbot_system_name); ?> AI Assistant</h4>
                <p class="online-status">
                    <span class="online-dot"></span>
                    <span class="ai-badge">🤖 Powered by Polinar 69</span>
                </p>
            </div>
            <button class="chatbot-header-clear" onclick="event.stopPropagation(); clearChatHistory();" title="Clear chat history" style="background:none;border:none;color:rgba(255,255,255,0.7);cursor:pointer;padding:6px;margin-right:2px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all 0.2s;" onmouseover="this.style.color='#fff';this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.color='rgba(255,255,255,0.7)';this.style.background='none'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            </button>
            <button class="chatbot-header-close" onclick="toggleChatbot()" title="Minimize">
                <svg viewBox="0 0 24 24">
                    <path d="M19 13H5v-2h14v2z"/>
                </svg>
            </button>
        </div>
        
        
        <div class="chatbot-messages" id="chatMessages">
            
        </div>
        
        
        <div class="quick-replies" id="quickReplies">
            <button class="quick-reply-btn" onclick="sendQuickReply('What are your store hours?')">🕐 Hours</button>
            <button class="quick-reply-btn" onclick="sendQuickReply('Where are you located?')">📍 Location</button>
            <button class="quick-reply-btn" onclick="sendQuickReply('What medicine do you recommend for mild symptoms like headache or fever?')">🤒 Symptoms</button>
            <button class="quick-reply-btn" onclick="document.getElementById('imageInput').click()">📷 Upload Rx</button>
        </div>
        
        
        <div class="chatbot-suggestions" id="chatSuggestions" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();"></div>

        
        <div class="chatbot-input">
            
            <div class="image-preview-container" id="imagePreviewContainer">
                <img src="" alt="Preview" class="image-preview" id="imagePreview">
                <button class="image-preview-remove" onclick="removeImage()" title="Remove image">&times;</button>
            </div>
            
            <div class="chatbot-input-row">
                
                <label class="image-upload-btn" title="Upload prescription or medicine image">
                    <input type="file" id="imageInput" accept="image/*" onchange="handleImageSelect(event)">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                    </svg>
                </label>
                
                <input type="text" id="chatInput" placeholder="Type message or upload image...">
                <button id="sendBtn">
                    <svg viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>

(function() {
    'use strict';
    
    
    if (window.chatbotInitialized) {
        console.log('Chatbot already initialized, skipping...');
        return;
    }
    window.chatbotInitialized = true;

const chatbotBaseUrl = '<?php echo defined("base_url") ? base_url : "/pickupdrug/"; ?>';

const isUserLoggedIn = <?php echo (isset($_SESSION['userdata']['login_type']) && $_SESSION['userdata']['login_type'] == 2) ? 'true' : 'false'; ?>;

let searchTimeout;
let currentSearchQuery = '';
let lastMessageTime = 0;
const MESSAGE_COOLDOWN = 2000; 

let selectedImageData = null;
let selectedImageMimeType = null;

const CHAT_STORAGE_KEY = 'pharmachat_history';
const CHAT_TOPIC_KEY  = 'pharmachat_topic';
const CHAT_MAX_SAVED  = 50; 

function saveChatHistory() {
    try {
        const container = document.getElementById('chatMessages');
        if (!container) return;
        const msgs = [];
        container.querySelectorAll('.chat-message').forEach(el => {
            if (el.id === 'typingIndicator') return; 
            const isUser = el.classList.contains('user');
            const content = el.querySelector('.message-content');
            const timeEl  = el.querySelector('.message-time');
            if (!content) return;
            msgs.push({
                role: isUser ? 'user' : 'bot',
                html: content.outerHTML,
                time: timeEl ? timeEl.textContent : '',
                type: content.classList.contains('warning') ? 'warning'
                    : content.classList.contains('danger')  ? 'danger'
                    : content.classList.contains('success') ? 'success'
                    : 'info'
            });
        });
        
        const trimmed = msgs.slice(-CHAT_MAX_SAVED);
        localStorage.setItem(CHAT_STORAGE_KEY, JSON.stringify(trimmed));
    } catch(e) {  }
}

function loadChatHistory() {
    try {
        const raw = localStorage.getItem(CHAT_STORAGE_KEY);
        if (!raw) return false;
        const msgs = JSON.parse(raw);
        if (!Array.isArray(msgs) || msgs.length === 0) return false;
        const container = document.getElementById('chatMessages');
        if (!container) return false;
        msgs.forEach(m => {
            if (m.role === 'user') {
                const html = `
                    <div class="chat-message user">
                        <div class="message-wrapper">
                            ${m.html}
                            <span class="message-time">${m.time}</span>
                        </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', html);
            } else {
                const html = `
                    <div class="chat-message bot">
                        <div class="message-avatar">
                            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                        </div>
                        <div class="message-wrapper">
                            ${m.html}
                            <span class="message-time">${m.time}</span>
                        </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', html);
            }
        });
        scrollToBottom();
        return true; 
    } catch(e) { return false; }
}

function saveChatTopic(name) {
    try { localStorage.setItem(CHAT_TOPIC_KEY, name); } catch(e) {}
}
function getChatTopic() {
    try { return localStorage.getItem(CHAT_TOPIC_KEY) || ''; } catch(e) { return ''; }
}
function clearChatTopic() {
    try { localStorage.removeItem(CHAT_TOPIC_KEY); } catch(e) {}
}

window.clearChatHistory = function() {
    try {
        localStorage.removeItem(CHAT_STORAGE_KEY);
        localStorage.removeItem(CHAT_TOPIC_KEY);
        sessionStorage.removeItem('chatbot_welcomed');
    } catch(e) {}
    const container = document.getElementById('chatMessages');
    if (container) container.innerHTML = '';
    
    setTimeout(() => {
        addBotMessage(getWelcomeMessage());
        sessionStorage.setItem('chatbot_welcomed', 'true');
    }, 300);
}

const notificationSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleRILMYTT4bJ2ChAse7zQ1LRzDhYoc7LD16x3GCBdqMbOtp1rJyBXqcPLsJtvMCRUpcTItpxxNylOoMPGr5t3PTFFm8TDqpd8STtBl8PBpp2CWUE7k8K/o52HX0g2jcK9n5yNaFIxh8G8nJySc182gMC6mZqYfmw/eb+4lpiegrVHcrq3k5WihFpTbLaykJKlil9ecLKvjpCpkGdndK2sjI6tl3R0dqmqiYyxnIF/eqWmhoqzt4qJgKGigoeyvoWRhJqdfoC5xoiZiZeZe3zAz4uhj5KVeHnC1pGpk46TeXbD3JexlYmQe3PE4JuymYGLeXjH5KG2nHyGgHrL6qm8oHaCiHzQ8LPCpW1+joHV97vIqWV6lIfa/sXOrV54mozy/87Tsl5znJT9/djYt1dvn5j//+DeumpomJz///3m5MFjZJOa///98u3LWl2Qm/v//vP22k9XjJzz/f308+VFUYic6vz99fbuOEiDnN/7/Pbv+y09f5rS+fv38P8hM3ua2Pn69vL/Gix1mtX4+fbx/xEna5nR9/j18P8LI2aYzPf49PD/Bx9il8j39/Tw/wQdX5bD9vfz7/8BHEQ=');

const dragState = {
    isDragging: false,
    startX: 0,
    startY: 0,
    initialRight: 30,
    initialBottom: 30,
    hasMoved: false,
    dragThreshold: 5 
};

function initDraggable() {
    const widget = document.getElementById('chatbotWidget');
    const toggle = widget.querySelector('.chatbot-toggle');
    const header = widget.querySelector('.chatbot-header');
    
    
    restorePosition();
    
    
    toggle.addEventListener('mousedown', startDrag);
    
    
    header.addEventListener('mousedown', startDrag);
    
    
    toggle.addEventListener('touchstart', startDrag, { passive: false });
    
    
    header.addEventListener('touchstart', startDrag, { passive: false });
    
    
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('touchend', endDrag);
}

function startDrag(e) {
    const widget = document.getElementById('chatbotWidget');
    
    
    if (e.target.closest('.chatbot-header-close') || 
        e.target.closest('.chatbot-input') ||
        e.target.closest('.chatbot-messages') ||
        e.target.closest('.quick-replies')) {
        return;
    }
    
    const touch = e.touches ? e.touches[0] : e;
    const rect = widget.getBoundingClientRect();
    
    dragState.isDragging = true;
    dragState.hasMoved = false;
    dragState.startX = touch.clientX;
    dragState.startY = touch.clientY;
    
    
    dragState.initialRight = window.innerWidth - rect.right;
    dragState.initialBottom = window.innerHeight - rect.bottom;
    
    
}

function onDrag(e) {
    if (!dragState.isDragging) return;
    
    const widget = document.getElementById('chatbotWidget');
    const touch = e.touches ? e.touches[0] : e;
    
    const deltaX = dragState.startX - touch.clientX;
    const deltaY = dragState.startY - touch.clientY;
    
    
    if (!dragState.hasMoved) {
        if (Math.abs(deltaX) > dragState.dragThreshold || Math.abs(deltaY) > dragState.dragThreshold) {
            dragState.hasMoved = true;
            widget.classList.add('is-dragging');
        } else {
            return; 
        }
    }
    
    
    e.preventDefault();
    
    
    let newRight = dragState.initialRight + deltaX;
    let newBottom = dragState.initialBottom + deltaY;
    
    
    const widgetRect = widget.getBoundingClientRect();
    const widgetWidth = widgetRect.width;
    const widgetHeight = widgetRect.height;
    
    
    const padding = 10;
    const maxRight = window.innerWidth - widgetWidth - padding;
    const maxBottom = window.innerHeight - widgetHeight - padding;
    
    newRight = Math.max(padding, Math.min(newRight, maxRight));
    newBottom = Math.max(padding, Math.min(newBottom, maxBottom));
    
    
    widget.style.right = newRight + 'px';
    widget.style.bottom = newBottom + 'px';
    widget.style.left = 'auto';
    widget.style.top = 'auto';
}

function endDrag(e) {
    if (!dragState.isDragging) return;
    
    const widget = document.getElementById('chatbotWidget');
    const wasDragging = dragState.hasMoved;
    
    dragState.isDragging = false;
    widget.classList.remove('is-dragging');
    
    if (wasDragging) {
        
        savePosition();
        
        
        e.preventDefault();
        e.stopPropagation();
        
        
        setTimeout(() => {
            dragState.hasMoved = false;
        }, 100);
    }
}

function savePosition() {
    const widget = document.getElementById('chatbotWidget');
    const position = {
        right: widget.style.right || '30px',
        bottom: widget.style.bottom || '30px'
    };
    try {
        localStorage.setItem('chatbot_position', JSON.stringify(position));
    } catch (e) {
        
    }
}

function restorePosition() {
    try {
        const saved = localStorage.getItem('chatbot_position');
        if (saved) {
            const position = JSON.parse(saved);
            const widget = document.getElementById('chatbotWidget');
            
            
            const right = parseInt(position.right) || 30;
            const bottom = parseInt(position.bottom) || 30;
            
            if (right >= 0 && right < window.innerWidth - 50 &&
                bottom >= 0 && bottom < window.innerHeight - 50) {
                widget.style.right = position.right;
                widget.style.bottom = position.bottom;
                widget.style.left = 'auto';
                widget.style.top = 'auto';
            }
        }
    } catch (e) {
        
    }
}

function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

document.addEventListener('DOMContentLoaded', function() {
    
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    
    if (chatInput) {
        chatInput.addEventListener('keypress', handleKeyPress);
        chatInput.addEventListener('input', handleInput);
    }
    
    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }
    
    
    initDraggable();
    
    
    setTimeout(function() {
        if (!sessionStorage.getItem('chatbot_welcomed')) {
            showBadge();
        }
    }, 3000);
});

window.toggleChatbot = function() {
    
    if (dragState.hasMoved) {
        return;
    }
    
    const widget = document.getElementById('chatbotWidget');
    widget.classList.toggle('active');
    
    if (widget.classList.contains('active')) {
        hideBadge();
        
        
        const container = document.getElementById('chatMessages');
        const hasMessages = container && container.querySelectorAll('.chat-message').length > 0;
        
        if (!hasMessages) {
            
            const restored = loadChatHistory();
            if (restored) {
                sessionStorage.setItem('chatbot_welcomed', 'true');
            } else if (!sessionStorage.getItem('chatbot_welcomed')) {
                
                sessionStorage.setItem('chatbot_welcomed', 'true');
                setTimeout(() => {
                    addBotMessage(getWelcomeMessage());
                }, 500);
            }
        }
        
        
        setTimeout(() => {
            document.getElementById('chatInput').focus();
        }, 300);
    }
}

window.triggerLoginFromChat = function() {
    
    const widget = document.getElementById('chatbotWidget');
    widget.classList.remove('active');
    
    
    if (typeof toggleLoginModal === 'function') {
        toggleLoginModal();
    } else {
        
        window.location.href = chatbotBaseUrl + '?login=1';
    }
}

function getWelcomeMessage() {
    return `👋 **Hello! Welcome to <?php echo htmlspecialchars($chatbot_system_name); ?>!**

I'm your **AI pharmacy assistant** powered by Google Gemini. 🤖

I can help you with:
• 💊 Medicine availability & prices
• ✅ FDA approval status
• 📋 Prescription requirements
• 🏪 Store hours & location
• 💰 Generic alternatives
• 📷 **NEW: Upload prescription or medicine images for analysis!**

Just ask me anything about medicines! 😊

_I understand both English and Bisaya/Cebuano._

⚠️ **Note:** I cannot verify prescription authenticity. Please bring original prescriptions to our pharmacy.`;
}

function showBadge() {
    document.getElementById('chatBadge').style.display = 'flex';
}

function hideBadge() {
    document.getElementById('chatBadge').style.display = 'none';
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    
    if (selectedImageData) {
        sendImageMessage();
        return;
    }
    
    if (!message) return;
    
    
    if (!isUserLoggedIn) {
        
        addBotMessage('Please login to chat with me! Click the button below to sign in.', 'info');
        
        
        const messagesContainer = document.getElementById('chatMessages');
        const loginBtnHtml = `
            <div class="chat-message bot" style="margin-top: 10px;">
                <div class="message-content" style="padding: 0;">
                    <button onclick="triggerLoginFromChat()" style="
                        width: 100%;
                        padding: 12px 20px;
                        background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
                        color: white;
                        border: none;
                        border-radius: 10px;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        transition: all 0.3s ease;
                    ">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Sign in with Google
                    </button>
                </div>
            </div>
        `;
        messagesContainer.insertAdjacentHTML('beforeend', loginBtnHtml);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        
        input.value = '';
        return;
    }
    
    
    if (message.length > 1000) {
        addBotMessage('Message too long. Please keep it under 1000 characters.', 'warning');
        return;
    }
    
    
    hideSuggestions();
    
    
    addUserMessage(message);
    
    
    input.value = '';
    
    
    showTypingIndicator();
    
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); 
    
    
    const savedTopic = getChatTopic();
    let bodyStr = 'message=' + encodeURIComponent(message);
    if (savedTopic) {
        bodyStr += '&topic_hint=' + encodeURIComponent(savedTopic);
    }
    
    fetch(chatbotBaseUrl + 'ajax.php?action=chatbot_chat', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: bodyStr,
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        
        hideTypingIndicator();
        
        
        if (data.topic) {
            saveChatTopic(data.topic);
        }
        
        
        addBotMessage(data.message, data.type);
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Chatbot error:', error);
        hideTypingIndicator();
        
        
        if (error.name === 'AbortError') {
            addBotMessage('Request timed out. Please try again.', 'warning');
        } else {
            addBotMessage('Sorry, I encountered an error. Please try again.', 'danger');
        }
    });
}

window.sendQuickReply = function(message) {
    document.getElementById('chatInput').value = message;
    sendMessage();
}

function addUserMessage(message) {
    const messagesContainer = document.getElementById('chatMessages');
    const time = getCurrentTime();
    const messageHtml = `
        <div class="chat-message user">
            <div class="message-wrapper">
                <div class="message-content">
                    ${escapeHtml(message)}
                </div>
                <span class="message-time">${time}</span>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
    scrollToBottom();
    saveChatHistory();
}

function addBotMessage(message, type = 'info') {
    const messagesContainer = document.getElementById('chatMessages');
    const time = getCurrentTime();
    
    
    let formattedMessage = escapeHtml(message);
    formattedMessage = formattedMessage.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    formattedMessage = formattedMessage.replace(/_(.*?)_/g, '<em>$1</em>');
    formattedMessage = formattedMessage.replace(/\n/g, '<br>');
    
    
    const clickableOptions = extractClickableOptions(message);
    let optionsHtml = '';
    
    if (clickableOptions.length > 0) {
        optionsHtml = '<div class="chat-follow-up-options">';
        clickableOptions.forEach(option => {
            optionsHtml += `<button class="follow-up-btn" onclick="sendFollowUpAnswer('${escapeHtml(option)}')">${escapeHtml(option)}</button>`;
        });
        optionsHtml += '</div>';
    }
    
    const messageHtml = `
        <div class="chat-message bot">
            <div class="message-avatar">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            </div>
            <div class="message-wrapper">
                <div class="message-content ${type}">
                    ${formattedMessage}
                </div>
                ${optionsHtml}
                <span class="message-time">${time}</span>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
    scrollToBottom();
    saveChatHistory();
    
    
    try {
        notificationSound.volume = 0.3;
        notificationSound.play().catch(() => {}); 
    } catch(e) {}
}

function extractClickableOptions(message) {
    const options = [];
    
    
    if (message.toLowerCase().includes('adult or a child') || message.toLowerCase().includes('adult or child')) {
        options.push('Adult', 'Child');
    }
    
    
    if (message.toLowerCase().includes('age') && (message.includes('?') || message.toLowerCase().includes('tell me'))) {
        options.push('Under 6 years', '6-12 years', '13-17 years', '18+ Adult', 'Senior (60+)');
    }
    
    
    if (message.toLowerCase().includes('how long') && message.toLowerCase().includes('symptom')) {
        options.push('Just started today', '2-3 days', '1 week', 'More than a week');
    }
    
    
    if (message.toLowerCase().includes('severity') || message.toLowerCase().includes('how severe') || message.toLowerCase().includes('mild or severe')) {
        options.push('Mild', 'Moderate', 'Severe');
    }
    
    
    if (message.toLowerCase().includes('do you have') || message.toLowerCase().includes('are you') || 
        message.toLowerCase().includes('have you') || message.toLowerCase().includes('is there')) {
        if (!options.length) {
            options.push('Yes', 'No');
        }
    }
    
    
    if (message.toLowerCase().includes('other symptoms') || message.toLowerCase().includes('any other')) {
        options.push('No other symptoms', 'Fever', 'Headache', 'Cough', 'Body pain');
    }
    
    
    if (message.toLowerCase().includes('pregnant') || message.toLowerCase().includes('breastfeeding')) {
        options.push('Not pregnant', 'Pregnant', 'Breastfeeding');
    }
    
    
    if (message.toLowerCase().includes('allerg')) {
        options.push('No allergies', 'Yes, I have allergies');
    }
    
    return options;
}

window.sendFollowUpAnswer = function(answer) {
    
    const input = document.getElementById('chatInput');
    input.value = answer;
    sendMessage();
}

function showTypingIndicator() {
    const messagesContainer = document.getElementById('chatMessages');
    const typingHtml = `
        <div class="chat-message bot" id="typingIndicator">
            <div class="message-avatar">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            </div>
            <div class="message-wrapper">
                <div class="message-content">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="typing-text"></div>
                </div>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', typingHtml);
    scrollToBottom();
    
    
    document.getElementById('sendBtn').disabled = true;
}

function hideTypingIndicator() {
    const typing = document.getElementById('typingIndicator');
    if (typing) typing.remove();
    
    
    document.getElementById('sendBtn').disabled = false;
}

function scrollToBottom() {
    const messages = document.getElementById('chatMessages');
    messages.scrollTop = messages.scrollHeight;
}

function handleKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    } else if (event.key === 'Escape') {
        hideSuggestions();
    }
}

function handleInput(event) {
    const query = event.target.value.trim();
    
    
    clearTimeout(searchTimeout);
    
    
    if (query.length < 1) {
        hideSuggestions();
        return;
    }
    
    
    searchTimeout = setTimeout(() => {
        searchProducts(query);
    }, 300);
}

function searchProducts(query) {
    const suggestionsDiv = document.getElementById('chatSuggestions');
    currentSearchQuery = query;
    
    
    suggestionsDiv.innerHTML = '<div class="suggestion-loading">🔍 Searching...</div>';
    suggestionsDiv.classList.add('show');
    
    
    fetch(chatbotBaseUrl + 'ajax.php?action=chatbot_search', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'query=' + encodeURIComponent(query)
    })
    .then(response => response.json())
    .then(data => {
        
        if (query === currentSearchQuery) {
            displaySuggestions(data, query);
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (query === currentSearchQuery) {
            suggestionsDiv.innerHTML = '<div class="suggestion-empty">❌ Search failed</div>';
        }
    });
}

function displaySuggestions(data, query) {
    const suggestionsDiv = document.getElementById('chatSuggestions');
    
    
    if (!data.products || data.products.length === 0) {
        suggestionsDiv.innerHTML = `<div class="suggestion-empty">No products found for "${escapeHtml(query)}"</div>`;
        return;
    }
    
    
    let html = '';
    for (const product of data.products) {
        const stockLabel = product.in_stock 
            ? `<span style="color:#10b981;">✅ In Stock (${product.stock})</span>` 
            : `<span style="color:#ef4444;">❌ Out of Stock</span>`;
        
        const safeName = product.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const cartBtn = product.in_stock 
            ? `<button class="suggestion-cart-btn" onclick="event.stopPropagation(); addToCartFromChat(${product.id}, '${safeName}')" title="Add to cart">🛒</button>` 
            : '';
        
        html += `
            <div class="suggestion-item" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); selectSuggestion('${safeName}')" role="button" tabindex="0">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="flex:1;">
                        <div class="suggestion-name">${escapeHtml(product.name)}</div>
                        <div class="suggestion-brand">${escapeHtml(product.brand)} ${product.dose ? '• ' + escapeHtml(product.dose) : ''}</div>
                        <div class="suggestion-price">₱${product.price} ${stockLabel}</div>
                    </div>
                    ${cartBtn}
                </div>
            </div>
        `;
    }
    
    suggestionsDiv.innerHTML = html;
}

window.selectSuggestion = function(productName) {
    document.getElementById('chatInput').value = productName;
    hideSuggestions();
    document.getElementById('chatInput').focus();
}

window.addToCartFromChat = function(productId, productName) {
    if (!isUserLoggedIn) {
        addBotMessage('Please login first to add items to your cart.', 'warning');
        hideSuggestions();
        return;
    }
    
    hideSuggestions();
    
    fetch(chatbotBaseUrl + 'ajax.php?action=add_to_cart', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId + '&quantity=1'
    })
    .then(r => r.text())
    .then(result => {
        if (result.trim() === '1') {
            addBotMessage('🛒 **' + productName + '** has been added to your cart! \n\nYou can continue shopping or [view your cart](' + chatbotBaseUrl + 'customer/?p=cart_list).', 'success');
            
            if (typeof updateCartCount === 'function') updateCartCount();
            
            const cartBadge = document.querySelector('.cart-count, #cart-count, .badge-cart');
            if (cartBadge) {
                cartBadge.textContent = parseInt(cartBadge.textContent || '0') + 1;
            }
        } else if (result.trim() === '2') {
            addBotMessage('❌ Sorry, **' + productName + '** is currently out of stock.', 'warning');
        } else {
            addBotMessage('⚠️ Could not add to cart. Please try again or add it from the product page.', 'warning');
        }
    })
    .catch(() => {
        addBotMessage('⚠️ Something went wrong. Please try again.', 'danger');
    });
}

function hideSuggestions() {
    const suggestionsDiv = document.getElementById('chatSuggestions');
    suggestionsDiv.classList.remove('show');
    suggestionsDiv.innerHTML = '';
}

document.addEventListener('click', function(event) {
    const suggestionsDiv = document.getElementById('chatSuggestions');
    const inputField = document.getElementById('chatInput');
    
    if (suggestionsDiv && !suggestionsDiv.contains(event.target) && event.target !== inputField) {
        hideSuggestions();
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.handleImageSelect = function(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        addBotMessage('⚠️ Please select a valid image file (JPEG, PNG, GIF, or WebP).', 'warning');
        event.target.value = '';
        return;
    }
    
    
    if (file.size > 10 * 1024 * 1024) {
        addBotMessage('⚠️ Image too large. Please select an image under 10MB.', 'warning');
        event.target.value = '';
        return;
    }
    
    
    const reader = new FileReader();
    reader.onload = function(e) {
        selectedImageData = e.target.result;
        selectedImageMimeType = file.type;
        
        
        const previewContainer = document.getElementById('imagePreviewContainer');
        const previewImg = document.getElementById('imagePreview');
        previewImg.src = selectedImageData;
        previewContainer.classList.add('active');
        
        
        document.getElementById('chatInput').placeholder = 'Add a message about the image (optional)...';
    };
    reader.readAsDataURL(file);
}

window.removeImage = function() {
    selectedImageData = null;
    selectedImageMimeType = null;
    
    
    document.getElementById('imagePreviewContainer').classList.remove('active');
    document.getElementById('imagePreview').src = '';
    document.getElementById('imageInput').value = '';
    
    
    document.getElementById('chatInput').placeholder = 'Type message or upload image...';
}

function sendImageMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!selectedImageData) {
        addBotMessage('⚠️ No image selected. Please select an image first.', 'warning');
        return;
    }
    
    
    addUserMessageWithImage(message || 'Uploaded an image for analysis');
    
    
    input.value = '';
    const imageData = selectedImageData;
    const mimeType = selectedImageMimeType;
    removeImage();
    
    
    showTypingIndicator();
    
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 60000); 
    
    fetch(chatbotBaseUrl + 'ajax.php?action=chatbot_analyze_image', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'image_base64=' + encodeURIComponent(imageData) + 
              '&mime_type=' + encodeURIComponent(mimeType) +
              '&message=' + encodeURIComponent(message),
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        hideTypingIndicator();
        addBotMessage(data.message, data.type);
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Image analysis error:', error);
        hideTypingIndicator();
        
        if (error.name === 'AbortError') {
            addBotMessage('⚠️ Image analysis timed out. Please try again with a smaller image.', 'warning');
        } else {
            addBotMessage('Sorry, I encountered an error analyzing the image. Please try again.', 'danger');
        }
    });
}

function addUserMessageWithImage(message) {
    const messagesContainer = document.getElementById('chatMessages');
    const time = getCurrentTime();
    const messageHtml = `
        <div class="chat-message user">
            <div class="message-wrapper">
                <div class="message-content">
                    <div style="margin-bottom: 8px;">
                        <img src="${selectedImageData}" alt="Uploaded image" style="max-width: 120px; max-height: 80px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3);">
                    </div>
                    📷 ${escapeHtml(message)}
                </div>
                <span class="message-time">${time}</span>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
    scrollToBottom();
}

})(); 
</script>
