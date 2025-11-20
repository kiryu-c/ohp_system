<?php
// app/views/dashboard/doctor.php - スマホ対応レスポンシブ版（ページネーション対応）
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tachometer-alt me-2"></i>産業医ダッシュボード</h2>
    <div class="btn-group">
        <a href="<?= base_url('service_records/select-contract') ?>" class="btn btn-success">
            <i class="fas fa-play me-1"></i>役務開始
        </a>
        <a href="<?= base_url('service_records/create?return_to=dashboard') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>役務記録を追加
        </a>
    </div>
</div>


<!-- 二要素認証未設定の警告 -->
<?php if (!$twoFactorEnabled): ?>
<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div class="flex-grow-1">
            <h5 class="mb-2">
                <i class="fas fa-shield-alt me-2"></i>二要素認証の設定をおすすめします
            </h5>
            <p class="mb-0">
                アカウントのセキュリティを強化するため、二要素認証(2FA)の設定をおすすめします。<br>
                二要素認証を有効にすることで、パスワードに加えてスマートフォンアプリによる認証コードが必要となり、不正アクセスのリスクを大幅に軽減できます。
            </p>
        </div>
        <div class="flex-shrink-0">
            <a href="<?= base_url('settings/security') ?>" class="btn btn-info btn-sm text-nowrap">
                <i class="fas fa-cog me-1"></i>設定する
            </a>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
</div>
<?php endif; ?>
<!-- 進行中の役務がある場合の警告 -->
<?php if ($hasActiveService): ?>
<div class="alert alert-warning mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1"><i class="fas fa-exclamation-triangle me-2"></i>進行中の役務があります</h5>
            <p class="mb-0">現在実施中の役務を先に終了してください。</p>
        </div>
        <a href="<?= base_url('service_records/active') ?>" class="btn btn-warning">
            <i class="fas fa-eye me-1"></i>確認・終了
        </a>
    </div>
</div>
<?php endif; ?>

<!-- 契約一覧（スマホ対応版・ページネーション対応） -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>契約一覧</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary"><?= $contractsPaginationInfo['total_count'] ?>件</span>
                            
                            <!-- ページサイズ選択 -->
                            <div class="d-flex align-items-center">
                                <label for="contracts_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                                <select id="contracts_page_size" class="form-select form-select-sm" style="width: auto;">
                                    <option value="5" <?= ($contractsPaginationInfo['page_size'] == 5) ? 'selected' : '' ?>>5件</option>
                                    <option value="10" <?= ($contractsPaginationInfo['page_size'] == 10) ? 'selected' : '' ?>>10件</option>
                                    <option value="15" <?= ($contractsPaginationInfo['page_size'] == 15) ? 'selected' : '' ?>>15件</option>
                                    <option value="20" <?= ($contractsPaginationInfo['page_size'] == 20) ? 'selected' : '' ?>>20件</option>
                                    <option value="30" <?= ($contractsPaginationInfo['page_size'] == 30) ? 'selected' : '' ?>>30件</option>
                                </select>
                            </div>
                            
                            <a href="<?= base_url('contracts') ?>" class="btn btn-sm btn-outline-primary">すべて見る</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($contracts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                        <p class="text-muted">契約がありません。</p>
                    </div>
                <?php else: ?>
                    
                    <!-- デスクトップ表示（1024px以上） -->
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>企業名</th>
                                    <th>拠点</th>
                                    <th class="text-center">訪問頻度</th>
                                    <th class="text-center">契約時間</th>
                                    <th class="text-center">今月実績（時間）</th>
                                    <th class="text-center">時間なし役務</th>
                                    <th class="text-center">残り時間</th>
                                    <th class="text-center">進捗</th>
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
                                    
                                    // 契約の有効性チェック
                                    $isContractActive = ($contractStatus === 'active');
                                    $isContractStarted = ($startDate && $startDate <= $currentDate);
                                    $isContractValid = ($isContractActive && $isContractStarted);
                                    
                                    // 契約終了チェック
                                    $isContractExpired = ($endDate && $endDate < $currentDate);
                                    if ($isContractExpired) {
                                        $isContractValid = false;
                                    }
                                    
                                    // 訪問月かどうかの判定（有効な契約の場合のみ）
                                    $isVisitMonth = false;
                                    if ($isContractValid) {
                                        $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                                    }

                                    // 月締めチェック（新規追加）
                                    $isCurrentMonthClosed = $contract['is_current_month_closed'] ?? false;

                                    // 役務の追加・開始が可能かの最終判定
                                    $canPerformService = $isContractValid && !$isCurrentMonthClosed;
                                    
                                    // 行のスタイル決定
                                    $rowClass = '';
                                    $rowStatus = '';
                                    if (!$isContractActive) {
                                        $rowClass = 'table-danger';
                                        $rowStatus = 'inactive';
                                    } elseif (!$isContractStarted) {
                                        $rowClass = 'table-warning';
                                        $rowStatus = 'not_started';
                                    } elseif ($isContractExpired) {
                                        $rowClass = 'table-danger';
                                        $rowStatus = 'expired';
                                    } elseif ($frequency === 'bimonthly' && !$isVisitMonth) {
                                        $rowClass = 'table-info';
                                        $rowStatus = 'non_visit_month_limited';
                                    } else {
                                        $rowStatus = 'active';
                                    }
                                    
                                    // 使用率に応じた色分け（定期訪問時間ベース、有効契約のみ）
                                    $progressClass = 'bg-success';
                                    if ($isContractValid && $isVisitMonth) {
                                        if ($contract['usage_percentage'] >= 100) {
                                            $progressClass = 'bg-danger';
                                        } elseif ($contract['usage_percentage'] >= 80) {
                                            $progressClass = 'bg-warning';
                                        } elseif ($contract['usage_percentage'] >= 60) {
                                            $progressClass = 'bg-info';
                                        }
                                    }
                                    
                                    // 時間なし役務の件数を取得
                                    $documentCount = $contract['document_count'] ?? 0;
                                    $remoteConsultationCount = $contract['remote_consultation_count'] ?? 0;
                                    $otherCount = $contract['other_count'] ?? 0;
                                    $totalNoTimeServices = $documentCount + $remoteConsultationCount + $otherCount;
                                    
                                    // データ属性の値を事前に準備（拠点モーダル用）
                                    $branchName = htmlspecialchars($contract['branch_name'] ?? '-', ENT_QUOTES, 'UTF-8');
                                    $branchAddress = htmlspecialchars($contract['branch_address'] ?? '-', ENT_QUOTES, 'UTF-8');
                                    $branchPhone = htmlspecialchars($contract['branch_phone'] ?? '-', ENT_QUOTES, 'UTF-8');
                                    $branchEmail = htmlspecialchars($contract['branch_email'] ?? '-', ENT_QUOTES, 'UTF-8');
                                    $companyName = htmlspecialchars($contract['company_name'] ?? '-', ENT_QUOTES, 'UTF-8');
                                    $companyAddress = htmlspecialchars($contract['company_address'] ?? '-', ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td>
                                            <i class="fas fa-building me-1 text-success"></i>
                                            <strong><?= $companyName ?></strong>
                                            
                                            <!-- 契約状態バッジ -->
                                            <?php if ($rowStatus !== 'active'): ?>
                                                <div class="mt-1">
                                                    <?php if ($rowStatus === 'inactive'): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-ban me-1"></i>契約無効
                                                        </span>
                                                    <?php elseif ($rowStatus === 'not_started'): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock me-1"></i>開始前
                                                        </span>
                                                    <?php elseif ($rowStatus === 'expired'): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-calendar-times me-1"></i>期間終了
                                                        </span>
                                                    <?php elseif ($rowStatus === 'non_visit_month_limited'): ?>
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-calendar-minus me-1"></i>非訪問月（制限あり）
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                            <a href="#" 
                                               class="text-decoration-none branch-info-link" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#branchInfoModal"
                                               data-branch-name="<?= $branchName ?>"
                                               data-branch-address="<?= $branchAddress ?>"
                                               data-branch-phone="<?= $branchPhone ?>"
                                               data-branch-email="<?= $branchEmail ?>"
                                               data-company-name="<?= $companyName ?>"
                                               data-company-address="<?= $companyAddress ?>"
                                               title="クリックで詳細情報を表示">
                                                <?= $branchName ?>
                                                <i class="fas fa-info-circle ms-1 text-info"></i>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-<?= get_visit_frequency_class($frequency) ?> mb-1">
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
                                                    <small class="text-muted">
                                                        <?php if ($contract['exclude_holidays'] ?? 1): ?>
                                                            <i class="fas fa-calendar-times text-warning me-1"></i>祝日非訪問
                                                        <?php else: ?>
                                                            <i class="fas fa-calendar-check text-info me-1"></i>祝日訪問可
                                                        <?php endif; ?>
                                                        
                                                        <?php 
                                                        // 非訪問日設定の有無をチェック
                                                        $currentYear = date('Y');
                                                        $currentMonth = date('m');
                                                        $nonVisitSql = "SELECT COUNT(*) as count FROM contract_non_visit_days 
                                                                        WHERE contract_id = :contract_id 
                                                                        AND (year = :year 
                                                                        OR (is_recurring = 1 AND year < :year2))
                                                                        AND MONTH(non_visit_date) = :month
                                                                        AND is_active = 1";
                                                        $stmt = $db->prepare($nonVisitSql);
                                                        $stmt->execute(['contract_id' => $contract['id'], 'year' => $currentYear, 'year2' => $currentYear, 'month' => $currentMonth]);
                                                        $nonVisitCount = $stmt->fetchColumn();
                                                        ?>
                                                        <br>
                                                        <?php if ($nonVisitCount > 0): ?>
                                                            <span class="ms-2">
                                                                <i class="fas fa-ban text-danger me-1"></i>
                                                                <a href="#" class="text-decoration-none text-danger" 
                                                                onclick="showNonVisitDays(<?= $contract['id'] ?>, '<?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                                                    非訪問日<?= $nonVisitCount ?>件(今月)
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
                                            <span class="badge bg-primary fs-6">
                                                <?= format_total_hours($contract['regular_visit_hours']) ?>
                                            </span>
                                            <?php if ($frequency === 'weekly' && $isContractValid): ?>
                                                <br><small class="text-muted">(1回あたり)</small>
                                                <?php
                                                // 毎週契約の場合は動的に月間時間を計算
                                                $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                    $contract['id'], 
                                                    $contract['regular_visit_hours'] ?? 0, 
                                                    date('Y'), 
                                                    date('n'),
                                                    $db ?? null
                                                );
                                                $visitCount = ($contract['regular_visit_hours'] ?? 0) > 0 ? round($monthlyHours / ($contract['regular_visit_hours'] ?? 1)) : 0;
                                                ?>
                                                <br><small class="text-success">今月: <?= format_total_hours($monthlyHours) ?> (<?= $visitCount ?>回)</small>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isContractValid && $isVisitMonth): ?>
                                                <div>
                                                    <strong><?= format_total_hours($contract['total_hours'] ?? 0) ?></strong>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    <!-- 定期訪問の内訳 -->
                                                    <div>
                                                        定期: <?= format_total_hours($contract['regular_hours'] ?? 0) ?>
                                                    </div>
                                                    
                                                    <!-- 臨時訪問の表示 -->
                                                    <?php if (($contract['emergency_hours'] ?? 0) > 0): ?>
                                                        <div class="text-warning">
                                                            臨時: <?= format_total_hours($contract['emergency_hours']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- 定期延長の表示 -->
                                                    <?php if (($contract['extension_hours'] ?? 0) > 0): ?>
                                                        <div class="text-info">
                                                            延長: <?= format_total_hours($contract['extension_hours']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (($contract['pending_hours'] ?? 0) > 0): ?>
                                                    <div class="mt-1">
                                                        <small class="text-warning">
                                                            (承認待ち: <?= format_total_hours($contract['pending_hours']) ?>)
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($isContractValid && !$isVisitMonth): ?>
                                                <!-- 非訪問月の場合：臨時・時間なし役務の実績を表示 -->
                                                <div class="text-center">
                                                    <?php if (($contract['emergency_hours'] ?? 0) > 0 || $totalNoTimeServices > 0): ?>
                                                        <br><small class="text-info">
                                                            <?php if (($contract['emergency_hours'] ?? 0) > 0): ?>
                                                                臨時: <?= format_total_hours($contract['emergency_hours']) ?><br>
                                                            <?php endif; ?>
                                                            <?php if ($totalNoTimeServices > 0): ?>
                                                                時間なし役務: <?= $totalNoTimeServices ?>件
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center">
                                                   <small class="text-danger">
                                                        <?php if ($rowStatus === 'inactive'): ?>
                                                            契約無効
                                                        <?php elseif ($rowStatus === 'not_started'): ?>
                                                            開始前
                                                        <?php elseif ($rowStatus === 'expired'): ?>
                                                            期間終了
                                                        <?php else: ?>
                                                            利用不可
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <!-- 時間なし役務列 -->
                                        <td class="text-center">
                                            <?php if ($isContractValid): ?>
                                                <?php if ($totalNoTimeServices > 0): ?>
                                                    <div class="d-flex flex-column align-items-center gap-1">
                                                        <?php if ($documentCount > 0): ?>
                                                            <span class="badge bg-primary">
                                                                <i class="fas fa-file-alt me-1"></i>
                                                                書面: <?= $documentCount ?>件
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($remoteConsultationCount > 0): ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-envelope me-1"></i>
                                                                遠隔: <?= $remoteConsultationCount ?>件
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($otherCount > 0): ?>
                                                            <span class="badge bg-dark">
                                                                <i class="fas fa-plus me-1"></i>
                                                                その他: <?= $otherCount ?>件
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-danger">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($frequency === 'spot'): ?>
                                                <span class="text-muted">-</span>
                                            <?php elseif ($isContractValid): ?>
                                                <?php if ($isVisitMonth): ?>
                                                    <!-- 訪問月：定期訪問の残り時間を表示 -->
                                                    <?php 
                                                    // 毎週契約の場合は動的に月間時間を計算して残り時間を算出
                                                    if ($frequency === 'weekly') {
                                                        $monthlyHoursForRemaining = calculate_weekly_monthly_hours_with_contract_settings(
                                                            $contract['id'], 
                                                            $contract['regular_visit_hours'] ?? 0, 
                                                            date('Y'), 
                                                            date('n'),
                                                            get_database_connection()
                                                        );
                                                        $remainingHours = $monthlyHoursForRemaining - ($contract['regular_hours'] ?? 0);
                                                    } else {
                                                        $remainingHours = $contract['remaining_hours'];
                                                    }
                                                    ?>
                                                    <?php if ($remainingHours > 0): ?>
                                                        <span class="text-success fw-bold">
                                                            <?= format_total_hours($remainingHours) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-danger fw-bold">
                                                            <?= format_total_hours(abs($remainingHours)) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <br><small class="text-muted">定期訪問分</small>
                                                <?php else: ?>
                                                    <!-- 非訪問月：臨時・時間なし役務のみ利用可能 -->
                                                    <div class="text-center">
                                                        <span class="text-info">非訪問月</span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <small class="text-danger">-</small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center" style="width: 120px;">
                                            <?php if ($frequency === 'spot'): ?>
                                                <span class="text-muted">-</span>
                                            <?php elseif ($isContractValid): ?>
                                                <?php if ($isVisitMonth): ?>
                                                    <!-- 訪問月の場合：進捗バーを表示 -->
                                                    <div class="progress position-relative" style="height: 20px;">
                                                        <?php 
                                                        // 使用率の計算
                                                        if ($frequency === 'weekly') {
                                                            $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                                $contract['id'], 
                                                                $contract['regular_visit_hours'] ?? 0, 
                                                                date('Y'), 
                                                                date('n'),
                                                                $db ?? null
                                                            );
                                                        } else {
                                                            $monthlyHours = $contract['regular_visit_hours'] ?? 0;
                                                        }

                                                        $thisMonthRegularHours = $contract['regular_hours'] ?? 0;
                                                        $actualPercentage = $monthlyHours > 0 ? ($thisMonthRegularHours / $monthlyHours) * 100 : 0;

                                                        // 表示用の進捗率（100%でキャップ）
                                                        $displayPercentage = min(100, $actualPercentage);
                                                        
                                                        // 色分けの計算
                                                        $progressClass = 'bg-success';
                                                        if ($actualPercentage >= 100) {
                                                            $progressClass = 'bg-danger';
                                                        } elseif ($actualPercentage >= 80) {
                                                            $progressClass = 'bg-warning';
                                                        } elseif ($actualPercentage >= 60) {
                                                            $progressClass = 'bg-info';
                                                        }
                                                        ?>
                                                        
                                                        <div class="progress-bar <?= $progressClass ?>" 
                                                            role="progressbar" 
                                                            style="width: <?= $displayPercentage ?>%"
                                                            aria-valuenow="<?= $actualPercentage ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100"
                                                            title="<?= format_total_hours($thisMonthRegularHours) ?> / <?= format_total_hours($monthlyHours) ?> (定期+延長)">
                                                            <?= round($actualPercentage) ?>%
                                                        </div>
                                                        
                                                        <!-- 100%より延長の場合の視覚的インジケーター -->
                                                        <?php if ($actualPercentage > 100): ?>
                                                            <div class="progress-overflow-indicator position-absolute top-0 end-0 h-100 d-flex align-items-center pe-1">
                                                                <i class="fas fa-exclamation-triangle text-white" style="font-size: 0.75rem;" title="契約時間延長"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted">定期訪問ベース</small>
                                                <?php else: ?>
                                                    <!-- 非訪問月の場合：限定的な役務利用を示すアイコン -->
                                                    <div class="text-center">
                                                        <small class="text-info">-</small>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <small class="text-danger">-</small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-grid gap-2">
                                                <?php if ($isContractValid): ?>
                                                    <?php if ($isCurrentMonthClosed): ?>
                                                        <!-- 月締め済みの場合 -->
                                                        <button class="btn btn-outline-secondary" disabled>
                                                            <i class="fas fa-lock me-2"></i>月締め済み
                                                        </button>
                                                        <small class="text-muted mt-1">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            今月は締め処理済みです
                                                        </small>
                                                    <?php else: ?>
                                                        <!-- 有効な契約：常に役務開始ボタンを表示 -->
                                                        <a href="<?= base_url("contracts/{$contract['id']}/select-service-type") ?>" 
                                                        class="btn btn-success"
                                                        <?= $hasActiveService ? 'onclick="return false;" style="opacity: 0.5; pointer-events: none;"' : '' ?>>
                                                            <i class="fas fa-play me-2"></i>役務開始
                                                        </a>
                                                        <a href="<?= base_url("service_records/create?contract_id={$contract['id']}&return_to=dashboard") ?>" 
                                                            class="btn btn-primary flex-fill">
                                                            <i class="fas fa-plus me-1"></i>役務追加
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                <?php else: ?>
                                                    <!-- 無効な契約 -->
                                                    <button class="btn btn-outline-danger" disabled>
                                                        <i class="fas fa-ban me-2"></i>
                                                        <?php if ($rowStatus === 'inactive'): ?>
                                                            契約無効
                                                        <?php elseif ($rowStatus === 'not_started'): ?>
                                                            開始前
                                                        <?php elseif ($rowStatus === 'expired'): ?>
                                                            期間終了
                                                        <?php else: ?>
                                                            利用不可
                                                        <?php endif; ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- 契約期間情報 -->
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <?= format_date($contract['start_date']) ?>～
                                                    <?php if ($contract['end_date']): ?>
                                                        <br><?= format_date($contract['end_date']) ?>
                                                    <?php endif; ?>
                                                </small>
                                                <?php if ($frequency === 'bimonthly' && $isContractValid): ?>
                                                    <br><small class="text-info">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        隔月（<?= $isVisitMonth ? '訪問月' : '休み月' ?>）
                                                        <?php if (!$isVisitMonth): ?>
                                                            <br><small class="text-warning" style="font-size: 0.7rem;">
                                                                ※定期役務は実施不可
                                                            </small>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php elseif ($frequency === 'weekly' && $isContractValid): ?>
                                                    <?php
                                                    $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                        $contract['id'], 
                                                        $contract['regular_visit_hours'] ?? 0, 
                                                        date('Y'), 
                                                        date('n'),
                                                        $db ?? null
                                                    );
                                                    $visitCount = ($contract['regular_visit_hours'] ?? 0) > 0 ? round($monthlyHours / ($contract['regular_visit_hours'] ?? 1)) : 0;
                                                    ?>
                                                    <br><small class="text-info">
                                                        <i class="fas fa-calendar-week me-1"></i>
                                                        今月: <?= format_total_hours($monthlyHours) ?> (<?= $visitCount ?>回)
                                                        <?php if ($contract['exclude_holidays']): ?>
                                                            <br><span class="text-warning">※祝日非訪問</span>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- モバイル表示（1024px未満） -->
                    <div class="d-lg-none">
                        <?php foreach ($contracts as $contract): ?>
                            <?php 
                            $frequency = $contract['visit_frequency'] ?? 'monthly';
                            $contractStatus = $contract['contract_status'] ?? 'active';
                            $startDate = $contract['start_date'] ?? null;
                            $endDate = $contract['end_date'] ?? null;
                            $currentDate = date('Y-m-d');
                            
                            // 契約の有効性チェック
                            $isContractActive = ($contractStatus === 'active');
                            $isContractStarted = ($startDate && $startDate <= $currentDate);
                            $isContractValid = ($isContractActive && $isContractStarted);
                            
                            // 契約終了チェック
                            $isContractExpired = ($endDate && $endDate < $currentDate);
                            if ($isContractExpired) {
                                $isContractValid = false;
                            }
                            
                            // 訪問月かどうかの判定（有効な契約の場合のみ）
                            $isVisitMonth = false;
                            if ($isContractValid) {
                                $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                            }

                            // 月締めチェック（新規追加）
                            $isCurrentMonthClosed = $contract['is_current_month_closed'] ?? false;

                            // 役務の追加・開始が可能かの最終判定
                            $canPerformService = $isContractValid && !$isCurrentMonthClosed;
                            
                            
                            // カードのスタイル決定
                            $cardClass = '';
                            $rowStatus = '';
                            if (!$isContractActive) {
                                $cardClass = 'border-danger bg-danger bg-opacity-10';
                                $rowStatus = 'inactive';
                            } elseif (!$isContractStarted) {
                                $cardClass = 'border-warning bg-warning-subtle';
                                $rowStatus = 'not_started';
                            } elseif ($isContractExpired) {
                                $cardClass = 'border-danger bg-danger bg-opacity-10';
                                $rowStatus = 'expired';
                            } elseif ($frequency === 'bimonthly' && !$isVisitMonth) {
                                $cardClass = 'border-info bg-info bg-opacity-10';
                                $rowStatus = 'non_visit_month_limited';
                            } else {
                                $rowStatus = 'active';
                            }
                            
                            // 時間なし役務の件数を取得
                            $documentCount = $contract['document_count'] ?? 0;
                            $remoteConsultationCount = $contract['remote_consultation_count'] ?? 0;
                            $otherCount = $contract['other_count'] ?? 0;
                            $totalNoTimeServices = $documentCount + $remoteConsultationCount + $otherCount;
                            
                            // データ属性の値を事前に準備（拠点モーダル用）
                            $branchName = htmlspecialchars($contract['branch_name'] ?? '-', ENT_QUOTES, 'UTF-8');
                            $branchAddress = htmlspecialchars($contract['branch_address'] ?? '-', ENT_QUOTES, 'UTF-8');
                            $branchPhone = htmlspecialchars($contract['branch_phone'] ?? '-', ENT_QUOTES, 'UTF-8');
                            $branchEmail = htmlspecialchars($contract['branch_email'] ?? '-', ENT_QUOTES, 'UTF-8');
                            $companyName = htmlspecialchars($contract['company_name'] ?? '-', ENT_QUOTES, 'UTF-8');
                            $companyAddress = htmlspecialchars($contract['company_address'] ?? '-', ENT_QUOTES, 'UTF-8');
                            ?>
                            
                            <div class="card mobile-contract-card mb-3 <?= $cardClass ?>">
                                <div class="card-body">
                                    <!-- 企業名・拠点名 -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">
                                                <i class="fas fa-building me-2 text-success"></i>
                                                <?= $companyName ?>
                                            </h6>
                                            <p class="card-subtitle text-muted mb-2">
                                                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                                <a href="#" 
                                                   class="text-decoration-none branch-info-link" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#branchInfoModal"
                                                   data-branch-name="<?= $branchName ?>"
                                                   data-branch-address="<?= $branchAddress ?>"
                                                   data-branch-phone="<?= $branchPhone ?>"
                                                   data-branch-email="<?= $branchEmail ?>"
                                                   data-company-name="<?= $companyName ?>"
                                                   data-company-address="<?= $companyAddress ?>">
                                                    <?= $branchName ?>
                                                    <i class="fas fa-info-circle ms-1 text-info"></i>
                                                </a>
                                            </p>
                                        </div>
                                        
                                        <!-- 契約状態バッジ -->
                                        <?php if ($rowStatus !== 'active'): ?>
                                            <div class="ms-2">
                                                <?php if ($rowStatus === 'inactive'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-ban me-1"></i>契約無効
                                                    </span>
                                                <?php elseif ($rowStatus === 'not_started'): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>開始前
                                                    </span>
                                                <?php elseif ($rowStatus === 'expired'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-calendar-times me-1"></i>期間終了
                                                    </span>
                                                <?php elseif ($rowStatus === 'non_visit_month_limited'): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-calendar-minus me-1"></i>非訪問月（制限あり）
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 契約詳細情報 -->
                                    <div class="row g-3 mb-3">
                                        <!-- 訪問頻度 -->
                                        <div class="col-6">
                                            <div class="info-item">
                                                <small class="text-muted d-block">訪問頻度</small>
                                                <span class="badge bg-<?= get_visit_frequency_class($frequency) ?> <?= $isContractExpired ? 'opacity-50' : '' ?>">
                                                    <i class="<?= get_visit_frequency_icon($frequency) ?> me-1"></i>
                                                    <?= get_visit_frequency_label($frequency) ?>
                                                </span>
                                                <?php if ($frequency === 'bimonthly' && $isContractValid): ?>
                                                    <div class="mt-1">
                                                        <small class="text-<?= $isVisitMonth ? 'success' : 'muted' ?>">
                                                            <i class="fas fa-calendar-<?= $isVisitMonth ? 'check' : 'times' ?> me-1"></i>
                                                            <?= $isVisitMonth ? '今月訪問' : '今月休み' ?>
                                                        </small>
                                                    </div>
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
                                                    <div class="mt-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar-week me-1"></i><?= $scheduleText ?>曜
                                                        </small>
                                                    </div>
                                                    <div class="mt-1">
                                                        <small class="text-muted">
                                                            <?php if ($contract['exclude_holidays'] ?? 1): ?>
                                                                <i class="fas fa-calendar-times text-warning me-1"></i>祝日非訪問
                                                            <?php else: ?>
                                                                <i class="fas fa-calendar-check text-info me-1"></i>祝日訪問可
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                    <div class="mt-1">
                                                        <small class="text-muted">
                                                            <?php 
                                                            // 非訪問日設定の有無をチェック（モバイル用）
                                                            $currentYear = date('Y');
                                                            $currentMonth = date('m');
                                                            $nonVisitSql = "SELECT COUNT(*) as count FROM contract_non_visit_days 
                                                                            WHERE contract_id = :contract_id 
                                                                            AND (year = :year 
                                                                            OR (is_recurring = 1 AND year < :year2))
                                                                            AND MONTH(non_visit_date) = :month
                                                                            AND is_active = 1";
                                                            $stmt = $db->prepare($nonVisitSql);
                                                            $stmt->execute(['contract_id' => $contract['id'], 'year' => $currentYear, 'year2' => $currentYear, 'month' => $currentMonth]);
                                                            $nonVisitCount = $stmt->fetchColumn();
                                                            
                                                            if ($nonVisitCount > 0): ?>
                                                                <i class="fas fa-ban text-danger me-1"></i>
                                                                <a href="#" class="text-decoration-none text-danger" 
                                                                onclick="showNonVisitDays(<?= $contract['id'] ?>, '<?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                                                    非訪問日<?= $nonVisitCount ?>件(今月)
                                                                </a>
                                                            <?php else: ?>
                                                                <i class="fas fa-calendar-day text-muted me-1"></i>非訪問日未設定
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 契約時間 -->
                                        <div class="col-6">
                                            <div class="mobile-info-item">
                                                <small class="text-muted d-block">契約時間</small>
                                                <?php if ($frequency === 'spot'): ?>
                                                    <span class="text-muted">-</span>
                                                <?php else: ?>
                                                <span class="badge bg-primary fs-6">
                                                    <?= format_total_hours($contract['regular_visit_hours']) ?>
                                                </span>
                                                <?php if ($frequency === 'weekly' && $isContractValid): ?>
                                                    <br><small class="text-muted">(1回あたり)</small>
                                                    <?php
                                                    // 毎週契約の場合は動的に月間時間を計算（モバイル用）
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
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 今月実績 -->
                                        <div class="col-6">
                                            <div class="mobile-info-item">
                                                <small class="text-muted d-block">今月実績</small>
                                                <?php if ($frequency === 'spot'): ?>
                                                    <!-- スポット契約は今月実績のみ表示（残り時間なし） -->
                                                    <div class="fw-bold text-primary">
                                                        <?= format_total_hours($contract['total_hours'] ?? 0) ?>
                                                    </div>
                                                    <?php if ((($contract['regular_hours'] ?? 0) + ($contract['emergency_hours'] ?? 0) + ($contract['extension_hours'] ?? 0)) > 0): ?>
                                                    <div class="small text-muted">
                                                        <?php if (($contract['regular_hours'] ?? 0) > 0): ?>
                                                            定期: <?= format_total_hours($contract['regular_hours']) ?>
                                                        <?php endif; ?>
                                                        <?php if (($contract['emergency_hours'] ?? 0) > 0): ?>
                                                            <br>臨時: <?= format_total_hours($contract['emergency_hours']) ?>
                                                        <?php endif; ?>
                                                        <?php if (($contract['extension_hours'] ?? 0) > 0): ?>
                                                            <br>延長: <?= format_total_hours($contract['extension_hours']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php elseif ($isContractValid): ?>
                                                    <?php if ($isVisitMonth): ?>
                                                        <!-- 訪問月の場合：全ての役務実績を表示 -->
                                                        <div class="fw-bold text-primary">
                                                            <?= format_total_hours($contract['total_hours'] ?? 0) ?>
                                                        </div>
                                                        <div class="small text-muted">
                                                            定期: <?= format_total_hours($contract['regular_hours'] ?? 0) ?>
                                                            
                                                            <?php if (($contract['emergency_hours'] ?? 0) > 0): ?>
                                                                <br>臨時: <?= format_total_hours($contract['emergency_hours']) ?>
                                                            <?php endif; ?>
                                                            <?php if (($contract['extension_hours'] ?? 0) > 0): ?>
                                                                <br>延長: <?= format_total_hours($contract['extension_hours']) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <!-- 毎週契約の場合の残り時間表示 -->
                                                        <?php if ($frequency === 'spot'): ?>
                                                            <!-- スポット契約は残り時間を表示しない -->
                                                        <?php elseif ($frequency === 'weekly'): ?>
                                                            <?php
                                                            $monthlyHoursForRemaining = calculate_weekly_monthly_hours_with_contract_settings(
                                                                $contract['id'], 
                                                                $contract['regular_visit_hours'] ?? 0, 
                                                                date('Y'), 
                                                                date('n'),
                                                                get_database_connection()
                                                            );
                                                            $remainingHours = $monthlyHoursForRemaining - ($contract['regular_hours'] ?? 0);
                                                            ?>
                                                            <div class="mt-1">
                                                                <small class="<?= $remainingHours > 0 ? 'text-success' : 'text-danger' ?>">
                                                                    残り: <?= format_total_hours(abs($remainingHours)) ?>
                                                                </small>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="mt-1">
                                                                <small class="<?= ($contract['remaining_hours'] ?? 0) > 0 ? 'text-success' : 'text-danger' ?>">
                                                                    残り: <?= format_total_hours(abs($contract['remaining_hours'] ?? 0)) ?>
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                    <?php else: ?>
                                                        <!-- 非訪問月の場合：臨時・時間なし役務の実績を表示 -->
                                                        <div class="fw-bold text-secondary">
                                                            非訪問月
                                                        </div>
                                                        <?php if (($contract['emergency_hours'] ?? 0) > 0 || $totalNoTimeServices > 0): ?>
                                                            <div class="small text-info">
                                                                <?php if (($contract['emergency_hours'] ?? 0) > 0): ?>
                                                                    臨時: <?= format_total_hours($contract['emergency_hours']) ?><br>
                                                                <?php endif; ?>
                                                                <?php if ($totalNoTimeServices > 0): ?>
                                                                    時間なし役務: <?= $totalNoTimeServices ?>件
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="small text-muted">
                                                                実績なし
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-danger">利用不可</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 時間なし役務 -->
                                        <div class="col-6">
                                            <div class="mobile-info-item">
                                                <small class="text-muted d-block">時間なし役務</small>
                                                <?php if ($isContractValid && $totalNoTimeServices > 0): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php if ($documentCount > 0): ?>
                                                            <span class="badge bg-primary">
                                                                書面: <?= $documentCount ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($remoteConsultationCount > 0): ?>
                                                            <span class="badge bg-secondary">
                                                                遠隔: <?= $remoteConsultationCount ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($otherCount > 0): ?>
                                                            <span class="badge bg-dark">
                                                                その他: <?= $otherCount ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 操作ボタン -->
                                    <div class="d-grid mb-2">
                                        <?php if ($isContractValid): ?>
                                            <?php if ($isCurrentMonthClosed): ?>
                                                <!-- 月締め済みの場合 -->
                                                <button class="btn btn-outline-secondary btn-lg" disabled>
                                                    <i class="fas fa-lock me-2"></i>月締め済み
                                                </button>
                                                <div class="alert alert-info mt-2 mb-0" style="padding: 0.5rem;">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        <strong>今月は締め処理が完了しています</strong><br>
                                                        • 新規役務の開始・追加はできません<br>
                                                        • 既存役務の編集もできません
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <!-- 有効な契約：常に役務開始ボタンを表示 -->
                                                <a href="<?= base_url("contracts/{$contract['id']}/select-service-type") ?>" 
                                                class="btn btn-success btn-lg"
                                                <?= $hasActiveService ? 'onclick="return false;" style="opacity: 0.5; pointer-events: none;"' : '' ?>>
                                                    <i class="fas fa-play me-2"></i>役務開始
                                                </a>
                                                <a href="<?= base_url("service_records/create?contract_id={$contract['id']}&return_to=dashboard") ?>" 
                                                    class="btn btn-primary btn-lg flex-fill">
                                                    <i class="fas fa-plus me-1"></i>役務追加
                                                </a>
                                            <?php endif; ?>

                                            <!-- 非訪問月の場合は注意書きを表示 -->
                                            <?php if ($frequency === 'bimonthly' && !$isVisitMonth): ?>
                                                <div class="alert alert-info mt-2 mb-0" style="padding: 0.5rem;">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        <strong>定期訪問は利用できません</strong>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <!-- 無効な契約 -->
                                            <button class="btn btn-outline-danger btn-lg" disabled>
                                                <i class="fas fa-ban me-2"></i>
                                                <?php if ($rowStatus === 'inactive'): ?>
                                                    契約無効
                                                <?php elseif ($rowStatus === 'not_started'): ?>
                                                    開始前
                                                <?php elseif ($rowStatus === 'expired'): ?>
                                                    期間終了
                                                <?php else: ?>
                                                    利用不可
                                                <?php endif; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 契約期間情報 -->
                                    <div class="text-center">
                                        <small class="text-muted">
                                            契約期間: <?= format_date($contract['start_date']) ?>～
                                            <?php if ($contract['end_date']): ?>
                                                <?= format_date($contract['end_date']) ?>
                                            <?php endif; ?>
                                        </small>
                                        <?php if ($frequency === 'bimonthly' && $isContractValid): ?>
                                            <br><small class="text-info">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                隔月契約（<?= $isVisitMonth ? '訪問月' : '休み月' ?>）
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 凡例（契約状態・訪問頻度対応版） -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    <strong>進捗の色分け:</strong>
                                    <span class="badge bg-success me-1">0-59%</span>
                                    <span class="badge bg-info me-1">60-79%</span>
                                    <span class="badge bg-warning me-1">80-99%</span>
                                    <span class="badge bg-danger me-1">100%以上</span>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
                <!-- 契約一覧ページネーション -->
                <?php if ($contractsPaginationInfo['total_pages'] > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div class="d-none d-md-block">
                            <small class="text-muted">
                                <?= $contractsPaginationInfo['start_record'] ?>-<?= $contractsPaginationInfo['end_record'] ?>件 
                                (全<?= $contractsPaginationInfo['total_count'] ?>件中)
                            </small>
                        </div>
                        
                        <nav aria-label="契約一覧ページネーション">
                            <ul class="pagination pagination-sm mb-0">
                                <!-- 前のページ -->
                                <li class="page-item <?= !$contractsPaginationInfo['has_previous'] ? 'disabled' : '' ?>">
                                    <a class="page-link contracts-pagination-link" 
                                       href="#" 
                                       data-page="<?= $contractsPaginationInfo['current_page'] - 1 ?>"
                                       data-page-size="<?= $contractsPaginationInfo['page_size'] ?>"
                                       aria-label="前のページ">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <!-- ページ番号 -->
                                <?php 
                                $start = max(1, $contractsPaginationInfo['current_page'] - 2);
                                $end = min($contractsPaginationInfo['total_pages'], $contractsPaginationInfo['current_page'] + 2);
                                ?>
                                
                                <?php if ($start > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link contracts-pagination-link" 
                                           href="#" 
                                           data-page="1"
                                           data-page-size="<?= $contractsPaginationInfo['page_size'] ?>">1</a>
                                    </li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $contractsPaginationInfo['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link contracts-pagination-link" 
                                           href="#" 
                                           data-page="<?= $i ?>"
                                           data-page-size="<?= $contractsPaginationInfo['page_size'] ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end < $contractsPaginationInfo['total_pages']): ?>
                                    <?php if ($end < $contractsPaginationInfo['total_pages'] - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link contracts-pagination-link" 
                                           href="#" 
                                           data-page="<?= $contractsPaginationInfo['total_pages'] ?>"
                                           data-page-size="<?= $contractsPaginationInfo['page_size'] ?>"><?= $contractsPaginationInfo['total_pages'] ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- 次のページ -->
                                <li class="page-item <?= !$contractsPaginationInfo['has_next'] ? 'disabled' : '' ?>">
                                    <a class="page-link contracts-pagination-link" 
                                       href="#" 
                                       data-page="<?= $contractsPaginationInfo['current_page'] + 1 ?>"
                                       data-page-size="<?= $contractsPaginationInfo['page_size'] ?>"
                                       aria-label="次のページ">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        
                        <div class="d-md-none">
                            <small class="text-muted">
                                <?= $contractsPaginationInfo['current_page'] ?>/<?= $contractsPaginationInfo['total_pages'] ?>ページ
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 最近の役務記録（スマホ対応版・ページネーション対応） -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>最近の役務記録</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                            <span class="badge bg-info"><?= $serviceRecordsPaginationInfo['total_count'] ?>件</span>
                            
                            <!-- ページサイズ選択 -->
                            <div class="d-flex align-items-center">
                                <label for="service_records_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                                <select id="service_records_page_size" class="form-select form-select-sm" style="width: auto;">
                                    <option value="5" <?= ($serviceRecordsPaginationInfo['page_size'] == 5) ? 'selected' : '' ?>>5件</option>
                                    <option value="10" <?= ($serviceRecordsPaginationInfo['page_size'] == 10) ? 'selected' : '' ?>>10件</option>
                                    <option value="15" <?= ($serviceRecordsPaginationInfo['page_size'] == 15) ? 'selected' : '' ?>>15件</option>
                                    <option value="20" <?= ($serviceRecordsPaginationInfo['page_size'] == 20) ? 'selected' : '' ?>>20件</option>
                                    <option value="30" <?= ($serviceRecordsPaginationInfo['page_size'] == 30) ? 'selected' : '' ?>>30件</option>
                                </select>
                            </div>
                            
                            <a href="<?= base_url('service_records') ?>" class="btn btn-sm btn-outline-primary">すべて見る</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($serviceRecords)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">今月の役務記録がありません。</p>
                    </div>
                <?php else: ?>
                    
                    <!-- デスクトップ表示（768px以上） -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>企業名</th>
                                    <th>拠点</th>
                                    <th>役務日</th>
                                    <th>実施時間</th>
                                    <th>役務時間</th>
                                    <th>種別</th>
                                    <th>状態</th>
                                    <th>登録日時</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($serviceRecords as $record): ?>
                                    <?php
                                    // 自動分割記録かどうかの判定
                                    $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                                                  strpos($record['description'] ?? '', '自動分割') !== false ||
                                                  strpos($record['description'] ?? '', '延長分') !== false;
                                    
                                    // 役務種別と訪問種別を取得
                                    $currentServiceType = $record['service_type'] ?? 'regular';
                                    $currentVisitType = $record['visit_type'] ?? 'visit';
                                    
                                    // 時間管理が必要な役務種別かどうかを判定
                                    $requiresTime = in_array($currentServiceType, ['regular', 'emergency', 'extension', 'spot']);
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-building me-1 text-success"></i>
                                            <?= htmlspecialchars($record['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                            <?= htmlspecialchars($record['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= format_date($record['service_date']) ?></td>
                                        <td>
                                            <small>
                                                <?php if ($requiresTime): ?>
                                                    <!-- 時間管理ありの役務の場合 -->
                                                    <?php if ($record['service_hours'] > 0): ?>
                                                        <!-- 時間分割対応の表示 -->
                                                        <?= format_service_time_display($record) ?>
                                                        
                                                        <!-- 自動分割記録の場合の追加情報 -->
                                                        <?php if ($isAutoSplit): ?>
                                                            <div class="small text-info mt-1">
                                                                <i class="fas fa-info-circle me-1"></i>分割記録
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <!-- 進行中の役務 -->
                                                        <span class="text-warning">
                                                            <i class="fas fa-spinner fa-spin me-1"></i>進行中
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <!-- 時間管理なしの役務の場合（書面作成・遠隔相談） -->
                                                    <span class="text-muted">
                                                        <i class="fas fa-minus-circle me-1"></i>時間記録なし
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($requiresTime): ?>
                                                <!-- 時間管理ありの役務の場合 -->
                                                <?php if ($record['service_hours'] > 0): ?>
                                                    <span class="badge bg-info"><?= format_service_hours($record['service_hours']) ?></span>
                                                <?php else: ?>
                                                    <!-- 進行中の役務 -->
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-spinner fa-spin me-1"></i>進行中
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- 時間管理なしの役務の場合（書面作成・遠隔相談） -->
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-minus me-1"></i>時間なし
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                             $serviceTypeIcons = [
                                                'regular' => ['icon' => 'calendar-check', 'class' => 'success', 'label' => '定期'],
                                                'emergency' => ['icon' => 'exclamation-triangle', 'class' => 'warning', 'label' => '臨時'],
                                                'extension' => ['icon' => 'clock', 'class' => 'info', 'label' => '延長'],
                                                'document' => ['icon' => 'file-alt', 'class' => 'primary', 'label' => '書面'],
                                                'remote_consultation' => ['icon' => 'envelope', 'class' => 'secondary', 'label' => '遠隔'],
                                                'spot' => ['icon' => 'clock', 'class' => 'info', 'label' => 'スポット'],
                                                'other' => ['icon' => 'plus', 'class' => 'dark', 'label' => 'その他']
                                            ];
                                            $serviceType = $serviceTypeIcons[$currentServiceType];
                                            ?>
                                            <span class="badge bg-<?= $serviceType['class'] ?>">
                                                <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                                <?= $serviceType['label'] ?>
                                            </span>
                                            
                                            <!-- 訪問種別の表示（訪問種別が必要な役務のみ） -->
                                            <?php if (requires_visit_type_selection($currentServiceType)): ?>
                                                <div class="mt-1">
                                                    <?php if ($currentVisitType === 'online'): ?>
                                                        <span class="badge bg-light text-dark border" title="オンライン実施">
                                                            <i class="fas fa-laptop me-1"></i>オンライン
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark border" title="現地訪問">
                                                            <i class="fas fa-walking me-1"></i>訪問
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- 自動分割記録のマーク -->
                                            <?php if ($currentServiceType === 'extension' && $isAutoSplit): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-secondary" title="自動分割により作成された記録">
                                                        <i class="fas fa-scissors me-1"></i>分割
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($record['status'] === 'rejected'): ?>
                                                <span class="badge bg-<?= get_status_class($record['status']) ?> cursor-pointer rejection-reason-badge" 
                                                      data-bs-toggle="modal" 
                                                      data-bs-target="#rejectionReasonModal"
                                                      data-company-comment="<?= htmlspecialchars($record['company_comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                      data-company-name="<?= htmlspecialchars($record['company_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                      data-service-date="<?= format_date($record['service_date']) ?>"
                                                      title="クリックして差戻し理由を表示">
                                                    <?= get_status_label($record['status']) ?>
                                                    <i class="fas fa-info-circle ms-1"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-<?= get_status_class($record['status']) ?>">
                                                    <?= get_status_label($record['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= date('m/d H:i', strtotime($record['created_at'])) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- モバイル表示（768px未満） -->
                    <div class="d-md-none">
                        <?php foreach ($serviceRecords as $record): ?>
                            <?php
                            // 自動分割記録かどうかの判定
                            $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                                          strpos($record['description'] ?? '', '自動分割') !== false ||
                                          strpos($record['description'] ?? '', '延長分') !== false;
                            
                            // 役務種別と訪問種別を取得
                            $currentServiceType = $record['service_type'] ?? 'regular';
                            $currentVisitType = $record['visit_type'] ?? 'visit';
                            
                            // 時間管理が必要な役務種別かどうかを判定
                            $requiresTime = in_array($currentServiceType, ['regular', 'emergency', 'extension', 'spot']);
                            
                            $serviceTypeIcons = [
                                'regular' => ['icon' => 'calendar-check', 'class' => 'success', 'label' => '定期'],
                                'emergency' => ['icon' => 'exclamation-triangle', 'class' => 'warning', 'label' => '臨時'],
                                'extension' => ['icon' => 'clock', 'class' => 'info', 'label' => '延長'],
                                'document' => ['icon' => 'file-alt', 'class' => 'primary', 'label' => '書面'],
                                'remote_consultation' => ['icon' => 'envelope', 'class' => 'secondary', 'label' => '遠隔'],
                                'spot' => ['icon' => 'clock', 'class' => 'info', 'label' => 'スポット'],
                                'other' => ['icon' => 'plus', 'class' => 'dark', 'label' => 'その他']
                            ];
                            $serviceType = $serviceTypeIcons[$currentServiceType];
                            ?>
                            
                            <div class="card mobile-service-card mb-3">
                                <div class="card-body">
                                    <!-- 企業名・拠点名 -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">
                                                <i class="fas fa-building me-2 text-success"></i>
                                                <?= htmlspecialchars($record['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </h6>
                                            <p class="card-subtitle text-muted mb-0">
                                                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                                <?= htmlspecialchars($record['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                        </div>
                                        
                                        <!-- 状態バッジ -->
                                        <?php if ($record['status'] === 'rejected'): ?>
                                            <span class="badge bg-<?= get_status_class($record['status']) ?> cursor-pointer rejection-reason-badge" 
                                                  data-bs-toggle="modal" 
                                                  data-bs-target="#rejectionReasonModal"
                                                  data-company-comment="<?= htmlspecialchars($record['company_comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                  data-company-name="<?= htmlspecialchars($record['company_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                  data-service-date="<?= format_date($record['service_date']) ?>"
                                                  title="タップして差戻し理由を表示">
                                                <?= get_status_label($record['status']) ?>
                                                <i class="fas fa-info-circle ms-1"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-<?= get_status_class($record['status']) ?>">
                                                <?= get_status_label($record['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 役務詳細情報 -->
                                    <div class="row g-2 mb-2">
                                        <!-- 役務日 -->
                                        <div class="col-6">
                                            <div class="mobile-info-item">
                                                <small class="text-muted d-block">役務日</small>
                                                <span class="fw-bold"><?= format_date($record['service_date']) ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- 登録日時 -->
                                        <div class="col-6">
                                            <div class="mobile-info-item">
                                                <small class="text-muted d-block">登録日時</small>
                                                <span><?= date('m/d H:i', strtotime($record['created_at'])) ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- 種別 -->
                                        <div class="col-6">
                                            <div class="mobile-info-item">
                                                <small class="text-muted d-block">種別</small>
                                                <span class="badge bg-<?= $serviceType['class'] ?>">
                                                    <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                                    <?= $serviceType['label'] ?>
                                                </span>
                                                
                                                <!-- 訪問種別の表示（訪問種別が必要な役務のみ） -->
                                                <?php if (requires_visit_type_selection($currentServiceType)): ?>
                                                    <div class="mt-1">
                                                        <?php if ($currentVisitType === 'online'): ?>
                                                            <span class="badge bg-light text-dark border">
                                                                <i class="fas fa-laptop me-1"></i>オンライン
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-dark border">
                                                                <i class="fas fa-walking me-1"></i>訪問
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- 自動分割記録のマーク -->
                                                <?php if ($currentServiceType === 'extension' && $isAutoSplit): ?>
                                                    <div class="mt-1">
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-scissors me-1"></i>分割
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 役務時間 -->
                                        <div class="col-6">
                                            <div class="mobile-info-item">
                                                <small class="text-muted d-block">役務時間</small>
                                                <?php if ($requiresTime): ?>
                                                    <!-- 時間管理ありの役務の場合 -->
                                                    <?php if ($record['service_hours'] > 0): ?>
                                                        <span class="badge bg-info"><?= format_service_hours($record['service_hours']) ?></span>
                                                    <?php else: ?>
                                                        <!-- 進行中の役務 -->
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-spinner fa-spin me-1"></i>進行中
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <!-- 時間管理なしの役務の場合（書面作成・遠隔相談） -->
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-minus me-1"></i>時間なし
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 実施時間（時間管理ありの場合のみ表示） -->
                                    <?php if ($requiresTime): ?>
                                        <div class="mobile-info-item">
                                            <small class="text-muted d-block">実施時間</small>
                                            <?php if ($record['service_hours'] > 0): ?>
                                                <!-- 時間分割対応の表示 -->
                                                <small class="text-dark"><?= format_service_time_display($record) ?></small>
                                                
                                                <!-- 自動分割記録の場合の追加情報 -->
                                                <?php if ($isAutoSplit): ?>
                                                    <div class="small text-info mt-1">
                                                        <i class="fas fa-info-circle me-1"></i>分割記録
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- 進行中の役務 -->
                                                <small class="text-warning">
                                                    <i class="fas fa-spinner fa-spin me-1"></i>進行中
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 役務記録ページネーション -->
                <?php if ($serviceRecordsPaginationInfo['total_pages'] > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div class="d-none d-md-block">
                            <small class="text-muted">
                                <?= $serviceRecordsPaginationInfo['start_record'] ?>-<?= $serviceRecordsPaginationInfo['end_record'] ?>件 
                                (全<?= $serviceRecordsPaginationInfo['total_count'] ?>件中)
                            </small>
                        </div>
                        
                        <nav aria-label="役務記録ページネーション">
                            <ul class="pagination pagination-sm mb-0">
                                <!-- 前のページ -->
                                <li class="page-item <?= !$serviceRecordsPaginationInfo['has_previous'] ? 'disabled' : '' ?>">
                                    <a class="page-link service-records-pagination-link" 
                                       href="#" 
                                       data-page="<?= $serviceRecordsPaginationInfo['current_page'] - 1 ?>"
                                       data-page-size="<?= $serviceRecordsPaginationInfo['page_size'] ?>"
                                       aria-label="前のページ">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <!-- ページ番号 -->
                                <?php 
                                $start = max(1, $serviceRecordsPaginationInfo['current_page'] - 2);
                                $end = min($serviceRecordsPaginationInfo['total_pages'], $serviceRecordsPaginationInfo['current_page'] + 2);
                                ?>
                                
                                <?php if ($start > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link service-records-pagination-link" 
                                           href="#" 
                                           data-page="1"
                                           data-page-size="<?= $serviceRecordsPaginationInfo['page_size'] ?>">1</a>
                                    </li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $serviceRecordsPaginationInfo['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link service-records-pagination-link" 
                                           href="#" 
                                           data-page="<?= $i ?>"
                                           data-page-size="<?= $serviceRecordsPaginationInfo['page_size'] ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end < $serviceRecordsPaginationInfo['total_pages']): ?>
                                    <?php if ($end < $serviceRecordsPaginationInfo['total_pages'] - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link service-records-pagination-link" 
                                           href="#" 
                                           data-page="<?= $serviceRecordsPaginationInfo['total_pages'] ?>"
                                           data-page-size="<?= $serviceRecordsPaginationInfo['page_size'] ?>"><?= $serviceRecordsPaginationInfo['total_pages'] ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- 次のページ -->
                                <li class="page-item <?= !$serviceRecordsPaginationInfo['has_next'] ? 'disabled' : '' ?>">
                                    <a class="page-link service-records-pagination-link" 
                                       href="#" 
                                       data-page="<?= $serviceRecordsPaginationInfo['current_page'] + 1 ?>"
                                       data-page-size="<?= $serviceRecordsPaginationInfo['page_size'] ?>"
                                       aria-label="次のページ">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        
                        <div class="d-md-none">
                            <small class="text-muted">
                                <?= $serviceRecordsPaginationInfo['current_page'] ?>/<?= $serviceRecordsPaginationInfo['total_pages'] ?>ページ
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 拠点詳細情報モーダル（修正版） -->
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
                                    <dd class="col-sm-8" id="modalCompanyName">読み込み中...</dd>
                                    
                                    <dt class="col-sm-4">本社住所:</dt>
                                    <dd class="col-sm-8" id="modalCompanyAddress">読み込み中...</dd>
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
                                    <dd class="col-sm-8" id="modalBranchName">読み込み中...</dd>
                                    
                                    <dt class="col-sm-4">住所:</dt>
                                    <dd class="col-sm-8" id="modalBranchAddress">読み込み中...</dd>
                                    
                                    <dt class="col-sm-4">電話番号:</dt>
                                    <dd class="col-sm-8" id="modalBranchPhone">読み込み中...</dd>
                                    
                                    <dt class="col-sm-4">メール:</dt>
                                    <dd class="col-sm-8" id="modalBranchEmail">読み込み中...</dd>
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
                                <div class="btn-group" role="group">
                                    <button type="button" 
                                            class="btn btn-outline-primary" 
                                            id="copyAddressBtn"
                                            data-address="">
                                        <i class="fas fa-copy me-1"></i>住所をコピー
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-success" 
                                            id="openMapBtn"
                                            data-address="">
                                        <i class="fas fa-map me-1"></i>地図で表示
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-info" 
                                            id="callPhoneBtn"
                                            data-phone="">
                                        <i class="fas fa-phone me-1"></i>電話をかける
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- デバッグ情報表示エリア（開発時のみ） -->
                <?php 
                $showDebug = (defined('ENVIRONMENT') && ENVIRONMENT === 'development') || 
                            (defined('CI_DEBUG') && CI_DEBUG) || 
                            (isset($_GET['debug']) && $_GET['debug'] === '1');
                if ($showDebug): 
                ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-bug me-2"></i>デバッグ情報（開発環境のみ）
                                </h6>
                            </div>
                            <div class="card-body">
                                <small class="text-muted">
                                    <div id="debugInfo">データ読み込み中...</div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 差戻し理由表示モーダル -->
<div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-labelledby="rejectionReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="rejectionReasonModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>差戻し理由
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-2">企業名・役務日</h6>
                    <p class="mb-0">
                        <i class="fas fa-building me-2 text-success"></i>
                        <span id="rejectionCompanyName"></span>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2 text-primary"></i>
                        <span id="rejectionServiceDate"></span>
                    </p>
                </div>
                <hr>
                <div>
                    <h6 class="text-muted mb-2">差戻し理由</h6>
                    <div class="alert alert-warning mb-0" id="rejectionReasonContent">
                        <i class="fas fa-comment-dots me-2"></i>
                        <span id="rejectionReasonText"></span>
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

<style>
/* ========== 基本スタイル ========== */

/* 差戻し理由バッジのカーソルスタイル */
.cursor-pointer {
    cursor: pointer;
}

.rejection-reason-badge {
    transition: all 0.2s ease;
}

.rejection-reason-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* 契約状態別行スタイル */
.table-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.table-secondary {
    background-color: rgba(108, 117, 125, 0.1) !important;
}

/* 新しい非訪問月（制限付き）のスタイル */
.table-info {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

/* カードホバーエフェクト */
.card-hover {
    transition: all 0.3s ease;
    cursor: pointer;
}

.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

/* ボタンの間隔調整 */
.d-flex.gap-1 > .btn {
    font-size: 0.875rem;
}

.d-flex.gap-2 > .btn {
    font-size: 0.875rem;
}

/* モバイルでのボタン調整 */
@media (max-width: 767px) {
    .mobile-contract-card .d-flex.gap-2 > .btn {
        font-size: 0.8rem;
        padding: 0.6rem 0.8rem;
    }
}

@media (max-width: 575px) {
    .mobile-contract-card .d-flex.gap-2 > .btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.6rem;
    }
}

/* ========== モバイル専用スタイル ========== */

/* モバイル契約カード */
.mobile-contract-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.mobile-contract-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.mobile-contract-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

.mobile-contract-card .card-subtitle {
    font-size: 0.875rem;
}

/* モバイル情報項目 */
.mobile-info-item {
    padding: 0.5rem 0;
}

.mobile-info-item small {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

/* モバイル役務記録カード */
.mobile-service-card {
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.mobile-service-card:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-service-card .card-title {
    font-size: 0.95rem;
    font-weight: 600;
}

.mobile-service-card .card-subtitle {
    font-size: 0.825rem;
}

/* モバイル統計カード */
.mobile-stats-card {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.mobile-stats-card:hover {
    background-color: #e9ecef;
    transform: scale(1.02);
}

/* 進捗バーの100%より延長対応 */
.progress-overflow-indicator {
    pointer-events: none;
    z-index: 10;
}

/* ========== 新しいスタイル（非訪問月制限対応） ========== */

/* 非訪問月の契約状態バッジを調整 */
.badge.non-visit-month-limited {
    background-color: #0dcaf0 !important;
    color: white;
}

/* モバイルカードの非訪問月スタイル */
.mobile-contract-card.border-info {
    border-width: 2px !important;
    background-color: rgba(13, 202, 240, 0.1) !important;
}

/* アイコンの整列 */
.service-icons-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.25rem;
}

.service-icons-row i {
    width: 1rem;
    text-align: center;
}

/* ========== ページネーション専用スタイル ========== */

/* ページネーション全体のスタイル */
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

/* ========== レスポンシブ対応 ========== */

/* タブレット表示（1024px未満） */
@media (max-width: 1023px) {
    /* 統計カードのフォントサイズ調整 */
    .card-body h5 {
        font-size: 0.9rem;
    }
    
    .card-body h2 {
        font-size: 1.5rem;
    }
    
    .card-body small {
        font-size: 0.75rem;
    }
    
    /* ページネーションの調整 */
    .pagination .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

/* モバイル表示（768px未満） */
@media (max-width: 767px) {
    /* ヘッダー調整 */
    .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between.align-items-center.mb-4 h2 {
        text-align: center;
        margin-bottom: 0;
        font-size: 1.5rem;
    }
    
    .btn-group {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        flex: 1;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* 契約一覧ヘッダー調整 */
    .card-header .row {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .card-header h5 {
        text-align: center;
        margin-bottom: 0;
    }
    
    .card-header .d-flex {
        justify-content: center;
    }
    
    /* ページサイズ選択の調整 */
    .form-select-sm {
        font-size: 0.8rem;
        min-width: 70px;
    }
    
    /* ページネーションの調整 */
    .pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .pagination .page-link {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
        min-width: 32px;
        text-align: center;
    }
    
    /* ページネーション情報の調整 */
    .pagination-info {
        font-size: 0.75rem;
        text-align: center;
    }
    
    /* モバイル契約カードのスタイル調整 */
    .mobile-contract-card .card-body {
        padding: 1rem;
    }
    
    .mobile-contract-card .btn-lg {
        padding: 0.75rem 1rem;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .mobile-info-item {
        padding: 0.25rem 0;
    }
    
    .mobile-info-item small {
        font-size: 0.75rem;
    }
    
    /* 統計カードの調整 */
    .card-body .card-title {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .card-body h2 {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }
    
    .card-body .opacity-75 {
        font-size: 0.7rem;
    }
    
    /* モバイル統計の調整 */
    .mobile-stats-card {
        padding: 0.75rem !important;
    }
    
    .mobile-stats-card .fw-bold {
        font-size: 1.1rem;
    }
    
    .mobile-stats-card small {
        font-size: 0.7rem;
    }
    
    .mobile-stats-card .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}

/* 小型モバイル表示（576px未満） */
@media (max-width: 575px) {
    /* ヘッダーのさらなる調整 */
    .d-flex.justify-content-between.align-items-center.mb-4 h2 {
        font-size: 1.25rem;
    }
    
    .btn-group .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
    
    /* カードヘッダーの調整 */
    .card-header h5,
    .card-header h6 {
        font-size: 0.95rem;
    }
    
    /* モバイル契約カードの詳細調整 */
    .mobile-contract-card .card-body {
        padding: 0.75rem;
    }
    
    .mobile-contract-card .card-title {
        font-size: 0.9rem;
    }
    
    .mobile-contract-card .card-subtitle {
        font-size: 0.8rem;
    }
    
    .mobile-contract-card .btn-lg {
        font-size: 0.9rem;
        padding: 0.6rem 0.8rem;
    }
    
    /* グリッドの調整 */
    .row.g-3 .col-6 {
        margin-bottom: 0.75rem;
    }
    
    /* 統計カードのさらなる調整 */
    .text-center h4 {
        font-size: 1.1rem;
    }
    
    .text-center small {
        font-size: 0.65rem;
    }
    
    /* モバイル統計カードの調整 */
    .mobile-stats-card {
        padding: 0.5rem !important;
    }
    
    .mobile-stats-card .fw-bold {
        font-size: 1rem;
    }
    
    .mobile-stats-card small {
        font-size: 0.65rem;
    }
    
    .mobile-stats-card .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }
    
    /* ページネーションの最適化 */
    .pagination .page-link {
        padding: 0.25rem 0.4rem;
        font-size: 0.7rem;
        min-width: 28px;
    }
}

/* 極小モバイル表示（400px未満） */
@media (max-width: 399px) {
    /* 最小限の表示に調整 */
    .btn-group {
        flex-direction: column;
    }
    
    .mobile-contract-card .card-body {
        padding: 0.5rem;
    }
    
    .mobile-contract-card .card-title {
        font-size: 0.85rem;
    }
    
    .mobile-contract-card .btn-lg {
        font-size: 0.85rem;
        padding: 0.5rem;
    }
    
    .mobile-stats-card .fw-bold {
        font-size: 0.95rem;
    }
    
    .text-center h4 {
        font-size: 1rem;
    }
    
    /* ページネーション最小サイズ */
    .pagination .page-link {
        padding: 0.2rem 0.3rem;
        font-size: 0.65rem;
        min-width: 24px;
    }
}

/* ========== モーダルスタイル ========== */

/* 拠点情報リンクのスタイル */
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

/* モーダルのスタイル */
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

/* アクションボタンのスタイル */
#branchInfoModal .btn-group .btn {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    margin-right: 0.25rem;
    transition: all 0.15s ease-in-out;
}

#branchInfoModal .btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* モーダルの背景 */
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

/* カードのアニメーション */
#branchInfoModal .card {
    transform: translateY(10px);
    opacity: 0;
    animation: slideInUp 0.3s ease forwards;
}

#branchInfoModal .card:nth-child(2) {
    animation-delay: 0.1s;
}

@keyframes slideInUp {
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* プログレスバーのアニメーション */
.progress-bar {
    transition: width 0.6s ease;
}

/* テーブルのホバーエフェクト */
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.02);
}

.table-danger:hover {
    background-color: rgba(220, 53, 69, 0.15) !important;
}

.table-warning:hover {
    background-color: rgba(255, 193, 7, 0.15) !important;
}

.table-secondary:hover {
    background-color: rgba(108, 117, 125, 0.15) !important;
}

.table-info:hover {
    background-color: rgba(13, 202, 240, 0.15) !important;
}

/* アイコンとテキストの間隔調整 */
.fas.me-1 {
    margin-right: 0.5rem !important;
}

.fas.me-2 {
    margin-right: 0.75rem !important;
}

/* ボタンのスタイル統一 */
.btn {
    transition: all 0.15s ease-in-out;
}

.btn-success:hover:not([style*="pointer-events: none"]) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* バッジのスタイル調整 */
.badge.me-1 {
    margin-right: 0.5rem !important;
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

/* 改行制御 */
.table td {
    word-wrap: break-word;
    word-break: break-word;
}

/* フォントサイズの段階的調整 */
@media (max-width: 992px) {
    .table {
        font-size: 0.9rem;
    }
}

@media (max-width: 768px) {
    .table {
        font-size: 0.85rem;
    }
}

@media (max-width: 576px) {
    .table {
        font-size: 0.8rem;
    }
}

@media (max-width: 400px) {
    .table {
        font-size: 0.75rem;
    }
}

/* 役務開始ボタンの特別なスタイル（常に見やすく） */
.mobile-contract-card .btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.mobile-contract-card .btn-success:hover:not([style*="pointer-events: none"]) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
}

/* 契約状態別カードスタイル（モバイル表示用） */
.mobile-contract-card.border-danger {
    border-width: 2px !important;
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.mobile-contract-card.border-warning {
    border-width: 2px !important;
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.mobile-contract-card.border-secondary {
    border-width: 2px !important;
    background-color: rgba(108, 117, 125, 0.1) !important;
}

/* Bootstrap 5.3以降のbg-warning-subtleに対応 */
.mobile-contract-card.bg-warning-subtle {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

/* モバイルでのアクセシビリティ向上 */
@media (max-width: 767px) {
    /* タッチターゲットのサイズを44px以上に */
    .mobile-contract-card .btn,
    .mobile-service-card .badge,
    .branch-info-link {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .branch-info-link {
        padding: 0.5rem;
        border-radius: 0.25rem;
        margin: 0.25rem 0;
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

/* 月締め済み契約のスタイル */
.btn-outline-secondary:disabled {
    background-color: #f8f9fa;
    border-color: #6c757d;
    color: #6c757d;
    opacity: 0.8;
}

.table .btn-outline-secondary:disabled {
    cursor: not-allowed;
}

.mobile-contract-card .btn-outline-secondary:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

<script>
// パフォーマンス測定開始マーク
if (window.performance && window.performance.mark) {
    window.performance.mark('doctor-dashboard-script-start');
}

// Bootstrap Modal イベントを活用した拠点詳細情報表示
document.addEventListener('DOMContentLoaded', function() {

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
        
        // デバッグ情報を更新（開発環境のみ）
        updateDebugInfo(modalData, triggerElement);
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

    // デバッグ情報更新関数
    function updateDebugInfo(data, triggerElement) {
        const debugInfo = document.getElementById('debugInfo');
        if (debugInfo) {
            debugInfo.innerHTML = `
                <strong>最後にクリックされたリンクのデータ:</strong><br>
                拠点名: ${data.branchName}<br>
                住所: ${data.branchAddress}<br>
                電話: ${data.branchPhone}<br>
                メール: ${data.branchEmail}<br>
                企業名: ${data.companyName}<br>
                企業住所: ${data.companyAddress}
            `;
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

    // ================== ページネーション機能 ==================
    
    // ページネーション初期化
    initializePagination();
    
    function initializePagination() {
        // 契約一覧のページネーション
        initializeContractsPagination();
        
        // 役務記録のページネーション
        initializeServiceRecordsPagination();
    }
    
    // 契約一覧のページネーション初期化
    function initializeContractsPagination() {
        // ページサイズ選択の変更イベント
        const contractsPageSizeSelect = document.getElementById('contracts_page_size');
        if (contractsPageSizeSelect) {
            contractsPageSizeSelect.addEventListener('change', function() {
                const newPageSize = this.value;
                reloadPage({
                    contracts_page: 1, // ページサイズ変更時は1ページ目に戻る
                    contracts_page_size: newPageSize
                });
            });
        }
        
        // ページネーションリンクのクリックイベント
        document.addEventListener('click', function(e) {
            if (e.target.closest('.contracts-pagination-link')) {
                e.preventDefault();
                const link = e.target.closest('.contracts-pagination-link');
                const page = link.getAttribute('data-page');
                const pageSize = link.getAttribute('data-page-size');
                
                if (page && pageSize && !link.parentNode.classList.contains('disabled')) {
                    reloadPage({
                        contracts_page: page,
                        contracts_page_size: pageSize
                    });
                }
            }

            if (e.target.closest('.btn-outline-secondary:disabled')) {
                e.preventDefault();
                
                const button = e.target.closest('button');
                if (button && button.textContent.includes('月締め済み')) {
                    showMonthClosedWarning();
                }
            }
        });

        // 月締め警告表示
        function showMonthClosedWarning() {
            const alertMessage = `
                <div class="alert alert-info alert-dismissible fade show month-closed-alert" role="alert">
                    <i class="fas fa-lock me-2"></i>
                    <strong>今月は締め処理が完了しています。</strong><br>
                    新規役務の追加や既存役務の編集はできません。
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // 既存のアラートがあれば削除
            const existingAlert = document.querySelector('.month-closed-alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // 新しいアラートを追加
            const alertDiv = document.createElement('div');
            alertDiv.innerHTML = alertMessage;
            
            const mainContent = document.querySelector('.container-fluid') || document.body;
            if (mainContent) {
                mainContent.insertBefore(alertDiv, mainContent.firstChild);
            }
            
            // 5秒後に自動削除
            setTimeout(() => {
                const alert = document.querySelector('.month-closed-alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    }
    
    // 役務記録のページネーション初期化
    function initializeServiceRecordsPagination() {
        // ページサイズ選択の変更イベント
        const serviceRecordsPageSizeSelect = document.getElementById('service_records_page_size');
        if (serviceRecordsPageSizeSelect) {
            serviceRecordsPageSizeSelect.addEventListener('change', function() {
                const newPageSize = this.value;
                reloadPage({
                    service_records_page: 1, // ページサイズ変更時は1ページ目に戻る
                    service_records_page_size: newPageSize
                });
            });
        }
        
        // ページネーションリンクのクリックイベント
        document.addEventListener('click', function(e) {
            if (e.target.closest('.service-records-pagination-link')) {
                e.preventDefault();
                const link = e.target.closest('.service-records-pagination-link');
                const page = link.getAttribute('data-page');
                const pageSize = link.getAttribute('data-page-size');
                
                if (page && pageSize && !link.parentNode.classList.contains('disabled')) {
                    reloadPage({
                        service_records_page: page,
                        service_records_page_size: pageSize
                    });
                }
            }
        });
    }
    
    // ページリロード関数
    function reloadPage(params) {
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
        const serviceRecordsCard = document.querySelector('.card:has(.service-records-pagination-link)');
        
        if (contractsCard) {
            contractsCard.classList.add('pagination-loading');
        }
        
        if (serviceRecordsCard) {
            serviceRecordsCard.classList.add('pagination-loading');
        }
    }

    // 契約状態別のツールチップ強化（修正版）
    const contractRows = document.querySelectorAll('tbody tr');
    contractRows.forEach(row => {
        const statusBadge = row.querySelector('.badge');
        const operationButton = row.querySelector('.btn-success, .btn-outline-danger, .btn-outline-secondary');
        
        if (statusBadge && operationButton) {
            // 契約状態に応じたツールチップメッセージ
            let tooltipMessage = '';
            
            if (row.classList.contains('table-danger')) {
                if (statusBadge.textContent.includes('契約無効')) {
                    tooltipMessage = '契約ステータスが無効のため、すべての役務が利用できません。';
                } else if (statusBadge.textContent.includes('期間終了')) {
                    tooltipMessage = '契約期間が終了しているため、すべての役務が利用できません。';
                }
            } else if (row.classList.contains('table-warning')) {
                tooltipMessage = '契約開始日前のため、まだ役務を開始できません。';
            } else if (row.classList.contains('table-info')) {
                // 新しい非訪問月制限の場合
                tooltipMessage = '隔月契約の非訪問月です。';
            }
            
            if (tooltipMessage) {
                operationButton.setAttribute('title', tooltipMessage);
            }
        }
    });

    // 無効な契約のボタンクリック時の処理（修正版）
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-outline-danger:disabled') || e.target.closest('.btn-outline-secondary:disabled')) {
            e.preventDefault();
            
            const button = e.target.closest('button');
            const row = button.closest('tr');
            let companyName = '';
            
            // デスクトップ表示の場合
            if (row) {
                const companyElement = row.querySelector('td:first-child strong');
                if (companyElement) {
                    companyName = companyElement.textContent;
                }
            } else {
                // モバイル表示の場合
                const card = button.closest('.mobile-contract-card');
                if (card) {
                    const titleElement = card.querySelector('.card-title');
                    if (titleElement) {
                        companyName = titleElement.textContent.replace(/^\s*[^\s]+\s*/, ''); // アイコンを除去
                    }
                }
            }
            
            let message = '';
            if (button.textContent.includes('契約無効')) {
                message = `${companyName} の契約は無効状態です。管理者にお問い合わせください。`;
            } else if (button.textContent.includes('期間終了')) {
                message = `${companyName} の契約期間が終了しています。契約更新が必要です。`;
            } else if (button.textContent.includes('開始前')) {
                message = `${companyName} の契約開始日がまだ到来していません。`;
            }
            
            if (message) {
                showContractStatusWarning(message);
            }
        }
    });

    // 契約状態警告表示
    function showContractStatusWarning(message) {
        const alertMessage = `
            <div class="alert alert-warning alert-dismissible fade show contract-status-alert" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>${message}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // 既存のアラートがあれば削除
        const existingAlert = document.querySelector('.contract-status-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // 新しいアラートを追加
        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = alertMessage;
        
        const mainContent = document.querySelector('.container-fluid') || document.body;
        if (mainContent) {
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
        }
        
        // 5秒後に自動削除
        setTimeout(() => {
            const alert = document.querySelector('.contract-status-alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }

    // データ属性チェック機能（デバッグ用）
    function checkDataAttributes() {
        const links = document.querySelectorAll('.branch-info-link');
        
        links.forEach((link, index) => {
            const data = {
                branchName: link.getAttribute('data-branch-name'),
                branchAddress: link.getAttribute('data-branch-address'),
                branchPhone: link.getAttribute('data-branch-phone'),
                branchEmail: link.getAttribute('data-branch-email'),
                companyName: link.getAttribute('data-company-name'),
                companyAddress: link.getAttribute('data-company-address')
            };
            
            // 空の属性をチェック
            const emptyAttrs = Object.keys(data).filter(key => !data[key] || data[key].trim() === '');
            if (emptyAttrs.length > 0) {
                console.warn(`Link ${index + 1} has empty attributes:`, emptyAttrs);
            }
        });
    }

    // グローバル関数として公開（デバッグ用）
    window.branchModalDebug = {
        checkDataAttributes,
        updateModalContent,
        updateActionButtons,
        showAlert
    };

    // 初期チェック実行
    setTimeout(checkDataAttributes, 1000);

    // スマホでのタッチ操作向上
    if ('ontouchstart' in window) {
        // タッチデバイスでのホバー効果を調整
        const hoverElements = document.querySelectorAll('.card-hover, .mobile-contract-card, .mobile-service-card');
        hoverElements.forEach(element => {
            element.addEventListener('touchstart', function() {
                this.classList.add('touch-active');
            });
            
            element.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.classList.remove('touch-active');
                }, 300);
            });
        });
    }

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
        const statsCards = document.querySelectorAll('.mobile-stats-card');
        if (screenWidth < 576) {
            statsCards.forEach(card => {
                card.classList.add('small-mobile');
            });
        } else {
            statsCards.forEach(card => {
                card.classList.remove('small-mobile');
            });
        }
    }

    // ウィンドウリサイズ時の調整
    window.addEventListener('resize', adjustResponsiveElements);
    
    // 初期実行
    adjustResponsiveElements();

    // スクロール位置の記憶（モバイルでの操作性向上）
    let lastScrollPosition = 0;
    window.addEventListener('scroll', function() {
        const currentScrollPosition = window.pageYOffset;
        
        // 下にスクロールしている場合、ヘッダーを少し小さくする（オプション）
        const header = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-4');
        if (header && window.innerWidth < 768) {
            if (currentScrollPosition > lastScrollPosition && currentScrollPosition > 100) {
                header.style.transform = 'scale(0.95)';
                header.style.transformOrigin = 'top center';
                header.style.transition = 'transform 0.3s ease';
            } else {
                header.style.transform = 'scale(1)';
            }
        }
        
        lastScrollPosition = currentScrollPosition;
    });

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
            .mobile-contract-card:focus-within,
            .mobile-service-card:focus-within,
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

    // ページネーション関連のユーティリティ関数
    function getCurrentPageParams() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            contracts_page: urlParams.get('contracts_page') || 1,
            contracts_page_size: urlParams.get('contracts_page_size') || 10,
            service_records_page: urlParams.get('service_records_page') || 1,
            service_records_page_size: urlParams.get('service_records_page_size') || 15
        };
    }

    // ページネーション状態をローカルストレージに保存（オプション）
    function savePaginationState() {
        const params = getCurrentPageParams();
        try {
            localStorage.setItem('doctor_dashboard_pagination', JSON.stringify(params));
        } catch (e) {
            // ローカルストレージが使用できない場合は無視
            console.warn('Unable to save pagination state:', e);
        }
    }

    // ページネーション状態をローカルストレージから復元（オプション）
    function loadPaginationState() {
        try {
            const saved = localStorage.getItem('doctor_dashboard_pagination');
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) {
            console.warn('Unable to load pagination state:', e);
        }
        return null;
    }

    // エラーハンドリング
    window.addEventListener('error', function(e) {
        console.error('JavaScript error in doctor dashboard:', e);
    });

    // パフォーマンス監視（開発用）
    if (window.performance && window.performance.mark) {
        window.performance.mark('doctor-dashboard-script-end');
        window.performance.measure('doctor-dashboard-script-duration', 'doctor-dashboard-script-start', 'doctor-dashboard-script-end');
    }

});

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
        const response = await fetch(`<?= base_url('api/contracts/') ?>${contractId}/non-visit-days?scope=current_month`, {
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

// 差戻し理由モーダルの初期化
document.addEventListener('DOMContentLoaded', function() {
    const rejectionModal = document.getElementById('rejectionReasonModal');
    if (rejectionModal) {
        rejectionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const companyComment = button.getAttribute('data-company-comment');
            const companyName = button.getAttribute('data-company-name');
            const serviceDate = button.getAttribute('data-service-date');
            
            // モーダル内の要素に値を設定
            document.getElementById('rejectionCompanyName').textContent = companyName;
            document.getElementById('rejectionServiceDate').textContent = serviceDate;
            
            const reasonText = document.getElementById('rejectionReasonText');
            if (companyComment && companyComment.trim() !== '') {
                reasonText.textContent = companyComment;
            } else {
                reasonText.textContent = '差戻し理由が記録されていません。';
                document.getElementById('rejectionReasonContent').classList.add('alert-secondary');
                document.getElementById('rejectionReasonContent').classList.remove('alert-warning');
            }
        });
        
        // モーダルが閉じられた時にスタイルをリセット
        rejectionModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('rejectionReasonContent').classList.remove('alert-secondary');
            document.getElementById('rejectionReasonContent').classList.add('alert-warning');
        });
    }
});
</script>