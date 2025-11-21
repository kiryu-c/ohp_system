<?php
// app/views/closing/index.php - 締め処理一覧画面(ワークフロー対応)
$userType = Session::get('user_type');
$currentUser = Session::get('user_name');
$title = '月次締め処理';

$currentMonth = $_GET['month'] ?? date('Y-m');
$monthName = date('Y年m月', strtotime($currentMonth . '-01'));

// フィルターパラメータの取得
$filterServiceType = $_GET['service_type'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['search'] ?? '';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-3">
                        <i class="fas fa-calculator me-2"></i>月次締め処理 - <?= h($monthName) ?>
                    </h4>
                    <div class="month-selector-wrapper">
                        <form method="GET" class="d-flex align-items-center">
                            <label for="month-select" class="form-label me-2 mb-0">対象月:</label>
                            <select id="month-select" name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php 
                                for ($i = -12; $i <= 1; $i++) {
                                    $date = date('Y-m', strtotime($i . ' months'));
                                    $displayName = date('Y年m月', strtotime($date . '-01'));
                                    $selected = ($date === $currentMonth) ? 'selected' : '';
                                    echo "<option value='{$date}' {$selected}>{$displayName}</option>";
                                }
                                ?>
                            </select>
                        </form>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- フィルターエリア -->
                    <div class="filter-area mb-4">
                        <form method="GET" id="filterForm" class="row g-3">
                            <input type="hidden" name="month" value="<?= h($currentMonth) ?>">
                            
                            <div class="col-md-3">
                                <label for="service-type-filter" class="form-label">役務種別</label>
                                <select id="service-type-filter" name="service_type" class="form-select">
                                    <option value="">すべて</option>
                                    <?php foreach ($availableServiceTypes as $englishType => $japaneseType): ?>
                                        <option value="<?= h($englishType) ?>" <?= $filterServiceType === $englishType ? 'selected' : '' ?>>
                                            <?= h($japaneseType) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="status-filter" class="form-label">締め状況</label>
                                <select id="status-filter" name="status" class="form-select">
                                    <option value="">すべて</option>
                                    <option value="not_processed" <?= $filterStatus === 'not_processed' ? 'selected' : '' ?>>未処理</option>
                                    <option value="pending_approval" <?= $filterStatus === 'pending_approval' ? 'selected' : '' ?>>企業承認待ち</option>
                                    <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>企業承認済み</option>
                                    <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>差戻し</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="search-filter" class="form-label">
                                    <?php if ($userType === 'admin'): ?>
                                        検索(企業・拠点・産業医)
                                    <?php elseif ($userType === 'company'): ?>
                                        検索(拠点・産業医)
                                    <?php else: ?>
                                        検索(企業・拠点)
                                    <?php endif; ?>
                                </label>
                                <input type="text" id="search-filter" name="search" class="form-control" 
                                       placeholder="キーワードを入力" value="<?= h($filterSearch) ?>">
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter me-1"></i>絞り込み
                                </button>
                                <a href="?month=<?= h($currentMonth) ?>" class="btn btn-secondary">
                                    <i class="fas fa-undo me-1"></i>クリア
                                </a>
                            </div>
                        </form>
                    </div>

                    <?php if (empty($closingData)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php if ($filterServiceType || $filterStatus || $filterSearch): ?>
                                検索条件に該当する契約がありません。
                            <?php else: ?>
                                該当する契約がありません。
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- フィルター結果表示 -->
                        <?php if ($filterServiceType || $filterStatus || $filterSearch): ?>
                            <?php
                            // 役務種別の日本語変換
                            $serviceTypeLabel = '';
                            if ($filterServiceType && isset($availableServiceTypes[$filterServiceType])) {
                                $serviceTypeLabel = $availableServiceTypes[$filterServiceType];
                            }
                            
                            // 締め状況の日本語変換
                            $statusLabels = [
                                'not_processed' => '未処理',
                                'pending_approval' => '企業承認待ち',
                                'approved' => '企業承認済み',
                                'rejected' => '差戻し'
                            ];
                            $statusLabel = $statusLabels[$filterStatus] ?? '';
                            ?>
                            <div class="alert alert-success mb-3">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= count($closingData) ?>件の契約が見つかりました
                                <?php if ($serviceTypeLabel): ?>
                                    <span class="badge bg-primary ms-2">役務種別: <?= h($serviceTypeLabel) ?></span>
                                <?php endif; ?>
                                <?php if ($statusLabel): ?>
                                    <span class="badge bg-info ms-2">締め状況: <?= h($statusLabel) ?></span>
                                <?php endif; ?>
                                <?php if ($filterSearch): ?>
                                    <span class="badge bg-warning text-dark ms-2">検索: <?= h($filterSearch) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- デスクトップ用テーブル表示 -->
                        <div class="table-responsive desktop-view">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <?php if ($userType !== 'doctor'): ?>
                                            <th>産業医</th>
                                        <?php endif; ?>
                                        <th>会社名</th>
                                        <th>支店名</th>
                                        <th>未承認役務数</th>
                                        <th>未承認交通費数</th>
                                        <th>未締め役務数</th>
                                        <th>締め状況</th>
                                        <th>締め日時</th>
                                        <?php if ($userType !== 'company'): ?>
                                            <th>請求金額</th>
                                        <?php endif; ?>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($closingData as $data): ?>
                                        <?php 
                                        $contract = $data['contract'];
                                        $closingRecord = $data['closing_record'];
                                        $unClosedCount = $data['unclosed_records_count'];
                                        $unapprovedCount = $data['unapproved_records_count'];
                                        $unapprovedTravelCount = $data['unapproved_travel_expenses_count'];
                                        $canProcess = $data['can_process'];
                                        ?>
                                        <tr>
                                            <?php if ($userType !== 'doctor'): ?>
                                                <td><?= h($contract['doctor_name'] ?? '') ?></td>
                                            <?php endif; ?>
                                            <td><?= h($contract['company_name']) ?></td>
                                            <td><?= h($contract['branch_name']) ?></td>
                                            
                                            <td>
                                                <?php if ($unapprovedCount > 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i><?= $unapprovedCount ?>件
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>0件
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <?php if ($unapprovedTravelCount > 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i><?= $unapprovedTravelCount ?>件
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>0件
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <?php if ($unClosedCount > 0): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i><?= $unClosedCount ?>件
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">0件</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <?php if ($closingRecord): ?>
                                                    <?php if ($closingRecord['company_approved'] == 1): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-double me-1"></i>企業承認済み
                                                        </span>
                                                    <?php elseif ($closingRecord['company_rejected_at']): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>差戻し
                                                        </span>
                                                    <?php elseif ($closingRecord['status'] === 'finalized'): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-clock me-1"></i>企業承認待ち
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">未処理</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <?php if ($closingRecord && $closingRecord['finalized_at']): ?>
                                                    <?= date('Y/m/d H:i', strtotime($closingRecord['finalized_at'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <?php if ($userType !== 'company'): ?>
                                            <td>
                                                <?php if ($closingRecord && $closingRecord['total_amount_with_tax']): ?>
                                                    <strong><?= number_format($closingRecord['total_amount_with_tax']) ?>円</strong>
                                                    <?php if ($closingRecord['tax_amount'] > 0): ?>
                                                        <br><small class="text-muted">
                                                            (税抜: <?= number_format($closingRecord['total_amount']) ?>円)
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                            
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <?php if ($userType === 'company'): ?>
                                                        <!-- 企業ユーザーの場合 -->
                                                        <?php if ($canProcess): ?>
                                                            <!-- 詳細表示 -->
                                                            <a href="closing/simulation/<?= $contract['id'] ?>?period=<?= h($currentMonth) ?>" 
                                                               class="btn btn-outline-info" title="内容確認">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            
                                                            <!-- 承認ボタン -->
                                                            <button type="button" class="btn btn-outline-success" 
                                                                    onclick="approveClosing(<?= $contract['id'] ?>, '<?= h($currentMonth) ?>')"
                                                                    title="承認">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            
                                                            <!-- 差戻しボタン -->
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="rejectClosing(<?= $contract['id'] ?>, '<?= h($currentMonth) ?>')"
                                                                    title="差戻し">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted small">処理不可</span>
                                                        <?php endif; ?>
                                                        
                                                    <?php else: ?>
                                                        <!-- 産業医・管理者の場合 -->
                                                        <!-- シミュレーションボタン -->
                                                        <a href="closing/simulation/<?= $contract['id'] ?>?period=<?= h($currentMonth) ?>" 
                                                           class="btn btn-outline-primary" title="シミュレーション">
                                                            <i class="fas fa-calculator"></i>
                                                        </a>
                                                        
                                                        <?php if ($closingRecord): ?>
                                                            <!-- 編集ボタン(差戻しの場合のみ) -->
                                                            <?php if ($closingRecord['company_rejected_at']): ?>
                                                                <a href="closing/simulation/<?= $contract['id'] ?>?period=<?= h($currentMonth) ?>" 
                                                                   class="btn btn-outline-warning" title="編集">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <!-- PDF出力ボタン(確定済みの場合) -->
                                                            <?php if ($closingRecord['status'] === 'finalized' && $closingRecord['invoice_pdf_path']): ?>
                                                                <a href="/invoice-download/<?= $closingRecord['id'] ?>" 
                                                                   target="_blank" class="btn btn-outline-danger" title="請求書PDF">
                                                                    <i class="fas fa-file-pdf"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <!-- 締め取り消しボタン(産業医・管理者：確定済みで未承認の場合) -->
                                                            <?php if ($closingRecord['status'] === 'finalized' && $closingRecord['company_approved'] != 1): ?>
                                                                <button type="button" class="btn btn-outline-warning" 
                                                                        onclick="reopenClosing(<?= $contract['id'] ?>, '<?= h($currentMonth) ?>')"
                                                                        title="締め取消">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            
                                                            <!-- 管理者専用: 企業承認の取り消しボタン -->
                                                            <?php if ($userType === 'admin' && $closingRecord['company_approved'] == 1): ?>
                                                                <button type="button" class="btn btn-outline-warning" 
                                                                        onclick="revokeApproval(<?= $contract['id'] ?>, '<?= h($currentMonth) ?>')"
                                                                        title="承認取消">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- モバイル用カード表示 -->
                        <div class="mobile-view">
                            <?php foreach ($closingData as $data): ?>
                                <?php 
                                $contract = $data['contract'];
                                $closingRecord = $data['closing_record'];
                                $unClosedCount = $data['unclosed_records_count'];
                                $unapprovedCount = $data['unapproved_records_count'];
                                $unapprovedTravelCount = $data['unapproved_travel_expenses_count'];
                                $canProcess = $data['can_process'];
                                ?>
                                <div class="mobile-card mb-3">
                                    <div class="mobile-card-header">
                                        <h6>
                                            <?php if ($userType !== 'doctor'): ?>
                                                <?= h($contract['doctor_name'] ?? '') ?><br>
                                            <?php endif; ?>
                                            <?= h($contract['company_name']) ?> - <?= h($contract['branch_name']) ?>
                                        </h6>
                                    </div>
                                    
                                    <div class="mobile-card-body">
                                        <div class="mobile-row">
                                            <span class="mobile-label">未承認役務数</span>
                                            <span class="mobile-value">
                                                <?php if ($unapprovedCount > 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i><?= $unapprovedCount ?>件
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>0件
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mobile-row">
                                            <span class="mobile-label">未承認交通費数</span>
                                            <span class="mobile-value">
                                                <?php if ($unapprovedTravelCount > 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i><?= $unapprovedTravelCount ?>件
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>0件
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mobile-row">
                                            <span class="mobile-label">未締め役務数</span>
                                            <span class="mobile-value">
                                                <?php if ($unClosedCount > 0): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i><?= $unClosedCount ?>件
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">0件</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mobile-row">
                                            <span class="mobile-label">締め状況</span>
                                            <span class="mobile-value">
                                                <?php if ($closingRecord): ?>
                                                    <?php if ($closingRecord['company_approved'] == 1): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-double me-1"></i>企業承認済み
                                                        </span>
                                                    <?php elseif ($closingRecord['company_rejected_at']): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>差戻し
                                                        </span>
                                                    <?php elseif ($closingRecord['status'] === 'finalized'): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-clock me-1"></i>企業承認待ち
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">未処理</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mobile-row">
                                            <span class="mobile-label">締め日時</span>
                                            <span class="mobile-value">
                                                <?php if ($closingRecord && $closingRecord['finalized_at']): ?>
                                                    <?= date('Y/m/d H:i', strtotime($closingRecord['finalized_at'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($userType !== 'company'): ?>
                                        <div class="mobile-row">
                                            <span class="mobile-label">請求金額</span>
                                            <span class="mobile-value">
                                                <?php if ($closingRecord && $closingRecord['total_amount_with_tax']): ?>
                                                    <strong><?= number_format($closingRecord['total_amount_with_tax']) ?>円</strong>
                                                    <?php if ($closingRecord['tax_amount'] > 0): ?>
                                                        <br><small class="text-muted">
                                                            (税抜: <?= number_format($closingRecord['total_amount']) ?>円)
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mobile-card-footer">
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <?php if ($userType === 'company'): ?>
                                                <?php if ($canProcess): ?>
                                                    <a href="closing/simulation/<?= $contract['id'] ?>?period=<?= h($currentMonth) ?>" 
                                                       class="btn btn-outline-info">
                                                        <i class="fas fa-eye me-1"></i>確認
                                                    </a>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="approveClosing(<?= $contract['id'] ?>, '<?= h($currentMonth) ?>')">
                                                        <i class="fas fa-check me-1"></i>承認
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="rejectClosing(<?= $contract['id'] ?>, '<?= h($currentMonth) ?>')">
                                                        <i class="fas fa-times me-1"></i>差戻し
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">処理不可</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="closing/simulation/<?= $contract['id'] ?>?period=<?= h($currentMonth) ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-calculator me-1"></i>シミュレーション
                                                </a>
                                                <?php if ($closingRecord): ?>
                                                    <?php if ($closingRecord['status'] === 'draft' || $closingRecord['company_rejected_at']): ?>
                                                        <a href="closing/simulation/<?= $contract['id'] ?>?period=<?= h($currentMonth) ?>" 
                                                           class="btn btn-outline-warning">
                                                            <i class="fas fa-edit me-1"></i>編集
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($closingRecord['status'] === 'finalized' && $closingRecord['company_approved'] != 1): ?>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="reopenClosing(<?= $contract['id'] ?>, '<?= h($currentMonth) ?>')">
                                                            <i class="fas fa-undo me-1"></i>締め取消
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 企業承認モーダル -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>締め処理の承認確認
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>この締め処理を承認してもよろしいですか?</p>
                <p class="text-muted small">承認後、請求書PDFが生成されます。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    キャンセル
                </button>
                <form method="POST" id="approveForm">
                    <input type="hidden" name="csrf_token" value="<?= Session::get('csrf_token') ?>">
                    <input type="hidden" name="closing_period" id="approve-period">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>承認する
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 差戻しモーダル -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle me-2"></i>締め処理の差戻し
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= Session::get('csrf_token') ?>">
                    <input type="hidden" name="closing_period" id="reject-period">
                    
                    <div class="mb-3">
                        <label for="reject-reason" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject-reason" name="reject_reason" 
                                  rows="4" required placeholder="差戻しの理由を入力してください"></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        差戻し後、産業医が再度締め処理を行う必要があります。
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        キャンセル
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>差戻す
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 承認取消モーダル -->
<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo me-2"></i>企業承認の取消確認
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>この締め処理の企業承認を取り消してもよろしいですか?</p>
                <p class="text-warning small">請求書PDFも削除されます。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    キャンセル
                </button>
                <form method="POST" id="revokeForm">
                    <input type="hidden" name="csrf_token" value="<?= Session::get('csrf_token') ?>">
                    <input type="hidden" name="closing_period" id="revoke-period">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-2"></i>取消する
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 締め取り消しモーダル -->
<div class="modal fade" id="reopenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo me-2"></i>締め処理の取消確認
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>この締め処理を取り消して、再編集できる状態に戻してもよろしいですか?</p>
                <p class="text-warning small">
                    <i class="fas fa-info-circle me-1"></i>
                    確定状態が解除され、未処理状態に戻ります。
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    キャンセル
                </button>
                <form method="POST" id="reopenForm">
                    <input type="hidden" name="csrf_token" value="<?= Session::get('csrf_token') ?>">
                    <input type="hidden" name="closing_period" id="reopen-period">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-2"></i>取消する
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// アラート表示関数
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.card-body').prepend(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// 企業承認処理
function approveClosing(contractId, period) {
    document.getElementById('approveForm').action = 'closing/approve/' + contractId;
    document.getElementById('approve-period').value = period;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

// 差戻し処理
function rejectClosing(contractId, period) {
    document.getElementById('rejectForm').action = 'closing/reject/' + contractId;
    document.getElementById('reject-period').value = period;
    document.getElementById('reject-reason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

// 承認取消処理
function revokeApproval(contractId, period) {
    document.getElementById('revokeForm').action = 'closing/revokeCompanyApproval/' + contractId;
    document.getElementById('revoke-period').value = period;
    new bootstrap.Modal(document.getElementById('revokeModal')).show();
}

// 締め取り消し処理
function reopenClosing(contractId, period) {
    document.getElementById('reopenForm').action = 'closing/reopen/' + contractId;
    document.getElementById('reopen-period').value = period;
    new bootstrap.Modal(document.getElementById('reopenModal')).show();
}

// ページ読み込み時のフラッシュメッセージ表示
document.addEventListener('DOMContentLoaded', function() {
    <?php 
    $successFlash = Session::flash('success');
    if ($successFlash): ?>
        showAlert('success', <?= json_encode($successFlash) ?>);
    <?php endif; ?>
    
    <?php 
    $errorFlash = Session::flash('error');
    if ($errorFlash): ?>
        showAlert('danger', <?= json_encode($errorFlash) ?>);
    <?php endif; ?>
    
    <?php 
    $infoFlash = Session::flash('info');
    if ($infoFlash): ?>
        showAlert('info', <?= json_encode($infoFlash) ?>);
    <?php endif; ?>
});
</script>

<style>
/* 基本スタイル */
.table th {
    white-space: nowrap;
}

.btn-group .btn {
    margin: 0;
}

.badge {
    font-size: 0.85em;
}

.card-title {
    color: #495057;
}

.fs-4 {
    font-size: 1.5rem !important;
}

.bg-light {
    background-color: #f8f9fa !important;
}

/* フィルターエリアのスタイル */
.filter-area {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}

.filter-area .form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* レスポンシブ対応 */
/* 1024px以上: デスクトップ表示 */
@media (min-width: 1024px) {
    .desktop-view {
        display: block;
    }
    .mobile-view {
        display: none;
    }
    .month-selector-wrapper {
        display: flex;
        justify-content: flex-end;
    }
}

/* 1024px未満: モバイル表示 */
@media (max-width: 1023px) {
    .desktop-view {
        display: none;
    }
    .mobile-view {
        display: block;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .month-selector-wrapper {
        width: 100%;
        margin-top: 1rem;
    }
    
    .month-selector-wrapper form {
        width: 100%;
    }
    
    .month-selector-wrapper select {
        flex: 1;
    }
    
    /* フィルターエリアのモバイル調整 */
    .filter-area {
        padding: 1rem;
    }
    
    .filter-area .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .filter-area .btn:last-child {
        margin-bottom: 0;
    }
    
    /* モバイルカードスタイル */
    .mobile-card {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .mobile-card-header {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
        border-radius: 0.375rem 0.375rem 0 0;
    }
    
    .mobile-card-header h6 {
        margin: 0;
        font-weight: 600;
        color: #212529;
    }
    
    .mobile-card-body {
        padding: 1rem;
    }
    
    .mobile-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .mobile-row:last-child {
        border-bottom: none;
    }
    
    .mobile-label {
        font-weight: 500;
        color: #6c757d;
        font-size: 0.875rem;
        flex-shrink: 0;
        margin-right: 1rem;
    }
    
    .mobile-value {
        text-align: right;
        font-size: 0.875rem;
    }
    
    .mobile-card-footer {
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        background-color: #f8f9fa;
        border-radius: 0 0 0.375rem 0.375rem;
    }
    
    .mobile-card-footer .btn {
        font-size: 0.875rem;
    }
    
    /* サマリーカードのレスポンシブ調整 */
    .col-md-6 {
        margin-bottom: 1rem;
    }
}

/* タブレット用の微調整 */
@media (min-width: 768px) and (max-width: 1023px) {
    .mobile-row {
        padding: 0.875rem 0;
    }
    
    .mobile-label {
        font-size: 0.9375rem;
    }
    
    .mobile-value {
        font-size: 0.9375rem;
    }
}

/* 小さいスマホ用の調整 */
@media (max-width: 360px) {
    .mobile-card-header,
    .mobile-card-body,
    .mobile-card-footer {
        padding: 0.75rem;
    }
    
    .mobile-label {
        font-size: 0.8125rem;
    }
    
    .mobile-value {
        font-size: 0.8125rem;
    }
    
    .mobile-card-footer .btn {
        font-size: 0.8125rem;
        padding: 0.375rem 0.5rem;
    }
}
</style>