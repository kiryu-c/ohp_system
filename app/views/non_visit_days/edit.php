<?php
// app/views/non_visit_days/edit.php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-edit me-2 text-warning"></i>非訪問日編集</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('contracts') ?>">契約一覧</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url("contracts/{$contract['id']}/edit") ?>">契約編集</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url("non_visit_days/{$contract['id']}?year={$year}") ?>">非訪問日管理</a></li>
                <li class="breadcrumb-item active">編集</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url("non_visit_days/{$contract['id']}?year={$year}") ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>一覧に戻る
        </a>
    </div>
</div>

<!-- 契約情報サマリー -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-primary bg-opacity-10 border-primary">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-4">
                        <strong>契約対象:</strong><br>
                        <span class="text-primary"><?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>産業医:</strong><br>
                        <span class="text-primary"><?= htmlspecialchars($contract['doctor_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>対象年度:</strong><br>
                        <span class="badge bg-success fs-6"><?= $year ?>年</span>
                    </div>
                    <div class="col-md-2">
                        <strong>訪問頻度:</strong><br>
                        <span class="badge bg-info">毎週</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 現在の登録情報 -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-info bg-opacity-10 border-info">
            <div class="card-header bg-info bg-opacity-20">
                <h6 class="mb-0 text-info">
                    <i class="fas fa-info-circle me-2"></i>現在の登録情報
                </h6>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-3">
                        <strong>日付:</strong><br>
                        <span class="text-info fw-bold"><?= $nonVisitDay['formatted_date'] ?></span><br>
                        <small class="text-muted"><?= $nonVisitDay['day_of_week_name'] ?>曜日</small>
                    </div>
                    <div class="col-md-4">
                        <strong>説明:</strong><br>
                        <span class="text-info"><?= htmlspecialchars($nonVisitDay['description'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="col-md-2">
                        <strong>種別:</strong><br>
                        <?php if ($nonVisitDay['is_recurring']): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-repeat me-1"></i>毎年繰り返し
                        </span>
                        <?php else: ?>
                        <span class="badge bg-primary">
                            <i class="fas fa-calendar-day me-1"></i>単発
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <strong>状態:</strong><br>
                        <span class="badge bg-<?= $nonVisitDay['is_active'] ? 'success' : 'secondary' ?>">
                            <i class="fas fa-<?= $nonVisitDay['is_active'] ? 'check' : 'times' ?> me-1"></i>
                            <?= $nonVisitDay['is_active'] ? '有効' : '無効' ?>
                        </span>
                    </div>
                    <div class="col-md-1">
                        <strong>ID:</strong><br>
                        <span class="text-muted">#<?= $nonVisitDay['id'] ?></span>
                    </div>
                </div>
                <?php if (!empty($nonVisitDay['created_by_name']) || !empty($nonVisitDay['updated_by_name'])): ?>
                <hr class="my-2">
                <div class="row">
                    <?php if (!empty($nonVisitDay['created_by_name'])): ?>
                    <div class="col-md-6">
                        <small class="text-muted">
                            登録者: <?= htmlspecialchars($nonVisitDay['created_by_name'], ENT_QUOTES, 'UTF-8') ?>
                            (<?= date('Y年m月d日 H:i', strtotime($nonVisitDay['created_at'])) ?>)
                        </small>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($nonVisitDay['updated_by_name'])): ?>
                    <div class="col-md-6">
                        <small class="text-muted">
                            最終更新: <?= htmlspecialchars($nonVisitDay['updated_by_name'], ENT_QUOTES, 'UTF-8') ?>
                            (<?= date('Y年m月d日 H:i', strtotime($nonVisitDay['updated_at'])) ?>)
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-times me-2"></i>非訪問日情報編集
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url("non_visit_days/{$contract['id']}/{$nonVisitDay['id']}") ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    
                    <!-- 日付入力 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="non_visit_date" class="form-label">
                                非訪問日 <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="non_visit_date" name="non_visit_date" 
                                   min="<?= $year ?>-01-01" max="<?= $year ?>-12-31" required
                                   value="<?= date('Y-m-d', strtotime($nonVisitDay['non_visit_date'])) ?>"
                                   onchange="updateDateInfo()">
                            <div class="form-text">
                                <?= $year ?>年の日付を選択してください
                            </div>
                            
                            <!-- 日付詳細情報 -->
                            <div id="date-info" class="mt-2">
                                <div class="card bg-info bg-opacity-10 border-info">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-day text-info me-2"></i>
                                            <div>
                                                <div id="date-display" class="fw-bold text-info"></div>
                                                <div id="day-of-week" class="small text-muted"></div>
                                            </div>
                                        </div>
                                        <div id="date-change-warning" class="mt-2" style="display: none;">
                                            <div class="alert alert-warning alert-sm py-1 mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <small>日付が変更されました。この変更は保存後に反映されます。</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">繰り返し設定</label>
                            <div class="card bg-light border-0">
                                <div class="card-body py-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="is_recurring" id="recurring_no" value="0" 
                                               <?= !$nonVisitDay['is_recurring'] ? 'checked' : '' ?> onchange="updateRecurringInfo()">
                                        <label class="form-check-label" for="recurring_no">
                                            <i class="fas fa-calendar-day text-primary me-1"></i>
                                            <strong>単発の非訪問日</strong>
                                            <div class="small text-muted">この年のみに適用</div>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_recurring" id="recurring_yes" value="1"
                                               <?= $nonVisitDay['is_recurring'] ? 'checked' : '' ?> onchange="updateRecurringInfo()">
                                        <label class="form-check-label" for="recurring_yes">
                                            <i class="fas fa-repeat text-success me-1"></i>
                                            <strong>毎年繰り返し</strong>
                                            <div class="small text-muted">来年以降も同じ日付に自動適用</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">
                                年末年始、創立記念日などは「毎年繰り返し」がお勧めです
                            </div>
                            
                            <!-- 繰り返し設定変更時の警告 -->
                            <div id="recurring-change-warning" class="mt-2" style="display: none;">
                                <div class="alert alert-warning alert-sm py-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <small id="recurring-warning-text"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 説明入力 -->
                    <div class="mb-4">
                        <label for="description" class="form-label">
                            説明 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="description" name="description" 
                               placeholder="例：年末年始休暇、お盆休み、創立記念日など" 
                               value="<?= htmlspecialchars($nonVisitDay['description'], ENT_QUOTES, 'UTF-8') ?>"
                               required maxlength="200" onchange="updateConfirmation()">
                        <div class="form-text">
                            非訪問日の理由や説明を入力してください（最大200文字）
                        </div>
                        
                        <!-- よく使用される説明のクイック選択 -->
                        <div class="mt-3">
                            <label class="form-label small">よく使用される説明</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDescription('年末年始休暇')">
                                    年末年始休暇
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDescription('お盆休み')">
                                    お盆休み
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDescription('創立記念日')">
                                    創立記念日
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDescription('ゴールデンウィーク')">
                                    ゴールデンウィーク
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDescription('会社行事')">
                                    会社行事
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDescription('施設点検日')">
                                    施設点検日
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 有効/無効設定 -->
                    <div class="mb-4">
                        <label class="form-label">状態設定</label>
                        <div class="card bg-light border-0">
                            <div class="card-body py-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="is_active" id="active_yes" value="1" 
                                           <?= $nonVisitDay['is_active'] ? 'checked' : '' ?> onchange="updateActiveInfo()">
                                    <label class="form-check-label" for="active_yes">
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                        <strong>有効</strong>
                                        <div class="small text-muted">この非訪問日は適用されます</div>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_active" id="active_no" value="0"
                                           <?= !$nonVisitDay['is_active'] ? 'checked' : '' ?> onchange="updateActiveInfo()">
                                    <label class="form-check-label" for="active_no">
                                        <i class="fas fa-times-circle text-danger me-1"></i>
                                        <strong>無効</strong>
                                        <div class="small text-muted">この非訪問日は適用されません（一時的に無効化）</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-text">
                            無効にすると、この日付での役務記録作成が可能になります
                        </div>
                        
                        <!-- 状態変更時の警告 -->
                        <div id="active-change-warning" class="mt-2" style="display: none;">
                            <div class="alert alert-info alert-sm py-2">
                                <i class="fas fa-info-circle me-1"></i>
                                <small id="active-warning-text"></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 確認情報 -->
                    <div class="mb-4">
                        <div class="card bg-warning bg-opacity-10 border-warning">
                            <div class="card-header bg-warning bg-opacity-20">
                                <h6 class="mb-0 text-warning">
                                    <i class="fas fa-info-circle me-2"></i>更新内容の確認
                                </h6>
                            </div>
                            <div class="card-body py-3">
                                <div id="confirmation-info">
                                    <!-- 初期表示は現在の内容 -->
                                </div>
                                
                                <!-- 変更点の表示 -->
                                <div id="changes-info" class="mt-3" style="display: none;">
                                    <hr class="my-2">
                                    <h6 class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>変更点
                                    </h6>
                                    <div id="changes-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 注意事項 -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>編集に関する注意事項</h6>
                        <ul class="mb-0 small">
                            <li><strong>重複チェック:</strong> 同じ日付の非訪問日が既に登録されている場合はエラーになります</li>
                            <li><strong>繰り返し設定変更:</strong> 繰り返し設定を変更すると、来年以降の適用に影響します</li>
                            <li><strong>日付変更:</strong> 日付を変更した場合、元の日付での役務記録制限は解除されます</li>
                            <li><strong>無効化:</strong> 無効にした非訪問日では、役務記録の作成が可能になります</li>
                            <li><strong>既存役務記録:</strong> 既に作成された役務記録には影響しません</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <div class="d-flex gap-2">
                            <a href="<?= base_url("non_visit_days/{$contract['id']}?year={$year}") ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>キャンセル
                            </a>
                            <button type="button" class="btn btn-outline-danger" onclick="deleteNonVisitDay()">
                                <i class="fas fa-trash me-1"></i>削除
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save me-1"></i>変更を保存
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 削除用のフォーム（非表示） -->
<form id="delete-form" method="POST" action="<?= base_url("non_visit_days/{$contract['id']}/{$nonVisitDay['id']}/delete") ?>" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<script>
// 元の値を保持（変更検知用）
const originalData = {
    date: '<?= date('Y-m-d', strtotime($nonVisitDay['non_visit_date'])) ?>',
    description: '<?= htmlspecialchars($nonVisitDay['description'], ENT_QUOTES, 'UTF-8') ?>',
    isRecurring: <?= $nonVisitDay['is_recurring'] ? 'true' : 'false' ?>,
    isActive: <?= $nonVisitDay['is_active'] ? 'true' : 'false' ?>
};

// 説明のクイック設定
function setDescription(text) {
    document.getElementById('description').value = text;
    updateConfirmation();
}

// 日付情報の更新
function updateDateInfo() {
    const dateInput = document.getElementById('non_visit_date');
    const dateDisplay = document.getElementById('date-display');
    const dayOfWeek = document.getElementById('day-of-week');
    const changeWarning = document.getElementById('date-change-warning');
    
    if (dateInput.value) {
        const date = new Date(dateInput.value);
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        const monthNames = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
        
        dateDisplay.textContent = `${monthNames[date.getMonth()]}${date.getDate()}日`;
        dayOfWeek.textContent = `${dayNames[date.getDay()]}曜日`;
        
        // 変更検知
        if (dateInput.value !== originalData.date) {
            changeWarning.style.display = 'block';
        } else {
            changeWarning.style.display = 'none';
        }
        
        // 既存チェック（実装可能ならAjaxで）
        checkDuplicateDate(dateInput.value);
    }
    
    updateConfirmation();
}

// 繰り返し設定変更時の警告表示
function updateRecurringInfo() {
    const recurringYes = document.getElementById('recurring_yes');
    const warning = document.getElementById('recurring-change-warning');
    const warningText = document.getElementById('recurring-warning-text');
    
    const currentIsRecurring = recurringYes.checked;
    
    if (currentIsRecurring !== originalData.isRecurring) {
        warning.style.display = 'block';
        if (currentIsRecurring) {
            warningText.textContent = '毎年繰り返しに変更されます。来年以降も同じ日付が自動で非訪問日として設定されます。';
        } else {
            warningText.textContent = '単発設定に変更されます。来年以降は自動で非訪問日として設定されなくなります。';
        }
    } else {
        warning.style.display = 'none';
    }
    
    updateConfirmation();
}

// 有効/無効変更時の情報表示
function updateActiveInfo() {
    const activeYes = document.getElementById('active_yes');
    const warning = document.getElementById('active-change-warning');
    const warningText = document.getElementById('active-warning-text');
    
    const currentIsActive = activeYes.checked;
    
    if (currentIsActive !== originalData.isActive) {
        warning.style.display = 'block';
        if (currentIsActive) {
            warningText.textContent = '有効に変更されます。この日付での役務記録作成が制限されます。';
        } else {
            warningText.textContent = '無効に変更されます。この日付での役務記録作成が可能になります。';
        }
    } else {
        warning.style.display = 'none';
    }
    
    updateConfirmation();
}

// 重複チェック（簡単なクライアントサイド実装）
function checkDuplicateDate(date) {
    // 実際の実装では Ajax で重複チェックを行う
    // ここでは基本的な表示のみ
}

// 確認情報の更新
function updateConfirmation() {
    const dateInput = document.getElementById('non_visit_date');
    const descriptionInput = document.getElementById('description');
    const recurringYes = document.getElementById('recurring_yes');
    const activeYes = document.getElementById('active_yes');
    const confirmationInfo = document.getElementById('confirmation-info');
    const changesInfo = document.getElementById('changes-info');
    const changesList = document.getElementById('changes-list');
    
    if (dateInput.value && descriptionInput.value) {
        const date = new Date(dateInput.value);
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        const isRecurring = recurringYes.checked;
        const isActive = activeYes.checked;
        
        // 現在の設定表示
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <strong>非訪問日:</strong><br>
                    <span class="text-primary">${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日（${dayNames[date.getDay()]}曜日）</span>
                </div>
                <div class="col-md-6">
                    <strong>種別:</strong><br>
                    <span class="badge bg-${isRecurring ? 'success' : 'primary'}">
                        <i class="fas fa-${isRecurring ? 'repeat' : 'calendar-day'} me-1"></i>
                        ${isRecurring ? '毎年繰り返し' : '単発'}
                    </span>
                </div>
                <div class="col-md-8 mt-2">
                    <strong>説明:</strong><br>
                    <span class="text-primary">${descriptionInput.value}</span>
                </div>
                <div class="col-md-4 mt-2">
                    <strong>状態:</strong><br>
                    <span class="badge bg-${isActive ? 'success' : 'secondary'}">
                        <i class="fas fa-${isActive ? 'check' : 'times'} me-1"></i>
                        ${isActive ? '有効' : '無効'}
                    </span>
                </div>
            </div>
        `;
        
        confirmationInfo.innerHTML = html;
        
        // 変更点の検出と表示
        const changes = [];
        
        if (dateInput.value !== originalData.date) {
            const originalDate = new Date(originalData.date);
            changes.push(`<div class="text-warning"><i class="fas fa-calendar me-1"></i>日付: ${originalDate.getFullYear()}年${originalDate.getMonth() + 1}月${originalDate.getDate()}日 → ${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日</div>`);
        }
        
        if (descriptionInput.value !== originalData.description) {
            changes.push(`<div class="text-warning"><i class="fas fa-edit me-1"></i>説明: ${originalData.description} → ${descriptionInput.value}</div>`);
        }
        
        if (isRecurring !== originalData.isRecurring) {
            changes.push(`<div class="text-warning"><i class="fas fa-sync me-1"></i>繰り返し: ${originalData.isRecurring ? '毎年' : '単発'} → ${isRecurring ? '毎年' : '単発'}</div>`);
        }
        
        if (isActive !== originalData.isActive) {
            changes.push(`<div class="text-warning"><i class="fas fa-toggle-on me-1"></i>状態: ${originalData.isActive ? '有効' : '無効'} → ${isActive ? '有効' : '無効'}</div>`);
        }
        
        if (changes.length > 0) {
            changesList.innerHTML = changes.join('<hr class="my-1">');
            changesInfo.style.display = 'block';
        } else {
            changesInfo.style.display = 'none';
        }
        
    } else {
        confirmationInfo.innerHTML = `
            <div class="text-muted">
                <i class="fas fa-arrow-up me-1"></i>
                上記の項目を入力すると、更新内容の詳細がここに表示されます
            </div>
        `;
        changesInfo.style.display = 'none';
    }
}

// 年の範囲チェック
function validateYear() {
    const dateInput = document.getElementById('non_visit_date');
    const targetYear = <?= (int)$year ?>;
    
    if (dateInput.value) {
        const selectedDate = new Date(dateInput.value);
        const selectedYear = selectedDate.getFullYear();
        
        if (selectedYear !== targetYear) {
            alert(`${targetYear}年の日付を選択してください。`);
            dateInput.value = originalData.date;
            updateDateInfo();
            return false;
        }
    }
    return true;
}

// 削除処理
function deleteNonVisitDay() {
    const description = '<?= htmlspecialchars($nonVisitDay['description'], ENT_QUOTES, 'UTF-8') ?>';
    if (confirm(`非訪問日「${description}」を削除しますか？\n\n削除されたデータは復元できません。`)) {
        document.getElementById('delete-form').submit();
    }
}

// ページ読み込み時の処理
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const dateInput = document.getElementById('non_visit_date');
    const descriptionInput = document.getElementById('description');
    const recurringInputs = document.querySelectorAll('input[name="is_recurring"]');
    const activeInputs = document.querySelectorAll('input[name="is_active"]');
    
    // 初期表示
    updateDateInfo();
    updateConfirmation();
    
    // イベントリスナー
    dateInput.addEventListener('change', function() {
        validateYear();
    });
    
    descriptionInput.addEventListener('input', updateConfirmation);
    
    recurringInputs.forEach(input => {
        input.addEventListener('change', updateRecurringInfo);
    });
    
    activeInputs.forEach(input => {
        input.addEventListener('change', updateActiveInfo);
    });
    
    // フォームバリデーション
    form.addEventListener('submit', function(e) {
        if (!validateYear()) {
            e.preventDefault();
            return;
        }
        
        const dateValue = dateInput.value;
        const descriptionValue = descriptionInput.value.trim();
        
        if (!dateValue) {
            alert('非訪問日を選択してください。');
            e.preventDefault();
            return;
        }
        
        if (!descriptionValue) {
            alert('説明を入力してください。');
            descriptionInput.focus();
            e.preventDefault();
            return;
        }
        
        // 変更がある場合のみ確認ダイアログ
        const hasChanges = 
            dateValue !== originalData.date ||
            descriptionValue !== originalData.description ||
            document.getElementById('recurring_yes').checked !== originalData.isRecurring ||
            document.getElementById('active_yes').checked !== originalData.isActive;
        
        if (hasChanges) {
            const date = new Date(dateValue);
            const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
            const recurringYes = document.getElementById('recurring_yes').checked;
            const activeYes = document.getElementById('active_yes').checked;
            const recurringText = recurringYes ? '（毎年繰り返し）' : '（単発）';
            const activeText = activeYes ? '（有効）' : '（無効）';
            
            const confirmMessage = `以下の内容で非訪問日を更新しますか？\n\n` +
                                 `日付: ${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日（${dayNames[date.getDay()]}曜日）\n` +
                                 `説明: ${descriptionValue}\n` +
                                 `種別: ${recurringText}\n` +
                                 `状態: ${activeText}`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        } else {
            alert('変更がありません。');
            e.preventDefault();
        }
    });
});
</script>