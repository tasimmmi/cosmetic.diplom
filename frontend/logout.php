<?php
// Просто редирект на главную с очисткой localStorage через JS
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Выход - Cosmetic</title>
</head>
<body>
    <script>
        // Отзываем токен на сервере
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
    </script>
</body>
</html>