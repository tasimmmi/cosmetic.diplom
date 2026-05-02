<?php
$pageTitle = 'Записи клиентов';
$extraStyles = '
    .calendar-mini { margin-bottom: 20px; }
    .calendar-mini-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .calendar-mini-header h3 { margin: 0; font-size: 15px; font-weight: 600; color: #333; }
    .calendar-mini-nav { display: flex; gap: 5px; }
    .calendar-mini-nav button { 
        background: white; border: 1px solid #e0e0e0; border-radius: 6px; padding: 4px 10px; 
        cursor: pointer; font-size: 13px; color: #555; transition: all 0.15s;
    }
    .calendar-mini-nav button:hover { background: #f5f5f5; border-color: #ccc; }
    .calendar-mini-nav button.today-btn { color: #667eea; border-color: #667eea; font-weight: 500; }
    
    .calendar-mini-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 4px; }
    .calendar-mini-weekday { text-align: center; font-size: 10px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px; padding: 2px 0; }
    
    .calendar-mini-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
    .calendar-mini-day { 
        text-align: center; padding: 6px 2px; border-radius: 6px; cursor: pointer; font-size: 12px;
        position: relative; transition: all 0.15s; color: #555; font-weight: 500;
    }
    .calendar-mini-day:hover { background: #f0f4ff; }
    .calendar-mini-day.other-month { color: #ddd; cursor: default; }
    .calendar-mini-day.other-month:hover { background: transparent; }
    .calendar-mini-day.today { background: #667eea10; color: #667eea; font-weight: 700; }
    .calendar-mini-day.selected { background: #667eea; color: white; font-weight: 700; box-shadow: 0 2px 8px rgba(102,126,234,0.3); }
    .calendar-mini-day.has-bookings { font-weight: 600; }
    
    .calendar-dots { display: flex; justify-content: center; gap: 2px; margin-top: 2px; }
    .calendar-dot { width: 5px; height: 5px; border-radius: 50%; }
    .calendar-dot.my { background: #f0ad4e; }
    .calendar-dot.other { background: #d9534f; }
    
    .day-tooltip {
        display: none; position: absolute; bottom: 110%; left: 50%; transform: translateX(-50%);
        background: #333; color: white; padding: 6px 10px; border-radius: 6px; font-size: 11px;
        white-space: nowrap; z-index: 1000; pointer-events: none;
    }
    .calendar-mini-day:hover .day-tooltip { display: block; }
    
    .filter-bar { display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; }
    .form-control { padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
    
    .booking-row {
        display: flex; align-items: center; gap: 12px; padding: 10px 12px;
        border-bottom: 1px solid #f0f0f0; transition: background 0.15s; position: relative;
    }
    .booking-row:hover { background: #fafbff; }
    .booking-row:last-child { border-bottom: none; }
    
    .status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .booking-date-col { min-width: 50px; text-align: center; font-size: 12px; color: #888; line-height: 1.4; flex-shrink: 0; }
    .booking-date-col .booking-time { font-weight: 700; color: #333; font-size: 13px; }
    .booking-client-col { min-width: 130px; line-height: 1.4; flex-shrink: 0; }
    .booking-client-name { font-weight: 600; color: #333; cursor: pointer; font-size: 13px; }
    .booking-client-name:hover { color: #667eea; }
    .booking-phone { font-size: 11px; color: #aaa; }
    .booking-service-name { color: #555; flex: 1; min-width: 100px; font-size: 13px; }
    .booking-comment-icon { cursor: pointer; font-size: 13px; opacity: 0.5; transition: opacity 0.15s; flex-shrink: 0; width: 20px; text-align: center; }
    .booking-comment-icon:hover { opacity: 1; }
    .booking-price { color: #333; font-weight: 500; white-space: nowrap; font-size: 13px; min-width: 75px; text-align: right; flex-shrink: 0; }
    .booking-actions-cell { width: 84px; flex-shrink: 0; display: flex; justify-content: flex-end; }
    .booking-actions-inline { display: flex; gap: 4px; opacity: 0; transition: opacity 0.15s; }
    .booking-row:hover .booking-actions-inline { opacity: 1; }
    
    .btn-icon { width: 24px; height: 24px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 10px; transition: all 0.15s; color: white; flex-shrink: 0; position: relative; }
    .btn-icon:hover { transform: scale(1.2); }
    .btn-icon::after { content: attr(title); position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px; white-space: nowrap; pointer-events: none; opacity: 0; transition: opacity 0.15s; font-family: Arial, sans-serif; font-weight: normal; }
    .btn-icon:hover::after { opacity: 1; }
    .btn-icon-confirm { background: #28a745; }
    .btn-icon-complete { background: #17a2b8; }
    .btn-icon-cancel { background: #dc3545; }
    .btn-icon-edit { background: #6c757d; }
    
    .comment-edit-inline { display: none; width: 100%; gap: 6px; align-items: center; padding: 4px 0 4px 19px; }
    .comment-edit-inline.show { display: flex; }
    .comment-edit-inline input { flex: 1; padding: 4px 8px; font-size: 12px; border: 1px solid #ddd; border-radius: 4px; }
    
    .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-outline { background: none; border: 1px solid #ddd; }
    .btn-sm { padding: 4px 8px; font-size: 11px; }
    .btn-xs { padding: 2px 6px; font-size: 11px; border-radius: 3px; }
    .btn-block { display: block; width: 100%; }
    
    .load-more { text-align: center; margin-top: 15px; }
    
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: white; border-radius: 12px; width: 95%; max-width: 500px; padding: 25px; max-height: 90vh; overflow-y: auto; }
    .modal-box h3 { margin-top: 0; }
    
    .time-slot { padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 6px; cursor: pointer; text-align: center; font-size: 13px; transition: all 0.15s; }
    .time-slot:hover { background: #f0f4ff; border-color: #667eea; }
    .time-slot.selected { background: #667eea; color: white; border-color: #667eea; }
    .time-slot.other-time { border-style: dashed; color: #667eea; }
    
    .time-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin: 8px 0; }
    
    @media (max-width: 768px) {
        .booking-row { gap: 8px; flex-wrap: wrap; }
        .booking-service-name { flex: none; width: 100%; padding-left: 19px; }
        .booking-price { margin-left: auto; }
        .time-grid { grid-template-columns: repeat(3, 1fr); }
    }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Записи клиентов</h1>
                <button class="btn btn-primary" onclick="openAddBookingModal()">
                    <i class="fas fa-plus"></i> Новая запись
                </button>
            </div>
            
            <div class="calendar-mini">
                <div class="calendar-mini-header">
                    <h3 id="cal-title"></h3>
                    <div class="calendar-mini-nav">
                        <button onclick="changeMonth(-1)">←</button>
                        <button class="today-btn" onclick="goToToday()">Сегодня</button>
                        <button onclick="changeMonth(1)">→</button>
                    </div>
                </div>
                <div class="calendar-mini-weekdays" id="cal-weekdays"></div>
                <div class="calendar-mini-grid" id="cal-grid"></div>
            </div>
            
            <div class="filter-bar">
                <select id="status-filter" class="form-control" style="width: auto;">
                    <option value="all">Все статусы</option>
                    <option value="pending">Ожидают</option>
                    <option value="confirmed">Подтверждены</option>
                    <option value="completed">Завершены</option>
                    <option value="cancelled">Отменены</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadBookings()">Показать</button>
                <span style="font-size:12px; color:#999;" id="bookings-count"></span>
            </div>
            
            <div id="bookings-list">
                <div class="loading-container"><div class="loading-spinner"></div><p>Загрузка...</p></div>
            </div>
            
            <div class="load-more" id="load-more" style="display:none;">
                <button class="btn btn-outline btn-sm" onclick="loadBookings(true)">Загрузить ещё</button>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО -->
<div class="modal-overlay" id="add-booking-modal">
    <div class="modal-box">
        <h3>Новая запись</h3>
        
        <div class="form-group"><label>Клиент</label>
            <select class="form-control" id="booking-client">
                <option value="">Выберите клиента</option>
                <option value="new">+ Добавить нового</option>
            </select>
        </div>
        <div id="new-client-fields" style="display:none;">
            <div class="form-group"><input type="text" class="form-control" id="new-client-name" placeholder="Имя и фамилия"></div>
            <div class="form-group"><input type="tel" class="form-control" id="new-client-phone" placeholder="Телефон"></div>
        </div>
        <div class="form-group"><label>Услуга</label>
            <select class="form-control" id="booking-service"><option value="">Выберите услугу</option></select>
        </div>
        <div class="form-group"><label>Дата</label>
            <input type="date" class="form-control" id="booking-date" min="">
        </div>
        <div class="form-group" id="time-section" style="display:none;">
            <label>Время</label>
            <div class="time-grid" id="time-slots"></div>
            <div id="custom-time-block" style="display:none; margin-top:8px;">
                <input type="time" class="form-control" id="booking-custom-time" step="1800">
            </div>
        </div>
        <div class="form-group"><label>Комментарий</label>
            <textarea class="form-control" id="booking-desc" rows="2" placeholder="Необязательно"></textarea>
        </div>
        <button class="btn btn-primary btn-block" onclick="createBooking()">Создать запись</button>
        <button class="btn btn-outline btn-block" style="margin-top:5px;" onclick="closeAddBookingModal()">Отмена</button>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var curDate = new Date();
var selDate = null;
var curUserId = null;
var cosmetologistId = null;
var monthData = {};
var page = 1;
var hasMore = false;
var availableSlots = [];
var clients = [];
var services = [];

(function init() {
    var token = localStorage.getItem('access_token');
    if (token) { try { var p = JSON.parse(atob(token.split('.')[1])); curUserId = p.user_id; cosmetologistId = p.cosmetologist_id; } catch(e) {} }
    renderCal(); loadMonthData(); loadBookings(); loadClientsAndServices();
})();

function changeMonth(d) { curDate.setMonth(curDate.getMonth() + d); renderCal(); loadMonthData(); }
function goToToday() { curDate = new Date(); selDate = new Date().toISOString().split('T')[0]; renderCal(); loadMonthData(); page = 1; loadBookings(); }

function renderCal() {
    var y = curDate.getFullYear(), m = curDate.getMonth();
    var months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
    document.getElementById('cal-title').textContent = months[m] + ' ' + y;
    document.getElementById('cal-weekdays').innerHTML = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].map(function(d) { return '<div class="calendar-mini-weekday">' + d + '</div>'; }).join('');
    var fd = new Date(y, m, 1).getDay(); fd = fd === 0 ? 6 : fd - 1;
    var dim = new Date(y, m + 1, 0).getDate(), prevDim = new Date(y, m, 0).getDate();
    var html = '';
    for (var i = fd - 1; i >= 0; i--) { html += '<div class="calendar-mini-day other-month">' + (prevDim - i) + '</div>'; }
    var todayStr = new Date().toISOString().split('T')[0];
    for (var day = 1; day <= dim; day++) {
        var ds = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        var cls = 'calendar-mini-day';
        if (ds === todayStr) cls += ' today';
        if (ds === selDate) cls += ' selected';
        var dayBookings = monthData[ds] || [];
        if (dayBookings.length > 0) cls += ' has-bookings';
        html += '<div class="' + cls + '" onclick="pickDate(\'' + ds + '\')">';
        if (dayBookings.length > 0) {
            html += '<div class="day-tooltip">';
            dayBookings.forEach(function(b) {
                var time = b.schedule.split(' ')[1]?.substring(0, 5) || '';
                html += '<div style="color:' + (b.cosmetologist_user_id == curUserId ? '#f0ad4e' : '#d9534f') + '; font-size:10px;">' + (b.cosmetologist_user_id == curUserId ? 'Моя' : 'Чужая') + ': ' + time + ' ' + esc(b.service_name) + '</div>';
            });
            html += '</div>';
        }
        html += day;
        if (dayBookings.length > 0) {
            html += '<div class="calendar-dots">';
            if (dayBookings.some(function(b) { return b.cosmetologist_user_id == curUserId; })) html += '<span class="calendar-dot my"></span>';
            if (dayBookings.some(function(b) { return b.cosmetologist_user_id != curUserId; })) html += '<span class="calendar-dot other"></span>';
            html += '</div>';
        }
        html += '</div>';
    }
    var total = fd + dim, remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (var i = 1; i <= remaining; i++) { html += '<div class="calendar-mini-day other-month">' + i + '</div>'; }
    document.getElementById('cal-grid').innerHTML = html;
}

function pickDate(ds) { selDate = (selDate === ds) ? null : ds; renderCal(); page = 1; loadBookings(); }

async function loadMonthData() {
    var y = curDate.getFullYear(), m = String(curDate.getMonth() + 1).padStart(2, '0');
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/bookings?month=' + y + '-' + m);
        var d = await r.json();
        if (d.success && d.data.month_bookings) {
            monthData = {};
            d.data.month_bookings.forEach(function(b) { var dk = b.schedule.split(' ')[0]; if (!monthData[dk]) monthData[dk] = []; monthData[dk].push(b); });
            renderCal();
        }
    } catch(e) {}
}

async function loadBookings(append) {
    var c = document.getElementById('bookings-list');
    if (!append) { page = 1; c.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>'; }
    var status = document.getElementById('status-filter').value;
    try {
        var url = '/backend/public/api/cosmetologist/bookings?page=' + page + '&limit=20';
        if (selDate) url += '&date=' + selDate;
        if (status !== 'all') url += '&status=' + status;
        var r = await AUTH.fetch(url); var d = await r.json();
        if (d.success) {
            var bookings = d.data.bookings || [];
            hasMore = d.data.has_more || false;
            document.getElementById('bookings-count').textContent = 'Найдено: ' + (d.data.total || bookings.length);
            document.getElementById('load-more').style.display = hasMore ? 'block' : 'none';
            if (bookings.length === 0 && !append) { c.innerHTML = '<p class="text-muted text-center py-4">Нет записей</p>'; return; }
            var sc = { pending: '#f0ad4e', confirmed: '#5bc0de', completed: '#5cb85c', cancelled: '#d9534f' };
            var html = append ? c.innerHTML : '';
            bookings.forEach(function(b) {
                var dt = new Date(b.schedule);
                var ds = dt.toLocaleDateString('ru-RU', {day:'numeric',month:'short'});
                var ts = dt.toLocaleTimeString('ru-RU', {hour:'2-digit',minute:'2-digit'});
                var ph = b.client_phone || '';
                html += '<div class="booking-row" id="booking-' + b.id + '">' +
                    '<span class="status-dot" style="background:' + (sc[b.status]||'#999') + '" title="' + b.status + '"></span>' +
                    '<span class="booking-date-col">' + ds + '<br><span class="booking-time">' + ts + '</span></span>' +
                    '<span class="booking-client-col"><span class="booking-client-name" onclick="openClient(' + b.client_id + ')">' + esc(b.client_name) + '</span>' +
                    (ph ? '<br><span class="booking-phone">' + esc(ph) + '</span>' : '') + '</span>' +
                    '<span class="booking-service-name">' + esc(b.service_name) + '</span>';
                html += '<span class="booking-comment-icon"' + (b.description ? ' title="' + esc(b.description) + '" onclick="editComment(' + b.id + ')"' : ' style="visibility:hidden;"') + '>💬</span>';
                html += '<span class="booking-price">' + (b.price||0) + ' BYN</span>' +
                    '<span class="booking-actions-cell"><span class="booking-actions-inline">' +
                    '<button class="btn-icon btn-icon-edit" onclick="editComment(' + b.id + ')" title="Комментарий"><i class="fas fa-pen"></i></button>';
                if (b.status === 'pending') html += '<button class="btn-icon btn-icon-confirm" onclick="confirmBooking(' + b.id + ')" title="Подтвердить"><i class="fas fa-check"></i></button><button class="btn-icon btn-icon-cancel" onclick="cancelBooking(' + b.id + ')" title="Отменить"><i class="fas fa-times"></i></button>';
                if (b.status === 'confirmed') html += '<button class="btn-icon btn-icon-complete" onclick="completeBooking(' + b.id + ')" title="Завершить"><i class="fas fa-flag-checkered"></i></button><button class="btn-icon btn-icon-cancel" onclick="cancelBooking(' + b.id + ')" title="Отменить"><i class="fas fa-times"></i></button>';
                html += '</span></span><div class="comment-edit-inline" id="comment-edit-' + b.id + '"><input type="text" class="form-control" id="comment-text-' + b.id + '" value="' + esc(b.description||'') + '" placeholder="Комментарий..."><button class="btn btn-xs btn-primary" onclick="saveComment(' + b.id + ')">✓</button><button class="btn btn-xs btn-outline" onclick="cancelEditComment(' + b.id + ')">✕</button></div></div>';
            });
            c.innerHTML = html;
        }
    } catch(e) { c.innerHTML = '<p class="text-danger">Ошибка</p>'; }
}

async function confirmBooking(id) { if(!confirm('Подтвердить?')) return; await AUTH.fetch('/backend/public/api/cosmetologist/bookings/'+id+'/confirm',{method:'PUT'}); loadBookings(); loadMonthData(); }
async function completeBooking(id) { if(!confirm('Завершить?')) return; await AUTH.fetch('/backend/public/api/cosmetologist/bookings/'+id+'/complete',{method:'PUT'}); loadBookings(); loadMonthData(); }
async function cancelBooking(id) { if(!confirm('Отменить?')) return; await AUTH.fetch('/backend/public/api/bookings/'+id+'/cancel',{method:'PUT'}); loadBookings(); loadMonthData(); }
function editComment(id) { document.getElementById('comment-edit-' + id).classList.add('show'); document.getElementById('comment-text-' + id).focus(); }
function cancelEditComment(id) { document.getElementById('comment-edit-' + id).classList.remove('show'); }
async function saveComment(id) { var c = document.getElementById('comment-text-' + id).value.trim(); await AUTH.fetch('/backend/public/api/bookings/'+id+'/comment',{method:'PUT',body:JSON.stringify({description:c})}); loadBookings(); }

async function loadClientsAndServices() {
    try {
        var r1 = await AUTH.fetch('/backend/public/api/cosmetologist/clients');
        var d1 = await r1.json();
        if (d1.success) {
            clients = d1.data.clients || [];
            document.getElementById('booking-client').innerHTML = '<option value="">Выберите клиента</option>' + clients.map(function(c) { return '<option value="'+c.id+'">'+esc(c.fullname)+' — '+esc(c.phone)+'</option>'; }).join('') + '<option value="new">+ Добавить нового</option>';
        }
        var r2 = await AUTH.fetch('/backend/public/api/cosmetologist/services');
        var d2 = await r2.json();
        if (d2.success) {
            services = d2.data.services || [];
            document.getElementById('booking-service').innerHTML = '<option value="">Выберите услугу</option>' + services.map(function(s) { return '<option value="'+s.id+'">'+esc(s.service)+' — '+s.price+' BYN</option>'; }).join('');
        }
    } catch(e) {}
}

function openAddBookingModal() {
    document.getElementById('add-booking-modal').classList.add('show');
    document.getElementById('booking-client').value = '';
    document.getElementById('new-client-name').value = '';
    document.getElementById('new-client-phone').value = '';
    document.getElementById('new-client-fields').style.display = 'none';
    document.getElementById('booking-service').value = '';
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('booking-date').value = today;
    document.getElementById('booking-date').min = today;
    document.getElementById('booking-desc').value = '';
    document.getElementById('time-section').style.display = 'none';
    document.getElementById('time-slots').innerHTML = '';
    document.getElementById('custom-time-block').style.display = 'none';
    document.getElementById('booking-custom-time').value = '';
}

function closeAddBookingModal() { document.getElementById('add-booking-modal').classList.remove('show'); }

document.getElementById('booking-client').addEventListener('change', function() { document.getElementById('new-client-fields').style.display = this.value === 'new' ? 'block' : 'none'; });
document.getElementById('booking-service').addEventListener('change', function() { if (this.value) { document.getElementById('time-section').style.display = 'block'; loadAvailableSlots(); } else { document.getElementById('time-section').style.display = 'none'; } });
document.getElementById('booking-date').addEventListener('change', function() { if (document.getElementById('booking-service').value) loadAvailableSlots(); });

async function loadAvailableSlots() {
    var date = document.getElementById('booking-date').value;
    var serviceId = document.getElementById('booking-service').value;
    var grid = document.getElementById('time-slots');
    var customBlock = document.getElementById('custom-time-block');
    if (!date || !serviceId || !cosmetologistId) return;
    grid.innerHTML = '<span style="font-size:12px;color:#999;">Загрузка...</span>';
    customBlock.style.display = 'none';
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologists/' + cosmetologistId + '/slots?date=' + date + '&service_id=' + serviceId);
        var d = await r.json();
        var html = '';
        if (d.success && d.data.slots && d.data.slots.length > 0) {
            d.data.slots.forEach(function(s) { var time = s.begin_time.split(' ')[1]?.substring(0, 5) || ''; html += '<div class="time-slot" onclick="selectTimeSlot(\'' + time + '\', this)">' + time + '</div>'; });
        }
        html += '<div class="time-slot other-time" onclick="showCustomTime()">' + (html === '' ? 'Добавить время' : 'Другое время') + '</div>';
        grid.innerHTML = html;
    } catch(e) { grid.innerHTML = '<span style="font-size:12px;color:#d9534f;">Ошибка загрузки</span>'; }
}

function selectTimeSlot(time, el) { document.querySelectorAll('#time-slots .time-slot').forEach(function(s) { s.classList.remove('selected'); }); el.classList.add('selected'); document.getElementById('custom-time-block').style.display = 'none'; }
function showCustomTime() { document.querySelectorAll('#time-slots .time-slot').forEach(function(s) { s.classList.remove('selected'); }); document.getElementById('custom-time-block').style.display = 'block'; document.getElementById('booking-custom-time').focus(); }

async function createBooking() {
    var clientId = document.getElementById('booking-client').value;
    var newName = document.getElementById('new-client-name').value.trim();
    var newPhone = document.getElementById('new-client-phone').value.trim();
    var serviceId = document.getElementById('booking-service').value;
    var date = document.getElementById('booking-date').value;
    var desc = document.getElementById('booking-desc').value.trim();
    var time = null;
    var sel = document.querySelector('#time-slots .time-slot.selected');
    var cust = document.getElementById('booking-custom-time');
    if (sel && !sel.classList.contains('other-time')) time = sel.textContent.trim();
    else if (cust.value) time = cust.value;
    if (!serviceId) { alert('Выберите услугу'); return; }
    if (!date) { alert('Выберите дату'); return; }
    if (!time) { alert('Выберите время'); return; }
    if (!clientId && (!newName || !newPhone)) { alert('Выберите клиента или заполните имя и телефон'); return; }
    if (clientId === 'new' && newName && newPhone) {
        try {
            var cr = await AUTH.fetch('/backend/public/api/cosmetologist/clients', { method:'POST', body:JSON.stringify({fullname:newName,phone:newPhone,communication:'phone'}) });
            var cd = await cr.json();
            if (cd.success && cd.data && cd.data.client_id) clientId = cd.data.client_id;
            else { alert('Ошибка создания клиента: ' + (cd.error||'')); return; }
        } catch(e) { alert('Ошибка соединения'); return; }
    }
    if (!clientId || clientId === 'new') { alert('Не удалось определить клиента'); return; }
    try {
        var r = await AUTH.fetch('/backend/public/api/bookings', { method:'POST', body:JSON.stringify({client_id:parseInt(clientId),service_id:parseInt(serviceId),schedule:date+' '+time+':00',description:desc}) });
        var d = await r.json();
        if (r.ok && d.success) { closeAddBookingModal(); loadBookings(); loadMonthData(); loadClientsAndServices(); }
        else alert(d.error || 'Ошибка создания записи');
    } catch(e) { alert('Ошибка соединения'); }
}

document.getElementById('add-booking-modal').addEventListener('click', function(e) { if (e.target === this) closeAddBookingModal(); });
function openClient(id) { window.open('/frontend/cosmetologist/clients.php?client='+id, '_blank'); }
function esc(t) { if(!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>