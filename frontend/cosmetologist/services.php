<?php
$pageTitle = 'Услуги';
$extraStyles = '
    .service-card { 
        background: white; 
        border-radius: 12px; 
        padding: 20px; 
        margin-bottom: 15px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        flex-wrap: wrap;
        gap: 15px;
    }
    .service-info { 
        flex: 1; 
        min-width: 200px;
    }
    .service-info h4 { 
        margin-bottom: 5px; 
    }
    .service-price { 
        font-size: 20px; 
        font-weight: 700; 
        color: #667eea; 
        white-space: nowrap;
        min-width: 100px;
        text-align: left;
    }
    .service-actions { 
        display: flex; 
        gap: 10px; 
        white-space: nowrap;
    }
    
    @media (max-width: 600px) {
        .service-card {
            flex-direction: column;
            align-items: flex-start;
        }
        .service-price {
            text-align: left;
        }
    }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Мои услуги</h1>
                <button class="btn btn-primary" onclick="showServiceForm()">
                    <i class="fas fa-plus"></i> Добавить услугу
                </button>
            </div>
            
            <div id="services-list">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Загрузка...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно -->
<div class="modal" id="service-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Добавить услугу</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="service-form">
                <input type="hidden" id="service-id">
                <div class="form-group">
                    <label>Название услуги</label>
                    <input type="text" class="form-control" id="service-name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Длительность (мин)</label>
                        <input type="number" class="form-control" id="service-duration" min="5" step="5" value="30" required>
                    </div>
                    <div class="form-group">
                        <label>Перерыв (мин)</label>
                        <input type="number" class="form-control" id="service-break" min="0" step="5" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Цена (BYN)</label>
                    <input type="number" class="form-control" id="service-price" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea class="form-control" id="service-description" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal()">Отмена</button>
            <button class="btn btn-primary" onclick="saveService()">Сохранить</button>
        </div>
    </div>
</div>

<script>
const token = localStorage.getItem('access_token');
let services = [];

async function loadServices() {
    const container = document.getElementById('services-list');
    
    try {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        const response = await fetch(`/backend/public/api/cosmetologists/${user.cosmetologist_id}/services`, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await response.json();
        
        if (data.success) {
            services = data.data.services || [];
            
            if (services.length === 0) {
                container.innerHTML = '<p class="text-muted text-center py-5">Услуги не добавлены</p>';
                return;
            }
            
            container.innerHTML = services.map(s => `
                <div class="service-card">
                    <div class="service-info">
                        <h4>${escapeHtml(s.service)}</h4>
                        <p class="text-muted">
                            <i class="far fa-clock"></i> ${s.duration}
                            ${s.break_time && s.break_time !== '00:00:00' ? ` (перерыв ${s.break_time})` : ''}
                        </p>
                        ${s.description ? `<p>${escapeHtml(s.description)}</p>` : ''}
                    </div>
                    <div class="service-price">${s.price} BYN</div>
                    <div class="service-actions">
                        <button class="btn-icon" onclick="editService(${s.id})" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon text-danger" onclick="deleteService(${s.id})" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function showServiceForm(service = null) {
    const modal = document.getElementById('service-modal');
    const title = document.getElementById('modal-title');
    
    if (service) {
        title.textContent = 'Редактировать услугу';
        document.getElementById('service-id').value = service.id;
        document.getElementById('service-name').value = service.service;
        document.getElementById('service-duration').value = parseDuration(service.duration);
        document.getElementById('service-break').value = parseDuration(service.break_time);
        document.getElementById('service-price').value = service.price;
        document.getElementById('service-description').value = service.description || '';
    } else {
        title.textContent = 'Добавить услугу';
        document.getElementById('service-id').value = '';
        document.getElementById('service-form').reset();
        document.getElementById('service-duration').value = '30';
        document.getElementById('service-break').value = '0';
    }
    
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('service-modal').style.display = 'none';
}

function parseDuration(time) {
    if (!time) return 0;
    const parts = time.split(':');
    return parseInt(parts[0]) * 60 + parseInt(parts[1]);
}

async function saveService() {
    const id = document.getElementById('service-id').value;
    const data = {
        service: document.getElementById('service-name').value,
        duration: minutesToTime(document.getElementById('service-duration').value),
        break_time: document.getElementById('service-break').value > 0 
            ? minutesToTime(document.getElementById('service-break').value) 
            : null,
        price: parseFloat(document.getElementById('service-price').value),
        description: document.getElementById('service-description').value
    };
    
    if (!data.service) {
        alert('Введите название услуги');
        return;
    }
    
    if (!data.price || data.price <= 0) {
        alert('Введите корректную цену');
        return;
    }
    
    try {
        const url = id ? `/backend/public/api/services/${id}` : '/backend/public/api/services';
        const method = id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            closeModal();
            loadServices();
        } else {
            const error = await response.json();
            alert(error.error || 'Ошибка сохранения');
        }
    } catch (error) {
        alert('Ошибка соединения');
    }
}

function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:00`;
}

function editService(id) {
    const service = services.find(s => s.id == id);
    if (service) showServiceForm(service);
}

async function deleteService(id) {
    if (!confirm('Удалить услугу?')) return;
    
    try {
        const response = await fetch(`/backend/public/api/services/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (response.ok) {
            loadServices();
        } else {
            alert('Ошибка удаления');
        }
    } catch (error) {
        alert('Ошибка соединения');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadServices();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>