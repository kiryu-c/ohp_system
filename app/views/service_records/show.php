<?php
// app/views/service_records/show.php - 訪問種別対応版

// フィールドラベル取得用のヘルパー関数（最初に定義）
function getFieldLabel($field) {
    $labels = [
        'service_date' => '役務日',
        'start_time' => '開始時刻',
        'end_time' => '終了時刻',
        'service_type' => '役務種別',
        'visit_type' => '訪問種別',
        'description' => '役務内容',
        'overtime_reason' => '延長理由'
    ];
    
    return $labels[$field] ?? $field;
}

// 役務種別の情報を定義（新種別を追加）
$serviceTypeInfo = [
    'regular' => [
        'class' => 'success', 
        'icon' => 'calendar-check', 
        'label' => '定期訪問',
        'requires_time' => true,
        'requires_visit_type' => true
    ],
    'emergency' => [
        'class' => 'warning', 
        'icon' => 'exclamation-triangle', 
        'label' => '臨時訪問',
        'requires_time' => true,
        'requires_visit_type' => true
    ],
    'extension' => [
        'class' => 'info', 
        'icon' => 'clock', 
        'label' => '定期延長',
        'requires_time' => true,
        'requires_visit_type' => true
    ],
    'spot' => [
        'class' => 'info', 
        'icon' => 'clock', 
        'label' => 'スポット',
        'requires_time' => true,
        'requires_visit_type' => true
    ],
    'document' => [
        'class' => 'primary', 
        'icon' => 'file-alt', 
        'label' => '書面作成',
        'requires_time' => false,
        'requires_visit_type' => false
    ],
    'remote_consultation' => [
        'class' => 'secondary', 
        'icon' => 'envelope', 
        'label' => '遠隔相談',
        'requires_time' => false,
        'requires_visit_type' => false
    ],
    'other' => [
        'class' => 'dark', 
        'icon' => 'plus', 
        'label' => 'その他',
        'requires_time' => false,
        'requires_visit_type' => false
    ]
];

// 訪問種別の情報を定義
$visitTypeInfo = [
    'visit' => [
        'class' => 'success',
        'icon' => 'walking',
        'label' => '訪問',
        'description' => '現地への物理的な訪問'
    ],
    'online' => [
        'class' => 'info',
        'icon' => 'laptop',
        'label' => 'オンライン',
        'description' => 'リモートでの実施（オンライン会議など）'
    ]
];

// 現在の役務種別と訪問種別を取得
$currentServiceType = $record['service_type'] ?? 'regular';
$currentVisitType = $record['visit_type'] ?? 'visit';
$serviceType = $serviceTypeInfo[$currentServiceType] ?? $serviceTypeInfo['regular'];
$visitType = $visitTypeInfo[$currentVisitType] ?? $visitTypeInfo['visit'];
$requiresTime = $serviceType['requires_time'];
$requiresVisitType = $serviceType['requires_visit_type'];
?>

<!-- タイムライン表示用CSS（最初に出力） -->
<style>
/* タイムライン表示用CSS */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    z-index: 1;
}

.timeline-current .timeline-marker {
    border-color: #007bff;
    background: #007bff;
    color: white;
}

.timeline-content {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-left: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-header {
    margin-bottom: 10px;
}

.timeline-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.timeline-body {
    margin-top: 10px;
}

.timeline-metadata details {
    cursor: pointer;
}

.timeline-metadata summary {
    list-style: none;
    outline: none;
}

.timeline-metadata summary::-webkit-details-marker {
    display: none;
}

.timeline-metadata summary:before {
    content: '▶';
    margin-right: 5px;
    transition: transform 0.2s;
}

.timeline-metadata details[open] summary:before {
    transform: rotate(90deg);
}

/* 時間なし役務用のスタイル */
.no-time-service {
    background-color: #f8f9fa;
    border-left: 4px solid #6c757d;
}

.time-not-applicable {
    color: #6c757d;
    font-style: italic;
}

/* 訪問種別表示用のスタイル */
.visit-type-display {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 0.5rem;
    margin-top: 0.5rem;
}

.visit-type-badge {
    font-size: 0.9rem;
    padding: 0.4rem 0.8rem;
}

.visit-type-description {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* オンライン実施の特別スタイル */
.online-service-highlight {
    background: linear-gradient(135deg, rgba(13, 202, 240, 0.1) 0%, rgba(13, 202, 240, 0.05) 100%);
    border-left: 4px solid #0dcaf0;
}

/* 訪問実施の特別スタイル */
.visit-service-highlight {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.05) 100%);
    border-left: 4px solid #198754;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .timeline {
        padding-left: 20px;
    }
    
    .timeline-marker {
        left: -15px;
        width: 24px;
        height: 24px;
        font-size: 10px;
    }
    
    .timeline-content {
        margin-left: 10px;
        padding: 10px;
    }
    
    .timeline-title {
        font-size: 0.9rem;
    }
    
    .visit-type-display {
        padding: 0.25rem;
    }
    
    .visit-type-badge {
        font-size: 0.8rem;
        padding: 0.3rem 0.6rem;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-eye me-2"></i>役務記録詳細</h2>
    <div>
        <?php if ($userType === 'doctor' && $requiresTime && $record['service_hours'] > 0 && ($record['status'] ?? '') !== 'finalized' && ($record['visit_type'] ?? 'visit') !== 'online'): ?>
            <!-- 交通費登録ボタン（産業医のみ、時間ありの完了した役務のみ、締め済みでない場合のみ、訪問種別がオンラインでない場合のみ） -->
            <a href="<?= base_url("travel_expenses/create/{$record['id']}") ?>" 
                class="btn btn-info btn-sm me-2">
                <i class="fas fa-train me-1"></i>交通費登録
            </a>
        <?php endif; ?>
        <?php if (($record['status'] ?? '') !== 'approved' && $userType === 'doctor'): ?>
            <a href="<?= base_url("service_records/{$record['id']}/edit") ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i>編集
            </a>
        <?php endif; ?>
        <a href="<?= base_url('service_records') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>一覧に戻る
        </a>
    </div>
</div>

<!-- 役務記録詳細表示 -->
<div class="card <?= !$requiresTime ? 'no-time-service' : ($requiresVisitType ? ($currentVisitType === 'online' ? 'online-service-highlight' : 'visit-service-highlight') : '') ?>">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-file-medical me-2"></i>役務記録詳細
            <span class="badge bg-<?= get_status_class($record['status'] ?? 'pending') ?> ms-2">
                <?= get_status_label($record['status'] ?? 'pending') ?>
            </span>
            <?php if (!$requiresTime): ?>
                <span class="badge bg-secondary ms-1">
                    <i class="fas fa-info-circle me-1"></i>時間記録なし
                </span>
            <?php endif; ?>
            <?php if ($requiresVisitType): ?>
                <span class="badge bg-<?= $visitType['class'] ?> ms-1">
                    <i class="fas fa-<?= $visitType['icon'] ?> me-1"></i><?= $visitType['label'] ?>
                </span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="120">企業:</th>
                        <td>
                            <i class="fas fa-building me-1 text-primary"></i>
                            <?= htmlspecialchars($record['company_name'] ?? $contract['company_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                    <tr>
                        <th>拠点:</th>
                        <td>
                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                            <?= htmlspecialchars($record['branch_name'] ?? $contract['branch_name'] ?? '不明', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                    <tr>
                        <th>役務日:</th>
                        <td>
                            <i class="fas fa-calendar me-1 text-success"></i>
                            <?= format_date($record['service_date']) ?>
                        </td>
                    </tr>
                    
                    <?php if ($requiresTime): ?>
                        <!-- 時間管理ありの役務の場合 -->
                        <tr>
                            <th>実施時間:</th>
                            <td>
                                <i class="fas fa-clock me-1 text-info"></i>
                                <?php if ($record['service_hours'] > 0): ?>
                                    <?= htmlspecialchars(format_time($record['start_time']), ENT_QUOTES, 'UTF-8') ?> - 
                                    <?= htmlspecialchars(format_time($record['end_time']), ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    <span class="text-warning">
                                        <i class="fas fa-spinner fa-spin me-1"></i>進行中
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- 時間管理なしの役務の場合 -->
                        <tr>
                            <th>実施時間:</th>
                            <td>
                                <span class="time-not-applicable">
                                    <i class="fas fa-minus-circle me-1"></i>
                                    時間記録対象外
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <?php if ($requiresTime): ?>
                        <!-- 時間管理ありの役務の場合 -->
                        <tr>
                            <th width="120">役務時間:</th>
                            <td>
                                <?php if ($record['service_hours'] > 0): ?>
                                    <span class="badge bg-primary fs-6">
                                        <?= format_total_hours($record['service_hours']) ?>
                                    </span>
                                    
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-spinner fa-spin me-1"></i>進行中
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- 時間管理なしの役務の場合 -->
                        <tr>
                            <th width="120">役務時間:</th>
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-minus me-1"></i>時間記録なし
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr>
                        <th>役務種別:</th>
                        <td>
                            <span class="badge bg-<?= $serviceType['class'] ?>">
                                <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                <?= $serviceType['label'] ?>
                            </span>
                            
                            <!-- 自動分割記録の場合 -->
                            <?php if (($record['is_auto_split'] ?? false)): ?>
                                <span class="badge bg-secondary ms-1">
                                    <i class="fas fa-scissors me-1"></i>自動分割
                                </span>
                            <?php endif; ?>
                            
                            <!-- 役務種別の説明を追加 -->
                            <div class="mt-1">
                                <small class="text-muted">
                                    <?php
                                    $descriptions = [
                                        'regular' => '月間契約時間内での通常業務',
                                        'emergency' => '緊急対応や特別要請による訪問',
                                        'extension' => '定期訪問の時間延長分',
                                        'spot' => '必要に応じて実施',
                                        'document' => '意見書・報告書等の書面作成業務',
                                        'remote_consultation' => 'メールによる相談対応',
                                        'other' => 'その他の役務'
                                    ];
                                    echo $descriptions[$currentServiceType] ?? '';
                                    ?>
                                </small>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- 訪問種別の表示（訪問種別が必要な役務のみ） -->
                    <?php if ($requiresVisitType): ?>
                    <tr>
                        <th>実施方法:</th>
                        <td>
                            <div class="visit-type-display">
                                <span class="badge bg-<?= $visitType['class'] ?> visit-type-badge">
                                    <i class="fas fa-<?= $visitType['icon'] ?> me-2"></i>
                                    <?= $visitType['label'] ?>
                                </span>
                                <div class="visit-type-description">
                                    <?= $visitType['description'] ?>
                                </div>
                                
                                <!-- 交通費対象・対象外の表示 -->
                                <div class="mt-2">
                                    <?php if ($currentVisitType === 'visit'): ?>
                                        <small class="text-success">
                                            <i class="fas fa-train me-1"></i>
                                            交通費登録対象
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">
                                            <i class="fas fa-times me-1"></i>
                                            交通費登録対象外
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr>
                        <th>登録日時:</th>
                        <td>
                            <i class="fas fa-clock me-1 text-muted"></i>
                            <?= date('Y-m-d H:i', strtotime($record['created_at'])) ?>
                        </td>
                    </tr>
                    <?php if (!empty($record['updated_at']) && $record['updated_at'] !== $record['created_at']): ?>
                    <tr>
                        <th>更新日時:</th>
                        <td>
                            <i class="fas fa-clock me-1 text-muted"></i>
                            <?= date('Y-m-d H:i', strtotime($record['updated_at'])) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- 役務内容 -->
        <hr>
        <div class="row">
            <div class="col-12">
                <h6>
                    <i class="fas fa-comment-dots me-2"></i>
                    <?= !$requiresTime ? '業務内容' : '役務内容' ?>
                </h6>
                <?php if (!empty($record['description'])): ?>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($record['description'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-light rounded">
                        <?php if (!$requiresTime): ?>
                            <span class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                業務内容が記載されていません
                            </span>
                        <?php else: ?>
                            <span class="text-muted">記載なし</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$requiresTime): ?>
                    <!-- 時間なし役務の場合の説明 -->
                    <div class="mt-2">
                        <small class="text-info">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong><?= $serviceType['label'] ?></strong>では、具体的な業務内容の記載が重要です。
                            実施した作業の詳細を記録してください。
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 延長理由（時間ありの役務のみ） -->
        <?php if ($requiresTime && !empty($record['overtime_reason'])): ?>
        <hr>
        <div class="row">
            <div class="col-12">
                <h6><i class="fas fa-exclamation-triangle me-2 text-warning"></i>延長理由</h6>
                <div class="p-3 bg-warning bg-opacity-10 rounded">
                    <?= nl2br(htmlspecialchars($record['overtime_reason'], ENT_QUOTES, 'UTF-8')) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 差戻し理由 -->
        <?php if (($record['status'] ?? '') === 'rejected' && !empty($record['company_comment'])): ?>
        <hr>
        <div class="row">
            <div class="col-12">
                <h6><i class="fas fa-times-circle me-2 text-danger"></i>差戻し理由</h6>
                <div class="p-3 bg-danger bg-opacity-10 rounded">
                    <?= nl2br(htmlspecialchars($record['company_comment'], ENT_QUOTES, 'UTF-8')) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 承認コメント -->
        <?php if (($record['status'] ?? '') === 'approved' && !empty($record['company_comment'])): ?>
        <hr>
        <div class="row">
            <div class="col-12">
                <h6><i class="fas fa-check-circle me-2 text-success"></i>承認コメント</h6>
                <div class="p-3 bg-success bg-opacity-10 rounded">
                    <?= nl2br(htmlspecialchars($record['company_comment'], ENT_QUOTES, 'UTF-8')) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 交通費情報（訪問実施の時間ありの役務のみ） -->
<?php if ($requiresTime && $record['service_hours'] > 0 && $currentVisitType === 'visit'): ?>
<div class="row mt-3">
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-train me-2"></i>関連する交通費記録
                    <span class="badge bg-light text-info ms-2">
                        <i class="fas fa-walking me-1"></i>訪問実施のため登録可能
                    </span>
                </h6>
            </div>
            <div class="card-body">
                <?php
                // 交通費情報を取得
                try {
                    require_once __DIR__ . '/../../models/TravelExpense.php';
                    $travelExpenseModel = new TravelExpense();
                    $travelExpenses = $travelExpenseModel->findByServiceRecord($record['id']);
                    
                    if (!empty($travelExpenses)):
                        $totalAmount = array_sum(array_column($travelExpenses, 'amount'));
                        $approvedCount = count(array_filter($travelExpenses, function($e) { return $e['status'] === 'approved'; }));
                        $pendingCount = count(array_filter($travelExpenses, function($e) { return $e['status'] === 'pending'; }));
                        $rejectedCount = count(array_filter($travelExpenses, function($e) { return $e['status'] === 'rejected'; }));
                ?>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>登録状況</h6>
                            <ul class="list-unstyled">
                                <li><strong>登録件数:</strong> <?= count($travelExpenses) ?>件</li>
                                <li><strong>合計金額:</strong> <?= format_amount($totalAmount) ?></li>
                                <li>
                                    <strong>ステータス内訳:</strong>
                                    <?php if ($approvedCount > 0): ?>
                                        <span class="badge bg-success me-1">承認 <?= $approvedCount ?>件</span>
                                    <?php endif; ?>
                                    <?php if ($pendingCount > 0): ?>
                                        <span class="badge bg-warning me-1">待機 <?= $pendingCount ?>件</span>
                                    <?php endif; ?>
                                    <?php if ($rejectedCount > 0): ?>
                                        <span class="badge bg-danger me-1">差戻 <?= $rejectedCount ?>件</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>操作</h6>
                            <div class="d-flex gap-2">
                                <?php if (($record['status'] ?? '') !== 'finalized' && ($record['visit_type'] ?? 'visit') !== 'online'): ?>
                                <a href="<?= base_url("travel_expenses/create/{$record['id']}") ?>" 
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>交通費追加
                                </a>
                                <?php endif; ?>
                                <?php if (count($travelExpenses) === 1): ?>
                                    <a href="<?= base_url("travel_expenses/{$travelExpenses[0]['id']}") ?>" 
                                       class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye me-1"></i>詳細確認
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-info btn-sm" 
                                            onclick="alert('複数の交通費記録があります。一覧画面から確認してください。')">
                                        <i class="fas fa-list me-1"></i>一覧確認
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2">この役務に関連する交通費記録はありません。</p>
                        <?php if ($userType === 'doctor' && ($record['status'] ?? '') !== 'finalized' && ($record['visit_type'] ?? 'visit') !== 'online'): ?>
                            <a href="<?= base_url("travel_expenses/create/{$record['id']}") ?>" 
                               class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>交通費を登録
                            </a>
                        <?php endif; ?>
                    </div>
                <?php 
                    endif;
                } catch (Exception $e) {
                    error_log('Travel expense load error: ' . $e->getMessage());
                    echo '<div class="alert alert-warning">交通費情報の取得でエラーが発生しました。</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($requiresTime && $record['service_hours'] > 0 && $currentVisitType === 'online'): ?>
<!-- オンライン実施の場合の説明 -->
<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info border-info">
            <h6><i class="fas fa-laptop me-2"></i>オンライン実施について</h6>
            <p class="mb-0">
                この役務はオンラインで実施されたため、交通費の登録は対象外です。<br>
                <i class="fas fa-check-circle me-1 text-success"></i>
                移動時間の短縮により効率的な役務提供が実現されています。
            </p>
        </div>
    </div>
</div>

<?php elseif (!$requiresTime): ?>
<!-- 時間なし役務の説明 -->
<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i><?= $serviceType['label'] ?>について</h6>
            <p class="mb-0">
                この役務種別では、開始・終了時刻の記録や交通費の登録は対象外です。<br>
                業務内容の詳細な記載が重要な記録項目となります。
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 企業ユーザーの場合：承認・差戻しボタン -->
<?php if ($userType === 'company' && ($record['status'] ?? '') === 'pending'): ?>
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-check me-2"></i>承認</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url("service_records/{$record['id']}/approve") ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label for="approve_comment" class="form-label">承認コメント（任意）</label>
                        <textarea class="form-control" id="approve_comment" name="comment" rows="2" 
                                  placeholder="承認に関するコメントがあれば記載してください"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" 
                            onclick="return confirm('この<?= $serviceType['label'] ?><?= $requiresVisitType ? '（' . $visitType['label'] . '）' : '' ?>を承認しますか？')">
                        <i class="fas fa-check me-1"></i>承認する
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="fas fa-times me-2"></i>差戻し</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url("service_records/{$record['id']}/reject") ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label for="reject_comment" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_comment" name="comment" rows="2" 
                                  placeholder="差戻しの理由を具体的に記載してください&#13;&#10;<?= !$requiresTime ? '※ 業務内容の記載が不十分な場合は、詳細な記載を求めてください' : '' ?>&#13;&#10;<?= $requiresVisitType ? '※ 実施方法（' . $visitType['label'] . '）についても確認してください' : '' ?>" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('この<?= $serviceType['label'] ?><?= $requiresVisitType ? '（' . $visitType['label'] . '）' : '' ?>を差戻しますか？')">
                        <i class="fas fa-times me-1"></i>差戻しする
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 履歴表示セクション -->
<?php
// 履歴を取得
try {
    require_once __DIR__ . '/../../models/ServiceRecordHistory.php';
    $historyModel = new ServiceRecordHistory();
    $history = $historyModel->getHistoryByServiceRecord($record['id']);
    $workflowStatus = $historyModel->getWorkflowStatus($record['id']);
    $formattedHistory = $historyModel->formatHistoryForDisplay($history);
} catch (Exception $e) {
    error_log('History display error: ' . $e->getMessage());
    $formattedHistory = [];
    $workflowStatus = ['workflow_stage' => 1, 'has_been_rejected' => false, 'rejection_count' => 0, 'resubmit_count' => 0];
}
?>

<?php if (!empty($formattedHistory) || $workflowStatus['workflow_stage'] > 1): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>承認履歴
                    <span class="badge bg-info ms-2">
                        ステージ <?= $workflowStatus['workflow_stage'] ?>
                    </span>
                    <?php if ($workflowStatus['has_been_rejected']): ?>
                        <span class="badge bg-warning ms-1">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            差戻し <?= $workflowStatus['rejection_count'] ?>回
                        </span>
                    <?php endif; ?>
                    <?php if ($workflowStatus['resubmit_count'] > 0): ?>
                        <span class="badge bg-success ms-1">
                            <i class="fas fa-redo me-1"></i>
                            再提出 <?= $workflowStatus['resubmit_count'] ?>回
                        </span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($formattedHistory)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        履歴情報はありません。
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($formattedHistory as $index => $item): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="<?= $item['action_type_icon'] ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="timeline-title">
                                            <?= htmlspecialchars($item['action_type_label'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php if ($item['is_status_change']): ?>
                                                <span class="badge bg-secondary ms-2">
                                                    <?= get_status_label($item['status_from']) ?> 
                                                    <i class="fas fa-arrow-right mx-1"></i>
                                                    <?= get_status_label($item['status_to']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($item['action_by_name'], ENT_QUOTES, 'UTF-8') ?>
                                            <span class="badge bg-light text-dark ms-1">
                                                <?= $item['action_by_type_label'] ?>
                                            </span>
                                            <i class="fas fa-clock ms-2 me-1"></i>
                                            <?= $item['action_at_formatted'] ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($item['has_comment']): ?>
                                        <div class="timeline-body mt-2">
                                            <div class="p-3 bg-light rounded">
                                                <i class="fas fa-comment-dots me-2 text-primary"></i>
                                                <?= nl2br(htmlspecialchars($item['comment'], ENT_QUOTES, 'UTF-8')) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($item['metadata']) && ($userType === 'admin' || ($userType === 'doctor' && $item['action_type'] === 'resubmitted'))): ?>
                                        <div class="timeline-metadata mt-2">
                                            <details>
                                                <summary class="text-muted small">
                                                    <i class="fas fa-info-circle me-1"></i>詳細情報
                                                </summary>
                                                <div class="mt-2 p-2 bg-secondary bg-opacity-10 rounded small">
                                                    <?php if (isset($item['metadata']['changes'])): ?>
                                                        <strong>変更内容:</strong>
                                                        <ul class="mb-0 mt-1">
                                                        <?php foreach ($item['metadata']['changes'] as $field => $change): ?>
                                                            <li>
                                                                <?= getFieldLabel($field) ?>: 
                                                                <code><?= htmlspecialchars($change['from'], ENT_QUOTES, 'UTF-8') ?></code>
                                                                <i class="fas fa-arrow-right mx-1"></i>
                                                                <code><?= htmlspecialchars($change['to'], ENT_QUOTES, 'UTF-8') ?></code>
                                                            </li>
                                                        <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($item['metadata']['approved_at'])): ?>
                                                        <strong>承認日時:</strong> <?= date('Y-m-d H:i:s', strtotime($item['metadata']['approved_at'])) ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($item['metadata']['rejected_at'])): ?>
                                                        <strong>差戻し日時:</strong> <?= date('Y-m-d H:i:s', strtotime($item['metadata']['rejected_at'])) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- 現在のステータス -->
                        <div class="timeline-item timeline-current">
                            <div class="timeline-marker">
                                <i class="fas fa-circle text-primary"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="timeline-title">
                                        現在のステータス
                                        <span class="badge bg-<?= get_status_class($record['status'] ?? 'pending') ?> ms-2">
                                            <?= get_status_label($record['status'] ?? 'pending') ?>
                                        </span>
                                        <?php if ($requiresVisitType): ?>
                                            <span class="badge bg-<?= $visitType['class'] ?> ms-1">
                                                <i class="fas fa-<?= $visitType['icon'] ?> me-1"></i><?= $visitType['label'] ?>実施
                                            </span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('Y年m月d日 H:i', strtotime($record['updated_at'] ?? $record['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>