<!DOCTYPE html>
<html lang="ru">
<script src="/frontend/js/auth-check.js"></script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Cosmetic' ?> - Cosmetic</title>
    
    <script src="../js/roleGuard.js"></script>

    <link rel="icon" type="image/x-icon" href="/frontend/images/favicon.ico">
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="/frontend/css/style.css">
    <link rel="stylesheet" href="/frontend/css/dashboard.css">

    <?php if (isset($extraStyles)): ?>
        <style><?= $extraStyles ?></style>
    <?php endif; ?>
</head>
<body>
    <div id="app">
        <header class="header">
            <div class="header-top">
                <div class="container">
                    <div class="header-contact">
                        <i class="fas fa-phone"></i> +375 (29) 123-45-67
                        <i class="fas fa-envelope ms-3"></i> info@cosmetic.helioho.st
                    </div>
                    <div class="header-links" id="header-links">
                        <!-- Заполняется через JS -->
                    </div>
                </div>
            </div>
            <div class="header-main">
                <div class="container">
                    <a href="/frontend/" class="logo">
                        <span>Cosmetic</span>
                    </a>
                    
                    <nav>
                        <ul class="nav-menu">
                            <li><a href="/frontend/">Главная</a></li>
                            <li><a href="/frontend/#how-it-works">Как работает</a></li>
                            <li><a href="/frontend/#contacts">Контакты</a></li>
                        </ul>
                    </nav>
                    
                    <div class="header-actions" id="header-actions">
                        <!-- Заполняется через JS -->
                    </div>
                </div>
            </div>
        </header>
        
        <main class="main-content">