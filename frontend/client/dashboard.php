<?php
$pageTitle = 'Мои записи';
$extraStyles = '
    .booking-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid; }
    .booking-card.status-pending { border-left-color: #ffc107; }
    .booking-card.status-confirmed { border-left-color: #28a745; }
    .booking-card.status-completed { border-left-color: #17a2b8; }
    .booking-card.status-cancelled { border-left-color: #dc3545; }
    .booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .booking-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .status-pending .booking-status { background: #fff3cd; color: #856404; }
    .status-confirmed .booking-status { background: #d4edda; color: #155724; }
    .status-completed .booking-status { background: #d1ecf1; color: #0c5460; }
    .status-cancelled .booking-status { background: #f8d7da; color: #721c24; }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-client.php'; ?>
        
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Мои записи</h1>
                
                <div class="dashboard-tabs">
                    <button class="tab-btn active" data-status="all">Все</button>
                    <button class="tab-btn" data-status="pending">Ожидают</button>
                    <button class="tab-btn" data-status="confirmed">Подтверждены</button>
                    <button class="tab-btn" data-status="completed">Завершены</button>
                    <button class="tab-btn" data-status="cancelled">Отменены</button>
                </div>
            </div>
            
            <div id="bookings-list">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Загрузка записей...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const token = localStorage.getItem('access_token');
let bookings = [];
let currentStatus = 'all';

async function loadBookings() {
    const container = document.getElementById('bookings-list');
    
    try {
        const response = await fetch('/backend/public/api/users/bookings', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await response.json();
        
        if (data.success) {
            bookings = data.data.bookings || [];
            renderBookings();
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function renderBookings() {
    const container = document.getElementById('bookings-list');
    const filtered = currentStatus === 'all' 
        ? bookings 
        : bookings.filter(b => b.status === currentStatus);
    
    if (filtered.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-5">Нет записей</p>';
        return;
    }
    
    const statusNames = {
        pending: 'Ожидает подтверждения',
        confirmed: 'Подтверждена',
        completed: 'Завершена',
        cancelled: 'Отменена'
    };
    
    container.innerHTML = filtered.map(b => `
        <div class="booking-card status-${b.status}">
            <div class="booking-header">
                <h3>${escapeHtml(b.cosmetologist_name)}</h3>
                <span class="booking-status">${statusNames[b.status] || b.status}</span>
            </div>
            <div class="booking-body">
                <p><i class="fas fa-cut"></i> ${escapeHtml(b.service_name)}</p>
                <p><i class="fas fa-calendar"></i> ${formatDateTime(b.schedule)}</p>
                <p><i class="fas fa-clock"></i> ${b.duration || '—'}</p>
                <p><i class="fas fa-tag"></i> <strong>${b.price} BYN</strong></p>
                ${b.address ? `<p><i class="fas fa-map-marker-alt"></i> ${escapeHtml(b.address)}</p>` : ''}
            </div>
            ${(b.status === 'pending' || b.status === 'confirmed') ? `
                <div class="booking-footer">
                    <button class="btn btn-outline-danger btn-sm" onclick="cancelBooking(${b.id})">
                        <i class="fas fa-times"></i> Отменить
                    </button>
                </div>
            ` : ''}
        </div>
    `).join('');
}

function formatDateTime(datetime) {
    const d = new Date(datetime);
    return d.toLocaleString('ru-RU', { 
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

async function cancelBooking(id) {
    if (!confirm('Отменить запись?')) return;
    
    try {
        await fetch(`/backend/public/api/bookings/${id}/cancel`, {
            method: 'PUT',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        loadBookings();
    } catch (error) {
        alert('Ошибка отмены');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Вкладки
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentStatus = btn.dataset.status;
        renderBookings();
    });
});

loadBookings();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>