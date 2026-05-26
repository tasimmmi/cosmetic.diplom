<?php
$pageTitle = 'Клиенты';
$extraStyles = '
    .client-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.2s; }
    .client-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    .client-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .client-name { font-size: 18px; font-weight: 600; }
    .client-stats { display: flex; gap: 20px; color: #666; font-size: 14px; flex-wrap: wrap; }
    .client-stats span { display: flex; align-items: center; gap: 5px; }
    .badge-active { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
    .badge-inactive { background: #e2e3e5; color: #383d41; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
    
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: white; border-radius: 12px; width: 95%; max-width: 1000px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; }
    .modal-box-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid #eee; }
    .modal-box-header h3 { margin: 0; }
    .modal-box-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
    .modal-box-body { display: flex; flex: 1; overflow: hidden; }
    .modal-left { flex: 1; padding: 20px; overflow-y: auto; }
    .modal-right { width: 350px; padding: 20px; background: #f8f9fa; overflow-y: auto; }
    
    /* Стили для слотов времени */
    .time-slot { padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 6px; cursor: pointer; text-align: center; font-size: 13px; transition: all 0.15s; }
    .time-slot:hover { background: #f0f4ff; border-color: #667eea; }
    .time-slot.selected { background: #667eea; color: white; border-color: #667eea; }
    .time-slot.other-time { border-style: dashed; color: #667eea; }
    
    .time-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin: 8px 0; }
    
    .history-item { padding: 12px 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; background: white; }
    .history-item:last-child { margin-bottom: 0; }
    .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .history-date-block { display: flex; align-items: center; gap: 8px; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .history-date { font-weight: 500; font-size: 14px; color: #333; }
    .history-service { font-size: 14px; color: #333; margin-bottom: 5px; padding-left: 16px; }
    .history-comment { font-size: 13px; color: #666; margin-bottom: 6px; padding: 5px 8px 5px 16px; border-left: 3px solid #667eea; border-radius: 0 4px 4px 0; background: #f8f9fa; }
    .history-actions { display: flex; gap: 6px; opacity: 0.4; transition: opacity 0.2s; }
    .history-item:hover .history-actions { opacity: 1; }
    
    .btn-icon { width: 28px; height: 28px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 11px; transition: all 0.2s; color: white; }
    .btn-icon:hover { transform: scale(1.15); }
    .btn-icon-confirm { background: #28a745; }
    .btn-icon-complete { background: #17a2b8; }
    .btn-icon-cancel { background: #dc3545; }
    .btn-icon-edit { background: #6c757d; }
    
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; margin-bottom: 4px; font-size: 13px; color: #555; }
    .form-control { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; box-sizing: border-box; }
    
    .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-outline { background: none; border: 1px solid #ddd; }
    .btn-sm { padding: 4px 8px; font-size: 11px; }
    .btn-block { display: block; width: 100%; }
    .btn-xs { padding: 3px 8px; font-size: 11px; border-radius: 3px; }
    
    .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .search-input { flex: 1; min-width: 200px; }
    
    .comment-edit-area { display: none; margin-top: 8px; }
    .comment-edit-area.show { display: block; }
    .comment-edit-area textarea { width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px; }
    .comment-edit-actions { display: flex; gap: 5px; margin-top: 5px; }
    
    .custom-time-block { display: none; margin-top: 8px; }
    .custom-time-block.show { display: block; }
    
    @media (max-width: 768px) {
        .modal-box-body { flex-direction: column; }
        .modal-right { width: 100%; }
        .time-grid { grid-template-columns: repeat(3, 1fr); }
    }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Клиенты</h1>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-user-plus"></i> Добавить клиента
                </button>
            </div>
            
            <div class="filter-bar">
                <input type="text" class="form-control search-input" id="search-clients" placeholder="Поиск по имени или телефону..." oninput="filterClients()">
                <select class="form-control" style="width: auto;" id="sort-clients" onchange="filterClients()">
                    <option value="recent">По дате</option>
                    <option value="name">По имени</option>
                    <option value="frequent">По визитам</option>
                    <option value="revenue">По сумме</option>
                </select>
            </div>
            
            <div id="clients-list">
                <div class="loading-container"><div class="loading-spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО #1: ДЕТАЛИ КЛИЕНТА (история + быстрая запись) -->
<div class="modal-overlay" id="detail-modal">
    <div class="modal-box">
        <div class="modal-box-header">
            <h3 id="modal-client-name">Клиент</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button class="btn btn-outline btn-sm" onclick="editCurrentClient()">
                    <i class="fas fa-edit"></i> Изменить
                </button>
                <button class="modal-box-close" onclick="closeModal()">&times;</button>
            </div>
        </div>
        <div class="modal-box-body">
            <!-- ЛЕВАЯ КОЛОНКА: История посещений -->
            <div class="modal-left" id="modal-history">
                <div class="loading-container"><div class="loading-spinner"></div></div>
            </div>
            
            <!-- ПРАВАЯ КОЛОНКА: Форма быстрой записи (улучшенная) -->
            <div class="modal-right">
                <h4 style="margin-top: 0;">📅 Записать на прием</h4>
                <div class="form-group">
                    <label>Услуга</label>
                    <select class="form-control" id="modal-service" onchange="loadAvailableSlots()">
                        <option value="">Выберите услугу</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата</label>
                    <input type="date" class="form-control" id="modal-date" onchange="loadAvailableSlots()">
                </div>
                <div class="form-group" id="time-section" style="display: none;">
                    <label>Время</label>
                    <div class="time-grid" id="time-slots"></div>
                    <div id="custom-time-block" class="custom-time-block">
                        <input type="time" class="form-control" id="modal-custom-time" step="1800">
                    </div>
                </div>
                <div class="form-group">
                    <label>Комментарий</label>
                    <textarea class="form-control" id="modal-comment" rows="2" placeholder="Необязательно"></textarea>
                </div>
                <button class="btn btn-primary btn-block" onclick="createBookingFromModal()">
                    <i class="fas fa-calendar-plus"></i> Записать
                </button>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО #2: ДОБАВЛЕНИЕ КЛИЕНТА -->
<div class="modal-overlay" id="add-modal">
    <div class="modal-box" style="max-width: 450px;">
        <div class="modal-box-header">
            <h3 id="add-modal-title">Добавить клиента</h3>
            <button class="modal-box-close" onclick="closeAddModal()">&times;</button>
        </div>
        <div style="padding: 20px;">
            <div class="form-group">
                <label>Имя *</label>
                <input type="text" class="form-control" id="add-name" placeholder="Имя и фамилия">
            </div>
            <div class="form-group">
                <label>Телефон *</label>
                <input type="tel" class="form-control" id="add-phone" placeholder="+375...">
            </div>
            <div class="form-group">
                <label>Предпочитаемый способ связи</label>
                <select class="form-control" id="add-comm">
                    <option value="phone">Телефон</option>
                    <option value="email">Email</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="telegram">Telegram</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="btn btn-outline" onclick="closeAddModal()">Отмена</button>
                <button class="btn btn-primary" onclick="saveNewClient()">
                    <i class="fas fa-save"></i> Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО #3: РЕДАКТИРОВАНИЕ КЛИЕНТА -->
<div class="modal-overlay" id="edit-modal">
    <div class="modal-box" style="max-width: 450px;">
        <div class="modal-box-header">
            <h3 id="edit-modal-title">Изменить данные клиента</h3>
            <button class="modal-box-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div style="padding: 20px;">
            <input type="hidden" id="edit-id">
            <div class="form-group">
                <label>Имя *</label>
                <input type="text" class="form-control" id="edit-name" placeholder="Имя и фамилия">
            </div>
            <div class="form-group">
                <label>Телефон *</label>
                <input type="tel" class="form-control" id="edit-phone" placeholder="+375...">
            </div>
            <div class="form-group">
                <label>Предпочитаемый способ связи</label>
                <select class="form-control" id="edit-comm">
                    <option value="phone">Телефон</option>
                    <option value="email">Email</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="telegram">Telegram</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="btn btn-outline" onclick="closeEditModal()">Отмена</button>
                <button class="btn btn-primary" onclick="saveEditedClient()">
                    <i class="fas fa-save"></i> Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var clients = [];
var currentDetailId = null;
var currentClientData = null;
var services = [];
var cosmetologistId = null;

// Получаем ID косметолога из токена
(function init() {
    var token = localStorage.getItem('access_token');
    if (token) {
        try {
            var p = JSON.parse(atob(token.split('.')[1]));
            cosmetologistId = p.cosmetologist_id;
        } catch(e) {}
    }
})();

// ========== ЗАГРУЗКА СПИСКА КЛИЕНТОВ ==========
async function loadClients() {
    var container = document.getElementById('clients-list');
    container.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>';
    
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/clients');
        var data = await response.json();
        
        if (data.success) {
            clients = data.data.clients || [];
            renderClients(clients);
        } else {
            container.innerHTML = '<p class="text-danger text-center py-5">Ошибка загрузки клиентов</p>';
        }
    } catch(e) {
        container.innerHTML = '<p class="text-danger text-center py-5">Ошибка соединения с сервером</p>';
    }
}

function renderClients(list) {
    var container = document.getElementById('clients-list');
    
    if (list.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-5">Клиенты не найдены</p>';
        return;
    }
    
    var html = '';
    list.forEach(function(cl) {
        var isRegistered = cl.user_id && cl.user_id > 0;
        var icon = isRegistered 
            ? '<span title="Зарегистрирован самостоятельно" class="reg-icon">' +
                '<svg width="16" height="16" viewBox="0 0 16 16" style="vertical-align:middle; margin-left:2px;">' +
                    '<circle cx="8" cy="5" r="3" fill="#667eea"/>' +
                    '<ellipse cx="8" cy="13" rx="5" ry="3" fill="#667eea"/>' +
                '</svg>' +
              '</span>' 
            : '';
        
        html += '<div class="client-card" onclick="openDetail(' + cl.id + ')">' +
            '<div class="client-header">' +
                '<span class="client-name">' + esc(cl.fullname) + icon + '</span>' +
                '<span class="' + (cl.is_active == 1 ? 'badge-active' : 'badge-inactive') + '">' + 
                    (cl.is_active == 1 ? 'Активный' : 'Неактивный') + 
                '</span>' +
            '</div>' +
            '<div class="client-stats">' +
                '<span><i class="fas fa-phone"></i> ' + esc(cl.phone) + '</span>' +
                '<span><i class="fas fa-calendar-check"></i> Визитов: ' + (cl.visit_count || 0) + '</span>' +
                '<span><i class="fas fa-money-bill"></i> ' + (cl.total_spent || 0) + ' BYN</span>' +
                (cl.last_visit ? '<span><i class="fas fa-clock"></i> ' + fmtDate(cl.last_visit) + '</span>' : '') +
            '</div>' +
        '</div>';
    });
    container.innerHTML = html;
}

function filterClients() {
    var query = document.getElementById('search-clients').value.toLowerCase();
    var sort = document.getElementById('sort-clients').value;
    
    var list = clients.filter(function(x) {
        return x.fullname.toLowerCase().includes(query) || x.phone.includes(query);
    });
    
    if (sort === 'name') {
        list.sort(function(a, b) { return a.fullname.localeCompare(b.fullname); });
    } else if (sort === 'frequent') {
        list.sort(function(a, b) { return (b.visit_count || 0) - (a.visit_count || 0); });
    } else if (sort === 'revenue') {
        list.sort(function(a, b) { return (b.total_spent || 0) - (a.total_spent || 0); });
    } else if (sort === 'recent') {
        list.sort(function(a, b) { return new Date(b.last_visit) - new Date(a.last_visit); });
    }
    
    renderClients(list);
}

// ========== ЗАГРУЗКА УСЛУГ ==========
async function loadServices() {
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/services');
        var data = await response.json();
        if (data.success) {
            services = data.data.services || [];
            var select = document.getElementById('modal-service');
            select.innerHTML = '<option value="">Выберите услугу</option>';
            services.forEach(function(s) {
                select.innerHTML += '<option value="' + s.id + '">' + esc(s.service) + ' — ' + s.price + ' BYN (' + s.duration + ')</option>';
            });
        }
    } catch(e) {}
}

// ========== ЗАГРУЗКА ДОСТУПНЫХ СЛОТОВ ==========
async function loadAvailableSlots() {
    var date = document.getElementById('modal-date').value;
    var serviceId = document.getElementById('modal-service').value;
    var timeSection = document.getElementById('time-section');
    var grid = document.getElementById('time-slots');
    var customBlock = document.getElementById('custom-time-block');
    
    if (!date || !serviceId || !cosmetologistId) {
        timeSection.style.display = 'none';
        return;
    }
    
    timeSection.style.display = 'block';
    grid.innerHTML = '<span style="font-size:12px;color:#999;">Загрузка...</span>';
    customBlock.classList.remove('show');
    
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologists/' + cosmetologistId + '/slots?date=' + date + '&service_id=' + serviceId);
        var d = await r.json();
        var html = '';
        if (d.success && d.data.slots && d.data.slots.length > 0) {
            d.data.slots.forEach(function(s) {
                var time = s.begin_time.split(' ')[1]?.substring(0, 5) || '';
                html += '<div class="time-slot" onclick="selectTimeSlot(\'' + time + '\', this)">' + time + '</div>';
            });
        }
        html += '<div class="time-slot other-time" onclick="showCustomTime()">' + (html === '' ? 'Добавить время' : 'Другое время') + '</div>';
        grid.innerHTML = html;
    } catch(e) {
        grid.innerHTML = '<span style="font-size:12px;color:#d9534f;">Ошибка загрузки</span>';
    }
}

function selectTimeSlot(time, el) {
    document.querySelectorAll('#time-slots .time-slot').forEach(function(s) {
        s.classList.remove('selected');
    });
    el.classList.add('selected');
    document.getElementById('custom-time-block').classList.remove('show');
}

function showCustomTime() {
    document.querySelectorAll('#time-slots .time-slot').forEach(function(s) {
        s.classList.remove('selected');
    });
    document.getElementById('custom-time-block').classList.add('show');
    document.getElementById('modal-custom-time').focus();
}

// ========== МОДАЛЬНОЕ ОКНО #1: ДЕТАЛИ (история + запись) ==========
async function openDetail(id) {
    currentDetailId = id;
    document.getElementById('detail-modal').classList.add('show');
    document.getElementById('modal-history').innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>';
    
    // Сбрасываем форму записи
    document.getElementById('modal-service').value = '';
    document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('modal-date').min = new Date().toISOString().split('T')[0];
    document.getElementById('modal-comment').value = '';
    document.getElementById('time-section').style.display = 'none';
    document.getElementById('time-slots').innerHTML = '';
    document.getElementById('custom-time-block').classList.remove('show');
    document.getElementById('modal-custom-time').value = '';
    
    await loadServices();
    
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/clients/' + id);
        var data = await response.json();
        
        if (data.success) {
            currentClientData = data.data.client;
            document.getElementById('modal-client-name').textContent = esc(currentClientData.fullname);
            renderClientHistory(data.data.history || []);
        }
    } catch(e) {
        document.getElementById('modal-history').innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function closeModal() {
    document.getElementById('detail-modal').classList.remove('show');
    currentDetailId = null;
    currentClientData = null;
}

function renderClientHistory(history) {
    var container = document.getElementById('modal-history');
    
    if (history.length === 0) {
        container.innerHTML = '<p class="text-muted">Нет записей</p>';
        return;
    }
    
    var statusNames = { pending: 'Ожидает', confirmed: 'Подтверждена', completed: 'Завершена', cancelled: 'Отменена' };
    var statusColors = { pending: '#f0ad4e', confirmed: '#5bc0de', completed: '#5cb85c', cancelled: '#d9534f' };
    
    var html = '';
    history.forEach(function(h) {
        var date = new Date(h.schedule);
        var dateStr = date.toLocaleDateString('ru-RU');
        var timeStr = date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        var statusName = statusNames[h.status] || h.status;
        var statusColor = statusColors[h.status] || '#999';
        var safeComment = h.description ? h.description.replace(/'/g, "\\'") : '';
        
        html += '<div class="history-item" data-booking-id="' + h.booking_id + '">';
        html += '<div class="history-header">';
        html += '<div class="history-date-block">';
        html += '<span class="status-dot" style="background:' + statusColor + '"></span>';
        html += '<span class="history-date">' + dateStr + ' в ' + timeStr + '</span>';
        html += '</div>';
        html += '<div class="history-actions">';
        if (h.status === 'pending') {
            html += '<button class="btn-icon btn-icon-confirm" onclick="confirmBooking(' + h.booking_id + ')" title="Подтвердить"><i class="fas fa-check"></i></button>';
        }
        if (h.status === 'confirmed') {
            html += '<button class="btn-icon btn-icon-complete" onclick="completeBooking(' + h.booking_id + ')" title="Завершить"><i class="fas fa-flag-checkered"></i></button>';
        }
        if (h.status === 'pending' || h.status === 'confirmed') {
            html += '<button class="btn-icon btn-icon-cancel" onclick="cancelBooking(' + h.booking_id + ')" title="Отменить"><i class="fas fa-times"></i></button>';
        }
        html += '<button class="btn-icon btn-icon-edit" onclick="editComment(' + h.booking_id + ', \'' + safeComment + '\')" title="Комментарий"><i class="fas fa-pen"></i></button>';
        html += '</div></div>';
        html += '<div class="history-service">' + esc(h.service) + ' — <strong>' + (h.price || 0) + ' BYN</strong></div>';
        if (h.description) {
            html += '<div class="history-comment" id="comment-text-' + h.booking_id + '">💬 ' + esc(h.description) + '</div>';
        }
        html += '</div>';
    });
    container.innerHTML = html;
}

// ========== ДЕЙСТВИЯ С ЗАПИСЯМИ ==========
async function confirmBooking(id) {
    if (!confirm('Подтвердить запись?')) return;
    await AUTH.fetch('/backend/public/api/cosmetologist/bookings/' + id + '/confirm', { method: 'PUT' });
    openDetail(currentDetailId);
    loadClients();
}

async function completeBooking(id) {
    if (!confirm('Завершить запись?')) return;
    await AUTH.fetch('/backend/public/api/cosmetologist/bookings/' + id + '/complete', { method: 'PUT' });
    openDetail(currentDetailId);
    loadClients();
}

async function cancelBooking(id) {
    if (!confirm('Отменить запись?')) return;
    await AUTH.fetch('/backend/public/api/bookings/' + id + '/cancel', { method: 'PUT' });
    openDetail(currentDetailId);
    loadClients();
}

function editComment(id, oldComment) {
    var newComment = prompt('Введите комментарий:', oldComment || '');
    if (newComment !== null) {
        saveComment(id, newComment);
    }
}

async function saveComment(id, comment) {
    await AUTH.fetch('/backend/public/api/bookings/' + id + '/comment', {
        method: 'PUT',
        body: JSON.stringify({ description: comment })
    });
    openDetail(currentDetailId);
    loadClients();
}

// ========== СОЗДАНИЕ ЗАПИСИ (как в bookings.php) ==========
async function createBookingFromModal() {
    var serviceId = document.getElementById('modal-service').value;
    var date = document.getElementById('modal-date').value;
    var comment = document.getElementById('modal-comment').value.trim();
    var time = null;
    
    var sel = document.querySelector('#time-slots .time-slot.selected');
    var cust = document.getElementById('modal-custom-time');
    
    if (sel && !sel.classList.contains('other-time') && !sel.classList.contains('no-slots')) {
        time = sel.textContent.trim();
    } else if (cust.value) {
        time = cust.value;
    }
    
    if (!serviceId) { alert('Выберите услугу'); return; }
    if (!date) { alert('Выберите дату'); return; }
    if (!time) { alert('Выберите время'); return; }
    if (!currentDetailId) { alert('Клиент не выбран'); return; }
    
    var schedule = date + ' ' + time + ':00';
    
    // Находим услугу для получения длительности (если нужно для кастомного времени)
    var service = services.find(function(s) { return s.id == serviceId; });
    
    if (!service) {
        alert('Услуга не найдена');
        return;
    }
    
    // Отправляем запрос на создание записи (всю проверку делает бэкенд)
    try {
        var r = await AUTH.fetch('/backend/public/api/bookings', {
            method: 'POST',
            body: JSON.stringify({
                cosmetologist_id: cosmetologistId,
                client_id: parseInt(currentDetailId),
                service_id: parseInt(serviceId),
                schedule: schedule,
                description: comment
            })
        });
        var d = await r.json();
        
        if (r.ok && d.success) {
            alert('Запись успешно создана!');
            openDetail(currentDetailId);
            loadClients();
        } else {
            alert(d.error || 'Ошибка создания записи');
        }
    } catch(e) {
        alert('Ошибка соединения');
    }
}

// ========== ДОБАВИТЬ/РЕДАКТИРОВАТЬ КЛИЕНТА ==========
function openAddModal() {
    document.getElementById('add-modal-title').textContent = 'Добавить клиента';
    document.getElementById('add-name').value = '';
    document.getElementById('add-phone').value = '';
    document.getElementById('add-comm').value = 'phone';
    document.getElementById('add-modal').classList.add('show');
}

function closeAddModal() {
    document.getElementById('add-modal').classList.remove('show');
}

async function saveNewClient() {
    var name = document.getElementById('add-name').value.trim();
    var phone = document.getElementById('add-phone').value.trim();
    var comm = document.getElementById('add-comm').value;
    
    if (!name || !phone) {
        alert('Заполните имя и телефон');
        return;
    }
    
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/clients', {
            method: 'POST',
            body: JSON.stringify({ fullname: name, phone: phone, communication: comm })
        });
        var result = await response.json();
        
        if (response.ok && result.success) {
            closeAddModal();
            loadClients();
            alert('Клиент добавлен');
        } else {
            alert(result.error || 'Ошибка добавления');
        }
    } catch(e) {
        alert('Ошибка соединения');
    }
}

function editCurrentClient() {
    if (!currentClientData) return;
    document.getElementById('edit-modal-title').textContent = 'Редактировать клиента';
    document.getElementById('edit-id').value = currentClientData.id;
    document.getElementById('edit-name').value = currentClientData.fullname || '';
    document.getElementById('edit-phone').value = currentClientData.phone || '';
    document.getElementById('edit-comm').value = currentClientData.communication || 'phone';
    document.getElementById('edit-modal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('show');
}

async function saveEditedClient() {
    var id = document.getElementById('edit-id').value;
    var name = document.getElementById('edit-name').value.trim();
    var phone = document.getElementById('edit-phone').value.trim();
    var comm = document.getElementById('edit-comm').value;
    
    if (!name || !phone) {
        alert('Заполните имя и телефон');
        return;
    }
    
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/clients/' + id, {
            method: 'PUT',
            body: JSON.stringify({ fullname: name, phone: phone, communication: comm })
        });
        var result = await response.json();
        
        if (response.ok && result.success) {
            closeEditModal();
            loadClients();
            if (currentDetailId) openDetail(currentDetailId);
            alert('Данные обновлены');
        } else {
            alert(result.error || 'Ошибка обновления');
        }
    } catch(e) {
        alert('Ошибка соединения');
    }
}

function fmtDate(date) {
    if (!date) return '—';
    var d = new Date(date);
    return d.toLocaleDateString('ru-RU');
}

function esc(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Закрытие модалок по клику вне
document.getElementById('detail-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('add-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

loadClients();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>