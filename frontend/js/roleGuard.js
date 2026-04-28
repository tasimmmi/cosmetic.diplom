/**
 * Единый защитник роутов
 * Подключается на ВСЕ страницы
 */

(function() {
    'use strict';
    
    const token = localStorage.getItem('access_token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const currentPath = window.location.pathname;
    const isAuthenticated = !!(token && user.id);
    
    console.log('[RoleGuard] Path:', currentPath);
    console.log('[RoleGuard] Auth:', isAuthenticated ? 'Yes (' + user.role + ')' : 'No');
    
    // ========== ПУБЛИЧНЫЕ СТРАНИЦЫ ==========
    const publicPages = [
        '/frontend/',
        '/frontend/index.php',
        '/frontend/card.php',
        '/frontend/verify-email.php'
    ];
    
    const isPublicPage = publicPages.some(page => currentPath.includes(page) || currentPath === page);
    
    // ========== СТРАНИЦЫ ВХОДА И РЕГИСТРАЦИИ ==========
    const isLoginPage = currentPath.includes('/login.php');
    const isRegisterPage = currentPath.includes('/register.php');
    
    // ========== СТРАНИЦЫ КЛИЕНТА ==========
    const clientPages = [
        '/frontend/client/',
        '/frontend/client/dashboard.php',
        '/frontend/client/history.php'
    ];
    
    const isClientPage = clientPages.some(page => currentPath.includes(page));
    
    // ========== СТРАНИЦЫ КОСМЕТОЛОГА ==========
    const cosmetologistPages = [
        '/frontend/cosmetologist/',
        '/frontend/cosmetologist/dashboard.php',
        '/frontend/cosmetologist/bookings.php',
        '/frontend/cosmetologist/schedule.php',
        '/frontend/cosmetologist/services.php',
        '/frontend/cosmetologist/materials.php',
        '/frontend/cosmetologist/clients.php',
        '/frontend/cosmetologist/reports.php'
    ];
    
    const isCosmetologistPage = cosmetologistPages.some(page => currentPath.includes(page));
    
    // ========== СТРАНИЦА ПРОФИЛЯ ==========
    const isProfilePage = currentPath.includes('/frontend/profile.php');
    
    // ========== ЛОГИКА ПРОВЕРКИ ==========
    
    // 1. АВТОРИЗОВАН И НАХОДИТСЯ НА СТРАНИЦЕ ВХОДА ИЛИ РЕГИСТРАЦИИ
    if (isAuthenticated && (isLoginPage || isRegisterPage)) {
        const urlParams = new URLSearchParams(window.location.search);
        const redirect = urlParams.get('redirect');
        
        let redirectUrl;
        if (redirect) {
            redirectUrl = redirect;
        } else {
            redirectUrl = user.role === 'cosmetologist' 
                ? '/frontend/cosmetologist/dashboard.php' 
                : '/frontend/client/dashboard.php';
        }
        
        console.log('[RoleGuard] Authenticated user on auth page, redirecting to:', redirectUrl);
        window.location.href = redirectUrl;
        return;
    }
    
    // 2. НЕ АВТОРИЗОВАН, НО ПЫТАЕТСЯ ЗАЙТИ В ЗАЩИЩЕННЫЙ РАЗДЕЛ
    if (!isAuthenticated && (isClientPage || isCosmetologistPage || isProfilePage)) {
        console.log('[RoleGuard] Not authenticated, redirecting to login');
        window.location.href = '/frontend/login.php?redirect=' + encodeURIComponent(currentPath + window.location.search);
        return;
    }
    
    // 3. КЛИЕНТ ПЫТАЕТСЯ ЗАЙТИ В РАЗДЕЛ КОСМЕТОЛОГА
    if (isAuthenticated && user.role === 'client' && isCosmetologistPage) {
        console.log('[RoleGuard] Client cannot access cosmetologist area, redirecting to client dashboard');
        window.location.href = '/frontend/client/dashboard.php';
        return;
    }
    
    // 4. КОСМЕТОЛОГ ПЫТАЕТСЯ ЗАЙТИ В РАЗДЕЛ КЛИЕНТА
    if (isAuthenticated && user.role === 'cosmetologist' && isClientPage) {
        console.log('[RoleGuard] Cosmetologist cannot access client area, redirecting to cosmetologist dashboard');
        window.location.href = '/frontend/cosmetologist/dashboard.php';
        return;
    }
    
    console.log('[RoleGuard] Access granted');
    
})();