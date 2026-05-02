<?php
$pageTitle = 'Расписание';
$extraStyles = '
    :root {
        --dot-size: 6px;
        --color-my: #f0ad4e;
        --color-other: #d9534f;
        --color-slot-free: #e8f5e9;
        --color-slot-booked: #fff3e0;
        --color-slot-deactivated: #f5f5f5;
        --color-slot-partner: #fff8e1;
    }

    .schedule-layout { display: flex; gap: 20px; }
    .schedule-calendar { width: 320px; flex-shrink: 0; }
    .schedule-detail { flex: 1; min-width: 0; }

    /* Календарь */
    .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .cal-header h3 { margin: 0; font-size: 16px; font-weight: 600; }
    .cal-header button { background: white; border: 1px solid #e0e0e0; border-radius: 6px; padding: 5px 10px; cursor: pointer; font-size: 13px; }
    .cal-header button:hover { background: #f5f5f5; }
    .cal-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 4px; }
    .cal-weekdays span { text-align: center; font-size: 10px; color: #aaa; text-transform: uppercase; padding: 4px 0; }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
    
    .cal-day {
        position: relative;
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.15s;
        user-select: none;
        -webkit-user-select: none;
    }
    .cal-day:hover { background: #f0f4ff; }
    .cal-day.other-month { color: #e0e0e0; cursor: default; }
    .cal-day.other-month:hover { background: transparent; }
    .cal-day.no-slots { color: #ccc; }
    .cal-day.has-slots { color: #333; }
    .cal-day.today { box-shadow: inset 0 0 0 2px #667eea; }
    .cal-day.selected { background: #667eea; color: white !important; }
    .cal-day.selected-multi { background: #667eea20; }
    
    .cal-dots { display: flex; gap: 3px; margin-top: 2px; }
    .cal-dot { width: var(--dot-size); height: var(--dot-size); border-radius: 50%; }
    .cal-dot.my { background: var(--color-my); }
    .cal-dot.other { background: var(--color-other); }

    /* Тултип */
    .cal-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 11px;
        white-space: nowrap;
        z-index: 100;
        pointer-events: none;
        max-width: 200px;
    }
    .cal-tooltip::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #333;
    }
    .cal-day:hover .cal-tooltip { display: block; }
    @media (hover: none) {
        .cal-day:active .cal-tooltip { display: block; }
    }

    /* Панель управления */
    .panel { background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .panel-title { font-weight: 600; font-size: 14px; margin-bottom: 10px; }
    .panel-row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    
    /* Слоты */
    .slots-grid { display: flex; flex-wrap: wrap; gap: 6px; }
    .slot-item {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
        border: 1px solid transparent;
        position: relative;
        min-width: 65px;
        text-align: center;
    }
    .slot-free { background: var(--color-slot-free); border-color: #c8e6c9; color: #2e7d32; }
    .slot-free:hover { background: #c8e6c9; }
    .slot-booked { background: var(--color-slot-booked); border-color: #ffe0b2; color: #e65100; cursor: default; }
    .slot-deactivated { background: var(--color-slot-deactivated); border-color: #e0e0e0; color: #bbb; }
    .slot-deactivated:hover { background: #eeeeee; }
    .slot-partner { background: var(--color-slot-partner); border-color: #ffecb3; color: #f57f17; cursor: default; }
    
    .slot-actions { display: none; position: absolute; top: -8px; right: -8px; gap: 2px; }
    .slot-item:hover .slot-actions { display: flex; }
    .slot-actions button {
        width: 18px; height: 18px; border-radius: 50%; border: none; cursor: pointer;
        font-size: 8px; color: white; display: flex; align-items: center; justify-content: center;
    }
    .slot-actions .act-del { background: #dc3545; }
    .slot-actions .act-off { background: #ffc107; color: #333; }
    .slot-actions .act-on { background: #28a745; }

    .selected-dates { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
    .date-tag { 
        background: #667eea10; color: #667eea; padding: 3px 8px; border-radius: 12px; 
        font-size: 11px; display: flex; align-items: center; gap: 4px; cursor: pointer;
    }
    .date-tag:hover { background: #667eea20; }

    .btn { padding: 7px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-outline { background: white; border: 1px solid #ddd; }
    .btn-sm { padding: 4px 10px; font-size: 11px; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-warning { background: #ffc107; color: #333; }
    .form-control { padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 12px; }
    .form-control-sm { padding: 4px 8px; font-size: 11px; width: 60px; }

    @media (max-width: 768px) {
        .schedule-layout { flex-direction: column; }
        .schedule-calendar { width: 100%; }
    }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <h1>Расписание</h1>
            
            <div class="schedule-layout">
                <!-- КАЛЕНДАРЬ -->
                <div class="schedule-calendar">
                    <div class="cal-header">
                        <button onclick="changeMonth(-1)">←</button>
                        <h3 id="cal-title"></h3>
                        <button onclick="changeMonth(1)">→</button>
                    </div>
                    <div class="cal-weekdays">
                        <span>Пн</span><span>Вт</span><span>Ср</span><span>Чт</span><span>Пт</span><span>Сб</span><span>Вс</span>
                    </div>
                    <div class="cal-grid" id="cal-grid"></div>
                    <button class="btn btn-outline btn-sm" style="width:100%;margin-top:8px;" onclick="goToToday()">Сегодня</button>
                    
                    <!-- Выбранные даты -->
                    <div class="selected-dates" id="selected-dates"></div>
                    <button class="btn btn-primary btn-sm" style="width:100%;margin-top:8px;display:none;" id="apply-multi-btn" onclick="showMultiPanel()">
                        Рабочий режим на выбранные даты
                    </button>
                </div>
                
                <!-- ДЕТАЛИ -->
                <div class="schedule-detail" id="detail-panel">
                    <p class="text-muted text-center py-5">Выберите дату в календаре</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО: Рабочий режим на несколько дат -->
<div class="modal-overlay" id="multi-modal" style="display:none; position:fixed; top:0;left:0;right:0;bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:25px; width:90%; max-width:400px;">
        <h3 style="margin-top:0;">Рабочий режим</h3>
        <p style="font-size:12px;color:#888;" id="multi-dates-text"></p>
        <div class="form-group"><label>Начало</label><input type="time" class="form-control" id="multi-start" value="09:00"></div>
        <div class="form-group"><label>Конец</label><input type="time" class="form-control" id="multi-end" value="18:00"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:15px;">
            <button class="btn btn-outline" onclick="closeMultiModal()">Отмена</button>
            <button class="btn btn-primary" onclick="applyMultiSchedule()">Создать</button>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var curDate = new Date();
var selDate = null;
var multiDates = [];
var curUserId = null;
var cosmetologistId = null;
var monthData = {};  // { '2026-05-01': { hasSlots: true, myBookings: [], otherBookings: [] } }
var daySlots = [];

(function init() {
    var token = localStorage.getItem('access_token');
    if (token) {
        try { 
            var payload = JSON.parse(atob(token.split('.')[1]));
            curUserId = payload.user_id;
            cosmetologistId = payload.cosmetologist_id;
        } catch(e) {}
    }
    renderCal();
    loadMonthData();
})();

// ==================== КАЛЕНДАРЬ ====================
function changeMonth(d) { curDate.setMonth(curDate.getMonth() + d); renderCal(); loadMonthData(); }
function goToToday() { curDate = new Date(); selDate = formatDate(curDate); renderCal(); loadMonthData(); selectDate(selDate); }

function formatDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function renderCal() {
    var y = curDate.getFullYear(), m = curDate.getMonth();
    var months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
    document.getElementById('cal-title').textContent = months[m] + ' ' + y;
    
    var fd = new Date(y, m, 1).getDay(); fd = fd === 0 ? 6 : fd - 1;
    var dim = new Date(y, m + 1, 0).getDate();
    var prevDim = new Date(y, m, 0).getDate();
    var todayStr = formatDate(new Date());
    
    var html = '';
    
    // Предыдущий месяц
    for (var i = fd - 1; i >= 0; i--) {
        html += '<div class="cal-day other-month">' + (prevDim - i) + '</div>';
    }
    
    // Текущий месяц
    for (var day = 1; day <= dim; day++) {
        var ds = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        var cls = 'cal-day';
        
        if (ds === todayStr) cls += ' today';
        if (ds === selDate) cls += ' selected';
        if (multiDates.includes(ds)) cls += ' selected-multi';
        
        var dayInfo = monthData[ds];
        if (dayInfo && dayInfo.hasSlots) {
            cls += ' has-slots';
        } else {
            cls += ' no-slots';
        }
        
        html += '<div class="' + cls + '" onclick="onDayClick(\'' + ds + '\', event)" data-date="' + ds + '">';
        
        // Тултип
        if (dayInfo && (dayInfo.myBookings.length > 0 || dayInfo.otherBookings.length > 0)) {
            html += '<div class="cal-tooltip">';
            dayInfo.myBookings.forEach(function(b) {
                html += '<div style="color:#f0ad4e;">Моя: ' + b.time + ' ' + esc(b.service) + '</div>';
            });
            dayInfo.otherBookings.forEach(function(b) {
                html += '<div style="color:#d9534f;">' + esc(b.cosmetologist) + ': ' + b.time + ' ' + esc(b.service) + '</div>';
            });
            html += '</div>';
        }
        
        html += day;
        
        // Точки
        if (dayInfo && (dayInfo.myBookings.length > 0 || dayInfo.otherBookings.length > 0)) {
            html += '<div class="cal-dots">';
            if (dayInfo.myBookings.length > 0) html += '<span class="cal-dot my"></span>';
            if (dayInfo.otherBookings.length > 0) html += '<span class="cal-dot other"></span>';
            html += '</div>';
        }
        
        html += '</div>';
    }
    
    // Оставшиеся ячейки
    var total = fd + dim;
    var remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (var i = 1; i <= remaining; i++) {
        html += '<div class="cal-day other-month">' + i + '</div>';
    }
    
    document.getElementById('cal-grid').innerHTML = html;
}

function onDayClick(ds, event) {
    if (event.ctrlKey || event.metaKey) {
        // Множественный выбор
        var idx = multiDates.indexOf(ds);
        if (idx > -1) multiDates.splice(idx, 1);
        else multiDates.push(ds);
        updateMultiDates();
        renderCal();
    } else {
        // Одиночный выбор
        selDate = ds;
        multiDates = [];
        updateMultiDates();
        renderCal();
        selectDate(ds);
    }
}

function updateMultiDates() {
    var container = document.getElementById('selected-dates');
    var btn = document.getElementById('apply-multi-btn');
    
    container.innerHTML = multiDates.map(function(d) {
        return '<span class="date-tag" onclick="removeMultiDate(\'' + d + '\')">' + d + ' ✕</span>';
    }).join('');
    
    btn.style.display = multiDates.length > 1 ? 'block' : 'none';
}

function removeMultiDate(ds) {
    multiDates = multiDates.filter(function(d) { return d !== ds; });
    updateMultiDates();
    renderCal();
}

// ==================== ЗАГРУЗКА ДАННЫХ ====================
async function loadMonthData() {
    var y = curDate.getFullYear(), m = String(curDate.getMonth() + 1).padStart(2, '0');
    var startDate = y + '-' + m + '-01';
    var endDate = y + '-' + m + '-31';
    
    try {
        // 🔥 Загружаем РАСПИСАНИЕ (слоты) — для чёрных/серых чисел
        var r1 = await AUTH.fetch('/backend/public/api/cosmetologist/schedule?date=' + startDate);
        var d1 = await r1.json();
        
        // 🔥 Загружаем ЗАПИСИ (брони) — для точек
        var r2 = await AUTH.fetch('/backend/public/api/cosmetologist/schedule/calendar-data?start_date=' + startDate + '&end_date=' + endDate);
        var d2 = await r2.json();
        
        monthData = {};
        
        // Помечаем дни со слотами (чёрные/серые)
        if (d1.success && d1.data.slots) {
            var datesWithSlots = {};
            d1.data.slots.forEach(function(s) {
                var date = s.begin_time.split(' ')[0];
                datesWithSlots[date] = true;
            });
            
            Object.keys(datesWithSlots).forEach(function(date) {
                if (!monthData[date]) monthData[date] = { hasSlots: false, myBookings: [], otherBookings: [] };
                monthData[date].hasSlots = true;
            });
        }
        
        // 🔥 Добавляем записи для точек
        if (d2.success && d2.data.bookings) {
            Object.keys(d2.data.bookings).forEach(function(date) {
                if (!monthData[date]) monthData[date] = { hasSlots: false, myBookings: [], otherBookings: [] };
                
                d2.data.bookings[date].forEach(function(b) {
                    var time = b.time ? b.time.substring(0, 5) : '';
                    if (b.cosmetologist_user_id == curUserId) {
                        monthData[date].myBookings.push({ time: time, service: b.service_name });
                    } else {
                        monthData[date].otherBookings.push({ 
                            time: time, 
                            service: b.service_name, 
                            cosmetologist: b.cosmetologist_name 
                        });
                    }
                });
            });
        }
        
        renderCal();
    } catch(e) { console.error('loadMonthData error:', e); }
}

// ==================== ВЫБОР ДАТЫ ====================
async function selectDate(ds) {
    var panel = document.getElementById('detail-panel');
    panel.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>';
    
    try {
        // Слоты косметолога
        var r1 = await AUTH.fetch('/backend/public/api/cosmetologist/schedule?date=' + ds);
        var d1 = await r1.json();
        
        // Занятые слоты (для партнёра)
        var r2 = await AUTH.fetch('/backend/public/api/cosmetologist/schedule/booked?date=' + ds);
        var d2 = await r2.json();
        
        var mySlots = d1.success ? (d1.data.slots || []) : [];
        var partnerSlots = [];
        
        if (d2.success && d2.data.cosmetologists) {
            d2.data.cosmetologists.forEach(function(cosm) {
                if (cosm.cosmetologist_id != cosmetologistId) {
                    cosm.slots.forEach(function(s) {
                        partnerSlots.push({
                            begin_time: s.begin_time,
                            end_time: s.end_time,
                            client_name: s.client_name,
                            service: s.booking_service
                        });
                    });
                }
            });
        }
        
        daySlots = mySlots;
        renderDetailPanel(ds, mySlots, partnerSlots);
    } catch(e) {
        panel.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function renderDetailPanel(ds, mySlots, partnerSlots) {
    var panel = document.getElementById('detail-panel');
    var stats = {
        total: mySlots.length,
        free: mySlots.filter(function(s) { return s.is_booked == 0; }).length,
        booked: mySlots.filter(function(s) { return s.is_booked == 1; }).length,
        deactivated: mySlots.filter(function(s) { return s.is_booked == 2; }).length
    };
    
    var html = '<div class="panel">' +
        '<div class="panel-title">' + ds + ' — слотов: ' + stats.total + ' (свободно: ' + stats.free + ', занято: ' + stats.booked + ', отключено: ' + stats.deactivated + ')</div>';
    
    // Мои слоты
    html += '<div style="margin-bottom:10px;"><strong>Мои окна:</strong></div>';
    html += '<div class="slots-grid">';
    
    if (mySlots.length === 0) {
        html += '<span style="color:#aaa;font-size:12px;">Нет окон</span>';
    } else {
        mySlots.forEach(function(s) {
            var time = s.begin_time.split(' ')[1]?.substring(0, 5) || '';
            var cls = 'slot-free';
            var title = 'Свободно';
            
            if (s.is_booked == 1) {
                cls = 'slot-booked';
                title = 'Занято: ' + (s.client_name || '') + ' — ' + (s.booking_service || '');
            } else if (s.is_booked == 2) {
                cls = 'slot-deactivated';
                title = 'Отключено';
            }
            
            html += '<div class="slot-item ' + cls + '" title="' + title + '">' + time +
                '<div class="slot-actions">';
            
            if (s.is_booked != 1) {
                html += '<button class="act-del" onclick="deleteSlot(' + s.id + ')" title="Удалить">✕</button>';
                if (s.is_booked == 0) {
                    html += '<button class="act-off" onclick="deactivateSlot(' + s.id + ')" title="Отключить">⏸</button>';
                } else if (s.is_booked == 2) {
                    html += '<button class="act-on" onclick="activateSlot(' + s.id + ')" title="Включить">▶</button>';
                }
            }
            
            html += '</div></div>';
        });
    }
    html += '</div>';
    
    // Слоты партнёра
    if (partnerSlots.length > 0) {
        html += '<div style="margin-bottom:10px;margin-top:15px;"><strong>Занято у партнёра:</strong></div>';
        html += '<div class="slots-grid">';
        partnerSlots.forEach(function(s) {
            var time = s.begin_time.split(' ')[1]?.substring(0, 5) || '';
            html += '<div class="slot-item slot-partner" title="' + esc(s.client_name) + ' — ' + esc(s.service) + '">' + time + '</div>';
        });
        html += '</div>';
    }
    
    // Кнопки управления
    html += '<div style="margin-top:15px;display:flex;gap:8px;flex-wrap:wrap;">' +
        '<button class="btn btn-primary btn-sm" onclick="showAddSlotForm(\'' + ds + '\')">+ Окно</button>' +
        '<button class="btn btn-outline btn-sm" onclick="showDayForm(\'' + ds + '\')">📅 Рабочий режим</button>' +
        '<button class="btn btn-warning btn-sm" onclick="deactivateDay(\'' + ds + '\')">⏸ Отключить все</button>' +
        '<button class="btn btn-outline btn-sm" onclick="activateDay(\'' + ds + '\')">▶ Включить свободные</button>' +
        '<button class="btn btn-danger btn-sm" onclick="clearDay(\'' + ds + '\')">🗑 Очистить день</button>' +
    '</div>';
    
    // Форма добавления одного окна
    html += '<div id="add-slot-form" style="display:none;margin-top:10px;display:flex;gap:6px;align-items:center;">' +
        '<input type="time" class="form-control form-control-sm" id="slot-time" value="09:00">' +
        '<button class="btn btn-primary btn-sm" onclick="addSingleSlot(\'' + ds + '\')">Добавить</button>' +
        '<button class="btn btn-outline btn-sm" onclick="document.getElementById(\'add-slot-form\').style.display=\'none\'">Отмена</button>' +
    '</div>';
    
    // Форма рабочего режима на день
    html += '<div id="day-form" style="display:none;margin-top:10px;display:flex;gap:6px;align-items:center;">' +
        'с <input type="time" class="form-control form-control-sm" id="day-start" value="09:00">' +
        'до <input type="time" class="form-control form-control-sm" id="day-end" value="18:00">' +
        '<button class="btn btn-primary btn-sm" onclick="applyDaySchedule(\'' + ds + '\')">Создать</button>' +
        '<button class="btn btn-outline btn-sm" onclick="document.getElementById(\'day-form\').style.display=\'none\'">Отмена</button>' +
    '</div>';
    
    html += '</div>';
    
    panel.innerHTML = html;
    
    // Скрываем формы по умолчанию
    var addForm = document.getElementById('add-slot-form');
    var dayForm = document.getElementById('day-form');
    if (addForm) addForm.style.display = 'none';
    if (dayForm) dayForm.style.display = 'none';
}

function showAddSlotForm(ds) {
    var form = document.getElementById('add-slot-form');
    if (form) form.style.display = 'flex';
}

function showDayForm(ds) {
    var form = document.getElementById('day-form');
    if (form) form.style.display = 'flex';
}

// ==================== ДЕЙСТВИЯ ====================
// Универсальная функция добавления минут к времени
function addMinutes(time, mins) {
    var parts = time.split(':');
    var h = parseInt(parts[0]);
    var m = parseInt(parts[1]) + mins;
    if (m >= 60) { h += Math.floor(m / 60); m = m % 60; }
    if (h >= 24) { h = h % 24; }
    return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
}

async function addSingleSlot(ds) {
    var time = document.getElementById('slot-time').value;
    if (!time) return;
    
    var endTime = addMinutes(time, 30);
    
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/schedule/generate', {
            method: 'POST',
            body: JSON.stringify({ dates: [ds], start_time: time, end_time: endTime })
        });
        var d = await r.json();
        if (r.ok && d.success) { selectDate(ds); loadMonthData(); }
        else { alert(d.error || 'Ошибка'); }
    } catch(e) {}
}

async function applyDaySchedule(ds) {
    var start = document.getElementById('day-start').value;
    var end = document.getElementById('day-end').value;
    if (!start || !end) return;
    
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/schedule/generate', {
            method: 'POST',
            body: JSON.stringify({ dates: [ds], start_time: start, end_time: end })
        });
        if (r.ok) { selectDate(ds); loadMonthData(); }
    } catch(e) {}
}

async function deleteSlot(id) {
    if (!confirm('Удалить окно?')) return;
    await AUTH.fetch('/backend/public/api/cosmetologist/schedule/slot/' + id, { method: 'DELETE' });
    selectDate(selDate); loadMonthData();
}

async function deactivateSlot(id) {
    await AUTH.fetch('/backend/public/api/cosmetologist/schedule/slot/' + id + '/deactivate', { method: 'PUT' });
    selectDate(selDate); loadMonthData();
}

async function activateSlot(id) {
    await AUTH.fetch('/backend/public/api/cosmetologist/schedule/slot/' + id + '/activate', { method: 'PUT' });
    selectDate(selDate); loadMonthData();
}

async function deactivateDay(ds) {
    if (!confirm('Отключить все свободные окна на ' + ds + '?')) return;
    await AUTH.fetch('/backend/public/api/cosmetologist/schedule/day/deactivate', {
        method: 'PUT', body: JSON.stringify({ date: ds })
    });
    selectDate(ds); loadMonthData();
}

async function activateDay(ds) {
    await AUTH.fetch('/backend/public/api/cosmetologist/schedule/day/activate', {
        method: 'PUT', body: JSON.stringify({ date: ds })
    });
    selectDate(ds); loadMonthData();
}

async function clearDay(ds) {
    if (!confirm('Удалить ВСЕ свободные окна на ' + ds + '?')) return;
    await AUTH.fetch('/backend/public/api/cosmetologist/schedule/day', {
        method: 'DELETE', body: JSON.stringify({ date: ds })
    });
    selectDate(ds); loadMonthData();
}

// ==================== МУЛЬТИ-ДАТЫ ====================
function showMultiPanel() {
    if (multiDates.length < 2) return;
    document.getElementById('multi-dates-text').textContent = 'Выбрано дат: ' + multiDates.length;
    document.getElementById('multi-modal').style.display = 'flex';
}

function closeMultiModal() {
    document.getElementById('multi-modal').style.display = 'none';
}

async function applyMultiSchedule() {
    var start = document.getElementById('multi-start').value;
    var end = document.getElementById('multi-end').value;
    if (!start || !end || multiDates.length === 0) return;
    
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/schedule/generate', {
            method: 'POST',
            body: JSON.stringify({ dates: multiDates, start_time: start, end_time: end })
        });
        if (r.ok) {
            closeMultiModal();
            multiDates = [];
            updateMultiDates();
            loadMonthData();
            if (selDate) selectDate(selDate);
        }
    } catch(e) {}
}

// Закрытие модалки по клику вне
document.getElementById('multi-modal').addEventListener('click', function(e) {
    if (e.target === this) closeMultiModal();
});

function esc(t) { if(!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>