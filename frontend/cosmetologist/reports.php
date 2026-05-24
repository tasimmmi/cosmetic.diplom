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
    .report-section h3 { margin-top: 0; font-size: 16px; font-weight: 600; color: #333; }
    
    .table-mini { width: 100%; font-size: 13px; }
    .table-mini th { text-align: left; padding: 8px 10px; border-bottom: 2px solid #eee; color: #888; font-weight: 500; white-space: nowrap; }
    .table-mini td { padding: 8px 10px; border-bottom: 1px solid #f5f5f5; }
    
    .badge-stock { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; display: inline-block; white-space: nowrap; }
    .badge-stock.in { background: #d4edda; color: #155724; }
    .badge-stock.out { background: #f8d7da; color: #721c24; }
    .badge-mutable { background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 6px; display: inline-block; white-space: nowrap; }
    
    .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .btn { padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-outline { background: white; border: 1px solid #ddd; }
    .btn-sm { padding: 4px 10px; font-size: 11px; }
    .form-control { padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
    
    .fin-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 10px; }
    .fin-item { text-align: center; }
    .fin-value { font-size: 22px; font-weight: 700; white-space: nowrap; }
    .fin-value span { font-size: 12px; font-weight: 500; }
    .fin-label { font-size: 11px; color: #999; margin-top: 2px; }
    
    .info-dot { display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: #ddd; color: #888; text-align: center; line-height: 14px; font-size: 9px; cursor: default; flex-shrink: 0; }
    .info-dot:hover { background: #667eea; color: #fff; }
    
    @media (max-width: 700px) {
        .bookings-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 500px) {
        .table-mini { font-size: 12px; }
        .table-mini th, .table-mini td { padding: 6px 6px; }
        .badge-stock { padding: 2px 6px; font-size: 10px; }
        .badge-mutable { padding: 1px 5px; font-size: 9px; }
        .fin-value { font-size: 18px; }
        .fin-value span { font-size: 10px; }
        .stat-card { padding: 15px 10px; }
        .stat-card .stat-value { font-size: 20px; }
    }
    
    @media print {
        body * { visibility: hidden; }
        #print-main, #print-main * { visibility: visible; }
        #print-main { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print, #bookings-section { display: none !important; }
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
                <button class="btn btn-primary btn-sm" onclick="loadReports()">Показать</button>
            </div>
            
            <div id="print-main">
                <div id="stats-row" class="stats-row"></div>
                <div class="report-section" id="financial-section"></div>
                <div class="report-section" id="services-section"></div>
                <div class="report-section" id="materials-section"></div>
            </div>
            
            <div class="report-section no-print" id="bookings-section"></div>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
loadReports();

async function loadReports() {
    var start = document.getElementById('start-date').value;
    var end = document.getElementById('end-date').value;
    
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/reports/summary?start_date=' + start + '&end_date=' + end);
        var d = await r.json();
        
        if (d.success) {
            var data = d.data;
            renderStats(data.stats);
            renderFinancial(data.financial, start, end);
            renderServices(data.services);
            renderMaterials(data.materials, data.out_of_stock);
            renderBookings(data.today_bookings || [], data.tomorrow_pending || []);
        }
    } catch(e) { console.error(e); }
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

function renderFinancial(data, start, end) {
    var html = '<h3>Финансовый отчёт <span style="font-weight:400;font-size:12px;color:#999;">(' + fmtDateShort(start) + ' — ' + fmtDateShort(end) + ')</span></h3>';
    
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
        html += '<table class="table-mini"><thead><tr><th>Услуга</th><th>Выполнено</th><th>Отменено <span class="info-dot" title="Все незавершённые записи">i</span></th><th>Выручка</th><th>Доля</th></tr></thead><tbody>';
        data.forEach(function(s) {
            html += '<tr><td>' + esc(s.service) + '</td><td>' + s.completed_count + '</td><td>' + s.cancelled_count + '</td><td>' + s.total_revenue + ' BYN</td><td>' + (s.revenue_percentage||0) + '%</td></tr>';
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
        html += '<table class="table-mini"><thead><tr><th>Материал</th><th>Наличие</th><th>Тип</th><th>Посл. закупка</th><th>Средняя цена</th></tr></thead><tbody>';
        stats.forEach(function(m) {
            html += '<tr><td>' + esc(m.material) + '</td>' +
                '<td><span class="badge-stock ' + (m.is_stock == 1 ? 'in' : 'out') + '">' + esc(m.is_stock_text) + '</span></td>' +
                '<td>' + (m.is_mutable == 1 ? '<span class="badge-mutable">Общий</span>' : 'Личный') + '</td>' +
                '<td>' + fmtDateTime(m.last_procurement) + '</td>' +
                '<td>' + (m.average_price || '—') + '</td></tr>';
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
        html += '<tr><td colspan="4" style="color:#999;text-align:center;">Нет записей</td></tr>';
    } else {
        today.forEach(function(b) {
            html += '<tr><td>' + (b.booking_time ? b.booking_time.substring(0,5) : '—') + '</td><td>' + esc(b.client_name) + '</td><td>' + esc(b.service) + '</td><td>' + (sc[b.booking_status]||b.booking_status) + '</td></tr>';
        });
    }
    html += '</tbody></table>';
    return html;
}

function tomorrowTableHtml(tomorrow) {
    if (!tomorrow || tomorrow.length === 0) tomorrow = [];
    var html = '<table class="table-mini"><thead><tr><th>Время</th><th>Клиент</th><th>Услуга</th><th>Телефон</th></tr></thead><tbody>';
    if (tomorrow.length === 0) {
        html += '<tr><td colspan="4" style="color:#999;text-align:center;">Нет записей</td></tr>';
    } else {
        tomorrow.forEach(function(b) {
            html += '<tr><td>' + (b.booking_time ? b.booking_time.substring(0,5) : '—') + '</td><td>' + esc(b.client_name) + '</td><td>' + esc(b.service) + '</td><td>' + esc(b.client_phone) + '</td></tr>';
        });
    }
    html += '</tbody></table>';
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

function fmtDateTime(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(dateStr);
    return d.toLocaleDateString('ru-RU', {day:'2-digit',month:'2-digit',year:'2-digit'}) + ' ' + 
           d.toLocaleTimeString('ru-RU', {hour:'2-digit',minute:'2-digit'});
}

function fmtDateShort(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(dateStr);
    return d.toLocaleDateString('ru-RU', {day:'2-digit',month:'2-digit',year:'2-digit'});
}

function esc(t) { if(!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>