<?php
// app/views/service_records/edit.php - 1カラム対応版テンプレート機能

// 変数が設定されているか確認
if (!isset($record) || !isset($contracts) || !isset($contract)) {
    die('必要なデータが設定されていません。');
}

// 統計データの安全な初期化
if (!isset($monthlyStats)) {
    $monthlyStats = [
        'regular' => ['hours' => 0, 'count' => 0],
        'emergency' => ['hours' => 0, 'count' => 0],
        'spot' => ['hours' => 0, 'count' => 0],
        'document' => ['hours' => 0, 'count' => 0],
        'remote_consultation' => ['hours' => 0, 'count' => 0],
        'other' => ['hours' => 0, 'count' => 0]
    ];
} else {
    $defaultStats = [
        'regular' => ['hours' => 0, 'count' => 0],
        'emergency' => ['hours' => 0, 'count' => 0],
        'spot' => ['hours' => 0, 'count' => 0],
        'document' => ['hours' => 0, 'count' => 0],
        'remote_consultation' => ['hours' => 0, 'count' => 0],
        'other' => ['hours' => 0, 'count' => 0]
    ];
    $monthlyStats = array_merge($defaultStats, $monthlyStats);
}

if (!isset($adjustedStats)) {
    $adjustedStats = $monthlyStats;
} else {
    $defaultStats = [
        'regular' => ['hours' => 0, 'count' => 0],
        'emergency' => ['hours' => 0, 'count' => 0],
        'spot' => ['hours' => 0, 'count' => 0],
        'document' => ['hours' => 0, 'count' => 0],
        'remote_consultation' => ['hours' => 0, 'count' => 0],
        'other' => ['hours' => 0, 'count' => 0]
    ];
    $adjustedStats = array_merge($defaultStats, $adjustedStats);
}

// 定期訪問の使用状況（安全な配列アクセス）
$regularUsed = isset($adjustedStats['regular']['hours']) ? $adjustedStats['regular']['hours'] : 0;
$regularLimit = isset($contract['regular_visit_hours']) ? $contract['regular_visit_hours'] : 0;
$regularRemaining = max(0, $regularLimit - $regularUsed);

// 現在の年月を取得
$currentMonth = date('n', strtotime($record['service_date']));
$currentYear = date('Y', strtotime($record['service_date']));

// 毎週契約の場合の月間上限時間を計算
$displayRegularLimit = $regularLimit; // デフォルトは契約時間
$displayRegularRemaining = $regularRemaining; // デフォルトは従来の残り時間

if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
    // 毎週契約の場合、APIと同じロジックで月間上限を計算
    require_once __DIR__ . '/../../core/helpers.php';
    
    // 修正: 新しいhelpers関数を呼び出し
    $monthlyLimit = calculate_weekly_monthly_hours_with_contract_settings(
        $contract['id'], 
        $contract['regular_visit_hours'], 
        $currentYear, 
        $currentMonth, 
        $db ?? Database::getInstance()->getConnection()
    );
    
    if ($monthlyLimit > 0) {
        $displayRegularLimit = $monthlyLimit;
        $displayRegularRemaining = max(0, $monthlyLimit - $regularUsed);
    }
}

// 役務種別情報を設定（新しい種別を追加）
$serviceTypeInfo = [
    'regular' => ['icon' => 'calendar-check', 'class' => 'primary', 'label' => '定期訪問'],
    'emergency' => ['icon' => 'exclamation-triangle', 'class' => 'warning', 'label' => '臨時訪問'],
    'document' => ['icon' => 'file-alt', 'class' => 'success', 'label' => '書面作成'],
    'remote_consultation' => ['icon' => 'envelope', 'class' => 'secondary', 'label' => '遠隔相談'],
    'other' => ['icon' => 'plus', 'class' => 'dark', 'label' => 'その他']
];

// 現在の役務種別が時間入力を必要とするかチェック
$currentServiceType = $form_data['service_type'] ?? $record['service_type'] ?? 'regular';
$requiresTimeInput = in_array($currentServiceType, ['regular', 'emergency', 'spot']);

// 訪問種別が必要な役務種別かチェック
$requiresVisitType = in_array($currentServiceType, ['regular', 'emergency', 'spot']);

// 訪問種別の値を取得（既存データがない場合はデフォルト）
$currentVisitType = $form_data['visit_type'] ?? $record['visit_type'] ?? ($requiresVisitType ? 'visit' : null);

// 他の記録があるかどうかの判定（安全な配列アクセス）
$regularHours = isset($adjustedStats['regular']['hours']) ? $adjustedStats['regular']['hours'] : 0;
$emergencyHours = isset($adjustedStats['emergency']['hours']) ? $adjustedStats['emergency']['hours'] : 0;
$spotHours = isset($adjustedStats['spot']['hours']) ? $adjustedStats['spot']['hours'] : 0;
$documentHours = isset($adjustedStats['document']['hours']) ? $adjustedStats['document']['hours'] : 0;
$remoteConsultationHours = isset($adjustedStats['remote_consultation']['hours']) ? $adjustedStats['remote_consultation']['hours'] : 0;

$regularCount = isset($adjustedStats['regular']['count']) ? $adjustedStats['regular']['count'] : 0;
$emergencyCount = isset($adjustedStats['emergency']['count']) ? $adjustedStats['emergency']['count'] : 0;
$spotCount = isset($adjustedStats['spot']['count']) ? $adjustedStats['spot']['count'] : 0;
$documentCount = isset($adjustedStats['document']['count']) ? $adjustedStats['document']['count'] : 0;
$remoteConsultationCount = isset($adjustedStats['remote_consultation']['count']) ? $adjustedStats['remote_consultation']['count'] : 0;

// 全ての役務種別の件数で判定（時間ありなし問わず）
$totalRecordCount = $regularCount + $emergencyCount + $spotCount + $documentCount + $remoteConsultationCount;
$hasOtherRecords = $totalRecordCount > 0;

// 契約の役務種別の利用可否を取得
// コントローラーから渡された値がない場合のみデフォルト値を使用
if (!isset($useRemoteConsultation)) {
    $useRemoteConsultation = false;
}
if (!isset($useDocumentCreation)) {
    $useDocumentCreation = false;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>役務記録の編集
                        </h4>
                        <!-- 交通費登録ボタン（時間ありの役務のみ表示） -->
                        <?php if ($requiresTimeInput): ?>
                            <a href="<?= base_url("travel_expenses/create/{$record['id']}") ?>" class="btn btn-info btn-sm me-2">
                                <i class="fas fa-train me-1"></i>交通費登録
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('service_records') ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>一覧に戻る
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- 契約情報 -->
                    <div class="alert alert-info">
                        <h5 class="alert-heading mb-2">
                            <i class="fas fa-file-contract me-2"></i>契約情報
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>企業名：</strong>
                                    <i class="fas fa-building me-1 text-success"></i>
                                    <?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="mb-1">
                                    <strong>拠点：</strong>
                                    <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                    <?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <?php if (($contract['visit_frequency'] ?? 'monthly') !== 'spot'): ?>
                                <p class="mb-0"><strong>定期訪問時間：</strong><?= format_total_hours($displayRegularLimit) ?>/月</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div id="static-usage-info">
                                    <?php if (($contract['visit_frequency'] ?? 'monthly') === 'spot'): ?>
                                        <!-- スポット契約の場合 -->
                                        <p class="mb-1"><strong><?= $currentYear ?>年<?= $currentMonth ?>月の役務実績：</strong></p>
                                        <?php if ($hasOtherRecords): ?>
                                            <!-- 他の記録がある場合 -->
                                            <ul class="mb-2 small">
                                                <li>スポット: <?= format_total_hours($spotHours) ?> (<?= $spotCount ?>件)</li>
                                                <?php if($useDocumentCreation): ?>
                                                <li>書面作成: <?= $documentCount ?>件 (時間記録なし)</li>
                                                <?php endif; ?>
                                                <?php if ($useRemoteConsultation): ?>
                                                <li>遠隔相談: <?= $remoteConsultationCount ?>件 (時間記録なし)</li>
                                                <?php endif; ?>
                                            </ul>
                                            <p class="mb-1 small text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                上記は編集中の記録を除く実績です
                                            </p>
                                        <?php else: ?>
                                            <!-- 編集中の記録のみの場合 -->
                                            <div class="mb-2">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-edit me-1"></i>
                                                    編集中の記録のみ
                                                </span>
                                            </div>
                                            <ul class="mb-2 small">
                                                <li>編集中のスポット: 
                                                    <?php if ($requiresTimeInput): ?>
                                                        <?= format_total_hours($record['service_hours']) ?>
                                                        <?php if ($requiresVisitType && $currentVisitType): ?>
                                                            (<?= $currentVisitType === 'visit' ? '訪問' : 'オンライン' ?>)
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">時間記録なし</span>
                                                    <?php endif; ?>
                                                </li>
                                                <li>他の役務記録: なし</li>
                                            </ul>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- 通常契約の場合 -->
                                        <p class="mb-1"><strong><?= $currentYear ?>年<?= $currentMonth ?>月の役務実績：</strong></p>
                                        
                                        <?php if ($hasOtherRecords): ?>
                                            <!-- 他の記録がある場合：全ての役務種別を表示 -->
                                            <ul class="mb-2 small">
                                                <li>定期訪問: <?= format_total_hours($regularHours) ?> (<?= $regularCount ?>件)</li>
                                                <li>臨時訪問: <?= format_total_hours($emergencyHours) ?> (<?= $emergencyCount ?>件)</li>
                                                <?php if($useDocumentCreation): ?>
                                                <li>書面作成: <?= $documentCount ?>件 (時間記録なし)</li>
                                                <?php endif; ?>
                                                <?php if ($useRemoteConsultation): ?>
                                                <li>遠隔相談: <?= $remoteConsultationCount ?>件 (時間記録なし)</li>
                                                <?php endif; ?>
                                            </ul>
                                            <p class="mb-1 small text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                上記は編集中の記録を除く実績です
                                            </p>
                                        <?php else: ?>
                                            <!-- 編集中の記録のみの場合：分かりやすい表示 -->
                                            <div class="mb-2">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-edit me-1"></i>
                                                    編集中の記録のみ
                                                </span>
                                            </div>
                                            <ul class="mb-2 small">
                                                <li>編集中の<?= isset($serviceTypeInfo[$currentServiceType]['label']) ? $serviceTypeInfo[$currentServiceType]['label'] : '定期訪問' ?>: 
                                                    <?php if ($requiresTimeInput): ?>
                                                        <?= format_total_hours($record['service_hours']) ?>
                                                        <?php if ($requiresVisitType && $currentVisitType): ?>
                                                            (<?= $currentVisitType === 'visit' ? '訪問' : 'オンライン' ?>)
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">時間記録なし</span>
                                                    <?php endif; ?>
                                                </li>
                                                <li>他の役務記録: なし</li>
                                            </ul>
                                        <?php endif; ?>
                                        
                                        <p class="mb-0"><strong>定期訪問残り時間：</strong>
                                            <?php if ($displayRegularRemaining  > 0): ?>
                                                <?= format_total_hours($displayRegularRemaining ) ?>
                                            <?php else: ?>
                                                <span class="text-warning">0時間（上限到達）</span>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- 動的な月間使用状況表示がここに追加される -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- 編集フォーム -->
                    <form action="<?= base_url("service_records/{$record['id']}") ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contract_id" class="form-label">
                                        <i class="fas fa-file-contract me-1"></i>契約 <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="contract_id" name="contract_id" required>
                                        <?php foreach ($contracts as $c): ?>
                                            <option value="<?= $c['id'] ?>" 
                                                    data-regular-hours="<?= $c['regular_visit_hours'] ?>"
                                                    data-visit-frequency="<?= $c['visit_frequency'] ?? 'monthly' ?>"
                                                    data-bimonthly-type="<?= $c['bimonthly_type'] ?? '' ?>"
                                                    data-start-month="<?= date('n', strtotime($c['start_date'])) ?>"
                                                    data-start-year="<?= date('Y', strtotime($c['start_date'])) ?>"
                                                    data-company-name="<?= htmlspecialchars($c['company_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-branch-name="<?= htmlspecialchars($c['branch_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= ($form_data['contract_id'] ?? $record['contract_id']) == $c['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['company_name'], ENT_QUOTES, 'UTF-8') ?> - 
                                                <?= htmlspecialchars($c['branch_name'], ENT_QUOTES, 'UTF-8') ?> 
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">企業名 - 拠点名 (定期訪問時間) の形式で表示</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_date" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>役務日 <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="service_date" name="service_date" 
                                           value="<?= htmlspecialchars($form_data['service_date'] ?? $record['service_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 役務種別と訪問種別を1行に配置 -->
                        <div class="row">
                            <div class="col-md-6" id="service-type-group">
                                <div class="mb-3">
                                    <label for="service_type" class="form-label">
                                        <i class="fas fa-tags me-1"></i>役務種別 <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="service_type" name="service_type" required>
                                        <?php if (($contract['visit_frequency'] ?? 'monthly') === 'spot'): ?>
                                        <option value="spot" <?= $currentServiceType === 'spot' ? 'selected' : '' ?>>
                                            <i class="fas fa-clock me-1"></i>スポット
                                        </option>
                                        <option value="other" <?= $currentServiceType === 'other' ? 'selected' : '' ?>>
                                            <i class="fas fa-plus me-1"></i>その他
                                        </option>
                                        <?php else: ?>
                                        <option value="regular" <?= $currentServiceType === 'regular' ? 'selected' : '' ?>>
                                            <i class="fas fa-calendar-check me-1"></i>定期訪問
                                        </option>
                                        <option value="emergency" <?= $currentServiceType === 'emergency' ? 'selected' : '' ?>>
                                            <i class="fas fa-exclamation-triangle me-1"></i>臨時訪問
                                        </option>
                                        <?php if ($useDocumentCreation): ?>
                                        <option value="document" <?= $currentServiceType === 'document' ? 'selected' : '' ?>>
                                            <i class="fas fa-file-alt me-1"></i>書面作成
                                        </option>
                                        <?php endif; ?>
                                        <?php if ($useRemoteConsultation): ?>
                                        <option value="remote_consultation" <?= $currentServiceType === 'remote_consultation' ? 'selected' : '' ?>>
                                            <i class="fas fa-envelope me-1"></i>遠隔相談
                                        </option>
                                        <?php endif; ?>
                                        <option value="other" <?= $currentServiceType === 'other' ? 'selected' : '' ?>>
                                            <i class="fas fa-plus me-1"></i>その他
                                        </option>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text" id="service-type-help">
                                        <?php
                                        switch($currentServiceType) {
                                            case 'regular':
                                                echo '月間契約時間内での通常業務';
                                                break;
                                            case 'emergency':
                                                echo '緊急対応や特別要請による訪問';
                                                break;
                                            case 'spot':
                                                echo 'スポット契約による対応';
                                                break;
                                            case 'document':
                                                echo '意見書・報告書等の書面作成業務';
                                                break;
                                            case 'remote_consultation':
                                                echo 'メールによる相談対応';
                                                break;
                                            case 'other':
                                                echo 'その他の業務（請求金額を直接入力）';
                                                break;
                                            default:
                                                echo '月間契約時間内での通常業務';
                                        }
                                        ?>
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 訪問種別選択（定期訪問・臨時訪問・定期延長のみ） -->
                            <div class="col-md-6" id="visit-type-group" style="<?= ($requiresVisitType) ? '' : 'display: none;' ?>">
                                <div class="mb-3">
                                    <label for="visit_type" class="form-label">
                                        <i class="fas fa-route me-1"></i>訪問種別 <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="visit_type" name="visit_type">
                                        <option value="visit" <?= $currentVisitType === 'visit' ? 'selected' : '' ?>>
                                            <i class="fas fa-walking me-1"></i>訪問
                                        </option>
                                        <option value="online" <?= $currentVisitType === 'online' ? 'selected' : '' ?>>
                                            <i class="fas fa-laptop me-1"></i>オンライン
                                        </option>
                                    </select>
                                    <div class="form-text">役務の実施方法を選択してください</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 開始時刻と終了時刻を1行に配置（時間管理が必要な役務種別のみ表示） -->
                        <div class="row" id="time-input-row" style="<?= $requiresTimeInput ? '' : 'display: none;' ?>">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">
                                        <i class="fas fa-clock me-1"></i>開始時刻 <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" 
                                           value="<?= $requiresTimeInput ? htmlspecialchars($form_data['start_time'] ?? $record['start_time'], ENT_QUOTES, 'UTF-8') : '' ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">
                                        <i class="fas fa-clock me-1"></i>終了時刻 <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" 
                                           value="<?= $requiresTimeInput ? htmlspecialchars($form_data['end_time'] ?? $record['end_time'], ENT_QUOTES, 'UTF-8') : '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 予想役務時間表示（時間管理が必要な役務種別のみ） -->
                        <div class="row" id="hours-preview-group" style="<?= $requiresTimeInput ? '' : 'display: none;' ?>">
                            <div class="col-12">
                                <div class="alert alert-secondary" id="hours-preview">
                                    <i class="fas fa-hourglass-half me-2"></i>
                                    <strong>役務時間：</strong>
                                    <span id="calculated-hours">
                                        <?= $requiresTimeInput ? format_service_hours($record['service_hours']) : '0:00' ?>
                                    </span>
                                    <span id="overtime-info" class="ms-3"></span>
                                    <!-- 動的な警告がここに追加される -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- 時間なし役務の説明 -->
                        <div class="row" id="no-time-info" style="<?= !$requiresTimeInput ? '' : 'display: none;' ?>">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>時間記録なし：</strong>
                                    この役務種別では開始・終了時刻の記録は行いません。業務内容の詳細な記載をお願いします。
                                </div>
                            </div>
                        </div>
                        
                        <!-- 請求金額入力フィールド（その他のみ） -->
                        <div class="row" id="billing-amount-section" style="<?= $currentServiceType === 'other' ? '' : 'display: none;' ?>">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="direct_billing_amount" class="form-label">
                                        <i class="fas fa-yen-sign me-1"></i>請求金額 <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">¥</span>
                                        <input type="number" class="form-control" id="direct_billing_amount" name="direct_billing_amount" 
                                               value="<?= htmlspecialchars($form_data['direct_billing_amount'] ?? $record['direct_billing_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                               min="0" step="1" placeholder="0">
                                    </div>
                                    <div class="form-text">
                                        請求する金額を入力してください（円単位、税抜）
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- その他の説明 -->
                        <div class="row" id="other-notice" style="<?= $currentServiceType === 'other' ? '' : 'display: none;' ?>">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>その他：</strong>
                                    時間の入力は不要です。役務日、業務内容、および請求金額を入力してください。
                                </div>
                            </div>
                        </div>
                        
                        <!-- 時間重複チェック警告表示エリア -->
                        <div id="time-overlap-warning"></div>
                        
                        <!-- 役務内容入力セクション（テンプレート機能付き） -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="description" class="form-label mb-0">
                                    <i class="fas fa-comment-dots me-1"></i>業務内容
                                    <span id="description-required-mark" class="text-danger" style="<?= ($currentServiceType === 'spot' || !$requiresTimeInput) ? '' : 'display: none;' ?>">*</span>
                                </label>
                                <div class="btn-group" role="group" id="template-buttons">
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="showTemplateModal()">
                                        <i class="fas fa-list me-1"></i>テンプレート選択
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearDescription()">
                                        <i class="fas fa-eraser me-1"></i>クリア
                                    </button>
                                </div>
                            </div>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" placeholder="実施した業務内容を入力してください"><?= htmlspecialchars($form_data['description'] ?? $record['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text" id="description-help">
                                <?php if ($currentServiceType === 'spot'): ?>
                                    <strong>スポットの場合、業務内容の詳細な記載は必須です。</strong><br>
                                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                                <?php elseif ($requiresTimeInput): ?>
                                    実施した業務内容を記載してください（任意）。承認の際に参考となります。<br>
                                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                                <?php else: ?>
                                    <strong><?= $currentServiceType === 'other' ? 'その他' : '書面作成・遠隔相談' ?>の場合、業務内容の詳細な記載は必須です。</strong><br>
                                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                                <?php endif; ?>
                            </div>
                            <!-- テンプレート適用通知 -->
                            <div id="template-applied-notice" class="alert alert-success mt-2" style="display: none;">
                                <i class="fas fa-check-circle me-2"></i>
                                <span id="template-applied-text">テンプレートが適用されました</span>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="overtime-reason-group" style="<?= (!empty($form_data['overtime_reason']) || !empty($record['overtime_reason'])) ? '' : 'display: none;' ?>">
                            <label for="overtime_reason" class="form-label text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>延長理由 <span class="text-danger" id="overtime-reason-required" style="display: none;">*</span>
                            </label>
                            <textarea class="form-control" id="overtime_reason" name="overtime_reason" 
                                      rows="3" placeholder="時間延長や特別対応の理由を入力してください"><?= htmlspecialchars($form_data['overtime_reason'] ?? $record['overtime_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <small class="text-muted">定期訪問で月間定期時間を延長する場合は必須入力です。具体的な理由を記載してください（承認の際に参考となります）。<br>
                            <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></small>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= base_url('service_records') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>戻る
                            </a>
                            <button type="button" class="btn btn-primary" onclick="handleFormSubmission()">
                                <i class="fas fa-save me-1"></i>更新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 編集時の注意事項 -->
<div class="alert alert-info mt-3">
    <h6><i class="fas fa-info-circle me-2"></i>編集時の注意事項</h6>
    <ul class="mb-0">
        <li><strong>訪問種別:</strong> 定期訪問・臨時訪問・定期延長では「訪問」または「オンライン」を選択できます</li>
        <li><strong>リアルタイム判定:</strong> 現在の月間使用状況を取得して正確な延長判定を行います</li>
        <li><strong>延長理由:</strong> 定期訪問で月間定期時間を延長する場合は延長理由の入力が必須となります</li>
        <li><strong>承認プロセス:</strong> 編集された記録は再度承認対象となります</li>
    </ul>
</div>

<!-- テンプレート選択モーダル（1カラム対応版） -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">
                    <i class="fas fa-list me-2"></i>役務内容テンプレート選択
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- システムテンプレート -->
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-cog me-1"></i>システムテンプレート
                        </h6>
                        <div id="system-templates" class="template-list">
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-spinner fa-spin"></i> 読み込み中...
                            </div>
                        </div>
                    </div>
                    
                    <!-- 個人テンプレート -->
                    <div class="col-md-6">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-user me-1"></i>個人テンプレート
                        </h6>
                        <div id="personal-templates" class="template-list">
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-spinner fa-spin"></i> 読み込み中...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>キャンセル
                </button>
                <a href="<?= base_url('service_templates') ?>" class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-cog me-1"></i>テンプレート管理
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 役務内容テンプレート自動保存確認モーダル（1カラム対応版） -->
<div class="modal fade" id="autoSaveModal" tabindex="-1" aria-labelledby="autoSaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="autoSaveModalLabel">
                    <i class="fas fa-save me-2"></i>テンプレート保存
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>入力された内容をテンプレートとして保存しますか？</p>
                <div class="mb-3">
                    <label class="form-label">保存する内容のプレビュー</label>
                    <div class="form-control bg-light" id="template-preview" style="height: auto; min-height: 60px; padding: 10px; font-size: 0.9rem; color: #666;">
                        <!-- プレビューテキストがここに表示される -->
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>この内容が個人テンプレートとして保存され、次回から簡単に呼び出せるようになります。</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeAutoSaveModal()">
                    <i class="fas fa-times me-1"></i>保存しない
                </button>
                <button type="button" class="btn btn-primary" onclick="saveAsTemplate()">
                    <i class="fas fa-save me-1"></i>保存する
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* 月間使用状況表示のスタイル */
.monthly-usage-display {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
    margin-top: 1rem;
}

.monthly-usage-display .progress {
    background-color: #e9ecef;
}

/* 動的警告のスタイル */
#overtime-warning {
    border-left: 4px solid #ffc107;
    background-color: #fff8e1;
    margin-top: 0.5rem;
    padding: 0.75rem;
    border-radius: 0.375rem;
}

#usage-warning {
    border-left: 4px solid #17a2b8;
    background-color: #e1f5fe;
    margin-top: 0.5rem;
    padding: 0.75rem;
    border-radius: 0.375rem;
}

#time-validation-warning {
    border-left: 4px solid #dc3545;
    background-color: #f8d7da;
    margin-top: 0.5rem;
    padding: 0.75rem;
    border-radius: 0.375rem;
}

/* テンプレート関連のスタイル（1カラム対応版） */
.template-list {
    max-height: 400px;
    overflow-y: auto;
}

.template-item {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}

.template-item:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.template-item.selected {
    border-color: #0d6efd;
    background-color: #e7f3ff;
}

.template-preview {
    font-size: 0.85rem;
    color: #495057;
    line-height: 1.4;
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.template-meta {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.usage-badge {
    background-color: #e9ecef;
    color: #495057;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.7rem;
    font-weight: 500;
}

.form-control:focus, .form-select:focus {
    border-color: #7dd3fc;
    box-shadow: 0 0 0 0.2rem rgba(125, 211, 252, 0.25);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#template-preview {
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* セクションのアニメーション */
#visit-type-group, #time-input-row, #hours-preview-group, #no-time-info {
    transition: all 0.3s ease;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .monthly-usage-display .row {
        flex-direction: column;
    }
    
    .monthly-usage-display .col-md-6 {
        margin-bottom: 0.5rem;
    }
    
    .template-item {
        padding: 0.5rem;
    }
    
    .template-preview {
        -webkit-line-clamp: 2;
        max-height: 40px;
    }
    
    .modal-dialog.modal-lg {
        margin: 1rem;
    }
    
    .modal-body .col-md-6 {
        margin-bottom: 1rem;
    }
}

/* 締め処理警告のスタイル */
#closing-warning {
    border-left: 4px solid #dc3545;
    background-color: #f8d7da;
    margin-top: 0.5rem;
    padding: 0.75rem;
    border-radius: 0.375rem;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

/* 無効化されたボタンのスタイル */
button.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
function escapeHtml(text) {
    if (typeof text !== 'string') return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 契約情報表示を更新する共通関数
function updateContractDisplay(companyName, branchName, regularHours) {
    const selectedOption = document.getElementById('contract_id').options[document.getElementById('contract_id').selectedIndex];
    const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
    
    // 左側の列を更新
    const contractInfo = document.querySelector('.alert.alert-info .col-md-6:first-child');
    if (contractInfo) {
        let displayHours = regularHours;
        let timeLabel = '定期訪問時間';
        
        if (visitFrequency === 'weekly' && currentMonthlyUsage) {
            displayHours = currentMonthlyUsage.regular_limit;
            timeLabel = '定期訪問時間（今月上限）';
        }
        
        // 拠点名が正常に取得できた場合のみ拠点行を表示
        const branchDisplay = branchName && branchName !== '解析失敗' ? `
            <p class="mb-1">
                <strong>拠点：</strong>
                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                ${escapeHtml(branchName)}
            </p>
        ` : '';
        
        // スポット契約の場合は定期訪問時間を表示しない
        const timeDisplay = visitFrequency !== 'spot' ? `
            <p class="mb-0"><strong>${timeLabel}：</strong>${formatHours(displayHours)}/月</p>
        ` : '';
        
        // 初期表示と同じ形式で表示
        contractInfo.innerHTML = `
            <p class="mb-1">
                <strong>企業名：</strong>
                <i class="fas fa-building me-1 text-success"></i>
                ${escapeHtml(companyName)}
            </p>
            ${branchDisplay}
            ${timeDisplay}
        `;
    }
    
    // 右側の列を更新（訪問頻度に応じて）
    updateUsageInfoColumn(visitFrequency);
    
    // 契約情報更新後に残り時間も即座に更新
    setTimeout(() => {
        updateRemainingTimeDisplay();
    }, 100);
}

// 右側の列(使用状況)を更新する関数
function updateUsageInfoColumn(visitFrequency) {
    // 契約変更時も既存の表示を維持する
    // (PHPで生成された内容を保持し、不要な「読み込み中」表示を避ける)
    return;
}

// フォールバック: selectのoptionから情報を抽出
function updateContractInfoFromSelect() {
    const selectedOption = document.getElementById('contract_id').options[document.getElementById('contract_id').selectedIndex];
    
    if (!selectedOption) return;
    
    // data属性から直接取得（優先）
    const companyName = selectedOption.dataset.companyName;
    const branchName = selectedOption.dataset.branchName;
    const regularHours = parseFloat(selectedOption.dataset.regularHours || 0);
    
    if (companyName && branchName) {
        // data属性から取得できた場合はそれを使用
        updateContractDisplay(companyName, branchName, regularHours);
        return;
    }
    
    // フォールバック: 既存のテキスト解析処理
    const optionText = selectedOption.textContent.trim();
    
    const parenIndex = optionText.indexOf('(');
    if (parenIndex > 0) {
        const beforeParen = optionText.substring(0, parenIndex).trim();
        
        const separators = [' - ', '－', '−', '—', '–', '-'];
        
        for (const sep of separators) {
            const parts = beforeParen.split(sep);
            if (parts.length === 2) {
                const parsedCompanyName = parts[0].trim();
                const parsedBranchName = parts[1].trim();
                
                if (parsedCompanyName && parsedBranchName) {
                    updateContractDisplay(parsedCompanyName, parsedBranchName, regularHours);
                    return;
                }
            }
        }
        
        // 最後のスペース区切りで分割を試行
        const lastSpaceIndex = beforeParen.lastIndexOf(' ');
        if (lastSpaceIndex > 0) {
            const parsedCompanyName = beforeParen.substring(0, lastSpaceIndex).trim();
            const parsedBranchName = beforeParen.substring(lastSpaceIndex + 1).trim();
            
            if (parsedCompanyName && parsedBranchName) {
                updateContractDisplay(parsedCompanyName, parsedBranchName, regularHours);
                return;
            }
        }
    }
    
    // 完全にフォールバック: 全体を企業名として扱う
    updateContractDisplay(optionText, '解析失敗', regularHours);
}

/**
 * 契約セレクトボックスのオプションを更新
 */
function updateContractSelectOptions(contracts, previousContractId) {
    const contractSelect = document.getElementById('contract_id');
    contractSelect.innerHTML = '';
    
    if (contracts.length === 0) {
        const noContractOption = document.createElement('option');
        noContractOption.value = '';
        noContractOption.textContent = '有効な契約がありません';
        contractSelect.appendChild(noContractOption);
        
        showMessage('選択した役務日に有効な契約が見つかりませんでした。別の日付を選択してください。', 'warning');
        return;
    }
    
    // 契約オプションを追加
    let foundPreviousContract = false;
    let firstValidContractId = null;
    
    contracts.forEach((contract, index) => {
        if (index === 0) {
            firstValidContractId = contract.id;
        }
        
        const option = document.createElement('option');
        option.value = contract.id;
        option.textContent = `${contract.company_name} - ${contract.branch_name}`;
        
        // data属性を設定
        option.dataset.regularHours = contract.regular_visit_hours || 0;
        option.dataset.visitFrequency = contract.visit_frequency || 'monthly';
        option.dataset.bimonthlyType = contract.bimonthly_type || '';
        option.dataset.startMonth = new Date(contract.start_date).getMonth() + 1;
        option.dataset.startYear = new Date(contract.start_date).getFullYear();
        option.dataset.companyName = contract.company_name;
        option.dataset.branchName = contract.branch_name;
        
        // 以前選択していた契約が有効リストに含まれる場合は選択状態にする
        if (contract.id == previousContractId) {
            option.selected = true;
            foundPreviousContract = true;
        }
        
        contractSelect.appendChild(option);
    });
    
    // 以前の契約が見つからなかった場合の処理
    if (!foundPreviousContract) {
        if (firstValidContractId) {
            // 最初の契約を自動選択
            contractSelect.value = firstValidContractId;
            
            // 契約が変更されたことを通知
            showMessage('役務日の変更により、有効な契約が自動選択されました。', 'info');
        }
        
        // 契約情報を更新
        updateContractInfoFromSelect();
        
        // 月間使用状況も更新
        loadMonthlyUsage(contractSelect.value);
        
        // 締めチェック
        const serviceDate = document.getElementById('service_date').value;
        checkClosingStatus(contractSelect.value, serviceDate);
    }
    
    // 契約リスト更新後に隔月チェックを実行
    const serviceDate = document.getElementById('service_date').value;
    const serviceType = document.getElementById('service_type').value;
    const contractId = contractSelect.value;
    
    if (serviceType === 'regular' && serviceDate && contractId) {
        const isValid = checkBimonthlyVisitValidity(contractId, serviceDate);
        
        if (!isValid) {
            showBimonthlyWarning();
            document.getElementById('service_type').value = 'emergency';
            
            // updateServiceTypeInfoを呼ばずに、必要なUI更新のみ実行
            const helpText = document.getElementById('service-type-help');
            if (helpText) {
                helpText.textContent = serviceTypeHelp['emergency'];
            }
            
            toggleTimeFields();
            toggleVisitTypeField();
            updateDescriptionRequirement();
            updateTravelExpenseButton();
            calculateHours();
        } else {
            hideBimonthlyWarning();
        }
    }
}

/**
 * 役務日に基づいて契約リストを動的に更新
 */
function updateContractListByServiceDate(serviceDate) {
    const contractSelect = document.getElementById('contract_id');
    const currentContractId = contractSelect.value; // 現在選択されている契約ID
    
    if (!serviceDate) {
        return;
    }
    
    // ローディング表示
    contractSelect.disabled = true;
    const loadingOption = document.createElement('option');
    loadingOption.textContent = '読み込み中...';
    contractSelect.innerHTML = '';
    contractSelect.appendChild(loadingOption);
    
    // APIで役務日に有効な契約を取得
    const formData = new FormData();
    formData.append('service_date', serviceDate);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url('api/service_records/contracts-by-date') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.contracts) {
            updateContractSelectOptions(data.contracts, currentContractId);
        } else {
            showContractListError('契約の取得に失敗しました');
        }
    })
    .catch(error => {
        console.error('Contract list update error:', error);
        showContractListError('契約リストの更新中にエラーが発生しました');
    })
    .finally(() => {
        contractSelect.disabled = false;
    });
}

/**
 * 契約リスト更新エラー時の処理
 */
function showContractListError(message) {
    const contractSelect = document.getElementById('contract_id');
    contractSelect.innerHTML = '';
    
    const errorOption = document.createElement('option');
    errorOption.value = '';
    errorOption.textContent = message;
    contractSelect.appendChild(errorOption);
    
    showMessage(message, 'error');
}

// グローバル関数を最初に定義（HTMLのonclick属性から呼び出すため）
function handleFormSubmission() {
    // 基本バリデーション
    if (!validateFormInternal()) {
        return false;
    }
    
    // 直接フォーム送信を試す
    const form = document.querySelector('form');
    if (form) {
        form.submit();
    } else {
        alert('フォームが見つかりません。ページを再読み込みしてください。');
    }
    
    return false;
}

// その他のグローバル関数も最初に定義
function validateForm() {
    return validateFormInternal();
}

function validateAndCheckAutoSave(event) {
    // まず従来のバリデーションを実行
    if (!validateForm()) {
        return false;
    }
    
    const description = document.getElementById('description').value.trim();
    
    // 役務内容が入力されている場合、テンプレート保存チェック
    if (description && description.length >= 1) {
        if (event && event.preventDefault) {
            event.preventDefault();
        }
        checkAutoSaveBeforeSubmit(description);
        return false;
    } else {
        return true;
    }
}

// 契約の定期訪問時間と現在の記録情報
const originalRecord = {
    id: <?= $record['id'] ?>,
    contract_id: <?= $record['contract_id'] ?>,
    service_date: '<?= $record['service_date'] ?>',
    service_hours: <?= $record['service_hours'] ?>,
    service_type: '<?= isset($record['service_type']) ? $record['service_type'] : 'regular' ?>',
    visit_type: '<?= isset($currentVisitType) ? $currentVisitType : 'visit' ?>'
};

// 契約リストをJavaScriptで利用可能にする
const contractsData = <?= json_encode($contracts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// 契約IDをキーとしたマップを作成
const contractsMap = {};
contractsData.forEach(contract => {
    contractsMap[contract.id] = contract;
});

// 現在の月間使用状況を保存する変数（編集中の記録を除く）
let currentMonthlyUsage = null;

// テンプレート関連のグローバル変数
let isSubmittingForm = false;
let templates = { system: [], personal: [] };
let selectedTemplate = null;

// DOM要素のグローバル変数（累積時間チェックで使用）
let contractSelect, serviceDateInput, overtimeReasonGroup, overtimeInfo, hoursPreview;

// 役務種別の説明（新しい種別を追加）
const serviceTypeHelp = {
    'regular': '月間契約時間内での通常業務',
    'emergency': '緊急対応や特別要請による訪問',
    'spot': 'スポット契約による対応',
    'document': '意見書・報告書等の書面作成業務',
    'remote_consultation': 'メールによる相談対応',
    'other': 'その他の業務（請求金額を直接入力）'
};

// 時間入力が必要な役務種別
const timeRequiredTypes = ['regular', 'emergency', 'spot'];

// 訪問種別が必要な役務種別
const visitTypeRequiredTypes = ['regular', 'emergency', 'spot'];
const serviceTypeLabels = {
    'regular': '定期訪問',
    'emergency': '臨時訪問',
    'spot': 'スポット',
    'document': '書面作成',
    'remote_consultation': '遠隔相談',
    'other': 'その他'
};

// 時間フォーマット関数（グローバル）
function formatHours(hours) {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    return h + ':' + (m < 10 ? '0' : '') + m;
}

/**
 * 契約に基づいて役務種別の選択肢を更新
 * @param {number} contractId - 契約ID
 */
function updateServiceTypeOptions(contractId) {
    const serviceTypeSelect = document.getElementById('service_type');
    if (!serviceTypeSelect) return;
    
    const contract = contractsMap[contractId];
    if (!contract) {
        console.warn('Contract not found:', contractId);
        return;
    }
    
    // 契約の設定を取得
    const useRemoteConsultation = contract.use_remote_consultation == 1;
    const useDocumentCreation = contract.use_document_creation == 1;
    const visitFrequency = contract.visit_frequency || 'monthly';
    
    // 現在選択されている役務種別を保存
    const currentValue = serviceTypeSelect.value;
    
    // 選択肢をクリア
    serviceTypeSelect.innerHTML = '';
    
    // スポット契約の場合
    if (visitFrequency === 'spot') {
        const option = document.createElement('option');
        option.value = 'spot';
        option.textContent = 'スポット';
        serviceTypeSelect.appendChild(option);
        if (currentValue !== 'other') {
            serviceTypeSelect.value = 'spot';
        }

        // その他の選択肢(常に表示)
        const otherOption = document.createElement('option');
        otherOption.value = 'other';
        otherOption.textContent = 'その他';
        serviceTypeSelect.appendChild(otherOption);
        if (currentValue === 'other') {
            serviceTypeSelect.value = 'other';
        }
        
        // スポット契約に変更された場合は通知
        if (currentValue !== 'spot' && currentValue !== 'other') {
            alert('スポット契約に変更されたため、役務種別がスポットに変更されました。');
        }
    } else {
        // スポット契約以外の場合
        // 常に表示される選択肢
        const alwaysOptions = [
            { value: 'regular', label: '定期訪問', icon: 'calendar-check' },
            { value: 'emergency', label: '臨時訪問', icon: 'exclamation-triangle' }
        ];
        
        alwaysOptions.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            serviceTypeSelect.appendChild(option);
        });
        
        // 書面作成の選択肢(契約設定で利用可の場合のみ)
        if (useDocumentCreation) {
            const option = document.createElement('option');
            option.value = 'document';
            option.textContent = '書面作成';
            serviceTypeSelect.appendChild(option);
        }
        
        // 遠隔相談の選択肢(契約設定で利用可の場合のみ)
        if (useRemoteConsultation) {
            const option = document.createElement('option');
            option.value = 'remote_consultation';
            option.textContent = '遠隔相談';
            serviceTypeSelect.appendChild(option);
        }
        
        // その他の選択肢(常に表示)
        const otherOption = document.createElement('option');
        otherOption.value = 'other';
        otherOption.textContent = 'その他';
        serviceTypeSelect.appendChild(otherOption);
        
        // 以前選択されていた値が新しい選択肢に存在する場合は復元
        const availableValues = Array.from(serviceTypeSelect.options).map(opt => opt.value);
        if (availableValues.includes(currentValue)) {
            serviceTypeSelect.value = currentValue;
        } else {
            // 選択されていた役務種別が利用不可になった場合は定期訪問に変更
            serviceTypeSelect.value = 'regular';
            
            // ユーザーに通知
            if (currentValue === 'spot') {
                alert('通常契約に変更されたため、役務種別が定期訪問に変更されました。');
            } else if (currentValue === 'remote_consultation' && !useRemoteConsultation) {
                alert('選択した契約では遠隔相談が利用できないため、定期訪問に変更されました。');
            } else if (currentValue === 'document' && !useDocumentCreation) {
                alert('選択した契約では書面作成が利用できないため、定期訪問に変更されました。');
            }
        }
    }
    
    // UIを更新
    updateServiceTypeInfo();
    toggleTimeFields();
    toggleVisitTypeField();
    updateDescriptionRequirement();
    updateTravelExpenseButton();
}

document.addEventListener('DOMContentLoaded', function() {
    // グローバル変数に代入
    contractSelect = document.getElementById('contract_id');
    serviceDateInput = document.getElementById('service_date');
    overtimeReasonGroup = document.getElementById('overtime-reason-group');
    overtimeInfo = document.getElementById('overtime-info');
    hoursPreview = document.getElementById('hours-preview');
    
    // ローカル変数
    const serviceTypeSelect = document.getElementById('service_type');
    const visitTypeSelect = document.getElementById('visit_type');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const descriptionInput = document.getElementById('description');
    const currentServiceType = '<?= $currentServiceType ?>';

    // 初期読み込み時に月間使用状況を取得
    loadMonthlyUsage(originalRecord.contract_id);
    
    // 契約変更時の処理
    contractSelect.addEventListener('change', function() {
        const newContractId = this.value;
        
        // 役務種別の選択肢を更新（契約設定に基づく）
        updateServiceTypeOptions(newContractId);
        
        // 常に契約情報を更新
        updateContractInfoFromSelect();
        
        // 月間使用状況も常に更新
        loadMonthlyUsage(newContractId);
        
        // 締めチェックを追加
        const serviceDate = document.getElementById('service_date').value;
        checkClosingStatus(newContractId, serviceDate);

        // 隔月契約チェック
        const serviceType = document.getElementById('service_type').value;
        
        if (serviceType === 'regular' && serviceDate && !checkBimonthlyVisitValidity(newContractId, serviceDate)) {
            showBimonthlyWarning();
            document.getElementById('service_type').value = 'emergency';
            updateServiceTypeInfo();
        } else {
            hideBimonthlyWarning();
        }
    });
    
    // 初期表示時に締めチェック
    checkClosingStatus(originalRecord.contract_id, originalRecord.service_date);

    // 役務種別変更時の処理
    serviceTypeSelect.addEventListener('change', function() {
        updateServiceTypeInfo();
        toggleTimeFields();
        toggleVisitTypeField();
        updateDescriptionRequirement();
        updateTravelExpenseButton();
    });

    // 訪問種別変更時のイベントリスナーを追加
    visitTypeSelect.addEventListener('change', function() {
        updateTravelExpenseButton();
    });

    // 時間・日付変更時の処理
    startTimeInput.addEventListener('change', function() {
        checkTimeValidation();
        calculateHours();
    });
    endTimeInput.addEventListener('change', function() {
        checkTimeValidation();
        calculateHours();
    });

    serviceDateInput.addEventListener('change', function() {
        const newServiceDate = this.value;
    
        if (!newServiceDate) {
            return;
        }
        
        // 契約リストを更新（この中で隔月チェックも実行される）
        updateContractListByServiceDate(newServiceDate);

        // 既存の処理
        const currentDate = new Date(this.value);
        const originalDate = new Date(originalRecord.service_date);
        
        if (currentDate.getFullYear() !== originalDate.getFullYear() || 
            currentDate.getMonth() !== originalDate.getMonth()) {
            loadMonthlyUsage(contractSelect.value);
        }
        // 月が変わっていない場合のみ直接calculateHoursを呼ぶ
        // 月が変わった場合はloadMonthlyUsage内でcalculateHoursが呼ばれる
        if (!(currentDate.getFullYear() !== originalDate.getFullYear() || 
              currentDate.getMonth() !== originalDate.getMonth())) {
            calculateHours();
        }

        // 時間重複チェックを追加
        checkTimeValidation();

        // 締めチェックを追加
        const contractId = document.getElementById('contract_id').value;
        checkClosingStatus(contractId, this.value);
    });
    
    // 初期表示
    updateServiceTypeOptions(originalRecord.contract_id); // 追加: 初期表示時に役務種別を更新
    updateServiceTypeInfo();
    toggleTimeFields();
    toggleVisitTypeField();
    updateDescriptionRequirement();
    calculateHours();
    updateTravelExpenseButton();
});

// 締め処理チェック関数(作成画面と同様)
function checkClosingStatus(contractId, serviceDate) {
    if (!contractId || !serviceDate) {
        hideClosingWarning();
        return;
    }
    
    const formData = new FormData();
    formData.append('contract_id', contractId);
    formData.append('service_date', serviceDate);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url('api/contracts/check-closing-status') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // レスポンスのステータスをチェック
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        // まずテキストとして取得
        return response.text();
    })
    .then(text => {
        // 空のレスポンスをチェック
        if (!text || text.trim() === '') {
            console.warn('Empty response from check-closing-status API');
            hideClosingWarning();
            return;
        }
        
        // JSONパースを試みる
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('Invalid JSON response:', text);
            console.error('Parse error:', parseError);
            hideClosingWarning();
            return;
        }
        
        // データの処理
        if (data.success && data.is_closed) {
            showClosingWarning(data.closing_period, data.finalized_at, data.finalized_by_name);
        } else {
            hideClosingWarning();
        }
    })
    .catch(error => {
        console.error('Closing status check error:', error);
        hideClosingWarning();
    });
}

// 締め処理警告表示
function showClosingWarning(closingPeriod, finalizedAt, finalizedBy) {
    hideClosingWarning();
    
    const [year, month] = closingPeriod.split('-');
    const formattedDate = finalizedAt ? new Date(finalizedAt).toLocaleString('ja-JP') : '不明';
    
    const warningDiv = document.createElement('div');
    warningDiv.id = 'closing-warning';
    warningDiv.className = 'alert alert-danger mt-3';
    warningDiv.style.cssText = 'border: 2px solid #dc3545 !important;';
    warningDiv.innerHTML = `
        <i class="fas fa-lock me-2"></i>
        <strong>警告:</strong> ${year}年${month}月は既に締め処理が完了しています。<br>
        <small class="text-muted">
            締め確定日時: ${formattedDate}${finalizedBy ? ' (確定者: ' + finalizedBy + ')' : ''}<br>
            この期間の役務記録は編集できません。別の日付を選択してください。
        </small>
    `;
    
    const serviceDateInput = document.getElementById('service_date');
    const insertLocation = serviceDateInput.closest('.mb-3');
    
    if (insertLocation && insertLocation.parentNode) {
        if (insertLocation.nextSibling) {
            insertLocation.parentNode.insertBefore(warningDiv, insertLocation.nextSibling);
        } else {
            insertLocation.parentNode.appendChild(warningDiv);
        }
    }
    
    // 更新ボタンを無効化
    disableSubmitButton(true, '締め処理済み');
}

// 締め処理警告を非表示
function hideClosingWarning() {
    const warningDiv = document.getElementById('closing-warning');
    if (warningDiv) {
        warningDiv.remove();
    }
    
    // 更新ボタンを有効化(他のエラーがない場合)
    const timeOverlapWarning = document.getElementById('time-overlap-warning');
    if (!timeOverlapWarning || !timeOverlapWarning.innerHTML.trim()) {
        disableSubmitButton(false);
    }
}

// 新規追加：静的な契約情報表示を更新する関数
function updateStaticContractInfo() {
    const selectedOption = document.getElementById('contract_id').options[document.getElementById('contract_id').selectedIndex];
    
    if (!selectedOption.value) return;
    
    const regularHours = parseFloat(selectedOption.dataset.regularHours || 0);
    const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
    
    // スポット契約の場合は何もしない（PHPで生成された表示をそのまま使用）
    if (visitFrequency === 'spot') {
        return;
    }
    
    // 毎週契約の場合は月間上限時間を再計算
    if (visitFrequency === 'weekly' && currentMonthlyUsage) {
        // APIで取得した実際の月間上限時間を使用
        const monthlyLimit = currentMonthlyUsage.regular_limit;
        const remainingHours = Math.max(0, monthlyLimit - currentMonthlyUsage.current_used);
        
        updateStaticContractDisplay(monthlyLimit, remainingHours, true);
    } else {
        // 毎月・隔月契約は従来通り
        const remainingHours = currentMonthlyUsage ? 
            Math.max(0, regularHours - currentMonthlyUsage.current_used) : regularHours;
        
        updateStaticContractDisplay(regularHours, remainingHours, false);
    }
}

// 新規追加：静的表示部分のDOM更新
function updateStaticContractDisplay(limitHours, remainingHours, isWeekly) {
    const staticUsageInfo = document.getElementById('static-usage-info');
    if (!staticUsageInfo) return;
    
    // 定期訪問時間の表示を更新
    const regularLimitText = staticUsageInfo.querySelector('p:first-child strong');
    if (regularLimitText && regularLimitText.nextSibling) {
        const limitLabel = isWeekly ? '定期訪問時間（今月上限）' : '定期訪問時間';
        regularLimitText.textContent = `${limitLabel}：`;
        // テキストノードを更新
        regularLimitText.parentNode.childNodes.forEach(node => {
            if (node.nodeType === Node.TEXT_NODE && node.textContent.includes('/月')) {
                node.textContent = `${formatHours(limitHours)}/月`;
            }
        });
    }
    
    // 残り時間の表示を更新
    const remainingTimeElement = staticUsageInfo.querySelector('p:last-child strong');
    if (remainingTimeElement) {
        const remainingContent = remainingHours > 0 ? 
            formatHours(remainingHours) : 
            '<span class="text-warning">0時間（上限到達）</span>';
        
        const remainingParagraph = remainingTimeElement.parentNode;
        remainingParagraph.innerHTML = `<strong>定期訪問残り時間：</strong>${remainingContent}`;
    }
}

// 月間使用状況の取得（編集中の記録を除く）- 毎週契約対応版
function loadMonthlyUsage(contractId) {
    const serviceDate = document.getElementById('service_date').value || originalRecord.service_date;
    const year = new Date(serviceDate).getFullYear();
    const month = new Date(serviceDate).getMonth() + 1;
    
    // 編集中の記録を除いた月間使用状況を取得
    fetch(`/op_website/public/api/contracts/${contractId}/monthly-usage?year=${year}&month=${month}&exclude_record_id=${originalRecord.id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentMonthlyUsage = {
                regular_limit: data.regular_limit,
                current_used: data.regular_hours || 0,
                remaining_hours: Math.max(0, data.regular_limit - (data.regular_hours || 0)),
                usage_percentage: data.regular_limit > 0 ? ((data.regular_hours || 0) / data.regular_limit) * 100 : 0,
                visit_frequency: data.contract_info?.visit_frequency || 'monthly',
                spot_hours: data.spot_hours || 0,
                spot_count: data.spot_count || 0,
                // 各役務種別の件数を追加
                regular_count: data.regular_count || 0,
                emergency_hours: data.emergency_hours || 0,
                emergency_count: data.emergency_count || 0,
                document_count: data.document_count || 0,
                remote_consultation_count: data.remote_consultation_count || 0
            };
            
            updateMonthlyUsageDisplay();
            
            // 新規追加：契約情報表示も更新
            updateContractInfoAfterUsageLoad();
            
            calculateHours();
        } else {
            handleMonthlyUsageError();
        }
    })
    .catch(error => {
        console.error('Monthly usage load error:', error);
        handleMonthlyUsageError();
    });
}

// エラー時のフォールバック処理
function handleMonthlyUsageError() {
    const selectedOption = document.getElementById('contract_id').options[document.getElementById('contract_id').selectedIndex];
    const regularLimit = parseFloat(selectedOption.dataset.regularHours || 0);
    
    currentMonthlyUsage = {
        regular_limit: regularLimit,
        current_used: 0,
        remaining_hours: regularLimit,
        usage_percentage: 0,
        visit_frequency: selectedOption.dataset.visitFrequency || 'monthly'
    };
    updateMonthlyUsageDisplay();
    
    // エラー時も残り時間を更新
    updateRemainingTimeDisplay();
    updateStaticContractInfo();
}

// 月間使用状況取得後の契約情報更新
function updateContractInfoAfterUsageLoad() {
    const selectedOption = document.getElementById('contract_id').options[document.getElementById('contract_id').selectedIndex];
    if (!selectedOption) return;
    
    // 契約情報(企業名・拠点名・定期訪問時間)を更新
    updateContractBasicInfo();
    
    // 残り時間と役務実績の更新
    updateRemainingTimeDisplay();
}

// 契約の基本情報(企業名・拠点名・定期訪問時間)を更新
function updateContractBasicInfo() {
    if (!currentMonthlyUsage || !currentMonthlyUsage.contract_info) return;
    
    const contractInfo = currentMonthlyUsage.contract_info;
    const visitFrequency = contractInfo.visit_frequency || 'monthly';
    
    // 企業名を更新
    const companyNameElement = document.querySelector('.alert.alert-info .col-md-6:first-child p:first-child');
    if (companyNameElement) {
        companyNameElement.innerHTML = `
            <strong>企業名：</strong>
            <i class="fas fa-building me-1 text-success"></i>
            ${escapeHtml(contractInfo.company_name)}
        `;
    }
    
    // 拠点名を更新
    const branchNameElement = document.querySelector('.alert.alert-info .col-md-6:first-child p:nth-child(2)');
    if (branchNameElement) {
        branchNameElement.innerHTML = `
            <strong>拠点：</strong>
            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
            ${escapeHtml(contractInfo.branch_name)}
        `;
    }
    
    // 定期訪問時間を更新（スポット契約以外の場合）
    if (visitFrequency !== 'spot') {
        const regularTimeElement = document.querySelector('.alert.alert-info .col-md-6:first-child p:nth-child(3)');
        if (regularTimeElement) {
            const displayLimit = currentMonthlyUsage.regular_limit;
            const label = visitFrequency === 'weekly' ? '定期訪問時間（今月上限）：' : '定期訪問時間：';
            regularTimeElement.innerHTML = `<strong>${label}</strong>${formatHours(displayLimit)}/月`;
        }
    } else {
        // スポット契約の場合は定期訪問時間の行を非表示
        const regularTimeElement = document.querySelector('.alert.alert-info .col-md-6:first-child p:nth-child(3)');
        if (regularTimeElement) {
            regularTimeElement.style.display = 'none';
        }
    }
}

// HTMLエスケープ用のヘルパー関数
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 残り時間表示の更新
function updateRemainingTimeDisplay() {
    if (!currentMonthlyUsage) return;
    
    const selectedOption = document.getElementById('contract_id').options[document.getElementById('contract_id').selectedIndex];
    if (!selectedOption) return;
    
    const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
    
    // スポット契約の場合はスポット役務の統計を更新
    if (visitFrequency === 'spot') {
        updateSpotUsageDisplay();
        return;
    }
    
    // 通常契約の場合は#static-usage-infoを完全に再生成
    updateRegularContractUsageDisplay(visitFrequency);
}

// 通常契約の使用状況表示を更新（完全再生成）
function updateRegularContractUsageDisplay(visitFrequency) {
    if (!currentMonthlyUsage) return;
    
    const staticUsageInfo = document.getElementById('static-usage-info');
    if (!staticUsageInfo) return;
    
    // 契約が変更された場合、常に完全な表示を再生成
    generateFullRegularDisplay();
}

// 残り時間のみを更新
function updateRemainingTimeOnly() {
    if (!currentMonthlyUsage) return;
    
    const remainingTimeElement = document.querySelector('#static-usage-info p:last-child');
    if (!remainingTimeElement) return;
    
    const remainingHours = currentMonthlyUsage.remaining_hours;
    const remainingContent = remainingHours > 0 ? 
        formatHours(remainingHours) : 
        '<span class="text-warning">0時間（上限到達）</span>';
    
    // 残り時間の行のみを更新
    remainingTimeElement.innerHTML = `<strong>定期訪問残り時間：</strong>${remainingContent}`;
}

// 通常契約の完全な表示を生成（スポット契約から切り替えたとき）
function generateFullRegularDisplay() {
    if (!currentMonthlyUsage) return;
    
    const staticUsageInfo = document.getElementById('static-usage-info');
    if (!staticUsageInfo) return;
    
    // 年月を取得
    const serviceDate = document.getElementById('service_date').value || originalRecord.service_date;
    const date = new Date(serviceDate);
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    
    // APIから取得したデータ
    const regularHours = currentMonthlyUsage.current_used || 0;
    const regularCount = currentMonthlyUsage.regular_count || 0;
    const emergencyHours = currentMonthlyUsage.emergency_hours || 0;
    const emergencyCount = currentMonthlyUsage.emergency_count || 0;
    const documentCount = currentMonthlyUsage.document_count || 0;
    const remoteConsultationCount = currentMonthlyUsage.remote_consultation_count || 0;
    const remainingHours = currentMonthlyUsage.remaining_hours;
    const remainingContent = remainingHours > 0 ? 
        formatHours(remainingHours) : 
        '<span class="text-warning">0時間（上限到達）</span>';
    
    // 他の記録があるかチェック（全ての役務種別の合計件数で判定）
    const totalRecordCount = regularCount + emergencyCount + documentCount + remoteConsultationCount;
    const hasOtherRecords = totalRecordCount > 0;
    
    // HTMLを完全に生成
    if (hasOtherRecords) {
        let recordsList = '';
        
        // 定期訪問
        if (regularCount > 0) {
            recordsList += `<li>定期訪問: ${formatHours(regularHours)} (${regularCount}件)</li>`;
        }
        
        // 臨時訪問
        if (emergencyCount > 0) {
            recordsList += `<li>臨時訪問: ${formatHours(emergencyHours)} (${emergencyCount}件)</li>`;
        } else {
            recordsList += `<li>臨時訪問: 0:00 (0件)</li>`;
        }
        
        // 書面作成（時間記録なし）
        if (documentCount > 0) {
            recordsList += `<li>書面作成: ${documentCount}件 (時間記録なし)</li>`;
        }
        
        // 遠隔相談（時間記録なし）
        if (remoteConsultationCount > 0) {
            recordsList += `<li>遠隔相談: ${remoteConsultationCount}件 (時間記録なし)</li>`;
        }
        
        staticUsageInfo.innerHTML = `
            <p class="mb-1"><strong>${year}年${month}月の役務実績：</strong></p>
            <ul class="mb-2 small">
                ${recordsList}
            </ul>
            <p class="mb-1 small text-muted">
                <i class="fas fa-info-circle me-1"></i>
                上記は編集中の記録を除く実績です
            </p>
            <p class="mb-0"><strong>定期訪問残り時間：</strong>${remainingContent}</p>
        `;
    } else {
        staticUsageInfo.innerHTML = `
            <p class="mb-1"><strong>${year}年${month}月の役務実績：</strong></p>
            <div class="mb-2">
                <span class="badge bg-info">
                    <i class="fas fa-edit me-1"></i>
                    編集中の記録のみ
                </span>
            </div>
            <ul class="mb-2 small">
                <li>他の役務記録: なし</li>
            </ul>
            <p class="mb-0"><strong>定期訪問残り時間：</strong>${remainingContent}</p>
        `;
    }
}

// スポット契約の使用状況表示を更新
function updateSpotUsageDisplay() {
    if (!currentMonthlyUsage) return;
    
    const staticUsageInfo = document.getElementById('static-usage-info');
    if (!staticUsageInfo) return;
    
    const spotHours = currentMonthlyUsage.spot_hours || 0;
    const spotCount = currentMonthlyUsage.spot_count || 0;
    const documentCount = currentMonthlyUsage.document_count || 0;
    const remoteConsultationCount = currentMonthlyUsage.remote_consultation_count || 0;
    
    // 年月を取得
    const serviceDate = document.getElementById('service_date').value || originalRecord.service_date;
    const date = new Date(serviceDate);
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    
    // 他の役務記録があるかチェック（全ての役務種別の合計件数で判定）
    const totalRecordCount = spotCount + documentCount + remoteConsultationCount;
    const hasOtherRecords = totalRecordCount > 0;
    
    // HTMLを完全に再生成
    if (hasOtherRecords) {
        let recordsList = '';
        
        // スポット
        if (spotCount > 0) {
            recordsList += `<li>スポット: ${formatHours(spotHours)} (${spotCount}件)</li>`;
        }
        
        // 書面作成（時間記録なし）
        if (documentCount > 0) {
            recordsList += `<li>書面作成: ${documentCount}件 (時間記録なし)</li>`;
        }
        
        // 遠隔相談（時間記録なし）
        if (remoteConsultationCount > 0) {
            recordsList += `<li>遠隔相談: ${remoteConsultationCount}件 (時間記録なし)</li>`;
        }
        
        staticUsageInfo.innerHTML = `
            <p class="mb-1"><strong>${year}年${month}月の役務実績：</strong></p>
            <ul class="mb-2 small">
                ${recordsList}
            </ul>
            <p class="mb-1 small text-muted">
                <i class="fas fa-info-circle me-1"></i>
                上記は編集中の記録を除く実績です
            </p>
        `;
    } else {
        staticUsageInfo.innerHTML = `
            <p class="mb-1"><strong>${year}年${month}月の役務実績：</strong></p>
            <div class="mb-2">
                <span class="badge bg-info">
                    <i class="fas fa-edit me-1"></i>
                    編集中の記録のみ
                </span>
            </div>
            <ul class="mb-2 small">
                <li>他の役務記録: なし</li>
            </ul>
        `;
    }
}

// 月間使用状況の表示更新（毎週契約対応版）
function updateMonthlyUsageDisplay() {
    // 契約情報表示エリアに月間使用状況を追加
    const alertInfo = document.querySelector('.alert.alert-info');
    if (alertInfo && currentMonthlyUsage) {
        // 既存の使用状況表示があれば削除
        const existingUsage = alertInfo.querySelector('.monthly-usage-display');
        if (existingUsage) {
            existingUsage.remove();
        }
        
        // 契約情報から訪問頻度を取得
        const selectedOption = document.getElementById('contract_id').options[document.getElementById('contract_id').selectedIndex];
        const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
        
        // スポット契約の場合は何もしない（PHPで生成された表示をそのまま使用）
        if (visitFrequency === 'spot') {
            return;
        }
        
        // 通常契約の場合は従来通りの表示
        const usagePercent = Math.round(currentMonthlyUsage.usage_percentage * 10) / 10;
        const progressBarClass = usagePercent >= 100 ? 'bg-danger' : 
                               usagePercent >= 80 ? 'bg-warning' : 'bg-success';
        
        const bimonthlyType = selectedOption.dataset.bimonthlyType;
        
        // 訪問頻度に応じたラベル
        let limitLabel = '定期訪問時間';
        let usageNote = '';
        
        switch(visitFrequency) {
            case 'weekly':
                limitLabel = '今月の上限時間';
                usageNote = '<small class="text-muted">※毎週契約:祝日・非訪問日考慮で計算</small>';
                break;
            case 'bimonthly':
                const serviceDate = document.getElementById('service_date').value;
                let isVisitMonth = false;
                let bimonthlyNote = '';
                
                if (serviceDate && bimonthlyType) {
                    // bimonthly_typeを使用して訪問月判定
                    const serviceDateObj = new Date(serviceDate);
                    const currentMonth = serviceDateObj.getMonth() + 1; // 1-12
                    
                    if (bimonthlyType === 'even') {
                        isVisitMonth = currentMonth % 2 === 0;
                        bimonthlyNote = '偶数月訪問(2,4,6,8,10,12月)';
                    } else if (bimonthlyType === 'odd') {
                        isVisitMonth = currentMonth % 2 === 1;
                        bimonthlyNote = '奇数月訪問(1,3,5,7,9,11月)';
                    } else {
                        // フォールバック
                        const startMonth = parseInt(selectedOption.dataset.startMonth || '1');
                        const startYear = parseInt(selectedOption.dataset.startYear || new Date().getFullYear());
                        const currentYear = serviceDateObj.getFullYear();
                        const currentMonth = serviceDateObj.getMonth() + 1;
                        
                        const totalMonthsFromStart = ((currentYear - startYear) * 12) + (currentMonth - startMonth);
                        isVisitMonth = totalMonthsFromStart % 2 === 0;
                        bimonthlyNote = '開始月基準';
                    }
                }
                
                limitLabel = isVisitMonth ? '隔月訪問時間' : '隔月契約(非訪問月)';
                usageNote = `<small class="text-muted">※隔月契約:${isVisitMonth ? '訪問月' : '非訪問月'}(${bimonthlyNote})</small>`;
                break;
            default:
                limitLabel = '月間定期時間';
                usageNote = '<small class="text-muted">※毎月契約</small>';
                break;
        }
        
        const usageDiv = document.createElement('div');
        usageDiv.className = 'monthly-usage-display mt-3';
        usageDiv.innerHTML = `
            <h6><i class="fas fa-chart-bar me-2"></i>今月の使用状況(編集中の記録を除く)</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <small>${limitLabel}</small>
                            <small>${formatHours(currentMonthlyUsage.current_used)} / ${formatHours(currentMonthlyUsage.regular_limit)}</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ${progressBarClass}" style="width: ${Math.min(100, usagePercent)}%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <p class="small mb-0">
                        <strong>残り時間:</strong> ${formatHours(currentMonthlyUsage.remaining_hours)}<br>
                        <strong>使用率:</strong> ${usagePercent}%
                    </p>
                </div>
            </div>
            ${usageNote}
        `;
        
        alertInfo.appendChild(usageDiv);
    }
}

// 訪問種別フィールドの表示/非表示切り替え
function toggleVisitTypeField() {
    const serviceType = document.getElementById('service_type').value;
    const requiresVisitType = visitTypeRequiredTypes.includes(serviceType);
    const visitTypeGroup = document.getElementById('visit-type-group');
    const visitTypeSelect = document.getElementById('visit_type');
    
    if (requiresVisitType) {
        visitTypeGroup.style.display = '';
        visitTypeSelect.required = true;
        
        // デフォルト値が設定されていない場合は「訪問」を選択
        if (!visitTypeSelect.value) {
            visitTypeSelect.value = 'visit';
        }
    } else {
        visitTypeGroup.style.display = 'none';
        visitTypeSelect.required = false;
        visitTypeSelect.value = '';
    }
    
    // 交通費ボタンの表示も更新
    updateTravelExpenseButton();
}

// 役務種別に応じて時間入力欄の表示/非表示を切り替え
function toggleTimeFields() {
    const serviceType = document.getElementById('service_type').value;
    const requiresTime = timeRequiredTypes.includes(serviceType);
    const isOtherType = serviceType === 'other';
    
    // 時間入力欄の表示/非表示
    const timeInputRow = document.getElementById('time-input-row');
    const hoursPreviewGroup = document.getElementById('hours-preview-group');
    const noTimeInfo = document.getElementById('no-time-info');
    
    // 請求金額フィールドの表示/非表示
    const billingAmountSection = document.getElementById('billing-amount-section');
    const billingAmountInput = document.getElementById('direct_billing_amount');
    const otherNotice = document.getElementById('other-notice');
    
    if (requiresTime) {
        timeInputRow.style.display = '';
        hoursPreviewGroup.style.display = '';
        noTimeInfo.style.display = 'none';
        
        // 時間入力欄を必須にする
        document.getElementById('start_time').required = true;
        document.getElementById('end_time').required = true;
        
        // 請求金額フィールドを非表示
        if (billingAmountSection) {
            billingAmountSection.style.display = 'none';
            billingAmountInput.required = false;
        }
        if (otherNotice) {
            otherNotice.style.display = 'none';
        }
    } else {
        timeInputRow.style.display = 'none';
        hoursPreviewGroup.style.display = 'none';
        
        if (isOtherType) {
            noTimeInfo.style.display = 'none';
            
            // 請求金額フィールドを表示
            if (billingAmountSection) {
                billingAmountSection.style.display = '';
                billingAmountInput.required = true;
            }
            if (otherNotice) {
                otherNotice.style.display = '';
            }
        } else {
            noTimeInfo.style.display = '';
            
            // 請求金額フィールドを非表示
            if (billingAmountSection) {
                billingAmountSection.style.display = 'none';
                billingAmountInput.required = false;
            }
            if (otherNotice) {
                otherNotice.style.display = 'none';
            }
        }
        
        // 時間入力欄の必須を解除し、値をクリア
        document.getElementById('start_time').required = false;
        document.getElementById('end_time').required = false;
        document.getElementById('start_time').value = '';
        document.getElementById('end_time').value = '';
        
        // 時間関連の警告をクリア
        hideOvertimeWarning();
        hideTimeValidationError();
    }
}

// 業務内容の必須/任意を切り替え
function updateDescriptionRequirement() {
    const serviceType = document.getElementById('service_type').value;
    const requiresTime = timeRequiredTypes.includes(serviceType);
    const descriptionInput = document.getElementById('description');
    const descriptionRequiredMark = document.getElementById('description-required-mark');
    const descriptionHelp = document.getElementById('description-help');
    
    // スポット、書面作成、遠隔相談、その他の場合は業務内容必須
    if (serviceType === 'spot' || !requiresTime) {
        descriptionInput.required = true;
        descriptionRequiredMark.style.display = '';
        
        if (serviceType === 'spot') {
            descriptionHelp.innerHTML = '<strong>スポットの場合、業務内容の詳細な記載は必須です。</strong><br><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>';
        } else if (serviceType === 'other') {
            descriptionHelp.innerHTML = '<strong>その他の場合、業務内容の詳細な記載は必須です。</strong><br><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>';
        } else {
            descriptionHelp.innerHTML = '<strong>書面作成・遠隔相談の場合、業務内容の詳細な記載は必須です。</strong><br><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>';
        }
    } else {
        // 定期訪問・臨時訪問の場合は任意
        descriptionInput.required = false;
        descriptionRequiredMark.style.display = 'none';
        descriptionHelp.innerHTML = '実施した業務内容を記載してください（任意）。承認の際に参考となります。<br><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>';
    }
}

// 隔月契約の非訪問月チェック関数
function checkBimonthlyVisitValidity(contractId, serviceDate) {
    const contractSelect = document.getElementById('contract_id');
    if (!contractSelect) {
        return true;
    }
    
    // 選択されたオプションを取得
    let selectedOption = null;
    for (let i = 0; i < contractSelect.options.length; i++) {
        if (contractSelect.options[i].value == contractId) {
            selectedOption = contractSelect.options[i];
            break;
        }
    }
    
    if (!selectedOption) {
        return true;
    }
    
    // dataattribute から訪問頻度を取得
    const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
    
    if (visitFrequency !== 'bimonthly') {
        return true;
    }
    
    // bimonthly_typeを取得（新しい判定方法）
    const bimonthlyType = selectedOption.dataset.bimonthlyType;
    
    if (!bimonthlyType) {
        console.warn('bimonthly_type not set for contract:', contractId);
        
        // フォールバック: start_dateから判定（後方互換性）
        const startMonth = parseInt(selectedOption.dataset.startMonth || '1');
        const startYear = parseInt(selectedOption.dataset.startYear || new Date().getFullYear());
        
        // サービス日から年月を取得
        const serviceDateObj = new Date(serviceDate);
        const serviceYear = serviceDateObj.getFullYear();
        const serviceMonth = serviceDateObj.getMonth() + 1; // 1-12
        
        // 開始月からの経過月数が偶数の場合は訪問月
        const totalMonthsFromStart = ((serviceYear - startYear) * 12) + (serviceMonth - startMonth);
        return totalMonthsFromStart % 2 === 0;
    }
    
    // bimonthly_typeに基づいて判定（新しい方法）
    const serviceDateObj = new Date(serviceDate);
    const serviceMonth = serviceDateObj.getMonth() + 1; // 1-12
    
    if (bimonthlyType === 'even') {
        // 偶数月が訪問月
        return serviceMonth % 2 === 0;
    } else if (bimonthlyType === 'odd') {
        // 奇数月が訪問月
        return serviceMonth % 2 === 1;
    }
    
    // 不明なbimonthly_typeの場合は訪問月とする（安全のため）
    console.warn('Unknown bimonthly_type:', bimonthlyType);
    return true;
}

// 隔月契約警告の表示
function showBimonthlyWarning() {
    try {
        // 既存の警告を削除
        hideBimonthlyWarning();
        
        // 契約情報からbimonthly_typeを取得
        const contractSelect = document.getElementById('contract_id');
        const selectedOption = contractSelect.options[contractSelect.selectedIndex];
        const bimonthlyType = selectedOption.dataset.bimonthlyType;
        
        let bimonthlyTypeText = '';
        if (bimonthlyType === 'even') {
            bimonthlyTypeText = '(偶数月が訪問月)';
        } else if (bimonthlyType === 'odd') {
            bimonthlyTypeText = '(奇数月が訪問月)';
        }
        
        // 警告を挿入する適切な場所を特定
        const serviceTypeSelect = document.getElementById('service_type');
        
        if (!serviceTypeSelect) {
            return;
        }
        
        let insertLocation = serviceTypeSelect.closest('.mb-3');
        
        if (!insertLocation) {
            insertLocation = serviceTypeSelect.parentNode.parentNode;
        }
        
        if (!insertLocation) {
            insertLocation = document.querySelector('.row').querySelector('.col-md-6');
        }
        
        if (!insertLocation) {
            return;
        }
        
        const warningDiv = document.createElement('div');
        warningDiv.id = 'bimonthly-warning';
        warningDiv.className = 'alert alert-warning mt-2';
        warningDiv.style.cssText = 'margin-top: 0.5rem !important; border: 2px solid #ffc107 !important;';
        warningDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>注意:</strong> この月は隔月契約の非訪問月です${bimonthlyTypeText}。定期訪問は実施できないため、臨時訪問に自動変更されました。
        `;
        
        try {
            if (insertLocation.nextSibling) {
                insertLocation.parentNode.insertBefore(warningDiv, insertLocation.nextSibling);
            } else {
                insertLocation.parentNode.appendChild(warningDiv);
            }
        } catch (insertError) {
            const serviceTypeRow = document.querySelector('.row').children[1];
            if (serviceTypeRow) {
                serviceTypeRow.insertAdjacentElement('afterend', warningDiv);
            } else {
                return;
            }
        }
        
    } catch (error) {
        // フォールバック: エラーが発生した場合はalertで表示
        alert('注意: この月は隔月契約の非訪問月です。定期訪問は実施できないため、臨時訪問に自動変更されました。');
    }
}

// 隔月契約警告の非表示
function hideBimonthlyWarning() {
    const warningDiv = document.getElementById('bimonthly-warning');
    if (warningDiv) {
        warningDiv.remove();
    }
}

// 役務種別変更時の処理（修正版）
function updateServiceTypeInfo() {
    const serviceType = document.getElementById('service_type').value;

    const helpText = document.getElementById('service-type-help');
    if (helpText) {
        helpText.textContent = serviceTypeHelp[serviceType];
    }
    
    // 隔月警告の削除条件を修正
    if (serviceType === 'regular') {
        const contractId = document.getElementById('contract_id').value;
        const serviceDate = document.getElementById('service_date').value;
        
        if (contractId && serviceDate) {
            const isValidVisit = checkBimonthlyVisitValidity(contractId, serviceDate);
            
            if (!isValidVisit) {
                // 警告を表示
                showBimonthlyWarning();
                
                // 臨時訪問に変更
                document.getElementById('service_type').value = 'emergency';
                
                // updateServiceTypeInfoを再帰呼び出ししない
                // 代わりに必要なUI更新のみ直接実行
                const helpText = document.getElementById('service-type-help');
                if (helpText) {
                    helpText.textContent = serviceTypeHelp['emergency'];
                }
                
                toggleTimeFields();
                toggleVisitTypeField();
                updateDescriptionRequirement();
                updateTravelExpenseButton();
                calculateHours();
                
                return; // 重要: ここで関数を終了して後続の処理をスキップ
            } else {
                hideBimonthlyWarning();
            }
        } else {
            hideBimonthlyWarning();
        }
    } else if (serviceType === 'emergency') {
        // emergency の場合は警告を削除しない
        // （隔月契約の自動変更による emergency の場合、警告を保持）
        const existingWarning = document.getElementById('bimonthly-warning');
        if (!existingWarning) {
            // 既存の警告がない場合は手動での emergency 選択
            // 特に処理不要
        }
    } else {
        // document や remote_consultation など、その他の役務種別の場合は警告を削除
        hideBimonthlyWarning();
    }
    
    calculateHours();
}

// 時間計算（時間入力が必要な役務種別のみ）
function calculateHours() {
    const serviceType = document.getElementById('service_type').value;
    const requiresTime = timeRequiredTypes.includes(serviceType);
    
    if (!requiresTime) {
        // 時間入力が不要な役務種別では計算をスキップ
        hideOvertimeWarning();
        const overtimeReasonGroup = document.getElementById('overtime-reason-group');
        if (overtimeReasonGroup) overtimeReasonGroup.style.display = 'none';
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) requiredMark.style.display = 'none';
        return;
    }
    
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (startTime && endTime) {
        const start = new Date('2000-01-01 ' + startTime);
        const end = new Date('2000-01-01 ' + endTime);
        
        if (end < start) {
            end.setDate(end.getDate() + 1);
        }
        
        const diff = (end - start) / 1000 / 60 / 60;
        let displayHours, displayMinutes;
        displayHours = Math.floor(diff);
        displayMinutes = Math.round((diff - displayHours) * 60);
        
        document.getElementById('calculated-hours').textContent = 
            displayHours + ':' + (displayMinutes < 10 ? '0' : '') + displayMinutes;
        
        // 定期訪問の場合のみ月間定期時間延長チェック
        const overtimeInfo = document.getElementById('overtime-info');
        const overtimeReasonGroup = document.getElementById('overtime-reason-group');
        
        if (serviceType === 'regular' && currentMonthlyUsage) {
            checkRegularOvertimeWarning(diff);
        } else {
            // 定期訪問以外では延長警告を表示しない
            if (overtimeInfo) overtimeInfo.textContent = '';
            if (overtimeReasonGroup) overtimeReasonGroup.style.display = 'none';
            hideOvertimeWarning();
            const overtimeReasonInput = document.getElementById('overtime_reason');
            if (overtimeReasonInput) overtimeReasonInput.required = false;
            
            // 必須マークを非表示
            const requiredMark = document.getElementById('overtime-reason-required');
            if (requiredMark) requiredMark.style.display = 'none';
        }
    }
}

// ============================================
// 累積時間チェックロジック(役務記録編集画面用)
// ============================================

/**
 * 定期訪問の月間定期時間延長警告チェック(累積時間ベース - 編集用)
 * calculateHours()から呼ばれる
 * 
 * @param {number} serviceHours - 役務時間
 */
function checkRegularOvertimeWarning(serviceHours) {
    if (!currentMonthlyUsage) {
        // 月間使用状況が取得できていない場合はスキップ
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        hideOvertimeWarning();
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) requiredMark.style.display = 'none';
        return;
    }
    
    // 契約の月の訪問時間(regular_visit_hours)がNULLまたは0の場合は延長警告を表示しない（上限なし）
    if (currentMonthlyUsage.regular_limit === null || currentMonthlyUsage.regular_limit === 0) {
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        hideOvertimeWarning();
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) requiredMark.style.display = 'none';
        return;
    }
    
    const contractId = contractSelect.value;
    const serviceDate = serviceDateInput.value;
    const recordId = <?= isset($record['id']) ? $record['id'] : 0 ?>; // 編集中の記録ID
    
    if (!contractId || !serviceDate) {
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        hideOvertimeWarning();
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) requiredMark.style.display = 'none';
        return;
    }
    
    // 累積時間チェックAPIを呼び出し(編集中の記録を除外)
    checkCumulativeHoursAPI(contractId, serviceDate, serviceHours, recordId);
}

/**
 * 累積時間チェックAPIの呼び出し(編集用)
 * 
 * @param {number} contractId - 契約ID
 * @param {string} serviceDate - 役務日(YYYY-MM-DD)
 * @param {number} serviceHours - 役務時間
 * @param {number} excludeRecordId - 除外する記録ID(編集中の記録)
 */
function checkCumulativeHoursAPI(contractId, serviceDate, serviceHours, excludeRecordId) {
    // 契約IDのバリデーション（数値または数値文字列でない場合はスキップ）
    const numericContractId = parseInt(contractId, 10);
    if (!contractId || isNaN(numericContractId) || numericContractId <= 0) {
        console.log('checkCumulativeHoursAPI: Invalid contract ID', {
            contractId: contractId,
            numericContractId: numericContractId
        });
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) requiredMark.style.display = 'none';
        return;
    }
    
    // その他のバリデーション
    if (!serviceDate || typeof serviceHours !== 'number' || isNaN(serviceHours) || serviceHours <= 0) {
        console.log('checkCumulativeHoursAPI: Validation failed', {
            contractId: contractId,
            serviceDate: serviceDate,
            serviceHours: serviceHours,
            serviceHoursType: typeof serviceHours
        });
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) requiredMark.style.display = 'none';
        return;
    }

    console.log('checkCumulativeHoursAPI: Calling API with', {
        contract_id: numericContractId,
        service_date: serviceDate,
        service_hours: serviceHours,
        exclude_record_id: excludeRecordId
    });

    fetch('/op_website/public/api/contracts/check-cumulative-hours', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            contract_id: numericContractId,
            service_date: serviceDate,
            service_hours: serviceHours,
            exclude_record_id: excludeRecordId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('checkCumulativeHoursAPI: API response', data);
        handleCumulativeCheckResult(data);
    })
    .catch(error => {
        console.error('checkCumulativeHoursAPI: Error', error);
        // エラー時は警告を非表示にして延長理由を任意にする
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) requiredMark.style.display = 'none';
    });
}

/**
 * 累積時間チェック結果の処理
 * 
/**
 * 累積時間チェック結果の処理
 * 
 * @param {Object} data - APIレスポンスデータ
 */
function handleCumulativeCheckResult(data) {
    const visitFrequency = currentMonthlyUsage.visit_frequency || 'monthly';
    
    // 毎週契約の場合は、1回の役務時間のみをチェック（トータル役務時間は考慮しない）
    // 毎月/隔月契約の場合は、トータル役務時間も考慮
    let shouldRequireOvertimeReason = false;
    let warningMessage = '';
    
    if (visitFrequency === 'weekly') {
        // 毎週契約：1回の役務時間が契約時間を超える場合のみ延長理由が必要
        if (data.exceeds_single_visit_limit) {
            shouldRequireOvertimeReason = true;
            warningMessage = '1回の役務時間が契約時間を延長します';
        }
    } else {
        // 毎月/隔月契約：通常通り、トータル役務時間を考慮
        if (data.requires_overtime_reason) {
            shouldRequireOvertimeReason = true;
            
            if (data.exceeds_single_visit_limit) {
                // 1回の役務時間が契約時間を延長
                warningMessage = '1回の役務時間が契約時間を延長します';
            } else if (data.exceeds_monthly_limit) {
                // 累積時間が月の訪問時間を延長
                if (visitFrequency === 'bimonthly') {
                    warningMessage = '累積時間が隔月契約の訪問時間を延長します';
                } else {
                    warningMessage = '累積時間が月間定期時間を延長します';
                }
            }
        }
    }
    
    if (shouldRequireOvertimeReason) {
        // 延長理由が必要な場合
        // 累積時間ベースの延長警告を表示
        showCumulativeOvertimeWarning(
            data.this_service_hours,
            data.cumulative_hours,
            data.total_with_this_service,
            data.monthly_limit,
            data.contract_regular_hours,
            warningMessage,
            data.exceeds_single_visit_limit
        );
        
        // 延長理由フィールドを表示・必須化
        overtimeReasonGroup.style.display = 'block';
        overtimeInfo.textContent = `(${warningMessage})`;
        overtimeInfo.className = 'text-danger ms-3';
        document.getElementById('overtime_reason').required = true;
        
        // 必須マークを表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) {
            requiredMark.style.display = 'inline';
        }
    } else {
        // 正常範囲
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
        
        // 必須マークを非表示
        const requiredMark = document.getElementById('overtime-reason-required');
        if (requiredMark) {
            requiredMark.style.display = 'none';
        }
    }
}

/**
 * 累積時間ベースの延長警告表示
 * 
 * @param {number} serviceHours - 今回の役務時間
 * @param {number} cumulativeHours - 累積時間(今回より前の役務)
 * @param {number} totalHours - 今回を含む合計時間
 * @param {number} monthlyLimit - 月の訪問時間
 * @param {number} contractHours - 契約の基本時間
 * @param {string} customMessage - カスタムメッセージ
 * @param {boolean} isSingleVisitExcess - 1回の役務時間延長フラグ
 */
function showCumulativeOvertimeWarning(serviceHours, cumulativeHours, totalHours, monthlyLimit, contractHours, customMessage, isSingleVisitExcess) {
    let warningDiv = document.getElementById('overtime-warning');
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'overtime-warning';
        warningDiv.className = 'alert alert-warning mt-2';
        hoursPreview.appendChild(warningDiv);
    }
    
    const visitFrequency = currentMonthlyUsage.visit_frequency || 'monthly';
    
    let limitDescription = '月間定期訪問時間';
    switch(visitFrequency) {
        case 'weekly':
            limitDescription = '今月の上限時間(祝日考慮)';
            break;
        case 'bimonthly':
            limitDescription = '隔月契約の訪問時間';
            break;
    }
    
    let detailsHtml = '';
    
    if (isSingleVisitExcess) {
        // 1回の役務時間延長の場合
        const excessHours = serviceHours - contractHours;
        detailsHtml = `
            <strong>・今回の役務時間:</strong> ${formatHours(serviceHours)}<br>
            <strong>・契約の基本時間:</strong> ${formatHours(contractHours)}<br>
            <strong>・延長時間:</strong> ${formatHours(excessHours)}
        `;
    } else {
        // 累積時間延長の場合
        const excessHours = totalHours - monthlyLimit;
        detailsHtml = `
            <strong>・今回の役務時間:</strong> ${formatHours(serviceHours)}<br>
            <strong>・累積時間(編集中の記録を除く今回の役務より前):</strong> ${formatHours(cumulativeHours)}<br>
            <strong>・今回の役務を含む合計:</strong> ${formatHours(totalHours)}<br>
            <strong>・月の訪問時間:</strong> ${formatHours(monthlyLimit)}<br>
            <strong>・延長時間:</strong> ${formatHours(excessHours)}<br>
            <strong>・上限種別:</strong> ${limitDescription}
        `;
    }
    
    warningDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>注意:</strong> ${customMessage || '時間を延長します'}<br>
        <small class="text-muted">
            ${detailsHtml}
        </small>
    `;
}

/**
 * フォールバック: 従来の単純な延長チェック
 * APIがエラーの場合に使用
 * 
 * @param {number} serviceHours - 役務時間
 */
function fallbackToSimpleCheck(serviceHours) {
    if (!currentMonthlyUsage) {
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
        return;
    }
    
    const newTotalRegularHours = currentMonthlyUsage.current_used + serviceHours;
    
    if (newTotalRegularHours > currentMonthlyUsage.regular_limit) {
        const visitFrequency = currentMonthlyUsage.visit_frequency || 'monthly';
        let warningMessage = '';
        
        switch(visitFrequency) {
            case 'weekly':
                warningMessage = '今月の上限時間を延長します(毎週契約)';
                break;
            case 'bimonthly':
                warningMessage = '隔月契約の訪問時間を延長します';
                break;
            default:
                warningMessage = '月間定期時間を延長します';
                break;
        }
        
        showOvertimeWarning(serviceHours, newTotalRegularHours, warningMessage);
        overtimeReasonGroup.style.display = 'block';
        overtimeInfo.textContent = `(${warningMessage})`;
        overtimeInfo.className = 'text-danger ms-3';
        document.getElementById('overtime_reason').required = true;
    } else {
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
    }
}

// 月間定期時間延長警告の表示（毎週契約対応版）
function showOvertimeWarning(serviceHours, newTotalHours, customMessage) {
    let warningDiv = document.getElementById('overtime-warning');
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'overtime-warning';
        warningDiv.className = 'alert alert-warning mt-2';
        document.getElementById('hours-preview').appendChild(warningDiv);
    }
    
    const excessHours = newTotalHours - currentMonthlyUsage.regular_limit;
    const visitFrequency = currentMonthlyUsage.visit_frequency || 'monthly';
    
    let limitDescription = '月間定期訪問時間';
    switch(visitFrequency) {
        case 'weekly':
            limitDescription = '今月の上限時間（祝日考慮）';
            break;
        case 'bimonthly':
            limitDescription = '隔月契約の訪問時間';
            break;
    }
    
    warningDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>注意:</strong> ${customMessage || '時間を延長します'}<br>
        <small class="text-muted">
            <strong>・今回の役務時間:</strong> ${formatHours(serviceHours)}<br>
            <strong>・現在の使用状況:</strong> ${formatHours(currentMonthlyUsage.current_used)} / ${formatHours(currentMonthlyUsage.regular_limit)}<br>
            <strong>・延長時間:</strong> ${formatHours(excessHours)}<br>
            <strong>・上限種別:</strong> ${limitDescription}<br>
            <strong>・延長理由の入力が必須です</strong>
        </small>
    `;
}

// 使用率警告の表示
function showUsageWarning(message, type) {
    let warningDiv = document.getElementById('usage-warning');
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'usage-warning';
        warningDiv.className = `alert alert-${type} mt-2`;
        document.getElementById('hours-preview').appendChild(warningDiv);
    }
    
    warningDiv.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        <strong>情報:</strong> ${message}
    `;
}

// 延長警告の非表示
function hideOvertimeWarning() {
    const warningDiv = document.getElementById('overtime-warning');
    if (warningDiv) {
        warningDiv.remove();
    }
    
    const usageWarningDiv = document.getElementById('usage-warning');
    if (usageWarningDiv) {
        usageWarningDiv.remove();
    }
}

// 従来のバリデーション関数の実装
function validateFormInternal() {
    const serviceType = document.getElementById('service_type').value;
    const requiresTime = timeRequiredTypes.includes(serviceType);
    const requiresVisitType = visitTypeRequiredTypes.includes(serviceType);
    const description = document.getElementById('description').value.trim();
    
    // 訪問種別が必要な役務種別でチェック
    if (requiresVisitType) {
        const visitType = document.getElementById('visit_type').value;
        if (!visitType) {
            alert('訪問種別を選択してください。');
            document.getElementById('visit_type').focus();
            return false;
        }
    }
    
    // 書面作成・遠隔相談の場合は業務内容必須チェック
    if (!requiresTime && !description) {
        alert('書面作成・遠隔相談の場合、業務内容の入力は必須です。');
        document.getElementById('description').focus();
        return false;
    }
    
    // 時間入力が必要な役務種別の場合の従来のチェック
    if (requiresTime) {
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        
        if (!startTime || !endTime) {
            alert('開始時刻と終了時刻を入力してください。');
            if (!startTime) document.getElementById('start_time').focus();
            else document.getElementById('end_time').focus();
            return false;
        }
        
        // 定期訪問の延長理由必須チェック
        // required属性が設定されているかどうかで判定（APIによる累積時間チェック結果を反映）
        if (serviceType === 'regular') {
            const overtimeReasonInput = document.getElementById('overtime_reason');
            const overtimeReason = overtimeReasonInput.value.trim();
            
            // required属性がtrueの場合のみチェック
            if (overtimeReasonInput.required && !overtimeReason) {
                alert('月の訪問時間を超過しています。延長理由の入力は必須です。');
                overtimeReasonInput.focus();
                return false;
            }
        }
    }

    // 隔月契約の非訪問月チェック
    if (serviceType === 'regular') {
        const contractId = document.getElementById('contract_id').value;
        const serviceDate = document.getElementById('service_date').value;
        
        if (contractId && serviceDate) {
            // 隔月契約の非訪問月かどうかをチェック
            if (!checkBimonthlyVisitValidity(contractId, serviceDate)) {
                alert('選択された役務日は隔月契約の非訪問月です。定期訪問は実施できません。臨時訪問をご選択ください。');
                document.getElementById('service_type').focus();
                return false;
            }
        }
    }

    return true;
}

// 時間重複チェック（時間入力が必要な役務種別のみ）
function checkTimeValidation() {
    const serviceType = document.getElementById('service_type').value;
    const requiresTime = timeRequiredTypes.includes(serviceType);
    
    if (!requiresTime) {
        // 時間入力が不要な役務種別では重複チェックをスキップ
        hideTimeValidationError();
        disableSubmitButton(false);
        return;
    }
    
    const serviceDate = document.getElementById('service_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (serviceDate && startTime && endTime) {
        if (startTime >= endTime) {
            showTimeValidationError('終了時刻は開始時刻より後に設定してください。');
            disableSubmitButton(true);
        } else {
            hideTimeValidationError();
            // サーバーサイドでの重複チェック
            checkTimeOverlapAPI(serviceDate, startTime, endTime);
        }
    } else {
        hideTimeValidationError();
        disableSubmitButton(false);
    }
}

// API による時間重複チェック
function checkTimeOverlapAPI(serviceDate, startTime, endTime) {
    const formData = new FormData();
    formData.append('service_date', serviceDate);
    formData.append('start_time', startTime);
    formData.append('end_time', endTime);
    formData.append('exclude_id', originalRecord.id); // 編集中の記録を除外
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    // ローディング表示
    showTimeValidationError('<i class="fas fa-spinner fa-spin me-2"></i>時間重複をチェック中...', 'info');
    disableSubmitButton(true);
    
    fetch('/op_website/public/api/service_records/check-time-overlap', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            hideTimeValidationError();
            disableSubmitButton(false);
        } else {
            showTimeValidationError(data.message, 'danger');
            disableSubmitButton(true);
        }
    })
    .catch(error => {
        showTimeValidationError('時間重複チェックでエラーが発生しました。', 'warning');
        disableSubmitButton(false); // エラー時は送信を許可
    });
}

// 送信ボタンの有効/無効切り替え
function disableSubmitButton(disabled, reason = null) {
    const submitButton = document.querySelector('button[onclick="handleFormSubmission()"]');
    if (submitButton) {
        submitButton.disabled = disabled;
        if (disabled) {
            submitButton.classList.add('disabled');
            if (reason === '締め処理済み') {
                submitButton.innerHTML = '<i class="fas fa-lock me-1"></i>締め処理済み';
            } else if (reason) {
                submitButton.innerHTML = `<i class="fas fa-ban me-1"></i>${reason}`;
            } else {
                submitButton.innerHTML = '<i class="fas fa-ban me-1"></i>エラー';
            }
        } else {
            submitButton.classList.remove('disabled');
            submitButton.innerHTML = '<i class="fas fa-save me-1"></i>更新';
        }
    }
}

function showTimeValidationError(message, type = 'danger') {
    const warningDiv = document.getElementById('time-overlap-warning');
    warningDiv.innerHTML = `
        <div class="alert alert-${type}">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
}

function hideTimeValidationError() {
    const warningDiv = document.getElementById('time-overlap-warning');
    warningDiv.innerHTML = '';
}

// 交通費登録ボタンの表示制御を更新する関数
function updateTravelExpenseButton() {
    const serviceType = document.getElementById('service_type').value;
    const visitType = document.getElementById('visit_type').value;
    const requiresTime = timeRequiredTypes.includes(serviceType);
    const requiresVisitType = visitTypeRequiredTypes.includes(serviceType);
    
    const travelExpenseButton = document.querySelector('a[href*="travel_expenses/create"]');
    
    if (travelExpenseButton) {
        // 時間入力が必要な役務種別で、かつ訪問（オンラインではない）場合のみ表示
        const shouldShow = requiresTime && (!requiresVisitType || visitType === 'visit');
        
        if (shouldShow) {
            travelExpenseButton.style.display = '';
        } else {
            travelExpenseButton.style.display = 'none';
        }
    }
}

// =================== テンプレート機能関連 ===================

// テンプレート選択モーダルを表示
function showTemplateModal() {
    loadTemplates();
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    modal.show();
}

// テンプレートを読み込み
function loadTemplates() {
    fetch('<?= base_url('api/service_templates') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                templates = data.data;
                displayTemplates();
            } else {
                showTemplateError('テンプレートの読み込みに失敗しました。');
            }
        })
        .catch(error => {
            showTemplateError('テンプレートの読み込み中にエラーが発生しました。');
        });
}

// テンプレートを表示
function displayTemplates() {
    const systemContainer = document.getElementById('system-templates');
    const personalContainer = document.getElementById('personal-templates');
    
    // システムテンプレート
    if (templates.system && templates.system.length > 0) {
        systemContainer.innerHTML = templates.system.map(template => 
            createTemplateItem(template, 'system')
        ).join('');
    } else {
        systemContainer.innerHTML = '<div class="text-muted text-center py-3">システムテンプレートがありません</div>';
    }
    
    // 個人テンプレート
    if (templates.personal && templates.personal.length > 0) {
        personalContainer.innerHTML = templates.personal.map(template => 
            createTemplateItem(template, 'personal')
        ).join('');
    } else {
        personalContainer.innerHTML = '<div class="text-muted text-center py-3">個人テンプレートがありません</div>';
    }
}

// テンプレートアイテムのHTML生成
function createTemplateItem(template, type) {
    const preview = template.preview || getPreviewText(template.content, 80);
    
    return `
        <div class="template-item" onclick="selectTemplate(${template.id}, '${type}')">
            <div class="template-preview">${escapeHtml(preview)}</div>
        </div>
    `;
}

function getPreviewText(content, maxLength = 50) {
    const preview = content.replace(/\s+/g, ' ').trim();
    
    if (preview.length <= maxLength) {
        return preview;
    }
    
    return preview.substring(0, maxLength) + '...';
}

// テンプレートを選択
function selectTemplate(templateId, type) {
    const template = (type === 'system' ? templates.system : templates.personal)
        .find(t => t.id === templateId);
    
    if (template) {
        // テンプレート内容をテキストエリアに設定
        const descriptionField = document.getElementById('description');
        const currentContent = descriptionField.value.trim();
        
        // 既存の内容がある場合は「。」で繋げて追加、ない場合はそのまま設定
        if (currentContent) {
            descriptionField.value = currentContent + '。' + template.content;
        } else {
            descriptionField.value = template.content;
        }

        // 使用統計を更新
        updateTemplateUsage(templateId);
        
        // モーダルを閉じる
        const modal = bootstrap.Modal.getInstance(document.getElementById('templateModal'));
        modal.hide();
        
        // 成功メッセージ
        showTemplateAppliedNotice(getPreviewText(template.content, 30));
    }
}

// テンプレート適用通知の表示
function showTemplateAppliedNotice(preview) {
    const notice = document.getElementById('template-applied-notice');
    const noticeText = document.getElementById('template-applied-text');
    
    if (notice && noticeText) {
        noticeText.textContent = `テンプレート「${preview}」が適用されました`;
        notice.style.display = 'block';
        
        // 3秒後に自動で非表示
        setTimeout(() => {
            notice.style.display = 'none';
        }, 3000);
    }
}

// 業務内容をクリア
function clearDescription() {
    if (confirm('入力された役務内容をクリアしますか？')) {
        document.getElementById('description').value = '';
        
        // テンプレート適用通知も非表示
        const notice = document.getElementById('template-applied-notice');
        if (notice) {
            notice.style.display = 'none';
        }
    }
}

// テンプレート使用統計を更新
function updateTemplateUsage(templateId) {
    const formData = new FormData();
    formData.append('template_id', templateId);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url('api/service_templates/use') ?>', {
        method: 'POST',
        body: formData
    }).catch(error => {
        // エラーは無視（統計更新の失敗はユーザーに影響しない）
    });
}

// 更新前の自動保存チェック
function checkAutoSaveBeforeSubmit(description) {
    try {
        const apiUrl = '<?= base_url('api/service_templates/check_auto_save') ?>';
        
        const formData = new FormData();
        formData.append('content', description);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            if (!text.trim()) {
                throw new Error('Empty response from server');
            }
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                throw new Error('Invalid JSON response: ' + text);
            }
            
            return data;
        })
        .then(data => {
            if (data.success && data.should_save) {
                // テンプレート保存ダイアログを表示
                showAutoSaveModalBeforeSubmit(description);
            } else {
                // 保存不要の場合は直接フォーム送信
                proceedWithFormSubmit();
            }
        })
        .catch(error => {
            // エラーの場合でもテンプレート保存を提案
            showAutoSaveModalBeforeSubmit(description);
        });
    } catch (error) {
        // 外側のエラーの場合もテンプレート保存を提案
        showAutoSaveModalBeforeSubmit(description);
    }
}

// 更新前のテンプレート保存モーダル表示
function showAutoSaveModalBeforeSubmit(content) {
    const templatePreview = document.getElementById('template-preview');
    const modal = document.getElementById('autoSaveModal');
    
    if (!templatePreview || !modal) {
        // モーダルが見つからない場合は直接送信
        proceedWithFormSubmit();
        return;
    }
    
    // プレビューに内容を設定
    templatePreview.textContent = content;
    
    // モーダルの状態を「更新前」に設定
    modal.setAttribute('data-context', 'before-submit');
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// フォーム送信を実際に実行
function proceedWithFormSubmit() {
    // フォーム送信フラグを立てる
    isSubmittingForm = true;
    
    // 送信ボタンを無効化して多重送信を防止
    const submitButton = document.querySelector('button[onclick="handleFormSubmission()"]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>処理中...';
    }
    
    // フォームを実際に送信
    const form = document.querySelector('form');
    if (form) {
        form.submit();
    }
}

// 自動保存モーダルを閉じる
function closeAutoSaveModal() {
    try {
        const modal = bootstrap.Modal.getInstance(document.getElementById('autoSaveModal'));
        if (modal) {
            modal.hide();
        }
    } catch (error) {
        // エラーは無視
    }
    
    // コンテキストをチェック
    const modalElement = document.getElementById('autoSaveModal');
    if (modalElement && modalElement.getAttribute('data-context') === 'before-submit') {
        modalElement.removeAttribute('data-context');
        
        // 「保存しない」で閉じられた場合はフォーム送信を続行
        setTimeout(() => {
            proceedWithFormSubmit();
        }, 100);
    }
}

// テンプレートとして保存
function saveAsTemplate() {
    const content = document.getElementById('description').value.trim();
    
    const formData = new FormData();
    formData.append('content', content);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url('api/service_templates/save') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('テンプレートを保存しました', 'success');
            closeAutoSaveModal();
            
            // 更新前のコンテキストの場合はフォーム送信を続行
            const modal = document.getElementById('autoSaveModal');
            if (modal.getAttribute('data-context') === 'before-submit') {
                setTimeout(() => {
                    proceedWithFormSubmit();
                }, 500);
            }
        } else {
            showMessage(data.error || 'テンプレートの保存に失敗しました', 'error');
        }
    })
    .catch(error => {
        showMessage('テンプレートの保存中にエラーが発生しました', 'error');
        
        // エラーが発生してもフォーム送信は続行
        const modal = document.getElementById('autoSaveModal');
        if (modal.getAttribute('data-context') === 'before-submit') {
            setTimeout(() => {
                proceedWithFormSubmit();
            }, 1000);
        }
    });
}

// ユーティリティ関数
function showTemplateError(message) {
    const systemContainer = document.getElementById('system-templates');
    const personalContainer = document.getElementById('personal-templates');
    const errorHtml = `<div class="text-danger text-center py-3">${message}</div>`;
    systemContainer.innerHTML = errorHtml;
    personalContainer.innerHTML = errorHtml;
}

function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    
    const messageHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${iconClass} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertAdjacentHTML('afterbegin', messageHtml);
    
    // 3秒後に自動で非表示
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 3000);
}


</script>