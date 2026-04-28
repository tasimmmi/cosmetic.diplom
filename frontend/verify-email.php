<?php
$token = $_GET['token'] ?? '';
$error = null;
$success = false;

if ($token) {
    // Вызываем API для подтверждения
    $ch = curl_init('/backend/public/api/auth/verify-email?token=' . urlencode($token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $success = true;
    } else {
        $error = 'Недействительный или истекший токен подтверждения';
    }
} else {
    $error = 'Токен не указан';
}

$pageTitle = 'Подтверждение Email';
include __DIR__ . '/partials/header-simple.php';
?>

<div class="container">
    <div class="verify-container">
        <?php if ($success): ?>
            <div class="verify-icon">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
            </div>
            <h2 class="verify-title">Email подтвержден!</h2>
            <p class="verify-message">Ваш email успешно подтвержден. Теперь вы можете войти в систему.</p>
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Перейти ко входу
            </a>
        <?php else: ?>
            <div class="verify-icon error">
                <i class="fas fa-times-circle" style="color: #dc3545;"></i>
            </div>
            <h2 class="verify-title">Ошибка подтверждения</h2>
            <p class="verify-message"><?= htmlspecialchars($error) ?></p>
            <a href="login.php" class="btn btn-outline">Вернуться ко входу</a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>