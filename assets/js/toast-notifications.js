

function getToastContainer() {
    var container = document.getElementById('globalToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'globalToastContainer';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
        document.body.appendChild(container);
    }
    return container;
}

function addToastStyles() {
    if (document.getElementById('globalToastStyles')) return;
    var style = document.createElement('style');
    style.id = 'globalToastStyles';
    style.textContent = `
        .global-toast {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: toastIn 0.3s ease;
            pointer-events: auto;
            min-width: 280px;
            max-width: 400px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
        }
        .global-toast.toast-success { background: linear-gradient(135deg, #0d9488, #14b8a6); color: white; }
        .global-toast.toast-error { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .global-toast.toast-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .global-toast.toast-info { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .global-toast.removing { animation: toastOut 0.3s ease forwards; }
        .global-toast .toast-icon { font-size: 18px; flex-shrink: 0; }
        .global-toast .toast-msg { flex: 1; font-weight: 500; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(100px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100px); } }
    `;
    document.head.appendChild(style);
}

window.alert_toast = function(msg, type, position) {
    addToastStyles();
    var container = getToastContainer();
    
    
    var icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ',
        danger: '✕'
    };
    
    
    type = type || 'info';
    if (type === 'danger') type = 'error';
    
    var toast = document.createElement('div');
    toast.className = 'global-toast toast-' + type;
    toast.innerHTML = '<span class="toast-icon">' + (icons[type] || 'ℹ') + '</span><span class="toast-msg">' + msg + '</span>';
    
    container.appendChild(toast);
    
    
    setTimeout(function() {
        toast.classList.add('removing');
        setTimeout(function() {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    }, 4000);
};

if (typeof window.start_loader === 'undefined') {
    window.start_loader = function() {
        if (document.getElementById('preloader')) return;
        var loader = document.createElement('div');
        loader.id = 'preloader';
        loader.innerHTML = '<div class="loader-holder"><div></div><div></div><div></div><div></div></div>';
        document.body.appendChild(loader);
    };
}

if (typeof window.end_loader === 'undefined') {
    window.end_loader = function() {
        var loader = document.getElementById('preloader');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(function() {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 300);
        }
    };
}
