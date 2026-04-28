<?php
$pageTitle = 'Профиль';
$extraStyles = '
    .profile-container { max-width: 800px; margin: 0 auto; }
    .profile-section { background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .profile-field { display: flex; padding: 12px 0; border-bottom: 1px solid #eee; }
    .profile-label { width: 150px; font-weight: 500; color: #666; }
    .profile-value { flex: 1; }
    .oauth-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; }
';

include __DIR__ . '/partials/header.php';
?>

<div class="container profile-container">
    <h1>Профиль</h1>
    
    <div id="profile-content">
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <p>Загрузка...</p>
        </div>
    </div>
</div>

<script>
const token = localStorage.getItem('access_token');
const user = JSON.parse(localStorage.getItem('user') || '{}');

if (!token) {
    window.location.href = '/frontend/login.php?redirect=/frontend/profile.php';
}

async function loadProfile() {
    const container = document.getElementById('profile-content');
    
    try {
        const response = await fetch('/backend/public/api/users/me', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await response.json();
        
        if (data.success) {
            const userData = data.data.user;
            const linkedAccounts = await loadLinkedAccounts();
            
            container.innerHTML = `
                <div class="profile-section">
                    <h3>Основная информация</h3>
                    <div class="profile-field">
                        <span class="profile-label">Email</span>
                        <span class="profile-value">${userData.email}</span>
                    </div>
                    <div class="profile-field">
                        <span class="profile-label">Роль</span>
                        <span class="profile-value">${userData.role === 'cosmetologist' ? 'Косметолог' : 'Клиент'}</span>
                    </div>
                    ${renderProfileFields(userData)}
                    <button class="btn btn-outline btn-sm mt-3" onclick="editProfile()">
                        <i class="fas fa-edit"></i> Редактировать
                    </button>
                </div>
                
                <div class="profile-section">
                    <h3>Безопасность</h3>
                    <button class="btn btn-outline btn-sm" onclick="changePassword()">
                        <i class="fas fa-lock"></i> Изменить пароль
                    </button>
                </div>
                
                <div class="profile-section">
                    <h3>Связанные аккаунты</h3>
                    <div class="oauth-item">
                        <div>
                            <strong>Яндекс</strong>
                            <span class="badge ${linkedAccounts.yandex ? 'badge-success' : 'badge-secondary'}">
                                ${linkedAccounts.yandex ? 'Привязан' : 'Не привязан'}
                            </span>
                        </div>
                        ${linkedAccounts.yandex ? `
                            <button class="btn btn-outline-danger btn-sm" onclick="unlinkYandex()">Отвязать</button>
                        ` : `
                            <button class="btn btn-outline btn-sm" onclick="linkYandex()">Привязать</button>
                        `}
                    </div>
                </div>
                
                <div class="profile-section">
                    <button class="btn btn-outline-danger" onclick="logoutAll()">
                        <i class="fas fa-sign-out-alt"></i> Выйти со всех устройств
                    </button>
                </div>
            `;
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки профиля</p>';
    }
}

function renderProfileFields(user) {
    if (user.role === 'cosmetologist') {
        return `
            <div class="profile-field">
                <span class="profile-label">Имя</span>
                <span class="profile-value">${user.first_name || '—'}</span>
            </div>
            <div class="profile-field">
                <span class="profile-label">Фамилия</span>
                <span class="profile-value">${user.last_name || '—'}</span>
            </div>
            <div class="profile-field">
                <span class="profile-label">Телефон</span>
                <span class="profile-value">${user.cosmetologist_phone || user.phone || '—'}</span>
            </div>
            <div class="profile-field">
                <span class="profile-label">Адрес</span>
                <span class="profile-value">${user.address || '—'}</span>
            </div>
        `;
    } else {
        return `
            <div class="profile-field">
                <span class="profile-label">Имя</span>
                <span class="profile-value">${user.client_name || user.fullname || '—'}</span>
            </div>
            <div class="profile-field">
                <span class="profile-label">Телефон</span>
                <span class="profile-value">${user.client_phone || user.phone || '—'}</span>
            </div>
        `;
    }
}

async function loadLinkedAccounts() {
    try {
        const response = await fetch('/backend/public/api/auth/linked-accounts', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await response.json();
        const accounts = data.data?.accounts || [];
        return {
            yandex: accounts.some(a => a.provider === 'yandex')
        };
    } catch {
        return { yandex: false };
    }
}

async function linkYandex() {
    try {
        const response = await fetch('/backend/public/api/auth/yandex');
        const data = await response.json();
        if (data.success) {
            sessionStorage.setItem('link_yandex_return', window.location.href);
            window.location.href = data.data.url;
        }
    } catch (error) {
        alert('Ошибка');
    }
}

async function unlinkYandex() {
    if (!confirm('Отвязать Яндекс аккаунт?')) return;
    
    try {
        await fetch('/backend/public/api/auth/yandex/unlink', {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        loadProfile();
    } catch (error) {
        alert('Ошибка');
    }
}

async function logoutAll() {
    if (!confirm('Выйти со всех устройств?')) return;
    
    try {
        await fetch('/backend/public/api/auth/logout-all', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        localStorage.clear();
        window.location.href = '/frontend/';
    } catch (error) {
        alert('Ошибка');
    }
}

loadProfile();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>