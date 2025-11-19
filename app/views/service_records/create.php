<?php
// app/views/service_records/create.php - テンプレート機能修正版（時刻正規化対応）

// セッションから保存された入力値を取得し、使用後は削除
$savedInput = $_SESSION['form_input'] ?? [];
unset($_SESSION['form_input']);

// フォームデータの優先順位: セッション保存データ > POST > GET > デフォルト値
$formData = $form_data ?? [];
foreach (['contract_id', 'service_date', 'service_type', 'visit_type', 'start_time', 'end_time', 'description', 'overtime_reason'] as $field) {
    if (isset($savedInput[$field])) {
        $formData[$field] = $savedInput[$field];
    }
}

// 選択されている役務種別を取得(優先順位: POST > GET > フォームデータ > デフォルト)
$selectedServiceType = $_POST['service_type'] ?? $_GET['service_type'] ?? $formData['service_type'] ?? 'regular';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-plus me-2"></i>役務記録追加</h2>
    <a href="<?= base_url('service_records') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>役務記録一覧に戻る
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>役務記録情報
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('service_records') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="contract_id" class="form-label">契約 <span class="text-danger">*</span></label>
                        <select class="form-select" id="contract_id" name="contract_id" required>
                            <option value="">契約を選択してください</option>
                            <?php foreach ($contracts as $contract): ?>
                                <?php 
                                // 訪問月判定
                                $isVisitMonth = is_visit_month($contract, date('Y'), date('n'));
                                ?>
                                <option value="<?= $contract['id'] ?>" 
                                        data-regular-hours="<?= $contract['regular_visit_hours'] ?>"
                                        data-company="<?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-branch="<?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-visit-frequency="<?= $contract['visit_frequency'] ?? 'monthly' ?>"
                                        data-is-visit-month="<?= $isVisitMonth ? '1' : '0' ?>"
                                        data-use-remote-consultation="<?= isset($contract['use_remote_consultation']) ? $contract['use_remote_consultation'] : '0' ?>"
                                        data-use-document-creation="<?= isset($contract['use_document_creation']) ? $contract['use_document_creation'] : '0' ?>"
                                        <?= ($formData['contract_id'] ?? '') == $contract['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($contract['company_name'], ENT_QUOTES, 'UTF-8') ?> - 
                                    <?= htmlspecialchars($contract['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (($contract['visit_frequency'] ?? 'monthly') === 'bimonthly'): ?>
                                        <?= $isVisitMonth ? '[訪問月]' : '[非訪問月]' ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="service_date" class="form-label">
                            役務日 <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control" id="service_date" name="service_date" 
                               value="<?= htmlspecialchars($formData['service_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- 役務種別選択（既存のコード） -->
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="service_type" class="form-label">役務種別 <span class="text-danger">*</span></label>
                        <div class="row">
                            <?php 
                            $selectedServiceType = $formData['service_type'] ?? 'regular';
                            
                            // 選択されている契約の設定を確認
                            $selectedContract = null;
                            if (!empty($formData['contract_id'])) {
                                foreach ($contracts as $contract) {
                                    if ($contract['id'] == $formData['contract_id']) {
                                        $selectedContract = $contract;
                                        break;
                                    }
                                }
                            }
                            
                            $serviceTypes = [
                                'regular' => [
                                    'icon' => 'calendar-check',
                                    'color' => 'primary',
                                    'label' => '定期訪問',
                                    'description' => '月間契約時間内での通常業務'
                                ],
                                'emergency' => [
                                    'icon' => 'exclamation-triangle',
                                    'color' => 'warning',
                                    'label' => '臨時訪問',
                                    'description' => '緊急対応や特別要請による訪問'
                                ],
                                'spot' => [
                                    'icon' => 'clock',
                                    'color' => 'info',
                                    'label' => 'スポット',
                                    'description' => 'スポット契約による対応'
                                ],
                                'document' => [
                                    'icon' => 'file-alt',
                                    'color' => 'success',
                                    'label' => '書面作成',
                                    'description' => '意見書・報告書等の書面作成業務'
                                ],
                                'remote_consultation' => [
                                    'icon' => 'envelope',
                                    'color' => 'secondary',
                                    'label' => '遠隔相談',
                                    'description' => 'メールによる相談対応'
                                ],
                                'other' => [
                                    'icon' => 'plus',
                                    'color' => 'dark',
                                    'label' => 'その他',
                                    'description' => 'その他の業務（請求金額を直接入力）'
                                ]
                            ];
                            
                            // 初期表示時の可視カード数をカウント
                            $visibleCount = 0;
                            foreach ($serviceTypes as $type => $info) {
                                $shouldHide = false;
                                if ($type === 'remote_consultation') {
                                    $shouldHide = !$selectedContract || empty($selectedContract['use_remote_consultation']);
                                } elseif ($type === 'document') {
                                    $shouldHide = !$selectedContract || empty($selectedContract['use_document_creation']);
                                } elseif ($type === 'spot') {
                                    // スポット契約でない場合は非表示
                                    $shouldHide = !$selectedContract || ($selectedContract['visit_frequency'] ?? 'monthly') !== 'spot';
                                } elseif (in_array($type, ['regular', 'emergency'])) {
                                    // スポット契約の場合は定期訪問と臨時訪問を非表示
                                    $shouldHide = $selectedContract && ($selectedContract['visit_frequency'] ?? 'monthly') === 'spot';
                                }
                                if (!$shouldHide) {
                                    $visibleCount++;
                                }
                            }
                            
                            // カードの数に応じて初期クラスを決定
                            $initialColClass = 'col-md-3'; // デフォルト
                            if ($visibleCount === 1) {
                                $initialColClass = 'col-md-12';
                            } elseif ($visibleCount === 2) {
                                $initialColClass = 'col-md-6';
                            } elseif ($visibleCount === 3) {
                                $initialColClass = 'col-md-4';
                            }
                            ?>
                            
                            <?php foreach ($serviceTypes as $type => $info): 
                                // 初期表示の判定
                                $shouldHide = false;
                                if ($type === 'remote_consultation') {
                                    // 契約未選択、または契約でuse_remote_consultation=0の場合は非表示
                                    $shouldHide = !$selectedContract || empty($selectedContract['use_remote_consultation']);
                                } elseif ($type === 'document') {
                                    // 契約未選択、または契約でuse_document_creation=0の場合は非表示
                                    $shouldHide = !$selectedContract || empty($selectedContract['use_document_creation']);
                                } elseif ($type === 'spot') {
                                    // スポット契約でない場合は非表示
                                    $shouldHide = !$selectedContract || ($selectedContract['visit_frequency'] ?? 'monthly') !== 'spot';
                                } elseif (in_array($type, ['regular', 'emergency'])) {
                                    // スポット契約の場合は定期訪問と臨時訪問を非表示
                                    $shouldHide = $selectedContract && ($selectedContract['visit_frequency'] ?? 'monthly') === 'spot';
                                }
                            ?>
                                <div class="<?= $initialColClass ?> mb-3 service-type-wrapper" 
                                     data-service-type="<?= $type ?>"
                                     style="<?= $shouldHide ? 'display: none;' : '' ?>">
                                    <div class="card h-100 service-type-card <?= $selectedServiceType === $type ? 'selected' : '' ?>" 
                                        data-type="<?= $type ?>" onclick="selectServiceType('<?= $type ?>')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <i class="fas fa-<?= $info['icon'] ?> fa-2x text-<?= $info['color'] ?>"></i>
                                            </div>
                                            <h6 class="card-title mb-2"><?= $info['label'] ?></h6>
                                            <small class="text-muted"><?= $info['description'] ?></small>
                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="radio" name="service_type" 
                                                    id="<?= $type ?>" value="<?= $type ?>" 
                                                    <?= $selectedServiceType === $type && !$shouldHide ? 'checked' : '' ?>
                                                    <?= $shouldHide ? 'disabled' : '' ?> required>
                                                <label class="form-check-label fw-bold" for="<?= $type ?>">
                                                    選択
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text" id="service-type-help">
                            <?= $serviceTypes[$selectedServiceType]['description'] ?? '役務種別を選択してください' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 訪問種別選択セクション（定期訪問・臨時訪問のみ） -->
            <div class="row" id="visit-type-section" style="<?= !in_array($selectedServiceType, ['regular', 'emergency']) ? 'display: none;' : '' ?>">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">訪問種別 <span class="text-danger visit-type-required">*</span></label>
                        <div class="row">
                            <?php 
                            $selectedVisitType = $formData['visit_type'] ?? 'visit';
                            $visitTypes = [
                                'visit' => [
                                    'icon' => 'walking',
                                    'color' => 'primary',
                                    'label' => '訪問',
                                    'description' => '現地への物理的な訪問'
                                ],
                                'online' => [
                                    'icon' => 'laptop',
                                    'color' => 'info',
                                    'label' => 'オンライン',
                                    'description' => 'リモートでの実施（オンライン会議など）'
                                ]
                            ];
                            ?>
                            
                            <?php foreach ($visitTypes as $type => $info): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 visit-type-card <?= $selectedVisitType === $type ? 'selected' : '' ?>" 
                                         data-type="<?= $type ?>" onclick="selectVisitType('<?= $type ?>')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <i class="fas fa-<?= $info['icon'] ?> fa-2x text-<?= $info['color'] ?>"></i>
                                            </div>
                                            <h6 class="card-title mb-2"><?= $info['label'] ?></h6>
                                            <small class="text-muted"><?= $info['description'] ?></small>
                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="radio" name="visit_type" 
                                                       id="visit_<?= $type ?>" value="<?= $type ?>" 
                                                       <?= $selectedVisitType === $type ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold" for="visit_<?= $type ?>">
                                                    選択
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 時間入力セクション（定期訪問・臨時訪問のみ） -->
            <div class="row" id="time-input-section" style="<?= in_array($selectedServiceType, ['document', 'remote_consultation']) ? 'display: none;' : '' ?>">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="start_time" class="form-label">
                            開始時刻 <span class="text-danger time-required">*</span>
                        </label>
                        <input type="time" class="form-control time-input" id="start_time" name="start_time" 
                               value="<?= htmlspecialchars($formData['start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text">時刻は自動的に分単位（秒は00）に調整されます</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="end_time" class="form-label">
                            終了時刻 <span class="text-danger time-required">*</span>
                        </label>
                        <input type="time" class="form-control time-input" id="end_time" name="end_time" 
                               value="<?= htmlspecialchars($formData['end_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text">時刻は自動的に分単位（秒は00）に調整されます</div>
                    </div>
                </div>
            </div>
            
            <!-- 時間不要の説明（書面作成・遠隔相談のみ） -->
            <div class="alert alert-info" id="no-time-notice" style="<?= !in_array($selectedServiceType, ['document', 'remote_consultation']) ? 'display: none;' : '' ?>">
                <i class="fas fa-info-circle me-2"></i>
                <strong id="no-time-service-label">
                    <?= ($selectedServiceType === 'document') ? '書面作成' : (($selectedServiceType === 'remote_consultation') ? '遠隔相談' : '') ?>
                </strong>では時間の入力は不要です。
                役務日と業務内容を入力してください。
            </div>
            
            <!-- 請求金額入力フィールド（その他のみ） -->
            <div class="row" id="billing-amount-section" style="<?= $selectedServiceType !== 'other' ? 'display: none;' : '' ?>">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="direct_billing_amount" class="form-label">
                            請求金額 <span class="text-danger billing-amount-required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">¥</span>
                            <input type="number" class="form-control" id="direct_billing_amount" name="direct_billing_amount" 
                                   value="<?= htmlspecialchars($formData['direct_billing_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   min="0" step="1" placeholder="0">
                        </div>
                        <div class="form-text">
                            請求する金額を入力してください
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- その他の説明 -->
            <div class="alert alert-info" id="other-notice" style="<?= $selectedServiceType !== 'other' ? 'display: none;' : '' ?>">
                <i class="fas fa-info-circle me-2"></i>
                <strong>その他</strong>では時間の入力は不要です。
                役務日、業務内容、および請求金額を入力してください。
            </div>
            
            <!-- 予想役務時間表示（時間入力がある場合のみ） -->
            <div class="row" id="hours-preview-section" style="<?= in_array($selectedServiceType, ['document', 'remote_consultation']) ? 'display: none;' : '' ?>">
                <div class="col-12">
                    <div class="alert alert-info" id="hours-preview" style="display: none;">
                        <i class="fas fa-hourglass-half me-2"></i>
                        <strong>役務時間：</strong>
                        <span id="calculated-hours">--:--</span>
                        <span id="overtime-info" class="ms-3"></span>
                    </div>
                </div>
            </div>
            
            <!-- 役務内容入力セクション -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="description" class="form-label">
                        役務内容 <span class="text-danger" id="description-required" style="display: none;">*</span>
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
                <textarea class="form-control" id="description" name="description" rows="6"><?= htmlspecialchars($formData['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text" id="description-help">
                    実施した業務内容を記載してください（<span id="description-optional">任意</span>）。承認の際に参考となります。<br>
                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                </div>
                <!-- テンプレート適用通知 -->
                <div id="template-applied-notice" class="alert alert-success mt-2" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="template-applied-text">テンプレートが適用されました</span>
                </div>
            </div>
            
            <div class="mb-3" id="overtime-reason-group" style="display: none;">
                <label for="overtime_reason" class="form-label text-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>延長理由 <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="overtime_reason" name="overtime_reason" rows="3" 
                        placeholder="時間延長や特別対応の理由を入力してください&#13;&#10;例：緊急の健康相談対応、詳細な職場巡視が必要だった、労働者の健康問題への対応等"><?= htmlspecialchars($formData['overtime_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <small class="form-text text-muted">
                    <strong>定期訪問で月間定期時間を延長する場合は必須入力です。</strong><br>
                    具体的な理由を記載してください（承認の際に参考となります）。<br>
                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>
                </small>
            </div>
            
            <!-- 時間重複警告表示エリア -->
            <div id="time-overlap-warning"></div>
            
            <?php
            // 遷移元に応じたキャンセル先を決定
            $returnTo = Session::get('service_record_return_to');
            $cancelUrl = match($returnTo) {
                'dashboard' => base_url('dashboard'),
                'contracts' => base_url('contracts'),
                default => base_url('service_records')
            };
            ?>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= $cancelUrl ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-times me-1"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary" onclick="return validateAndCheckAutoSave(event)">
                    <i class="fas fa-save me-1"></i>登録
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 契約情報表示エリア -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-info-circle me-2"></i>選択した契約の情報
        </h5>
    </div>
    <div class="card-body">
        <div id="contract-info" class="text-muted">
            契約を選択すると、詳細情報が表示されます。
        </div>
    </div>
</div>

<!-- 役務種別に関する説明 -->
<div class="alert alert-info mt-3">
    <h6><i class="fas fa-info-circle me-2"></i>役務種別について</h6>
    <div class="row">
        <div class="col-md-6">
            <ul class="mb-2">
                <li><strong>定期訪問:</strong> 月間契約時間内での通常業務（時間記録あり、内容記載任意）</li>
                <li><strong>臨時訪問:</strong> 緊急対応や特別要請による訪問（時間記録あり、内容記載任意）</li>
                <li><strong>書面作成:</strong> 意見書・報告書等の作成業務（時間記録なし、内容記載必須）</li>
            </ul>
        </div>
        <div class="col-md-6">
            <ul class="mb-2">
                <li><strong>遠隔相談:</strong> メールによる相談対応（時間記録なし、内容記載必須）</li>
                <li><strong>その他:</strong> その他の業務（時間記録なし、内容記載必須、請求金額入力）</li>
            </ul>
        </div>
    </div>
    <div class="mt-2">
        <small class="text-muted">
            <i class="fas fa-lightbulb me-1"></i>
            <strong>定期訪問・臨時訪問</strong>では「訪問」または「オンライン」を選択してください。
            <strong>書面作成・遠隔相談・その他</strong>では時間記録は不要ですが、業務内容の記載が必要です。
        </small>
    </div>
</div>

<!-- テンプレート選択モーダル（修正版） -->
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

<!-- 役務内容テンプレート自動保存確認モーダル（修正版） -->
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
/* 役務種別カードのスタイル（既存） */
.service-type-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e9ecef;
    min-height: 200px;
}

.service-type-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.service-type-card.selected {
    border-color: #007bff;
    background-color: #f8f9fa;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* 訪問種別カードのスタイル（既存） */
.visit-type-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e9ecef;
    min-height: 160px;
}

.visit-type-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.visit-type-card.selected {
    border-color: #007bff;
    background-color: #f8f9fa;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* 時間入力フィールドのスタイル */
.time-input {
    position: relative;
}

.time-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* テンプレート関連のスタイル（修正版） */
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
    justify-content: between;
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

/* その他のスタイル（既存） */
.card-body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
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

.alert {
    border: none;
    border-radius: 0.5rem;
}

#template-preview {
    white-space: pre-wrap;
    word-wrap: break-word;
}


/* 無効化された役務種別カードのスタイル（改善版） */
.service-type-card.disabled {
    opacity: 0.6;
    cursor: not-allowed !important;
    border-color: #dee2e6 !important;
    background-color: #f8f9fa !important;
    box-shadow: none !important;
    position: relative;
}

.service-type-card.disabled:hover {
    transform: none !important;
    box-shadow: none !important;
    border-color: #dee2e6 !important;
    background-color: #f8f9fa !important;
}

.service-type-card.disabled::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.5);
    z-index: 1;
    pointer-events: none;
}

.service-type-card.disabled .form-check-input:disabled {
    opacity: 0.5;
}

.service-type-card.disabled .card-title,
.service-type-card.disabled .text-muted,
.service-type-card.disabled .fas {
    color: #6c757d !important;
}

.service-type-card.disabled .form-check-label {
    color: #6c757d !important;
}

/* 制限メッセージのスタイル */
#bimonthly-restriction-message {
    border-left: 4px solid #ffc107;
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
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

/* 締め処理警告スタイル */
#closing-warning {
    border-left: 4px solid #dc3545;
    animation: fadeInDown 0.4s ease-out;
    box-shadow: 0 4px 6px rgba(220, 53, 69, 0.1);
}

#closing-warning .alert-heading {
    color: #dc3545;
    font-weight: 600;
}

#closing-warning hr {
    border-color: rgba(220, 53, 69, 0.2);
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 締め処理済みボタンスタイル */
button[type="submit"].disabled {
    cursor: not-allowed;
    opacity: 0.6;
}
</style>

<script>
// グローバル変数の定義
let isSubmittingForm = false;
let templates = { system: [], personal: [] };
let selectedTemplate = null;
let currentMonthlyUsage = null;
let isClosedPeriod = false; // 締め処理済みフラグ

// 時間入力が必要な役務種別
const timeRequiredTypes = ['regular', 'emergency', 'spot'];

// 訪問種別が必要な役務種別
const visitTypeRequiredTypes = ['regular', 'emergency', 'spot'];

// 役務種別のラベル
const serviceTypeLabels = {
    'regular': '定期訪問',
    'emergency': '臨時訪問',
    'spot': 'スポット',
    'document': '書面作成',
    'remote_consultation': '遠隔相談',
    'other': 'その他'
};

// 役務種別の説明
const serviceTypeDescriptions = {
    'regular': '月間契約時間内での通常業務',
    'emergency': '緊急対応や特別要請による訪問',
    'spot': 'スポット契約による対応',
    'document': '意見書・報告書等の書面作成業務',
    'remote_consultation': 'メールによる相談対応',
    'other': 'その他の業務（請求金額を直接入力）'
};

// DOMエレメントの参照をグローバルスコープで保持
let contractSelect, serviceDateInput, contractInfoDiv, hoursPreview, calculatedHours, overtimeInfo, overtimeReasonGroup, serviceTypeHelp;
let startTimeInput, endTimeInput;
let visitTypeSection, timeInputSection, hoursPreviewSection, noTimeNotice, noTimeServiceLabel;
let descriptionRequired, descriptionOptional;

// 時間フォーマット関数（グローバル）
function formatHours(hours) {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    return h + ':' + (m < 10 ? '0' : '') + m;
}

// 時刻正規化関数（秒を00に設定）
function normalizeTime(timeString) {
    if (!timeString) return '';
    
    // HH:MM または HH:MM:SS 形式を HH:MM:00 に正規化
    const timeParts = timeString.split(':');
    if (timeParts.length >= 2) {
        const hours = timeParts[0].padStart(2, '0');
        const minutes = timeParts[1].padStart(2, '0');
        return `${hours}:${minutes}:00`;
    }
    return timeString;
}

// 契約情報表示（グローバルスコープに移動）
function updateContractInfo() {
    const selectedOption = contractSelect.options[contractSelect.selectedIndex];
    if (selectedOption.value) {
        const regularHours = selectedOption.dataset.regularHours;
        const company = selectedOption.dataset.company;
        const branch = selectedOption.dataset.branch;
        const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
        const isVisitMonth = selectedOption.dataset.isVisitMonth === '1';
        
        let frequencyInfo = '';
        switch(visitFrequency) {
            case 'weekly':
                frequencyInfo = `<p><strong>訪問頻度:</strong> 毎週契約</p>`;
                frequencyInfo += `<p class="text-info"><small><i class="fas fa-info-circle me-1"></i>月間上限は祝日を除く訪問回数で自動計算されます</small></p>`;
                break;
            case 'bimonthly':
                const serviceDate = serviceDateInput.value;
                const monthStr = serviceDate ? new Date(serviceDate).toLocaleDateString('ja-JP', { year: 'numeric', month: 'long' }) : '';
                frequencyInfo = `<p><strong>訪問頻度:</strong> 隔月契約</p>`;
                if (serviceDate) {
                    frequencyInfo += `<p><strong>${monthStr}:</strong> <span class="${isVisitMonth ? 'text-success' : 'text-warning'}">${isVisitMonth ? '訪問月' : '非訪問月'}</span></p>`;
                }
                break;
            default:
                frequencyInfo = `<p><strong>訪問頻度:</strong> 毎月契約</p>`;
                break;
        }
        
        contractInfoDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-file-contract me-2"></i>契約詳細</h6>
                    <p><strong>企業:</strong> ${company}</p>
                    <p><strong>拠点:</strong> ${branch}</p>
                    <p><strong>基本時間:</strong> ${formatHours(parseFloat(regularHours))}/回</p>
                    ${frequencyInfo}
                </div>
                <div class="col-md-6">
                    <div id="monthly-usage-info">
                        <h6><i class="fas fa-chart-bar me-2"></i>今月の使用状況</h6>
                        <div class="text-muted">読み込み中...</div>
                    </div>
                </div>
            </div>
        `;
        
        // 今月の使用状況を取得
        loadMonthlyUsage(selectedOption.value, regularHours);

        // 締め処理状態をチェック
        checkClosingStatus(selectedOption.value, serviceDateInput.value);
    } else {
        contractInfoDiv.innerHTML = '<div class="text-muted">契約を選択すると、詳細情報が表示されます。</div>';
        currentMonthlyUsage = null;
        hideClosingWarning(); // 警告を非表示
    }
}

// 役務種別の表示/非表示を制御する関数
function updateServiceTypeAvailability() {
    const selectedOption = contractSelect.options[contractSelect.selectedIndex];
    
    // 契約が選択されていない場合は、遠隔相談と書面作成を非表示、スポットも非表示
    if (!selectedOption.value) {
        hideServiceType('remote_consultation');
        hideServiceType('document');
        hideServiceType('spot');
        showServiceType('regular');
        showServiceType('emergency');
        return;
    }
    
    // 訪問頻度を取得
    const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
    
    // スポット契約の場合
    if (visitFrequency === 'spot') {
        // 定期訪問と臨時訪問を非表示
        hideServiceType('regular');
        hideServiceType('emergency');
        // スポットを表示
        showServiceType('spot');
        
        // スポットが選択されていなければ自動選択
        const spotRadio = document.getElementById('spot');
        if (spotRadio && !spotRadio.checked) {
            selectServiceType('spot');
        }
    } else {
        // スポット契約でない場合
        // 定期訪問と臨時訪問を表示
        showServiceType('regular');
        showServiceType('emergency');
        // スポットを非表示
        hideServiceType('spot');
    }
    
    // 契約のuse_remote_consultationフラグを確認
    const remoteConsultationValue = selectedOption.dataset.useRemoteConsultation;
    const useRemoteConsultation = remoteConsultationValue === '1' || remoteConsultationValue === 1;
    
    if (useRemoteConsultation) {
        showServiceType('remote_consultation');
    } else {
        hideServiceType('remote_consultation');
    }
    
    // 契約のuse_document_creationフラグを確認
    const documentCreationValue = selectedOption.dataset.useDocumentCreation;
    const useDocumentCreation = documentCreationValue === '1' || documentCreationValue === 1;
    
    if (useDocumentCreation) {
        showServiceType('document');
    } else {
        hideServiceType('document');
    }
}

// 役務種別を表示する関数
function showServiceType(serviceType) {
    const wrapper = document.querySelector(`.service-type-wrapper[data-service-type="${serviceType}"]`);
    if (wrapper) {
        wrapper.style.display = '';  // 空文字にすることでBootstrapのクラスが有効になる
        const radio = document.getElementById(serviceType);
        if (radio) {
            radio.disabled = false;
        }
    }
    // 表示後にレイアウトを再計算
    adjustServiceTypeLayout();
}

// 役務種別を非表示にする関数
function hideServiceType(serviceType) {
    const wrapper = document.querySelector(`.service-type-wrapper[data-service-type="${serviceType}"]`);
    if (wrapper) {
        wrapper.style.display = 'none';
        const radio = document.getElementById(serviceType);
        if (radio) {
            radio.disabled = true;
            // もし現在選択されている役務種別が非表示になる場合、代替の役務種別に変更
            if (radio.checked) {
                // スポット契約の場合はスポットを選択
                const spotRadio = document.getElementById('spot');
                if (spotRadio && !spotRadio.disabled) {
                    selectServiceType('spot');
                } else {
                    // それ以外は定期訪問を選択
                    const regularRadio = document.getElementById('regular');
                    if (regularRadio && !regularRadio.disabled) {
                        selectServiceType('regular');
                    }
                }
            }
        }
    }
    // 非表示後にレイアウトを再計算
    adjustServiceTypeLayout();
}

// 役務種別カードのレイアウトを調整する関数
function adjustServiceTypeLayout() {
    const allWrappers = document.querySelectorAll('.service-type-wrapper');
    
    // 表示されているカードの数をカウント
    let visibleCount = 0;
    allWrappers.forEach(wrapper => {
        if (wrapper.style.display !== 'none') {
            visibleCount++;
        }
    });
    
    // カードの数に応じてクラスを変更
    let colClass = 'col-md-3'; // デフォルトは4列（25%）
    
    if (visibleCount === 1) {
        colClass = 'col-md-12'; // 1つの場合は100%
    } else if (visibleCount === 2) {
        colClass = 'col-md-6'; // 2つの場合は50%ずつ
    } else if (visibleCount === 3) {
        colClass = 'col-md-4'; // 3つの場合は33.33%ずつ
    } else {
        colClass = 'col-md-3'; // 4つの場合は25%ずつ
    }
    
    // すべての表示されているカードにクラスを適用
    allWrappers.forEach(wrapper => {
        if (wrapper.style.display !== 'none') {
            // 既存のcol-*クラスを削除
            wrapper.classList.remove('col-md-3', 'col-md-4', 'col-md-6', 'col-md-12');
            // 新しいクラスを追加
            wrapper.classList.add(colClass);
        }
    });
}

// 締め処理状態チェック関数
function checkClosingStatus(contractId, serviceDate) {
    if (!contractId || !serviceDate) {
        hideClosingWarning();
        return;
    }
    
    const formData = new FormData();
    formData.append('contract_id', contractId);
    formData.append('service_date', serviceDate);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('/op_website/public/api/contracts/check-closing-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            isClosedPeriod = data.is_closed;
            
            if (isClosedPeriod) {
                const year = new Date(serviceDate).getFullYear();
                const month = new Date(serviceDate).getMonth() + 1;
                showClosingWarning(year, month, data.finalized_at);
                disableSubmitButton(true, 'closed');
            } else {
                hideClosingWarning();
                // 他のエラーがなければ送信ボタンを有効化
                const timeOverlapWarning = document.getElementById('time-overlap-warning');
                if (!timeOverlapWarning || !timeOverlapWarning.innerHTML) {
                    disableSubmitButton(false);
                }
            }
        }
    })
    .catch(error => {
        console.error('Closing status check error:', error);
        // エラー時は安全のため警告を表示
        hideClosingWarning();
    });
}

// 締め処理警告表示
function showClosingWarning(year, month, finalizedAt) {
    let warningDiv = document.getElementById('closing-warning');
    
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'closing-warning';
        warningDiv.className = 'alert alert-danger mt-3';
        
        // フォームの直前に挿入
        const form = document.querySelector('form');
        form.parentNode.insertBefore(warningDiv, form);
    }
    
    const finalizedDate = finalizedAt ? new Date(finalizedAt).toLocaleDateString('ja-JP') : '';
    
    warningDiv.innerHTML = `
        <div class="d-flex align-items-start">
            <i class="fas fa-lock fa-2x text-danger me-3"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>締め処理済み期間
                </h5>
                <p class="mb-2">
                    <strong>${year}年${month}月</strong>は既に締め処理が完了しています。
                </p>
                ${finalizedDate ? `<p class="mb-2"><small class="text-muted">確定日時: ${finalizedDate}</small></p>` : ''}
                <hr>
                <p class="mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    この期間には新しい役務記録を登録できません。別の日付を選択してください。
                </p>
            </div>
        </div>
    `;
    
    warningDiv.style.display = 'block';
    
    // 警告位置までスクロール
    warningDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// 締め処理警告非表示
function hideClosingWarning() {
    const warningDiv = document.getElementById('closing-warning');
    if (warningDiv) {
        warningDiv.style.display = 'none';
    }
    isClosedPeriod = false;
}

// 月間使用状況の取得（グローバルスコープに移動）
function loadMonthlyUsage(contractId, regularHours) {
    const serviceDate = serviceDateInput.value || new Date().toISOString().split('T')[0];
    const year = new Date(serviceDate).getFullYear();
    const month = new Date(serviceDate).getMonth() + 1;
    
    // APIエンドポイントで月間使用状況を取得
    fetch(`/op_website/public/api/contracts/${contractId}/monthly-usage?year=${year}&month=${month}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 訪問頻度に応じた上限時間を設定
            const contractInfo = data.contract_info || {};
            const visitFrequency = contractInfo.visit_frequency || 'monthly';
            
            currentMonthlyUsage = {
                regular_limit: data.regular_limit, // APIで計算された実際の上限時間
                current_used: data.regular_hours || 0,
                remaining_hours: data.remaining_hours || 0,
                usage_percentage: data.usage_percentage || 0,
                visit_frequency: visitFrequency
            };
            
            // デバッグログ
            console.log('Monthly usage loaded:', {
                contract_id: contractId,
                visit_frequency: visitFrequency,
                regular_limit: data.regular_limit,
                current_used: data.regular_hours,
                year: year,
                month: month
            });
            
            updateMonthlyUsageDisplay();
            calculateHours(); // 使用状況が更新されたら時間計算を再実行
        } else {
            console.error('Monthly usage load error:', data.error);
            // エラー時はフォールバック
            currentMonthlyUsage = {
                regular_limit: parseFloat(regularHours),
                current_used: 0,
                remaining_hours: parseFloat(regularHours),
                usage_percentage: 0,
                visit_frequency: 'monthly'
            };
            updateMonthlyUsageDisplay();
        }
    })
    .catch(error => {
        console.error('Monthly usage load error:', error);
        // エラー時はフォールバック
        currentMonthlyUsage = {
            regular_limit: parseFloat(regularHours),
            current_used: 0,
            remaining_hours: parseFloat(regularHours),
            usage_percentage: 0,
            visit_frequency: 'monthly'
        };
        updateMonthlyUsageDisplay();
    });
}

// 月間使用状況の表示更新（グローバルスコープに移動）
function updateMonthlyUsageDisplay() {
    const usageDiv = document.getElementById('monthly-usage-info');
    if (usageDiv && currentMonthlyUsage) {
        const usagePercent = Math.round(currentMonthlyUsage.usage_percentage * 10) / 10;
        const progressBarClass = usagePercent >= 100 ? 'bg-danger' : 
                               usagePercent >= 80 ? 'bg-warning' : 'bg-success';
        
        // 訪問頻度情報の取得
        const selectedOption = contractSelect.options[contractSelect.selectedIndex];
        const visitFrequency = selectedOption.dataset.visitFrequency || 'monthly';
        
        // 訪問頻度に応じたラベル
        let limitLabel = '定期訪問時間';
        let usageNote = '';
        
        switch(visitFrequency) {
            case 'weekly':
                limitLabel = '今月の上限時間';
                // 修正: より詳細な説明を追加
                usageNote = '<small class="text-muted">※毎週契約：祝日・非訪問日考慮で計算</small>';
                break;
            case 'bimonthly':
                const isVisitMonth = selectedOption.dataset.isVisitMonth === '1';
                limitLabel = isVisitMonth ? '隔月訪問時間' : '隔月契約（非訪問月）';
                usageNote = `<small class="text-muted">※隔月契約：${isVisitMonth ? '訪問月' : '非訪問月'}　　　　　　　　　　　　　　</small>`;
                break;
            default:
                limitLabel = '月間定期時間';
                usageNote = '<small class="text-muted">※毎月契約　　　　　　　　　　　　　　</small>';
                break;
        }
        
        usageDiv.innerHTML = `
            <h6><i class="fas fa-chart-bar me-2"></i>今月の使用状況</h6>
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <small>${limitLabel}</small>
                    <small>${formatHours(currentMonthlyUsage.current_used)} / ${formatHours(currentMonthlyUsage.regular_limit)}</small>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar ${progressBarClass}" style="width: ${Math.min(100, usagePercent)}%"></div>
                </div>
            </div>
            <p class="small mb-0">
                <strong>残り時間:</strong> ${formatHours(currentMonthlyUsage.remaining_hours)}<br>
                <strong>使用率:</strong> ${usagePercent}%
            </p>
            ${usageNote}
        `;
    }
}

// 時間計算
function getCurrentServiceType() {
    // チェックされたラジオボタンを優先
    const checked = document.querySelector('input[name="service_type"]:checked');
    if (checked) return checked.value;
    
    // hiddenフィールドを確認
    const hidden = document.querySelector('input[name="service_type"][type="hidden"]');
    if (hidden) return hidden.value;
    
    // どちらもない場合はデフォルト
    return 'regular';
}

function calculateHours() {
    const currentServiceType = getCurrentServiceType();

    // 時間入力が不要な役務種別の場合はスキップ
    if (!timeRequiredTypes.includes(currentServiceType)) {
        return;
    }
    
    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;
    
    if (startTime && endTime) {
        const start = new Date(`1970-01-01T${startTime}:00`);
        const end = new Date(`1970-01-01T${endTime}:00`);
        
        if (end < start) {
            end.setDate(end.getDate() + 1); // 日をまたぐ場合
        }
        
        const diffMs = end - start;
        const diffHours = diffMs / (1000 * 60 * 60);
        
        if (diffHours > 0) {
            let displayHours, displayMinutes;
            
            // 定期延長は15分切り上げ
            if (currentServiceType === 'extension') {
                // 15分切り上げ処理
                const roundedHours = Math.ceil(diffHours * 4) / 4;
                displayHours = Math.floor(roundedHours);
                displayMinutes = Math.round((roundedHours - displayHours) * 60);
            } else {
                // 定期訪問と臨時訪問は通常の時間計算
                displayHours = Math.floor(diffHours);
                displayMinutes = Math.round((diffHours - displayHours) * 60);
            }
            
            calculatedHours.textContent = `${displayHours}:${displayMinutes < 10 ? '0' : ''}${displayMinutes}`;
            hoursPreview.style.display = 'block';
            
            // 定期訪問の場合のみ月間定期時間延長チェック（分割なし）
            if (currentServiceType === 'regular') {
                checkRegularOvertimeWarning(diffHours);
            } else {
                overtimeInfo.textContent = '（契約時間の制約なし）';
                overtimeInfo.className = 'text-info ms-3';
                overtimeReasonGroup.style.display = 'none';
                hideOvertimeWarning();
            }
        } else {
            hoursPreview.style.display = 'none';
            overtimeReasonGroup.style.display = 'none';
            hideOvertimeWarning();
        }
    } else {
        hoursPreview.style.display = 'none';
        overtimeReasonGroup.style.display = 'none';
        hideOvertimeWarning();
    }
    
    // 時間重複チェック
    checkTimeOverlap();
}

// ============================================
// 累積時間チェックロジック(役務記録追加・編集画面用)
// ============================================

/**
 * 累積時間チェックAPIの呼び出し
 * 
 * @param {number} contractId - 契約ID
 * @param {string} serviceDate - 役務日(YYYY-MM-DD)
 * @param {number} serviceHours - 役務時間
 * @param {number} excludeRecordId - 除外する記録ID(編集時のみ、デフォルト0)
 */
function checkCumulativeHoursAPI(contractId, serviceDate, serviceHours, excludeRecordId = 0) {
    // 基本的なバリデーション
    if (!contractId || !serviceDate || serviceHours <= 0) {
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
        return;
    }

    fetch('/op_website/public/api/contracts/check-cumulative-hours', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            contract_id: contractId,
            service_date: serviceDate,
            service_hours: serviceHours,
            exclude_record_id: excludeRecordId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            handleCumulativeCheckResult(data);
        } else {
            console.error('Cumulative hours check error:', data.error);
            // エラー時は従来のロジックにフォールバック
            fallbackToSimpleCheck(serviceHours);
        }
    })
    .catch(error => {
        console.error('Cumulative hours check API error:', error);
        // エラー時は従来のロジックにフォールバック
        fallbackToSimpleCheck(serviceHours);
    });
}

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
    } else {
        // 正常範囲
        hideOvertimeWarning();
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        document.getElementById('overtime_reason').required = false;
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
            <strong>・累積時間(今回の役務より前):</strong> ${formatHours(cumulativeHours)}<br>
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

/**
 * 定期訪問の月間定期時間延長警告チェック(累積時間ベース)
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
        return;
    }
    
    const contractId = contractSelect.value;
    const serviceDate = serviceDateInput.value;
    
    if (!contractId || !serviceDate) {
        overtimeInfo.textContent = '';
        overtimeReasonGroup.style.display = 'none';
        hideOvertimeWarning();
        return;
    }
    
    // 累積時間チェックAPIを呼び出し(編集時は0を渡す)
    checkCumulativeHoursAPI(contractId, serviceDate, serviceHours, 0);
}

// 月間定期時間延長警告の表示（グローバルスコープに移動）
function showOvertimeWarning(serviceHours, newTotalHours, customMessage) {
    let warningDiv = document.getElementById('overtime-warning');
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'overtime-warning';
        warningDiv.className = 'alert alert-warning mt-2';
        hoursPreview.appendChild(warningDiv);
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
            <strong>・上限種別:</strong> ${limitDescription}
        </small>
    `;
}

// 延長警告の非表示（グローバルスコープに移動）
function hideOvertimeWarning() {
    const warningDiv = document.getElementById('overtime-warning');
    if (warningDiv) {
        warningDiv.remove();
    }
}

// 時間重複チェック（グローバルスコープに移動）
function checkTimeOverlap() {
    const currentServiceType = getCurrentServiceType();
    
    // 時間入力が不要な役務種別の場合はスキップ
    if (!timeRequiredTypes.includes(currentServiceType)) {
        clearTimeWarning();
        // 締め処理エラーがない場合のみ有効化
        if (!isClosedPeriod) {
            disableSubmitButton(false);
        }
        return;
    }
    
    const serviceDate = serviceDateInput.value;
    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;
    
    if (serviceDate && startTime && endTime) {
        // 時間の妥当性チェック
        if (startTime >= endTime) {
            showTimeWarning('終了時刻は開始時刻より後に設定してください。', 'danger');
            disableSubmitButton(true, 'time_error');
            return;
        }
        
        // サーバーサイドでの重複チェック
        checkTimeOverlapAPI(serviceDate, startTime, endTime);
    } else {
        clearTimeWarning();
        // 締め処理エラーがない場合のみ有効化
        if (!isClosedPeriod) {
            disableSubmitButton(false);
        }
    }
}

// その他のヘルパー関数もグローバルスコープに移動
function disableSubmitButton(disabled, reason = 'overlap') {

    console.log("=== disableSubmitButton:" + reason);

    const submitButton = document.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = disabled;
        if (disabled) {
            submitButton.classList.add('disabled');
            if (reason === 'closed') {
                submitButton.innerHTML = '<i class="fas fa-lock me-1"></i>締め処理済み';
            } else if (reason === 'time_error') {
                submitButton.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>時刻エラー';
            } else {
                submitButton.innerHTML = '<i class="fas fa-ban me-1"></i>重複エラー';
            }
        } else {
            submitButton.classList.remove('disabled');
            submitButton.innerHTML = '<i class="fas fa-save me-1"></i>登録';
        }
    }
}

function showTimeWarning(message, type = 'warning') {
    const warningDiv = document.getElementById('time-overlap-warning');
    warningDiv.innerHTML = `
        <div class="alert alert-${type}">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
}

function clearTimeWarning() {
    const warningDiv = document.getElementById('time-overlap-warning');
    warningDiv.innerHTML = '';
}

// 隔月契約の非訪問月チェック（グローバルスコープに移動）
function checkBimonthlyRestriction(serviceType) {
    const selectedOption = contractSelect.options[contractSelect.selectedIndex];
    
    if (!selectedOption.value) {
        return false;
    }
    
    // データ属性から契約情報を取得
    const visitFrequency = selectedOption.dataset.visitFrequency;
    const isVisitMonth = selectedOption.dataset.isVisitMonth === '1';
    
    // 隔月契約の非訪問月で定期訪問を選択しようとした場合
    const shouldDisable = visitFrequency === 'bimonthly' && !isVisitMonth && serviceType === 'regular';
    
    return shouldDisable;
}

// 役務種別選択関数をグローバルスコープで先に定義
function selectServiceType(type) {
    // DOMから直接取得してカードの選択状態をリセット
    const serviceTypeCards = document.querySelectorAll('.service-type-card');
    serviceTypeCards.forEach(card => {
        card.classList.remove('selected');
    });
    
    // 対応するラジオボタンをチェック
    const radio = document.getElementById(type);
    if (radio && !radio.disabled) {
        radio.checked = true;
        // 選択されたカードをハイライト
        const selectedCard = radio.closest('.service-type-card');
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
        updateServiceTypeInfo(type);
    }
}

// 訪問種別選択関数もグローバルスコープで先に定義
function selectVisitType(type) {
    // DOMから直接取得してカードの選択状態をリセット
    const visitTypeCards = document.querySelectorAll('.visit-type-card');
    visitTypeCards.forEach(card => {
        card.classList.remove('selected');
    });
    
    // 対応するラジオボタンをチェック
    const radio = document.getElementById('visit_' + type);
    if (radio) {
        radio.checked = true;
        // 選択されたカードをハイライト
        const selectedCard = radio.closest('.visit-type-card');
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
    }
}

// 修正されたupdateRegularServiceTypeAvailability関数（隔月契約用）
function updateRegularServiceTypeAvailability(isRegularDisabled) {
    const regularCard = document.querySelector('.service-type-card[data-type="regular"]');
    const regularRadio = document.getElementById('regular');
    
    if (!regularCard) {
        console.error('Regular card not found!');
        return;
    }
    
    if (isRegularDisabled) {
        // 強制的にスタイルを適用
        regularCard.style.setProperty('opacity', '0.5', 'important');
        regularCard.style.setProperty('cursor', 'not-allowed', 'important');
        regularCard.style.setProperty('pointer-events', 'none', 'important');
        regularCard.style.setProperty('background-color', '#f8f9fa', 'important');
        regularCard.style.setProperty('border-color', '#dee2e6', 'important');
        regularCard.classList.add('disabled');
        
        // カード内の要素も灰色に
        const cardTitle = regularCard.querySelector('.card-title');
        const cardText = regularCard.querySelector('small.text-muted');
        const cardIcon = regularCard.querySelector('.fas');
        const checkLabel = regularCard.querySelector('.form-check-label');
        
        [cardTitle, cardText, cardIcon, checkLabel].forEach((element) => {
            if (element) {
                element.style.setProperty('color', '#6c757d', 'important');
            }
        });
        
        if (regularRadio) {
            regularRadio.disabled = true;
        }
        
        // 現在定期訪問が選択されている場合は臨時訪問に変更
        if (regularRadio && regularRadio.checked) {
            // selectServiceType関数が定義されているかチェック
            if (typeof selectServiceType === 'function') {
                selectServiceType('emergency');
            } else {
                // 直接的に変更
                regularRadio.checked = false;
                const emergencyRadio = document.getElementById('emergency');
                if (emergencyRadio) {
                    emergencyRadio.checked = true;
                    document.querySelectorAll('.service-type-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    emergencyRadio.closest('.service-type-card').classList.add('selected');
                }
            }
        }
        
        showBimonthlyRestrictionMessage();
        
    } else {
        // スタイルをリセット
        regularCard.style.removeProperty('opacity');
        regularCard.style.removeProperty('cursor');
        regularCard.style.removeProperty('pointer-events');
        regularCard.style.removeProperty('background-color');
        regularCard.style.removeProperty('border-color');
        regularCard.classList.remove('disabled');
        
        // カード内の要素の色もリセット
        const cardTitle = regularCard.querySelector('.card-title');
        const cardText = regularCard.querySelector('small.text-muted');
        const cardIcon = regularCard.querySelector('.fas');
        const checkLabel = regularCard.querySelector('.form-check-label');
        
        [cardTitle, cardText, cardIcon, checkLabel].forEach((element) => {
            if (element) {
                element.style.removeProperty('color');
            }
        });
        
        if (regularRadio) {
            regularRadio.disabled = false;
        }
        
        hideBimonthlyRestrictionMessage();
    }
}

// DOMContentLoadedイベント内の処理を修正
document.addEventListener('DOMContentLoaded', function() {
    // DOMエレメントの参照を取得（グローバル変数に代入）
    contractSelect = document.getElementById('contract_id');
    serviceDateInput = document.getElementById('service_date');
    contractInfoDiv = document.getElementById('contract-info');
    hoursPreview = document.getElementById('hours-preview');
    calculatedHours = document.getElementById('calculated-hours');
    overtimeInfo = document.getElementById('overtime-info');
    overtimeReasonGroup = document.getElementById('overtime-reason-group');
    serviceTypeHelp = document.getElementById('service-type-help');
    startTimeInput = document.getElementById('start_time');
    endTimeInput = document.getElementById('end_time');
    
    // 追加のDOMエレメント参照（グローバル変数に代入）
    visitTypeSection = document.getElementById('visit-type-section');
    timeInputSection = document.getElementById('time-input-section');
    hoursPreviewSection = document.getElementById('hours-preview-section');
    noTimeNotice = document.getElementById('no-time-notice');
    noTimeServiceLabel = document.getElementById('no-time-service-label');
    descriptionRequired = document.getElementById('description-required');
    descriptionOptional = document.getElementById('description-optional');
    
    const serviceTypeCards = document.querySelectorAll('.service-type-card');
    const serviceTypeRadios = document.querySelectorAll('input[name="service_type"]');
    const visitTypeCards = document.querySelectorAll('.visit-type-card');
    const visitTypeRadios = document.querySelectorAll('input[name="visit_type"]');
    
    // 時刻入力の正規化処理
    function handleTimeInput(event) {
        const input = event.target;
        const normalizedTime = normalizeTime(input.value);
        
        // 正規化された時刻を表示用に HH:MM 形式に戻す
        if (normalizedTime) {
            const displayTime = normalizedTime.substring(0, 5); // HH:MM部分のみ
            input.value = displayTime;
        }
        
        // 時間計算を再実行
        calculateHours();
        
        // 契約が既に選択されている場合の初期チェック
        if (contractSelect.value) {
            const currentServiceType = getCurrentServiceType();
            if (currentServiceType) {
                updateServiceTypeInfo(currentServiceType);
            }
        }
    }
    
    // 時刻入力フィールドにイベントリスナーを追加
    const timeInputs = document.querySelectorAll('.time-input');
    timeInputs.forEach(input => {
        input.addEventListener('change', handleTimeInput);
        input.addEventListener('blur', handleTimeInput);
        
        // 初期値の正規化
        if (input.value) {
            handleTimeInput({ target: input });
        }
    });
    
    // API による時間重複チェック
    function checkTimeOverlapAPI(serviceDate, startTime, endTime) {
        const formData = new FormData();
        formData.append('service_date', serviceDate);
        formData.append('start_time', normalizeTime(startTime));
        formData.append('end_time', normalizeTime(endTime));
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        fetch('/op_website/public/api/service_records/check-time-overlap', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                clearTimeWarning();
                // 締め処理エラーがない場合のみ有効化
                if (!isClosedPeriod) {
                    disableSubmitButton(false);
                }
            } else {
                showTimeWarning(data.message, 'danger');
                disableSubmitButton(true, 'overlap');
            }
        })
        .catch(error => {
            console.error('Time overlap check error:', error);
            showTimeWarning('時間重複チェックでエラーが発生しました。', 'warning');
            // エラー時は締め処理を確認して適切に処理
            if (!isClosedPeriod) {
                disableSubmitButton(false);
            }
        });
    }
    
    // 保存された選択状態を復元
    const savedServiceType = '<?= $selectedServiceType ?>';
    const savedVisitType = '<?= $formData['visit_type'] ?? 'visit' ?>';
    
    // 役務種別カードの選択状態を復元
    if (savedServiceType) {
        selectServiceType(savedServiceType);
    }
    
    // 訪問種別の選択状態を復元
    if (savedVisitType) {
        selectVisitType(savedVisitType);
    }
    
    // イベントリスナーの設定
    serviceTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.type;
            const radio = document.getElementById(type);
            
            // ラジオボタンが無効化されていない場合のみ選択を許可
            if (radio && !radio.disabled) {
                selectServiceType(type);
            }
        });
    });
    
    visitTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.type;
            selectVisitType(type);
        });
    });
    
    serviceTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked && !this.disabled) {
                selectServiceType(this.value);
            }
        });
    });
    
    visitTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                selectVisitType(this.value);
            }
        });
    });
    
    // グローバルスコープに関数を定義（必要であれば）
    window.selectServiceType = selectServiceType;
    window.selectVisitType = selectVisitType;
    window.checkTimeOverlapAPI = checkTimeOverlapAPI;
    
    // イベントリスナー
    contractSelect.addEventListener('change', function() {
        updateContractInfo();
        updateServiceTypeAvailability(); // 役務種別の表示/非表示を更新
        
        // 現在選択されている役務種別をチェック
        const currentServiceType = getCurrentServiceType();
        if (currentServiceType) {
            updateServiceTypeInfo(currentServiceType);
        }
    });
    
    startTimeInput.addEventListener('change', calculateHours);
    endTimeInput.addEventListener('change', calculateHours);
    
    serviceDateInput.addEventListener('change', function() {
        const newServiceDate = this.value;
        
        if (!newServiceDate) return;
        
        // 契約リストを役務日に基づいて再取得
        updateContractsByServiceDate(newServiceDate);
        
        // 既存の処理(月間使用状況の再取得など)
        if (contractSelect.value) {
            const selectedOption = contractSelect.options[contractSelect.selectedIndex];
            loadMonthlyUsage(selectedOption.value, selectedOption.dataset.regularHours);
        }
        calculateHours();
        updateContractVisitMonth();
        
        // 締め処理状態もチェック
        if (contractSelect.value) {
            checkClosingStatus(contractSelect.value, this.value);
        }
        
        // 重要:日付変更後は必ず制限状態を再チェック
        setTimeout(() => {
            forceCheckRestrictions();
        }, 200);
    });
    
    // 初期表示
    updateContractInfo();
    // 初期表示時に役務種別に応じた表示を設定（必須マーク含む）
    const initialServiceType = getCurrentServiceType();
    if (initialServiceType) {
        updateServiceTypeInfo(initialServiceType);
    }
    calculateHours();
    updateServiceTypeAvailability(); // 初期表示時にも役務種別の表示/非表示を設定
    adjustServiceTypeLayout(); // 初期表示時にレイアウトも調整
    
    // 初期制限チェック
    if (contractSelect.value) {
        setTimeout(() => {
            forceCheckRestrictions();
        }, 100);
    }
});

/**
 * 役務日に基づいて契約リストを更新
 */
function updateContractsByServiceDate(serviceDate) {
    const currentContractId = contractSelect.value;
    
    const formData = new FormData();
    formData.append('service_date', serviceDate);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('/op_website/public/api/service_records/contracts-by-date', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 契約リストを更新
            updateContractSelectOptions(data.contracts, currentContractId);
            
            // 現在選択されている契約が新しいリストに存在するか確認
            const contractStillExists = data.contracts.some(c => c.id == currentContractId);
            
            if (!contractStillExists && currentContractId) {
                // 選択されていた契約が有効期間外になった場合
                contractSelect.value = '';
                showContractChangedWarning(serviceDate);
                updateContractInfo(); // 契約情報をクリア
                updateServiceTypeAvailability(); // 役務種別の表示/非表示も更新
            } else if (currentContractId) {
                // 契約が有効な場合は情報を更新
                updateContractInfo();
                updateServiceTypeAvailability(); // 役務種別の表示/非表示も更新
            }
        } else {
            console.error('Failed to load contracts:', data.error);
        }
    })
    .catch(error => {
        console.error('Error loading contracts:', error);
    });
}

/**
 * 契約セレクトボックスのオプションを更新
 */
function updateContractSelectOptions(contracts, selectedContractId) {
    // 既存のオプションをクリア(最初の「選択してください」オプション以外)
    while (contractSelect.options.length > 1) {
        contractSelect.remove(1);
    }
    
    // 新しいオプションを追加
    contracts.forEach(contract => {
        const isVisitMonth = contract.is_visit_month;
        const option = document.createElement('option');
        option.value = contract.id;
        option.dataset.regularHours = contract.regular_visit_hours;
        option.dataset.company = contract.company_name;
        option.dataset.branch = contract.branch_name;
        option.dataset.visitFrequency = contract.visit_frequency || 'monthly';
        option.dataset.isVisitMonth = isVisitMonth ? '1' : '0';
        option.dataset.useRemoteConsultation = contract.use_remote_consultation || 0;
        option.dataset.useDocumentCreation = contract.use_document_creation || 0;
        
        let displayText = `${contract.company_name} - ${contract.branch_name}`;
        if (contract.visit_frequency === 'bimonthly') {
            displayText += isVisitMonth ? ' [訪問月]' : ' [非訪問月]';
        }
        
        option.textContent = displayText;
        
        // 以前選択されていた契約IDと一致する場合は選択状態にする
        if (contract.id == selectedContractId) {
            option.selected = true;
        }
        
        contractSelect.appendChild(option);
    });
}

/**
 * 契約が有効期間外になったことを警告
 */
function showContractChangedWarning(serviceDate) {
    const warningDiv = document.createElement('div');
    warningDiv.className = 'alert alert-warning alert-dismissible fade show mt-3';
    warningDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>注意:</strong> 選択されていた契約は指定された役務日(${serviceDate})に対して有効ではありません。
        契約を選択し直してください。
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // 契約セレクトボックスの直後に挿入
    const contractDiv = contractSelect.closest('.mb-3');
    contractDiv.parentNode.insertBefore(warningDiv, contractDiv.nextSibling);
    
    // 3秒後に自動で消す
    setTimeout(() => {
        warningDiv.remove();
    }, 5000);
}

// 定期訪問サービスの制限状態をチェックする専用関数
function checkRegularServiceRestriction() {
    const selectedOption = contractSelect.options[contractSelect.selectedIndex];
    
    if (!selectedOption.value) {
        return false;
    }
    
    const visitFrequency = selectedOption.dataset.visitFrequency;
    const isVisitMonth = selectedOption.dataset.isVisitMonth === '1';
    
    // 隔月契約の非訪問月の場合のみ制限
    const shouldDisable = visitFrequency === 'bimonthly' && !isVisitMonth;
    
    return shouldDisable;
}

// 修正されたupdateServiceTypeInfo関数（内部関数定義を削除）
function updateServiceTypeInfo(serviceType) {
    // DOMエレメントの存在チェック
    if (!serviceTypeHelp) {
        console.error('serviceTypeHelp not initialized');
        return;
    }
    
    serviceTypeHelp.textContent = serviceTypeDescriptions[serviceType];
    
    const requiresTime = timeRequiredTypes.includes(serviceType);
    const requiresVisitType = visitTypeRequiredTypes.includes(serviceType);
    const isOtherType = serviceType === 'other';

    // 重要：定期訪問の制限状態は現在選択中の役務種別に関係なく常にチェック
    const isRegularDisabled = checkRegularServiceRestriction();
    
    // 役務種別カードの無効化処理
    updateRegularServiceTypeAvailability(isRegularDisabled);
    
    // 訪問種別セクション
    if (visitTypeSection) {
        if (requiresVisitType) {
            visitTypeSection.style.display = 'block';
            if (!document.querySelector('input[name="visit_type"]:checked')) {
                selectVisitType('visit');
            }
        } else {
            visitTypeSection.style.display = 'none';
        }
    }
    
    // 請求金額セクション（その他のみ）
    const billingAmountSection = document.getElementById('billing-amount-section');
    const billingAmountInput = document.getElementById('direct_billing_amount');
    const otherNotice = document.getElementById('other-notice');
    
    if (billingAmountSection && billingAmountInput) {
        if (isOtherType) {
            billingAmountSection.style.display = 'block';
            billingAmountInput.required = true;
            if (otherNotice) {
                otherNotice.style.display = 'block';
            }
        } else {
            billingAmountSection.style.display = 'none';
            billingAmountInput.required = false;
            if (otherNotice) {
                otherNotice.style.display = 'none';
            }
        }
    }
    
    if (timeInputSection && hoursPreviewSection && noTimeNotice) {
        if (requiresTime) {
            timeInputSection.style.display = 'block';
            hoursPreviewSection.style.display = 'block';
            noTimeNotice.style.display = 'none';
            
            if (startTimeInput && endTimeInput) {
                startTimeInput.required = true;
                endTimeInput.required = true;
            }
            
            // スポットの場合は役務内容を必須に
            const descriptionField = document.getElementById('description');
            if (descriptionField && descriptionRequired && descriptionOptional) {
                if (serviceType === 'spot') {
                    descriptionField.required = true; // 必須
                    descriptionRequired.style.display = 'inline'; // 必須マークを表示
                    descriptionOptional.textContent = '必須';
                } else {
                    // 定期訪問・臨時訪問は役務内容を任意に変更
                    descriptionField.required = false; // 必須を解除
                    descriptionRequired.style.display = 'none'; // 必須マークを非表示
                    descriptionOptional.textContent = '任意';
                }
            }
            
            if (hoursPreview && overtimeReasonGroup) {
                hoursPreview.style.display = 'none';
                overtimeReasonGroup.style.display = 'none';
                hideOvertimeWarning();
            }
        } else {
            // 書面作成・遠隔相談・その他の場合
            timeInputSection.style.display = 'none';
            hoursPreviewSection.style.display = 'none';
            
            if (isOtherType) {
                noTimeNotice.style.display = 'none';
            } else {
                noTimeNotice.style.display = 'block';
            }
            
            if (startTimeInput && endTimeInput) {
                startTimeInput.required = false;
                endTimeInput.required = false;
            }
            
            // 書面作成・遠隔相談・その他は役務内容を必須に設定
            const descriptionField = document.getElementById('description');
            if (descriptionField && descriptionRequired && descriptionOptional && noTimeServiceLabel) {
                descriptionField.required = true;
                descriptionRequired.style.display = 'inline';
                descriptionOptional.textContent = '必須';
                if (!isOtherType) {
                    noTimeServiceLabel.textContent = serviceTypeLabels[serviceType];
                }
            }
        }
    }
    
    updateContractInfo();
    calculateHours();
}



// 隔月契約制限メッセージの表示
function showBimonthlyRestrictionMessage() {
    let messageDiv = document.getElementById('bimonthly-restriction-message');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.id = 'bimonthly-restriction-message';
        messageDiv.className = 'alert alert-warning mt-2';
        
        // 役務種別セクションの後に挿入
        const serviceTypeSection = document.querySelector('.row .col-md-12 .mb-3');
        if (serviceTypeSection && serviceTypeSection.parentNode) {
            serviceTypeSection.parentNode.insertBefore(messageDiv, serviceTypeSection.nextSibling);
        }
    }
    
    messageDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>注意:</strong> 隔月契約の非訪問月のため、定期訪問は選択できません。
    `;
    messageDiv.style.display = 'block';
}

// 隔月契約制限メッセージの非表示
function hideBimonthlyRestrictionMessage() {
    const messageDiv = document.getElementById('bimonthly-restriction-message');
    if (messageDiv) {
        messageDiv.style.display = 'none';
    }
}

// 契約の訪問月判定を更新
function updateContractVisitMonth() {
    const selectedOption = contractSelect.options[contractSelect.selectedIndex];
    
    if (!selectedOption.value || !serviceDateInput.value) {
        return;
    }
    
    const contractId = selectedOption.value;
    const serviceDate = serviceDateInput.value;
    const visitFrequency = selectedOption.dataset.visitFrequency;
    
    // 隔月契約の場合のみAPIで再計算
    if (visitFrequency === 'bimonthly') {
        checkBimonthlyVisitMonth(contractId, serviceDate, function(isVisitMonth) {
            // データ属性を更新
            selectedOption.dataset.isVisitMonth = isVisitMonth ? '1' : '0';
            
            // 契約情報表示を更新
            updateContractInfo();
            
            // APIコールバック内でも制限チェック
            const isRegularDisabled = checkRegularServiceRestriction();
            updateRegularServiceTypeAvailability(isRegularDisabled);
        });
    } else {
        // 隔月契約以外でも契約情報は更新
        updateContractInfo();
    }
}

// 即座に制限状態をチェックするヘルパー関数
function forceCheckRestrictions() {
    // 契約が選択されているかチェック
    if (!contractSelect.value) {
        return;
    }
    
    const isRegularDisabled = checkRegularServiceRestriction();
    updateRegularServiceTypeAvailability(isRegularDisabled);
}

// 隔月契約の訪問月判定をAPIで取得
function checkBimonthlyVisitMonth(contractId, serviceDate, callback) {
    const formData = new FormData();
    formData.append('contract_id', contractId);
    formData.append('service_date', serviceDate);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('/op_website/public/api/contracts/check-visit-month', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            callback(data.is_visit_month);
        } else {
            console.error('Visit month check failed:', data.error);
            // エラー時は安全のため訪問月として扱う
            callback(true);
        }
    })
    .catch(error => {
        console.error('Visit month check error:', error);
        // エラー時は安全のため訪問月として扱う
        callback(true);
    });
}

// 従来のバリデーション関数
window.validateForm = function() {

    if (!document.getElementById('contract_id').value) {
        alert('契約を選択してください。');
        return false;
    }

    const currentServiceType = getCurrentServiceType();
    
    if (!currentServiceType) {
        alert('役務種別を選択してください。');
        return false;
    }

    // 締め処理チェックを追加
    if (isClosedPeriod) {
        alert('選択された期間は締め処理済みのため、新しい役務記録を登録できません。');
        return false;
    }
    
    // 隔月契約制限チェック
    if (checkBimonthlyRestriction(currentServiceType)) {
        alert('隔月契約の非訪問月のため、定期訪問は選択できません。');
        return false;
    }
    
    // 訪問種別が必要な役務種別の場合
    if (visitTypeRequiredTypes.includes(currentServiceType)) {
        const currentVisitType = document.querySelector('input[name="visit_type"]:checked')?.value;
        if (!currentVisitType) {
            alert('訪問種別を選択してください。');
            return false;
        }
    }
    
    // 時間入力が必要な役務種別の場合
    if (timeRequiredTypes.includes(currentServiceType)) {
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        
        if (!startTime || !endTime) {
            alert('開始時刻と終了時刻を入力してください。');
            return false;
        }
        
        // 時刻を正規化して元のフィールドに設定
        const normalizedStartTime = normalizeTime(startTime);
        const normalizedEndTime = normalizeTime(endTime);
        
        // 元のフィールドに正規化された値を設定
        document.getElementById('start_time').value = normalizedStartTime;
        document.getElementById('end_time').value = normalizedEndTime;
        
    } else {
        // 時間入力が不要な役務種別の場合（書面作成・遠隔相談）
        const description = document.getElementById('description').value.trim();
        
        if (!description) {
            alert(`${serviceTypeLabels[currentServiceType]}では業務内容の入力が必須です。`);
            document.getElementById('description').focus();
            return false;
        }
    }
    
    return true;
};

// 登録前の自動保存チェック
window.validateAndCheckAutoSave = function(event) {
    // まず従来のバリデーションを実行
    if (!validateForm()) {
        return false;
    }
    
    const description = document.getElementById('description').value.trim();
    
    // 役務内容が入力されている場合、テンプレート保存チェック
    if (description && description.length >= 1) {
        event.preventDefault();
        checkAutoSaveBeforeSubmit(description);
        return false;
    } else {
        // 内容が少ない場合は直接送信
        return true;
    }
};

// 登録前の自動保存チェック
function checkAutoSaveBeforeSubmit(description) {
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
        console.error('Auto save check error:', error);
        
        // エラーの場合でもテンプレート保存を提案
        showAutoSaveModalBeforeSubmit(description);
    });
}

// 登録前のテンプレート保存モーダル表示
function showAutoSaveModalBeforeSubmit(content) {
    const templatePreview = document.getElementById('template-preview');
    const modal = document.getElementById('autoSaveModal');
    
    if (!templatePreview || !modal) {
        console.error('Template preview or modal not found');
        return;
    }
    
    // プレビューに内容を設定
    templatePreview.textContent = content;
    
    // モーダルの状態を「登録前」に設定
    modal.setAttribute('data-context', 'before-submit');
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// フォーム送信を実際に実行
function proceedWithFormSubmit() {
    // フォーム送信フラグを立てる
    isSubmittingForm = true;
    
    // 送信ボタンを無効化して多重送信を防止
    const submitButton = document.querySelector('button[type="submit"]');
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

// テンプレート関連の関数群（active.phpと同じ）
function showTemplateModal() {
    loadTemplates();
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    modal.show();
}

function loadTemplates() {
    fetch('<?= base_url('api/service_templates') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                templates = data.data;
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

function createTemplateItem(template, type) {
    const preview = template.preview || getPreviewText(template.content, 80);
    const usageCount = template.usage_count || 0;
    const lastUsed = template.last_used_at ? new Date(template.last_used_at).toLocaleDateString('ja-JP') : '未使用';
    
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

function updateTemplateUsage(templateId) {
    const formData = new FormData();
    formData.append('template_id', templateId);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('<?= base_url('api/service_templates/use') ?>', {
        method: 'POST',
        body: formData
    }).catch(error => {
        console.error('使用統計更新エラー:', error);
    });
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
            
            // 登録前のコンテキストの場合はフォーム送信を続行
            const modal = document.getElementById('autoSaveModal');
            if (modal.getAttribute('data-context') === 'before-submit') {
                setTimeout(() => {
                    proceedWithFormSubmit();
                }, 500); // メッセージ表示の時間を確保
            }
        } else {
            showMessage(data.error || 'テンプレートの保存に失敗しました', 'error');
        }
    })
    .catch(error => {
        console.error('テンプレート保存エラー:', error);
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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>