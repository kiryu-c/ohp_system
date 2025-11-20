<?php
// app/views/travel_expenses/edit.php

// 不足している関数を定義
if (!function_exists('format_datetime')) {
    function format_datetime($datetime) {
        if (empty($datetime)) return '';
        return date('Y年m月d日 H:i', strtotime($datetime));
    }
}

if (!function_exists('get_transport_type_label')) {
    function get_transport_type_label($type) {
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
        return $labels[$type] ?? $type;
    }
}

if (!function_exists('get_transport_type_icon')) {
    function get_transport_type_icon($type) {
        $icons = [
            'train' => 'fas fa-train',
            'bus' => 'fas fa-bus',
            'taxi' => 'fas fa-taxi',
            'gasoline' => 'fas fa-car',
            'highway_toll' => 'fas fa-car',
            'parking' => 'fas fa-car',
            'rental_car' => 'fas fa-car',
            'airplane' => 'fas fa-plane',
            'other' => 'fas fa-question'
        ];
        return $icons[$type] ?? 'fas fa-question';
    }
}

if (!function_exists('get_trip_type_label')) {
    function get_trip_type_label($type) {
        return $type === 'round_trip' ? '往復' : '片道';
    }
}

if (!function_exists('get_trip_type_icon')) {
    function get_trip_type_icon($type) {
        return $type === 'round_trip' ? 'fas fa-exchange-alt' : 'fas fa-arrow-right';
    }
}

if (!function_exists('format_amount')) {
    function format_amount($amount) {
        return '¥' . number_format($amount);
    }
}

if (!function_exists('getFileIcon')) {
    function getFileIcon($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'pdf': return 'fas fa-file-pdf text-danger';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif': return 'fas fa-file-image text-primary';
            default: return 'fas fa-file text-secondary';
        }
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($size) {
        if ($size >= 1024 * 1024) {
            return round($size / (1024 * 1024), 2) . 'MB';
        } elseif ($size >= 1024) {
            return round($size / 1024, 2) . 'KB';
        }
        return $size . 'B';
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>交通費編集
                        </h4>
                        <div>
                            <a href="<?= base_url("travel_expenses/{$expense['id']}") ?>" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-eye me-1"></i>詳細表示
                            </a>
                            <a href="<?= base_url("service_records/{$expense['service_record_id']}") ?>" class="btn btn-outline-dark btn-sm me-2">
                                <i class="fas fa-arrow-left me-1"></i>役務詳細に戻る
                            </a>
                            <a href="<?= base_url('travel_expenses') ?>" class="btn btn-outline-dark btn-sm">
                                <i class="fas fa-list me-1"></i>交通費一覧
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- 編集可能性チェック -->
                    <?php if ($expense['status'] === 'approved'): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-lock me-2"></i>編集不可</h5>
                            <p class="mb-0">この交通費記録は既に承認済みのため編集できません。</p>
                        </div>
                        
                        <!-- 読み取り専用表示 -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>交通手段</strong></label>
                                    <div class="form-control-plaintext">
                                        <i class="<?= get_transport_type_icon($expense['transport_type']) ?> me-2"></i>
                                        <?= get_transport_type_label($expense['transport_type']) ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>往復・片道</strong></label>
                                    <div class="form-control-plaintext">
                                        <i class="<?= get_trip_type_icon($expense['trip_type']) ?> me-2"></i>
                                        <?= get_trip_type_label($expense['trip_type']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>出発地点</strong></label>
                                    <div class="form-control-plaintext">
                                        <i class="fas fa-map-marker-alt text-success me-1"></i>
                                        <?= htmlspecialchars($expense['departure_point'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>到着地点</strong></label>
                                    <div class="form-control-plaintext">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        <?= htmlspecialchars($expense['arrival_point'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>金額</strong></label>
                                    <div class="form-control-plaintext">
                                        <i class="fas fa-yen-sign me-1"></i>
                                        <?= format_amount($expense['amount']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($expense['memo'])): ?>
                            <div class="mb-3">
                                <label class="form-label"><strong>メモ・備考</strong></label>
                                <div class="form-control-plaintext">
                                    <?= nl2br(htmlspecialchars($expense['memo'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-center mt-4">
                            <a href="<?= base_url("travel_expenses/{$expense['id']}") ?>" class="btn btn-primary">
                                <i class="fas fa-eye me-1"></i>詳細表示に戻る
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- 編集可能な場合 -->
                        
                        <!-- 役務記録情報 -->
                        <div class="alert alert-primary">
                            <h5 class="alert-heading mb-3">
                                <i class="fas fa-info-circle me-2"></i>対象役務記録
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>企業・拠点：</strong>
                                        <i class="fas fa-building me-1 text-success"></i>
                                        <?= htmlspecialchars($expense['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                        -
                                        <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                        <?= htmlspecialchars($expense['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>役務日：</strong>
                                        <?= format_date($expense['service_date']) ?>
                                        (<?= date('w', strtotime($expense['service_date'])) == 0 ? '日' : 
                                               (date('w', strtotime($expense['service_date'])) == 6 ? '土' : '平日') ?>)
                                    </p>
                                    <p class="mb-0">
                                        <strong>役務時間：</strong>
                                        <?= htmlspecialchars($expense['start_time'], ENT_QUOTES, 'UTF-8') ?>
                                        -
                                        <?= htmlspecialchars($expense['end_time'], ENT_QUOTES, 'UTF-8') ?>
                                        (<?= format_total_hours($expense['service_hours']) ?>)
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <?php
                                    $serviceTypeInfo = [
                                        'regular' => ['class' => 'success', 'label' => '定期訪問', 'icon' => 'calendar-check'],
                                        'emergency' => ['class' => 'warning', 'label' => '臨時訪問', 'icon' => 'exclamation-triangle'],
                                        'extension' => ['class' => 'info', 'label' => '定期延長', 'icon' => 'clock']
                                    ];
                                    $serviceType = $serviceTypeInfo[$expense['service_type'] ?? 'regular'];
                                    ?>
                                    <p class="mb-1">
                                        <strong>役務種別：</strong>
                                        <span class="badge bg-<?= $serviceType['class'] ?>">
                                            <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                            <?= $serviceType['label'] ?>
                                        </span>
                                    </p>
                                    
                                    <!-- タクシー利用可否表示 -->
                                    <p class="mb-1">
                                        <strong>タクシー利用：</strong>
                                        <?php if (isset($expense['taxi_allowed'])): ?>
                                            <?php if ($expense['taxi_allowed']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-taxi me-1"></i>利用可能
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-ban me-1"></i>利用不可
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-question me-1"></i>設定なし
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <!-- 現在のステータス -->
                                    <p class="mb-0">
                                        <strong>現在のステータス：</strong>
                                        <?php
                                        $statusInfo = [
                                            'pending' => ['class' => 'warning', 'label' => '承認待ち', 'icon' => 'clock'],
                                            'approved' => ['class' => 'success', 'label' => '承認済み', 'icon' => 'check-circle'],
                                            'rejected' => ['class' => 'danger', 'label' => '差戻し', 'icon' => 'times-circle']
                                        ];
                                        $status = $statusInfo[$expense['status'] ?? 'pending'];
                                        ?>
                                        <span class="badge bg-<?= $status['class'] ?>">
                                            <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                            <?= $status['label'] ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- タクシー利用不可の場合の注意表示 -->
                        <?php if (isset($expense['taxi_allowed']) && !$expense['taxi_allowed']): ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>タクシー利用に関する注意</h6>
                                <p class="mb-0">この契約では原則としてタクシーの利用が認められていません。やむを得ずタクシーを利用した場合は、<strong>メモ・備考欄に利用理由を必ず記載</strong>してください。</p>
                            </div>
                        <?php endif; ?>

                        <!-- 差戻し理由の表示 -->
                        <?php if ($expense['status'] === 'rejected' && !empty($expense['admin_comment'])): ?>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>差戻し理由</h6>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($expense['admin_comment'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- 交通費編集フォーム -->
                        <form action="<?= base_url("travel_expenses/{$expense['id']}") ?>" method="POST" enctype="multipart/form-data" id="expenseEditForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="used_template_id" id="used_template_id" value="">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="transport_type" class="form-label">
                                            <i class="fas fa-subway me-1"></i>交通手段 <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="transport_type" name="transport_type" required>
                                            <option value="train" <?= $expense['transport_type'] === 'train' ? 'selected' : '' ?>>電車</option>
                                            <option value="bus" <?= $expense['transport_type'] === 'bus' ? 'selected' : '' ?>>バス</option>
                                            <option value="taxi" <?= $expense['transport_type'] === 'taxi' ? 'selected' : '' ?>>
                                                タクシー
                                                <?= (!isset($expense['taxi_allowed']) || !$expense['taxi_allowed']) ? '（要理由記載）' : '' ?>
                                            </option>
                                            <option value="gasoline" <?= $expense['transport_type'] === 'gasoline' ? 'selected' : '' ?>>ガソリン</option>
                                            <option value="highway_toll" <?= $expense['transport_type'] === 'highway_toll' ? 'selected' : '' ?>>有料道路</option>
                                            <option value="parking" <?= $expense['transport_type'] === 'parking' ? 'selected' : '' ?>>駐車料金</option>
                                            <option value="rental_car" <?= $expense['transport_type'] === 'rental_car' ? 'selected' : '' ?>>レンタカー</option>
                                            <option value="airplane" <?= $expense['transport_type'] === 'airplane' ? 'selected' : '' ?>>航空機</option>
                                            <option value="other" <?= $expense['transport_type'] === 'other' ? 'selected' : '' ?>>その他</option>
                                        </select>
                                        <div class="form-text">
                                            利用した主な交通手段を選択してください
                                            <?php if (!isset($expense['taxi_allowed']) || !$expense['taxi_allowed']): ?>
                                                <br><small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    タクシー利用時は理由の記載が必要です
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6" id="trip_type_group">
                                    <div class="mb-3">
                                        <label for="trip_type" class="form-label">
                                            <i class="fas fa-exchange-alt me-1"></i>往復・片道 <span class="text-danger" id="trip_type_required">*</span>
                                        </label>
                                        <select class="form-select" id="trip_type" name="trip_type">
                                            <option value="" <?= empty($expense['trip_type']) ? 'selected' : '' ?> style="display:none;"></option>
                                            <option value="round_trip" <?= $expense['trip_type'] === 'round_trip' ? 'selected' : '' ?>>往復</option>
                                            <option value="one_way" <?= $expense['trip_type'] === 'one_way' ? 'selected' : '' ?>>片道</option>
                                        </select>
                                        <div class="form-text">往復か片道かを選択してください</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row" id="route_group">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="departure_point" class="form-label">
                                            <i class="fas fa-map-marker-alt me-1 text-success"></i>出発地点 <span class="text-danger" id="departure_required">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="departure_point" name="departure_point" 
                                               value="<?= htmlspecialchars($expense['departure_point'], ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="例：自宅、○○駅" maxlength="200">
                                        <div class="form-text">出発した場所を入力してください</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="arrival_point" class="form-label">
                                            <i class="fas fa-map-marker-alt me-1 text-danger"></i>到着地点 <span class="text-danger" id="arrival_required">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="arrival_point" name="arrival_point" 
                                               value="<?= htmlspecialchars($expense['arrival_point'], ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="例：企業所在地、○○駅" maxlength="200">
                                        <div class="form-text">到着した場所を入力してください</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">
                                            <i class="fas fa-yen-sign me-1"></i>金額（円） <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               value="<?= htmlspecialchars($expense['amount'], ENT_QUOTES, 'UTF-8') ?>"
                                               min="1" max="999999" step="1" required>
                                        <div class="form-text">交通費の総額を入力してください（1円～999,999円）</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="receipt_file" class="form-label">
                                            <i class="fas fa-receipt me-1"></i>レシート・領収書
                                            <span id="receipt-required-mark" class="text-danger" style="display: none;">*</span>
                                        </label>

                                        <!-- 既存ファイルがある場合の表示 -->
                                        <?php if (!empty($expense['receipt_file_path'])): ?>
                                            <div class="mb-2">
                                                <div class="card">
                                                    <div class="card-body p-2">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="<?= getFileIcon($expense['receipt_file_name']) ?> me-2"></i>
                                                                <span class="small">
                                                                    <?= htmlspecialchars($expense['receipt_file_name'], ENT_QUOTES, 'UTF-8') ?>
                                                                    <?php if (!empty($expense['receipt_file_size'])): ?>
                                                                        (<?= formatFileSize($expense['receipt_file_size']) ?>)
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <a href="<?= base_url($expense['receipt_file_path']) ?>" 
                                                                target="_blank" class="btn btn-sm btn-info me-1">
                                                                    <i class="fas fa-eye"></i> 表示
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-danger" id="delete-receipt-btn">
                                                                    <i class="fas fa-trash"></i> 削除
                                                                </button>
                                                                <input type="hidden" name="delete_receipt" id="delete_receipt" value="0">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <input type="file" class="form-control" id="receipt_file" name="receipt_file" 
                                            accept=".jpg,.jpeg,.png,.gif,.pdf">
                                        <div class="form-text" id="receipt-help-text">
                                            <?php if (!empty($expense['receipt_file_path'])): ?>
                                                新しいファイルをアップロードすると、既存のファイルは上書きされます<br>
                                            <?php endif; ?>
                                            <small class="text-muted">対応形式：JPG, PNG, GIF, PDF（最大5MB）</small>
                                        </div>
                                    </div>
                                </div>
                            
                            <div class="mb-3">
                                <label for="memo" class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i>メモ・備考
                                    <span id="memo-required-mark" class="text-danger" style="display: none;">*</span>
                                </label>
                                <textarea class="form-control" id="memo" name="memo" rows="3" 
                                          placeholder="特記事項があれば入力してください（例：複数路線利用、深夜料金込み等）"><?= htmlspecialchars($expense['memo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                <div class="form-text" id="memo-help-text">交通費に関する補足情報があれば記載してください<br>
                                    <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></div>
                            </div>

                            <!-- 企業にお伝え済みチェックボックス（タクシー不可時のみ表示） -->
                            <div class="mb-3" id="company-notified-group" style="display: none;">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="company_notified" name="company_notified" value="1"
                                                <?= !empty($expense['company_notified']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="company_notified">
                                                <strong class="text-danger">
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    企業にお伝え済み <span class="text-danger">*</span>
                                                </strong>
                                            </label>
                                        </div>
                                        <div class="form-text mt-2">
                                            <i class="fas fa-info-circle me-1 text-primary"></i>
                                            タクシー利用について、事前に企業担当者へ連絡・承諾を得た場合にチェックしてください。<br>
                                            <small class="text-danger">※タクシー利用不可の契約でタクシーを利用する場合、このチェックが必須です。</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- テンプレート保存オプション -->
                            <div class="mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-bookmark me-2 text-primary"></i>テンプレートオプション
                                        </h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="save_as_template" name="save_as_template" value="1">
                                            <label class="form-check-label" for="save_as_template">
                                                編集後の内容をテンプレートとして保存する
                                            </label>
                                        </div>
                                        <div id="template_name_section" style="display: none;">
                                            <label for="custom_template_name" class="form-label">テンプレート名（任意）</label>
                                            <input type="text" class="form-control" id="custom_template_name" name="custom_template_name" 
                                                   placeholder="未入力の場合は自動生成されます" maxlength="100">
                                            <div class="form-text">例：自宅→○○会社（電車往復）</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 変更内容のプレビュー -->
                            <div class="alert alert-info" id="change-preview" style="display: none;">
                                <h6><i class="fas fa-info-circle me-2"></i>変更内容プレビュー</h6>
                                <div id="change-content"></div>
                            </div>
                            
                            <!-- 注意事項 -->
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>編集時の注意事項</h6>
                                <ul class="mb-0">
                                    <li>編集後は再度承認待ち状態になります</li>
                                    <li>承認済みの交通費は編集できません</li>
                                    <li>実際にかかった費用のみ請求してください</li>
                                    <?php if (!isset($expense['taxi_allowed']) || !$expense['taxi_allowed']): ?>
                                        <li class="text-warning">
                                            <strong>タクシー利用時は理由の記載が必須です</strong>
                                        </li>
                                    <?php endif; ?>
                                    <li>大幅な金額変更の場合は、メモ欄に理由を記載してください</li>
                                </ul>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <a href="<?= base_url("travel_expenses/{$expense['id']}") ?>" class="btn btn-secondary me-2">
                                        <i class="fas fa-arrow-left me-1"></i>キャンセル
                                    </a>
                                    
                                    <!-- 削除ボタン（承認待ち・差戻しのみ） -->
                                    <?php if (in_array($expense['status'], ['pending', 'rejected'])): ?>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-1"></i>削除
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-warning" id="submitBtn">
                                    <i class="fas fa-save me-1"></i>変更を保存
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 全テンプレート表示モーダル -->
<div class="modal fade" id="allTemplatesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bookmark me-2"></i>交通費テンプレート一覧
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($allTemplates)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>テンプレート名</th>
                                    <th>交通手段</th>
                                    <th>区間</th>
                                    <th>金額</th>
                                    <th>使用回数</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allTemplates as $template): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($template['template_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <?php
                                            $transportLabels = [
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
                                            echo $transportLabels[$template['transport_type']] ?? $template['transport_type'];
                                            ?>
                                            <?php if ($template['trip_type']): ?>
                                            <small class="text-muted">
                                                (<?= $template['trip_type'] === 'round_trip' ? '往復' : '片道' ?>)
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($template['departure_point']): ?>
                                            <small>
                                                <?= htmlspecialchars($template['departure_point'], ENT_QUOTES, 'UTF-8') ?>
                                                →
                                                <?= htmlspecialchars($template['arrival_point'], ENT_QUOTES, 'UTF-8') ?>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">¥<?= number_format($template['amount']) ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= $template['usage_count'] ?>回</span>
                                            <?php if ($template['last_used_at']): ?>
                                                <br><small class="text-muted">
                                                    <?= format_date($template['last_used_at']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-primary btn-sm template-use-btn" 
                                                    data-template-id="<?= $template['id'] ?>" 
                                                    title="このテンプレートを使用">
                                                <i class="fas fa-check"></i> 適用
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
                        <p class="text-muted">まだテンプレートがありません。<br>交通費を登録する際に「テンプレートとして保存する」をチェックしてください。</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<?php if ($expense['status'] !== 'approved'): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i>交通費記録の削除
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>削除する交通費記録：</strong>
                    <ul class="list-unstyled mt-2">
                        <li><strong>役務日：</strong><?= format_date($expense['service_date']) ?></li>
                        <li><strong>交通手段：</strong><?= get_transport_type_label($expense['transport_type']) ?></li>
                        <li><strong>区間：</strong><?= htmlspecialchars($expense['departure_point']) ?> → <?= htmlspecialchars($expense['arrival_point']) ?></li>
                        <li><strong>金額：</strong><?= format_amount($expense['amount']) ?></li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>注意：</strong>削除した記録は復元できません。
                </div>
                
                <p>本当にこの交通費記録を削除しますか？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/delete") ?>" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>削除する
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* フォームスタイリング */
.form-control:focus, .form-select:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header.bg-warning {
    background-color: #ffc107 !important;
    border-bottom: 1px solid #ffca2c;
}

/* 現在のファイル表示 */
.current-file .card {
    background-color: #f8f9fa;
}

/* 変更プレビュー */
#change-preview {
    border-left: 4px solid #17a2b8;
}

/* 読み取り専用表示 */
.form-control-plaintext {
    padding: 0.375rem 0;
    background-color: transparent;
    border: none;
    border-bottom: 1px solid #dee2e6;
}

/* テンプレート機能のスタイル */
.template-buttons .btn {
    max-width: 200px;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

/* テンプレート関連のアニメーション */
.template-btn:hover, .template-use-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .btn-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn-group .btn {
        margin-bottom: 0.5rem;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .template-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .template-buttons .btn {
        max-width: 100%;
        flex: 1 1 auto;
        min-width: 120px;
    }
}

/* ファイル入力のスタイル */
.form-control[type="file"] {
    padding: 0.375rem 0.75rem;
}

/* 金額入力のスタイル */
#amount {
    text-align: right;
}

/* 必須マークのスタイル */
.text-danger {
    font-weight: bold;
}

/* アラートのカスタマイズ */
.alert-primary {
    border-left: 4px solid #007bff;
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

.alert-danger {
    border-left: 4px solid #dc3545;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}

/* タクシー利用可否バッジのスタイル */
.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

/* モーダル内のテーブルスタイル */
#allTemplatesModal .table td {
    vertical-align: middle;
}

#allTemplatesModal .btn-sm {
    padding: 0.25rem 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 元の値を保存
    const originalValues = {
        transport_type: document.getElementById('transport_type').value,
        trip_type: document.getElementById('trip_type').value,
        departure_point: document.getElementById('departure_point').value,
        arrival_point: document.getElementById('arrival_point').value,
        amount: document.getElementById('amount').value,
        memo: document.getElementById('memo').value
    };
    
    // 交通手段選択時の処理
    const transportTypeSelect = document.getElementById('transport_type');
    const amountInput = document.getElementById('amount');
    const departureInput = document.getElementById('departure_point');
    const memoTextarea = document.getElementById('memo');
    
    // タクシー利用可否の設定
    const taxiAllowed = <?= json_encode(isset($expense['taxi_allowed']) ? (bool)$expense['taxi_allowed'] : false) ?>;
    
    // ★ 追加：既存の領収書の有無
    const hasExistingReceipt = <?= json_encode(!empty($expense['receipt_file_path'])) ?>;
    
    // 初期表示時に交通手段に応じた表示/非表示を設定
    const initialTransportType = transportTypeSelect.value;
    const hideRouteTypes = ['gasoline', 'parking', 'rental_car'];
    const tripTypeGroup = document.getElementById('trip_type_group');
    const routeGroup = document.getElementById('route_group');
    const tripTypeSelect = document.getElementById('trip_type');
    const arrivalInput = document.getElementById('arrival_point');
    
    if (hideRouteTypes.includes(initialTransportType)) {
        // 初期値がガソリン等の場合は非表示
        tripTypeGroup.style.display = 'none';
        routeGroup.style.display = 'none';
        tripTypeSelect.removeAttribute('required');
        departureInput.removeAttribute('required');
        arrivalInput.removeAttribute('required');
        
        // 値をクリア
        tripTypeSelect.value = '';
    }
    
    // 初期状態でタクシーが選択されている場合の処理
    if (transportTypeSelect.value === 'taxi' && !taxiAllowed) {
        setMemoRequired(true);
        // ★ 追加：初期表示時に企業にお伝え済みも表示
        const companyNotifiedGroup = document.getElementById('company-notified-group');
        const companyNotifiedCheckbox = document.getElementById('company_notified');
        if (companyNotifiedGroup && companyNotifiedCheckbox) {
            companyNotifiedGroup.style.display = 'block';
            companyNotifiedCheckbox.required = true;
        }
        // 領収書必須マークも表示
        const receiptRequiredMark = document.getElementById('receipt-required-mark');
        if (receiptRequiredMark) {
            receiptRequiredMark.style.display = 'inline';
        }
    }
    
    // ★ 追加：領収書削除ボタンの処理
    const deleteReceiptBtn = document.getElementById('delete-receipt-btn');
    const deleteReceiptInput = document.getElementById('delete_receipt');
    
    if (deleteReceiptBtn) {
        deleteReceiptBtn.addEventListener('click', function() {
            // タクシー不可契約でタクシーを選択している場合は削除不可
            const transportType = transportTypeSelect.value;
            if (transportType === 'taxi' && !taxiAllowed) {
                const receiptFile = document.getElementById('receipt_file');
                if (!receiptFile.files || receiptFile.files.length === 0) {
                    alert('タクシー利用不可の契約でタクシーを利用する場合、領収書の削除はできません。\n新しい領収書をアップロードしてください。');
                    return;
                }
            }
            
            if (confirm('領収書を削除してもよろしいですか？')) {
                deleteReceiptInput.value = '1';
                this.closest('.card').remove();
                showMessage('領収書が削除されます（保存時に確定）', 'warning');
            }
        });
    }
    
    // 交通手段変更時の処理
    if (transportTypeSelect) {
        transportTypeSelect.addEventListener('change', function() {
            const transportType = this.value;
            
            // ★ 追加：領収書・企業お伝え済みの要素を取得
            const receiptFileInput = document.getElementById('receipt_file');
            const receiptRequiredMark = document.getElementById('receipt-required-mark');
            const receiptHelpText = document.getElementById('receipt-help-text');
            const companyNotifiedGroup = document.getElementById('company-notified-group');
            const companyNotifiedCheckbox = document.getElementById('company_notified');
            
            // 往復・片道と地点入力のグループ要素を取得
            const tripTypeGroup = document.getElementById('trip_type_group');
            const routeGroup = document.getElementById('route_group');
            const tripTypeSelect = document.getElementById('trip_type');
            const arrivalInput = document.getElementById('arrival_point');
            
            // 地点入力を非表示にする交通手段
            const hideRouteTypes = ['gasoline', 'parking', 'rental_car'];
            
            if (hideRouteTypes.includes(transportType)) {
                // 往復・片道と地点入力を非表示
                tripTypeGroup.style.display = 'none';
                routeGroup.style.display = 'none';
                
                // required属性を削除
                tripTypeSelect.removeAttribute('required');
                departureInput.removeAttribute('required');
                arrivalInput.removeAttribute('required');
                
                // 値をクリア
                tripTypeSelect.value = '';
            } else {
                // 往復・片道と地点入力を表示
                tripTypeGroup.style.display = '';
                routeGroup.style.display = '';
                
                // required属性を追加
                tripTypeSelect.setAttribute('required', 'required');
                departureInput.setAttribute('required', 'required');
                arrivalInput.setAttribute('required', 'required');
            }
            
            // タクシー選択時の処理
            if (transportType === 'taxi' && !taxiAllowed) {
                // ①メモを必須にする
                setMemoRequired(true);
                
                // ②領収書を必須にする
                if (receiptRequiredMark) {
                    receiptRequiredMark.style.display = 'inline';
                }
                
                if (hasExistingReceipt) {
                    if (receiptHelpText) {
                        receiptHelpText.innerHTML = '<strong class="text-warning">タクシー利用時は領収書が必須です（既存の領収書あり）</strong><br>' +
                                                   '<small class="text-muted">新しいファイルをアップロードすると、既存のファイルは上書きされます。対応形式：JPG, PNG, GIF, PDF（最大5MB）</small>';
                    }
                    // 既存の領収書がある場合、削除ボタンを制御
                    if (deleteReceiptBtn) {
                        deleteReceiptBtn.disabled = false;
                        deleteReceiptBtn.title = '新しい領収書をアップロードする場合のみ削除可能';
                    }
                } else {
                    if (receiptFileInput) {
                        receiptFileInput.required = true;
                    }
                    if (receiptHelpText) {
                        receiptHelpText.innerHTML = '<strong class="text-warning">タクシー利用時は領収書のアップロードが必須です</strong><br>' +
                                                   '<small class="text-muted">対応形式：JPG, PNG, GIF, PDF（最大5MB）</small>';
                    }
                }
                
                // ③企業にお伝え済みチェックボックスを表示＋必須にする
                if (companyNotifiedGroup) {
                    companyNotifiedGroup.style.display = 'block';
                }
                if (companyNotifiedCheckbox) {
                    companyNotifiedCheckbox.required = true;
                }
                
                showMessage('タクシーが選択されました。この契約では原則利用不可のため、以下の対応が必須です：\n' +
                           '1. 利用理由をメモ欄に記載\n' +
                           '2. 領収書の保持（既にアップロード済み' + (hasExistingReceipt ? 'です' : 'が必要です') + '）\n' +
                           '3. 「企業にお伝え済み」にチェック', 'warning');
            } else {
                // メモを任意にする
                setMemoRequired(false);
                
                // 領収書を任意にする
                if (receiptFileInput) {
                    receiptFileInput.required = false;
                }
                if (receiptRequiredMark) {
                    receiptRequiredMark.style.display = 'none';
                }
                if (receiptHelpText) {
                    if (hasExistingReceipt) {
                        receiptHelpText.innerHTML = '新しいファイルをアップロードすると、既存のファイルは上書きされます<br>' +
                                                   '<small class="text-muted">対応形式：JPG, PNG, GIF, PDF（最大5MB）</small>';
                    } else {
                        receiptHelpText.innerHTML = '<small class="text-muted">対応形式：JPG, PNG, GIF, PDF（最大5MB）</small>';
                    }
                }
                
                // 領収書削除ボタンを有効化
                if (deleteReceiptBtn) {
                    deleteReceiptBtn.disabled = false;
                    deleteReceiptBtn.title = '';
                }
                
                // 企業にお伝え済みチェックボックスを非表示＋任意にする
                if (companyNotifiedGroup) {
                    companyNotifiedGroup.style.display = 'none';
                }
                if (companyNotifiedCheckbox) {
                    companyNotifiedCheckbox.required = false;
                }
            }
            
            // 交通手段に応じたプレースホルダーやヒントを設定
            switch(transportType) {
                case 'train':
                    departureInput.placeholder = '例：○○駅';
                    arrivalInput.placeholder = '例：○○駅';
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：複数路線利用、IC カード利用';
                    }
                    break;
                case 'bus':
                    departureInput.placeholder = '例：○○バス停';
                    arrivalInput.placeholder = '例：○○バス停';
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：路線バス、高速バス';
                    }
                    break;
                case 'taxi':
                    departureInput.placeholder = '例：自宅、○○駅';
                    arrivalInput.placeholder = '例：訪問先';
                    if (taxiAllowed) {
                        memoTextarea.placeholder = '例：深夜料金込み、迎車料金込み';
                        showMessage('タクシー利用が選択されました。契約でタクシー利用が認められています。', 'info');
                    } else {
                        memoTextarea.placeholder = '例：終電後のため、体調不良のため、重い機材運搬のため等';
                    }
                    break;
                case 'gasoline':
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：給油日、リッター数など';
                    }
                    break;
                case 'highway_toll':
                    departureInput.placeholder = '例：○○IC';
                    arrivalInput.placeholder = '例：○○IC';
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：利用区間、高速道路名';
                    }
                    break;
                case 'parking':
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：駐車場名、駐車時間';
                    }
                    break;
                case 'rental_car':
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：レンタカー会社、車種、利用日';
                    }
                    break;
                case 'airplane':
                    departureInput.placeholder = '例：○○空港';
                    arrivalInput.placeholder = '例：○○空港';
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：便名、席種など';
                    }
                    break;
                case 'other':
                    departureInput.placeholder = '例：出発地点';
                    arrivalInput.placeholder = '例：到着地点';
                    if (!memoTextarea.required) {
                        memoTextarea.placeholder = '例：利用した交通手段の詳細';
                    }
                    break;
            }
            
            checkForChanges();
        });
    }
    
    // メモ必須状態の切り替え
    function setMemoRequired(required) {
        const memoRequiredMark = document.getElementById('memo-required-mark');
        const memoHelpText = document.getElementById('memo-help-text');
        
        if (required) {
            memoTextarea.required = true;
            memoRequiredMark.style.display = 'inline';
            memoHelpText.innerHTML = '<strong class="text-warning">タクシー利用時は理由の記載が必須です</strong>';
            memoTextarea.placeholder = '例：終電後のため、体調不良のため、重い機材運搬のため等';
        } else {
            memoTextarea.required = false;
            memoRequiredMark.style.display = 'none';
            memoHelpText.innerHTML = '交通費に関する補足情報があれば記載してください<br><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>';
            memoTextarea.placeholder = '特記事項があれば入力してください（例：複数路線利用、深夜料金込等）';
        }
    }
    
    // 変更検出関数
    function checkForChanges() {
        const currentValues = {
            transport_type: transportTypeSelect.value,
            trip_type: tripTypeSelect.value,
            departure_point: departureInput.value,
            arrival_point: arrivalInput.value,
            amount: amountInput.value,
            memo: memoTextarea.value
        };
        
        let hasChanges = false;
        let changes = [];
        
        // 交通手段の変更
        if (currentValues.transport_type !== originalValues.transport_type) {
            hasChanges = true;
            changes.push(`交通手段: ${getTransportTypeLabel(originalValues.transport_type)} → ${getTransportTypeLabel(currentValues.transport_type)}`);
        }
        
        // 往復・片道の変更
        if (currentValues.trip_type !== originalValues.trip_type) {
            hasChanges = true;
            const oldLabel = originalValues.trip_type === 'round_trip' ? '往復' : '片道';
            const newLabel = currentValues.trip_type === 'round_trip' ? '往復' : '片道';
            changes.push(`往復・片道: ${oldLabel} → ${newLabel}`);
        }
        
        // 出発地点の変更
        if (currentValues.departure_point !== originalValues.departure_point) {
            hasChanges = true;
            changes.push(`出発地点: ${originalValues.departure_point || '(なし)'} → ${currentValues.departure_point || '(なし)'}`);
        }
        
        // 到着地点の変更
        if (currentValues.arrival_point !== originalValues.arrival_point) {
            hasChanges = true;
            changes.push(`到着地点: ${originalValues.arrival_point || '(なし)'} → ${currentValues.arrival_point || '(なし)'}`);
        }
        
        // 金額の変更
        if (currentValues.amount !== originalValues.amount) {
            hasChanges = true;
            const oldAmount = originalValues.amount ? `¥${Number(originalValues.amount).toLocaleString()}` : '¥0';
            const newAmount = currentValues.amount ? `¥${Number(currentValues.amount).toLocaleString()}` : '¥0';
            changes.push(`金額: ${oldAmount} → ${newAmount}`);
        }
        
        // メモの変更
        if (currentValues.memo !== originalValues.memo) {
            hasChanges = true;
            changes.push('メモ: 変更あり');
        }
        
        // プレビュー表示
        const changePreview = document.getElementById('change-preview');
        const changeContent = document.getElementById('change-content');
        
        if (hasChanges && changePreview && changeContent) {
            changePreview.style.display = 'block';
            changeContent.innerHTML = '<ul class="mb-0">' + 
                changes.map(c => `<li>${c}</li>`).join('') + 
                '</ul>';
        } else if (changePreview) {
            changePreview.style.display = 'none';
        }
    }
    
    // 入力フィールドの変更を監視
    [transportTypeSelect, tripTypeSelect, departureInput, arrivalInput, amountInput, memoTextarea].forEach(field => {
        if (field) {
            field.addEventListener('input', checkForChanges);
            field.addEventListener('change', checkForChanges);
        }
    });
    
    // テンプレート保存チェックボックスの処理
    const saveAsTemplateCheck = document.getElementById('save_as_template');
    const templateNameSection = document.getElementById('template_name_section');
    
    if (saveAsTemplateCheck && templateNameSection) {
        saveAsTemplateCheck.addEventListener('change', function() {
            if (this.checked) {
                templateNameSection.style.display = 'block';
            } else {
                templateNameSection.style.display = 'none';
            }
        });
    }
    
    // テンプレート適用関数
    window.applyTemplate = function(templateId) {
        fetch(`<?= base_url('api/travel_expenses/template/') ?>${templateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const template = data.data;
                    
                    // フォームに値を設定
                    document.getElementById('transport_type').value = template.transport_type;
                    document.getElementById('trip_type').value = template.trip_type;
                    document.getElementById('departure_point').value = template.departure_point;
                    document.getElementById('arrival_point').value = template.arrival_point;
                    document.getElementById('amount').value = template.amount;
                    document.getElementById('memo').value = template.memo || '';
                    
                    // 使用したテンプレートIDを記録
                    document.getElementById('used_template_id').value = templateId;
                    
                    // テンプレート保存チェックボックスを無効化
                    if (saveAsTemplateCheck) {
                        saveAsTemplateCheck.checked = false;
                        saveAsTemplateCheck.disabled = true;
                        templateNameSection.style.display = 'none';
                    }
                    
                    // 交通手段変更イベントを手動でトリガー（プレースホルダー等の更新）
                    transportTypeSelect.dispatchEvent(new Event('change'));
                    
                    // 変更検出のため元の値を更新
                    Object.keys(originalValues).forEach(key => {
                        if (template[key] !== undefined) {
                            originalValues[key] = template[key];
                        }
                    });
                    
                    checkForChanges();
                    
                    showMessage(`テンプレート「${template.template_name}」を適用しました。`, 'success');
                    
                    // モーダルを閉じる
                    const modal = bootstrap.Modal.getInstance(document.getElementById('allTemplatesModal'));
                    if (modal) {
                        modal.hide();
                    }
                } else {
                    showMessage(data.error || 'テンプレートの読み込みに失敗しました。', 'error');
                }
            })
            .catch(error => {
                console.error('Template load error:', error);
                showMessage('システムエラーが発生しました。', 'error');
            });
    }
    
    // フォーム送信前のバリデーション
    const form = document.querySelector('#expenseEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const departure = departureInput.value.trim();
            const arrival = document.getElementById('arrival_point').value.trim();
            const amount = parseInt(amountInput.value);
            const selectedTransport = transportTypeSelect.value;
            const memo = memoTextarea.value.trim();
            
            // ★ 修正：タクシー利用不可契約でタクシーが選択された場合の追加チェック
            if (selectedTransport === 'taxi' && !taxiAllowed) {
                // ①メモが空の場合
                if (!memo) {
                    e.preventDefault();
                    showMessage('タクシー利用時は、メモ・備考欄に利用理由を必ず記載してください。', 'error');
                    memoTextarea.focus();
                    memoTextarea.scrollIntoView({ behavior: 'smooth' });
                    return false;
                }
                
                // ②領収書がない場合
                const receiptFile = document.getElementById('receipt_file');
                const deleteReceipt = document.getElementById('delete_receipt');
                const hasNewUpload = receiptFile.files && receiptFile.files.length > 0;
                const willDeleteExisting = deleteReceipt && deleteReceipt.value === '1';
                
                if (!hasExistingReceipt && !hasNewUpload) {
                    e.preventDefault();
                    showMessage('タクシー利用時は、領収書のアップロードが必須です。', 'error');
                    receiptFile.focus();
                    receiptFile.scrollIntoView({ behavior: 'smooth' });
                    return false;
                }
                
                if (hasExistingReceipt && willDeleteExisting && !hasNewUpload) {
                    e.preventDefault();
                    showMessage('タクシー利用時は、領収書が必須です。削除する場合は新しい領収書をアップロードしてください。', 'error');
                    receiptFile.focus();
                    receiptFile.scrollIntoView({ behavior: 'smooth' });
                    return false;
                }
                
                // ③企業にお伝え済みチェックがない場合
                const companyNotified = document.getElementById('company_notified');
                if (companyNotified && !companyNotified.checked) {
                    e.preventDefault();
                    showMessage('タクシー利用時は、「企業にお伝え済み」チェックが必須です。', 'error');
                    companyNotified.focus();
                    companyNotified.scrollIntoView({ behavior: 'smooth' });
                    return false;
                }
            }
            
            // 出発地点と到着地点が同じ場合の警告
            if (departure && arrival && departure.toLowerCase() === arrival.toLowerCase()) {
                if (!confirm('出発地点と到着地点が同じようですが、よろしいですか？')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // 高額な交通費の確認
            if (amount > 10000) {
                if (!confirm(`交通費が${amount.toLocaleString()}円と高額ですが、よろしいですか？`)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // 変更がない場合の確認
            const changePreview = document.getElementById('change-preview');
            if (changePreview.style.display === 'none') {
                if (!confirm('変更が検出されませんでした。このまま保存しますか？')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    }
    
    // ファイル選択時の処理
    const fileInput = document.getElementById('receipt_file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // ファイルサイズチェック（5MB）
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showMessage('ファイルサイズが大きすぎます。5MB以下のファイルを選択してください。', 'error');
                    this.value = '';
                    return;
                }
                
                // ファイル形式チェック
                const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                const fileName = file.name.toLowerCase();
                const extension = fileName.split('.').pop();
                
                if (!allowedExtensions.includes(extension)) {
                    showMessage('対応していないファイル形式です。JPG、PNG、GIF、PDFのいずれかを選択してください。', 'error');
                    this.value = '';
                    return;
                }
                
                showMessage(`新しいファイル「${file.name}」を選択しました。`, 'success');
            }
        });
    }
});

// ヘルパー関数
function getTransportTypeLabel(type) {
    const labels = {
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
    return labels[type] || type;
}

function formatAmount(amount) {
    return '¥' + parseInt(amount).toLocaleString();
}

// メッセージ表示関数
function showMessage(message, type = 'info') {
    // 既存のメッセージを削除
    const existingAlert = document.querySelector('.dynamic-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // アラートクラスを設定
    let alertClass = 'alert-info';
    let iconClass = 'fa-info-circle';
    
    switch(type) {
        case 'success':
            alertClass = 'alert-success';
            iconClass = 'fa-check-circle';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            iconClass = 'fa-exclamation-triangle';
            break;
        case 'error':
            alertClass = 'alert-danger';
            iconClass = 'fa-times-circle';
            break;
    }
    
    // メッセージ要素を作成
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} dynamic-alert mt-3`;
    alertDiv.innerHTML = `
        <i class="fas ${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // フォームの上に挿入
    const form = document.querySelector('#expenseEditForm');
    if (form) {
        form.insertBefore(alertDiv, form.firstChild);
    }
    
    // 3秒後に自動削除
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}
</script>