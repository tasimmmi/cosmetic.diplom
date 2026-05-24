<?php
$pageTitle = 'Мои записи';
$extraStyles = '
    .dashboard-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
    .tab-btn { padding: 6px 14px; border: 1px solid #ddd; border-radius: 20px; cursor: pointer; font-size: 12px; background: white; color: #666; transition: all 0.15s; }
    .tab-btn:hover { border-color: #667eea; color: #667eea; }
    .tab-btn.active { background: #667eea; color: white; border-color: #667eea; }
    
    .booking-row {
        display: flex; align-items: center; gap: 12px; padding: 10px 12px;
        border-bottom: 1px solid #f0f0f0; transition: background 0.15s; position: relative;
    }
    .booking-row:hover { background: #fafbff; }
    .booking-row:last-child { border-bottom: none; }
    .booking-row.needs-action { background: #fff8e1; }
    
    .status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .booking-date-col { min-width: 50px; text-align: center; font-size: 12px; color: #888; line-height: 1.4; flex-shrink: 0; }
    .booking-date-col .booking-time { font-weight: 700; color: #333; font-size: 13px; }
    .booking-cosm-col { min-width: 130px; line-height: 1.4; flex-shrink: 0; }
    .booking-cosm-name { font-weight: 600; color: #333; font-size: 13px; }
    .booking-cosm-addr { font-size: 11px; color: #aaa; }
    .booking-service-name { color: #555; flex: 1; min-width: 100px; font-size: 13px; }
    .booking-price { color: #333; font-weight: 500; white-space: nowrap; font-size: 13px; min-width: 75px; text-align: right; flex-shrink: 0; }
    .booking-actions-cell { width: 60px; flex-shrink: 0; display: flex; justify-content: flex-end; }
    .booking-actions-inline { display: flex; gap: 4px; opacity: 0; transition: opacity 0.15s; }
    .booking-row:hover .booking-actions-inline { opacity: 1; }
    .booking-row.needs-action .booking-actions-inline { opacity: 1; }
    
    .btn-icon { width: 24px; height: 24px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 10px; transition: all 0.15s; color: white; flex-shrink: 0; position: relative; }
    .btn-icon:hover { transform: scale(1.2); }
    .btn-icon::after { content: attr(title); position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px; white-space: nowrap; pointer-events: none; opacity: 0; transition: opacity 0.15s; font-family: Arial, sans-serif; font-weight: normal; }
    .btn-icon:hover::after { opacity: 1; }
    .btn-icon-confirm { background: #28a745; }
    .btn-icon-cancel { background: #dc3545; }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-client.php'; ?>
        
        <div class="dashboard-content">
            <h1>Предстоящие записи</h1>
            
            <div id="bookings-list">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Загрузка записей...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
async function loadBookings() {
    var container = document.getElementById('bookings-list');
    
    try {
        var response = await AUTH.fetch('/backend/public/api/users/bookings');
        var data = await response.json();
        
        if (data.success) {
            var bookings = (data.data.bookings || []).filter(function(b) {
                return b.status === 'pending' || b.status === 'confirmed';
            });
            renderBookings(bookings);
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function renderBookings(bookings) {
    var container = document.getElementById('bookings-list');
    
    if (bookings.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Нет предстоящих записей</p>';
        return;
    }
    
    var statusColors = { pending: '#f0ad4e', confirmed: '#5bc0de' };
    
    var html = '';
    bookings.forEach(function(b) {
        var dt = new Date(b.schedule);
        var dateStr = dt.toLocaleDateString('ru-RU', {day:'numeric',month:'short'});
        var timeStr = dt.toLocaleTimeString('ru-RU', {hour:'2-digit',minute:'2-digit'});
        var addr = b.address || '';
        var needsAction = b.status === 'pending';
        
        html += '<div class="booking-row' + (needsAction ? ' needs-action' : '') + '">' +
            '<span class="status-dot" style="background:' + (statusColors[b.status]||'#999') + '" title="' + b.status + '"></span>' +
            '<span class="booking-date-col">' + dateStr + '<br><span class="booking-time">' + timeStr + '</span></span>' +
            '<span class="booking-cosm-col">' +
                '<span class="booking-cosm-name">' + esc(b.cosmetologist_name) + '</span>' +
                (addr ? '<br><span class="booking-cosm-addr">' + esc(addr) + '</span>' : '') +
            '</span>' +
            '<span class="booking-service-name">' + esc(b.service_name) + '</span>' +
            '<span class="booking-price">' + (b.price||0) + ' BYN</span>' +
            '<span class="booking-actions-cell"><span class="booking-actions-inline">';
        
        if (b.status === 'pending') {
            html += '<button class="btn-icon btn-icon-confirm" onclick="confirmBooking(' + b.id + ')" title="Подтвердить"><i class="fas fa-check"></i></button>';
        }
        html += '<button class="btn-icon btn-icon-cancel" onclick="cancelBooking(' + b.id + ')" title="Отменить"><i class="fas fa-times"></i></button>';
        
        html += '</span></span></div>';
    });
    container.innerHTML = html;
}

async function confirmBooking(id) {
    if (!confirm('Подтвердить запись?')) return;
    try {
        await AUTH.fetch('/backend/public/api/bookings/' + id + '/confirm', { method:'PUT' });
        loadBookings();
    } catch (e) { alert('Ошибка'); }
}

async function cancelBooking(id) {
    if (!confirm('Отменить запись?')) return;
    try {
        await AUTH.fetch('/backend/public/api/bookings/' + id + '/cancel', { method:'PUT' });
        loadBookings();
    } catch (e) { alert('Ошибка'); }
}

function esc(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadBookings();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>