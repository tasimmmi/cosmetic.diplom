<?php
$pageTitle = 'Отчёты';
$extraStyles = '
    .bookings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .booking-column { min-width: 0; }
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
    
    .stat-card { background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-card .stat-value { font-size: 24px; font-weight: 700; color: #667eea; white-space: nowrap; }
    .stat-card .stat-label { font-size: 11px; color: #888; margin-top: 4px; }
    .stat-card.warning .stat-value { color: #f0ad4e; }
    .stat-card.success .stat-value { color: #28a745; }
    .stat-card.danger .stat-value { color: #dc3545; }
    
    .report-section { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .report-section h3 { margin-top: 0; font-size: 16px; font-weight: 600; color: #333; display: flex; justify-content: space-between; align-items: center; }
    
    /* Шапка отчёта - только для печати */
    .print-header {
        display: none;
    }
    
    .table-mini { width: 100%; font-size: 13px; border-collapse: collapse; }
    .table-mini th { text-align: left; padding: 8px 10px; border-bottom: 2px solid #eee; color: #888; font-weight: 500; white-space: nowrap; }
    .table-mini td { padding: 8px 10px; border-bottom: 1px solid #f5f5f5; }
    .table-mini tr:hover td { background: #fafbff; }
    
    .badge-stock { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; display: inline-block; white-space: nowrap; }
    .badge-stock.in { background: #d4edda; color: #155724; }
    .badge-stock.out { background: #f8d7da; color: #721c24; }
    
    /* Единое оформление для типа материала - без цветного фона */
    .material-type {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        white-space: nowrap;
        background: #f0f0f0;
        color: #555;
    }
    
    .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .btn { padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
    .btn-primary { background: #667eea; color: white; }
    .btn-primary:hover { background: #5a67d8; }
    .btn-outline { background: white; border: 1px solid #ddd; }
    .btn-outline:hover { background: #f5f5f5; }
    .btn-sm { padding: 4px 10px; font-size: 11px; }
    .form-control { padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
    
    /* Стиль для чекбокса "За всё время" */
    .all-time-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: 10px;
    }
    .all-time-checkbox input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .all-time-checkbox label {
        cursor: pointer;
        font-size: 13px;
        color: #555;
        white-space: nowrap;
    }
    
    .fin-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 10px; }
    .fin-item { text-align: center; }
    .fin-value { font-size: 22px; font-weight: 700; white-space: nowrap; }
    .fin-value span { font-size: 12px; font-weight: 500; }
    .fin-label { font-size: 11px; color: #999; margin-top: 2px; }
    
    .info-dot { display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: #ddd; color: #888; text-align: center; line-height: 14px; font-size: 9px; cursor: default; flex-shrink: 0; }
    .info-dot:hover { background: #667eea; color: #fff; }
    
    /* Вкладки для отчёта по закупкам */
    .procurement-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }
    .procurement-tab {
        padding: 8px 16px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 13px;
        color: #666;
        transition: all 0.2s;
        margin-bottom: -1px;
    }
    .procurement-tab:hover {
        color: #667eea;
    }
    .procurement-tab.active {
        color: #667eea;
        border-bottom: 2px solid #667eea;
    }
    
    /* Кнопка печати без текста */
    .print-icon {
        background: none;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 5px 8px;
        cursor: pointer;
        color: #666;
        font-size: 14px;
        transition: all 0.2s;
    }
    .print-icon:hover {
        background: #f5f5f5;
        color: #667eea;
        border-color: #667eea;
    }
    
    @media (max-width: 700px) {
        .bookings-grid { grid-template-columns: 1fr; }
        .filter-bar { flex-direction: column; align-items: stretch; }
        .all-time-checkbox { margin-left: 0; }
    }
    
    @media (max-width: 500px) {
        .table-mini { font-size: 12px; }
        .table-mini th, .table-mini td { padding: 6px 6px; }
        .badge-stock, .material-type { padding: 2px 6px; font-size: 10px; }
        .fin-value { font-size: 18px; }
        .fin-value span { font-size: 10px; }
        .stat-card { padding: 15px 10px; }
        .stat-card .stat-value { font-size: 20px; }
        .report-section h3 { flex-wrap: wrap; gap: 10px; }
    }
    
    @media print {
        body * { visibility: hidden; }
        #print-main, #print-main * { visibility: visible; }
        #print-main { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print, #bookings-section, .filter-bar, .procurement-tabs { display: none !important; }
        .print-icon, .btn { display: none !important; }
        
        /* Показываем шапку только при печати */
        .print-header {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .print-header h2 {
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .print-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
    }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Отчёты и аналитика</h1>
                <div class="no-print">
                    <button class="btn btn-outline btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Печать всего</button>
                </div>
            </div>
            
            <div class="filter-bar no-print">
                <input type="date" class="form-control" id="start-date" value="<?= date('Y-m-01') ?>">
                <span>—</span>
                <input type="date" class="form-control" id="end-date" value="<?= date('Y-m-t') ?>">
                <div class="all-time-checkbox">
                    <input type="checkbox" id="all-time-checkbox" onchange="toggleAllTime()">
                    <label for="all-time-checkbox">За всё время</label>
                </div>
                <button class="btn btn-primary btn-sm" onclick="loadReports()">Показать</button>
            </div>
            
            <div id="print-main">
                <!-- Шапка отчёта - будет видна только при печати -->
                <div class="print-header" id="print-header">
                    <h2 id="print-cosmetologist-name">Косметолог</h2>
                    <p id="print-report-period">Период: --</p>
                </div>
                
                <div id="stats-row" class="stats-row"></div>
                <div class="report-section" id="financial-section"></div>
                <div class="report-section" id="services-section"></div>
                <div class="report-section" id="procurement-section">
                    <h3>
                        Отчёт по закупкам
                        <button class="print-icon no-print" onclick="printProcurementSection()" title="Печать">
                            <i class="fas fa-print"></i>
                        </button>
                    </h3>
                    <div class="procurement-tabs no-print">
                        <button class="procurement-tab active" data-procurement-tab="all" onclick="switchProcurementTab('all')">Все закупки</button>
                        <button class="procurement-tab" data-procurement-tab="my" onclick="switchProcurementTab('my')">Мои материалы</button>
                        <button class="procurement-tab" data-procurement-tab="shared" onclick="switchProcurementTab('shared')">Общие материалы</button>
                    </div>
                    <div id="procurement-content">
                        <div class="loading-container"><div class="loading-spinner"></div><p>Загрузка...</p></div>
                    </div>
                </div>
                <div class="report-section" id="materials-section"></div>
            </div>
            
            <div class="report-section no-print" id="bookings-section"></div>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var currentProcurementTab = 'all';
var allProcurements = [];
var cosmetologistName = '';

loadReports();

function toggleAllTime() {
    var isAllTime = document.getElementById('all-time-checkbox').checked;
    var startDateInput = document.getElementById('start-date');
    var endDateInput = document.getElementById('end-date');
    
    if (isAllTime) {
        startDateInput.disabled = true;
        endDateInput.disabled = true;
        startDateInput.style.opacity = '0.6';
        endDateInput.style.opacity = '0.6';
    } else {
        startDateInput.disabled = false;
        endDateInput.disabled = false;
        startDateInput.style.opacity = '1';
        endDateInput.style.opacity = '1';
    }
    
    loadReports();
}

function getDateParams() {
    var isAllTime = document.getElementById('all-time-checkbox').checked;
    var startDateInput = document.getElementById('start-date');
    var endDateInput = document.getElementById('end-date');
    
    if (isAllTime) {
        return { start_date: '1970-01-01', end_date: '2099-12-31' };
    }
    
    return {
        start_date: startDateInput.value,
        end_date: endDateInput.value
    };
}

async function loadReports() {
    var dateParams = getDateParams();
    var start = dateParams.start_date;
    var end = dateParams.end_date;
    
    var isAllTime = document.getElementById('all-time-checkbox').checked;
    if (isAllTime) {
        document.getElementById('print-report-period').textContent = 'Период: За всё время';
    } else {
        document.getElementById('print-report-period').textContent = 'Период: ' + formatDateDisplay(start) + ' — ' + formatDateDisplay(end);
    }
    
    try {
        var user = JSON.parse(localStorage.getItem('user') || '{}');
        if (user.fullname) {
            cosmetologistName = user.fullname;
        } else if (user.first_name && user.last_name) {
            cosmetologistName = user.first_name + ' ' + user.last_name;
        } else {
            cosmetologistName = user.email || 'Косметолог';
        }
        document.getElementById('print-cosmetologist-name').textContent = cosmetologistName;
        
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/reports/summary?start_date=' + start + '&end_date=' + end);
        var d = await r.json();
        
        if (d.success) {
            var data = d.data;
            renderStats(data.stats);
            renderFinancial(data.financial);
            renderServices(data.services);
            renderMaterials(data.materials, data.out_of_stock);
            renderBookings(data.today_bookings || [], data.tomorrow_pending || []);
            
            if (data.procurements) {
                allProcurements = data.procurements;
                renderProcurements();
            } else {
                document.getElementById('procurement-content').innerHTML = '<p class="text-muted">Нет данных о закупках за выбранный период</p>';
            }
        }
        
    } catch(e) { 
        console.error(e);
        document.getElementById('procurement-content').innerHTML = '<p class="text-danger">Ошибка загрузки данных</p>';
    }
}

function formatDateDisplay(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return d.toLocaleDateString('ru-RU', {day:'2-digit',month:'2-digit',year:'numeric'});
}

function renderProcurements() {
    var container = document.getElementById('procurement-content');
    
    var filtered = [];
    
    if (currentProcurementTab === 'my') {
        filtered = allProcurements.filter(function(p) { return !p.is_mutable; });
    } else if (currentProcurementTab === 'shared') {
        filtered = allProcurements.filter(function(p) { return p.is_mutable; });
    } else {
        filtered = allProcurements;
    }
    
    if (filtered.length === 0) {
        var emptyMsg = '';
        if (currentProcurementTab === 'my') {
            emptyMsg = 'Нет закупок по личным материалам за выбранный период';
        } else if (currentProcurementTab === 'shared') {
            emptyMsg = 'Нет закупок по общим материалам за выбранный период';
        } else {
            emptyMsg = 'Нет закупок за выбранный период';
        }
        container.innerHTML = '<p class="text-muted text-center py-4">' + emptyMsg + '</p>';
        return;
    }
    
    var totalAmount = filtered.reduce(function(sum, p) { return sum + parseFloat(p.price); }, 0);
    var totalCount = filtered.length;
    
    var html = '<table class="table-mini">' +
        '<thead>' +
            '<tr>' +
                '<th>Дата</th>' +
                '<th>Материал</th>' +
                '<th>Тип</th>' +
                '<th>Цена (BYN)</th>' +
            '</tr>' +
        '</thead>' +
        '<tbody>';
    
    filtered.forEach(function(p) {
        var typeLabel = p.is_mutable ? 'Общий' : 'Личный';
        html += '<tr>' +
            '<td>' + fmtDateDisplay(p.date) + '</td>' +
            '<td>' + esc(p.material_name) + '</td>' +
            '<td><span class="material-type">' + typeLabel + '</span></td>' +
            '<td><strong>' + formatNum(p.price) + ' BYN</strong></td>' +
        '</tr>';
    });
    
    html += '</tbody>' +
        '<tfoot style="background: #f8f9fa; font-weight: 600;">' +
            '<tr>' +
                '<td colspan="3"><strong>Итого:</strong></td>' +
                '<td><strong>' + formatNum(totalAmount) + ' BYN</strong></td>' +
            '</tr>' +
            '<tr>' +
                '<td colspan="3"><strong>Количество закупок:</strong></td>' +
                '<td><strong>' + totalCount + '</strong></td>' +
            '</tr>' +
        '</tfoot>' +
    '</tr>';
    
    container.innerHTML = html;
}

function printProcurementSection() {
    var content = document.getElementById('procurement-section').cloneNode(true);
    
    var btns = content.querySelectorAll('.print-icon, .procurement-tabs, .btn');
    btns.forEach(function(btn) { btn.remove(); });
    
    var title = 'Отчёт по закупкам - ' + cosmetologistName + ' за выбранный период';
    
    var oldFrame = document.getElementById('print-frame');
    if (oldFrame) oldFrame.remove();
    
    var frame = document.createElement('iframe');
    frame.id = 'print-frame';
    frame.style.display = 'none';
    document.body.appendChild(frame);
    
    frame.contentDocument.write('<html><head><title>' + title + '</title>');
    frame.contentDocument.write('<style>body{font-family:Arial;padding:20px}table{width:100%;border-collapse:collapse}th,td{padding:8px;text-align:left;border-bottom:1px solid #ddd}th{color:#888;background:#f5f5f5}.material-type{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;background:#f0f0f0;color:#555}@media print{body{margin:0}}</style>');
    frame.contentDocument.write('</head><body>' + content.innerHTML + '</body></html>');
    frame.contentDocument.close();
    
    frame.contentWindow.focus();
    frame.contentWindow.print();
    
    setTimeout(function() { frame.remove(); }, 1000);
}

function switchProcurementTab(tab) {
    currentProcurementTab = tab;
    
    document.querySelectorAll('.procurement-tab').forEach(function(btn) {
        btn.classList.remove('active');
        if (btn.getAttribute('data-procurement-tab') === tab) {
            btn.classList.add('active');
        }
    });
    
    renderProcurements();
}

function renderStats(stats) {
    if (!stats) return;
    document.getElementById('stats-row').innerHTML = `
        <div class="stat-card"><div class="stat-value">${stats.today_bookings||0}</div><div class="stat-label">Записей сегодня</div></div>
        <div class="stat-card warning"><div class="stat-value">${stats.tomorrow_pending||0}</div><div class="stat-label">Подтверждений на завтра</div></div>
        <div class="stat-card success"><div class="stat-value">${stats.free_slots||0}</div><div class="stat-label">Свободных окон</div></div>
        <div class="stat-card"><div class="stat-value">${stats.in_stock||0}/${stats.total_materials||0}</div><div class="stat-label">Материалов в наличии</div></div>
    `;
}

function renderFinancial(data) {
    var html = '<h3>Финансовый отчёт</h3>';
    
    if (!data || data.length === 0) {
        html += '<p style="color:#999;">Нет данных за период</p>';
    } else {
        var r = data[0];
        html += '<div class="fin-grid">' +
            '<div class="fin-item"><div class="fin-value" style="color:#667eea;">' + formatNum(r.total_revenue) + ' <span>BYN</span></div><div class="fin-label">Выручка</div></div>' +
            '<div class="fin-item"><div class="fin-value" style="color:#f0ad4e;">' + formatNum(r.total_procurements_cost) + ' <span>BYN</span></div><div class="fin-label">Расходы</div></div>' +
            '<div class="fin-item"><div class="fin-value" style="color:' + (r.net_profit >= 0 ? '#28a745' : '#dc3545') + ';">' + formatNum(r.net_profit) + ' <span>BYN</span></div><div class="fin-label">Прибыль</div></div>' +
            '<div class="fin-item"><div class="fin-value" style="color:#666;">' + (r.profitability_percent||0) + ' <span>%</span></div><div class="fin-label">Рентабельность</div></div>' +
        '</div>';
    }
    document.getElementById('financial-section').innerHTML = html;
}

function formatNum(val) {
    return parseFloat(val||0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ');
}

function renderServices(data) {
    var html = '<h3>Услуги за период</h3>';
    
    if (!data || data.length === 0) {
        html += '<p style="color:#999;">Нет данных</p>';
    } else {
        html += '<table class="table-mini">' +
            '<thead>' +
                '<tr>' +
                    '<th>Услуга</th>' +
                    '<th>Выполнено</th>' +
                    '<th>Отменено <span class="info-dot" title="Все незавершённые записи">i</span></th>' +
                    '<th>Выручка</th>' +
                    '<th>Доля</th>' +
                '</tr>' +
            '</thead>' +
            '<tbody>';
        
        data.forEach(function(s) {
            html += '<tr>' +
                '<td>' + esc(s.service) + '</td>' +
                '<td>' + s.completed_count + '</td>' +
                '<td>' + s.cancelled_count + '</td>' +
                '<td>' + s.total_revenue + ' BYN</td>' +
                '<td>' + (s.revenue_percentage || 0) + '%</td>' +
            '</tr>';
        });
        
        html += '</tbody></table>';
    }
    document.getElementById('services-section').innerHTML = html;
}

function renderMaterials(stats, outOfStock) {
    var html = '<h3>Материалы</h3>';
    
    if (!stats || stats.length === 0) {
        html += '<p style="color:#999;">Нет материалов</p>';
    } else {
        html += '<table class="table-mini">' +
            '<thead>' +
                '<tr>' +
                    '<th>Материал</th>' +
                    '<th>Наличие</th>' +
                    '<th>Тип</th>' +
                    '<th>Посл. закупка</th>' +
                    '<th>Средняя цена</th>' +
                '</tr>' +
            '</thead>' +
            '<tbody>';
        
        stats.forEach(function(m) {
            var typeLabel = (m.is_mutable == 1) ? 'Общий' : 'Личный';
            html += '<tr>' +
                '<td>' + esc(m.material) + '</td>' +
                '<td><span class="badge-stock ' + (m.is_stock == 1 ? 'in' : 'out') + '">' + esc(m.is_stock_text) + '</span></td>' +
                '<td><span class="material-type">' + typeLabel + '</span></td>' +
                '<td>' + (m.last_procurement ? fmtDateDisplay(m.last_procurement) : '—') + '</td>' +
                '<td>' + (m.average_price || '—') + '</td>' +
            '</tr>';
        });
        
        html += '</tbody></table>';
    }
    
    if (outOfStock && outOfStock.length > 0) {
        html += '<div style="margin-top:10px;"><strong style="color:#dc3545;">Отсутствуют:</strong> ' + outOfStock.map(function(m) { return esc(m.material_name); }).join(', ') + '</div>';
    }
    
    document.getElementById('materials-section').innerHTML = html;
}

function renderBookings(today, tomorrow) {
    var html = '<div class="bookings-grid">';
    
    html += '<div class="booking-column"><h3 style="display:flex;justify-content:space-between;align-items:center;">Записи на сегодня <button class="btn btn-outline btn-sm" onclick="printSection(\'print-today\')"><i class="fas fa-print"></i></button></h3>';
    html += '<div id="print-today">';
    html += todayTableHtml(today);
    html += '</div></div>';
    
    html += '<div class="booking-column"><h3 style="display:flex;justify-content:space-between;align-items:center;">Подтвердить на завтра <button class="btn btn-outline btn-sm" onclick="printSection(\'print-tomorrow\')"><i class="fas fa-print"></i></button></h3>';
    html += '<div id="print-tomorrow">';
    html += tomorrowTableHtml(tomorrow);
    html += '</div></div>';
    
    html += '</div>';
    
    document.getElementById('bookings-section').innerHTML = html;
}

function todayTableHtml(today) {
    if (!today || today.length === 0) today = [];
    var sc = {pending:'Ожидает',confirmed:'Подтверждена',completed:'Завершена',cancelled:'Отменена'};
    var html = '<table class="table-mini"><thead><tr><th>Время</th><th>Клиент</th><th>Услуга</th><th>Статус</th></tr></thead><tbody>';
    if (today.length === 0) {
        html += '<tr><td colspan="4" style="color:#999;text-align:center;">Нет записей<\/td><\/tr>';
    } else {
        today.forEach(function(b) {
            html += '<tr>' +
                '<td>' + (b.booking_time ? b.booking_time.substring(0,5) : '—') + '</td>' +
                '<td>' + esc(b.client_name) + '</td>' +
                '<td>' + esc(b.service) + '</td>' +
                '<td>' + (sc[b.booking_status] || b.booking_status) + '</td>' +
            '</tr>';
        });
    }
    html += '<\/tbody><\/table>';
    return html;
}

function tomorrowTableHtml(tomorrow) {
    if (!tomorrow || tomorrow.length === 0) tomorrow = [];
    var html = '<table class="table-mini"><thead><tr><th>Время</th><th>Клиент</th><th>Услуга</th><th>Телефон</th></tr></thead><tbody>';
    if (tomorrow.length === 0) {
        html += '<td><td colspan="4" style="color:#999;text-align:center;">Нет записей<\/td><\/tr>';
    } else {
        tomorrow.forEach(function(b) {
            html += '<tr>' +
                '<td>' + (b.booking_time ? b.booking_time.substring(0,5) : '—') + '</td>' +
                '<td>' + esc(b.client_name) + '</td>' +
                '<td>' + esc(b.service) + '</td>' +
                '<td>' + esc(b.client_phone) + '</td>' +
            '</tr>';
        });
    }
    html += '<\/tbody><\/table>';
    return html;
}

function printSection(id) {
    var content = document.getElementById(id).outerHTML;
    var title = id === 'print-today' ? 'Записи на сегодня' : 'Подтвердить на завтра';
    
    var oldFrame = document.getElementById('print-frame');
    if (oldFrame) oldFrame.remove();
    
    var frame = document.createElement('iframe');
    frame.id = 'print-frame';
    frame.style.display = 'none';
    document.body.appendChild(frame);
    
    frame.contentDocument.write('<html><head><title>' + title + '</title>');
    frame.contentDocument.write('<style>body{font-family:Arial;padding:20px}table{width:100%;border-collapse:collapse}th,td{padding:8px;text-align:left;border-bottom:1px solid #ddd}th{color:#888}@media print{body{margin:0}}</style>');
    frame.contentDocument.write('</head><body>' + content + '</body></html>');
    frame.contentDocument.close();
    
    frame.contentWindow.focus();
    frame.contentWindow.print();
    
    setTimeout(function() { frame.remove(); }, 1000);
}

function fmtDateDisplay(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(dateStr);
    return d.toLocaleDateString('ru-RU', {day:'2-digit',month:'2-digit',year:'numeric'});
}

function esc(t) { if(!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>