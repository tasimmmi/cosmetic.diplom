/**
 * Единая проверка авторизации и обновление токена
 * Подключать на всех защищенных страницах
 */

var AUTH = {
    token: localStorage.getItem('access_token'),
    user: JSON.parse(localStorage.getItem('user') || '{}'),
    
    // Проверить и обновить токен при необходимости
    refreshIfNeeded: async function() {
        // Если токена нет - редирект
        if (!this.token) {
            console.log('[AUTH] No token, redirecting to login');
            window.location.href = '/frontend/login.php?redirect=' + encodeURIComponent(window.location.pathname);
            return false;
        }
        
        try {
            // Расшифровываем токен
            var payload = JSON.parse(atob(this.token.split('.')[1]));
            var expiresAt = payload.exp * 1000;
            var now = Date.now();
            var timeLeft = expiresAt - now;
            
            console.log('[AUTH] Token expires in:', Math.round(timeLeft / 1000), 'seconds');
            
            // Если истекает через 2 минуты - обновляем
            if (timeLeft < 120000) {
                console.log('[AUTH] Token expiring soon, refreshing...');
                
                var refreshToken = localStorage.getItem('refresh_token');
                if (!refreshToken) {
                    console.log('[AUTH] No refresh token, redirecting to login');
                    this.clear();
                    window.location.href = '/frontend/login.php';
                    return false;
                }
                
                var response = await fetch('/backend/public/api/auth/refresh', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ refresh_token: refreshToken })
                });
                
                if (response.ok) {
                    var data = await response.json();
                    if (data.success && data.data) {
                        this.token = data.data.access_token;
                        localStorage.setItem('access_token', this.token);
                        if (data.data.refresh_token) {
                            localStorage.setItem('refresh_token', data.data.refresh_token);
                        }
                        console.log('[AUTH] Token refreshed successfully!');
                        return true;
                    }
                }
                
                // Если не удалось обновить - редирект на вход
                console.log('[AUTH] Token refresh failed');
                this.clear();
                window.location.href = '/frontend/login.php';
                return false;
            }
            
            return true;
            
        } catch (e) {
            console.error('[AUTH] Token check error:', e);
            this.clear();
            window.location.href = '/frontend/login.php';
            return false;
        }
    },
    
    // Очистить данные
    clear: function() {
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('user');
        this.token = null;
        this.user = {};
    },
    
    // Получить заголовки для запросов
    getHeaders: function() {
        return {
            'Authorization': 'Bearer ' + this.token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    },
    
    // Выполнить запрос к API с автообновлением токена
    fetch: async function(url, options) {
        // Проверяем токен перед запросом
        await this.refreshIfNeeded();
        
        options = options || {};
        options.headers = this.getHeaders();
        
        var response = await fetch(url, options);
        
        // Если 401 - пробуем обновить токен и повторить
        if (response.status === 401) {
            console.log('[AUTH] Got 401, trying to refresh token...');
            
            var refreshed = await this.refreshIfNeeded();
            if (refreshed) {
                options.headers = this.getHeaders();
                response = await fetch(url, options);
            }
        }
        
        return response;
    }
};

// Автоматически проверяем при загрузке страницы
(async function() {
    var isAuthPage = window.location.pathname.includes('/login.php') || 
                     window.location.pathname.includes('/register.php');
    
    if (!isAuthPage) {
        var ok = await AUTH.refreshIfNeeded();
        if (!ok) return; // Уже редиректнули
    }
})();