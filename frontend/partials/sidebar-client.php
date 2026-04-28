<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-header">
        <h3>Личный кабинет</h3>
    </div>
    <nav class="sidebar-menu">
        <a href="/frontend/client/dashboard.php" class="sidebar-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar"></i>
            <span>Мои записи</span>
        </a>
        <a href="/frontend/client/history.php" class="sidebar-item <?= $currentPage === 'history.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            <span>История</span>
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