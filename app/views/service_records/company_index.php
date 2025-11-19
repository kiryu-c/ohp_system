<?php
// app/views/service_records/company_index.php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-clipboard-list me-2"></i>役務記録</h2>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
    </a>
</div>

<!-- 検索・フィルター -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('service_records') ?>" class="row g-3" id="filterForm">
            <div class="col-md-2 col-sm-6">
                <label for="year" class="form-label">年</label>
                <select class="form-select" id="year" name="year">
                    <option value="">全て</option>
                    <?php for($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                        <option value="<?= $i ?>" <?= ($_GET['year'] ?? date('Y')) == $i ? 'selected' : '' ?>><?= $i ?>年</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="month" class="form-label">月</label>
                <select class="form-select" id="month" name="month">
                    <option value="">全て</option>
                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= ($_GET['month'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?>月</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="contract" class="form-label">契約</label>
                <select class="form-select" id="contract" name="contract">
                    <option value="">全て</option>
                    <?php if (!empty($contracts)): ?>
                        <?php foreach ($contracts as $contract): ?>
                            <option value="<?= $contract['id'] ?>" <?= ($_GET['contract'] ?? '') == $contract['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($contract['doctor_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="status" class="form-label">ステータス</label>
                <select class="form-select" id="status" name="status">
                    <option value="">全て</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>承認待ち</option>
                    <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>承認済み</option>
                    <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>差戻し</option>
                    <option value="finalized" <?= ($_GET['status'] ?? '') === 'finalized' ? 'selected' : '' ?>>締め済み</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="service_type" class="form-label">役務種別</label>
                <select class="form-select" id="service_type" name="service_type">
                    <option value="">全て</option>
                    <?php if (in_array('regular', $availableServiceTypes ?? [])): ?>
                    <option value="regular" <?= ($_GET['service_type'] ?? '') === 'regular' ? 'selected' : '' ?>>定期訪問</option>
                    <?php endif; ?>
                    <?php if (in_array('emergency', $availableServiceTypes ?? [])): ?>
                    <option value="emergency" <?= ($_GET['service_type'] ?? '') === 'emergency' ? 'selected' : '' ?>>臨時訪問</option>
                    <?php endif; ?>
                    <?php if (in_array('spot', $availableServiceTypes ?? [])): ?>
                    <option value="spot" <?= ($_GET['service_type'] ?? '') === 'spot' ? 'selected' : '' ?>>スポット</option>
                    <?php endif; ?>
                    <?php if (in_array('document', $availableServiceTypes ?? [])): ?>
                    <option value="document" <?= ($_GET['service_type'] ?? '') === 'document' ? 'selected' : '' ?>>書面作成</option>
                    <?php endif; ?>
                    <?php if (in_array('remote_consultation', $availableServiceTypes ?? [])): ?>
                    <option value="remote_consultation" <?= ($_GET['service_type'] ?? '') === 'remote_consultation' ? 'selected' : '' ?>>遠隔相談</option>
                    <?php endif; ?>
                    <option value="other" <?= ($_GET['service_type'] ?? '') === 'other' ? 'selected' : '' ?>>その他</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="doctor" class="form-label">産業医</label>
                <select class="form-select" id="doctor" name="doctor">
                    <option value="">全て</option>
                    <?php if (!empty($doctors)): ?>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= $doctor['id'] ?>" <?= ($_GET['doctor'] ?? '') == $doctor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        <span class="ms-1">検索</span>
                    </button>
                    <a href="<?= base_url('service_records') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                        <span class="ms-1">リセット</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- モバイル用並び替え (1024px未満) - 検索フィルターの直後に追加 -->
<div class="mobile-sort-section d-lg-none mb-3">
    <label for="mobile_sort" class="form-label">並び替え</label>
    <select class="form-select" id="mobile_sort">
        <option value="service_date-desc" <?= ($sortColumn ?? 'service_date') === 'service_date' && ($sortOrder ?? 'desc') === 'desc' ? 'selected' : '' ?>>
            日付(新しい順)
        </option>
        <option value="service_date-asc" <?= ($sortColumn ?? '') === 'service_date' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            日付(古い順)
        </option>
        <option value="doctor_name-asc" <?= ($sortColumn ?? '') === 'doctor_name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            産業医名(昇順)
        </option>
        <option value="doctor_name-desc" <?= ($sortColumn ?? '') === 'doctor_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            産業医名(降順)
        </option>
        <option value="branch_name-asc" <?= ($sortColumn ?? '') === 'branch_name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            拠点名(昇順)
        </option>
        <option value="branch_name-desc" <?= ($sortColumn ?? '') === 'branch_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            拠点名(降順)
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
                    <i class="fas fa-list me-2"></i>役務記録一覧
                    <?php if (!empty($records)): ?>
                        <span class="badge bg-primary"><?= $pagination['total_records'] ?>件</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <?php 
                    // 承認待ちの記録をカウント
                    $pendingCount = count(array_filter($records, function($r) { return $r['status'] === 'pending'; }));
                    ?>
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-warning me-2"><?= $pendingCount ?>件承認待ち</span>
                        <button type="button" class="btn btn-sm btn-success me-2" onclick="showBulkApproveModal()" title="選択した記録を一括承認" disabled id="bulkApproveBtn">
                            <i class="fas fa-check-double me-1"></i>
                            <span class="d-none d-md-inline">一括承認</span>
                        </button>
                    <?php endif; ?>
                    
                    <!-- ページサイズ選択（ダッシュボード統一） -->
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
    <div class="card-body p-0">
        <?php if (empty($records)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">表示する役務記録がありません</p>
            </div>
        <?php else: ?>
            <!-- デスクトップ用テーブル (1024px以上) -->
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php if ($pendingCount > 0): ?>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllRecords" onclick="toggleAllRecords()">
                                </th>
                            <?php endif; ?>
                            <th style="width: 110px;">
                                <a href="#" class="text-decoration-none text-dark sort-link" data-sort="service_date">
                                    役務日
                                    <?php if ($sortColumn === 'service_date'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'desc' ? 'down' : 'up' ?> ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="text-decoration-none text-dark sort-link" data-sort="doctor_name">
                                    産業医
                                    <?php if ($sortColumn === 'doctor_name'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'desc' ? 'down' : 'up' ?> ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="text-decoration-none text-dark sort-link" data-sort="branch_name">
                                    拠点
                                    <?php if ($sortColumn === 'branch_name'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'desc' ? 'down' : 'up' ?> ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="text-decoration-none text-dark sort-link" data-sort="service_type">
                                    役務種別
                                    <?php if ($sortColumn === 'service_type'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'desc' ? 'down' : 'up' ?> ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="text-decoration-none text-dark sort-link" data-sort="visit_type">
                                    実施方法
                                    <?php if ($sortColumn === 'visit_type'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'desc' ? 'down' : 'up' ?> ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th style="width: 80px;">
                                <a href="#" class="text-decoration-none text-dark sort-link" data-sort="service_hours">
                                    役務時間
                                    <?php if ($sortColumn === 'service_hours'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'desc' ? 'down' : 'up' ?> ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th style="width: 120px;">
                                <a href="#" class="text-decoration-none text-dark sort-link" data-sort="status">
                                    ステータス
                                    <?php if ($sortColumn === 'status'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'desc' ? 'down' : 'up' ?> ms-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th style="width: 250px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <?php
                            // 進行中かどうかの判定（役務時間が0の場合、ただし書面・遠隔は除く）
                            $isInProgress = !in_array($record['service_type'], ['document', 'remote_consultation', 'other']) && 
                                           ($record['service_hours'] == 0 || $record['service_hours'] === null);
                            ?>
                            <tr<?= $isInProgress ? ' class="table-secondary"' : '' ?>>
                                <?php if ($pendingCount > 0): ?>
                                    <td>
                                        <?php if ($record['status'] === 'pending'): ?>
                                            <input type="checkbox" class="record-checkbox" value="<?= $record['id'] ?>" onchange="updateBulkApproveButton()" <?= $isInProgress ? 'disabled title="進行中の役務は承認できません"' : '' ?>>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= date('Y/m/d', strtotime($record['service_date'])) ?></td>
                                <td><?= htmlspecialchars($record['doctor_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($record['branch_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                    $serviceTypes = [
                                        'regular' => '<span class="badge bg-success">定期訪問</span>',
                                        'emergency' => '<span class="badge bg-warning">臨時訪問</span>',
                                        'extension' => '<span class="badge bg-info">定期延長</span>',
                                        'spot' => '<span class="badge bg-info">スポット</span>',
                                        'document' => '<span class="badge bg-primary">書面作成</span>',
                                        'remote_consultation' => '<span class="badge bg-secondary">遠隔相談</span>',
                                        'other' => '<span class="badge bg-dark">その他</span>'
                                    ];
                                    echo $serviceTypes[$record['service_type']] ?? $record['service_type'];
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if (in_array($record['service_type'], ['document', 'remote_consultation', 'other'])) {
                                        echo '<span class="text-muted small">対象外</span>';
                                    } else {
                                        $visitTypes = [
                                            'visit' => '<span class="badge bg-primary"><i class="fas fa-building me-1"></i>訪問</span>',
                                            'online' => '<span class="badge bg-info"><i class="fas fa-video me-1"></i>オンライン</span>'
                                        ];
                                        echo $visitTypes[$record['visit_type']] ?? '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end">
                                    <?php if (in_array($record['service_type'], ['document', 'remote_consultation', 'other'])): ?>
                                        <span class="text-muted small">対象外</span>
                                    <?php elseif ($isInProgress): ?>
                                        <span class="badge bg-warning">進行中</span>
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-info-circle"></i> 承認不可
                                        </div>
                                    <?php else: ?>
                                        <?= format_hours_minutes($record['service_hours']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'pending' => '<span class="badge bg-warning">承認待ち</span>',
                                        'approved' => '<span class="badge bg-success">承認済み</span>',
                                        'rejected' => '<span class="badge bg-danger">差戻し</span>',
                                        'finalized' => '<span class="badge bg-secondary">締め済み</span>'
                                    ];
                                    echo $statusBadges[$record['status']] ?? $record['status'];
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="showRecordDetail(<?= $record['id'] ?>)" title="詳細を表示">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($record['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="approveRecord(<?= $record['id'] ?>)" title="承認" <?= $isInProgress ? 'disabled' : '' ?>>
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="showRejectModal(<?= $record['id'] ?>)" title="差戻し" <?= $isInProgress ? 'disabled' : '' ?>>
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($record['status'] === 'approved'): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="unapproveRecord(<?= $record['id'] ?>)" title="承認取消">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- モバイル用カード表示 (1024px未満) -->
            <div class="d-lg-none">
                <?php if ($pendingCount > 0): ?>
                    <div class="p-3 border-bottom bg-light">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllRecordsMobile" onclick="toggleAllRecordsMobile()">
                            <label class="form-check-label" for="selectAllRecordsMobile">
                                全て選択
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php foreach ($records as $record): ?>
                    <?php
                    // 進行中かどうかの判定（役務時間が0の場合、ただし書面・遠隔は除く）
                    $isInProgress = !in_array($record['service_type'], ['document', 'remote_consultation', 'other']) && 
                                   ($record['service_hours'] == 0 || $record['service_hours'] === null);
                    ?>
                    <div class="record-card border-bottom p-3<?= $isInProgress ? ' bg-light' : '' ?>" data-record-id="<?= $record['id'] ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <?php if ($pendingCount > 0 && $record['status'] === 'pending'): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input record-checkbox-mobile" type="checkbox" value="<?= $record['id'] ?>" onchange="updateBulkApproveButtonMobile()" <?= $isInProgress ? 'disabled title="進行中の役務は承認できません"' : '' ?>>
                                    </div>
                                <?php endif; ?>
                                <h6 class="mb-1">
                                    <?= date('Y/m/d', strtotime($record['service_date'])) ?>
                                    <?php
                                    $statusBadges = [
                                        'pending' => '<span class="badge bg-warning ms-2">承認待ち</span>',
                                        'approved' => '<span class="badge bg-success ms-2">承認済み</span>',
                                        'rejected' => '<span class="badge bg-danger ms-2">差戻し</span>',
                                        'finalized' => '<span class="badge bg-secondary ms-2">締め済み</span>'
                                    ];
                                    echo $statusBadges[$record['status']] ?? $record['status'];
                                    ?>
                                </h6>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-user-md me-1"></i><?= htmlspecialchars($record['doctor_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-building me-1"></i><?= htmlspecialchars($record['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="mb-2">
                                    <?php
                                    $serviceTypes = [
                                        'regular' => '<span class="badge bg-success me-1">定期訪問</span>',
                                        'emergency' => '<span class="badge bg-warning me-1">臨時訪問</span>',
                                        'extension' => '<span class="badge bg-info me-1">定期延長</span>',
                                        'spot' => '<span class="badge bg-info me-1">スポット</span>',
                                        'document' => '<span class="badge bg-primary me-1">書面作成</span>',
                                        'remote_consultation' => '<span class="badge bg-secondary me-1">遠隔相談</span>',
                                        'other' => '<span class="badge bg-dark me-1">その他</span>'
                                    ];
                                    echo $serviceTypes[$record['service_type']] ?? $record['service_type'];
                                    ?>
                                    
                                    <?php if (!in_array($record['service_type'], ['document', 'remote_consultation', 'other'])): ?>
                                        <?php
                                        $visitTypes = [
                                            'visit' => '<span class="badge bg-primary"><i class="fas fa-building me-1"></i>訪問</span>',
                                            'online' => '<span class="badge bg-info"><i class="fas fa-video me-1"></i>オンライン</span>'
                                        ];
                                        echo $visitTypes[$record['visit_type']] ?? '';
                                        ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (in_array($record['service_type'], ['document', 'remote_consultation', 'other'])): ?>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>役務時間: 対象外
                                    </div>
                                <?php elseif ($isInProgress): ?>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>役務時間: 
                                        <span class="badge bg-warning">進行中</span>
                                        <span class="ms-1"><i class="fas fa-info-circle"></i> 承認不可</span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>役務時間: <?= format_hours_minutes($record['service_hours']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="showRecordDetail(<?= $record['id'] ?>)">
                                <i class="fas fa-eye me-1"></i>詳細
                            </button>
                            <?php if ($record['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm flex-fill" onclick="approveRecord(<?= $record['id'] ?>)" <?= $isInProgress ? 'disabled' : '' ?>>
                                    <i class="fas fa-check me-1"></i>承認
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm flex-fill" onclick="showRejectModal(<?= $record['id'] ?>)" <?= $isInProgress ? 'disabled' : '' ?>>
                                    <i class="fas fa-times me-1"></i>差戻し
                                </button>
                            <?php elseif ($record['status'] === 'approved'): ?>
                                <button type="button" class="btn btn-outline-warning btn-sm flex-fill" onclick="unapproveRecord(<?= $record['id'] ?>)">
                                    <i class="fas fa-undo me-1"></i>承認取消
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- ページネーション -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                    <p class="mb-0 text-muted small">
                        <?= $pagination['start_record'] ?>-<?= $pagination['end_record'] ?>件 / 全<?= $pagination['total_records'] ?>件
                    </p>
                </div>
                <div class="col-md-6">
                    <nav aria-label="ページネーション">
                        <ul class="pagination pagination-sm justify-content-md-end justify-content-center mb-0">
                            <li class="page-item <?= !$pagination['has_prev'] ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= base_url('service_records?' . http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']]))) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $start = max(1, $pagination['current_page'] - 2);
                            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            if ($start > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= base_url('service_records?' . http_build_query(array_merge($_GET, ['page' => 1]))) ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif;
                            endif;
                            
                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= base_url('service_records?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($end < $pagination['total_pages']): ?>
                                <?php if ($end < $pagination['total_pages'] - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= base_url('service_records?' . http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']]))) ?>"><?= $pagination['total_pages'] ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= base_url('service_records?' . http_build_query(array_merge($_GET, ['page' => $pagination['next_page']]))) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- 詳細モーダル -->
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordDetailModalLabel">
                    <i class="fas fa-clipboard-list me-2"></i>役務記録詳細
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body" id="recordDetailContent">
                <!-- 詳細情報がJavaScriptで動的に挿入されます -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">読み込み中...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 差戻しモーダル -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="fas fa-times-circle me-2 text-danger"></i>役務記録の差戻し
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <form id="rejectForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="source" value="service_records">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="4" required placeholder="差戻しの理由を入力してください"></textarea>
                        <div class="form-text">産業医にこの理由が通知されます</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>差戻す
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 一括承認モーダル -->
<div class="modal fade" id="bulkApproveModal" tabindex="-1" aria-labelledby="bulkApproveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkApproveModalLabel">
                    <i class="fas fa-check-double me-2 text-success"></i>一括承認の確認
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <form id="bulkApproveForm" method="POST" action="<?= base_url('service_records/bulk_approve') ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="source" value="service_records">
                <input type="hidden" name="record_ids" id="bulkApproveRecordIds">
                <div class="modal-body">
                    <p>選択した<strong id="bulkApproveCount">0</strong>件の役務記録を一括承認しますか?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        一括承認後は個別に承認を取り消すことができます
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-double me-1"></i>一括承認する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ページサイズ変更の処理(ダッシュボード統一)
document.getElementById('service_records_page_size').addEventListener('change', function() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('per_page', this.value);
    currentUrl.searchParams.set('page', '1'); // ページを1に戻す
    window.location.href = currentUrl.toString();
});

// ==================== 詳細表示機能 ====================

// 役務時間を「時間分」形式に変換する関数（PHPのformat_hours_minutes()と同等）
function formatServiceHours(decimalHours) {
    if (!decimalHours || decimalHours <= 0) {
        return '0分';
    }
    
    let hours = Math.floor(decimalHours);
    let minutes = Math.round((decimalHours - hours) * 60);
    
    // 60分になった場合は1時間に繰り上げ
    if (minutes >= 60) {
        hours += 1;
        minutes = 0;
    }
    
    if (hours > 0 && minutes > 0) {
        return hours + '時間' + minutes + '分';
    } else if (hours > 0) {
        return hours + '時間';
    } else {
        return minutes + '分';
    }
}

function showRecordDetail(recordId) {
    const modal = new bootstrap.Modal(document.getElementById('recordDetailModal'));
    modal.show();
    
    // モーダル内容を初期化
    document.getElementById('recordDetailContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">読み込み中...</span>
            </div>
        </div>
    `;
    
    // Ajax でデータ取得
    fetch(`<?= base_url('') ?>service_records/${recordId}/detail`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const record = data.record;
                
                // 役務種別ラベル
                const serviceTypeLabels = {
                    'regular': '<span class="badge bg-success">定期訪問</span>',
                    'emergency': '<span class="badge bg-warning">臨時訪問</span>',
                    'extension': '<span class="badge bg-info">定期延長</span>',
                    'spot': '<span class="badge bg-info">スポット</span>',
                    'document': '<span class="badge bg-primary">書面作成</span>',
                    'remote_consultation': '<span class="badge bg-secondary">遠隔相談</span>',
                    'other': '<span class="badge bg-dark">その他</span>'
                };
                
                // 実施方法ラベル
                const visitTypeLabels = {
                    'visit': '<span class="badge bg-primary"><i class="fas fa-building me-1"></i>訪問</span>',
                    'online': '<span class="badge bg-info"><i class="fas fa-video me-1"></i>オンライン</span>'
                };
                
                // ステータスラベル
                const statusLabels = {
                    'pending': '<span class="badge bg-warning">承認待ち</span>',
                    'approved': '<span class="badge bg-success">承認済み</span>',
                    'rejected': '<span class="badge bg-danger">差戻し</span>',
                    'finalized': '<span class="badge bg-secondary">締め済み</span>'
                };
                
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">基本情報</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 120px;">役務日</th>
                                    <td>${record.service_date}</td>
                                </tr>
                                <tr>
                                    <th>産業医</th>
                                    <td>${record.doctor_name}</td>
                                </tr>
                                <tr>
                                    <th>拠点</th>
                                    <td>${record.branch_name}</td>
                                </tr>
                                <tr>
                                    <th>役務種別</th>
                                    <td>${serviceTypeLabels[record.service_type] || record.service_type}</td>
                                </tr>
                `;
                
                if (record.service_type !== 'document') {
                    html += `
                                <tr>
                                    <th>実施方法</th>
                                    <td>${visitTypeLabels[record.visit_type] || '<span class="text-muted">-</span>'}</td>
                                </tr>
                    `;
                }
                
                html += `
                                <tr>
                                    <th>ステータス</th>
                                    <td>${statusLabels[record.status] || record.status}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">時間情報</h6>
                            <table class="table table-sm">
                `;
                
                if (record.service_type === 'document' || record.service_type === 'remote_consultation' || record.service_type === 'other') {
                    html += `
                                <tr>
                                    <th style="width: 120px;">役務時間</th>
                                    <td><span class="text-muted">対象外</span></td>
                                </tr>
                    `;
                } else {
                    html += `
                                <tr>
                                    <th style="width: 120px;">開始時刻</th>
                                    <td>${record.start_time || '<span class="text-muted">未設定</span>'}</td>
                                </tr>
                                <tr>
                                    <th>終了時刻</th>
                                    <td>${record.end_time || '<span class="text-muted">未設定</span>'}</td>
                                </tr>
                                <tr>
                                    <th>役務時間</th>
                                    <td>${formatServiceHours(parseFloat(record.service_hours))}</td>
                                </tr>
                    `;
                }
                
                html += `
                            </table>
                            
                            <h6 class="text-muted mb-2 mt-3">登録情報</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 120px;">登録日時</th>
                                    <td>${record.created_at}</td>
                                </tr>
                `;
                
                if (record.updated_at && record.updated_at !== record.created_at) {
                    html += `
                                <tr>
                                    <th>更新日時</th>
                                    <td>${record.updated_at}</td>
                                </tr>
                    `;
                }
                
                html += `
                            </table>
                        </div>
                    </div>
                `;
                
                // 内容
                if (record.service_content) {
                    html += `
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">役務内容</h6>
                            <div class="p-3 bg-light rounded">
                                ${record.service_content.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                }
                
                // 承認情報
                if (record.status === 'approved' && record.approved_at) {
                    html += `
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">承認情報</h6>
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                ${record.approved_at} に承認されました
                                ${record.approved_by_name ? `<br><small>承認者: ${record.approved_by_name}</small>` : ''}
                            </div>
                        </div>
                    `;
                }
                
                // 差戻し情報
                if (record.status === 'rejected' && record.reject_reason) {
                    html += `
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">差戻し理由</h6>
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${record.reject_reason.replace(/\n/g, '<br>')}
                                ${record.rejected_at ? `<br><small class="text-muted">差戻し日時: ${record.rejected_at}</small>` : ''}
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById('recordDetailContent').innerHTML = html;
            } else {
                document.getElementById('recordDetailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        データの取得に失敗しました
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('recordDetailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    エラーが発生しました
                </div>
            `;
        });
}

// ==================== 承認機能 ====================

function approveRecord(recordId) {
    if (confirm('この役務記録を承認しますか?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?= base_url('') ?>service_records/${recordId}/approve`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        // 役務記録画面から実行されることを明示
        const sourceInput = document.createElement('input');
        sourceInput.type = 'hidden';
        sourceInput.name = 'source';
        sourceInput.value = 'service_records';
        form.appendChild(sourceInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// ==================== 差戻し機能 ====================

function showRejectModal(recordId) {
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    const form = document.getElementById('rejectForm');
    form.action = `<?= base_url('') ?>service_records/${recordId}/reject`;
    document.getElementById('reject_reason').value = '';
    modal.show();
}

// ==================== 一括承認機能 ====================

function showBulkApproveModal() {
    const checkboxes = document.querySelectorAll('.record-checkbox:checked, .record-checkbox-mobile:checked');
    const recordIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (recordIds.length === 0) {
        alert('承認する記録を選択してください');
        return;
    }
    
    document.getElementById('bulkApproveRecordIds').value = recordIds.join(',');
    document.getElementById('bulkApproveCount').textContent = recordIds.length;
    
    const modal = new bootstrap.Modal(document.getElementById('bulkApproveModal'));
    modal.show();
}

function toggleAllRecords() {
    const mainCheckbox = document.getElementById('selectAllRecords');
    const checkboxes = document.querySelectorAll('.record-checkbox');
    checkboxes.forEach(cb => {
        // 無効化されていないチェックボックスのみを切り替え
        if (!cb.disabled) {
            cb.checked = mainCheckbox.checked;
        }
    });
    updateBulkApproveButton();
}

function toggleAllRecordsMobile() {
    const mainCheckbox = document.getElementById('selectAllRecordsMobile');
    const checkboxes = document.querySelectorAll('.record-checkbox-mobile');
    checkboxes.forEach(cb => {
        // 無効化されていないチェックボックスのみを切り替え
        if (!cb.disabled) {
            cb.checked = mainCheckbox.checked;
        }
    });
    updateBulkApproveButtonMobile();
}

function updateBulkApproveButton() {
    const checkboxes = document.querySelectorAll('.record-checkbox:checked');
    const button = document.getElementById('bulkApproveBtn');
    if (button) {
        button.disabled = checkboxes.length === 0;
    }
}

function updateBulkApproveButtonMobile() {
    const checkboxes = document.querySelectorAll('.record-checkbox-mobile:checked');
    const button = document.getElementById('bulkApproveBtn');
    if (button) {
        button.disabled = checkboxes.length === 0;
    }
}

// ==================== イベントリスナー ====================

document.addEventListener('DOMContentLoaded', function() {
    // テーブル行のホバー効果を強化
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        // 実施方法に応じてホバー効果を変える
        let isVisit = false;
        let isOnline = false;
        let isTargetOut = false;
        
        const badges = row.querySelectorAll('.badge');
        badges.forEach(badge => {
            const text = badge.textContent.trim();
            if (badge.classList.contains('bg-primary') && text.includes('訪問')) {
                isVisit = true;
            } else if (badge.classList.contains('bg-info') && text.includes('オンライン')) {
                isOnline = true;
            }
        });
        
        // 対象外の判定
        const targetOutElements = row.querySelectorAll('.text-muted');
        targetOutElements.forEach(element => {
            if (element.textContent.includes('対象外')) {
                isTargetOut = true;
            }
        });
        
        // ハイライト効果を適用
        if (isVisit) {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        } else if (isOnline) {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(13, 202, 240, 0.1)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        } else if (isTargetOut) {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(108, 117, 125, 0.1)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        }
    });
    
    // カード表示のアニメーション効果
    const recordCards = document.querySelectorAll('.record-card');
    recordCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 50}ms`;
        card.classList.add('fade-in');
    });

    // グローバル関数として定義（HTMLから呼び出される）
    window.showRecordDetail = showRecordDetail;
    window.approveRecord = approveRecord;
    window.showRejectModal = showRejectModal;
    window.showBulkApproveModal = showBulkApproveModal;
    window.toggleAllRecords = toggleAllRecords;
    window.toggleAllRecordsMobile = toggleAllRecordsMobile;
    window.updateBulkApproveButton = updateBulkApproveButton;
    window.updateBulkApproveButtonMobile = updateBulkApproveButtonMobile;
});

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

// CSSアニメーション用のクラスを動的に追加
const style = document.createElement('style');
style.textContent = `
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
    
    .fade-in {
        animation: fadeIn 0.3s ease-out forwards;
    }
`;
document.head.appendChild(style);

// 承認取消し機能
function unapproveRecord(recordId) {
    if (confirm('この役務記録の承認を取り消しますか?\n承認待ち状態に戻ります。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?= base_url('') ?>service_records/${recordId}/unapprove`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        // 役務記録画面から実行されることを明示
        const sourceInput = document.createElement('input');
        sourceInput.type = 'hidden';
        sourceInput.name = 'source';
        sourceInput.value = 'service_records';
        form.appendChild(sourceInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// グローバル関数として定義（HTMLから呼び出される）
window.unapproveRecord = unapproveRecord;
</script>