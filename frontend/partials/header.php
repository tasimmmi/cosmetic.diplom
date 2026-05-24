<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Cosmetic' ?> - Косметологический портал</title>

    <script src="/frontend/js/roleGuard.js"></script>
    <script src="/frontend/js/auth-check.js"></script>

    <link rel="icon" type="image/x-icon" href="/frontend/images/favicon.ico">
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="/frontend/css/style.css">
    <link rel="stylesheet" href="/frontend/css/dashboard.css">
    <link rel="stylesheet" href="/frontend/css/responsive.css">

    <?php if (isset($extraStyles)): ?>
        <style><?= $extraStyles ?></style>
    <?php endif; ?>
</head>
<body>
    <div id="app">
        <!-- Оверлей -->
        <div class="mobile-overlay" id="mobile-overlay"></div>
        
        <!-- Шторка-меню -->
        <div class="mobile-panel" id="mobile-panel">
            <div class="mobile-panel-brand">Косметологический портал</div>
            <div class="mobile-panel-user" id="mobile-user"></div>
            <div class="mobile-panel-nav" id="mobile-nav"></div>
            <div class="mobile-panel-footer">
                <a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </div>
        </div>
        
        <!-- Мини-шапка для мобильных -->
        <div class="mobile-header">
            <a href="/frontend/" class="logo">Косметологический портал</a>
            <button class="burger-btn" id="burger-btn" aria-label="Меню">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- Обычная шапка (ПК) -->
        <header class="header">
            <div class="header-top">
                <div class="container">
                    <a href="/frontend/" class="logo">
                        <span>Косметологический портал</span>
                    </a>
                    <div class="header-links" id="header-links"></div>
                </div>
            </div>
        </header>
        
        <main class="main-content">