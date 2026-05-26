<?php
$pageTitle = 'Регистрация';
$redirect = $_GET['redirect'] ?? '';

include __DIR__ . '/partials/header-simple.php';
?>

<div class="container">
    <div class="auth-container register-container">
        <div class="auth-header">
            <h2><i class="fas fa-user-plus"></i> Регистрация</h2>
            <p>Создайте аккаунт для записи к косметологам</p>
        </div>
        
        <form id="register-form">
            <input type="hidden" name="redirect" id="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <input type="hidden" name="role" value="client">
            
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Введите ваш email" required>
                <div class="form-error" id="email-error"></div>
            </div>
            
            <div class="form-group">
                <label for="fullname" class="form-label">
                    <i class="fas fa-user"></i> Полное имя
                </label>
                <input type="text" class="form-control" id="fullname" name="fullname" 
                       placeholder="Иванова Анна" required>
                <div class="form-error" id="fullname-error"></div>
            </div>
            
            <div class="form-group">
                <label for="phone" class="form-label">
                    <i class="fas fa-phone"></i> Телефон
                </label>
                <input type="tel" class="form-control" id="phone" name="phone" 
                       placeholder="+375 XX XXX XX XX" required>
                <div class="form-error" id="phone-error"></div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Пароль
                </label>
                <div class="password-field">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Не менее 8 символов" required>
                    <button type="button" class="password-toggle" id="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-error" id="password-error"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock"></i> Подтверждение пароля
                </label>
                <div class="password-field">
                    <input type="password" class="form-control" id="confirm_password" 
                           name="confirm_password" placeholder="Повторите пароль" required>
                    <button type="button" class="password-toggle" id="toggle-confirm-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-error" id="confirm_password-error"></div>
            </div>
            
            <div id="form-error" class="alert alert-danger" style="display: none;"></div>
            
            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
                <i class="fas fa-user-plus"></i> Зарегистрироваться
            </button>
            
            <div class="auth-footer">
                <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
            </div>
        </form>
        
        <div class="divider"><span>или</span></div>
        
        <button type="button" class="yandex-btn" id="yandex-register">
            <span style="font-weight: bold; margin-right: 8px;">Я</span>
            <span>Зарегистрироваться через Яндекс</span>
        </button>
    </div>
</div>

<style>
.password-field {
    position: relative;
    display: flex;
    align-items: center;
}
.password-field .form-control {
    flex: 1;
    padding-right: 40px;
}
.password-toggle {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: #999;
    font-size: 16px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.password-toggle:hover {
    color: #667eea;
}
</style>

<script>
// Обработка формы
document.getElementById('register-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    var formData = new FormData(e.target);
    var data = Object.fromEntries(formData.entries());
    
    // Очистка ошибок
    document.querySelectorAll('.form-error').forEach(function(el) { el.textContent = ''; });
    document.getElementById('form-error').style.display = 'none';
    
    // Валидация
    if (!data.email || !data.email.includes('@')) {
        document.getElementById('email-error').textContent = 'Введите корректный email';
        return;
    }
    if (!data.fullname || data.fullname.trim().length < 2) {
        document.getElementById('fullname-error').textContent = 'Введите ваше имя';
        return;
    }
    if (!data.phone || data.phone.length < 7) {
        document.getElementById('phone-error').textContent = 'Введите корректный телефон';
        return;
    }
    if (!data.password || data.password.length < 8) {
        document.getElementById('password-error').textContent = 'Пароль должен быть не менее 8 символов';
        return;
    }
    if (data.password !== data.confirm_password) {
        document.getElementById('confirm_password-error').textContent = 'Пароли не совпадают';
        return;
    }
    
    var submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Регистрация...';
    
    try {
        var userData = {
            email: data.email,
            password: data.password,
            fullname: data.fullname,
            phone: data.phone,
            role: 'client'
        };
        
        var response = await fetch('/backend/public/api/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        
        var result = await response.json();
        
        if (response.ok && result.success) {
            localStorage.setItem('remembered_email', data.email);
            
            alert('Регистрация успешна! Проверьте email для подтверждения.');
            
            setTimeout(function() {
                var redirect = data.redirect || '';
                window.location.href = redirect 
                    ? '/frontend/login.php?redirect=' + encodeURIComponent(redirect) 
                    : '/frontend/login.php';
            }, 2000);
        } else {
            showError(result.error || 'Ошибка регистрации');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Зарегистрироваться';
        }
    } catch (error) {
        showError('Ошибка соединения с сервером');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Зарегистрироваться';
    }
});

function showError(message) {
    var errorEl = document.getElementById('form-error');
    errorEl.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>' + message + '</span>';
    errorEl.style.display = 'flex';
}

// Яндекс регистрация
document.getElementById('yandex-register').addEventListener('click', async function() {
    try {
        var response = await fetch('/backend/public/api/auth/yandex');
        var data = await response.json();
        if (data.success && data.data.url) {
            window.location.href = data.data.url;
        }
    } catch (error) {
        alert('Ошибка регистрации через Яндекс');
    }
});

// Показать/скрыть пароль (основное поле)
document.getElementById('toggle-password').addEventListener('click', function() {
    var passwordInput = document.getElementById('password');
    var icon = this.querySelector('i');
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

// Показать/скрыть пароль (подтверждение)
document.getElementById('toggle-confirm-password').addEventListener('click', function() {
    var confirmInput = document.getElementById('confirm_password');
    var icon = this.querySelector('i');
    if (confirmInput.type === 'password') {
        confirmInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        confirmInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>