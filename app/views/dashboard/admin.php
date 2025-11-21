<?php
// app/views/dashboard/admin.php - ページネーション機能付き完全版（タクシー利用列追加）
?>
<!-- 既存のヘッダー部分はそのまま -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-cog me-2"></i>管理者ダッシュボード</h2>
    <div class="btn-group">
        <a href="<?= base_url('users/create') ?>" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i>ユーザー追加
        </a>
        <a href="<?= base_url('companies/create') ?>" class="btn btn-success">
            <i class="fas fa-building me-1"></i>企業追加
        </a>
        <a href="<?= base_url('contracts/create') ?>" class="btn btn-info">
            <i class="fas fa-file-contract me-1"></i>契約追加
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
<!-- 交通費承認待ち（タクシー利用列追加版） -->
<?php if (!empty($pendingTravelExpenses)): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-car me-2"></i>交通費承認待ち
                    <!-- 承認待ち交通費の表示件数とページネーション情報 -->
                    <?php if ($pendingTravelExpensesPaginationInfo['total_count'] > 0): ?>
                        <small class="text-muted">
                            （<?= $pendingTravelExpensesPaginationInfo['start_record'] ?>-<?= $pendingTravelExpensesPaginationInfo['end_record'] ?>件 / 
                            全<?= $pendingTravelExpensesPaginationInfo['total_count'] ?>件）
                        </small>
                    <?php endif; ?>
                </h5>
                <div>
                    <span class="badge bg-warning me-2"><?= count($pendingTravelExpenses) ?>件</span>
                    <!-- 一括承認ボタン -->
                    <button type="button" class="btn btn-sm btn-success me-2" onclick="showBulkApproveTravelExpenseModal()" title="選択した交通費を一括承認" disabled id="bulkApproveTravelExpenseBtn">
                        <i class="fas fa-check-double me-1"></i>一括承認
                    </button>
                    <a href="<?= base_url('travel_expenses?status=pending') ?>" class="btn btn-sm btn-outline-primary">
                        すべて見る
                    </a>
                </div>
            </div>

            <!-- ページネーション設定（承認待ち交通費） -->
            <?php if (isset($pendingTravelExpensesPaginationInfo) && $pendingTravelExpensesPaginationInfo['total_count'] > 0): ?>
            <div class="card-body border-bottom bg-light">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <form method="GET" class="d-flex align-items-center">
                            <!-- 既存のGETパラメータを維持 -->
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if (!in_array($key, ['pending_travel_expenses_page', 'pending_travel_expenses_page_size'])): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <label for="pendingTravelExpensesPageSize" class="form-label me-2 mb-0">表示件数:</label>
                            <select class="form-select form-select-sm" id="pendingTravelExpensesPageSize" name="pending_travel_expenses_page_size" onchange="this.form.submit()" style="width: auto;">
                                <option value="5" <?= (($_GET['pending_travel_expenses_page_size'] ?? 10) == 5) ? 'selected' : '' ?>>5件</option>
                                <option value="10" <?= (($_GET['pending_travel_expenses_page_size'] ?? 10) == 10) ? 'selected' : '' ?>>10件</option>
                                <option value="15" <?= (($_GET['pending_travel_expenses_page_size'] ?? 10) == 15) ? 'selected' : '' ?>>15件</option>
                                <option value="20" <?= (($_GET['pending_travel_expenses_page_size'] ?? 10) == 20) ? 'selected' : '' ?>>20件</option>
                                <option value="30" <?= (($_GET['pending_travel_expenses_page_size'] ?? 10) == 30) ? 'selected' : '' ?>>30件</option>
                                <option value="50" <?= (($_GET['pending_travel_expenses_page_size'] ?? 10) == 50) ? 'selected' : '' ?>>50件</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-8 text-end">
                        <small class="text-muted">
                            <?= $pendingTravelExpensesPaginationInfo['start_record'] ?>-<?= $pendingTravelExpensesPaginationInfo['end_record'] ?>件 / 
                            全<?= $pendingTravelExpensesPaginationInfo['total_count'] ?>件
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAllTravelExpenses" onchange="toggleAllTravelExpenses()">
                                        <label class="form-check-label" for="selectAllTravelExpenses">
                                            <span class="visually-hidden">すべて選択</span>
                                        </label>
                                    </div>
                                </th>
                                <th>産業医</th>
                                <th>企業・拠点</th>
                                <th>タクシー利用</th>
                                <th>役務日</th>
                                <th>出発地・到着地</th>
                                <th>手段</th>
                                <th class="text-end">金額</th>
                                <th class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingTravelExpenses as $expense): ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input travel-expense-checkbox" type="checkbox" 
                                                value="<?= $expense['id'] ?>" 
                                                id="travel_expense_<?= $expense['id'] ?>"
                                                onchange="updateBulkApproveTravelExpenseButton()">
                                            <label class="form-check-label" for="travel_expense_<?= $expense['id'] ?>">
                                                <span class="visually-hidden">選択</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-user-md me-1 text-info"></i>
                                        <small><?= htmlspecialchars($expense['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td>
                                        <small>
                                            <strong><?= htmlspecialchars($expense['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <span class="text-muted"><?= htmlspecialchars($expense['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?></span>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if (isset($expense['taxi_allowed']) && $expense['taxi_allowed'] == 1): ?>
                                            <span class="badge bg-success" title="契約でタクシー利用が許可されています">
                                                <i class="fas fa-taxi me-1"></i>可
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger" title="契約でタクシー利用が許可されていません">
                                                <i class="fas fa-ban me-1"></i>不可
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= format_date($expense['service_date'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?php if ($expense['departure_point']): ?>
                                        <small>
                                            <strong><?= htmlspecialchars($expense['departure_point'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <span class="text-muted">↓</span><br>
                                            <strong><?= htmlspecialchars($expense['arrival_point'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $transportMethods = [
                                            'train' => ['icon' => 'train', 'label' => '電車'],
                                            'bus' => ['icon' => 'bus', 'label' => 'バス'],
                                            'taxi' => ['icon' => 'taxi', 'label' => 'タクシー', 'class' => 'bg-warning'],
                                            'gasoline' => ['icon' => 'car', 'label' => 'ガソリン'],
                                            'highway_toll' => ['icon' => 'car', 'label' => '有料道路'],
                                            'parking' => ['icon' => 'car', 'label' => '駐車料金'],
                                            'rental_car' => ['icon' => 'car', 'label' => 'レンタカー'],
                                            'airplane' => ['icon' => 'plane', 'label' => '航空機'],
                                            'other' => ['icon' => 'question', 'label' => 'その他']
                                        ];
                                        $method = $transportMethods[$expense['transport_type'] ?? 'other'] ?? $transportMethods['other'];
                                        $badgeClass = 'bg-info';
                                        
                                        // タクシーの場合は契約の許可状況に応じてスタイルを変更
                                        if ($expense['transport_type'] === 'taxi') {
                                            if (isset($expense['taxi_allowed']) && $expense['taxi_allowed'] == 1) {
                                                $badgeClass = 'bg-success';
                                            } else {
                                                $badgeClass = 'bg-danger';
                                            }
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <i class="fas fa-<?= $method['icon'] ?> me-1"></i>
                                            <?= $method['label'] ?>
                                        </span>
                                        
                                        <!-- タクシーで契約許可なしの場合の警告 -->
                                        <?php if ($expense['transport_type'] === 'taxi' && (!isset($expense['taxi_allowed']) || $expense['taxi_allowed'] != 1)): ?>
                                            <div class="mt-1">
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    契約で許可されていません
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-primary">¥<?= number_format($expense['amount'] ?? 0) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" 
                                                    onclick="showTravelExpenseDetail(<?= $expense['id'] ?>)" 
                                                    title="詳細">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-success" 
                                                    onclick="approveTravelExpense(<?= $expense['id'] ?>)" 
                                                    title="承認"
                                                    <?= ($expense['transport_type'] === 'taxi' && (!isset($expense['taxi_allowed']) || $expense['taxi_allowed'] != 1)) ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="タクシーは契約で許可されていません"' : '' ?>>
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-danger" 
                                                    onclick="showRejectTravelExpenseModal(<?= $expense['id'] ?>, '<?= htmlspecialchars($expense['doctor_name'], ENT_QUOTES, 'UTF-8') ?>')" 
                                                    title="差戻し">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 承認待ち交通費のページネーション -->
                <?php if (isset($pendingTravelExpensesPaginationInfo) && $pendingTravelExpensesPaginationInfo['total_pages'] > 1): ?>
                <div class="mt-3">
                    <nav aria-label="承認待ち交通費ページネーション">
                        <ul class="pagination justify-content-center">
                            <!-- 前のページ -->
                            <?php if ($pendingTravelExpensesPaginationInfo['has_previous']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_travel_expenses_page' => $pendingTravelExpensesPaginationInfo['current_page'] - 1])) ?>">
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
                            $startPage = max(1, $pendingTravelExpensesPaginationInfo['current_page'] - 2);
                            $endPage = min($pendingTravelExpensesPaginationInfo['total_pages'], $pendingTravelExpensesPaginationInfo['current_page'] + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['pending_travel_expenses_page' => 1])) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?= $i == $pendingTravelExpensesPaginationInfo['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_travel_expenses_page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php
                            endfor;
                            
                            if ($endPage < $pendingTravelExpensesPaginationInfo['total_pages']) {
                                if ($endPage < $pendingTravelExpensesPaginationInfo['total_pages'] - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['pending_travel_expenses_page' => $pendingTravelExpensesPaginationInfo['total_pages']])) . '">' . $pendingTravelExpensesPaginationInfo['total_pages'] . '</a></li>';
                            }
                            ?>
                            
                            <!-- 次のページ -->
                            <?php if ($pendingTravelExpensesPaginationInfo['has_next']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_travel_expenses_page' => $pendingTravelExpensesPaginationInfo['current_page'] + 1])) ?>">
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
                
                <!-- タクシー利用に関する注意事項 -->
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>タクシー利用について:</strong>
                        <span class="badge bg-success me-1">許可</span>契約でタクシー利用が認められている拠点
                        <span class="badge bg-danger me-1">不可</span>契約でタクシー利用が認められていない拠点
                        <br>
                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                        タクシー利用が許可されていない契約での申請は慎重に承認してください
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 管理メニュー（既存のまま） -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>管理メニュー</h5>
            </div>
            <div class="card-body">
                <div class="row mt-3">
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="<?= base_url('subsidiary_subjects') ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-list-alt fa-2x mb-2 d-block"></i>
                                補助科目設定
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-secondary btn-lg" onclick="showCsvExportModal()">
                                <i class="fas fa-file-csv fa-2x mb-2 d-block"></i>
                                請求CSV出力
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="<?= base_url('email-logs') ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-envelope-open-text fa-2x mb-2 d-block"></i>
                                メール送信ログ
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="<?= base_url('/invoice_settings') ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-cogs fa-2x mb-2 d-block"></i>
                                請求書設定
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 最近の役務記録（書面作成・遠隔相談対応版・ページネーション対応） -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>最近の役務記録
                    <!-- 役務記録の表示件数とページネーション情報 -->
                    <?php if ($recentServicesPaginationInfo['total_count'] > 0): ?>
                        <small class="text-muted">
                            （<?= $recentServicesPaginationInfo['start_record'] ?>-<?= $recentServicesPaginationInfo['end_record'] ?>件 / 
                            全<?= $recentServicesPaginationInfo['total_count'] ?>件）
                        </small>
                    <?php endif; ?>
                </h5>
                <div>
                    <span class="badge bg-info me-2"><?= count($recentServices ?? []) ?>件</span>
                    <a href="<?= base_url('service_records') ?>" class="btn btn-sm btn-outline-primary">すべて見る</a>
                </div>
            </div>

            <!-- ページネーション設定（最近の役務記録） -->
            <?php if (isset($recentServicesPaginationInfo) && $recentServicesPaginationInfo['total_count'] > 0): ?>
            <div class="card-body border-bottom bg-light">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <form method="GET" class="d-flex align-items-center">
                            <!-- 既存のGETパラメータを維持 -->
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if (!in_array($key, ['recent_services_page', 'recent_services_page_size'])): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <label for="recentServicesPageSize" class="form-label me-2 mb-0">表示件数:</label>
                            <select class="form-select form-select-sm" id="recentServicesPageSize" name="recent_services_page_size" onchange="this.form.submit()" style="width: auto;">
                                <option value="5" <?= (($_GET['recent_services_page_size'] ?? 15) == 5) ? 'selected' : '' ?>>5件</option>
                                <option value="10" <?= (($_GET['recent_services_page_size'] ?? 15) == 10) ? 'selected' : '' ?>>10件</option>
                                <option value="15" <?= (($_GET['recent_services_page_size'] ?? 15) == 15) ? 'selected' : '' ?>>15件</option>
                                <option value="20" <?= (($_GET['recent_services_page_size'] ?? 15) == 20) ? 'selected' : '' ?>>20件</option>
                                <option value="30" <?= (($_GET['recent_services_page_size'] ?? 15) == 30) ? 'selected' : '' ?>>30件</option>
                                <option value="50" <?= (($_GET['recent_services_page_size'] ?? 15) == 50) ? 'selected' : '' ?>>50件</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-8 text-end">
                        <small class="text-muted">
                            <?= $recentServicesPaginationInfo['start_record'] ?>-<?= $recentServicesPaginationInfo['end_record'] ?>件 / 
                            全<?= $recentServicesPaginationInfo['total_count'] ?>件
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card-body">
                <?php if (empty($recentServices)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">役務記録がありません。</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>役務日</th>
                                    <th>企業名</th>
                                    <th>拠点名</th>
                                    <th>産業医</th>
                                    <th>実施時間</th>
                                    <th>役務時間</th>
                                    <th>役務種別</th>
                                    <th class="text-center">交通費</th>
                                    <th>状態</th>
                                    <th>備考</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentServices as $record): ?>
                                    <?php
                                    // 役務種別の安全な取得
                                    $serviceType = $record['service_type'] ?? 'regular';
                                    $hasTimeRecord = in_array($serviceType, ['regular', 'emergency', 'extension', 'spot']);
                                    ?>
                                    <tr>
                                        <td>
                                            <?= format_date($record['service_date']) ?>
                                            <div class="small text-muted">
                                                <?= date('w', strtotime($record['service_date'])) == 0 ? '日' : 
                                                   (date('w', strtotime($record['service_date'])) == 6 ? '土' : '平日') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="fas fa-building text-muted me-1"></i>
                                            <?= htmlspecialchars($record['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                            <?= htmlspecialchars($record['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-md text-muted me-1"></i>
                                            <?= htmlspecialchars($record['doctor_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if ($hasTimeRecord && ($record['service_hours'] ?? 0) > 0): ?>
                                                    <!-- 時間分割対応の表示 -->
                                                    <?= format_service_time_display($record) ?>
                                                <?php elseif ($hasTimeRecord): ?>
                                                    <span class="text-warning">進行中</span>
                                                <?php else: ?>
                                                    <span class="text-muted">時間記録なし</span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($hasTimeRecord): ?>
                                                <?php if (($record['service_hours'] ?? 0) > 0): ?>
                                                    <span class="badge bg-info">
                                                        <?= format_service_hours($record['service_hours']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">進行中</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">時間なし</span>
                                            <?php endif; ?>
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
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($record['travel_expense_id'])): ?>
                                                <!-- 交通費登録済み -->
                                                <div>
                                                    <span class="badge bg-<?php 
                                                        switch($record['travel_expense_status']) {
                                                            case 'approved': echo 'success'; break;
                                                            case 'pending': echo 'warning'; break;
                                                            case 'rejected': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <i class="fas fa-car me-1"></i>
                                                        <?php 
                                                        switch($record['travel_expense_status']) {
                                                            case 'approved': echo '承認済'; break;
                                                            case 'pending': echo '承認待ち'; break;
                                                            case 'rejected': echo '差戻し'; break;
                                                            default: echo '不明';
                                                        }
                                                        ?>
                                                    </span>
                                                    <div class="small text-muted">
                                                        ¥<?= number_format($record['travel_expense_amount'] ?? 0) ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- 交通費未登録 -->
                                                <span class="text-muted">
                                                    <i class="fas fa-minus"></i>
                                                    <div class="small">未登録</div>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch($record['status']) {
                                                    case 'pending': echo 'warning'; break;
                                                    case 'approved': echo 'success'; break;
                                                    case 'rejected': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?= get_status_label($record['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($record['description'])): ?>
                                                <small title="<?= htmlspecialchars($record['description'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars(mb_substr($record['description'], 0, 20), ENT_QUOTES, 'UTF-8') ?>
                                                    <?= mb_strlen($record['description']) > 20 ? '...' : '' ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 最近の役務記録のページネーション -->
                    <?php if (isset($recentServicesPaginationInfo) && $recentServicesPaginationInfo['total_pages'] > 1): ?>
                    <div class="mt-3">
                        <nav aria-label="最近の役務記録ページネーション">
                            <ul class="pagination justify-content-center">
                                <!-- 前のページ -->
                                <?php if ($recentServicesPaginationInfo['has_previous']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['recent_services_page' => $recentServicesPaginationInfo['current_page'] - 1])) ?>">
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
                                $startPage = max(1, $recentServicesPaginationInfo['current_page'] - 2);
                                $endPage = min($recentServicesPaginationInfo['total_pages'], $recentServicesPaginationInfo['current_page'] + 2);
                                
                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['recent_services_page' => 1])) . '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= $i == $recentServicesPaginationInfo['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['recent_services_page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php
                                endfor;
                                
                                if ($endPage < $recentServicesPaginationInfo['total_pages']) {
                                    if ($endPage < $recentServicesPaginationInfo['total_pages'] - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['recent_services_page' => $recentServicesPaginationInfo['total_pages']])) . '">' . $recentServicesPaginationInfo['total_pages'] . '</a></li>';
                                }
                                ?>
                                
                                <!-- 次のページ -->
                                <?php if ($recentServicesPaginationInfo['has_next']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['recent_services_page' => $recentServicesPaginationInfo['current_page'] + 1])) ?>">
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
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 既存のモーダルはそのまま -->
<!-- 交通費詳細モーダル -->
<div class="modal fade" id="travelExpenseDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">交通費詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="travelExpenseDetailContent">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">読み込み中...</p>
                </div>
            </div>
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
                    <p>産業医: <span id="travelExpenseDoctorName"></span></p>
                    <div class="mb-3">
                        <label for="rejectTravelExpenseComment" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectTravelExpenseComment" name="comment" rows="3" required 
                                  placeholder="差戻す理由を入力してください"></textarea>
                        <small><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></small>
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

<!-- 交通費一括承認モーダル -->
<div class="modal fade" id="bulkApproveTravelExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-double me-2"></i>交通費の一括承認
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkApproveTravelExpenseForm" method="POST" action="<?= base_url('/travel_expenses/bulk-approve') ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        選択した <strong id="selectedTravelExpenseCount">0</strong> 件の交通費を一括承認します。
                    </div>
                    
                    <!-- タクシー利用に関する警告 -->
                    <div class="alert alert-warning" id="taxiWarningAlert" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        選択した交通費の中に、<strong>タクシー利用が契約で許可されていない</strong>申請が含まれています。
                        承認前に内容を確認してください。
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkApproveTravelExpenseComment" class="form-label">承認コメント（任意）</label>
                        <textarea class="form-control" id="bulkApproveTravelExpenseComment" name="comment" rows="3" 
                                  placeholder="承認に関するコメントがあれば入力してください（省略可）"></textarea>
                        <small><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></small>
                    </div>
                    
                    <!-- 選択された交通費一覧 -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">承認対象の交通費</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>産業医</th>
                                            <th>拠点</th>
                                            <th>役務日</th>
                                            <th>交通手段</th>
                                            <th class="text-end">金額</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedTravelExpensesList">
                                        <!-- JavaScript で動的に生成 -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>合計金額: ¥<span id="totalTravelExpenseAmount">0</span></strong>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <strong>件数: <span id="totalTravelExpenseCount">0</span>件</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmBulkApproveTravelExpense" required>
                            <label class="form-check-label" for="confirmBulkApproveTravelExpense">
                                上記の交通費をすべて承認することを確認しました
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>キャンセル
                    </button>
                    <button type="submit" class="btn btn-success" id="bulkApproveTravelExpenseSubmitBtn" disabled>
                        <i class="fas fa-check-double me-1"></i>
                        <span id="bulkApproveTravelExpenseCountBtn">0</span>件を一括承認
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* カードホバーエフェクト */
.card-hover {
    transition: all 0.3s ease;
    cursor: pointer;
}

.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

/* グラデーションカード */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

/* 一括承認関連のスタイル */
.form-check-input:indeterminate {
    background-color: #0d6efd;
    border-color: #0d6efd;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}

/* 統計セクションのスタイル */
.border-rounded {
    border-radius: 0.5rem;
}

/* タクシー関連のスタイル */
.badge.bg-danger {
    background-color: #dc3545 !important;
}

.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

/* ページネーション用スタイル */
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

/* レスポンシブ対応 */
@media (max-width: 992px) {
    .col-md-2 {
        flex: 0 0 auto;
        width: 50%;
        margin-bottom: 1rem;
    }
    
    .col-md-3 {
        flex: 0 0 auto;
        width: 50%;
        margin-bottom: 1rem;
    }
}

@media (max-width: 768px) {
    .col-md-2, .col-md-3 {
        flex: 0 0 auto;
        width: 100%;
        margin-bottom: 1rem;
    }
    
    /* 小画面では統計を縦並びにする */
    .row .col-md-3 .p-3 {
        text-align: center;
        margin-bottom: 1rem;
    }
    
    /* 表の列を簡略化 */
    .table th:nth-child(9),
    .table td:nth-child(9) {
        display: none;
    }
    
    .table th:nth-child(10),
    .table td:nth-child(10) {
        display: none;
    }
    
    .pagination .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        margin: 0 1px;
    }
    
    .form-select-sm {
        font-size: 0.8rem;
        min-width: 70px;
    }
}

@media (max-width: 576px) {
    /* 非常に小さい画面では更に簡略化 */
    .table th:nth-child(6),
    .table td:nth-child(6) {
        display: none;
    }
    
    .table th:nth-child(7),
    .table td:nth-child(7) {
        display: none;
    }
    
    /* カードの文字サイズを調整 */
    .card-body h6 {
        font-size: 0.8rem;
    }
    
    .card-body h3 {
        font-size: 1.5rem;
    }
    
    .pagination .page-link {
        padding: 0.2rem 0.4rem;
        font-size: 0.8rem;
    }
    
    .form-select-sm {
        font-size: 0.75rem;
        min-width: 60px;
    }
}

/* 役務種別バッジのカスタムスタイル */
.badge.bg-primary {
    background-color: #007bff !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
}

/* 統計エリアのハイライト */
.bg-light {
    background-color: #f8f9fa !important;
}

/* ボーダーの調整 */
.border {
    border: 1px solid #dee2e6 !important;
}

/* アニメーション効果 */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease-out;
}

/* 透明度の調整 */
.opacity-75 {
    opacity: 0.75 !important;
}

/* タクシー利用列の追加スタイル */
.table th:nth-child(4),
.table td:nth-child(4) {
    min-width: 80px;
    text-align: center;
}

/* 警告アイコンの色調整 */
.text-warning {
    color: #ffc107 !important;
}

.text-danger {
    color: #dc3545 !important;
}

/* ツールチップの調整 */
[data-bs-toggle="tooltip"] {
    cursor: help;
}
</style>

<script>
// 交通費一括承認関連の変数
let pendingTravelExpensesData = <?= json_encode($pendingTravelExpenses ?? [], JSON_UNESCAPED_UNICODE) ?>;

// 全選択・全解除（交通費）
function toggleAllTravelExpenses() {
    const selectAllCheckbox = document.getElementById('selectAllTravelExpenses');
    const expenseCheckboxes = document.querySelectorAll('.travel-expense-checkbox');
    
    expenseCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkApproveTravelExpenseButton();
}

// 一括承認ボタンの状態更新（交通費）
function updateBulkApproveTravelExpenseButton() {
    const selectedCheckboxes = document.querySelectorAll('.travel-expense-checkbox:checked');
    const bulkApproveBtn = document.getElementById('bulkApproveTravelExpenseBtn');
    
    if (bulkApproveBtn) {
        if (selectedCheckboxes.length > 0) {
            bulkApproveBtn.classList.remove('btn-success');
            bulkApproveBtn.classList.add('btn-warning');
            bulkApproveBtn.innerHTML = `<i class="fas fa-check-double me-1"></i>一括承認 (${selectedCheckboxes.length})`;
            bulkApproveBtn.disabled = false;
        } else {
            bulkApproveBtn.classList.remove('btn-warning');
            bulkApproveBtn.classList.add('btn-success');
            bulkApproveBtn.innerHTML = '<i class="fas fa-check-double me-1"></i>一括承認';
            bulkApproveBtn.disabled = true;
        }
    }
    
    // 全選択チェックボックスの状態更新
    const allCheckboxes = document.querySelectorAll('.travel-expense-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllTravelExpenses');
    
    if (selectAllCheckbox) {
        if (selectedCheckboxes.length === allCheckboxes.length && allCheckboxes.length > 0) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else if (selectedCheckboxes.length > 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }
}

// 交通費一括承認モーダルを表示
function showBulkApproveTravelExpenseModal() {
    const selectedCheckboxes = document.querySelectorAll('.travel-expense-checkbox:checked');
    
    if (selectedCheckboxes.length === 0) {
        alert('承認する交通費を選択してください。');
        return;
    }
    
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    const selectedExpenses = pendingTravelExpensesData.filter(expense => 
        selectedIds.includes(expense.id.toString())
    );
    
    // タクシー利用で許可されていない申請があるかチェック
    const unauthorizedTaxiExpenses = selectedExpenses.filter(expense => 
        expense.transport_type === 'taxi' && 
        (!expense.taxi_allowed || expense.taxi_allowed != 1)
    );
    
    // 警告アラートの表示制御
    const taxiWarningAlert = document.getElementById('taxiWarningAlert');
    if (unauthorizedTaxiExpenses.length > 0) {
        taxiWarningAlert.style.display = 'block';
    } else {
        taxiWarningAlert.style.display = 'none';
    }
    
    // 選択件数と金額を更新
    const selectedCount = selectedExpenses.length;
    const totalAmount = selectedExpenses.reduce((sum, expense) => 
        sum + (Number(expense.amount) || 0), 0
    );
    
    document.getElementById('selectedTravelExpenseCount').textContent = selectedCount;
    document.getElementById('bulkApproveTravelExpenseCountBtn').textContent = selectedCount;
    document.getElementById('totalTravelExpenseAmount').textContent = totalAmount.toLocaleString();
    document.getElementById('totalTravelExpenseCount').textContent = selectedCount;
    
    // 選択された交通費一覧を生成
    const expensesList = document.getElementById('selectedTravelExpensesList');
    expensesList.innerHTML = '';
    
    selectedExpenses.forEach(expense => {
        const transportMethods = {
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
        
        const row = document.createElement('tr');
        
        // タクシーで許可されていない場合の行のスタイリング
        if (expense.transport_type === 'taxi' && (!expense.taxi_allowed || expense.taxi_allowed != 1)) {
            row.classList.add('table-warning');
        }
        
        row.innerHTML = `
            <td>
                ${escapeHtml(expense.doctor_name || '不明')}
                ${expense.transport_type === 'taxi' && (!expense.taxi_allowed || expense.taxi_allowed != 1) ? 
                  '<br><small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>タクシー未許可</small>' : ''}
            </td>
            <td>${escapeHtml(expense.branch_name || '不明')}</td>
            <td>${formatDate(expense.service_date)}</td>
            <td>
                ${transportMethods[expense.transport_type] || 'その他'}
                ${expense.transport_type === 'taxi' && (!expense.taxi_allowed || expense.taxi_allowed != 1) ? 
                  ' <i class="fas fa-exclamation-triangle text-danger" title="契約で許可されていません"></i>' : ''}
            </td>
            <td class="text-end">¥${(expense.amount || 0).toLocaleString()}</td>
        `;
        expensesList.appendChild(row);
    });
    
    // フォームのhidden inputを更新（修正部分）
    const form = document.getElementById('bulkApproveTravelExpenseForm');
    
    // 既存のhidden inputを全て削除
    const existingHiddenInputs = form.querySelectorAll('input[name="expense_ids[]"]');
    existingHiddenInputs.forEach(input => input.remove());
    
    // 新しいhidden inputを追加
    selectedIds.forEach(id => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'expense_ids[]';
        hiddenInput.value = id;
        form.appendChild(hiddenInput);
    });
    
    console.log('Selected IDs:', selectedIds); // デバッグ用
    console.log('Form data check:', new FormData(form).getAll('expense_ids[]')); // デバッグ用
    
    // モーダルを表示
    const modal = new bootstrap.Modal(document.getElementById('bulkApproveTravelExpenseModal'));
    modal.show();
}

// 交通費詳細表示
function showTravelExpenseDetail(expenseId) {
    const modal = new bootstrap.Modal(document.getElementById('travelExpenseDetailModal'));
    const content = document.getElementById('travelExpenseDetailContent');
    
    content.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">読み込み中...</p>
        </div>
    `;
    
    modal.show();
    
    // APIから詳細を取得
    fetch(`<?= base_url('api/travel_expenses/') ?>${expenseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const expense = data.data;
                const transportMethods = {
                    'train': { icon: 'train', label: '電車' },
                    'bus': { icon: 'bus', label: 'バス' },
                    'taxi': { icon: 'taxi', label: 'タクシー' },
                    'gasoline': { icon: 'car', label: 'ガソリン' },
                    'highway_toll': { icon: 'car', label: '有料道路' },
                    'parking': { icon: 'car', label: '駐車料金' },
                    'rental_car': { icon: 'car', label: 'レンタカー' },
                    'airplane': { icon: 'plane', label: '航空機' },
                    'other': { icon: 'question', label: 'その他' }
                };
                const method = transportMethods[expense.transport_type || 'other'];
                
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>基本情報</h6>
                            <table class="table table-sm">
                                <tr><th>申請日</th><td>${formatDate(expense.created_at)}</td></tr>
                                <tr><th>産業医</th><td>${expense.doctor_name || '不明'}</td></tr>
                                <tr><th>拠点</th><td>${expense.branch_name || '不明'}</td></tr>
                                <tr><th>役務日</th><td>${formatDate(expense.service_date)}</td></tr>
                                <tr><th>交通手段</th><td>
                                    <span class="badge bg-info">
                                        <i class="fas fa-${method.icon} me-1"></i>
                                        ${method.label}
                                    </span>
                                    ${expense.transport_type === 'taxi' ? 
                                      (expense.taxi_allowed == 1 ? 
                                        '<br><small class="text-success"><i class="fas fa-check me-1"></i>タクシー利用許可済み</small>' : 
                                        '<br><small class="text-danger"><i class="fas fa-times me-1"></i>タクシー利用未許可</small>') : ''}
                                </td></tr>
                                <tr><th>金額</th><td><strong class="text-primary">¥${(expense.amount || 0).toLocaleString()}</strong></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>詳細情報</h6>
                            <table class="table table-sm">
                                <tr><th>出発地</th><td>${expense.departure_point || '-'}</td></tr>
                                <tr><th>到着地</th><td>${expense.arrival_point || '-'}</td></tr>
                                <tr><th>ステータス</th><td>
                                    <span class="badge bg-warning">承認待ち</span>
                                </td></tr>
                                <tr><th>申請時刻</th><td>${formatDateTime(expense.created_at)}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>備考</h6>
                            <div class="border rounded p-3 bg-light">
                                ${expense.memo || '<span class="text-muted">記載なし</span>'}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        詳細情報の取得に失敗しました。
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    エラーが発生しました。
                </div>
            `;
        });
}

// 交通費承認
function approveTravelExpense(expenseId) {
    if (confirm('この交通費を承認しますか？')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?= base_url('') ?>travel_expenses/${expenseId}/approve`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 交通費差戻しモーダル表示
function showRejectTravelExpenseModal(expenseId, doctorName) {
    document.getElementById('travelExpenseDoctorName').textContent = doctorName;
    document.getElementById('rejectTravelExpenseForm').action = `<?= base_url('') ?>travel_expenses/${expenseId}/reject`;
    document.getElementById('rejectTravelExpenseComment').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('rejectTravelExpenseModal'));
    modal.show();
}

// ユーティリティ関数
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('ja-JP') + ' ' + date.toLocaleTimeString('ja-JP', {hour: '2-digit', minute: '2-digit'});
}

// DOMContentLoaded イベントリスナー
document.addEventListener('DOMContentLoaded', function() {
    // ツールチップの初期化
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    const bulkApproveTravelExpenseForm = document.getElementById('bulkApproveTravelExpenseForm');
    if (bulkApproveTravelExpenseForm) {
        bulkApproveTravelExpenseForm.addEventListener('submit', function(e) {
            e.preventDefault(); // デフォルトの送信を停止
            
            const formData = new FormData(this);
            const expenseIds = formData.getAll('expense_ids[]');
            
            console.log('Form submission - expense IDs:', expenseIds); // デバッグ用
            
            if (expenseIds.length === 0) {
                alert('承認する交通費が選択されていません。');
                return false;
            }
            
            // 送信ボタンを無効化
            const submitBtn = document.getElementById('bulkApproveTravelExpenseSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>処理中...';
            }
            
            // フォームを手動で送信
            this.submit();
        });
    }
    
    // 確認チェックボックスの処理も追加
    const confirmTravelExpenseCheckbox = document.getElementById('confirmBulkApproveTravelExpense');
    if (confirmTravelExpenseCheckbox) {
        confirmTravelExpenseCheckbox.addEventListener('change', function() {
            const submitBtn = document.getElementById('bulkApproveTravelExpenseSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = !this.checked;
            }
        });
    }
    
    // 統計カードのアニメーション
    const cards = document.querySelectorAll('.card-hover');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // 初期状態で一括承認ボタンを無効化
    updateBulkApproveTravelExpenseButton();
    
    // 統計カードの数値アニメーション
    const numberElements = document.querySelectorAll('.card-body h3, .card-body h4');
    numberElements.forEach(el => {
        const finalValue = parseInt(el.textContent) || 0;
        if (finalValue > 0) {
            animateNumber(el, 0, finalValue, 1000);
        }
    });
});

// 数値アニメーション関数
function animateNumber(element, start, end, duration) {
    const range = end - start;
    const startTime = Date.now();
    
    function step() {
        const now = Date.now();
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(start + (range * progress));
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(step);
        }
    }
    
    requestAnimationFrame(step);
}
</script>
<!-- CSV出力用年月選択モーダル -->
<div class="modal fade" id="csvExportModal" tabindex="-1" aria-labelledby="csvExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csvExportModalLabel">
                    <i class="fas fa-file-csv me-2"></i>請求CSV出力
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <form id="csvExportForm" method="POST" action="<?= base_url('csv-export/download') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csvExportYearMonth" class="form-label">
                            対象年月を選択 <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="csvExportYearMonth" name="year_month" required>
                            <option value="">-- 年月を選択してください --</option>
                        </select>
                        <div class="form-text">
                            確定済みの締め処理がある年月のみ選択できます
                        </div>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>出力内容:</strong>
                        <ul class="mb-0 mt-2">
                            <li>選択した年月の全企業・産業医の締め処理明細</li>
                            <li>1明細 = CSV 1行（小計・合計行は除く）</li>
                            <li>UTF-8(BOM付き)形式で出力されます</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>キャンセル
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i>CSV出力
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * CSV出力モーダルを表示
 */
function showCsvExportModal() {
    // モーダルを表示
    const modal = new bootstrap.Modal(document.getElementById('csvExportModal'));
    modal.show();
    
    // 利用可能な年月リストを取得
    loadAvailableMonths();
}

/**
 * 利用可能な年月リストをAPIから取得
 */
function loadAvailableMonths() {
    const selectElement = document.getElementById('csvExportYearMonth');
    
    // ローディング表示
    selectElement.innerHTML = '<option value="">読み込み中...</option>';
    selectElement.disabled = true;
    
    fetch('<?= base_url('api/csv-export/available-months') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.months && data.months.length > 0) {
                // 年月リストをセレクトボックスに設定
                selectElement.innerHTML = '<option value="">-- 年月を選択してください --</option>';
                
                data.months.forEach(month => {
                    const option = document.createElement('option');
                    option.value = month;
                    option.textContent = formatYearMonth(month);
                    selectElement.appendChild(option);
                });
                
                selectElement.disabled = false;
            } else {
                // データがない場合
                selectElement.innerHTML = '<option value="">確定済みの締め処理データがありません</option>';
                selectElement.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading available months:', error);
            selectElement.innerHTML = '<option value="">エラー: 年月リストの取得に失敗しました</option>';
            selectElement.disabled = true;
            
            // エラーメッセージを表示
            alert('年月リストの取得に失敗しました。ページを再読み込みしてください。');
        });
}

/**
 * YYYY-MM形式の年月を「YYYY年MM月」形式に変換
 * 
 * @param {string} yearMonth - YYYY-MM形式の年月
 * @return {string} 「YYYY年MM月」形式の文字列
 */
function formatYearMonth(yearMonth) {
    const [year, month] = yearMonth.split('-');
    return `${year}年${month}月`;
}

/**
 * フォーム送信前のバリデーション
 */
document.getElementById('csvExportForm').addEventListener('submit', function(e) {
    const yearMonth = document.getElementById('csvExportYearMonth').value;
    
    if (!yearMonth) {
        e.preventDefault();
        alert('年月を選択してください');
        return false;
    }
    
    // 送信後はモーダルを閉じる(ダウンロードが始まる)
    const modal = bootstrap.Modal.getInstance(document.getElementById('csvExportModal'));
    if (modal) {
        modal.hide();
    }
});
</script>