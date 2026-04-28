<?php
$pageTitle = 'Регистрация';
$redirect = $_GET['redirect'] ?? '';
$role = $_GET['role'] ?? 'client';

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
                <label class="form-label">Я хочу</label>
                <div class="role-selector">
                    <label class="role-card <?= $role === 'client' ? 'selected' : '' ?>" id="role-client">
                        <input type="radio" name="role" value="client" <?= $role === 'client' ? 'checked' : '' ?>>
                        <i class="fas fa-user"></i>
                        <h5>Клиент</h5>
                        <small>Буду записываться на услуги</small>
                    </label>
                    <label class="role-card <?= $role === 'cosmetologist' ? 'selected' : '' ?>" id="role-cosmetologist">
                        <input type="radio" name="role" value="cosmetologist" <?= $role === 'cosmetologist' ? 'checked' : '' ?>>
                        <i class="fas fa-spa"></i>
                        <h5>Косметолог</h5>
                        <small>Буду оказывать услуги</small>
                    </label>
                </div>
            </div>
            
            <div id="cosmetologist-fields" style="display: <?= $role === 'cosmetologist' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label for="first_name">Имя</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Анна">
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Иванова">
                </div>
                <div class="form-group">
                    <label for="address">Адрес работы</label>
                    <input type="text" class="form-control" id="address" name="address" placeholder="г. Могилев, ул. ...">
                </div>
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
                <input type="password" class="form-control" id="confirm_password" 
                       name="confirm_password" placeholder="Повторите пароль" required>
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

<script>
// Переключение роли
document.querySelectorAll('.role-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
        
        var role = this.querySelector('input').value;
        document.getElementById('cosmetologist-fields').style.display = 
            role === 'cosmetologist' ? 'block' : 'none';
    });
});

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
    if (data.role === 'cosmetologist') {
        if (!data.first_name || data.first_name.trim().length < 2) {
            var el = document.getElementById('first_name-error');
            if (el) el.textContent = 'Введите имя';
            return;
        }
        if (!data.last_name || data.last_name.trim().length < 2) {
            var el = document.getElementById('last_name-error');
            if (el) el.textContent = 'Введите фамилию';
            return;
        }
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
            role: data.role
        };
        
        if (data.role === 'cosmetologist') {
            userData.first_name = data.first_name;
            userData.last_name = data.last_name;
            userData.address = data.address || '';
        }
        
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

// Показать/скрыть пароль
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
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>