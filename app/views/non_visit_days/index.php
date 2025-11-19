<?php
// app/views/non_visit_days/index.php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-calendar-times me-2 text-warning"></i>非訪問日管理</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('contracts') ?>">契約一覧</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url("contracts/{$contract['id']}/edit") ?>">契約編集</a></li>
                <li class="breadcrumb-item active">非訪問日管理</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url("contracts/{$contract['id']}/edit") ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>契約編集に戻る
        </a>
    </div>
</div>

<!-- 契約情報サマリー -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-primary bg-opacity-10 border-primary">
            <div class="card-header bg-primary bg-opacity-20">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-file-contract me-2"></i>契約情報
                </h5>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-3">
                        <strong>企業・拠点:</strong><br>
                        <span class="text-primary"><?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?></span><br>
                        <span class="text-secondary"><?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>産業医:</strong><br>
                        <span class="text-primary"><?= htmlspecialchars($contract['doctor_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>訪問頻度:</strong><br>
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-calendar-week me-1"></i>毎週
                        </span>
                        <span class="badge bg-<?= $contract['exclude_holidays'] ? 'warning' : 'info' ?> ms-1">
                            <?= $contract['exclude_holidays'] ? '祝日非訪問' : '祝日訪問可' ?>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>1回あたりの時間:</strong><br>
                        <span class="text-primary fw-bold"><?= $contract['regular_visit_hours'] ?>時間</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 年度選択とアクション -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>年度選択</h6>
            </div>
            <div class="card-body py-3">
                <form method="GET" action="<?= base_url("non_visit_days/{$contract['id']}") ?>" class="d-flex align-items-end gap-2">
                    <div class="flex-grow-1">
                        <label for="year" class="form-label">対象年度</label>
                        <select class="form-select" id="year" name="year" onchange="this.form.submit()">
                            <?php foreach ($yearOptions as $yearOption): ?>
                                <option value="<?= $yearOption ?>" <?= ($year == $yearOption) ? 'selected' : '' ?>>
                                    <?= $yearOption ?>年
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>非訪問日追加</h6>
            </div>
            <div class="card-body py-3">
                <div class="d-flex gap-2">
                    <a href="<?= base_url("non_visit_days/{$contract['id']}/create?year={$year}") ?>" 
                       class="btn btn-primary flex-grow-1">
                        <i class="fas fa-plus me-1"></i>新規登録
                    </a>
                    <?php if (!empty($prevYearDays)): ?>
                    <button type="button" class="btn btn-outline-info" onclick="copyFromPreviousYear()">
                        <i class="fas fa-copy me-1"></i>前年からコピー
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 非訪問日一覧 -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i><?= $year ?>年の非訪問日一覧
                </h5>
                <?php if (!empty($nonVisitDays)): ?>
                <div class="d-flex gap-2">
                    <a href="<?= base_url("non_visit_days/{$contract['id']}/statistics") ?>" 
                       class="btn btn-sm btn-outline-info">
                        <i class="fas fa-chart-bar me-1"></i>統計
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($nonVisitDays)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-check text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3"><?= $year ?>年の非訪問日は登録されていません</h5>
                        <p class="text-muted">年末年始、お盆休み、創立記念日などの非訪問日を登録してください。</p>
                        <div class="mt-4">
                            <a href="<?= base_url("non_visit_days/{$contract['id']}/create?year={$year}") ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>最初の非訪問日を登録
                            </a>
                            <?php if (!empty($prevYearDays)): ?>
                            <button type="button" class="btn btn-outline-info ms-2" onclick="copyFromPreviousYear()">
                                <i class="fas fa-copy me-1"></i>前年のデータをコピー
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- 過去年の繰り返し設定に関する説明 -->
                    <?php 
                    $pastRecurringCount = 0;
                    foreach ($nonVisitDays as $day) {
                        if (isset($day['is_from_past_recurring']) && $day['is_from_past_recurring']) {
                            $pastRecurringCount++;
                        }
                    }
                    ?>
                    
                    <?php if ($pastRecurringCount > 0): ?>
                    <div class="alert alert-info alert-dismissible fade show mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>お知らせ:</strong> 過去年の繰り返し設定から<?= $pastRecurringCount ?>件の非訪問日が表示されています。
                        これらは編集・削除できません。変更が必要な場合は、新規登録してください。
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="120">日付</th>
                                    <th width="80">曜日</th>
                                    <th>説明</th>
                                    <th width="100">種別</th>
                                    <th width="100">状態</th>
                                    <th width="100">日数</th>
                                    <th width="150">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nonVisitDays as $day): ?>
                                <tr class="<?= $day['days_until'] !== null && $day['days_until'] <= 7 ? 'table-warning' : '' ?>
                                           <?= isset($day['is_from_past_recurring']) && $day['is_from_past_recurring'] ? 'table-info' : '' ?>">
                                    <td>
                                        <strong><?= $day['formatted_date'] ?></strong><br>
                                        <small class="text-muted"><?= date('Y-m-d', strtotime($day['non_visit_date'])) ?></small>
                                        <?php if (isset($day['is_from_past_recurring']) && $day['is_from_past_recurring']): ?>
                                        <br><small class="text-info">
                                            <i class="fas fa-history me-1"></i><?= $day['original_year'] ?>年設定
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $day['day_of_week_name'] ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($day['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($day['created_by_name'])): ?>
                                        <small class="text-muted">
                                            登録者: <?= htmlspecialchars($day['created_by_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                        <?php endif; ?>
                                        <?php if (isset($day['is_from_past_recurring']) && $day['is_from_past_recurring']): ?>
                                        <br><small class="text-info">
                                            <i class="fas fa-info-circle me-1"></i>過去年の繰り返し設定より表示
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($day['is_recurring']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-repeat me-1"></i>毎年
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-calendar-day me-1"></i>単発
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($day['is_from_past_recurring']) && $day['is_from_past_recurring']): ?>
                                        <br><span class="badge bg-info mt-1">
                                            <i class="fas fa-history me-1"></i>過去設定
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($day['can_edit']) && !$day['can_edit']): ?>
                                        <!-- 過去年繰り返し設定の場合は状態変更不可 -->
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> 有効
                                        </span>
                                        <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-<?= $day['is_active'] ? 'success' : 'secondary' ?>" 
                                                onclick="toggleActive(<?= $day['id'] ?>)"
                                                title="<?= $day['is_active'] ? '有効' : '無効' ?>">
                                            <i class="fas fa-<?= $day['is_active'] ? 'check' : 'times' ?>"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($day['days_until'] !== null): ?>
                                        <span class="badge bg-<?= $day['days_until'] <= 7 ? 'warning' : 'info' ?>">
                                            <?= $day['days_until'] ?>日後
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($day['can_edit']) && !$day['can_edit']): ?>
                                        <!-- 過去年繰り返し設定の場合は編集・削除不可 -->
                                        <div class="btn-group btn-group-sm">
                                            <span class="btn btn-outline-secondary disabled" title="編集不可">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                            <span class="btn btn-outline-secondary disabled" title="削除不可">
                                                <i class="fas fa-trash"></i>
                                            </span>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-info">
                                                <i class="fas fa-info-circle me-1"></i>過去設定
                                            </small>
                                        </div>
                                        <?php else: ?>
                                        <!-- 通常の編集・削除ボタン -->
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url("non_visit_days/{$contract['id']}/{$day['id']}/edit") ?>" 
                                               class="btn btn-outline-primary" title="編集">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="deleteNonVisitDay(<?= $day['id'] ?>, '<?= htmlspecialchars($day['description'], ENT_QUOTES, 'UTF-8') ?>')"
                                                    title="削除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php endif; ?>
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

<!-- 前年データコピー用のフォーム（非表示） -->
<form id="copy-form" method="POST" action="<?= base_url("non_visit_days/{$contract['id']}/copy-from-previous-year") ?>" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="target_year" value="<?= $year ?>">
</form>

<!-- 削除用のフォーム（非表示） -->
<form id="delete-form" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="_method" value="DELETE">
</form>

<script>
// 前年データコピー
function copyFromPreviousYear() {
    const prevYear = <?= $year - 1 ?>;
    if (confirm(`${prevYear}年の繰り返し設定データを<?= $year ?>年にコピーしますか？\n\n既に同じ日付のデータがある場合はスキップされます。`)) {
        document.getElementById('copy-form').submit();
    }
}

// 有効/無効切り替え
function toggleActive(id) {
    if (confirm('この非訪問日の有効/無効を切り替えますか？')) {
        // Ajax実装またはフォーム送信
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?= base_url("non_visit_days/{$contract['id']}") ?>/${id}/toggle-active`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 削除
function deleteNonVisitDay(id, description) {
    if (confirm(`非訪問日「${description}」を削除しますか？\n\n削除されたデータは復元できません。`)) {
        const form = document.getElementById('delete-form');
        form.action = `<?= base_url("non_visit_days/{$contract['id']}") ?>/${id}/delete`;
        form.submit();
    }
}

// ページ読み込み時の処理
document.addEventListener('DOMContentLoaded', function() {
    // 30日以内の非訪問日をハイライト表示
    const warningRows = document.querySelectorAll('.table-warning');
    if (warningRows.length > 0) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>お知らせ:</strong> 30日以内に予定されている非訪問日が${warningRows.length}件あります。
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.card').insertBefore(alertDiv, document.querySelector('.card-body'));
    }
    
    // 過去年繰り返し設定の説明ツールチップ
    const pastRecurringElements = document.querySelectorAll('[title="過去設定"]');
    pastRecurringElements.forEach(element => {
        // Bootstrap tooltipの初期化（必要に応じて）
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(element);
        }
    });
});
</script>