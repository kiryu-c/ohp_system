<?php
// app/views/service_templates/edit.php - DB構造変更対応版
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit me-2 text-primary"></i>テンプレート編集</h2>
    <div>
        <a href="<?= base_url('service_templates') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>一覧に戻る
        </a>
    </div>
</div>

<!-- テンプレート情報表示 -->
<div class="card border-primary mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-info-circle me-2"></i>テンプレート情報
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="120">作成日時:</th>
                        <td><?= date('Y年m月d日 H:i', strtotime($template['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <th>更新日時:</th>
                        <td><?= date('Y年m月d日 H:i', strtotime($template['updated_at'])) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="120">使用回数:</th>
                        <td>
                            <span class="badge bg-info"><?= $template['usage_count'] ?? 0 ?>回</span>
                        </td>
                    </tr>
                    <tr>
                        <th>最終使用:</th>
                        <td>
                            <?php if ($template['last_used_at']): ?>
                                <?= date('Y年m月d日 H:i', strtotime($template['last_used_at'])) ?>
                            <?php else: ?>
                                <span class="text-muted">未使用</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 編集フォーム -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>テンプレート編集
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $_SESSION['flash_type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php 
            unset($_SESSION['flash_message'], $_SESSION['flash_type']); 
            ?>
        <?php endif; ?>

        <form method="POST" action="<?= base_url("service_templates/{$template['id']}") ?>" id="editTemplateForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="mb-3">
                <label for="content" class="form-label">
                    <i class="fas fa-file-alt me-1"></i>役務内容 <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" 
                          id="content" 
                          name="content" 
                          rows="8" 
                          required 
                          maxlength="1000"
                          placeholder="役務内容のテンプレートを入力してください&#13;&#10;例：&#13;&#10;・健康診断結果の確認と指導&#13;&#10;・職場巡視の実施&#13;&#10;・労働者からの健康相談対応&#13;&#10;・安全衛生委員会への出席&#13;&#10;・労働環境の評価と改善提案"><?= htmlspecialchars($template['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text">
                    最大1000文字まで入力できます。よく実施する役務内容を記載してください。
                </div>
                <div class="invalid-feedback">
                    役務内容を入力してください。
                </div>
                
                <!-- 文字数カウンター -->
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>改行や句読点を含めた内容で、具体的に記載すると使いやすくなります
                    </small>
                    <small class="text-muted">
                        <span id="char-count">0</span>/1000文字
                    </small>
                </div>
            </div>

            <!-- プレビュー機能 -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">
                        <i class="fas fa-eye me-1"></i>プレビュー
                    </label>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="togglePreview()">
                        <i class="fas fa-eye me-1"></i>
                        <span id="preview-toggle-text">プレビューを表示</span>
                    </button>
                </div>
                <div id="preview-area" class="border rounded p-3 bg-light" style="display: none;">
                    <div class="text-muted mb-2">
                        <i class="fas fa-file-alt me-1"></i>テンプレート内容のプレビュー:
                    </div>
                    <div id="preview-content" class="bg-white p-3 rounded border">
                        <!-- プレビュー内容がここに表示されます -->
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                        <i class="fas fa-undo me-1"></i>元に戻す
                    </button>
                    <button type="button" class="btn btn-outline-primary ms-2" onclick="saveDraft()">
                        <i class="fas fa-save me-1"></i>下書き保存
                    </button>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>変更を保存
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 削除フォーム -->
<div class="card border-danger mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0 text-danger">
            <i class="fas fa-trash-alt me-2"></i>テンプレートの削除
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>注意:</strong> テンプレートを削除すると元に戻すことはできません。
        </div>
        
        <form method="POST" action="<?= base_url("service_templates/{$template['id']}/delete") ?>" onsubmit="return confirmDelete()">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-0">
                        このテンプレート
                        <strong>「<?= htmlspecialchars(mb_substr($template['content'], 0, 50), ENT_QUOTES, 'UTF-8') ?>...」</strong>
                        を削除しますか？
                    </p>
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-1"></i>削除する
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.invalid-feedback {
    display: block;
}

.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control.is-valid {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

#preview-area {
    transition: all 0.3s ease;
}

#preview-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    min-height: 100px;
    font-family: inherit;
}

.char-count-warning {
    color: #ffc107 !important;
}

.char-count-danger {
    color: #dc3545 !important;
}

/* 下書き保存ボタンのアニメーション */
.btn-draft-saving {
    pointer-events: none;
}

.btn-draft-saved {
    background-color: #198754;
    border-color: #198754;
    color: white;
}

@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between > div {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<script>
// 元の値を保存（リセット用） - contentフィールドに変更
const originalValues = {
    content: document.getElementById('content').value
};

// フォーム送信フラグ
let isSubmittingForm = false;

// 文字数カウンター
function updateCharCount() {
    const textarea = document.getElementById('content');
    const charCount = document.getElementById('char-count');
    const currentLength = textarea.value.length;
    const maxLength = 1000;
    
    charCount.textContent = currentLength;
    
    // 文字数に応じて色を変更
    charCount.className = 'text-muted';
    if (currentLength > maxLength * 0.9) {
        charCount.className = 'char-count-danger';
    } else if (currentLength > maxLength * 0.8) {
        charCount.className = 'char-count-warning';
    }
}

// プレビュー表示切り替え
function togglePreview() {
    const previewArea = document.getElementById('preview-area');
    const previewContent = document.getElementById('preview-content');
    const toggleText = document.getElementById('preview-toggle-text');
    const content = document.getElementById('content').value;
    
    if (previewArea.style.display === 'none') {
        // プレビューを表示
        previewContent.textContent = content || 'プレビューする内容がありません';
        previewArea.style.display = 'block';
        toggleText.textContent = 'プレビューを非表示';
    } else {
        // プレビューを非表示
        previewArea.style.display = 'none';
        toggleText.textContent = 'プレビューを表示';
    }
}

// フォームリセット
function resetForm() {
    if (confirm('入力内容を元に戻しますか？未保存の変更は失われます。')) {
        document.getElementById('content').value = originalValues.content;
        
        // バリデーション状態をリセット
        clearValidation();
        updateCharCount();
        
        // プレビューエリアを非表示
        const previewArea = document.getElementById('preview-area');
        if (previewArea.style.display !== 'none') {
            togglePreview();
        }
    }
}

// 下書き保存
function saveDraft() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // ボタンの状態を変更
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>保存中...';
    button.classList.add('btn-draft-saving');
    
    // LocalStorageに保存
    const draftData = {
        content: document.getElementById('content').value,
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem(`template_draft_${<?= $template['id'] ?>}`, JSON.stringify(draftData));
    
    // 成功表示
    setTimeout(() => {
        button.innerHTML = '<i class="fas fa-check me-1"></i>保存完了';
        button.classList.remove('btn-draft-saving', 'btn-outline-primary');
        button.classList.add('btn-draft-saved');
        
        // 2秒後に元に戻す
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-draft-saved');
            button.classList.add('btn-outline-primary');
        }, 2000);
    }, 500);
}

// 下書きの読み込み
function loadDraft() {
    const draftKey = `template_draft_${<?= $template['id'] ?>}`;
    const draftData = localStorage.getItem(draftKey);
    
    if (draftData) {
        try {
            const data = JSON.parse(draftData);
            const draftDate = new Date(data.timestamp);
            const daysDiff = Math.floor((new Date() - draftDate) / (1000 * 60 * 60 * 24));
            
            if (daysDiff <= 7 && confirm(`${daysDiff}日前の下書きが見つかりました。読み込みますか？`)) {
                document.getElementById('content').value = data.content;
                updateCharCount();
            }
        } catch (error) {
            console.error('下書き読み込みエラー:', error);
        }
    }
}

// バリデーション
function validateForm() {
    let isValid = true;
    
    // 役務内容のバリデーション
    const content = document.getElementById('content');
    if (!content.value.trim()) {
        content.classList.add('is-invalid');
        isValid = false;
    } else {
        content.classList.remove('is-invalid');
        content.classList.add('is-valid');
    }
    
    return isValid;
}

// バリデーション状態をクリア
function clearValidation() {
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.classList.remove('is-invalid', 'is-valid');
    });
}

// 削除確認
function confirmDelete() {
    const contentPreview = '<?= htmlspecialchars(mb_substr($template['content'], 0, 50), ENT_QUOTES, 'UTF-8') ?>...';
    
    let message = `テンプレート「${contentPreview}」を削除しますか？\n\n`;
    message += `削除すると元に戻すことはできません。`;
    
    return confirm(message);
}

// 変更チェック
function hasChanges() {
    const currentValues = {
        content: document.getElementById('content').value
    };
    
    return originalValues.content !== currentValues.content;
}

// beforeunloadハンドラー
function beforeUnloadHandler(e) {
    if (isSubmittingForm) {
        return undefined;
    }
    
    if (hasChanges()) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
}

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // 文字数カウンターの初期化
    updateCharCount();
    
    // 下書きの読み込み確認
    loadDraft();
    
    // イベントリスナーの設定
    document.getElementById('content').addEventListener('input', updateCharCount);
    
    // リアルタイムバリデーション
    document.getElementById('content').addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    });
    
    // フォーム送信時の処理
    document.getElementById('editTemplateForm').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        isSubmittingForm = true;
        
        // 送信ボタンを無効化
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>保存中...';
        
        // 下書きを削除
        localStorage.removeItem(`template_draft_${<?= $template['id'] ?>}`);
        
        // beforeunloadイベントを無効化
        window.removeEventListener('beforeunload', beforeUnloadHandler);
    });
    
    // 自動保存（5分ごと）
    setInterval(() => {
        if (hasChanges() && !isSubmittingForm) {
            const draftData = {
                content: document.getElementById('content').value,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem(`template_draft_${<?= $template['id'] ?>}`, JSON.stringify(draftData));
        }
    }, 5 * 60 * 1000); // 5分
});

// ページを離れる前の確認
window.addEventListener('beforeunload', beforeUnloadHandler);

// ページの可視性変更時の処理
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden' && hasChanges() && !isSubmittingForm) {
        // ページが非表示になる時に自動保存
        const draftData = {
            content: document.getElementById('content').value,
            timestamp: new Date().toISOString()
        };
        localStorage.setItem(`template_draft_${<?= $template['id'] ?>}`, JSON.stringify(draftData));
    }
});
</script>