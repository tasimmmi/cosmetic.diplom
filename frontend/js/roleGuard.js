/**
 * roleGuard.js - защита маршрутов по ролям
 */

(function() {
    'use strict';
    
    // ========== ОБРАБОТКА OAuth CALLBACK (сохранение токенов из URL) ==========
    const urlParams = new URLSearchParams(window.location.search);
    const accessToken = urlParams.get('access_token');
    const refreshToken = urlParams.get('refresh_token');
    
    // Проверяем наличие токенов в URL
    if (accessToken && refreshToken) {
        // Сохраняем токены
        localStorage.setItem('access_token', accessToken);
        localStorage.setItem('refresh_token', refreshToken);
        
        // Сохраняем данные пользователя
        const user = {};
        for (const [key, value] of urlParams.entries()) {
            if (key.startsWith('user[')) {
                const match = key.match(/user\[(.*?)\]/);
                if (match) {
                    let val = value;
                    // Декодируем URL-encoded строки
                    try {
                        val = decodeURIComponent(value);
                    } catch(e) {}
                    // Преобразуем числовые значения
                    if (match[1] === 'id' || match[1] === 'cosmetologist_id' || match[1] === 'client_id') {
                        val = parseInt(value);
                    }
                    // Преобразуем boolean
                    if (match[1] === 'email_verified') {
                        val = value === '1';
                    }
                    user[match[1]] = val;
                }
            }
        }
        
        if (Object.keys(user).length > 0) {
            localStorage.setItem('user', JSON.stringify(user));
        }
        
        // Очищаем URL от всех параметров и остаёмся на той же странице
        const cleanUrl = window.location.pathname;
        window.location.replace(cleanUrl);
        return;
    }
    
    // ========== ОСНОВНАЯ ЛОГИКА РОЛЕЙ ==========
    
    // Получаем данные из localStorage
    const token = localStorage.getItem('access_token');
    let user = null;
    
    try {
        const userStr = localStorage.getItem('user');
        if (userStr && userStr !== 'undefined') {
            user = JSON.parse(userStr);
        }
    } catch(e) {
        console.error('[RoleGuard] Error parsing user:', e);
    }
    
    let currentPath = window.location.pathname;
    
    // Нормализация пути
    if (currentPath === '/frontend/' || currentPath === '/frontend') {
        currentPath = '/frontend/login.php';
    }
    
    const isAuthenticated = !!(token && user && user.id);
    
    // ========== СТРАНИЦЫ АВТОРИЗАЦИИ ==========
    const authPages = [
        '/frontend/login.php',
        '/frontend/register.php',
        '/frontend/forgot-password.php',
        '/frontend/reset-password.php',
        '/frontend/verify-email.php'
    ];
    const isAuthPage = authPages.some(page => currentPath === page || currentPath.includes(page));
    
    // Если на странице авторизации и авторизован -> на дашборд
    if (isAuthPage && isAuthenticated) {
        const dashboardUrl = user.role === 'cosmetologist' 
            ? '/frontend/cosmetologist/dashboard.php' 
            : '/frontend/client/dashboard.php';
        window.location.replace(dashboardUrl);
        return;
    }
    
    // ========== ПУБЛИЧНЫЕ СТРАНИЦЫ ==========
    const publicPages = [
        '/frontend/index.php',
        '/frontend/card.php',
        '/frontend/logout.php'
    ];
    const isPublicPage = publicPages.some(page => currentPath.includes(page));
    
    if (isPublicPage) {
        return;
    }
    
    // ========== ЗАЩИЩЁННЫЕ СТРАНИЦЫ ==========
    const isClientPage = currentPath.includes('/frontend/client/');
    const isCosmetologistPage = currentPath.includes('/frontend/cosmetologist/');
    const isProfilePage = currentPath.includes('/frontend/profile.php');
    const isProtectedPage = isClientPage || isCosmetologistPage || isProfilePage;
    
    if (isProtectedPage) {
        // Не авторизован - перенаправляем на вход
        if (!isAuthenticated) {
            const redirectUrl = encodeURIComponent(currentPath + window.location.search);
            window.location.replace('/frontend/login.php?redirect=' + redirectUrl);
            return;
        }
        
        // Проверка ролей
        if (isClientPage && user.role !== 'client') {
            window.location.replace('/frontend/cosmetologist/dashboard.php');
            return;
        }
        
        if (isCosmetologistPage && user.role !== 'cosmetologist') {
            window.location.replace('/frontend/client/dashboard.php');
            return;
        }
    }
    
})();