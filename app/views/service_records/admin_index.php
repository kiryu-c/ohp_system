<?php
// app/views/service_records/admin_index.php - 訪問頻度対応版（ページネーション対応）
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-clipboard-list me-2"></i>役務記録管理</h2>
    <div class="btn-group">
        <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
        </a>
    </div>
</div>

<!-- 検索・フィルター -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('service_records/admin') ?>" class="row g-3" id="filterForm">
            <div class="col-md-2">
                <label for="year" class="form-label">年</label>
                <select class="form-select" id="year" name="year">
                    <?php for($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                        <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>><?= $i ?>年</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="month" class="form-label">月</label>
                <select class="form-select" id="month" name="month">
                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>><?= $i ?>月</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="approval_status" class="form-label">承認状況</label>
                <select class="form-select" id="approval_status" name="approval_status">
                    <option value="">すべて</option>
                    <option value="finalized" <?= $approval_status == 'finalized' ? 'selected' : '' ?>>締済み</option>
                    <option value="pending" <?= $approval_status == 'pending' ? 'selected' : '' ?>>承認待ち</option>
                    <option value="approved" <?= $approval_status == 'approved' ? 'selected' : '' ?>>承認済み</option>
                    <option value="rejected" <?= $approval_status == 'rejected' ? 'selected' : '' ?>>差戻し</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">検索</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" 
                       placeholder="産業医名/企業名/拠点名で検索">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-search me-1"></i>検索
                </button>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <a href="<?= base_url('service_records/admin') ?>" class="btn btn-outline-secondary d-block w-100">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- モバイル用並び替え (1200px未満) -->
<div class="mobile-sort-section d-xl-none mb-3">
    <label for="mobile_sort" class="form-label">並び替え</label>
    <select class="form-select" id="mobile_sort">
        <option value="doctor_name-asc" <?= ($sortColumn ?? 'doctor_name') === 'doctor_name' && ($sortOrder ?? 'asc') === 'asc' ? 'selected' : '' ?>>
            産業医名(昇順)
        </option>
        <option value="doctor_name-desc" <?= ($sortColumn ?? '') === 'doctor_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            産業医名(降順)
        </option>
        <option value="company_name-asc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            企業・拠点名(昇順)
        </option>
        <option value="company_name-desc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            企業・拠点名(降順)
        </option>
        <option value="usage_percentage-desc" <?= ($sortColumn ?? '') === 'usage_percentage' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            使用率(高い順)
        </option>
        <option value="usage_percentage-asc" <?= ($sortColumn ?? '') === 'usage_percentage' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            使用率(低い順)
        </option>
        <option value="total_hours-desc" <?= ($sortColumn ?? '') === 'total_hours' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            実績時間(多い順)
        </option>
        <option value="total_hours-asc" <?= ($sortColumn ?? '') === 'total_hours' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            実績時間(少ない順)
        </option>
        <option value="visit_frequency-asc" <?= ($sortColumn ?? '') === 'visit_frequency' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            訪問頻度(昇順)
        </option>
        <option value="visit_frequency-desc" <?= ($sortColumn ?? '') === 'visit_frequency' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            訪問頻度(降順)
        </option>
    </select>
</div>

<!-- サマリー一覧 -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= $year ?>年<?= $month ?>月の役務記録サマリー
                <span class="badge bg-primary"><?= $pagination['total_records'] ?>件</span>
            </h5>
            <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                <!-- ページサイズ選択 -->
                <div class="d-flex align-items-center">
                    <label for="admin_records_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                    <select id="admin_records_page_size" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" <?= ($pagination['per_page'] == 10) ? 'selected' : '' ?>>10件</option>
                        <option value="20" <?= ($pagination['per_page'] == 20) ? 'selected' : '' ?>>20件</option>
                        <option value="50" <?= ($pagination['per_page'] == 50) ? 'selected' : '' ?>>50件</option>
                        <option value="100" <?= ($pagination['per_page'] == 100) ? 'selected' : '' ?>>100件</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($summaries)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">指定期間の役務記録がありません。</p>
                <?php if (!empty($search) || !empty($approval_status)): ?>
                    <p class="text-muted">検索条件やフィルターを変更してお試しください。</p>
                    <a href="<?= base_url('service_records/admin') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-redo me-1"></i>条件をリセット
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive d-none d-xl-block">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="#" class="sort-link" data-sort="doctor_name" title="産業医名で並び替え">
                                    産業医
                                    <?php if (($sortColumn ?? 'doctor_name') === 'doctor_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="company_name" title="企業・拠点名で並び替え">
                                    企業・拠点
                                    <?php if (($sortColumn ?? '') === 'company_name'): ?>
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
                            <th class="text-center">契約時間</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="total_hours" title="実績時間で並び替え">
                                    時間業務実績
                                    <?php if (($sortColumn ?? '') === 'total_hours'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">実施方法別実績</th>
                            <th class="text-center">非時間業務実績</th>
                            <th class="text-center">承認状況</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="usage_percentage" title="使用率で並び替え">
                                    使用率
                                    <?php if (($sortColumn ?? '') === 'usage_percentage'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">記録件数</th>
                            <th class="text-center">交通費</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                        <?php foreach ($summaries as $summary): ?>
                            <?php 
                            // 使用率による色分け
                            $usageClass = 'text-success';
                            if ($summary['usage_percentage'] >= 100) {
                                $usageClass = 'text-danger';
                            } elseif ($summary['usage_percentage'] >= 80) {
                                $usageClass = 'text-warning';
                            }
                            
                            // 各役務種別の件数を取得
                            $documentCount = $summary['document_count'] ?? 0;
                            $remoteCount = $summary['remote_consultation_count'] ?? 0;
                            $regularCount = $summary['regular_count'] ?? 0;
                            $emergencyCount = $summary['emergency_count'] ?? 0;
                            $spotCount = $summary['spot_count'] ?? 0;
                            $otherCount = $summary['other_count'] ?? 0;
                            
                            // 訪問頻度を取得
                            $visitFrequency = $summary['visit_frequency'] ?? 'monthly';
                            $frequencyInfo = get_visit_frequency_info($visitFrequency);
                            $isVisitMonth = is_visit_month($summary, $year, $month);
                            
                            // 週間スケジュールの取得
                            $weeklySchedule = '';
                            if ($visitFrequency === 'weekly') {
                                require_once __DIR__ . '/../../core/Database.php';
                                $db = Database::getInstance()->getConnection();
                                $sql = "SELECT day_of_week FROM contract_weekly_schedules WHERE contract_id = :contract_id ORDER BY day_of_week";
                                $stmt = $db->prepare($sql);
                                $stmt->execute(['contract_id' => $summary['contract_id']]);
                                $weeklyDays = array_column($stmt->fetchAll(), 'day_of_week');
                                
                                if (!empty($weeklyDays)) {
                                    $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                                    $weeklySchedule = implode('・', array_map(function($day) use ($dayNames) {
                                        return $dayNames[$day] ?? '';
                                    }, $weeklyDays));
                                }
                            }
                            
                            // 実施方法別の統計を取得
                            $visitTypeDetailSql = "SELECT 
                                                    COUNT(CASE WHEN visit_type = 'visit' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN 1 END) as visit_count,
                                                    COUNT(CASE WHEN visit_type = 'online' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN 1 END) as online_count,
                                                    SUM(CASE WHEN visit_type = 'visit' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as visit_hours,
                                                    SUM(CASE WHEN visit_type = 'online' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as online_hours
                                                FROM service_records sr
                                                WHERE sr.contract_id = :contract_id 
                                                AND YEAR(sr.service_date) = :year 
                                                AND MONTH(sr.service_date) = :month";
                            
                            $stmt = $db->prepare($visitTypeDetailSql);
                            $stmt->execute([
                                'contract_id' => $summary['contract_id'],
                                'year' => $year,
                                'month' => $month
                            ]);
                            $visitTypeDetail = $stmt->fetch() ?: [
                                'visit_count' => 0, 'online_count' => 0, 'visit_hours' => 0, 'online_hours' => 0
                            ];
                            ?>
                            <tr>
                                <td>
                                    <i class="fas fa-user-md me-1 text-info"></i>
                                    <strong><?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-building me-1 text-primary"></i>
                                        <strong><?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $frequencyInfo['class'] ?>">
                                        <i class="<?= $frequencyInfo['icon'] ?>"></i>
                                        <?= $frequencyInfo['label'] ?>
                                    </span>
                                    <?php if ($visitFrequency === 'bimonthly'): ?>
                                        <br>
                                        <small class="text-<?= $isVisitMonth ? 'success' : 'muted' ?>">
                                            <?= $isVisitMonth ? '今月訪問' : '今月非訪問' ?>
                                        </small>
                                    <?php elseif ($visitFrequency === 'weekly' && $weeklySchedule): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= $weeklySchedule ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($visitFrequency === 'weekly'): ?>
                                        <!-- 毎週契約の場合：月間計算時間を表示 -->
                                        <span class="badge bg-success"><?= format_total_hours($summary['display_contract_hours']) ?></span>
                                        <br><small class="text-muted">月間計算時間</small>
                                        <br><small class="text-info"><?= format_total_hours($summary['weekly_base_hours']) ?>×<?= floor($summary['display_contract_hours'] / $summary['weekly_base_hours']) ?>回</small>
                                    <?php elseif ($visitFrequency === 'bimonthly'): ?>
                                        <!-- 隔月契約の場合 -->
                                        <span class="badge bg-info"><?= format_total_hours($summary['display_contract_hours']) ?></span>
                                        <br><small class="text-muted">隔月契約</small>
                                        <br><small class="<?= $isVisitMonth ? 'text-success' : 'text-warning' ?>">
                                            <?= $isVisitMonth ? '訪問月' : '非訪問月' ?>
                                        </small>
                                    <?php elseif ($visitFrequency === 'spot'): ?>
                                        -
                                    <?php else: ?>
                                        <!-- 毎月契約の場合（従来通り） -->
                                        <span class="badge bg-info"><?= format_total_hours($summary['display_contract_hours']) ?></span>
                                        <br><small class="text-muted">月間契約</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="mb-1">
                                        <strong><?= format_total_hours($summary['total_hours']) ?></strong>
                                    </div>
                                    <div class="small">
                                        <?php if ($regularCount > 0): ?>
                                            <span class="badge bg-success me-1" title="定期訪問">定期: <?= $regularCount ?></span>
                                        <?php endif; ?>
                                        <?php if ($emergencyCount > 0): ?>
                                            <span class="badge bg-warning me-1" title="臨時訪問">臨時: <?= $emergencyCount ?></span>
                                        <?php endif; ?>
                                        <?php if ($spotCount > 0): ?>
                                            <span class="badge bg-info me-1" title="スポット">スポット: <?= $spotCount ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <!-- 実施方法別実績列 -->
                                <td class="text-center">
                                    <?php if ($visitTypeDetail['visit_count'] > 0 || $visitTypeDetail['online_count'] > 0): ?>
                                        <div class="small">
                                            <?php if ($visitTypeDetail['visit_count'] > 0): ?>
                                                <span class="badge bg-primary me-1" title="訪問実施">
                                                    <i class="fas fa-walking me-1"></i>訪問: <?= $visitTypeDetail['visit_count'] ?>件
                                                </span>
                                                <div class="text-muted small"><?= format_total_hours($visitTypeDetail['visit_hours']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($visitTypeDetail['online_count'] > 0): ?>
                                                <span class="badge bg-info me-1" title="オンライン実施">
                                                    <i class="fas fa-laptop me-1"></i>オンライン: <?= $visitTypeDetail['online_count'] ?>件
                                                </span>
                                                <div class="text-muted small"><?= format_total_hours($visitTypeDetail['online_hours']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <?php if ($visitTypeDetail['visit_count'] > 0): ?>
                                                    <i class="fas fa-train text-success me-1"></i>交通費対象: <?= $visitTypeDetail['visit_count'] ?>件
                                                <?php else: ?>
                                                    <i class="fas fa-wifi text-info me-1"></i>全てオンライン
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($documentCount > 0 || $remoteCount > 0 || $otherCount > 0): ?>
                                        <div class="small">
                                            <?php if ($documentCount > 0): ?>
                                                <span class="badge bg-primary me-1" title="書面作成">
                                                    <i class="fas fa-file-alt me-1"></i>書面: <?= $documentCount ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($remoteCount > 0): ?>
                                                <span class="badge bg-secondary me-1" title="遠隔相談">
                                                    <i class="fas fa-envelope me-1"></i>遠隔: <?= $remoteCount ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($otherCount > 0): ?>
                                                <span class="badge bg-dark me-1" title="その他">
                                                    <i class="fas fa-plus me-1"></i>その他: <?= $otherCount ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">件数のみ記録</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (isset($summary['closing_status']) && $summary['closing_status'] === 'finalized'): ?>
                                        <!-- 締め処理済みの場合 -->
                                        <div class="mb-1">
                                            <span class="badge bg-dark">
                                                <i class="fas fa-lock me-1"></i>締め済み
                                            </span>
                                        </div>
                                        <div class="small text-muted">
                                            <?php if (!empty($summary['closing_finalized_at'])): ?>
                                                <?= date('Y/m/d', strtotime($summary['closing_finalized_at'])) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-1">
                                            <button type="button" 
                                                    class="btn btn-outline-info btn-sm" 
                                                    onclick="showClosingDetailFromAdmin(<?= $summary['contract_id'] ?>, '<?= sprintf('%04d-%02d', $year, $month) ?>', '<?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>')"
                                                    title="締め処理詳細">
                                                <i class="fas fa-eye me-1"></i>締め詳細
                                            </button>
                                        </div>
                                        <!-- 承認状況も併せて表示 -->
                                        <?php if ($summary['approved_hours'] > 0 || $summary['pending_hours'] > 0 || $summary['rejected_hours'] > 0): ?>
                                            <div class="small mt-1">
                                                <?php if ($summary['approved_hours'] > 0): ?>
                                                    <small class="text-muted">承認済:</small><span class="badge bg-success"><?= format_total_hours($summary['approved_hours']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($summary['pending_hours'] > 0): ?>
                                                    <small class="text-muted">承認待ち:</small><span class="badge bg-warning"><?= format_total_hours($summary['pending_hours']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($summary['rejected_hours'] > 0): ?>
                                                    <small class="text-muted">差戻し:</small><span class="badge bg-danger"><?= format_total_hours($summary['rejected_hours']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="small">
                                            <?php if ($summary['approved_hours'] > 0): ?>
                                                <small class="text-muted">承認済:</small><span class="badge bg-success"><?= format_total_hours($summary['approved_hours']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($summary['pending_hours'] > 0): ?>
                                                <small class="text-muted">承認待ち:</small><span class="badge bg-warning"><?= format_total_hours($summary['pending_hours']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($summary['rejected_hours'] > 0): ?>
                                                <small class="text-muted">差戻し:</small><span class="badge bg-danger"><?= format_total_hours($summary['rejected_hours']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="<?= $usageClass ?>">
                                        <strong><?= round($summary['usage_percentage'], 1) ?>%</strong>
                                    </span>
                                    <?php if ($summary['remaining_hours'] < 0): ?>
                                        <br><small class="text-danger">延長 <?= format_total_hours(abs($summary['remaining_hours'])) ?></small>
                                    <?php else: ?>
                                        <br><small class="text-muted">残り <?= format_total_hours($summary['remaining_hours']) ?></small>
                                    <?php endif; ?>
                                    <?php if ($visitFrequency === 'weekly'): ?>
                                        <br><small class="text-success"><i class="fas fa-calendar-week me-1"></i>週間ベース</small>
                                    <?php elseif ($visitFrequency === 'bimonthly'): ?>
                                        <br><small class="text-info"><i class="fas fa-calendar-alt me-1"></i>隔月ベース</small>
                                    <?php else: ?>
                                        <br><small class="text-muted">定期訪問ベース</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $summary['record_count'] ?></span>
                                    <br><small class="text-muted">全種別</small>
                                </td>
                                <td class="text-center">
                                    <?php
                                    // 交通費情報を取得
                                    $travelExpensesSql = "SELECT te.id, te.status, te.amount, sr.service_date, sr.service_type
                                                        FROM travel_expenses te
                                                        JOIN service_records sr ON te.service_record_id = sr.id
                                                        WHERE sr.contract_id = :contract_id 
                                                        AND YEAR(sr.service_date) = :year 
                                                        AND MONTH(sr.service_date) = :month
                                                        ORDER BY sr.service_date DESC, te.created_at DESC";
                                    
                                    $stmt = $db->prepare($travelExpensesSql);
                                    $stmt->execute([
                                        'contract_id' => $summary['contract_id'],
                                        'year' => $year,
                                        'month' => $month
                                    ]);
                                    $travelExpenses = $stmt->fetchAll();
                                    
                                    if (!empty($travelExpenses)):
                                        $pendingCount = count(array_filter($travelExpenses, fn($te) => $te['status'] === 'pending'));
                                        $approvedCount = count(array_filter($travelExpenses, fn($te) => $te['status'] === 'approved'));
                                        $rejectedCount = count(array_filter($travelExpenses, fn($te) => $te['status'] === 'rejected'));
                                        $totalAmount = array_sum(array_column($travelExpenses, 'amount'));
                                    ?>
                                        <button type="button" 
                                                class="btn btn-outline-info btn-sm" 
                                                onclick="showTravelExpenseModal(<?= $summary['contract_id'] ?>, '<?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>', <?= $year ?>, <?= $month ?>)"
                                                title="交通費詳細">
                                            <i class="fas fa-car me-1"></i>
                                            <?= count($travelExpenses) ?>件
                                        </button>
                                        <div class="small mt-1">
                                            <?php if ($pendingCount > 0): ?>
                                                <span class="badge bg-warning"><?= $pendingCount ?>件待機</span>
                                            <?php endif; ?>
                                            <?php if ($approvedCount > 0): ?>
                                                <span class="badge bg-success"><?= $approvedCount ?>件承認</span>
                                            <?php endif; ?>
                                            <?php if ($rejectedCount > 0): ?>
                                                <span class="badge bg-danger"><?= $rejectedCount ?>件差戻</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted">
                                            合計: ¥<?= number_format($totalAmount) ?>
                                        </div>
                                        <!-- 訪問実施件数との比較 -->
                                        <?php if ($visitTypeDetail['visit_count'] > 0): ?>
                                            <div class="small text-info mt-1">
                                                <i class="fas fa-info-circle me-1"></i>
                                                訪問<?= $visitTypeDetail['visit_count'] ?>件中
                                                <?= count($travelExpenses) ?>件で交通費登録
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fas fa-minus"></i>
                                            <div class="small">なし</div>
                                        </span>
                                        <!-- 訪問実施があるのに交通費がない場合の警告 -->
                                        <?php if ($visitTypeDetail['visit_count'] > 0): ?>
                                            <div class="small text-warning mt-1">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                訪問<?= $visitTypeDetail['visit_count'] ?>件で交通費未登録
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url("service_records/admin/{$summary['contract_id']}/detail?year={$year}&month={$month}") ?>" 
                                           class="btn btn-outline-primary" title="詳細">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($summary['pending_hours'] > 0): ?>
                                            <a href="<?= base_url("service_records/admin/{$summary['contract_id']}/detail?year={$year}&month={$month}&status=pending") ?>" 
                                               class="btn btn-outline-warning" title="承認待ちのみ表示">
                                                <i class="fas fa-clock"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-outline-info" 
                                                onclick="showVisitTypeBreakdown(<?= $summary['contract_id'] ?>, '<?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>', <?= $year ?>, <?= $month ?>)"
                                                title="実施方法詳細">
                                            <i class="fas fa-chart-pie"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- モバイル・タブレット表示（1200px未満） -->
            <div class="d-xl-none">
                <?php foreach ($summaries as $summary): ?>
                    <?php 
                    // 既存の変数定義をコピー
                    $usageClass = 'text-success';
                    if ($summary['usage_percentage'] >= 100) {
                        $usageClass = 'text-danger';
                    } elseif ($summary['usage_percentage'] >= 80) {
                        $usageClass = 'text-warning';
                    }
                    
                    $documentCount = $summary['document_count'] ?? 0;
                    $remoteCount = $summary['remote_consultation_count'] ?? 0;
                    $regularCount = $summary['regular_count'] ?? 0;
                    $emergencyCount = $summary['emergency_count'] ?? 0;
                    $spotCount = $summary['spot_count'] ?? 0;
                    $otherCount = $summary['other_count'] ?? 0;
                    
                    $visitFrequency = $summary['visit_frequency'] ?? 'monthly';
                    $frequencyInfo = get_visit_frequency_info($visitFrequency);
                    $isVisitMonth = is_visit_month($summary, $year, $month);
                    
                    // 実施方法別統計を取得
                    $visitTypeDetailSql = "SELECT 
                                            COUNT(CASE WHEN visit_type = 'visit' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN 1 END) as visit_count,
                                            COUNT(CASE WHEN visit_type = 'online' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN 1 END) as online_count,
                                            SUM(CASE WHEN visit_type = 'visit' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as visit_hours,
                                            SUM(CASE WHEN visit_type = 'online' AND service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as online_hours
                                        FROM service_records sr
                                        WHERE sr.contract_id = :contract_id 
                                        AND YEAR(sr.service_date) = :year 
                                        AND MONTH(sr.service_date) = :month";
                    
                    $stmt = $db->prepare($visitTypeDetailSql);
                    $stmt->execute([
                        'contract_id' => $summary['contract_id'],
                        'year' => $year,
                        'month' => $month
                    ]);
                    $visitTypeDetail = $stmt->fetch() ?: [
                        'visit_count' => 0, 'online_count' => 0, 'visit_hours' => 0, 'online_hours' => 0
                    ];
                    
                    // 交通費情報を取得
                    $travelExpensesSql = "SELECT te.id, te.status, te.amount, sr.service_date, sr.service_type
                                        FROM travel_expenses te
                                        JOIN service_records sr ON te.service_record_id = sr.id
                                        WHERE sr.contract_id = :contract_id 
                                        AND YEAR(sr.service_date) = :year 
                                        AND MONTH(sr.service_date) = :month
                                        ORDER BY sr.service_date DESC, te.created_at DESC";
                    
                    $stmt = $db->prepare($travelExpensesSql);
                    $stmt->execute([
                        'contract_id' => $summary['contract_id'],
                        'year' => $year,
                        'month' => $month
                    ]);
                    $travelExpenses = $stmt->fetchAll();
                    
                    if (!empty($travelExpenses)):
                        $pendingCount = count(array_filter($travelExpenses, fn($te) => $te['status'] === 'pending'));
                        $approvedCount = count(array_filter($travelExpenses, fn($te) => $te['status'] === 'approved'));
                        $rejectedCount = count(array_filter($travelExpenses, fn($te) => $te['status'] === 'rejected'));
                        $totalAmount = array_sum(array_column($travelExpenses, 'amount'));
                    endif;
                    ?>
                    
                    <div class="card mobile-summary-card mb-3">
                        <div class="card-body">
                            <!-- ヘッダー: 産業医・企業情報 -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-user-md me-1 text-info"></i>
                                        <strong><?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </h6>
                                    <div class="card-subtitle text-muted mb-1">
                                        <i class="fas fa-building me-1 text-primary"></i>
                                        <?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="card-subtitle text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                                
                                <!-- 使用率バッジ -->
                                <div class="ms-2">
                                    <span class="badge bg-<?= $summary['usage_percentage'] >= 100 ? 'danger' : ($summary['usage_percentage'] >= 80 ? 'warning' : 'success') ?> fs-6">
                                        <?= round($summary['usage_percentage'], 1) ?>%
                                    </span>
                                </div>
                            </div>
                            
                            <!-- 訪問頻度情報 -->
                            <div class="mobile-info-section mb-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block">訪問頻度</small>
                                        <span class="badge bg-<?= $frequencyInfo['class'] ?>">
                                            <i class="<?= $frequencyInfo['icon'] ?>"></i>
                                            <?= $frequencyInfo['label'] ?>
                                        </span>
                                        <?php if ($visitFrequency === 'bimonthly'): ?>
                                            <div class="mt-1">
                                                <small class="text-<?= $isVisitMonth ? 'success' : 'muted' ?>">
                                                    <?= $isVisitMonth ? '今月訪問' : '今月非訪問' ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-6">
                                        <small class="text-muted d-block">契約時間</small>
                                        <strong><?= format_total_hours($summary['display_contract_hours']) ?></strong>
                                        <?php if ($visitFrequency === 'weekly'): ?>
                                            <div class="mt-1">
                                                <small class="text-success">
                                                    <i class="fas fa-calendar-week me-1"></i>週間ベース
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 実績情報 -->
                            <div class="mobile-info-section mb-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block">時間業務実績</small>
                                        <strong class="text-primary"><?= format_total_hours($summary['total_hours']) ?></strong>
                                        <div class="mt-1">
                                            <?php if ($regularCount > 0): ?>
                                                <span class="badge bg-success me-1" style="font-size: 0.65rem;">定期: <?= $regularCount ?></span>
                                            <?php endif; ?>
                                            <?php if ($emergencyCount > 0): ?>
                                                <span class="badge bg-warning me-1" style="font-size: 0.65rem;">臨時: <?= $emergencyCount ?></span>
                                            <?php endif; ?>
                                            <?php if ($spotCount > 0): ?>
                                                <span class="badge bg-info me-1" style="font-size: 0.65rem;">スポット: <?= $spotCount ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-6">
                                        <small class="text-muted d-block">残り時間</small>
                                        <?php if ($summary['remaining_hours'] < 0): ?>
                                            <strong class="text-danger">延長 <?= format_total_hours(abs($summary['remaining_hours'])) ?></strong>
                                        <?php else: ?>
                                            <strong class="text-success"><?= format_total_hours($summary['remaining_hours']) ?></strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 実施方法別実績 -->
                            <?php if ($visitTypeDetail['visit_count'] > 0 || $visitTypeDetail['online_count'] > 0): ?>
                                <div class="mobile-info-section mb-3">
                                    <small class="text-muted d-block mb-2">実施方法別実績</small>
                                    <div class="row g-2">
                                        <?php if ($visitTypeDetail['visit_count'] > 0): ?>
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <i class="fas fa-walking text-primary mb-1"></i>
                                                    <div class="fw-bold"><?= $visitTypeDetail['visit_count'] ?>件</div>
                                                    <small class="text-muted d-block">訪問実施</small>
                                                    <small class="text-muted"><?= format_total_hours($visitTypeDetail['visit_hours']) ?></small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($visitTypeDetail['online_count'] > 0): ?>
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <i class="fas fa-laptop text-info mb-1"></i>
                                                    <div class="fw-bold"><?= $visitTypeDetail['online_count'] ?>件</div>
                                                    <small class="text-muted d-block">オンライン</small>
                                                    <small class="text-muted"><?= format_total_hours($visitTypeDetail['online_hours']) ?></small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 非時間業務 -->
                            <?php if ($documentCount > 0 || $remoteCount > 0 || $otherCount > 0): ?>
                                <div class="mobile-info-section mb-3">
                                    <small class="text-muted d-block mb-2">非時間業務実績</small>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <?php if ($documentCount > 0): ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-file-alt me-1"></i>書面: <?= $documentCount ?>件
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($remoteCount > 0): ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-envelope me-1"></i>遠隔: <?= $remoteCount ?>件
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($otherCount > 0): ?>
                                            <span class="badge bg-dark">
                                                <i class="fas fa-plus me-1"></i>その他: <?= $otherCount ?>件
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 承認状況 -->
                            <div class="mobile-info-section mb-3">
                                <small class="text-muted d-block mb-2">承認状況</small>
                                <?php if (isset($summary['closing_status']) && $summary['closing_status'] === 'finalized'): ?>
                                    <div class="alert alert-dark mb-2 py-2">
                                        <i class="fas fa-lock me-1"></i>
                                        <strong>締め済み</strong>
                                        <?php if (!empty($summary['closing_finalized_at'])): ?>
                                            <div class="small"><?= date('Y/m/d', strtotime($summary['closing_finalized_at'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if ($summary['approved_hours'] > 0): ?>
                                        <div class="flex-fill border rounded p-2 text-center">
                                            <div class="badge bg-success mb-1">承認済み</div>
                                            <div class="small"><?= format_total_hours($summary['approved_hours']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($summary['pending_hours'] > 0): ?>
                                        <div class="flex-fill border rounded p-2 text-center">
                                            <div class="badge bg-warning mb-1">承認待ち</div>
                                            <div class="small"><?= format_total_hours($summary['pending_hours']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($summary['rejected_hours'] > 0): ?>
                                        <div class="flex-fill border rounded p-2 text-center">
                                            <div class="badge bg-danger mb-1">差戻し</div>
                                            <div class="small"><?= format_total_hours($summary['rejected_hours']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- 交通費情報 -->
                            <?php if (!empty($travelExpenses)): ?>
                                <div class="mobile-info-section mb-3">
                                    <small class="text-muted d-block mb-2">交通費</small>
                                    <div class="border rounded p-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong><?= count($travelExpenses) ?>件登録</strong>
                                            <strong>¥<?= number_format($totalAmount) ?></strong>
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php if ($pendingCount > 0): ?>
                                                <span class="badge bg-warning"><?= $pendingCount ?>件待機</span>
                                            <?php endif; ?>
                                            <?php if ($approvedCount > 0): ?>
                                                <span class="badge bg-success"><?= $approvedCount ?>件承認</span>
                                            <?php endif; ?>
                                            <?php if ($rejectedCount > 0): ?>
                                                <span class="badge bg-danger"><?= $rejectedCount ?>件差戻</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($visitTypeDetail['visit_count'] > 0): ?>
                                            <div class="small text-info mt-1">
                                                <i class="fas fa-info-circle me-1"></i>
                                                訪問<?= $visitTypeDetail['visit_count'] ?>件中<?= count($travelExpenses) ?>件で交通費登録
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php elseif ($visitTypeDetail['visit_count'] > 0): ?>
                                <div class="mobile-info-section mb-3">
                                    <small class="text-muted d-block mb-2">交通費</small>
                                    <div class="alert alert-warning mb-0 py-2">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        訪問<?= $visitTypeDetail['visit_count'] ?>件で交通費未登録
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 操作ボタン -->
                            <div class="d-grid gap-2">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <a href="<?= base_url("service_records/admin/{$summary['contract_id']}/detail?year={$year}&month={$month}") ?>" 
                                        class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-eye me-1"></i>詳細
                                        </a>
                                    </div>
                                    
                                    <?php if ($summary['pending_hours'] > 0): ?>
                                        <div class="col-6">
                                            <a href="<?= base_url("service_records/admin/{$summary['contract_id']}/detail?year={$year}&month={$month}&status=pending") ?>" 
                                            class="btn btn-outline-warning btn-sm w-100">
                                                <i class="fas fa-clock me-1"></i>承認待ち
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" 
                                                class="btn btn-outline-info btn-sm w-100" 
                                                onclick="showVisitTypeBreakdown(<?= $summary['contract_id'] ?>, '<?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>', <?= $year ?>, <?= $month ?>)">
                                            <i class="fas fa-chart-pie me-1"></i>実施方法
                                        </button>
                                    </div>
                                    
                                    <?php if (!empty($travelExpenses)): ?>
                                        <div class="col-6">
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm w-100" 
                                                    onclick="showTravelExpenseModal(<?= $summary['contract_id'] ?>, '<?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>', <?= $year ?>, <?= $month ?>)">
                                                <i class="fas fa-car me-1"></i>交通費
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($summary['closing_status']) && $summary['closing_status'] === 'finalized'): ?>
                                    <button type="button" 
                                            class="btn btn-outline-dark btn-sm" 
                                            onclick="showClosingDetailFromAdmin(<?= $summary['contract_id'] ?>, '<?= sprintf('%04d-%02d', $year, $month) ?>', '<?= htmlspecialchars($summary['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['company_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($summary['branch_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-eye me-1"></i>締め詳細
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 凡例（毎週契約対応版） -->
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>使用率：</strong>月間契約時間に対する時間業務（定期・臨時・延長）の合計実績で計算　
                            <strong>役務種別：</strong>
                            <span class="badge bg-success me-1"><i class="fas fa-calendar-check"></i> 定期</span>
                            <span class="badge bg-warning me-1"><i class="fas fa-exclamation-triangle"></i> 臨時</span>
                            <span class="badge bg-info me-1"><i class="fas fa-clock"></i> スポット</span>
                            <span class="badge bg-primary me-1"><i class="fas fa-file-alt"></i> 書面</span>
                            <span class="badge bg-secondary"><i class="fas fa-envelope"></i> 遠隔</span>
                        </small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">
                            <strong>契約時間：</strong>
                            <span class="badge bg-info me-1"><i class="fas fa-calendar-alt"></i> 毎月</span>
                            <span class="badge bg-info me-1"><i class="fas fa-calendar-alt"></i> 隔月</span>
                            <span class="badge bg-success"><i class="fas fa-calendar-week"></i> 毎週（月間計算時間）</span>
                            　<strong>実施方法：</strong>
                            <span class="badge bg-primary me-1"><i class="fas fa-walking"></i> 訪問</span>
                            <span class="badge bg-info me-1"><i class="fas fa-laptop"></i> オンライン</span>
                            （定期・臨時・延長のみ）　
                            <strong>交通費：</strong>訪問実施時のみ対象、オンライン・書面・遠隔は対象外
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- ページネーション -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-none d-md-block">
                    <small class="text-muted">
                        <?= number_format($pagination['start_record']) ?>-<?= number_format($pagination['end_record']) ?>件 
                        (全<?= number_format($pagination['total_records']) ?>件中)
                    </small>
                </div>
                
                <nav aria-label="役務記録サマリーページネーション">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- 前のページ -->
                        <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link admin-pagination-link" 
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
                                <a class="page-link admin-pagination-link" 
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
                                <a class="page-link admin-pagination-link" 
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
                                <a class="page-link admin-pagination-link" 
                                   href="#" 
                                   data-page="<?= $pagination['total_pages'] ?>"
                                   data-page-size="<?= $pagination['per_page'] ?>"><?= $pagination['total_pages'] ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 次のページ -->
                        <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                            <a class="page-link admin-pagination-link" 
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
        </div>
    <?php endif; ?>
</div>

<!-- 実施方法詳細モーダル -->
<div class="modal fade" id="visitTypeBreakdownModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-pie me-2"></i>実施方法別詳細
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitTypeBreakdownContent">
                <!-- JavaScriptで動的に生成 -->
            </div>
        </div>
    </div>
</div>

<!-- 交通費詳細・管理モーダル -->
<div class="modal fade" id="travelExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-car me-2"></i>交通費詳細・管理
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="travelExpenseContent">
                <!-- JavaScriptで動的に生成 -->
            </div>
        </div>
    </div>
</div>

<!-- 交通費承認モーダル -->
<div class="modal fade" id="approveTravelExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">交通費の承認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveTravelExpenseForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <p>この交通費を承認しますか？</p>
                    <div id="approveTravelExpenseInfo"></div>
                    
                    <div class="mb-3">
                        <label for="approveTravelExpenseComment" class="form-label">承認コメント（任意）</label>
                        <textarea class="form-control" id="approveTravelExpenseComment" name="comment" rows="3" 
                                  placeholder="承認に関するコメントがあれば入力してください"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-success">承認</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 交通費差戻しモーダル -->
<div class="modal fade" id="rejectTravelExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">交通費の差戻し</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectTravelExpenseForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <p>この交通費を差戻しますか？</p>
                    <div id="rejectTravelExpenseInfo"></div>
                    
                    <div class="mb-3">
                        <label for="rejectTravelExpenseComment" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectTravelExpenseComment" name="comment" rows="3" required
                                  placeholder="差戻す理由を入力してください"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-danger">差戻し</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 締め処理詳細表示用のモーダル -->
<div class="modal fade" id="closingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calculator me-2"></i>締め処理詳細
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="closingDetailContent">
                <!-- Ajax で読み込み -->
            </div>
        </div>
    </div>
</div>

<script>
// 管理者役務記録サマリー：ページネーション対応版
document.addEventListener('DOMContentLoaded', function() {
    
    // ===================== 並び替え機能 =====================
    
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

    // 並び替えセレクトボックスの自動送信
    const sortSelect = document.getElementById('sort');
    const orderSelect = document.getElementById('order');
    
    if (sortSelect && orderSelect) {
        sortSelect.addEventListener('change', function() {
            // ページを1に戻してフォームを送信
            const form = this.form;
            const pageInput = form.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.value = '1';
            } else {
                const hiddenPageInput = document.createElement('input');
                hiddenPageInput.type = 'hidden';
                hiddenPageInput.name = 'page';
                hiddenPageInput.value = '1';
                form.appendChild(hiddenPageInput);
            }
            form.submit();
        });
        
        orderSelect.addEventListener('change', function() {
            // ページを1に戻してフォームを送信
            const form = this.form;
            const pageInput = form.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.value = '1';
            } else {
                const hiddenPageInput = document.createElement('input');
                hiddenPageInput.type = 'hidden';
                hiddenPageInput.name = 'page';
                hiddenPageInput.value = '1';
                form.appendChild(hiddenPageInput);
            }
            form.submit();
        });
    }

    // ===================== ページネーション機能 =====================
    
    // ページサイズ変更時の処理
    const adminRecordsPageSizeSelect = document.getElementById('admin_records_page_size');
    if (adminRecordsPageSizeSelect) {
        adminRecordsPageSizeSelect.addEventListener('change', function() {
            const newPageSize = this.value;
            reloadPageWithPagination({
                page: 1, // ページサイズ変更時は1ページ目に戻る
                per_page: newPageSize
            });
        });
    }
    
    // ページネーションリンクのクリック処理
    document.addEventListener('click', function(e) {
        if (e.target.closest('.admin-pagination-link')) {
            e.preventDefault();
            const link = e.target.closest('.admin-pagination-link');
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
    
    // ページリロード関数
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
        const summaryCard = document.querySelector('.card:has(.admin-pagination-link)');
        if (summaryCard) {
            summaryCard.classList.add('pagination-loading');
        }
    }
    
    // ===================== 既存の機能 =====================
    
    // ツールチップの初期化
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 検索フォームの自動送信（役務種別変更時）
    const serviceTypeSelect = document.getElementById('service_type');
    if (serviceTypeSelect) {
        serviceTypeSelect.addEventListener('change', function() {
            // フィルター変更時は1ページ目に戻す
            const form = this.form;
            const pageInput = form.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.value = '1';
            } else {
                const hiddenPageInput = document.createElement('input');
                hiddenPageInput.type = 'hidden';
                hiddenPageInput.name = 'page';
                hiddenPageInput.value = '1';
                form.appendChild(hiddenPageInput);
            }
            form.submit();
        });
    }
    
    // 年月変更時の自動送信
    const yearSelect = document.getElementById('year');
    const monthSelect = document.getElementById('month');
    
    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            const form = this.form;
            const pageInput = form.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.value = '1';
            } else {
                const hiddenPageInput = document.createElement('input');
                hiddenPageInput.type = 'hidden';
                hiddenPageInput.name = 'page';
                hiddenPageInput.value = '1';
                form.appendChild(hiddenPageInput);
            }
            form.submit();
        });
    }
    
    if (monthSelect) {
        monthSelect.addEventListener('change', function() {
            const form = this.form;
            const pageInput = form.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.value = '1';
            } else {
                const hiddenPageInput = document.createElement('input');
                hiddenPageInput.type = 'hidden';
                hiddenPageInput.name = 'page';
                hiddenPageInput.value = '1';
                form.appendChild(hiddenPageInput);
            }
            form.submit();
        });
    }
    
    // テーブル行のホバーエフェクト
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(0, 123, 255, 0.05)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
});

// 週間スケジュール詳細モーダル表示（修正版）
function showWeeklyScheduleModal(contractId, doctorName, companyName, year, month) {
    console.log('showWeeklyScheduleModal called with:', {contractId, doctorName, companyName, year, month});
    
    const modal = new bootstrap.Modal(document.getElementById('weeklyScheduleModal'));
    const content = document.getElementById('weeklyScheduleContent');
    
    // ローディング表示
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">週間スケジュール詳細を取得中...</p>
        </div>
    `;
    
    modal.show();
    
    // 修正：正しいURLを構築
    const url = `<?= base_url('service_records/weekly_schedule_detail') ?>?contract_id=${contractId}&year=${year}&month=${month}`;
    
    console.log('Fetching URL:', url);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (response.status === 302 || response.status === 401) {
            throw new Error('認証エラー: ログインが必要です');
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Success! Response data:', data);
        
        if (data && data.success) {
            content.innerHTML = generateWeeklyScheduleContent(data.data, doctorName, companyName);
        } else {
            const errorMessage = data && data.error ? data.error : 'データの取得に失敗しました';
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${errorMessage}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                週間スケジュール詳細の取得に失敗しました。<br>
                <small>${error.message}</small>
            </div>
        `;
    });
}

// 週間スケジュールコンテンツ生成関数を追加
function generateWeeklyScheduleContent(data, doctorName, companyName) {
    const weeklyDetails = data.weekly_details || {};
    
    return `
        <div class="mb-3">
            <h6><i class="fas fa-user-md me-1"></i> ${escapeHtml(doctorName)}</h6>
            <p class="text-muted mb-0">
                <i class="fas fa-building me-1"></i> ${escapeHtml(companyName)}<br>
                <i class="fas fa-map-marker-alt me-1"></i> ${escapeHtml(data.branch_name || '')}
            </p>
            <p class="text-muted mb-0">
                <small><i class="fas fa-calendar me-1"></i> ${data.year}年${data.month}月の週間スケジュール</small>
            </p>
        </div>
        <hr>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-primary">週間基本時間</h6>
                        <h4>${formatHours(weeklyDetails.weekly_base_hours || 0)}</h4>
                        <small class="text-muted">毎週の契約時間</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-success">月間計算時間</h6>
                        <h4>${formatHours(weeklyDetails.monthly_calculated_hours || 0)}</h4>
                        <small class="text-muted">${data.year}年${data.month}月分</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>項目</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>週間スケジュール</strong></td>
                        <td>${(weeklyDetails.weekly_schedule || []).join('・') || '設定なし'}</td>
                    </tr>
                    <tr>
                        <td><strong>月の週数</strong></td>
                        <td>${weeklyDetails.week_count || 0}週</td>
                    </tr>
                    <tr>
                        <td><strong>月間予定訪問回数</strong></td>
                        <td>${weeklyDetails.monthly_visit_count || 0}回</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        ${weeklyDetails.error ? `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${weeklyDetails.error}
            </div>
        ` : ''}
    `;
}

// ユーティリティ関数の追加
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatHours(hours) {
    if (!hours || hours <= 0) return '0時間';
    
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    
    if (m > 0) {
        return `${h}時間${m}分`;
    } else {
        return `${h}時間`;
    }
}

// 実施方法詳細モーダル表示
function showVisitTypeBreakdown(contractId, doctorName, companyName, branchName, year, month) {
    const modal = new bootstrap.Modal(document.getElementById('visitTypeBreakdownModal'));
    const content = document.getElementById('visitTypeBreakdownContent');
    
    // ローディング表示
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">実施方法別データを取得中...</p>
        </div>
    `;
    
    modal.show();
    
    // 役務記録の詳細データを取得
    fetch(`<?= base_url('service_records/admin/') ?>${contractId}/detail/api?year=${year}&month=${month}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success && data.data) {
                const records = data.data;
                
                // 実施方法別に分類
                const visitRecords = records.filter(r => 
                    ['regular', 'emergency', 'extension', 'spot'].includes(r.service_type) && r.visit_type === 'visit'
                );
                const onlineRecords = records.filter(r => 
                    ['regular', 'emergency', 'extension', 'spot'].includes(r.service_type) && r.visit_type === 'online'
                );
                const noVisitTypeRecords = records.filter(r => 
                    ['document', 'remote_consultation', 'other'].includes(r.service_type)
                );
                
                content.innerHTML = `
                    <div class="mb-3">
                        <h6><i class="fas fa-user-md me-1"></i> ${doctorName}</h6>
                        <p class="text-muted mb-0">
                            <i class="fas fa-building me-1"></i> ${companyName}<br>
                            <i class="fas fa-map-marker-alt me-1"></i> ${branchName}
                        </p>
                        <p class="text-muted mb-0">
                            <small><i class="fas fa-calendar me-1"></i> ${year}年${month}月の実施方法別詳細</small>
                        </p>
                    </div>
                    <hr>
                    
                    <!-- 統計カード -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-walking text-primary fa-2x mb-2"></i>
                                    <h5 class="text-primary">訪問実施</h5>
                                    <h4>${formatHours(visitRecords.reduce((sum, r) => sum + parseFloat(r.service_hours || 0), 0))}</h4>
                                    <p class="mb-0">${visitRecords.length}件</p>
                                    <small class="text-success"><i class="fas fa-train me-1"></i>交通費対象</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-laptop text-info fa-2x mb-2"></i>
                                    <h5 class="text-info">オンライン実施</h5>
                                    <h4>${formatHours(onlineRecords.reduce((sum, r) => sum + parseFloat(r.service_hours || 0), 0))}</h4>
                                    <p class="mb-0">${onlineRecords.length}件</p>
                                    <small class="text-info"><i class="fas fa-wifi me-1"></i>交通費対象外</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-secondary">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt text-secondary fa-2x mb-2"></i>
                                    <h5 class="text-secondary">時間なし業務</h5>
                                    <h4>${noVisitTypeRecords.length}件</h4>
                                    <p class="mb-0">書面・遠隔相談</p>
                                    <small class="text-muted"><i class="fas fa-minus me-1"></i>実施方法対象外</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 詳細記録 -->
                    ${generateVisitTypeDetailTable(visitRecords, onlineRecords, noVisitTypeRecords)}
                `;
            } else {
                // フォールバック処理
                generateVisitTypeBreakdownFromCurrentData(contractId, doctorName, companyName, branchName, year, month, content);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // フォールバック処理
            generateVisitTypeBreakdownFromCurrentData(contractId, doctorName, companyName, branchName, year, month, content);
        });
}

// フォールバック：現在のページデータから実施方法別詳細を生成
function generateVisitTypeBreakdownFromCurrentData(contractId, doctorName, companyName, branchName, year, month, content) {
    // ページ内のサマリーデータから概算を取得
    const summaryRow = document.querySelector(`tr:has(a[href*="/${contractId}/detail"])`);
    
    if (summaryRow) {
        // サマリー行から基本データを抽出
        const totalHoursEl = summaryRow.querySelector('td:nth-child(5) strong');
        const recordCountEl = summaryRow.querySelector('.badge.bg-secondary');
        
        const totalHours = totalHoursEl ? totalHoursEl.textContent.trim() : '0時間';
        const recordCount = recordCountEl ? recordCountEl.textContent.trim() : '0';
        
        content.innerHTML = `
            <div class="mb-3">
                <h6><i class="fas fa-user-md me-1"></i> ${doctorName}</h6>
                <p class="text-muted mb-0">
                    <i class="fas fa-building me-1"></i> ${companyName}<br>
                    <i class="fas fa-map-marker-alt me-1"></i> ${branchName}
                </p>
                <p class="text-muted mb-0">
                    <small><i class="fas fa-calendar me-1"></i> ${year}年${month}月の実施方法別詳細</small>
                </p>
            </div>
            <hr>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>概要情報</strong><br>
                総実績時間: ${totalHours}<br>
                総記録件数: ${recordCount}<br>
                <small class="text-muted">詳細な実施方法別データを表示するには、APIエンドポイントの実装が必要です。</small>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-walking text-primary fa-2x mb-2"></i>
                            <h5 class="text-primary">訪問実施</h5>
                            <h4>-</h4>
                            <p class="mb-0">データ取得中</p>
                            <small class="text-success"><i class="fas fa-train me-1"></i>交通費対象</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-laptop text-info fa-2x mb-2"></i>
                            <h5 class="text-info">オンライン実施</h5>
                            <h4>-</h4>
                            <p class="mb-0">データ取得中</p>
                            <small class="text-info"><i class="fas fa-wifi me-1"></i>交通費対象外</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-secondary">
                        <div class="card-body text-center">
                            <i class="fas fa-file-alt text-secondary fa-2x mb-2"></i>
                            <h5 class="text-secondary">時間なし業務</h5>
                            <h4>-</h4>
                            <p class="mb-0">データ取得中</p>
                            <small class="text-muted"><i class="fas fa-minus me-1"></i>実施方法対象外</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center py-4">
                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                <p class="text-muted">詳細データの取得に失敗しました。</p>
                <p class="text-muted">
                    <a href="<?= base_url('service_records/admin/') ?>${contractId}/detail?year=${year}&month=${month}" 
                       class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>詳細ページで確認
                    </a>
                </p>
            </div>
        `;
    } else {
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                実施方法別データの取得に失敗しました。APIエンドポイントが実装されていない可能性があります。
            </div>
        `;
    }
}

// 実施方法別詳細テーブル生成
function generateVisitTypeDetailTable(visitRecords, onlineRecords, noVisitTypeRecords) {
    const allRecords = [...visitRecords, ...onlineRecords, ...noVisitTypeRecords].sort((a, b) => 
        new Date(b.service_date) - new Date(a.service_date)
    );
    
    if (allRecords.length === 0) {
        return '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-muted mb-3"></i><p class="text-muted">この期間の記録はありません。</p></div>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>日付</th>
                        <th>役務種別</th>
                        <th>実施方法</th>
                        <th>時間</th>
                        <th>ステータス</th>
                        <th>交通費</th>
                        <th>備考</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    allRecords.forEach(record => {
        const serviceTypeLabels = {
            'regular': { label: '定期', class: 'success' },
            'emergency': { label: '臨時', class: 'warning' },
            'extension': { label: '延長', class: 'info' },
            'spot': { label: 'スポット', class: 'info' },
            'document': { label: '書面', class: 'primary' },
            'remote_consultation': { label: '遠隔', class: 'secondary' },
            'other': { label: 'その他', class: 'dark' }
        };
        
        const serviceType = serviceTypeLabels[record.service_type] || { label: '定期', class: 'success' };
        
        let visitTypeDisplay = '-';
        let visitTypeClass = 'muted';
        if (['regular', 'emergency', 'extension', 'spot'].includes(record.service_type)) {
            if (record.visit_type === 'visit') {
                visitTypeDisplay = '<i class="fas fa-walking me-1"></i>訪問';
                visitTypeClass = 'primary';
            } else if (record.visit_type === 'online') {
                visitTypeDisplay = '<i class="fas fa-laptop me-1"></i>オンライン';
                visitTypeClass = 'info';
            }
        } else {
            visitTypeDisplay = '<i class="fas fa-minus me-1"></i>対象外';
            visitTypeClass = 'secondary';
        }
        
        const statusLabels = {
            'pending': { label: '承認待ち', class: 'warning' },
            'approved': { label: '承認済み', class: 'success' },
            'rejected': { label: '差戻し', class: 'danger' },
            'finalized': { label: '締め済み', class: 'dark' }
        };
        const status = statusLabels[record.status] || { label: '不明', class: 'secondary' };
        
        const timeDisplay = ['regular', 'emergency', 'extension', 'spot'].includes(record.service_type) ? 
            (parseFloat(record.service_hours) > 0 ? formatHours(parseFloat(record.service_hours)) : '進行中') : 
            '時間なし';
        
        // 交通費情報（簡易版）
        const isVisitType = record.visit_type === 'visit' && ['regular', 'emergency', 'extension', 'spot'].includes(record.service_type);
        const travelExpenseDisplay = isVisitType ? 
            '<small class="text-success"><i class="fas fa-train me-1"></i>対象</small>' : 
            '<small class="text-muted"><i class="fas fa-minus me-1"></i>対象外</small>';
        
        html += `
            <tr>
                <td>
                    ${formatDate(record.service_date)}
                    <div class="small text-muted">${getDayOfWeek(record.service_date)}</div>
                </td>
                <td>
                    <span class="badge bg-${serviceType.class}">${serviceType.label}</span>
                </td>
                <td>
                    <span class="text-${visitTypeClass}">${visitTypeDisplay}</span>
                </td>
                <td>
                    ${timeDisplay}
                </td>
                <td>
                    <span class="badge bg-${status.class}">${status.label}</span>
                </td>
                <td>
                    ${travelExpenseDisplay}
                </td>
                <td>
                    ${record.description ? 
                        `<span title="${escapeHtml(record.description)}">${escapeHtml(record.description.substring(0, 30))}${record.description.length > 30 ? '...' : ''}</span>` : 
                        '<span class="text-muted">-</span>'
                    }
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

// 交通費詳細モーダル表示
function showTravelExpenseModal(contractId, doctorName, companyName, branchName, year, month) {
    const modal = new bootstrap.Modal(document.getElementById('travelExpenseModal'));
    const content = document.getElementById('travelExpenseContent');
    
    // ローディング表示
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">交通費データを取得中...</p>
        </div>
    `;
    
    modal.show();
    
    // 交通費データを取得
    fetch(`<?= base_url('api/travel_expenses/contract/') ?>${contractId}?year=${year}&month=${month}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const expenses = data.data;
                
                content.innerHTML = `
                    <div class="mb-3">
                        <h6><i class="fas fa-user-md me-1"></i> ${doctorName}</h6>
                        <p class="text-muted mb-0">
                            <i class="fas fa-building me-1"></i> ${companyName}<br>
                            <i class="fas fa-map-marker-alt me-1"></i> ${branchName}
                        </p>
                        <p class="text-muted mb-0">
                            <small><i class="fas fa-calendar me-1"></i> ${year}年${month}月の交通費</small>
                        </p>
                    </div>
                    <hr>
                    
                    ${expenses.length > 0 ? generateTravelExpenseList(expenses) : '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-muted mb-3"></i><p class="text-muted">この期間の交通費記録はありません。</p></div>'}
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        交通費データの取得に失敗しました。
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    エラーが発生しました。
                </div>
            `;
        });
}

// 交通費リスト生成
function generateTravelExpenseList(expenses) {
    const transportIcons = {
        'train': 'fa-train',
        'bus': 'fa-bus', 
        'taxi': 'fa-taxi',
        'gasoline': 'fa-car',
        'highway_toll': 'fa-car',
        'parking': 'fa-car',
        'rental_car': 'fa-car',
        'airplane': 'fa-plane',
        'other': 'fa-question-circle'
    };
    
    const transportLabels = {
        'train': '電車',
        'bus': 'バス',
        'taxi': 'タクシー', 
        'gasoline': 'ガソリン',
        'highway_toll': '有料道路',
        'parking': '駐車料金',
        'rental_car': 'レンタカー',
        'airplane': '航空機',
        'other': 'その他'
    };
    
    const tripTypeLabels = {
        'round_trip': '往復',
        'one_way': '片道'
    };
    
    const statusLabels = {
        'pending': { label: '承認待ち', class: 'warning' },
        'approved': { label: '承認済み', class: 'success' },
        'rejected': { label: '差戻し', class: 'danger' },
        'finalized': { label: '締め済み', class: 'dark'}
    };
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>役務日</th>
                        <th>役務種別</th>
                        <th>実施方法</th>
                        <th>交通手段</th>
                        <th>区間</th>
                        <th class="text-end">金額</th>
                        <th>状態</th>
                        <th class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    expenses.forEach(expense => {
        const transportIcon = transportIcons[expense.transport_type] || 'fa-question-circle';
        const transportLabel = transportLabels[expense.transport_type] || 'その他';
        // trip_typeがNULLの場合は空文字列を設定
        const tripTypeLabel = expense.trip_type ? (tripTypeLabels[expense.trip_type] || '') : '';
        const statusInfo = statusLabels[expense.status] || { label: '不明', class: 'secondary' };
        
        const serviceTypeLabels = {
            'regular': '定期',
            'emergency': '臨時',
            'extension': '延長',
            'spot': 'スポット',
            'document': '書面',
            'remote_consultation': '遠隔',
            'other': 'その他'
        };
        const serviceTypeLabel = serviceTypeLabels[expense.service_type] || '定期';
        
        // 実施方法の表示（交通費があるということは基本的に訪問）
        const visitTypeDisplay = expense.visit_type === 'online' ? 
            '<span class="badge bg-info"><i class="fas fa-laptop me-1"></i>オンライン</span>' :
            '<span class="badge bg-primary"><i class="fas fa-walking me-1"></i>訪問</span>';
        
        html += `
            <tr>
                <td>
                    ${formatDate(expense.service_date)}
                    <div class="small text-muted">${getDayOfWeek(expense.service_date)}</div>
                </td>
                <td>
                    <span class="badge bg-primary">${serviceTypeLabel}</span>
                </td>
                <td>
                    ${visitTypeDisplay}
                </td>
                <td>
                    <i class="fas ${transportIcon} me-1"></i>
                    ${transportLabel}
                    ${tripTypeLabel ? `<div class="small text-muted">${tripTypeLabel}</div>` : ''}
                    ${expense.transport_type === 'taxi' ? 
                      (expense.taxi_allowed == 1 ? 
                        '<div><span class="badge bg-success mt-1"><i class="fas fa-check me-1"></i>利用可</span></div>' : 
                        '<div><span class="badge bg-danger mt-1"><i class="fas fa-times me-1"></i>利用不可</span></div>' +
                        (expense.company_notified == 1 ? '<div><small class="text-info"><i class="fas fa-info-circle me-1"></i>企業通知済</small></div>' : '')) : ''}
                </td>
                <td>
                    ${expense.departure_point ? `
                        <div class="small">
                            <strong>${escapeHtml(expense.departure_point)}</strong><br>
                            <i class="fas fa-arrow-down text-muted"></i><br>
                            <strong>${escapeHtml(expense.arrival_point)}</strong>
                        </div>
                    ` : `
                        <div class="small">
                            <strong>${escapeHtml(expense.arrival_point)}</strong>
                        </div>
                    `}
                </td>
                <td class="text-end">
                    <strong>¥${expense.amount.toLocaleString()}</strong>
                </td>
                <td>
                    <span class="badge bg-${statusInfo.class}">${statusInfo.label}</span>
                    ${expense.status === 'rejected' && expense.admin_comment ? 
                        `<div class="small text-danger mt-1" title="${escapeHtml(expense.admin_comment)}">
                            <i class="fas fa-comment me-1"></i>理由あり
                        </div>` : ''}
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" 
                                onclick="toggleTravelExpenseDetail(${expense.id})" 
                                title="詳細"
                                id="detailBtn_${expense.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${expense.status === 'pending' ? `
                            <button class="btn btn-outline-success" 
                                    onclick="showApproveTravelExpenseModal(${expense.id})" 
                                    title="承認">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-outline-danger" 
                                    onclick="showRejectTravelExpenseModal(${expense.id})" 
                                    title="差戻し">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
            <tr id="detailRow_${expense.id}" style="display: none;">
                <td colspan="8" class="bg-light">
                    <div class="p-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-info-circle me-1"></i>詳細情報</h6>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="120">交通手段:</th>
                                        <td>
                                            <i class="fas ${transportIcon} me-1"></i>${transportLabel}
                                            ${expense.transport_type === 'taxi' ? 
                                              (expense.taxi_allowed == 1 ? 
                                                '<span class="badge bg-success ms-2"><i class="fas fa-check me-1"></i>利用可</span>' : 
                                                '<span class="badge bg-danger ms-2"><i class="fas fa-times me-1"></i>利用不可</span>' +
                                                (expense.company_notified == 1 ? '<br><small class="text-info mt-1 d-inline-block"><i class="fas fa-info-circle me-1"></i>企業にお伝え済み</small>' : '')) : ''}
                                        </td>
                                    </tr>
                                    ${tripTypeLabel ? `<tr><th>往復・片道:</th><td>${tripTypeLabel}</td></tr>` : ''}
                                    <tr><th>出発地:</th><td>${escapeHtml(expense.departure_point) || '-'}</td></tr>
                                    <tr><th>到着地:</th><td>${escapeHtml(expense.arrival_point) || '-'}</td></tr>
                                    <tr><th>金額:</th><td><strong class="text-primary">¥${expense.amount.toLocaleString()}</strong></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-clipboard me-1"></i>備考・その他</h6>
                                <div class="mb-3">
                                    <strong>備考:</strong><br>
                                    <div class="bg-white p-2 rounded border">
                                        ${expense.memo ? escapeHtml(expense.memo) : '<span class="text-muted">記載なし</span>'}
                                    </div>
                                </div>
                                ${expense.receipt_file_path ? 
                                  '<div class="mb-2">' +
                                    '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="showReceipt(\'' + escapeHtml(expense.receipt_file_path) + '\', ' + expense.id + ')">' +
                                      '<i class="fas fa-receipt me-1"></i>レシートを表示' +
                                    '</button>' +
                                  '</div>' : ''}
                                ${expense.admin_comment ? 
                                  '<div class="alert alert-warning mb-0">' +
                                    '<strong><i class="fas fa-comment me-1"></i>管理者コメント:</strong><br>' +
                                    escapeHtml(expense.admin_comment) +
                                  '</div>' : ''}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    // 統計情報を追加
    const totalAmount = expenses.reduce((sum, exp) => sum + parseInt(exp.amount), 0);
    const pendingCount = expenses.filter(exp => exp.status === 'pending').length;
    const approvedCount = expenses.filter(exp => exp.status === 'approved').length;
    const rejectedCount = expenses.filter(exp => exp.status === 'rejected').length;
    
    // 実施方法別の統計
    const visitExpenses = expenses.filter(exp => exp.visit_type !== 'online');
    const onlineExpenses = expenses.filter(exp => exp.visit_type === 'online');
    
    html += `
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body py-2">
                        <h6 class="mb-1">合計金額</h6>
                        <h5 class="text-primary">¥${totalAmount.toLocaleString()}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body py-2">
                        <h6 class="mb-1">状態別件数</h6>
                        <div class="small">
                            <span class="badge bg-warning me-1">待機: ${pendingCount}</span>
                            <span class="badge bg-success me-1">承認: ${approvedCount}</span>
                            <span class="badge bg-danger">差戻: ${rejectedCount}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body py-2">
                        <h6 class="mb-1">実施方法別</h6>
                        <div class="small">
                            <span class="badge bg-primary me-1">訪問: ${visitExpenses.length}</span>
                            ${onlineExpenses.length > 0 ? `<span class="badge bg-info">オンライン: ${onlineExpenses.length}</span>` : ''}
                        </div>
                        ${onlineExpenses.length > 0 ? '<small class="text-warning d-block mt-1"><i class="fas fa-exclamation-triangle me-1"></i>オンライン実施で交通費登録</small>' : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return html;
}

// 交通費承認モーダル表示
function showApproveTravelExpenseModal(expenseId) {
    // まず交通費詳細を取得
    fetch(`<?= base_url('api/travel_expenses/') ?>${expenseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const expense = data.data;
                
                document.getElementById('approveTravelExpenseInfo').innerHTML = `
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>役務日:</strong> ${formatDate(expense.service_date)}<br>
                                    <strong>交通手段:</strong> ${getTransportLabel(expense.transport_type)}
                                    ${expense.transport_type === 'taxi' ? 
                                      (expense.taxi_allowed == 1 ? 
                                        '<span class="badge bg-success ms-1"><i class="fas fa-check me-1"></i>利用可</span>' : 
                                        '<span class="badge bg-danger ms-1"><i class="fas fa-times me-1"></i>利用不可</span>' +
                                        (expense.company_notified == 1 ? '<br><small class="text-info"><i class="fas fa-info-circle me-1"></i>企業にお伝え済み</small>' : '')) : ''}<br>
                                    <strong>区間:</strong> ${expense.departure_point ? `${escapeHtml(expense.departure_point)} → ${escapeHtml(expense.arrival_point)}` : escapeHtml(expense.arrival_point)}
                                </div>
                                <div class="col-md-6">
                                    <strong>金額:</strong> ¥${expense.amount.toLocaleString()}<br>
                                    ${expense.trip_type ? `<strong>往復・片道:</strong> ${expense.trip_type === 'round_trip' ? '往復' : '片道'}<br>` : ''}
                                    ${expense.memo ? `<strong>備考:</strong> ${escapeHtml(expense.memo)}<br>` : ''}
                                    ${expense.receipt_file_path ? 
                                      '<button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="showReceipt(\'' + escapeHtml(expense.receipt_file_path) + '\', ' + expenseId + ')">' +
                                        '<i class="fas fa-receipt me-1"></i>レシートを表示' +
                                      '</button>' : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('approveTravelExpenseForm').action = `<?= base_url('travel_expenses/') ?>${expenseId}/approve`;
                document.getElementById('approveTravelExpenseComment').value = '';
                
                const modal = new bootstrap.Modal(document.getElementById('approveTravelExpenseModal'));
                modal.show();
            }
        });
}

// 交通費差戻しモーダル表示
function showRejectTravelExpenseModal(expenseId) {
    // まず交通費詳細を取得
    fetch(`<?= base_url('api/travel_expenses/') ?>${expenseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const expense = data.data;
                
                document.getElementById('rejectTravelExpenseInfo').innerHTML = `
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>役務日:</strong> ${formatDate(expense.service_date)}<br>
                                    <strong>交通手段:</strong> ${getTransportLabel(expense.transport_type)}
                                    ${expense.transport_type === 'taxi' ? 
                                      (expense.taxi_allowed == 1 ? 
                                        '<span class="badge bg-success ms-1"><i class="fas fa-check me-1"></i>利用可</span>' : 
                                        '<span class="badge bg-danger ms-1"><i class="fas fa-times me-1"></i>利用不可</span>' +
                                        (expense.company_notified == 1 ? '<br><small class="text-info"><i class="fas fa-info-circle me-1"></i>企業にお伝え済み</small>' : '')) : ''}<br>
                                    <strong>区間:</strong> ${expense.departure_point ? `${escapeHtml(expense.departure_point)} → ${escapeHtml(expense.arrival_point)}` : escapeHtml(expense.arrival_point)}
                                </div>
                                <div class="col-md-6">
                                    <strong>金額:</strong> ¥${expense.amount.toLocaleString()}<br>
                                    ${expense.trip_type ? `<strong>往復・片道:</strong> ${expense.trip_type === 'round_trip' ? '往復' : '片道'}<br>` : ''}
                                    ${expense.memo ? `<strong>備考:</strong> ${escapeHtml(expense.memo)}<br>` : ''}
                                    ${expense.receipt_file_path ? 
                                      '<button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="showReceipt(\'' + escapeHtml(expense.receipt_file_path) + '\', ' + expenseId + ')">' +
                                        '<i class="fas fa-receipt me-1"></i>レシートを表示' +
                                      '</button>' : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('rejectTravelExpenseForm').action = `<?= base_url('travel_expenses/') ?>${expenseId}/reject`;
                document.getElementById('rejectTravelExpenseComment').value = '';
                
                const modal = new bootstrap.Modal(document.getElementById('rejectTravelExpenseModal'));
                modal.show();
            }
        });
}

// 交通費詳細の表示切替（テーブル内展開）
function toggleTravelExpenseDetail(expenseId) {
    const detailRow = document.getElementById(`detailRow_${expenseId}`);
    const detailBtn = document.getElementById(`detailBtn_${expenseId}`);
    
    if (detailRow.style.display === 'none') {
        // 詳細行を表示
        detailRow.style.display = 'table-row';
        detailBtn.classList.remove('btn-outline-info');
        detailBtn.classList.add('btn-info', 'text-white');
        detailBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
        detailBtn.title = '詳細を閉じる';
    } else {
        // 詳細行を非表示
        detailRow.style.display = 'none';
        detailBtn.classList.remove('btn-info', 'text-white');
        detailBtn.classList.add('btn-outline-info');
        detailBtn.innerHTML = '<i class="fas fa-eye"></i>';
        detailBtn.title = '詳細';
    }
}

// ユーティリティ関数
function getTransportLabel(transportType) {
    const labels = {
        'train': '電車',
        'bus': 'バス',
        'taxi': 'タクシー',
        'gasoline': 'ガソリン',
        'highway_toll': '有料道路',
        'parking': '駐車料金',
        'rental_car': 'レンタカー',
        'airplane': '航空機',
        'other': 'その他'
    };
    return labels[transportType] || 'その他';
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'long', 
        day: 'numeric'
    });
}

function getDayOfWeek(dateString) {
    const date = new Date(dateString);
    const days = ['日', '月', '火', '水', '木', '金', '土'];
    return days[date.getDay()] + '曜日';
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('ja-JP') + ' ' + date.toLocaleTimeString('ja-JP', {hour: '2-digit', minute: '2-digit'});
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// レシート表示
function showReceipt(receiptPath, expenseId) {
    // レシートファイルのURLを生成
    const receiptUrl = `<?= base_url('') ?>${receiptPath}`;
    
    // 新しいウィンドウでレシートを表示
    window.open(receiptUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

// 時間フォーマット関数
function formatHours(hours) {
    if (hours <= 0) return '0時間';
    
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    
    if (m > 0) {
        return `${h}時間${m}分`;
    } else {
        return `${h}時間`;
    }
}

// 締め処理詳細表示関数
function showClosingDetailFromAdmin(contractId, closingPeriod, doctorName, companyName, branchName) {
    const modal = new bootstrap.Modal(document.getElementById('closingDetailModal'));
    const content = document.getElementById('closingDetailContent');
    
    // ローディング表示
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">締め処理詳細を取得中...</p>
        </div>
    `;
    
    modal.show();
    
    // 締め処理詳細データを取得
    fetch(`<?= base_url('service_records/closing_detail') ?>?contract_id=${contractId}&period=${closingPeriod}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    締め処理詳細の取得に失敗しました。
                </div>
            `;
        });
}

</script>

<style>
.table td {
    vertical-align: middle;
}

.table th {
    border-top: none;
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.badge {
    font-size: 0.75em;
}

/* ページネーション用のスタイル（契約一覧と統一） */
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

/* 毎週契約の特別スタイル */
.badge.bg-success {
    background-color: #198754 !important;
}

.text-success {
    color: #198754 !important;
}

/* 訪問頻度別の色分け */
.text-info {
    color: #0dcaf0 !important;
}

/* 訪問頻度バッジスタイル */
.badge.bg-primary {
    background-color: #0d6efd !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
}

/* 実施方法別統計カードのスタイル */
.card.border-primary {
    border-color: #0d6efd !important;
    border-width: 2px !important;
}

.card.border-info {
    border-color: #0dcaf0 !important;
    border-width: 2px !important;
}

.card.border-secondary {
    border-color: #6c757d !important;
    border-width: 2px !important;
}

/* 統計カードのアニメーション */
.card:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* テーブル行のアニメーション */
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

/* レスポンシブ対応 */
@media (max-width: 1400px) {
    .table th:nth-child(6), .table td:nth-child(6) { display: none; }
}

@media (max-width: 1200px) {
    .table th:nth-child(7), .table td:nth-child(7) { display: none; }
}

@media (max-width: 992px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
    
    .col-md-3, .col-md-4 {
        margin-bottom: 1rem;
    }
    
    .table th:nth-child(8), .table td:nth-child(8) { display: none; }
    .table th:nth-child(11), .table td:nth-child(11) { display: none; }
}

@media (max-width: 768px) {
    .table th:nth-child(5), .table td:nth-child(5) { display: none; }
    .table th:nth-child(9), .table td:nth-child(9) { display: none; }
    
    .col-md-2, .col-md-3 {
        margin-bottom: 0.5rem;
    }
    
    .col-md-3, .col-md-4 {
        width: 50%;
    }
    
    /* ページネーション調整 */
    .pagination .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .table th:nth-child(4), .table td:nth-child(4) { display: none; }
    .table th:nth-child(10), .table td:nth-child(10) { display: none; }
    
    .card-body h3 {
        font-size: 1.5rem;
    }
    
    .card-body p {
        font-size: 0.875rem;
    }
    
    .col-md-3, .col-md-4 {
        width: 100%;
    }
    
    /* モバイルページネーション最適化 */
    .pagination .page-link {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
        min-width: 32px;
        text-align: center;
    }
}

/* 印刷時のスタイル */
@media print {
    .btn,
    .btn-group,
    .modal,
    .dropdown,
    .card-header .badge,
    .pagination {
        display: none !important;
    }
    
    .table {
        font-size: 0.8rem;
    }
    
    .card {
        border: 1px solid #000 !important;
        page-break-inside: avoid;
    }
    
    .badge {
        border: 1px solid #000;
        color: #000 !important;
        background-color: #fff !important;
    }
    
    .table th, .table td {
        display: table-cell !important;
    }
}

/* ========== モバイルサマリーカード用スタイル ========== */

.mobile-summary-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-summary-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.mobile-summary-card .card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.mobile-summary-card .card-subtitle {
    font-size: 0.875rem;
}

.mobile-info-section {
    padding: 0.5rem 0;
    border-top: 1px solid #e9ecef;
}

.mobile-info-section:first-child {
    border-top: none;
}

.mobile-info-section small.text-muted {
    font-weight: 500;
    font-size: 0.75rem;
}

/* タブレット表示調整 (768px-1199px) */
@media (min-width: 768px) and (max-width: 1199px) {
    .mobile-summary-card {
        margin-bottom: 1rem;
    }
    
    .mobile-summary-card .row.g-2 {
        gap: 0.75rem !important;
    }
}

/* モバイル表示調整 (768px未満) */
@media (max-width: 767px) {
    .mobile-summary-card .card-body {
        padding: 1rem;
    }
    
    .mobile-summary-card .card-title {
        font-size: 0.9rem;
    }
    
    .mobile-summary-card .card-subtitle {
        font-size: 0.8rem;
    }
    
    .mobile-info-section {
        padding: 0.4rem 0;
    }
    
    .mobile-info-section small {
        font-size: 0.7rem;
    }
    
    .badge.fs-6 {
        font-size: 0.9rem !important;
    }
}

/* 小型モバイル表示調整 (576px未満) */
@media (max-width: 575px) {
    .mobile-summary-card .card-body {
        padding: 0.75rem;
    }
    
    .mobile-summary-card .btn-sm {
        font-size: 0.8rem;
        padding: 0.4rem 0.5rem;
    }
    
    .mobile-summary-card .badge {
        font-size: 0.65rem;
        padding: 0.25em 0.5em;
    }
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