<?php
$pageTitle = 'Записи клиентов';
$extraStyles = '
    .calendar-mini { margin-bottom: 20px; background: white; border-radius: 10px; padding: 10px 15px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
    .calendar-mini-header { font-size: 14px; font-weight: 600; white-space: nowrap; }
    .calendar-mini-nav { display: flex; gap: 3px; }
    .calendar-mini-nav button { background: none; border: 1px solid #ddd; border-radius: 3px; padding: 2px 8px; cursor: pointer; font-size: 12px; }
    .calendar-mini-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; }
    .calendar-mini-day-header { text-align: center; font-size: 9px; color: #aaa; padding: 2px 0; }
    .calendar-mini-day { text-align: center; padding: 2px 1px; border-radius: 2px; cursor: pointer; font-size: 10px; position: relative; }
    .calendar-mini-day:hover { background: #f0f0f0; }
    .calendar-mini-day.today { font-weight: 700; color: #667eea; }
    .calendar-mini-day.selected { background: #667eea; color: white; border-radius: 50%; }
    .calendar-mini-day.other-month { color: #ddd; }
    .calendar-mini-dot { display: block; width: 3px; height: 3px; border-radius: 50%; margin: 0 auto; position: absolute; bottom: 1px; left: 50%; transform: translateX(-50%); }
    .calendar-mini-dot.my { background: #f0ad4e; }
    .calendar-mini-dot.other { background: #d9534f; }
    
    .filter-bar { display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; }
    
    .booking-card { background: white; border-radius: 8px; padding: 12px 15px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border-left: 3px solid #ddd; }
    .booking-card.status-pending { border-left-color: #f0ad4e; }
    .booking-card.status-confirmed { border-left-color: #5bc0de; }
    .booking-card.status-completed { border-left-color: #5cb85c; }
    .booking-card.status-cancelled { border-left-color: #d9534f; }
    
    .booking-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
    .booking-client { font-weight: 600; font-size: 14px; cursor: pointer; }
    .booking-client:hover { color: #667eea; }
    .booking-status-text { font-size: 10px; font-weight: 500; }
    .status-pending .booking-status-text { color: #f0ad4e; }
    .status-confirmed .booking-status-text { color: #5bc0de; }
    .status-completed .booking-status-text { color: #5cb85c; }
    .status-cancelled .booking-status-text { color: #d9534f; }
    
    .booking-meta { font-size: 12px; color: #888; display: flex; gap: 10px; margin-bottom: 3px; }
    .booking-service { color: #555; font-size: 13px; }
    
    .booking-actions { display: flex; gap: 4px; margin-top: 4px; padding-top: 4px; border-top: 1px solid #f0f0f0; }
    .btn-xs { padding: 2px 7px; font-size: 10px; background: none; border: 1px solid #ddd; border-radius: 3px; cursor: pointer; color: #888; }
    .btn-xs:hover { background: #f5f5f5; }
    .btn-xs.confirm { border-color: #5cb85c; color: #5cb85c; }
    .btn-xs.complete { border-color: #5bc0de; color: #5bc0de; }
    .btn-xs.cancel { border-color: #d9534f; color: #d9534f; }
    
    .load-more { text-align: center; margin-top: 15px; }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <h1>Записи клиентов</h1>
            
            <!-- КОМПАКТНЫЙ КАЛЕНДАРЬ -->
            <div class="calendar-mini">
                <span class="calendar-mini-header" id="cal-title"></span>
                <div class="calendar-mini-nav">
                    <button onclick="changeMonth(-1)">←</button>
                    <button onclick="goToToday()">Сегодня</button>
                    <button onclick="changeMonth(1)">→</button>
                </div>
                <div style="flex:1;">
                    <div class="calendar-mini-grid" id="cal-headers"></div>
                    <div class="calendar-mini-grid" id="cal-grid"></div>
                </div>
            </div>
            
            <!-- ФИЛЬТРЫ -->
            <div class="filter-bar">
                <input type="date" id="date-filter" class="form-control" style="width: auto; padding: 6px 10px; font-size: 13px;">
                <select id="status-filter" class="form-control" style="width: auto; padding: 6px 10px; font-size: 13px;">
                    <option value="all">Все статусы</option>
                    <option value="pending">Ожидают</option>
                    <option value="confirmed">Подтверждены</option>
                    <option value="completed">Завершены</option>
                    <option value="cancelled">Отменены</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadBookings()">Показать</button>
                <span style="font-size:12px; color:#999;" id="bookings-count"></span>
            </div>
            
            <!-- СПИСОК ЗАПИСЕЙ -->
            <div id="bookings-list">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Загрузка...</p>
                </div>
            </div>
            
            <div class="load-more" id="load-more" style="display:none;">
                <button class="btn btn-outline btn-sm" onclick="loadBookings(true)">Загрузить ещё</button>
            </div>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var curDate = new Date();
var selDate = null; // null = показать все записи
var curUserId = null;
var monthData = {};
var page = 1;
var hasMore = false;

(function init() {
    var token = localStorage.getItem('access_token');
    if (token) { try { curUserId = JSON.parse(atob(token.split('.')[1])).user_id; } catch(e) {} }
    renderCal();
    loadMonthData();
    loadBookings();
})();

// ========== МИНИ-КАЛЕНДАРЬ ==========
function changeMonth(d) { curDate.setMonth(curDate.getMonth() + d); renderCal(); loadMonthData(); }
function goToToday() { curDate = new Date(); selDate = new Date().toISOString().split('T')[0]; document.getElementById('date-filter').value = selDate; renderCal(); loadMonthData(); loadBookings(); }

function renderCal() {
    var y = curDate.getFullYear(), m = curDate.getMonth();
    var months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
    document.getElementById('cal-title').textContent = months[m] + ' ' + y;
    
    document.getElementById('cal-headers').innerHTML = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].map(function(d) {
        return '<div class="calendar-mini-day-header">' + d + '</div>';
    }).join('');
    
    var fd = new Date(y, m, 1).getDay(); fd = fd === 0 ? 6 : fd - 1;
    var dim = new Date(y, m + 1, 0).getDate();
    var prevDim = new Date(y, m, 0).getDate();
    
    var html = '';
    
    // Предыдущий месяц
    for (var i = fd - 1; i >= 0; i--) {
        html += '<div class="calendar-mini-day other-month">' + (prevDim - i) + '</div>';
    }
    
    // Текущий месяц
    var todayStr = new Date().toISOString().split('T')[0];
    for (var day = 1; day <= dim; day++) {
        var ds = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        var cls = 'calendar-mini-day';
        if (ds === todayStr) cls += ' today';
        if (ds === selDate) cls += ' selected';
        
        html += '<div class="' + cls + '" onclick="pickDate(\'' + ds + '\')" style="position:relative;">' + day;
        
        // ТОЧКИ
        if (monthData[ds]) {
            var hasMy = monthData[ds].some(function(b) { return b.cosmetologist_user_id == curUserId; });
            var hasOther = monthData[ds].some(function(b) { return b.cosmetologist_user_id != curUserId; });
            if (hasMy) html += '<span class="calendar-mini-dot my"></span>';
            if (hasOther) html += '<span class="calendar-mini-dot other"></span>';
        }
        
        html += '</div>';
    }
    
    // Оставшиеся ячейки
    var total = fd + dim;
    var remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (var i = 1; i <= remaining; i++) {
        html += '<div class="calendar-mini-day other-month">' + i + '</div>';
    }
    
    document.getElementById('cal-grid').innerHTML = html;
}

function pickDate(ds) {
    // Переключение: если дата уже выбрана - сбрасываем (показать все)
    if (selDate === ds) {
        selDate = null;
        document.getElementById('date-filter').value = '';
    } else {
        selDate = ds;
        document.getElementById('date-filter').value = ds;
    }
    renderCal();
    page = 1;
    loadBookings();
}

async function loadMonthData() {
    var y = curDate.getFullYear(), m = String(curDate.getMonth() + 1).padStart(2, '0');
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/bookings?month=' + y + '-' + m);
        var d = await r.json();
        if (d.success && d.data.month_bookings) {
            monthData = {};
            d.data.month_bookings.forEach(function(b) {
                var dk = b.schedule.split(' ')[0];
                if (!monthData[dk]) monthData[dk] = [];
                monthData[dk].push(b);
            });
            renderCal();
        }
    } catch(e) {}
}

// ========== ЗАПИСИ ==========
async function loadBookings(append) {
    var c = document.getElementById('bookings-list');
    if (!append) { page = 1; c.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>'; }
    
    var date = document.getElementById('date-filter').value;
    var status = document.getElementById('status-filter').value;
    
    try {
        var url = '/backend/public/api/cosmetologist/bookings?page=' + page + '&limit=20';
        if (date) url += '&date=' + date;
        if (status !== 'all') url += '&status=' + status;
        
        var r = await AUTH.fetch(url);
        var d = await r.json();
        
        if (d.success) {
            var bookings = d.data.bookings || [];
            hasMore = d.data.has_more || false;
            
            document.getElementById('bookings-count').textContent = 'Найдено: ' + (d.data.total || bookings.length);
            document.getElementById('load-more').style.display = hasMore ? 'block' : 'none';
            
            if (bookings.length === 0 && !append) {
                c.innerHTML = '<p class="text-muted text-center py-4">Нет записей</p>';
                return;
            }
            
            var sn = { pending:'Ожидает', confirmed:'Подтверждена', completed:'Завершена', cancelled:'Отменена' };
            var html = append ? c.innerHTML : '';
            
            bookings.forEach(function(b) {
                var time = new Date(b.schedule).toLocaleTimeString('ru-RU', { hour:'2-digit', minute:'2-digit' });
                var dateStr = new Date(b.schedule).toLocaleDateString('ru-RU');
                var sc = b.description ? b.description.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;') : '';
                
                html += '<div class="booking-card status-' + b.status + '">';
                
                // Верхняя строка: клиент + статус
                html += '<div class="booking-top">';
                html += '<span class="booking-client" onclick="openClient(' + b.client_id + ')" title="Открыть карточку">' + esc(b.client_name) + '</span>';
                html += '<span class="booking-status-text">' + (sn[b.status]||b.status) + '</span>';
                html += '</div>';
                
                // Услуга
                html += '<div class="booking-service">' + esc(b.service_name) + '</div>';
                
                // Мета-информация
                html += '<div class="booking-meta">';
                html += '<span>' + dateStr + ' в ' + time + '</span>';
                html += '<span>' + (b.price||0) + ' BYN</span>';
                html += '<span>' + esc(b.client_phone||'') + '</span>';
                html += '</div>';
                
                // Комментарий
                if (b.description) {
                    html += '<div style="font-size:11px; color:#aaa; margin-bottom:2px;">💬 ' + esc(b.description) + ' <span style="cursor:pointer; color:#ccc;" onclick="event.stopPropagation(); editComment(' + b.id + ',\'' + sc + '\')">✏️</span></div>';
                }
                
                // Кнопки
                html += '<div class="booking-actions">';
                if (b.status === 'pending') html += '<button class="btn-xs confirm" onclick="confirm(' + b.id + ')">✓ Подтвердить</button>';
                if (b.status === 'confirmed') html += '<button class="btn-xs complete" onclick="complete(' + b.id + ')">✓✓ Завершить</button>';
                if (b.status === 'pending' || b.status === 'confirmed') html += '<button class="btn-xs cancel" onclick="cancel(' + b.id + ')">✕ Отменить</button>';
                html += '<button class="btn-xs" onclick="addComment(' + b.id + ')">💬</button>';
                html += '</div>';
                
                html += '</div>';
            });
            
            c.innerHTML = html;
        }
    } catch(e) { c.innerHTML = '<p class="text-danger">Ошибка загрузки</p>'; }
}

// ========== ДЕЙСТВИЯ ==========
async function confirm(id) { if(!confirm('Подтвердить?')) return; await AUTH.fetch('/backend/public/api/cosmetologist/bookings/'+id+'/confirm',{method:'PUT'}); loadBookings(); loadMonthData(); }
async function complete(id) { if(!confirm('Завершить?')) return; await AUTH.fetch('/backend/public/api/cosmetologist/bookings/'+id+'/complete',{method:'PUT'}); loadBookings(); loadMonthData(); }
async function cancel(id) { if(!confirm('Отменить?')) return; await AUTH.fetch('/backend/public/api/bookings/'+id+'/cancel',{method:'PUT'}); loadBookings(); loadMonthData(); }
function addComment(id) { var c = prompt('Комментарий:'); if(c && c.trim()) saveComment(id, c); }
function editComment(id, old) { var c = prompt('Редактировать:', old); if(c && c.trim()) saveComment(id, c); }
async function saveComment(id, c) { await AUTH.fetch('/backend/public/api/bookings/'+id+'/comment',{method:'PUT',body:JSON.stringify({description:c})}); loadBookings(); }
function openClient(id) { window.open('/frontend/cosmetologist/clients.php?client='+id,'_blank'); }
function esc(t) { if(!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>