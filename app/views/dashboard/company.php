<?php
// app/views/dashboard/company.php - ページネーション機能付き完全版
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-building me-2"></i>企業ダッシュボード</h2>
    <a href="<?= base_url('service_records') ?>" class="btn btn-primary">
        <i class="fas fa-check-circle me-1"></i>役務記録を確認
    </a>
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
<div class="row">
    <!-- 承認待ちの役務記録 -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>承認待ちの役務記録
                    <!-- 承認待ち記録の表示件数とページネーション情報 -->
                    <?php if ($pendingRecordsPaginationInfo['total_count'] > 0): ?>
                        <small class="text-muted">
                            （<?= $pendingRecordsPaginationInfo['start_record'] ?>-<?= $pendingRecordsPaginationInfo['end_record'] ?>件 / 
                            全<?= $pendingRecordsPaginationInfo['total_count'] ?>件）
                        </small>
                    <?php endif; ?>
                </h5>
                <div>
                    <span class="badge bg-warning me-2"><?= isset($pendingRecords) ? count($pendingRecords) : 0 ?>件</span>
                    <?php if (!empty($pendingRecords)): ?>
                    <!-- 一括承認ボタンを追加 -->
                    <button type="button" class="btn btn-sm btn-success me-2" onclick="showBulkApproveModal()" title="選択した記録を一括承認">
                        <i class="fas fa-check-double me-1"></i>一括承認
                    </button>
                    <a href="<?= base_url('service_records?status=pending') ?>" class="btn btn-sm btn-outline-primary">
                        すべて見る
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ページネーション設定（承認待ち記録） -->
            <?php if (isset($pendingRecordsPaginationInfo) && $pendingRecordsPaginationInfo['total_count'] > 0): ?>
            <div class="card-body border-bottom bg-light">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <form method="GET" class="d-flex align-items-center">
                            <!-- 既存のGETパラメータを維持 -->
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if (!in_array($key, ['pending_records_page', 'pending_records_page_size'])): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <label for="pendingRecordsPageSize" class="form-label me-2 mb-0">表示件数:</label>
                            <select class="form-select form-select-sm" id="pendingRecordsPageSize" name="pending_records_page_size" onchange="this.form.submit()" style="width: auto;">
                                <option value="5" <?= (($_GET['pending_records_page_size'] ?? 15) == 5) ? 'selected' : '' ?>>5件</option>
                                <option value="10" <?= (($_GET['pending_records_page_size'] ?? 15) == 10) ? 'selected' : '' ?>>10件</option>
                                <option value="15" <?= (($_GET['pending_records_page_size'] ?? 15) == 15) ? 'selected' : '' ?>>15件</option>
                                <option value="20" <?= (($_GET['pending_records_page_size'] ?? 15) == 20) ? 'selected' : '' ?>>20件</option>
                                <option value="30" <?= (($_GET['pending_records_page_size'] ?? 15) == 30) ? 'selected' : '' ?>>30件</option>
                                <option value="50" <?= (($_GET['pending_records_page_size'] ?? 15) == 50) ? 'selected' : '' ?>>50件</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-8 text-end">
                        <small class="text-muted">
                            <?= $pendingRecordsPaginationInfo['start_record'] ?>-<?= $pendingRecordsPaginationInfo['end_record'] ?>件 / 
                            全<?= $pendingRecordsPaginationInfo['total_count'] ?>件
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card-body">
                <?php if (empty($pendingRecords)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">承認待ちの役務記録はありません。</p>
                    </div>
                <?php else: ?>
                    <!-- デスクトップ表示: 通常のテーブル -->
                    <div class="desktop-table d-none d-lg-block">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAllRecords(event)">
                                                <label class="form-check-label" for="selectAll">
                                                    <span class="visually-hidden">すべて選択</span>
                                                </label>
                                            </div>
                                        </th>
                                        <th>拠点</th>
                                        <th>産業医</th>
                                        <th>役務日</th>
                                        <th>実施時間</th>
                                        <th>役務種別</th>
                                        <th>役務時間</th>
                                        <th>役務内容</th>
                                        <th>延長理由</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingRecords as $record): ?>
                                        <?php
                                        // 自動分割記録かどうかの判定
                                        $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                                                      strpos($record['description'] ?? '', '自動分割') !== false ||
                                                      strpos($record['description'] ?? '', '延長分') !== false;
                                        
                                        // 役務種別の安全な取得
                                        $serviceType = $record['service_type'] ?? 'regular';
                                        
                                        // 進行中かどうかの判定（役務時間が0の場合、ただし書面・遠隔は除く）
                                        $isInProgress = !in_array($serviceType, ['document', 'remote_consultation', 'other']) && 
                                                       ($record['service_hours'] == 0 || $record['service_hours'] === null);
                                        ?>
                                        <tr<?= $isInProgress ? ' class="table-secondary"' : '' ?>>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input record-checkbox" type="checkbox" 
                                                        value="<?= $record['id'] ?>" 
                                                        id="record_<?= $record['id'] ?>"
                                                        onchange="updateBulkApproveButton()"
                                                        <?= $isInProgress ? 'disabled title="進行中の役務は承認できません"' : '' ?>>
                                                    <label class="form-check-label" for="record_<?= $record['id'] ?>">
                                                        <span class="visually-hidden">選択</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                                <strong><?= htmlspecialchars($record['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-user-md me-1 text-info"></i>
                                                <?= htmlspecialchars($record['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td><?= format_date($record['service_date'] ?? '') ?></td>
                                            <td>
                                                <small>
                                                    <?php if (in_array($serviceType, ['document', 'remote_consultation', 'other'])): ?>
                                                        <span class="text-muted">時間記録なし</span>
                                                    <?php elseif ($record['service_hours'] > 0): ?>
                                                        <!-- 時間分割対応の表示 -->
                                                        <?= format_service_time_display($record) ?>
                                                        
                                                        <!-- 自動分割記録の場合の追加情報 -->
                                                        <?php if ($isAutoSplit): ?>
                                                            <div class="small text-info mt-1">
                                                                <i class="fas fa-info-circle me-1"></i>分割記録
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-warning">進行中</span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $serviceTypeInfo = [
                                                    'regular' => ['class' => 'success', 'icon' => 'calendar-check', 'label' => '定期'],
                                                    'emergency' => ['class' => 'warning', 'icon' => 'exclamation-triangle', 'label' => '臨時'],
                                                    'extension' => ['class' => 'info', 'icon' => 'clock', 'label' => '延長'],
                                                    'spot' => ['class' => 'info', 'icon' => 'clock', 'label' => 'スポット'],
                                                    'document' => ['class' => 'primary', 'icon' => 'file-alt', 'label' => '書面'],
                                                    'remote_consultation' => ['class' => 'secondary', 'icon' => 'envelope', 'label' => '遠隔'],
                                                    'other' => ['class' => 'dark', 'icon' => 'plus', 'label' => 'その他']
                                                ];
                                                $typeInfo = $serviceTypeInfo[$serviceType] ?? $serviceTypeInfo['regular'];
                                                ?>
                                                <span class="badge bg-<?= $typeInfo['class'] ?>">
                                                    <i class="fas fa-<?= $typeInfo['icon'] ?> me-1"></i>
                                                    <?= $typeInfo['label'] ?>
                                                </span>
                                                
                                                <!-- 自動分割記録のマーク -->
                                                <?php if ($serviceType === 'extension' && $isAutoSplit): ?>
                                                    <div class="mt-1">
                                                        <span class="badge bg-secondary" title="自動分割により作成された記録">
                                                            <i class="fas fa-scissors me-1"></i>自動
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (in_array($serviceType, ['document', 'remote_consultation', 'other'])): ?>
                                                    <span class="badge bg-secondary">時間なし</span>
                                                <?php elseif ($record['service_hours'] > 0): ?>
                                                    <span class="badge bg-info"><?= format_hours_minutes($record['service_hours']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">進行中</span>
                                                    <div class="small text-muted mt-1">
                                                        <i class="fas fa-info-circle"></i> 承認不可
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="description-cell">
                                                    <?php if (!empty($record['description'])): ?>
                                                        <?php
                                                        $description = $record['description'];
                                                        // 自動分割記録の場合、元の内容部分のみを表示
                                                        if ($isAutoSplit && strpos($description, '元の内容:') !== false) {
                                                            preg_match('/元の内容:\s*(.+?)$/s', $description, $matches);
                                                            $description = $matches[1] ?? $description;
                                                        }
                                                        $shortDesc = mb_strlen($description) > 30 ? 
                                                                    mb_substr($description, 0, 30) . '...' : $description;
                                                        ?>
                                                        <small class="d-block" title="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
                                                            <?= htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8') ?>
                                                        </small>
                                                        
                                                        <!-- 自動分割情報の表示 -->
                                                        <?php if ($isAutoSplit): ?>
                                                            <div class="small text-secondary mt-1">
                                                                <i class="fas fa-info-circle me-1"></i>自動分割記録
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <small class="text-muted">記載なし</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($record['overtime_reason'])): ?>
                                                    <small class="text-warning" title="<?= htmlspecialchars($record['overtime_reason'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars(mb_substr($record['overtime_reason'], 0, 20), ENT_QUOTES, 'UTF-8') ?>
                                                        <?= mb_strlen($record['overtime_reason']) > 20 ? '...' : '' ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info" onclick="showRecordDetailModal(<?= htmlspecialchars(json_encode($record), ENT_QUOTES, 'UTF-8') ?>)" title="詳細">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                    <button class="btn btn-success" onclick="approveRecord(<?= $record['id'] ?>)" title="承認" <?= $isInProgress ? 'disabled' : '' ?>>
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-danger" onclick="showRejectModal(<?= $record['id'] ?>, '<?= htmlspecialchars($record['doctor_name'], ENT_QUOTES, 'UTF-8') ?>')" title="差戻し" <?= $isInProgress ? 'disabled' : '' ?>>
                                                        <i class="fas fa-times"></i>
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllMobile" onclick="toggleAllRecords(event)">
                                <label class="form-check-label fw-bold" for="selectAllMobile">
                                    すべて選択
                                </label>
                            </div>
                        </div>

                        <?php foreach ($pendingRecords as $record): ?>
                            <?php
                            // 自動分割記録かどうかの判定
                            $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                                          strpos($record['description'] ?? '', '自動分割') !== false ||
                                          strpos($record['description'] ?? '', '延長分') !== false;
                            
                            // 役務種別の安全な取得
                            $serviceType = $record['service_type'] ?? 'regular';
                            
                            // 進行中かどうかの判定（役務時間が0の場合、ただし書面・遠隔は除く）
                            $isInProgress = !in_array($serviceType, ['document', 'remote_consultation', 'other']) && 
                                           ($record['service_hours'] == 0 || $record['service_hours'] === null);
                            
                            $serviceTypeInfo = [
                                'regular' => ['class' => 'success', 'icon' => 'calendar-check', 'label' => '定期'],
                                'emergency' => ['class' => 'warning', 'icon' => 'exclamation-triangle', 'label' => '臨時'],
                                'extension' => ['class' => 'info', 'icon' => 'clock', 'label' => '延長'],
                                'spot' => ['class' => 'info', 'icon' => 'clock', 'label' => 'スポット'],
                                'document' => ['class' => 'primary', 'icon' => 'file-alt', 'label' => '書面'],
                                'remote_consultation' => ['class' => 'secondary', 'icon' => 'envelope', 'label' => '遠隔'],
                                'other' => ['class' => 'dark', 'icon' => 'plus', 'label' => 'その他']
                            ];
                            $typeInfo = $serviceTypeInfo[$serviceType] ?? $serviceTypeInfo['regular'];
                            ?>
                            <div class="card mb-3 pending-record-card border-warning<?= $isInProgress ? ' bg-light' : '' ?>">
                                <div class="card-body">
                                    <!-- ヘッダー部分 -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input record-checkbox" type="checkbox" 
                                                value="<?= $record['id'] ?>" 
                                                id="record_mobile_<?= $record['id'] ?>"
                                                onchange="updateBulkApproveButton()"
                                                <?= $isInProgress ? 'disabled title="進行中の役務は承認できません"' : '' ?>>
                                            <label class="form-check-label fw-bold" for="record_mobile_<?= $record['id'] ?>">
                                                選択
                                            </label>
                                        </div>
                                        
                                        <div class="record-badges">
                                            <span class="badge bg-<?= $typeInfo['class'] ?>">
                                                <i class="fas fa-<?= $typeInfo['icon'] ?> me-1"></i>
                                                <?= $typeInfo['label'] ?>
                                            </span>
                                            <?php if ($serviceType === 'extension' && $isAutoSplit): ?>
                                                <span class="badge bg-secondary" title="自動分割により作成された記録">
                                                    <i class="fas fa-scissors me-1"></i>自動
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 基本情報 -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h6 class="card-title mb-2">
                                                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                                <?= htmlspecialchars($record['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            </h6>
                                            <p class="text-muted mb-1">
                                                <i class="fas fa-user-md me-1"></i>
                                                <?= htmlspecialchars($record['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= format_date($record['service_date'] ?? '') ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- 時間情報 -->
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">実施時間</small>
                                            <?php if (in_array($serviceType, ['document', 'remote_consultation', 'other'])): ?>
                                                <span class="text-muted">時間記録なし</span>
                                            <?php elseif ($record['service_hours'] > 0): ?>
                                                <?= format_service_time_display($record) ?>
                                                <?php if ($isAutoSplit): ?>
                                                    <div class="small text-info mt-1">
                                                        <i class="fas fa-info-circle me-1"></i>分割記録
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning">進行中</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">役務時間</small>
                                            <?php if (in_array($serviceType, ['document', 'remote_consultation', 'other'])): ?>
                                                <span class="badge bg-secondary">時間なし</span>
                                            <?php elseif ($record['service_hours'] > 0): ?>
                                                <span class="badge bg-info"><?= format_hours_minutes($record['service_hours']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">進行中</span>
                                                <div class="small text-muted mt-1">
                                                    <i class="fas fa-info-circle"></i> 承認不可
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 役務内容 -->
                                    <?php if (!empty($record['description'])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block">役務内容</small>
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
                                            <p class="mb-0"><?= htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php if ($isAutoSplit): ?>
                                                <small class="text-secondary">
                                                    <i class="fas fa-info-circle me-1"></i>自動分割記録
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- 延長理由 -->
                                    <?php if (!empty($record['overtime_reason'])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block">延長理由</small>
                                            <p class="text-warning mb-0"><?= htmlspecialchars($record['overtime_reason'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- 操作ボタン -->
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-info btn-sm flex-fill" onclick="showRecordDetailModal(<?= htmlspecialchars(json_encode($record), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fas fa-info-circle me-1"></i>詳細
                                        </button>
                                        <button class="btn btn-success btn-sm flex-fill" onclick="approveRecord(<?= $record['id'] ?>)" <?= $isInProgress ? 'disabled' : '' ?>>
                                            <i class="fas fa-check me-1"></i>承認
                                        </button>
                                        <button class="btn btn-danger btn-sm flex-fill" onclick="showRejectModal(<?= $record['id'] ?>, '<?= htmlspecialchars($record['doctor_name'], ENT_QUOTES, 'UTF-8') ?>')" <?= $isInProgress ? 'disabled' : '' ?>>
                                            <i class="fas fa-times me-1"></i>差戻
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ページネーション(承認待ち記録) -->
            <?php if (isset($pendingRecordsPaginationInfo) && $pendingRecordsPaginationInfo['total_pages'] > 1): ?>
            <div class="card-footer">
                <nav aria-label="承認待ち記録のページネーション">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <!-- 最初のページ -->
                        <?php if ($pendingRecordsPaginationInfo['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_records_page' => 1])) ?>" aria-label="最初">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 前のページ -->
                        <?php if ($pendingRecordsPaginationInfo['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_records_page' => $pendingRecordsPaginationInfo['current_page'] - 1])) ?>" aria-label="前へ">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- ページ番号 -->
                        <?php
                        $startPage = max(1, $pendingRecordsPaginationInfo['current_page'] - 2);
                        $endPage = min($pendingRecordsPaginationInfo['total_pages'], $pendingRecordsPaginationInfo['current_page'] + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?= $i === $pendingRecordsPaginationInfo['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_records_page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- 次のページ -->
                        <?php if ($pendingRecordsPaginationInfo['current_page'] < $pendingRecordsPaginationInfo['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_records_page' => $pendingRecordsPaginationInfo['current_page'] + 1])) ?>" aria-label="次へ">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 最後のページ -->
                        <?php if ($pendingRecordsPaginationInfo['current_page'] < $pendingRecordsPaginationInfo['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_records_page' => $pendingRecordsPaginationInfo['total_pages']])) ?>" aria-label="最後">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 承認待ちの締め処理 -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice-dollar me-2"></i>承認待ちの締め処理
                    <!-- 承認待ち締め処理の表示件数とページネーション情報 -->
                    <?php if ($pendingClosingsPaginationInfo['total_count'] > 0): ?>
                        <small class="text-muted">
                            （<?= $pendingClosingsPaginationInfo['start_record'] ?>-<?= $pendingClosingsPaginationInfo['end_record'] ?>件 / 
                            全<?= $pendingClosingsPaginationInfo['total_count'] ?>件）
                        </small>
                    <?php endif; ?>
                </h5>
                <div>
                    <span class="badge bg-warning me-2"><?= isset($pendingClosings) ? count($pendingClosings) : 0 ?>件</span>
                    <?php if (!empty($pendingClosings)): ?>
                    <!-- 一括承認ボタンを追加 -->
                    <button type="button" class="btn btn-sm btn-success me-2" onclick="showBulkApproveClosingModal()" title="選択した締め処理を一括承認">
                        <i class="fas fa-check-double me-1"></i>一括承認
                    </button>
                    <a href="<?= base_url('closing') ?>" class="btn btn-sm btn-outline-primary">
                        すべて見る
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ページネーション設定（締め処理） -->
            <?php if (isset($pendingClosingsPaginationInfo) && $pendingClosingsPaginationInfo['total_count'] > 0): ?>
            <div class="card-body border-bottom bg-light">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <form method="GET" class="d-flex align-items-center">
                            <!-- 既存のGETパラメータを維持 -->
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if (!in_array($key, ['pending_closing_page', 'pending_closing_page_size'])): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <label for="pendingClosingPageSize" class="form-label me-2 mb-0">表示件数:</label>
                            <select class="form-select form-select-sm" id="pendingClosingPageSize" name="pending_closing_page_size" onchange="this.form.submit()" style="width: auto;">
                                <option value="5" <?= (($_GET['pending_closing_page_size'] ?? 15) == 5) ? 'selected' : '' ?>>5件</option>
                                <option value="10" <?= (($_GET['pending_closing_page_size'] ?? 15) == 10) ? 'selected' : '' ?>>10件</option>
                                <option value="15" <?= (($_GET['pending_closing_page_size'] ?? 15) == 15) ? 'selected' : '' ?>>15件</option>
                                <option value="20" <?= (($_GET['pending_closing_page_size'] ?? 15) == 20) ? 'selected' : '' ?>>20件</option>
                                <option value="30" <?= (($_GET['pending_closing_page_size'] ?? 15) == 30) ? 'selected' : '' ?>>30件</option>
                                <option value="50" <?= (($_GET['pending_closing_page_size'] ?? 15) == 50) ? 'selected' : '' ?>>50件</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-8 text-end">
                        <small class="text-muted">
                            <?= $pendingClosingsPaginationInfo['start_record'] ?>-<?= $pendingClosingsPaginationInfo['end_record'] ?>件 / 
                            全<?= $pendingClosingsPaginationInfo['total_count'] ?>件
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card-body">
                <?php if (empty($pendingClosings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">承認待ちの締め処理はありません。</p>
                    </div>
                <?php else: ?>
                    <!-- デスクトップ表示: 通常のテーブル -->
                    <div class="desktop-table d-none d-lg-block">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllClosing" onclick="toggleAllClosingItems(event)">
                                                <label class="form-check-label" for="selectAllClosing">
                                                    <span class="visually-hidden">すべて選択</span>
                                                </label>
                                            </div>
                                        </th>
                                        <th>対象月</th>
                                        <th>拠点</th>
                                        <th>産業医</th>
                                        <th>契約種別</th>
                                        <th>締め日</th>
                                        <th>役務時間</th>
                                        <th>産業医コメント</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingClosings as $closing): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input closing-checkbox" type="checkbox" 
                                                        value="<?= $closing['id'] ?>" 
                                                        id="closing_<?= $closing['id'] ?>"
                                                        data-contract-id="<?= $closing['contract_id'] ?>"
                                                        data-closing-period="<?= htmlspecialchars($closing['closing_period'], ENT_QUOTES, 'UTF-8') ?>"
                                                        onchange="updateBulkApproveClosingButton()">
                                                    <label class="form-check-label" for="closing_<?= $closing['id'] ?>">
                                                        <span class="visually-hidden">選択</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($closing['closing_period'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                                <?= htmlspecialchars($closing['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user-md me-1 text-info"></i>
                                                <?= htmlspecialchars($closing['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <?php
                                                $contractTypeLabels = [
                                                    'weekly' => '週契約',
                                                    'monthly' => '月契約',
                                                    'bimonthly' => '隔月契約',
                                                    'spot' => 'スポット'
                                                ];
                                                $contractTypeLabel = $contractTypeLabels[$closing['visit_frequency']] ?? $closing['visit_frequency'];
                                                ?>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($contractTypeLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td><?= format_date($closing['finalized_at'] ?? '') ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= format_hours_minutes($closing['total_approved_hours'] ?? 0) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($closing['doctor_comment'])): ?>
                                                    <small class="text-muted">
                                                        <?= nl2br(htmlspecialchars($closing['doctor_comment'], ENT_QUOTES, 'UTF-8')) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-success" onclick="approveClosing(<?= $closing['id'] ?>)" title="承認">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-danger" onclick="showRejectClosingModal(<?= $closing['id'] ?>, '<?= htmlspecialchars($closing['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($closing['closing_period'], ENT_QUOTES, 'UTF-8') ?>')" title="差戻し">
                                                        <i class="fas fa-times"></i>
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllClosingMobile" onclick="toggleAllClosingItems(event)">
                                <label class="form-check-label fw-bold" for="selectAllClosingMobile">
                                    すべて選択
                                </label>
                            </div>
                        </div>

                        <?php foreach ($pendingClosings as $closing): ?>
                            <?php
                            $contractTypeLabels = [
                                'weekly' => '週契約',
                                'monthly' => '月契約',
                                'bimonthly' => '隔月契約',
                                'spot' => 'スポット'
                            ];
                            $contractTypeLabel = $contractTypeLabels[$closing['visit_frequency']] ?? $closing['visit_frequency'];
                            ?>
                            <div class="card mb-3 pending-closing-card border-warning">
                                <div class="card-body">
                                    <!-- ヘッダー部分 -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input closing-checkbox" type="checkbox" 
                                                value="<?= $closing['id'] ?>" 
                                                id="closing_mobile_<?= $closing['id'] ?>"
                                                data-contract-id="<?= $closing['contract_id'] ?>"
                                                data-closing-period="<?= htmlspecialchars($closing['closing_period'], ENT_QUOTES, 'UTF-8') ?>"
                                                onchange="updateBulkApproveClosingButton()">
                                            <label class="form-check-label fw-bold" for="closing_mobile_<?= $closing['id'] ?>">
                                                選択
                                            </label>
                                        </div>
                                        
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($contractTypeLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>

                                    <!-- 基本情報 -->
                                    <div class="mb-3">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?= htmlspecialchars($closing['closing_period'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </h6>
                                        <p class="mb-1">
                                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                            <?= htmlspecialchars($closing['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-user-md me-1"></i>
                                            <?= htmlspecialchars($closing['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>

                                    <!-- 詳細情報 -->
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">締め日</small>
                                            <?= format_date($closing['finalized_at'] ?? '') ?>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">役務時間</small>
                                            <span class="badge bg-info">
                                                <?= format_hours_minutes($closing['total_approved_hours'] ?? 0) ?>
                                            </span>
                                        </div>
                                    </div>


                                    <!-- 産業医コメント -->
                                    <?php if (!empty($closing['doctor_comment'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">産業医コメント</small>
                                        <div class="p-2 bg-light rounded">
                                            <small class="text-muted">
                                                <?= nl2br(htmlspecialchars($closing['doctor_comment'], ENT_QUOTES, 'UTF-8')) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- 操作ボタン -->
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success btn-sm flex-fill" onclick="approveClosing(<?= $closing['id'] ?>)">
                                            <i class="fas fa-check me-1"></i>承認
                                        </button>
                                        <button class="btn btn-danger btn-sm flex-fill" onclick="showRejectClosingModal(<?= $closing['id'] ?>, '<?= htmlspecialchars($closing['doctor_name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($closing['closing_period'], ENT_QUOTES, 'UTF-8') ?>')">
                                            <i class="fas fa-times me-1"></i>差戻
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ページネーション(締め処理) -->
            <?php if (isset($pendingClosingsPaginationInfo) && $pendingClosingsPaginationInfo['total_pages'] > 1): ?>
            <div class="card-footer">
                <nav aria-label="締め処理のページネーション">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <!-- 最初のページ -->
                        <?php if ($pendingClosingsPaginationInfo['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_closing_page' => 1])) ?>" aria-label="最初">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 前のページ -->
                        <?php if ($pendingClosingsPaginationInfo['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_closing_page' => $pendingClosingsPaginationInfo['current_page'] - 1])) ?>" aria-label="前へ">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- ページ番号 -->
                        <?php
                        $startPage = max(1, $pendingClosingsPaginationInfo['current_page'] - 2);
                        $endPage = min($pendingClosingsPaginationInfo['total_pages'], $pendingClosingsPaginationInfo['current_page'] + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?= $i === $pendingClosingsPaginationInfo['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_closing_page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- 次のページ -->
                        <?php if ($pendingClosingsPaginationInfo['current_page'] < $pendingClosingsPaginationInfo['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_closing_page' => $pendingClosingsPaginationInfo['current_page'] + 1])) ?>" aria-label="次へ">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- 最後のページ -->
                        <?php if ($pendingClosingsPaginationInfo['current_page'] < $pendingClosingsPaginationInfo['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_closing_page' => $pendingClosingsPaginationInfo['total_pages']])) ?>" aria-label="最後">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

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
                                    <th>拠点</th>
                                    <th>産業医</th>
                                    <th class="text-center">訪問頻度</th>
                                    <th class="text-center">契約時間</th>
                                    <th class="text-center">今月実績（時間）</th>
                                    <th class="text-center">時間なし役務</th>
                                    <th class="text-center">残り時間</th>
                                    <th class="text-center">進捗</th>
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
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td>
                                            <i class="fas fa-building me-1 text-primary"></i>
                                            <strong><?= htmlspecialchars($contract['branch_name']) ?></strong>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-md me-1 text-success"></i>
                                            <?= htmlspecialchars($contract['doctor_name']) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($frequency === 'monthly'): ?>
                                                <span class="badge bg-primary">毎月</span>
                                            <?php elseif ($frequency === 'bimonthly'): ?>
                                                <span class="badge bg-info">隔月</span>
                                            <?php elseif ($frequency === 'weekly'): ?>
                                                <span class="badge bg-success">毎週</span>
                                                <?php if (!empty($contract['weekly_schedule'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($contract['weekly_schedule']) ?></small>
                                                <?php endif; ?>
                                            <?php elseif ($frequency === 'spot'): ?>
                                                <span class="badge bg-warning">スポット</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            if ($frequency === 'spot') {
                                                // スポット契約の場合は「-」を表示
                                                echo '-';
                                            } else {
                                                $displayHours = $contract['regular_visit_hours'] ?? 0;
                                                if ($frequency === 'weekly') {
                                                    // 毎週契約の場合は月間計算時間を表示
                                                    $displayHours = $contract['monthly_hours'] ?? 0;
                                                }
                                                echo format_hours_minutes($displayHours);
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $thisMonthHours = $contract['this_month_hours'] ?? 0;
                                            $regularHours = $contract['regular_hours'] ?? 0;
                                            $emergencyHours = $contract['emergency_hours'] ?? 0;
                                            $extensionHours = $contract['extension_hours'] ?? 0;
                                            ?>
                                            <span class="badge bg-primary"><?= format_hours_minutes($thisMonthHours) ?></span>
                                            <?php if ($regularHours > 0 || $emergencyHours > 0 || $extensionHours > 0): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php if ($regularHours > 0): ?>
                                                        定期: <?= format_hours_minutes($regularHours) ?>
                                                    <?php endif; ?>
                                                    <?php if ($emergencyHours > 0): ?>
                                                        <span class="text-danger">臨時: <?= format_hours_minutes($emergencyHours) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($extensionHours > 0): ?>
                                                        <span class="text-warning">延長: <?= format_hours_minutes($extensionHours) ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $documentCount = $contract['document_count'] ?? 0;
                                            $remoteConsultationCount = $contract['remote_consultation_count'] ?? 0;
                                            $otherCount = $contract['other_count'] ?? 0;
                                            ?>
                                            <?php if ($documentCount > 0): ?>
                                                <span class="badge bg-secondary me-1" title="書類作成">
                                                    <i class="fas fa-file-alt"></i> <?= $documentCount ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($remoteConsultationCount > 0): ?>
                                                <span class="badge bg-info" title="遠隔面談">
                                                    <i class="fas fa-envelope"></i> <?= $remoteConsultationCount ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($otherCount > 0): ?>
                                                <span class="badge bg-dark" title="その他">
                                                    <i class="fas fa-plus"></i> <?= $otherCount ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($documentCount == 0 && $remoteConsultationCount == 0 && $otherCount == 0): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            if ($frequency === 'spot') {
                                                // スポット契約の場合は「-」を表示
                                                echo '<span class="text-muted">-</span>';
                                            } else {
                                                $remainingHours = $contract['remaining_hours'] ?? 0;
                                                $remainingClass = $remainingHours < 0 ? 'text-danger' : 'text-success';
                                            ?>
                                            <span class="<?= $remainingClass ?>">
                                                <?= format_hours_minutes($remainingHours) ?>
                                            </span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            if ($frequency === 'spot') {
                                                // スポット契約の場合は「-」を表示
                                                echo '<span class="text-muted">-</span>';
                                            } else {
                                                $usagePercentage = $contract['usage_percentage'] ?? 0;
                                                $monthlyHours = $contract['monthly_hours'] ?? ($contract['regular_visit_hours'] ?? 0);
                                            ?>
                                            <div class="progress" style="height: 20px; min-width: 80px;">
                                                <div class="progress-bar <?= $progressClass ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= min(100, $usagePercentage) ?>%;" 
                                                     aria-valuenow="<?= $usagePercentage ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?= number_format($usagePercentage, 1) ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?= format_hours_minutes($regularHours) ?> / <?= format_hours_minutes($monthlyHours) ?>
                                            </small>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- モバイル表示（1024px未満）: カード形式 -->
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
                            
                            // カードのスタイル決定
                            $cardClass = 'border-start border-4';
                            $borderColor = 'border-success';
                            $statusBadge = '';
                            
                            if (!$isContractActive) {
                                $borderColor = 'border-danger';
                                $statusBadge = '<span class="badge bg-danger">無効</span>';
                            } elseif (!$isContractStarted) {
                                $borderColor = 'border-warning';
                                $statusBadge = '<span class="badge bg-warning">未開始</span>';
                            } elseif ($isContractExpired) {
                                $borderColor = 'border-danger';
                                $statusBadge = '<span class="badge bg-danger">期限切れ</span>';
                            } elseif ($frequency === 'bimonthly' && !$isVisitMonth) {
                                $borderColor = 'border-info';
                                $statusBadge = '<span class="badge bg-info">非訪問月</span>';
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
                            
                            $thisMonthHours = $contract['this_month_hours'] ?? 0;
                            $regularHours = $contract['regular_hours'] ?? 0;
                            $emergencyHours = $contract['emergency_hours'] ?? 0;
                            $extensionHours = $contract['extension_hours'] ?? 0;
                            $documentCount = $contract['document_count'] ?? 0;
                            $remoteConsultationCount = $contract['remote_consultation_count'] ?? 0;
                            $otherCount = $contract['other_count'] ?? 0;
                            $remainingHours = $contract['remaining_hours'] ?? 0;
                            $remainingClass = $remainingHours < 0 ? 'text-danger' : 'text-success';
                            $usagePercentage = $contract['usage_percentage'] ?? 0;
                            $displayHours = $contract['regular_visit_hours'] ?? 0;
                            if ($frequency === 'weekly') {
                                $displayHours = $contract['monthly_hours'] ?? 0;
                            }
                            $monthlyHours = $contract['monthly_hours'] ?? ($contract['regular_visit_hours'] ?? 0);
                            ?>
                            
                            <div class="card mb-3 <?= $cardClass ?> <?= $borderColor ?>">
                                <div class="card-body">
                                    <!-- ヘッダー部分 -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="fas fa-building me-1 text-primary"></i>
                                                <strong><?= htmlspecialchars($contract['branch_name']) ?></strong>
                                            </h6>
                                            <div class="text-muted small">
                                                <i class="fas fa-user-md me-1 text-success"></i>
                                                <?= htmlspecialchars($contract['doctor_name']) ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($frequency === 'monthly'): ?>
                                                <span class="badge bg-primary">毎月</span>
                                            <?php elseif ($frequency === 'bimonthly'): ?>
                                                <span class="badge bg-info">隔月</span>
                                            <?php elseif ($frequency === 'weekly'): ?>
                                                <span class="badge bg-success">毎週</span>
                                            <?php elseif ($frequency === 'spot'): ?>
                                                <span class="badge bg-warning">スポット</span>
                                            <?php endif; ?>
                                            <?php if ($statusBadge): ?>
                                                <br><?= $statusBadge ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- 週間スケジュール（毎週契約の場合） -->
                                    <?php if ($frequency === 'weekly' && !empty($contract['weekly_schedule'])): ?>
                                        <div class="alert alert-success py-2 px-3 mb-3">
                                            <small><i class="fas fa-calendar-week me-1"></i><?= htmlspecialchars($contract['weekly_schedule']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- 契約時間と今月実績 -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <div class="bg-light p-2 rounded text-center">
                                                <small class="text-muted d-block">契約時間</small>
                                                <strong><?= $frequency === 'spot' ? '-' : format_hours_minutes($displayHours) ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="bg-light p-2 rounded text-center">
                                                <small class="text-muted d-block">今月実績</small>
                                                <strong class="text-primary"><?= format_hours_minutes($thisMonthHours) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 役務内訳 -->
                                    <?php if ($regularHours > 0 || $emergencyHours > 0 || $extensionHours > 0): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">役務内訳</small>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php if ($regularHours > 0): ?>
                                                    <span class="badge bg-primary">定期: <?= format_hours_minutes($regularHours) ?></span>
                                                <?php endif; ?>
                                                <?php if ($emergencyHours > 0): ?>
                                                    <span class="badge bg-danger">緊急: <?= format_hours_minutes($emergencyHours) ?></span>
                                                <?php endif; ?>
                                                <?php if ($extensionHours > 0): ?>
                                                    <span class="badge bg-warning">延長: <?= format_hours_minutes($extensionHours) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- 時間なし役務 -->
                                    <?php if ($documentCount > 0 || $remoteConsultationCount > 0 || $otherCount > 0): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">時間なし役務</small>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php if ($documentCount > 0): ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-file-alt"></i> 書類作成 <?= $documentCount ?>件
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($remoteConsultationCount > 0): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-envelope"></i> 遠隔面談 <?= $remoteConsultationCount ?>件
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($otherCount > 0): ?>
                                                    <span class="badge bg-dark">
                                                        <i class="fas fa-plus"></i> その他 <?= $otherCount ?>件
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- 残り時間 -->
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">残り時間</small>
                                            <strong class="<?= $remainingClass ?>">
                                                <?= format_hours_minutes($remainingHours) ?>
                                            </strong>
                                        </div>
                                    </div>
                                    
                                    <!-- 進捗バー -->
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">進捗</small>
                                            <small class="text-muted">
                                                <?= format_hours_minutes($regularHours) ?> / <?= format_hours_minutes($monthlyHours) ?>
                                            </small>
                                        </div>
                                        <div class="progress" style="height: 24px;">
                                            <div class="progress-bar <?= $progressClass ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= min(100, $usagePercentage) ?>%;" 
                                                 aria-valuenow="<?= $usagePercentage ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <strong><?= number_format($usagePercentage, 1) ?>%</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- ページネーション -->
                    <?php if ($contractsPaginationInfo['total_pages'] > 1): ?>
                        <nav aria-label="契約一覧ページネーション" class="mt-3">
                            <ul class="pagination justify-content-center flex-wrap">
                                <?php if ($contractsPaginationInfo['has_previous']): ?>
                                    <li class="page-item">
                                        <a class="page-link contracts-page-link" href="#" data-page="<?= $contractsPaginationInfo['current_page'] - 1 ?>">&laquo; 前へ</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                $startPage = max(1, $contractsPaginationInfo['current_page'] - 2);
                                $endPage = min($contractsPaginationInfo['total_pages'], $contractsPaginationInfo['current_page'] + 2);
                                ?>
                                
                                <?php if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link contracts-page-link" href="#" data-page="1">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= $i == $contractsPaginationInfo['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link contracts-page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $contractsPaginationInfo['total_pages']): ?>
                                    <?php if ($endPage < $contractsPaginationInfo['total_pages'] - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link contracts-page-link" href="#" data-page="<?= $contractsPaginationInfo['total_pages'] ?>"><?= $contractsPaginationInfo['total_pages'] ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($contractsPaginationInfo['has_next']): ?>
                                    <li class="page-item">
                                        <a class="page-link contracts-page-link" href="#" data-page="<?= $contractsPaginationInfo['current_page'] + 1 ?>">次へ &raquo;</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 役務記録詳細モーダル -->
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="recordDetailModalLabel">
                    <i class="fas fa-info-circle me-2"></i>役務記録詳細
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body" id="recordDetailContent">
                <!-- JavaScriptで動的に内容を挿入 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 差戻しモーダル -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>差戻し理由
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" id="rejectRecordId" name="record_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="alert alert-warning">
                        <strong id="rejectDoctorName"></strong>さんの役務記録を差戻します
                    </div>
                    <div class="mb-3">
                        <label for="rejectReason" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectReason" name="comment" rows="4" required
                            placeholder="差戻しの理由を具体的に記入してください"></textarea>
                        <small><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>キャンセル
                    </button>
                    <button type="submit" class="btn btn-danger" id="rejectSubmitBtn">
                        <i class="fas fa-paper-plane me-1"></i>差戻す
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 締め処理差戻しモーダル -->
<div class="modal fade" id="rejectClosingModal" tabindex="-1" aria-labelledby="rejectClosingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectClosingModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>締め処理差戻し理由
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <form id="rejectClosingForm">
                <div class="modal-body">
                    <input type="hidden" id="rejectClosingId" name="closing_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="alert alert-warning">
                        <strong id="rejectClosingDoctorName"></strong>さんの<strong id="rejectClosingMonth"></strong>締め処理を差戻します
                    </div>
                    <div class="mb-3">
                        <label for="rejectClosingReason" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectClosingReason" name="rejection_reason" rows="4" required
                            placeholder="差戻しの理由を具体的に記入してください"></textarea>
                        <small><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>キャンセル
                    </button>
                    <button type="submit" class="btn btn-danger" id="rejectClosingSubmitBtn">
                        <i class="fas fa-paper-plane me-1"></i>差戻す
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 一括承認モーダル(役務記録) -->
<div class="modal fade" id="bulkApproveModal" tabindex="-1" aria-labelledby="bulkApproveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkApproveModalLabel">
                    <i class="fas fa-check-double me-2"></i>役務記録の一括承認
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <form id="bulkApproveForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        選択された<strong><span id="bulkApproveRecordCount">0</span>件</strong>の役務記録を一括承認します。
                    </div>
                    
                    <div id="bulkApproveList" class="mb-3">
                        <!-- JavaScriptで動的に挿入 -->
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmBulkApprove" required>
                        <label class="form-check-label" for="confirmBulkApprove">
                            上記の内容を確認し、一括承認することに同意します
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>キャンセル
                    </button>
                    <button type="submit" class="btn btn-success" id="bulkApproveSubmitBtn" disabled>
                        <i class="fas fa-check-double me-1"></i><span id="bulkApproveCount">0</span>件を一括承認
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 一括承認モーダル(締め処理) -->
<div class="modal fade" id="bulkApproveClosingModal" tabindex="-1" aria-labelledby="bulkApproveClosingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkApproveClosingModalLabel">
                    <i class="fas fa-check-double me-2"></i>締め処理の一括承認
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <form id="bulkApproveClosingForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        選択された<strong><span id="bulkApproveClosingRecordCount">0</span>件</strong>の締め処理を一括承認します。
                    </div>
                    
                    <div id="bulkApproveClosingList" class="mb-3">
                        <!-- JavaScriptで動的に挿入 -->
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmBulkApproveClosing" required>
                        <label class="form-check-label" for="confirmBulkApproveClosing">
                            上記の内容を確認し、一括承認することに同意します
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>キャンセル
                    </button>
                    <button type="submit" class="btn btn-success" id="bulkApproveClosingSubmitBtn" disabled>
                        <i class="fas fa-check-double me-1"></i><span id="bulkApproveClosingCountBtn">0</span>件を一括承認
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* レスポンシブ対応のスタイル */
@media (max-width: 991.98px) {
    .desktop-table {
        display: none !important;
    }
    .mobile-cards {
        display: block !important;
    }
}

@media (min-width: 992px) {
    .desktop-table {
        display: block !important;
    }
    .mobile-cards {
        display: none !important;
    }
}

/* モバイルカードのスタイル */
.pending-record-card,
.pending-closing-card {
    border-left: 4px solid #ffc107;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.pending-record-card:hover,
.pending-closing-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.record-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* 役務記録詳細モーダルのスタイル */
.detail-row {
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #495057;
    min-width: 120px;
}

.detail-value {
    color: #212529;
}

/* チェックボックス選択時のハイライト */
.record-checkbox:checked ~ label {
    font-weight: bold;
}

.closing-checkbox:checked ~ label {
    font-weight: bold;
}
</style>

<script>
// 役務記録詳細モーダルを表示
function showRecordDetailModal(record) {
    const modal = new bootstrap.Modal(document.getElementById('recordDetailModal'));
    const content = document.getElementById('recordDetailContent');
    
    // 役務種別の情報
    const serviceTypeInfo = {
        'regular': { label: '定期', icon: 'calendar-check', class: 'success' },
        'emergency': { label: '臨時', icon: 'exclamation-triangle', class: 'warning' },
        'extension': { label: '延長', icon: 'clock', class: 'info' },
        'spot': { label: 'スポット', icon: 'clock', class: 'info' },
        'document': { label: '書面', icon: 'file-alt', class: 'primary' },
        'remote_consultation': { label: '遠隔', icon: 'envelope', class: 'secondary' },
        'other': { label: 'その他', icon: 'file-plus', class: 'dark' }
    };
    
    const serviceType = record.service_type || 'regular';
    const typeInfo = serviceTypeInfo[serviceType] || serviceTypeInfo['regular'];
    
    // 自動分割記録の判定
    const isAutoSplit = record.is_auto_split || 
                       (record.description && record.description.includes('自動分割')) ||
                       (record.description && record.description.includes('延長分'));
    
    // 役務内容の処理
    let description = record.description || '';
    if (isAutoSplit && description.includes('元の内容:')) {
        const matches = description.match(/元の内容:\s*(.+?)$/s);
        if (matches) {
            description = matches[1];
        }
    }
    
    // 時間情報の表示
    let timeDisplay = '';
    if (['document', 'remote_consultation', 'other'].includes(serviceType)) {
        timeDisplay = '<span class="text-muted">時間記録なし</span>';
    } else if (record.service_hours > 0) {
        if (record.start_time && record.end_time) {
            timeDisplay = `${record.start_time} ～ ${record.end_time}`;
        } else {
            timeDisplay = '<span class="text-muted">未記録</span>';
        }
        if (isAutoSplit) {
            timeDisplay += '<br><small class="text-info"><i class="fas fa-info-circle me-1"></i>分割記録</small>';
        }
    } else {
        timeDisplay = '<span class="badge bg-warning">進行中</span>';
    }
    
    // 役務時間の表示（PHPのformat_hours_minutes()と同じロジック）
    let hoursDisplay = '';
    if (['document', 'remote_consultation', 'other'].includes(serviceType)) {
        hoursDisplay = '<span class="badge bg-secondary">時間なし</span>';
    } else if (record.service_hours > 0) {
        const decimalHours = parseFloat(record.service_hours);
        let hours = Math.floor(decimalHours);
        let minutes = Math.round((decimalHours - hours) * 60);
        
        // 60分になった場合は1時間に繰り上げ
        if (minutes >= 60) {
            hours += 1;
            minutes = 0;
        }
        
        let timeText = '';
        if (hours > 0 && minutes > 0) {
            timeText = `${hours}時間${minutes}分`;
        } else if (hours > 0) {
            timeText = `${hours}時間`;
        } else {
            timeText = `${minutes}分`;
        }
        
        hoursDisplay = `<span class="badge bg-info">${timeText}</span>`;
    } else {
        hoursDisplay = '<span class="badge bg-warning">進行中</span>';
    }
    
    // HTMLを構築
    content.innerHTML = `
        <div class="container-fluid">
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-hashtag me-2 text-muted"></i>記録ID
                </div>
                <div class="col-md-8 detail-value">
                    <code>#${record.id}</code>
                </div>
            </div>
            
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>拠点
                </div>
                <div class="col-md-8 detail-value">
                    <strong>${escapeHtml(record.branch_name || '不明')}</strong>
                </div>
            </div>
            
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-user-md me-2 text-info"></i>産業医
                </div>
                <div class="col-md-8 detail-value">
                    ${escapeHtml(record.doctor_name || '不明')}
                </div>
            </div>
            
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-calendar me-2 text-success"></i>役務日
                </div>
                <div class="col-md-8 detail-value">
                    ${formatDate(record.service_date || '')}
                </div>
            </div>
            
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-tag me-2 text-${typeInfo.class}"></i>役務種別
                </div>
                <div class="col-md-8 detail-value">
                    <span class="badge bg-${typeInfo.class}">
                        <i class="fas fa-${typeInfo.icon} me-1"></i>${typeInfo.label}
                    </span>
                    ${serviceType === 'extension' && isAutoSplit ? 
                        '<span class="badge bg-secondary ms-2"><i class="fas fa-scissors me-1"></i>自動分割</span>' : ''}
                </div>
            </div>
            
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-clock me-2 text-primary"></i>実施時間
                </div>
                <div class="col-md-8 detail-value">
                    ${timeDisplay}
                </div>
            </div>
            
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-hourglass-half me-2 text-info"></i>役務時間
                </div>
                <div class="col-md-8 detail-value">
                    ${hoursDisplay}
                </div>
            </div>
            
            ${description ? `
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-file-alt me-2 text-secondary"></i>役務内容
                </div>
                <div class="col-md-8 detail-value">
                    <div class="bg-light p-3 rounded" style="white-space: pre-wrap;">${escapeHtml(description)}</div>
                    ${isAutoSplit ? '<small class="text-secondary mt-2 d-block"><i class="fas fa-info-circle me-1"></i>この記録は自動分割により作成されました</small>' : ''}
                </div>
            </div>
            ` : ''}
            
            ${record.overtime_reason ? `
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>延長理由
                </div>
                <div class="col-md-8 detail-value">
                    <div class="alert alert-warning mb-0" style="white-space: pre-wrap;">${escapeHtml(record.overtime_reason)}</div>
                </div>
            </div>
            ` : ''}
            
            ${record.created_at ? `
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-plus-circle me-2 text-muted"></i>作成日時
                </div>
                <div class="col-md-8 detail-value">
                    <small class="text-muted">${formatDateTime(record.created_at)}</small>
                </div>
            </div>
            ` : ''}
            
            ${record.updated_at ? `
            <div class="row detail-row">
                <div class="col-md-4 detail-label">
                    <i class="fas fa-edit me-2 text-muted"></i>更新日時
                </div>
                <div class="col-md-8 detail-value">
                    <small class="text-muted">${formatDateTime(record.updated_at)}</small>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
    modal.show();
}

// HTMLエスケープ関数
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 日付フォーマット関数
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    const weekday = weekdays[date.getDay()];
    return `${year}年${month}月${day}日（${weekday}）`;
}

// 日時フォーマット関数
function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return '-';
    const date = new Date(dateTimeStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}年${month}月${day}日 ${hours}:${minutes}`;
}

// 承認処理
function approveRecord(recordId) {
    if (!confirm('この役務記録を承認してもよろしいですか?')) {
        return;
    }
    
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
    
    if (!csrfToken) {
        alert('CSRFトークンが見つかりません。ページをリロードしてください。');
        return;
    }
    
    const params = new URLSearchParams();
    params.append('csrf_token', csrfToken);
    
    fetch(`<?= base_url('service_records/') ?>${recordId}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('役務記録を承認しました。');
            removeRecordFromPendingList(recordId);
            updatePendingCount();
        } else {
            handleError(new Error(data.message || '承認処理に失敗しました'), '承認処理');
        }
    })
    .catch(error => {
        console.error('承認処理エラー:', error);
        handleError(error, '承認処理');
    });
}

// 差戻しモーダルを表示
function showRejectModal(recordId, doctorName) {
    document.getElementById('rejectRecordId').value = recordId;
    document.getElementById('rejectDoctorName').textContent = doctorName;
    document.getElementById('rejectReason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

// 差戻し処理
document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const recordId = formData.get('record_id');
    const comment = formData.get('comment');
    const csrfToken = formData.get('csrf_token');
    const btn = document.getElementById('rejectSubmitBtn');
    const originalText = btn.innerHTML;
    
    if (!csrfToken) {
        alert('CSRFトークンが見つかりません。ページをリロードしてください。');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>処理中...';
    
    const params = new URLSearchParams();
    params.append('comment', comment);
    params.append('csrf_token', csrfToken);
    
    fetch(`<?= base_url('service_records/') ?>${recordId}/reject`, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest' 
        },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('役務記録を差戻しました。');
            const modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
            if (modal) modal.hide();
            removeRecordFromPendingList(recordId);
            updatePendingCount();
        } else {
            handleError(new Error(data.message || '差戻し処理に失敗しました'), '差戻し処理');
        }
    })
    .catch(error => {
        console.error('差戻し処理エラー:', error);
        handleError(error, '差戻し処理');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

// ペンディングリストから記録を削除
function removeRecordFromPendingList(recordId) {
    const desktopRow = document.querySelector(`.desktop-table tr:has(input[value="${recordId}"])`);
    if (desktopRow) {
        desktopRow.style.transition = 'opacity 0.3s ease';
        desktopRow.style.opacity = '0';
        setTimeout(() => desktopRow.remove(), 300);
    }
    
    const mobileCard = document.querySelector(`.mobile-cards .card:has(input[value="${recordId}"])`);
    if (mobileCard) {
        mobileCard.style.transition = 'opacity 0.3s ease';
        mobileCard.style.opacity = '0';
        setTimeout(() => mobileCard.remove(), 300);
    }
}

// 承認待ち件数を更新
function updatePendingCount() {
    // 実際に表示されている行数をカウント（削除される前/後も正確に）
    const desktopRows = document.querySelectorAll('.desktop-table tbody tr');
    const mobileCards = document.querySelectorAll('.mobile-cards .card');
    
    // デスクトップとモバイルのどちらかでカウント（同じ記録なので）
    const count = desktopRows.length > 0 ? desktopRows.length : mobileCards.length;
    
    console.log('[updatePendingCount] Desktop rows:', desktopRows.length, 'Mobile cards:', mobileCards.length, 'Final count:', count);
    
    // バッジの更新
    const badges = document.querySelectorAll('.badge.bg-warning');
    badges.forEach(badge => {
        if (badge.textContent.includes('件')) {
            badge.textContent = `${count}件`;
        }
    });
    
    // ページネーション情報の更新
    const headerPaginationInfo = document.querySelector('.card-header h5 small.text-muted');
    const bodyPaginationInfo = document.querySelector('.card-body.border-bottom.bg-light .text-end small.text-muted');
    
    console.log('[updatePendingCount] Header info element:', !!headerPaginationInfo, 'Body info element:', !!bodyPaginationInfo);
    
    if (count > 0) {
        const paginationText = `（1-${count}件 / 全${count}件）`;
        if (headerPaginationInfo) {
            headerPaginationInfo.textContent = paginationText;
            headerPaginationInfo.style.display = '';
            console.log('[updatePendingCount] Updated header pagination to:', paginationText);
        }
        if (bodyPaginationInfo) {
            bodyPaginationInfo.textContent = `1-${count}件 / 全${count}件`;
            console.log('[updatePendingCount] Updated body pagination');
        }
        const paginationSettings = document.querySelector('.card-body.border-bottom.bg-light');
        if (paginationSettings) {
            paginationSettings.style.display = '';
        }
    } else {
        // カウントが0の場合
        console.log('[updatePendingCount] Count is 0, replacing with empty message');
        
        // ページネーション情報を非表示
        if (headerPaginationInfo) {
            headerPaginationInfo.style.display = 'none';
        }
        const paginationSettings = document.querySelector('.card-body.border-bottom.bg-light');
        if (paginationSettings) {
            paginationSettings.style.display = 'none';
        }
        
        // テーブル全体を空メッセージに置き換え
        // 承認待ちの役務記録のカードを特定（ヘッダーに「承認待ちの役務記録」という文字列がある）
        const pendingRecordsCard = Array.from(document.querySelectorAll('.card')).find(card => {
            const header = card.querySelector('.card-header h5');
            return header && header.textContent.includes('承認待ちの役務記録');
        });
        
        if (pendingRecordsCard) {
            // そのカード内のメインのcard-bodyを探す（ページネーション設定ではない方）
            const cardBodies = pendingRecordsCard.querySelectorAll('.card-body');
            cardBodies.forEach(cardBody => {
                // ページネーション設定のcard-bodyは除外
                if (!cardBody.classList.contains('border-bottom')) {
                    cardBody.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">承認待ちの役務記録はありません。</p>
                        </div>
                    `;
                    console.log('[updatePendingCount] Replaced table with empty message');
                }
            });
        }
        
        // 一括承認ボタンと「すべて見る」ボタンを非表示
        const bulkApproveBtn = document.querySelector('button[onclick="showBulkApproveModal()"]');
        const viewAllBtn = document.querySelector('a[href*="service_records?status=pending"]');
        if (bulkApproveBtn) {
            bulkApproveBtn.style.display = 'none';
            console.log('[updatePendingCount] Hidden bulk approve button');
        }
        if (viewAllBtn) {
            viewAllBtn.style.display = 'none';
            console.log('[updatePendingCount] Hidden view all button');
        }
    }
}





// 成功メッセージを表示
function showSuccessMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// エラーハンドリング
function handleError(error, context) {
    console.error(`${context}エラー:`, error);
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${context}に失敗しました: ${error.message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// すべてのチェックボックスを切り替え
function toggleAllRecords(event) {
    const isChecked = event.target.checked;
    document.querySelectorAll('.record-checkbox').forEach(checkbox => {
        // 無効化されていないチェックボックスのみを切り替え
        if (!checkbox.disabled) {
            checkbox.checked = isChecked;
        }
    });
    updateBulkApproveButton();
}

// 一括承認ボタンの状態を更新
function updateBulkApproveButton() {
    const checkedBoxes = document.querySelectorAll('.record-checkbox:checked');
    const count = checkedBoxes.length;
    
    // ボタンの有効/無効を切り替え
    const bulkApproveButtons = document.querySelectorAll('button[onclick="showBulkApproveModal()"]');
    bulkApproveButtons.forEach(btn => {
        btn.disabled = count === 0;
    });
}

// 一括承認モーダルを表示
function showBulkApproveModal() {
    const checkedBoxes = document.querySelectorAll('.record-checkbox:checked');
    
    // 重複を避けるため、ユニークなrecord_idのみをカウント
    const uniqueIds = new Set();
    checkedBoxes.forEach(checkbox => {
        uniqueIds.add(checkbox.value);
    });
    const count = uniqueIds.size;
    
    if (count === 0) {
        alert('承認する記録を選択してください。');
        return;
    }
    
    // カウントを更新
    document.getElementById('bulkApproveRecordCount').textContent = count;
    document.getElementById('bulkApproveCount').textContent = count;
    
    // 重複を避けるため、record_idごとに一度だけ処理
    const processedIds = new Set();
    const listHtml = Array.from(checkedBoxes).map(checkbox => {
        const recordId = checkbox.value;
        
        // すでに処理済みのIDはスキップ
        if (processedIds.has(recordId)) {
            return '';
        }
        processedIds.add(recordId);
        
        const row = checkbox.closest('tr, .card');
        let branchName, doctorName, serviceDate;
        
        if (row.tagName === 'TR') {
            // デスクトップ版
            branchName = row.querySelector('td:nth-child(2) strong')?.textContent || '不明';
            doctorName = row.querySelector('td:nth-child(3)')?.textContent.trim() || '不明';
            serviceDate = row.querySelector('td:nth-child(4)')?.textContent.trim() || '不明';
        } else {
            // モバイル版
            branchName = row.querySelector('.card-title')?.textContent.trim().replace(/\s+/g, ' ') || '不明';
            doctorName = row.querySelector('.text-muted')?.textContent.trim().replace(/\s+/g, ' ') || '不明';
            serviceDate = row.querySelectorAll('.text-muted')[1]?.textContent.trim().replace(/\s+/g, ' ') || '不明';
        }
        
        return `
            <input type="hidden" name="record_ids[]" value="${recordId}">
            <div class="border-bottom pb-2 mb-2">
                <strong>${branchName}</strong> - ${doctorName}<br>
                <small class="text-muted">${serviceDate}</small>
            </div>
        `;
    }).filter(html => html !== '').join('');
    
    document.getElementById('bulkApproveList').innerHTML = listHtml;
    
    // 確認チェックボックスをリセット
    const confirmCheckbox = document.getElementById('confirmBulkApprove');
    confirmCheckbox.checked = false;
    document.getElementById('bulkApproveSubmitBtn').disabled = true;
    
    const modal = new bootstrap.Modal(document.getElementById('bulkApproveModal'));
    modal.show();
}

// 締め処理の承認
function approveClosing(closingId) {
    if (!confirm('この締め処理を承認してもよろしいですか?')) {
        return;
    }
    
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    fetch('<?= base_url('closing/companyApprove') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `closing_id=${closingId}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('締め処理を承認しました。');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            handleError(new Error(data.message || '承認処理に失敗しました'), '承認処理');
        }
    })
    .catch(error => {
        console.error('承認処理エラー:', error);
        handleError(error, '承認処理');
    });
}

// 締め処理の差戻しモーダルを表示
function showRejectClosingModal(closingId, doctorName, targetMonth) {
    document.getElementById('rejectClosingId').value = closingId;
    document.getElementById('rejectClosingDoctorName').textContent = doctorName;
    document.getElementById('rejectClosingMonth').textContent = targetMonth;
    document.getElementById('rejectClosingReason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('rejectClosingModal'));
    modal.show();
}

// 締め処理の差戻し処理
document.getElementById('rejectClosingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = document.getElementById('rejectClosingSubmitBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>処理中...';
    
    // FormDataをURLSearchParamsに変換
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        params.append(key, value);
    }
    
    fetch('<?= base_url('closing/companyReject') ?>', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest' 
        },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('締め処理を差戻しました。');
            const modal = bootstrap.Modal.getInstance(document.getElementById('rejectClosingModal'));
            if (modal) modal.hide();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            handleError(new Error(data.message || '差戻し処理に失敗しました'), '差戻し処理');
        }
    })
    .catch(error => {
        console.error('差戻し処理エラー:', error);
        handleError(error, '差戻し処理');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

// すべての締め処理チェックボックスを切り替え
function toggleAllClosingItems(event) {
    const isChecked = event.target.checked;
    document.querySelectorAll('.closing-checkbox').forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    updateBulkApproveClosingButton();
}

// 一括承認ボタン(締め処理)の状態を更新
function updateBulkApproveClosingButton() {
    const checkedBoxes = document.querySelectorAll('.closing-checkbox:checked');
    const count = checkedBoxes.length;
    
    const bulkApproveButtons = document.querySelectorAll('button[onclick="showBulkApproveClosingModal()"]');
    bulkApproveButtons.forEach(btn => {
        btn.disabled = count === 0;
    });
}

// 一括承認モーダル(締め処理)を表示
function showBulkApproveClosingModal() {
    const checkedBoxes = document.querySelectorAll('.closing-checkbox:checked');
    
    // 重複を避けるため、ユニークなclosing_idのみをカウント
    const uniqueIds = new Set();
    checkedBoxes.forEach(checkbox => {
        uniqueIds.add(checkbox.value);
    });
    const count = uniqueIds.size;
    
    if (count === 0) {
        alert('承認する締め処理を選択してください。');
        return;
    }
    
    document.getElementById('bulkApproveClosingRecordCount').textContent = count;
    document.getElementById('bulkApproveClosingCountBtn').textContent = count;
    
    // 重複を避けるため、closing_idごとに一度だけ処理
    const processedIds = new Set();
    const listHtml = Array.from(checkedBoxes).map(checkbox => {
        const closingId = checkbox.value;
        
        // すでに処理済みのIDはスキップ
        if (processedIds.has(closingId)) {
            return '';
        }
        processedIds.add(closingId);
        
        const contractId = checkbox.dataset.contractId;
        const closingPeriod = checkbox.dataset.closingPeriod;
        const row = checkbox.closest('tr, .card');
        let targetMonth, branchName, doctorName;
        
        if (row.tagName === 'TR') {
            targetMonth = row.querySelector('td:nth-child(2) strong')?.textContent || '不明';
            branchName = row.querySelector('td:nth-child(3)')?.textContent.trim() || '不明';
            doctorName = row.querySelector('td:nth-child(4)')?.textContent.trim() || '不明';
        } else {
            targetMonth = row.querySelector('.card-title')?.textContent.trim().replace(/\s+/g, ' ') || '不明';
            branchName = row.querySelectorAll('p')[0]?.textContent.trim().replace(/\s+/g, ' ') || '不明';
            doctorName = row.querySelectorAll('.text-muted')[0]?.textContent.trim().replace(/\s+/g, ' ') || '不明';
        }
        
        // JSON形式でデータを格納
        const itemData = JSON.stringify({
            contract_id: contractId,
            closing_period: closingPeriod
        });
        
        return `
            <input type="hidden" name="closing_items[]" value='${itemData}'>
            <div class="border-bottom pb-2 mb-2">
                <strong>${targetMonth}</strong><br>
                ${branchName} - ${doctorName}
            </div>
        `;
    }).filter(html => html !== '').join('');
    
    document.getElementById('bulkApproveClosingList').innerHTML = listHtml;
    
    const confirmCheckbox = document.getElementById('confirmBulkApproveClosing');
    confirmCheckbox.checked = false;
    document.getElementById('bulkApproveClosingSubmitBtn').disabled = true;
    
    const modal = new bootstrap.Modal(document.getElementById('bulkApproveClosingModal'));
    modal.show();
}

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    updateBulkApproveButton();
    updateBulkApproveClosingButton();

    // 一括承認フォーム(役務記録)
    const bulkApproveForm = document.getElementById('bulkApproveForm');
    if (bulkApproveForm) {
        bulkApproveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const recordIds = formData.getAll('record_ids[]');
            
            if (recordIds.length === 0) {
                alert('承認する記録が選択されていません。');
                return;
            }
            
            const submitBtn = document.getElementById('bulkApproveSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>処理中...';
            }
            
            // FormDataをURLSearchParamsに変換
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            fetch('<?= base_url('service_records/bulk-approve') ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' 
                },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(`${recordIds.length}件の役務記録を一括承認しました。`);
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkApproveModal'));
                    if (modal) modal.hide();
                    
                    // 各記録を順番に削除し、すべて完了後にカウントを更新
                    let completedCount = 0;
                    recordIds.forEach((recordId, index) => {
                        setTimeout(() => {
                            removeRecordFromPendingList(recordId);
                            completedCount++;
                            
                            // すべての削除が完了したら、さらに350ms待ってからカウント更新
                            // （最後の要素のフェードアウト300ms + 余裕50ms）
                            if (completedCount === recordIds.length) {
                                setTimeout(() => {
                                    // DOM更新を確実に反映させるためrequestAnimationFrameを使用
                                    requestAnimationFrame(() => {
                                        updateBulkApproveButton();
                                        updatePendingCount();
                                    });
                                }, 350);
                            }
                        }, index * 50);
                    });
                    
                    // チェックボックスのチェックを外す（不要だが念のため）
                    document.querySelectorAll('.record-checkbox:checked').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                } else {
                    handleError(new Error(data.message || '一括承認処理に失敗しました'), '一括承認処理');
                }
            })
            .catch(error => {
                console.error('一括承認処理エラー:', error);
                handleError(error, '一括承認処理');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-double me-1"></i><span id="bulkApproveCount">0</span>件を一括承認';
                }
            });
        });
        
        const confirmCheckbox = document.getElementById('confirmBulkApprove');
        if (confirmCheckbox) {
            confirmCheckbox.addEventListener('change', function() {
                const submitBtn = document.getElementById('bulkApproveSubmitBtn');
                if (submitBtn) submitBtn.disabled = !this.checked;
            });
        }
    }

    // 一括承認フォーム(締め処理)
    const bulkApproveClosingForm = document.getElementById('bulkApproveClosingForm');
    if (bulkApproveClosingForm) {
        bulkApproveClosingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const closingItems = formData.getAll('closing_items[]');
            
            if (closingItems.length === 0) {
                alert('承認する締め処理が選択されていません。');
                return;
            }
            
            const submitBtn = document.getElementById('bulkApproveClosingSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>処理中...';
            }
            
            // FormDataをURLSearchParamsに変換
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            fetch('<?= base_url('closing/bulk-company-approve') ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' 
                },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(`${closingItems.length}件の締め処理を一括承認しました。`);
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkApproveClosingModal'));
                    if (modal) modal.hide();
                    
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    handleError(new Error(data.message || '一括承認処理に失敗しました'), '一括承認処理');
                }
            })
            .catch(error => {
                console.error('一括承認処理エラー:', error);
                handleError(error, '一括承認処理');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-double me-1"></i><span id="bulkApproveClosingCountBtn">0</span>件を一括承認';
                }
            });
        });
        
        const confirmClosingCheckbox = document.getElementById('confirmBulkApproveClosing');
        if (confirmClosingCheckbox) {
            confirmClosingCheckbox.addEventListener('change', function() {
                const submitBtn = document.getElementById('bulkApproveClosingSubmitBtn');
                if (submitBtn) submitBtn.disabled = !this.checked;
            });
        }
    }
    
    // 契約一覧のページネーション
    const contractsPageSize = document.getElementById('contracts_page_size');
    if (contractsPageSize) {
        contractsPageSize.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('contracts_page_size', this.value);
            url.searchParams.set('contracts_page', '1');
            window.location.href = url.toString();
        });
    }
    
    document.querySelectorAll('.contracts-page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            const url = new URL(window.location);
            url.searchParams.set('contracts_page', page);
            window.location.href = url.toString();
        });
    });
});
</script>