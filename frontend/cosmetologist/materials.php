<?php
$pageTitle = 'Склад материалов';
$extraStyles = '
    .material-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .material-info { flex: 1; }
    .material-info h4 { margin: 0 0 5px 0; }
    .material-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-left: 10px; }
    .status-in { background: #d4edda; color: #155724; }
    .status-out { background: #f8d7da; color: #721c24; }
    .material-price { color: #666; font-size: 14px; margin-top: 3px; }
    .material-actions { display: flex; gap: 8px; align-items: center; }
    .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .filter-btn { padding: 8px 16px; border: 1px solid #ddd; border-radius: 20px; cursor: pointer; background: white; font-size: 14px; }
    .filter-btn:hover { background: #f0f0f0; }
    .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
    .filter-count { font-size: 12px; opacity: 0.8; }
    
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
';

include __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar-cosmetologist.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Склад материалов</h1>
                <button class="btn btn-primary" onclick="showAddMaterialForm()">
                    <i class="fas fa-plus"></i> Добавить материал
                </button>
            </div>
            
            <!-- ФИЛЬТР -->
            <div class="filter-bar">
                <button class="filter-btn active" onclick="filterMaterials('all', this)">Все <span class="filter-count" id="count-all">0</span></button>
                <button class="filter-btn" onclick="filterMaterials('in-stock', this)">✓ В наличии <span class="filter-count" id="count-in">0</span></button>
                <button class="filter-btn" onclick="filterMaterials('out-of-stock', this)">✗ Отсутствуют <span class="filter-count" id="count-out">0</span></button>
            </div>
            
            <div id="materials-list">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Загрузка...</p>
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
            <h3>Добавить закупку</h3>
            <button class="modal-close" onclick="closeProcurementModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Материал: <strong id="procurement-material-name"></strong></p>
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
var currentFilter = 'all';
var currentProcurementMaterialId = null;

// ========== ЗАГРУЗКА ==========
async function loadMaterials() {
    var container = document.getElementById('materials-list');
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/materials', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        var data = await response.json();
        
        if (data.success) {
            materials = data.data.materials || [];
            updateCounters();
            renderMaterials(materials);
        } else {
            container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки</p>';
    }
}

// ========== ОТРИСОВКА ==========
function renderMaterials(list) {
    var container = document.getElementById('materials-list');
    
    if (list.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-5">Материалы не найдены</p>';
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
                '<h4>' + materialName + '<span class="material-status ' + statusClass + '">' + statusText + '</span></h4>';
        
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
                '<button class="btn btn-sm btn-outline" onclick="showProcurementForm(' + m.id + ', \'' + safeName + '\')">' +
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

// ========== ФИЛЬТР ==========
function filterMaterials(filter, btn) {
    currentFilter = filter;
    
    document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    
    var filtered = materials;
    if (filter === 'in-stock') filtered = materials.filter(function(m) { return m.is_stock; });
    if (filter === 'out-of-stock') filtered = materials.filter(function(m) { return !m.is_stock; });
    
    renderMaterials(filtered);
}

function updateCounters() {
    document.getElementById('count-all').textContent = '(' + materials.length + ')';
    document.getElementById('count-in').textContent = '(' + materials.filter(function(m) { return m.is_stock; }).length + ')';
    document.getElementById('count-out').textContent = '(' + materials.filter(function(m) { return !m.is_stock; }).length + ')';
}

// ========== ДОБАВИТЬ МАТЕРИАЛ ==========
function showAddMaterialForm() {
    document.getElementById('modal-title').textContent = 'Добавить материал';
    document.getElementById('edit-material-id').value = '';
    document.getElementById('material-name').value = '';
    document.getElementById('material-price').value = '';
    document.getElementById('material-stock').checked = true;
    document.getElementById('material-mutable').checked = true;
    document.getElementById('material-modal').classList.add('show');
}

// ========== РЕДАКТИРОВАТЬ МАТЕРИАЛ ==========
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
            
            // Если указана начальная цена - создаем закупку
            if (price && parseFloat(price) > 0 && !id) {
                await fetch('/backend/public/api/cosmetologist/procurements', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: JSON.stringify({ material_id: materialId, price: parseFloat(price) })
                });
            }
            
            closeMaterialModal();
            loadMaterials();
        } else {
            var error = await response.json();
            alert(error.error || 'Ошибка');
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
}

// ========== УДАЛИТЬ ==========
async function deleteMaterial(id, name) {
    if (!confirm('Удалить материал "' + name + '"?')) return;
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/materials/' + id, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (response.ok) {
            loadMaterials();
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

// ========== ЗАКУПКА ==========
function showProcurementForm(materialId, materialName) {
    currentProcurementMaterialId = materialId;
    document.getElementById('procurement-material-name').textContent = materialName;
    document.getElementById('procurement-price').value = '';
    document.getElementById('procurement-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('procurement-modal').classList.add('show');
}

function closeProcurementModal() {
    document.getElementById('procurement-modal').classList.remove('show');
}

async function saveProcurement() {
    var price = document.getElementById('procurement-price').value;
    
    if (!price || parseFloat(price) <= 0) { alert('Введите цену'); return; }
    
    try {
        var response = await fetch('/backend/public/api/cosmetologist/procurements', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ material_id: currentProcurementMaterialId, price: parseFloat(price) })
        });
        
        if (response.ok) {
            closeProcurementModal();
            loadMaterials();
        } else {
            var error = await response.json();
            alert(error.error || 'Ошибка');
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    }
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

loadMaterials();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>