<?php
$pageTitle = 'Вход';
$redirect = $_GET['redirect'] ?? '';
$booking = $_GET['booking'] ?? false;
$verified = $_GET['verified'] ?? false;

include __DIR__ . '/partials/header-simple.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h2><i class="fas fa-sign-in-alt"></i> Вход в систему</h2>
            <p>Войдите, чтобы записаться к косметологу или управлять записями</p>
        </div>
        
        <?php if ($booking): ?>
            <div class="booking-message">
                <h5><i class="fas fa-calendar-check"></i> Запись на услугу</h5>
                <p>Для бронирования выбранной услуги необходимо авторизоваться</p>
            </div>
        <?php endif; ?>
        
        <?php if ($verified): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Email успешно подтвержден! Теперь вы можете войти.</span>
            </div>
        <?php endif; ?>
        
        <form id="login-form">
            <input type="hidden" name="redirect" id="redirect" value="<?= htmlspecialchars($redirect) ?>">
            
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Введите ваш email" required autocomplete="email">
                <div class="form-error" id="email-error"></div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Пароль
                </label>
                <div class="password-field">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Введите ваш пароль" required>
                    <button type="button" class="password-toggle" id="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-error" id="password-error"></div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Запомнить меня</span>
                </label>
            </div>
            
            <div id="form-error" class="alert alert-danger" style="display: none;"></div>
            
            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>
            
            <div class="auth-links">
                <a href="register.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">
                    <i class="fas fa-user-plus"></i> Нет аккаунта? Зарегистрироваться
                </a>
                <a href="forgot-password.php">
                    <i class="fas fa-question-circle"></i> Забыли пароль?
                </a>
            </div>
        </form>
        
        <div class="divider"><span>или</span></div>
        
        <button type="button" class="yandex-btn" id="yandex-login">
            <span style="font-weight: bold; margin-right: 8px;">Я</span>
            <span>Войти через Яндекс</span>
        </button>
        
        <div class="auth-footer">
            <p class="text-muted">
                Вы косметолог? <a href="register.php?role=cosmetologist">Зарегистрируйтесь здесь</a>
            </p>
        </div>
    </div>
</div>

<script>
// Сохраняем redirect для Яндекс OAuth
const redirect = document.getElementById('redirect').value;
if (redirect) {
    sessionStorage.setItem('login_redirect', redirect);
}

// Обработка формы
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const remember = document.getElementById('remember').checked;
    const redirect = document.getElementById('redirect').value;
    
    // Очистка ошибок
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
    document.getElementById('form-error').style.display = 'none';
    
    if (!email) {
        document.getElementById('email-error').textContent = 'Введите email';
        return;
    }
    if (!password) {
        document.getElementById('password-error').textContent = 'Введите пароль';
        return;
    }
    
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вход...';
    
    try {
        const response = await fetch('/backend/public/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            localStorage.setItem('access_token', data.data.access_token);
            localStorage.setItem('refresh_token', data.data.refresh_token);
            localStorage.setItem('user', JSON.stringify(data.data.user));
            
            if (remember) {
                localStorage.setItem('remembered_email', email);
            }
            
            let redirectUrl = redirect;
            if (!redirectUrl) {
                redirectUrl = data.data.user.role === 'cosmetologist' 
                    ? '/cosmetologist/dashboard.php' 
                    : 'client/dashboard.php';
            }
            
            window.location.href = redirectUrl;
        } else {
            showError(data.error || 'Неверный email или пароль');
        }
    } catch (error) {
        showError('Ошибка соединения с сервером');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Войти';
    }
});

function showError(message) {
    const errorEl = document.getElementById('form-error');
    errorEl.querySelector('span') ? 
        errorEl.querySelector('span').textContent = message : 
        errorEl.textContent = message;
    errorEl.style.display = 'flex';
}

// Яндекс вход
document.getElementById('yandex-login').addEventListener('click', async () => {
    try {
        const response = await fetch('/backend/public/api/auth/yandex');
        const data = await response.json();
        if (data.success && data.data.url) {
            window.location.href = data.data.url;
        }
    } catch (error) {
        alert('Ошибка входа через Яндекс');
    }
});

// Показать/скрыть пароль
document.getElementById('toggle-password').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>