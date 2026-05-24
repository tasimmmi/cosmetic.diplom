function logout() {
    const refreshToken = localStorage.getItem('refresh_token');
    
    if (refreshToken) {
        fetch('/backend/public/api/auth/logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: refreshToken })
        }).finally(() => {
            localStorage.clear();
            window.location.href = '/frontend/';
        });
    } else {
        localStorage.clear();
        window.location.href = '/frontend/';
    }
}

function updateHeader() {
    const linksContainer = document.getElementById('header-links');
    if (!linksContainer) return;
    
    const token = localStorage.getItem('access_token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    if (token && user.id) {
        const dashboardUrl = user.role === 'cosmetologist' 
            ? '/frontend/cosmetologist/dashboard.php' 
            : '/frontend/client/dashboard.php';
        
        linksContainer.innerHTML = `
            <a href="${dashboardUrl}"><i class="fas fa-user"></i> ${user.email}</a>
            <a href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        `;
    } else {
        linksContainer.innerHTML = `
            <a href="/frontend/login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
            <a href="/frontend/register.php"><i class="fas fa-user-plus"></i> Регистрация</a>
        `;
    }
}

function showToast(message, type = 'info', duration = 4000) {
    let container = document.getElementById('toast-container');
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icons = { success: '✓', error: '✗', warning: '⚠', info: 'ℹ' };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(toast);
    setTimeout(() => toast.remove(), duration);
}

document.addEventListener('DOMContentLoaded', () => updateHeader());

function formatDateTime(datetime) {
    if (!datetime) return '—';
    const d = new Date(datetime);
    return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========== БУРГЕР-МЕНЮ ДЛЯ МОБИЛЬНЫХ ==========
(function() {
    var burger = document.getElementById('burger-btn');
    var panel = document.getElementById('mobile-panel');
    var overlay = document.getElementById('mobile-overlay');
    
    if (!burger || !panel) return;
    
    function openPanel() {
        burger.classList.add('active');
        panel.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closePanel() {
        burger.classList.remove('active');
        panel.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    burger.addEventListener('click', function() {
        panel.classList.contains('show') ? closePanel() : openPanel();
    });
    
    if (overlay) {
        overlay.addEventListener('click', closePanel);
    }
    
    // Заполняем мобильное меню из сайдбара
    var sidebar = document.querySelector('.dashboard-sidebar');
    if (sidebar) {
        var mobileNav = document.getElementById('mobile-nav');
        if (mobileNav) {
            mobileNav.innerHTML = sidebar.querySelector('.sidebar-menu').innerHTML;
        }
    }
    
    // Имя пользователя
    var user = JSON.parse(localStorage.getItem('user') || '{}');
    var mobileUser = document.getElementById('mobile-user');
    if (mobileUser && user.email) {
        mobileUser.innerHTML = '<strong>' + user.email + '</strong>' + (user.role === 'cosmetologist' ? 'Косметолог' : 'Клиент');
    }
})();