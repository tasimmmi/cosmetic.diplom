<?php
$pageTitle = 'Главная';
include __DIR__ . '/partials/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Найдите своего <span>косметолога</span></h1>
            <p class="hero-subtitle">Профессиональные услуги косметологов — запись онлайн за пару кликов</p>
            <div class="hero-search">
                <input type="text" class="form-control" id="search-input" placeholder="Поиск по имени, услуге или адресу...">
                <button class="btn btn-primary" id="search-btn">
                    <i class="fas fa-search"></i> Найти
                </button>
            </div>
        </div>
        <div class="hero-image">
            <img src="/frontend/images/hero-cosmetologist.svg" alt="Косметолог">
        </div>
    </div>
</section>

<!-- Список косметологов -->
<section class="cosmetologists-section">
    <div class="container">
        <h2 class="section-title">Наши косметологи</h2>
        
        <div class="filters-bar">
            <div class="filter-group">
                <label>Сортировка:</label>
                <select class="form-control" id="filter-sort">
                    <option value="rating">По рейтингу</option>
                    <option value="name">По имени</option>
                    <option value="price_asc">Цена: по возрастанию</option>
                    <option value="price_desc">Цена: по убыванию</option>
                </select>
            </div>
        </div>
        
        <div class="cosmetologists-grid" id="cosmetologists-list">
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p>Загрузка косметологов...</p>
            </div>
        </div>
    </div>
</section>

<!-- Как это работает -->
<section class="how-it-works" id="how-it-works">
    <div class="container">
        <h2 class="section-title">Как это работает</h2>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <i class="fas fa-search"></i>
                <h3>Найдите косметолога</h3>
                <p>Выберите специалиста по рейтингу, услугам и расположению</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <i class="fas fa-calendar-check"></i>
                <h3>Выберите услугу и время</h3>
                <p>Ознакомьтесь с услугами и выберите удобное время</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <i class="fas fa-user-check"></i>
                <h3>Запишитесь онлайн</h3>
                <p>Войдите или зарегистрируйтесь для завершения записи</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <i class="fas fa-bell"></i>
                <h3>Получите подтверждение</h3>
                <p>Ожидайте подтверждения от косметолога и приходите</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA для косметологов -->
<section class="cta-cosmetologist">
    <div class="container">
        <div class="cta-content">
            <h2>Вы косметолог?</h2>
            <p>Присоединяйтесь к платформе и находите новых клиентов</p>
            <a href="register.php?role=cosmetologist" class="btn btn-primary btn-lg">
                Стать косметологом
            </a>
        </div>
    </div>
</section>

<script>
let cosmetologists = [];

// Загрузка косметологов
async function loadCosmetologists() {
    const container = document.getElementById('cosmetologists-list');
    
    try {
        const response = await fetch('/backend/public/api/cosmetologists');
        const data = await response.json();
        
        if (data.success) {
            cosmetologists = data.data || [];
            renderCosmetologists(cosmetologists);
        }
    } catch (error) {
        container.innerHTML = '<p class="text-center text-danger">Ошибка загрузки</p>';
    }
}

function renderCosmetologists(list) {
    const container = document.getElementById('cosmetologists-list');
    
    if (list.length === 0) {
        container.innerHTML = '<p class="text-center">Косметологи не найдены</p>';
        return;
    }
    
    container.innerHTML = list.map(c => `
        <div class="cosmetologist-card">
            <div class="cosmetologist-avatar">
                ${c.avatar ? `<img src="${c.avatar}" alt="${c.first_name}">` : 
                    `<div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div>`}
            </div>
            <div class="cosmetologist-info">
                <h3>${c.first_name} ${c.last_name}</h3>
                ${c.rating ? `
                    <div class="rating">
                        ${renderStars(c.rating)}
                        <span>${c.rating}</span>
                    </div>
                ` : ''}
                <p class="address"><i class="fas fa-map-marker-alt"></i> ${c.address || 'Адрес не указан'}</p>
                <p class="phone"><i class="fas fa-phone"></i> ${c.phone || 'Телефон не указан'}</p>
                <a href="card.php?id=${c.id}" class="btn btn-primary">Посмотреть услуги и записаться</a>
            </div>
        </div>
    `).join('');
}

function renderStars(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    let html = '';
    for (let i = 0; i < fullStars; i++) html += '<i class="fas fa-star"></i>';
    if (halfStar) html += '<i class="fas fa-star-half-alt"></i>';
    return html;
}

// Поиск
document.getElementById('search-btn').addEventListener('click', () => {
    const query = document.getElementById('search-input').value.toLowerCase();
    if (!query) {
        renderCosmetologists(cosmetologists);
        return;
    }
    const filtered = cosmetologists.filter(c => 
        c.first_name.toLowerCase().includes(query) ||
        c.last_name.toLowerCase().includes(query) ||
        (c.address && c.address.toLowerCase().includes(query))
    );
    renderCosmetologists(filtered);
});

// Сортировка
document.getElementById('filter-sort').addEventListener('change', (e) => {
    const sortBy = e.target.value;
    let sorted = [...cosmetologists];
    
    switch (sortBy) {
        case 'rating':
            sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            break;
        case 'name':
            sorted.sort((a, b) => a.first_name.localeCompare(b.first_name));
            break;
        case 'price_asc':
            sorted.sort((a, b) => (a.min_price || 0) - (b.min_price || 0));
            break;
        case 'price_desc':
            sorted.sort((a, b) => (b.min_price || 0) - (a.min_price || 0));
            break;
    }
    
    renderCosmetologists(sorted);
});

// Загрузка при старте
loadCosmetologists();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>