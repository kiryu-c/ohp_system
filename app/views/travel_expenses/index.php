<?php
// app/views/travel_expenses/index.php - スマホ対応レスポンシブ版（表示件数選択UI改善・ページネーション対応）

// ページネーションURL構築のヘルパー関数
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return base_url('travel_expenses') . '?' . http_build_query($params);
}
?>

<!-- ヘッダー -->
<div class="header-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-train me-2"></i>交通費一覧</h2>
        <div class="header-buttons">
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
            </a>
        </div>
    </div>
</div>

<!-- 検索・フィルター（拡張版） -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>フィルター条件
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= base_url('travel_expenses') ?>" class="filter-form" id="filterForm">
            <div class="row g-3">
                <div class="col-md-2 col-6">
                    <label for="year" class="form-label">年</label>
                    <select class="form-select" id="year" name="year">
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 3; $y--): ?>
                            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?>年</option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2 col-6">
                    <label for="month" class="form-label">月</label>
                    <select class="form-select" id="month" name="month">
                        <option value="0" <?= $month == 0 ? 'selected' : '' ?>>全月</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?>月</option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <?php if ($userType === 'admin'): ?>
                    <div class="col-md-2 col-6">
                        <label for="status" class="form-label">ステータス</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">全て</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>承認待ち</option>
                            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>承認済み</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>差戻し</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 col-6">
                        <label for="contract_id" class="form-label">契約（企業・拠点）</label>
                        <select class="form-select" id="contract_id" name="contract_id">
                            <option value="0">全ての契約</option>
                            <?php if (!empty($contracts)): ?>
                                <?php foreach ($contracts as $contract): ?>
                                    <option value="<?= $contract['id'] ?>" <?= $contractId == $contract['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($contract['display_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 col-6">
                        <label for="doctor_id" class="form-label">産業医</label>
                        <select class="form-select" id="doctor_id" name="doctor_id">
                            <option value="0">全ての産業医</option>
                            <?php if (!empty($doctors)): ?>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['id'] ?>" <?= $doctorId == $doctor['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($userType === 'doctor'): ?>                
                    <div class="col-md-3 col-12">
                        <label for="contract_id" class="form-label">契約（企業・拠点）</label>
                        <select class="form-select" id="contract_id" name="contract_id">
                            <option value="0">全ての契約</option>
                            <?php if (!empty($contracts)): ?>
                                <?php foreach ($contracts as $contract): ?>
                                    <option value="<?= $contract['id'] ?>" <?= $contractId == $contract['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($contract['display_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-<?= $userType === 'admin' ? '2' : '3' ?> col-12">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 filter-buttons">
                        <button type="submit" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-search"></i>
                            <span class="btn-text">検索</span>
                        </button>
                        <a href="<?= base_url('travel_expenses') ?>" class="btn btn-outline-secondary flex-fill">
                            <i class="fas fa-redo"></i>
                            <span class="btn-text">リセット</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- モバイル用並び替え (1024px未満) -->
<div class="mobile-sort-section d-lg-none">
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
        <option value="amount-desc" <?= ($sortColumn ?? '') === 'amount' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            金額(高い順)
        </option>
        <option value="amount-asc" <?= ($sortColumn ?? '') === 'amount' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            金額(安い順)
        </option>
        <option value="status-asc" <?= ($sortColumn ?? '') === 'status' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            ステータス(昇順)
        </option>
        <option value="transport_type-asc" <?= ($sortColumn ?? '') === 'transport_type' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            交通手段(昇順)
        </option>
    </select>
</div>

<!-- 交通費一覧 -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>交通費記録
                    <?php if ($totalCount > 0): ?>
                        <span class="badge bg-primary">全<?= $totalCount ?>件</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <!-- ページサイズ選択（契約一覧と統一） -->
                    <div class="d-flex align-items-center">
                        <label for="travel_expenses_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                        <select id="travel_expenses_page_size" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10件</option>
                            <option value="20" <?= ($perPage == 20) ? 'selected' : '' ?>>20件</option>
                            <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50件</option>
                            <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100件</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($expenses)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>交通費記録がありません。
                <?php if ($userType === 'doctor'): ?>
                    役務記録詳細から<strong>「交通費登録」</strong>を行ってください。
                <?php endif; ?>
            </div>
        <?php else: ?>
            
            <!-- デスクトップ表示（1024px以上） -->
            <div class="table-responsive d-none d-lg-block">
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
                            <?php if ($userType === 'admin'): ?>
                                <th>産業医</th>
                            <?php endif; ?>
                            <th>
                                <a href="#" class="sort-link" data-sort="company_name" title="企業・拠点で並び替え">
                                    企業・拠点
                                    <?php if (($sortColumn ?? '') === 'company_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="transport_type" title="交通手段で並び替え">
                                    交通手段
                                    <?php if (($sortColumn ?? '') === 'transport_type'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>区間</th>
                            <th>往復/片道</th>
                            <th>タクシー利用</th>
                            <th class="text-end">
                                <a href="#" class="sort-link" data-sort="amount" title="金額で並び替え">
                                    金額
                                    <?php if (($sortColumn ?? '') === 'amount'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="status" title="ステータスで並び替え">
                                    ステータス
                                    <?php if (($sortColumn ?? '') === 'status'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">レシート</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <?php
                            // ステータス情報
                            $statusInfo = [
                                'pending' => ['class' => 'warning', 'label' => '承認待ち', 'icon' => 'clock'],
                                'approved' => ['class' => 'success', 'label' => '承認済み', 'icon' => 'check-circle'],
                                'rejected' => ['class' => 'danger', 'label' => '差戻し', 'icon' => 'times-circle'],
                                'finalized' => ['class' => 'dark', 'label' => '締済み', 'icon' => 'lock']
                            ];
                            $status = $statusInfo[$expense['status'] ?? 'pending'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= format_date($expense['service_date']) ?></strong>
                                    <div class="small text-muted">
                                        <?= date('w', strtotime($expense['service_date'])) == 0 ? '日' : 
                                           (date('w', strtotime($expense['service_date'])) == 6 ? '土' : '平日') ?>
                                    </div>
                                </td>
                                
                                <?php if ($userType === 'admin'): ?>
                                    <td>
                                        <i class="fas fa-user-md me-1 text-info"></i>
                                        <?= htmlspecialchars($expense['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                <?php endif; ?>
                                
                                <td>
                                    <div class="fw-bold">
                                        <i class="fas fa-building me-1 text-primary"></i>
                                        <?= htmlspecialchars($expense['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($expense['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php
                                        $transportIcons = [
                                            'train' => 'fas fa-train text-primary',
                                            'bus' => 'fas fa-bus text-success',
                                            'taxi' => 'fas fa-taxi text-warning',
                                            'gasoline' => 'fas fa-car text-info',
                                            'highway_toll' => 'fas fa-car text-info',
                                            'parking' => 'fas fa-car text-info',
                                            'rental_car' => 'fas fa-car text-info',
                                            'airplane' => 'fas fa-plane text-success',
                                            'other' => 'fas fa-question text-muted'
                                        ];
                                        $icon = $transportIcons[$expense['transport_type']] ?? 'fas fa-question text-muted';
                                        ?>
                                        <i class="<?= $icon ?> me-1"></i>
                                        <?= get_transport_type_label($expense['transport_type']) ?>
                                    </span>
                                    
                                    <!-- タクシー利用時の注意表示 -->
                                    <?php if ($expense['transport_type'] === 'taxi' && !$expense['taxi_allowed']): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-warning text-dark" 
                                                  data-bs-toggle="tooltip" 
                                                  title="この契約ではタクシー利用が認められていません">
                                                <i class="fas fa-exclamation-triangle me-1"></i>要確認
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($expense['departure_point']): ?>
                                    <small>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-map-marker-alt text-success me-1"></i>
                                            <span><?= htmlspecialchars($expense['departure_point'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="text-center my-1">
                                            <i class="fas fa-arrow-down text-muted"></i>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            <span><?= htmlspecialchars($expense['arrival_point'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($expense['trip_type'] === 'round_trip'): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-exchange-alt me-1"></i>往復
                                        </span>
                                    <?php elseif ($expense['trip_type'] === 'one_way'): ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-arrow-right me-1"></i>片道
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- タクシー利用可否の表示 -->
                                <td class="text-center">
                                    <?php if ($expense['taxi_allowed']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-taxi me-1"></i>可
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-ban me-1"></i>不可
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end">
                                    <strong><?= format_amount($expense['amount']) ?></strong>
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge bg-<?= $status['class'] ?>">
                                        <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                    
                                    <?php if ($expense['status'] === 'rejected' && !empty($expense['admin_comment'])): ?>
                                        <div class="mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="popover" 
                                                    data-bs-title="差戻し理由" 
                                                    data-bs-content="<?= htmlspecialchars($expense['admin_comment'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if (!empty($expense['receipt_file_path'])): ?>
                                        <a href="<?= base_url($expense['receipt_file_path']) ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-receipt me-1"></i>表示
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">なし</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <div class="btn-group-vertical" role="group">
                                        <!-- 詳細表示 -->
                                        <a href="<?= base_url("travel_expenses/{$expense['id']}") ?>" 
                                           class="btn btn-outline-primary btn-sm mb-1">
                                            <i class="fas fa-eye me-1"></i>詳細
                                        </a>
                                        
                                        <?php if ($userType === 'doctor'): ?>
                                            <!-- 産業医の操作 -->
                                            <?php if ($expense['status'] === 'pending' || $expense['status'] === 'rejected'): ?>
                                                <a href="<?= base_url("travel_expenses/{$expense['id']}/edit") ?>" 
                                                class="btn btn-outline-secondary btn-sm mb-1">
                                                    <i class="fas fa-edit me-1"></i>編集
                                                </a>
                                                
                                                <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/delete") ?>" 
                                                    style="display: inline;" 
                                                    class="delete-form"
                                                    data-expense-id="<?= $expense['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm delete-btn"
                                                            data-expense='<?= htmlspecialchars(json_encode([
                                                                'id' => $expense['id'],
                                                                'amount' => format_amount($expense['amount']),
                                                                'departure_point' => $expense['departure_point'],
                                                                'arrival_point' => $expense['arrival_point']
                                                            ]), ENT_QUOTES, 'UTF-8') ?>'>
                                                        <i class="fas fa-trash me-1"></i>削除
                                                    </button>
                                                </form>
                                            <?php elseif ($expense['status'] === 'approved'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>承認済み
                                                </span>
                                            <?php elseif ($expense['status'] === 'finalized'): ?>
                                                <span class="badge bg-dark">
                                                    <i class="fas fa-lock me-1"></i>締済み
                                                </span>
                                            <?php endif; ?>
                                            
                                        <?php elseif ($userType === 'admin'): ?>
                                            <!-- 管理者の操作 -->
                                            <?php if ($expense['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-outline-success btn-sm mb-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal<?= $expense['id'] ?>">
                                                    <i class="fas fa-check me-1"></i>承認
                                                </button>
                                                
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal<?= $expense['id'] ?>">
                                                    <i class="fas fa-times me-1"></i>差戻し
                                                </button>
                                            <?php elseif ($expense['status'] === 'approved'): ?>
                                                <span class="badge bg-success mb-1">
                                                    <i class="fas fa-check me-1"></i>承認済み
                                                </span>
                                                <button type="button" class="btn btn-outline-warning btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#unapproveModal<?= $expense['id'] ?>">
                                                    <i class="fas fa-undo me-1"></i>承認取消
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-<?= $status['class'] ?>">
                                                    <?= $status['label'] ?>
                                                </span>
                                            <?php endif; ?>
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
                <?php foreach ($expenses as $expense): ?>
                    <?php
                    // ステータス情報
                    $statusInfo = [
                        'pending' => ['class' => 'warning', 'label' => '承認待ち', 'icon' => 'clock'],
                        'approved' => ['class' => 'success', 'label' => '承認済み', 'icon' => 'check-circle'],
                        'rejected' => ['class' => 'danger', 'label' => '差戻し', 'icon' => 'times-circle'],
                        'finalized' => ['class' => 'dark', 'label' => '締済み', 'icon' => 'lock']
                    ];
                    $status = $statusInfo[$expense['status'] ?? 'pending'];
                    
                    // 交通手段のアイコン
                    $transportIcons = [
                        'train' => 'fas fa-train text-primary',
                        'bus' => 'fas fa-bus text-success',
                        'taxi' => 'fas fa-taxi text-warning',
                        'gasoline' => 'fas fa-car text-info',
                        'highway_toll' => 'fas fa-car text-info',
                        'parking' => 'fas fa-car text-info',
                        'rental_car' => 'fas fa-car text-info',
                        'airplane' => 'fas fa-plane text-success',
                        'other' => 'fas fa-question text-muted'
                    ];
                    $transportIcon = $transportIcons[$expense['transport_type']] ?? 'fas fa-question text-muted';
                    ?>
                    
                    <div class="card mobile-expense-card mb-3">
                        <div class="card-body">
                            <!-- ヘッダー：日付・ステータス -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-calendar me-2 text-primary"></i>
                                        <?= format_date($expense['service_date']) ?>
                                    </h6>
                                    <p class="card-subtitle text-muted mb-0">
                                        <?= date('w', strtotime($expense['service_date'])) == 0 ? '日曜日' : 
                                           (date('w', strtotime($expense['service_date'])) == 6 ? '土曜日' : '平日') ?>
                                    </p>
                                </div>
                                
                                <!-- ステータスバッジ -->
                                <div class="ms-2">
                                    <span class="badge bg-<?= $status['class'] ?> status-badge">
                                        <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- 企業・拠点情報 -->
                            <div class="mobile-info-item mb-3">
                                <small class="text-muted d-block">企業・拠点</small>
                                <div class="fw-bold">
                                    <i class="fas fa-building me-1 text-primary"></i>
                                    <?= htmlspecialchars($expense['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($expense['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            
                            <!-- 交通費詳細 -->
                            <div class="row g-2 mb-3">
                                <!-- 交通手段 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">交通手段</small>
                                        <span class="badge bg-light text-dark">
                                            <i class="<?= $transportIcon ?> me-1"></i>
                                            <?= get_transport_type_label($expense['transport_type']) ?>
                                        </span>
                                        
                                        <!-- タクシー利用時の注意表示 -->
                                        <?php if ($expense['transport_type'] === 'taxi' && !$expense['taxi_allowed']): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>要確認
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- 往復/片道 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">往復/片道</small>
                                        <?php if ($expense['trip_type'] === 'round_trip'): ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-exchange-alt me-1"></i>往復
                                            </span>
                                        <?php elseif ($expense['trip_type'] === 'one_way'): ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-arrow-right me-1"></i>片道
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- タクシー利用可否 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">タクシー利用</small>
                                        <?php if ($expense['taxi_allowed']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>許可
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>不可
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- 金額 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">金額</small>
                                        <h5 class="text-success mb-0">
                                            <i class="fas fa-yen-sign me-1"></i>
                                            <?= format_amount($expense['amount']) ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 区間情報 -->
                            <div class="mobile-info-item mb-3">
                                <small class="text-muted d-block">移動区間</small>
                                <div class="route-display">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas fa-map-marker-alt text-success me-2"></i>
                                        <span class="fw-bold"><?= htmlspecialchars($expense['departure_point'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="text-center my-2">
                                        <i class="fas fa-arrow-down text-primary fa-lg"></i>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                        <span class="fw-bold"><?= htmlspecialchars($expense['arrival_point'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 産業医名（管理者のみ） -->
                            <?php if ($userType === 'admin'): ?>
                                <div class="mobile-info-item mb-3">
                                    <small class="text-muted d-block">産業医</small>
                                    <div>
                                        <i class="fas fa-user-md me-1 text-info"></i>
                                        <?= htmlspecialchars($expense['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- レシート情報 -->
                            <div class="mobile-info-item mb-3">
                                <small class="text-muted d-block">レシート</small>
                                <?php if (!empty($expense['receipt_file_path'])): ?>
                                    <a href="<?= base_url($expense['receipt_file_path']) ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-receipt me-1"></i>表示する
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-times me-1"></i>未添付
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 操作ボタン -->
                            <div class="d-grid gap-2">
                                <div class="d-flex gap-2">
                                    <!-- 詳細表示 -->
                                    <a href="<?= base_url("travel_expenses/{$expense['id']}") ?>" 
                                       class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-eye me-1"></i>詳細
                                    </a>
                                    
                                    <?php if ($userType === 'doctor'): ?>
                                        <!-- 産業医の操作 -->
                                        <?php if ($expense['status'] === 'pending' || $expense['status'] === 'rejected'): ?>
                                            <a href="<?= base_url("travel_expenses/{$expense['id']}/edit") ?>" 
                                            class="btn btn-outline-secondary btn-sm flex-fill">
                                                <i class="fas fa-edit me-1"></i>編集
                                            </a>
                                        <?php elseif ($expense['status'] === 'approved'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>承認済み
                                            </span>
                                        <?php elseif ($expense['status'] === 'finalized'): ?>
                                            <span class="badge bg-dark">
                                                <i class="fas fa-lock me-1"></i>締済み
                                            </span>
                                        <?php endif; ?>
                                        
                                    <?php elseif ($userType === 'admin'): ?>
                                        <!-- 管理者の操作 -->
                                        <?php if ($expense['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-outline-success btn-sm flex-fill" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#approveModal<?= $expense['id'] ?>">
                                                <i class="fas fa-check me-1"></i>承認
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm flex-fill" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#rejectModal<?= $expense['id'] ?>">
                                                <i class="fas fa-times me-1"></i>差戻
                                            </button>
                                        <?php elseif ($expense['status'] === 'approved'): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm w-100 mt-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#unapproveModal<?= $expense['id'] ?>">
                                                <i class="fas fa-undo me-1"></i>承認取り消し
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($userType === 'doctor' && ($expense['status'] === 'pending' || $expense['status'] === 'rejected')): ?>
                                    <!-- 削除ボタン（産業医のみ） -->
                                    <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/delete") ?>" 
                                        class="delete-form"
                                        data-expense-id="<?= $expense['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm w-100 delete-btn"
                                                data-expense='<?= htmlspecialchars(json_encode([
                                                    'id' => $expense['id'],
                                                    'amount' => format_amount($expense['amount']),
                                                    'departure_point' => $expense['departure_point'],
                                                    'arrival_point' => $expense['arrival_point']
                                                ]), ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="fas fa-trash me-1"></i>削除
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 差戻し理由（ある場合） -->
                            <?php if ($expense['status'] === 'rejected' && !empty($expense['admin_comment'])): ?>
                                <div class="mt-3 alert alert-danger">
                                    <small class="fw-bold">差戻し理由:</small>
                                    <div class="mt-1">
                                        <?= nl2br(htmlspecialchars($expense['admin_comment'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- ページネーション（契約一覧と統一フォーマット） -->
            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="d-none d-md-block">
                        <small class="text-muted">
                            <?= number_format(($currentPage - 1) * $perPage + 1) ?>-<?= number_format(min($currentPage * $perPage, $totalCount)) ?>件 
                            (全<?= number_format($totalCount) ?>件中)
                        </small>
                    </div>
                    
                    <nav aria-label="交通費一覧ページネーション">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- 前のページ -->
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link travel-expenses-pagination-link" 
                                   href="#" 
                                   data-page="<?= $currentPage - 1 ?>"
                                   data-page-size="<?= $perPage ?>"
                                   aria-label="前のページ">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <!-- ページ番号 -->
                            <?php 
                            $start = max(1, $currentPage - 2);
                            $end = min($totalPages, $currentPage + 2);
                            ?>
                            
                            <?php if ($start > 1): ?>
                                <li class="page-item">
                                    <a class="page-link travel-expenses-pagination-link" 
                                       href="#" 
                                       data-page="1"
                                       data-page-size="<?= $perPage ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                    <a class="page-link travel-expenses-pagination-link" 
                                       href="#" 
                                       data-page="<?= $i ?>"
                                       data-page-size="<?= $perPage ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end < $totalPages): ?>
                                <?php if ($end < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link travel-expenses-pagination-link" 
                                       href="#" 
                                       data-page="<?= $totalPages ?>"
                                       data-page-size="<?= $perPage ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- 次のページ -->
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link travel-expenses-pagination-link" 
                                   href="#" 
                                   data-page="<?= $currentPage + 1 ?>"
                                   data-page-size="<?= $perPage ?>"
                                   aria-label="次のページ">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <div class="d-md-none">
                        <small class="text-muted">
                            <?= $currentPage ?>/<?= $totalPages ?>ページ
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <!-- データがある場合のページ情報表示（ページネーションなし） -->
                <?php if ($totalCount > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div class="d-none d-md-block">
                            <small class="text-muted">
                                <?= number_format(($currentPage - 1) * $perPage + 1) ?>-<?= number_format(min($currentPage * $perPage, $totalCount)) ?>件 
                                (全<?= number_format($totalCount) ?>件中)
                            </small>
                        </div>
                        
                        <div class="d-md-none">
                            <small class="text-muted">
                                全<?= number_format($totalCount) ?>件
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- 合計金額表示 -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <!-- デスクトップ統計表示 -->
                            <div class="row text-center d-none d-md-flex">
                                <?php
                                $expenseStats = get_travel_expense_stats($expenses);
                                ?>
                                <div class="col-md-3">
                                    <h6 class="text-primary">総申請額</h6>
                                    <h4><?= format_amount($expenseStats['total_amount']) ?></h4>
                                    <small class="text-muted"><?= $expenseStats['total_count'] ?>件</small>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-success">承認済み</h6>
                                    <h4><?= format_amount($expenseStats['approved_amount']) ?></h4>
                                    <small class="text-muted"><?= $expenseStats['approved_count'] ?>件</small>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-warning">承認待ち</h6>
                                    <h4><?= format_amount($expenseStats['pending_amount']) ?></h4>
                                    <small class="text-muted"><?= $expenseStats['pending_count'] ?>件</small>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-danger">差戻し</h6>
                                    <h4><?= format_amount($expenseStats['rejected_amount']) ?></h4>
                                    <small class="text-muted"><?= $expenseStats['rejected_count'] ?>件</small>
                                </div>
                            </div>
                            
                            <!-- モバイル統計表示 -->
                            <div class="row g-3 d-md-none">
                                <?php
                                $expenseStats = get_travel_expense_stats($expenses);
                                ?>
                                <div class="col-6">
                                    <div class="mobile-stats-card text-center p-2 border rounded">
                                        <i class="fas fa-calculator fa-lg text-primary mb-1"></i>
                                        <div class="fw-bold"><?= format_amount($expenseStats['total_amount']) ?></div>
                                        <small class="text-muted d-block">総申請額</small>
                                        <span class="badge bg-primary"><?= $expenseStats['total_count'] ?>件</span>
                                    </div>
                                </div>
                                
                                <div class="col-6">
                                    <div class="mobile-stats-card text-center p-2 border rounded">
                                        <i class="fas fa-check-circle fa-lg text-success mb-1"></i>
                                        <div class="fw-bold"><?= format_amount($expenseStats['approved_amount']) ?></div>
                                        <small class="text-muted d-block">承認済み</small>
                                        <span class="badge bg-success"><?= $expenseStats['approved_count'] ?>件</span>
                                    </div>
                                </div>
                                
                                <div class="col-6">
                                    <div class="mobile-stats-card text-center p-2 border rounded">
                                        <i class="fas fa-clock fa-lg text-warning mb-1"></i>
                                        <div class="fw-bold"><?= format_amount($expenseStats['pending_amount']) ?></div>
                                        <small class="text-muted d-block">承認待ち</small>
                                        <span class="badge bg-warning"><?= $expenseStats['pending_count'] ?>件</span>
                                    </div>
                                </div>
                                
                                <div class="col-6">
                                    <div class="mobile-stats-card text-center p-2 border rounded">
                                        <i class="fas fa-times-circle fa-lg text-danger mb-1"></i>
                                        <div class="fw-bold"><?= format_amount($expenseStats['rejected_amount']) ?></div>
                                        <small class="text-muted d-block">差戻し</small>
                                        <span class="badge bg-danger"><?= $expenseStats['rejected_count'] ?>件</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 管理者用承認・差戻しモーダル -->
<?php if ($userType === 'admin' && !empty($expenses)): ?>
    <?php foreach ($expenses as $expense): ?>
        <?php if ($expense['status'] === 'pending'): ?>
            <!-- 承認モーダル -->
            <div class="modal fade" id="approveModal<?= $expense['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-check me-2"></i>交通費承認
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/approve") ?>">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                
                                <div class="mb-3">
                                    <strong>交通費詳細：</strong>
                                    <ul class="list-unstyled mt-2">
                                        <li><strong>産業医：</strong><?= htmlspecialchars($expense['doctor_name'], ENT_QUOTES, 'UTF-8') ?></li>
                                        <li><strong>役務日：</strong><?= format_date($expense['service_date']) ?></li>
                                        <li><strong>交通手段：</strong><?= get_transport_type_label($expense['transport_type']) ?></li>
                                        <li><strong>区間：</strong><?= format_travel_route($expense['departure_point'], $expense['arrival_point']) ?></li>
                                        <li><strong>金額：</strong><?= format_amount($expense['amount']) ?></li>
                                        <li><strong>タクシー利用：</strong>
                                            <?php if ($expense['taxi_allowed']): ?>
                                                <span class="badge bg-success">許可</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">不可</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="approve_comment<?= $expense['id'] ?>" class="form-label">承認コメント（任意）</label>
                                    <textarea class="form-control" id="approve_comment<?= $expense['id'] ?>" name="comment" 
                                              rows="3" placeholder="承認時のコメントがあれば入力してください"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-1"></i>承認する
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- 差戻しモーダル -->
            <div class="modal fade" id="rejectModal<?= $expense['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-times me-2"></i>交通費差戻し
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/reject") ?>">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                
                                <div class="mb-3">
                                    <strong>交通費詳細：</strong>
                                    <ul class="list-unstyled mt-2">
                                        <li><strong>産業医：</strong><?= htmlspecialchars($expense['doctor_name'], ENT_QUOTES, 'UTF-8') ?></li>
                                        <li><strong>役務日：</strong><?= format_date($expense['service_date']) ?></li>
                                        <li><strong>交通手段：</strong><?= get_transport_type_label($expense['transport_type']) ?></li>
                                        <li><strong>区間：</strong><?= format_travel_route($expense['departure_point'], $expense['arrival_point']) ?></li>
                                        <li><strong>金額：</strong><?= format_amount($expense['amount']) ?></li>
                                        <li><strong>タクシー利用：</strong>
                                            <?php if ($expense['taxi_allowed']): ?>
                                                <span class="badge bg-success">許可</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">不可</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reject_comment<?= $expense['id'] ?>" class="form-label">
                                        差戻し理由 <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="reject_comment<?= $expense['id'] ?>" name="comment" 
                                              rows="3" placeholder="差戻しの理由を詳しく入力してください" required></textarea>
                                    <div class="form-text">差戻し理由は産業医に通知されます。<br>
                                        <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times me-1"></i>差戻しする
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php elseif ($expense['status'] === 'approved'): ?>
            <!-- 承認取り消しモーダル -->
            <div class="modal fade" id="unapproveModal<?= $expense['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-undo me-2"></i>交通費承認取り消し
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/unapprove") ?>">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>注意:</strong> 承認を取り消すと、この交通費は「承認待ち」状態に戻ります。
                                </div>
                                
                                <div class="mb-3">
                                    <strong>交通費詳細:</strong>
                                    <ul class="list-unstyled mt-2">
                                        <li><strong>産業医:</strong><?= htmlspecialchars($expense['doctor_name'], ENT_QUOTES, 'UTF-8') ?></li>
                                        <li><strong>役務日:</strong><?= format_date($expense['service_date']) ?></li>
                                        <li><strong>交通手段:</strong><?= get_transport_type_label($expense['transport_type']) ?></li>
                                        <li><strong>区間:</strong><?= format_travel_route($expense['departure_point'], $expense['arrival_point']) ?></li>
                                        <li><strong>金額:</strong><?= format_amount($expense['amount']) ?></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-undo me-1"></i>承認を取り消す
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

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

/* ページサイズ選択スタイル（契約一覧と統一） */
.form-select-sm {
    font-size: 0.875rem;
    padding: 0.25rem 1.75rem 0.25rem 0.5rem;
    min-width: 80px;
}

/* ページネーション統一スタイル */
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

/* モバイル交通費カード */
.mobile-expense-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-expense-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.mobile-expense-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

.mobile-expense-card .card-subtitle {
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

/* ステータスバッジ */
.status-badge {
    font-size: 0.8rem;
    padding: 0.4em 0.7em;
}

/* 区間表示 */
.route-display {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    border-left: 4px solid #007bff;
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

/* ========== レスポンシブ対応 ========== */

/* 1024px以下でモバイル表示に切り替え */
@media (max-width: 1023px) {
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
    
    /* ページネーション調整 */
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination .page-item {
        margin-bottom: 0.25rem;
    }
}

/* タブレット表示（768px-1023px） */
@media (min-width: 768px) and (max-width: 1023px) {
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
    
    /* モバイル交通費カードの調整 */
    .mobile-expense-card .card-body {
        padding: 1rem;
    }
    
    .mobile-info-item {
        padding: 0.25rem 0;
    }
    
    .mobile-info-item small {
        font-size: 0.75rem;
    }
    
    /* 統計カードの調整 */
    .mobile-stats-card {
        padding: 0.75rem !important;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .mobile-stats-card .fw-bold {
        font-size: 1.1rem;
        margin: 0.25rem 0;
    }
    
    .mobile-stats-card small {
        font-size: 0.7rem;
    }
    
    .mobile-stats-card .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        margin-top: 0.25rem;
    }
    
    /* カードヘッダーの調整 */
    .card-header h5 {
        font-size: 1rem;
    }
    
    /* 区間表示の調整 */
    .route-display {
        padding: 0.75rem;
    }
    
    /* ページネーション調整 */
    .pagination .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
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
    .mobile-expense-card .card-body {
        padding: 0.75rem;
    }
    
    .mobile-expense-card .card-title {
        font-size: 0.9rem;
    }
    
    .mobile-expense-card .card-subtitle {
        font-size: 0.8rem;
    }
    
    /* ボタンの調整 */
    .mobile-expense-card .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
    
    /* 統計カードのさらなる調整 */
    .mobile-stats-card {
        padding: 0.5rem !important;
        min-height: 70px;
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
    
    /* 区間表示の調整 */
    .route-display {
        padding: 0.5rem;
    }
    
    /* ページネーション調整 */
    .pagination .page-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
    }
    
    .pagination .page-link i {
        font-size: 0.75rem;
    }
}

/* 極小モバイル表示（400px未満） */
@media (max-width: 399px) {
    /* 最小限の表示に調整 */
    .header-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .mobile-expense-card .card-body {
        padding: 0.5rem;
    }
    
    .mobile-expense-card .card-title {
        font-size: 0.85rem;
    }
    
    .mobile-expense-card .btn {
        font-size: 0.75rem;
        padding: 0.4rem;
    }
    
    .mobile-stats-card {
        padding: 0.4rem !important;
        min-height: 60px;
    }
    
    .mobile-stats-card .fw-bold {
        font-size: 0.95rem;
    }
    
    .pagination .page-link {
        padding: 0.35rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* ========== その他のスタイル ========== */

/* テーブルのスタイリング */
.table td, .table th {
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

/* バッジのスタイル */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}

/* ボタングループの調整 */
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

/* 統計カードのスタイル */
.card.bg-primary, .card.bg-success, .card.bg-warning, .card.bg-info {
    border: none;
}

/* 区間表示のスタイル */
.table td small {
    line-height: 1.2;
}

/* モーダルのカスタマイズ */
.modal-header.bg-success,
.modal-header.bg-danger {
    border-bottom: none;
}

/* 金額のスタイル */
.text-end strong {
    font-size: 1.1em;
    color: #2d6a4f;
}

/* 区間表示のアイコンカラー */
.fa-map-marker-alt.text-success {
    color: #28a745 !important;
}

.fa-map-marker-alt.text-danger {
    color: #dc3545 !important;
}

/* アクセシビリティ向上 */
@media (max-width: 767px) {
    /* タップターゲットのサイズを44px以上に */
    .mobile-expense-card .btn,
    .mobile-expense-card .badge {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .mobile-expense-card .badge {
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

/* モーダルのレスポンシブ対応 */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}

@media (max-width: 400px) {
    .modal-dialog {
        margin: 0.25rem;
        max-width: calc(100% - 0.5rem);
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

/* 並び替えアイコンがない列にもアイコンを表示（薄く） */
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
document.addEventListener('DOMContentLoaded', function() {
    
    // ===================== ダッシュボード統一ページネーション機能 =====================
    
    // ページサイズ変更時の処理（交通費一覧）
    const travelExpensesPageSizeSelect = document.getElementById('travel_expenses_page_size');
    if (travelExpensesPageSizeSelect) {
        travelExpensesPageSizeSelect.addEventListener('change', function() {
            const newPageSize = this.value;
            reloadPageWithPagination({
                page: 1, // ページサイズ変更時は1ページ目に戻る
                per_page: newPageSize
            });
        });
    }
    
    // ページネーションリンクのクリック処理（交通費一覧）
    document.addEventListener('click', function(e) {
        if (e.target.closest('.travel-expenses-pagination-link')) {
            e.preventDefault();
            const link = e.target.closest('.travel-expenses-pagination-link');
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
    
    // ページリロード関数（交通費一覧）
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
        const expensesCard = document.querySelector('.card:has(.travel-expenses-pagination-link)');
        if (expensesCard) {
            expensesCard.classList.add('pagination-loading');
        }
    }
    
    // ==================== 並び替え機能 ====================

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


    // ===================== 既存の機能（承認・差戻し・削除等） =====================
    
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
    
    // 承認・差戻しフォームの送信確認
    const approveForms = document.querySelectorAll('form[action*="/approve"]');
    const rejectForms = document.querySelectorAll('form[action*="/reject"]');
    
    approveForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('この交通費を承認しますか？\n承認後は変更できません。')) {
                e.preventDefault();
            }
        });
    });
    
    rejectForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const textarea = form.querySelector('textarea[name="comment"]');
            if (!textarea.value.trim()) {
                alert('差戻し理由を入力してください。');
                e.preventDefault();
                return;
            }
            
            if (!confirm('この交通費を差戻ししますか？\n産業医に差戻し理由が通知されます。')) {
                e.preventDefault();
            }
        });
    });
    
    // 削除ボタンのクリックイベントを設定
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
            e.preventDefault();
            
            const deleteBtn = e.target.classList.contains('delete-btn') ? e.target : e.target.closest('.delete-btn');
            const form = deleteBtn.closest('form');
            const expenseDataStr = deleteBtn.getAttribute('data-expense');
            
            try {
                const expenseData = JSON.parse(expenseDataStr);
                handleDeleteRequest(form, expenseData, deleteBtn);
            } catch (error) {
                console.error('Error parsing expense data:', error);
                showErrorMessage('データの解析に失敗しました。');
            }
        }
    });
    
    // テーブル行のハイライト（デスクトップのみ）
    if (window.innerWidth >= 1024) {
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }
    
    // フィルター変更時にページを1に戻す
    const filterElements = document.querySelectorAll('#year, #month, #status, #contract_id, #doctor_id');
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
});

// 削除処理のメイン関数
async function handleDeleteRequest(form, expenseData, deleteBtn) {
    // 確認ダイアログの表示
    if (!showDeleteConfirmation(expenseData)) {
        return;
    }
    
    // ボタンの状態を変更
    setButtonLoading(deleteBtn, true);
    
    try {
        // 削除処理の実行
        const result = await performDelete(form);
        
        if (result.success !== false) {
            showSuccessMessage('交通費記録が正常に削除されました。');
            
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
function showDeleteConfirmation(expenseData) {
    let confirmMessage = '【交通費記録の削除】\n\n';
    confirmMessage += `金額: ${expenseData.amount}\n`;
    confirmMessage += `区間: ${expenseData.departure_point} → ${expenseData.arrival_point}\n`;
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
    
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    // レスポンスのContent-Typeを確認
    const contentType = response.headers.get('Content-Type') || '';
    
    // リダイレクトの場合
    if (response.redirected) {
        return { success: true };
    }
    
    // JSONレスポンスの場合
    if (contentType.includes('application/json')) {
        return await response.json();
    }
    
    // その他のレスポンス（HTMLなど）
    const text = await response.text();
    
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
    // モーダル表示中の場合、レイアウトを調整
    const modals = document.querySelectorAll('.modal.show');
    if (modals.length > 0) {
        adjustModalLayout();
    }
});

// モーダルレイアウト調整
function adjustModalLayout() {
    if (window.innerWidth <= 576) {
        // 小画面用の調整
        const modalBodies = document.querySelectorAll('.modal-body');
        modalBodies.forEach(body => {
            body.style.fontSize = '0.9rem';
            body.style.padding = '1rem 0.75rem';
        });
    }
}

// タッチイベント対応（モバイルのみ）
if (window.innerWidth < 1024) {
    document.addEventListener('touchstart', function(e) {
        // モバイルカードのタッチハイライト
        if (e.target.closest('.mobile-expense-card')) {
            const card = e.target.closest('.mobile-expense-card');
            card.style.backgroundColor = '#f8f9fa';
        }
    });
    
    document.addEventListener('touchend', function(e) {
        // タッチ終了時にハイライトを解除
        if (e.target.closest('.mobile-expense-card')) {
            const card = e.target.closest('.mobile-expense-card');
            setTimeout(() => {
                card.style.backgroundColor = '';
            }, 200);
        }
    });
}

// 表示件数変更関数（後方互換性のため）
function changePerPage(perPage) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', perPage);
    urlParams.set('page', 1); // ページを1に戻す
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}
</script>