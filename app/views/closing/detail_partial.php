<?php
// app/views/closing/detail_partial.php - 締め処理詳細の部分ビュー

// 時間を「X時間Y分」形式に変換する関数
if (!function_exists('formatHoursMinutes')) {
    function formatHoursMinutes($hours) {
        if ($hours == 0) {
            return '0時間';
        }
        
        $totalMinutes = round($hours * 60);
        $h = intval($totalMinutes / 60);
        $m = $totalMinutes % 60;
        
        if ($h == 0) {
            return $m . '分';
        } elseif ($m == 0) {
            return $h . '時間';
        } else {
            return $h . '時間' . $m . '分';
        }
    }
}
?>
<div class="closing-detail">
    <!-- 基本情報 -->
    <div class="row mb-3">
        <div class="col-md-6">
            <h6 class="fw-bold">契約情報</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td class="text-muted" style="width: 120px;">会社名:</td>
                    <td><?= h($closingRecord['company_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">支店名:</td>
                    <td><?= h($closingRecord['branch_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">産業医:</td>
                    <td><?= h($closingRecord['doctor_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">対象期間:</td>
                    <td><?= date('Y年m月', strtotime($closingRecord['closing_period'] . '-01')) ?></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold">処理情報</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td class="text-muted" style="width: 120px;">ステータス:</td>
                    <td>
                        <?php if ($closingRecord['status'] === 'finalized'): ?>
                            <span class="badge bg-success">確定済み</span>
                        <?php else: ?>
                            <span class="badge bg-info">下書き</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">確定日時:</td>
                    <td>
                        <?php if ($closingRecord['finalized_at']): ?>
                            <?= date('Y/m/d H:i', strtotime($closingRecord['finalized_at'])) ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">処理者:</td>
                    <td><?= h($closingRecord['finalized_by_name'] ?? '') ?></td>
                </tr>
            </table>
        </div>
    </div>

    <hr>

    <!-- 時間・金額サマリー -->
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="fw-bold mb-3">集計結果</h6>
            <div class="row">
                <div class="col-md-2">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center p-2">
                            <div class="text-primary fw-bold fs-5"><?= formatHoursMinutes($closingRecord['regular_hours']) ?></div>
                            <small class="text-muted">定期訪問時間</small>
                            <div class="text-success mt-1"><?= number_format($closingRecord['regular_amount']) ?>円</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center p-2">
                            <div class="text-warning fw-bold fs-5"><?= formatHoursMinutes($closingRecord['regular_extension_hours']) ?></div>
                            <small class="text-muted">定期延長時間</small>
                            <div class="text-success mt-1"><?= number_format($closingRecord['regular_extension_amount']) ?>円</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center p-2">
                            <div class="text-danger fw-bold fs-5"><?= formatHoursMinutes($closingRecord['emergency_hours']) ?></div>
                            <small class="text-muted">臨時訪問時間</small>
                            <div class="text-success mt-1"><?= number_format($closingRecord['emergency_amount']) ?>円</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center p-2">
                            <div class="text-info fw-bold fs-5">
                                <?= $closingRecord['document_count'] + $closingRecord['remote_consultation_count'] ?>件
                            </div>
                            <small class="text-muted">その他業務</small>
                            <div class="text-success mt-1">
                                <?= number_format($closingRecord['document_amount'] + $closingRecord['remote_consultation_amount']) ?>円
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 交通費のカードを追加 -->
                <div class="col-md-2">
                    <div class="card bg-light border-0">
                        <div class="card-body text-center p-2">
                            <div class="text-secondary fw-bold fs-5">
                                <?= $closingRecord['travel_expense_count'] ?? 0 ?>件
                            </div>
                            <small class="text-muted">交通費</small>
                            <div class="text-success mt-1">
                                <?= number_format($closingRecord['travel_expense_amount'] ?? 0) ?>円
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 交通費詳細セクションを追加 -->
    <?php if ($simulationData && isset($simulationData['travel_expenses']) && !empty($simulationData['travel_expenses'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="fw-bold mb-3">交通費詳細</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>サービス日</th>
                            <th>交通手段</th>
                            <th>出発地</th>
                            <th>到着地</th>
                            <th>往復・片道</th>
                            <th class="text-end">金額</th>
                            <th>備考</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($simulationData['travel_expenses'] as $expense): ?>
                            <tr>
                                <td><?= date('Y/m/d', strtotime($expense['service_date'])) ?></td>
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
                                    echo h($transportLabels[$expense['transport_type']] ?? $expense['transport_type']);
                                    ?>
                                </td>
                                <td><?= h($expense['departure_point']) ?></td>
                                <td><?= h($expense['arrival_point']) ?></td>
                                <td>
                                    <?php if ($expense['trip_type'] === 'round_trip'): ?>
                                        <span class="badge bg-info">往復</span>
                                    <?php elseif ($expense['trip_type'] === 'one_way'): ?>
                                        <span class="badge bg-secondary">片道</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold">
                                    <?= number_format($expense['amount']) ?>円
                                </td>
                                <td>
                                    <?php if ($expense['memo']): ?>
                                        <?= h($expense['memo']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-active">
                            <th colspan="5">交通費合計</th>
                            <th class="text-end"><?= number_format($simulationData['travel_expense_total']) ?>円</th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 請求金額 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="card-title mb-1">総請求金額</h6>
                            <div class="fs-4 fw-bold">
                                <?= number_format($closingRecord['total_amount_with_tax']) ?>円
                            </div>
                            <?php if ($closingRecord['tax_amount'] > 0): ?>
                                <small class="opacity-75">
                                    (税抜: <?= number_format($closingRecord['total_amount']) ?>円 + 消費税: <?= number_format($closingRecord['tax_amount']) ?>円)
                                </small>
                            <?php endif; ?>
                            <!-- 交通費を含む内訳表示 -->
                            <div class="mt-2">
                                <small class="opacity-75">
                                    役務: <?= number_format($closingRecord['total_amount'] - ($closingRecord['travel_expense_amount'] ?? 0)) ?>円 + 
                                    交通費: <?= number_format($closingRecord['travel_expense_amount'] ?? 0) ?>円
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="text-white-50 mb-1">契約時間</div>
                            <div class="fw-bold"><?= formatHoursMinutes($closingRecord['contract_hours']) ?></div>
                            <div class="text-white-50 mb-1 mt-2">承認済み時間</div>
                            <div class="fw-bold"><?= formatHoursMinutes($closingRecord['total_approved_hours']) ?></div>
                            <div class="text-white-50 mb-1 mt-2">交通費件数</div>
                            <div class="fw-bold"><?= $closingRecord['travel_expense_count'] ?? 0 ?>件</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 詳細内訳 -->
    <?php if ($simulationData && isset($simulationData['service_breakdown'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="fw-bold mb-3">詳細内訳</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>サービス日</th>
                            <th>サービス種別</th>
                            <th>実働時間</th>
                            <th>請求時間</th>
                            <th>請求金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($simulationData['service_breakdown'] as $item): ?>
                            <?php $serviceRecord = $item['service_record']; ?>
                            <tr>
                                <td>
                                    <?php if ($serviceRecord): ?>
                                        <?= date('Y/m/d', strtotime($serviceRecord['service_date'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">延長時間</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeLabels = [
                                        'regular' => '定期訪問',
                                        'regular_extension' => '定期延長',
                                        'emergency' => '臨時訪問',
                                        'document' => '書面相談',
                                        'remote_consultation' => '遠隔相談'
                                    ];
                                    echo h($typeLabels[$item['billing_service_type']] ?? $item['billing_service_type']);
                                    ?>
                                </td>
                                <td>
                                    <?php if ($item['original_hours'] > 0): ?>
                                        <?= formatHoursMinutes($item['original_hours']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['billing_hours'] > 0): ?>
                                        <?= formatHoursMinutes($item['billing_hours']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold">
                                    <?= number_format($item['billing_amount']) ?>円
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 処理履歴 -->
    <?php if (!empty($history)): ?>
    <div class="row">
        <div class="col-12">
            <h6 class="fw-bold mb-3">処理履歴</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>日時</th>
                            <th>処理</th>
                            <th>実行者</th>
                            <th>コメント</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                            <tr>
                                <td><?= date('Y/m/d H:i', strtotime($record['action_at'])) ?></td>
                                <td>
                                    <?php
                                    $actionLabels = [
                                        'simulate' => 'シミュレーション実行',
                                        'finalize' => '締め処理確定',
                                        'reopen' => '締め処理取り消し'
                                    ];
                                    echo h($actionLabels[$record['action_type']] ?? $record['action_type']);
                                    ?>
                                </td>
                                <td><?= h($record['action_by_name']) ?></td>
                                <td>
                                    <?php if ($record['comment']): ?>
                                        <?= h($record['comment']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.closing-detail .card {
    box-shadow: none;
    border: 1px solid rgba(0,0,0,.125);
}

.closing-detail .table-borderless td {
    border: none;
    padding: 0.25rem 0;
}

.closing-detail .fs-5 {
    font-size: 1.25rem !important;
}
</style>