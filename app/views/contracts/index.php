<?php
// app/views/contracts/index.php - 期限切れ契約対応版 + ページネーション対応（企業版と同じスタイル）
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-contract me-2"></i>契約管理</h2>
    <div class="btn-group">
        <a href="<?= base_url('contracts/create') ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>契約を追加
        </a>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
        </a>
    </div>
</div>

<!-- 検索・フィルター -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('contracts') ?>" class="row g-3" id="filterForm">
            <div class="col-md-2">
                <label for="search" class="form-label">検索</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                       placeholder="産業医名/企業名/拠点名で検索">
            </div>
            <div class="col-md-3">
                <label class="form-label">ステータス</label>
                <?php
                // デフォルト値の設定: GETパラメータがない場合は['active']
                $statusArray = isset($_GET['status']) ? (array)$_GET['status'] : ['active'];
                ?>
                <div class="d-flex gap-3 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="status[]" value="active" id="status_active"
                               <?= in_array('active', $statusArray) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_active">有効</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="status[]" value="inactive" id="status_inactive"
                               <?= in_array('inactive', $statusArray) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_inactive">無効</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="status[]" value="expired" id="status_expired"
                               <?= in_array('expired', $statusArray) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_expired">期間終了</label>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <label for="frequency" class="form-label">訪問頻度</label>
                <select class="form-select" id="frequency" name="frequency">
                    <option value="">全て</option>
                    <option value="monthly" <?= ($_GET['frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>毎月</option>
                    <option value="bimonthly" <?= ($_GET['frequency'] ?? '') === 'bimonthly' ? 'selected' : '' ?>>隔月</option>
                    <option value="weekly" <?= ($_GET['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>毎週</option>
                    <option value="spot" <?= ($_GET['frequency'] ?? '') === 'spot' ? 'selected' : '' ?>>スポット</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="tax_type" class="form-label">税種別</label>
                <select class="form-select" id="tax_type" name="tax_type">
                    <option value="">全て</option>
                    <option value="exclusive" <?= ($_GET['tax_type'] ?? '') === 'exclusive' ? 'selected' : '' ?>>外税</option>
                    <option value="inclusive" <?= ($_GET['tax_type'] ?? '') === 'inclusive' ? 'selected' : '' ?>>内税</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="year" class="form-label">年</label>
                <select class="form-select" id="year" name="year">
                    <option value="">全て</option>
                    <?php for($i = date('Y'); $i >= date('Y') - 3; $i--): ?>
                        <option value="<?= $i ?>" <?= ($_GET['year'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?>年</option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-outline-primary d-block w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- モバイル用並び替え (1024px未満) -->
<div class="mobile-sort-section d-lg-none mb-3">
    <label for="mobile_sort" class="form-label">並び替え</label>
    <select class="form-select" id="mobile_sort">
        <option value="company_name-asc" <?= ($sortColumn ?? 'company_name') === 'company_name' && ($sortOrder ?? 'asc') === 'asc' ? 'selected' : '' ?>>
            企業名(昇順)
        </option>
        <option value="company_name-desc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            企業名(降順)
        </option>
        <option value="doctor_name-asc" <?= ($sortColumn ?? '') === 'doctor_name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            産業医名(昇順)
        </option>
        <option value="doctor_name-desc" <?= ($sortColumn ?? '') === 'doctor_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            産業医名(降順)
        </option>
        <option value="visit_frequency-asc" <?= ($sortColumn ?? '') === 'visit_frequency' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            訪問頻度(昇順)
        </option>
        <option value="regular_visit_hours-desc" <?= ($sortColumn ?? '') === 'regular_visit_hours' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            定期訪問時間(多い順)
        </option>
        <option value="regular_visit_hours-asc" <?= ($sortColumn ?? '') === 'regular_visit_hours' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            定期訪問時間(少ない順)
        </option>
        <option value="effective_date-desc" <?= ($sortColumn ?? '') === 'effective_date' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            反映日(新しい順)
        </option>
        <option value="effective_date-asc" <?= ($sortColumn ?? '') === 'effective_date' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            反映日(古い順)
        </option>
        <option value="start_date-desc" <?= ($sortColumn ?? '') === 'start_date' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            開始日(新しい順)
        </option>
        <option value="start_date-asc" <?= ($sortColumn ?? '') === 'start_date' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            開始日(古い順)
        </option>
        <option value="contract_status-asc" <?= ($sortColumn ?? '') === 'contract_status' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            ステータス(昇順)
        </option>
    </select>
</div>

<!-- ページネーション設定エリア（企業版と同じスタイル） -->
<?php if (isset($pagination) && $pagination['total_count'] > 0): ?>
<div class="card mb-4">
    <div class="card-body border-bottom bg-light">
        <div class="row align-items-center">
            <div class="col-md-4">
                <form method="GET" class="d-flex align-items-center">
                    <!-- 既存のGETパラメータを維持 -->
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if (!in_array($key, ['page', 'per_page'])): ?>
                            <?php if (is_array($value)): ?>
                                <?php foreach ($value as $v): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>[]" value="<?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <label for="contractsPageSize" class="form-label me-2 mb-0">表示件数:</label>
                    <select class="form-select form-select-sm" id="contractsPageSize" name="per_page" onchange="this.form.submit()" style="width: auto;">
                        <?php foreach ($pagination['per_page_options'] as $option): ?>
                            <option value="<?= $option ?>" <?= $pagination['per_page'] == $option ? 'selected' : '' ?>>
                                <?= $option ?>件
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="col-md-8 text-end">
                <small class="text-muted">
                    <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>件 / 
                    全<?= number_format($pagination['total_count']) ?>件
                </small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 契約一覧 -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>契約一覧
            <?php if (!empty($contracts)): ?>
                <?php if (isset($pagination) && $pagination['total_count'] > count($contracts)): ?>
                    <span class="badge bg-info">全<?= number_format($pagination['total_count']) ?>件</span>
                <?php else: ?>
                    <span class="badge bg-primary"><?= count($contracts) ?>件</span>
                <?php endif; ?>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($contracts)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                <p class="text-muted">契約が登録されていません。</p>
                <a href="<?= base_url('contracts/create') ?>" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>最初の契約を追加
                </a>
            </div>
        <?php else: ?>
            <!-- デスクトップ表示: テーブル (1024px以上) -->
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>契約ID</th>
                            <th>
                                <a href="#" class="sort-link" data-sort="company_name" title="企業で並び替え">
                                    企業
                                    <?php if (($sortColumn ?? 'company_name') === 'company_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>拠点</th>
                            <th>
                                <a href="#" class="sort-link" data-sort="doctor_name" title="産業医で並び替え">
                                    産業医
                                    <?php if (($sortColumn ?? '') === 'doctor_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="visit_frequency" title="訪問頻度で並び替え">
                                    訪問頻度
                                    <?php if (($sortColumn ?? '') === 'visit_frequency'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="regular_visit_hours" title="定期訪問時間で並び替え">
                                    定期訪問時間
                                    <?php if (($sortColumn ?? '') === 'regular_visit_hours'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="taxi_allowed" title="タクシー利用で並び替え">
                                    タクシー利用
                                    <?php if (($sortColumn ?? '') === 'taxi_allowed'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="tax_type" title="税種別で並び替え">
                                    税種別
                                    <?php if (($sortColumn ?? '') === 'tax_type'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="effective_date" title="反映日で並び替え">
                                    反映日
                                    <?php if (($sortColumn ?? '') === 'effective_date'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="start_date" title="契約期間で並び替え">
                                    契約期間
                                    <?php if (($sortColumn ?? '') === 'start_date'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="contract_status" title="ステータスで並び替え">
                                    ステータス
                                    <?php if (($sortColumn ?? '') === 'contract_status'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">契約書</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                            <?php 
                            $frequency = $contract['visit_frequency'] ?? 'monthly';
                            $frequencyInfo = get_visit_frequency_info($frequency);
                            $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                            $taxiAllowed = $contract['taxi_allowed'] ?? 0;
                            $taxType = $contract['tax_type'] ?? 'exclusive';
                            $isInclusive = $taxType === 'inclusive';
                            $isContractExpired = $contract['is_contract_expired'] ?? false;
                            
                            // 契約期間のチェック
                            $currentDate = date('Y-m-d');
                            $startDate = $contract['start_date'] ?? '';
                            $endDate = $contract['end_date'] ?? '';
                            
                            // 期間外かどうかの判定
                            $isOutsideContractPeriod = false;
                            $periodStatus = '';
                            
                            if ($startDate && $currentDate < $startDate) {
                                $isOutsideContractPeriod = true;
                                $periodStatus = 'before-start';
                            } elseif ($endDate && $currentDate > $endDate) {
                                $isOutsideContractPeriod = true;
                                $periodStatus = 'after-end';
                            }
                            
                            // 行のクラス決定
                            $rowClass = '';
                            if ($isContractExpired) {
                                $rowClass = 'table-secondary expired-contract';
                            } elseif ($isOutsideContractPeriod) {
                                $rowClass = 'table-secondary contract-outside-period';
                            } elseif (!$isVisitMonth && $frequency === 'bimonthly') {
                                $rowClass = 'table-light';
                            }
                            
                            // 週間スケジュールの取得
                            $weeklySchedule = '';
                            if ($frequency === 'weekly') {
                                if (!empty($contract['weekly_schedule'])) {
                                    $weeklySchedule = $contract['weekly_schedule'];
                                }
                            }
                            ?>
                            <tr class="<?= $rowClass ?>" <?= $isOutsideContractPeriod ? 'data-period-status="' . $periodStatus . '"' : '' ?>>
                                <td>
                                    <span class="badge bg-secondary <?= $isContractExpired ? 'opacity-50' : '' ?>">#<?= $contract['id'] ?></span>
                                    <?php if ($isContractExpired): ?>
                                        <br>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            期限切れ
                                        </small>
                                    <?php elseif ($isOutsideContractPeriod): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?php if ($periodStatus === 'before-start'): ?>
                                                開始前
                                            <?php else: ?>
                                                終了済
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-building text-muted me-1 <?= $isContractExpired ? 'opacity-50' : '' ?>"></i>
                                    <strong class="<?= $isContractExpired ? 'text-muted' : ($isOutsideContractPeriod ? 'text-muted' : '') ?>">
                                        <?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-muted me-1 <?= $isContractExpired ? 'opacity-50' : '' ?>"></i>
                                    <span class="<?= $isContractExpired ? 'text-muted' : ($isOutsideContractPeriod ? 'text-muted' : '') ?>">
                                        <?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="fas fa-user-md text-muted me-1 <?= $isContractExpired ? 'opacity-50' : '' ?>"></i>
                                    <span class="<?= $isContractExpired ? 'text-muted' : ($isOutsideContractPeriod ? 'text-muted' : '') ?>">
                                        <?= htmlspecialchars($contract['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : $frequencyInfo['class'] ?> <?= $isContractExpired ? 'opacity-75' : '' ?>">
                                        <i class="<?= $frequencyInfo['icon'] ?>"></i>
                                        <?= $frequencyInfo['label'] ?>
                                    </span>
                                    <?php if ($frequency === 'bimonthly' && !$isContractExpired): ?>
                                        <br>
                                        <small class="text-<?= $isVisitMonth ? 'success' : 'muted' ?>">
                                            <?= $isVisitMonth ? '今月訪問' : '今月非訪問' ?>
                                        </small>
                                    <?php elseif ($frequency === 'weekly' && $weeklySchedule && !$isContractExpired): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= $weeklySchedule ?>曜
                                        </small>
                                            <br>
                                        <small class="text-muted">
                                            <?php if ($contract['exclude_holidays'] ?? 1): ?>
                                                <i class="fas fa-calendar-times text-warning me-1"></i>祝日非訪問
                                            <?php else: ?>
                                                <i class="fas fa-calendar-check text-info me-1"></i>祝日訪問可
                                            <?php endif; ?>
                                            
                                            <?php 
                                            // 非訪問日設定の有無をチェック
                                            $db = Database::getInstance()->getConnection();
                                            $currentYear = date('Y');
                                            
                                            $nonVisitSql = "SELECT COUNT(*) as count FROM contract_non_visit_days 
                                                            WHERE contract_id = :contract_id 
                                                            AND (year = :year 
                                                            OR (is_recurring = 1 AND year < :year2)) 
                                                            AND is_active = 1";
                                            $stmt = $db->prepare($nonVisitSql);
                                            $stmt->execute(['contract_id' => $contract['id'], 'year' => $currentYear, 'year2' => $currentYear]);
                                            $nonVisitCount = $stmt->fetchColumn();
                                            ?>
                                            <br>
                                            <?php if ($nonVisitCount > 0): ?>
                                                <span class="ms-2">
                                                    <i class="fas fa-ban text-danger me-1"></i>非訪問日<?= $nonVisitCount ?>件設定済
                                                </span>
                                            <?php else: ?>
                                                <span class="ms-2 text-muted">
                                                    <i class="fas fa-calendar-day me-1"></i>非訪問日未設定
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($frequency === 'spot'): ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'info' ?>">
                                            -
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'info' ?> <?= $isContractExpired ? 'opacity-75' : '' ?>">
                                            <?= number_format($contract['regular_visit_hours'] ?? 0, 1) ?>時間
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <?php 
                                            switch($frequency) {
                                                case 'weekly': echo '1回あたり'; break;
                                                case 'bimonthly': echo '隔月'; break;
                                                default: echo '月間'; break;
                                            }
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($taxiAllowed): ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'success' ?> <?= $isContractExpired ? 'opacity-75' : '' ?>">
                                            <i class="fas fa-taxi me-1"></i>
                                            可
                                        </span>
                                        <br>
                                        <small class="text-muted">利用可能</small>
                                    <?php else: ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'danger' ?> <?= $isContractExpired ? 'opacity-75' : '' ?>">
                                            <i class="fas fa-ban me-1"></i>
                                            不可
                                        </span>
                                        <br>
                                        <small class="text-muted">利用不可</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : ($isInclusive ? 'info' : 'warning') ?>">
                                        <i class="fas fa-<?= $isInclusive ? 'calculator' : 'percent' ?> me-1"></i>
                                        <?= $isInclusive ? '内税' : '外税' ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <?= $isInclusive ? '税込価格' : '税抜価格' ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="<?= $isContractExpired ? 'text-muted' : ($isOutsideContractPeriod ? 'text-muted' : '') ?>">
                                        <?php if (!empty($contract['effective_date'])): ?>
                                            <i class="fas fa-calendar-check text-success me-1"></i>
                                            <?= format_date($contract['effective_date']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="<?= $isContractExpired ? 'text-muted' : ($isOutsideContractPeriod ? 'text-muted' : '') ?>">
                                        <?= format_date($contract['start_date']) ?>
                                        〜
                                        <?php if ($contract['end_date']): ?>
                                            <span class="<?= $isContractExpired ? 'text-danger fw-bold' : '' ?>">
                                                <?= format_date($contract['end_date']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">無期限</span>
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($isContractExpired): ?>
                                        <br>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php 
                                            $daysAfterEnd = ceil((time() - strtotime($endDate)) / (60 * 60 * 24));
                                            echo "終了から{$daysAfterEnd}日経過";
                                            ?>
                                        </small>
                                    <?php elseif ($isOutsideContractPeriod): ?>
                                        <br>
                                        <small class="text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php if ($periodStatus === 'before-start'): ?>
                                                <?php 
                                                $daysUntilStart = ceil((strtotime($startDate) - time()) / (60 * 60 * 24));
                                                echo "開始まであと{$daysUntilStart}日";
                                                ?>
                                            <?php else: ?>
                                                <?php 
                                                $daysAfterEnd = ceil((time() - strtotime($endDate)) / (60 * 60 * 24));
                                                echo "終了から{$daysAfterEnd}日経過";
                                                ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php
                                        if ($isContractExpired) {
                                            echo 'dark';
                                        } elseif ($isOutsideContractPeriod) {
                                            echo 'secondary';
                                        } else {
                                            switch($contract['contract_status']) {
                                                case 'active': echo 'success'; break;
                                                case 'inactive': echo 'warning'; break;
                                                default: echo 'secondary';
                                            }
                                        }
                                    ?> <?= $isContractExpired ? 'opacity-75' : '' ?>">
                                        <?php if ($isContractExpired): ?>
                                            期間終了
                                        <?php else: ?>
                                            <?= get_status_label($contract['contract_status']) ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($isContractExpired): ?>
                                        <br>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            期限切れ
                                        </small>
                                    <?php elseif ($isOutsideContractPeriod): ?>
                                        <br>
                                        <small class="text-muted">
                                            期間<?= $periodStatus === 'before-start' ? '前' : '後' ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($contract['contract_file_path'])): ?>
                                        <a href="<?= htmlspecialchars($contract['contract_file_path'], ENT_QUOTES, 'UTF-8') ?>" 
                                        class="btn btn-sm btn-outline-primary <?= ($isContractExpired || $isOutsideContractPeriod) ? 'opacity-50' : '' ?>" 
                                        target="_blank"
                                        title="<?= htmlspecialchars($contract['contract_file_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">なし</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url("contracts/{$contract['id']}/edit") ?>" 
                                        class="btn btn-outline-primary <?= $isContractExpired ? 'opacity-75' : '' ?>" title="編集">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-info <?= $isContractExpired ? 'opacity-75' : '' ?>" 
                                                onclick="showContractDetails(<?= $contract['id'] ?>)"
                                                title="詳細">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($frequency === 'weekly' && !$isContractExpired): ?>
                                            <a href="<?= base_url("non_visit_days/{$contract['id']}") ?>" 
                                            class="btn btn-outline-warning" 
                                            title="非訪問日設定">
                                                <i class="fas fa-calendar-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($contract['contract_status'] === 'active' && !$isContractExpired): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="terminateContract(<?= $contract['id'] ?>)"
                                                    title="契約終了">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- モバイル表示: カード形式 (1024px未満) -->
            <div class="mobile-contract-cards d-lg-none">
                <?php foreach ($contracts as $contract): ?>
                    <?php 
                    $frequency = $contract['visit_frequency'] ?? 'monthly';
                    $frequencyInfo = get_visit_frequency_info($frequency);
                    $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                    $taxiAllowed = $contract['taxi_allowed'] ?? 0;
                    $taxType = $contract['tax_type'] ?? 'exclusive';
                    $isInclusive = $taxType === 'inclusive';
                    $isContractExpired = $contract['is_contract_expired'] ?? false;
                    
                    // 契約期間のチェック
                    $currentDate = date('Y-m-d');
                    $startDate = $contract['start_date'] ?? '';
                    $endDate = $contract['end_date'] ?? '';
                    
                    // 期間外かどうかの判定
                    $isOutsideContractPeriod = false;
                    $periodStatus = '';
                    
                    if ($startDate && $currentDate < $startDate) {
                        $isOutsideContractPeriod = true;
                        $periodStatus = 'before-start';
                    } elseif ($endDate && $currentDate > $endDate) {
                        $isOutsideContractPeriod = true;
                        $periodStatus = 'after-end';
                    }
                    
                    // カードのクラス決定
                    $cardClass = 'contract-card';
                    if ($isContractExpired) {
                        $cardClass .= ' expired-contract-card';
                    } elseif ($isOutsideContractPeriod) {
                        $cardClass .= ' outside-period-card';
                    } elseif (!$isVisitMonth && $frequency === 'bimonthly') {
                        $cardClass .= ' non-visit-month-card';
                    }
                    
                    // 週間スケジュールの取得
                    $weeklySchedule = '';
                    if ($frequency === 'weekly') {
                        if (!empty($contract['weekly_schedule'])) {
                            $weeklySchedule = $contract['weekly_schedule'];
                        }
                    }
                    ?>
                    
                    <div class="<?= $cardClass ?>" data-contract-id="<?= $contract['id'] ?>">
                        <!-- カードヘッダー -->
                        <div class="contract-card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-secondary mb-2">#<?= $contract['id'] ?></span>
                                    <?php if ($isContractExpired): ?>
                                        <span class="badge bg-danger ms-1">期限切れ</span>
                                    <?php elseif ($isOutsideContractPeriod): ?>
                                        <span class="badge bg-warning ms-1">
                                            <?= $periodStatus === 'before-start' ? '開始前' : '終了済' ?>
                                        </span>
                                    <?php endif; ?>
                                    <h6 class="contract-card-title mb-1">
                                        <i class="fas fa-building text-muted me-1"></i>
                                        <?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </h6>
                                    <p class="contract-card-subtitle mb-0">
                                        <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                        <?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php
                                        if ($isContractExpired) {
                                            echo 'dark';
                                        } elseif ($isOutsideContractPeriod) {
                                            echo 'secondary';
                                        } else {
                                            switch($contract['contract_status']) {
                                                case 'active': echo 'success'; break;
                                                case 'inactive': echo 'warning'; break;
                                                default: echo 'secondary';
                                            }
                                        }
                                    ?>">
                                        <?php if ($isContractExpired): ?>
                                            期間終了
                                        <?php else: ?>
                                            <?= get_status_label($contract['contract_status']) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- カード本体 -->
                        <div class="contract-card-body">
                            <!-- 産業医 -->
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="fas fa-user-md text-muted me-1"></i>産業医
                                </span>
                                <span class="contract-info-value">
                                    <?= htmlspecialchars($contract['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            
                            <!-- 訪問頻度 -->
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="<?= $frequencyInfo['icon'] ?> me-1"></i>訪問頻度
                                </span>
                                <span class="contract-info-value">
                                    <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : $frequencyInfo['class'] ?>">
                                        <?= $frequencyInfo['label'] ?>
                                    </span>
                                    <?php if ($frequency === 'bimonthly' && !$isContractExpired): ?>
                                        <small class="text-<?= $isVisitMonth ? 'success' : 'muted' ?> ms-2">
                                            <?= $isVisitMonth ? '今月訪問' : '今月非訪問' ?>
                                        </small>
                                    <?php elseif ($frequency === 'weekly' && $weeklySchedule && !$isContractExpired): ?>
                                        <small class="text-muted ms-2"><?= $weeklySchedule ?>曜</small>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- 定期訪問時間 -->
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="fas fa-clock text-muted me-1"></i>定期訪問時間
                                </span>
                                <span class="contract-info-value">
                                    <?php if ($frequency === 'spot'): ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'warning' ?>">
                                            -
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'info' ?>">
                                            <?= number_format($contract['regular_visit_hours'] ?? 0, 1) ?>時間
                                        </span>
                                        <small class="text-muted ms-2">
                                            <?php 
                                            switch($frequency) {
                                                case 'weekly': echo '1回あたり'; break;
                                                case 'bimonthly': echo '隔月'; break;
                                                default: echo '月間'; break;
                                            }
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- タクシー利用 -->
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="fas fa-taxi text-muted me-1"></i>タクシー利用
                                </span>
                                <span class="contract-info-value">
                                    <?php if ($taxiAllowed): ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'success' ?>">
                                            <i class="fas fa-check me-1"></i>可
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : 'danger' ?>">
                                            <i class="fas fa-ban me-1"></i>不可
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- 税種別 -->
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="fas fa-file-invoice text-muted me-1"></i>税種別
                                </span>
                                <span class="contract-info-value">
                                    <span class="badge bg-<?= ($isContractExpired || $isOutsideContractPeriod) ? 'secondary' : ($isInclusive ? 'info' : 'warning') ?>">
                                        <i class="fas fa-<?= $isInclusive ? 'calculator' : 'percent' ?> me-1"></i>
                                        <?= $isInclusive ? '内税' : '外税' ?>
                                    </span>
                                </span>
                            </div>

                            <!-- 反映日 -->
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="fas fa-calendar-check text-muted me-1"></i>反映日
                                </span>
                                <span class="contract-info-value">
                                    <?php if (!empty($contract['effective_date'])): ?>
                                        <small><?= format_date($contract['effective_date']) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">未設定</small>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- 契約期間 -->
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="fas fa-calendar-alt text-muted me-1"></i>契約期間
                                </span>
                                <span class="contract-info-value">
                                    <small>
                                        <?= format_date($contract['start_date']) ?>
                                        〜
                                        <?php if ($contract['end_date']): ?>
                                            <span class="<?= $isContractExpired ? 'text-danger fw-bold' : '' ?>">
                                                <?= format_date($contract['end_date']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">無期限</span>
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($isContractExpired): ?>
                                        <br>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php 
                                            $daysAfterEnd = ceil((time() - strtotime($endDate)) / (60 * 60 * 24));
                                            echo "終了から{$daysAfterEnd}日経過";
                                            ?>
                                        </small>
                                    <?php elseif ($isOutsideContractPeriod): ?>
                                        <br>
                                        <small class="text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php if ($periodStatus === 'before-start'): ?>
                                                <?php 
                                                $daysUntilStart = ceil((strtotime($startDate) - time()) / (60 * 60 * 24));
                                                echo "開始まであと{$daysUntilStart}日";
                                                ?>
                                            <?php else: ?>
                                                <?php 
                                                $daysAfterEnd = ceil((time() - strtotime($endDate)) / (60 * 60 * 24));
                                                echo "終了から{$daysAfterEnd}日経過";
                                                ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- 契約書 -->
                            <?php if (!empty($contract['contract_file_path'])): ?>
                            <div class="contract-info-row">
                                <span class="contract-info-label">
                                    <i class="fas fa-file-pdf text-muted me-1"></i>契約書
                                </span>
                                <span class="contract-info-value">
                                    <a href="<?= htmlspecialchars($contract['contract_file_path'], ENT_QUOTES, 'UTF-8') ?>" 
                                    class="btn btn-sm btn-outline-primary"
                                    target="_blank">
                                        <i class="fas fa-download me-1"></i>ダウンロード
                                    </a>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- カードフッター: アクション -->
                        <div class="contract-card-footer">
                            <div class="btn-group w-100" role="group">
                                <a href="<?= base_url("contracts/{$contract['id']}/edit") ?>" 
                                class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>編集
                                </a>
                                <button type="button" 
                                        class="btn btn-outline-info btn-sm" 
                                        onclick="showContractDetails(<?= $contract['id'] ?>)">
                                    <i class="fas fa-eye me-1"></i>詳細
                                </button>
                                <?php if ($frequency === 'weekly' && !$isContractExpired): ?>
                                    <a href="<?= base_url("non_visit_days/{$contract['id']}") ?>" 
                                    class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-calendar-times me-1"></i>非訪問日
                                    </a>
                                <?php endif; ?>
                                <?php if ($contract['contract_status'] === 'active' && !$isContractExpired): ?>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm" 
                                            onclick="terminateContract(<?= $contract['id'] ?>)">
                                        <i class="fas fa-stop me-1"></i>終了
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>


            <!-- 凡例 -->
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>表示について：</strong>
                    グレー表示の契約は開始前または終了後です。
                    隔月契約で薄いグレーは非訪問月です。
                </small>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<!-- ページネーション（下部・企業版と同じスタイル） -->
<?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
<div class="mt-4">
    <nav aria-label="契約一覧ページネーション">
        <ul class="pagination justify-content-center">
            <!-- 前のページ -->
            <?php if ($pagination['current_page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= create_pagination_url($pagination['current_page'] - 1, $_GET) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                </li>
            <?php endif; ?>
            
            <!-- ページ番号 -->
            <?php
            $startPage = max(1, $pagination['current_page'] - 2);
            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
            
            if ($startPage > 1) {
                echo '<li class="page-item"><a class="page-link" href="' . create_pagination_url(1, $_GET) . '">1</a></li>';
                if ($startPage > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= create_pagination_url($i, $_GET) ?>"><?= $i ?></a>
                </li>
            <?php
            endfor;
            
            if ($endPage < $pagination['total_pages']) {
                if ($endPage < $pagination['total_pages'] - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="' . create_pagination_url($pagination['total_pages'], $_GET) . '">' . $pagination['total_pages'] . '</a></li>';
            }
            ?>
            
            <!-- 次のページ -->
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= create_pagination_url($pagination['current_page'] + 1, $_GET) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>

<!-- 契約詳細モーダル -->
<div class="modal fade" id="contractDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">契約詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contractDetailContent">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">読み込み中...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ページネーション用スタイル（企業版と統一） */
.pagination {
    margin: 0;
}

.pagination .page-link {
    color: #007bff;
    border: 1px solid #dee2e6;
    padding: 0.375rem 0.75rem;
    margin: 0 2px;
    border-radius: 0.375rem;
    transition: all 0.2s ease-in-out;
}

.pagination .page-link:hover {
    color: #0056b3;
    background-color: #e9ecef;
    border-color: #adb5bd;
}

.pagination .page-item.active .page-link {
    z-index: 3;
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    background-color: #fff;
    border-color: #dee2e6;
    cursor: not-allowed;
}

.form-select-sm {
    min-width: 80px;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}

/* ページネーション情報のスタイル */
.card-body.border-bottom.bg-light {
    background-color: #f8f9fa !important;
    padding: 0.75rem 1rem;
}

/* 基本スタイル */
.table {
    table-layout: auto;
    width: 100%;
}

.table td, .table th {
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

/* 期限切れ契約のスタイル */
.expired-contract {
    background-color: #f8f9fa !important;
    opacity: 0.7;
}

.expired-contract:hover {
    background-color: #e9ecef !important;
}

.opacity-50 {
    opacity: 0.5 !important;
}

.opacity-75 {
    opacity: 0.75 !important;
}

/* タクシー可否のバッジスタイル */
.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
}

.badge.bg-dark {
    background-color: #212529 !important;
}

/* 請求方法のバッジスタイル */
.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000 !important;
}

/* タクシーアイコンの色調整 */
.fas.fa-taxi {
    color: #ffc107;
}

.fas.fa-ban {
    color: #ffffff;
}

/* 請求方法アイコンの色調整 */
.fas.fa-percent {
    color: #000000;
}

.fas.fa-minus-circle {
    color: #000000;
}

/* 期間外契約のスタイル */
.contract-outside-period {
    background-color: #f8f9fa !important;
    opacity: 0.7;
}

.contract-outside-period:hover {
    background-color: #e9ecef !important;
    opacity: 0.8;
}

/* 期間外契約のテキストを少し暗くする */
.contract-outside-period td {
    color: #6c757d;
}

/* 期間外契約のバッジを統一 */
.contract-outside-period .badge:not(.bg-secondary) {
    background-color: #6c757d !important;
}

/* 期限切れ強調スタイル */
.text-danger.fw-bold {
    color: #dc3545 !important;
    font-weight: bold !important;
}

/* チェックボックスの調整 */
.form-check {
    padding-top: 0.375rem;
}

.form-check-input {
    margin-top: 0.125rem;
}

/* モーダルのスタイル */
#contractDetailModal .modal-dialog {
    max-width: 900px;
}

#contractDetailModal .table th {
    background-color: #f8f9fa;
    font-weight: 600;
    width: 30%;
}

#contractDetailModal .badge {
    font-size: 0.8rem;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25em 0.5em;
    }
    
    /* ページネーション情報の調整 */
    .card-body.border-bottom.bg-light .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .card-body.border-bottom.bg-light .text-end {
        text-align: start !important;
    }
    
    /* ページネーションをモバイル対応 */
    .pagination {
        font-size: 0.9rem;
    }
    
    .pagination .page-link {
        padding: 0.375rem 0.5rem;
    }
}

/* 印刷対応 */
@media print {
    .btn, .btn-group, .pagination {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 0.85rem;
    }
}

/* ==================== モバイルカード形式のスタイル ==================== */

.mobile-contract-cards {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.contract-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.contract-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

/* 期限切れ契約カード */
.expired-contract-card {
    background-color: #f8f9fa;
    opacity: 0.8;
    border-color: #adb5bd;
}

/* 期間外契約カード */
.outside-period-card {
    background-color: #f8f9fa;
    opacity: 0.75;
    border-color: #ced4da;
}

/* 非訪問月カード */
.non-visit-month-card {
    background-color: #fefefe;
    border-color: #e9ecef;
}

/* カードヘッダー */
.contract-card-header {
    padding: 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.contract-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.contract-card-subtitle {
    font-size: 0.9rem;
    color: #6c757d;
}

/* カード本体 */
.contract-card-body {
    padding: 1rem;
}

.contract-info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f5;
}

.contract-info-row:last-child {
    border-bottom: none;
}

.contract-info-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
    flex: 0 0 40%;
    display: flex;
    align-items: center;
}

.contract-info-value {
    font-size: 0.9rem;
    color: #212529;
    font-weight: 500;
    flex: 1;
    text-align: right;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* カードフッター */
.contract-card-footer {
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.contract-card-footer .btn-group {
    display: flex;
    gap: 0.25rem;
}

.contract-card-footer .btn {
    flex: 1;
    font-size: 0.875rem;
    padding: 0.5rem 0.25rem;
    white-space: nowrap;
}

/* バッジの調整 */
.contract-card .badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
    font-weight: 600;
}

/* レスポンシブ調整 */
@media (max-width: 576px) {
    .contract-card-header {
        padding: 0.75rem;
    }
    
    .contract-card-body {
        padding: 0.75rem;
    }
    
    .contract-info-row {
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-start;
    }
    
    .contract-info-label {
        flex: none;
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .contract-info-value {
        flex: none;
        width: 100%;
        text-align: left;
        justify-content: flex-start;
    }
    
    .contract-card-footer .btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.2rem;
    }
    
    .contract-card-footer .btn i {
        font-size: 0.875rem;
    }
    
    /* ボタンテキストを小画面で非表示 */
    .contract-card-footer .btn span {
        display: none;
    }
}

/* タブレット表示 */
@media (min-width: 577px) and (max-width: 1023px) {
    .mobile-contract-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .contract-card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .contract-card-body {
        flex: 1;
    }
}

/* アニメーション */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.contract-card {
    animation: slideIn 0.3s ease-out;
}

/* デスクトップビューを1024px以上で表示 */
@media (min-width: 1024px) {
    .mobile-contract-cards {
        display: none !important;
    }
}

/* モバイル・タブレットビューを1024px未満で表示 */
@media (max-width: 1023px) {
    .table-responsive {
        display: none !important;
    }
}

/* 並び替えリンクのスタイル */
.sort-link {
    color: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 600;
}

.sort-link:hover {
    color: inherit;
    text-decoration: none;
    transform: translateX(2px);
}

.sort-link i {
    font-size: 0.875rem;
    opacity: 1;
    transition: transform 0.2s ease;
    color: #6c757d;
}

.sort-link:hover i {
    transform: scale(1.2);
    color: #495057;
}

/* アクティブな並び替え列を強調 */
th:has(.sort-link) {
    background-color: #f8f9fa;
    position: relative;
}

/* 並び替えアイコンがない列にもアイコンを表示(薄く) */
.table-light th a.sort-link:not(:has(i))::after {
    content: '\f0dc';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: 0.25rem;
    opacity: 0.3;
    font-size: 0.75rem;
}

/* 現在の並び替え列をハイライト */
.table-light th:has(.sort-link i) {
    background-color: #e7f1ff;
}

.table-light th a.sort-link {
    font-weight: 600;
}

/* モバイル用の並び替え */
.mobile-sort-section {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}

.mobile-sort-section .form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

@media (max-width: 767px) {
    .sort-link {
        font-size: 0.875rem;
    }
}
</style>

<script>
// 契約詳細表示
function showContractDetails(contractId) {
    const modal = new bootstrap.Modal(document.getElementById('contractDetailModal'));
    const content = document.getElementById('contractDetailContent');
    
    // ローディング表示
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">読み込み中...</p>
        </div>
    `;
    
    modal.show();
    
    // 契約詳細を表示
    setTimeout(() => {
        const contracts = <?= json_encode($contracts ?? []) ?>;
        const contract = contracts.find(c => c.id == contractId);
        
        if (contract) {
            const frequency = contract.visit_frequency || 'monthly';
            const frequencyInfo = getFrequencyInfo(frequency);
            
            // 修正: is_visit_monthの結果を直接使用
            // PHPで計算された訪問月判定を使用（bimonthly_typeを考慮済み）
            const isVisitMonth = contract.is_visit_month || false;
            
            const taxiAllowed = contract.taxi_allowed || 0;
            const taxType = contract.tax_type || 'exclusive';
            const isInclusive = taxType === 'inclusive';
            const isContractExpired = contract.is_contract_expired || false;
            
            // 契約期間のチェック
            const currentDate = new Date().toISOString().split('T')[0];
            const startDate = contract.start_date || '';
            const endDate = contract.end_date || '';
            
            let isOutsideContractPeriod = false;
            let periodStatus = '';
            let periodMessage = '';
            
            if (isContractExpired) {
                isOutsideContractPeriod = true;
                periodStatus = 'expired';
                const daysAfterEnd = Math.ceil((new Date() - new Date(endDate)) / (1000 * 60 * 60 * 24));
                periodMessage = `契約期間が終了(終了から${daysAfterEnd}日経過)`;
            } else if (startDate && currentDate < startDate) {
                isOutsideContractPeriod = true;
                periodStatus = 'before-start';
                const daysUntilStart = Math.ceil((new Date(startDate) - new Date()) / (1000 * 60 * 60 * 24));
                periodMessage = `開始まであと${daysUntilStart}日`;
            } else if (endDate && currentDate > endDate) {
                isOutsideContractPeriod = true;
                periodStatus = 'after-end';
                const daysAfterEnd = Math.ceil((new Date() - new Date(endDate)) / (1000 * 60 * 60 * 24));
                periodMessage = `終了から${daysAfterEnd}日経過`;
            }
            
            // 週間スケジュールの表示
            let weeklyScheduleHtml = '';
            let weeklyScheduleText = '';
            if (frequency === 'weekly') {
                const weeklyDays = contract.weekly_days || [];
                if (weeklyDays.length > 0) {
                    const dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                    weeklyScheduleText = weeklyDays.map(day => dayNames[day]).join('・');
                    weeklyScheduleHtml = `<br><small class="text-muted">${weeklyScheduleText}</small>`;
                } else if (contract.weekly_schedule) {
                    weeklyScheduleText = contract.weekly_schedule;
                    weeklyScheduleHtml = `<br><small class="text-muted">${weeklyScheduleText}</small>`;
                }
            }
            
            // 隔月契約のbimonthly_type表示
            let bimonthlyTypeHtml = '';
            if (frequency === 'bimonthly') {
                const bimonthlyType = contract.bimonthly_type || '';
                let bimonthlyTypeText = '';
                
                if (bimonthlyType === 'even') {
                    bimonthlyTypeText = '偶数月訪問(2,4,6,8,10,12月)';
                } else if (bimonthlyType === 'odd') {
                    bimonthlyTypeText = '奇数月訪問(1,3,5,7,9,11月)';
                } else {
                    bimonthlyTypeText = '未設定';
                }
                
                bimonthlyTypeHtml = `<br><small class="text-muted">${bimonthlyTypeText}</small>`;
            }
            
            content.innerHTML = `
                ${isOutsideContractPeriod ? `
                    <div class="alert alert-${periodStatus === 'expired' ? 'danger' : 'warning'}">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>${periodStatus === 'expired' ? '契約期間終了' : '契約期間外です'}</strong>
                        <br>
                        ${periodMessage}
                    </div>
                ` : ''}
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>基本情報</h6>
                        <table class="table table-sm">
                            <tr><th>契約ID</th><td>#${contract.id}</td></tr>
                            <tr><th>産業医</th><td>${contract.doctor_name}</td></tr>
                            <tr><th>企業</th><td>${contract.company_name}</td></tr>
                            <tr><th>拠点</th><td>${contract.branch_name}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>契約詳細</h6>
                        <table class="table table-sm">
                            <tr><th>訪問頻度</th><td>
                                <span class="badge bg-${(isContractExpired || isOutsideContractPeriod) ? 'secondary' : frequencyInfo.class}">
                                    ${frequencyInfo.label}
                                </span>
                                ${frequency === 'bimonthly' ? 
                                    bimonthlyTypeHtml + 
                                    `<br><small class="text-${isVisitMonth ? 'success' : 'muted'}">${isVisitMonth ? '今月は訪問月' : '今月は非訪問月'}</small>` : 
                                    weeklyScheduleHtml
                                }
                                ${frequency === 'weekly' ? `
                                    <br>
                                    <small class="text-muted">
                                        ${contract.exclude_holidays ? 
                                            '<i class="fas fa-calendar-times text-warning me-1"></i>祝日非訪問' : 
                                            '<i class="fas fa-calendar-check text-info me-1"></i>祝日訪問可'
                                        }
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        ${contract.non_visit_count > 0 ? 
                                            `<i class="fas fa-ban text-danger me-1"></i>非訪問日${contract.non_visit_count}件設定済` : 
                                            '<i class="fas fa-calendar-day text-muted me-1"></i>非訪問日未設定'
                                        }
                                    </small>
                                ` : ''}
                            </td></tr>
                            <tr><th>定期訪問時間</th><td>${frequency === 'spot' ? '-' : `${contract.regular_visit_hours || 0}時間/${frequency === 'weekly' ? '1回あたり' : (frequency === 'monthly' ? '月' : '隔月')}`}</td></tr>
                            <tr><th>タクシー利用</th><td>
                                ${taxiAllowed ? 
                                    `<span class="badge bg-${(isContractExpired || isOutsideContractPeriod) ? 'secondary' : 'success'}"><i class="fas fa-taxi me-1"></i>可</span><br><small class="text-muted">利用可能</small>` : 
                                    `<span class="badge bg-${(isContractExpired || isOutsideContractPeriod) ? 'secondary' : 'danger'}"><i class="fas fa-ban me-1"></i>不可</span><br><small class="text-muted">利用不可</small>`
                                }
                            </td></tr>
                            <tr><th>税種別</th><td>
                                <span class="badge bg-${(isContractExpired || isOutsideContractPeriod) ? 'secondary' : (isInclusive ? 'info' : 'warning')}">
                                    <i class="fas fa-${isInclusive ? 'calculator' : 'percent'} me-1"></i>
                                    ${isInclusive ? '内税' : '外税'}
                                </span>
                                <br><small class="text-muted">${isInclusive ? '税込価格' : '税抜価格'}</small>
                            </td></tr>
                            <tr><th>反映日</th><td>
                                ${contract.effective_date ? 
                                    `<i class="fas fa-calendar-check text-success me-1"></i>${contract.effective_date}` : 
                                    '<span class="text-muted">未設定</span>'
                                }
                            </td></tr>
                            <tr><th>開始日</th><td>${contract.start_date}</td></tr>
                            <tr><th>終了日</th><td>
                                <span class="${isContractExpired ? 'text-danger fw-bold' : ''}">
                                    ${contract.end_date || '無期限'}
                                </span>
                                ${isContractExpired ? '<br><small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>期限切れ</small>' : ''}
                            </td></tr>
                            <tr><th>ステータス</th><td>
                                <span class="badge bg-${isContractExpired ? 'dark' : getStatusClass(contract.contract_status)}">
                                    ${isContractExpired ? '期間終了' : getStatusLabel(contract.contract_status)}
                                </span>
                                ${isOutsideContractPeriod ? '<br><small class="text-muted">期間外</small>' : ''}
                            </td></tr>
                            <tr><th>契約書</th><td>
                                ${contract.contract_file_name && contract.contract_file_path ? 
                                    `<a href="${contract.contract_file_path}" target="_blank" class="btn btn-sm btn-outline-primary ${(isContractExpired || isOutsideContractPeriod) ? 'opacity-75' : ''}"><i class="fas fa-download me-1"></i>ダウンロード</a>` : 
                                    '<span class="text-muted">なし</span>'
                                }
                            </td></tr>
                        </table>
                    </div>
                </div>
                
                <!-- 管理者用追加操作 -->
                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url("contracts/") ?>${contract.id}/edit" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>編集
                    </a>
                    ${frequency === 'weekly' && !isContractExpired ? 
                        `<a href="<?= base_url("non_visit_days/") ?>${contract.id}" class="btn btn-outline-warning">
                            <i class="fas fa-calendar-times me-1"></i>非訪問日設定
                        </a>` : ''
                    }
                    ${contract.contract_status === 'active' && !isContractExpired ? 
                        `<button type="button" class="btn btn-outline-danger" onclick="terminateContract(${contract.id})">
                            <i class="fas fa-stop me-1"></i>契約終了
                        </button>` : ''
                    }
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    契約詳細の取得に失敗しました。
                </div>
            `;
        }
    }, 500);
}

// 契約終了処理
function terminateContract(contractId) {
    if (!confirm('この契約を終了しますか？\n\n注意：この操作は取り消せません。')) {
        return;
    }
    
    // CSRF トークンを取得
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Ajax リクエストで契約終了
    fetch(`<?= base_url('contracts/') ?>${contractId}/terminate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功時：ページをリロード
            location.reload();
        } else {
            alert('契約終了に失敗しました：' + (data.message || '不明なエラー'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('通信エラーが発生しました。');
    });
}

// 契約再開処理
function activateContract(contractId) {
    if (!confirm('この契約を再開しますか？')) {
        return;
    }
    
    // CSRF トークンを取得
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Ajax リクエストで契約再開
    fetch(`<?= base_url('contracts/') ?>${contractId}/activate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功時：ページをリロード
            location.reload();
        } else {
            alert('契約再開に失敗しました：' + (data.message || '不明なエラー'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('通信エラーが発生しました。');
    });
}

// ヘルパー関数
function getStatusClass(status) {
    const classes = {
        'active': 'success',
        'inactive': 'warning',
    };
    return classes[status] || 'secondary';
}

function getStatusLabel(status) {
    const labels = {
        'active': '有効',
        'inactive': '無効',
    };
    return labels[status] || status;
}

function getFrequencyInfo(frequency) {
    const info = {
        'monthly': {
            label: '毎月',
            class: 'primary',
            icon: 'fas fa-calendar-alt text-primary',
            description: '毎月1回の定期訪問'
        },
        'bimonthly': {
            label: '隔月',
            class: 'info',
            icon: 'fas fa-calendar-alt text-info',
            description: '2ヶ月に1回の定期訪問'
        },
        'weekly': {
            label: '毎週',
            class: 'success',
            icon: 'fas fa-calendar-week text-success',
            description: '指定した曜日に毎週訪問'
        },
        'spot': {
            label: 'スポット',
            class: 'warning',
            icon: 'fas fa-clock text-primary',
            description: '必要に応じて実施'
        }
    };
    return info[frequency] || info.monthly;
}

// 初期化処理
document.addEventListener('DOMContentLoaded', function() {
    // フィルター自動送信(selectボックス)
    const filterSelects = document.querySelectorAll('#frequency, #tax_type, #year');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
    
    // ステータスチェックボックスの自動送信
    const statusCheckboxes = document.querySelectorAll('input[name="status[]"]');
    statusCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.form.submit();
        });
    });
    
    // 表示件数変更時の自動送信(ページネーション設定エリアのセレクトボックス)
    const contractsPageSizeSelect = document.getElementById('contractsPageSize');
    if (contractsPageSizeSelect) {
        contractsPageSizeSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});

<?php
// ページネーション用URL生成関数
function create_pagination_url($page, $params) {
    $url_params = $params;
    $url_params['page'] = $page;
    
    // 空の値は除外
    $url_params = array_filter($url_params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return base_url('contracts') . '?' . http_build_query($url_params);
}
?>

// ==================== 並び替え機能 ====================

// デスクトップ用の並び替えリンク
const sortLinks = document.querySelectorAll('.sort-link');

sortLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        const sortColumn = this.getAttribute('data-sort');
        const currentUrl = new URL(window.location);
        const currentSort = currentUrl.searchParams.get('sort');
        const currentOrder = currentUrl.searchParams.get('order') || 'asc';
        
        // 同じ列をクリックした場合は昇順・降順を切り替え
        if (currentSort === sortColumn) {
            const newOrder = currentOrder === 'desc' ? 'asc' : 'desc';
            currentUrl.searchParams.set('order', newOrder);
        } else {
            // 新しい列の場合はデフォルトで昇順
            currentUrl.searchParams.set('sort', sortColumn);
            currentUrl.searchParams.set('order', 'asc');
        }
        
        // ページを1に戻す
        currentUrl.searchParams.set('page', '1');
        
        // 並び替え中表示
        showSortLoading();
        
        // リダイレクト
        window.location.href = currentUrl.toString();
    });
});

// モバイル用の並び替えセレクトボックス
const mobileSortSelect = document.getElementById('mobile_sort');
if (mobileSortSelect) {
    mobileSortSelect.addEventListener('change', function() {
        const [sortColumn, sortOrder] = this.value.split('-');
        
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('sort', sortColumn);
        currentUrl.searchParams.set('order', sortOrder);
        currentUrl.searchParams.set('page', '1');
        
        showSortLoading();
        window.location.href = currentUrl.toString();
    });
}

function showSortLoading() {
    const loadingHtml = `
        <div id="sortLoadingOverlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(255, 255, 255, 0.8); z-index: 9999; display: flex; 
            align-items: center; justify-content: center;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">並び替え中...</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', loadingHtml);
}
</script>