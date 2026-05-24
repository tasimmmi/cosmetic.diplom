<?php
$pageTitle = 'Восстановление пароля';
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
    .alert-success { background: #d4edda; color: #155724; }
    .alert-danger { background: #f8d7da; color: #721c24; }
    .auth-links { text-align: center; margin-top: 15px; font-size: 13px; }
    .auth-links a { color: #667eea; text-decoration: none; }
';

include __DIR__ . '/partials/header-simple.php';
?>

<div class="auth-container">
    <h2>Восстановление пароля</h2>
    
    <!-- Шаг 1: Ввод email -->
    <div id="step-email">
        <p style="text-align:center;color:#888;font-size:13px;">Введите email, указанный при регистрации. Мы отправим на него ссылку для сброса пароля.</p>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" id="forgot-email" placeholder="your@email.com">
        </div>
        
        <div id="forgot-error" class="alert alert-danger" style="display:none;"></div>
        <div id="forgot-success" class="alert alert-success" style="display:none;"></div>
        
        <button class="btn btn-primary" onclick="sendResetLink()">Отправить ссылку</button>
        
        <div class="auth-links">
            <a href="/frontend/login.php">Вернуться ко входу</a>
        </div>
    </div>
    
    <!-- Шаг 2: Новый пароль (скрыто) -->
    <div id="step-reset" style="display:none;">
        <p style="text-align:center;color:#888;font-size:13px;">Придумайте новый пароль</p>
        
        <input type="hidden" id="reset-token">
        
        <div class="form-group">
            <label>Новый пароль</label>
            <input type="password" class="form-control" id="new-password" placeholder="Минимум 8 символов">
        </div>
        
        <div class="form-group">
            <label>Подтвердите пароль</label>
            <input type="password" class="form-control" id="confirm-password" placeholder="Повторите пароль">
        </div>
        
        <div id="reset-error" class="alert alert-danger" style="display:none;"></div>
        <div id="reset-success" class="alert alert-success" style="display:none;"></div>
        
        <button class="btn btn-primary" onclick="resetPassword()">Сохранить пароль</button>
        
        <div class="auth-links">
            <a href="/frontend/login.php">Вернуться ко входу</a>
        </div>
    </div>
</div>

<script>
// Проверяем, есть ли token в URL (для шага 2)
var urlParams = new URLSearchParams(window.location.search);
var token = urlParams.get('token');
if (token) {
    document.getElementById('step-email').style.display = 'none';
    document.getElementById('step-reset').style.display = 'block';
    document.getElementById('reset-token').value = token;
}

async function sendResetLink() {
    var email = document.getElementById('forgot-email').value.trim();
    
    if (!email) {
        showError('forgot-error', 'Введите email');
        return;
    }
    
    var btn = document.querySelector('#step-email .btn');
    btn.disabled = true;
    btn.textContent = 'Отправка...';
    
    try {
        var r = await fetch('/backend/public/api/auth/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        var d = await r.json();
        
        if (r.ok && d.success) {
            document.getElementById('forgot-success').textContent = 'Ссылка для сброса пароля отправлена на ' + email;
            document.getElementById('forgot-success').style.display = 'block';
            document.getElementById('forgot-error').style.display = 'none';
        } else {
            showError('forgot-error', d.error || 'Ошибка отправки');
        }
    } catch(e) {
        showError('forgot-error', 'Ошибка соединения');
    }
    
    btn.disabled = false;
    btn.textContent = 'Отправить ссылку';
}

async function resetPassword() {
    var token = document.getElementById('reset-token').value;
    var newP = document.getElementById('new-password').value;
    var conf = document.getElementById('confirm-password').value;
    
    if (!newP || !conf) {
        showError('reset-error', 'Заполните все поля');
        return;
    }
    if (newP !== conf) {
        showError('reset-error', 'Пароли не совпадают');
        return;
    }
    if (newP.length < 8) {
        showError('reset-error', 'Минимум 8 символов');
        return;
    }
    
    var btn = document.querySelector('#step-reset .btn');
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
            document.getElementById('reset-success').textContent = 'Пароль изменён! Сейчас вы будете перенаправлены на страницу входа.';
            document.getElementById('reset-success').style.display = 'block';
            document.getElementById('reset-error').style.display = 'none';
            
            setTimeout(function() {
                window.location.href = '/frontend/login.php';
            }, 2000);
        } else {
            showError('reset-error', d.error || 'Ошибка сброса пароля');
        }
    } catch(e) {
        showError('reset-error', 'Ошибка соединения');
    }
    
    btn.disabled = false;
    btn.textContent = 'Сохранить пароль';
}

function showError(id, msg) {
    var el = document.getElementById(id);
    el.textContent = msg;
    el.style.display = 'block';
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>