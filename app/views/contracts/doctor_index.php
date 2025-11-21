<?php
// app/views/contracts/doctor_index.php - ダッシュボード統一ページネーション版
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-contract me-2"></i>契約一覧</h2>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
    </a>
</div>

<!-- 検索・フィルター -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('contracts') ?>" class="row g-3" id="filterForm">
            <div class="col-md-2">
                <label for="search" class="form-label">検索</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                       placeholder="企業名/拠点名で検索">
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
        <option value="usage_percentage-desc" <?= ($sortColumn ?? '') === 'usage_percentage' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            今月の進捗(高い順)
        </option>
        <option value="usage_percentage-asc" <?= ($sortColumn ?? '') === 'usage_percentage' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            今月の進捗(低い順)
        </option>
        <option value="regular_visit_hours-desc" <?= ($sortColumn ?? '') === 'regular_visit_hours' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            契約時間(多い順)
        </option>
        <option value="regular_visit_hours-asc" <?= ($sortColumn ?? '') === 'regular_visit_hours' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            契約時間(少ない順)
        </option>
        <option value="visit_frequency-asc" <?= ($sortColumn ?? '') === 'visit_frequency' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            訪問頻度
        </option>
        <option value="start_date-desc" <?= ($sortColumn ?? '') === 'start_date' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            開始日(新しい順)
        </option>
        <option value="start_date-asc" <?= ($sortColumn ?? '') === 'start_date' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            開始日(古い順)
        </option>
    </select>
</div>

<!-- 契約一覧（ダッシュボード統一ページネーション対応） -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>契約一覧
                    <?php if (!empty($contracts)): ?>
                        <span class="badge bg-primary"><?= $pagination['total_count'] ?>件</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <?php if (isset($_GET['status']) && in_array('expired', (array)$_GET['status'])): ?>
                        <span class="badge bg-warning">期間終了含む</span>
                    <?php endif; ?>
                    
                    <!-- ページサイズ選択（ダッシュボード統一） -->
                    <div class="d-flex align-items-center">
                        <label for="contracts_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                        <select id="contracts_page_size" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" <?= ($pagination['per_page'] == 10) ? 'selected' : '' ?>>10件</option>
                            <option value="15" <?= ($pagination['per_page'] == 15) ? 'selected' : '' ?>>15件</option>
                            <option value="20" <?= ($pagination['per_page'] == 20) ? 'selected' : '' ?>>20件</option>
                            <option value="30" <?= ($pagination['per_page'] == 30) ? 'selected' : '' ?>>30件</option>
                            <option value="50" <?= ($pagination['per_page'] == 50) ? 'selected' : '' ?>>50件</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($contracts)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                <p class="text-muted">該当する契約が見つかりませんでした。</p>
            </div>
        <?php else: ?>
            <!-- デスクトップ表示: 通常のテーブル -->
            <div class="desktop-table d-none d-lg-block">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="#" class="sort-link" data-sort="company_name" title="企業名で並び替え">
                                        企業名
                                        <?php if (($sortColumn ?? 'company_name') === 'company_name'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>拠点</th>
                                <th class="text-center">
                                    <a href="#" class="sort-link" data-sort="visit_frequency" title="訪問頻度で並び替え">
                                        訪問頻度
                                        <?php if (($sortColumn ?? '') === 'visit_frequency'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="#" class="sort-link" data-sort="regular_visit_hours" title="契約時間で並び替え">
                                        契約時間
                                        <?php if (($sortColumn ?? '') === 'regular_visit_hours'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="#" class="sort-link" data-sort="usage_percentage" title="今月の進捗で並び替え">
                                        今月の進捗
                                        <?php if (($sortColumn ?? '') === 'usage_percentage'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="#" class="sort-link" data-sort="taxi_allowed" title="タクシー利用で並び替え">
                                        タクシー利用
                                        <?php if (($sortColumn ?? '') === 'taxi_allowed'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="#" class="sort-link" data-sort="tax_type" title="税種別で並び替え">
                                        税種別
                                        <?php if (($sortColumn ?? '') === 'tax_type'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="#" class="sort-link" data-sort="start_date" title="契約開始日で並び替え">
                                        契約開始日
                                        <?php if (($sortColumn ?? '') === 'start_date'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="#" class="sort-link" data-sort="end_date" title="契約終了日で並び替え">
                                        契約終了日
                                        <?php if (($sortColumn ?? '') === 'end_date'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="#" class="sort-link" data-sort="contract_status" title="状態で並び替え">
                                        状態
                                        <?php if (($sortColumn ?? '') === 'contract_status'): ?>
                                            <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contracts as $contract): ?>
                                <?php
                                $frequency = $contract['visit_frequency'] ?? 'monthly';
                                $contractStatus = $contract['contract_status'] ?? 'active';
                                $startDate = $contract['start_date'] ?? null;
                                $endDate = $contract['end_date'] ?? null;
                                $currentDate = date('Y-m-d');
                                $taxiAllowed = $contract['taxi_allowed'] ?? 0;
                                $taxType = $contract['tax_type'] ?? 'exclusive';
                                $isInclusive = $taxType === 'inclusive';
                                $isContractExpired = $contract['is_contract_expired'] ?? false;
                                
                                // 契約の有効性チェック
                                $isContractActive = ($contractStatus === 'active');
                                $isContractStarted = ($startDate && $startDate <= $currentDate);
                                $isContractValid = ($isContractActive && $isContractStarted && !$isContractExpired);
                                
                                // 訪問月かどうかの判定（有効な契約の場合のみ）
                                $isVisitMonth = false;
                                if ($isContractValid) {
                                    $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                                }
                                
                                // 行のスタイル決定
                                $rowClass = '';
                                $rowStatus = '';
                                if ($isContractExpired) {
                                    $rowClass = 'table-secondary expired-contract';
                                    $rowStatus = 'expired';
                                } elseif (!$isContractActive) {
                                    $rowClass = 'table-danger';
                                    $rowStatus = 'inactive';
                                } elseif (!$isContractStarted) {
                                    $rowClass = 'table-warning';
                                    $rowStatus = 'not_started';
                                } elseif ($frequency === 'bimonthly' && !$isVisitMonth) {
                                    $rowClass = 'table-secondary';
                                    $rowStatus = 'non_visit_month';
                                } else {
                                    $rowStatus = 'active';
                                }
                                
                                // 使用率に応じた色分け
                                $progressClass = 'bg-success';
                                if ($isContractValid && $isVisitMonth) {
                                    // 修正: 毎週契約の場合は動的に月間時間を計算
                                    if ($frequency === 'weekly') {
                                        require_once __DIR__ . '/../../core/Database.php';
                                        $db = Database::getInstance()->getConnection();
                                        $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                            $contract['id'], 
                                            $contract['regular_visit_hours'] ?? 0, 
                                            date('Y'), 
                                            date('n'),
                                            $db
                                        );
                                    } else {
                                        $monthlyHours = $contract['regular_visit_hours'] ?? 0;
                                    }
                                    
                                    $thisMonthHours = $contract['this_month_hours'] ?? 0;
                                    $usagePercentage = $monthlyHours > 0 ? ($thisMonthHours / $monthlyHours) * 100 : 0;
                                    
                                    if ($usagePercentage >= 100) {
                                        $progressClass = 'bg-danger';
                                    } elseif ($usagePercentage >= 80) {
                                        $progressClass = 'bg-warning';
                                    } elseif ($usagePercentage >= 60) {
                                        $progressClass = 'bg-info';
                                    }
                                }
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td>
                                        <div class="company-with-branch">
                                            <div class="company-name">
                                                <i class="fas fa-building me-1 text-primary <?= $isContractExpired ? 'opacity-50' : '' ?>"></i>
                                                <strong class="<?= $isContractExpired ? 'text-muted' : '' ?>">
                                                    <?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </div>
                                            
                                            <!-- 契約状態バッジ -->
                                            <?php if ($rowStatus !== 'active'): ?>
                                                <div class="status-badges">
                                                    <?php if ($rowStatus === 'expired'): ?>
                                                        <span class="badge bg-dark">
                                                            <i class="fas fa-calendar-times me-1"></i>期間終了
                                                        </span>
                                                    <?php elseif ($rowStatus === 'inactive'): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-ban me-1"></i>契約無効
                                                        </span>
                                                    <?php elseif ($rowStatus === 'not_started'): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock me-1"></i>開始前
                                                        </span>
                                                    <?php elseif ($rowStatus === 'non_visit_month'): ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-calendar-minus me-1"></i>非訪問月
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt me-1 text-primary <?= $isContractExpired ? 'opacity-50' : '' ?>"></i>
                                        <a href="#" class="text-decoration-none branch-info-link <?= $isContractExpired ? 'text-muted' : '' ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#branchInfoModal"
                                        data-branch-name="<?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch-address="<?= htmlspecialchars($contract['branch_address'] ?? '住所未登録', ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch-phone="<?= htmlspecialchars($contract['branch_phone'] ?? '電話番号未登録', ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch-email="<?= htmlspecialchars($contract['branch_email'] ?? 'メールアドレス未登録', ENT_QUOTES, 'UTF-8') ?>"
                                        data-company-name="<?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                        data-company-address="<?= htmlspecialchars($contract['company_address'] ?? '住所未登録', ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            <i class="fas fa-info-circle ms-1 text-info <?= $isContractExpired ? 'opacity-50' : '' ?>" title="クリックで詳細情報を表示"></i>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-<?= get_visit_frequency_class($frequency) ?> mb-1 <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                                <i class="<?= get_visit_frequency_icon($frequency) ?> me-1"></i>
                                                <?= get_visit_frequency_label($frequency) ?>
                                            </span>
                                            <?php if ($frequency === 'bimonthly' && $isContractValid): ?>
                                                <small class="text-<?= $isVisitMonth ? 'success' : 'muted' ?>">
                                                    <i class="fas fa-calendar-<?= $isVisitMonth ? 'check' : 'times' ?> me-1"></i>
                                                    <?= $isVisitMonth ? '今月訪問' : '今月休み' ?>
                                                </small>
                                            <?php elseif ($frequency === 'weekly' && $isContractValid): ?>
                                                <?php
                                                // 週間スケジュールを取得
                                                require_once __DIR__ . '/../../core/Database.php';
                                                $db = Database::getInstance()->getConnection();
                                                $sql = "SELECT day_of_week FROM contract_weekly_schedules WHERE contract_id = :contract_id ORDER BY day_of_week";
                                                $stmt = $db->prepare($sql);
                                                $stmt->execute(['contract_id' => $contract['id']]);
                                                $weeklyDays = array_column($stmt->fetchAll(), 'day_of_week');
                                                
                                                if (!empty($weeklyDays)):
                                                    $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                                                    $scheduleText = implode('・', array_map(function($day) use ($dayNames) {
                                                        return $dayNames[$day] ?? '';
                                                    }, $weeklyDays));
                                                ?>
                                                <small class="text-muted">
                                                    <?= $scheduleText ?>曜
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
                                                            <i class="fas fa-ban text-danger me-1"></i>
                                                            <a href="#" class="text-decoration-none text-danger" 
                                                            onclick="showNonVisitDays(<?= $contract['id'] ?>, '<?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                                                非訪問日<?= $nonVisitCount ?>件設定済
                                                            </a>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="ms-2 text-muted">
                                                            <i class="fas fa-calendar-day me-1"></i>非訪問日未設定
                                                        </span>
                                                    <?php endif; ?>
                                                </small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($frequency === 'spot'): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                        <span class="badge bg-info fs-6 <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                            <?= format_total_hours($contract['regular_visit_hours']) ?>
                                        </span>
                                        <?php if ($frequency === 'bimonthly' && $isContractValid): ?>
                                            <br><small class="text-muted">(2ヶ月に1回)</small>
                                        <?php elseif ($frequency === 'weekly' && $isContractValid): ?>
                                            <br><small class="text-muted">(1回あたり)</small>
                                            <?php
                                            // 当月の訪問回数を表示
                                            $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                $contract['id'], 
                                                $contract['regular_visit_hours'] ?? 0, 
                                                date('Y'), 
                                                date('n'),
                                                $db
                                            );
                                            $visitCount = ($contract['regular_visit_hours'] ?? 0) > 0 ? round($monthlyHours / ($contract['regular_visit_hours'] ?? 1)) : 0;
                                            ?>
                                            <br><small class="text-success">今月: <?= format_total_hours($monthlyHours) ?> (<?= $visitCount ?>回)</small>
                                        <?php else: ?>
                                            <br><small class="text-muted">(月間)</small>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isContractValid && $frequency === 'spot'): ?>
                                            <span class="text-muted">-</span>
                                        <?php elseif ($isContractExpired): ?>
                                            <div class="text-center">
                                                <i class="fas fa-calendar-times text-muted fa-2x"></i>
                                                <br><small class="text-danger">期間終了</small>
                                            </div>
                                        <?php elseif ($isContractValid && $isVisitMonth): ?>
                                            <?php 
                                            // 修正: 毎週契約の場合は動的に月間時間を計算
                                            if ($frequency === 'weekly') {
                                                $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                    $contract['id'], 
                                                    $contract['regular_visit_hours'] ?? 0, 
                                                    date('Y'), 
                                                    date('n'),
                                                    $db
                                                );
                                            } else {
                                                $monthlyHours = $contract['regular_visit_hours'] ?? 0;
                                            }
                                            
                                            $thisMonthHours = $contract['this_month_hours'] ?? 0; // 定期+延長の合計時間
                                            $usagePercentage = $monthlyHours > 0 ? ($thisMonthHours / $monthlyHours) * 100 : 0;
                                            ?>
                                            <div class="progress" style="height: 20px; min-width: 120px;">
                                                <div class="progress-bar <?= $progressClass ?>" 
                                                    role="progressbar" 
                                                    style="width: <?= min(100, $usagePercentage) ?>%"
                                                    title="<?= format_total_hours($thisMonthHours) ?> / <?= format_total_hours($monthlyHours) ?> (定期+延長)">
                                                    <small class="text-white fw-bold"><?= round($usagePercentage) ?>%</small>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?= format_total_hours($thisMonthHours) ?> / <?= format_total_hours($monthlyHours) ?>
                                                <?php if (($contract['extension_hours_only'] ?? 0) > 0): ?>
                                                    <br><span class="text-info">
                                                        (定期: <?= format_total_hours($contract['regular_hours_only'] ?? 0) ?> + 
                                                        延長: <?= format_total_hours($contract['extension_hours_only'] ?? 0) ?>)
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        <?php elseif ($isContractValid && !$isVisitMonth): ?>
                                            <div class="text-center">
                                                <i class="fas fa-calendar-times text-muted fa-2x"></i>
                                                <br><small class="text-muted">非訪問月</small>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <i class="fas fa-ban text-danger fa-2x"></i>
                                                <br><small class="text-danger">
                                                    <?php if ($rowStatus === 'inactive'): ?>
                                                        契約無効
                                                    <?php elseif ($rowStatus === 'not_started'): ?>
                                                        開始前
                                                    <?php else: ?>
                                                        利用不可
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($taxiAllowed): ?>
                                            <span class="badge bg-success <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                                <i class="fas fa-taxi me-1"></i>
                                                可
                                            </span>
                                            <br><small class="text-muted">利用可能</small>
                                        <?php else: ?>
                                            <span class="badge bg-danger <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                                <i class="fas fa-ban me-1"></i>
                                                不可
                                            </span>
                                            <br><small class="text-muted">利用不可</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $isContractExpired ? 'secondary' : ($isInclusive ? 'info' : 'warning') ?>">
                                            <i class="fas fa-<?= $isInclusive ? 'calculator' : 'percent' ?> me-1"></i>
                                            <?= $isInclusive ? '内税' : '外税' ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <?= $isInclusive ? '税込価格' : '税抜価格' ?>
                                        </small>
                                    </td>
                                    <td><?= format_date($contract['start_date']) ?></td>
                                    <td>
                                        <span class="<?= $isContractExpired ? 'text-danger fw-bold' : '' ?>">
                                            <?= format_date($contract['end_date']) ?: '無期限' ?>
                                        </span>
                                        <?php if ($isContractExpired): ?>
                                            <div class="small text-danger mt-1">
                                                <i class="fas fa-exclamation-triangle me-1"></i>期限切れ
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php
                                            if ($isContractExpired) {
                                                echo 'dark';
                                            } else {
                                                switch($contract['contract_status']) {
                                                    case 'active': 
                                                        if (!$isContractStarted) {
                                                            echo 'warning';
                                                        } else {
                                                            echo 'success';
                                                        }
                                                        break;
                                                    case 'inactive': echo 'secondary'; break;
                                                    case 'terminated': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            }
                                        ?> <?= $isContractExpired ? '' : 'opacity-50' ?>">
                                            <?php
                                            if ($isContractExpired) {
                                                echo '期間終了';
                                            } elseif ($contractStatus === 'active') {
                                                if (!$isContractStarted) {
                                                    echo '開始前';
                                                } else {
                                                    echo '有効';
                                                }
                                            } else {
                                                echo get_status_label($contractStatus);
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($isContractValid): ?>
                                                    <a href="<?= base_url("service_records/create?contract_id={$contract['id']}&return_to=contracts") ?>" 
                                                    class="btn btn-outline-primary" title="役務記録">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                <a href="<?= base_url("service_records?contract_id={$contract['id']}") ?>" 
                                                class="btn btn-outline-info" title="履歴">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-outline-danger" 
                                                        title="<?php
                                                            if ($rowStatus === 'expired') {
                                                                echo '契約期間が終了しているため利用できません';
                                                            } elseif ($rowStatus === 'inactive') {
                                                                echo '契約ステータスが無効のため利用できません';
                                                            } elseif ($rowStatus === 'not_started') {
                                                                echo '契約開始日前のため利用できません';
                                                            } else {
                                                                echo '利用できません';
                                                            }
                                                        ?>" 
                                                        disabled>
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" 
                                                        title="契約が無効のため履歴を表示できません" 
                                                        disabled>
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            <?php endif; ?>
                                            <!-- 料金情報ボタンを追加 -->
                                            <button type="button" 
                                                    class="btn btn-outline-secondary rate-info-btn" 
                                                    title="料金情報"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#rateInfoModal"
                                                    data-contract-id="<?= $contract['id'] ?>"
                                                    data-company-name="<?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-branch-name="<?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-visit-frequency="<?= htmlspecialchars($contract['visit_frequency'] ?? 'monthly', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-regular-rate="<?= $contract['regular_visit_rate'] ?? 0 ?>"
                                                    data-regular-extension-rate="<?= $contract['regular_extension_rate'] ?? 0 ?>"
                                                    data-emergency-rate="<?= $contract['emergency_visit_rate'] ?? 0 ?>"
                                                    data-spot-rate="<?= $contract['spot_rate'] ?? 0 ?>"
                                                    data-document-rate="<?= $contract['document_consultation_rate'] ?? 0 ?>"
                                                    data-use-remote-consultation="<?= $contract['use_remote_consultation'] ?? 0 ?>"
                                                    data-use-document-creation="<?= $contract['use_document_creation'] ?? 0 ?>"
                                                    data-tax-type="<?= htmlspecialchars($contract['tax_type'] ?? 'exclusive', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-tax-type-label="<?= htmlspecialchars($contract['tax_type_label'] ?? '外税', ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-yen-sign"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- モバイル表示: カード形式 -->
            <div class="mobile-cards d-lg-none">
                <?php foreach ($contracts as $contract): ?>
                    <?php
                    $frequency = $contract['visit_frequency'] ?? 'monthly';
                    $contractStatus = $contract['contract_status'] ?? 'active';
                    $startDate = $contract['start_date'] ?? null;
                    $endDate = $contract['end_date'] ?? null;
                    $currentDate = date('Y-m-d');
                    $taxiAllowed = $contract['taxi_allowed'] ?? 0;
                    $taxType = $contract['tax_type'] ?? 'exclusive';
                    $isInclusive = $taxType === 'inclusive';
                    $isContractExpired = $contract['is_contract_expired'] ?? false;
                    
                    // 契約の有効性チェック
                    $isContractActive = ($contractStatus === 'active');
                    $isContractStarted = ($startDate && $startDate <= $currentDate);
                    $isContractValid = ($isContractActive && $isContractStarted && !$isContractExpired);
                    
                    // 訪問月かどうかの判定
                    $isVisitMonth = false;
                    if ($isContractValid) {
                        $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                    }
                    
                    // カードのスタイル決定
                    $cardClass = '';
                    $rowStatus = '';
                    if ($isContractExpired) {
                        $cardClass = 'border-secondary expired-contract-card';
                        $rowStatus = 'expired';
                    } elseif (!$isContractActive) {
                        $cardClass = 'border-danger';
                        $rowStatus = 'inactive';
                    } elseif (!$isContractStarted) {
                        $cardClass = 'border-warning';
                        $rowStatus = 'not_started';
                    } elseif ($frequency === 'bimonthly' && !$isVisitMonth) {
                        $cardClass = 'border-secondary';
                        $rowStatus = 'non_visit_month';
                    } else {
                        $cardClass = 'border-success';
                        $rowStatus = 'active';
                    }
                    
                    // 使用率計算
                    $progressClass = 'bg-success';
                    $usagePercentage = 0;
                    if ($isContractValid && $isVisitMonth) {
                        // 修正: 毎週契約の場合は動的に月間時間を計算
                        if ($frequency === 'weekly') {
                            require_once __DIR__ . '/../../core/Database.php';
                            $db = Database::getInstance()->getConnection();
                            $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                $contract['id'], 
                                $contract['regular_visit_hours'] ?? 0, 
                                date('Y'), 
                                date('n'),
                                $db
                            );
                        } else {
                            $monthlyHours = $contract['regular_visit_hours'] ?? 0;
                        }
                        
                        $thisMonthHours = $contract['this_month_hours'] ?? 0;
                        $usagePercentage = $monthlyHours > 0 ? ($thisMonthHours / $monthlyHours) * 100 : 0;
                        
                        if ($usagePercentage >= 100) {
                            $progressClass = 'bg-danger';
                        } elseif ($usagePercentage >= 80) {
                            $progressClass = 'bg-warning';
                        } elseif ($usagePercentage >= 60) {
                            $progressClass = 'bg-info';
                        }
                    }
                    ?>
                    <div class="card mb-3 contract-card <?= $cardClass ?>">
                        <div class="card-body">
                            <!-- ヘッダー部分 -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="contract-header flex-grow-1">
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-building me-2 text-primary <?= $isContractExpired ? 'opacity-50' : '' ?>"></i>
                                        <span class="<?= $isContractExpired ? 'text-muted' : '' ?>">
                                            <?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <?php if ($isContractExpired): ?>
                                            <span class="badge bg-dark ms-2">期限切れ</span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1 <?= $isContractExpired ? 'opacity-50' : '' ?>"></i>
                                        <a href="#" class="text-decoration-none branch-info-link <?= $isContractExpired ? 'text-muted' : '' ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#branchInfoModal"
                                        data-branch-name="<?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch-address="<?= htmlspecialchars($contract['branch_address'] ?? '住所未登録', ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch-phone="<?= htmlspecialchars($contract['branch_phone'] ?? '電話番号未登録', ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch-email="<?= htmlspecialchars($contract['branch_email'] ?? 'メールアドレス未登録', ENT_QUOTES, 'UTF-8') ?>"
                                        data-company-name="<?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                        data-company-address="<?= htmlspecialchars($contract['company_address'] ?? '住所未登録', ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            <i class="fas fa-info-circle text-info <?= $isContractExpired ? 'opacity-50' : '' ?>" title="詳細情報"></i>
                                        </a>
                                    </p>
                                </div>
                                
                                <!-- ステータスバッジ -->
                                <div class="contract-status">
                                    <span class="badge bg-<?php
                                        if ($isContractExpired) {
                                            echo 'dark';
                                        } else {
                                            switch($contract['contract_status']) {
                                                case 'active': 
                                                    if (!$isContractStarted) {
                                                        echo 'warning';
                                                    } else {
                                                        echo 'success';
                                                    }
                                                    break;
                                                case 'inactive': echo 'secondary'; break;
                                                case 'terminated': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        }
                                    ?>">
                                        <?php
                                        if ($isContractExpired) {
                                            echo '期間終了';
                                        } elseif ($contractStatus === 'active') {
                                            if (!$isContractStarted) {
                                                echo '開始前';
                                            } else {
                                                echo '有効';
                                            }
                                        } else {
                                            echo get_status_label($contractStatus);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <!-- 契約詳細情報 -->
                            <div class="row mb-3">
                                <div class="col-3">
                                    <div class="info-item">
                                        <small class="text-muted d-block">訪問頻度</small>
                                        <span class="badge bg-<?= get_visit_frequency_class($frequency) ?> <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                            <i class="<?= get_visit_frequency_icon($frequency) ?> me-1"></i>
                                            <?= get_visit_frequency_label($frequency) ?>
                                        </span>
                                        <?php if ($frequency === 'bimonthly' && $isContractValid): ?>
                                            <br><small class="text-<?= $isVisitMonth ? 'success' : 'muted' ?>">
                                                <i class="fas fa-calendar-<?= $isVisitMonth ? 'check' : 'times' ?> me-1"></i>
                                                <?= $isVisitMonth ? '今月訪問' : '今月休み' ?>
                                            </small>
                                        <?php elseif ($frequency === 'weekly' && $isContractValid): ?>
                                            <?php
                                            // 週間スケジュールを取得（モバイル用）
                                            require_once __DIR__ . '/../../core/Database.php';
                                            $db = Database::getInstance()->getConnection();
                                            $sql = "SELECT day_of_week FROM contract_weekly_schedules WHERE contract_id = :contract_id ORDER BY day_of_week";
                                            $stmt = $db->prepare($sql);
                                            $stmt->execute(['contract_id' => $contract['id']]);
                                            $weeklyDays = array_column($stmt->fetchAll(), 'day_of_week');
                                            
                                            if (!empty($weeklyDays)):
                                                $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                                                $scheduleText = implode('・', array_map(function($day) use ($dayNames) {
                                                    return $dayNames[$day] ?? '';
                                                }, $weeklyDays));
                                            ?>
                                            <br><small class="text-muted">
                                                <?= $scheduleText ?>曜
                                            </small>
                                            <br><small class="text-muted">
                                                <?php if ($contract['exclude_holidays'] ?? 1): ?>
                                                    <i class="fas fa-calendar-times text-warning me-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-calendar-check text-info me-1"></i>祝日訪問可
                                                <?php endif; ?>
                                            </small>
                                            <br><small class="text-muted">
                                                <?php 
                                                // 非訪問日設定の有無をチェック（モバイル用）
                                                $currentYear = date('Y');
                                                $nonVisitSql = "SELECT COUNT(*) as count FROM contract_non_visit_days 
                                                                WHERE contract_id = :contract_id 
                                                                AND (year = :year
                                                                OR (is_recurring = 1 AND year < :year2)) 
                                                                AND is_active = 1";
                                                $stmt = $db->prepare($nonVisitSql);
                                                $stmt->execute(['contract_id' => $contract['id'], 'year' => $currentYear, 'year2' => $currentYear]);
                                                $nonVisitCount = $stmt->fetchColumn();
                                                
                                                if ($nonVisitCount > 0): ?>
                                                    <i class="fas fa-ban text-danger me-1"></i>
                                                    <a href="#" class="text-decoration-none text-danger" 
                                                    onclick="showNonVisitDays(<?= $contract['id'] ?>, '<?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                                        非訪問日<?= $nonVisitCount ?>件
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fas fa-calendar-day text-muted me-1"></i>非訪問日未設定
                                                <?php endif; ?>
                                            </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="info-item">
                                        <small class="text-muted d-block">契約時間</small>
                                        <?php if ($frequency === 'spot'): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                        <span class="badge bg-info <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                            <?= format_total_hours($contract['regular_visit_hours']) ?>
                                        </span>
                                        <?php if ($frequency === 'bimonthly'): ?>
                                            <br><small class="text-muted">(2ヶ月に1回)</small>
                                        <?php elseif ($frequency === 'weekly' && $isContractValid): ?>
                                            <br><small class="text-muted">(1回あたり)</small>
                                            <?php
                                            // 当月の訪問回数を表示（モバイル用）
                                            $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                $contract['id'], 
                                                $contract['regular_visit_hours'] ?? 0, 
                                                date('Y'), 
                                                date('n'),
                                                $db
                                            );
                                            $visitCount = ($contract['regular_visit_hours'] ?? 0) > 0 ? round($monthlyHours / ($contract['regular_visit_hours'] ?? 1)) : 0;
                                            ?>
                                            <br><small class="text-success">今月: <?= format_total_hours($monthlyHours) ?> (<?= $visitCount ?>回)</small>
                                        <?php else: ?>
                                            <br><small class="text-muted">(月間)</small>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="info-item">
                                        <small class="text-muted d-block">タクシー</small>
                                        <?php if ($taxiAllowed): ?>
                                            <span class="badge bg-success <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                                <i class="fas fa-taxi me-1"></i>
                                                可
                                            </span>
                                            <br><small class="text-muted">利用可能</small>
                                        <?php else: ?>
                                            <span class="badge bg-danger <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                                <i class="fas fa-ban me-1"></i>
                                                不可
                                            </span>
                                            <br><small class="text-muted">利用不可</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="info-item">
                                        <small class="text-muted d-block">税種別</small>
                                        <span class="badge bg-<?= $isInclusive ? 'info' : 'warning' ?> <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                            <i class="fas fa-<?= $isInclusive ? 'calculator' : 'percent' ?> me-1"></i>
                                            <?= $isInclusive ? '内税' : '外税' ?>
                                        </span>
                                        <br><small class="text-muted">
                                            <?= $isInclusive ? '税込価格' : '税抜価格' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- 進捗表示 -->
                            <div class="progress-section mb-3">
                                <small class="text-muted d-block mb-2">今月の進捗</small>
                                <?php if ($frequency === 'spot'): ?>
                                    <div class="text-center text-muted">
                                        <span>-</span>
                                    </div>
                                <?php elseif ($isContractExpired): ?>
                                    <div class="text-center text-danger">
                                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                        <br><span>期間終了</span>
                                        <br><small class="text-danger">契約期間終了</small>
                                    </div>
                                <?php elseif ($isContractValid && $isVisitMonth): ?>
                                    <?php 
                                    // 修正: 毎週契約の場合は動的に月間時間を計算
                                    if ($frequency === 'weekly') {
                                        require_once __DIR__ . '/../../core/Database.php';
                                        $db = Database::getInstance()->getConnection();
                                        $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                            $contract['id'], 
                                            $contract['regular_visit_hours'] ?? 0, 
                                            date('Y'), 
                                            date('n'),
                                            $db
                                        );
                                    } else {
                                        $monthlyHours = $contract['regular_visit_hours'] ?? 0;
                                    }
                                    
                                    $thisMonthHours = $contract['this_month_hours'] ?? 0; // 定期+延長の合計時間
                                    $usagePercentage = $monthlyHours > 0 ? ($thisMonthHours / $monthlyHours) * 100 : 0;
                                    ?>
                                    <div class="progress mb-2" style="height: 24px;">
                                        <div class="progress-bar <?= $progressClass ?>" 
                                            role="progressbar" 
                                            style="width: <?= min(100, $usagePercentage) ?>%">
                                            <small class="text-white fw-bold"><?= round($usagePercentage) ?>%</small>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= format_total_hours($thisMonthHours) ?> / <?= format_total_hours($monthlyHours) ?>
                                        <?php if (($contract['extension_hours_only'] ?? 0) > 0): ?>
                                            <br><span class="text-info">
                                                定期: <?= format_total_hours($contract['regular_hours_only'] ?? 0) ?> + 
                                                延長: <?= format_total_hours($contract['extension_hours_only'] ?? 0) ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                <?php elseif ($isContractValid && !$isVisitMonth): ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                        <br><span>非訪問月</span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-danger">
                                        <i class="fas fa-ban fa-2x mb-2"></i>
                                        <br><span>
                                            <?php if ($rowStatus === 'inactive'): ?>
                                                契約無効
                                            <?php elseif ($rowStatus === 'not_started'): ?>
                                                開始前
                                            <?php else: ?>
                                                利用不可
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- 契約期間 -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">開始日</small>
                                    <span><?= format_date($contract['start_date']) ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">終了日</small>
                                    <span class="<?= $isContractExpired ? 'text-danger fw-bold' : '' ?>">
                                        <?= format_date($contract['end_date']) ?: '無期限' ?>
                                    </span>
                                    <?php if ($isContractExpired): ?>
                                        <br><small class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>期限切れ
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 操作ボタン -->
                            <div class="d-flex gap-2">
                                <?php if ($isContractValid): ?>
                                    <?php if ($isVisitMonth): ?>
                                        <a href="<?= base_url("service_records/create?contract_id={$contract['id']}&return_to=contracts") ?>" 
                                        class="btn btn-primary btn-sm flex-fill">
                                            <i class="fas fa-plus me-1"></i>役務記録
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm flex-fill" 
                                                title="非訪問月のため役務記録は作成できません" 
                                                disabled>
                                            <i class="fas fa-calendar-minus me-1"></i>非訪問月
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= base_url("service_records?contract_id={$contract['id']}") ?>" 
                                    class="btn btn-outline-info btn-sm flex-fill">
                                        <i class="fas fa-history me-1"></i>履歴
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-outline-danger btn-sm flex-fill" 
                                            title="<?php
                                                if ($rowStatus === 'expired') {
                                                    echo '契約期間が終了しているため利用できません';
                                                } elseif ($rowStatus === 'inactive') {
                                                    echo '契約ステータスが無効のため利用できません';
                                                } elseif ($rowStatus === 'not_started') {
                                                    echo '契約開始日前のため利用できません';
                                                } else {
                                                    echo '利用できません';
                                                }
                                            ?>" 
                                            disabled>
                                        <i class="fas fa-ban me-1"></i>利用不可
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm flex-fill" 
                                            title="契約が無効のため履歴を表示できません" 
                                            disabled>
                                        <i class="fas fa-history me-1"></i>履歴
                                    </button>
                                <?php endif; ?>
                                <!-- 料金情報ボタンを追加 -->
                                <button type="button" 
                                        class="btn btn-outline-secondary rate-info-btn" 
                                        title="料金情報"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#rateInfoModal"
                                        data-contract-id="<?= $contract['id'] ?>"
                                        data-company-name="<?= htmlspecialchars($contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch-name="<?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>"
                                        data-visit-frequency="<?= htmlspecialchars($contract['visit_frequency'] ?? 'monthly', ENT_QUOTES, 'UTF-8') ?>"
                                        data-regular-rate="<?= $contract['regular_visit_rate'] ?? 0 ?>"
                                        data-regular-extension-rate="<?= $contract['regular_extension_rate'] ?? 0 ?>"
                                        data-emergency-rate="<?= $contract['emergency_visit_rate'] ?? 0 ?>"
                                        data-spot-rate="<?= $contract['spot_rate'] ?? 0 ?>"
                                        data-document-rate="<?= $contract['document_consultation_rate'] ?? 0 ?>"
                                        data-use-remote-consultation="<?= $contract['use_remote_consultation'] ?? 0 ?>"
                                        data-use-document-creation="<?= $contract['use_document_creation'] ?? 0 ?>"
                                        data-tax-type="<?= htmlspecialchars($contract['tax_type'] ?? 'exclusive', ENT_QUOTES, 'UTF-8') ?>"
                                        data-tax-type-label="<?= htmlspecialchars($contract['tax_type_label'] ?? '外税', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-yen-sign"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- 契約一覧ページネーション（ダッシュボード統一版） -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="d-none d-md-block">
                    <small class="text-muted">
                        <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>件 
                        (全<?= number_format($pagination['total_count']) ?>件中)
                    </small>
                </div>
                
                <nav aria-label="契約一覧ページネーション">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- 前のページ -->
                        <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link contracts-pagination-link" 
                               href="#" 
                               data-page="<?= $pagination['current_page'] - 1 ?>"
                               data-page-size="<?= $pagination['per_page'] ?>"
                               aria-label="前のページ">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- ページ番号 -->
                        <?php 
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        ?>
                        
                        <?php if ($start > 1): ?>
                            <li class="page-item">
                                <a class="page-link contracts-pagination-link" 
                                   href="#" 
                                   data-page="1"
                                   data-page-size="<?= $pagination['per_page'] ?>">1</a>
                            </li>
                            <?php if ($start > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link contracts-pagination-link" 
                                   href="#" 
                                   data-page="<?= $i ?>"
                                   data-page-size="<?= $pagination['per_page'] ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end < $pagination['total_pages']): ?>
                            <?php if ($end < $pagination['total_pages'] - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link contracts-pagination-link" 
                                   href="#" 
                                   data-page="<?= $pagination['total_pages'] ?>"
                                   data-page-size="<?= $pagination['per_page'] ?>"><?= $pagination['total_pages'] ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 次のページ -->
                        <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                            <a class="page-link contracts-pagination-link" 
                               href="#" 
                               data-page="<?= $pagination['current_page'] + 1 ?>"
                               data-page-size="<?= $pagination['per_page'] ?>"
                               aria-label="次のページ">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <div class="d-md-none">
                    <small class="text-muted">
                        <?= $pagination['current_page'] ?>/<?= $pagination['total_pages'] ?>ページ
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 拠点詳細情報モーダル -->
<div class="modal fade" id="branchInfoModal" tabindex="-1" aria-labelledby="branchInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchInfoModalLabel">
                    <i class="fas fa-building me-2"></i>拠点詳細情報
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- 企業情報 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-building me-2"></i>企業情報
                                </h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">企業名:</dt>
                                    <dd class="col-sm-8" id="modalCompanyName">-</dd>
                                    
                                    <dt class="col-sm-4">本社住所:</dt>
                                    <dd class="col-sm-8" id="modalCompanyAddress">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 拠点情報 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i>拠点情報
                                </h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">拠点名:</dt>
                                    <dd class="col-sm-8" id="modalBranchName">-</dd>
                                    
                                    <dt class="col-sm-4">住所:</dt>
                                    <dd class="col-sm-8" id="modalBranchAddress">-</dd>
                                    
                                    <dt class="col-sm-4">電話番号:</dt>
                                    <dd class="col-sm-8" id="modalBranchPhone">-</dd>
                                    
                                    <dt class="col-sm-4">メール:</dt>
                                    <dd class="col-sm-8" id="modalBranchEmail">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 追加のアクション -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-tools me-2"></i>アクション
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary" id="copyAddressBtn">
                                        <i class="fas fa-copy me-1"></i>住所をコピー
                                    </button>
                                    <button type="button" class="btn btn-outline-success" id="openMapBtn">
                                        <i class="fas fa-map me-1"></i>地図で表示
                                    </button>
                                    <button type="button" class="btn btn-outline-info" id="callPhoneBtn">
                                        <i class="fas fa-phone me-1"></i>電話をかける
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 非訪問日一覧モーダル -->
<div class="modal fade" id="nonVisitDaysModal" tabindex="-1" aria-labelledby="nonVisitDaysModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nonVisitDaysModalLabel">
                    <i class="fas fa-calendar-times me-2"></i>非訪問日一覧
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="nonVisitDaysContent">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">読み込み中...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 料金情報モーダル（ページ下部に追加） -->
<div class="modal fade" id="rateInfoModal" tabindex="-1" aria-labelledby="rateInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="rateInfoModalLabel">
                    <i class="fas fa-yen-sign me-2"></i>契約料金情報
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 契約基本情報 -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-building me-2"></i>契約先情報
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>企業名:</strong>
                                <span id="rateModalCompanyName">-</span>
                            </div>
                            <div class="col-md-6">
                                <strong>拠点名:</strong>
                                <span id="rateModalBranchName">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 料金情報 -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-calculator me-2"></i>料金設定
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- 定期訪問料金 -->
                            <div class="col-md-6" id="regularRateSection">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-calendar-check text-success me-2"></i>
                                        <strong>定期訪問</strong>
                                    </div>
                                    <div class="fs-4 fw-bold text-success" id="regularRateDisplay">
                                        <span class="rate-amount">-</span>
                                        <small class="text-muted fs-6">円/時間</small>
                                    </div>
                                    <small class="text-muted">月1回の定期訪問時間あたりの料金</small>
                                </div>
                            </div>

                            <!-- 定期延長料金 -->
                            <div class="col-md-6" id="regularExtensionRateSection">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock text-info me-2"></i>
                                        <strong>定期延長</strong>
                                    </div>
                                    <div class="fs-4 fw-bold text-info" id="regularExtensionRateDisplay">
                                        <span class="rate-amount">-</span>
                                        <small class="text-muted fs-6">円/15分</small>
                                    </div>
                                    <small class="text-muted">定期訪問の延長時間あたりの料金</small>
                                </div>
                            </div>

                            <!-- 臨時訪問料金 -->
                            <div class="col-md-6" id="emergencyRateSection">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                        <strong>臨時訪問</strong>
                                    </div>
                                    <div class="fs-4 fw-bold text-warning" id="emergencyRateDisplay">
                                        <span class="rate-amount">-</span>
                                        <small class="text-muted fs-6">円/15分</small>
                                    </div>
                                    <small class="text-muted">緊急時の臨時訪問時間あたりの料金</small>
                                </div>
                            </div>

                            <!-- スポット料金 -->
                            <div class="col-md-6" id="spotRateSection" style="display: none;">
                                <div class="border rounded p-3 h-100 bg-warning bg-opacity-10">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        <strong>スポット料金</strong>
                                    </div>
                                    <div class="fs-4 fw-bold text-warning" id="spotRateDisplay">
                                        <span class="rate-amount">-</span>
                                        <small class="text-muted fs-6">円/15分</small>
                                    </div>
                                    <small class="text-muted">スポット訪問時間あたりの料金</small>
                                </div>
                            </div>

                            <!-- 書面作成・遠隔相談料金 -->
                            <div class="col-md-6" id="documentConsultationRateSection">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-file-alt text-primary me-2"></i>
                                        <strong id="documentConsultationLabel">書面作成・遠隔相談</strong>
                                    </div>
                                    <div class="fs-4 fw-bold text-primary" id="documentRateDisplay">
                                        <span class="rate-amount">-</span>
                                        <small class="text-muted fs-6">円/回</small>
                                    </div>
                                    <small class="text-muted" id="documentConsultationDescription">書面作成や遠隔相談1回あたりの料金</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 税種別情報 -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>税種別
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="badge fs-6 me-3" id="taxTypeBadge">
                                <i class="fas fa-percent me-1"></i>外税
                            </span>
                            <span id="taxTypeDescription">消費税別での請求となります</span>
                        </div>
                    </div>
                </div>

                <!-- 未設定の場合の注意 -->
                <div class="alert alert-info mt-3" id="noRateAlert" style="display: none;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>料金未設定:</strong> この契約では料金が設定されていません。必要に応じて管理者にお問い合わせください。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ダッシュボード統一ページネーションスタイル */
.pagination {
    margin-bottom: 0;
}

.pagination .page-link {
    border-color: #dee2e6;
    color: #0d6efd;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.15s ease-in-out;
}

.pagination .page-link:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    color: #0a58ca;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
    font-weight: 600;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    background-color: #fff;
    border-color: #dee2e6;
    cursor: not-allowed;
}

/* ページサイズ選択スタイル */
.form-select-sm {
    font-size: 0.875rem;
    padding: 0.25rem 1.75rem 0.25rem 0.5rem;
    min-width: 80px;
}

/* ページネーション情報テキスト */
.pagination-info {
    font-size: 0.875rem;
    color: #6c757d;
}

/* ローディング中のスタイル */
.pagination-loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.pagination-loading::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 10;
}

.pagination-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    z-index: 11;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* 既存のスタイルを継続 */
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

/* 既存のスタイルを継続 */
.expired-contract {
    background-color: #f8f9fa !important;
    opacity: 0.7;
}

.expired-contract:hover {
    background-color: #e9ecef !important;
}

.expired-contract-card {
    background-color: #f8f9fa;
    opacity: 0.8;
    border-color: #dee2e6;
}

.expired-contract-card:hover {
    opacity: 0.9;
}

.opacity-50 {
    opacity: 0.5 !important;
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.table-secondary {
    background-color: rgba(108, 117, 125, 0.1) !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
}

.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000 !important;
}

.badge.bg-dark {
    background-color: #212529 !important;
}

.badge.bg-danger .fas.fa-ban {
    color: #ffffff !important;
}

.fa-ban.text-danger {
    color: #dc3545 !important;
    opacity: 0.7;
}

.text-danger {
    color: #dc3545 !important;
}

.text-danger.fw-bold {
    color: #dc3545 !important;
    font-weight: bold !important;
}

.btn-outline-danger:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    border-color: #dc3545;
    color: #dc3545;
    background-color: transparent;
}

.btn-outline-secondary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    border-color: #6c757d;
    color: #6c757d;
    background-color: transparent;
}

.fas.fa-percent {
    color: #000000;
}

.fas.fa-minus-circle {
    color: #000000;
}

.mobile-cards {
    padding: 0;
}

.contract-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left-width: 4px !important;
}

.contract-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.contract-card.border-success {
    border-left-color: #28a745 !important;
}

.contract-card.border-warning {
    border-left-color: #ffc107 !important;
}

.contract-card.border-danger {
    border-left-color: #dc3545 !important;
}

.contract-card.border-secondary {
    border-left-color: #6c757d !important;
}

.contract-header h6 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.info-item {
    margin-bottom: 0.5rem;
}

.info-item small {
    font-weight: 500;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.progress-section {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.375rem;
}

.contract-card .btn {
    font-size: 0.875rem;
    font-weight: 500;
}

.contract-card .btn:disabled {
    opacity: 0.6;
}

.branch-info-link {
    color: inherit;
    font-weight: 500;
    transition: all 0.2s ease;
}

.branch-info-link:hover {
    color: #007bff !important;
    text-decoration: none !important;
    cursor: pointer;
}

.branch-info-link:hover .fa-info-circle {
    transform: scale(1.1);
    color: #17a2b8 !important;
}

.branch-info-link .fa-info-circle {
    transition: all 0.2s ease;
    opacity: 0.7;
}

.branch-info-link:hover .fa-info-circle {
    opacity: 1;
}

.desktop-table .table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.desktop-table .table td {
    vertical-align: middle;
}

.desktop-table .progress {
    background-color: #e9ecef;
    border-radius: 10px;
}

.desktop-table .progress-bar {
    transition: width 0.6s ease;
    border-radius: 10px;
}

.btn-group-sm .btn {
    min-width: 35px;
    font-size: 0.8rem;
}

.btn-group-sm .btn:disabled {
    cursor: not-allowed;
}

.form-check {
    padding-top: 0.375rem;
}

.form-check-input {
    margin-top: 0.125rem;
}

#branchInfoModal .modal-dialog {
    max-width: 800px;
}

#branchInfoModal .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#branchInfoModal .card-header {
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

#branchInfoModal .card-header h6 {
    font-weight: 600;
}

#branchInfoModal dl {
    margin-bottom: 0;
}

#branchInfoModal dt {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

#branchInfoModal dd {
    color: #212529;
    font-size: 0.9rem;
    word-break: break-word;
}

#branchInfoModal .btn {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
}

#branchInfoModal .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#branchInfoModal .modal-content {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

#branchInfoModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    border-bottom: none;
}

#branchInfoModal .modal-header .btn-close {
    filter: invert(1);
    opacity: 0.8;
}

#branchInfoModal .modal-header .btn-close:hover {
    opacity: 1;
}

#branchInfoModal .modal-footer {
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}

@media (max-width: 1199.98px) {
    .desktop-table {
        display: none !important;
    }
    
    .mobile-cards {
        display: block !important;
    }
}

@media (min-width: 1200px) {
    .desktop-table {
        display: block !important;
    }
    
    .mobile-cards {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .contract-card {
        margin-bottom: 1rem;
    }
    
    .contract-card .card-body {
        padding: 1rem;
    }
    
    .contract-header h6 {
        font-size: 1rem;
    }
    
    .info-item {
        margin-bottom: 0.75rem;
    }
    
    .progress-section {
        padding: 0.75rem;
    }
    
    .contract-card .btn {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }
    
    #branchInfoModal .modal-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    #branchInfoModal .d-flex.gap-2 {
        flex-direction: column;
    }
    
    #branchInfoModal .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    #branchInfoModal .btn:last-child {
        margin-bottom: 0;
    }
    
    /* ページネーション調整 */
    .pagination .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .contract-card .card-body {
        padding: 0.75rem;
    }
    
    .contract-header h6 {
        font-size: 0.95rem;
    }
    
    .info-item small {
        font-size: 0.75rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    
    .progress {
        height: 20px !important;
    }
    
    .fa-2x {
        font-size: 1.5rem !important;
    }
    
    .contract-card .btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
    }
    
    /* モバイルページネーション最適化 */
    .pagination .page-link {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
        min-width: 32px;
        text-align: center;
    }
}

@media (max-width: 768px) {
    .card-body .row.g-3 > div {
        margin-bottom: 0.75rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
}

.contract-card {
    animation: slideInUp 0.3s ease forwards;
}

.contract-card:nth-child(2) {
    animation-delay: 0.05s;
}

.contract-card:nth-child(3) {
    animation-delay: 0.1s;
}

.contract-card:nth-child(4) {
    animation-delay: 0.15s;
}

@keyframes slideInUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.contract-card:focus-within {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

.btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

@media print {
    .mobile-cards {
        display: none !important;
    }
    
    .desktop-table {
        display: block !important;
    }
    
    .btn-group {
        display: none;
    }
}
/* 非訪問日一覧モーダルのスタイル */
#nonVisitDaysModal .modal-dialog {
    max-width: 800px;
}

#nonVisitDaysModal .list-group-item {
    border-left: none;
    border-right: none;
    border-top: 1px solid #dee2e6;
    padding: 0.75rem 0;
}

#nonVisitDaysModal .list-group-item:first-child {
    border-top: none;
}

#nonVisitDaysModal .list-group-item:last-child {
    border-bottom: none;
}

#nonVisitDaysModal .card {
    border: 1px solid #dee2e6;
}

#nonVisitDaysModal .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

/* 非訪問日リンクのホバー効果 */
.text-danger a:hover {
    text-decoration: underline !important;
    color: #b02a37 !important;
}

/* 料金情報モーダル用スタイル */
#rateInfoModal .modal-dialog {
    max-width: 900px;
}

#rateInfoModal .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#rateInfoModal .card-header {
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

#rateInfoModal .card-header h6 {
    font-weight: 600;
}

#rateInfoModal .modal-content {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

#rateInfoModal .modal-header.bg-warning {
    color: #212529;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    border-bottom: none;
}

#rateInfoModal .modal-footer {
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}

/* 料金表示カード */
#rateInfoModal .border.rounded {
    border-color: #e9ecef !important;
    transition: all 0.2s ease;
}

#rateInfoModal .border.rounded:hover {
    border-color: #adb5bd !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* 料金未設定時のスタイル */
#rateInfoModal .text-muted .rate-amount {
    font-style: italic;
    opacity: 0.7;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    #rateInfoModal .modal-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    #rateInfoModal .fs-4 {
        font-size: 1.25rem !important;
    }
    
    #rateInfoModal .col-md-6 {
        margin-bottom: 1rem;
    }
}

/* 料金ボタンのスタイル */
.rate-info-btn {
    min-width: 35px;
    font-size: 0.8rem;
    transition: all 0.15s ease-in-out;
}

.rate-info-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-cards .rate-info-btn {
    font-size: 0.75rem;
    padding: 0.4rem 0.6rem;
}

/* デスクトップテーブル用 */
.desktop-table .rate-info-btn {
    background-color: transparent;
    border-color: #ffc107;
    color: #ffc107;
}

.desktop-table .rate-info-btn:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
}

/* モバイル用 */
.mobile-cards .btn-outline-warning {
    border-color: #ffc107;
    color: #ffc107;
}

.mobile-cards .btn-outline-warning:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
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
    margin-bottom: 1rem;
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
            // 新しい列の場合はデフォルトで昇順(usage_percentageは降順)
            currentUrl.searchParams.set('sort', sortColumn);
            const defaultOrder = sortColumn === 'usage_percentage' ? 'desc' : 'asc';
            currentUrl.searchParams.set('order', defaultOrder);
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

// 契約一覧ページ：ダッシュボード統一ページネーション対応版
document.addEventListener('DOMContentLoaded', function() {
    const rateInfoModal = document.getElementById('rateInfoModal');
    
    if (!rateInfoModal) {
        console.error('Rate info modal not found!');
        return;
    }

    // Bootstrap Modal のshow.bs.modalイベントを使用
    rateInfoModal.addEventListener('show.bs.modal', function(event) {
        const triggerButton = event.relatedTarget;
        
        if (!triggerButton) {
            console.error('No trigger button found for rate modal');
            return;
        }

        // データ属性から情報を取得
        const contractData = {
            contractId: triggerButton.getAttribute('data-contract-id'),
            companyName: triggerButton.getAttribute('data-company-name') || '-',
            branchName: triggerButton.getAttribute('data-branch-name') || '-',
            visitFrequency: triggerButton.getAttribute('data-visit-frequency') || 'monthly',
            regularRate: parseInt(triggerButton.getAttribute('data-regular-rate')) || 0,
            regularExtensionRate: parseInt(triggerButton.getAttribute('data-regular-extension-rate')) || 0,
            emergencyRate: parseInt(triggerButton.getAttribute('data-emergency-rate')) || 0,
            spotRate: parseInt(triggerButton.getAttribute('data-spot-rate')) || 0,
            documentRate: parseInt(triggerButton.getAttribute('data-document-rate')) || 0,
            useRemoteConsultation: parseInt(triggerButton.getAttribute('data-use-remote-consultation')) || 0,
            useDocumentCreation: parseInt(triggerButton.getAttribute('data-use-document-creation')) || 0,
            taxType: triggerButton.getAttribute('data-tax-type') || 'exclusive',
            taxTypeLabel: triggerButton.getAttribute('data-tax-type-label') || '外税'
        };

        // モーダル内容を更新
        updateRateModalContent(contractData);
    });


    // ===================== ダッシュボード統一ページネーション機能 =====================
    
    // ページサイズ変更時の処理（ダッシュボード統一版）
    const contractsPageSizeSelect = document.getElementById('contracts_page_size');
    if (contractsPageSizeSelect) {
        contractsPageSizeSelect.addEventListener('change', function() {
            const newPageSize = this.value;
            reloadPageWithPagination({
                page: 1, // ページサイズ変更時は1ページ目に戻る
                per_page: newPageSize
            });
        });
    }
    
    // ページネーションリンクのクリック処理（ダッシュボード統一版）
    document.addEventListener('click', function(e) {
        if (e.target.closest('.contracts-pagination-link')) {
            e.preventDefault();
            const link = e.target.closest('.contracts-pagination-link');
            const page = link.getAttribute('data-page');
            const pageSize = link.getAttribute('data-page-size');
            
            if (page && pageSize && !link.parentNode.classList.contains('disabled')) {
                reloadPageWithPagination({
                    page: page,
                    per_page: pageSize
                });
            }
        }
    });
    
    // ページリロード関数（ダッシュボード統一版）
    function reloadPageWithPagination(params) {
        // ローディング状態を表示
        showPaginationLoading();
        
        // 現在のURLパラメータを取得
        const currentUrl = new URL(window.location);
        const currentParams = new URLSearchParams(currentUrl.search);
        
        // 新しいパラメータを設定
        Object.keys(params).forEach(key => {
            if (params[key]) {
                currentParams.set(key, params[key]);
            } else {
                currentParams.delete(key);
            }
        });
        
        // 新しいURLを構築
        const newUrl = `${currentUrl.pathname}?${currentParams.toString()}`;
        
        // ページリロード
        window.location.href = newUrl;
    }
    
    // ページネーションローディング表示
    function showPaginationLoading() {
        const contractsCard = document.querySelector('.card:has(.contracts-pagination-link)');
        if (contractsCard) {
            contractsCard.classList.add('pagination-loading');
        }
    }
    
    // ===================== 既存の機能（拠点情報モーダル等） =====================
    
    // Bootstrap Modal のshow.bs.modalイベントを使用
    const branchInfoModal = document.getElementById('branchInfoModal');
    
    if (!branchInfoModal) {
        console.error('Branch info modal not found!');
        return;
    }

    // Bootstrap Modal のshow.bs.modalイベントを使用
    branchInfoModal.addEventListener('show.bs.modal', function(event) {
        
        // イベントをトリガーしたボタン（リンク）を取得
        const triggerElement = event.relatedTarget;
        
        if (!triggerElement) {
            console.error('No trigger element found');
            return;
        }

        // データ属性から情報を取得
        const modalData = {
            branchName: triggerElement.getAttribute('data-branch-name') || '-',
            branchAddress: triggerElement.getAttribute('data-branch-address') || '-', 
            branchPhone: triggerElement.getAttribute('data-branch-phone') || '-',
            branchEmail: triggerElement.getAttribute('data-branch-email') || '-',
            companyName: triggerElement.getAttribute('data-company-name') || '-',
            companyAddress: triggerElement.getAttribute('data-company-address') || '-'
        };

        // モーダル内の要素を更新
        updateModalContent(modalData);
        
        // アクションボタンのデータ属性を更新
        updateActionButtons(modalData);
    });

    // モーダルコンテンツ更新関数
    function updateModalContent(data) {
        const elements = {
            'modalCompanyName': data.companyName,
            'modalCompanyAddress': data.companyAddress,
            'modalBranchName': data.branchName,
            'modalBranchAddress': data.branchAddress,
            'modalBranchPhone': data.branchPhone,
            'modalBranchEmail': data.branchEmail
        };

        Object.keys(elements).forEach(elementId => {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = elements[elementId];
            } else {
                console.error(`Element not found: ${elementId}`);
            }
        });
    }

    // アクションボタン更新関数
    function updateActionButtons(data) {
        const copyBtn = document.getElementById('copyAddressBtn');
        const mapBtn = document.getElementById('openMapBtn');
        const phoneBtn = document.getElementById('callPhoneBtn');

        if (copyBtn) {
            copyBtn.setAttribute('data-address', data.branchAddress);
        }

        if (mapBtn) {
            mapBtn.setAttribute('data-address', data.branchAddress);
        }

        if (phoneBtn) {
            phoneBtn.setAttribute('data-phone', data.branchPhone);
        }
    }
    
    // アクションボタンのイベントリスナー（一度だけ設定）
    initializeActionButtons();

    function initializeActionButtons() {
        // 住所コピー機能
        const copyBtn = document.getElementById('copyAddressBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const address = this.getAttribute('data-address');
                
                if (address && address !== '-' && address.trim() !== '') {
                    copyToClipboard(address, this);
                } else {
                    showAlert('コピーできる住所が登録されていません。', 'warning');
                }
            });
        }

        // 地図表示機能
        const mapBtn = document.getElementById('openMapBtn');
        if (mapBtn) {
            mapBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const address = this.getAttribute('data-address');
                
                if (address && address !== '-' && address.trim() !== '') {
                    const encodedAddress = encodeURIComponent(address);
                    const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodedAddress}`;
                    window.open(googleMapsUrl, '_blank');
                } else {
                    showAlert('地図表示できる住所が登録されていません。', 'warning');
                }
            });
        }

        // 電話機能
        const phoneBtn = document.getElementById('callPhoneBtn');
        if (phoneBtn) {
            phoneBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const phone = this.getAttribute('data-phone');
                
                if (phone && phone !== '-' && phone.trim() !== '') {
                    const cleanPhone = phone.replace(/[^\d\-\+]/g, '');
                    if (confirm(`${phone} に電話をかけますか？`)) {
                        window.location.href = `tel:${cleanPhone}`;
                    }
                } else {
                    showAlert('電話番号が登録されていません。', 'warning');
                }
            });
        }
    }

    // クリップボードコピー機能（改良版）
    async function copyToClipboard(text, buttonElement) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                // Clipboard API を使用（HTTPS環境）
                await navigator.clipboard.writeText(text);
                showCopySuccess(buttonElement);
            } else {
                // フォールバック方法
                fallbackCopyTextToClipboard(text, buttonElement);
            }
        } catch (err) {
            console.error('Copy failed:', err);
            fallbackCopyTextToClipboard(text, buttonElement);
        }
    }

    // フォールバックコピー機能
    function fallbackCopyTextToClipboard(text, buttonElement) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(buttonElement);
            } else {
                throw new Error('execCommand failed');
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            showAlert('住所のコピーに失敗しました。', 'error');
        } finally {
            document.body.removeChild(textArea);
        }
    }

    // コピー成功表示
    function showCopySuccess(buttonElement) {
        const originalHTML = buttonElement.innerHTML;
        const originalClass = buttonElement.className;
        
        buttonElement.innerHTML = '<i class="fas fa-check me-1"></i>コピー完了！';
        buttonElement.className = buttonElement.className.replace('btn-outline-primary', 'btn-success');
        
        setTimeout(() => {
            buttonElement.innerHTML = originalHTML;
            buttonElement.className = originalClass;
        }, 2000);
    }

    // アラート表示機能
    function showAlert(message, type = 'info') {
        // 既存のアラートを削除
        const existingAlert = document.querySelector('.modal-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // 新しいアラートを作成
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show modal-alert`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // モーダル内に挿入
        const modalBody = branchInfoModal.querySelector('.modal-body');
        if (modalBody) {
            modalBody.insertBefore(alertDiv, modalBody.firstChild);
        }

        // 3秒後に自動削除
        setTimeout(() => {
            if (alertDiv && alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
    
    // フィルター変更時にページを1に戻す
    const filterElements = document.querySelectorAll('#frequency, #tax_type, #year, input[name="status[]"]');
    filterElements.forEach(element => {
        element.addEventListener('change', function() {
            const form = document.getElementById('filterForm');
            if (form) {
                // フォームを直接送信するのではなく、1ページ目に戻してから送信
                const currentParams = new URLSearchParams(window.location.search);
                currentParams.set('page', '1'); // 1ページ目に戻す
                // フォームの送信を実行
                form.submit();
            }
        });
    });
    
    // スマホでのタップ操作改善
    if ('ontouchstart' in window) {
        // タッチデバイスでの操作性向上
        document.querySelectorAll('.contract-card').forEach(function(card) {
            card.style.cursor = 'pointer';
        });
        
        // プログレスバーのタップ操作
        document.querySelectorAll('.progress').forEach(function(progress) {
            progress.addEventListener('touchstart', function(e) {
                // タップ時の視覚的フィードバック
                this.style.transform = 'scale(1.02)';
            });
            
            progress.addEventListener('touchend', function(e) {
                this.style.transform = 'scale(1)';
            });
        });
    }
    
    // エラーハンドリング
    window.addEventListener('error', function(e) {
        console.error('JavaScript error in contracts index:', e);
    });
    
    // レスポンシブ対応の動的調整
    function adjustResponsiveElements() {
        const screenWidth = window.innerWidth;
        
        // 非常に小さい画面での更なる調整
        if (screenWidth < 360) {
            document.body.classList.add('very-small-screen');
        } else {
            document.body.classList.remove('very-small-screen');
        }
        
        // 統計カードの動的調整
        const contractCards = document.querySelectorAll('.contract-card');
        if (screenWidth < 576) {
            contractCards.forEach(card => {
                card.classList.add('small-mobile');
            });
        } else {
            contractCards.forEach(card => {
                card.classList.remove('small-mobile');
            });
        }
    }

    // ウィンドウリサイズ時の調整
    window.addEventListener('resize', adjustResponsiveElements);
    
    // 初期実行
    adjustResponsiveElements();

    // アクセシビリティの改善
    function improveAccessibility() {
        // キーボードナビゲーション対応
        const focusableElements = document.querySelectorAll('a, button, [tabindex]:not([tabindex="-1"])');
        
        focusableElements.forEach(element => {
            element.addEventListener('keydown', function(e) {
                // Enterキーまたはスペースキーでクリック
                if (e.key === 'Enter' || e.key === ' ') {
                    if (this.tagName === 'A' || this.tagName === 'BUTTON') {
                        e.preventDefault();
                        this.click();
                    }
                }
            });
        });
        
        // フォーカス時のスタイル改善
        const style = document.createElement('style');
        style.textContent = `
            .contract-card:focus-within,
            .branch-info-link:focus {
                outline: 2px solid #007bff;
                outline-offset: 2px;
                border-radius: 0.375rem;
            }
            
            @media (max-width: 767px) {
                .btn:focus,
                .badge:focus,
                a:focus {
                    outline: 2px solid #007bff;
                    outline-offset: 2px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // アクセシビリティ改善を実行
    improveAccessibility();
});

// モーダルコンテンツ更新関数
function updateRateModalContent(data) {
    try {
        // 基本情報を設定
        const companyNameElement = document.getElementById('rateModalCompanyName');
        const branchNameElement = document.getElementById('rateModalBranchName');
        
        if (companyNameElement) companyNameElement.textContent = data.companyName;
        if (branchNameElement) branchNameElement.textContent = data.branchName;

        // 訪問頻度がスポットの場合の表示制御
        const isSpot = data.visitFrequency === 'spot';
        
        // 通常の料金セクションの表示/非表示
        const regularRateSection = document.getElementById('regularRateSection');
        const regularExtensionRateSection = document.getElementById('regularExtensionRateSection');
        const emergencyRateSection = document.getElementById('emergencyRateSection');
        const spotRateSection = document.getElementById('spotRateSection');
        
        if (isSpot) {
            // スポットの場合: スポット料金のみ表示
            if (regularRateSection) regularRateSection.style.display = 'none';
            if (regularExtensionRateSection) regularExtensionRateSection.style.display = 'none';
            if (emergencyRateSection) emergencyRateSection.style.display = 'none';
            if (spotRateSection) spotRateSection.style.display = '';
            
            // スポット料金を設定
            updateRateDisplay('spotRateDisplay', data.spotRate);
        } else {
            // 通常の場合: 定期・延長・臨時を表示、スポットを非表示
            if (regularRateSection) regularRateSection.style.display = '';
            if (regularExtensionRateSection) regularExtensionRateSection.style.display = '';
            if (emergencyRateSection) emergencyRateSection.style.display = '';
            if (spotRateSection) spotRateSection.style.display = 'none';
            
            // 料金情報を設定
            updateRateDisplay('regularRateDisplay', data.regularRate);
            updateRateDisplay('regularExtensionRateDisplay', data.regularExtensionRate);
            updateRateDisplay('emergencyRateDisplay', data.emergencyRate);
        }
        
        // 書面作成・遠隔相談の表示制御
        const documentSection = document.getElementById('documentConsultationRateSection');
        const documentLabel = document.getElementById('documentConsultationLabel');
        const documentDescription = document.getElementById('documentConsultationDescription');
        
        const useRemote = data.useRemoteConsultation == 1;
        const useDocument = data.useDocumentCreation == 1;
        
        if (useRemote || useDocument) {
            // いずれかが有効な場合は表示
            if (documentSection) documentSection.style.display = '';
            
            // ラベルと説明文を動的に生成
            let labelText = '';
            let descriptionText = '';
            
            if (useDocument && useRemote) {
                labelText = '書面作成・遠隔相談';
                descriptionText = '書面作成や遠隔相談1回あたりの料金';
            } else if (useDocument) {
                labelText = '書面作成';
                descriptionText = '書面作成1回あたりの料金';
            } else if (useRemote) {
                labelText = '遠隔相談';
                descriptionText = '遠隔相談1回あたりの料金';
            }
            
            if (documentLabel) documentLabel.textContent = labelText;
            if (documentDescription) documentDescription.textContent = descriptionText;
            
            updateRateDisplay('documentRateDisplay', data.documentRate);
        } else {
            // 両方とも無効な場合は非表示
            if (documentSection) documentSection.style.display = 'none';
        }

        // 請求方法を設定
        const taxTypeBadge = document.getElementById('taxTypeBadge');
        const taxTypeDescription = document.getElementById('taxTypeDescription');
        
        if (taxTypeBadge && taxTypeDescription) {
            const isInclusive = data.taxType === 'inclusive';
            
            taxTypeBadge.innerHTML = isInclusive 
                ? '<i class="fas fa-calculator me-1"></i>内税'
                : '<i class="fas fa-percent me-1"></i>外税';
            
            taxTypeBadge.className = isInclusive 
                ? 'badge bg-info fs-6 me-3'
                : 'badge bg-warning text-dark fs-6 me-3';
                
            taxTypeDescription.textContent = isInclusive 
                ? '消費税込みでの請求となります'
                : '消費税別での請求となります';
        }

        // 料金未設定の警告表示
        let hasAnyRate = false;
        if (isSpot) {
            hasAnyRate = data.spotRate > 0;
        } else {
            hasAnyRate = data.regularRate > 0 || data.regularExtensionRate > 0 || 
                        data.emergencyRate > 0 || data.documentRate > 0;
        }
        
        const noRateAlert = document.getElementById('noRateAlert');
        if (noRateAlert) {
            noRateAlert.style.display = hasAnyRate ? 'none' : 'block';
        }
        
    } catch (error) {
        console.error('Error updating rate modal content:', error);
    }
}

// 個別料金表示更新関数
function updateRateDisplay(elementId, rate) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const rateAmountElement = element.querySelector('.rate-amount');
    if (!rateAmountElement) return;

    if (rate > 0) {
        rateAmountElement.textContent = number_format(rate);
        element.classList.remove('text-muted');
    } else {
        rateAmountElement.textContent = '未設定';
        element.classList.add('text-muted');
    }
}

// 数値フォーマット関数
function number_format(number) {
    return new Intl.NumberFormat('ja-JP').format(number);
}

// 非訪問日一覧表示機能
function showNonVisitDays(contractId, companyName, branchName) {
    const modal = new bootstrap.Modal(document.getElementById('nonVisitDaysModal'));
    const content = document.getElementById('nonVisitDaysContent');
    const modalTitle = document.getElementById('nonVisitDaysModalLabel');
    
    // モーダルタイトルを設定
    modalTitle.innerHTML = `<i class="fas fa-calendar-times me-2"></i>非訪問日一覧 - ${companyName} ${branchName}`;
    
    // ローディング表示
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">読み込み中...</p>
        </div>
    `;
    
    modal.show();
    
    // 非訪問日データを取得して表示
    fetchNonVisitDays(contractId, content);
}

// 非訪問日データ取得関数
async function fetchNonVisitDays(contractId, contentElement) {
    try {
        // APIパスを確認・修正
        const response = await fetch(`<?= base_url('api/contracts/') ?>${contractId}/non-visit-days?scope=all`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('API Response Status:', response.status); // デバッグ用
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response Data:', data); // デバッグ用
        
        if (data.success && data.data && data.data.length > 0) {
            displayNonVisitDays(data.data, contentElement);
        } else {
            contentElement.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    ${data.message || '非訪問日の設定がありません。'}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching non-visit days:', error);
        contentElement.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                非訪問日データの取得に失敗しました: ${error.message}
            </div>
        `;
    }
}

// 非訪問日データ表示関数
function displayNonVisitDays(nonVisitDays, contentElement) {
    // 年ごとにグループ化
    const groupedByYear = nonVisitDays.reduce((acc, item) => {
        if (!acc[item.year]) {
            acc[item.year] = [];
        }
        acc[item.year].push(item);
        return acc;
    }, {});
    
    let html = '';
    
    // 年ごとに表示
    Object.keys(groupedByYear).sort((a, b) => b - a).forEach(year => {
        const yearData = groupedByYear[year];
        const activeCount = yearData.filter(item => item.is_active).length;
        const inactiveCount = yearData.filter(item => !item.is_active).length;
        
        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>${year}年
                        <span class="badge bg-primary ms-2">${activeCount}件有効</span>
                        ${inactiveCount > 0 ? `<span class="badge bg-secondary ms-1">${inactiveCount}件無効</span>` : ''}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
        `;
        
        // 月ごとにグループ化
        const monthlyData = yearData.reduce((acc, item) => {
            const month = new Date(item.date).getMonth() + 1;
            if (!acc[month]) {
                acc[month] = [];
            }
            acc[month].push(item);
            return acc;
        }, {});
        
        // 月ごとに表示
        Object.keys(monthlyData).sort((a, b) => a - b).forEach(month => {
            const monthData = monthlyData[month].sort((a, b) => new Date(a.date) - new Date(b.date));
            
            html += `
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">${month}月</h6>
                    <div class="list-group list-group-flush">
            `;
            
            monthData.forEach(item => {
                const date = new Date(item.date);
                const dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][date.getDay()];
                const formattedDate = `${month}/${date.getDate()}(${dayOfWeek})`;
                const statusClass = item.is_active ? 'success' : 'secondary';
                const statusText = item.is_active ? '有効' : '無効';
                
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <span class="fw-bold">${formattedDate}</span>
                            ${item.reason ? `<br><small class="text-muted">${item.reason}</small>` : ''}
                            ${item.is_recurring ? '<br><small class="text-info"><i class="fas fa-repeat me-1"></i>毎年繰り返し</small>' : ''}
                        </div>
                        <span class="badge bg-${statusClass}">${statusText}</span>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        });
        
        html += `
                    </div>
                </div>
            </div>
        `;
    });
    
    contentElement.innerHTML = html;
}
</script>