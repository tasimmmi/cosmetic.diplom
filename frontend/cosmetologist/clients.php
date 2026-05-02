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
    
    .history-item { padding: 12px 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; background: white; }
    .history-item:last-child { margin-bottom: 0; }
    .history-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 8px; 
    }
    .history-date-block {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .history-date { 
        font-weight: 500; 
        font-size: 14px; 
        color: #333;
    }
    .history-service { 
        font-size: 14px; 
        color: #333; 
        margin-bottom: 5px; 
        padding-left: 16px;
    }
    .history-comment { 
        font-size: 13px; 
        color: #666; 
        margin-bottom: 6px; 
        padding: 5px 8px 5px 16px;
        border-left: 3px solid #667eea;
        border-radius: 0 4px 4px 0;
        background: #f8f9fa;
    }
    .history-actions { 
        display: flex; 
        gap: 6px; 
        opacity: 0.4;
        transition: opacity 0.2s;
    }
    .history-item:hover .history-actions {
        opacity: 1;
    }
    
    /* Минималистичные круглые кнопки */
    .btn-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        transition: all 0.2s;
        color: white;
    }
    .btn-icon:hover {
        transform: scale(1.15);
    }
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
    
    @media (max-width: 768px) {
        .modal-box-body { flex-direction: column; }
        .modal-right { width: 100%; }
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

<!-- ================================================================ -->
<!-- МОДАЛЬНОЕ ОКНО #1: ДЕТАЛИ КЛИЕНТА (история + быстрая запись)      -->
<!-- ================================================================ -->
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
            
            <!-- ПРАВАЯ КОЛОНКА: Форма быстрой записи -->
            <div class="modal-right">
                <h4 style="margin-top: 0;">📅 Записать на прием</h4>
                <div class="form-group">
                    <label>Услуга</label>
                    <select class="form-control" id="modal-service">
                        <option value="">Выберите услугу</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата</label>
                    <input type="date" class="form-control" id="modal-date">
                </div>
                <div class="form-group">
                    <label>Время</label>
                    <select class="form-control" id="modal-time">
                        <option value="">Выберите время</option>
                    </select>
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

<!-- ================================================================ -->
<!-- МОДАЛЬНОЕ ОКНО #2: ДОБАВЛЕНИЕ КЛИЕНТА                            -->
<!-- ================================================================ -->
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

<!-- ================================================================ -->
<!-- МОДАЛЬНОЕ ОКНО #3: РЕДАКТИРОВАНИЕ КЛИЕНТА                         -->
<!-- ================================================================ -->
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
        html += '<div class="client-card" onclick="openDetail(' + cl.id + ')">' +
            '<div class="client-header">' +
                '<span class="client-name">' + esc(cl.fullname) + '</span>' +
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
    }
    
    renderClients(list);
}

// ========== МОДАЛЬНОЕ ОКНО #1: ДЕТАЛИ (история + запись) ==========
async function openDetail(id) {
    currentDetailId = id;
    document.getElementById('detail-modal').classList.add('show');
    document.getElementById('modal-history').innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>';
    
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/clients/' + id);
        var data = await response.json();
        
        if (data.success) {
            currentClientData = data.data.client;
            document.getElementById('modal-client-name').textContent = data.data.client.fullname;
            renderHistory(data.data.history || []);
        } else {
            document.getElementById('modal-history').innerHTML = '<p class="text-danger">Ошибка загрузки истории</p>';
        }
    } catch(e) {
        document.getElementById('modal-history').innerHTML = '<p class="text-danger">Ошибка соединения с сервером</p>';
    }
    
    // Загружаем услуги для выпадающего списка
    try {
        var servicesResponse = await AUTH.fetch('/backend/public/api/cosmetologist/services');
        var servicesData = await servicesResponse.json();
        
        if (servicesData.success) {
            var select = document.getElementById('modal-service');
            select.innerHTML = '<option value="">Выберите услугу</option>';
            (servicesData.data.services || []).forEach(function(s) {
                select.innerHTML += '<option value="' + s.id + '">' + esc(s.service) + ' — ' + s.price + ' BYN</option>';
            });
        }
    } catch(e) {}
    
    // Заполняем время (с 9:00 до 18:00 с шагом 30 минут)
    var timeSelect = document.getElementById('modal-time');
    timeSelect.innerHTML = '<option value="">Выберите время</option>';
    for (var h = 9; h <= 18; h++) {
        var hourStr = (h < 10 ? '0' : '') + h;
        timeSelect.innerHTML += '<option value="' + hourStr + ':00">' + hourStr + ':00</option>';
        timeSelect.innerHTML += '<option value="' + hourStr + ':30">' + hourStr + ':30</option>';
    }
    
    document.getElementById('modal-date').min = new Date().toISOString().split('T')[0];
}

function closeModal() {
    document.getElementById('detail-modal').classList.remove('show');
    currentDetailId = null;
    currentClientData = null;
}

// 🔥 Финальный рендеринг — точка статуса, кнопки справа, комментарий только если есть
function renderHistory(history) {
    var container = document.getElementById('modal-history');
    
    if (history.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Нет записей</p>';
        return;
    }
    
    var statusColors = { 
        pending: '#f0ad4e', 
        confirmed: '#5bc0de', 
        completed: '#5cb85c', 
        cancelled: '#d9534f' 
    };
    
    var html = '';
    history.forEach(function(b) {
        var d = new Date(b.schedule);
        var bookingId = b.id;
        var status = b.status;
        
        html += '<div class="history-item" id="history-' + bookingId + '">' +
            '<div class="history-header">' +
                '<div class="history-date-block">' +
                    '<span class="status-dot" style="background:' + (statusColors[status] || '#999') + '" title="' + status + '"></span>' +
                    '<span class="history-date">' + d.toLocaleDateString('ru-RU') + ' в ' + 
                        d.toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'}) + '</span>' +
                '</div>' +
                '<div class="history-actions">' +
                    '<button class="btn-icon btn-icon-edit" onclick="editComment(' + bookingId + ')" title="Комментарий">' +
                        '<i class="fas fa-pen"></i></button>';
            
            if (status === 'pending') {
                html += '<button class="btn-icon btn-icon-confirm" onclick="confirmBooking(' + bookingId + ')" title="Подтвердить">' +
                    '<i class="fas fa-check"></i></button>' +
                    '<button class="btn-icon btn-icon-cancel" onclick="cancelBooking(' + bookingId + ')" title="Отменить">' +
                        '<i class="fas fa-times"></i></button>';
            }
            
            if (status === 'confirmed') {
                html += '<button class="btn-icon btn-icon-complete" onclick="completeBooking(' + bookingId + ')" title="Завершить">' +
                    '<i class="fas fa-flag-checkered"></i></button>' +
                    '<button class="btn-icon btn-icon-cancel" onclick="cancelBooking(' + bookingId + ')" title="Отменить">' +
                        '<i class="fas fa-times"></i></button>';
            }
            
        html += '</div></div>' +
            '<div class="history-service">' + esc(b.service) + ' — ' + (b.price || 0) + ' BYN</div>';
        
        // 🔥 Комментарий показываем ТОЛЬКО если есть текст
        if (b.description) {
            html += '<div class="history-comment" id="comment-display-' + bookingId + '">' +
                esc(b.description) +
            '</div>';
        }
        
        // Форма редактирования (всегда в DOM, но скрыта)
        html += '<div class="comment-edit-area" id="comment-edit-' + bookingId + '">' +
            '<textarea class="form-control" id="comment-text-' + bookingId + '" rows="2">' + esc(b.description || '') + '</textarea>' +
            '<div class="comment-edit-actions">' +
                '<button class="btn btn-xs btn-primary" onclick="saveComment(' + bookingId + ')">💾</button>' +
                '<button class="btn btn-xs btn-outline" onclick="cancelEditComment(' + bookingId + ')">✕</button>' +
            '</div>' +
        '</div>';
        
        html += '</div>';
    });
    
    container.innerHTML = html;
}

// ========== УПРАВЛЕНИЕ ЗАПИСЯМИ ==========

// 🔥 Подтвердить запись
async function confirmBooking(bookingId) {
    if (!confirm('Подтвердить эту запись?')) return;
    
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/bookings/' + bookingId + '/confirm', {
            method: 'PUT'
        });
        
        var data = await response.json();
        
        if (response.ok && data.success) {
            // Обновляем историю
            openDetail(currentDetailId);
        } else {
            alert(data.error || 'Ошибка подтверждения');
        }
    } catch(e) {
        alert('Ошибка соединения с сервером');
    }
}

// 🔥 Завершить запись
async function completeBooking(bookingId) {
    if (!confirm('Отметить запись как завершенную?')) return;
    
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/bookings/' + bookingId + '/complete', {
            method: 'PUT'
        });
        
        var data = await response.json();
        
        if (response.ok && data.success) {
            openDetail(currentDetailId);
            loadClients(); // Обновляем список клиентов (изменится статистика)
        } else {
            alert(data.error || 'Ошибка завершения');
        }
    } catch(e) {
        alert('Ошибка соединения с сервером');
    }
}

// 🔥 Отменить запись
async function cancelBooking(bookingId) {
    if (!confirm('Отменить эту запись? Это действие нельзя отменить.')) return;
    
    try {
        var response = await AUTH.fetch('/backend/public/api/bookings/' + bookingId + '/cancel', {
            method: 'PUT'
        });
        
        var data = await response.json();
        
        if (response.ok && data.success) {
            openDetail(currentDetailId);
            loadClients(); // Обновляем список клиентов
        } else {
            alert(data.error || 'Ошибка отмены');
        }
    } catch(e) {
        alert('Ошибка соединения с сервером');
    }
}

// ========== РЕДАКТИРОВАНИЕ КОММЕНТАРИЯ ==========

// 🔥 Показать форму редактирования комментария
function editComment(bookingId) {
    document.getElementById('comment-display-' + bookingId).style.display = 'none';
    document.getElementById('comment-edit-' + bookingId).classList.add('show');
    document.getElementById('comment-text-' + bookingId).focus();
}

// 🔥 Скрыть форму редактирования комментария
function cancelEditComment(bookingId) {
    document.getElementById('comment-display-' + bookingId).style.display = 'block';
    document.getElementById('comment-edit-' + bookingId).classList.remove('show');
}

// 🔥 Сохранить комментарий
async function saveComment(bookingId) {
    var comment = document.getElementById('comment-text-' + bookingId).value.trim();
    
    try {
        var response = await AUTH.fetch('/backend/public/api/bookings/' + bookingId + '/comment', {
            method: 'PUT',
            body: JSON.stringify({ description: comment })
        });
        
        var data = await response.json();
        
        if (response.ok && data.success) {
            // Обновляем отображение комментария
            var displayEl = document.getElementById('comment-display-' + bookingId);
            
            if (comment) {
                // Создаем или обновляем блок комментария
                if (displayEl) {
                    displayEl.innerHTML = esc(comment);
                    displayEl.style.display = '';
                } else {
                    // Создаем новый блок после service
                    var newComment = document.createElement('div');
                    newComment.className = 'history-comment';
                    newComment.id = 'comment-display-' + bookingId;
                    newComment.innerHTML = esc(comment);
                    
                    var historyItem = document.getElementById('history-' + bookingId);
                    var commentEdit = document.getElementById('comment-edit-' + bookingId);
                    historyItem.insertBefore(newComment, commentEdit);
                }
            } else {
                // Удаляем блок, если комментарий пустой
                if (displayEl) {
                    displayEl.remove();
                }
            }
            
            cancelEditComment(bookingId);
        } else {
            alert(data.error || 'Ошибка сохранения комментария');
        }
    } catch(e) {
        alert('Ошибка соединения с сервером');
    }
}

// ========== СОЗДАНИЕ НОВОЙ ЗАПИСИ ==========
async function createBookingFromModal() {
    var serviceId = document.getElementById('modal-service').value;
    var date = document.getElementById('modal-date').value;
    var time = document.getElementById('modal-time').value;
    var comment = document.getElementById('modal-comment').value;
    
    if (!serviceId || !date || !time || !currentDetailId) {
        alert('Заполните все поля');
        return;
    }
    
    var user = JSON.parse(localStorage.getItem('user') || '{}');
    
    try {
        var response = await AUTH.fetch('/backend/public/api/bookings', {
            method: 'POST',
            body: JSON.stringify({
                cosmetologist_id: user.cosmetologist_id,
                service_id: parseInt(serviceId),
                schedule: date + ' ' + time + ':00',
                client_id: currentDetailId,
                description: comment || ''
            })
        });
        
        var data = await response.json();
        
        if (response.ok && data.success) {
            alert('Запись создана!');
            // Очищаем форму
            document.getElementById('modal-service').value = '';
            document.getElementById('modal-date').value = '';
            document.getElementById('modal-time').value = '';
            document.getElementById('modal-comment').value = '';
            // Обновляем историю
            openDetail(currentDetailId);
            loadClients();
        } else {
            alert(data.error || 'Ошибка при создании записи');
        }
    } catch(e) {
        alert('Ошибка соединения с сервером');
    }
}

// ========== МОДАЛЬНОЕ ОКНО #2: ДОБАВЛЕНИЕ КЛИЕНТА ==========
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
            body: JSON.stringify({
                fullname: name,
                phone: phone,
                communication: comm
            })
        });
        
        var data = await response.json();
        
        if (response.ok && data.success) {
            closeAddModal();
            loadClients();
        } else {
            alert(data.error || 'Ошибка при добавлении клиента');
        }
    } catch(e) {
        alert('Ошибка соединения с сервером');
    }
}

// ========== МОДАЛЬНОЕ ОКНО #3: РЕДАКТИРОВАНИЕ КЛИЕНТА ==========
function editCurrentClient() {
    if (!currentClientData) {
        alert('Нет данных о клиенте');
        return;
    }
    
    document.getElementById('edit-modal-title').textContent = 'Изменить: ' + currentClientData.fullname;
    document.getElementById('edit-id').value = currentClientData.id;
    document.getElementById('edit-name').value = currentClientData.fullname;
    document.getElementById('edit-phone').value = currentClientData.phone || '';
    document.getElementById('edit-comm').value = currentClientData.communication || 'phone';
    
    document.getElementById('edit-modal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('show');
    document.getElementById('edit-id').value = '';
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
            body: JSON.stringify({
                fullname: name,
                phone: phone,
                communication: comm
            })
        });
        
        var data = await response.json();
        
        if (response.ok && data.success) {
            closeEditModal();
            if (currentDetailId == id) {
                openDetail(id);
            }
            loadClients();
        } else {
            alert(data.error || 'Ошибка при обновлении данных');
        }
    } catch(e) {
        alert('Ошибка соединения с сервером');
    }
}

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========
function esc(t) {
    if (!t) return '';
    var d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
}

function fmtDate(d) {
    return d ? new Date(d).toLocaleDateString('ru-RU') : '—';
}

// ========== ЗАКРЫТИЕ МОДАЛЬНЫХ ОКОН ПО КЛИКУ ВНЕ ==========
document.getElementById('detail-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('add-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});

document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// ========== ЗАГРУЗКА ПРИ СТАРТЕ ==========
loadClients();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>