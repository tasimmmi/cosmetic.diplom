<!-- МОДАЛЬНОЕ ОКНО ДЕТАЛЕЙ КЛИЕНТА (ПОДКЛЮЧАЕТСЯ НА РАЗНЫХ СТРАНИЦАХ) -->
<div class="modal" id="client-detail-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="detail-client-name">Клиент</h3>
            <button class="modal-close" onclick="closeClientDetail()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="history-panel">
                <h4>История посещений</h4>
                <div id="detail-history"><p class="text-muted">Загрузка...</p></div>
            </div>
            
            <div class="booking-panel">
                <h4 style="margin-bottom: 20px;">Записать на прием</h4>
                <div class="form-group"><label>Услуга *</label><select class="form-control" id="booking-service"><option value="">Выберите услугу</option></select></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group"><label>Дата *</label><input type="date" class="form-control" id="booking-date" min=""></div>
                    <div class="form-group"><label>Время *</label><select class="form-control" id="booking-time"><option value="">Выберите время</option></select></div>
                </div>
                <div class="form-group"><label>Комментарий</label><textarea class="form-control" id="booking-comment" rows="2" placeholder="Не обязательно"></textarea></div>
                <button class="btn btn-primary btn-block" onclick="createBooking()"><i class="fas fa-calendar-plus"></i> Записать</button>
                <hr style="margin: 20px 0;">
                <button class="btn btn-outline btn-block btn-sm" onclick="editClientInfo()"><i class="fas fa-edit"></i> Редактировать клиента</button>
                <button class="btn btn-outline-danger btn-block btn-sm mt-2" onclick="deleteClient()"><i class="fas fa-trash"></i> Удалить клиента</button>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО ДОБАВЛЕНИЯ/РЕДАКТИРОВАНИЯ КЛИЕНТА -->
<div class="modal" id="add-client-modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header"><h3 id="add-client-title">Добавить клиента</h3><button class="modal-close" onclick="closeAddClient()">&times;</button></div>
        <div class="modal-body" style="padding: 25px; display: block;">
            <input type="hidden" id="edit-client-id">
            <div class="form-group"><label>Полное имя *</label><input type="text" class="form-control" id="new-client-name" placeholder="Иванова Анна" required></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group"><label>Телефон *</label><input type="tel" class="form-control" id="new-client-phone" placeholder="+375..." required></div>
                <div class="form-group"><label>Способ связи</label><select class="form-control" id="new-client-communication">
                    <option value="phone">Телефон</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="telegram">Telegram</option>
                </select></div>
            </div>
        </div>
        <div style="padding: 0 25px 25px 25px; display: flex; justify-content: flex-end; gap: 10px;">
            <button class="btn btn-outline" onclick="closeAddClient()">Отмена</button>
            <button class="btn btn-primary" onclick="saveClient()">Сохранить</button>
        </div>
    </div>
</div>

<!-- ОБЩИЙ JavaScript ДЛЯ МОДАЛЬНЫХ ОКОН КЛИЕНТА -->
<script>
var currentClientId = null;
var currentClientData = null;

// ========== ДЕТАЛИ КЛИЕНТА ==========
async function openClientDetail(clientId) {
    currentClientId = clientId;
    try {
        var response = await AUTH.fetch('/backend/public/api/cosmetologist/clients/' + clientId);
        var data = await response.json();
        if (data.success) {
            currentClientData = data.data.client;
            document.getElementById('detail-client-name').textContent = currentClientData.fullname;
            renderClientHistory(data.data.history || []);
            await loadServicesForBooking();
            document.getElementById('booking-date').min = new Date().toISOString().split('T')[0];
            document.getElementById('client-detail-modal').classList.add('show');
        }
    } catch (error) { alert('Ошибка загрузки'); }
}

function closeClientDetail() {
    document.getElementById('client-detail-modal').classList.remove('show');
    currentClientId = null;
    currentClientData = null;
}

// ========== ИСТОРИЯ ==========
function renderClientHistory(history) {
    var container = document.getElementById('detail-history');
    if (history.length === 0) { container.innerHTML = '<p class="text-muted">Нет записей</p>'; return; }
    
    var statusNames = { pending: 'Ожидает', confirmed: 'Подтверждена', completed: 'Завершена', cancelled: 'Отменена' };
    var statusColors = { pending: '#f0ad4e', confirmed: '#5bc0de', completed: '#5cb85c', cancelled: '#d9534f' };
    
    var html = '';
    history.forEach(function(h) {
        var date = new Date(h.schedule);
        var dateStr = date.toLocaleDateString('ru-RU');
        var timeStr = date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        var statusName = statusNames[h.status] || h.status;
        var statusColor = statusColors[h.status] || '#999';
        var safeComment = h.description ? h.description.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;') : '';
        
        html += '<div class="history-item">';
        html += '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">';
        html += '<span style="font-weight: 600; color: #333;">' + dateStr + ' в ' + timeStr + '</span>';
        html += '<span style="font-size: 12px; font-weight: 500; color: ' + statusColor + ';">' + statusName + '</span>';
        html += '</div>';
        html += '<div style="margin-bottom: 8px;">' + escapeHtml(h.service) + ' — <strong>' + (h.price || 0) + ' BYN</strong></div>';
        
        if (h.description) {
            html += '<div style="margin-bottom: 8px; padding: 6px 10px; background: #f9f9f9; border-radius: 4px; font-size: 12px; color: #888;">';
            html += '<span id="comment-text-' + h.booking_id + '">' + escapeHtml(h.description) + '</span>';
            html += '<span style="margin-left: 8px; cursor: pointer; color: #aaa; font-size: 11px;" onclick="event.stopPropagation(); editComment(' + h.booking_id + ', \'' + safeComment + '\')">✏️</span>';
            html += '</div>';
        }
        
        html += '<div style="border-top: 1px solid #eee; padding-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">';
        if (h.status === 'pending') { html += '<button class="btn btn-sm" style="background: none; border: 1px solid #ddd; color: #666; font-size: 11px;" onclick="event.stopPropagation(); confirmBooking(' + h.booking_id + ')">✓ Подтвердить</button>'; }
        if (h.status === 'confirmed') { html += '<button class="btn btn-sm" style="background: none; border: 1px solid #ddd; color: #666; font-size: 11px;" onclick="event.stopPropagation(); completeBooking(' + h.booking_id + ')">✓✓ Завершить</button>'; }
        if (h.status === 'pending' || h.status === 'confirmed') { html += '<button class="btn btn-sm" style="background: none; border: 1px solid #ddd; color: #666; font-size: 11px;" onclick="event.stopPropagation(); cancelBooking(' + h.booking_id + ')">✕ Отменить</button>'; }
        html += '<button class="btn btn-sm" style="background: none; border: 1px solid #ddd; color: #666; font-size: 11px;" onclick="event.stopPropagation(); addComment(' + h.booking_id + ')">💬 Комментарий</button>';
        html += '</div></div>';
    });
    container.innerHTML = html;
}

// ========== ДЕЙСТВИЯ С ЗАПИСЯМИ ==========
async function confirmBooking(id) { if (!confirm('Подтвердить?')) return; await AUTH.fetch('/backend/public/api/cosmetologist/bookings/' + id + '/confirm', { method: 'PUT' }); openClientDetail(currentClientId); if (typeof loadClients === 'function') loadClients(); }
async function completeBooking(id) { if (!confirm('Завершить?')) return; await AUTH.fetch('/backend/public/api/cosmetologist/bookings/' + id + '/complete', { method: 'PUT' }); openClientDetail(currentClientId); if (typeof loadClients === 'function') loadClients(); }
async function cancelBooking(id) { if (!confirm('Отменить?')) return; await AUTH.fetch('/backend/public/api/bookings/' + id + '/cancel', { method: 'PUT' }); openClientDetail(currentClientId); if (typeof loadClients === 'function') loadClients(); }

function addComment(id) { var c = prompt('Комментарий:'); if (c && c.trim()) saveComment(id, c); }
function editComment(id, old) { var c = prompt('Редактировать:', old); if (c && c.trim()) saveComment(id, c); }
async function saveComment(id, comment) { await AUTH.fetch('/backend/public/api/bookings/' + id + '/comment', { method: 'PUT', body: JSON.stringify({ description: comment }) }); openClientDetail(currentClientId); }

// ========== УСЛУГИ ДЛЯ ЗАПИСИ ==========
async function loadServicesForBooking() {
    var response = await AUTH.fetch('/backend/public/api/cosmetologist/services');
    var data = await response.json();
    if (data.success) {
        var select = document.getElementById('booking-service');
        select.innerHTML = '<option value="">Выберите услугу</option>';
        (data.data.services || []).forEach(function(s) { select.innerHTML += '<option value="' + s.id + '">' + escapeHtml(s.service) + ' — ' + s.price + ' BYN</option>'; });
    }
}

function generateTimeSlots() {
    var select = document.getElementById('booking-time');
    select.innerHTML = '<option value="">Выберите время</option>';
    ['09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00'].forEach(function(t) { select.innerHTML += '<option value="' + t + '">' + t + '</option>'; });
}

// ========== СОЗДАТЬ ЗАПИСЬ ==========
async function createBooking() {
    var s = document.getElementById('booking-service').value;
    var d = document.getElementById('booking-date').value;
    var t = document.getElementById('booking-time').value;
    var c = document.getElementById('booking-comment').value;
    if (!s || !d || !t || !currentClientId) { alert('Заполните все поля'); return; }
    var user = JSON.parse(localStorage.getItem('user') || '{}');
    var response = await AUTH.fetch('/backend/public/api/bookings', { method: 'POST', body: JSON.stringify({ cosmetologist_id: user.cosmetologist_id, service_id: parseInt(s), schedule: d + ' ' + t + ':00', client_id: currentClientId, description: c || '' }) });
    var result = await response.json();
    if (response.ok && result.success) { alert('Запись создана!'); document.getElementById('booking-service').value = ''; document.getElementById('booking-date').value = ''; document.getElementById('booking-time').value = ''; document.getElementById('booking-comment').value = ''; openClientDetail(currentClientId); if (typeof loadClients === 'function') loadClients(); }
    else { alert(result.error || 'Ошибка'); }
}

// ========== ДОБАВИТЬ/РЕДАКТИРОВАТЬ КЛИЕНТА ==========
function showAddClientForm() {
    document.getElementById('add-client-title').textContent = 'Добавить клиента';
    document.getElementById('edit-client-id').value = '';
    document.getElementById('new-client-name').value = '';
    document.getElementById('new-client-phone').value = '';
    document.getElementById('new-client-communication').value = 'phone';
    document.getElementById('add-client-modal').classList.add('show');
}

function editClientInfo() {
    if (!currentClientData) return;
    document.getElementById('add-client-title').textContent = 'Редактировать клиента';
    document.getElementById('edit-client-id').value = currentClientData.id;
    document.getElementById('new-client-name').value = currentClientData.fullname || '';
    document.getElementById('new-client-phone').value = currentClientData.phone || '';
    document.getElementById('new-client-communication').value = currentClientData.communication || 'phone';
    document.getElementById('add-client-modal').classList.add('show');
}

function closeAddClient() { document.getElementById('add-client-modal').classList.remove('show'); }

async function saveClient() {
    var id = document.getElementById('edit-client-id').value;
    var name = document.getElementById('new-client-name').value.trim();
    var phone = document.getElementById('new-client-phone').value.trim();
    var comm = document.getElementById('new-client-communication').value;
    if (!name || !phone) { alert('Заполните имя и телефон'); return; }
    var url = '/backend/public/api/cosmetologist/clients';
    var method = 'POST';
    var body = { fullname: name, phone: phone, communication: comm };
    if (id) { url += '/' + id; method = 'PUT'; }
    var response = await AUTH.fetch(url, { method: method, body: JSON.stringify(body) });
    var result = await response.json();
    if (response.ok && result.success) { closeAddClient(); if (typeof loadClients === 'function') loadClients(); if (currentClientId) openClientDetail(currentClientId); }
    else { alert(result.error || 'Ошибка'); }
}

async function deleteClient() { if (!confirm('Удалить клиента?')) return; await AUTH.fetch('/backend/public/api/cosmetologist/clients/' + currentClientId, { method: 'DELETE' }); closeClientDetail(); if (typeof loadClients === 'function') loadClients(); }

function formatDate(d) { return d ? new Date(d).toLocaleDateString('ru-RU') : '—'; }
function escapeHtml(t) { if (!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

// Закрытие модалок по клику вне
document.getElementById('client-detail-modal').addEventListener('click', function(e) { if (e.target === this) closeClientDetail(); });
document.getElementById('add-client-modal').addEventListener('click', function(e) { if (e.target === this) closeAddClient(); });

document.getElementById('booking-date').min = new Date().toISOString().split('T')[0];
generateTimeSlots();
</script>