<?php
// app/views/service_templates/index.php - DB構造変更対応版
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-clipboard-list me-2"></i>役務内容テンプレート管理</h2>
                <div>
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                        <i class="fas fa-plus me-1"></i>新規テンプレート作成
                    </button>
                    <a href="<?= base_url('service_records') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>役務記録に戻る
                    </a>
                </div>
            </div>
            
            <!-- 統計情報 -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">システムテンプレート</h6>
                                    <h3 class="mb-0"><?= $stats['system_count'] ?? 0 ?>件</h3>
                                </div>
                                <i class="fas fa-cogs fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">個人テンプレート</h6>
                                    <h3 class="mb-0"><?= $stats['personal_count'] ?? 0 ?>件</h3>
                                </div>
                                <i class="fas fa-user-edit fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 個人テンプレート一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>個人テンプレート一覧</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($personalTemplates)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted">個人テンプレートがまだ作成されていません。</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                            <i class="fas fa-plus me-1"></i>最初のテンプレートを作成
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>テンプレート内容</th>
                                    <th>使用回数</th>
                                    <th>作成日</th>
                                    <th>最終使用日</th>
                                    <th width="120">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personalTemplates as $template): ?>
                                <tr>
                                    <td>
                                        <div class="description-preview">
                                            <?= htmlspecialchars(mb_substr($template['content'], 0, 100), ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (mb_strlen($template['content']) > 100): ?>
                                                <span class="text-muted">...</span>
                                                <button type="button" class="btn btn-link btn-sm p-0 ms-1" 
                                                        onclick="showFullDescription('テンプレート詳細', '<?= htmlspecialchars($template['content'], ENT_QUOTES, 'UTF-8') ?>')">
                                                    詳細
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $template['usage_count'] ?? 0 ?>回</span>
                                    </td>
                                    <td>
                                        <small><?= format_date($template['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if ($template['last_used_at']): ?>
                                                <?= format_date($template['last_used_at']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">未使用</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url("service_templates/{$template['id']}/edit") ?>" 
                                               class="btn btn-outline-primary btn-sm" title="編集">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="confirmDelete(<?= $template['id'] ?>, '<?= htmlspecialchars(mb_substr($template['content'], 0, 30), ENT_QUOTES, 'UTF-8') ?>...')" 
                                                    title="削除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 新規テンプレート作成モーダル -->
<div class="modal fade" id="createTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新規テンプレート作成</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createTemplateForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">役務内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="6" 
                                  maxlength="1000" required 
                                  placeholder="役務内容を詳細に入力してください。&#10;例: 作業場を巡視し、作業環境や労働衛生状況の確認・指導を実施しました。"></textarea>
                        <div class="form-text">
                            最大1000文字まで入力可能です。このテンプレートは個人テンプレートとして保存されます。
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>テンプレート保存について</strong><br>
                        <small>
                            ・入力した内容がそのまま個人テンプレートとして保存されます<br>
                            ・保存後は役務記録作成時に簡単に呼び出せます<br>
                            ・テンプレートは後から編集・削除が可能です
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 詳細表示モーダル -->
<div class="modal fade" id="descriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="descriptionModalTitle">テンプレート詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="descriptionModalContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">テンプレート削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>以下のテンプレートを削除しますか？</p>
                <div class="alert alert-warning">
                    <strong id="deleteTemplateName"></strong>
                </div>
                <p class="text-danger small">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    削除したテンプレートは復元できません。
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>削除する
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.description-preview {
    max-width: 300px;
    word-wrap: break-word;
}

.table td {
    vertical-align: middle;
}

.btn-group-sm .btn {
    padding: 0.2rem 0.4rem;
}

.opacity-75 {
    opacity: 0.75;
}

@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
    }
    
    .description-preview {
        max-width: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // テンプレート作成フォームの処理
    const createForm = document.getElementById('createTemplateForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // ボタンを無効化
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>保存中...';
            
            fetch('<?= base_url("api/service_templates/save") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 成功時
                    bootstrap.Modal.getInstance(document.getElementById('createTemplateModal')).hide();
                    
                    // フラッシュメッセージを表示
                    showFlashMessage('success', data.message);
                    
                    // ページをリロード
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // エラー時
                    showFlashMessage('error', data.error);
                }
            })
            .catch(error => {
                console.error('Template save error:', error);
                showFlashMessage('error', 'テンプレートの保存中にエラーが発生しました。');
            })
            .finally(() => {
                // ボタンを復元
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>保存';
            });
        });
    }
});

// 詳細表示モーダル
function showFullDescription(title, content) {
    document.getElementById('descriptionModalTitle').textContent = title;
    document.getElementById('descriptionModalContent').innerHTML = 
        '<div class="border rounded p-3 bg-light" style="white-space: pre-wrap;">' + 
        content.replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
        '</div>';
    
    const modal = new bootstrap.Modal(document.getElementById('descriptionModal'));
    modal.show();
}

// 削除確認
function confirmDelete(templateId, templatePreview) {
    document.getElementById('deleteTemplateName').textContent = templatePreview;
    document.getElementById('deleteForm').action = `<?= base_url('service_templates') ?>/${templateId}/delete`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

// フラッシュメッセージ表示
function showFlashMessage(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // 5秒後に自動削除
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>