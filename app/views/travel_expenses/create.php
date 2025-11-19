<?php
// app/views/travel_expenses/create.php

// 不足している関数を定義
if (!function_exists('format_datetime')) {
    function format_datetime($datetime) {
        if (empty($datetime)) return '';
        return date('Y年m月d日 H:i', strtotime($datetime));
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-train me-2"></i>交通費登録
                        </h4>
                        <div>
                            <a href="<?= base_url("service_records/{$serviceRecord['id']}") ?>" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-arrow-left me-1"></i>役務詳細に戻る
                            </a>
                            <a href="<?= base_url('service_records') ?>" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-list me-1"></i>役務一覧
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
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
                                    <?= htmlspecialchars($serviceRecord['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                    -
                                    <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                    <?= htmlspecialchars($serviceRecord['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="mb-1">
                                    <strong>役務日：</strong>
                                    <?= format_date($serviceRecord['service_date']) ?>
                                    (<?= date('w', strtotime($serviceRecord['service_date'])) == 0 ? '日' : 
                                           (date('w', strtotime($serviceRecord['service_date'])) == 6 ? '土' : '平日') ?>)
                                </p>
                                <p class="mb-0">
                                    <strong>役務時間：</strong>
                                    <?= htmlspecialchars($serviceRecord['start_time'], ENT_QUOTES, 'UTF-8') ?>
                                    -
                                    <?= htmlspecialchars($serviceRecord['end_time'], ENT_QUOTES, 'UTF-8') ?>
                                    (<?= format_total_hours($serviceRecord['service_hours']) ?>)
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $serviceTypeInfo = [
                                    'regular' => ['class' => 'success', 'label' => '定期訪問', 'icon' => 'calendar-check'],
                                    'emergency' => ['class' => 'warning', 'label' => '臨時訪問', 'icon' => 'exclamation-triangle'],
                                    'extension' => ['class' => 'info', 'label' => '定期延長', 'icon' => 'clock']
                                ];
                                $serviceType = $serviceTypeInfo[$serviceRecord['service_type'] ?? 'regular'];
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
                                    <?php if (isset($serviceRecord['taxi_allowed'])): ?>
                                        <?php if ($serviceRecord['taxi_allowed']): ?>
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
                                
                                <?php if (!empty($serviceRecord['description'])): ?>
                                    <p class="mb-0">
                                        <strong>業務内容：</strong>
                                        <span class="text-muted">
                                            <?= mb_strlen($serviceRecord['description']) > 50 ? 
                                                mb_substr($serviceRecord['description'], 0, 50) . '...' : 
                                                $serviceRecord['description'] ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- タクシー利用不可の場合の注意表示 -->
                    <?php if (isset($serviceRecord['taxi_allowed']) && !$serviceRecord['taxi_allowed']): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>タクシー利用に関する注意</h6>
                            <p class="mb-0">この契約では原則としてタクシーの利用が認められていません。やむを得ずタクシーを利用した場合は、<strong>メモ・備考欄に利用理由を必ず記載</strong>してください。</p>
                        </div>
                    <?php endif; ?>

                    <!-- 既存の交通費記録がある場合は表示 -->
                    <?php if (!empty($existingExpenses)): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>この役務記録には既に交通費が登録されています</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>交通手段</th>
                                            <th>区間</th>
                                            <th>往復/片道</th>
                                            <th>金額</th>
                                            <th>ステータス</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($existingExpenses as $expense): ?>
                                            <tr>
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
                                                    echo $transportLabels[$expense['transport_type']] ?? $expense['transport_type'];
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($expense['departure_point']): ?>
                                                    <?= htmlspecialchars($expense['departure_point'], ENT_QUOTES, 'UTF-8') ?>
                                                    →
                                                    <?= htmlspecialchars($expense['arrival_point'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($expense['trip_type']): ?>
                                                    <?= $expense['trip_type'] === 'round_trip' ? '往復' : '片道' ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    ¥<?= number_format($expense['amount']) ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusInfo = [
                                                        'pending' => ['class' => 'warning', 'label' => '承認待ち'],
                                                        'approved' => ['class' => 'success', 'label' => '承認済み'],
                                                        'rejected' => ['class' => 'danger', 'label' => '差戻し']
                                                    ];
                                                    $status = $statusInfo[$expense['status']] ?? ['class' => 'secondary', 'label' => '不明'];
                                                    ?>
                                                    <span class="badge bg-<?= $status['class'] ?>"><?= $status['label'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                複数の交通費を登録することも可能です。（例：行きと帰りで異なる交通手段を利用した場合など）
                            </small>
                        </div>
                    <?php endif; ?>

                    <!-- テンプレート機能の追加 -->
                    <?php if (!empty($contractTemplates) || !empty($allTemplates)): ?>
                        <div class="alert alert-info mb-4">
                            <h6><i class="fas fa-bookmark me-2"></i>この契約での交通費テンプレート</h6>
                            
                            <!-- この契約で使用されたテンプレート -->
                            <?php if (!empty($contractTemplates)): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2">
                                        <i class="fas fa-building me-1"></i>
                                        <?= htmlspecialchars($serviceRecord['branch_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>での過去の交通費：
                                    </small>
                                    <div class="template-buttons">
                                        <?php foreach ($contractTemplates as $template): ?>
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2 mb-2 template-btn" 
                                                    data-template-id="<?= $template['id'] ?>"
                                                    title="<?= $template['template_name'] ?>">
                                                <i class="fas fa-history me-1"></i>
                                                <?= htmlspecialchars(mb_strlen($template['template_name']) > 100 ? 
                                                    mb_substr($template['template_name'], 0, 100) . '...' : 
                                                    $template['template_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-success d-block mt-1">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        この契約の役務で過去に使用した交通費パターンです
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <?= htmlspecialchars($serviceRecord['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>での交通費実績：
                                    </small>
                                    <p class="text-muted mb-0">
                                        この契約ではまだ交通費の登録実績がありません。<br>
                                        初回登録後は、過去のパターンがテンプレートとして提案されるようになります。
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">テンプレートをクリックすると自動入力されます</small>
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#allTemplatesModal">
                                    <i class="fas fa-list me-1"></i>全テンプレート表示
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 交通費登録フォーム -->
                    <form action="<?= base_url('travel_expenses') ?>" method="POST" enctype="multipart/form-data" id="expenseForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="service_record_id" value="<?= $serviceRecord['id'] ?>">
                        <input type="hidden" name="used_template_id" id="used_template_id" value="">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="transport_type" class="form-label">
                                        <i class="fas fa-subway me-1"></i>交通手段 <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="transport_type" name="transport_type" required>
                                        <option value="train">電車</option>
                                        <option value="bus">バス</option>
                                        <option value="taxi">
                                            タクシー
                                            <?= (!isset($serviceRecord['taxi_allowed']) || !$serviceRecord['taxi_allowed']) ? '（要理由記載）' : '' ?>
                                        </option>
                                        <option value="gasoline">ガソリン</option>
                                        <option value="highway_toll">有料道路</option>
                                        <option value="parking">駐車料金</option>
                                        <option value="rental_car">レンタカー</option>
                                        <option value='airplane'>航空機</option>
                                        <option value="other">その他</option>
                                    </select>
                                    <div class="form-text">
                                        利用した主な交通手段を選択してください
                                        <?php if (!isset($serviceRecord['taxi_allowed']) || !$serviceRecord['taxi_allowed']): ?>
                                            <br><small class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                この契約ではタクシーは利用できません
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
                                        <option value="round_trip">往復</option>
                                        <option value="one_way">片道</option>
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
                                           min="1" max="999999" step="1" required>
                                    <div class="form-text">交通費の総額を入力してください（1円～999,999円）</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="receipt_file" class="form-label">
                                        <i class="fas fa-receipt me-1"></i>レシート・領収書（任意）
                                    </label>
                                    <input type="file" class="form-control" id="receipt_file" name="receipt_file" 
                                           accept=".jpg,.jpeg,.png,.gif,.pdf">
                                    <div class="form-text">
                                        レシートや領収書の画像ファイルまたはPDFをアップロードできます<br>
                                        <small class="text-muted">対応形式：JPG, PNG, GIF, PDF（最大5MB）</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="memo" class="form-label">
                                <i class="fas fa-sticky-note me-1"></i>メモ・備考
                                <span id="memo-required-mark" class="text-danger" style="display: none;">*</span>
                            </label>
                            <textarea class="form-control" id="memo" name="memo" rows="3" 
                                      placeholder="特記事項があれば入力してください（例：複数路線利用、深夜料金込等）"></textarea>
                            <div class="form-text" id="memo-help-text">交通費に関する補足情報があれば記載してください<br>
                            <span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span></div>
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
                                            この内容をテンプレートとして保存する
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
                        
                        <!-- 注意事項 -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>交通費登録に関する注意事項</h6>
                            <ul class="mb-0">
                                <li>交通費は実際にかかった費用のみ請求してください</li>
                                <li>可能な限りレシートや領収書を添付してください</li>
                                <?php if (!isset($serviceRecord['taxi_allowed']) || !$serviceRecord['taxi_allowed']): ?>
                                    <li class="text-warning">
                                        <strong>タクシー利用時は理由の記載が必須です</strong>
                                    </li>
                                <?php endif; ?>
                                <li>登録後は管理者による承認が必要です</li>
                                <li>承認前であれば編集・削除が可能です</li>
                                <li>不正な申請は承認されない場合があります</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= base_url("service_records/{$serviceRecord['id']}") ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>キャンセル
                            </a>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-save me-1"></i>交通費を登録
                            </button>
                        </div>
                    </form>
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
                                            <div class="template-name-editable" data-template-id="<?= $template['id'] ?>">
                                                <span class="template-name-display">
                                                    <?= htmlspecialchars($template['template_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <input type="text" class="form-control form-control-sm template-name-input" 
                                                       style="display: none;" value="<?= htmlspecialchars($template['template_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
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
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary template-use-btn" 
                                                        data-template-id="<?= $template['id'] ?>" 
                                                        title="このテンプレートを使用">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary template-edit-btn" 
                                                        data-template-id="<?= $template['id'] ?>" 
                                                        title="テンプレート名を編集">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger template-delete-btn" 
                                                        data-template-id="<?= $template['id'] ?>" 
                                                        title="テンプレートを削除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

<style>
/* フォームスタイリング */
.form-control:focus, .form-select:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header.bg-info {
    background-color: #17a2b8 !important;
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
}

/* アラートのスタイル */
.alert-primary {
    border-left: 4px solid #007bff;
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

.alert-info {
    border-left: 4px solid #17a2b8;
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

/* 無効化されたオプションのスタイル */
option:disabled {
    color: #6c757d;
    font-style: italic;
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

/* テンプレート機能のスタイル */
.template-buttons .btn {
    max-width: 200px;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.template-name-editable {
    position: relative;
}

.template-name-input {
    min-width: 200px;
}

/* テンプレート関連のアニメーション */
.template-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

/* モーダル内のテーブルスタイル */
#allTemplatesModal .table td {
    vertical-align: middle;
}

#allTemplatesModal .btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 元の交通手段選択時の処理
    const transportTypeSelect = document.getElementById('transport_type');
    const amountInput = document.getElementById('amount');
    const departureInput = document.getElementById('departure_point');
    const memoTextarea = document.getElementById('memo');
    
    // タクシー利用可否の設定
    const taxiAllowed = <?= json_encode(isset($serviceRecord['taxi_allowed']) ? (bool)$serviceRecord['taxi_allowed'] : false) ?>;
    
    // 初期表示時にデフォルトの交通手段に応じた表示/非表示を設定
    const initialTransportType = transportTypeSelect.value;
    const hideRouteTypes = ['gasoline', 'parking', 'rental_car'];
    const tripTypeGroup = document.getElementById('trip_type_group');
    const routeGroup = document.getElementById('route_group');
    const tripTypeSelect = document.getElementById('trip_type');
    const arrivalInput = document.getElementById('arrival_point');
    
    if (hideRouteTypes.includes(initialTransportType)) {
        tripTypeGroup.style.display = 'none';
        routeGroup.style.display = 'none';
        tripTypeSelect.removeAttribute('required');
        departureInput.removeAttribute('required');
        arrivalInput.removeAttribute('required');
    }
    
    // 交通手段変更時の処理
    transportTypeSelect.addEventListener('change', function() {
        const transportType = this.value;
        const memoLabel = document.querySelector('label[for="memo"]');
        const memoRequiredMark = document.getElementById('memo-required-mark');
        const memoHelpText = document.getElementById('memo-help-text');
        
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
            departureInput.value = '';
            tripTypeSelect.value = '';
            arrivalInput.value = '';
        } else {
            // 往復・片道と地点入力を表示
            tripTypeGroup.style.display = '';
            routeGroup.style.display = '';
            
            // required属性を追加
            tripTypeSelect.setAttribute('required', 'required');
            departureInput.setAttribute('required', 'required');
            arrivalInput.setAttribute('required', 'required');
        }
        
        // タクシーが選択された場合の処理
        if (transportType === 'taxi' && !taxiAllowed) {
            // メモを必須にする
            memoTextarea.required = true;
            memoRequiredMark.style.display = 'inline';
            memoHelpText.innerHTML = '<strong class="text-warning">タクシー利用時は理由の記載が必須です</strong>';
            memoTextarea.placeholder = '例：終電後のため、体調不良のため、重い機材運搬のため等';
            
            showMessage('タクシーが選択されました。この契約では原則利用不可のため、利用理由をメモ欄に必ず記載してください。', 'warning');
        } else {
            // メモを任意にする
            memoTextarea.required = false;
            memoRequiredMark.style.display = 'none';
            memoHelpText.innerHTML = '交通費に関する補足情報があれば記載してください<br><span class="text-danger">※個人情報や個人を特定できる情報は入力しないでください。</span>';
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
                if (!arrivalInput.value) {
                    arrivalInput.value = '<?= htmlspecialchars(($serviceRecord['company_name'] ?? '') . ' - ' . ($serviceRecord['branch_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>';
                }

                if (taxiAllowed) {
                    memoTextarea.placeholder = '例：深夜料金込み、迎車料金込み';
                    showMessage('タクシー利用が選択されました。契約でタクシー利用が認められています。', 'info');
                }
                // タクシー利用不可の場合のプレースホルダーは上で設定済み
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
    });
    
    // 金額入力時のフォーマット
    amountInput.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (value && value > 0) {
            // 入力値の妥当性チェック
            if (value > 999999) {
                this.value = 999999;
                showMessage('金額は999,999円以下で入力してください。', 'warning');
            }
        }
    });
    
    // フォーム送信前のバリデーション
    const form = document.querySelector('#expenseForm');
    form.addEventListener('submit', function(e) {
        const departure = departureInput.value.trim();
        const arrival = document.getElementById('arrival_point').value.trim();
        const amount = parseInt(amountInput.value);
        const selectedTransport = transportTypeSelect.value;
        
        // タクシー利用不可契約でタクシーが選択された場合
        if (selectedTransport === 'taxi' && !taxiAllowed) {
            const memo = memoTextarea.value.trim();
            if (!memo) {
                e.preventDefault();
                showMessage('タクシー利用時は理由の記載が必須です。', 'error');
                memoTextarea.focus();
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
        
        return true;
    });
    
    // ファイル選択時の処理
    const fileInput = document.getElementById('receipt_file');
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
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('対応していないファイル形式です。JPG、PNG、GIF、PDFファイルを選択してください。', 'error');
                this.value = '';
                return;
            }
            
            showMessage(`ファイル「${file.name}」を選択しました。`, 'success');
        }
    });

    // === テンプレート機能のJavaScript ===
    
    // テンプレート保存チェックボックスの制御
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
    
    // テンプレート使用ボタンの処理
    document.querySelectorAll('.template-btn, .template-use-btn').forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.dataset.templateId;
            loadTemplate(templateId);
        });
    });
    
    // テンプレート編集ボタンの処理
    document.querySelectorAll('.template-edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.dataset.templateId;
            const row = this.closest('tr');
            const nameDiv = row.querySelector('.template-name-editable');
            const displaySpan = nameDiv.querySelector('.template-name-display');
            const inputField = nameDiv.querySelector('.template-name-input');
            
            displaySpan.style.display = 'none';
            inputField.style.display = 'block';
            inputField.focus();
            
            // エンターキーまたはフォーカス外しで保存
            const saveEdit = () => {
                const newName = inputField.value.trim();
                if (newName && newName !== displaySpan.textContent.trim()) {
                    updateTemplateName(templateId, newName, displaySpan, inputField);
                } else {
                    displaySpan.style.display = 'inline';
                    inputField.style.display = 'none';
                }
            };
            
            inputField.addEventListener('blur', saveEdit);
            inputField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    saveEdit();
                }
            });
        });
    });
    
    // テンプレート削除ボタンの処理
    document.querySelectorAll('.template-delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.dataset.templateId;
            if (confirm('このテンプレートを削除しますか？')) {
                deleteTemplate(templateId);
            }
        });
    });
    
    // フォーム変更時にテンプレート保存チェックボックスを有効化
    const formFields = ['transport_type', 'trip_type', 'departure_point', 'arrival_point', 'amount', 'memo'];
    formFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                if (document.getElementById('used_template_id').value && saveAsTemplateCheck) {
                    saveAsTemplateCheck.disabled = false;
                }
            });
        }
    });
    
    // テンプレート読み込み関数
    function loadTemplate(templateId) {
        fetch(`<?= base_url('api/travel_expenses/template/') ?>${templateId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const template = data.data;
                    
                    // フォームにデータを設定
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
    
    // テンプレート名更新関数
    function updateTemplateName(templateId, newName, displaySpan, inputField) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= csrf_token() ?>');
        formData.append('template_name', newName);
        
        fetch(`<?= base_url('api/travel_expenses/template/') ?>${templateId}/name`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySpan.textContent = newName;
                displaySpan.style.display = 'inline';
                inputField.style.display = 'none';
                showMessage(data.message, 'success');
            } else {
                showMessage(data.error || 'テンプレート名の更新に失敗しました。', 'error');
                displaySpan.style.display = 'inline';
                inputField.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Template name update error:', error);
            showMessage('システムエラーが発生しました。', 'error');
            displaySpan.style.display = 'inline';
            inputField.style.display = 'none';
        });
    }
    
    // テンプレート削除関数
    function deleteTemplate(templateId) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= csrf_token() ?>');
        
        fetch(`<?= base_url('travel_expenses/template/') ?>${templateId}/delete`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                showMessage('テンプレートを削除しました。', 'success');
                // ページをリロードしてテンプレート一覧を更新
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showMessage('テンプレートの削除に失敗しました。', 'error');
            }
        })
        .catch(error => {
            console.error('Template delete error:', error);
            showMessage('システムエラーが発生しました。', 'error');
        });
    }
});

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
    const form = document.querySelector('form');
    form.insertBefore(alertDiv, form.firstChild);
    
    // 3秒後に自動削除
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}
</script>