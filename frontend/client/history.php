<?php
$pageTitle = 'История записей';
include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-client.php'; ?>
        
        <div class="dashboard-content">
            <h1>История записей</h1>
            
            <div id="history-list">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Загрузка...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const token = localStorage.getItem('access_token');

async function loadHistory() {
    const container = document.getElementById('history-list');
    
    try {
        const response = await fetch('/backend/public/api/users/bookings', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await response.json();
        
        if (data.success) {
            const bookings = data.data.bookings || [];
            const completed = bookings.filter(b => b.status === 'completed');
            
            if (completed.length === 0) {
                container.innerHTML = '<p class="text-muted text-center py-5">Нет завершенных записей</p>';
                return;
            }
            
            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Косметолог</th>
                                <th>Услуга</th>
                                <th>Стоимость</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${completed.map(b => `
                                <tr>
                                    <td>${new Date(b.schedule).toLocaleDateString('ru-RU')}</td>
                                    <td>${escapeHtml(b.cosmetologist_name)}</td>
                                    <td>${escapeHtml(b.service_name)}</td>
                                    <td>${b.price} BYN</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <p class="mt-3"><strong>Всего потрачено:</strong> ${completed.reduce((sum, b) => sum + parseFloat(b.price || 0), 0).toFixed(2)} BYN</p>
            `;
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadHistory();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>