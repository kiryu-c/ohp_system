<?php
// app/views/non_visit_days/create.php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-plus-circle me-2 text-primary"></i>非訪問日登録</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('contracts') ?>">契約一覧</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url("contracts/{$contract['id']}/edit") ?>">契約編集</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url("non_visit_days/{$contract['id']}?year={$year}") ?>">非訪問日管理</a></li>
                <li class="breadcrumb-item active">新規登録</li>
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

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-times me-2"></i>非訪問日情報入力
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url("non_visit_days/{$contract['id']}") ?>">
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
                                   onchange="updateDateInfo()">
                            <div class="form-text">
                                <?= $year ?>年の日付を選択してください
                            </div>
                            
                            <!-- 日付詳細情報 -->
                            <div id="date-info" class="mt-2" style="display: none;">
                                <div class="card bg-info bg-opacity-10 border-info">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-day text-info me-2"></i>
                                            <div>
                                                <div id="date-display" class="fw-bold text-info"></div>
                                                <div id="day-of-week" class="small text-muted"></div>
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
                                        <input class="form-check-input" type="radio" name="is_recurring" id="recurring_no" value="0" checked>
                                        <label class="form-check-label" for="recurring_no">
                                            <i class="fas fa-calendar-day text-primary me-1"></i>
                                            <strong>単発の非訪問日</strong>
                                            <div class="small text-muted">この年のみに適用</div>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_recurring" id="recurring_yes" value="1">
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
                        </div>
                    </div>
                    
                    <!-- 説明入力 -->
                    <div class="mb-4">
                        <label for="description" class="form-label">
                            説明 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="description" name="description" 
                               placeholder="例：年末年始休暇、お盆休み、創立記念日など" required maxlength="200">
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
                    
                    <!-- 確認情報 -->
                    <div class="mb-4">
                        <div class="card bg-warning bg-opacity-10 border-warning">
                            <div class="card-header bg-warning bg-opacity-20">
                                <h6 class="mb-0 text-warning">
                                    <i class="fas fa-info-circle me-2"></i>登録内容の確認
                                </h6>
                            </div>
                            <div class="card-body py-3">
                                <div id="confirmation-info">
                                    <div class="text-muted">
                                        <i class="fas fa-arrow-up me-1"></i>
                                        上記の項目を入力すると、登録内容の詳細がここに表示されます
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 注意事項 -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>登録に関する注意事項</h6>
                        <ul class="mb-0 small">
                            <li><strong>重複チェック:</strong> 同じ日付の非訪問日が既に登録されている場合はエラーになります</li>
                            <li><strong>毎年繰り返し:</strong> 繰り返し設定をした場合、来年以降も自動的に同じ日付が非訪問日として設定されます</li>
                            <li><strong>祝日との関係:</strong> 祝日非訪問設定と非訪問日設定は独立して動作します</li>
                            <li><strong>役務記録への影響:</strong> 非訪問日に登録された日付では新しい役務記録を作成できません</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url("non_visit_days/{$contract['id']}?year={$year}") ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>キャンセル
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save me-1"></i>非訪問日を登録
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// 説明のクイック設定
function setDescription(text) {
    document.getElementById('description').value = text;
    updateConfirmation();
}

// 日付情報の更新
function updateDateInfo() {
    const dateInput = document.getElementById('non_visit_date');
    const dateInfo = document.getElementById('date-info');
    const dateDisplay = document.getElementById('date-display');
    const dayOfWeek = document.getElementById('day-of-week');
    
    if (dateInput.value) {
        const date = new Date(dateInput.value);
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        const monthNames = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
        
        dateDisplay.textContent = `${monthNames[date.getMonth()]}${date.getDate()}日`;
        dayOfWeek.textContent = `${dayNames[date.getDay()]}曜日`;
        dateInfo.style.display = 'block';
        
        // 既存チェック（実装可能ならAjaxで）
        checkDuplicateDate(dateInput.value);
    } else {
        dateInfo.style.display = 'none';
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
    const confirmationInfo = document.getElementById('confirmation-info');
    
    if (dateInput.value && descriptionInput.value) {
        const date = new Date(dateInput.value);
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        const isRecurring = recurringYes.checked;
        
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
                <div class="col-md-12 mt-2">
                    <strong>説明:</strong><br>
                    <span class="text-primary">${descriptionInput.value}</span>
                </div>
            </div>
        `;
        
        confirmationInfo.innerHTML = html;
    } else {
        confirmationInfo.innerHTML = `
            <div class="text-muted">
                <i class="fas fa-arrow-up me-1"></i>
                上記の項目を入力すると、登録内容の詳細がここに表示されます
            </div>
        `;
    }
}

// 年の範囲チェック
function validateYear() {
    const dateInput = document.getElementById('non_visit_date');
    const targetYear = <?= $year ?>;
    
    if (dateInput.value) {
        const selectedDate = new Date(dateInput.value);
        const selectedYear = selectedDate.getFullYear();
        
        if (selectedYear !== targetYear) {
            alert(`${targetYear}年の日付を選択してください。`);
            dateInput.value = '';
            updateDateInfo();
            return false;
        }
    }
    return true;
}

// ページ読み込み時の処理
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const dateInput = document.getElementById('non_visit_date');
    const descriptionInput = document.getElementById('description');
    const recurringInputs = document.querySelectorAll('input[name="is_recurring"]');
    
    // イベントリスナー
    dateInput.addEventListener('change', function() {
        validateYear();
    });
    
    descriptionInput.addEventListener('input', updateConfirmation);
    
    recurringInputs.forEach(input => {
        input.addEventListener('change', updateConfirmation);
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
        
        // 確認ダイアログ
        const date = new Date(dateValue);
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        const recurringYes = document.getElementById('recurring_yes').checked;
        const recurringText = recurringYes ? '（毎年繰り返し）' : '（単発）';
        
        const confirmMessage = `以下の非訪問日を登録しますか？\n\n` +
                             `日付: ${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日（${dayNames[date.getDay()]}曜日）\n` +
                             `説明: ${descriptionValue}\n` +
                             `種別: ${recurringText}`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        }
    });
    
    // 今日の日付をデフォルトに設定（今年の場合）
    const today = new Date();
    const currentYear = today.getFullYear();
    const targetYear = <?= $year ?>;
    
    if (currentYear === targetYear) {
        dateInput.value = today.toISOString().split('T')[0];
        updateDateInfo();
    }
});
</script>