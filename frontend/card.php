<?php
$pageTitle = 'Карточка косметолога';
$cosmetologistId = $_GET['id'] ?? null;

if (!$cosmetologistId) {
    echo '<script>window.location.href="/frontend/";</script>';
    exit;
}

$extraStyles = '
    .cosm-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
    .cosm-header h1 { color: white; margin: 0 0 8px 0; font-size: 28px; }
    .cosm-header p { margin: 3px 0; opacity: 0.9; font-size: 14px; }
    .cosm-avatar { width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 15px; border: 3px solid rgba(255,255,255,0.5); overflow: hidden; }
    .cosm-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .cosm-contacts { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 15px; }
    .cosm-contacts span { font-size: 13px; }
    
    .service-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 12px; cursor: pointer; transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; }
    .service-card:hover { border-color: #667eea; box-shadow: 0 2px 8px rgba(102,126,234,0.15); }
    .service-card.selected { border-color: #667eea; background: rgba(102,126,234,0.03); }
    .service-info { flex: 1; }
    .service-name { font-size: 16px; font-weight: 600; }
    .service-meta { font-size: 12px; color: #888; margin-top: 3px; }
    .service-price { font-size: 20px; font-weight: 700; color: #667eea; white-space: nowrap; margin-left: 15px; }
    
    .booking-panel { background: #f7fafc; border-radius: 12px; padding: 25px; margin-top: 25px; display: none; }
    .booking-panel.show { display: block; }
    
    .date-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
    .date-tab { padding: 8px 14px; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; text-align: center; font-size: 12px; background: white; transition: all 0.15s; min-width: 55px; }
    .date-tab:hover { border-color: #667eea; }
    .date-tab.active { background: #667eea; color: white; border-color: #667eea; }
    .date-tab .day-num { font-size: 16px; font-weight: 700; }
    
    .time-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px; margin-top: 10px; }
    .time-slot { padding: 10px 8px; border: 1px solid #ddd; border-radius: 8px; text-align: center; cursor: pointer; font-size: 13px; background: white; transition: all 0.15s; }
    .time-slot:hover { border-color: #667eea; background: #f0f4ff; }
    .time-slot.selected { background: #667eea; color: white; border-color: #667eea; }
    
    .btn { padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-outline { background: white; border: 1px solid #ddd; }
    .btn-lg { padding: 12px 20px; font-size: 15px; }
    .btn-block { display: block; width: 100%; }
    .form-control { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; box-sizing: border-box; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 4px; font-size: 13px; color: #555; }
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; }
    .alert-info { background: #d1ecf1; color: #0c5460; }
    .alert-warning { background: #fff3cd; color: #856404; }
    .alert-danger { background: #f8d7da; color: #721c24; }
    .alert-success { background: #d4edda; color: #155724; }
    .steps { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .step { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #888; }
    .step .num { width: 22px; height: 22px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }
    .step.done .num { background: #28a745; }
    .step.active { color: #333; font-weight: 500; }
';

include __DIR__ . '/partials/header.php';
?>

<div id="cosmetologist-content">
    <div class="loading-container" style="text-align:center;padding:50px;">
        <div class="loading-spinner"></div>
        <p>Загрузка...</p>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var cosmetologistId = <?= (int)$cosmetologistId ?>;
var cosmetologist = null;
var services = [];
var selectedService = null;
var selectedDate = null;
var selectedTime = null;
var isAuth = !!localStorage.getItem('access_token');
var user = JSON.parse(localStorage.getItem('user') || '{}');
var isClient = isAuth && user.role === 'client';
var isCosmetologist = isAuth && user.role === 'cosmetologist';

loadCosmetologist();

async function loadCosmetologist() {
    var container = document.getElementById('cosmetologist-content');
    
    try {
        var r = await fetch('/backend/public/api/cosmetologists/' + cosmetologistId);
        var d = await r.json();
        
        if (d.success) {
            cosmetologist = d.data.cosmetologist;
            services = d.data.services || [];
            render();
        } else {
            container.innerHTML = '<div class="container"><div class="alert alert-danger">Косметолог не найден</div></div>';
        }
    } catch(e) {
        container.innerHTML = '<div class="container"><div class="alert alert-danger">Ошибка загрузки</div></div>';
    }
}

function render() {
    var container = document.getElementById('cosmetologist-content');
    
    var avatarHtml = cosmetologist.avatar
        ? '<img src="data:image/jpeg;base64,' + cosmetologist.avatar + '" alt="' + esc(cosmetologist.first_name) + '">'
        : '<i class="fas fa-user"></i>';
    
    var showForm = isClient || isCosmetologist;
    
    container.innerHTML = `
        <div class="cosm-header">
            <div class="container" style="text-align:center;">
                <div class="cosm-avatar">${avatarHtml}</div>
                <h1>${esc(cosmetologist.first_name)} ${esc(cosmetologist.last_name)}</h1>
                <div class="cosm-contacts">
                    <span><i class="fas fa-phone"></i> ${esc(cosmetologist.phone || '—')}</span>
                    <span><i class="fas fa-map-marker-alt"></i> ${esc(cosmetologist.address || 'Адрес не указан')}</span>
                </div>
            </div>
        </div>
        
        <div class="container">
            <div style="display:grid; grid-template-columns:1fr 350px; gap:25px;">
                <div>
                    <h3>Услуги и цены</h3>
                    ${services.length === 0 ? '<p style="color:#999;">Нет услуг</p>' : services.map(function(s) {
                        return `
                            <div class="service-card" data-service-id="${s.id}" onclick="selectService(${s.id})">
                                <div class="service-info">
                                    <div class="service-name">${esc(s.service)}</div>
                                    <div class="service-meta"><i class="far fa-clock"></i> ${s.duration || '—'}</div>
                                    ${s.description ? '<div class="service-meta">' + esc(s.description) + '</div>' : ''}
                                </div>
                                <div class="service-price">${s.price} BYN</div>
                            </div>
                        `;
                    }).join('')}
                    
                    <div class="booking-panel" id="booking-panel">
                        <div class="steps">
                            <div class="step done" id="step-1"><span class="num">1</span> Услуга выбрана</div>
                            <div class="step" id="step-2"><span class="num">2</span> ${!isAuth ? 'Войдите' : (isCosmetologist ? 'Просмотр' : 'Дата и время')}</div>
                            ${isClient ? '<div class="step" id="step-3"><span class="num">3</span> Подтверждение</div>' :''}
                        </div>
                        
                        <div id="booking-auth" style="display:none;">
                            <div style="text-align:center;padding:20px;">
                                <p style="color:#888;">Для записи необходимо авторизоваться как клиент</p>
                                <a href="/frontend/login.php?redirect=card.php?id=${cosmetologistId}" class="btn btn-primary">Войти</a>
                                <a href="/frontend/register.php?redirect=card.php?id=${cosmetologistId}" class="btn btn-outline" style="margin-left:8px;">Регистрация</a>
                            </div>
                        </div>
                        
                        <div id="booking-form-block" style="display:none;">
                            <div class="form-group">
                                <label>Выбранная услуга</label>
                                <div id="selected-service-name" style="font-weight:600;"></div>
                            </div>
                            <div class="form-group">
                                <label>Дата</label>
                                <div class="date-tabs" id="date-tabs"></div>
                            </div>
                            <div class="form-group">
                                <label>Время</label>
                                <div class="time-slots" id="time-slots"><p style="color:#999;font-size:12px;">Выберите дату</p></div>
                            </div>
                            <div class="form-group">
                                <label>Комментарий</label>
                                <textarea class="form-control" id="comment" rows="2" placeholder="Необязательно"></textarea>
                            </div>
                            <button class="btn btn-primary btn-lg btn-block" id="booking-submit-btn">
                                ${isCosmetologist ? 'Проверить запись' : 'Подтвердить запись'}
                            </button>
                            ${isCosmetologist ? '<p style="text-align:center;margin-top:8px;font-size:11px;color:#888;">Режим тестирования — запись не создаётся</p>' : ''}
                        </div>
                    </div>
                </div>
                
                <div>
                    <div style="background:white;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                        <h4>Как записаться</h4>
                        <div style="font-size:13px;color:#666;line-height:1.8;">
                            <div><span style="background:#667eea;color:white;padding:2px 8px;border-radius:10px;margin-right:8px;">1</span> Выберите услугу</div>
                            <div><span style="background:#667eea;color:white;padding:2px 8px;border-radius:10px;margin-right:8px;">2</span> ${!isAuth ? 'Авторизуйтесь' : (isCosmetologist ? '<span style="color:#f0ad4e;">Косметолог (Тестрование)</span>' : '<span style="color:#28a745;">Клиент</span>')}</div>
                            <div><span style="background:#667eea;color:white;padding:2px 8px;border-radius:10px;margin-right:8px;">3</span> Выберите дату и время</div>
                            <div><span style="background:#667eea;color:white;padding:2px 8px;border-radius:10px;margin-right:8px;">4</span> Подтвердите</div>
                        </div>
                        ${!isAuth ? '<div class="alert alert-warning" style="margin-top:15px;"><a href="/frontend/login.php?redirect=card.php?id=' + cosmetologistId + '">Войти</a></div>' : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('booking-submit-btn')?.addEventListener('click', isCosmetologist ? testBooking : submitBooking);
}

function selectService(serviceId) {
    selectedService = services.find(function(s) { return s.id == serviceId; });
    document.querySelectorAll('.service-card').forEach(function(c) { c.classList.toggle('selected', c.dataset.serviceId == serviceId); });
    
    var panel = document.getElementById('booking-panel');
    panel.classList.add('show');
    document.getElementById('selected-service-name').textContent = selectedService.service + ' — ' + selectedService.price + ' BYN';
    document.getElementById('booking-auth').style.display = !isAuth ? 'block' : 'none';
    document.getElementById('booking-form-block').style.display = (isClient || isCosmetologist) ? 'block' : 'none';
    if (isClient || isCosmetologist) loadAvailableSlots();
    panel.scrollIntoView({ behavior: 'smooth' });
}

async function loadAvailableSlots() {
    if (!selectedService) return;
    var tabsContainer = document.getElementById('date-tabs');
    var slotsContainer = document.getElementById('time-slots');
    tabsContainer.innerHTML = '<span style="font-size:12px;color:#999;">Загрузка...</span>';
    slotsContainer.innerHTML = '';
    
    try {
        var r = await fetch('/backend/public/api/cosmetologists/' + cosmetologistId + '/slots?service_id=' + selectedService.id);
        var d = await r.json();
        var slots = d.data?.slots || [];
        
        var slotsByDate = {};
        slots.forEach(function(s) { var dt = s.begin_time.split(' ')[0]; if (!slotsByDate[dt]) slotsByDate[dt] = []; slotsByDate[dt].push(s); });
        var dates = Object.keys(slotsByDate).sort();
        
        if (dates.length === 0) {
            tabsContainer.innerHTML = '<span style="font-size:12px;color:#999;">Нет доступных дат</span>';
            slotsContainer.innerHTML = '<p style="color:#999;font-size:12px;">Нет доступного времени</p>';
            return;
        }
        
        window._slotsByDate = slotsByDate;
        var days = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
        var months = ['янв','фев','мар','апр','мая','июн','июл','авг','сен','окт','ноя','дек'];
        
        tabsContainer.innerHTML = dates.map(function(date, i) {
            var d = new Date(date);
            return '<div class="date-tab ' + (i === 0 ? 'active' : '') + '" data-date="' + date + '" onclick="selectDateTab(this,\'' + date + '\')"><div>' + days[d.getDay()] + '</div><div class="day-num">' + d.getDate() + '</div><div>' + months[d.getMonth()] + '</div></div>';
        }).join('');
        
        if (dates.length > 0) renderTimeSlots(dates[0]);
    } catch(e) { tabsContainer.innerHTML = '<span style="font-size:12px;color:#dc3545;">Ошибка загрузки</span>'; }
}

function selectDateTab(el, date) {
    document.querySelectorAll('.date-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
    selectedDate = date;
    renderTimeSlots(date);
}

function renderTimeSlots(date) {
    var container = document.getElementById('time-slots');
    var slots = (window._slotsByDate || {})[date] || [];
    if (slots.length === 0) { container.innerHTML = '<p style="color:#999;font-size:12px;">Нет свободного времени</p>'; return; }
    container.innerHTML = slots.map(function(s) {
        var t = s.begin_time.split(' ')[1]?.substring(0, 5) || '';
        return '<div class="time-slot" data-time="' + t + '" onclick="selectTimeSlot(this,\'' + t + '\')">' + t + '</div>';
    }).join('');
}

function selectTimeSlot(el, time) {
    document.querySelectorAll('#time-slots .time-slot').forEach(function(s) { s.classList.remove('selected'); });
    el.classList.add('selected');
    selectedTime = time;
}

function testBooking() {
    if (!selectedService || !selectedDate || !selectedTime) { alert('Выберите услугу, дату и время'); return; }
    alert('Функционал работает успешно!\n\nУслуга: ' + selectedService.service + '\nДата: ' + selectedDate + '\nВремя: ' + selectedTime + '\n\n(Запись не создана — режим тестирования)');
}

async function submitBooking() {
    if (!selectedService || !selectedDate || !selectedTime) { alert('Выберите услугу, дату и время'); return; }
    var comment = document.getElementById('comment').value;
    try {
        var r = await AUTH.fetch('/backend/public/api/bookings', {
            method: 'POST',
            body: JSON.stringify({ cosmetologist_id: cosmetologistId, service_id: selectedService.id, schedule: selectedDate + ' ' + selectedTime + ':00', description: comment || '' })
        });
        var d = await r.json();
        if (r.ok && d.success) { alert('Запись успешно создана!'); window.location.href = '/frontend/client/dashboard.php'; }
        else alert(d.error || 'Ошибка создания записи');
    } catch(e) { alert('Ошибка соединения'); }
}

function esc(t) { if (!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>