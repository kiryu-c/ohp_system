<?php
// app/views/contracts/company_index.php - ページネーション統一版（期限切れ契約対応版）+ 毎週スケジュール表示対応
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
        <form method="GET" action="<?= base_url('contracts') ?>" class="row g-3">
            <div class="col-lg-3 col-md-6">
                <label for="search" class="form-label">検索</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                       placeholder="産業医名/拠点名で検索">
            </div>
            <div class="col-lg-3 col-md-6">
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
            <div class="col-lg-2 col-md-6">
                <label for="frequency" class="form-label">訪問頻度</label>
                <select class="form-select" id="frequency" name="frequency">
                    <option value="">全て</option>
                    <option value="monthly" <?= ($_GET['frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>毎月</option>
                    <option value="bimonthly" <?= ($_GET['frequency'] ?? '') === 'bimonthly' ? 'selected' : '' ?>>隔月</option>
                    <option value="weekly" <?= ($_GET['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>毎週</option>
                    <option value="spot" <?= ($_GET['frequency'] ?? '') === 'spot' ? 'selected' : '' ?>>スポット</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="year" class="form-label">年</label>
                <select class="form-select" id="year" name="year">
                    <option value="">全て</option>
                    <?php for($i = date('Y'); $i >= date('Y') - 3; $i--): ?>
                        <option value="<?= $i ?>" <?= ($_GET['year'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?>年</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="per_page" class="form-label">表示件数</label>
                <select class="form-select" id="per_page" name="per_page">
                    <?php foreach ($pagination['per_page_options'] as $option): ?>
                        <option value="<?= $option ?>" <?= $pagination['per_page'] == $option ? 'selected' : '' ?>>
                            <?= $option ?>件
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1 col-md-6">
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
            企業・拠点名(昇順)
        </option>
        <option value="company_name-desc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            企業・拠点名(降順)
        </option>
        <option value="service_date-desc" <?= ($sortColumn ?? '') === 'service_date' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            開始日(新しい順)
        </option>
        <option value="service_date-asc" <?= ($sortColumn ?? '') === 'service_date' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            開始日(古い順)
        </option>
        <option value="regular_visit_hours-desc" <?= ($sortColumn ?? '') === 'regular_visit_hours' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            訪問時間(多い順)
        </option>
        <option value="regular_visit_hours-asc" <?= ($sortColumn ?? '') === 'regular_visit_hours' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            訪問時間(少ない順)
        </option>
        <option value="visit_frequency-asc" <?= ($sortColumn ?? '') === 'visit_frequency' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            訪問頻度(昇順)
        </option>
        <option value="usage_percentage-desc" <?= ($sortColumn ?? '') === 'usage_percentage' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            使用率(高い順)
        </option>
        <option value="usage_percentage-asc" <?= ($sortColumn ?? '') === 'usage_percentage' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            使用率(低い順)
        </option>
    </select>
</div>

<!-- ページネーション設定エリア（ダッシュボードと同じスタイル） -->
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
                <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($_GET['show_expired']) && $_GET['show_expired']): ?>
                <span class="badge bg-warning">期限切れ含む</span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($contracts)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>現在、契約中の産業医はいません。
            </div>
        <?php else: ?>
            <!-- デスクトップ用テーブル -->
            <div class="table-responsive desktop-view">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="#" class="sort-link" data-sort="company_name" title="拠点名で並び替え">
                                    拠点
                                    <?php if (($sortColumn ?? 'company_name') === 'company_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="doctor_name" title="産業医名で並び替え">
                                    産業医名
                                    <?php if (($sortColumn ?? '') === 'doctor_name'): ?>
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
                                <a href="#" class="sort-link" data-sort="visit_frequency" title="訪問頻度で並び替え">
                                    訪問頻度
                                    <?php if (($sortColumn ?? '') === 'visit_frequency'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">今月の状況</th>
                            <th class="text-center">タクシー利用</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="service_date" title="契約期間で並び替え">
                                    契約期間
                                    <?php if (($sortColumn ?? '') === 'service_date'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">状態</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                            <?php 
                            $regularVisitHours = $contract['regular_visit_hours'] ?? 0;
                            $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
                            $visitFrequencyInfo = get_visit_frequency_info($visitFrequency);
                            $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                            $taxiAllowed = $contract['taxi_allowed'] ?? 0;
                            $isContractExpired = $contract['is_contract_expired'] ?? false;
                            $isFutureContract = $contract['is_future_contract'] ?? false;
                            
                            // 週間スケジュールの取得
                            $weeklySchedule = '';
                            if ($visitFrequency === 'weekly') {
                                if (!empty($contract['weekly_schedule'])) {
                                    $weeklySchedule = $contract['weekly_schedule'];
                                } elseif (!empty($contract['weekly_days'])) {
                                    // weekly_daysから曜日名を生成
                                    $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                                    $weeklySchedule = implode('・', array_map(function($day) use ($dayNames) {
                                        return $dayNames[$day] ?? '';
                                    }, $contract['weekly_days']));
                                }
                            }
                            ?>
                            <tr class="<?= ($isContractExpired || $isFutureContract) ? 'table-secondary expired-contract' : '' ?>">
                                <td>
                                    <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                    <strong><?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($contract['branch_address'])): ?>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars($contract['branch_address'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-user-md me-1 text-info"></i>
                                    <strong><?= htmlspecialchars($contract['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6 <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                        <?php if ($visitFrequency === 'spot'): ?>
                                            -
                                        <?php else: ?>
                                            <?= format_total_hours($regularVisitHours) ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($visitFrequency === 'bimonthly'): ?>
                                        <div class="small text-muted mt-1">
                                            隔月実施
                                        </div>
                                    <?php elseif ($visitFrequency === 'weekly'): ?>
                                        <div class="small text-muted mt-1">
                                            1回あたり
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $visitFrequencyInfo['class'] ?> <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                        <i class="<?= $visitFrequencyInfo['icon'] ?> me-1"></i>
                                        <?= $visitFrequencyInfo['label'] ?>
                                    </span>
                                    <?php if ($visitFrequency === 'weekly' && !$isContractExpired): ?>
                                        <?php if ($weeklySchedule): ?>
                                            <div class="small text-muted mt-1">
                                                <?= $weeklySchedule ?>曜
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // データベース接続を使用して祝日除外設定と非訪問日をチェック
                                        require_once __DIR__ . '/../../core/Database.php';
                                        $db = Database::getInstance()->getConnection();
                                        
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
                                        
                                        <div class="small text-muted mt-1">
                                            <?php if ($contract['exclude_holidays'] ?? 1): ?>
                                                <i class="fas fa-calendar-times text-warning me-1"></i>祝日非訪問
                                            <?php else: ?>
                                                <i class="fas fa-calendar-check text-info me-1"></i>祝日訪問可
                                            <?php endif; ?>
                                            <br>
                                            <?php if ($nonVisitCount > 0): ?>
                                                <i class="fas fa-ban text-danger me-1"></i>
                                                <a href="#" class="text-decoration-none text-danger" 
                                                onclick="showNonVisitDays(<?= $contract['id'] ?>, '<?= htmlspecialchars($contract['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($contract['branch_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>')">
                                                    非訪問日<?= $nonVisitCount ?>件設定済
                                                </a>
                                            <?php else: ?>
                                                <i class="fas fa-calendar-day text-muted me-1"></i>非訪問日未設定
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isFutureContract): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-hourglass-start me-1"></i>
                                            開始前
                                        </span>
                                        <div class="small text-muted mt-1">
                                            契約開始前
                                        </div>
                                    <?php elseif ($isContractExpired): ?>
                                        <span class="badge bg-dark">
                                            <i class="fas fa-calendar-times me-1"></i>
                                            期間終了
                                        </span>
                                        <div class="small text-muted mt-1">
                                            契約期間終了
                                        </div>
                                    <?php elseif ($visitFrequency === 'bimonthly' && !$isVisitMonth): ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-pause me-1"></i>
                                            非訪問月
                                        </span>
                                        <div class="small text-muted mt-1">
                                            今月は訪問なし
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            訪問月
                                        </span>
                                        <div class="small text-muted mt-1">
                                            役務実施可能
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($taxiAllowed): ?>
                                        <span class="badge bg-success <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                            <i class="fas fa-taxi me-1"></i>
                                            可
                                        </span>
                                        <div class="small text-muted mt-1">
                                            利用可能
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-danger <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                            <i class="fas fa-ban me-1"></i>
                                            不可
                                        </span>
                                        <div class="small text-muted mt-1">
                                            利用不可
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <small>
                                        <strong>開始:</strong><br>
                                        <?= format_date($contract['start_date']) ?><br>
                                        <strong>終了:</strong><br>
                                        <span class="<?= $isContractExpired ? 'text-danger fw-bold' : '' ?>">
                                            <?= $contract['end_date'] ? format_date($contract['end_date']) : '無期限' ?>
                                        </span>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if ($isFutureContract): ?>
                                        <span class="badge bg-secondary">開始前</span>
                                    <?php elseif ($isContractExpired): ?>
                                        <span class="badge bg-secondary">期間終了</span>
                                    <?php else: ?>
                                        <span class="badge bg-<?= get_status_class($contract['contract_status']) ?> <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                            <?= get_status_label($contract['contract_status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- モバイル用カード -->
            <div class="mobile-view">
                <?php foreach ($contracts as $contract): ?>
                    <?php 
                    $regularVisitHours = $contract['regular_visit_hours'] ?? 0;
                    $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
                    $visitFrequencyInfo = get_visit_frequency_info($visitFrequency);
                    $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                    $taxiAllowed = $contract['taxi_allowed'] ?? 0;
                    $isContractExpired = $contract['is_contract_expired'] ?? false;
                    $isFutureContract = $contract['is_future_contract'] ?? false;
                    
                    // 週間スケジュールの取得
                    $weeklySchedule = '';
                    if ($visitFrequency === 'weekly') {
                        if (!empty($contract['weekly_schedule'])) {
                            $weeklySchedule = $contract['weekly_schedule'];
                        } elseif (!empty($contract['weekly_days'])) {
                            // weekly_daysから曜日名を生成
                            $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                            $weeklySchedule = implode('・', array_map(function($day) use ($dayNames) {
                                return $dayNames[$day] ?? '';
                            }, $contract['weekly_days']));
                        }
                    }
                    ?>
                    <div class="card mb-3 contract-card <?= $isContractExpired || $isFutureContract ? 'expired-contract-card' : '' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="contract-title">
                                <i class="fas fa-file-contract me-2 text-primary <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                <strong class="<?= $isContractExpired ? 'text-muted' : '' ?>">
                                    <?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>
                            <?php if ($isContractExpired): ?>
                                <span class="badge bg-secondary">期間終了</span>
                            <?php elseif ($isFutureContract): ?>
                                <span class="badge bg-secondary">開始前</span>
                            <?php else: ?>
                                <span class="badge bg-<?= get_status_class($contract['contract_status']) ?> <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                    <?= get_status_label($contract['contract_status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <!-- 拠点情報 -->
                            <div class="contract-item">
                                <div class="item-label">
                                    <i class="fas fa-map-marker-alt text-primary <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                    拠点
                                </div>
                                <div class="item-value">
                                    <strong class="<?= $isContractExpired ? 'text-muted' : '' ?>">
                                        <?= htmlspecialchars($contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                    <?php if (!empty($contract['branch_address'])): ?>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars($contract['branch_address'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 産業医情報 -->
                            <div class="contract-item">
                                <div class="item-label">
                                    <i class="fas fa-user-md text-info <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                    産業医
                                </div>
                                <div class="item-value">
                                    <strong class="<?= $isContractExpired ? 'text-muted' : '' ?>">
                                        <?= htmlspecialchars($contract['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                </div>
                            </div>

                            <!-- 訪問時間 -->
                            <div class="contract-item">
                                <div class="item-label">
                                    <i class="fas fa-clock text-success <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                    訪問時間
                                </div>
                                <div class="item-value">
                                    <span class="badge bg-primary fs-6 <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                        <?php if ($visitFrequency === 'spot'): ?>
                                            -
                                        <?php else: ?>
                                            <?= format_total_hours($regularVisitHours) ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($visitFrequency === 'weekly'): ?>
                                        <div class="small text-muted mt-1">週1回あたり</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 訪問頻度 -->
                            <div class="contract-item">
                                <div class="item-label">
                                    <i class="fas fa-calendar text-warning <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                    頻度
                                </div>
                                <div class="item-value">
                                    <span class="badge bg-<?= $visitFrequencyInfo['class'] ?> <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                        <i class="<?= $visitFrequencyInfo['icon'] ?> me-1"></i>
                                        <?= $visitFrequencyInfo['label'] ?>
                                    </span>
                                    <div class="small text-muted mt-1">
                                        <?= $visitFrequencyInfo['description'] ?>
                                    </div>
                                    <?php if ($visitFrequency === 'weekly' && $weeklySchedule && !$isContractExpired): ?>
                                        <div class="small text-info mt-1">
                                            <i class="fas fa-calendar-week me-1"></i>
                                            <?= $weeklySchedule ?>曜
                                        </div>

                                        <?php
                                        // データベース接続を使用して祝日除外設定と非訪問日をチェック（モバイル用）
                                        require_once __DIR__ . '/../../core/Database.php';
                                        $db = Database::getInstance()->getConnection();
                                        
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
                                        
                                        <div class="small text-muted mt-1">
                                            <?php if ($contract['exclude_holidays'] ?? 1): ?>
                                                <i class="fas fa-calendar-times text-warning me-1"></i>祝日非訪問
                                            <?php else: ?>
                                                <i class="fas fa-calendar-check text-info me-1"></i>祝日訪問可
                                            <?php endif; ?>
                                            <br>
                                            <?php if ($nonVisitCount > 0): ?>
                                                <i class="fas fa-ban text-danger me-1"></i>
                                                <a href="#" class="text-decoration-none text-danger" 
                                                onclick="showNonVisitDays(<?= $contract['id'] ?>, '<?= htmlspecialchars($contract['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($contract['branch_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>')">
                                                    非訪問日<?= $nonVisitCount ?>件
                                                </a>
                                            <?php else: ?>
                                                <i class="fas fa-calendar-day text-muted me-1"></i>非訪問日未設定
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 今月の状況 -->
                            <div class="contract-item">
                                <div class="item-label">
                                    <i class="fas fa-calendar-check text-info <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                    今月
                                </div>
                                <div class="item-value">
                                    <?php if ($isFutureContract): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-hourglass-start me-1"></i>
                                            開始前
                                        </span>
                                        <div class="small text-info mt-1">
                                            <i class="fas fa-info-circle me-1"></i>
                                            契約開始前
                                        </div>
                                    <?php elseif ($isContractExpired): ?>
                                        <span class="badge bg-dark">
                                            <i class="fas fa-calendar-times me-1"></i>
                                            期間終了
                                        </span>
                                        <div class="small text-danger mt-1">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            契約期間終了
                                        </div>
                                    <?php elseif ($visitFrequency === 'bimonthly' && !$isVisitMonth): ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-pause me-1"></i>
                                            非訪問月
                                        </span>
                                        <div class="small text-muted mt-1">
                                            今月は訪問なし
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            訪問月
                                        </span>
                                        <div class="small text-muted mt-1">
                                            役務実施可能
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- タクシー利用 -->
                            <div class="contract-item">
                                <div class="item-label">
                                    <i class="fas fa-taxi text-warning <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                    タクシー
                                </div>
                                <div class="item-value">
                                    <?php if ($taxiAllowed): ?>
                                        <span class="badge bg-success <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                            <i class="fas fa-taxi me-1"></i>
                                            可
                                        </span>
                                        <div class="small text-muted mt-1">
                                            利用可能
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-danger <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>">
                                            <i class="fas fa-ban me-1"></i>
                                            不可
                                        </span>
                                        <div class="small text-muted mt-1">
                                            利用不可
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 契約期間 -->
                            <div class="contract-item">
                                <div class="item-label">
                                    <i class="fas fa-calendar-alt text-secondary <?= ($isContractExpired || $isFutureContract) ? 'opacity-50' : '' ?>"></i>
                                    期間
                                </div>
                                <div class="item-value">
                                    <div class="small">
                                        <strong>開始:</strong> <?= format_date($contract['start_date']) ?><br>
                                        <strong>終了:</strong> 
                                        <span class="<?= $isContractExpired ? 'text-danger fw-bold' : '' ?>">
                                            <?= $contract['end_date'] ? format_date($contract['end_date']) : '無期限' ?>
                                        </span>
                                        <?php if ($isContractExpired): ?>
                                            <div class="text-danger mt-1">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                期限切れ
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ページネーション（ダッシュボードと同じスタイル） -->
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

<style>
/* ページネーション用スタイル（ダッシュボードと統一） */
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

/* バッジのスタイル */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}

.badge.fs-6 {
    font-size: 1rem !important;
    padding: 0.5em 0.75em;
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

/* タクシーアイコンの色調整 */
.fas.fa-taxi {
    color: #ffc107;
}

.fas.fa-ban {
    color: #ffffff;
}

/* カードのスタイル */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

/* モバイル用契約カードのスタイル */
.contract-card {
    border: 1px solid #dee2e6;
    transition: box-shadow 0.2s ease-in-out, opacity 0.2s ease-in-out;
}

.contract-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.contract-title {
    font-size: 1.1rem;
    font-weight: 600;
}

.contract-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
}

.contract-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
    padding-bottom: 0;
}

.item-label {
    min-width: 80px;
    font-weight: 600;
    color: #495057;
    margin-right: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.item-value {
    flex: 1;
}

/* アイコンとテキストの間隔調整 */
.fas.me-1 {
    margin-right: 0.5rem !important;
}

.fas.me-2 {
    margin-right: 0.75rem !important;
}

/* 訪問頻度バッジの色調整 */
.badge.bg-primary {
    background-color: #0d6efd !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000 !important;
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

/* レスポンシブ対応 */
.desktop-view {
    display: block;
}

.mobile-view {
    display: none;
}

/* 1024px以下でモバイル表示に切り替え */
@media (max-width: 1024px) {
    .desktop-view {
        display: none;
    }
    
    .mobile-view {
        display: block;
    }
    
    /* 検索フォームもモバイル対応 */
    .card-body .row.g-3 {
        gap: 1rem;
    }
    
    .col-lg-3, .col-lg-2, .col-lg-1 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 0.5rem;
    }
    
    /* ボタンを横並びに */
    .search-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .search-buttons .col-lg-2,
    .search-buttons .col-lg-1 {
        flex: 1;
        max-width: none;
    }
    
    /* ページネーションをモバイル対応 */
    .pagination {
        font-size: 0.9rem;
    }
    
    .pagination .page-link {
        padding: 0.375rem 0.5rem;
    }
}

@media (max-width: 768px) {
    /* さらに小さい画面での調整 */
    .contract-item {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .item-label {
        min-width: auto;
        margin-right: 0;
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }
    
    .item-value {
        margin-left: 1.5rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25em 0.5em;
    }
    
    .contract-title {
        font-size: 1rem;
    }
    
    /* モバイルでのページネーション調整 */
    .pagination {
        font-size: 0.8rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination .page-link {
        padding: 0.25rem 0.4rem;
    }
    
    .pagination .page-item {
        margin: 0.1rem;
    }
    
    .form-select-sm {
        font-size: 0.8rem;
        min-width: 70px;
    }
}

@media (max-width: 576px) {
    /* 最小画面での調整 */
    .card-header {
        padding: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .contract-item {
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
    }
    
    .item-label {
        font-size: 0.85rem;
    }
    
    .item-value {
        margin-left: 1rem;
    }
    
    .small {
        font-size: 0.8rem;
    }
    
    /* 非常に小さい画面でのページネーション */
    .pagination {
        font-size: 0.75rem;
    }
    
    .pagination .page-link {
        padding: 0.2rem 0.3rem;
    }
    
    .form-select-sm {
        font-size: 0.75rem;
        min-width: 60px;
    }
    
    /* ページ番号が多い場合は省略表示 */
    .pagination .page-item:not(.active):not(:first-child):not(:last-child):not(.disabled) {
        display: none;
    }
    
    .pagination .page-item.active ~ .page-item:nth-child(-n+2),
    .pagination .page-item.active ~ .page-item:nth-last-child(-n+2) {
        display: inline-block;
    }
}

/* ホバーエフェクト */
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.025);
}

.expired-contract:hover {
    background-color: #e9ecef !important;
}

/* 統計カードのアニメーション */
.card.bg-info, .card.bg-success, .card.bg-primary, .card.bg-secondary {
    transition: transform 0.2s ease-in-out;
}

.card.bg-info:hover, .card.bg-success:hover, .card.bg-primary:hover, .card.bg-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* テーブル行のアニメーション */
.table tbody tr {
    transition: background-color 0.2s ease-in-out;
}

/* 隔月契約行のハイライト */
tr:has(.badge:contains("隔月")) {
    border-left: 3px solid #0dcaf0;
}

/* 毎月契約行のハイライト */
tr:has(.badge:contains("毎月")) {
    border-left: 3px solid #0d6efd;
}

/* 毎週契約行のハイライト */
tr:has(.badge:contains("毎週")) {
    border-left: 3px solid #198754;
}

/* 期限切れ契約行のハイライト */
.expired-contract {
    border-left: 3px solid #6c757d;
}

/* 週間スケジュール表示の強調 */
.text-info {
    color: #0dcaf0 !important;
}

.small.text-info {
    font-weight: 500;
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

/* モバイル用並び替え */
.mobile-sort-section {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}

.mobile-sort-section .form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
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
    position: relative;
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

/* 並び替えアイコンがない列にもアイコンを表示(薄く) */
.table-light th a.sort-link:not(:has(i))::after {
    content: '\f0dc';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: 0.25rem;
    opacity: 0.3;
    font-size: 0.75rem;
}

/* ホバー時にアイコンを濃く */
.table-light th a.sort-link:not(:has(i)):hover::after {
    opacity: 0.6;
}

/* アクティブな並び替え列を強調 */
th:has(.sort-link) {
    background-color: #f8f9fa;
    position: relative;
    cursor: pointer;
}

th:has(.sort-link):hover {
    background-color: #e9ecef;
}

/* 現在の並び替え列をハイライト */
.table-light th:has(.sort-link i) {
    background-color: #e7f1ff;
}

.table-light th:has(.sort-link i):hover {
    background-color: #d0e7ff;
}

.table-light th a.sort-link {
    font-weight: 600;
}

/* アクティブな並び替えアイコンを強調 */
.table-light th:has(.sort-link i) a.sort-link i {
    color: #0d6efd;
    opacity: 1;
}

@media (max-width: 767px) {
    .sort-link {
        font-size: 0.875rem;
    }
    
    .table-light th a.sort-link:not(:has(i))::after {
        font-size: 0.7rem;
    }
}
</style>

<script>
// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
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

    // ツールチップの初期化
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 統計カードのアニメーション効果
    const statCards = document.querySelectorAll('.card.bg-info, .card.bg-success, .card.bg-primary, .card.bg-secondary');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
    
    // 訪問頻度による行のハイライト
    const contractRows = document.querySelectorAll('tbody tr');
    contractRows.forEach(row => {
        const frequencyBadge = row.querySelector('.badge');
        if (frequencyBadge && !row.classList.contains('expired-contract')) {
            if (frequencyBadge.textContent.includes('隔月')) {
                row.style.borderLeft = '3px solid #0dcaf0';
            } else if (frequencyBadge.textContent.includes('毎月')) {
                row.style.borderLeft = '3px solid #0d6efd';
            } else if (frequencyBadge.textContent.includes('毎週')) {
                row.style.borderLeft = '3px solid #198754';
            }
        }
    });

    // モバイル画面での検索ボタンの配置調整
    
    // 表示件数変更時の自動送信
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // 表示件数変更時の自動送信（ページネーション設定エリアのセレクトボックス）
    const contractsPageSizeSelect = document.getElementById('contractsPageSize');
    if (contractsPageSizeSelect) {
        contractsPageSizeSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});

// 契約詳細の表示
function showContractDetail(contractId) {
    // 契約詳細モーダル表示などの機能を実装
    console.log('Showing contract detail for ID:', contractId);
}

// 役務記録画面への遷移
function goToServiceRecords(contractId) {
    window.location.href = `<?= base_url('service_records') ?>?contract_id=${contractId}`;
}

// 新規役務記録画面への遷移
function createServiceRecord(contractId) {
    window.location.href = `<?= base_url('service_records/create') ?>?contract_id=${contractId}`;
}

<?php
// ページネーション用URL生成関数をJavaScriptでも使用できるように
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
        const response = await fetch(`<?= base_url('api/contracts/') ?>${contractId}/non-visit-days?scope=all`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
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