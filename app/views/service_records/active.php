<?php
// app/views/service_records/active.php - 実施方法対応版 + テンプレート機能 + 毎週契約対応

// 契約の月間上限時間を計算する関数（訪問頻度対応版）
function calculate_monthly_limit_by_frequency($contract, $year, $month) {
    $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
    $regularVisitHours = $contract['regular_visit_hours'] ?? 0;
    
    switch ($visitFrequency) {
        case 'weekly':
            // 毎週契約の場合：新しい関数を使用してexclude_holidaysとnon_visit_daysを考慮
            require_once __DIR__ . '/../../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 古い関数を新しい関数に変更
            $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                $contract['id'], 
                $regularVisitHours, 
                $year, 
                $month, 
                $db
            );
            
            return [
                'monthly_limit' => $monthlyHours,
                'is_visit_month' => true,
                'calculation_note' => "毎週契約：{$year}年{$month}月は{$monthlyHours}時間が上限です（契約設定に基づく）"
            ];
            
        case 'bimonthly':
            // 隔月契約の場合
            $isVisitMonth = is_visit_month($contract, $year, $month);
            return [
                'monthly_limit' => $isVisitMonth ? $regularVisitHours : 0,
                'is_visit_month' => $isVisitMonth,
                'calculation_note' => $isVisitMonth 
                    ? "隔月訪問月：{$regularVisitHours}時間が上限です"
                    : '隔月非訪問月：定期訪問は実施されません'
            ];
            
        case 'monthly':
        default:
            // 毎月契約の場合
            return [
                'monthly_limit' => $regularVisitHours,
                'is_visit_month' => true,
                'calculation_note' => "毎月{$regularVisitHours}時間が上限です"
            ];
    }
}

// 役務種別の拡張定義
$serviceTypeInfo = [
    'regular' => ['icon' => 'calendar-check', 'class' => 'primary', 'label' => '定期訪問'],
    'emergency' => ['icon' => 'exclamation-triangle', 'class' => 'warning', 'label' => '臨時訪問'],
    'extension' => ['icon' => 'clock', 'class' => 'info', 'label' => '定期延長'],
    'document' => ['icon' => 'file-alt', 'class' => 'primary', 'label' => '書面作成'],
    'remote_consultation' => ['icon' => 'envelope', 'class' => 'secondary', 'label' => '遠隔相談'],
    'spot' => ['icon' => 'clock', 'class' => 'info', 'label' => 'スポット']
];

// 実施方法の定義
$visitTypeInfo = [
    'visit' => ['icon' => 'walking', 'class' => 'primary', 'label' => '訪問'],
    'online' => ['icon' => 'laptop', 'class' => 'info', 'label' => 'オンライン']
];

// 現在の役務種別が時間管理を必要とするかチェック
$currentServiceType = $activeService['service_type'] ?? 'regular';
$currentVisitType = $activeService['visit_type'] ?? 'visit';
$requiresTimeTracking = in_array($currentServiceType, ['regular', 'emergency', 'extension', 'spot']);
$requiresVisitType = requires_visit_type_selection($currentServiceType);

// 契約の月間上限時間を計算（訪問頻度対応）
$currentYear = date('Y');
$currentMonth = date('n');
$monthlyLimitInfo = calculate_monthly_limit_by_frequency($contract, $currentYear, $currentMonth);
$monthlyLimit = $monthlyLimitInfo['monthly_limit'];
$isVisitMonth = $monthlyLimitInfo['is_visit_month'];
$calculationNote = $monthlyLimitInfo['calculation_note'];

// 時間管理が不要な役務種別の場合はエラー処理
if (!$requiresTimeTracking) {
    // このページは時間管理が必要な役務のみが対象
    ?>
    <div class="container-fluid py-4">
        <div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>このページは利用できません</h4>
            <p class="mb-0">
                <?= $serviceTypeInfo[$currentServiceType]['label'] ?>は進行中管理の対象外です。
                <a href="<?= base_url('service_records/create') ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-plus me-1"></i>新規作成
                </a>
            </p>
        </div>
    </div>
    <?php
    return;
}

// 訪問月でない場合の処理（隔月契約など）
if (!$isVisitMonth && $currentServiceType === 'regular') {
    ?>
    <div class="container-fluid py-4">
        <div class="alert alert-info">
            <h4><i class="fas fa-info-circle me-2"></i>定期訪問対象外の月です</h4>
            <p class="mb-1"><?= $calculationNote ?></p>
            <p class="mb-0">
                臨時訪問は実施可能です。
                <a href="<?= base_url('service_records/create') ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-plus me-1"></i>新規作成
                </a>
            </p>
        </div>
    </div>
    <?php
    return;
}

$serviceType = $serviceTypeInfo[$currentServiceType];
$visitType = $visitTypeInfo[$currentVisitType] ?? $visitTypeInfo['visit'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-play-circle me-2 text-success"></i>進行中の役務</h2>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
    </a>
</div>

<!-- 進行中役務の情報 -->
<div class="card border-success mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-info-circle me-2"></i>役務情報
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
                        <th>役務種別:</th>
                        <td>
                            <span class="badge bg-<?= $serviceType['class'] ?>">
                                <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                <?= $serviceType['label'] ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>実施方法:</th>
                        <td>
                            <span class="badge bg-<?= $visitType['class'] ?>">
                                <i class="fas fa-<?= $visitType['icon'] ?> me-1"></i>
                                <?= $visitType['label'] ?>
                            </span>
                            
                            <!-- 交通費対象の表示 -->
                            <div class="mt-1">
                                <?php if ($currentVisitType === 'visit'): ?>
                                    <small class="text-success">
                                        <i class="fas fa-train me-1"></i>交通費対象
                                    </small>
                                <?php else: ?>
                                    <small class="text-info">
                                        <i class="fas fa-wifi me-1"></i>交通費対象外
                                    </small>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="120">開始日時:</th>
                        <td>
                            <i class="fas fa-calendar me-1 text-success"></i>
                            <strong><?= format_date($activeService['service_date']) ?></strong>
                            <span class="ms-2"><?= date('H:i', strtotime($activeService['start_time'])) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>経過時間:</th>
                        <td>
                            <i class="fas fa-clock me-1 text-warning"></i>
                            <span id="elapsed-time" class="badge bg-warning text-dark fs-6">計算中...</span>
                        </td>
                    </tr>
                    <tr>
                        <th>月間上限時間:</th>
                        <td>
                            <?php if ($contract['visit_frequency'] === 'spot'): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-infinity me-1"></i>上限なし
                                </span>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        スポット契約（単発実施）
                                    </small>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-hourglass-half me-1 text-info"></i>
                                <?= format_total_hours($monthlyLimit) ?>
                                <?php if ($contract['visit_frequency'] === 'weekly'): ?>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-week me-1"></i>
                                            毎週契約（契約設定に基づく実際の訪問回数で計算）
                                        </small>
                                    </div>
                                <?php elseif ($contract['visit_frequency'] === 'bimonthly'): ?>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            隔月契約（<?= $isVisitMonth ? '訪問月' : '非訪問月' ?>）
                                        </small>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($contract['visit_frequency'] === 'weekly'): ?>
        <div class="mt-2">
            <div class="alert alert-info mb-0">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    <?= $calculationNote ?>
                </small>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 今月の役務統計 -->
<?php
// 今月の役務統計を取得（実施方法別対応版）
$monthlyStats = [];
$visitTypeStats = ['visit_hours' => 0, 'visit_count' => 0, 'online_hours' => 0, 'online_count' => 0];

if (isset($serviceModel)) {
    try {
        require_once __DIR__ . '/../../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $statsSql = "SELECT 
                        SUM(CASE WHEN service_type = 'regular' THEN service_hours ELSE 0 END) as regular_hours,
                        SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                        SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                        SUM(CASE WHEN service_type = 'spot' THEN service_hours ELSE 0 END) as spot_hours,
                        COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                        COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                        SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as total_hours,
                        
                        -- 実施方法別統計
                        SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') AND visit_type = 'visit' THEN service_hours ELSE 0 END) as visit_hours,
                        COUNT(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') AND visit_type = 'visit' THEN 1 END) as visit_count,
                        SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') AND visit_type = 'online' THEN service_hours ELSE 0 END) as online_hours,
                        COUNT(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') AND visit_type = 'online' THEN 1 END) as online_count
                        
                     FROM service_records 
                     WHERE contract_id = :contract_id 
                     AND YEAR(service_date) = :year 
                     AND MONTH(service_date) = :month
                     AND id != :current_id";
        
        $stmt = $db->prepare($statsSql);
        $stmt->execute([
            'contract_id' => $activeService['contract_id'],
            'year' => $currentYear,
            'month' => $currentMonth,
            'current_id' => $activeService['id']
        ]);
        
        $result = $stmt->fetch();
        $monthlyStats = $result ?: [
            'regular_hours' => 0,
            'emergency_hours' => 0,
            'extension_hours' => 0,
            'spot_hours' => 0,
            'document_count' => 0,
            'remote_consultation_count' => 0,
            'total_hours' => 0,
            'visit_hours' => 0,
            'visit_count' => 0,
            'online_hours' => 0,
            'online_count' => 0
        ];
        
        // 実施方法別統計を抽出
        $visitTypeStats = [
            'visit_hours' => $monthlyStats['visit_hours'],
            'visit_count' => $monthlyStats['visit_count'],
            'online_hours' => $monthlyStats['online_hours'],
            'online_count' => $monthlyStats['online_count']
        ];
        
    } catch (Exception $e) {
        $monthlyStats = [
            'regular_hours' => 0,
            'emergency_hours' => 0,
            'extension_hours' => 0,
            'spot_hours' => 0,
            'document_count' => 0,
            'remote_consultation_count' => 0,
            'total_hours' => 0
        ];
        $visitTypeStats = ['visit_hours' => 0, 'visit_count' => 0, 'online_hours' => 0, 'online_count' => 0];
    }
}

// 現在進行中の役務の経過時間を計算
$currentElapsedHours = 0;
if ($activeService) {
    $startDateTime = new DateTime($activeService['service_date'] . ' ' . $activeService['start_time']);
    $now = new DateTime();
    $interval = $now->diff($startDateTime);
    $currentElapsedHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
}

// 現在進行中の定期訪問時間を加算
$currentRegularHours = 0;
if ($currentServiceType === 'regular') {
    $currentRegularHours = $currentElapsedHours;
}

// 残り時間の計算（現在進行中の時間も含める）
$totalUsedRegular = ($monthlyStats['regular_hours'] ?? 0) + $currentRegularHours;
$remainingRegular = $monthlyLimit - $totalUsedRegular;
?>

<!-- 動的警告表示用のコンテナ（JavaScriptで制御） -->
<div id="dynamic-warning-container"></div>

<!-- 役務終了フォーム -->
<div class="card border-danger">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="fas fa-stop me-2"></i>役務終了
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>注意:</strong> 役務を終了すると、現在の時刻で役務が完了となります。
            
            <!-- 実施方法に関する注意 -->
            <br><span class="text-info">
                <i class="fas fa-<?= $visitType['icon'] ?> me-1"></i>
                実施方法: <?= $visitType['label'] ?>
                <?php if ($currentVisitType === 'visit'): ?>
                    （役務終了後に交通費の登録が可能です）
                <?php else: ?>
                    （交通費の登録は不要です）
                <?php endif; ?>
            </span>
        </div>
        
        <form method="POST" action="<?= base_url("service_records/{$activeService['id']}/end") ?>" onsubmit="return confirmEnd()" id="endServiceForm">
            <!-- CSRFトークンを追加 -->
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="description" class="form-label">
                        役務内容
                        <?php if ($currentServiceType === 'spot'): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    <div>
                        <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="showTemplateModal()">
                            <i class="fas fa-list me-1"></i>テンプレート選択
                        </button>
                    </div>
                </div>
                <textarea class="form-control" id="description" name="description" rows="4"
                          <?php if ($currentServiceType === 'spot'): ?>required<?php endif; ?>
                          placeholder="本日の役務内容を詳しく記載してください&#13;&#10;例：&#13;&#10;・健康診断結果の確認と指導&#13;&#10;・職場巡視の実施&#13;&#10;・労働者からの健康相談対応&#13;&#10;&#13;&#10;【実施方法】<?= $visitType['label'] ?>での実施"></textarea>
                <div class="form-text">
                    <?php if ($currentServiceType === 'spot'): ?>
                        <span class="text-danger">※スポット役務では役務内容の記載が必須です。</span>実施した業務内容を詳しく記載してください。実施方法（<?= $visitType['label'] ?>）での具体的な内容も含めてください。<br>
                    <?php else: ?>
                        実施した業務内容を記載してください（任意）。実施方法（<?= $visitType['label'] ?>）での具体的な内容も含めてください。<br>
                    <?php endif; ?>
                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                </div>
            </div>
            
            <!-- 延長理由（定期訪問で月間上限超過時のみ表示） -->
            <div class="mb-3" id="overtime-reason-group" style="display: none;">
                <label for="overtime_reason" class="form-label" id="overtime-reason-label">
                    <i class="fas fa-exclamation-triangle me-1 text-warning"></i>延長理由
                    <span class="text-danger">*（月間上限時間超過のため必須）</span>
                </label>
                <textarea class="form-control" id="overtime_reason" name="overtime_reason" rows="2" 
                          placeholder="月間上限時間を超過する理由を記載してください&#13;&#10;例：緊急の健康相談対応、詳細な職場巡視が必要だった、予想以上に時間がかかった等"></textarea>
                <div class="form-text">
                    定期訪問で契約時間を超過する場合は理由の記載が必要です。<br>
                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-stop me-2"></i>役務終了
                </button>
            </div>
        </form>
    </div>
</div>

<!-- テンプレート選択モーダル -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">
                    <i class="fas fa-list me-2"></i>テンプレート選択
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- カテゴリ選択タブ -->
                <ul class="nav nav-pills mb-3" id="template-category-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="category-all-tab" data-bs-toggle="pill" 
                                data-bs-target="#category-all" type="button" role="tab" 
                                aria-controls="category-all" aria-selected="true">
                            <i class="fas fa-th-list me-1"></i>すべて
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="category-system-tab" data-bs-toggle="pill" 
                                data-bs-target="#category-system" type="button" role="tab" 
                                aria-controls="category-system" aria-selected="false">
                            <i class="fas fa-cog me-1"></i>システム
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="category-personal-tab" data-bs-toggle="pill" 
                                data-bs-target="#category-personal" type="button" role="tab" 
                                aria-controls="category-personal" aria-selected="false">
                            <i class="fas fa-user me-1"></i>個人
                        </button>
                    </li>
                </ul>
                
                <!-- 検索フィルター -->
                <div class="input-group mb-3">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="template-search" 
                           placeholder="テンプレート名や内容で検索...">
                    <button class="btn btn-outline-secondary" type="button" onclick="clearTemplateSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- テンプレート一覧 -->
                <div class="tab-content" id="template-category-content">
                    <div class="tab-pane fade show active" id="category-all" role="tabpanel" 
                         aria-labelledby="category-all-tab">
                        <div id="all-templates" class="template-list">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">読み込み中...</span>
                                </div>
                                <div class="mt-2">テンプレートを読み込んでいます...</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="category-system" role="tabpanel" 
                         aria-labelledby="category-system-tab">
                        <div id="system-templates" class="template-list">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">読み込み中...</span>
                                </div>
                                <div class="mt-2">テンプレートを読み込んでいます...</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="category-personal" role="tabpanel" 
                         aria-labelledby="category-personal-tab">
                        <div id="personal-templates" class="template-list">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">読み込み中...</span>
                                </div>
                                <div class="mt-2">テンプレートを読み込んでいます...</div>
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

<!-- 自動保存確認モーダル -->
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

<!-- 下書き保存の説明と時間分割機能の説明 -->
<div class="alert alert-info mt-3">
    <h6><i class="fas fa-info-circle me-2"></i>役務終了について</h6>
    <ul class="mb-0">
        <?php if ($currentServiceType === 'spot'): ?>
        <li><strong>役務内容</strong>は必須項目です。スポット役務では詳細な業務内容の記載が必要です</li>
        <?php else: ?>
        <li><strong>役務内容</strong>は任意項目です。記載いただくと承認時の参考となります</li>
        <?php endif; ?>
        <li><strong>テンプレート機能</strong>を使用して、よく使用する内容を簡単に入力できます</li>
        
        <li><strong>交通費:</strong> 
            <?php if ($currentVisitType === 'visit'): ?>
                訪問実施のため、役務終了後に交通費の登録が可能です
            <?php else: ?>
                オンライン実施のため、交通費の登録は不要です
            <?php endif; ?>
        </li>
    </ul>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.progress {
    background-color: #e9ecef;
}

.badge.fs-6 {
    font-size: 1rem !important;
}

/* 延長警告エリアのスタイル */
.alert-warning .bg-white {
    border: 1px solid #ffc107;
}

/* 経過時間の動的更新用スタイル */
#elapsed-time {
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

/* 延長理由エリアのスタイル */
#overtime-reason-group {
    transition: all 0.3s ease;
}

#overtime-reason-group.show {
    display: block !important;
}

/* 分割予想表示のスタイル */
#estimated-extension {
    font-weight: bold;
}

/* テンプレート関連のスタイル */
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
}

.template-item.selected {
    border-color: #0d6efd;
    background-color: #e7f3ff;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.1);
}

.template-item h6 {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    color: #495057;
    font-weight: 600;
}

.template-item .template-description {
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    color: #6c757d;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.template-item .template-meta {
    font-size: 0.75rem;
    color: #6c757d;
    display: flex;
    justify-content: between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.template-type-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

.template-stats {
    font-size: 0.7rem;
    color: #6c757d;
}

/* 検索とフィルター */
#template-search {
    border-radius: 0.375rem;
}

/* ローディング状態 */
.template-loading {
    text-align: center;
    padding: 2rem 1rem;
    color: #6c757d;
}

.template-loading .spinner-border-sm {
    width: 1.5rem;
    height: 1.5rem;
}

/* 空状態 */
.template-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: #6c757d;
}

.template-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* タブのスタイリング */
.nav-pills .nav-link {
    color: #6c757d;
    border-radius: 0.5rem;
}

.nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: white;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .alert .row {
        flex-direction: column;
    }
    
    .alert .col-md-4 {
        margin-top: 1rem;
    }
    
    .template-item {
        padding: 0.5rem;
    }
    
    .modal-dialog.modal-lg {
        margin: 1rem;
    }
    
    .nav-pills {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    
    .nav-pills .nav-item {
        flex-shrink: 0;
    }
}

@media (max-width: 576px) {
    .badge {
        font-size: 0.7rem;
    }
    
    .template-item h6 {
        font-size: 0.9rem;
    }
    
    .template-item .template-description {
        font-size: 0.8rem;
    }
}
</style>

<script>
// フォーム送信フラグ
let isSubmittingForm = false;

// 現在の役務種別
const currentServiceType = '<?= $currentServiceType ?>';
const currentVisitType = '<?= $currentVisitType ?>';

// 契約情報（毎週契約対応）
const contractHours = <?= $monthlyLimit ?>;
const monthlyUsed = <?= $monthlyStats['regular_hours'] ?? 0 ?>;
const visitFrequency = '<?= $contract['visit_frequency'] ?? 'monthly' ?>';
const calculationNote = '<?= addslashes($calculationNote) ?>';

// テンプレート関連の変数
let allTemplates = [];
let filteredTemplates = [];
let selectedTemplate = null;

// 月間上限超過の警告表示
function updateOvertimeWarning(elapsedHours) {
    if (currentServiceType !== 'regular') return;
    
    const totalUsed = monthlyUsed + elapsedHours;
    const remaining = contractHours - totalUsed;
    
    // 既存の警告要素を取得
    let warningElement = document.getElementById('dynamic-overtime-warning');
    const warningContainer = document.getElementById('dynamic-warning-container');
    
    if (remaining < 0) {
        // 超過している場合：警告を表示
        if (!warningElement && warningContainer) {
            const overHours = Math.abs(remaining);
            const hours = Math.floor(overHours);
            const minutes = Math.round((overHours - hours) * 60);
            
            const warningHtml = `
                <div class="alert alert-warning mb-4" id="dynamic-overtime-warning">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>月間上限時間を超過しています
                    </h6>
                    <p class="mb-1">${calculationNote}</p>
                    <p class="mb-2">
                        現在の予想超過時間: <strong class="text-danger">${hours}時間${minutes}分</strong>
                    </p>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        役務終了時に延長理由の入力が必要です。
                    </small>
                </div>
            `;
            warningContainer.innerHTML = warningHtml;
        } else if (warningElement) {
            // 超過時間の表示を更新
            const overHours = Math.abs(remaining);
            const hours = Math.floor(overHours);
            const minutes = Math.round((overHours - hours) * 60);
            const timeDisplay = warningElement.querySelector('.text-danger');
            if (timeDisplay) {
                timeDisplay.textContent = `${hours}時間${minutes}分`;
            }
        }
    } else {
        // 超過していない場合：警告を非表示
        if (warningElement) {
            warningElement.remove();
        }
    }
}

// 延長理由フィールドの動的制御
function checkDynamicOvertime(elapsedHours) {
    if (currentServiceType !== 'regular') return;
    
    const totalUsed = monthlyUsed + elapsedHours;
    const remaining = contractHours - totalUsed;
    
    const overtimeReasonGroup = document.getElementById('overtime-reason-group');
    const overtimeField = document.getElementById('overtime_reason');
    
    if (remaining < 0) {
        // 超過している場合：延長理由フィールドを表示
        if (overtimeReasonGroup && overtimeReasonGroup.style.display === 'none') {
            overtimeReasonGroup.style.display = 'block';
        }
        if (overtimeField) {
            overtimeField.required = true;
        }
    } else {
        // 超過していない場合：延長理由フィールドを非表示
        if (overtimeReasonGroup && overtimeReasonGroup.style.display !== 'none') {
            overtimeReasonGroup.style.display = 'none';
        }
        if (overtimeField) {
            overtimeField.required = false;
        }
    }
}

// 経過時間の計算と表示
function updateElapsedTime() {
    const startDateTime = new Date('<?= $activeService['service_date'] ?> <?= $activeService['start_time'] ?>');
    const now = new Date();

    const elapsed = Math.floor((now - startDateTime) / 1000); // 秒単位
    
    const hours = Math.floor(elapsed / 3600);
    const minutes = Math.floor((elapsed % 3600) / 60);
    const seconds = elapsed % 60;
    
    const elapsedText = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    const elapsedElement = document.getElementById('elapsed-time');
    if (elapsedElement) {
        elapsedElement.textContent = elapsedText;
        
        // 時間に応じて色を変更
        if (hours >= 8) {
            elapsedElement.className = 'badge bg-danger fs-6';
        } else if (hours >= 6) {
            elapsedElement.className = 'badge bg-warning text-dark fs-6';
        } else {
            elapsedElement.className = 'badge bg-success fs-6';
        }
    }
    
    // 進行中表示も更新
    const currentElapsedDisplay = document.getElementById('current-elapsed-display');
    if (currentElapsedDisplay) {
        const hoursMinutes = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        currentElapsedDisplay.textContent = hoursMinutes;
    }
    
    // 役務種別に応じた時間計算
    const elapsedHours = hours + (minutes / 60);
    
    if (currentServiceType === 'regular') {
        // 動的な警告表示を更新
        updateOvertimeWarning(elapsedHours);
        
        // 動的な延長チェックを呼び出し
        checkDynamicOvertime(elapsedHours);
        
    }
}

// 延長時間の予想計算（定期訪問用）
// テンプレートモーダルを表示
function showTemplateModal() {
    loadTemplates();
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    modal.show();
}

// テンプレート一覧を読み込み
function loadTemplates() {
    // ローディング表示
    showTemplateLoading();
    
    fetch('<?= base_url('api/service_templates') ?>')
        .then(response => response.json())
        .then(data => {
            console.log('Template response:', data); // デバッグ用
            if (data.success) {
                // データ構造を確認してallTemplatesを正しく設定
                if (Array.isArray(data.data)) {
                    // data.dataが配列の場合
                    allTemplates = data.data;
                } else if (data.data && typeof data.data === 'object') {
                    // data.dataがオブジェクトの場合
                    allTemplates = [...(data.data.system || []), ...(data.data.personal || [])];
                } else {
                    allTemplates = [];
                }
                
                filteredTemplates = [...allTemplates];
                console.log('All templates loaded:', allTemplates); // デバッグ用
                displayTemplates();
            } else {
                console.error('テンプレート読み込みエラー:', data.error);
                showTemplateError('テンプレートの読み込みに失敗しました。');
            }
        })
        .catch(error => {
            console.error('テンプレート読み込みエラー:', error);
            showTemplateError('テンプレートの読み込み中にエラーが発生しました。');
        });
}

// ローディング表示
function showTemplateLoading() {
    const containers = ['all-templates', 'system-templates', 'personal-templates'];
    const loadingHtml = `
        <div class="text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">読み込み中...</span>
            </div>
            <div class="mt-2">テンプレートを読み込んでいます...</div>
        </div>
    `;
    
    containers.forEach(id => {
        const container = document.getElementById(id);
        if (container) {
            container.innerHTML = loadingHtml;
        }
    });
}

// テンプレート一覧を表示
function displayTemplates() {
    const allContainer = document.getElementById('all-templates');
    const systemContainer = document.getElementById('system-templates');
    const personalContainer = document.getElementById('personal-templates');
    
    // 全て
    if (filteredTemplates.length > 0) {
        allContainer.innerHTML = filteredTemplates.map(template => 
            createTemplateItem(template)
        ).join('');
    } else {
        allContainer.innerHTML = createEmptyState('テンプレートが見つかりません');
    }
    
    // システムテンプレート（is_system_template = 1）
    const systemTemplates = filteredTemplates.filter(t => t.is_system_template == 1 || t.is_system_template === true);
    if (systemTemplates.length > 0) {
        systemContainer.innerHTML = systemTemplates.map(template => 
            createTemplateItem(template)
        ).join('');
    } else {
        systemContainer.innerHTML = createEmptyState('システムテンプレートがありません');
    }
    
    // 個人テンプレート（is_system_template = 0 or null）
    const personalTemplates = filteredTemplates.filter(t => !t.is_system_template || t.is_system_template == 0);
    if (personalTemplates.length > 0) {
        personalContainer.innerHTML = personalTemplates.map(template => 
            createTemplateItem(template)
        ).join('');
    } else {
        personalContainer.innerHTML = createEmptyState('個人テンプレートがありません');
    }
}

// テンプレートアイテムのHTML作成
function createTemplateItem(template) {
    // データの存在チェックとデフォルト値設定 - 正しいDB構造に対応
    const content = template.content || template.description || '内容なし';
    const isSystemTemplate = template.is_system_template == 1 || template.is_system_template === true;
    const usageCount = parseInt(template.usage_count) || 0;
    const createdAt = template.created_at || template.date || new Date().toISOString();
    
    // is_system_templateフラグに基づいてタイプを判定
    const typeClass = isSystemTemplate ? 'primary' : 'success';
    const typeLabel = isSystemTemplate ? 'システム' : '個人';
    
    // プレビュー用のテキストを生成（最初の50文字程度）
    const preview = getPreviewText(content, 80);
    
    return `
        <div class="template-item" onclick="selectTemplate(${template.id})" data-template-id="${template.id}">
            <div class="template-description">${escapeHtml(preview)}</div>
            <div class="template-meta">
                <span class="template-type-badge badge bg-${typeClass}">${typeLabel}</span>
            </div>
        </div>
    `;
}

// 空状態の表示
function createEmptyState(message) {
    return `
        <div class="template-empty">
            <i class="fas fa-inbox"></i>
            <div>${message}</div>
        </div>
    `;
}

// テンプレート選択
function selectTemplate(templateId) {
    const template = allTemplates.find(t => t.id === templateId);
    
    if (template) {
        // テンプレート内容をテキストエリアに設定 - contentフィールドを使用
        const descriptionField = document.getElementById('description');
        const currentContent = descriptionField.value.trim();
        
        // 既存の内容がある場合は「、」で繋げて追加、ない場合はそのまま設定
        if (currentContent) {
            descriptionField.value = currentContent + '、' + template.content;
        } else {
            descriptionField.value = template.content;
        }

        // 使用統計を更新
        updateTemplateUsage(templateId);
        
        // モーダルを閉じる
        const modal = bootstrap.Modal.getInstance(document.getElementById('templateModal'));
        modal.hide();
        
        // 成功メッセージ
        showMessage('テンプレートを適用しました', 'success');
        
        // フォーカスをテキストエリアに移動
        setTimeout(() => {
            document.getElementById('description').focus();
        }, 500);
    }
}

// テンプレート使用統計更新
function updateTemplateUsage(templateId) {
    const formData = new FormData();
    formData.append('template_id', templateId);
    formData.append('csrf_token', '<?= csrf_token() ?>');
    
    fetch('<?= base_url('api/service_templates/use') ?>', {
        method: 'POST',
        body: formData
    }).catch(error => {
        console.error('使用統計更新エラー:', error);
    });
}

// テンプレート検索
function filterTemplates() {
    const searchTerm = document.getElementById('template-search').value.toLowerCase().trim();
    
    if (searchTerm === '') {
        filteredTemplates = [...allTemplates];
    } else {
        filteredTemplates = allTemplates.filter(template => {
            const content = template.content || '';
            return content.toLowerCase().includes(searchTerm);
        });
    }
    
    displayTemplates();
}

// プレビューテキスト生成関数を追加
function getPreviewText(content, maxLength = 50) {
    const preview = content.replace(/\s+/g, ' ').trim();
    
    if (preview.length <= maxLength) {
        return preview;
    }
    
    return preview.substring(0, maxLength) + '...';
}

// 検索クリア
function clearTemplateSearch() {
    document.getElementById('template-search').value = '';
    filterTemplates();
}

// 役務終了前の自動保存チェック
function checkAutoSaveBeforeEnd(description) {
    console.log('Checking auto save before service end...');
    
    const formData = new FormData();
    formData.append('content', description); // 'description' を 'content' に変更
    formData.append('csrf_token', '<?= csrf_token() ?>');
    
    fetch('<?= base_url('api/service_templates/check_auto_save') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text().then(text => {
        if (!text.trim()) {
            throw new Error('Empty response from server');
        }
        try {
            return JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid JSON response: ' + text);
        }
    }))
    .then(data => {
        console.log('Auto save check response:', data);
        if (data.success && data.should_save) {
            // テンプレート保存ダイアログを表示
            showAutoSaveModalBeforeEnd(data.suggested_name);
        } else {
            // 保存不要の場合は直接役務終了
            proceedWithServiceEnd();
        }
    })
    .catch(error => {
        console.error('自動保存チェックエラー:', error);
        // エラーの場合は役務終了を続行
        proceedWithServiceEnd();
    });
}

// 役務終了前のテンプレート保存モーダル表示
function showAutoSaveModalBeforeEnd(suggestedName) {
    const templatePreview = document.getElementById('template-preview');
    const modal = document.getElementById('autoSaveModal');
    
    // プレビューに役務内容を設定
    const description = document.getElementById('description').value.trim();
    templatePreview.textContent = description;
    
    // モーダルの状態を「役務終了前」に設定
    modal.setAttribute('data-context', 'before-end');
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// 役務終了を実際に実行
function proceedWithServiceEnd() {
    console.log('Proceeding with service end...');
    
    // フォーム送信フラグを立てる
    isSubmittingForm = true;
    
    // beforeunloadイベントを無効化
    window.removeEventListener('beforeunload', beforeUnloadHandler);
    
    // 送信ボタンを無効化して多重送信を防止
    const submitButton = document.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>処理中...';
    }
    
    // フォームを実際に送信
    const form = document.getElementById('endServiceForm');
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
        console.error('Modal close error:', error);
    }
    
    // コンテキストをリセット
    const modalElement = document.getElementById('autoSaveModal');
    if (modalElement && modalElement.getAttribute('data-context') === 'before-end') {
        modalElement.removeAttribute('data-context');
        
        // 「保存しない」で閉じられた場合は役務終了を続行
        setTimeout(() => {
            proceedWithServiceEnd();
        }, 100);
    }
}

// テンプレートとして保存
function saveAsTemplate() {
    const description = document.getElementById('description').value.trim();
    
    const formData = new FormData();
    formData.append('content', description); // 'description' を 'content' に変更
    formData.append('csrf_token', '<?= csrf_token() ?>');
    
    fetch('<?= base_url('api/service_templates/save') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('テンプレートを保存しました', 'success');
            closeAutoSaveModal();
            
            // 役務終了前のコンテキストの場合は役務終了を続行
            const modal = document.getElementById('autoSaveModal');
            if (modal.getAttribute('data-context') === 'before-end') {
                setTimeout(() => {
                    proceedWithServiceEnd();
                }, 500); // メッセージ表示の時間を確保
            }
        } else {
            showMessage(data.error || 'テンプレートの保存に失敗しました', 'error');
        }
    })
    .catch(error => {
        console.error('テンプレート保存エラー:', error);
        showMessage('テンプレートの保存中にエラーが発生しました', 'error');
    });
}

// エラー表示
function showTemplateError(message) {
    const containers = ['all-templates', 'system-templates', 'personal-templates'];
    const errorHtml = `<div class="text-danger text-center py-3">${message}</div>`;
    
    containers.forEach(id => {
        const container = document.getElementById(id);
        if (container) {
            container.innerHTML = errorHtml;
        }
    });
}

// メッセージ表示
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

// HTML エスケープ
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 日付フォーマット
function formatDate(dateString) {
    const date = new Date(dateString);
    return `${date.getFullYear()}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')}`;
}

// 役務終了の確認
function confirmEnd() {
    const description = document.getElementById('description').value.trim();
    const overtimeReason = document.getElementById('overtime_reason').value.trim();
    
    // スポット役務の場合は役務内容が必須
    if (currentServiceType === 'spot' && !description) {
        alert('スポット役務では役務内容の入力が必須です。');
        return false;
    }
    
    // 動的に延長状況をチェック
    const startDateTime = new Date('<?= $activeService['service_date'] ?> <?= $activeService['start_time'] ?>');
    const now = new Date();
    const elapsed = Math.floor((now - startDateTime) / 1000);
    const hours = Math.floor(elapsed / 3600);
    const minutes = Math.floor((elapsed % 3600) / 60);
    const elapsedHours = hours + (minutes / 60);
    
    const totalUsed = monthlyUsed + elapsedHours;
    const remaining = contractHours - totalUsed;
    
    // 定期訪問で動的に延長状況をチェック
    if (currentServiceType === 'regular' && remaining < 0 && !overtimeReason) {
        alert('月間上限時間を延長するため、延長理由の入力が必要です。');
        return false;
    }
    
    const elapsedElement = document.getElementById('elapsed-time');
    const currentTime = elapsedElement ? elapsedElement.textContent : '不明';
    
    // 役務種別に応じた確認メッセージ
    let confirmMessage = `役務を終了しますか？\n\n経過時間: ${currentTime}\n役務種別: <?= $serviceType['label'] ?>\n実施方法: <?= $visitType['label'] ?>`;
    
    // 契約の上限時間情報を追加
    if (visitFrequency === 'spot') {
        confirmMessage += '\n\n※ スポット契約：月間上限時間の制約はありません';
    } else if (visitFrequency === 'weekly') {
        confirmMessage += '\n\n※ 毎週契約：祝日を除く実際の訪問回数で月間上限が計算されます';
    } else if (visitFrequency === 'bimonthly') {
        confirmMessage += '\n\n※ 隔月契約：訪問月のみ上限時間が適用されます';
    }
    
    // 実施方法に応じた注意事項
    if (currentVisitType === 'visit') {
        confirmMessage += '\n\n※ 役務終了後に交通費の登録が可能です';
    } else {
        confirmMessage += '\n\n※ オンライン実施のため交通費の登録は不要です';
    }
    
    confirmMessage += '\n\n終了後は内容の変更ができません。';
    
    const confirmed = confirm(confirmMessage);
    
    if (confirmed) {
        // 役務終了前にテンプレート保存をチェック
        if (description && description.length >= 3) {
            checkAutoSaveBeforeEnd(description);
            return false; // フォーム送信を一旦停止
        } else {
            proceedWithServiceEnd();
        }
    }
    
    return false; // 常にfalseを返してフォーム送信を制御
}

// beforeunloadハンドラーを関数として定義
function beforeUnloadHandler(e) {
    // フォーム送信中は警告を出さない
    if (isSubmittingForm) {
        return undefined;
    }
    
    const description = document.getElementById('description').value.trim();
    const overtimeReason = document.getElementById('overtime_reason').value.trim();
    
    if (description || overtimeReason) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
}

// 1秒ごとに更新
setInterval(updateElapsedTime, 1000);
updateElapsedTime(); // 初回実行

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // 検索機能の初期化
    const searchInput = document.getElementById('template-search');
    if (searchInput) {
        searchInput.addEventListener('input', filterTemplates);
    }
    
    // フォーム送信エラー時の復旧処理
    const form = document.getElementById('endServiceForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // 送信エラー時のタイムアウト処理
            setTimeout(function() {
                if (!isSubmittingForm) {
                    const submitButton = document.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-stop me-2"></i>役務終了';
                    }
                }
            }, 10000); // 10秒後にボタンを復活
        });
    }
});

// ページを離れる前の確認
window.addEventListener('beforeunload', beforeUnloadHandler);

// ページの可視性変更時の処理（ブラウザタブ切り替え対応）
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible' && !isSubmittingForm) {
        // ページが再表示された時に経過時間を更新（確認ダイアログは表示しない）
        updateElapsedTime();
    }
});
</script>