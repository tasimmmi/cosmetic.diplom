<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-header">
        <h3>Панель управления</h3>
    </div>
    <nav class="sidebar-menu">
        <a href="/frontend/cosmetologist/dashboard.php" class="sidebar-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i>
            <span>Главная</span>
        </a>
        <a href="/frontend/cosmetologist/bookings.php" class="sidebar-item <?= $currentPage === 'bookings.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar"></i>
            <span>Записи</span>
        </a>
        <a href="/frontend/cosmetologist/schedule.php" class="sidebar-item <?= $currentPage === 'schedule.php' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i>
            <span>Расписание</span>
        </a>
        <a href="/frontend/cosmetologist/services.php" class="sidebar-item <?= $currentPage === 'services.php' ? 'active' : '' ?>">
            <i class="fas fa-list"></i>
            <span>Услуги</span>
        </a>
        <a href="/frontend/cosmetologist/materials.php" class="sidebar-item <?= $currentPage === 'materials.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i>
            <span>Склад</span>
        </a>
        <a href="/frontend/cosmetologist/clients.php" class="sidebar-item <?= $currentPage === 'clients.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Клиенты</span>
        </a>
        <a href="/frontend/cosmetologist/reports.php" class="sidebar-item <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Отчеты</span>
        </a>
        <a href="/frontend/profile.php" class="sidebar-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>Профиль</span>
        </a>
        <a href="#" onclick="logout()" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Выйти</span>
        </a>
    </nav>
</aside>