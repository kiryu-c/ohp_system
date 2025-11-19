<?php
// app/views/service_records/select_contract.php - 訪問種別対応版（隔月非訪問月対応）
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-play me-2"></i>役務開始 - 契約選択</h2>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>契約を選択して役務を開始してください</h5>
    </div>
    <div class="card-body">
        <?php if (empty($contracts)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                有効な契約がありません。管理者にお問い合わせください。
            </div>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">ダッシュボードに戻る</a>
        <?php else: ?>
            <!-- 進行中の役務がある場合の警告 -->
            <?php if (isset($activeService) && $activeService): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>進行中の役務があります</strong><br>
                    新しい役務を開始する前に、現在の役務を終了してください。
                    <div class="mt-2">
                        <a href="<?= base_url('service_records/active') ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit me-1"></i>進行中の役務を確認
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // 契約の訪問月判定を行う
            $currentDate = date('Y-m-d');
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('n');
            
            $availableContracts = [];
            $restrictedContracts = [];
            $unavailableContracts = [];
            
            foreach ($contracts as $contract) {
                // 契約が有効かどうかを判定
                $isContractActive = is_contract_active($contract, $currentYear, $currentMonth);
                $isVisitMonth = is_visit_month($contract, $currentYear, $currentMonth);
                $frequency = $contract['visit_frequency'] ?? 'monthly';
                
                // 契約の利用可能状況を判定
                if (!$isContractActive) {
                    $contract['unavailable_reason'] = '';
                    if (!empty($contract['start_date']) && $currentDate < $contract['start_date']) {
                        $contract['unavailable_reason'] = '契約開始前（開始日: ' . format_date($contract['start_date']) . '）';
                    } elseif (!empty($contract['end_date']) && $currentDate > $contract['end_date']) {
                        $contract['unavailable_reason'] = '契約終了済み（終了日: ' . format_date($contract['end_date']) . '）';
                    } else {
                        $contract['unavailable_reason'] = '契約が有効ではありません';
                    }
                    $unavailableContracts[] = $contract;
                } elseif ($frequency === 'bimonthly' && !$isVisitMonth) {
                    // 隔月契約の非訪問月は制限付きで利用可能
                    $contract['is_restricted'] = true;
                    $contract['restriction_reason'] = '隔月契約の非訪問月のため、定期訪問は利用できません';
                    $restrictedContracts[] = $contract;
                } else {
                    // 通常利用可能
                    $contract['is_restricted'] = false;
                    $availableContracts[] = $contract;
                }
            }
            ?>

            <!-- 通常利用可能な契約 -->
            <?php if (!empty($availableContracts)): ?>
                <div class="mb-4">
                    <h6 class="text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        利用可能な契約（<?= count($availableContracts) ?>件）
                    </h6>
                    <div class="row">
                        <?php foreach ($availableContracts as $contract): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 border-success <?= isset($activeService) && $activeService ? 'opacity-50' : '' ?>">
                                    <div class="card-body">
                                        <h6 class="card-title text-success">
                                            <i class="fas fa-building me-1"></i>
                                            <?= sanitize($contract['company_name']) ?>
                                        </h6>
                                        <div class="mb-2">
                                            <span class="badge bg-info">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= sanitize($contract['branch_name'] ?? '拠点不明') ?>
                                            </span>
                                            
                                            <!-- 訪問頻度を表示 -->
                                            <?php 
                                            $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
                                            $frequencyInfo = get_visit_frequency_info($visitFrequency);
                                            ?>
                                            <span class="badge bg-<?= $frequencyInfo['class'] ?> ms-1">
                                                <i class="<?= $frequencyInfo['icon'] ?> me-1"></i>
                                                <?= $frequencyInfo['label'] ?>
                                            </span>
                                        </div>
                                        
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php
                                                // 毎週契約の場合は祝日を考慮した月間時間を計算
                                                if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
                                                    require_once __DIR__ . '/../../core/helpers.php';
                                                    require_once __DIR__ . '/../../core/Database.php';
                                                    $db = Database::getInstance()->getConnection();
                                                    
                                                    $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                        $contract['id'], 
                                                        $contract['regular_visit_hours'], 
                                                        $currentYear, 
                                                        $currentMonth, 
                                                        $db
                                                    );
                                                ?>
                                                    <?php if (!is_null($contract['regular_visit_hours'])): ?>
                                                    <i class="fas fa-clock me-1"></i>定期訪問時間: <?= format_total_hours($monthlyHours) ?>/月
                                    （契約設定に基づく）<br>
                                                    <?php endif; ?>
                                                <?php } else { ?>
                                                    <?php if (!is_null($contract['regular_visit_hours'])): ?>
                                                    <i class="fas fa-clock me-1"></i>定期訪問時間: <?= format_total_hours($contract['regular_visit_hours']) ?>/月<br>
                                                    <?php endif; ?>
                                                <?php } ?>
                                                <i class="fas fa-calendar me-1"></i>契約開始: <?= format_date($contract['start_date']) ?>
                                                <?php if (!empty($contract['end_date'])): ?>
                                                    <br><i class="fas fa-calendar-times me-1"></i>契約終了: <?= format_date($contract['end_date']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                        
                                        <!-- 月間使用状況を表示 -->
                                        <?php if (!is_null($contract['regular_visit_hours'])): ?>
                                        <?php
                                        $monthlyUsage = check_monthly_regular_usage($contract['id'], $currentDate);
                                        $usagePercentage = $monthlyUsage['usage_percentage'];
                                        $remainingHours = $monthlyUsage['remaining_hours'];

                                        // 毎週契約の場合は祝日を考慮した正確な計算を行う
                                        if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
                                            // 祝日を考慮した今月の訪問回数を取得
                                            require_once __DIR__ . '/../../core/helpers.php';
                                            require_once __DIR__ . '/../../core/Database.php';
                                            $db = Database::getInstance()->getConnection();
                                            
                                            // 新しい関数を使用してcontract設定を考慮
                                            $monthlyLimit = calculate_weekly_monthly_hours_with_contract_settings(
                                                $contract['id'], 
                                                $contract['regular_visit_hours'], 
                                                $currentYear, 
                                                $currentMonth, 
                                                $db
                                            );
                                            
                                            // 使用率を再計算
                                            if ($monthlyLimit > 0) {
                                                $usagePercentage = ($monthlyUsage['current_used'] / $monthlyLimit) * 100;
                                                $remainingHours = max(0, $monthlyLimit - $monthlyUsage['current_used']);
                                            } else {
                                                $usagePercentage = 0;
                                                $remainingHours = 0;
                                            }
                                        }                                        
                                        ?>
                                        <div class="mb-3">
                                            <small class="text-muted">今月の定期訪問時間使用状況:</small>
                                            <div class="progress mb-1" style="height: 4px;">
                                                <div class="progress-bar <?= $usagePercentage >= 100 ? 'bg-danger' : ($usagePercentage >= 80 ? 'bg-warning' : 'bg-success') ?>" 
                                                     style="width: <?= min(100, $usagePercentage) ?>%"></div>
                                            </div>
                                            <small class="<?= $usagePercentage >= 100 ? 'text-danger' : ($usagePercentage >= 80 ? 'text-warning' : 'text-success') ?>">
                                                <?= number_format($usagePercentage, 1) ?>% 使用済み
                                                （残り <?= format_total_hours($remainingHours) ?>）
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($activeService) && $activeService): ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-ban me-1"></i>役務進行中のため開始不可
                                            </button>
                                        <?php else: ?>
                                            <!-- 役務種別選択ボタン -->
                                            <div class="d-grid gap-2">
                                                <?php
                                                // regular_visit_hoursがNULLの場合は-1を渡して、モーダル側で警告を非表示にする
                                                $modalRemainingHours = is_null($contract['regular_visit_hours']) ? -1 : $remainingHours;
                                                ?>
                                                <button type="button" class="btn btn-success" 
                                                        onclick="showServiceTypeModal(<?= $contract['id'] ?>, '<?= sanitize($contract['company_name']) ?>', '<?= sanitize($contract['branch_name'] ?? '拠点不明') ?>', <?= $modalRemainingHours ?>, false, <?= $contract['use_remote_consultation'] ?? 1 ?>, <?= $contract['use_document_creation'] ?? 0 ?>, '<?= $contract['visit_frequency'] ?? 'monthly' ?>')">
                                                    <i class="fas fa-play me-1"></i>役務開始
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 制限付き利用可能な契約（隔月契約の非訪問月） -->
            <?php if (!empty($restrictedContracts)): ?>
                <div class="mb-4">
                    <h6 class="text-warning">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        制限付き利用可能な契約（<?= count($restrictedContracts) ?>件）
                    </h6>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>隔月契約の非訪問月です</strong><br>
                        定期訪問はご利用いただけません。
                    </div>
                    <div class="row">
                        <?php foreach ($restrictedContracts as $contract): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 border-warning <?= isset($activeService) && $activeService ? 'opacity-50' : '' ?>">
                                    <div class="card-body">
                                        <h6 class="card-title text-warning">
                                            <i class="fas fa-building me-1"></i>
                                            <?= sanitize($contract['company_name']) ?>
                                        </h6>
                                        <div class="mb-2">
                                            <span class="badge bg-info">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= sanitize($contract['branch_name'] ?? '拠点不明') ?>
                                            </span>
                                            
                                            <!-- 隔月契約バッジ -->
                                            <span class="badge bg-warning ms-1">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                隔月契約（非訪問月）
                                            </span>
                                        </div>
                                        
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php
                                                // 毎週契約の場合は祝日を考慮した月間時間を計算
                                                if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
                                                    require_once __DIR__ . '/../../core/helpers.php';
                                                    require_once __DIR__ . '/../../core/Database.php';
                                                    $db = Database::getInstance()->getConnection();
                                                    
                                                    $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                        $contract['id'], 
                                                        $contract['regular_visit_hours'], 
                                                        $currentYear, 
                                                        $currentMonth, 
                                                        $db
                                                    );
                                                ?>
                                                    <?php if (!is_null($contract['regular_visit_hours'])): ?>
                                                    <i class="fas fa-clock me-1"></i>定期訪問時間: <?= format_total_hours($monthlyHours) ?>/月
                                    （契約設定に基づく）<br>
                                                    <?php endif; ?>
                                                <?php } else { ?>
                                                    <?php if (!is_null($contract['regular_visit_hours'])): ?>
                                                    <i class="fas fa-clock me-1"></i>定期訪問時間: <?= format_total_hours($contract['regular_visit_hours']) ?>/月<br>
                                                    <?php endif; ?>
                                                <?php } ?>
                                                <i class="fas fa-calendar me-1"></i>契約開始: <?= format_date($contract['start_date']) ?>
                                                <?php if (!empty($contract['end_date'])): ?>
                                                    <br><i class="fas fa-calendar-times me-1"></i>契約終了: <?= format_date($contract['end_date']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                        
                                        <!-- 利用可能役務の表示 -->
                                        <div class="mb-3">
                                            <small class="text-muted">今月利用可能な役務:</small>
                                            <div class="mt-1">
                                                <span class="badge bg-warning me-1">臨時訪問</span>
                                                <?php if (($contract['use_document_creation'] ?? 0) == 1): ?>
                                                <span class="badge bg-success me-1">書面作成</span>
                                                <?php endif; ?>
                                                <?php if (($contract['use_remote_consultation'] ?? 0) == 1): ?>
                                                <span class="badge bg-info">遠隔相談</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (isset($activeService) && $activeService): ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-ban me-1"></i>役務進行中のため開始不可
                                            </button>
                                        <?php else: ?>
                                            <!-- 制限付き役務種別選択ボタン -->
                                            <div class="d-grid gap-2">
                                                <?php
                                                // regular_visit_hoursがNULLの場合は-1を渡す（制限付き契約では通常0だが、NULLの場合を考慮）
                                                $modalRemainingHours = is_null($contract['regular_visit_hours']) ? -1 : 0;
                                                ?>
                                                <button type="button" class="btn btn-warning" 
                                                        onclick="showServiceTypeModal(<?= $contract['id'] ?>, '<?= sanitize($contract['company_name']) ?>', '<?= sanitize($contract['branch_name'] ?? '拠点不明') ?>', <?= $modalRemainingHours ?>, true, <?= $contract['use_remote_consultation'] ?? 1 ?>, <?= $contract['use_document_creation'] ?? 0 ?>, '<?= $contract['visit_frequency'] ?? 'monthly' ?>')">
                                                    <i class="fas fa-play me-1"></i>制限付き役務開始
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 利用不可能な契約 -->
            <?php if (!empty($unavailableContracts)): ?>
                <div class="mb-4">
                    <h6 class="text-muted">
                        <i class="fas fa-times-circle me-1"></i>
                        利用不可能な契約（<?= count($unavailableContracts) ?>件）
                        <button class="btn btn-sm btn-outline-secondary ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#unavailableContracts">
                            <i class="fas fa-eye me-1"></i>表示/非表示
                        </button>
                    </h6>
                    
                    <div class="collapse" id="unavailableContracts">
                        <div class="row">
                            <?php foreach ($unavailableContracts as $contract): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 border-secondary bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">
                                                <i class="fas fa-building me-1"></i>
                                                <?= sanitize($contract['company_name']) ?>
                                            </h6>
                                            <div class="mb-2">
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= sanitize($contract['branch_name'] ?? '拠点不明') ?>
                                                </span>
                                                
                                                <!-- 訪問頻度を表示 -->
                                                <?php 
                                                $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
                                                $frequencyInfo = get_visit_frequency_info($visitFrequency);
                                                ?>
                                                <span class="badge bg-secondary ms-1">
                                                    <i class="<?= $frequencyInfo['icon'] ?> me-1"></i>
                                                    <?= $frequencyInfo['label'] ?>
                                                </span>
                                            </div>
                                            
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <?php
                                                    // 毎週契約の場合は祝日を考慮した月間時間を計算
                                                    if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
                                                        require_once __DIR__ . '/../../core/helpers.php';
                                                        require_once __DIR__ . '/../../core/Database.php';
                                                        $db = Database::getInstance()->getConnection();
                                                        
                                                        $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                                                            $contract['id'], 
                                                            $contract['regular_visit_hours'], 
                                                            $currentYear, 
                                                            $currentMonth, 
                                                            $db
                                                        );
                                                    ?>
                                                        <i class="fas fa-clock me-1"></i>定期訪問時間: <?= format_total_hours($monthlyHours) ?>/月
                                    （契約設定に基づく）<br>
                                                    <?php } else { ?>
                                                        <i class="fas fa-clock me-1"></i>定期訪問時間: <?= format_total_hours($contract['regular_visit_hours']) ?>/月<br>
                                                    <?php } ?>
                                                    <i class="fas fa-calendar me-1"></i>契約開始: <?= format_date($contract['start_date']) ?>
                                                    <?php if (!empty($contract['end_date'])): ?>
                                                        <br><i class="fas fa-calendar-times me-1"></i>契約終了: <?= format_date($contract['end_date']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </p>
                                            
                                            <!-- 利用不可理由 -->
                                            <div class="alert alert-warning mb-3">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <strong>利用不可理由:</strong><br>
                                                    <?= $contract['unavailable_reason'] ?>
                                                </small>
                                            </div>
                                            
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-ban me-1"></i>利用不可
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 利用可能な契約がない場合の警告 -->
            <?php if (empty($availableContracts) && empty($restrictedContracts)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>現在利用可能な契約がありません</strong><br>
                    <?php if (!empty($unavailableContracts)): ?>
                        上記の理由により、今月は新しい役務を開始できません。
                    <?php else: ?>
                        管理者にお問い合わせください。
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- 役務種別・実施方法選択モーダル（非訪問月対応版） -->
<div class="modal fade" id="serviceTypeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">役務種別と実施方法を選択してください</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= base_url('service_records/start') ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="contract_id" id="modal_contract_id" value="">
                <input type="hidden" name="visit_type" id="modal_visit_type" value="">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>契約情報</strong><br>
                        <span id="modal_company_name"></span> - <span id="modal_branch_name"></span>
                    </div>
                    
                    <!-- 非訪問月制限の警告 -->
                    <div id="bimonthlyRestrictionWarning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>隔月契約の非訪問月のため、定期訪問は利用できません</strong>
                    </div>
                    
                    <!-- 定期訪問の月間使用状況警告 -->
                    <div id="monthlyUsageWarning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>月間定期訪問時間の使用状況にご注意ください</strong>
                        <div id="usageWarningContent"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">役務種別を選択 <span class="text-danger">*</span></label>
                        
                        <!-- 時間管理ありの役務 -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card service-type-card" id="regular-card" onclick="selectServiceType('regular')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                                            <h6>定期訪問</h6>
                                            <small class="text-muted">月間契約時間内での通常業務</small>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="radio" name="service_type" 
                                                       id="regular" value="regular" required>
                                                <label class="form-check-label fw-bold" for="regular">選択</label>
                                            </div>
                                            <!-- 制限表示用 -->
                                            <div id="regular-restriction" class="mt-2 d-none">
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-ban me-1"></i>非訪問月のため利用不可
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card service-type-card" onclick="selectServiceType('emergency')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                            <h6>臨時訪問</h6>
                                            <small class="text-muted">緊急対応や特別要請による訪問</small>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="radio" name="service_type" 
                                                       id="emergency" value="emergency" required>
                                                <label class="form-check-label fw-bold" for="emergency">選択</label>
                                            </div>
                                            <!-- 非訪問月でも利用可能表示 -->
                                            <div id="emergency-available" class="mt-2 d-none">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3" id="spot-card" style="display: none;">
                                    <div class="card service-type-card" onclick="selectServiceType('spot')">
                                        <div class="card-body text-center">
                                            <div class="mb-2">
                                                <i class="fas fa-clock fa-2x text-info"></i>
                                            </div>
                                            <h6>スポット</h6>
                                            <small class="text-muted">スポット契約での役務実施</small>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="radio" name="service_type" 
                                                       id="spot" value="spot" required>
                                                <label class="form-check-label fw-bold" for="spot">選択</label>
                                            </div>
                                            <div class="mt-2">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-check me-1"></i>スポット契約専用
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 時間管理なしの役務 -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3" id="document-card">
                                    <div class="card service-type-card no-time-service" onclick="selectServiceType('document')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
                                            <h6>書面作成</h6>
                                            <small class="text-muted">意見書・報告書等の書面作成業務</small>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="radio" name="service_type" 
                                                       id="document" value="document" required>
                                                <label class="form-check-label fw-bold" for="document">選択</label>
                                            </div>
                                            <!-- 非訪問月でも利用可能表示 -->
                                            <div id="document-available" class="mt-2 d-none">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3" id="remote_consultation-card">
                                    <div class="card service-type-card no-time-service" onclick="selectServiceType('remote_consultation')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-envelope fa-2x text-info mb-2"></i>
                                            <h6>遠隔相談</h6>
                                            <small class="text-muted">メールによる相談対応</small>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="radio" name="service_type" 
                                                       id="remote_consultation" value="remote_consultation" required>
                                                <label class="form-check-label fw-bold" for="remote_consultation">選択</label>
                                            </div>
                                            <!-- 非訪問月でも利用可能表示 -->
                                            <div id="remote_consultation-available" class="mt-2 d-none">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3" id="other-card">
                                    <div class="card service-type-card no-time-service" onclick="selectServiceType('other')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-plus fa-2x text-dark mb-2"></i>
                                            <h6>その他</h6>
                                            <small class="text-muted">その他の業務（請求金額を直接入力）</small>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="radio" name="service_type" 
                                                       id="other" value="other" required>
                                                <label class="form-check-label fw-bold" for="other">選択</label>
                                            </div>
                                            <!-- 非訪問月でも利用可能表示 -->
                                            <div id="other-available" class="mt-2 d-none">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 実施方法選択（時間管理ありの役務のみ表示） -->
                    <div id="visitTypeSelection" class="mb-4 d-none">
                        <label class="form-label">実施方法を選択 <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card visit-type-card" onclick="selectVisitType('visit')">
                                    <div class="card-body text-center">
                                        <i class="fas fa-walking fa-3x text-primary mb-3"></i>
                                        <h5>訪問実施</h5>
                                        <p class="text-muted mb-3">現地への物理的な訪問による実施</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="visit_type_radio" 
                                                   id="visit" value="visit">
                                            <label class="form-check-label fw-bold" for="visit">選択</label>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-success">
                                                <i class="fas fa-train me-1"></i>交通費登録可能
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card visit-type-card" onclick="selectVisitType('online')">
                                    <div class="card-body text-center">
                                        <i class="fas fa-laptop fa-3x text-info mb-3"></i>
                                        <h5>オンライン実施</h5>
                                        <p class="text-muted mb-3">リモートでの実施（オンライン会議など）</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="visit_type_radio" 
                                                   id="online" value="online">
                                            <label class="form-check-label fw-bold" for="online">選択</label>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-times me-1"></i>交通費登録不要
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 実施方法別の詳細説明 -->
                        <div class="alert alert-light border">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">
                                        <i class="fas fa-walking me-1"></i>訪問実施の場合
                                    </h6>
                                    <ul class="mb-0 small">
                                        <li>現地での面談、巡視、健康相談等</li>
                                        <li>交通費の登録が可能です</li>
                                        <li>移動時間は含まず、実際の役務時間のみ記録</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-info">
                                        <i class="fas fa-laptop me-1"></i>オンライン実施の場合
                                    </h6>
                                    <ul class="mb-0 small">
                                        <li>オンライン会議、リモート相談等</li>
                                        <li>交通費の登録は不要です</li>
                                        <li>実際のオンライン接続時間を記録</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 選択された内容の詳細説明 -->
                    <div id="serviceTypeDescription" class="alert alert-secondary d-none">
                        <div id="descriptionContent"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="button" class="btn btn-success" id="startServiceBtn" onclick="handleServiceStart()" disabled>
                        <i class="fas fa-play me-1"></i><span id="startButtonText">役務開始</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.service-type-card,
.visit-type-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e9ecef;
    height: 100%;
}

.service-type-card:hover,
.visit-type-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.service-type-card.selected,
.visit-type-card.selected {
    border-color: #007bff;
    background-color: #f8f9fa;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* 制限された役務カードのスタイル */
.service-type-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #f8f9fa;
    border-color: #dc3545;
}

.service-type-card.disabled:hover {
    transform: none;
    box-shadow: none;
    border-color: #dc3545;
}

.service-type-card.disabled input {
    cursor: not-allowed;
}

/* 時間なし役務用のスタイル */
.service-type-card.no-time-service {
    border-color: #6c757d;
    background-color: #f8f9fa;
}

.service-type-card.no-time-service:hover {
    border-color: #495057;
    background-color: #e9ecef;
}

.service-type-card.no-time-service.selected {
    border-color: #495057;
    background-color: #e9ecef;
    box-shadow: 0 0 0 0.2rem rgba(108,117,125,.25);
}

/* 訪問種別カードのスタイル */
.visit-type-card {
    border: 2px solid #dee2e6;
    min-height: 220px;
}

.visit-type-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.visit-type-card.selected {
    border-color: #007bff;
    background-color: #f8f9fa;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.visit-type-card .fa-3x {
    font-size: 2.5rem;
}

.badge {
    font-size: 0.75em;
    padding: 0.5em 0.75em;
}

/* モーダルサイズ調整 */
.modal-xl {
    max-width: 1200px;
}

/* 制限付き契約のカードスタイル */
.card.border-warning {
    border-color: #ffc107 !important;
    border-width: 2px;
}

/* 制限バッジのスタイル */
.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

/* 実施方法選択エリアのアニメーション */
#visitTypeSelection {
    transition: all 0.3s ease-in-out;
}

#visitTypeSelection.show {
    opacity: 1;
    transform: translateY(0);
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .modal-dialog {
        margin: 1rem;
    }
    
    .modal-xl {
        max-width: none;
    }
    
    .service-type-card .card-body,
    .visit-type-card .card-body {
        padding: 1rem;
    }
    
    .service-type-card h6,
    .visit-type-card h5 {
        font-size: 1rem;
    }
    
    .service-type-card small {
        font-size: 0.8rem;
    }
    
    .visit-type-card {
        min-height: 180px;
    }
    
    .visit-type-card .fa-3x {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .col-md-6.col-lg-4 {
        margin-bottom: 1rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .visit-type-card {
        min-height: 160px;
    }
    
    .visit-type-card .fa-3x {
        font-size: 1.8rem;
    }
    
    .visit-type-card h5 {
        font-size: 1.1rem;
    }
    
    .visit-type-card p {
        font-size: 0.9rem;
    }
}

/* 高解像度ディスプレイ対応 */
@media (min-width: 1200px) {
    .service-type-card,
    .visit-type-card {
        margin-bottom: 1.5rem;
    }
    
    .visit-type-card .fa-3x {
        font-size: 3rem;
    }
}

/* アクセシビリティ向上 */
.service-type-card:focus,
.visit-type-card:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* ダークモード対応（必要に応じて） */
@media (prefers-color-scheme: dark) {
    .service-type-card,
    .visit-type-card {
        background-color: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
    
    .service-type-card.selected,
    .visit-type-card.selected {
        background-color: #4a5568;
        border-color: #63b3ed;
    }
}

/* 利用不可能な契約のスタイル */
.card.bg-light {
    background-color: #f8f9fa !important;
}

.card.border-success {
    border-color: #198754 !important;
    border-width: 2px;
}

.card.border-secondary {
    border-color: #6c757d !important;
    border-width: 2px;
}

/* 月間使用状況のプログレスバー */
.progress {
    background-color: #e9ecef;
}

/* 警告エリアのスタイル */
.alert.alert-warning.bg-opacity-25 {
    background-color: rgba(255, 193, 7, 0.25) !important;
    border-color: #ffc107;
}

/* 利用不可能契約のコラプス */
.collapse .row {
    margin-top: 1rem;
}

/* バッジのスタイル調整 */
.badge.bg-secondary {
    background-color: #6c757d !important;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .col-md-6.col-lg-4 {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .progress {
        height: 6px;
    }
}
</style>

<script>
// 役務種別の詳細説明データ（訪問種別対応版）
const serviceTypeDescriptions = {
    regular: {
        title: '定期訪問が選択されました',
        content: `
            <strong>特徴:</strong>
            <ul class="mb-2">
                <li>月間契約時間内での通常業務として記録されます</li>
                <li>開始・終了時刻を記録し、自動で役務時間を計算します</li>
                <li>選択した実施方法（訪問/オンライン）で記録されます</li>
                <li>訪問実施の場合は交通費の登録が可能です</li>
            </ul>
            <strong>注意事項:</strong> 役務開始後は他の役務を開始できません。
        `,
        buttonText: '定期訪問を開始',
        requiresVisitType: true
    },
    emergency: {
        title: '臨時訪問が選択されました',
        content: `
            <strong>特徴:</strong>
            <ul class="mb-2">
                <li>緊急対応や特別要請による訪問として記録されます</li>
                <li>開始・終了時刻を記録し、15分単位で切り上げされます</li>
                <li>月間契約時間に関係なく記録されます</li>
                <li>選択した実施方法（訪問/オンライン）で記録されます</li>
                <li>訪問実施の場合は交通費の登録が可能です</li>
            </ul>
            <strong>注意事項:</strong> 役務開始後は他の役務を開始できません。
        `,
        buttonText: '臨時訪問を開始',
        requiresVisitType: true
    },
    spot: {
        title: 'スポットが選択されました',
        content: `
            <strong>特徴:</strong>
            <ul class="mb-2">
                <li>スポット契約での役務実施として記録されます</li>
                <li>開始・終了時刻を記録し、自動で役務時間を計算します</li>
                <li>選択した実施方法（訪問/オンライン）で記録されます</li>
                <li>訪問実施の場合は交通費の登録が可能です</li>
            </ul>
            <strong>注意事項:</strong> 役務開始後は他の役務を開始できません。
        `,
        buttonText: 'スポット役務を開始',
        requiresVisitType: true
    },
    document: {
        title: '書面作成が選択されました',
        content: `
            <strong>特徴:</strong>
            <ul class="mb-2">
                <li>意見書・報告書等の書面作成業務として記録されます</li>
                <li>開始・終了時刻は記録せず、業務内容の詳細な記載が重要です</li>
                <li>時間管理の対象外のため、他の役務と並行して実施可能です</li>
                <li>実施方法の選択は不要で、交通費の登録はできません</li>
            </ul>
            <strong>注意事項:</strong> 業務内容の詳細な記載が必須項目です。
        `,
        buttonText: '書面作成を記録',
        requiresVisitType: false
    },
    remote_consultation: {
        title: '遠隔相談が選択されました',
        content: `
            <strong>特徴:</strong>
            <ul class="mb-2">
                <li>メールによる相談対応として記録されます</li>
                <li>開始・終了時刻は記録せず、業務内容の詳細な記載が重要です</li>
                <li>時間管理の対象外のため、他の役務と並行して実施可能です</li>
                <li>実施方法の選択は不要で、交通費の登録はできません</li>
            </ul>
            <strong>注意事項:</strong> 相談内容や対応の詳細な記載が必須項目です。
        `,
        buttonText: '遠隔相談を記録',
        requiresVisitType: false
    },
    other: {
        title: 'その他が選択されました',
        content: `
            <strong>特徴:</strong>
            <ul class="mb-2">
                <li>その他の業務として記録されます</li>
                <li>開始・終了時刻は記録せず、業務内容の詳細な記載が重要です</li>
                <li>請求金額を直接入力します</li>
                <li>時間管理の対象外のため、他の役務と並行して実施可能です</li>
                <li>実施方法の選択は不要で、交通費の登録はできません</li>
            </ul>
            <strong>注意事項:</strong> 業務内容の詳細な記載と請求金額の入力が必須項目です。
        `,
        buttonText: 'その他の役務を記録',
        requiresVisitType: false
    }
};

// 訪問種別の詳細説明データ
const visitTypeDescriptions = {
    visit: {
        title: '訪問実施',
        details: '現地での面談、職場巡視、健康相談等を実施。交通費の登録が可能。',
        icon: 'fas fa-walking text-primary',
        badge: '交通費登録可能'
    },
    online: {
        title: 'オンライン実施',
        details: 'オンライン会議システム等を使用したリモート実施。交通費の登録は不要。',
        icon: 'fas fa-laptop text-info',
        badge: '交通費登録不要'
    }
};

let selectedServiceType = null;
let selectedVisitType = null;
let currentRemainingHours = 0;
let isRestrictedContract = false;
let isSpotContract = false;

function showServiceTypeModal(contractId, companyName, branchName, remainingHours, isRestricted, useRemoteConsultation = 1, useDocumentCreation = 1, visitFrequency = 'monthly') {
    document.getElementById('modal_contract_id').value = contractId;
    document.getElementById('modal_company_name').textContent = companyName;
    document.getElementById('modal_branch_name').textContent = branchName;
    currentRemainingHours = remainingHours || 0;
    isRestrictedContract = isRestricted || false;
    isSpotContract = (visitFrequency === 'spot');
    
    // フォームをリセット
    resetModalForm();
    
    // スポット契約の制限モードを設定（隔月制限より優先）
    if (isSpotContract) {
        setupSpotContractMode();
    } else {
        // 制限モードの設定（隔月契約の非訪問月）
        setupRestrictedMode(isRestricted);
    }
    
    // 契約設定に基づく役務の表示/非表示を設定
    setupServiceTypeVisibility(useRemoteConsultation, useDocumentCreation);
    
    // 月間使用状況警告の更新
    updateMonthlyUsageWarning(remainingHours || 0);
    
    const modal = new bootstrap.Modal(document.getElementById('serviceTypeModal'));
    modal.show();
}

function setupSpotContractMode() {
    // 全ての役務種別カードと入力を取得
    const regularCard = document.getElementById('regular-card');
    const regularInput = document.getElementById('regular');
    const emergencyCard = document.querySelector('.service-type-card[onclick*="emergency"]');
    const emergencyInput = document.getElementById('emergency');
    const spotCard = document.getElementById('spot-card');
    const spotInput = document.getElementById('spot');
    
    // スポット契約の警告を表示
    const bimonthlyWarning = document.getElementById('bimonthlyRestrictionWarning');
    if (bimonthlyWarning) {
        bimonthlyWarning.classList.remove('d-none');
        bimonthlyWarning.classList.remove('alert-warning');
        bimonthlyWarning.classList.add('alert-info');
        bimonthlyWarning.innerHTML = `
            <i class="fas fa-clock me-2"></i>
            <strong>スポット契約</strong><br>
            スポット契約では、役務種別は「スポット」のみ選択可能です。
        `;
    }
    
    // 定期訪問を無効化・非表示
    if (regularCard && regularInput) {
        regularCard.style.display = 'none';
        regularInput.disabled = true;
    }
    
    // 臨時訪問を無効化・非表示
    if (emergencyCard && emergencyInput) {
        emergencyCard.style.display = 'none';
        emergencyInput.disabled = true;
    }
    
    // スポットカードを表示・有効化
    if (spotCard && spotInput) {
        spotCard.style.display = '';
        spotInput.disabled = false;
    }
    
    // 書面作成、遠隔相談、その他を非表示
    const documentCard = document.getElementById('document-card');
    const documentInput = document.getElementById('document');
    const remoteCard = document.getElementById('remote_consultation-card');
    const remoteInput = document.getElementById('remote_consultation');
    const otherCard = document.getElementById('other-card');
    const otherInput = document.getElementById('other');
    
    if (documentCard && documentInput) {
        documentCard.style.display = 'none';
        documentInput.disabled = true;
    }
    
    if (remoteCard && remoteInput) {
        remoteCard.style.display = 'none';
        remoteInput.disabled = true;
    }
    
    if (otherCard && otherInput) {
        otherCard.style.display = 'none';
        otherInput.disabled = true;
    }
}

function setupRestrictedMode(isRestricted) {
    const regularCard = document.getElementById('regular-card');
    const regularInput = document.getElementById('regular');
    const regularRestriction = document.getElementById('regular-restriction');
    const bimonthlyWarning = document.getElementById('bimonthlyRestrictionWarning');
    
    // 非訪問月でも利用可能表示
    const emergencyAvailable = document.getElementById('emergency-available');
    const documentAvailable = document.getElementById('document-available');
    const remoteAvailable = document.getElementById('remote_consultation-available');
    const otherAvailable = document.getElementById('other-available');
    
    if (isRestricted) {
        // 定期訪問を無効化
        regularCard.classList.add('disabled');
        regularInput.disabled = true;
        regularRestriction.classList.remove('d-none');
        bimonthlyWarning.classList.remove('d-none');
        
        // 利用可能役務にバッジ表示
        emergencyAvailable.classList.remove('d-none');
        documentAvailable.classList.remove('d-none');
        remoteAvailable.classList.remove('d-none');
        if (otherAvailable) {
            otherAvailable.classList.remove('d-none');
        }
    } else {
        // 通常モード
        regularCard.classList.remove('disabled');
        regularInput.disabled = false;
        regularRestriction.classList.add('d-none');
        bimonthlyWarning.classList.add('d-none');
        
        // バッジを非表示
        emergencyAvailable.classList.add('d-none');
        documentAvailable.classList.add('d-none');
        remoteAvailable.classList.add('d-none');
        if (otherAvailable) {
            otherAvailable.classList.add('d-none');
        }
    }
}

function setupServiceTypeVisibility(useRemoteConsultation, useDocumentCreation) {
    console.log('setupServiceTypeVisibility called:', {
        useRemoteConsultation: useRemoteConsultation,
        useDocumentCreation: useDocumentCreation
    });
    
    // 遠隔相談の表示/非表示
    const remoteConsultationCard = document.getElementById('remote_consultation-card');
    const remoteConsultationInput = document.getElementById('remote_consultation');
    
    console.log('Remote consultation elements:', {
        card: remoteConsultationCard,
        input: remoteConsultationInput
    });
    
    if (remoteConsultationCard) {
        if (useRemoteConsultation == 1 || useRemoteConsultation === '1' || useRemoteConsultation === true) {
            remoteConsultationCard.style.display = '';
            if (remoteConsultationInput) {
                remoteConsultationInput.disabled = false;
            }
            console.log('Remote consultation: SHOWN');
        } else {
            remoteConsultationCard.style.display = 'none';
            if (remoteConsultationInput) {
                remoteConsultationInput.disabled = true;
                remoteConsultationInput.checked = false;
            }
            console.log('Remote consultation: HIDDEN');
        }
    }
    
    // 書面作成の表示/非表示
    const documentCard = document.getElementById('document-card');
    const documentInput = document.getElementById('document');
    
    console.log('Document creation elements:', {
        card: documentCard,
        input: documentInput
    });
    
    if (documentCard) {
        if (useDocumentCreation == 1 || useDocumentCreation === '1' || useDocumentCreation === true) {
            documentCard.style.display = '';
            if (documentInput) {
                documentInput.disabled = false;
            }
            console.log('Document creation: SHOWN');
        } else {
            documentCard.style.display = 'none';
            if (documentInput) {
                documentInput.disabled = true;
                documentInput.checked = false;
            }
            console.log('Document creation: HIDDEN');
        }
    }
}

function updateMonthlyUsageWarning(remainingHours) {
    const warningDiv = document.getElementById('monthlyUsageWarning');
    const warningContent = document.getElementById('usageWarningContent');
    
    // 制限付き契約の場合は月間使用状況警告を表示しない
    if (isRestrictedContract) {
        warningDiv.classList.add('d-none');
        return;
    }
    
    // regular_visit_hoursがNULLの場合（remainingHours == -1）は警告を表示しない
    if (remainingHours < 0) {
        warningDiv.classList.add('d-none');
        return;
    }
    
    if (remainingHours <= 0) {
        warningContent.innerHTML = `
            <p class="mb-1">月間定期訪問時間を既に使い切っています。</p>
        `;
        warningDiv.classList.remove('d-none');
    } else if (remainingHours <= 2) {
        warningContent.innerHTML = `
            <p class="mb-1">月間定期訪問時間の残りが ${formatTotalHours(remainingHours)} と少なくなっています。</p>
        `;
        warningDiv.classList.remove('d-none');
    } else {
        warningDiv.classList.add('d-none');
    }
}

function formatTotalHours(hours) {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (m > 0) {
        return h + '時間' + m + '分';
    } else {
        return h + '時間';
    }
}

function resetModalForm() {
    // ラジオボタンをリセット
    const serviceRadios = document.querySelectorAll('input[name="service_type"]');
    const visitRadios = document.querySelectorAll('input[name="visit_type_radio"]');
    
    serviceRadios.forEach(radio => radio.checked = false);
    visitRadios.forEach(radio => radio.checked = false);
    
    // カードの選択状態をリセット
    const serviceCards = document.querySelectorAll('.service-type-card');
    const visitCards = document.querySelectorAll('.visit-type-card');
    
    serviceCards.forEach(card => card.classList.remove('selected'));
    visitCards.forEach(card => card.classList.remove('selected'));
    
    // 全ての役務カードを表示・有効化
    const regularCard = document.getElementById('regular-card');
    const emergencyCard = document.querySelector('.service-type-card[onclick*="emergency"]');
    const spotCard = document.getElementById('spot-card');
    const documentCard = document.getElementById('document-card');
    const remoteCard = document.getElementById('remote_consultation-card');
    
    if (regularCard) {
        regularCard.style.display = '';
        regularCard.classList.remove('disabled');
        document.getElementById('regular').disabled = false;
    }
    if (emergencyCard) {
        emergencyCard.style.display = '';
        emergencyCard.classList.remove('disabled');
        document.getElementById('emergency').disabled = false;
    }
    if (spotCard) {
        spotCard.style.display = 'none';  // デフォルトは非表示
        document.getElementById('spot').disabled = true;
    }
    if (documentCard) {
        documentCard.style.display = '';
        document.getElementById('document').disabled = false;
    }
    if (remoteCard) {
        remoteCard.style.display = '';
        document.getElementById('remote_consultation').disabled = false;
    }
    
    // 制限表示をリセット
    const regularRestriction = document.getElementById('regular-restriction');
    if (regularRestriction) {
        regularRestriction.classList.add('d-none');
    }
    
    // 警告をリセット
    const bimonthlyWarning = document.getElementById('bimonthlyRestrictionWarning');
    if (bimonthlyWarning) {
        bimonthlyWarning.classList.add('d-none');
        bimonthlyWarning.classList.remove('alert-info');
        bimonthlyWarning.classList.add('alert-warning');
    }
    
    // 開始ボタンを無効化・リセット
    const startBtn = document.getElementById('startServiceBtn');
    startBtn.disabled = true;
    document.getElementById('startButtonText').textContent = '役務開始';
    
    // エリアを非表示
    document.getElementById('visitTypeSelection').classList.add('d-none');
    document.getElementById('serviceTypeDescription').classList.add('d-none');
    
    // hiddenフィールドをリセット
    document.getElementById('modal_visit_type').value = '';
    
    // 選択状態をリセット
    selectedServiceType = null;
    selectedVisitType = null;
}

function selectServiceType(type) {
    // スポット契約で定期訪問または臨時訪問が選択された場合は阻止
    if (isSpotContract && (type === 'regular' || type === 'emergency')) {
        alert('スポット契約では、定期訪問と臨時訪問は利用できません。\n「スポット」のみ選択可能です。');
        return;
    }
    
    // 制限モードで定期訪問が選択された場合は阻止
    if (isRestrictedContract && type === 'regular') {
        alert('隔月契約の非訪問月のため、定期訪問は利用できません。');
        return;
    }
    
    selectedServiceType = type;
    
    // すべてのサービスタイプカードの選択状態をリセット
    const cards = document.querySelectorAll('.service-type-card');
    cards.forEach(card => card.classList.remove('selected'));
    
    // 対応するラジオボタンをチェック
    const radio = document.getElementById(type);
    radio.checked = true;
    
    // 選択されたカードをハイライト
    radio.closest('.service-type-card').classList.add('selected');
    
    // 説明文を更新
    updateServiceTypeDescription(type);
    
    // 実施方法選択の表示/非表示
    const visitTypeSelection = document.getElementById('visitTypeSelection');
    if (serviceTypeDescriptions[type].requiresVisitType) {
        visitTypeSelection.classList.remove('d-none');
        // 実施方法が選択されるまで開始ボタンは無効のまま
        updateStartButton();
    } else {
        visitTypeSelection.classList.add('d-none');
        // 実施方法選択不要な場合はすぐに開始可能
        selectedVisitType = null;
        document.getElementById('modal_visit_type').value = '';
        updateStartButton();
    }
    
    // 訪問種別の選択をリセット
    resetVisitTypeSelection();
}

function selectVisitType(type) {
    selectedVisitType = type;
    
    // すべての訪問種別カードの選択状態をリセット
    const cards = document.querySelectorAll('.visit-type-card');
    cards.forEach(card => card.classList.remove('selected'));
    
    // 対応するラジオボタンをチェック
    const radio = document.getElementById(type);
    radio.checked = true;
    
    // hiddenフィールドに値を設定
    document.getElementById('modal_visit_type').value = type;
    
    // 選択されたカードをハイライト
    radio.closest('.visit-type-card').classList.add('selected');
    
    // 開始ボタンの状態を更新
    updateStartButton();
    
    // 説明文を更新
    updateServiceTypeDescription(selectedServiceType);
}

function resetVisitTypeSelection() {
    selectedVisitType = null;
    document.getElementById('modal_visit_type').value = '';
    
    const visitRadios = document.querySelectorAll('input[name="visit_type_radio"]');
    const visitCards = document.querySelectorAll('.visit-type-card');
    
    visitRadios.forEach(radio => radio.checked = false);
    visitCards.forEach(card => card.classList.remove('selected'));
}

function updateServiceTypeDescription(serviceType) {
    if (!serviceTypeDescriptions[serviceType]) return;
    
    const descArea = document.getElementById('serviceTypeDescription');
    const descContent = document.getElementById('descriptionContent');
    
    let content = `
        <h6><i class="fas fa-info-circle me-2"></i>${serviceTypeDescriptions[serviceType].title}</h6>
        ${serviceTypeDescriptions[serviceType].content}
    `;
    
    // 実施方法が選択されている場合は追加情報を表示
    if (selectedVisitType && serviceTypeDescriptions[serviceType].requiresVisitType) {
        const visitInfo = visitTypeDescriptions[selectedVisitType];
        content += `
            <div class="mt-3 p-3 border rounded bg-light">
                <h6><i class="${visitInfo.icon} me-2"></i>${visitInfo.title}で実施</h6>
                <p class="mb-1">${visitInfo.details}</p>
                <span class="badge bg-info">${visitInfo.badge}</span>
            </div>
        `;
    }
    
    descContent.innerHTML = content;
    descArea.classList.remove('d-none');
}

function updateStartButton() {
    const startBtn = document.getElementById('startServiceBtn');
    const startButtonText = document.getElementById('startButtonText');
    const form = startBtn.closest('form');
    
    // 役務種別が選択されているかチェック
    if (!selectedServiceType) {
        startBtn.disabled = true;
        startButtonText.textContent = '役務開始';
        return;
    }
    
    const serviceInfo = serviceTypeDescriptions[selectedServiceType];
    
    // 書面作成、遠隔相談、その他の場合は役務追加画面に遷移
    if (['document', 'remote_consultation', 'other'].includes(selectedServiceType)) {
        form.action = '<?= base_url('service_records/create') ?>';
    } else {
        // 時間管理ありの役務種別は役務開始
        form.action = '<?= base_url('service_records/start') ?>';
    }
    
    // 実施方法が必要な役務種別の場合
    if (serviceInfo.requiresVisitType) {
        if (selectedVisitType) {
            startBtn.disabled = false;
            const visitInfo = visitTypeDescriptions[selectedVisitType];
            startButtonText.textContent = `${serviceInfo.buttonText}（${visitInfo.title}）`;
        } else {
            startBtn.disabled = true;
            startButtonText.textContent = '実施方法を選択してください';
        }
    } else {
        // 実施方法選択不要な場合
        startBtn.disabled = false;
        startButtonText.textContent = serviceInfo.buttonText;
    }
}

function handleServiceStart() {
    const contractId = document.getElementById('modal_contract_id').value;
    
    // 書面作成、遠隔相談、その他の場合はGETパラメータで役務追加画面に遷移
    if (['document', 'remote_consultation', 'other'].includes(selectedServiceType)) {
        const url = '<?= base_url('service_records/create') ?>?contract_id=' + contractId + '&service_type=' + selectedServiceType;
        window.location.href = url;
    } else {
        // 時間管理ありの役務種別は通常のフォーム送信
        const form = document.querySelector('#serviceTypeModal form');
        form.submit();
    }
}

// ラジオボタンの変更を監視
document.addEventListener('DOMContentLoaded', function() {
    const serviceRadios = document.querySelectorAll('input[name="service_type"]');
    const visitRadios = document.querySelectorAll('input[name="visit_type_radio"]');
    
    serviceRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                selectServiceType(this.value);
            }
        });
    });
    
    visitRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                selectVisitType(this.value);
            }
        });
    });
    
    // モーダルが閉じられた時のリセット処理
    const modal = document.getElementById('serviceTypeModal');
    modal.addEventListener('hidden.bs.modal', function() {
        resetModalForm();
    });
});
</script>