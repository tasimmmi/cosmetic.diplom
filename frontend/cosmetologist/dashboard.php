<?php
$pageTitle = 'Панель управления';
$extraStyles = '
    .stat-card { background: white; border-radius: 12px; padding: 25px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .stat-value { font-size: 36px; font-weight: 700; color: #667eea; }
    .stat-label { color: #666; margin-top: 5px; }
    .today-bookings { margin-top: 30px; }
    .booking-item { display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; }
    .booking-time { width: 100px; font-weight: 600; }
    .booking-info { flex: 1; }
    .booking-client { font-weight: 500; }
    .booking-service { color: #666; font-size: 14px; }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <h1>Добро пожаловать, <span id="cosmetologist-name"></span>!</h1>
            
            <div class="row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0;">
                <div class="stat-card">
                    <div class="stat-value" id="stat-today">0</div>
                    <div class="stat-label">Записей сегодня</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="stat-pending">0</div>
                    <div class="stat-label">Ожидают подтверждения</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="stat-completed">0</div>
                    <div class="stat-label">Выполнено сегодня</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="stat-revenue">0</div>
                    <div class="stat-label">Выручка сегодня</div>
                </div>
            </div>
            
            <div class="today-bookings">
                <h3>Записи на сегодня</h3>
                <div id="today-bookings-list">
                    <div class="loading-container">
                        <div class="loading-spinner"></div>
                        <p>Загрузка...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем систему авторизации -->
<script src="/frontend/js/auth-check.js"></script>

<script>
// Используем AUTH из auth-check.js
var user = JSON.parse(localStorage.getItem('user') || '{}');

// Имя косметолога
document.getElementById('cosmetologist-name').textContent = 
    user.fullname || user.first_name || 'Косметолог';

// Загрузка дашборда
async function loadDashboard() {
    try {
        // Загрузка статистики через AUTH.fetch (автообновление токена)
        var statsResponse = await AUTH.fetch('/backend/public/api/cosmetologist/statistics');
        
        if (statsResponse.ok) {
            var statsData = await statsResponse.json();
            
            if (statsData.success && statsData.data) {
                var stats = statsData.data;
                document.getElementById('stat-today').textContent = stats.today_bookings || 0;
                document.getElementById('stat-pending').textContent = stats.pending || 0;
                document.getElementById('stat-completed').textContent = stats.completed_today || 0;
                document.getElementById('stat-revenue').textContent = (stats.today_revenue || 0) + ' BYN';
            }
        }
        
        // Загрузка записей на сегодня
        var today = new Date().toISOString().split('T')[0];
        var bookingsResponse = await AUTH.fetch('/backend/public/api/cosmetologist/bookings?date=' + today);
        
        var container = document.getElementById('today-bookings-list');
        
        if (bookingsResponse.ok) {
            var bookingsData = await bookingsResponse.json();
            
            if (bookingsData.success && bookingsData.data) {
                var bookings = bookingsData.data.bookings || [];
                
                if (bookings.length === 0) {
                    container.innerHTML = '<p class="text-muted">Нет записей на сегодня</p>';
                    return;
                }
                
                var html = '';
                bookings.forEach(function(b) {
                    var time = new Date(b.schedule).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
                    var clientName = escapeHtml(b.client_name || '—');
                    var serviceName = escapeHtml(b.service_name || '—');
                    var price = b.price || 0;
                    
                    html += '<div class="booking-item">' +
                        '<div class="booking-time">' + time + '</div>' +
                        '<div class="booking-info">' +
                            '<div class="booking-client">' + clientName + '</div>' +
                            '<div class="booking-service">' + serviceName + ' - ' + price + ' BYN</div>' +
                        '</div>' +
                    '</div>';
                });
                
                container.innerHTML = html;
            }
        }
        
    } catch (error) {
        console.error('Dashboard error:', error);
    }
}

function escapeHtml(text) {
    if (!text) return '—';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Запускаем загрузку
loadDashboard();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>