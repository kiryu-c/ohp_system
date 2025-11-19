<?php
// app/views/service_records/select_service_type.php - 訪問種別対応版（隔月制限対応）
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-clipboard-list me-2"></i>役務種別・実施方法選択</h2>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
    </a>
</div>

<?php
// 契約の有効性と訪問月の判定
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

// 非訪問月で定期役務が制限されるかの判定
$isRegularRestricted = ($frequency === 'bimonthly' && !$isVisitMonth && $isContractValid);

// スポット契約かどうかの判定
$isSpotContract = ($frequency === 'spot');
?>

<!-- スポット契約の制限警告 -->
<?php if ($isSpotContract): ?>
<div class="alert alert-warning mb-4">
    <h6><i class="fas fa-clock me-2"></i>スポット契約です</h6>
    <p class="mb-2">
        <i class="fas fa-exclamation-triangle me-2"></i>
        この契約はスポット契約のため、<strong>「スポット」役務種別</strong>のみ選択可能です。
    </p>
</div>
<?php endif; ?>

<!-- 非訪問月の制限警告 -->
<?php if ($isRegularRestricted): ?>
<div class="alert alert-info mb-4">
    <h6><i class="fas fa-info-circle me-2"></i>隔月契約の非訪問月です</h6>
    <p class="mb-2">
        現在は隔月契約の非訪問月のため、以下の役務のみ利用可能です：
    </p>
    <ul class="mb-0">
        <li><strong>臨時訪問</strong>：緊急対応や特別要請による訪問</li>
        <?php if (($contract['use_document_creation'] ?? 0) == 1): ?>
        <li><strong>書面作成</strong>：意見書・報告書等の書面作成業務</li>
        <?php endif; ?>
        <?php if (($contract['use_remote_consultation'] ?? 0) == 1): ?>
        <li><strong>遠隔相談</strong>：メールによる相談対応</li>
        <?php endif; ?>
        <li><strong>その他</strong>：その他の業務</li>
    </ul>
    <div class="mt-2">
        <small class="text-muted">
            <i class="fas fa-calendar-times me-1"></i>
            定期訪問は来月（訪問月）にご利用ください。
        </small>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2 text-success"></i>
                    <?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?>
                    <i class="fas fa-map-marker-alt ms-2 me-2 text-primary"></i>
                    <?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <?php if ($frequency === 'bimonthly'): ?>
                    <div class="mt-2">
                        <span class="badge bg-<?= $isVisitMonth ? 'success' : 'info' ?>">
                            <i class="fas fa-calendar-alt me-1"></i>
                            隔月契約（<?= $isVisitMonth ? '訪問月' : '非訪問月' ?>）
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">実施する役務の種別と実施方法を選択してください。</p>
                
                <form method="POST" action="<?= base_url("contracts/{$contract['id']}/start-service") ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="visit_type" id="visit_type_hidden" value="">
                    
                    <!-- 時間管理ありの役務 -->
                    <?php if (!$isSpotContract): ?>
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-clock me-2"></i>時間管理あり
                        <small class="text-muted">（開始・終了時刻を記録、実施方法選択）</small>
                    </h6>
                    <div class="row mb-4">
                        <!-- 定期訪問 -->
                        <div class="col-lg-6 col-md-6 mb-3">
                            <div class="card h-100 border-primary service-type-card <?= $isRegularRestricted ? 'disabled-card' : '' ?>" 
                                 data-type="regular" <?= $isRegularRestricted ? 'data-disabled="true"' : '' ?>>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-calendar-check fa-3x text-<?= $isRegularRestricted ? 'muted' : 'primary' ?>"></i>
                                    </div>
                                    <h5 class="card-title <?= $isRegularRestricted ? 'text-muted' : '' ?>">定期訪問</h5>
                                    <p class="card-text <?= $isRegularRestricted ? 'text-muted' : '' ?>">
                                        月間契約時間内での<br>
                                        定期的な産業医業務
                                    </p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <?php if (($contract['visit_frequency'] ?? 'monthly') === 'weekly'): ?>
                                                今月上限: <?= format_total_hours($contract['monthly_limit']) ?>（<?= $contract['monthly_visit_count'] ?>回訪問）<br>
                                                今月使用: <?= format_total_hours($monthlyStats['regular_hours']) ?>
                                            <?php else: ?>
                                                月間上限: <?= format_total_hours($contract['monthly_limit']) ?><br>
                                                今月使用: <?= format_total_hours($monthlyStats['regular_hours']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($isRegularRestricted): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-warning">
                                                <i class="fas fa-ban me-1"></i>非訪問月のため利用不可
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="service_type" 
                                               id="regular" value="regular" required 
                                               <?= $isRegularRestricted ? 'disabled' : '' ?>>
                                        <label class="form-check-label fw-bold <?= $isRegularRestricted ? 'text-muted' : '' ?>" for="regular">
                                            定期訪問を選択
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 臨時訪問 -->
                        <div class="col-lg-6 col-md-6 mb-3">
                            <div class="card h-100 border-warning service-type-card" data-type="emergency">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                                    </div>
                                    <h5 class="card-title">臨時訪問</h5>
                                    <p class="card-text">
                                        定期訪問以外での<br>
                                        緊急対応や追加業務
                                    </p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            今月実績: <?= format_total_hours($monthlyStats['emergency_hours']) ?>
                                        </small>
                                    </div>
                                    <?php if ($isRegularRestricted): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="service_type" 
                                               id="emergency" value="emergency" required>
                                        <label class="form-check-label fw-bold" for="emergency">
                                            臨時訪問を選択
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 時間記録なしの役務 -->
                    <?php 
                    // use_remote_consultationまたはuse_document_creationがtrueの場合のみセクションを表示
                    $showNoTimeSection = (($contract['use_remote_consultation'] ?? 0) == 1) || (($contract['use_document_creation'] ?? 0) == 1);
                    ?>
                    <?php if ($showNoTimeSection): ?>
                    <h6 class="text-secondary mb-3">
                        <i class="fas fa-file-alt me-2"></i>時間記録なし
                        <small class="text-muted">（業務内容の記載が重要、実施方法選択不要）</small>
                    </h6>
                    <div class="row mb-4">
                        <!-- 書面作成 -->
                        <?php if (($contract['use_document_creation'] ?? 0) == 1): ?>
                        <div class="col-lg-6 col-md-6 mb-3">
                            <div class="card h-100 border-success service-type-card no-time-service" data-type="document">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-file-alt fa-3x text-success"></i>
                                    </div>
                                    <h5 class="card-title">書面作成</h5>
                                    <p class="card-text">
                                        意見書・報告書等の<br>
                                        書面作成業務
                                    </p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            時間記録対象外<br>
                                            今月実績: <?= $monthlyStats['document_count'] ?? 0 ?>件
                                        </small>
                                    </div>
                                    <?php if ($isRegularRestricted): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="service_type" 
                                               id="document" value="document" required>
                                        <label class="form-check-label fw-bold" for="document">
                                            書面作成を選択
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 遠隔相談 -->
                        <?php if (($contract['use_remote_consultation'] ?? 0) == 1): ?>
                        <div class="col-lg-6 col-md-6 mb-3">
                            <div class="card h-100 border-info service-type-card no-time-service" data-type="remote_consultation">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-envelope fa-3x text-info"></i>
                                    </div>
                                    <h5 class="card-title">遠隔相談</h5>
                                    <p class="card-text">
                                        メールによる<br>
                                        相談対応
                                    </p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            時間記録対象外<br>
                                            今月実績: <?= $monthlyStats['remote_consultation_count'] ?? 0 ?>件
                                        </small>
                                    </div>
                                    <?php if ($isRegularRestricted): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="service_type" 
                                               id="remote_consultation" value="remote_consultation" required>
                                        <label class="form-check-label fw-bold" for="remote_consultation">
                                            遠隔相談を選択
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- その他 -->
                        <div class="col-lg-6 col-md-6 mb-3">
                            <div class="card h-100 border-dark service-type-card no-time-service" data-type="other">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-plus fa-3x text-dark"></i>
                                    </div>
                                    <h5 class="card-title">その他</h5>
                                    <p class="card-text">
                                        その他の業務<br>
                                        （請求金額を直接入力）
                                    </p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            時間記録対象外<br>
                                            今月実績: <?= $monthlyStats['other_count'] ?? 0 ?>件
                                        </small>
                                    </div>
                                    <?php if ($isRegularRestricted): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>非訪問月でも利用可能
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="service_type" 
                                               id="other" value="other" required>
                                        <label class="form-check-label fw-bold" for="other">
                                            その他を選択
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- スポット役務（スポット契約専用） -->
                    <?php if ($isSpotContract): ?>
                    <h6 class="text-info mb-3">
                        <i class="fas fa-clock me-2"></i>スポット役務
                        <small class="text-muted">（単発実施、時間管理・実施方法選択あり）</small>
                    </h6>
                    <div class="row mb-4">
                        <div class="col-lg-8 col-md-10 mx-auto mb-3">
                            <div class="card h-100 border-info service-type-card" data-type="spot">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-clock fa-3x text-info"></i>
                                    </div>
                                    <h5 class="card-title">スポット</h5>
                                    <p class="card-text">
                                        単発での産業医業務<br>
                                        （時間管理あり・実施方法選択）
                                    </p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            今月実績: <?= format_total_hours($monthlyStats['spot_hours'] ?? 0) ?>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-info text-dark">
                                            <i class="fas fa-exclamation-triangle me-1"></i>スポット契約専用
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="service_type" 
                                               id="spot" value="spot" required>
                                        <label class="form-check-label fw-bold" for="spot">
                                            スポットを選択
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 実施方法選択（時間管理ありの役務のみ） -->
                    <div id="visitTypeSelection" class="mb-4 d-none">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-walking me-2"></i>実施方法を選択
                            <small class="text-muted">（訪問または オンライン）</small>
                        </h6>
                        <div class="row">
                            <div class="col-lg-6 col-md-6 mb-3">
                                <div class="card h-100 border-primary visit-type-card" data-visit-type="visit">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-walking fa-3x text-primary"></i>
                                        </div>
                                        <h5 class="card-title">訪問実施</h5>
                                        <p class="card-text">
                                            現地への物理的な訪問<br>
                                            による役務の実施
                                        </p>
                                        <div class="mt-3">
                                            <span class="badge bg-success">
                                                <i class="fas fa-train me-1"></i>交通費登録可能
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="visit_type_radio" 
                                                   id="visit" value="visit">
                                            <label class="form-check-label fw-bold" for="visit">
                                                訪問で実施
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-6 col-md-6 mb-3">
                                <div class="card h-100 border-info visit-type-card" data-visit-type="online">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-laptop fa-3x text-info"></i>
                                        </div>
                                        <h5 class="card-title">オンライン実施</h5>
                                        <p class="card-text">
                                            リモートでの実施<br>
                                            （オンライン会議など）
                                        </p>
                                        <div class="mt-3">
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times me-1"></i>交通費登録不要
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="visit_type_radio" 
                                                   id="online" value="online">
                                            <label class="form-check-label fw-bold" for="online">
                                                オンラインで実施
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 実施方法の詳細説明 -->
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
                    
                    
                    <!-- 月間使用状況の警告（定期訪問のみ） -->
                    <?php if ($monthlyStats['regular_hours'] > 0 && $monthlyStats['regular_hours'] >= $contract['monthly_limit']): ?>
                        <div class="alert alert-warning mb-4">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>定期訪問時間の上限に達しています</h6>
                            <p class="mb-0">
                                今月の定期訪問時間（<?= format_total_hours($monthlyStats['regular_hours']) ?>）が
                                <?php if (($contract['visit_frequency'] ?? 'monthly') === 'weekly'): ?>
                                    今月の上限（<?= format_total_hours($contract['monthly_limit']) ?>：契約時間 <?= format_total_hours($contract['regular_visit_hours']) ?> × 今月訪問回数 <?= $contract['monthly_visit_count'] ?>回）
                                <?php else: ?>
                                    契約上限（<?= format_total_hours($contract['monthly_limit']) ?>）
                                <?php endif; ?>
                                に達しています。<br>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 選択した役務種別・実施方法の詳細情報 -->
                    <div id="serviceTypeInfo" class="alert alert-info d-none mb-4">
                        <div id="serviceTypeInfoContent"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('contracts') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>戻る
                        </a>
                        <button type="submit" class="btn btn-success btn-lg" id="start-service-btn" disabled>
                            <i class="fas fa-play me-1"></i><span id="btn-text">役務種別を選択してください</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// 役務種別の詳細情報（定期延長削除版）
const serviceTypeDetails = {
    regular: {
        title: '定期訪問について',
        content: `
            <ul class="mb-2">
                <li>月間契約時間内での通常業務として記録されます</li>
                <li>開始・終了時刻を記録し、役務時間を計算します</li>
                <li>選択した実施方法（訪問/オンライン）で記録されます</li>
                <li>訪問実施の場合は交通費の登録が可能です</li>
            </ul>
        `,
        buttonText: '定期訪問を開始',
        requiresTime: true,
        requiresVisitType: true
    },
    emergency: {
        title: '臨時訪問について',
        content: `
            <ul class="mb-2">
                <li>緊急対応や特別要請による訪問として記録されます</li>
                <li>月間契約時間に関係なく記録されます</li>
                <li>選択した実施方法（訪問/オンライン）で記録されます</li>
                <li>訪問実施の場合は交通費の登録が可能です</li>
            </ul>
        `,
        buttonText: '臨時訪問を開始',
        requiresTime: true,
        requiresVisitType: true
    },
    document: {
        title: '書面作成について',
        content: `
            <ul class="mb-2">
                <li>意見書・報告書等の書面作成業務として記録されます</li>
                <li>開始・終了時刻は記録せず、業務内容の詳細な記載が重要です</li>
                <li>時間管理の対象外のため、他の役務と並行して実施可能です</li>
                <li>実施方法の選択は不要で、交通費の登録はできません</li>
                <li><strong>業務内容の詳細な記載が必須項目です</strong></li>
            </ul>
        `,
        buttonText: '書面作成を記録',
        requiresTime: false,
        requiresVisitType: false
    },
    remote_consultation: {
        title: '遠隔相談について',
        content: `
            <ul class="mb-2">
                <li>メールによる相談対応として記録されます</li>
                <li>開始・終了時刻は記録せず、業務内容の詳細な記載が重要です</li>
                <li>時間管理の対象外のため、他の役務と並行して実施可能です</li>
                <li>実施方法の選択は不要で、交通費の登録はできません</li>
                <li><strong>相談内容や対応の詳細な記載が必須項目です</strong></li>
            </ul>
        `,
        buttonText: '遠隔相談を記録',
        requiresTime: false,
        requiresVisitType: false
    },
    other: {
        title: 'その他について',
        content: `
            <ul class="mb-2">
                <li>その他の業務として記録されます</li>
                <li>開始・終了時刻は記録せず、業務内容の詳細な記載が重要です</li>
                <li>請求金額を直接入力します</li>
                <li>時間管理の対象外のため、他の役務と並行して実施可能です</li>
                <li>実施方法の選択は不要で、交通費の登録はできません</li>
                <li><strong>業務内容の詳細な記載と請求金額の入力が必須項目です</strong></li>
            </ul>
        `,
        buttonText: 'その他の役務を記録',
        requiresTime: false,
        requiresVisitType: false
    },
    spot: {
        title: 'スポットについて',
        content: `
            <ul class="mb-2">
                <li>スポット契約での単発役務として記録されます</li>
                <li>選択した実施方法（訪問/オンライン）で記録されます</li>
                <li>訪問実施の場合は交通費の登録が可能です</li>
                <li>スポット契約専用の役務種別です</li>
            </ul>
        `,
        buttonText: 'スポットを開始',
        requiresTime: true,
        requiresVisitType: true
    }
};

// 訪問種別の詳細情報
const visitTypeDetails = {
    visit: {
        title: '訪問実施',
        description: '現地での面談、職場巡視、健康相談等を実施。交通費の登録が可能。',
        badge: '交通費登録可能'
    },
    online: {
        title: 'オンライン実施',
        description: 'オンライン会議システム等を使用したリモート実施。交通費の登録は不要。',
        badge: '交通費登録不要'
    }
};

let selectedServiceType = null;
let selectedVisitType = null;

document.addEventListener('DOMContentLoaded', function() {
    const serviceTypeCards = document.querySelectorAll('.service-type-card');
    const visitTypeCards = document.querySelectorAll('.visit-type-card');
    const serviceTypeRadios = document.querySelectorAll('input[name="service_type"]');
    const visitTypeRadios = document.querySelectorAll('input[name="visit_type_radio"]');
    const startServiceBtn = document.getElementById('start-service-btn');
    const btnText = document.getElementById('btn-text');
    const serviceTypeInfo = document.getElementById('serviceTypeInfo');
    const serviceTypeInfoContent = document.getElementById('serviceTypeInfoContent');
    const visitTypeSelection = document.getElementById('visitTypeSelection');
    const visitTypeHidden = document.getElementById('visit_type_hidden');
    
    // 役務種別カード選択時の処理
    serviceTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            // 無効化されているカードはクリックできない
            if (this.dataset.disabled === 'true') {
                showDisabledAlert(this.dataset.type);
                return;
            }
            
            const type = this.dataset.type;
            const radio = document.getElementById(type);
            radio.checked = true;
            
            selectServiceType(type);
        });
    });
    
    // 訪問種別カード選択時の処理
    visitTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const visitType = this.dataset.visitType;
            const radio = document.getElementById(visitType);
            radio.checked = true;
            
            selectVisitType(visitType);
        });
    });
    
    // 役務種別ラジオボタン変更時の処理
    serviceTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked && !this.disabled) {
                selectServiceType(this.value);
            }
        });
    });
    
    // 訪問種別ラジオボタン変更時の処理
    visitTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                selectVisitType(this.value);
            }
        });
    });
    
    function selectServiceType(type) {
        selectedServiceType = type;
        
        // 他の役務種別カードの選択状態をクリア
        serviceTypeCards.forEach(c => {
            if (!c.classList.contains('disabled-card')) {
                c.classList.remove('border-3', 'bg-light');
            }
        });
        
        // 選択されたカードをハイライト（無効でない場合）
        const card = document.querySelector(`[data-type="${type}"]`);
        if (card && !card.classList.contains('disabled-card')) {
            card.classList.add('border-3', 'bg-light');
        }
        
        // 訪問種別選択をリセット
        resetVisitTypeSelection();
        
        // 実施方法選択の表示/非表示
        const details = serviceTypeDetails[type];
        if (details && details.requiresVisitType) {
            visitTypeSelection.classList.remove('d-none');
        } else {
            visitTypeSelection.classList.add('d-none');
            selectedVisitType = null;
            visitTypeHidden.value = '';
        }
        
        // ボタン状態とサービス情報を更新
        updateServiceInfo();
        updateSubmitButton();
    }
    
    function selectVisitType(visitType) {
        selectedVisitType = visitType;
        
        // 他の訪問種別カードの選択状態をクリア
        visitTypeCards.forEach(c => c.classList.remove('border-3', 'bg-light'));

        // 選択されたカードをハイライト
        const card = document.querySelector(`[data-visit-type="${visitType}"]`);
        if (card) {
            card.classList.add('border-3', 'bg-light');
        }
        
        // hiddenフィールドに値を設定
        const visitTypeHidden = document.getElementById('visit_type_hidden');
        visitTypeHidden.value = visitType;
        
        // ボタン状態とサービス情報を更新
        updateServiceInfo();
        updateSubmitButton();
    }
    
    function resetVisitTypeSelection() {
        selectedVisitType = null;
        visitTypeHidden.value = '';
        
        visitTypeRadios.forEach(radio => radio.checked = false);
        visitTypeCards.forEach(card => card.classList.remove('border-3', 'bg-light'));
    }
    
    function updateServiceInfo() {
        if (!selectedServiceType || !serviceTypeDetails[selectedServiceType]) {
            serviceTypeInfo.classList.add('d-none');
            return;
        }
        
        const details = serviceTypeDetails[selectedServiceType];
        let content = `
            <h6><i class="fas fa-info-circle me-2"></i>${details.title}</h6>
            ${details.content}
        `;
        
        // 実施方法が選択されている場合は追加情報を表示
        if (selectedVisitType && details.requiresVisitType) {
            const visitInfo = visitTypeDetails[selectedVisitType];
            content += `
                <div class="mt-3 p-3 border rounded bg-light">
                    <h6><i class="fas fa-${selectedVisitType === 'visit' ? 'walking' : 'laptop'} me-2 text-${selectedVisitType === 'visit' ? 'primary' : 'info'}"></i>${visitInfo.title}で実施</h6>
                    <p class="mb-1">${visitInfo.description}</p>
                    <span class="badge bg-${selectedVisitType === 'visit' ? 'success' : 'secondary'}">${visitInfo.badge}</span>
                </div>
            `;
        }
        
        serviceTypeInfoContent.innerHTML = content;
        serviceTypeInfo.classList.remove('d-none');
    }
    
    function updateSubmitButton() {
        if (!selectedServiceType) {
            startServiceBtn.disabled = true;
            btnText.textContent = '役務種別を選択してください';
            return;
        }
        
        const details = serviceTypeDetails[selectedServiceType];
        
        // 実施方法が必要な役務種別の場合
        if (details.requiresVisitType) {
            if (selectedVisitType) {
                startServiceBtn.disabled = false;
                const visitInfo = visitTypeDetails[selectedVisitType];
                btnText.textContent = `${details.buttonText}（${visitInfo.title}）`;
                
                // アイコンを更新
                const icon = startServiceBtn.querySelector('i');
                icon.className = details.requiresTime ? 'fas fa-play me-1' : 'fas fa-save me-1';
            } else {
                startServiceBtn.disabled = true;
                btnText.textContent = '実施方法を選択してください';
            }
        } else {
            // 実施方法選択不要な場合
            startServiceBtn.disabled = false;
            btnText.textContent = details.buttonText;
            
            // アイコンを更新
            const icon = startServiceBtn.querySelector('i');
            icon.className = details.requiresTime ? 'fas fa-play me-1' : 'fas fa-save me-1';
        }
    }
    
    // 無効化されたカードクリック時のアラート表示
    function showDisabledAlert(type) {
        let message = '';
        if (type === 'regular') {
            message = '隔月契約の非訪問月のため、定期訪問は利用できません。\n\n利用可能な役務：\n• 臨時訪問\n• 書面作成\n• 遠隔相談';
        }
        
        if (message) {
            alert(message);
        }
    }
    
    // フォーム送信時の確認
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!selectedServiceType) {
            e.preventDefault();
            alert('役務種別を選択してください。');
            return;
        }
        
        const details = serviceTypeDetails[selectedServiceType];
        if (details.requiresVisitType && !selectedVisitType) {
            e.preventDefault();
            alert('実施方法を選択してください。');
            return;
        }
        
        // 書面作成、遠隔相談、その他の場合はGETパラメータで役務追加画面に遷移
        if (['document', 'remote_consultation', 'other'].includes(selectedServiceType)) {
            e.preventDefault();
            const contractId = '<?= $contract['id'] ?>';
            const url = '<?= base_url('service_records/create') ?>?contract_id=' + contractId + '&service_type=' + selectedServiceType;
            window.location.href = url;
            return;
        }
        
        const typeLabels = {
            'regular': '定期訪問',
            'emergency': '臨時訪問',
            'spot': 'スポット'
        };
        
        const visitLabels = {
            'visit': '訪問実施',
            'online': 'オンライン実施'
        };
        
        const typeName = typeLabels[selectedServiceType];
        let confirmMessage = '';
        
        if (details.requiresTime) {
            const visitName = selectedVisitType ? `（${visitLabels[selectedVisitType]}）` : '';
            confirmMessage = `${typeName}${visitName}を開始しますか？\n\n開始後は現在時刻で役務が開始され、終了時に詳細情報を入力します。`;
        }
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.service-type-card,
.visit-type-card {
    cursor: pointer;
    transition: all 0.2s ease;
}

.service-type-card:hover,
.visit-type-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.service-type-card.border-3,
.visit-type-card.border-3 {
    border-width: 3px !important;
}

/* 無効化されたカードのスタイル */
.service-type-card.disabled-card {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #f8f9fa;
}

.service-type-card.disabled-card:hover {
    transform: none;
    box-shadow: none;
}

.service-type-card.disabled-card .card-body {
    background-color: #f8f9fa;
}

.service-type-card.disabled-card .form-check-input:disabled {
    cursor: not-allowed;
}

.service-type-card.disabled-card .form-check-label {
    cursor: not-allowed;
}

/* 時間記録なし役務用のスタイル */
.service-type-card.no-time-service {
    background-color: #f8f9fa;
    border-color: #6c757d !important;
}

.service-type-card.no-time-service:hover {
    background-color: #e9ecef;
}

.service-type-card.no-time-service.border-3.bg-light {
    background-color: #e9ecef !important;
    border-color: #495057 !important;
}

/* 訪問種別カードのスタイル */
.visit-type-card {
    min-height: 280px;
}

.visit-type-card.border-3.bg-light {
    background-color: #f8f9fa !important;
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.card-footer {
    background-color: transparent;
    border-top: 1px solid rgba(0,0,0,0.125);
}

/* セクション見出しのスタイル */
h6.text-primary, 
h6.text-secondary, 
h6.text-info {
    border-bottom: 2px solid currentColor;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

/* 実施方法選択エリアのアニメーション */
#visitTypeSelection {
    transition: all 0.3s ease-in-out;
}

/* バッジのスタイル */
.badge {
    font-size: 0.75em;
    padding: 0.4em 0.8em;
}

/* レスポンシブ対応 */
@media (max-width: 992px) {
    .col-lg-4,
    .col-lg-6 {
        margin-bottom: 1rem;
    }
}

@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .service-type-card .card-body,
    .visit-type-card .card-body {
        padding: 1rem;
    }
    
    .service-type-card .fa-3x,
    .visit-type-card .fa-3x {
        font-size: 2rem !important;
    }
    
    .service-type-card h5,
    .visit-type-card h5 {
        font-size: 1.1rem;
    }
    
    .service-type-card .card-text,
    .visit-type-card .card-text {
        font-size: 0.9rem;
    }
    
    .visit-type-card {
        min-height: 240px;
    }
}

@media (max-width: 576px) {
    .col-md-6 {
        width: 100%;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-lg {
        width: 100%;
    }
    
    .visit-type-card {
        min-height: 200px;
    }
    
    .visit-type-card .fa-3x {
        font-size: 1.8rem !important;
    }
}

/* 高解像度ディスプレイ対応 */
@media (min-width: 1200px) {
    .service-type-card,
    .visit-type-card {
        margin-bottom: 1.5rem;
    }
    
    .visit-type-card {
        min-height: 320px;
    }
}

/* アクセシビリティ向上 */
.service-type-card:focus,
.visit-type-card:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* 特別なバッジスタイル */
.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

/* 制限警告のスタイル */
.alert-info {
    border-left: 4px solid #0dcaf0;
}

.alert-info h6 {
    color: #055160;
}

.alert-info ul {
    margin-left: 1rem;
}

.alert-info ul li {
    margin-bottom: 0.25rem;
}

/* 契約状態バッジ */
.card-header .badge {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}
</style>