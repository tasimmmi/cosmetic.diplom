<?php
$pageTitle = 'Клиенты';
$extraStyles = '
    .client-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.2s; }
    .client-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    .client-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .client-name { font-size: 18px; font-weight: 600; }
    .client-stats { display: flex; gap: 20px; color: #666; font-size: 14px; flex-wrap: wrap; }
    .client-stats span { display: flex; align-items: center; gap: 5px; }
    .badge-active { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
    .badge-inactive { background: #e2e3e5; color: #383d41; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
    
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: white; border-radius: 12px; width: 95%; max-width: 1000px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; }
    .modal-box-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid #eee; }
    .modal-box-header h3 { margin: 0; }
    .modal-box-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
    .modal-box-body { display: flex; flex: 1; overflow: hidden; }
    .modal-left { flex: 1; padding: 20px; overflow-y: auto; }
    .modal-right { width: 350px; padding: 20px; background: #f8f9fa; overflow-y: auto; }
    
    .history-item { padding: 12px 0; border-bottom: 1px solid #eee; }
    .history-item:last-child { border-bottom: none; }
    
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; margin-bottom: 4px; font-size: 13px; color: #555; }
    .form-control { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
    
    .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
    .btn-primary { background: #667eea; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-outline { background: none; border: 1px solid #ddd; }
    .btn-sm { padding: 4px 8px; font-size: 11px; }
    
    .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .search-input { flex: 1; min-width: 200px; }
    
    @media (max-width: 768px) {
        .modal-box-body { flex-direction: column; }
        .modal-right { width: 100%; }
    }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Клиенты</h1>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-user-plus"></i> Добавить клиента
                </button>
            </div>
            
            <div class="filter-bar">
                <input type="text" class="form-control search-input" id="search-clients" placeholder="Поиск..." oninput="filterClients()">
                <select class="form-control" style="width: auto;" id="sort-clients" onchange="filterClients()">
                    <option value="recent">По дате</option>
                    <option value="name">По имени</option>
                    <option value="frequent">По визитам</option>
                    <option value="revenue">По сумме</option>
                </select>
            </div>
            
            <div id="clients-list">
                <div class="loading-container"><div class="loading-spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО ИСТОРИИ + ЗАПИСИ -->
<div class="modal-overlay" id="detail-modal">
    <div class="modal-box">
        <div class="modal-box-header">
            <h3 id="modal-client-name">Клиент</h3>
            <button class="modal-box-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-box-body">
            <div class="modal-left" id="modal-history">Загрузка...</div>
            <div class="modal-right">
                <h4>Записать на прием</h4>
                <div class="form-group"><label>Услуга</label><select class="form-control" id="modal-service"><option value="">Выберите</option></select></div>
                <div class="form-group"><label>Дата</label><input type="date" class="form-control" id="modal-date"></div>
                <div class="form-group"><label>Время</label><select class="form-control" id="modal-time"><option value="">Выберите</option></select></div>
                <div class="form-group"><label>Комментарий</label><textarea class="form-control" id="modal-comment" rows="2"></textarea></div>
                <button class="btn btn-primary" onclick="createBookingFromModal()" style="width:100%;">Записать</button>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО ДОБАВЛЕНИЯ/РЕДАКТИРОВАНИЯ -->
<div class="modal-overlay" id="edit-modal">
    <div class="modal-box" style="max-width: 450px;">
        <div class="modal-box-header">
            <h3 id="edit-modal-title">Добавить клиента</h3>
            <button class="modal-box-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div style="padding: 20px;">
            <input type="hidden" id="edit-id">
            <div class="form-group"><label>Имя *</label><input type="text" class="form-control" id="edit-name"></div>
            <div class="form-group"><label>Телефон *</label><input type="tel" class="form-control" id="edit-phone"></div>
            <div class="form-group"><label>Связь</label><select class="form-control" id="edit-comm">
                <option value="phone">Телефон</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="telegram">Telegram</option>
            </select></div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-outline" onclick="closeEditModal()">Отмена</button>
                <button class="btn btn-primary" onclick="saveClient()">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script src="/frontend/js/auth-check.js"></script>

<script>
var clients = [];
var currentDetailId = null;

// ========== ЗАГРУЗКА СПИСКА ==========
async function loadClients() {
    var c = document.getElementById('clients-list');
    c.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>';
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/clients');
        var d = await r.json();
        if (d.success) { clients = d.data.clients || []; renderClients(clients); }
        else { c.innerHTML = '<p class="text-danger">Ошибка</p>'; }
    } catch(e) { c.innerHTML = '<p class="text-danger">Ошибка</p>'; }
}

function renderClients(list) {
    var c = document.getElementById('clients-list');
    if (list.length === 0) { c.innerHTML = '<p class="text-muted text-center py-5">Клиенты не найдены</p>'; return; }
    var h = '';
    list.forEach(function(cl) {
        h += '<div class="client-card" onclick="openDetail(' + cl.client_id + ')">' +
            '<div class="client-header"><span class="client-name">' + esc(cl.fullname) + '</span>' +
                '<span class="' + (cl.is_active ? 'badge-active' : 'badge-inactive') + '">' + (cl.is_active ? 'Активный' : 'Неактивный') + '</span></div>' +
            '<div class="client-stats"><span><i class="fas fa-phone"></i> ' + esc(cl.phone) + '</span>' +
                '<span><i class="fas fa-calendar-check"></i> Визитов: ' + (cl.visit_count||0) + '</span>' +
                '<span><i class="fas fa-money-bill"></i> ' + (cl.total_spent||0) + ' BYN</span>' +
                (cl.last_visit ? '<span><i class="fas fa-clock"></i> ' + fmtDate(cl.last_visit) + '</span>' : '') + '</div></div>';
    });
    c.innerHTML = h;
}

function filterClients() {
    var q = document.getElementById('search-clients').value.toLowerCase();
    var sort = document.getElementById('sort-clients').value;
    var list = clients.filter(function(x) { return x.fullname.toLowerCase().includes(q) || x.phone.includes(q); });
    if (sort === 'name') list.sort(function(a,b){ return a.fullname.localeCompare(b.fullname); });
    if (sort === 'frequent') list.sort(function(a,b){ return (b.visit_count||0)-(a.visit_count||0); });
    if (sort === 'revenue') list.sort(function(a,b){ return (b.total_spent||0)-(a.total_spent||0); });
    renderClients(list);
}

// ========== МОДАЛЬНОЕ ОКНО ДЕТАЛЕЙ ==========
async function openDetail(id) {
    currentDetailId = id;
    document.getElementById('detail-modal').classList.add('show');
    document.getElementById('modal-history').innerHTML = '<div class="loading-container"><div class="loading-spinner"></div></div>';
    
    try {
        var r = await AUTH.fetch('/backend/public/api/cosmetologist/clients/' + id);
        var d = await r.json();
        if (d.success) {
            document.getElementById('modal-client-name').textContent = d.data.client.fullname;
            renderHistory(d.data.history || []);
        }
    } catch(e) {}
    
    // Загружаем услуги
    var sr = await AUTH.fetch('/backend/public/api/cosmetologist/services');
    var sd = await sr.json();
    if (sd.success) {
        var sel = document.getElementById('modal-service');
        sel.innerHTML = '<option value="">Выберите</option>';
        (sd.data.services||[]).forEach(function(s){ sel.innerHTML += '<option value="'+s.id+'">'+esc(s.service)+' — '+s.price+' BYN</option>'; });
    }
    
    // Время
    var ts = document.getElementById('modal-time');
    ts.innerHTML = '<option value="">Выберите</option>';
    for (var h=9; h<=18; h++) { var hm = (h<10?'0':'')+h; ts.innerHTML += '<option value="'+hm+':00">'+hm+':00</option><option value="'+hm+':30">'+hm+':30</option>'; }
    
    document.getElementById('modal-date').min = new Date().toISOString().split('T')[0];
}

function closeModal() {
    document.getElementById('detail-modal').classList.remove('show');
    currentDetailId = null;
}

function renderHistory(history) {
    var c = document.getElementById('modal-history');
    if (history.length === 0) { c.innerHTML = '<p class="text-muted">Нет записей</p>'; return; }
    var sn = { pending:'Ожидает', confirmed:'Подтверждена', completed:'Завершена', cancelled:'Отменена' };
    var sc = { pending:'#f0ad4e', confirmed:'#5bc0de', completed:'#5cb85c', cancelled:'#d9534f' };
    var h = '';
    history.forEach(function(b) {
        var d = new Date(b.schedule);
        h += '<div class="history-item">' +
            '<div><strong>' + d.toLocaleDateString('ru-RU') + ' в ' + d.toLocaleTimeString('ru-RU',{hour:'2-digit',minute:'2-digit'}) + '</strong> ' +
                '<span style="color:'+(sc[b.status]||'#999')+'; font-size:12px;">'+(sn[b.status]||b.status)+'</span></div>' +
            '<div>' + esc(b.service) + ' — ' + (b.price||0) + ' BYN</div>';
        if (b.description) h += '<div style="font-size:12px; color:#999;">💬 '+esc(b.description)+'</div>';
        h += '</div>';
    });
    c.innerHTML = h;
}

async function createBookingFromModal() {
    var s = document.getElementById('modal-service').value;
    var d = document.getElementById('modal-date').value;
    var t = document.getElementById('modal-time').value;
    var c = document.getElementById('modal-comment').value;
    if (!s||!d||!t||!currentDetailId) { alert('Заполните все поля'); return; }
    var user = JSON.parse(localStorage.getItem('user')||'{}');
    var r = await AUTH.fetch('/backend/public/api/bookings', { method:'POST', body:JSON.stringify({cosmetologist_id:user.cosmetologist_id, service_id:parseInt(s), schedule:d+' '+t+':00', client_id:currentDetailId, description:c||''}) });
    var data = await r.json();
    if (r.ok && data.success) { alert('Запись создана!'); openDetail(currentDetailId); loadClients(); }
    else { alert(data.error||'Ошибка'); }
}

// ========== МОДАЛЬНОЕ ОКНО ДОБАВЛЕНИЯ/РЕДАКТИРОВАНИЯ ==========
function openAddModal() {
    document.getElementById('edit-modal-title').textContent = 'Добавить клиента';
    document.getElementById('edit-id').value = '';
    document.getElementById('edit-name').value = '';
    document.getElementById('edit-phone').value = '';
    document.getElementById('edit-comm').value = 'phone';
    document.getElementById('edit-modal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('show');
}

async function saveClient() {
    var id = document.getElementById('edit-id').value;
    var name = document.getElementById('edit-name').value.trim();
    var phone = document.getElementById('edit-phone').value.trim();
    var comm = document.getElementById('edit-comm').value;
    if (!name||!phone) { alert('Заполните имя и телефон'); return; }
    
    var url = '/backend/public/api/cosmetologist/clients';
    var method = 'POST';
    var body = { fullname:name, phone:phone, communication:comm };
    if (id) { url += '/' + id; method = 'PUT'; }
    
    var r = await AUTH.fetch(url, { method:method, body:JSON.stringify(body) });
    var d = await r.json();
    if (r.ok && d.success) { closeEditModal(); loadClients(); }
    else { alert(d.error||'Ошибка'); }
}

function esc(t) { if(!t) return ''; var d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('ru-RU') : '—'; }

// Закрытие по клику вне модалки
document.getElementById('detail-modal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });
document.getElementById('edit-modal').addEventListener('click', function(e){ if(e.target===this) closeEditModal(); });

loadClients();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>