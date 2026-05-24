<?php
$pageTitle = 'История записей';
$extraStyles = '
    .dashboard-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
    .tab-btn { padding: 6px 14px; border: 1px solid #ddd; border-radius: 20px; cursor: pointer; font-size: 12px; background: white; color: #666; transition: all 0.15s; }
    .tab-btn:hover { border-color: #667eea; color: #667eea; }
    .tab-btn.active { background: #667eea; color: white; border-color: #667eea; }
    
    .history-item { padding: 12px 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; background: white; }
    .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .history-date-block { display: flex; align-items: center; gap: 8px; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .history-date { font-weight: 500; font-size: 14px; color: #333; }
    .history-service { font-size: 14px; color: #333; margin-bottom: 5px; padding-left: 16px; }
    .history-meta { font-size: 12px; color: #888; padding-left: 16px; margin-bottom: 4px; }
    .history-comment { font-size: 13px; color: #666; padding: 8px 8px 8px 16px; border-left: 3px solid #667eea; border-radius: 0 4px 4px 0; background: #f8f9fa; margin-top: 6px; }
    
    .summary-box { background: #f0f4ff; border-radius: 10px; padding: 15px 20px; margin-bottom: 20px; display: flex; justify-content: space-around; text-align: center; }
    .summary-box strong { display: block; font-size: 20px; color: #667eea; }
    .summary-box span { font-size: 12px; color: #888; }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-client.php'; ?>
        
        <div class="dashboard-content">
            <h1>История записей</h1>
            
            <div class="dashboard-tabs">
                <button class="tab-btn active" data-status="all">Все</button>
                <button class="tab-btn" data-status="pending">Ожидают</button>
                <button class="tab-btn" data-status="confirmed">Подтверждены</button>
                <button class="tab-btn" data-status="completed">Завершены</button>
                <button class="tab-btn" data-status="cancelled">Отменены</button>
            </div>
            
            <div class="summary-box" id="summary" style="display:none;">
                <div><strong id="total-visits">0</strong><span>Всего визитов</span></div>
                <div><strong id="total-spent">0</strong><span>BYN потрачено</span></div>
            </div>
            
            <div id="history-list">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Загрузка...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var bookings = [];
var currentStatus = 'all';

async function loadHistory() {
    var container = document.getElementById('history-list');
    
    try {
        var response = await AUTH.fetch('/backend/public/api/users/bookings');
        var data = await response.json();
        
        if (data.success) {
            bookings = data.data.bookings || [];
            renderHistory();
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function renderHistory() {
    var container = document.getElementById('history-list');
    var filtered = currentStatus === 'all' 
        ? bookings 
        : bookings.filter(function(b) { return b.status === currentStatus; });
    
    if (filtered.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Нет записей</p>';
        document.getElementById('summary').style.display = 'none';
        return;
    }
    
    // Статистика
    var completed = bookings.filter(function(b) { return b.status === 'completed'; });
    document.getElementById('summary').style.display = 'flex';
    document.getElementById('total-visits').textContent = completed.length;
    document.getElementById('total-spent').textContent = completed.reduce(function(s, b) { return s + parseFloat(b.price||0); }, 0).toFixed(2);
    
    var statusColors = { pending: '#f0ad4e', confirmed: '#5bc0de', completed: '#5cb85c', cancelled: '#d9534f' };
    
    var html = '';
    filtered.forEach(function(b) {
        var dt = new Date(b.schedule);
        var dateStr = dt.toLocaleDateString('ru-RU', {day:'numeric',month:'short'});
        var timeStr = dt.toLocaleTimeString('ru-RU', {hour:'2-digit',minute:'2-digit'});
        
        html += '<div class="history-item">' +
            '<div class="history-header">' +
                '<div class="history-date-block">' +
                    '<span class="status-dot" style="background:' + (statusColors[b.status]||'#999') + '"></span>' +
                    '<span class="history-date">' + dateStr + ' в ' + timeStr + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="history-service">' + esc(b.service_name) + ' — ' + (b.price||0) + ' BYN</div>' +
            '<div class="history-meta">' + esc(b.cosmetologist_name) + (b.address ? ' • ' + esc(b.address) : '') + '</div>';
        
        if (b.description) {
            html += '<div class="history-comment">💬 ' + esc(b.description) + '</div>';
        }
        
        html += '</div>';
    });
    container.innerHTML = html;
}

function esc(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        currentStatus = btn.dataset.status;
        renderHistory();
    });
});

loadHistory();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>