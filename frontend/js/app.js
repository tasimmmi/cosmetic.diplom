/**
 * Общие функции для фронтенда
 */

// Выход из системы
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

// Обновление шапки (информация о пользователе)
function updateHeader() {
    const linksContainer = document.getElementById('header-links');
    const actionsContainer = document.getElementById('header-actions');
    
    if (!linksContainer && !actionsContainer) return;
    
    const token = localStorage.getItem('access_token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    if (token && user.id) {
        const dashboardUrl = user.role === 'cosmetologist' 
            ? '/frontend/cosmetologist/dashboard.php' 
            : '/frontend/client/dashboard.php';
        
        if (linksContainer) {
            linksContainer.innerHTML = `
                <a href="${dashboardUrl}"><i class="fas fa-user"></i> ${user.email}</a>
                <a href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            `;
        }
        
        if (actionsContainer) {
            actionsContainer.innerHTML = '';
        }
    } else {
        if (linksContainer) {
            linksContainer.innerHTML = `
                <a href="/frontend/login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
                <a href="/frontend/register.php"><i class="fas fa-user-plus"></i> Регистрация</a>
            `;
        }
        
        if (actionsContainer) {
            actionsContainer.innerHTML = `
                <a href="/frontend/login.php" class="btn btn-outline btn-sm">Войти</a>
                <a href="/frontend/register.php" class="btn btn-primary btn-sm">Регистрация</a>
            `;
        }
    }
}

// Toast уведомления
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

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    updateHeader();
});

// Форматирование даты
function formatDateTime(datetime) {
    if (!datetime) return '—';
    const d = new Date(datetime);
    return d.toLocaleString('ru-RU', { 
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

// Экранирование HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}