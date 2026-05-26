<?php
$pageTitle = 'Склад материалов';
$extraStyles = '
    .material-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .material-info { flex: 1; }
    .material-info h4 { margin: 0 0 5px 0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .material-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-left: 10px; }
    .status-in { background: #d4edda; color: #155724; }
    .status-out { background: #f8d7da; color: #721c24; }
    .material-price { color: #666; font-size: 14px; margin-top: 3px; }
    .material-actions { display: flex; gap: 8px; align-items: center; }
    
    /* Вкладки */
    .tabs-bar {
        display: flex;
        gap: 5px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
    }
    .tab-btn {
        padding: 10px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        color: #666;
        transition: all 0.2s;
        margin-bottom: 0px;
        position: relative;
    }
    .tab-btn:hover {
        color: #667eea;
    }
    .tab-btn.active {
        color: #667eea;
    }
    .tab-btn.active::after {
        content: "";
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: #667eea;
    }
    
    /* Стили для закупок */
    .procurement-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 15px;
        background: white;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .procurement-info {
        flex: 1;
    }
    .procurement-material {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    .procurement-details {
        font-size: 12px;
        color: #888;
    }
    .procurement-price {
        font-weight: 700;
        color: #667eea;
        font-size: 16px;
        min-width: 100px;
    }
    .procurement-actions {
        display: flex;
        gap: 8px;
    }
    
    /* Точка для личных материалов */
    .material-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 8px;
        height: 8px;
        background: #28a745;
        border-radius: 50%;
        cursor: help;
        flex-shrink: 0;
    }
    .material-badge:hover::after {
        content: "Личный материал (только для вас)";
        position: absolute;
        background: #333;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        white-space: nowrap;
        margin-top: -25px;
        margin-left: 5px;
        z-index: 100;
    }
    
    .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 10000; }
    .modal.show { display: flex; }
    .modal-content { background: white; border-radius: 12px; width: 100%; max-width: 500px; padding: 25px; max-height: 80vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-header h3 { margin: 0; }
    .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    .checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .form-hint { font-size: 12px; color: #999; margin-top: 3px; }
    .btn-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; background: white; font-size: 14px; }
    .btn-icon:hover { background: #f0f0f0; }
    .btn-icon.text-danger { color: #dc3545; border-color: #dc3545; }
    .btn-icon.text-danger:hover { background: #fff5f5; }
    
    .select-material {
        max-height: 200px;
        overflow-y: auto;
    }
    .filter-bar {
        display: flex;
        gap: 8px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .filter-btn {
        padding: 5px 12px;
        border: 1px solid #ddd;
        border-radius: 15px;
        background: white;
        cursor: pointer;
        font-size: 12px;
    }
    .filter-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Склад материалов</h1>
                <button class="btn btn-primary" id="add-button" onclick="handleAddButton()">
                    <i class="fas fa-plus"></i> Добавить
                </button>
            </div>
            
            <!-- ВКЛАДКИ -->
            <div class="tabs-bar">
                <button class="tab-btn active" data-tab="materials" onclick="switchTab('materials')">Все</button>
                <button class="tab-btn" data-tab="my" onclick="switchTab('my')">Мои</button>
                <button class="tab-btn" data-tab="in-stock" onclick="switchTab('in-stock')">В наличии</button>
                <button class="tab-btn" data-tab="out-of-stock" onclick="switchTab('out-of-stock')">Отсутствуют</button>
                <button class="tab-btn" data-tab="procurements" onclick="switchTab('procurements')">Закупки</button>
            </div>
            
            <!-- Панель материалов -->
            <div id="materials-panel">
                <div id="materials-list">
                    <div class="loading-container">
                        <div class="loading-spinner"></div>
                        <p>Загрузка...</p>
                    </div>
                </div>
            </div>
            
            <!-- Панель закупок -->
            <div id="procurements-panel" style="display: none;">
                <div id="procurements-list">
                    <div class="loading-container">
                        <div class="loading-spinner"></div>
                        <p>Загрузка...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО МАТЕРИАЛА -->
<div class="modal" id="material-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Добавить материал</h3>
            <button class="modal-close" onclick="closeMaterialModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Название материала *</label>
                <input type="text" class="form-control" id="material-name" placeholder="Например: Ватные диски" required>
            </div>
            <div class="form-group">
                <label>Начальная стоимость (BYN)</label>
                <input type="number" class="form-control" id="material-price" placeholder="Не обязательно" step="0.01" min="0">
                <div class="form-hint">Если указать, автоматически создастся первая закупка</div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="material-stock" checked>
                    <span>Есть в наличии</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="material-mutable" checked>
                    <span>Общий материал (доступен всем)</span>
                </label>
            </div>
            <input type="hidden" id="edit-material-id">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeMaterialModal()">Отмена</button>
            <button class="btn btn-primary" onclick="saveMaterial()">Сохранить</button>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО ЗАКУПКИ -->
<div class="modal" id="procurement-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="procurement-modal-title">Добавить закупку</h3>
            <button class="modal-close" onclick="closeProcurementModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-procurement-id">
            <div class="form-group">
                <label>Материал *</label>
                <select class="form-control" id="procurement-material-id" required>
                    <option value="">Выберите материал</option>
                </select>
            </div>
            <div class="form-group">
                <label>Цена закупки (BYN) *</label>
                <input type="number" class="form-control" id="procurement-price" placeholder="0.00" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label>Дата закупки</label>
                <input type="date" class="form-control" id="procurement-date">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeProcurementModal()">Отмена</button>
            <button class="btn btn-primary" onclick="saveProcurement()">Сохранить</button>
        </div>
    </div>
</div>

<script>
var token = localStorage.getItem('access_token');
var materials = [];
var procurements = [];
var currentMaterialsTab = 'all'; // 'all', 'my', 'in-stock', 'out-of-stock'
var currentTab = 'materials'; // 'materials' or 'procurements'

// ========== ПЕРЕКЛЮЧЕНИЕ ВКЛАДОК ==========
function switchTab(tab) {
    currentTab = tab;
    
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('active');
        if (btn.getAttribute('data-tab') === tab) {
            btn.classList.add('active');
        }
    });
    
    var addButton = document.getElementById('add-button');
    
    if (tab === 'procurements') {
        document.getElementById('materials-panel').style.display = 'none';
        document.getElementById('procurements-panel').style.display = 'block';
        addButton.innerHTML = '<i class="fas fa-plus"></i> Добавить закупку';
        addButton.setAttribute('onclick', 'showAddProcurementForm()');
        loadProcurements();
    } else {
        document.getElementById('materials-panel').style.display = 'block';
        document.getElementById('procurements-panel').style.display = 'none';
        addButton.innerHTML = '<i class="fas fa-plus"></i> Добавить материал';
        addButton.setAttribute('onclick', 'showAddMaterialForm()');
        
        // Обновляем активную вкладку материалов
        currentMaterialsTab = tab;
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.classList.remove('active');
            if (btn.getAttribute('data-tab') === tab) {
                btn.classList.add('active');
            }
        });
        applyMaterialsFilter();
    }
}

function handleAddButton() {
    if (currentTab === 'procurements') {
        showAddProcurementForm();
    } else {
        showAddMaterialForm();
    }
}

// ========== ЗАГРУЗКА МАТЕРИАЛОВ ==========
async function loadMaterials() {
    var container = document.getElementById('materials-list');
    container.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div><p>Загрузка...</p></div>';
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/materials', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        var data = await response.json();
        
        if (data.success) {
            materials = data.data.materials || [];
            applyMaterialsFilter();
        } else {
            container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

function applyMaterialsFilter() {
    var filtered = [...materials];
    
    if (currentMaterialsTab === 'my') {
        filtered = filtered.filter(function(m) { return !m.is_mutable; });
    } else if (currentMaterialsTab === 'in-stock') {
        filtered = filtered.filter(function(m) { return m.is_stock; });
    } else if (currentMaterialsTab === 'out-of-stock') {
        filtered = filtered.filter(function(m) { return !m.is_stock; });
    }
    
    renderMaterials(filtered);
}

function renderMaterials(list) {
    var container = document.getElementById('materials-list');
    
    if (list.length === 0) {
        var emptyMessage = '';
        if (currentMaterialsTab === 'my') {
            emptyMessage = 'У вас пока нет личных материалов. Добавьте свой первый материал!';
        } else if (currentMaterialsTab === 'in-stock') {
            emptyMessage = 'Нет материалов в наличии';
        } else if (currentMaterialsTab === 'out-of-stock') {
            emptyMessage = 'Нет отсутствующих материалов';
        } else {
            emptyMessage = 'Материалы не найдены';
        }
        container.innerHTML = '<p class="text-muted text-center py-5">' + emptyMessage + '</p>';
        return;
    }
    
    var html = '';
    list.forEach(function(m) {
        var statusClass = m.is_stock ? 'status-in' : 'status-out';
        var statusText = m.is_stock ? '✓ В наличии' : '✗ Отсутствует';
        var materialName = escapeHtml(m.material);
        var safeName = materialName.replace(/'/g, "\\'");
        
        html += '<div class="material-item">' +
            '<div class="material-info">' +
                '<h4>' +
                    materialName +
                    (!m.is_mutable ? '<span class="material-badge" title="Личный материал (только для вас)"></span>' : '') +
                    '<span class="material-status ' + statusClass + '">' + statusText + '</span>' +
                '</h4>';
        
        if (m.last_price) {
            html += '<div class="material-price">Последняя закупка: ' + escapeHtml(m.last_price) + '</div>';
        }
        if (m.average_price) {
            html += '<div class="material-price">Средняя цена: ' + escapeHtml(m.average_price) + '</div>';
        }
        
        html += '</div>' +
            '<div class="material-actions">' +
                '<button class="btn-icon" onclick="toggleStock(' + m.id + ')" title="Изменить наличие">' +
                    (m.is_stock ? '<i class="fas fa-box-open"></i>' : '<i class="fas fa-box"></i>') +
                '</button>' +
                '<button class="btn btn-sm btn-outline" onclick="showProcurementFormForMaterial(' + m.id + ', \'' + safeName + '\')">' +
                    '<i class="fas fa-cart-plus"></i> Закупка' +
                '</button>' +
                '<button class="btn-icon" onclick="editMaterial(' + m.id + ')" title="Редактировать">' +
                    '<i class="fas fa-edit"></i>' +
                '</button>' +
                '<button class="btn-icon text-danger" onclick="deleteMaterial(' + m.id + ', \'' + safeName + '\')" title="Удалить">' +
                    '<i class="fas fa-trash"></i>' +
                '</button>' +
            '</div>' +
        '</div>';
    });
    
    container.innerHTML = html;
}

// ========== ЗАГРУЗКА ЗАКУПОК ==========
async function loadProcurements() {
    var container = document.getElementById('procurements-list');
    container.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div><p>Загрузка...</p></div>';
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/procurements/all', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        var data = await response.json();
        
        if (data.success) {
            procurements = data.data.procurements || [];
            renderProcurements();
        } else {
            container.innerHTML = '<p class="text-danger">Ошибка загрузки закупок</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки закупок</p>';
    }
}

function renderProcurements() {
    var container = document.getElementById('procurements-list');
    
    if (procurements.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-5">Закупок не найдено</p>';
        return;
    }
    
    var html = '';
    procurements.forEach(function(p) {
        var typeLabel = p.is_mutable ? 'Общий' : 'Личный';
        html += '<div class="procurement-item" id="procurement-' + p.id + '">' +
            '<div class="procurement-info">' +
                '<div class="procurement-material">' + escapeHtml(p.material_name) + ' <span style="font-size:11px;color:#888;">(' + typeLabel + ')</span></div>' +
                '<div class="procurement-details">' + fmtDateDisplay(p.date) + '</div>' +
            '</div>' +
            '<div class="procurement-price">' + formatNum(p.price) + ' BYN</div>' +
            '<div class="procurement-actions">' +
                '<button class="btn-icon" onclick="editProcurement(' + p.id + ')" title="Редактировать">' +
                    '<i class="fas fa-edit"></i>' +
                '</button>' +
                '<button class="btn-icon text-danger" onclick="deleteProcurement(' + p.id + ', \'' + escapeHtml(p.material_name) + '\')" title="Удалить">' +
                    '<i class="fas fa-trash"></i>' +
                '</button>' +
            '</div>' +
        '</div>';
    });
    
    container.innerHTML = html;
}

// ========== ДОБАВИТЬ/РЕДАКТИРОВАТЬ ЗАКУПКУ ==========
async function showAddProcurementForm() {
    await loadMaterialsForSelect();
    document.getElementById('procurement-modal-title').textContent = 'Добавить закупку';
    document.getElementById('edit-procurement-id').value = '';
    document.getElementById('procurement-material-id').value = '';
    document.getElementById('procurement-price').value = '';
    document.getElementById('procurement-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('procurement-modal').classList.add('show');
}

async function loadMaterialsForSelect() {
    var select = document.getElementById('procurement-material-id');
    select.innerHTML = '<option value="">Загрузка...</option>';
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/materials', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        var data = await response.json();
        
        if (data.success) {
            var mats = data.data.materials || [];
            select.innerHTML = '<option value="">Выберите материал</option>';
            mats.forEach(function(m) {
                var typeLabel = m.is_mutable ? ' (Общий)' : ' (Личный)';
                select.innerHTML += '<option value="' + m.id + '">' + escapeHtml(m.material) + typeLabel + '</option>';
            });
        }
    } catch(e) {
        select.innerHTML = '<option value="">Ошибка загрузки</option>';
    }
}

function showProcurementFormForMaterial(materialId, materialName) {
    document.getElementById('procurement-modal-title').textContent = 'Добавить закупку для ' + materialName;
    document.getElementById('edit-procurement-id').value = '';
    document.getElementById('procurement-material-id').innerHTML = '<option value="' + materialId + '" selected>' + materialName + '</option>';
    document.getElementById('procurement-price').value = '';
    document.getElementById('procurement-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('procurement-modal').classList.add('show');
}

function closeProcurementModal() {
    document.getElementById('procurement-modal').classList.remove('show');
}

async function saveProcurement() {
    var id = document.getElementById('edit-procurement-id').value;
    var materialId = document.getElementById('procurement-material-id').value;
    var price = document.getElementById('procurement-price').value;
    var date = document.getElementById('procurement-date').value;
    
    if (!materialId) { alert('Выберите материал'); return; }
    if (!price || parseFloat(price) <= 0) { alert('Введите корректную цену'); return; }
    
    var url = '/backend/public/api/cosmetologist/procurements';
    var method = 'POST';
    var body = { material_id: parseInt(materialId), price: parseFloat(price), date: date };
    
    if (id) {
        url = '/backend/public/api/cosmetologist/procurements/' + id;
        method = 'PUT';
    }
    
    try {
        var response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(body)
        });
        
        if (response.ok) {
            closeProcurementModal();
            if (currentTab === 'procurements') {
                loadProcurements();
            }
            loadMaterials();
        } else {
            var error = await response.json();
            alert(error.error || 'Ошибка сохранения');
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
}

async function editProcurement(id) {
    var procurement = null;
    for (var i = 0; i < procurements.length; i++) {
        if (procurements[i].id == id) { procurement = procurements[i]; break; }
    }
    if (!procurement) return;
    
    await loadMaterialsForSelect();
    
    document.getElementById('procurement-modal-title').textContent = 'Редактировать закупку';
    document.getElementById('edit-procurement-id').value = procurement.id;
    document.getElementById('procurement-material-id').value = procurement.material_id;
    document.getElementById('procurement-price').value = procurement.price;
    document.getElementById('procurement-date').value = procurement.date.split(' ')[0];
    document.getElementById('procurement-modal').classList.add('show');
}

async function deleteProcurement(id, materialName) {
    if (!confirm('Удалить закупку материала "' + materialName + '"?')) return;
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/procurements/' + id, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (response.ok) {
            if (currentTab === 'procurements') {
                loadProcurements();
            }
            loadMaterials();
        } else {
            var error = await response.json();
            alert(error.error || 'Ошибка удаления');
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
}

// ========== ДОБАВИТЬ/РЕДАКТИРОВАТЬ МАТЕРИАЛ ==========
function showAddMaterialForm() {
    document.getElementById('modal-title').textContent = 'Добавить материал';
    document.getElementById('edit-material-id').value = '';
    document.getElementById('material-name').value = '';
    document.getElementById('material-price').value = '';
    document.getElementById('material-stock').checked = true;
    document.getElementById('material-mutable').checked = true;
    document.getElementById('material-modal').classList.add('show');
}

function editMaterial(id) {
    var material = null;
    for (var i = 0; i < materials.length; i++) {
        if (materials[i].id == id) { material = materials[i]; break; }
    }
    if (!material) return;
    
    document.getElementById('modal-title').textContent = 'Редактировать материал';
    document.getElementById('edit-material-id').value = material.id;
    document.getElementById('material-name').value = material.material;
    document.getElementById('material-price').value = '';
    document.getElementById('material-stock').checked = material.is_stock;
    document.getElementById('material-mutable').checked = material.is_mutable;
    document.getElementById('material-modal').classList.add('show');
}

function closeMaterialModal() {
    document.getElementById('material-modal').classList.remove('show');
}

async function saveMaterial() {
    var id = document.getElementById('edit-material-id').value;
    var name = document.getElementById('material-name').value.trim();
    var price = document.getElementById('material-price').value;
    var isStock = document.getElementById('material-stock').checked;
    var isMutable = document.getElementById('material-mutable').checked;
    
    if (!name) { alert('Введите название'); return; }
    
    var url = '/backend/public/api/cosmetologist/materials';
    var method = 'POST';
    var body = { material: name, is_stock: isStock, is_mutable: isMutable };
    
    if (id) {
        url = '/backend/public/api/cosmetologist/materials/' + id;
        method = 'PUT';
    }
    
    try {
        var response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(body)
        });
        
        if (response.ok) {
            var result = await response.json();
            var materialId = result.data?.material_id || id;
            
            if (price && parseFloat(price) > 0 && !id) {
                await fetch('/backend/public/api/cosmetologist/procurements', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: JSON.stringify({ material_id: materialId, price: parseFloat(price) })
                });
            }
            
            closeMaterialModal();
            loadMaterials();
            if (currentTab === 'procurements') {
                loadProcurements();
            }
        } else {
            var error = await response.json();
            alert(error.error || 'Ошибка');
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
}

// ========== УДАЛИТЬ МАТЕРИАЛ ==========
async function deleteMaterial(id, name) {
    if (!confirm('Удалить материал "' + name + '"?')) return;
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/materials/' + id, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (response.ok) {
            loadMaterials();
            if (currentTab === 'procurements') {
                loadProcurements();
            }
        } else {
            var error = await response.json();
            alert(error.error || 'Ошибка удаления');
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
}

// ========== ИЗМЕНИТЬ НАЛИЧИЕ ==========
async function toggleStock(id) {
    var material = null;
    for (var i = 0; i < materials.length; i++) {
        if (materials[i].id == id) { material = materials[i]; break; }
    }
    if (!material) return;
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/materials/' + id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ is_stock: !material.is_stock })
        });
        
        if (response.ok) {
            loadMaterials();
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
}

function formatNum(val) {
    return parseFloat(val||0).toFixed(2);
}

function fmtDateDisplay(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(dateStr);
    return d.toLocaleDateString('ru-RU', {day:'2-digit',month:'2-digit',year:'numeric'});
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Закрытие модалок по клику вне
document.getElementById('material-modal').addEventListener('click', function(e) {
    if (e.target === this) closeMaterialModal();
});
document.getElementById('procurement-modal').addEventListener('click', function(e) {
    if (e.target === this) closeProcurementModal();
});

// Загрузка материалов при старте
loadMaterials();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>