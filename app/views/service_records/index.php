<?php
// app/views/service_records/index.php - ページネーション対応版
?>
<!-- ヘッダー -->
<div class="header-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-history me-2"></i>役務記録一覧</h2>
        <div class="header-buttons">
            <a href="<?= base_url('service_records/create?return_to=service_records') ?>" class="btn btn-primary me-2">
                <i class="fas fa-plus me-1"></i>役務記録追加
            </a>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
            </a>
        </div>
    </div>
</div>

<!-- 検索・フィルター -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('service_records') ?>" class="filter-form" id="filterForm">
            <div class="row g-3">
                <div class="col-md-2 col-6">
                    <label for="search" class="form-label">検索</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($searchFilter ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="企業名/拠点名で検索">
                </div>
                <div class="col-md-2 col-6">
                    <label for="status" class="form-label">ステータス</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全て</option>
                        <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>承認待ち</option>
                        <option value="approved" <?= ($statusFilter ?? '') === 'approved' ? 'selected' : '' ?>>承認済み</option>
                        <option value="rejected" <?= ($statusFilter ?? '') === 'rejected' ? 'selected' : '' ?>>差戻し</option>
                        <option value="finalized" <?= ($statusFilter ?? '') === 'finalized' ? 'selected' : '' ?>>締済み</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label for="service_type" class="form-label">役務種別</label>
                    <select class="form-select" id="service_type" name="service_type">
                        <option value="">全て</option>
                        <?php if (in_array('regular', $availableServiceTypes ?? [])): ?>
                        <option value="regular" <?= ($serviceTypeFilter ?? '') === 'regular' ? 'selected' : '' ?>>定期訪問</option>
                        <?php endif; ?>
                        <?php if (in_array('emergency', $availableServiceTypes ?? [])): ?>
                        <option value="emergency" <?= ($serviceTypeFilter ?? '') === 'emergency' ? 'selected' : '' ?>>臨時訪問</option>
                        <?php endif; ?>
                        <?php if (in_array('spot', $availableServiceTypes ?? [])): ?>
                        <option value="spot" <?= ($serviceTypeFilter ?? '') === 'spot' ? 'selected' : '' ?>>スポット</option>
                        <?php endif; ?>
                        <?php if (in_array('remote_consultation', $availableServiceTypes ?? [])): ?>
                        <option value="remote_consultation" <?= ($serviceTypeFilter ?? '') === 'remote_consultation' ? 'selected' : '' ?>>遠隔相談</option>
                        <?php endif; ?>
                        <?php if (in_array('document', $availableServiceTypes ?? [])): ?>
                        <option value="document" <?= ($serviceTypeFilter ?? '') === 'document' ? 'selected' : '' ?>>書面作成</option>
                        <?php endif; ?>
                        <option value="other" <?= ($serviceTypeFilter ?? '') === 'other' ? 'selected' : '' ?>>その他</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label for="year_month" class="form-label">年月</label>
                    <input type="month" class="form-control" id="year_month" name="year_month" 
                           value="<?= $yearMonth ?? date('Y-m') ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label for="contract_id" class="form-label">契約</label>
                    <select class="form-select" id="contract_id" name="contract_id">
                        <option value="">全ての契約</option>
                        <?php if (!empty($contractsForFilter)): ?>
                            <?php foreach ($contractsForFilter as $contract): ?>
                                <option value="<?= $contract['contract_id'] ?>" 
                                        <?= ($contractFilter ?? '') == $contract['contract_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($contract['company_name'] . ' - ' . $contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2 col-12">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 filter-buttons">
                        <button type="submit" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-search"></i><span class="btn-text">検索</span>
                        </button>
                        <a href="<?= base_url('service_records') ?>" class="btn btn-outline-secondary flex-fill">
                            <i class="fas fa-redo"></i><span class="btn-text">リセット</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- フィルター結果の表示 -->
<?php if (!empty($statusFilter) || !empty($serviceTypeFilter) || !empty($searchFilter) || !empty($contractFilter)): ?>
<div class="alert alert-info">
    <h6 class="mb-2"><i class="fas fa-filter me-2"></i>適用中のフィルター:</h6>
    <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-primary">
            <?= $yearMonth ?>
        </span>
        
        <?php if (!empty($statusFilter)): ?>
            <span class="badge bg-warning">
                ステータス: <?= get_status_label($statusFilter) ?>
            </span>
        <?php endif; ?>
        
        <?php if (!empty($serviceTypeFilter)): ?>
            <span class="badge bg-info">
                役務種別: <?= get_service_type_label($serviceTypeFilter) ?>
            </span>
        <?php endif; ?>
        
        <?php if (!empty($searchFilter)): ?>
            <span class="badge bg-success">
                検索: <?= htmlspecialchars($searchFilter, ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>
        
        <?php if (!empty($contractFilter) && !empty($currentContract)): ?>
            <span class="badge bg-secondary">
                契約: <?= htmlspecialchars($currentContract['company_name'] . ' - ' . $currentContract['branch_name'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- モバイル用並び替え (768px未満) -->
<div class="mobile-sort-section d-xl-none">
    <label for="mobile_sort" class="form-label">並び替え</label>
    <select class="form-select" id="mobile_sort">
        <option value="service_date-desc" <?= ($sortColumn ?? 'service_date') === 'service_date' && ($sortOrder ?? 'desc') === 'desc' ? 'selected' : '' ?>>
            日付(新しい順)
        </option>
        <option value="service_date-asc" <?= ($sortColumn ?? '') === 'service_date' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            日付(古い順)
        </option>
        <option value="company_name-asc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            企業名(昇順)
        </option>
        <option value="company_name-desc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            企業名(降順)
        </option>
        <option value="service_hours-desc" <?= ($sortColumn ?? '') === 'service_hours' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            役務時間(多い順)
        </option>
        <option value="service_hours-asc" <?= ($sortColumn ?? '') === 'service_hours' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            役務時間(少ない順)
        </option>
        <option value="service_type-asc" <?= ($sortColumn ?? '') === 'service_type' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            役務種別(昇順)
        </option>
        <option value="status-asc" <?= ($sortColumn ?? '') === 'status' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            ステータス(昇順)
        </option>
    </select>
</div>

<!-- 役務記録一覧 -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>役務記録
                    <?php if (!empty($records)): ?>
                        <span class="badge bg-primary"><?= $pagination['total_records'] ?>件</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <!-- ページサイズ選択 -->
                    <div class="d-flex align-items-center">
                        <label for="service_records_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                        <select id="service_records_page_size" class="form-select form-select-sm" style="width: auto;">
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
        <?php if (empty($records)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>役務記録がありません。
                <a href="<?= base_url('service_records/create') ?>" class="alert-link">新しい記録を追加</a>してください。
            </div>
        <?php else: ?>
            
            <!-- デスクトップ表示（1400px以上） -->
            <div class="table-responsive d-none d-xl-block">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="#" class="sort-link" data-sort="service_date" title="日付で並び替え">
                                    日付
                                    <?php if (($sortColumn ?? 'service_date') === 'service_date'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="company_name" title="企業・拠点で並び替え">
                                    企業・拠点
                                    <?php if (($sortColumn ?? '') === 'company_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">実施時間</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="service_hours" title="役務時間で並び替え">
                                    役務時間
                                    <?php if (($sortColumn ?? '') === 'service_hours'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="service_type" title="役務種別で並び替え">
                                    役務種別
                                    <?php if (($sortColumn ?? '') === 'service_type'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">実施方法</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="status" title="ステータスで並び替え">
                                    ステータス
                                    <?php if (($sortColumn ?? '') === 'status'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">交通費</th>
                            <th>業務内容</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <?php
                            // 役務種別情報（拡張版）
                            $serviceTypeInfo = [
                                'regular' => ['class' => 'success', 'icon' => 'calendar-check', 'label' => '定期'],
                                'emergency' => ['class' => 'warning', 'icon' => 'exclamation-triangle', 'label' => '臨時'],
                                'extension' => ['class' => 'info', 'icon' => 'clock', 'label' => '延長'],
                                'spot' => ['class' => 'info', 'icon' => 'clock', 'label' => 'スポット'],
                                'document' => ['class' => 'primary', 'icon' => 'file-alt', 'label' => '書面'],
                                'remote_consultation' => ['class' => 'secondary', 'icon' => 'envelope', 'label' => '遠隔'],
                                'other' => ['class' => 'dark', 'icon' => 'plus', 'label' => 'その他'],
                            ];
                            $serviceType = $serviceTypeInfo[$record['service_type'] ?? 'regular'];
                            
                            // 訪問種別情報
                            $visitTypeInfo = [
                                'visit' => ['class' => 'primary', 'icon' => 'walking', 'label' => '訪問'],
                                'online' => ['class' => 'info', 'icon' => 'laptop', 'label' => 'オンライン']
                            ];
                            $visitType = $visitTypeInfo[$record['visit_type'] ?? 'visit'];
                            
                            // ステータス情報
                            $statusInfo = [
                                'pending' => ['class' => 'warning', 'label' => '承認待ち', 'icon' => 'clock'],
                                'approved' => ['class' => 'success', 'label' => '承認済み', 'icon' => 'check-circle'],
                                'rejected' => ['class' => 'danger', 'label' => '差戻し', 'icon' => 'times-circle'],
                                'finalized' => ['class' => 'dark', 'label' => '締済み', 'icon' => 'lock']
                            ];
                            $status = $statusInfo[$record['status'] ?? 'pending'];
                            
                            // 時間管理が必要な役務種別かチェック
                            $requiresTimeTracking = in_array($record['service_type'] ?? 'regular', ['regular', 'emergency', 'extension', 'spot']);
                            
                            // 訪問種別が選択可能な役務種別かチェック
                            $requiresVisitType = requires_visit_type_selection($record['service_type'] ?? 'regular');
                            
                            // 自動分割記録かどうかの判定
                            $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                                          strpos($record['description'] ?? '', '自動分割') !== false ||
                                          strpos($record['description'] ?? '', '延長分') !== false;
                            
                            // 進行中判定（時間管理が必要な役務のみ）
                            $isActive = $requiresTimeTracking && $record['service_hours'] == 0;
                            
                            // 交通費情報を取得（訪問種別が「訪問」で時間管理が必要で完了した役務のみ）
                            $travelExpenseInfo = null;
                            if ($requiresTimeTracking && $record['service_hours'] > 0 && ($record['visit_type'] ?? 'visit') === 'visit') {
                                require_once __DIR__ . '/../../models/TravelExpense.php';
                                $travelExpenseModel = new TravelExpense();
                                $travelExpenses = $travelExpenseModel->findByServiceRecord($record['id']);
                                $travelExpenseInfo = [
                                    'has_expenses' => !empty($travelExpenses),
                                    'count' => count($travelExpenses),
                                    'total_amount' => array_sum(array_column($travelExpenses, 'amount')),
                                    'pending_count' => count(array_filter($travelExpenses, function($e) { return $e['status'] === 'pending'; })),
                                    'approved_count' => count(array_filter($travelExpenses, function($e) { return $e['status'] === 'approved'; })),
                                    'rejected_count' => count(array_filter($travelExpenses, function($e) { return $e['status'] === 'rejected'; }))
                                ];
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?= format_date($record['service_date']) ?></strong>
                                    <div class="small text-muted">
                                        <?= date('w', strtotime($record['service_date'])) == 0 ? '日' : 
                                           (date('w', strtotime($record['service_date'])) == 6 ? '土' : '平日') ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="company-branch-info">
                                        <div class="fw-bold">
                                            <i class="fas fa-building me-1 text-primary"></i>
                                            <?= htmlspecialchars($record['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?= htmlspecialchars($record['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($requiresTimeTracking): ?>
                                        <?php if (!$isActive): ?>
                                            <!-- 時間分割対応の表示 -->
                                            <?= format_service_time_display($record) ?>
                                            
                                            <!-- 自動分割記録の場合の追加情報 -->
                                            <?php if ($isAutoSplit): ?>
                                                <div class="small text-info mt-1">
                                                    <i class="fas fa-info-circle me-1"></i>分割記録
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">進行中</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- 時間なし役務の場合 -->
                                        <span class="text-muted small">
                                            <i class="fas fa-minus-circle me-1"></i>
                                            時間記録なし
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($requiresTimeTracking): ?>
                                        <?php if (!$isActive): ?>
                                            <span class="badge bg-primary fs-6">
                                                <?= format_total_hours($record['service_hours']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">--:--</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- 時間なし役務の場合 -->
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-minus me-1"></i>時間なし
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge bg-<?= $serviceType['class'] ?>">
                                        <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                        <?= $serviceType['label'] ?>
                                    </span>
                                    
                                    <!-- 自動分割記録のマーク -->
                                    <?php if ($record['service_type'] === 'extension' && $isAutoSplit): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-secondary" title="自動分割により作成された記録">
                                                <i class="fas fa-scissors me-1"></i>自動分割
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- 訪問種別（実施方法）列 -->
                                <td class="text-center">
                                    <?php if ($requiresVisitType): ?>
                                        <span class="badge bg-<?= $visitType['class'] ?>">
                                            <i class="fas fa-<?= $visitType['icon'] ?> me-1"></i>
                                            <?= $visitType['label'] ?>
                                        </span>
                                        
                                    <?php else: ?>
                                        <!-- 訪問種別が不要な役務種別の場合 -->
                                        <span class="text-muted small">
                                            <i class="fas fa-minus me-1"></i>対象外
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge bg-<?= $status['class'] ?>">
                                        <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                    
                                    <?php if ($record['status'] === 'rejected' && !empty($record['rejection_reason'])): ?>
                                        <div class="mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="popover" 
                                                    data-bs-title="差戻し理由" 
                                                    data-bs-content="<?= htmlspecialchars($record['rejection_reason'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- 交通費列（訪問種別対応版） -->
                                <td class="text-center">
                                    <?php if ($requiresTimeTracking): ?>
                                        <?php if (!$isActive): ?>
                                            <!-- 完了した役務の場合 -->
                                            <?php if (($record['visit_type'] ?? 'visit') === 'visit'): ?>
                                                <!-- 訪問の場合：交通費登録可能 -->
                                                <div class="travel-expense-info">
                                                    <!-- 締め処理済みでない場合のみ追加ボタンを表示 -->
                                                    <?php if ($record['status'] !== 'finalized'): ?>
                                                        <a href="<?= base_url("travel_expenses/create/{$record['id']}") ?>" 
                                                        class="btn btn-sm btn-success mb-1" 
                                                        title="交通費を追加登録">
                                                            <i class="fas fa-plus me-1"></i>
                                                            <i class="fas fa-train"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- 締め処理済みの場合は追加不可メッセージ -->
                                                        <div class="text-center">
                                                            <span class="text-muted" title="締め処理済みのため交通費追加不可">
                                                                <i class="fas fa-lock"></i>
                                                                <div class="mt-1">
                                                                    <small>追加不可</small>
                                                                    <div class="small text-info">
                                                                        <i class="fas fa-lock me-1"></i>締め済み
                                                                    </div>
                                                                </div>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($travelExpenseInfo && $travelExpenseInfo['has_expenses']): ?>
                                                        <!-- 交通費が登録されている場合の詳細情報 -->
                                                        <div class="registered-expenses-info">
                                                            <small class="text-success fw-bold d-block">
                                                                <?= $travelExpenseInfo['count'] ?>件登録済み
                                                            </small>
                                                            <small class="text-muted d-block">
                                                                合計: <?= format_amount($travelExpenseInfo['total_amount']) ?>
                                                            </small>
                                                            
                                                            <!-- ステータス別の件数表示 -->
                                                            <div class="d-flex justify-content-center gap-1 mt-1">
                                                                <?php if ($travelExpenseInfo['approved_count'] > 0): ?>
                                                                    <span class="badge bg-success" title="承認済み">
                                                                        <i class="fas fa-check"></i>
                                                                        <?= $travelExpenseInfo['approved_count'] ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($travelExpenseInfo['pending_count'] > 0): ?>
                                                                    <span class="badge bg-warning" title="承認待ち">
                                                                        <i class="fas fa-clock"></i>
                                                                        <?= $travelExpenseInfo['pending_count'] ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($travelExpenseInfo['rejected_count'] > 0): ?>
                                                                    <span class="badge bg-danger" title="差戻し">
                                                                        <i class="fas fa-times"></i>
                                                                        <?= $travelExpenseInfo['rejected_count'] ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <!-- 登録済み交通費の管理リンク -->
                                                            <div class="mt-2">
                                                                <?php if ($record['status'] !== 'finalized'): ?>
                                                                    <?php if ($travelExpenseInfo['count'] == 1): ?>
                                                                        <!-- 1件のみの場合：直接編集ページへ -->
                                                                        <?php
                                                                        $travelExpenses = $travelExpenseModel->findByServiceRecord($record['id']);
                                                                        $singleExpense = $travelExpenses[0];
                                                                        ?>
                                                                        <?php if ($travelExpenseInfo['approved_count'] == 1): ?>
                                                                            <button type="button" 
                                                                                    class="btn btn-sm btn-outline-secondary" 
                                                                                    onclick="showTravelExpensesList(<?= $record['id'] ?>, '<?= $record['status'] ?>')"
                                                                                    title="交通費を確認（編集不可）">
                                                                                <i class="fas fa-eye me-1"></i>確認
                                                                            </button>
                                                                        <?php else: ?>
                                                                            <a href="<?= base_url("travel_expenses/{$singleExpense['id']}/edit") ?>" 
                                                                            class="btn btn-sm btn-outline-info" 
                                                                            title="交通費を編集">
                                                                                <i class="fas fa-edit me-1"></i>編集
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <!-- 複数件の場合：一覧ページへ -->
                                                                        <button type="button" 
                                                                                class="btn btn-sm btn-outline-info" 
                                                                                onclick="showTravelExpensesList(<?= $record['id'] ?>, '<?= $record['status'] ?>')"
                                                                                title="登録済み交通費を確認">
                                                                            <i class="fas fa-list me-1"></i>一覧
                                                                        </button>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <!-- 締め処理済みの場合は閲覧のみ -->
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-secondary" 
                                                                            onclick="showTravelExpensesList(<?= $record['id'] ?>, '<?= $record['status'] ?>')"
                                                                            title="交通費を確認（編集不可）">
                                                                        <i class="fas fa-eye me-1"></i>確認
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <!-- 交通費が未登録の場合 -->
                                                        <div class="mt-1">
                                                            <small class="text-muted">未登録</small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <!-- オンライン実施の場合：交通費対象外 -->
                                                <span class="text-muted" title="オンライン実施のため交通費対象外">
                                                    <i class="fas fa-laptop"></i>
                                                    <div class="mt-1">
                                                        <small>対象外</small>
                                                        <div class="small text-info">
                                                            <i class="fas fa-wifi me-1"></i>オンライン
                                                        </div>
                                                    </div>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- 進行中の役務の場合 -->
                                            <span class="text-muted" title="役務完了後に登録可能">
                                                <i class="fas fa-clock"></i>
                                                <div class="mt-1">
                                                    <small>完了後</small>
                                                </div>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- 時間なし役務の場合は交通費対象外 -->
                                        <span class="text-muted" title="時間なし役務は交通費対象外">
                                            <i class="fas fa-minus-circle"></i>
                                            <div class="mt-1">
                                                <small>対象外</small>
                                            </div>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if (!empty($record['description'])): ?>
                                        <div class="description-preview">
                                            <?php
                                            $description = $record['description'];
                                            // 自動分割記録の場合、元の内容部分のみを表示
                                            if ($isAutoSplit && strpos($description, '元の内容:') !== false) {
                                                preg_match('/元の内容:\s*(.+?)$/s', $description, $matches);
                                                $description = $matches[1] ?? $description;
                                            }
                                            $shortDesc = mb_strlen($description) > 50 ? 
                                                        mb_substr($description, 0, 50) . '...' : $description;
                                            ?>
                                            <span data-bs-toggle="tooltip" 
                                                  title="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= nl2br(htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8')) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <?php if (!$requiresTimeTracking): ?>
                                            <!-- 時間なし役務で業務内容が空の場合は警告 -->
                                            <span class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                記載なし
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">記載なし</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- 延長理由がある場合 -->
                                    <?php if (!empty($record['overtime_reason'])): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>延長理由あり
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <div class="btn-group-vertical" role="group">
                                        <!-- 詳細表示 -->
                                        <a href="<?= base_url("service_records/{$record['id']}") ?>" 
                                           class="btn btn-outline-primary btn-sm mb-1">
                                            <i class="fas fa-eye me-1"></i>詳細
                                        </a>
                                        
                                        <?php if ($record['status'] === 'pending' || $record['status'] === 'rejected'): ?>
                                            <!-- 編集（承認待ち・差戻しのみ） -->
                                            <?php if (!$isActive): ?>
                                                <a href="<?= base_url("service_records/{$record['id']}/edit") ?>" 
                                                   class="btn btn-outline-secondary btn-sm mb-1">
                                                    <i class="fas fa-edit me-1"></i>編集
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- 削除（承認待ちのみ） -->
                                            <form method="POST" action="<?= base_url("service_records/{$record['id']}/delete") ?>" 
                                                style="display: inline;" 
                                                class="delete-form"
                                                data-record-id="<?= $record['id'] ?>">
                                                
                                                <!-- CSRFトークンを確実に生成 -->
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="_method" value="DELETE">
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm delete-btn" 
                                                        id="delete-btn-<?= $record['id'] ?>"
                                                        data-record='<?= htmlspecialchars(json_encode([
                                                            'id' => $record['id'],
                                                            'company_name' => $record['company_name'] ?? '不明',
                                                            'branch_name' => $record['branch_name'] ?? '不明',
                                                            'service_date' => format_date($record['service_date']),
                                                            'service_type' => get_service_type_label($record['service_type'] ?? 'regular'),
                                                            'visit_type' => $requiresVisitType ? get_visit_type_label($record['visit_type'] ?? 'visit') : null,
                                                            'service_hours' => $requiresTimeTracking ? format_total_hours($record['service_hours']) : null,
                                                            'is_auto_split' => $isAutoSplit
                                                        ]), ENT_QUOTES, 'UTF-8') ?>'>
                                                    <i class="fas fa-trash me-1"></i>削除
                                                </button>
                                            </form>
                                            
                                        <?php elseif ($record['status'] === 'approved'): ?>
                                            <!-- 承認済み -->
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>承認済み
                                            </span>
                                            
                                        <?php elseif ($record['status'] === 'rejected'): ?>
                                            <!-- 差戻し後の再編集 -->
                                            <a href="<?= base_url("service_records/{$record['id']}/edit") ?>" 
                                               class="btn btn-warning btn-sm mb-1">
                                                <i class="fas fa-redo me-1"></i>修正
                                            </a>
                                            <form method="POST" action="<?= base_url("service_records/{$record['id']}/delete") ?>" 
                                                style="display: inline;" 
                                                class="delete-form"
                                                data-record-id="<?= $record['id'] ?>">
                                                
                                                <!-- CSRFトークンを確実に生成 -->
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="_method" value="DELETE">
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm delete-btn" 
                                                        id="delete-btn-<?= $record['id'] ?>"
                                                        data-record='<?= htmlspecialchars(json_encode([
                                                            'id' => $record['id'],
                                                            'company_name' => $record['company_name'] ?? '不明',
                                                            'branch_name' => $record['branch_name'] ?? '不明',
                                                            'service_date' => format_date($record['service_date']),
                                                            'service_type' => get_service_type_label($record['service_type'] ?? 'regular'),
                                                            'visit_type' => $requiresVisitType ? get_visit_type_label($record['visit_type'] ?? 'visit') : null,
                                                            'service_hours' => $requiresTimeTracking ? format_total_hours($record['service_hours']) : null,
                                                            'is_auto_split' => $isAutoSplit,
                                                            'status' => $record['status'] // ステータスを追加
                                                        ]), ENT_QUOTES, 'UTF-8') ?>'>
                                                    <i class="fas fa-trash me-1"></i>削除
                                                </button>
                                            </form>
                                        <?php elseif ($record['status'] === 'finalized'): ?>
                                            <!-- 締済み -->
                                            <span class="badge bg-dark">
                                                <i class="fas fa-lock me-1"></i>締済み
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- モバイル表示（1400px未満） -->
            <div class="d-xl-none">
                <?php foreach ($records as $record): ?>
                    <?php
                    // 役務種別情報（拡張版）
                    $serviceTypeInfo = [
                        'regular' => ['class' => 'success', 'icon' => 'calendar-check', 'label' => '定期'],
                        'emergency' => ['class' => 'warning', 'icon' => 'exclamation-triangle', 'label' => '臨時'],
                        'extension' => ['class' => 'info', 'icon' => 'clock', 'label' => '延長'],
                        'spot' => ['class' => 'info', 'icon' => 'clock', 'label' => 'スポット'],
                        'document' => ['class' => 'primary', 'icon' => 'file-alt', 'label' => '書面'],
                        'remote_consultation' => ['class' => 'secondary', 'icon' => 'envelope', 'label' => '遠隔'],
                        'other' => ['class' => 'dark', 'icon' => 'plus', 'label' => 'その他']
                    ];
                    $serviceType = $serviceTypeInfo[$record['service_type'] ?? 'regular'];
                    
                    // 訪問種別情報
                    $visitTypeInfo = [
                        'visit' => ['class' => 'primary', 'icon' => 'walking', 'label' => '訪問'],
                        'online' => ['class' => 'info', 'icon' => 'laptop', 'label' => 'オンライン']
                    ];
                    $visitType = $visitTypeInfo[$record['visit_type'] ?? 'visit'];
                    
                    // ステータス情報
                    $statusInfo = [
                        'pending' => ['class' => 'warning', 'label' => '承認待ち', 'icon' => 'clock'],
                        'approved' => ['class' => 'success', 'label' => '承認済み', 'icon' => 'check-circle'],
                        'rejected' => ['class' => 'danger', 'label' => '差戻し', 'icon' => 'times-circle'],
                        'finalized' => ['class' => 'dark', 'label' => '締済み', 'icon' => 'lock']
                    ];
                    $status = $statusInfo[$record['status'] ?? 'pending'];
                    
                    // 時間管理が必要な役務種別かチェック
                    $requiresTimeTracking = in_array($record['service_type'] ?? 'regular', ['regular', 'emergency', 'extension', 'spot']);
                    
                    // 訪問種別が選択可能な役務種別かチェック
                    $requiresVisitType = requires_visit_type_selection($record['service_type'] ?? 'regular');
                    
                    // 自動分割記録かどうかの判定
                    $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                                  strpos($record['description'] ?? '', '自動分割') !== false ||
                                  strpos($record['description'] ?? '', '延長分') !== false;
                    
                    // 進行中判定（時間管理が必要な役務のみ）
                    $isActive = $requiresTimeTracking && $record['service_hours'] == 0;
                    
                    // 交通費情報を取得（訪問種別が「訪問」で時間管理が必要で完了した役務のみ）
                    $travelExpenseInfo = null;
                    if ($requiresTimeTracking && $record['service_hours'] > 0 && ($record['visit_type'] ?? 'visit') === 'visit') {
                        require_once __DIR__ . '/../../models/TravelExpense.php';
                        $travelExpenseModel = new TravelExpense();
                        $travelExpenses = $travelExpenseModel->findByServiceRecord($record['id']);
                        $travelExpenseInfo = [
                            'has_expenses' => !empty($travelExpenses),
                            'count' => count($travelExpenses),
                            'total_amount' => array_sum(array_column($travelExpenses, 'amount')),
                            'pending_count' => count(array_filter($travelExpenses, function($e) { return $e['status'] === 'pending'; })),
                            'approved_count' => count(array_filter($travelExpenses, function($e) { return $e['status'] === 'approved'; })),
                            'rejected_count' => count(array_filter($travelExpenses, function($e) { return $e['status'] === 'rejected'; }))
                        ];
                    }
                    ?>
                    
                    <div class="card mobile-service-record-card mb-3">
                        <div class="card-body">
                            <!-- ヘッダー：企業名・ステータス -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-building me-2 text-success"></i>
                                        <?= htmlspecialchars($record['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </h6>
                                    <p class="card-subtitle text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                        <?= htmlspecialchars($record['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                
                                <!-- ステータスバッジ -->
                                <div class="ms-2">
                                    <span class="badge bg-<?= $status['class'] ?>">
                                        <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- 基本情報 -->
                            <div class="row g-2 mb-3">
                                <!-- 日付 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">役務日</small>
                                        <span class="fw-bold"><?= format_date($record['service_date']) ?></span>
                                        <div class="small text-muted">
                                            <?= date('w', strtotime($record['service_date'])) == 0 ? '日曜日' : 
                                               (date('w', strtotime($record['service_date'])) == 6 ? '土曜日' : '平日') ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 役務種別 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">役務種別</small>
                                        <span class="badge bg-<?= $serviceType['class'] ?>">
                                            <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                            <?= $serviceType['label'] ?>
                                        </span>
                                        
                                        <!-- 自動分割記録のマーク -->
                                        <?php if ($record['service_type'] === 'extension' && $isAutoSplit): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-secondary" title="自動分割により作成された記録">
                                                    <i class="fas fa-scissors me-1"></i>分割
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- 実施方法 -->
                                <?php if ($requiresVisitType): ?>
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">実施方法</small>
                                        <span class="badge bg-<?= $visitType['class'] ?>">
                                            <i class="fas fa-<?= $visitType['icon'] ?> me-1"></i>
                                            <?= $visitType['label'] ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- 役務時間 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">役務時間</small>
                                        <?php if ($requiresTimeTracking): ?>
                                            <?php if (!$isActive): ?>
                                                <span class="badge bg-primary fs-6">
                                                    <?= format_total_hours($record['service_hours']) ?>
                                                </span>
                                                
                                            <?php else: ?>
                                                <!-- 進行中の役務 -->
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-spinner fa-spin me-1"></i>進行中
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- 時間なし役務の場合 -->
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-minus me-1"></i>時間なし
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 実施時間（時間管理ありの場合のみ表示） -->
                            <?php if ($requiresTimeTracking && !$isActive): ?>
                                <div class="mobile-info-item mb-3">
                                    <small class="text-muted d-block">実施時間</small>
                                    <small class="text-dark"><?= format_service_time_display($record) ?></small>
                                    
                                    <!-- 自動分割記録の場合の追加情報 -->
                                    <?php if ($isAutoSplit): ?>
                                        <div class="small text-info mt-1">
                                            <i class="fas fa-info-circle me-1"></i>分割記録
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 業務内容 -->
                            <?php if (!empty($record['description'])): ?>
                                <div class="mobile-info-item mb-3">
                                    <small class="text-muted d-block">業務内容</small>
                                    <?php
                                    $description = $record['description'];
                                    // 自動分割記録の場合、元の内容部分のみを表示
                                    if ($isAutoSplit && strpos($description, '元の内容:') !== false) {
                                        preg_match('/元の内容:\s*(.+?)$/s', $description, $matches);
                                        $description = $matches[1] ?? $description;
                                    }
                                    $shortDesc = mb_strlen($description) > 80 ? 
                                                mb_substr($description, 0, 80) . '...' : $description;
                                    ?>
                                    <div class="description-text">
                                        <?= nl2br(htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8')) ?>
                                        <?php if (mb_strlen($description) > 80): ?>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 description-toggle" 
                                                    data-full-description="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
                                                <small>続きを読む</small>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if (!$requiresTimeTracking): ?>
                                    <!-- 時間なし役務で業務内容が空の場合は警告 -->
                                    <div class="mobile-info-item mb-3">
                                        <small class="text-muted d-block">業務内容</small>
                                        <span class="text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>記載なし
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- 交通費情報 -->
                            <?php if ($requiresTimeTracking && !$isActive && ($record['visit_type'] ?? 'visit') === 'visit'): ?>
                                <div class="mobile-info-item mb-3">
                                    <small class="text-muted d-block">交通費</small>
                                    
                                    <!-- 追加ボタン -->
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <?php if ($record['status'] !== 'finalized'): ?>
                                            <a href="<?= base_url("travel_expenses/create/{$record['id']}") ?>" 
                                            class="btn btn-sm btn-success">
                                                <i class="fas fa-plus me-1"></i>追加
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-sm btn-secondary disabled">
                                                <i class="fas fa-lock me-1"></i>追加不可
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($travelExpenseInfo && $travelExpenseInfo['has_expenses']): ?>
                                            <!-- 登録済み情報 -->
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <small class="text-success fw-bold">
                                                    <?= $travelExpenseInfo['count'] ?>件登録
                                                </small>
                                                <small class="text-muted">
                                                    (<?= format_amount($travelExpenseInfo['total_amount']) ?>)
                                                </small>
                                                
                                                <!-- ステータス別バッジ -->
                                                <?php if ($travelExpenseInfo['approved_count'] > 0): ?>
                                                    <span class="badge bg-success" title="承認済み">
                                                        <i class="fas fa-check"></i><?= $travelExpenseInfo['approved_count'] ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($travelExpenseInfo['pending_count'] > 0): ?>
                                                    <span class="badge bg-warning" title="承認待ち">
                                                        <i class="fas fa-clock"></i><?= $travelExpenseInfo['pending_count'] ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($travelExpenseInfo['rejected_count'] > 0): ?>
                                                    <span class="badge bg-danger" title="差戻し">
                                                        <i class="fas fa-times"></i><?= $travelExpenseInfo['rejected_count'] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- 管理ボタン -->
                                            <div class="mt-2">
                                                <?php if ($travelExpenseInfo['count'] == 1): ?>
                                                    <?php
                                                    $travelExpenses = $travelExpenseModel->findByServiceRecord($record['id']);
                                                    $singleExpense = $travelExpenses[0];
                                                    ?>
                                                    <a href="<?= base_url("travel_expenses/{$singleExpense['id']}/edit") ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-edit me-1"></i>編集
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-info" 
                                                            onclick="showTravelExpensesList(<?= $record['id'] ?>, '<?= $record['status'] ?>')">
                                                        <i class="fas fa-list me-1"></i>一覧
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">未登録</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php elseif ($requiresTimeTracking && !$isActive && ($record['visit_type'] ?? 'visit') === 'online'): ?>
                                <div class="mobile-info-item mb-3">
                                    <small class="text-muted d-block">交通費</small>
                                    <span class="text-muted">
                                        <i class="fas fa-laptop me-1"></i>オンライン実施のため対象外
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 操作ボタン -->
                            <div class="d-grid gap-2">
                                <div class="d-flex gap-2">
                                    <!-- 詳細表示 -->
                                    <a href="<?= base_url("service_records/{$record['id']}") ?>" 
                                       class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-eye me-1"></i>詳細
                                    </a>
                                    
                                    <?php if ($record['status'] === 'pending' || $record['status'] === 'rejected'): ?>
                                        <!-- 編集（承認待ち・差戻しのみ） -->
                                        <?php if (!$isActive): ?>
                                            <a href="<?= base_url("service_records/{$record['id']}/edit") ?>" 
                                               class="btn btn-outline-secondary btn-sm flex-fill">
                                                <i class="fas fa-edit me-1"></i>編集
                                            </a>
                                        <?php endif; ?>
                                        
                                    <?php elseif ($record['status'] === 'rejected'): ?>
                                        <!-- 差戻し後の再編集 -->
                                        <a href="<?= base_url("service_records/{$record['id']}/edit") ?>" 
                                           class="btn btn-warning btn-sm flex-fill">
                                            <i class="fas fa-redo me-1"></i>修正
                                        </a>
                                    <?php elseif ($record['status'] === 'finalized'): ?>
                                        <!-- 締済み（編集・削除不可） -->
                                        <div class="text-center">
                                            <span class="badge bg-dark fs-6">
                                                <i class="fas fa-lock me-1"></i>締済み完了
                                            </span>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    この記録は締済み済みのため、編集・削除はできません。
                                                </small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($record['status'] === 'pending' || $record['status'] === 'rejected'): ?>
                                    <!-- 削除ボタン（承認待ち・差戻しのみ） -->
                                    <form method="POST" action="<?= base_url("service_records/{$record['id']}/delete") ?>" 
                                        class="delete-form"
                                        data-record-id="<?= $record['id'] ?>">
                                        
                                        <!-- CSRFトークンを確実に生成 -->
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="_method" value="DELETE">
                                        
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm w-100 delete-btn" 
                                                id="delete-btn-<?= $record['id'] ?>"
                                                data-record='<?= htmlspecialchars(json_encode([
                                                    'id' => $record['id'],
                                                    'company_name' => $record['company_name'] ?? '不明',
                                                    'branch_name' => $record['branch_name'] ?? '不明',
                                                    'service_date' => format_date($record['service_date']),
                                                    'service_type' => get_service_type_label($record['service_type'] ?? 'regular'),
                                                    'visit_type' => $requiresVisitType ? get_visit_type_label($record['visit_type'] ?? 'visit') : null,
                                                    'service_hours' => $requiresTimeTracking ? format_total_hours($record['service_hours']) : null,
                                                    'is_auto_split' => $isAutoSplit
                                                ]), ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="fas fa-trash me-1"></i>削除
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 差戻し理由（ある場合） -->
                            <?php if ($record['status'] === 'rejected' && !empty($record['rejection_reason'])): ?>
                                <div class="mt-3 alert alert-danger">
                                    <small class="fw-bold">差戻し理由:</small>
                                    <div class="mt-1">
                                        <?= nl2br(htmlspecialchars($record['rejection_reason'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- ページネーション：契約一覧と同じUI -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="d-none d-md-block">
                        <small class="text-muted">
                            <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_records'])) ?>件 
                            (全<?= number_format($pagination['total_records']) ?>件中)
                        </small>
                    </div>
                    
                    <nav aria-label="役務記録一覧ページネーション">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- 前のページ -->
                            <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link service-records-pagination-link" 
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
                                    <a class="page-link service-records-pagination-link" 
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
                                    <a class="page-link service-records-pagination-link" 
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
                                    <a class="page-link service-records-pagination-link" 
                                       href="#" 
                                       data-page="<?= $pagination['total_pages'] ?>"
                                       data-page-size="<?= $pagination['per_page'] ?>"><?= $pagination['total_pages'] ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- 次のページ -->
                            <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                                <a class="page-link service-records-pagination-link" 
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
            
            <!-- 凡例 -->
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>役務種別:</strong>
                            <span class="badge bg-success me-1">定期</span>
                            <span class="badge bg-warning me-1">臨時</span>
                            <?php if (in_array('document', $availableServiceTypes ?? [])): ?>
                            <span class="badge bg-primary me-1">書面</span>
                            <?php endif; ?>
                            <?php if (in_array('remote_consultation', $availableServiceTypes ?? [])): ?>
                            <span class="badge bg-secondary me-1">遠隔</span>
                            <?php endif; ?>
                            <span class="badge bg-dark me-1">その他</span>
                        </small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">
                            <strong>ステータス:</strong>
                            <span class="badge bg-warning me-1">承認待ち</span>
                            <span class="badge bg-success me-1">承認済み</span>
                            <span class="badge bg-danger me-1">差戻し</span>
                            <span class="badge bg-dark me-1">締済み</span>
                        </small>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-walking me-1"></i>
                            <strong>実施方法:</strong>
                            <span class="badge bg-primary me-1">訪問</span>
                            <span class="badge bg-info me-1">オンライン</span>
                        </small>
                    </div>
               </div>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- 交通費一覧モーダル -->
<div class="modal fade" id="travelExpensesModal" tabindex="-1" aria-labelledby="travelExpensesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="travelExpensesModalLabel">
                    <i class="fas fa-train me-2"></i>登録済み交通費一覧
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="travelExpensesModalBody">
                <!-- Ajax で読み込まれる内容 -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">読み込み中...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
                <a href="#" id="addTravelExpenseLink" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>交通費を追加
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* ========== 基本スタイル ========== */

/* ヘッダーセクション */
.header-section {
    margin-bottom: 1.5rem;
}

.header-buttons {
    display: flex;
    gap: 0.5rem;
}

/* フィルターフォーム */
.filter-form {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
}

.filter-buttons {
    display: flex;
    gap: 0.5rem;
}

.filter-buttons .btn {
    white-space: nowrap;
}

/* ページネーション用スタイル（契約版と統一） */
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

/* モバイル役務記録カード */
.mobile-service-record-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-service-record-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.mobile-service-record-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

.mobile-service-record-card .card-subtitle {
    font-size: 0.875rem;
}

/* モバイル情報項目 */
.mobile-info-item {
    padding: 0.25rem 0;
}

.mobile-info-item small {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

/* 業務内容の展開機能 */
.description-text {
    word-wrap: break-word;
    line-height: 1.4;
}

.description-toggle {
    color: #007bff;
    text-decoration: none;
    font-size: 0.8rem;
}

.description-toggle:hover {
    text-decoration: underline;
}

/* ========== レスポンシブ対応 ========== */

/* 1400px以下でモバイル表示に切り替え */
@media (max-width: 1399px) {
    /* ヘッダー調整 */
    .header-section .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .header-section h2 {
        text-align: center;
        margin-bottom: 0;
        font-size: 1.5rem;
    }
    
    .header-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .header-buttons .btn {
        flex: 1;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* フィルターフォームの調整 */
    .filter-form .row {
        gap: 0.75rem;
    }
    
    .filter-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-buttons .btn {
        width: 100%;
    }
    
    .filter-buttons .btn .btn-text {
        margin-left: 0.5rem;
    }
}

/* タブレット表示（768px-1399px） */
@media (min-width: 768px) and (max-width: 1399px) {
    /* ヘッダーのタブレット調整 */
    .header-section .d-flex {
        flex-direction: row;
        align-items: center !important;
        gap: 1rem;
    }
    
    .header-section h2 {
        text-align: left;
        margin-bottom: 0;
        font-size: 1.5rem;
        flex-grow: 1;
    }
    
    .header-buttons {
        flex-shrink: 0;
    }
    
    .header-buttons .btn {
        flex: none;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* フィルターボタンは横並び */
    .filter-buttons {
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .filter-buttons .btn {
        width: auto;
        flex: 1;
    }
}

/* モバイル表示（768px未満） */
@media (max-width: 767px) {
    /* ヘッダー調整 */
    .header-section .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .header-section h2 {
        text-align: center;
        margin-bottom: 0;
        font-size: 1.5rem;
    }
    
    .header-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .header-buttons .btn {
        flex: 1;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* フィルターフォームの調整 */
    .filter-form .row {
        gap: 0.75rem;
    }
    
    .filter-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-buttons .btn {
        width: 100%;
    }
    
    .filter-buttons .btn .btn-text {
        margin-left: 0.5rem;
    }
    
    /* モバイル役務記録カードの調整 */
    .mobile-service-record-card .card-body {
        padding: 1rem;
    }
    
    .mobile-info-item {
        padding: 0.25rem 0;
    }
    
    .mobile-info-item small {
        font-size: 0.75rem;
    }
    
    /* カードヘッダーの調整 */
    .card-header h5 {
        font-size: 1rem;
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

/* 小型モバイル表示（576px未満） */
@media (max-width: 575px) {
    /* ヘッダーのさらなる調整 */
    .header-section h2 {
        font-size: 1.25rem;
    }
    
    .header-buttons .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
    
    /* フィルターの調整 */
    .filter-form {
        padding: 0.75rem;
    }
    
    .filter-form .col-6 {
        margin-bottom: 0.5rem;
    }
    
    /* モバイルカードの詳細調整 */
    .mobile-service-record-card .card-body {
        padding: 0.75rem;
    }
    
    .mobile-service-record-card .card-title {
        font-size: 0.9rem;
    }
    
    .mobile-service-record-card .card-subtitle {
        font-size: 0.8rem;
    }
    
    /* ボタンの調整 */
    .mobile-service-record-card .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
}

/* 極小モバイル表示（400px未満） */
@media (max-width: 399px) {
    /* 最小限の表示に調整 */
    .header-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .mobile-service-record-card .card-body {
        padding: 0.5rem;
    }
    
    .mobile-service-record-card .card-title {
        font-size: 0.85rem;
    }
    
    .mobile-service-record-card .btn {
        font-size: 0.75rem;
        padding: 0.4rem;
    }
}

/* ========== その他のスタイル ========== */

/* バッジのスタイル */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}

.badge.fs-6 {
    font-size: 1rem !important;
    padding: 0.5em 0.75em;
}

/* 業務内容プレビュー */
.description-preview {
    max-width: 200px;
    word-wrap: break-word;
}

/* ボタングループ */
.btn-group-vertical .btn {
    margin-bottom: 2px;
}

.btn-group-vertical .btn:last-child {
    margin-bottom: 0;
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

/* 交通費列のスタイリング */
.travel-expense-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    min-height: 80px;
    justify-content: flex-start;
}

.travel-expense-info .btn-success {
    background-color: #28a745;
    border-color: #28a745;
    padding: 0.3rem 0.6rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.travel-expense-info .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.registered-expenses-info {
    width: 100%;
    text-align: center;
}

.registered-expenses-info small {
    line-height: 1.3;
}

.registered-expenses-info .fw-bold {
    font-weight: 600 !important;
}

.travel-expense-info .badge {
    font-size: 0.65rem;
    padding: 0.2em 0.4em;
    margin: 0 0.1rem;
}

.travel-expense-info .d-flex.gap-1 {
    gap: 0.3rem !important;
    flex-wrap: wrap;
    justify-content: center;
}

.registered-expenses-info .btn-outline-info {
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    border-width: 1px;
}

.registered-expenses-info .btn-outline-info:hover {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

/* 企業・拠点情報の表示調整 */
.company-branch-info {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.company-branch-info .fw-bold {
    font-size: 0.9em;
    margin-bottom: 0.2rem;
}

.company-branch-info .small {
    font-size: 0.8em;
    color: #6c757d;
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

/* アクセシビリティ向上 */
@media (max-width: 767px) {
    /* タッチターゲットのサイズを44px以上に */
    .mobile-service-record-card .btn,
    .mobile-service-record-card .badge {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .mobile-service-record-card .badge {
        min-height: 32px; /* バッジは少し小さめでも可 */
    }
}

/* ボタンの統一スタイル */
.btn {
    transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* モーダルのスタイル */
@media (max-width: 768px) {
    #travelExpensesModal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}

@media (max-width: 576px) {
    #travelExpensesModal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}

@media (max-width: 400px) {
    #travelExpensesModal .modal-dialog {
        margin: 0.25rem;
        max-width: calc(100% - 0.5rem);
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

/* 並び替えリンクのスタイル */
.sort-link {
    color: inherit; /* 通常のテキスト色を維持 */
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 600; /* 太字にして目立たせる */
}

.sort-link:hover {
    color: inherit; /* ホバー時も色は変えない */
    text-decoration: none;
    transform: translateX(2px); /* わずかに右に移動 */
}

.sort-link i {
    font-size: 0.875rem;
    opacity: 1; /* 常に表示 */
    transition: transform 0.2s ease;
    color: #6c757d; /* アイコンは灰色 */
}

.sort-link:hover i {
    transform: scale(1.2); /* ホバー時にアイコンを拡大 */
    color: #495057; /* ホバー時は少し濃い灰色 */
}

/* アクティブな並び替え列を強調 */
th:has(.sort-link) {
    background-color: #f8f9fa;
    position: relative;
}

/* 並び替えアイコンがない列にもアイコンを表示（薄く） */
.table-light th a.sort-link:not(:has(i))::after {
    content: '\f0dc'; /* Font Awesome の sort アイコン */
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: 0.25rem;
    opacity: 0.3;
    font-size: 0.75rem;
}

/* 現在の並び替え列をハイライト */
.table-light th:has(.sort-link i) {
    background-color: #e7f1ff; /* 薄い青色でハイライト */
}

.table-light th a.sort-link {
    font-weight: 600;
}

@media (max-width: 767px) {
    .sort-link {
        font-size: 0.875rem;
    }
}
</style>

<script>
// 年月変更時に契約リストを更新
document.addEventListener('DOMContentLoaded', function() {
    const yearMonthInput = document.getElementById('year_month');
    const contractSelect = document.getElementById('contract_id');
    
    if (yearMonthInput && contractSelect) {
        yearMonthInput.addEventListener('change', function() {
            const selectedYearMonth = this.value;
            const currentContractId = contractSelect.value;
            
            // ローディング表示
            contractSelect.disabled = true;
            contractSelect.innerHTML = '<option value="">読み込み中...</option>';
            
            // Ajax で契約リストを取得
            fetch(`<?= base_url('api/service_records/contracts_for_month?year_month=') ?>${selectedYearMonth}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 契約リストを更新
                        let options = '<option value="">全ての契約</option>';
                        data.contracts.forEach(contract => {
                            const selected = (contract.contract_id == currentContractId) ? ' selected' : '';
                            options += `<option value="${contract.contract_id}"${selected}>` +
                                      `${escapeHtml(contract.company_name)} - ${escapeHtml(contract.branch_name)}` +
                                      `</option>`;
                        });
                        contractSelect.innerHTML = options;
                        contractSelect.disabled = false;
                    } else {
                        contractSelect.innerHTML = '<option value="">契約の取得に失敗しました</option>';
                        contractSelect.disabled = false;
                        console.error('契約取得エラー:', data.error);
                    }
                })
                .catch(error => {
                    console.error('システムエラー:', error);
                    contractSelect.innerHTML = '<option value="">エラーが発生しました</option>';
                    contractSelect.disabled = false;
                });
        });
    }
});

// HTML エスケープ用ヘルパー関数
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 交通費一覧を表示する関数
function showTravelExpensesList(serviceRecordId, serviceRecordStatus) {
    const modal = new bootstrap.Modal(document.getElementById('travelExpensesModal'));
    const modalBody = document.getElementById('travelExpensesModalBody');
    const addLink = document.getElementById('addTravelExpenseLink');
    
    // 追加リンクを設定
    addLink.href = `<?= base_url('travel_expenses/create/') ?>${serviceRecordId}`;
    
    // モーダルを表示
    modal.show();
    
    // Ajax で交通費一覧を取得
    fetch(`<?= base_url('api/travel_expenses/service_record/') ?>${serviceRecordId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTravelExpensesList(data.data, serviceRecordStatus);
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        交通費情報の取得に失敗しました: ${data.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    システムエラーが発生しました。
                </div>
            `;
        });
}

// 交通費一覧を表示する関数（モバイル最適化版）
function displayTravelExpensesList(expenses, serviceRecordStatus) {
    const modalBody = document.getElementById('travelExpensesModalBody');
    
    if (expenses.length === 0) {
        modalBody.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                登録済みの交通費はありません。
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover travel-expenses-table">
                <thead class="table-light">
                    <tr>
                        <th>交通手段</th>
                        <th>区間</th>
                        <th class="d-none d-md-table-cell">往復/片道</th>
                        <th class="text-end">金額</th>
                        <th class="text-center d-none d-sm-table-cell">ステータス</th>
                        <th class="text-center travel-expenses-operations">操作</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let totalAmount = 0;
    
    expenses.forEach(expense => {
        const statusInfo = {
            'pending': { class: 'warning', label: '承認待ち', icon: 'clock' },
            'approved': { class: 'success', label: '承認済み', icon: 'check-circle' },
            'rejected': { class: 'danger', label: '差戻し', icon: 'times-circle' },
            'finalized': { class: 'dark', label: '締済み', icon: 'lock' }
        };
        
        const status = statusInfo[expense.status] || statusInfo['pending'];
        const transportTypeLabels = {
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
        
        const transportLabel = transportTypeLabels[expense.transport_type] || expense.transport_type;
        
        // trip_typeがNULLの場合は空文字列を設定
        let tripTypeLabel = '';
        if (expense.trip_type === 'round_trip') {
            tripTypeLabel = '往復';
        } else if (expense.trip_type === 'one_way') {
            tripTypeLabel = '片道';
        }
        
        totalAmount += parseInt(expense.amount);
        
        // departure_pointがNULLの場合とそうでない場合で区間表示を分岐
        let routeDisplayFull = '';
        let routeDisplayMobile = '';
        let routeTitle = '';
        
        if (expense.departure_point) {
            // departure_pointがある場合は「出発地 → 到着地」形式
            const departureShort = expense.departure_point.length > 8 ? 
                                   expense.departure_point.substring(0, 6) + '...' : 
                                   expense.departure_point;
            const arrivalShort = expense.arrival_point.length > 8 ? 
                                 expense.arrival_point.substring(0, 6) + '...' : 
                                 expense.arrival_point;
            
            routeDisplayFull = `${expense.departure_point} → ${expense.arrival_point}`;
            routeDisplayMobile = `${departureShort}<br>↓<br>${arrivalShort}`;
            routeTitle = routeDisplayFull;
        } else {
            // departure_pointがNULLの場合は到着地のみ表示
            const arrivalShort = expense.arrival_point.length > 8 ? 
                                 expense.arrival_point.substring(0, 6) + '...' : 
                                 expense.arrival_point;
            
            routeDisplayFull = expense.arrival_point;
            routeDisplayMobile = arrivalShort;
            routeTitle = expense.arrival_point;
        }
        
        html += `
            <tr>
                <td>
                    <div class="d-flex flex-column align-items-center">
                        <i class="fas fa-${getTransportIcon(expense.transport_type)} me-1"></i>
                        <small class="d-block d-sm-none">${transportLabel}</small>
                        <span class="d-none d-sm-inline">${transportLabel}</span>
                    </div>
                </td>
                <td>
                    <div class="route-display">
                        <span class="d-none d-md-inline">${routeDisplayFull}</span>
                        <span class="d-block d-md-none" title="${routeTitle}">
                            ${routeDisplayMobile}
                        </span>
                    </div>
                </td>
                <td class="d-none d-md-table-cell">
                    ${tripTypeLabel ? `<span class="badge bg-${expense.trip_type === 'round_trip' ? 'primary' : 'secondary'}">${tripTypeLabel}</span>` : ''}
                </td>
                <td class="text-end">
                    <strong>¥${parseInt(expense.amount).toLocaleString()}</strong>
                    <div class="d-block d-md-none mt-1">
                        ${tripTypeLabel ? `<span class="badge bg-${expense.trip_type === 'round_trip' ? 'primary' : 'secondary'}" style="font-size: 0.6rem;">${tripTypeLabel}</span>` : ''}
                    </div>
                </td>
                <td class="text-center d-none d-sm-table-cell">
                    <span class="badge bg-${status.class}">
                        <i class="fas fa-${status.icon} me-1"></i>
                        <span class="d-none d-md-inline">${status.label}</span>
                        <span class="d-block d-md-none">${status.label.substring(0, 2)}</span>
                    </span>
                </td>
                <td class="text-center travel-expenses-operations-cell">
                    <div class="btn-group-vertical travel-expenses-btn-group" role="group">
                        <a href="<?= base_url('travel_expenses/') ?>${expense.id}" 
                           class="btn btn-outline-primary btn-sm mb-1 travel-expense-btn"
                           target="_blank"
                           title="詳細を表示">
                            <i class="fas fa-eye me-1"></i>
                            <span class="btn-text d-none d-sm-inline">詳細</span>
                        </a>
                        ${(expense.status === 'pending' || expense.status === 'rejected') && serviceRecordStatus !== 'finalized' ? `
                            <a href="<?= base_url('travel_expenses/') ?>${expense.id}/edit" 
                            class="btn btn-outline-secondary btn-sm travel-expense-btn"
                            target="_blank"
                            title="編集">
                                <i class="fas fa-edit me-1"></i>
                                <span class="btn-text d-none d-sm-inline">編集</span>
                            </a>
                        ` : serviceRecordStatus === 'finalized' ? `
                            <span class="btn btn-secondary btn-sm disabled" title="締め処理済みのため編集不可">
                                <i class="fas fa-lock me-1"></i>
                                <span class="btn-text d-none d-sm-inline">編集不可</span>
                            </span>
                        ` : ''}
                        
                        <!-- 極小画面用：ステータス表示 -->
                        <div class="d-block d-sm-none mt-1 mobile-status">
                            <span class="badge bg-${status.class}" style="font-size: 0.6rem;">
                                <i class="fas fa-${status.icon} me-1"></i>
                                ${status.label}
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr class="travel-expenses-total-row">
                        <th colspan="3" class="d-none d-md-table-cell travel-expenses-total-label">合計</th>
                        <th class="d-none d-sm-table-cell d-md-none travel-expenses-total-label">合計</th>
                        <th class="d-block d-sm-none travel-expenses-total-label">合計</th>
                        <th class="text-end travel-expenses-total-amount">
                            <strong>¥${totalAmount.toLocaleString()}</strong>
                        </th>
                        <th class="travel-expenses-total-operations"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- モバイル用の追加情報 -->
        <div class="d-block d-md-none mt-3">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>凡例:</strong>
                            <span class="badge bg-success me-1">承認済み</span>
                            <span class="badge bg-warning me-1">承認待ち</span>
                            <span class="badge bg-danger me-1">差戻し</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = html;
    
    // モバイル用のタッチイベント追加
    addMobileTouchEvents();
}

// 交通手段のアイコンを取得
function getTransportIcon(transportType) {
    const icons = {
        'train': 'train',
        'bus': 'bus',
        'taxi': 'taxi',
        'gasoline': 'car',
        'highway_toll': 'car',
        'parking': 'car',
        'rental_car': 'car',
        'airplane': 'plane',
        'other': 'question-circle'
    };
    return icons[transportType] || 'question-circle';
}

// モバイル用のタッチイベントを追加
function addMobileTouchEvents() {
    // 区間表示のタップで完全な文字列を表示
    document.querySelectorAll('.route-display .d-block.d-md-none').forEach(element => {
        element.addEventListener('click', function() {
            const fullText = this.getAttribute('title');
            if (fullText) {
                showToast(fullText);
            }
        });
    });
    
    // 行タップでハイライト効果
    document.querySelectorAll('#travelExpensesModal tbody tr').forEach(row => {
        row.addEventListener('touchstart', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.backgroundColor = '';
            }, 300);
        });
    });
}

// 簡易トースト表示機能
function showToast(message) {
    // 既存のトーストがあれば削除
    const existingToast = document.querySelector('.mobile-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = 'mobile-toast';
    toast.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        z-index: 10000;
        font-size: 0.9rem;
        max-width: 90%;
        text-align: center;
        word-wrap: break-word;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // 3秒後に自動削除
    setTimeout(() => {
        if (toast && toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

// 業務内容の展開機能
function toggleDescription(button) {
    const descriptionText = button.closest('.description-text');
    const fullDescription = button.getAttribute('data-full-description');
    const currentText = descriptionText.querySelector('span, div').innerHTML;
    
    if (button.textContent.includes('続きを読む')) {
        descriptionText.innerHTML = `
            ${fullDescription.replace(/\n/g, '<br>')}
            <button type="button" class="btn btn-link btn-sm p-0 ms-1 description-toggle" 
                    onclick="toggleDescription(this)"
                    data-full-description="${fullDescription}">
                <small>折りたたむ</small>
            </button>
        `;
    } else {
        const shortDesc = fullDescription.length > 80 ? 
                         fullDescription.substring(0, 80) + '...' : fullDescription;
        descriptionText.innerHTML = `
            ${shortDesc.replace(/\n/g, '<br>')}
            <button type="button" class="btn btn-link btn-sm p-0 ms-1 description-toggle" 
                    onclick="toggleDescription(this)"
                    data-full-description="${fullDescription}">
                <small>続きを読む</small>
            </button>
        `;
    }
}

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // ポップオーバーの初期化
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // ツールチップの初期化
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 業務内容展開ボタンのイベントリスナー
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('description-toggle')) {
            e.preventDefault();
            toggleDescription(e.target);
        }
    });
    
    // CSRFトークンの存在確認
    const csrfTokens = document.querySelectorAll('input[name="csrf_token"]');
    csrfTokens.forEach(token => {
        if (!token.value || token.value.trim() === '') {
            console.error('CSRF token is empty');
            showErrorMessage('セキュリティトークンが無効です。ページを再読み込みしてください。');
        }
    });
    
    // 削除ボタンのクリックイベントを設定
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
            e.preventDefault();
            
            const deleteBtn = e.target.classList.contains('delete-btn') ? e.target : e.target.closest('.delete-btn');
            const form = deleteBtn.closest('form');
            const recordDataStr = deleteBtn.getAttribute('data-record');
            
            try {
                const recordData = JSON.parse(recordDataStr);
                handleDeleteRequest(form, recordData, deleteBtn);
            } catch (error) {
                console.error('Error parsing record data:', error);
                showErrorMessage('データの解析に失敗しました。');
            }
        }
    });

    // ページサイズ選択機能
    const pageSizeSelect = document.getElementById('service_records_page_size');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            updatePageSize(this.value);
        });
    }

    // ページネーションリンクのイベント設定
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('service-records-pagination-link') || 
            e.target.closest('.service-records-pagination-link')) {
            e.preventDefault();
            
            const link = e.target.classList.contains('service-records-pagination-link') ? 
                        e.target : e.target.closest('.service-records-pagination-link');
            
            const page = link.getAttribute('data-page');
            const pageSize = link.getAttribute('data-page-size');
            
            if (page && pageSize) {
                navigateToPage(parseInt(page), parseInt(pageSize));
            }
        }
    });
});

// 並び替え機能
document.addEventListener('DOMContentLoaded', function() {
    // デスクトップ用の並び替えリンク
    const sortLinks = document.querySelectorAll('.sort-link');
    
    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const sortColumn = this.getAttribute('data-sort');
            const currentUrl = new URL(window.location);
            const currentSort = currentUrl.searchParams.get('sort');
            const currentOrder = currentUrl.searchParams.get('order') || 'desc';
            
            // 同じ列をクリックした場合は昇順・降順を切り替え
            if (currentSort === sortColumn) {
                const newOrder = currentOrder === 'desc' ? 'asc' : 'desc';
                currentUrl.searchParams.set('order', newOrder);
            } else {
                // 新しい列の場合はデフォルトで降順
                currentUrl.searchParams.set('sort', sortColumn);
                currentUrl.searchParams.set('order', 'desc');
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
});

// ページサイズ更新機能
function updatePageSize(newPageSize) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('per_page', newPageSize);
    currentUrl.searchParams.set('page', '1'); // ページサイズ変更時は1ページ目に戻る
    
    window.location.href = currentUrl.toString();
}

// ページ遷移機能
function navigateToPage(page, pageSize) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('page', page.toString());
    
    if (pageSize) {
        currentUrl.searchParams.set('per_page', pageSize.toString());
    }
    
    window.location.href = currentUrl.toString();
}

// 削除処理のメイン関数
async function handleDeleteRequest(form, recordData, deleteBtn) {
    // 確認ダイアログの表示
    if (!showDeleteConfirmation(recordData)) {
        return;
    }
    
    // ボタンの状態を変更
    setButtonLoading(deleteBtn, true);
    
    try {
        // 削除処理の実行
        const result = await performDelete(form);
        
        if (result.success !== false) {
            showSuccessMessage(result.message || '役務記録が正常に削除されました。');
            
            // 1秒後にページをリロード
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.error || '削除に失敗しました');
        }
        
    } catch (error) {
        console.error('Delete error:', error);
        showErrorMessage(`削除処理中にエラーが発生しました: ${error.message}`);
        setButtonLoading(deleteBtn, false);
    }
}

// 確認ダイアログの表示
function showDeleteConfirmation(recordData) {
    let confirmMessage = '【役務記録の削除】\n\n';
    confirmMessage += `企業・拠点: ${recordData.company_name} - ${recordData.branch_name}\n`;
    confirmMessage += `役務日: ${recordData.service_date}\n`;
    confirmMessage += `役務種別: ${recordData.service_type}\n`;
    
    if (recordData.visit_type) {
        confirmMessage += `実施方法: ${recordData.visit_type}\n`;
    }
    
    if (recordData.service_hours) {
        confirmMessage += `役務時間: ${recordData.service_hours}\n`;
    }
    
    if (recordData.is_auto_split) {
        confirmMessage += '\n⚠️ この記録は自動分割で作成された記録です。\n';
        confirmMessage += '関連する他の記録も影響を受ける可能性があります。\n';
    }
    
    confirmMessage += '\n削除した記録は復元できません。\n';
    confirmMessage += '本当に削除しますか？';
    
    return confirm(confirmMessage);
}

// ボタンのローディング状態を設定
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>削除中...';
    } else {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-trash me-1"></i>削除';
    }
}

// 削除処理の実行
async function performDelete(form) {
    const formData = new FormData(form);
    
    const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });
    
    console.log('Delete response status:', response.status);
    console.log('Delete response ok:', response.ok);
    
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    // レスポンスのContent-Typeを確認
    const contentType = response.headers.get('Content-Type') || '';
    console.log('Response Content-Type:', contentType);
    
    // リダイレクトの場合
    if (response.redirected) {
        console.log('Response was redirected');
        return { success: true };
    }
    
    // JSONレスポンスの場合
    if (contentType.includes('application/json')) {
        return await response.json();
    }
    
    // その他のレスポンス（HTMLなど）
    const text = await response.text();
    console.log('Response text length:', text.length);
    
    // 空のレスポンスまたは成功ページは成功とみなす
    if (!text.trim() || response.status === 200) {
        return { success: true };
    }
    
    // エラーの可能性があるHTML内容をチェック
    if (text.includes('エラー') || text.includes('失敗') || text.includes('error')) {
        throw new Error('サーバーエラーが発生しました');
    }
    
    return { success: true };
}

// 成功メッセージ表示
function showSuccessMessage(message) {
    // 既存のアラートがあれば削除
    const existingAlert = document.querySelector('.alert.alert-success.position-fixed');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // 5秒後に自動で削除
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// エラーメッセージ表示
function showErrorMessage(message) {
    // 既存のアラートがあれば削除
    const existingAlert = document.querySelector('.alert.alert-danger.position-fixed');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // 10秒後に自動で削除
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 10000);
}

// 画面サイズ変更時の対応
window.addEventListener('resize', function() {
    const modal = document.getElementById('travelExpensesModal');
    if (modal && modal.classList.contains('show')) {
        // モーダルが表示中の場合、レイアウトを調整
        adjustModalLayout();
    }
});

// モーダルレイアウト調整
function adjustModalLayout() {
    const modalBody = document.getElementById('travelExpensesModalBody');
    const table = modalBody.querySelector('.table');
    
    if (table && window.innerWidth <= 576) {
        // 小画面用の調整
        table.style.fontSize = '0.8rem';
    } else if (table) {
        // 大画面用の調整
        table.style.fontSize = '';
    }
}
</script>