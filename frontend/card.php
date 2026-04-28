<?php
$pageTitle = 'Карточка косметолога';
$cosmetologistId = $_GET['id'] ?? null;

if (!$cosmetologistId) {
    header('Location: /frontend/');
    exit;
}

$extraStyles = '
    .cosmetologist-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
    .cosmetologist-header h1 { color: white; }
    .service-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s; }
    .service-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .service-card.selected { border-color: #667eea; background: rgba(102,126,234,0.05); }
    .service-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
    .service-name { font-size: 18px; font-weight: 600; }
    .service-price { font-size: 22px; font-weight: 700; color: #667eea; }
    .booking-form { background: #f7fafc; border-radius: 12px; padding: 30px; margin-top: 30px; }
    .time-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px; }
    .time-slot { padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; cursor: pointer; }
    .time-slot.selected { background: #667eea; color: white; border-color: #667eea; }
';

include __DIR__ . '/partials/header.php';
?>

<div id="cosmetologist-content">
    <div class="loading-container">
        <div class="loading-spinner"></div>
        <p>Загрузка...</p>
    </div>
</div>

<script>
const cosmetologistId = <?= (int)$cosmetologistId ?>;
let cosmetologist = null;
let services = [];
let selectedService = null;
let selectedDate = null;
let selectedTime = null;

// Загрузка данных
async function loadCosmetologist() {
    const container = document.getElementById('cosmetologist-content');
    
    try {
        const response = await fetch(`/backend/public/api/cosmetologists/${cosmetologistId}`);
        const data = await response.json();
        
        if (data.success) {
            cosmetologist = data.data.cosmetologist;
            services = data.data.services || [];
            render();
        } else {
            container.innerHTML = '<div class="container"><div class="alert alert-danger">Косметолог не найден</div></div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="container"><div class="alert alert-danger">Ошибка загрузки</div></div>';
    }
}

function render() {
    const container = document.getElementById('cosmetologist-content');
    const isAuth = !!localStorage.getItem('access_token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    container.innerHTML = `
        <div class="cosmetologist-header">
            <div class="container">
                <h1>${cosmetologist.first_name} ${cosmetologist.last_name}</h1>
                ${cosmetologist.rating ? `<div class="rating">★ ${cosmetologist.rating}</div>` : ''}
                <p><i class="fas fa-map-marker-alt"></i> ${cosmetologist.address || 'Адрес не указан'}</p>
                <p><i class="fas fa-phone"></i> ${cosmetologist.phone || 'Телефон не указан'}</p>
            </div>
        </div>
        
        <div class="container">
            ${cosmetologist.education ? `
                <div class="education-section">
                    <h3>Образование и сертификаты</h3>
                    <p>${escapeHtml(cosmetologist.education)}</p>
                </div>
            ` : ''}
            
            ${cosmetologist.about ? `
                <div class="about-section">
                    <h3>О себе</h3>
                    <p>${escapeHtml(cosmetologist.about)}</p>
                </div>
            ` : ''}
            
            <h3>Услуги и цены</h3>
            <div class="services-list">
                ${services.map(s => `
                    <div class="service-card" data-service-id="${s.id}">
                        <div class="service-header">
                            <span class="service-name">${escapeHtml(s.service)}</span>
                            <span class="service-price">${s.price} BYN</span>
                        </div>
                        <div class="service-duration">
                            <i class="far fa-clock"></i> ${s.duration}
                            ${s.break_time ? ` (перерыв ${s.break_time})` : ''}
                        </div>
                        ${s.description ? `<div class="service-description">${escapeHtml(s.description)}</div>` : ''}
                    </div>
                `).join('')}
            </div>
            
            <div class="booking-form" id="booking-form">
                <h3><i class="fas fa-calendar-check"></i> Записаться на услугу</h3>
                
                ${!isAuth ? `
                    <div class="booking-message">
                        <p>Для записи необходимо авторизоваться</p>
                        <a href="login.php?redirect=card.php?id=${cosmetologistId}&booking=true" class="btn btn-primary">Войти</a>
                        <a href="register.php?redirect=card.php?id=${cosmetologistId}&booking=true" class="btn btn-outline">Регистрация</a>
                    </div>
                ` : (user.role !== 'client' ? `
                    <div class="alert alert-info">Только клиенты могут записываться на услуги</div>
                ` : `
                    <form id="booking-form-element">
                        <input type="hidden" name="cosmetologist_id" value="${cosmetologistId}">
                        <input type="hidden" name="service_id" id="selected-service-id">
                        
                        <div class="form-group">
                            <label>Выбранная услуга:</label>
                            <div id="selected-service-display">Не выбрана</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-date">Дата</label>
                            <input type="date" class="form-control" id="booking-date" min="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Время</label>
                            <div class="time-slots" id="time-slots">
                                <p class="text-muted">Выберите дату</p>
                            </div>
                            <input type="hidden" name="time" id="selected-time">
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Комментарий</label>
                            <textarea class="form-control" id="comment" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg btn-block" id="submit-booking" disabled>
                            Записаться
                        </button>
                    </form>
                `)}
            </div>
        </div>
    `;
    
    // Обработчики
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', () => selectService(card.dataset.serviceId));
    });
    
    const dateInput = document.getElementById('booking-date');
    if (dateInput) {
        dateInput.addEventListener('change', (e) => {
            selectedDate = e.target.value;
            loadTimeSlots();
        });
    }
    
    const form = document.getElementById('booking-form-element');
    if (form) {
        form.addEventListener('submit', handleBooking);
    }
}

function selectService(serviceId) {
    selectedService = services.find(s => s.id == serviceId);
    
    document.querySelectorAll('.service-card').forEach(c => {
        c.classList.toggle('selected', c.dataset.serviceId == serviceId);
    });
    
    document.getElementById('selected-service-id').value = serviceId;
    document.getElementById('selected-service-display').textContent = 
        `${selectedService.service} - ${selectedService.price} BYN`;
    
    document.getElementById('submit-booking').disabled = false;
    
    if (selectedDate) loadTimeSlots();
}

async function loadTimeSlots() {
    if (!selectedService || !selectedDate) return;
    
    const container = document.getElementById('time-slots');
    container.innerHTML = '<div class="loading-spinner"></div>';
    
    try {
        const response = await fetch(
            `/backend/public/api/cosmetologists/${cosmetologistId}/slots?service_id=${selectedService.id}&date=${selectedDate}`
        );
        const data = await response.json();
        
        const slots = data.data?.slots || [];
        
        if (slots.length === 0) {
            container.innerHTML = '<p class="text-muted">Нет доступного времени</p>';
            return;
        }
        
        container.innerHTML = slots.map(slot => {
            const time = slot.begin_time.split(' ')[1]?.substring(0, 5) || '';
            return `<div class="time-slot" data-time="${time}">${time}</div>`;
        }).join('');
        
        container.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', () => {
                container.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                slot.classList.add('selected');
                selectedTime = slot.dataset.time;
                document.getElementById('selected-time').value = selectedTime;
            });
        });
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки времени</p>';
    }
}

async function handleBooking(e) {
    e.preventDefault();
    
    if (!selectedService || !selectedDate || !selectedTime) {
        alert('Выберите услугу, дату и время');
        return;
    }
    
    const token = localStorage.getItem('access_token');
    const comment = document.getElementById('comment').value;
    
    try {
        const response = await fetch('/backend/public/api/bookings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                cosmetologist_id: cosmetologistId,
                service_id: selectedService.id,
                schedule: `${selectedDate} ${selectedTime}:00`,
                comment: comment
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            alert('Запись успешно создана!');
            window.location.href = '/frontend/client/dashboard.php';
        } else {
            alert(data.error || 'Ошибка создания записи');
        }
    } catch (error) {
        alert('Ошибка соединения с сервером');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadCosmetologist();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>