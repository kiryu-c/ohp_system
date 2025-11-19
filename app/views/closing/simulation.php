<?php
// app/views/closing/simulation.php - 締め処理シミュレーション画面(ワークフロー対応)
$title = '月次締め処理シミュレーション';
$closingPeriodName = date('Y年m月', strtotime($closingPeriod . '-01'));
$userType = Session::get('user_type');

// ユーザータイプに応じた表示制御
$showBillingAmount = ($userType !== 'company');
$showTravelExpenseDetail = ($userType !== 'company');

// 確定可能かどうか
$canFinalize = false;
$canApprove = false;
$canReject = false;
$isApproved = false;
$isRejected = false;

// 請求方法選択可否
$canChooseBillingMethod = $simulation['can_choose_billing_method'] ?? false;
$regularBillingMethod = $simulation['regular_billing_method'] ?? 'contract_hours';
$actualRegularHours = $simulation['actual_regular_hours'] ?? 0;

if ($existingRecord) {
    $isApproved = ($existingRecord['company_approved'] == 1);
    $isRejected = !empty($existingRecord['company_rejected_at']);
    $doctorComment = $existingRecord['doctor_comment'] ?? '';
    $extensionReason = $existingRecord['extension_reason'] ?? '';
    
    if ($userType === 'doctor' || $userType === 'admin') {
        // 【修正】未承認の役務と交通費の両方をチェック
        $canFinalize = ($existingRecord['status'] !== 'finalized' && !$isApproved && 
                       $unapprovedRecordsCount == 0 && $unapprovedTravelExpensesCount == 0);
    } elseif ($userType === 'company') {
        $canApprove = ($existingRecord['status'] === 'finalized' && !$isApproved && !$isRejected);
        $canReject = $canApprove;
    }
} else {
    $extensionReason = '';
    $doctorComment = '';
    if ($userType === 'doctor' || $userType === 'admin') {
        // 【修正】未承認の役務と交通費の両方をチェック
        $canFinalize = ($unapprovedRecordsCount == 0 && $unapprovedTravelExpensesCount == 0);
    }
}

// 定期延長がある場合、デフォルトの延長理由を生成
$defaultExtensionReason = '';
if (($simulation['summary']['regular_extension_hours'] ?? 0) > 0 && empty($extensionReason)) {
    $reasonParts = [];
    foreach ($simulation['service_breakdown'] as $item) {
        $record = $item['service_record'];
        if ($record && $record['service_type'] === 'regular' && !empty($record['overtime_reason'])) {
            $date = date('n/j', strtotime($record['service_date']));
            $reasonParts[] = $date . ' ' . $record['overtime_reason'];
        }
    }
    $defaultExtensionReason = implode("\n", $reasonParts);
}

// 契約時間の表示形式を決定
$contractHoursDisplay = '';
if ($contract['visit_frequency'] === 'weekly') {
    $visitCount = $contract['calculated_visit_count'] ?? 0;
    $singleVisitHours = $contract['single_visit_hours'] ?? 0;
    $totalContractHours = $contract['total_contract_hours'] ?? 0;
    
    $contractHoursDisplay = number_format($totalContractHours, 1) . '時間(' . 
                           number_format($singleVisitHours, 1) . '時間×' . $visitCount . '回)';
} elseif ($contract['visit_frequency'] === 'spot') {
    // スポット契約の場合
    $contractHoursDisplay = 'スポット契約';
} else {
    $contractHoursDisplay = number_format($contract['regular_visit_hours'] ?? 0, 1) . '時間/月';
}

// スポット契約かどうかをチェック
$isSpotContract = ($contract['visit_frequency'] === 'spot');
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- ヘッダー情報 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-calculator me-2"></i>
                        月次締め処理
                        <?php if ($userType === 'company'): ?>
                            確認
                        <?php else: ?>
                            シミュレーション
                        <?php endif; ?>
                         - <?= h($closingPeriodName) ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="text-muted" style="width: 120px;">会社名:</th>
                                    <td><?= h($contract['company_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">支店名:</th>
                                    <td><?= h($contract['branch_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">月の訪問時間:</th>
                                    <td><?= $contractHoursDisplay ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="text-muted" style="width: 120px;">対象期間:</th>
                                    <td><?= h($closingPeriodName) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">現在の状況:</th>
                                    <td>
                                        <?php if ($isApproved): ?>
                                            <span class="badge bg-success">企業承認済み</span>
                                            <small class="text-muted ms-2">
                                                <?= date('Y/m/d H:i', strtotime($existingRecord['company_approved_at'])) ?>
                                            </small>
                                        <?php elseif ($isRejected): ?>
                                            <span class="badge bg-danger">差戻し</span>
                                            <small class="text-muted ms-2">
                                                <?= date('Y/m/d H:i', strtotime($existingRecord['company_rejected_at'])) ?>
                                            </small>
                                        <?php elseif ($existingRecord && $existingRecord['status'] === 'finalized'): ?>
                                            <span class="badge bg-warning text-dark">企業承認待ち</span>
                                            <small class="text-muted ms-2">
                                                <?= date('Y/m/d H:i', strtotime($existingRecord['finalized_at'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">未処理</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($showBillingAmount): ?>
                                <tr>
                                    <th class="text-muted">税種別:</th>
                                    <td>
                                        <?php if ($contract['tax_type'] === 'exclusive'): ?>
                                            <span class="badge bg-warning text-dark">外税</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-white">内税</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                    <!-- 【追加】契約情報変更の警告表示 -->
                    <?php 
                    // 確定済み(企業承認待ち含む)の場合に契約情報変更をチェック
                    if ($existingRecord && $existingRecord['status'] === 'finalized' && !empty($existingRecord['contract_snapshot'])) {
                        $snapshotContract = json_decode($existingRecord['contract_snapshot'], true);
                        
                        // 現在の契約情報を取得(比較用)
                        $db = Database::getInstance()->getConnection();
                        $currentContractSql = "SELECT * FROM contracts WHERE id = :contract_id";
                        $stmt = $db->prepare($currentContractSql);
                        $stmt->execute(['contract_id' => $contract['id']]);
                        $currentContract = $stmt->fetch();
                        
                        $hasChanges = false;
                        $changes = [];
                        
                        // 料金の変更チェック
                        if ($snapshotContract['regular_visit_rate'] != $currentContract['regular_visit_rate']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                '定期訪問料金 (%s円 → %s円)',
                                number_format($snapshotContract['regular_visit_rate']),
                                number_format($currentContract['regular_visit_rate'])
                            );
                        }
                        if ($snapshotContract['regular_extension_rate'] != $currentContract['regular_extension_rate']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                '定期延長料金 (%s円 → %s円)',
                                number_format($snapshotContract['regular_extension_rate']),
                                number_format($currentContract['regular_extension_rate'])
                            );
                        }
                        if ($snapshotContract['emergency_visit_rate'] != $currentContract['emergency_visit_rate']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                '臨時訪問料金 (%s円 → %s円)',
                                number_format($snapshotContract['emergency_visit_rate']),
                                number_format($currentContract['emergency_visit_rate'])
                            );
                        }
                        if ($snapshotContract['document_consultation_rate'] != $currentContract['document_consultation_rate']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                '書面作成・遠隔相談料金 (%s円 → %s円)',
                                number_format($snapshotContract['document_consultation_rate']),
                                number_format($currentContract['document_consultation_rate'])
                            );
                        }
                        if (isset($snapshotContract['spot_rate']) && isset($currentContract['spot_rate']) && 
                            $snapshotContract['spot_rate'] != $currentContract['spot_rate']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                'スポット料金 (%s円 → %s円)',
                                number_format($snapshotContract['spot_rate']),
                                number_format($currentContract['spot_rate'])
                            );
                        }
                        
                        // 訪問時間の変更チェック
                        if ($snapshotContract['regular_visit_hours'] != $currentContract['regular_visit_hours']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                '月の訪問時間 (%s時間 → %s時間)',
                                number_format($snapshotContract['regular_visit_hours'], 1),
                                number_format($currentContract['regular_visit_hours'], 1)
                            );
                        }
                        
                        // 訪問頻度の変更チェック
                        if ($snapshotContract['visit_frequency'] != $currentContract['visit_frequency']) {
                            $hasChanges = true;
                            $frequencyLabel = [
                                'monthly' => '毎月',
                                'bimonthly' => '隔月',
                                'weekly' => '毎週',
                                'spot' => 'スポット'
                            ];
                            $changes[] = sprintf(
                                '訪問頻度 (%s → %s)',
                                $frequencyLabel[$snapshotContract['visit_frequency']] ?? $snapshotContract['visit_frequency'],
                                $frequencyLabel[$currentContract['visit_frequency']] ?? $currentContract['visit_frequency']
                            );
                        }
                        
                        // 毎週契約の場合の詳細チェック
                        if ($currentContract['visit_frequency'] === 'weekly') {
                            // スナップショットの訪問回数情報と現在の計算結果を比較
                            if (isset($snapshotContract['calculated_visit_count']) && isset($contract['calculated_visit_count'])) {
                                if ($snapshotContract['calculated_visit_count'] != $contract['calculated_visit_count']) {
                                    $hasChanges = true;
                                    $changes[] = sprintf(
                                        '月間訪問回数 (%d回 → %d回)',
                                        $snapshotContract['calculated_visit_count'],
                                        $contract['calculated_visit_count']
                                    );
                                }
                            }
                            
                            if (isset($snapshotContract['single_visit_hours']) && isset($contract['single_visit_hours'])) {
                                if ($snapshotContract['single_visit_hours'] != $contract['single_visit_hours']) {
                                    $hasChanges = true;
                                    $changes[] = sprintf(
                                        '1回あたり訪問時間 (%s時間 → %s時間)',
                                        number_format($snapshotContract['single_visit_hours'], 1),
                                        number_format($contract['single_visit_hours'], 1)
                                    );
                                }
                            }
                            
                            if (isset($snapshotContract['total_contract_hours']) && isset($contract['total_contract_hours'])) {
                                if ($snapshotContract['total_contract_hours'] != $contract['total_contract_hours']) {
                                    $hasChanges = true;
                                    $changes[] = sprintf(
                                        '月の契約時間合計 (%s時間 → %s時間)',
                                        number_format($snapshotContract['total_contract_hours'], 1),
                                        number_format($contract['total_contract_hours'], 1)
                                    );
                                }
                            }
                        }
                        
                        // 祝日除外設定の変更チェック
                        if ($snapshotContract['exclude_holidays'] != $currentContract['exclude_holidays']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                '祝日の扱い (%s → %s)',
                                $snapshotContract['exclude_holidays'] ? '非訪問日' : '訪問可能',
                                $currentContract['exclude_holidays'] ? '非訪問日' : '訪問可能'
                            );
                        }
                        
                        // タクシー利用可否の変更チェック
                        if ($snapshotContract['taxi_allowed'] != $currentContract['taxi_allowed']) {
                            $hasChanges = true;
                            $changes[] = sprintf(
                                'タクシー利用 (%s → %s)',
                                $snapshotContract['taxi_allowed'] ? '可' : '不可',
                                $currentContract['taxi_allowed'] ? '可' : '不可'
                            );
                        }
                        
                        // 税種別の変更チェック
                        if ($snapshotContract['tax_type'] != $currentContract['tax_type']) {
                            $hasChanges = true;
                            $taxTypeLabel = [
                                'exclusive' => '外税',
                                'inclusive' => '内税'
                            ];
                            $changes[] = sprintf(
                                '税種別 (%s → %s)',
                                $taxTypeLabel[$snapshotContract['tax_type']] ?? $snapshotContract['tax_type'],
                                $taxTypeLabel[$currentContract['tax_type']] ?? $currentContract['tax_type']
                            );
                        }
                        
                        if ($hasChanges):
                            // 表示するアラートの種類を状態に応じて変更
                            $alertClass = $isApproved ? 'alert-warning' : 'alert-info';
                            $iconClass = $isApproved ? 'fa-exclamation-triangle' : 'fa-info-circle';
                    ?>
                    <div class="alert <?= $alertClass ?> mt-3">
                        <h6><i class="fas <?= $iconClass ?> me-2"></i>契約内容の変更について</h6>
                        <p class="mb-2">
                            <?php if ($isApproved): ?>
                                この締め処理確定後に契約内容が変更されています。<br>
                            <?php else: ?>
                                この締め処理確定後に契約内容が変更されています。<br>
                            <?php endif; ?>
                            <strong>表示されている金額・時間は確定時の契約内容に基づいて計算されています。</strong>
                        </p>
                        <div class="bg-light p-2 rounded">
                            <small><strong>変更された項目:</strong></small>
                            <ul class="mb-0 mt-1" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($changes as $change): ?>
                                    <li><small><?= h($change) ?></small></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php if (!$isApproved): ?>
                        <div class="mt-2 p-2 bg-warning bg-opacity-10 rounded">
                            <small class="text-dark">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <strong>企業承認前の重要な注意事項:</strong><br>
                                承認前に契約内容が変更されています。承認する前に、変更内容が適切かどうかご確認ください。
                            </small>
                        </div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                確定時の契約内容を確認したい場合は、管理者にお問い合わせください。
                            </small>
                        </div>
                    </div>
                    <?php
                        endif;
                    }
                    ?>

                    <!-- 【追加】未承認の役務・交通費警告 -->
                    <?php if (($unapprovedRecordsCount > 0 || $unapprovedTravelExpensesCount > 0) && ($userType === 'doctor' || $userType === 'admin')): ?>
                    <div class="alert alert-warning mt-3">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>締め処理を確定できません</h6>
                        <ul class="mb-0">
                            <?php if ($unapprovedRecordsCount > 0): ?>
                                <li>未承認の役務記録が<strong><?= $unapprovedRecordsCount ?>件</strong>あります</li>
                            <?php endif; ?>
                            <?php if ($unapprovedTravelExpensesCount > 0): ?>
                                <li>未承認の交通費が<strong><?= $unapprovedTravelExpensesCount ?>件</strong>あります</li>
                            <?php endif; ?>
                        </ul>
                        <p class="mb-0 mt-2">
                            <small>※ シミュレーションには未承認のデータも含まれていますが、締め処理の確定には全ての承認が必要です。</small>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 産業医コメント表示 -->
                    <?php if (!empty($doctorComment)): ?>
                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-comment me-2"></i>産業医からのコメント</h6>
                        <p class="mb-0"><?= nl2br(h($doctorComment)) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 差戻し理由の表示 -->
                    <?php if ($isRejected): ?>
                    <div class="alert alert-danger mt-3">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>差戻し理由</h6>
                        <p class="mb-0"><?= nl2br(h($existingRecord['company_rejection_reason'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($simulation['service_breakdown']) && empty($simulation['travel_expenses'])): ?>
                <!-- 締め対象データがない場合 -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>締め対象の役務記録はありません</h5>
                    <p class="mb-0">
                        <?= h($closingPeriodName) ?>に承認済みの役務記録はありませんが、
                        0円で締め処理を実行できます。
                    </p>
                </div>
                
                <!-- 0円での表示 -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>役務記録
                                </h5>
                            </div>
                            <div class="card-body text-center text-muted">
                                <p class="mb-0">対象となる役務記録はありません</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <!-- 役務サマリー -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tasks me-2"></i>役務サマリー
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <th>定期訪問</th>
                                        <td class="text-end">0時間</td>
                                    </tr>
                                    <tr>
                                        <th>定期延長</th>
                                        <td class="text-end">0時間</td>
                                    </tr>
                                    <tr>
                                        <th>臨時訪問</th>
                                        <td class="text-end">0時間</td>
                                    </tr>
                                    <?php if ($contract['use_remote_consultation'] == 1): ?>
                                    <tr>
                                        <th>遠隔相談</th>
                                        <td class="text-end">0回</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($contract['use_document_creation'] == 1): ?>
                                    <tr>
                                        <th>書面作成</th>
                                        <td class="text-end">0回</td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 請求サマリー -->
                        <?php if ($showBillingAmount): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-yen-sign me-2"></i>請求サマリー
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th>定期訪問</th>
                                        <td class="text-end">0円</td>
                                    </tr>
                                    <tr>
                                        <th>定期延長</th>
                                        <td class="text-end">0円</td>
                                    </tr>
                                    <tr>
                                        <th>臨時訪問</th>
                                        <td class="text-end">0円</td>
                                    </tr>
                                    <?php if ($contract['use_remote_consultation'] == 1): ?>
                                    <tr>
                                        <th>遠隔相談</th>
                                        <td class="text-end">0円</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($contract['use_document_creation'] == 1): ?>
                                    <tr>
                                        <th>書面作成</th>
                                        <td class="text-end">0円</td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>交通費</th>
                                        <td class="text-end">0円</td>
                                    </tr>
                                    <?php if ($contract['tax_type'] === 'exclusive'): ?>
                                        <!-- 外税の場合 -->
                                        <tr>
                                            <th>消費税 (10%)</th>
                                            <td class="text-end">0円</td>
                                        </tr>
                                        <tr class="table-primary">
                                            <th><strong>合計</strong></th>
                                            <td class="text-end">
                                                <strong class="fs-5">0円</strong>
                                            </td>
                                        </tr>
                                        <tr class="table-light">
                                            <td colspan="2" class="pt-2 pb-2">
                                                <div class="row">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted">10%対象</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong>0円</strong>
                                                    </div>
                                                </div>
                                                <div class="row mt-1">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted">消費税対象外</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong>0円</strong>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <!-- 内税の場合 -->
                                        <tr class="table-primary">
                                            <th><strong>合計</strong></th>
                                            <td class="text-end">
                                                <strong class="fs-5">0円</strong>
                                            </td>
                                        </tr>
                                        <tr class="table-light">
                                            <td colspan="2" class="pt-2 pb-2">
                                                <div class="row">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted">10%対象</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong>0円</strong>
                                                    </div>
                                                </div>
                                                <div class="row mt-1">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted">消費税対象外</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong>0円</strong>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 操作ボタン -->
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if ($canFinalize): ?>
                                    <button type="button" class="btn btn-success btn-lg" 
                                            onclick="finalizeClosing()" id="finalizeBtn">
                                        <i class="fas fa-check me-2"></i>0円で締め処理を確定する
                                    </button>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            役務記録がない月も締め処理が必要です
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        この期間は既に締め処理が確定されています
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="<?= base_url('closing') ?>?month=<?= h($closingPeriod) ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>一覧に戻る
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- シミュレーション結果 -->
                <div class="row">
                    <div class="col-lg-8">
                        <!-- 請求方法選択カード(定期訪問の実働時間が契約時間未満の場合のみ表示) -->
                        <?php if ($canChooseBillingMethod && $showBillingAmount && !$isApproved): ?>
                        <div class="card mb-3 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calculator me-2"></i>定期訪問の請求方法を選択してください
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">
                                    <strong>月の契約時間:</strong> <?= format_hours_minutes($simulation['contract_hours']) ?><br>
                                    <strong>定期訪問の実働時間:</strong> <?= format_hours_minutes($actualRegularHours) ?>
                                </p>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="billing_method_choice" 
                                        id="billingMethodContract" value="contract_hours" 
                                        <?= $regularBillingMethod === 'contract_hours' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="billingMethodContract">
                                        <strong>月の訪問時間で請求</strong> (<?= format_hours_minutes($simulation['contract_hours']) ?>)
                                        <small class="text-muted d-block">契約上の月の訪問時間で請求します</small>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="billing_method_choice" 
                                        id="billingMethodActual" value="actual_hours"
                                        <?= $regularBillingMethod === 'actual_hours' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="billingMethodActual">
                                        <strong>実働時間で請求</strong> (<?= format_hours_minutes($actualRegularHours) ?>)
                                        <small class="text-muted d-block">実際に稼働した時間で請求します</small>
                                    </label>
                                </div>
                                <button type="button" class="btn btn-primary mt-3" onclick="updateSimulation()">
                                    <i class="fas fa-sync me-2"></i>選択した方法でシミュレーション
                                </button>
                            </div>
                        </div>
                        <?php elseif ($existingRecord && $existingRecord['status'] === 'finalized' && $showBillingAmount): ?>
                        <!-- 確定後の請求方法表示 -->
                        <div class="card mb-3 border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-check-circle me-2"></i>選択された請求方法
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    <strong>請求方法:</strong> 
                                    <?php if ($regularBillingMethod === 'actual_hours'): ?>
                                        <span class="badge bg-primary">実働時間で請求</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">月の訪問時間で請求</span>
                                    <?php endif; ?>
                                </p>
                                <p class="mb-0">
                                    <small class="text-muted">
                                        月の契約時間: <?= format_hours_minutes($simulation['contract_hours']) ?> / 
                                        定期訪問実働時間: <?= format_hours_minutes($actualRegularHours) ?>
                                    </small>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 役務記録の明細 -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>役務記録
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>日付</th>
                                                <th>役務種別</th>
                                                <th>訪問種別</th>
                                                <th>状態</th>
                                                <th class="text-end">実働時間</th>
                                                <th class="text-center">詳細</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $allItems = [];
                                            
                                            foreach ($simulation['service_breakdown'] as $item) {
                                                $record = $item['service_record'];
                                                if ($record) {
                                                    $allItems[] = [
                                                        'type' => 'service',
                                                        'date' => $record['service_date'],
                                                        'data' => $item
                                                    ];
                                                }
                                            }
                                            
                                            // 企業ユーザーには交通費詳細を表示しない
                                            if ($showTravelExpenseDetail) {
                                                foreach ($simulation['travel_expenses'] as $expense) {
                                                    $allItems[] = [
                                                        'type' => 'travel',
                                                        'date' => $expense['service_date'],
                                                        'data' => $expense
                                                    ];
                                                }
                                            }
                                            
                                            usort($allItems, function($a, $b) {
                                                $dateCompare = strcmp($a['date'], $b['date']);
                                                if ($dateCompare !== 0) {
                                                    return $dateCompare;
                                                }
                                                if ($a['type'] === 'service' && $b['type'] === 'travel') {
                                                    return -1;
                                                }
                                                if ($a['type'] === 'travel' && $b['type'] === 'service') {
                                                    return 1;
                                                }
                                                return 0;
                                            });
                                            
                                            foreach ($allItems as $item):
                                                if ($item['type'] === 'service'):
                                                    $serviceItem = $item['data'];
                                                    $record = $serviceItem['service_record'];
                                            ?>
                                                    <tr>
                                                        <td><?= date('m/d', strtotime($record['service_date'])) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= getServiceTypeColor($record['service_type']) ?>">
                                                                <?= getServiceTypeLabel($record['service_type']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($record['visit_type'])): ?>
                                                                <?php if ($record['visit_type'] === 'visit'): ?>
                                                                    <span class="badge bg-primary">訪問</span>
                                                                <?php elseif ($record['visit_type'] === 'online'): ?>
                                                                    <span class="badge bg-info">オンライン</span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($record['status'] === 'pending'): ?>
                                                                <span class="badge bg-warning text-dark">未承認</span>
                                                            <?php elseif ($record['status'] === 'approved'): ?>
                                                                <span class="badge bg-success">承認済み</span>
                                                            <?php elseif ($record['status'] === 'finalized'): ?>
                                                                <span class="badge bg-info">確定済み</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <?php if (in_array($record['service_type'], ['document', 'remote_consultation', 'other'])): ?>
                                                                <span class="text-muted">-</span>
                                                            <?php else: ?>
                                                                <?= format_hours_minutes($serviceItem['original_hours']) ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="showServiceDetail(<?= $record['id'] ?>)">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php else: // 交通費
                                                    $expense = $item['data'];
                                                ?>
                                                    <tr class="table-light">
                                                        <td><?= date('m/d', strtotime($expense['service_date'])) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= getTravelTypeColor($expense['transport_type']) ?>">
                                                                <?= getTravelTypeLabel($expense['transport_type']) ?>
                                                            </span>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php if ($expense['departure_point']): ?>
                                                                <?= h($expense['departure_point']) ?> → <?= h($expense['arrival_point']) ?>
                                                                <?php endif; ?>
                                                                <?php if ($expense['trip_type'] === 'round_trip'): ?>
                                                                    (往復)
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="text-muted">-</span>
                                                        </td>
                                                        <td>
                                                            <?php if ($expense['status'] === 'pending'): ?>
                                                                <span class="badge bg-warning text-dark">未承認</span>
                                                            <?php elseif ($expense['status'] === 'approved'): ?>
                                                                <span class="badge bg-success">承認済み</span>
                                                            <?php elseif ($expense['status'] === 'finalized'): ?>
                                                                <span class="badge bg-info">確定済み</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <?php if ($showBillingAmount): ?>
                                                                <strong class="text-info"><?= number_format($expense['amount']) ?>円</strong>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="text-muted">-</span>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- 延長理由入力(定期延長がある場合のみ表示) -->
                        <?php if (($simulation['summary']['regular_extension_hours'] ?? 0) > 0): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>延長理由
                                    <?php if ($userType !== 'company'): ?>
                                        <span class="badge bg-warning text-dark ms-2">定期延長あり</span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (($userType === 'doctor' || $userType === 'admin') && !$isApproved): ?>
                                    <textarea class="form-control" id="extensionReasonInput" rows="3" 
                                              placeholder="定期訪問が延長となった理由を入力してください"
                                              <?= $existingRecord && $existingRecord['status'] === 'finalized' ? 'readonly' : '' ?>><?= h($extensionReason ?: $defaultExtensionReason) ?></textarea>
                                    <small class="text-muted">
                                        ※各役務記録に登録された延長理由がある場合、自動的に表示されます<br>
                                        ※この情報は企業ユーザーと管理者が閲覧できます
                                    </small>
                                <?php else: ?>
                                    <?php if (!empty($extensionReason)): ?>
                                        <div class="alert alert-info mb-0" style="white-space: pre-line;"><?= h($extensionReason) ?></div>
                                    <?php else: ?>
                                        <div class="text-muted">延長理由は記録されていません</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-4">
                        <!-- 役務サマリー -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tasks me-2"></i>役務サマリー
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <?php if ($isSpotContract): ?>
                                    <tr>
                                        <th>スポット</th>
                                        <td class="text-end"><?= format_hours_minutes($simulation['summary']['spot_hours']) ?></td>
                                    </tr>
                                    <?php else: ?>
                                    <tr>
                                        <th>定期訪問</th>
                                        <td class="text-end"><?= format_hours_minutes($simulation['summary']['regular_hours']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>定期延長</th>
                                        <td class="text-end"><?= format_hours_minutes($simulation['summary']['regular_extension_hours']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>臨時訪問</th>
                                        <td class="text-end"><?= format_hours_minutes($simulation['summary']['emergency_hours']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($contract['use_remote_consultation'] == 1): ?>
                                    <tr>
                                        <th>遠隔相談</th>
                                        <td class="text-end"><?= $simulation['summary']['remote_consultation_count'] ?>回</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($contract['use_document_creation'] == 1): ?>
                                    <tr>
                                        <th>書面作成</th>
                                        <td class="text-end"><?= $simulation['summary']['document_count'] ?>回</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (($simulation['summary']['other_count'] ?? 0) > 0): ?>
                                    <tr>
                                        <th>その他</th>
                                        <td class="text-end"><?= $simulation['summary']['other_count'] ?>件</td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 請求サマリー(企業ユーザーには非表示) -->
                        <?php if ($showBillingAmount): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-yen-sign me-2"></i>請求サマリー
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <?php if ($isSpotContract): ?>
                                    <tr>
                                        <th>スポット</th>
                                        <td class="text-end"><?= number_format($simulation['summary']['spot_amount']) ?>円</td>
                                    </tr>
                                    <?php else: ?>
                                    <tr>
                                        <th>定期訪問</th>
                                        <td class="text-end"><?= number_format($simulation['summary']['regular_amount']) ?>円</td>
                                    </tr>
                                    <tr>
                                        <th>定期延長</th>
                                        <td class="text-end"><?= number_format($simulation['summary']['regular_extension_amount']) ?>円</td>
                                    </tr>
                                    <tr>
                                        <th>臨時訪問</th>
                                        <td class="text-end"><?= number_format($simulation['summary']['emergency_amount']) ?>円</td>
                                    </tr>
                                    <?php if ($contract['use_remote_consultation'] == 1): ?>
                                    <tr>
                                        <th>遠隔相談</th>
                                        <td class="text-end"><?= number_format($simulation['summary']['remote_consultation_amount']) ?>円</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($contract['use_document_creation'] == 1): ?>
                                    <tr>
                                        <th>書面作成</th>
                                        <td class="text-end"><?= number_format($simulation['summary']['document_amount']) ?>円</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php 
                                    // その他役務を個別に表示
                                    if (!empty($simulation['service_breakdown'])) {
                                        foreach ($simulation['service_breakdown'] as $item) {
                                            if ($item['billing_service_type'] === 'other' && $item['billing_amount'] > 0) {
                                                $record = $item['service_record'];
                                                $date = date('m/d', strtotime($record['service_date']));
                                    ?>
                                    <tr>
                                        <th>その他 (<?= $date ?>)</th>
                                        <td class="text-end"><?= number_format($item['billing_amount']) ?>円</td>
                                    </tr>
                                    <?php 
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <th>交通費</th>
                                        <td class="text-end"><?= number_format($simulation['travel_expense_total']) ?>円</td>
                                    </tr>
                                    <?php if (!empty($simulation['exclusive_registration_fee']) && $simulation['exclusive_registration_fee'] > 0): ?>
                                    <tr>
                                        <th>専属産業医登録料</th>
                                        <td class="text-end"><?= number_format($simulation['exclusive_registration_fee']) ?>円</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($contract['tax_type'] === 'exclusive'): ?>
                                        <!-- 外税の場合 -->
                                        <tr>
                                            <th>消費税 (<?= ($simulation['tax_rate'] * 100) ?>%)</th>
                                            <td class="text-end"><?= number_format($simulation['tax_amount']) ?>円</td>
                                        </tr>
                                        <tr class="table-primary">
                                            <th><strong>合計</strong></th>
                                            <td class="text-end">
                                                <strong class="fs-5"><?= number_format($simulation['total_with_tax']) ?>円</strong>
                                            </td>
                                        </tr>
                                        <tr class="table-light">
                                            <td colspan="2" class="pt-2 pb-2">
                                                <div class="row">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted"><?= ($simulation['tax_rate'] * 100) ?>%対象</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong><?= number_format($simulation['taxable_amount_with_tax']) ?>円</strong>
                                                    </div>
                                                </div>
                                                <div class="row mt-1">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted">消費税対象外</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong><?= number_format($simulation['travel_expense_total']) ?>円</strong>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <!-- 内税の場合 -->
                                        <tr class="table-primary">
                                            <th><strong>合計</strong></th>
                                            <td class="text-end">
                                                <strong class="fs-5"><?= number_format($simulation['total_with_tax']) ?>円</strong>
                                            </td>
                                        </tr>
                                        <tr class="table-light">
                                            <td colspan="2" class="pt-2 pb-2">
                                                <div class="row">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted"><?= ($simulation['tax_rate'] * 100) ?>%対象</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong><?= number_format($simulation['taxable_amount']) ?>円</strong>
                                                    </div>
                                                </div>
                                                <div class="row mt-1">
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted">消費税対象外</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong><?= number_format($simulation['travel_expense_total']) ?>円</strong>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 交通費詳細 -->
                        <?php if (!empty($simulation['travel_expenses'])): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-car me-2"></i>交通費詳細
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>日付</th>
                                                <th>交通手段</th>
                                                <th>状態</th>
                                                <th class="text-end">金額</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($simulation['travel_expenses'] as $expense): ?>
                                                <tr>
                                                    <td><?= date('m/d', strtotime($expense['service_date'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= getTravelTypeColor($expense['transport_type']) ?>">
                                                            <?= getTravelTypeLabel($expense['transport_type']) ?>
                                                        </span>
                                                        <?php if ($expense['trip_type'] === 'round_trip'): ?>
                                                            <small class="text-muted">(往復)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($expense['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">未承認</span>
                                                        <?php elseif ($expense['status'] === 'approved'): ?>
                                                            <span class="badge bg-success">承認済み</span>
                                                        <?php elseif ($expense['status'] === 'finalized'): ?>
                                                            <span class="badge bg-info">確定済み</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end"><?= number_format($expense['amount']) ?>円</td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-active">
                                                <th colspan="3">交通費合計</th>
                                                <th class="text-end"><?= number_format($simulation['travel_expense_total']) ?>円</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; // showBillingAmount ?>
                    </div>
                </div>

                <!-- 産業医コメントと操作ボタン（フルワイド） -->
                <div class="row mt-3">
                    <div class="col-12">
                        <!-- 産業医コメント入力(産業医・管理者のみ、未確定時のみ) -->
                        <?php if (($userType === 'doctor' || $userType === 'admin') && !$isApproved): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-comment me-2"></i>産業医コメント
                                </h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" id="doctorCommentInput" rows="4" 
                                          placeholder="締め確定時に企業へ伝えたいことがあれば入力してください(任意)"
                                          <?= $existingRecord && $existingRecord['status'] === 'finalized' ? 'readonly' : '' ?>><?= h($doctorComment) ?></textarea>
                                <small class="text-muted">
                                    ※このコメントは企業と管理者が閲覧できます<br>
                                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                                </small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- 操作ボタン -->
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if (($unapprovedRecordsCount > 0 || $unapprovedTravelExpensesCount > 0) && ($userType === 'doctor' || $userType === 'admin')): ?>
                                    <div class="alert alert-danger">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>締め処理を確定できません</h6>
                                        <p class="mb-0">
                                            <?php if ($unapprovedRecordsCount > 0): ?>
                                                未承認の役務記録が<strong><?= $unapprovedRecordsCount ?>件</strong>あります。<br>
                                            <?php endif; ?>
                                            <?php if ($unapprovedTravelExpensesCount > 0): ?>
                                                未承認の交通費が<strong><?= $unapprovedTravelExpensesCount ?>件</strong>あります。<br>
                                            <?php endif; ?>
                                            全てを承認してから締め処理を実行してください。
                                        </p>
                                    </div>
                                <?php elseif ($canFinalize): ?>
                                    <!-- 産業医・管理者の確定ボタン -->
                                    <button type="button" class="btn btn-success btn-lg" 
                                            onclick="finalizeClosing()" id="finalizeBtn">
                                        <i class="fas fa-check me-2"></i>締め処理を確定する
                                    </button>
                                    <div class="mt-2">
                                        <small class="text-muted">確定後は企業の承認をお待ちください</small>
                                    </div>
                                <?php elseif ($canApprove && $canReject): ?>
                                    <!-- 企業ユーザーの承認/差戻しボタン -->
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-success btn-lg" 
                                                onclick="approveClosing()">
                                            <i class="fas fa-check me-2"></i>この締め処理を承認する
                                        </button>
                                        <button type="button" class="btn btn-danger btn-lg" 
                                                onclick="rejectClosing()">
                                            <i class="fas fa-times me-2"></i>差戻す
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">承認すると請求書が自動生成されます</small>
                                    </div>
                                <<?php elseif ($isApproved): ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        この締め処理は企業承認済みです
                                    </div>
                                    <?php if ($showBillingAmount && !empty($existingRecord['invoice_pdf_path'])): ?>
                                    <div class="mt-3">
                                        <a href="<?= base_url('invoice-download/' . $existingRecord['id']) ?>" 
                                           class="btn btn-success" target="_blank">
                                            <i class="fas fa-file-pdf me-2"></i>請求書PDFダウンロード
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- 管理者用: 企業承認取り消しボタン -->
                                    <?php if ($userType === 'admin'): ?>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-warning" onclick="revokeApproval()">
                                            <i class="fas fa-undo me-2"></i>企業承認を取り消す
                                        </button>
                                        <div class="mt-2">
                                            <small class="text-muted">※ 請求書PDFも削除されます</small>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <?php if ($userType === 'company'): ?>
                                            この期間の締め処理は確定待ちです
                                        <?php else: ?>
                                            この期間は締め処理が完了しています
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="<?= base_url('closing') ?>?month=<?= h($closingPeriod) ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>一覧に戻る
                                    </a>
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
</div>

<!-- 確定確認モーダル(産業医・管理者用) -->
<div class="modal fade" id="finalizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">締め処理の確定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    締め処理を確定すると、企業の承認待ち状態になります。<br>
                    企業が承認するまでは取消しが可能です。
                </div>
                <?php if ($canChooseBillingMethod): ?>
                <div class="alert alert-info">
                    <strong>選択された請求方法:</strong><br>
                    <span id="selectedBillingMethodText"></span>
                </div>
                <?php endif; ?>
                <p>請求金額: <strong><?= number_format($simulation['total_with_tax'] ?? 0) ?>円</strong></p>
                <p>本当に締め処理を確定しますか?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-success" id="confirmFinalizeBtn">
                    <i class="fas fa-check me-1"></i>確定する
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 承認確認モーダル(企業ユーザー用) -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">締め処理の承認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    承認すると請求書が自動生成され、締め処理の取り消しができなくなります。
                </div>
                <p>この締め処理を承認しますか?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-success" id="confirmApproveBtn">
                    <i class="fas fa-check me-1"></i>承認する
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 差戻しモーダル(企業ユーザー用) -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">締め処理の差戻し</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejectionReason" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectionReason" rows="4" 
                              placeholder="差戻しの理由を入力してください" required></textarea>
                </div>
                <p class="text-muted">差戻し後、産業医が修正して再度締め処理を行います。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">
                    <i class="fas fa-times me-1"></i>差戻す
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 企業承認取り消しモーダル(管理者用) -->
<div class="modal fade" id="revokeApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">企業承認の取り消し</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>警告:</strong> この操作は取り消せません。
                </div>
                <p>企業承認を取り消すと:</p>
                <ul>
                    <li>締め処理は「確定済み(企業承認待ち)」の状態に戻ります</li>
                    <li>請求書PDFは削除されます</li>
                    <li>企業ユーザーが再度承認できるようになります</li>
                </ul>
                <p class="mb-0"><strong>本当に企業承認を取り消しますか?</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-warning" id="confirmRevokeBtn">
                    <i class="fas fa-undo me-1"></i>取り消す
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSRFトークン用のフォーム -->
<form id="csrfForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="contract_id" value="<?= h($contract['id']) ?>">
    <input type="hidden" name="closing_period" value="<?= h($closingPeriod) ?>">
    <input type="hidden" name="regular_billing_method" id="regularBillingMethodInput" value="<?= h($regularBillingMethod) ?>">
    <input type="hidden" name="doctor_comment" id="doctorCommentHidden" value="">
    <input type="hidden" name="extension_reason" id="extensionReasonHidden" value="">
</form>

<script>
// 請求方法変更時のシミュレーション更新
function updateSimulation() {
    const selectedMethod = document.querySelector('input[name="billing_method_choice"]:checked').value;
    const url = new URL(window.location.href);
    url.searchParams.set('billing_method', selectedMethod);
    window.location.href = url.toString();
}

// 産業医・管理者の確定処理
function finalizeClosing() {
    const doctorComment = document.getElementById('doctorCommentInput')?.value || '';
    const extensionReason = document.getElementById('extensionReasonInput')?.value || '';
    const selectedMethod = document.querySelector('input[name="billing_method_choice"]:checked')?.value || 'contract_hours';
    
    <?php if ($canChooseBillingMethod): ?>
    // 時間を「時間分」形式に変換する関数
    function formatHoursMinutes(hours) {
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return h + '時間' + (m > 0 ? m + '分' : '');
    }
    
    const methodText = selectedMethod === 'actual_hours' ? 
        '実働時間で請求 (' + formatHoursMinutes(<?= $actualRegularHours ?>) + ')' : 
        '月の訪問時間で請求 (' + formatHoursMinutes(<?= $simulation['contract_hours'] ?>) + ')';
    document.getElementById('selectedBillingMethodText').textContent = methodText;
    <?php endif; ?>
    
    document.getElementById('regularBillingMethodInput').value = selectedMethod;
    document.getElementById('doctorCommentHidden').value = doctorComment;
    document.getElementById('extensionReasonHidden').value = extensionReason;
    
    const modal = new bootstrap.Modal(document.getElementById('finalizeModal'));
    modal.show();
}

document.getElementById('confirmFinalizeBtn').onclick = function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>処理中...';
    
    const formData = new FormData();
    formData.append('contract_id', document.querySelector('input[name="contract_id"]').value);
    formData.append('closing_period', document.querySelector('input[name="closing_period"]').value);
    formData.append('regular_billing_method', document.getElementById('regularBillingMethodInput').value);
    formData.append('extension_reason', document.getElementById('extensionReasonHidden').value);
    formData.append('doctor_comment', document.getElementById('doctorCommentHidden').value);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url("closing/finalize") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        window.location.href = '<?= base_url("closing") ?>';
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('finalizeModal'));
        modal.hide();
        
        showAlert('danger', '締め処理の確定に失敗しました: ' + error.message);
    });
};

// 企業ユーザーの承認処理
function approveClosing() {
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

document.getElementById('confirmApproveBtn').onclick = function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>処理中...';
    
    const formData = new FormData();
    formData.append('contract_id', document.querySelector('input[name="contract_id"]').value);
    formData.append('closing_period', document.querySelector('input[name="closing_period"]').value);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url("closing/companyApprove") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        window.location.href = '<?= base_url("closing") ?>';
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('approveModal'));
        modal.hide();
        
        showAlert('danger', '承認処理に失敗しました: ' + error.message);
    });
};

// 企業ユーザーの差戻し処理
function rejectClosing() {
    document.getElementById('rejectionReason').value = '';
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

document.getElementById('confirmRejectBtn').onclick = function() {
    const reason = document.getElementById('rejectionReason').value.trim();
    
    if (!reason) {
        showAlert('warning', '差戻し理由を入力してください');
        return;
    }
    
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>処理中...';
    
    const formData = new FormData();
    formData.append('contract_id', document.querySelector('input[name="contract_id"]').value);
    formData.append('closing_period', document.querySelector('input[name="closing_period"]').value);
    formData.append('rejection_reason', reason);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url("closing/companyReject") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        window.location.href = '<?= base_url("closing") ?>';
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
        modal.hide();
        
        showAlert('danger', '差戻し処理に失敗しました: ' + error.message);
    });
};

// 管理者による企業承認の取り消し処理
function revokeApproval() {
    const modal = new bootstrap.Modal(document.getElementById('revokeApprovalModal'));
    modal.show();
}

document.getElementById('confirmRevokeBtn').onclick = function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>処理中...';
    
    const formData = new FormData();
    formData.append('contract_id', document.querySelector('input[name="contract_id"]').value);
    formData.append('closing_period', document.querySelector('input[name="closing_period"]').value);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url("closing/revokeCompanyApproval") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        window.location.href = '<?= base_url("closing") ?>';
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('revokeApprovalModal'));
        modal.hide();
        
        showAlert('danger', '企業承認の取り消しに失敗しました: ' + error.message);
    });
};

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');
    
    if (successMsg) {
        showAlert('success', successMsg);
    }
    if (errorMsg) {
        showAlert('danger', errorMsg);
    }
});

// 請求方法に応じたURLパラメータ処理
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const billingMethod = urlParams.get('billing_method');
    
    if (billingMethod && document.querySelector('input[name="billing_method_choice"]')) {
        const radio = document.querySelector(`input[name="billing_method_choice"][value="${billingMethod}"]`);
        if (radio) {
            radio.checked = true;
        }
    }
});

// 役務記録の詳細を表示する関数
function showServiceDetail(serviceRecordId) {
    // ローディング表示
    const modalBody = document.getElementById('serviceDetailModalBody');
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">読み込み中...</span></div></div>';
    
    // モーダルを表示
    const modal = new bootstrap.Modal(document.getElementById('serviceDetailModal'));
    modal.show();
    
    // APIから役務記録の詳細を取得
    fetch(`<?= base_url('api/service_records/') ?>${serviceRecordId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // APIレスポンスの構造を確認
            let record = null;
            if (data.success && data.record) {
                record = data.record;
            } else if (data.data) {
                // 別の構造の場合
                record = data.data;
            } else if (data.id) {
                // データが直接返される場合
                record = data;
            } else {
                throw new Error('役務記録データが見つかりません');
            }
            
            if (!record) {
                throw new Error('役務記録が取得できませんでした');
            }
            
            // 役務種別ラベル
            const serviceTypeLabels = {
                'regular': '定期訪問',
                'emergency': '臨時訪問',
                'document': '書面作成',
                'remote_consultation': '遠隔相談',
                'spot': 'スポット',
                'other': 'その他'
            };
            
            // 役務種別の色
            const serviceTypeColors = {
                'regular': 'primary',
                'emergency': 'danger',
                'document': 'success',
                'remote_consultation': 'secondary',
                'spot': 'info',
                'other': 'dark'
            };
            
            // 状態ラベル
            let statusBadge = '';
            if (record.status === 'pending') {
                statusBadge = '<span class="badge bg-warning text-dark">未承認</span>';
            } else if (record.status === 'approved') {
                statusBadge = '<span class="badge bg-success">承認済み</span>';
            } else if (record.status === 'finalized') {
                statusBadge = '<span class="badge bg-info">確定済み</span>';
            } else {
                statusBadge = '<span class="badge bg-secondary">' + (record.status || '不明') + '</span>';
            }
            
            // 時間表示
            let timeDisplay = '';
            if (record.service_type === 'document' || record.service_type === 'remote_consultation' || record.service_type === 'other') {
                timeDisplay = '<span class="text-muted">-</span>';
            } else {
                const startTime = record.start_time ? record.start_time.substring(0, 5) : '';
                const endTime = record.end_time ? record.end_time.substring(0, 5) : '';
                if (startTime && endTime) {
                    timeDisplay = `${startTime} ~ ${endTime}`;
                } else {
                    timeDisplay = '<span class="text-muted">-</span>';
                }
            }
            
            // 役務内容のHTML生成
            let html = `
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 150px;">役務日</th>
                        <td>${record.service_date || '-'}</td>
                    </tr>
                    <tr>
                        <th>役務種別</th>
                        <td>
                            <span class="badge bg-${serviceTypeColors[record.service_type] || 'secondary'}">
                                ${serviceTypeLabels[record.service_type] || record.service_type || '-'}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>状態</th>
                        <td>${statusBadge}</td>
                    </tr>
            `;
            
            // 訪問種別の表示
            if (record.visit_type) {
                let visitTypeBadge = '';
                if (record.visit_type === 'visit') {
                    visitTypeBadge = '<span class="badge bg-primary">訪問</span>';
                } else if (record.visit_type === 'online') {
                    visitTypeBadge = '<span class="badge bg-info">オンライン</span>';
                } else {
                    visitTypeBadge = '<span class="text-muted">-</span>';
                }
                html += `
                    <tr>
                        <th>訪問種別</th>
                        <td>${visitTypeBadge}</td>
                    </tr>
                `;
            }
            
            html += `
                    <tr>
                        <th>時間</th>
                        <td>${timeDisplay}</td>
                    </tr>
            `;
            
            // その他役務の場合、請求金額を表示
            if (record.service_type === 'other' && record.direct_billing_amount) {
                html += `
                    <tr>
                        <th>請求金額</th>
                        <td><strong>${Number(record.direct_billing_amount).toLocaleString()}円</strong></td>
                    </tr>
                `;
            }
            
            // 役務内容（フィールド名の可能性を複数チェック）
            const description = record.description || record.service_description || record.content || '';
            if (description) {
                html += `
                    <tr>
                        <th>役務内容</th>
                        <td>${escapeHtml(description).replace(/\n/g, '<br>')}</td>
                    </tr>
                `;
            }
            
            // 延長理由（overtime_reasonがある場合は表示）
            const overtimeReason = record.overtime_reason || record.extension_reason || '';
            if (overtimeReason) {
                const isOvertime = record.is_overtime == 1 || record.is_overtime === true || record.is_overtime === '1';
                const bgClass = isOvertime ? 'bg-warning bg-opacity-10' : '';
                html += `
                    <tr>
                        <th>延長理由</th>
                        <td class="${bgClass}">${escapeHtml(overtimeReason).replace(/\n/g, '<br>')}</td>
                    </tr>
                `;
            }
            
            // 備考
            const notes = record.notes || record.note || record.remarks || '';
            if (notes) {
                html += `
                    <tr>
                        <th>備考</th>
                        <td>${escapeHtml(notes).replace(/\n/g, '<br>')}</td>
                    </tr>
                `;
            }
            
            html += '</table>';
            
            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = `<div class="alert alert-danger">
                <strong>エラーが発生しました:</strong><br>
                ${error.message}
            </div>`;
        });
}

// HTMLエスケープ関数
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

</script>

<style>
.table th {
    white-space: nowrap;
}

.badge {
    font-size: 0.75em;
}

.fs-5 {
    font-size: 1.25rem !important;
}

.table-light {
    background-color: #f8f9fa !important;
}
</style>

<?php
// ヘルパー関数
function getTravelTypeLabel($transportType) {
    $labels = [
        'train' => '電車',
        'bus' => 'バス', 
        'taxi' => 'タクシー',
        'gasoline' => 'ガソリン',
        'highway_toll' => '有料道路',
        'parking' => '駐車料金',
        'rental_car' => 'レンタカー',
        'airplane' => '航空機',
        'other' => 'その他'
    ];
    return $labels[$transportType] ?? $transportType;
}

function getTravelTypeColor($transportType) {
    $colors = [
        'train' => 'primary',
        'bus' => 'success',
        'taxi' => 'warning',
        'gasoline' => 'info',
        'highway_toll' => 'info',
        'parking' => 'info',
        'rental_car' => 'info',
        'airplane' => 'success',
        'other' => 'secondary'
    ];
    return $colors[$transportType] ?? 'secondary';
}

function getServiceTypeLabel($serviceType) {
    $labels = [
        'regular' => '定期訪問',
        'emergency' => '臨時訪問',
        'document' => '書面作成',
        'remote_consultation' => '遠隔相談',
        'spot' => 'スポット',
        'other' => 'その他'
    ];
    return $labels[$serviceType] ?? $serviceType;
}

function getServiceTypeColor($serviceType) {
    $colors = [
        'regular' => 'primary',
        'emergency' => 'danger',
        'document' => 'info',
        'remote_consultation' => 'success',
        'spot' => 'info',
        'other' => 'dark'
    ];
    return $colors[$serviceType] ?? 'secondary';
}
?>

<!-- 役務記録詳細モーダル -->
<div class="modal fade" id="serviceDetailModal" tabindex="-1" aria-labelledby="serviceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceDetailModalLabel">
                    <i class="fas fa-clipboard-list me-2"></i>役務記録詳細
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body" id="serviceDetailModalBody">
                <!-- JavaScriptで内容を動的に生成 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>