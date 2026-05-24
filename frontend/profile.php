<?php
$pageTitle = 'Профиль';
$extraStyles = '
    .profile-container { max-width: 700px; margin: 0 auto; }
    .profile-section { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .profile-section h3 { margin-top: 0; font-size: 16px; font-weight: 600; color: #333; }
    .profile-field { display: flex; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .profile-label { width: 150px; font-weight: 500; color: #666; font-size: 13px; }
    .profile-value { flex: 1; font-size: 13px; }
    .oauth-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #eee; border-radius: 8px; }
    .form-control { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; box-sizing: border-box; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; margin-bottom: 4px; font-size: 13px; color: #555; }
    .btn { padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-outline { background: white; border: 1px solid #ddd; }
    .btn-outline-danger { background: white; border: 1px solid #dc3545; color: #dc3545; }
    .btn-sm { padding: 4px 10px; font-size: 11px; }
    .badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-secondary { background: #e2e3e5; color: #383d41; }
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: white; border-radius: 12px; padding: 25px; width: 95%; max-width: 450px; }
    .modal-box h3 { margin-top: 0; }
    
    .avatar-section { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; }
    .avatar-preview { width: 100px; height: 100px; border-radius: 50%; background: #f0f4ff; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid #667eea; flex-shrink: 0; }
    .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-preview .no-avatar { font-size: 36px; color: #667eea; }
    .avatar-actions { display: flex; gap: 8px; flex-direction: column; }
    #avatar-file { display: none; }
';

include __DIR__ . '/partials/header.php';
?>

<div class="container profile-container">
    <h1>Профиль</h1>
    <div id="profile-content"><div class="loading-container"><div class="loading-spinner"></div></div></div>
</div>

<div class="modal-overlay" id="edit-modal">
    <div class="modal-box">
        <h3>Редактировать профиль</h3>
        <div id="edit-form"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:15px;">
            <button class="btn btn-outline" onclick="closeModal('edit-modal')">Отмена</button>
            <button class="btn btn-primary" onclick="saveProfile()">Сохранить</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="password-modal">
    <div class="modal-box">
        <h3>Изменить пароль</h3>
        <div class="form-group"><label>Текущий пароль</label><input type="password" class="form-control" id="current-password"></div>
        <div class="form-group"><label>Новый пароль</label><input type="password" class="form-control" id="new-password"></div>
        <div class="form-group"><label>Подтвердите пароль</label><input type="password" class="form-control" id="confirm-password"></div>
        <button class="btn btn-primary" onclick="savePassword()" style="width:100%;">Сохранить</button>
        <p style="text-align:center;margin-top:10px;font-size:12px;"><a href="/frontend/forgot-password.php">Забыли пароль?</a></p>
        <button class="btn btn-outline" onclick="closeModal('password-modal')" style="width:100%;margin-top:8px;">Отмена</button>
    </div>
</div>

<div class="modal-overlay" id="set-password-modal">
    <div class="modal-box">
        <h3>Установить пароль</h3>
        <p style="font-size:13px;color:#888;">Для отвязки Яндекс аккаунта установите пароль.</p>
        <div class="form-group"><label>Новый пароль</label><input type="password" class="form-control" id="set-new-password"></div>
        <div class="form-group"><label>Подтвердите пароль</label><input type="password" class="form-control" id="set-confirm-password"></div>
        <button class="btn btn-primary" onclick="setPasswordAndUnlink()" style="width:100%;">Установить и отвязать</button>
        <p style="text-align:center;margin-top:10px;font-size:12px;"><a href="/frontend/forgot-password.php">Забыли пароль?</a></p>
        <button class="btn btn-outline" onclick="closeModal('set-password-modal')" style="width:100%;margin-top:8px;">Отмена</button>
    </div>
</div>

<input type="file" id="avatar-file" accept="image/*" onchange="uploadAvatar()">

<script src="/frontend/js/auth-check.js"></script>
<script>
var profileData = null, linkedYandex = false;
if (!AUTH.token) window.location.href = '/frontend/login.php?redirect=/frontend/profile.php';
loadProfile();

async function loadProfile() {
    var c = document.getElementById('profile-content');
    try {
        var r = await AUTH.fetch('/backend/public/api/users/me'), d = await r.json();
        if (d.success) {
            profileData = d.data.user;
            var ar = await AUTH.fetch('/backend/public/api/auth/linked-accounts'), ad = await ar.json();
            linkedYandex = (ad.data?.accounts || []).some(function(a) { return a.provider === 'yandex'; });
            c.innerHTML = buildHtml();
        }
    } catch(e) { c.innerHTML = '<p class="text-danger">Ошибка загрузки</p>'; }
}

function buildHtml() {
    var u = profileData, h = '';
    
    h += '<div class="profile-section">';
    h += '<div class="avatar-section">';
    h += '<div class="avatar-preview" id="avatar-preview">';
    if (u.avatar) {
        h += '<img src="' + u.avatar + '" alt="Аватар">';
    } else {
        h += '<div class="no-avatar"><i class="fas fa-user"></i></div>';
    }
    h += '</div>';
    h += '<div class="avatar-actions">';
    h += '<button class="btn btn-outline btn-sm" onclick="document.getElementById(\'avatar-file\').click()"><i class="fas fa-upload"></i> Загрузить</button>';
    if (u.avatar) {
        h += '<button class="btn btn-outline-danger btn-sm" onclick="deleteAvatar()"><i class="fas fa-trash"></i> Удалить</button>';
    }
    h += '</div></div>';
    
    h += '<h3>Основная информация</h3>';
    h += '<div class="profile-field"><span class="profile-label">Email</span><span class="profile-value">' + esc(u.email) + '</span></div>';
    h += '<div class="profile-field"><span class="profile-label">Роль</span><span class="profile-value">' + (u.role === 'cosmetologist' ? 'Косметолог' : 'Клиент') + '</span></div>';
    if (u.role === 'cosmetologist') {
        h += '<div class="profile-field"><span class="profile-label">Имя</span><span class="profile-value">' + esc(u.first_name||'—') + '</span></div>';
        h += '<div class="profile-field"><span class="profile-label">Фамилия</span><span class="profile-value">' + esc(u.last_name||'—') + '</span></div>';
        h += '<div class="profile-field"><span class="profile-label">Телефон</span><span class="profile-value">' + esc(u.cosmetologist_phone||u.phone||'—') + '</span></div>';
        h += '<div class="profile-field"><span class="profile-label">Адрес</span><span class="profile-value">' + esc(u.address||'—') + '</span></div>';
    } else {
        h += '<div class="profile-field"><span class="profile-label">Имя</span><span class="profile-value">' + esc(u.client_name||u.fullname||'—') + '</span></div>';
        h += '<div class="profile-field"><span class="profile-label">Телефон</span><span class="profile-value">' + esc(u.client_phone||u.phone||'—') + '</span></div>';
    }
    h += '<button class="btn btn-outline btn-sm" style="margin-top:12px;" onclick="editProfile()">Редактировать</button></div>';
    
    h += '<div class="profile-section"><h3>Безопасность</h3><button class="btn btn-outline btn-sm" onclick="showModal(\'password-modal\')">Изменить пароль</button></div>';
    
    h += '<div class="profile-section"><h3>Связанные аккаунты</h3><div class="oauth-item"><div><strong>Яндекс</strong> <span class="badge ' + (linkedYandex ? 'badge-success' : 'badge-secondary') + '">' + (linkedYandex ? 'Привязан' : 'Не привязан') + '</span></div>';
    h += linkedYandex ? '<button class="btn btn-outline-danger btn-sm" onclick="unlinkYandex()">Отвязать</button>' : '<button class="btn btn-outline btn-sm" onclick="linkYandex()">Привязать</button>';
    h += '</div></div>';
    
    h += '<div class="profile-section"><button class="btn btn-outline-danger" onclick="logoutAll()">Выйти со всех устройств</button></div>';
    return h;
}

async function uploadAvatar() {
    var file = document.getElementById('avatar-file').files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { alert('Максимальный размер 2MB'); return; }
    if (!file.type.startsWith('image/')) { alert('Только изображения'); return; }
    
    var reader = new FileReader();
    reader.onload = async function(e) {
        var base64 = e.target.result.split(',')[1]; // только base64 без префикса
        try {
            var r = await AUTH.fetch('/backend/public/api/users/profile', {
                method: 'PUT',
                body: JSON.stringify({ avatar: base64 })
            });
            var d = await r.json();
            if (r.ok && d.success) {
                profileData.avatar = 'data:image/jpeg;base64,' + base64;
                updateAvatarPreview();
                loadProfile();
            } else { alert(d.error || 'Ошибка загрузки'); }
        } catch(ex) { alert('Ошибка соединения'); }
    };
    reader.readAsDataURL(file);
}

async function deleteAvatar() {
    if (!confirm('Удалить аватар?')) return;
    try {
        var r = await AUTH.fetch('/backend/public/api/users/profile', {
            method: 'PUT',
            body: JSON.stringify({ avatar: null })
        });
        var d = await r.json();
        if (r.ok && d.success) {
            profileData.avatar = null;
            updateAvatarPreview();
            loadProfile();
        } else { alert(d.error || 'Ошибка'); }
    } catch(e) { alert('Ошибка соединения'); }
}

function updateAvatarPreview() {
    var preview = document.getElementById('avatar-preview');
    if (!preview) return;
    if (profileData.avatar) {
        preview.innerHTML = '<img src="' + profileData.avatar + '" alt="Аватар">';
    } else {
        preview.innerHTML = '<div class="no-avatar"><i class="fas fa-user"></i></div>';
    }
}

function editProfile() {
    var u = profileData, f = '';
    if (u.role === 'cosmetologist') {
        f += '<div class="form-group"><label>Имя</label><input type="text" class="form-control" id="edit-first-name" value="' + esc(u.first_name||'') + '"></div>';
        f += '<div class="form-group"><label>Фамилия</label><input type="text" class="form-control" id="edit-last-name" value="' + esc(u.last_name||'') + '"></div>';
        f += '<div class="form-group"><label>Телефон</label><input type="tel" class="form-control" id="edit-phone" value="' + esc(u.cosmetologist_phone||u.phone||'') + '"></div>';
        f += '<div class="form-group"><label>Адрес</label><input type="text" class="form-control" id="edit-address" value="' + esc(u.address||'') + '"></div>';
    } else {
        f += '<div class="form-group"><label>Имя</label><input type="text" class="form-control" id="edit-name" value="' + esc(u.client_name||u.fullname||'') + '"></div>';
        f += '<div class="form-group"><label>Телефон</label><input type="tel" class="form-control" id="edit-phone" value="' + esc(u.client_phone||u.phone||'') + '"></div>';
    }
    f += '<div class="form-group"><label>Email</label><input type="email" class="form-control" id="edit-email" value="' + esc(u.email) + '"></div>';
    document.getElementById('edit-form').innerHTML = f;
    showModal('edit-modal');
}

async function saveProfile() {
    var u = profileData, body = { phone: document.getElementById('edit-phone').value };
    if (u.role === 'cosmetologist') { body.first_name = document.getElementById('edit-first-name').value; body.last_name = document.getElementById('edit-last-name').value; body.address = document.getElementById('edit-address').value; }
    else { body.fullname = document.getElementById('edit-name').value; }
    var newEmail = document.getElementById('edit-email').value;
    if (newEmail !== u.email) body.email = newEmail;
    
    try {
        var r = await AUTH.fetch('/backend/public/api/users/profile', { method:'PUT', body:JSON.stringify(body) }), d = await r.json();
        if (r.ok && d.success) { closeModal('edit-modal'); loadProfile(); if (body.email) alert('На новый email отправлено письмо для подтверждения.'); }
        else alert(d.error || 'Ошибка');
    } catch(e) { alert('Ошибка соединения'); }
}

async function savePassword() {
    var cur = document.getElementById('current-password').value, newP = document.getElementById('new-password').value, conf = document.getElementById('confirm-password').value;
    if (!cur || !newP || !conf) { alert('Заполните все поля'); return; }
    if (newP !== conf) { alert('Пароли не совпадают'); return; }
    if (newP.length < 8) { alert('Минимум 8 символов'); return; }
    try {
        var r = await AUTH.fetch('/backend/public/api/users/change-password', { method:'POST', body:JSON.stringify({ current_password:cur, new_password:newP, confirm_password:conf }) }), d = await r.json();
        if (r.ok && d.success) { closeModal('password-modal'); alert('Пароль изменён'); }
        else alert(d.error || 'Ошибка');
    } catch(e) { alert('Ошибка соединения'); }
}

async function unlinkYandex() {
    if (!confirm('Отвязать Яндекс?')) return;
    try { 
        var r = await AUTH.fetch('/backend/public/api/auth/yandex/unlink', { method:'DELETE' }); 
        if (r.ok) { linkedYandex = false; loadProfile(); } 
        else { 
            var d = await r.json(); 
            if (d.error && d.error.includes('пароль')) { showModal('set-password-modal'); }
            else { alert(d.error || 'Ошибка'); }
        } 
    } catch(e) { alert('Ошибка'); }
}

async function setPasswordAndUnlink() {
    var newP = document.getElementById('set-new-password').value, conf = document.getElementById('set-confirm-password').value;
    if (!newP || !conf) { alert('Заполните все поля'); return; }
    if (newP !== conf) { alert('Пароли не совпадают'); return; }
    if (newP.length < 8) { alert('Минимум 8 символов'); return; }
    try {
        await AUTH.fetch('/backend/public/api/users/change-password', { method:'POST', body:JSON.stringify({ new_password:newP, confirm_password:conf, set_initial:true }) });
        var r = await AUTH.fetch('/backend/public/api/auth/yandex/unlink', { method:'DELETE' });
        if (r.ok) { closeModal('set-password-modal'); linkedYandex = false; loadProfile(); }
    } catch(e) { alert('Ошибка'); }
}

async function linkYandex() {
    try { var r = await AUTH.fetch('/backend/public/api/auth/yandex'), d = await r.json(); if (d.success) { sessionStorage.setItem('link_yandex_return', window.location.href); window.location.href = d.data.url; } }
    catch(e) { alert('Ошибка'); }
}

async function logoutAll() {
    if (!confirm('Выйти со всех устройств?')) return;
    try { await AUTH.fetch('/backend/public/api/auth/logout-all', { method:'POST' }); localStorage.clear(); window.location.href = '/frontend/'; }
    catch(e) { alert('Ошибка'); }
}

function showModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function esc(t) { if(!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>