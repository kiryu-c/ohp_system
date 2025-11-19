<?php
// app/views/contracts/edit.php - 隔月月次区分対応版
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit me-2"></i>契約編集</h2>
    <a href="<?= base_url('contracts') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>契約一覧に戻る
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>契約情報編集</h5>
                <div>
                    <span class="badge bg-secondary me-2">#<?= $contract['id'] ?></span>
                    <span class="badge bg-<?php
                        switch($contract['contract_status']) {
                            case 'active': echo 'success'; break;
                            case 'inactive': echo 'warning'; break;
                            case 'terminated': echo 'danger'; break;
                            default: echo 'secondary';
                        }
                    ?>">
                        <?= get_status_label($contract['contract_status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url("contracts/{$contract['id']}") ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <!-- 1行目:企業・拠点 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_id" class="form-label">
                                企業
                            </label>
                            <input type="hidden" name="company_id" value="<?= $contract['company_id'] ?>">
                            <input type="text" class="form-control" readonly 
                                   value="<?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?> (ID: <?= $contract['company_id'] ?>)">
                            <div class="form-text text-muted">
                                <i class="fas fa-lock me-1"></i>企業は変更できません
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="branch_id" class="form-label">
                                拠点
                            </label>
                            <input type="hidden" name="branch_id" value="<?= $contract['branch_id'] ?>">
                            <input type="text" class="form-control" readonly 
                                   value="<?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text text-muted">
                                <i class="fas fa-lock me-1"></i>拠点は変更できません
                            </div>
                        </div>
                    </div>
                    
                    <!-- 2行目:産業医・訪問頻度 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="doctor_id" class="form-label">
                                産業医
                            </label>
                            <input type="hidden" name="doctor_id" value="<?= $contract['doctor_id'] ?>">
                            <input type="text" class="form-control" readonly 
                                   value="<?= htmlspecialchars($contract['doctor_name'], ENT_QUOTES, 'UTF-8') ?> (ID: <?= $contract['doctor_id'] ?>)">
                            <div class="form-text text-muted">
                                <i class="fas fa-lock me-1"></i>産業医は編集後に変更できません
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="visit_frequency" class="form-label">
                                訪問頻度 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="visit_frequency" name="visit_frequency" required onchange="updateVisitFrequency()">
                                <option value="">訪問頻度を選択してください</option>
                                <option value="monthly" <?= ($contract['visit_frequency'] === 'monthly') ? 'selected' : '' ?>>
                                    毎月
                                </option>
                                <option value="bimonthly" <?= ($contract['visit_frequency'] === 'bimonthly') ? 'selected' : '' ?>>
                                    隔月(2ヶ月に1回)
                                </option>
                                <option value="weekly" <?= ($contract['visit_frequency'] === 'weekly') ? 'selected' : '' ?>>
                                    毎週
                                </option>
                                <option value="spot" <?= ($contract['visit_frequency'] === 'spot') ? 'selected' : '' ?>>
                                    スポット
                                </option>
                            </select>
                            <div class="form-text">産業医の訪問頻度を選択してください</div>
                        </div>
                    </div>

                    <!-- 隔月訪問の月次区分設定セクション -->
                    <div id="bimonthly_type_section" class="mb-3" style="display: <?= ($contract['visit_frequency'] === 'bimonthly') ? 'block' : 'none' ?>;">
                        <div class="card bg-info bg-opacity-10 border-info">
                            <div class="card-header bg-info bg-opacity-20">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-alt text-info me-2"></i>隔月訪問の月次区分設定
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">
                                            訪問する月を選択してください <span class="text-danger">*</span>
                                        </label>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-primary h-100">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="bimonthly_type" value="even" id="bimonthly_even"
                                                                   <?= ($contract['bimonthly_type'] === 'even') ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="bimonthly_even">
                                                                <i class="fas fa-calendar-check text-primary me-1"></i>偶数月訪問
                                                            </label>
                                                        </div>
                                                        <div class="text-muted small mt-2">
                                                            <i class="fas fa-info-circle me-1"></i>2月、4月、6月、8月、10月、12月に訪問
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-primary me-1">2月</span>
                                                            <span class="badge bg-primary me-1">4月</span>
                                                            <span class="badge bg-primary me-1">6月</span>
                                                            <span class="badge bg-primary me-1">8月</span>
                                                            <span class="badge bg-primary me-1">10月</span>
                                                            <span class="badge bg-primary">12月</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-success h-100">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="bimonthly_type" value="odd" id="bimonthly_odd"
                                                                   <?= ($contract['bimonthly_type'] === 'odd') ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="bimonthly_odd">
                                                                <i class="fas fa-calendar-check text-success me-1"></i>奇数月訪問
                                                            </label>
                                                        </div>
                                                        <div class="text-muted small mt-2">
                                                            <i class="fas fa-info-circle me-1"></i>1月、3月、5月、7月、9月、11月に訪問
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-success me-1">1月</span>
                                                            <span class="badge bg-success me-1">3月</span>
                                                            <span class="badge bg-success me-1">5月</span>
                                                            <span class="badge bg-success me-1">7月</span>
                                                            <span class="badge bg-success me-1">9月</span>
                                                            <span class="badge bg-success">11月</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>重要:</strong> 隔月訪問では、選択した月次区分に基づいて訪問月が決定されます。
                                    開始日の月に関わらず、選択した区分の月のみが訪問対象となります。
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 週間スケジュール設定セクション -->
                    <div id="weekly_schedule_section" class="mb-3" style="display: <?= ($contract['visit_frequency'] === 'weekly') ? 'block' : 'none' ?>;">
                        <div class="card bg-success bg-opacity-10 border-success">
                            <div class="card-header bg-success bg-opacity-20">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-calendar-week text-success me-2"></i>週間訪問スケジュール設定
                                    </h6>
                                    <!-- 非訪問日管理へのリンク -->
                                    <div id="non-visit-days-link" style="display: <?= ($contract['visit_frequency'] === 'weekly') ? 'block' : 'none' ?>;">
                                        <a href="<?= base_url("non_visit_days/{$contract['id']}") ?>" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="祝日や特別な非訪問日を管理">
                                            <i class="fas fa-calendar-times me-1"></i>非訪問日管理
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">訪問する曜日を選択してください(複数選択可)</label>
                                        <div class="row">
                                            <?php
                                            // 現在の週間スケジュールを取得
                                            $currentWeeklyDays = [];
                                            if ($contract['visit_frequency'] === 'weekly') {
                                                require_once __DIR__ . '/../../core/Database.php';
                                                $db = Database::getInstance()->getConnection();
                                                $sql = "SELECT day_of_week FROM contract_weekly_schedules WHERE contract_id = :contract_id ORDER BY day_of_week";
                                                $stmt = $db->prepare($sql);
                                                $stmt->execute(['contract_id' => $contract['id']]);
                                                $currentWeeklyDays = array_column($stmt->fetchAll(), 'day_of_week');
                                            }
                                            ?>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="1" id="day_1" <?= in_array('1', $currentWeeklyDays) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="day_1">月曜日</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="4" id="day_4" <?= in_array('4', $currentWeeklyDays) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="day_4">木曜日</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="2" id="day_2" <?= in_array('2', $currentWeeklyDays) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="day_2">火曜日</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="5" id="day_5" <?= in_array('5', $currentWeeklyDays) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="day_5">金曜日</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="3" id="day_3" <?= in_array('3', $currentWeeklyDays) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="day_3">水曜日</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="6" id="day_6" <?= in_array('6', $currentWeeklyDays) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="day_6">土曜日</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="7" id="day_7" <?= in_array('7', $currentWeeklyDays) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="day_7">日曜日</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 祝日除外設定 -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">祝日の扱い</label>
                                        <div class="card bg-warning bg-opacity-10 border-warning">
                                            <div class="card-body py-2">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="exclude_holidays" id="exclude_holidays_yes" value="1" 
                                                           <?= ($contract['exclude_holidays'] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="exclude_holidays_yes">
                                                        <i class="fas fa-times-circle text-danger me-1"></i>祝日は非訪問日
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="exclude_holidays" id="exclude_holidays_no" value="0"
                                                           <?= ($contract['exclude_holidays'] == 0) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="exclude_holidays_no">
                                                        <i class="fas fa-check-circle text-success me-1"></i>祝日も訪問可
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-text">祝日に訪問するかどうかを設定</div>
                                        
                                        <!-- 非訪問日管理の説明とリンク -->
                                        <div class="mt-3">
                                            <div class="card bg-info bg-opacity-10 border-info">
                                                <div class="card-body py-2 px-3">
                                                    <div class="small text-info">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        個別の祝日や特別な非訪問日は<br>
                                                        「非訪問日管理」で詳細設定できます
                                                    </div>
                                                    <div class="mt-2">
                                                        <a href="<?= base_url("non_visit_days/{$contract['id']}") ?>" 
                                                           class="btn btn-sm btn-info" 
                                                           target="_blank"
                                                           title="新しいタブで非訪問日管理画面を開きます">
                                                            <i class="fas fa-external-link-alt me-1"></i>詳細設定
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3行目:定期訪問時間・タクシー可否・請求方法・開始日・反映日 -->
                    <div class="row">
                        <div class="col-md-2 mb-3" id="regular_visit_hours_wrapper">
                            <label for="regular_visit_hours" class="form-label">
                                <span id="visit-hours-label">
                                    <?php
                                    switch($contract['visit_frequency']) {
                                        case 'weekly': echo '1回あたりの訪問時間'; break;
                                        case 'bimonthly': echo '定期訪問時間(隔月・2ヶ月分)'; break;
                                        case 'spot': echo 'スポット契約'; break;
                                        default: echo '定期訪問時間(毎月)'; break;
                                    }
                                    ?>
                                </span> 
                                <span class="text-danger" id="regular_visit_hours_required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="regular_visit_hours" name="regular_visit_hours" 
                                    value="<?= htmlspecialchars($contract['regular_visit_hours'], ENT_QUOTES, 'UTF-8') ?>" 
                                    required min="0.5" max="200" step="0.5" onchange="updateTimeInfo()">
                                <span class="input-group-text">時間</span>
                            </div>
                            <div class="form-text" id="visit-hours-description">
                                <?php
                                switch($contract['visit_frequency']) {
                                    case 'weekly': echo '1回の訪問あたりの時間を入力してください(0.5時間単位)'; break;
                                    case 'bimonthly': echo '隔月訪問時の2ヶ月分の時間を入力してください(0.5時間単位)'; break;
                                    case 'spot': echo 'スポット契約では訪問時間の事前設定は不要です'; break;
                                    default: echo '月間の定期訪問時間を入力してください(0.5時間単位)'; break;
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label">タクシー利用可否</label>
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="taxi_allowed" id="taxi_allowed_no" value="0" 
                                            <?= ($contract['taxi_allowed'] == 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="taxi_allowed_no">
                                            <i class="fas fa-times-circle text-danger me-1"></i>利用不可
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="taxi_allowed" id="taxi_allowed_yes" value="1"
                                            <?= ($contract['taxi_allowed'] == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="taxi_allowed_yes">
                                            <i class="fas fa-check-circle text-success me-1"></i>利用可
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">タクシー利用の交通費申請可否を設定</div>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label for="tax_type" class="form-label">
                                税種別 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="tax_type" name="tax_type" required>
                                <option value="">請求方法を選択してください</option>
                                <option value="exclusive" <?= ($contract['tax_type'] === 'exclusive') ? 'selected' : '' ?>>
                                    外税
                                </option>
                                <option value="inclusive" <?= ($contract['tax_type'] === 'inclusive') ? 'selected' : '' ?>>
                                    内税
                                </option>
                            </select>
                            <div class="form-text">
                                外税: 請求書で税額を別途表示<br>
                                内税: 請求書で税込価格を表示
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="start_date" class="form-label">
                                契約開始日 <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                value="<?= htmlspecialchars($contract['start_date'], ENT_QUOTES, 'UTF-8') ?>" 
                                required readonly>
                            <div class="form-text">
                                <i class="fas fa-lock me-1"></i>契約開始日は変更できません
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="effective_date" class="form-label">
                                反映日 <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="effective_date" name="effective_date" 
                                value="<?= htmlspecialchars($contract['effective_date'], ENT_QUOTES, 'UTF-8') ?>" 
                                required>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>この契約内容が適用される開始日(必ず1日を指定)
                            </div>
                        </div>
                    </div>

                    <!-- バージョン情報の表示 -->
                    <?php if ($contract['version_number'] > 1 || !empty($contract['effective_end_date'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6 class="mb-2">
                                    <i class="fas fa-history me-2"></i>契約履歴情報
                                </h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>バージョン番号:</strong> 
                                        <span class="badge bg-primary">Ver. <?= $contract['version_number'] ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>反映日:</strong> 
                                        <?= format_date($contract['effective_date']) ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>反映終了日:</strong> 
                                        <?= $contract['effective_end_date'] ? format_date($contract['effective_end_date']) : '最新版' ?>
                                    </div>
                                    <div class="col-md-3">
                                        <?php if ($contract['parent_contract_id']): ?>
                                            <strong>初版契約ID:</strong> #<?= $contract['parent_contract_id'] ?>
                                        <?php else: ?>
                                            <strong>初版契約</strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 料金設定セクション -->
                    <div class="mb-4">
                        <div class="card bg-info bg-opacity-10 border-info">
                            <div class="card-header bg-info bg-opacity-20">
                                <h6 class="mb-0">
                                    <i class="fas fa-yen-sign text-info me-2"></i>料金設定
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- 通常料金（定期・隔月・毎週の場合） -->
                                <div id="regular_rates_section" class="row" style="display: <?= ($contract['visit_frequency'] === 'spot') ? 'none' : 'flex' ?>;">
                                    <!-- 定期訪問料金 -->
                                    <div class="col-md-3 mb-3">
                                        <label for="regular_visit_rate" class="form-label">
                                            定期訪問料金(1時間あたり) <span class="text-danger" id="regular_visit_rate_required">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="regular_visit_rate" name="regular_visit_rate" 
                                                value="<?= htmlspecialchars($contract['regular_visit_rate'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">定期訪問1時間あたりの料金を入力</div>
                                    </div>
                                    
                                    <!-- 定期延長料金 -->
                                    <div class="col-md-3 mb-3">
                                        <label for="regular_extension_rate" class="form-label">
                                            定期延長料金(15分あたり) <span class="text-danger" id="regular_extension_rate_required">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="regular_extension_rate" name="regular_extension_rate" 
                                                value="<?= htmlspecialchars($contract['regular_extension_rate'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">定期訪問の延長15分あたりの料金を入力</div>
                                    </div>
                                    
                                    <!-- 臨時訪問料金 -->
                                    <div class="col-md-3 mb-3">
                                        <label for="emergency_visit_rate" class="form-label">
                                            臨時訪問料金(15分あたり) <span class="text-danger" id="emergency_visit_rate_required">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="emergency_visit_rate" name="emergency_visit_rate" 
                                                value="<?= htmlspecialchars($contract['emergency_visit_rate'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">臨時訪問15分あたりの料金を入力</div>
                                    </div>
                                    
                                    <!-- 書面作成・遠隔相談料金 -->
                                    <div class="col-md-3 mb-3">
                                        <!-- 使用フラグ -->
                                        <div class="mb-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="use_remote_consultation" name="use_remote_consultation" value="1" 
                                                    <?= (!empty($contract['use_remote_consultation'])) ? 'checked' : '' ?> onchange="toggleDocumentConsultationRate()">
                                                <label class="form-check-label" for="use_remote_consultation">
                                                    遠隔相談を使用
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="use_document_creation" name="use_document_creation" value="1"
                                                    <?= (!empty($contract['use_document_creation'])) ? 'checked' : '' ?> onchange="toggleDocumentConsultationRate()">
                                                <label class="form-check-label" for="use_document_creation">
                                                    書面作成を使用
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div id="document_consultation_rate_wrapper">
                                            <label for="document_consultation_rate" class="form-label">
                                                書面作成・遠隔相談料金(1回あたり) <span class="text-danger" id="document_rate_required">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="document_consultation_rate" name="document_consultation_rate" 
                                                    value="<?= htmlspecialchars($contract['document_consultation_rate'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    min="0">
                                                <span class="input-group-text">円</span>
                                            </div>
                                            <div class="form-text">書面作成・遠隔相談1回あたりの料金を入力</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- スポット料金（スポットの場合） -->
                                <div id="spot_rate_section" class="row" style="display: <?= ($contract['visit_frequency'] === 'spot') ? 'flex' : 'none' ?>;">
                                    <div class="col-md-6 mb-3">
                                        <label for="spot_rate" class="form-label">
                                            スポット料金(15分あたり) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="spot_rate" name="spot_rate" 
                                                value="<?= htmlspecialchars($contract['spot_rate'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">スポット15分あたりの料金を入力</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>スポット契約について:</strong><br>
                                            必要に応じて個別に訪問を実施する契約形態です。<br>
                                            料金は15分単位で計算されます。
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 料金設定の説明 -->
                                <div id="regular_rates_description" class="alert alert-light border-info" style="display: <?= ($contract['visit_frequency'] === 'spot') ? 'none' : 'block' ?>;">
                                    <div class="d-flex">
                                        <i class="fas fa-info-circle text-info me-2 mt-1"></i>
                                        <div>
                                            <strong>料金設定について:</strong>
                                            <ul class="mb-0 mt-1">
                                                <li><strong>定期訪問料金:</strong> 契約に基づく定期的な訪問の時間単価</li>
                                                <li><strong>定期延長料金:</strong> 定期訪問の予定時間を延長した場合の15分単価</li>
                                                <li><strong>臨時訪問料金:</strong> 緊急時や追加対応の15分単価</li>
                                                <li><strong>書面作成・遠隔相談料金:</strong> 意見書作成や電話相談等の1回あたり料金</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 4行目:契約終了日・ステータス -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">契約終了日</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?= htmlspecialchars($contract['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">終了日を指定しない場合は無期限契約となります</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contract_status" class="form-label">
                                契約ステータス <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="contract_status" name="contract_status" required>
                                <option value="active" <?= ($contract['contract_status'] === 'active') ? 'selected' : '' ?>>有効</option>
                                <option value="inactive" <?= ($contract['contract_status'] === 'inactive') ? 'selected' : '' ?>>無効</option>
                                <option value="terminated" <?= ($contract['contract_status'] === 'terminated') ? 'selected' : '' ?>>終了</option>
                            </select>
                            <div class="form-text">契約の現在の状態を選択してください</div>
                        </div>
                        
                        <!-- 訪問月の予定表示(隔月の場合) -->
                        <div class="col-md-8 mb-3" id="visit-schedule" style="display: <?= ($contract['visit_frequency'] === 'bimonthly') ? 'block' : 'none' ?>;">
                            <label class="form-label">
                                <i class="fas fa-calendar-check me-1"></i>訪問予定月
                            </label>
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body py-2">
                                    <div id="visit-months" class="d-flex flex-wrap gap-1">
                                        <!-- 動的に生成される月表示 -->
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        隔月契約では指定された月のみ定期訪問を実施します
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 訪問頻度詳細情報 -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div id="frequency-details" class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center">
                                        <i id="frequency-icon" class="fas fa-calendar-alt text-primary me-2"></i>
                                        <div>
                                            <div id="frequency-title" class="fw-bold text-primary">
                                                <?php
                                                switch($contract['visit_frequency']) {
                                                    case 'weekly': echo '毎週訪問'; break;
                                                    case 'bimonthly': echo '隔月訪問'; break;
                                                    default: echo '毎月訪問'; break;
                                                }
                                                ?>
                                            </div>
                                            <div id="frequency-desc" class="text-muted">
                                                <?php
                                                switch($contract['visit_frequency']) {
                                                    case 'weekly': echo '指定した曜日に毎週訪問を実施'; break;
                                                    case 'bimonthly': 
                                                        $typeLabel = ($contract['bimonthly_type'] === 'even') ? '(偶数月)' : (($contract['bimonthly_type'] === 'odd') ? '(奇数月)' : '');
                                                        echo '2ヶ月に1回、指定された時間数の訪問を実施' . $typeLabel;
                                                        break;
                                                    default: echo '毎月1回、指定された時間数の訪問を実施'; break;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="frequency-example" class="mt-2 small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <span id="frequency-example-text">
                                            <?php
                                            switch($contract['visit_frequency']) {
                                                case 'weekly': 
                                                    echo '選択した曜日に毎週' . $contract['regular_visit_hours'] . '時間の訪問が実施されます';
                                                    if ($contract['exclude_holidays']) {
                                                        echo '(祝日は非訪問日)';
                                                    } else {
                                                        echo '(祝日も訪問可)';
                                                    }
                                                    break;
                                                case 'bimonthly': 
                                                    $typeLabel = ($contract['bimonthly_type'] === 'even') ? '(偶数月)' : (($contract['bimonthly_type'] === 'odd') ? '(奇数月)' : '');
                                                    echo '隔月で' . $contract['regular_visit_hours'] . '時間の定期訪問が実施されます(2ヶ月に1回)' . $typeLabel;
                                                    break;
                                                default: 
                                                    echo '毎月' . $contract['regular_visit_hours'] . '時間の定期訪問が実施されます';
                                                    break;
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 専属登録料 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="exclusive_registration_fee" class="form-label">
                                <i class="fas fa-hand-holding-usd me-1"></i>専属登録料
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control text-end" id="exclusive_registration_fee" 
                                       name="exclusive_registration_fee" 
                                       value="<?= htmlspecialchars($contract['exclusive_registration_fee'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       min="0" step="1" placeholder="0">
                                <span class="input-group-text">円</span>
                            </div>
                            <div class="form-text">毎月請求されます(任意)</div>
                        </div>
                    </div>
                    
                    <!-- 契約書ファイル管理 -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                <i class="fas fa-file-pdf me-1"></i>契約書ファイル
                            </label>
                            
                            <!-- 現在のファイル表示 -->
                            <?php if (!empty($contract['contract_file_path'])): ?>
                                <div class="card bg-light mb-3" id="current-file">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-pdf me-2 text-danger"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">
                                                    <?= htmlspecialchars($contract['contract_file_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <?= formatFileSize($contract['contract_file_size'] ?? 0) ?>
                                                    - アップロード日: <?= date('Y年m月d日', strtotime($contract['updated_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= base_url("contracts/{$contract['id']}/download-file") ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="ダウンロード">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="removeCurrentFile()"
                                                        title="削除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-muted mb-3" id="no-file-message">
                                    <i class="fas fa-info-circle me-1"></i>契約書ファイルがアップロードされていません
                                </div>
                            <?php endif; ?>
                            
                            <!-- 新しいファイルアップロード -->
                            <input type="file" class="form-control" id="contract_file" name="contract_file" 
                                   accept=".pdf,.doc,.docx" onchange="validateFile()">
                            <input type="hidden" id="remove_file" name="remove_file" value="0">
                            <div class="form-text">
                                PDF、Word文書(.pdf, .doc, .docx)をアップロードできます。最大サイズ: 10MB<br>
                                新しいファイルを選択すると、既存のファイルが置き換えられます。
                            </div>
                            
                            <div id="file-info" class="mt-2" style="display: none;">
                                <div class="card bg-warning bg-opacity-10 border-warning">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file me-2 text-warning"></i>
                                            <div class="flex-grow-1">
                                                <div id="file-name" class="fw-bold"></div>
                                                <div id="file-size" class="text-muted small"></div>
                                                <div class="text-warning small">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    保存時に既存ファイルが置き換えられます
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="file-error" class="text-danger mt-1" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- 契約詳細情報 -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h6>契約詳細情報</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="150">契約ID</th>
                                        <td><?= $contract['id'] ?></td>
                                        <th width="150">作成日時</th>
                                        <td><?= date('Y年m月d日 H:i', strtotime($contract['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>最終更新</th>
                                        <td><?= date('Y年m月d日 H:i', strtotime($contract['updated_at'])) ?></td>
                                        <th>契約期間</th>
                                        <td>
                                            <?= format_date($contract['start_date']) ?> ~ 
                                            <?= $contract['end_date'] ? format_date($contract['end_date']) : '無期限' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>企業・拠点</th>
                                        <td colspan="3">
                                            <i class="fas fa-building me-1 text-success"></i>
                                            <?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            <i class="fas fa-map-marker-alt ms-2 me-1 text-primary"></i>
                                            <?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>訪問頻度</th>
                                        <td>
                                            <i class="<?= get_visit_frequency_icon($contract['visit_frequency'] ?? 'monthly') ?> me-1"></i>
                                            <?= get_visit_frequency_label($contract['visit_frequency'] ?? 'monthly') ?>
                                            <?php if ($contract['visit_frequency'] === 'weekly'): ?>
                                                <span class="badge bg-<?= $contract['exclude_holidays'] ? 'warning' : 'info' ?> ms-1">
                                                    <?= $contract['exclude_holidays'] ? '祝日除外' : '祝日訪問可' ?>
                                                </span>
                                            <?php elseif ($contract['visit_frequency'] === 'bimonthly' && !empty($contract['bimonthly_type'])): ?>
                                                <span class="badge bg-info ms-1">
                                                    <?= ($contract['bimonthly_type'] === 'even') ? '偶数月' : '奇数月' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <th>定期訪問時間</th>
                                        <td>
                                            <span class="badge bg-primary"><?= format_total_hours($contract['regular_visit_hours']) ?></span>
                                            <?php if (($contract['visit_frequency'] ?? 'monthly') === 'bimonthly'): ?>
                                                <small class="text-muted">(2ヶ月に1回)</small>
                                            <?php elseif (($contract['visit_frequency'] ?? 'monthly') === 'weekly'): ?>
                                                <small class="text-muted">(1回あたり)</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>税種別</th>
                                        <td>
                                            <span class="badge bg-<?= ($contract['tax_type'] === 'inclusive') ? 'info' : 'warning' ?>">
                                                <i class="fas fa-<?= ($contract['tax_type'] === 'inclusive') ? 'calculator' : 'percent' ?> me-1"></i>
                                                <?= ($contract['tax_type'] === 'inclusive') ? '内税' : '外税' ?>
                                            </span>
                                        </td>
                                        <th>タクシー利用</th>
                                        <td>
                                            <span class="badge bg-<?= $contract['taxi_allowed'] ? 'success' : 'secondary' ?>">
                                                <i class="fas fa-<?= $contract['taxi_allowed'] ? 'check' : 'times' ?> me-1"></i>
                                                <?= $contract['taxi_allowed'] ? '利用可' : '利用不可' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>料金設定</th>
                                        <td colspan="3">
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-info">定期: <?= number_format($contract['regular_visit_rate'] ?? 0) ?>円/時</span>
                                                <span class="badge bg-success">延長: <?= number_format($contract['regular_extension_rate'] ?? 0) ?>円/15分</span>
                                                <span class="badge bg-warning text-dark">臨時: <?= number_format($contract['emergency_visit_rate'] ?? 0) ?>円/15分</span>
                                                <span class="badge bg-secondary">書面・遠隔: <?= number_format($contract['document_consultation_rate'] ?? 0) ?>円/回</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 役務記録統計 -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h6>役務記録統計</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center py-2">
                                            <h6 class="mb-1"><?= format_total_hours($serviceStats['regular_visit_hours']) ?></h6>
                                            <small>今月の実績</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body text-center py-2">
                                            <h6 class="mb-1"><?= format_total_hours($serviceStats['pending_hours']) ?></h6>
                                            <small>承認待ち</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center py-2">
                                            <h6 class="mb-1"><?= format_total_hours($serviceStats['approved_hours']) ?></h6>
                                            <small>承認済み</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body text-center py-2">
                                            <h6 class="mb-1"><?= format_total_hours($serviceStats['rejected_hours']) ?></h6>
                                            <small>差戻し</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($contract['contract_status'] === 'terminated'): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>この契約は終了しています</h6>
                            <p class="mb-0">終了した契約では新しい役務記録を作成できません。必要に応じて契約を再開してください。</p>
                        </div>
                    <?php elseif ($contract['contract_status'] === 'inactive'): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-pause me-2"></i>この契約は無効です</h6>
                            <p class="mb-0">無効な契約では新しい役務記録を作成できません。</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <?php if ($contract['contract_status'] === 'active'): ?>
                                <button type="button" class="btn btn-danger" 
                                        onclick="terminateContract(<?= $contract['id'] ?>)">
                                    <i class="fas fa-stop me-1"></i>契約を終了
                                </button>
                            <?php elseif ($contract['contract_status'] === 'terminated'): ?>
                                <button type="button" class="btn btn-success" 
                                        onclick="reactivateContract()">
                                    <i class="fas fa-play me-1"></i>契約を再開
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('contracts') ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>キャンセル
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>変更を保存
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
const effectiveDateInput = document.getElementById('effective_date');

// 訪問頻度変更時の処理
function updateVisitFrequency() {
    const frequency = document.getElementById('visit_frequency').value;
    const weeklySection = document.getElementById('weekly_schedule_section');
    const bimonthlySection = document.getElementById('bimonthly_type_section');
    const visitSchedule = document.getElementById('visit-schedule');
    const nonVisitDaysLink = document.getElementById('non-visit-days-link');
    const regularRatesSection = document.getElementById('regular_rates_section');
    const spotRateSection = document.getElementById('spot_rate_section');
    const regularRatesDescription = document.getElementById('regular_rates_description');
    
    console.log("==frequency:" + frequency);

    // 料金セクションの表示/非表示制御
    if (frequency === 'spot') {
        // スポット：スポット料金のみ表示
        regularRatesSection.style.display = 'none';
        spotRateSection.style.display = 'flex';
        regularRatesDescription.style.display = 'none';
        
        // 通常料金フィールドの必須を解除
        document.getElementById('regular_visit_rate').required = false;
        document.getElementById('regular_extension_rate').required = false;
        document.getElementById('emergency_visit_rate').required = false;
        document.getElementById('regular_visit_rate_required').style.display = 'none';
        document.getElementById('regular_extension_rate_required').style.display = 'none';
        document.getElementById('emergency_visit_rate_required').style.display = 'none';
        
        // スポット料金フィールドを必須に
        document.getElementById('spot_rate').required = true;
        
        // 訪問時間入力を非表示・非必須に
        document.getElementById('regular_visit_hours_wrapper').style.display = 'none';
        document.getElementById('regular_visit_hours').required = false;
        document.getElementById('regular_visit_hours_required').style.display = 'none';
    } else {
        // 通常：定期・延長・臨時料金を表示
        regularRatesSection.style.display = 'flex';
        spotRateSection.style.display = 'none';
        regularRatesDescription.style.display = 'block';
        
        // 通常料金フィールドを必須に
        document.getElementById('regular_visit_rate').required = true;
        document.getElementById('regular_extension_rate').required = true;
        document.getElementById('emergency_visit_rate').required = true;
        document.getElementById('regular_visit_rate_required').style.display = 'inline';
        document.getElementById('regular_extension_rate_required').style.display = 'inline';
        document.getElementById('emergency_visit_rate_required').style.display = 'inline';
        
        // スポット料金フィールドの必須を解除
        document.getElementById('spot_rate').required = false;
        
        // 訪問時間入力を表示・必須に
        document.getElementById('regular_visit_hours_wrapper').style.display = 'block';
        document.getElementById('regular_visit_hours').required = true;
        document.getElementById('regular_visit_hours_required').style.display = 'inline';
    }

    // 週間スケジュールセクションの表示/非表示
    if (frequency === 'weekly') {
        weeklySection.style.display = 'block';
        bimonthlySection.style.display = 'none';
        visitSchedule.style.display = 'none';
        if (nonVisitDaysLink) {
            nonVisitDaysLink.style.display = 'block';
        }
        // チェックボックスをクリア(隔月)
        clearBimonthlyType();
    } else if (frequency === 'bimonthly') {
        weeklySection.style.display = 'none';
        bimonthlySection.style.display = 'block';
        visitSchedule.style.display = 'block';
        if (nonVisitDaysLink) {
            nonVisitDaysLink.style.display = 'none';
        }
        updateVisitSchedule();
        // チェックボックスをクリア(週間)
        const checkboxes = weeklySection.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
    } else {
        weeklySection.style.display = 'none';
        bimonthlySection.style.display = 'none';
        visitSchedule.style.display = 'none';
        if (nonVisitDaysLink) {
            nonVisitDaysLink.style.display = 'none';
        }
        // チェックボックスをクリア(週間)
        const checkboxes = weeklySection.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
        // ラジオボタンをクリア(隔月)
        clearBimonthlyType();
    }
    
    // ラベルと説明の更新
    updateVisitHoursLabel();
    updateFrequencyDetails();
}

// 隔月区分のクリア
function clearBimonthlyType() {
    const radios = document.querySelectorAll('input[name="bimonthly_type"]');
    radios.forEach(radio => radio.checked = false);
}

// 訪問頻度に応じて表示を更新する関数
function updateVisitHoursLabel() {
    const frequency = document.getElementById('visit_frequency').value;
    const label = document.getElementById('visit-hours-label');
    const description = document.getElementById('visit-hours-description');
    const hoursInput = document.getElementById('regular_visit_hours');
    
    if (frequency === 'monthly') {
        label.textContent = '定期訪問時間(毎月)';
        description.textContent = '月間の定期訪問時間を入力してください(0.5時間単位)';
    } else if (frequency === 'bimonthly') {
        label.textContent = '定期訪問時間(隔月・2ヶ月分)';
        description.textContent = '隔月訪問時の2ヶ月分の時間を入力してください(0.5時間単位)';
    } else if (frequency === 'weekly') {
        label.textContent = '1回あたりの訪問時間';
        description.textContent = '1回の訪問あたりの時間を入力してください(0.5時間単位)';
    } else if (frequency === 'spot') {
        label.textContent = 'スポット契約';
        description.textContent = 'スポット契約では訪問時間の事前設定は不要です';
    } else {
        label.textContent = '定期訪問時間';
        description.textContent = '訪問頻度を選択してください';
    }
}

// 頻度詳細情報の更新
function updateFrequencyDetails() {
    const frequency = document.getElementById('visit_frequency').value;
    const details = document.getElementById('frequency-details');
    const icon = document.getElementById('frequency-icon');
    const title = document.getElementById('frequency-title');
    const desc = document.getElementById('frequency-desc');
    const hoursInput = document.getElementById('regular_visit_hours');
    const exampleText = document.getElementById('frequency-example-text');
    
    if (frequency === 'monthly') {
        title.textContent = '毎月訪問';
        desc.textContent = '毎月1回、指定された時間数の訪問を実施';
        details.className = 'card bg-primary bg-opacity-10 border-primary';
        icon.className = 'fas fa-calendar-alt text-primary me-2';
        title.className = 'fw-bold text-primary';
        exampleText.textContent = `毎月${hoursInput.value || '1'}時間の定期訪問が実施されます`;
    } else if (frequency === 'bimonthly') {
        // 月次区分の取得
        const selectedType = document.querySelector('input[name="bimonthly_type"]:checked');
        const typeLabel = selectedType ? (selectedType.value === 'even' ? '(偶数月)' : '(奇数月)') : '';
        
        title.textContent = '隔月訪問';
        desc.textContent = '2ヶ月に1回、指定された時間数の訪問を実施' + typeLabel;
        details.className = 'card bg-info bg-opacity-10 border-info';
        icon.className = 'fas fa-calendar-alt text-info me-2';
        title.className = 'fw-bold text-info';
        exampleText.textContent = `隔月で${hoursInput.value || '1'}時間の定期訪問が実施されます(2ヶ月に1回)${typeLabel}`;
    } else if (frequency === 'weekly') {
        title.textContent = '毎週訪問';
        desc.textContent = '指定した曜日に毎週訪問を実施';
        details.className = 'card bg-success bg-opacity-10 border-success';
        icon.className = 'fas fa-calendar-week text-success me-2';
        title.className = 'fw-bold text-success';
        
        // 祝日除外設定を考慮
        const excludeHolidays = document.querySelector('input[name="exclude_holidays"]:checked')?.value;
        const holidayText = excludeHolidays === '1' ? '(祝日は非訪問日)' : '(祝日も訪問可)';
        exampleText.textContent = `選択した曜日に毎週${hoursInput.value || '1'}時間の訪問が実施されます${holidayText}`;
    } else if (frequency === 'spot') {
        title.textContent = 'スポット契約';
        desc.textContent = '必要に応じて個別に訪問を実施（料金は15分単位で計算）';
        details.className = 'card bg-warning bg-opacity-10 border-warning';
        icon.className = 'fas fa-clock text-warning me-2';
        title.className = 'fw-bold text-warning';
        exampleText.textContent = '必要に応じて個別に訪問を実施します';
    } else {
        title.textContent = '訪問頻度未選択';
        desc.textContent = '訪問頻度を選択してください';
        details.className = 'card bg-light border-secondary';
        icon.className = 'fas fa-calendar-alt text-muted me-2';
        title.className = 'fw-bold text-muted';
        exampleText.textContent = '訪問頻度を選択すると詳細が表示されます';
    }
    
    updateTimeInfo();
}

// 時間情報の更新
function updateTimeInfo() {
    const hoursInput = document.getElementById('regular_visit_hours');
    const frequencySelect = document.getElementById('visit_frequency');
    const exampleText = document.getElementById('frequency-example-text');
    
    const hours = parseFloat(hoursInput.value) || 0;
    const frequency = frequencySelect.value;
    
    if (hours > 0 && frequency) {
        let exampleTextContent = '';
        
        if (frequency === 'monthly') {
            exampleTextContent = `毎月${hours}時間の定期訪問が実施されます`;
        } else if (frequency === 'bimonthly') {
            const selectedType = document.querySelector('input[name="bimonthly_type"]:checked');
            const typeLabel = selectedType ? (selectedType.value === 'even' ? '(偶数月)' : '(奇数月)') : '';
            exampleTextContent = `隔月で${hours}時間の定期訪問が実施されます(2ヶ月に1回)${typeLabel}`;
        } else if (frequency === 'weekly') {
            // 祝日除外設定を考慮
            const excludeHolidays = document.querySelector('input[name="exclude_holidays"]:checked')?.value;
            const holidayText = excludeHolidays === '1' ? '(祝日は非訪問日)' : '(祝日も訪問可)';
            exampleTextContent = `選択した曜日に毎週${hours}時間の訪問が実施されます${holidayText}`;
        }
        
        if (exampleText) {
            exampleText.textContent = exampleTextContent;
        }
    }
}

// 隔月契約の訪問予定表示
function updateVisitSchedule() {
    const startDateInput = document.getElementById('start_date');
    const visitMonths = document.getElementById('visit-months');
    const selectedType = document.querySelector('input[name="bimonthly_type"]:checked');
    
    const startDate = startDateInput.value;
    if (!startDate || !selectedType) {
        if (!startDate) {
            visitMonths.innerHTML = '<span class="text-muted small">契約開始日を選択してください</span>';
        } else {
            visitMonths.innerHTML = '<span class="text-muted small">月次区分を選択してください</span>';
        }
        return;
    }
    
    const bimonthlyType = selectedType.value;
    const currentYear = new Date().getFullYear();
    const months = [];
    
    // 今年の訪問予定月を計算
    for (let month = 1; month <= 12; month++) {
        const targetDate = new Date(currentYear, month - 1, 1);
        
        // 月次区分に基づいて訪問月を判定
        let isVisitMonth = false;
        if (bimonthlyType === 'even') {
            // 偶数月: 2, 4, 6, 8, 10, 12
            isVisitMonth = (month % 2 === 0);
        } else if (bimonthlyType === 'odd') {
            // 奇数月: 1, 3, 5, 7, 9, 11
            isVisitMonth = (month % 2 === 1);
        }
        
        if (isVisitMonth) {
            const isCurrentMonth = month === (new Date().getMonth() + 1);
            const isPastMonth = targetDate < new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            
            let badgeClass = 'bg-primary';
            if (isPastMonth) {
                badgeClass = 'bg-secondary';
            } else if (isCurrentMonth) {
                badgeClass = 'bg-success';
            }
            
            months.push(`<span class="badge ${badgeClass} me-1">${month}月</span>`);
        }
    }
    
    if (months.length > 0) {
        visitMonths.innerHTML = months.join('');
    } else {
        visitMonths.innerHTML = '<span class="text-muted small">訪問予定月がありません</span>';
    }
}

// ファイルサイズフォーマット関数
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 現在のファイル削除
function removeCurrentFile() {
    if (confirm('現在の契約書ファイルを削除しますか?\n\n削除されたファイルは復元できません。')) {
        document.getElementById('remove_file').value = '1';
        document.getElementById('current-file').style.display = 'none';
        document.getElementById('no-file-message').style.display = 'block';
    }
}

// ファイルバリデーション関数
function validateFile() {
    const fileInput = document.getElementById('contract_file');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const fileError = document.getElementById('file-error');
    
    // エラー表示をクリア
    fileError.style.display = 'none';
    fileInfo.style.display = 'none';
    
    if (!fileInput.files || fileInput.files.length === 0) {
        return;
    }
    
    const file = fileInput.files[0];
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    // ファイルサイズチェック
    if (file.size > maxSize) {
        fileError.textContent = 'ファイルサイズが大きすぎます。10MB以下のファイルを選択してください。';
        fileError.style.display = 'block';
        fileInput.value = '';
        return;
    }
    
    // ファイル形式チェック
    if (!allowedTypes.includes(file.type)) {
        fileError.textContent = 'サポートされていないファイル形式です。PDF、Word文書をアップロードしてください。';
        fileError.style.display = 'block';
        fileInput.value = '';
        return;
    }
    
    // ファイル情報を表示
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    fileInfo.style.display = 'block';
}

function removeFile() {
    const fileInput = document.getElementById('contract_file');
    const fileInfo = document.getElementById('file-info');
    const fileError = document.getElementById('file-error');
    
    fileInput.value = '';
    fileInfo.style.display = 'none';
    fileError.style.display = 'none';
}

function terminateContract(id) {
    if (confirm('契約を終了しますか?\n\n終了後は新しい役務記録を作成できなくなります。')) {
        document.getElementById('contract_status').value = 'terminated';
        if (!document.getElementById('end_date').value) {
            document.getElementById('end_date').value = new Date().toISOString().split('T')[0];
        }
    }
}

function reactivateContract() {
    if (confirm('契約を再開しますか?')) {
        document.getElementById('contract_status').value = 'active';
        document.getElementById('end_date').value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const frequencySelect = document.getElementById('visit_frequency');
    const taxTypeSelect = document.getElementById('tax_type');
    const regularVisitHoursInput = document.getElementById('regular_visit_hours');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    // 料金設定の入力要素
    const regularVisitRateInput = document.getElementById('regular_visit_rate');
    const regularExtensionRateInput = document.getElementById('regular_extension_rate');
    const emergencyVisitRateInput = document.getElementById('emergency_visit_rate');
    const documentConsultationRateInput = document.getElementById('document_consultation_rate');
    
    // 初期表示時の情報更新
    updateFrequencyDetails();
    updateTimeInfo();
    
    // 隔月契約の場合は初期プレビュー表示
    if (frequencySelect.value === 'bimonthly') {
        updateVisitSchedule();
    }
    
    // イベントリスナー
    frequencySelect.addEventListener('change', updateVisitFrequency);
    regularVisitHoursInput.addEventListener('input', function() {
        updateTimeInfo();
    });
    
    // 祝日除外設定変更時に例示を更新
    document.querySelectorAll('input[name="exclude_holidays"]').forEach(radio => {
        radio.addEventListener('change', updateFrequencyDetails);
    });
    
    // 月次区分変更時に訪問予定を更新
    document.querySelectorAll('input[name="bimonthly_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateVisitSchedule();
            updateFrequencyDetails();
        });
    });
    
    startDateInput.addEventListener('change', function() {
        if (this.value) {
            const nextDay = new Date(this.value);
            nextDay.setDate(nextDay.getDate() + 1);
            endDateInput.min = nextDay.toISOString().split('T')[0];
            
            // 隔月契約の場合は訪問予定を更新
            if (frequencySelect.value === 'bimonthly') {
                updateVisitSchedule();
            }
        }
    });
    
    // フォームバリデーション
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // 開始日が1日かチェック（追加）
        if (startDateInput.value) {
            const startDate = new Date(startDateInput.value);
            if (startDate.getDate() !== 1) {
                showFieldError(startDateInput, '契約開始日は毎月1日を指定してください');
                isValid = false;
            } else {
                clearFieldError(startDateInput);
            }
        }

        // 訪問頻度の検証
        if (!frequencySelect.value) {
            showFieldError(frequencySelect, '訪問頻度を選択してください');
            isValid = false;
        } else {
            clearFieldError(frequencySelect);
        }
        
        // 税種別の検証
        if (!taxTypeSelect.value) {
            showFieldError(taxTypeSelect, '税種別を選択してください');
            isValid = false;
        } else {
            clearFieldError(taxTypeSelect);
        }
        
        // 週間スケジュールの検証
        if (frequencySelect.value === 'weekly') {
            const selectedDays = document.querySelectorAll('input[name="weekly_days[]"]:checked');
            if (selectedDays.length === 0) {
                alert('週間契約では訪問する曜日を最低1つ選択してください。');
                isValid = false;
            }
        }
        
        // 隔月訪問の月次区分検証
        if (frequencySelect.value === 'bimonthly') {
            const selectedType = document.querySelector('input[name="bimonthly_type"]:checked');
            if (!selectedType) {
                alert('隔月訪問では月次区分(偶数月/奇数月)を選択してください。');
                const bimonthlySection = document.getElementById('bimonthly_type_section');
                bimonthlySection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                bimonthlySection.classList.add('border-danger');
                setTimeout(() => {
                    bimonthlySection.classList.remove('border-danger');
                }, 3000);
                isValid = false;
            }
        }
        
        // 月間時間の検証（スポット契約の場合はスキップ）
        if (frequencySelect.value !== 'spot') {
            if (!regularVisitHoursInput.value || parseFloat(regularVisitHoursInput.value) <= 0) {
                showFieldError(regularVisitHoursInput, '定期訪問時間を正しく入力してください');
                isValid = false;
            } else {
                clearFieldError(regularVisitHoursInput);
            }
        }
        
        // 開始日の検証
        if (!startDateInput.value) {
            showFieldError(startDateInput, '契約開始日を選択してください');
            isValid = false;
        } else {
            clearFieldError(startDateInput);
        }

        // 料金設定の検証
        if (frequencySelect.value === 'spot') {
            // スポット契約の場合はスポット料金のみチェック
            const spotRateInput = document.getElementById('spot_rate');
            if (!spotRateInput.value || parseInt(spotRateInput.value) <= 0) {
                showFieldError(spotRateInput, 'スポット料金を入力してください');
                isValid = false;
            } else {
                clearFieldError(spotRateInput);
            }
        } else {
            // 通常契約の場合は定期訪問料金をチェック
            if (!regularVisitRateInput.value || parseInt(regularVisitRateInput.value) < 0) {
                showFieldError(regularVisitRateInput, '定期訪問料金を入力してください');
                isValid = false;
            } else {
                clearFieldError(regularVisitRateInput);
            }
        }
        
        if (!regularExtensionRateInput.value || parseInt(regularExtensionRateInput.value) < 0) {
            showFieldError(regularExtensionRateInput, '定期延長料金を入力してください');
            isValid = false;
        } else {
            clearFieldError(regularExtensionRateInput);
        }

        if (!emergencyVisitRateInput.value || parseInt(emergencyVisitRateInput.value) < 0) {
            showFieldError(emergencyVisitRateInput, '臨時訪問料金を入力してください');
            isValid = false;
        } else {
            clearFieldError(emergencyVisitRateInput);
        }
        
        // 書面作成・遠隔相談料金の検証（チェックボックスがONの場合のみ）
        const useRemoteConsultation = document.getElementById('use_remote_consultation').checked;
        const useDocumentCreation = document.getElementById('use_document_creation').checked;
        
        if (useRemoteConsultation || useDocumentCreation) {
            if (!documentConsultationRateInput.value || parseInt(documentConsultationRateInput.value) < 0) {
                showFieldError(documentConsultationRateInput, '書面作成・遠隔相談料金を入力してください');
                isValid = false;
            } else {
                clearFieldError(documentConsultationRateInput);
            }
        } else {
            // 両方OFFの場合はエラーをクリア
            clearFieldError(documentConsultationRateInput);
        }

        // 終了日の検証(開始日より後かどうか)
        if (endDateInput.value && startDateInput.value) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (endDate <= startDate) {
                showFieldError(endDateInput, '終了日は開始日より後の日付を選択してください');
                isValid = false;
            } else {
                clearFieldError(endDateInput);
            }
        }

        // 反映日のバリデーション
        if (effectiveDateInput.value) {
            const effectiveDate = new Date(effectiveDateInput.value);
            
            // 反映日が1日かチェック
            if (effectiveDate.getDate() !== 1) {
                showFieldError(effectiveDateInput, '反映日は毎月1日を指定してください');
                isValid = false;
            } else if (startDateInput.value) {
                const startDate = new Date(startDateInput.value);
                
                // 反映日が契約開始日以降かチェック
                if (effectiveDate < startDate) {
                    showFieldError(effectiveDateInput, '反映日は契約開始日以降を指定してください');
                    isValid = false;
                } else if (endDateInput.value) {
                    const endDate = new Date(endDateInput.value);
                    
                    // 反映日が契約終了日より前かチェック
                    if (effectiveDate >= endDate) {
                        showFieldError(effectiveDateInput, '反映日は契約終了日より前を指定してください');
                        isValid = false;
                    } else {
                        clearFieldError(effectiveDateInput);
                    }
                } else {
                    clearFieldError(effectiveDateInput);
                }
            }
        } else {
            showFieldError(effectiveDateInput, '反映日を入力してください');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });

    // 開始日変更時のリアルタイムバリデーション（追加）
    startDateInput.addEventListener('change', function() {
        if (this.value) {
            const startDate = new Date(this.value);
            if (startDate.getDate() !== 1) {
                showFieldError(this, '契約開始日は毎月1日を指定してください');
            } else {
                clearFieldError(this);
            }
        }
    });
        
    // 反映日変更時のリアルタイムバリデーション
    effectiveDateInput.addEventListener('change', function() {
        if (this.value) {
            const effectiveDate = new Date(this.value);
            if (effectiveDate.getDate() !== 1) {
                showFieldError(this, '反映日は毎月1日を指定してください');
            } else {
                clearFieldError(this);
            }
        }
    });
    
    function showFieldError(field, message) {
        clearFieldError(field);
        field.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    function clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
});

// 書面作成・遠隔相談料金の表示/非表示を切り替える関数
function toggleDocumentConsultationRate() {
    const useRemoteConsultation = document.getElementById('use_remote_consultation').checked;
    const useDocumentCreation = document.getElementById('use_document_creation').checked;
    const rateWrapper = document.getElementById('document_consultation_rate_wrapper');
    const rateInput = document.getElementById('document_consultation_rate');
    const requiredMark = document.getElementById('document_rate_required');
    
    if (useRemoteConsultation || useDocumentCreation) {
        // どちらかがONの場合、料金フィールドを表示して必須に
        rateWrapper.style.display = 'block';
        rateInput.required = true;
        requiredMark.style.display = 'inline';
    } else {
        // 両方OFFの場合、料金フィールドを非表示にして非必須に
        rateWrapper.style.display = 'none';
        rateInput.required = false;
        rateInput.value = '';
        requiredMark.style.display = 'none';
    }
}

// ページ読み込み時に初期状態を設定
document.addEventListener('DOMContentLoaded', function() {
    toggleDocumentConsultationRate();
});

</script>