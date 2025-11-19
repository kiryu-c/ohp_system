<?php
// app/views/service_records/admin_detail.php - 実施方法対応版（ページネーション追加）
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-list-alt me-2"></i>役務記録詳細</h2>
    <a href="<?= base_url("service_records/admin?year={$year}&month={$month}") ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>サマリーに戻る
    </a>
</div>

<!-- 契約情報 -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-file-contract me-2"></i>契約情報
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="120">企業:</th>
                        <td>
                            <i class="fas fa-building me-1 text-primary"></i>
                            <strong><?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <th>拠点:</th>
                        <td>
                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                            <?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                    <tr>
                        <th>産業医:</th>
                        <td>
                            <i class="fas fa-user-md me-1 text-info"></i>
                            <strong><?= htmlspecialchars($contract['doctor_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="140">定期訪問時間:</th>
                        <?php if ($contract['visit_frequency'] === 'spot'): ?>
                            <td>-</td>
                        <?php else: ?>
                            <td><span class="badge bg-info"><?= format_total_hours($contract['regular_visit_hours'] ?? 0) ?></span></td>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <th>訪問頻度:</th>
                        <td>
                            <?php
                            $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
                            $frequencyInfo = get_visit_frequency_info($visitFrequency);
                            ?>
                            <span class="badge bg-<?= $frequencyInfo['class'] ?>">
                                <i class="<?= $frequencyInfo['icon'] ?> me-1"></i>
                                <?= htmlspecialchars($frequencyInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($visitFrequency === 'bimonthly'): ?>
                                <span class="badge bg-secondary ms-1">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= ($contract['bimonthly_type'] ?? 'even') === 'even' ? '偶数月' : '奇数月' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>期間:</th>
                        <td><strong><?= $year ?>年<?= $month ?>月</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- フィルター -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url("service_records/admin/{$contract['id']}/detail") ?>" class="row g-3">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">
            <div class="col-md-2">
                <label for="status" class="form-label">ステータス</label>
                <select class="form-select" id="status" name="status">
                    <option value="">全て</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>承認待ち</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>承認済み</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>差戻し</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="service_type" class="form-label">役務種別</label>
                <select class="form-select" id="service_type" name="service_type">
                    <option value="">全て</option>
                    <option value="regular" <?= ($_GET['service_type'] ?? '') === 'regular' ? 'selected' : '' ?>>定期訪問</option>
                    <option value="emergency" <?= ($_GET['service_type'] ?? '') === 'emergency' ? 'selected' : '' ?>>臨時訪問</option>
                    <option value="spot" <?= ($_GET['service_type'] ?? '') === 'spot' ? 'selected' : '' ?>>スポット</option>
                    <option value="document" <?= ($_GET['service_type'] ?? '') === 'document' ? 'selected' : '' ?>>書面作成</option>
                    <option value="remote_consultation" <?= ($_GET['service_type'] ?? '') === 'remote_consultation' ? 'selected' : '' ?>>遠隔相談</option>
                    <option value="other" <?= ($_GET['service_type'] ?? '') === 'other' ? 'selected' : '' ?>>その他</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="visit_type" class="form-label">実施方法</label>
                <select class="form-select" id="visit_type" name="visit_type">
                    <option value="">全て</option>
                    <option value="visit" <?= ($_GET['visit_type'] ?? '') === 'visit' ? 'selected' : '' ?>>訪問</option>
                    <option value="online" <?= ($_GET['visit_type'] ?? '') === 'online' ? 'selected' : '' ?>>オンライン</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-filter me-1"></i>フィルター
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="<?= base_url("service_records/admin/{$contract['id']}/detail?year={$year}&month={$month}") ?>" 
                   class="btn btn-outline-secondary d-block w-100">
                    <i class="fas fa-redo me-1"></i>リセット
                </a>
            </div>
        </form>
    </div>
</div>

<!-- モバイル用並び替え (1200px未満) -->
<div class="mobile-sort-section d-xl-none mb-3">
    <label for="mobile_detail_sort" class="form-label">並び替え</label>
    <select class="form-select" id="mobile_detail_sort">
        <option value="service_date-desc" <?= ($sortColumn ?? 'service_date') === 'service_date' && ($sortOrder ?? 'desc') === 'desc' ? 'selected' : '' ?>>
            日付(新しい順)
        </option>
        <option value="service_date-asc" <?= ($sortColumn ?? '') === 'service_date' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            日付(古い順)
        </option>
        <option value="service_type-asc" <?= ($sortColumn ?? '') === 'service_type' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            役務種別(昇順)
        </option>
        <option value="service_type-desc" <?= ($sortColumn ?? '') === 'service_type' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            役務種別(降順)
        </option>
        <option value="visit_type-asc" <?= ($sortColumn ?? '') === 'visit_type' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            実施方法(昇順)
        </option>
        <option value="visit_type-desc" <?= ($sortColumn ?? '') === 'visit_type' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            実施方法(降順)
        </option>
        <option value="service_hours-desc" <?= ($sortColumn ?? '') === 'service_hours' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            役務時間(長い順)
        </option>
        <option value="service_hours-asc" <?= ($sortColumn ?? '') === 'service_hours' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            役務時間(短い順)
        </option>
        <option value="status-asc" <?= ($sortColumn ?? '') === 'status' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            ステータス(昇順)
        </option>
        <option value="status-desc" <?= ($sortColumn ?? '') === 'status' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            ステータス(降順)
        </option>
    </select>
</div>

<!-- 役務記録一覧 -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>役務記録一覧
                    <?php if (!empty($status)): ?>
                        <span class="badge bg-<?php
                            switch($status) {
                                case 'approved': echo 'success'; break;
                                case 'pending': echo 'warning'; break;
                                case 'rejected': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                        ?>"><?= get_status_label($status) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['visit_type'])): ?>
                        <span class="badge bg-<?= $_GET['visit_type'] === 'visit' ? 'primary' : 'info' ?>">
                            <i class="fas fa-<?= $_GET['visit_type'] === 'visit' ? 'walking' : 'laptop' ?> me-1"></i>
                            <?= $_GET['visit_type'] === 'visit' ? '訪問のみ' : 'オンラインのみ' ?>
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-primary"><?= $pagination['total_records'] ?>件</span>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <!-- ページサイズ選択 -->
                    <div class="d-flex align-items-center">
                        <label for="page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                        <select id="page_size" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" <?= ($pagination['per_page'] == 10) ? 'selected' : '' ?>>10件</option>
                            <option value="20" <?= ($pagination['per_page'] == 20) ? 'selected' : '' ?>>20件</option>
                            <option value="50" <?= ($pagination['per_page'] == 50) ? 'selected' : '' ?>>50件</option>
                            <option value="100" <?= ($pagination['per_page'] == 100) ? 'selected' : '' ?>>100件</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($records)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">指定条件の役務記録がありません。</p>
            </div>
        <?php else: ?>
            <!-- デスクトップ表示（1200px以上） -->
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
                            <th>開始時刻</th>
                            <th>終了時刻</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="service_hours" title="役務時間で並び替え">
                                    役務時間
                                    <?php if (($sortColumn ?? '') === 'service_hours'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="service_type" title="種別で並び替え">
                                    種別
                                    <?php if (($sortColumn ?? '') === 'service_type'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="visit_type" title="実施方法で並び替え">
                                    実施方法
                                    <?php if (($sortColumn ?? '') === 'visit_type'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>役務内容</th>
                            <th>延長理由</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="status" title="ステータスで並び替え">
                                    ステータス
                                    <?php if (($sortColumn ?? '') === 'status'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>承認日時</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <?php
                            // 役務種別の安全な取得
                            $serviceType = $record['service_type'] ?? 'regular';
                            $visitType = $record['visit_type'] ?? 'visit';
                            $hasTimeRecord = in_array($serviceType, ['regular', 'emergency', 'extension', 'spot']);
                            $requiresVisitType = in_array($serviceType, ['regular', 'emergency', 'extension', 'spot']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= format_date($record['service_date']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php 
                                        $dayOfWeek = date('w', strtotime($record['service_date']));
                                        $days = ['日', '月', '火', '水', '木', '金', '土'];
                                        echo $days[$dayOfWeek];
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($hasTimeRecord && !empty($record['start_time'])): ?>
                                        <?= date('H:i', strtotime($record['start_time'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hasTimeRecord): ?>
                                        <?php if (($record['service_hours'] ?? 0) > 0 && !empty($record['end_time'])): ?>
                                            <?= date('H:i', strtotime($record['end_time'])) ?>
                                        <?php else: ?>
                                            <span class="text-warning">進行中</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasTimeRecord): ?>
                                        <?php if (($record['service_hours'] ?? 0) > 0): ?>
                                            <span class="badge bg-info"><?= format_service_hours($record['service_hours']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">進行中</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">時間なし</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $serviceTypeInfo = [
                                        'regular' => ['icon' => 'calendar-check', 'class' => 'primary', 'label' => '定期'],
                                        'emergency' => ['icon' => 'exclamation-triangle', 'class' => 'warning', 'label' => '臨時'],
                                        'spot' => ['icon' => 'clock', 'class' => 'info', 'label' => 'スポット'],
                                        'document' => ['icon' => 'file-alt', 'class' => 'primary', 'label' => '書面'],
                                        'remote_consultation' => ['icon' => 'envelope', 'class' => 'secondary', 'label' => '遠隔'],
                                        'other' => ['icon' => 'plus', 'class' => 'dark', 'label' => 'その他']
                                    ];
                                    $typeInfo = $serviceTypeInfo[$serviceType] ?? $serviceTypeInfo['regular'];
                                    ?>
                                    <span class="badge bg-<?= $typeInfo['class'] ?>">
                                        <i class="fas fa-<?= $typeInfo['icon'] ?> me-1"></i>
                                        <?= $typeInfo['label'] ?>
                                    </span>
                                </td>
                                <!-- 実施方法列（新規追加） -->
                                <td class="text-center">
                                    <?php if ($requiresVisitType): ?>
                                        <?php if ($visitType === 'visit'): ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-walking me-1"></i>訪問
                                            </span>
                                            <div class="small text-success mt-1">
                                                <i class="fas fa-train me-1"></i>交通費対象
                                            </div>
                                        <?php elseif ($visitType === 'online'): ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-laptop me-1"></i>オンライン
                                            </span>
                                            <div class="small text-info mt-1">
                                                <i class="fas fa-wifi me-1"></i>交通費対象外
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-question me-1"></i>未設定
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-minus me-1"></i>対象外
                                        </span>
                                        <div class="small text-muted mt-1">実施方法対象外</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($record['description'])): ?>
                                        <span title="<?= htmlspecialchars($record['description'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(mb_substr($record['description'], 0, 50), ENT_QUOTES, 'UTF-8') ?>
                                            <?= mb_strlen($record['description']) > 50 ? '...' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">記載なし</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($record['overtime_reason'])): ?>
                                        <span class="text-warning" title="<?= htmlspecialchars($record['overtime_reason'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(mb_substr($record['overtime_reason'], 0, 30), ENT_QUOTES, 'UTF-8') ?>
                                            <?= mb_strlen($record['overtime_reason']) > 30 ? '...' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php
                                        switch($record['status'] ?? '') {
                                            case 'approved': echo 'success'; break;
                                            case 'pending': echo 'warning'; break;
                                            case 'rejected': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?= get_status_label($record['status'] ?? '') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (($record['status'] ?? '') === 'approved' && !empty($record['approved_at'])): ?>
                                        <small><?= date('Y/m/d H:i', strtotime($record['approved_at'])) ?></small>
                                    <?php elseif (($record['status'] ?? '') === 'rejected' && !empty($record['rejected_at'])): ?>
                                        <small class="text-danger"><?= date('Y/m/d H:i', strtotime($record['rejected_at'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-info" 
                                                onclick="showRecordDetail(<?= $record['id'] ?>)"
                                                title="詳細">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (($record['status'] ?? '') === 'pending'): ?>
                                            <button class="btn btn-outline-success" 
                                                    onclick="approveRecord(<?= $record['id'] ?>)" 
                                                    title="承認">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="showRejectModal(<?= $record['id'] ?>, '<?= htmlspecialchars($record['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>')" 
                                                    title="差戻し">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- モバイル・タブレット表示（1200px未満） -->
            <div class="d-xl-none">
                <?php foreach ($records as $record): ?>
                    <?php
                    // 既存の変数定義をコピー
                    $serviceType = $record['service_type'] ?? 'regular';
                    $visitType = $record['visit_type'] ?? 'visit';
                    $hasTimeRecord = in_array($serviceType, ['regular', 'emergency', 'extension']);
                    $requiresVisitType = in_array($serviceType, ['regular', 'emergency', 'extension']);
                    
                    $serviceTypeInfo = [
                        'regular' => ['icon' => 'calendar-check', 'class' => 'primary', 'label' => '定期'],
                        'emergency' => ['icon' => 'exclamation-triangle', 'class' => 'warning', 'label' => '臨時'],
                        'spot' => ['icon' => 'clock', 'class' => 'info', 'label' => 'スポット'],
                        'document' => ['icon' => 'file-alt', 'class' => 'primary', 'label' => '書面'],
                        'remote_consultation' => ['icon' => 'envelope', 'class' => 'secondary', 'label' => '遠隔'],
                        'other' => ['icon' => 'plus', 'class' => 'dark', 'label' => 'その他']
                    ];
                    $typeInfo = $serviceTypeInfo[$serviceType] ?? $serviceTypeInfo['regular'];
                    
                    $statusInfo = [
                        'pending' => ['class' => 'warning', 'label' => '承認待ち', 'icon' => 'clock'],
                        'approved' => ['class' => 'success', 'label' => '承認済み', 'icon' => 'check-circle'],
                        'rejected' => ['class' => 'danger', 'label' => '差戻し', 'icon' => 'times-circle'],
                        'finalized' => ['class' => 'dark', 'label' => '締済み', 'icon' => 'lock']
                    ];
                    $status = $statusInfo[$record['status'] ?? 'pending'];
                    ?>
                    
                    <div class="card mobile-detail-card mb-3">
                        <div class="card-body">
                            <!-- ヘッダー: 日付・ステータス -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-calendar me-1"></i>
                                        <strong><?= format_date($record['service_date']) ?></strong>
                                    </h6>
                                    <small class="text-muted">
                                        <?php 
                                        $dayOfWeek = date('w', strtotime($record['service_date']));
                                        $days = ['日', '月', '火', '水', '木', '金', '土'];
                                        echo $days[$dayOfWeek] . '曜日';
                                        ?>
                                    </small>
                                </div>
                                
                                <!-- ステータスバッジ -->
                                <span class="badge bg-<?= $status['class'] ?>">
                                    <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                    <?= $status['label'] ?>
                                </span>
                            </div>
                            
                            <!-- 基本情報 -->
                            <div class="row g-2 mb-3">
                                <!-- 役務種別 -->
                                <div class="col-6">
                                    <small class="text-muted d-block">役務種別</small>
                                    <span class="badge bg-<?= $typeInfo['class'] ?>">
                                        <i class="fas fa-<?= $typeInfo['icon'] ?> me-1"></i>
                                        <?= $typeInfo['label'] ?>
                                    </span>
                                </div>
                                
                                <!-- 役務時間 -->
                                <div class="col-6">
                                    <small class="text-muted d-block">役務時間</small>
                                    <?php if ($hasTimeRecord): ?>
                                        <?php if (($record['service_hours'] ?? 0) > 0): ?>
                                            <span class="badge bg-info"><?= format_service_hours($record['service_hours']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">進行中</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">時間なし</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- 実施方法 -->
                                <?php if ($requiresVisitType): ?>
                                    <div class="col-6">
                                        <small class="text-muted d-block">実施方法</small>
                                        <?php if ($visitType === 'visit'): ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-walking me-1"></i>訪問
                                            </span>
                                            <div class="small text-success mt-1">
                                                <i class="fas fa-train me-1"></i>交通費対象
                                            </div>
                                        <?php elseif ($visitType === 'online'): ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-laptop me-1"></i>オンライン
                                            </span>
                                            <div class="small text-info mt-1">
                                                <i class="fas fa-wifi me-1"></i>交通費対象外
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- 実施時間 -->
                                <?php if ($hasTimeRecord && !empty($record['start_time'])): ?>
                                    <div class="col-6">
                                        <small class="text-muted d-block">実施時間</small>
                                        <small class="text-dark">
                                            <?= date('H:i', strtotime($record['start_time'])) ?>
                                            <?php if (!empty($record['end_time'])): ?>
                                                - <?= date('H:i', strtotime($record['end_time'])) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 役務内容 -->
                            <?php if (!empty($record['description'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">役務内容</small>
                                    <div class="description-text border rounded p-2 bg-light">
                                        <?php
                                        $shortDesc = mb_strlen($record['description']) > 80 ? 
                                                    mb_substr($record['description'], 0, 80) . '...' : 
                                                    $record['description'];
                                        ?>
                                        <?= nl2br(htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8')) ?>
                                        <?php if (mb_strlen($record['description']) > 80): ?>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-1" 
                                                    onclick="showFullDescription(this)"
                                                    data-full-description="<?= htmlspecialchars($record['description'], ENT_QUOTES, 'UTF-8') ?>">
                                                <small>続きを読む</small>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 延長理由 -->
                            <?php if (!empty($record['overtime_reason'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">延長理由</small>
                                    <div class="alert alert-warning mb-0 py-2">
                                        <?= nl2br(htmlspecialchars($record['overtime_reason'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 承認日時 -->
                            <?php if (($record['status'] ?? '') === 'approved' && !empty($record['approved_at'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">承認日時</small>
                                    <small class="text-success">
                                        <?= date('Y/m/d H:i', strtotime($record['approved_at'])) ?>
                                    </small>
                                </div>
                            <?php elseif (($record['status'] ?? '') === 'rejected' && !empty($record['rejected_at'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">差戻し日時</small>
                                    <small class="text-danger">
                                        <?= date('Y/m/d H:i', strtotime($record['rejected_at'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 操作ボタン -->
                            <div class="d-grid gap-2">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" 
                                                class="btn btn-outline-info btn-sm w-100" 
                                                onclick="showRecordDetail(<?= $record['id'] ?>)">
                                            <i class="fas fa-eye me-1"></i>詳細
                                        </button>
                                    </div>
                                    
                                    <?php if (($record['status'] ?? '') === 'pending'): ?>
                                        <div class="col-6">
                                            <button class="btn btn-outline-success btn-sm w-100" 
                                                    onclick="approveRecord(<?= $record['id'] ?>)">
                                                <i class="fas fa-check me-1"></i>承認
                                            </button>
                                        </div>
                                        
                                        <div class="col-12">
                                            <button class="btn btn-outline-danger btn-sm w-100" 
                                                    onclick="showRejectModal(<?= $record['id'] ?>, '<?= htmlspecialchars($record['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>')">
                                                <i class="fas fa-times me-1"></i>差戻し
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 凡例 -->
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>役務種別：</strong>
                    <span class="badge bg-primary me-1"><i class="fas fa-calendar-check"></i> 定期</span>
                    <span class="badge bg-warning me-1"><i class="fas fa-exclamation-triangle"></i> 臨時</span>
                    <span class="badge bg-info me-1"><i class="fas fa-clock"></i> 延長</span>
                    <span class="badge bg-primary me-1"><i class="fas fa-file-alt"></i> 書面</span>
                    <span class="badge bg-secondary me-1"><i class="fas fa-envelope"></i> 遠隔</span>
                    　<strong>実施方法：</strong>
                    <span class="badge bg-primary me-1"><i class="fas fa-walking"></i> 訪問</span>
                    <span class="badge bg-info me-1"><i class="fas fa-laptop"></i> オンライン</span>
                    （定期・臨時・延長のみ）
                </small>
            </div>
        <?php endif; ?>
        
        <!-- ページネーション -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="d-none d-md-block">
                    <small class="text-muted">
                        <?= number_format($pagination['start_record']) ?>-<?= number_format($pagination['end_record']) ?>件 
                        (全<?= number_format($pagination['total_records']) ?>件中)
                    </small>
                </div>
                
                <nav aria-label="役務記録詳細ページネーション">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- 前のページ -->
                        <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" 
                               href="<?= $pagination['current_page'] <= 1 ? '#' : buildDetailUrl($pagination['prev_page']) ?>"
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
                                <a class="page-link" href="<?= buildDetailUrl(1) ?>">1</a>
                            </li>
                            <?php if ($start > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildDetailUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end < $pagination['total_pages']): ?>
                            <?php if ($end < $pagination['total_pages'] - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildDetailUrl($pagination['total_pages']) ?>"><?= $pagination['total_pages'] ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 次のページ -->
                        <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                            <a class="page-link" 
                               href="<?= $pagination['current_page'] >= $pagination['total_pages'] ? '#' : buildDetailUrl($pagination['next_page']) ?>"
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

<?php
// ページネーション用URL構築ヘルパー関数
function buildDetailUrl($page) {
    global $contract, $year, $month, $status, $serviceType;
    
    $params = [
        'year' => $year,
        'month' => $month,
        'page' => $page,
        'per_page' => $_GET['per_page'] ?? 20
    ];
    
    if (!empty($status)) {
        $params['status'] = $status;
    }
    
    if (!empty($serviceType)) {
        $params['service_type'] = $serviceType;
    }
    
    if (!empty($_GET['visit_type'])) {
        $params['visit_type'] = $_GET['visit_type'];
    }
    
    return base_url("service_records/admin/{$contract['id']}/detail?" . http_build_query($params));
}
?>

<!-- 役務記録詳細モーダル -->
<div class="modal fade" id="recordDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">役務記録詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="recordDetailContent">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">読み込み中...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 差戻し理由入力モーダル -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">役務記録の差戻し</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <p>産業医: <span id="doctorName"></span></p>
                    <div class="mb-3">
                        <label for="rejectComment" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectComment" name="comment" rows="3" required 
                                  placeholder="差戻す理由を入力してください"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-danger">差戻す</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ページネーション関連スタイル */
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

/* その他既存スタイル */
.table td {
    vertical-align: middle;
}

.table th {
    border-top: none;
}

/* 実施方法統計カードのスタイル */
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

.card.border-dark {
    border-color: #212529 !important;
    border-width: 2px !important;
}

/* 統計カードのアイコンスタイル */
.fa-3x {
    font-size: 3em !important;
}

@media (max-width: 1200px) {
    /* 大画面でも一部列を非表示 */
    .table th:nth-child(7), .table td:nth-child(7) { display: none; } /* 役務内容 */
    .table th:nth-child(8), .table td:nth-child(8) { display: none; } /* 延長理由 */
}

@media (max-width: 992px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
    
    /* 中画面では更に列を非表示 */
    .table th:nth-child(6), .table td:nth-child(6) { display: none; } /* 実施方法 */
    .table th:nth-child(10), .table td:nth-child(10) { display: none; } /* 承認日時 */
}

@media (max-width: 768px) {
    /* 小画面では一部列を非表示 */
    .table th:nth-child(2), .table td:nth-child(2) { display: none; } /* 開始時刻 */
    .table th:nth-child(3), .table td:nth-child(3) { display: none; } /* 終了時刻 */
    
    /* ページネーション調整 */
    .pagination .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    /* 極小画面では更に列を非表示 */
    .table th:nth-child(4), .table td:nth-child(4) { display: none; } /* 役務時間 */
    
    /* モバイルページネーション最適化 */
    .pagination .page-link {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
        min-width: 32px;
        text-align: center;
    }
}

/* 実施方法による行の強調 */
.table tbody tr:has(.badge:contains("訪問")) {
    border-left: 3px solid #0d6efd;
}

.table tbody tr:has(.badge:contains("オンライン")) {
    border-left: 3px solid #0dcaf0;
}

/* アニメーション効果 */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.table tbody tr {
    animation: fadeIn 0.3s ease-out;
}

/* ホバーエフェクト */
.btn:hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

/* 統計カードのアニメーション */
.card:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* ========== モバイル詳細カード用スタイル ========== */

.mobile-detail-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-detail-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.mobile-detail-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

.mobile-detail-card .description-text {
    font-size: 0.875rem;
    line-height: 1.4;
    word-wrap: break-word;
}

/* タブレット表示調整 */
@media (min-width: 768px) and (max-width: 1199px) {
    .mobile-detail-card .row.g-2 {
        gap: 0.75rem !important;
    }
}

/* モバイル表示調整 */
@media (max-width: 767px) {
    .mobile-detail-card .card-body {
        padding: 1rem;
    }
    
    .mobile-detail-card .card-title {
        font-size: 0.9rem;
    }
    
    .mobile-detail-card .btn-sm {
        font-size: 0.8rem;
        padding: 0.4rem 0.5rem;
    }
    
    .mobile-detail-card .badge {
        font-size: 0.7rem;
    }
}

/* 小型モバイル表示調整 */
@media (max-width: 575px) {
    .mobile-detail-card .card-body {
        padding: 0.75rem;
    }
    
    .mobile-detail-card small {
        font-size: 0.7rem;
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
// ページサイズ変更時の処理
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
            const currentOrder = currentUrl.searchParams.get('order') || 'desc';
            
            // 同じ列をクリックした場合は昇順・降順を切り替え
            if (currentSort === sortColumn) {
                const newOrder = currentOrder === 'desc' ? 'asc' : 'desc';
                currentUrl.searchParams.set('order', newOrder);
            } else {
                // 新しい列の場合
                currentUrl.searchParams.set('sort', sortColumn);
                // service_dateはデフォルトで降順、他は昇順
                currentUrl.searchParams.set('order', sortColumn === 'service_date' ? 'desc' : 'asc');
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
    const mobileSortSelect = document.getElementById('mobile_detail_sort');
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

    const pageSizeSelect = document.getElementById('page_size');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            const newPageSize = this.value;
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('per_page', newPageSize);
            currentUrl.searchParams.set('page', '1'); // ページサイズ変更時は1ページ目に戻る
            window.location.href = currentUrl.toString();
        });
    }
    
    // フィルター変更時に1ページ目に戻す
    const filterElements = document.querySelectorAll('#status, #service_type, #visit_type');
    filterElements.forEach(element => {
        element.addEventListener('change', function() {
            this.form.submit();
        });
    });
});

function showRecordDetail(recordId) {
    const modal = new bootstrap.Modal(document.getElementById('recordDetailModal'));
    const content = document.getElementById('recordDetailContent');
    
    // ローディング表示
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">読み込み中...</p>
        </div>
    `;
    
    modal.show();
    
    // 役務記録詳細を表示
    const records = <?= json_encode($records) ?>;
    const record = records.find(r => r.id == recordId);
    
    if (record) {
        setTimeout(() => {
            const serviceTypeInfo = {
                'regular': { icon: 'calendar-check', class: 'primary', label: '定期訪問' },
                'emergency': { icon: 'exclamation-triangle', class: 'warning', label: '臨時訪問' },
                'spot': { icon: 'clock', class: 'info', label: 'スポット' },
                'document': { icon: 'file-alt', class: 'primary', label: '書面作成' },
                'remote_consultation': { icon: 'envelope', class: 'secondary', label: '遠隔相談' },
                'other': { icon: 'plus', class: 'dark', label: 'その他' }
            };
            const serviceType = serviceTypeInfo[record.service_type || 'regular'];
            const hasTimeRecord = ['regular', 'emergency', 'extension', 'spot'].includes(record.service_type || 'regular');
            const requiresVisitType = ['regular', 'emergency', 'extension', 'spot'].includes(record.service_type || 'regular');
            
            // 実施方法の表示
            let visitTypeDisplay = '<span class="text-muted">対象外</span>';
            if (requiresVisitType) {
                if (record.visit_type === 'visit') {
                    visitTypeDisplay = '<span class="badge bg-primary"><i class="fas fa-walking me-1"></i>訪問</span><br><small class="text-success"><i class="fas fa-train me-1"></i>交通費対象</small>';
                } else if (record.visit_type === 'online') {
                    visitTypeDisplay = '<span class="badge bg-info"><i class="fas fa-laptop me-1"></i>オンライン</span><br><small class="text-info"><i class="fas fa-wifi me-1"></i>交通費対象外</small>';
                } else {
                    visitTypeDisplay = '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i>未設定</span>';
                }
            }
            
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>基本情報</h6>
                        <table class="table table-sm">
                            <tr><th>日付</th><td>${record.service_date || '-'}</td></tr>
                            ${hasTimeRecord ? `
                                <tr><th>開始時刻</th><td>${record.start_time || '-'}</td></tr>
                                <tr><th>終了時刻</th><td>${record.end_time || '進行中'}</td></tr>
                                <tr><th>役務時間</th><td>${(record.service_hours || 0) > 0 ? formatServiceHours(record.service_hours) : '進行中'}</td></tr>
                            ` : `
                                <tr><th>時間記録</th><td><span class="text-muted">時間記録なし</span></td></tr>
                            `}
                            <tr><th>役務種別</th><td>
                                <span class="badge bg-${serviceType.class}">
                                    <i class="fas fa-${serviceType.icon} me-1"></i>
                                    ${serviceType.label}
                                </span>
                            </td></tr>
                            <tr><th>実施方法</th><td>${visitTypeDisplay}</td></tr>
                            <tr><th>ステータス</th><td>
                                <span class="badge bg-${getStatusClass(record.status)}">
                                    ${getStatusLabel(record.status)}
                                </span>
                            </td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>詳細情報</h6>
                        <table class="table table-sm">
                            <tr><th>作成日時</th><td>${formatDateTime(record.created_at)}</td></tr>
                            <tr><th>更新日時</th><td>${formatDateTime(record.updated_at)}</td></tr>
                            ${record.approved_at ? `<tr><th>承認日時</th><td>${formatDateTime(record.approved_at)}</td></tr>` : ''}
                            ${record.rejected_at ? `<tr><th>差戻し日時</th><td>${formatDateTime(record.rejected_at)}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h6>役務内容</h6>
                        <div class="border rounded p-3 bg-light">
                            ${record.description || '<span class="text-muted">記載なし</span>'}
                        </div>
                        ${record.overtime_reason ? `
                            <h6 class="mt-3">延長理由</h6>
                            <div class="border rounded p-3 bg-warning bg-opacity-10">
                                ${record.overtime_reason}
                            </div>
                        ` : ''}
                        ${record.comment ? `
                            <h6 class="mt-3">差戻し理由</h6>
                            <div class="border rounded p-3 bg-danger bg-opacity-10">
                                ${record.comment}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }, 300);
    } else {
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                役務記録の詳細取得に失敗しました。
            </div>
        `;
    }
}

function approveRecord(recordId) {
    if (confirm('この役務記録を承認しますか？')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('') ?>service_records/' + recordId + '/approve';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(recordId, doctorName) {
    document.getElementById('doctorName').textContent = doctorName;
    document.getElementById('rejectForm').action = '<?= base_url('') ?>service_records/' + recordId + '/reject';
    document.getElementById('rejectComment').value = '';
    
    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

// ヘルパー関数
function getStatusClass(status) {
    const classes = {
        'approved': 'success',
        'pending': 'warning',
        'rejected': 'danger',
        'finalized': 'dark'
    };
    return classes[status] || 'secondary';
}

function getStatusLabel(status) {
    const labels = {
        'approved': '承認済み',
        'pending': '承認待ち',
        'rejected': '差戻し',
        'finalized': '締済み'
    };
    return labels[status] || status;
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('ja-JP') + ' ' + date.toLocaleTimeString('ja-JP', {hour: '2-digit', minute: '2-digit'});
}

function formatServiceHours(hours) {
    if (!hours || hours <= 0) return '0時間';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (m > 0) {
        return `${h}時間${m}分`;
    } else {
        return `${h}時間`;
    }
}

// 説明文の展開機能
function showFullDescription(button) {
    const descriptionDiv = button.closest('.description-text');
    const fullDescription = button.getAttribute('data-full-description');
    
    if (button.textContent.includes('続きを読む')) {
        descriptionDiv.innerHTML = `
            ${fullDescription.replace(/\n/g, '<br>')}
            <button type="button" class="btn btn-link btn-sm p-0 ms-1" 
                    onclick="showFullDescription(this)"
                    data-full-description="${fullDescription.replace(/"/g, '&quot;')}">
                <small>折りたたむ</small>
            </button>
        `;
    } else {
        const shortDesc = fullDescription.length > 80 ? 
                         fullDescription.substring(0, 80) + '...' : fullDescription;
        descriptionDiv.innerHTML = `
            ${shortDesc.replace(/\n/g, '<br>')}
            <button type="button" class="btn btn-link btn-sm p-0 ms-1" 
                    onclick="showFullDescription(this)"
                    data-full-description="${fullDescription.replace(/"/g, '&quot;')}">
                <small>続きを読む</small>
            </button>
        `;
    }
}
</script>