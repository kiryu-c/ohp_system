<?php
// app/views/subsidiary_subjects/index.php
?>

<!-- ヘッダー -->
<div class="header-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-list-alt me-2"></i>補助科目マスタ</h2>
        <div class="header-buttons">
            <button type="button" class="btn btn-success" id="addNewRowBtn">
                <i class="fas fa-plus me-1"></i>新規追加
            </button>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
            </a>
        </div>
    </div>
</div>

<!-- 補助科目一覧 -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>補助科目一覧
                <span class="badge bg-primary" id="listCount">全<?= count($subsidiarySubjects) ?>件</span>
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelAllBtn" style="display: none;">
                    <i class="fas fa-times me-1"></i>編集をキャンセル
                </button>
                <button type="button" class="btn btn-sm btn-success" id="saveAllBtn" style="display: none;">
                    <i class="fas fa-save me-1"></i>すべて保存
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($subsidiarySubjects)): ?>
            <div class="alert alert-info" id="emptyMessage">
                <i class="fas fa-info-circle me-2"></i>補助科目が登録されていません。「新規追加」ボタンから登録してください。
            </div>
        <?php endif; ?>
        
        <!-- デスクトップ用テーブル (768px以上) -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle" id="subsidiarySubjectTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 150px;">補助科目番号 <span class="text-danger">*</span></th>
                        <th>補助科目名称 <span class="text-danger">*</span></th>
                        <th style="width: 120px;" class="text-center">ステータス</th>
                        <th style="width: 150px;">更新日時</th>
                        <th style="width: 180px;" class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody id="sortableTable">
                    <?php foreach ($subsidiarySubjects as $subject): ?>
                        <tr data-id="<?= $subject['id'] ?>" data-mode="view">
                            <td>
                                <span class="view-mode">
                                    <code><?= htmlspecialchars($subject['number'], ENT_QUOTES, 'UTF-8') ?></code>
                                </span>
                                <input type="text" 
                                       class="form-control form-control-sm edit-mode" 
                                       name="number" 
                                       value="<?= htmlspecialchars($subject['number'], ENT_QUOTES, 'UTF-8') ?>"
                                       min="0" step="1"
                                       style="display: none;"
                                       data-original="<?= htmlspecialchars($subject['number'], ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td>
                                <span class="view-mode">
                                    <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <input type="text" 
                                       class="form-control form-control-sm edit-mode" 
                                       name="name" 
                                       value="<?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>"
                                       maxlength="100"
                                       style="display: none;"
                                       data-original="<?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td class="text-center">
                                <span class="view-mode">
                                    <?php if ($subject['is_active']): ?>
                                        <span class="badge bg-success">有効</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">無効</span>
                                    <?php endif; ?>
                                </span>
                                <select class="form-select form-select-sm edit-mode" 
                                        name="is_active" 
                                        style="display: none;"
                                        data-original="<?= $subject['is_active'] ?>">
                                    <option value="1" <?= $subject['is_active'] ? 'selected' : '' ?>>有効</option>
                                    <option value="0" <?= !$subject['is_active'] ? 'selected' : '' ?>>無効</option>
                                </select>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date('Y/m/d H:i', strtotime($subject['updated_at'])) ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm view-mode" role="group">
                                    <button type="button" class="btn btn-outline-primary edit-btn" title="編集">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger delete-btn" 
                                            title="削除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="btn-group btn-group-sm edit-mode" role="group" style="display: none;">
                                    <button type="button" class="btn btn-success save-btn" title="保存">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary cancel-btn" title="キャンセル">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- モバイル用カード (768px未満) -->
        <div class="d-md-none" id="mobileCardContainer">
            <?php foreach ($subsidiarySubjects as $subject): ?>
                <div class="card mb-3 shadow-sm mobile-card" data-id="<?= $subject['id'] ?>" data-mode="view">
                    <div class="card-body">
                        <!-- 表示モード -->
                        <div class="view-mode">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <code class="text-primary"><?= htmlspecialchars($subject['number'], ENT_QUOTES, 'UTF-8') ?></code>
                                    </h6>
                                    <div class="fw-bold"><?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div>
                                    <?php if ($subject['is_active']): ?>
                                        <span class="badge bg-success">有効</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">無効</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="small text-muted mb-2">
                                <div><i class="fas fa-clock me-1"></i><?= date('Y/m/d H:i', strtotime($subject['updated_at'])) ?></div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill mobile-edit-btn">
                                    <i class="fas fa-edit me-1"></i>編集
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger flex-fill mobile-delete-btn">
                                    <i class="fas fa-trash me-1"></i>削除
                                </button>
                            </div>
                        </div>
                        
                        <!-- 編集モード -->
                        <div class="edit-mode" style="display: none;">
                            <div class="mb-2">
                                <label class="form-label small mb-1">補助科目番号 <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control form-control-sm" 
                                       name="number" 
                                       value="<?= htmlspecialchars($subject['number'], ENT_QUOTES, 'UTF-8') ?>"
                                       min="0" step="1"
                                       data-original="<?= htmlspecialchars($subject['number'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">補助科目名称 <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control form-control-sm" 
                                       name="name" 
                                       value="<?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>"
                                       maxlength="100"
                                       data-original="<?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">ステータス</label>
                                <select class="form-select form-select-sm" 
                                        name="is_active"
                                        data-original="<?= $subject['is_active'] ?>">
                                    <option value="1" <?= $subject['is_active'] ? 'selected' : '' ?>>有効</option>
                                    <option value="0" <?= !$subject['is_active'] ? 'selected' : '' ?>>無効</option>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-success flex-fill mobile-save-btn">
                                    <i class="fas fa-check me-1"></i>保存
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary flex-fill mobile-cancel-btn">
                                    <i class="fas fa-times me-1"></i>キャンセル
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 削除用フォーム（非表示） -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<style>
tr[data-mode="edit"] {
    background-color: #fff3cd;
}

tr[data-mode="new"] {
    background-color: #d1ecf1;
}

.is-invalid {
    background-color: #fff3cd;
}

tr[data-mode="new"] {
    background-color: #d1ecf1;
}

.edit-mode input,
.edit-mode select {
    width: 100%;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    font-size: 0.875em;
    color: #dc3545;
}

@media (max-width: 767px) {
    .header-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .header-buttons .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .mobile-card[data-mode="edit"] {
        background-color: #fff3cd;
    }
    
    .mobile-card[data-mode="new"] {
        background-color: #d1ecf1;
    }
}
</style>

<script>
const BASE_URL = '<?= base_url() ?>';
const CSRF_TOKEN = '<?= csrf_token() ?>';

// グローバル変数
let editingRows = new Set();
let newRowCounter = 0;

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

// イベントリスナー初期化
function initializeEventListeners() {
    // 新規追加ボタン
    document.getElementById('addNewRowBtn').addEventListener('click', addNewRow);
    
    // すべて保存ボタン
    document.getElementById('saveAllBtn').addEventListener('click', saveAllChanges);
    
    // すべてキャンセルボタン
    document.getElementById('cancelAllBtn').addEventListener('click', cancelAllEdits);
    
    // デスクトップ用ボタン
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            enterEditMode(row);
        });
    });
    
    document.querySelectorAll('.save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            saveRow(row);
        });
    });
    
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            cancelEdit(row);
        });
    });
    
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            deleteRow(row);
        });
    });
    
    // モバイル用ボタン
    document.querySelectorAll('.mobile-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.mobile-card');
            enterEditModeMobile(card);
        });
    });
    
    document.querySelectorAll('.mobile-save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.mobile-card');
            saveRowMobile(card);
        });
    });
    
    document.querySelectorAll('.mobile-cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.mobile-card');
            cancelEditMobile(card);
        });
    });
    
    document.querySelectorAll('.mobile-delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.mobile-card');
            deleteRowMobile(card);
        });
    });
}

// 新規行追加
function addNewRow() {
    const table = document.getElementById('sortableTable');
    const emptyMessage = document.getElementById('emptyMessage');
    
    if (emptyMessage) {
        emptyMessage.style.display = 'none';
    }
    
    newRowCounter++;
    const newId = 'new_' + newRowCounter;
    
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-id', newId);
    newRow.setAttribute('data-mode', 'new');
    
    newRow.innerHTML = `
        <td>
            <input type="text" 
                   class="form-control form-control-sm" 
                   name="number" 
                   placeholder="番号を入力"
                   min="0" step="1"
                   required>
        </td>
        <td>
            <input type="text" 
                   class="form-control form-control-sm" 
                   name="name" 
                   placeholder="名称を入力"
                   maxlength="100"
                   required>
        </td>
        <td class="text-center">
            <select class="form-select form-select-sm" name="is_active">
                <option value="1" selected>有効</option>
                <option value="0">無効</option>
            </select>
        </td>
        <td>
            <small class="text-muted">新規</small>
        </td>
        <td class="text-center">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-success save-btn" title="保存">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" class="btn btn-secondary cancel-btn" title="キャンセル">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </td>
    `;
    
    table.insertBefore(newRow, table.firstChild);
    
    // イベントリスナー追加
    newRow.querySelector('.save-btn').addEventListener('click', function() {
        saveRow(newRow);
    });
    
    newRow.querySelector('.cancel-btn').addEventListener('click', function() {
        cancelEdit(newRow);
    });
    
    // モバイル版も追加
    addNewRowMobile();
    
    editingRows.add(newId);
    updateButtonVisibility();
    
    // 最初の入力欄にフォーカス
    newRow.querySelector('input[name="number"]').focus();
}

// モバイル用新規行追加
function addNewRowMobile() {
    const container = document.getElementById('mobileCardContainer');
    
    newRowCounter++;
    const newId = 'new_' + newRowCounter;
    
    const newCard = document.createElement('div');
    newCard.className = 'card mb-3 shadow-sm mobile-card';
    newCard.setAttribute('data-id', newId);
    newCard.setAttribute('data-mode', 'new');
    
    newCard.innerHTML = `
        <div class="card-body">
            <div class="mb-2">
                <label class="form-label small mb-1">補助科目番号 <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="number" 
                       placeholder="番号を入力"
                       min="0" step="1"
                       required>
            </div>
            <div class="mb-2">
                <label class="form-label small mb-1">補助科目名称 <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="name" 
                       placeholder="名称を入力"
                       maxlength="100"
                       required>
            </div>
            <div class="mb-3">
                <label class="form-label small mb-1">ステータス</label>
                <select class="form-select form-select-sm" name="is_active">
                    <option value="1" selected>有効</option>
                    <option value="0">無効</option>
                </select>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success flex-fill mobile-save-btn">
                    <i class="fas fa-check me-1"></i>保存
                </button>
                <button type="button" class="btn btn-sm btn-secondary flex-fill mobile-cancel-btn">
                    <i class="fas fa-times me-1"></i>キャンセル
                </button>
            </div>
        </div>
    `;
    
    container.insertBefore(newCard, container.firstChild);
    
    // イベントリスナー追加
    newCard.querySelector('.mobile-save-btn').addEventListener('click', function() {
        saveRowMobile(newCard);
    });
    
    newCard.querySelector('.mobile-cancel-btn').addEventListener('click', function() {
        cancelEditMobile(newCard);
    });
}

// 編集モードに入る（デスクトップ）
function enterEditMode(row) {
    const id = row.getAttribute('data-id');
    
    if (editingRows.has(id)) {
        return; // 既に編集中
    }
    
    row.setAttribute('data-mode', 'edit');
    row.querySelectorAll('.view-mode').forEach(el => el.style.display = 'none');
    row.querySelectorAll('.edit-mode').forEach(el => el.style.display = '');
    
    editingRows.add(id);
    updateButtonVisibility();
}

// 編集モードに入る（モバイル）
function enterEditModeMobile(card) {
    const id = card.getAttribute('data-id');
    
    if (editingRows.has(id)) {
        return;
    }
    
    card.setAttribute('data-mode', 'edit');
    card.querySelector('.view-mode').style.display = 'none';
    card.querySelector('.edit-mode').style.display = 'block';
    
    editingRows.add(id);
    updateButtonVisibility();
}

// 行を保存
function saveRow(row) {
    const id = row.getAttribute('data-id');
    const isNew = id.startsWith('new_');
    
    // バリデーション
    const number = row.querySelector('input[name="number"]').value.trim();
    const name = row.querySelector('input[name="name"]').value.trim();
    
    if (!number || !name) {
        alert('補助科目番号と名称は必須です。');
        return;
    }
    
    // データ収集
    const data = {
        number: parseInt(number, 10),
        name: name,
        is_active: row.querySelector('[name="is_active"]').value,
        csrf_token: CSRF_TOKEN
    };
    
    // 保存中表示
    const saveBtn = row.querySelector('.save-btn');
    const originalHtml = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Ajax送信
    const url = isNew 
        ? BASE_URL + 'subsidiary_subjects/createAjax'
        : BASE_URL + 'subsidiary_subjects/updateAjax/' + id;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (isNew) {
                // 新規作成の場合、IDを更新
                row.setAttribute('data-id', result.id);
                row.setAttribute('data-mode', 'view');
                
                // 表示モード用のHTMLを生成
                updateRowToViewMode(row, data, result.updated_at);
            } else {
                // 更新の場合、表示モードに戻す
                exitEditMode(row, data);
            }
            
            editingRows.delete(id);
            if (isNew) {
                editingRows.delete('new_' + newRowCounter);
            }
            updateButtonVisibility();
            
            showToast('success', result.message || '保存しました');
        } else {
            alert('エラー: ' + result.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('保存に失敗しました');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalHtml;
    });
}

// 行を保存（モバイル）
function saveRowMobile(card) {
    const id = card.getAttribute('data-id');
    const isNew = id.startsWith('new_');
    
    // バリデーション
    const number = card.querySelector('input[name="number"]').value.trim();
    const name = card.querySelector('input[name="name"]').value.trim();
    
    if (!number || !name) {
        alert('補助科目番号と名称は必須です。');
        return;
    }
    
    // データ収集
    const data = {
        number: parseInt(number, 10),
        name: name,
        is_active: card.querySelector('[name="is_active"]').value,
        csrf_token: CSRF_TOKEN
    };
    
    // 保存中表示
    const saveBtn = card.querySelector('.mobile-save-btn');
    const originalHtml = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>保存中...';
    
    // Ajax送信
    const url = isNew 
        ? BASE_URL + 'subsidiary_subjects/createAjax'
        : BASE_URL + 'subsidiary_subjects/updateAjax/' + id;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (isNew) {
                // 新規作成の場合は画面をリロード
                location.reload();
            } else {
                // 更新の場合、表示モードに戻す
                exitEditModeMobile(card, data);
            }
            
            editingRows.delete(id);
            updateButtonVisibility();
            
            showToast('success', result.message || '保存しました');
        } else {
            alert('エラー: ' + result.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('保存に失敗しました');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalHtml;
    });
}

// 表示モードに更新
function updateRowToViewMode(row, data, updatedAt) {
    const isActive = data.is_active == 1;
    const badge = isActive 
        ? '<span class="badge bg-success">有効</span>'
        : '<span class="badge bg-secondary">無効</span>';
    
    row.innerHTML = `
        <td>
            <span class="view-mode">
                <code>${escapeHtml(data.number)}</code>
            </span>
            <input type="text" 
                   class="form-control form-control-sm edit-mode" 
                   name="number" 
                   value="${escapeHtml(data.number)}"
                   min="0" step="1"
                   style="display: none;"
                   data-original="${escapeHtml(data.number)}">
        </td>
        <td>
            <span class="view-mode">
                ${escapeHtml(data.name)}
            </span>
            <input type="text" 
                   class="form-control form-control-sm edit-mode" 
                   name="name" 
                   value="${escapeHtml(data.name)}"
                   maxlength="100"
                   style="display: none;"
                   data-original="${escapeHtml(data.name)}">
        </td>
        <td class="text-center">
            <span class="view-mode">
                ${badge}
            </span>
            <select class="form-select form-select-sm edit-mode" 
                    name="is_active" 
                    style="display: none;"
                    data-original="${data.is_active}">
                <option value="1" ${isActive ? 'selected' : ''}>有効</option>
                <option value="0" ${!isActive ? 'selected' : ''}>無効</option>
            </select>
        </td>
        <td>
            <small class="text-muted">
                ${formatDateTime(updatedAt)}
            </small>
        </td>
        <td class="text-center">
            <div class="btn-group btn-group-sm view-mode" role="group">
                <button type="button" class="btn btn-outline-primary edit-btn" title="編集">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" 
                        class="btn btn-outline-danger delete-btn" 
                        title="削除">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="btn-group btn-group-sm edit-mode" role="group" style="display: none;">
                <button type="button" class="btn btn-success save-btn" title="保存">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" class="btn btn-secondary cancel-btn" title="キャンセル">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </td>
    `;
    
    // イベントリスナーを再設定
    row.querySelector('.edit-btn').addEventListener('click', function() {
        enterEditMode(row);
    });
    
    row.querySelector('.save-btn').addEventListener('click', function() {
        saveRow(row);
    });
    
    row.querySelector('.cancel-btn').addEventListener('click', function() {
        cancelEdit(row);
    });
    
    row.querySelector('.delete-btn').addEventListener('click', function() {
        deleteRow(row);
    });
}

// 編集モードを終了
function exitEditMode(row, data) {
    row.setAttribute('data-mode', 'view');
    
    // 表示用の値を更新
    const codeSpan = row.querySelector('td:nth-child(2) .view-mode');
    codeSpan.innerHTML = '<code>' + escapeHtml(data.number) + '</code>';
    
    const nameSpan = row.querySelector('td:nth-child(3) .view-mode');
    nameSpan.textContent = data.name;
    
    const statusSpan = row.querySelector('td:nth-child(4) .view-mode');
    const isActive = data.is_active == 1;
    statusSpan.innerHTML = isActive 
        ? '<span class="badge bg-success">有効</span>'
        : '<span class="badge bg-secondary">無効</span>';
    
    // 表示モードに切り替え
    row.querySelectorAll('.view-mode').forEach(el => el.style.display = '');
    row.querySelectorAll('.edit-mode').forEach(el => el.style.display = 'none');
    
    // オリジナル値を更新
    row.querySelector('input[name="number"]').setAttribute('data-original', data.number);
    row.querySelector('input[name="name"]').setAttribute('data-original', data.name);
    row.querySelector('select[name="is_active"]').setAttribute('data-original', data.is_active);
}

// 編集モードを終了（モバイル）
function exitEditModeMobile(card, data) {
    card.setAttribute('data-mode', 'view');
    
    // 表示用の値を更新
    const viewMode = card.querySelector('.view-mode');
    const codeElement = viewMode.querySelector('code');
    const nameElement = viewMode.querySelector('.fw-bold');
    const badgeElement = viewMode.querySelector('.badge');
    
    codeElement.textContent = data.number;
    nameElement.textContent = data.name;
    
    const isActive = data.is_active == 1;
    badgeElement.className = isActive ? 'badge bg-success' : 'badge bg-secondary';
    badgeElement.textContent = isActive ? '有効' : '無効';
    
    // 表示モードに切り替え
    viewMode.style.display = 'block';
    card.querySelector('.edit-mode').style.display = 'none';
    
    // オリジナル値を更新
    card.querySelector('input[name="number"]').setAttribute('data-original', data.number);
    card.querySelector('input[name="name"]').setAttribute('data-original', data.name);
    card.querySelector('select[name="is_active"]').setAttribute('data-original', data.is_active);
}

// 編集をキャンセル
function cancelEdit(row) {
    const id = row.getAttribute('data-id');
    
    if (id.startsWith('new_')) {
        // 新規行の場合は削除
        row.remove();
        
        // モバイル版も削除
        const mobileCard = document.querySelector(`.mobile-card[data-id="${id}"]`);
        if (mobileCard) {
            mobileCard.remove();
        }
    } else {
        // 既存行の場合は元の値に戻す
        row.setAttribute('data-mode', 'view');
        
        row.querySelectorAll('input, select').forEach(input => {
            const original = input.getAttribute('data-original');
            if (original !== null) {
                input.value = original;
            }
        });
        
        row.querySelectorAll('.view-mode').forEach(el => el.style.display = '');
        row.querySelectorAll('.edit-mode').forEach(el => el.style.display = 'none');
    }
    
    editingRows.delete(id);
    updateButtonVisibility();
}

// 編集をキャンセル（モバイル）
function cancelEditMobile(card) {
    const id = card.getAttribute('data-id');
    
    if (id.startsWith('new_')) {
        // 新規カードの場合は削除
        card.remove();
        
        // デスクトップ版も削除
        const desktopRow = document.querySelector(`tr[data-id="${id}"]`);
        if (desktopRow) {
            desktopRow.remove();
        }
    } else {
        // 既存カードの場合は元の値に戻す
        card.setAttribute('data-mode', 'view');
        
        const editMode = card.querySelector('.edit-mode');
        editMode.querySelectorAll('input, select').forEach(input => {
            const original = input.getAttribute('data-original');
            if (original !== null) {
                input.value = original;
            }
        });
        
        card.querySelector('.view-mode').style.display = 'block';
        editMode.style.display = 'none';
    }
    
    editingRows.delete(id);
    updateButtonVisibility();
}

// すべての編集をキャンセル
function cancelAllEdits() {
    if (!confirm('すべての編集中のデータをキャンセルしてもよろしいですか？')) {
        return;
    }
    
    // デスクトップ版
    document.querySelectorAll('tr[data-mode="edit"], tr[data-mode="new"]').forEach(row => {
        cancelEdit(row);
    });
    
    // モバイル版
    document.querySelectorAll('.mobile-card[data-mode="edit"], .mobile-card[data-mode="new"]').forEach(card => {
        cancelEditMobile(card);
    });
    
    editingRows.clear();
    updateButtonVisibility();
}

// すべて保存
function saveAllChanges() {
    const rows = document.querySelectorAll('tr[data-mode="edit"], tr[data-mode="new"]');
    
    if (rows.length === 0) {
        return;
    }
    
    if (!confirm(`${rows.length}件の変更を保存しますか？`)) {
        return;
    }
    
    rows.forEach(row => {
        saveRow(row);
    });
}

// 行を削除
function deleteRow(row) {
    const id = row.getAttribute('data-id');
    const name = row.querySelector('input[name="name"]').value || 
                 row.querySelector('.view-mode:nth-child(3)').textContent.trim();
    
    if (!confirm(`「${name}」を削除してもよろしいですか？\n\n※この操作は取り消せません。`)) {
        return;
    }
    
    fetch(BASE_URL + 'subsidiary_subjects/deleteAjax/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({ csrf_token: CSRF_TOKEN })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            row.remove();
            
            // モバイル版も削除
            const mobileCard = document.querySelector(`.mobile-card[data-id="${id}"]`);
            if (mobileCard) {
                mobileCard.remove();
            }
            
            editingRows.delete(id);
            updateButtonVisibility();
            
            showToast('success', '削除しました');
        } else {
            alert('エラー: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('削除に失敗しました');
    });
}

// 行を削除（モバイル）
function deleteRowMobile(card) {
    const id = card.getAttribute('data-id');
    const name = card.querySelector('input[name="name"]').value || 
                 card.querySelector('.fw-bold').textContent.trim();
    
    if (!confirm(`「${name}」を削除してもよろしいですか？\n\n※この操作は取り消せません。`)) {
        return;
    }
    
    fetch(BASE_URL + 'subsidiary_subjects/deleteAjax/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({ csrf_token: CSRF_TOKEN })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            card.remove();
            
            // デスクトップ版も削除
            const desktopRow = document.querySelector(`tr[data-id="${id}"]`);
            if (desktopRow) {
                desktopRow.remove();
            }
            
            editingRows.delete(id);
            updateButtonVisibility();
            
            showToast('success', '削除しました');
        } else {
            alert('エラー: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('削除に失敗しました');
    });
}

// ボタンの表示/非表示を更新
function updateButtonVisibility() {
    const hasEditing = editingRows.size > 0;
    
    document.getElementById('saveAllBtn').style.display = hasEditing ? 'block' : 'none';
    document.getElementById('cancelAllBtn').style.display = hasEditing ? 'block' : 'none';
}

// 統計情報を更新
// トースト通知を表示
function showToast(type, message) {
    // 簡易的なトースト表示（必要に応じてBootstrap Toastに置き換え）
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// HTMLエスケープ
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 日時フォーマット
function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const i = String(date.getMinutes()).padStart(2, '0');
    return `${y}/${m}/${d} ${h}:${i}`;
}
</script>