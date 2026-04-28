<?php
// Проверка авторизации через JWT токен в localStorage
// Этот файл подключается в начале каждой защищенной страницы
?>
<script>
// Проверяем наличие токена
const accessToken = localStorage.getItem('access_token');
const user = JSON.parse(localStorage.getItem('user') || '{}');

if (!accessToken || !user.id) {
    // Сохраняем текущий URL для редиректа после входа
    const currentUrl = encodeURIComponent(window.location.pathname + window.location.search);
    window.location.href = '/frontend/login.php?redirect=' + currentUrl;
}

// Проверяем роль пользователя
const requiredRole = '<?= $requiredRole ?? '' ?>';
if (requiredRole && user.role !== requiredRole) {
    if (user.role === 'cosmetologist') {
        window.location.href = '/frontend/cosmetologist/dashboard.php';
    } else {
        window.location.href = '/frontend/client/dashboard.php';
    }
}
</script>