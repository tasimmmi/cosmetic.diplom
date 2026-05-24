<?php
$pageTitle = 'Сброс пароля';
$token = $_GET['token'] ?? '';

$extraStyles = '
    .auth-container { max-width: 450px; margin: 40px auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .auth-container h2 { margin-top: 0; text-align: center; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; color: #555; }
    .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
    .btn-primary { background: #667eea; color: white; width: 100%; }
    .btn-outline { background: white; border: 1px solid #ddd; width: 100%; margin-top: 10px; }
    .alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
    .alert-danger { background: #f8d7da; color: #721c24; }
    .alert-success { background: #d4edda; color: #155724; }
    .auth-links { text-align: center; margin-top: 15px; font-size: 13px; }
    .auth-links a { color: #667eea; text-decoration: none; }
';

include __DIR__ . '/partials/header-simple.php';
?>

<div class="auth-container">
    <?php if (empty($token)): ?>
        <h2>Ошибка</h2>
        <div class="alert alert-danger">Не указан токен для сброса пароля. Проверьте ссылку из письма.</div>
        <div class="auth-links"><a href="/frontend/forgot-password.php">Запросить новую ссылку</a></div>
    <?php else: ?>
        <h2>Новый пароль</h2>
        <p style="text-align:center;color:#888;font-size:13px;">Придумайте новый пароль для входа</p>
        
        <input type="hidden" id="reset-token" value="<?= htmlspecialchars($token) ?>">
        
        <div class="form-group">
            <label>Новый пароль</label>
            <input type="password" class="form-control" id="new-password" placeholder="Минимум 8 символов" autocomplete="new-password">
        </div>
        
        <div class="form-group">
            <label>Подтвердите пароль</label>
            <input type="password" class="form-control" id="confirm-password" placeholder="Повторите пароль" autocomplete="new-password">
        </div>
        
        <div id="error-msg" class="alert alert-danger" style="display:none;"></div>
        <div id="success-msg" class="alert alert-success" style="display:none;"></div>
        
        <button class="btn btn-primary" id="submit-btn" onclick="resetPassword()">Сохранить пароль</button>
        
        <div class="auth-links"><a href="/frontend/login.php">Вернуться ко входу</a></div>
    <?php endif; ?>
</div>

<script>
async function resetPassword() {
    var token = document.getElementById('reset-token').value;
    var newP = document.getElementById('new-password').value;
    var conf = document.getElementById('confirm-password').value;
    var btn = document.getElementById('submit-btn');
    
    document.getElementById('error-msg').style.display = 'none';
    document.getElementById('success-msg').style.display = 'none';
    
    if (!newP || !conf) { showError('Заполните все поля'); return; }
    if (newP !== conf) { showError('Пароли не совпадают'); return; }
    if (newP.length < 8) { showError('Минимум 8 символов'); return; }
    
    btn.disabled = true;
    btn.textContent = 'Сохранение...';
    
    try {
        var r = await fetch('/backend/public/api/auth/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token, password: newP, confirm_password: conf })
        });
        var d = await r.json();
        
        if (r.ok && d.success) {
            document.getElementById('success-msg').textContent = 'Пароль успешно изменён! Перенаправление на страницу входа...';
            document.getElementById('success-msg').style.display = 'block';
            setTimeout(function() { window.location.href = '/frontend/login.php'; }, 2000);
        } else {
            showError(d.error || 'Ошибка сброса пароля. Возможно, ссылка устарела.');
        }
    } catch(e) {
        showError('Ошибка соединения с сервером');
    }
    
    btn.disabled = false;
    btn.textContent = 'Сохранить пароль';
}

function showError(msg) {
    var el = document.getElementById('error-msg');
    el.textContent = msg;
    el.style.display = 'block';
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>