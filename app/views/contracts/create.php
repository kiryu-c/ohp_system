<?php
// app/views/contracts/create.php - 月次区分対応版
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-plus me-2"></i>契約作成</h2>
    <a href="<?= base_url('contracts') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>契約一覧に戻る
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>新規契約作成</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('contracts') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <!-- 1行目:企業・拠点 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_id" class="form-label">
                                企業 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="company_id" name="company_id" required onchange="updateBranches(this.value)">
                                <option value="">企業を選択してください</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= $company['id'] ?>">
                                        <?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?>
                                        (ID: <?= $company['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">契約する企業を選択してください</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="branch_id" class="form-label">
                                拠点 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="branch_id" name="branch_id" required disabled>
                                <option value="">まず企業を選択してください</option>
                            </select>
                            <div class="form-text">契約する拠点を選択してください</div>
                            <div id="branch-loading" class="text-muted" style="display: none;">
                                <i class="fas fa-spinner fa-spin me-1"></i>拠点を読み込み中...
                            </div>
                            <div id="branch-error" class="text-danger" style="display: none;">
                                <i class="fas fa-exclamation-triangle me-1"></i>拠点情報の取得に失敗しました
                            </div>
                        </div>
                    </div>
                    
                    <!-- 2行目:産業医・訪問頻度 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="doctor_id" class="form-label">
                                産業医 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">産業医を選択してください</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['id'] ?>">
                                        <?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?>
                                        (ID: <?= $doctor['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">契約する産業医を選択してください</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="visit_frequency" class="form-label">
                                訪問頻度 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="visit_frequency" name="visit_frequency" required onchange="updateVisitFrequency()">
                                <option value="">訪問頻度を選択してください</option>
                                <option value="monthly" selected>毎月</option>
                                <option value="bimonthly">隔月(2ヶ月に1回)</option>
                                <option value="weekly">毎週</option>
                                <option value="spot">スポット</option>
                            </select>
                            <div class="form-text">産業医の訪問頻度を選択してください</div>
                        </div>
                    </div>
                    
                    <!-- 隔月訪問の月次区分設定セクション -->
                    <div id="bimonthly_type_section" class="mb-3" style="display: none;">
                        <div class="card bg-info bg-opacity-10 border-info">
                            <div class="card-header bg-info bg-opacity-20">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-alt text-info me-2"></i>隔月訪問の月次区分設定
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">
                                            訪問する月を選択してください <span class="text-danger">*</span>
                                        </label>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-primary h-100">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="bimonthly_type" value="even" id="bimonthly_even">
                                                            <label class="form-check-label fw-bold" for="bimonthly_even">
                                                                <i class="fas fa-calendar-check text-primary me-1"></i>偶数月訪問
                                                            </label>
                                                        </div>
                                                        <div class="text-muted small mt-2">
                                                            <i class="fas fa-info-circle me-1"></i>2月、4月、6月、8月、10月、12月に訪問
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-primary me-1">2月</span>
                                                            <span class="badge bg-primary me-1">4月</span>
                                                            <span class="badge bg-primary me-1">6月</span>
                                                            <span class="badge bg-primary me-1">8月</span>
                                                            <span class="badge bg-primary me-1">10月</span>
                                                            <span class="badge bg-primary">12月</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-success h-100">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="bimonthly_type" value="odd" id="bimonthly_odd">
                                                            <label class="form-check-label fw-bold" for="bimonthly_odd">
                                                                <i class="fas fa-calendar-check text-success me-1"></i>奇数月訪問
                                                            </label>
                                                        </div>
                                                        <div class="text-muted small mt-2">
                                                            <i class="fas fa-info-circle me-1"></i>1月、3月、5月、7月、9月、11月に訪問
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-success me-1">1月</span>
                                                            <span class="badge bg-success me-1">3月</span>
                                                            <span class="badge bg-success me-1">5月</span>
                                                            <span class="badge bg-success me-1">7月</span>
                                                            <span class="badge bg-success me-1">9月</span>
                                                            <span class="badge bg-success">11月</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>重要:</strong> 隔月訪問では、選択した月次区分に基づいて訪問月が決定されます。
                                    開始日の月に関わらず、選択した区分の月のみが訪問対象となります。
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 週間スケジュール設定セクション -->
                    <div id="weekly_schedule_section" class="mb-3" style="display: none;">
                        <div class="card bg-success bg-opacity-10 border-success">
                            <div class="card-header bg-success bg-opacity-20">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-week text-success me-2"></i>週間訪問スケジュール設定
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">訪問する曜日を選択してください(複数選択可)</label>
                                        <div class="row">
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="1" id="day_1">
                                                    <label class="form-check-label" for="day_1">月曜日</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="4" id="day_4">
                                                    <label class="form-check-label" for="day_4">木曜日</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="2" id="day_2">
                                                    <label class="form-check-label" for="day_2">火曜日</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="5" id="day_5">
                                                    <label class="form-check-label" for="day_5">金曜日</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="3" id="day_3">
                                                    <label class="form-check-label" for="day_3">水曜日</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="6" id="day_6">
                                                    <label class="form-check-label" for="day_6">土曜日</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="weekly_days[]" value="7" id="day_7">
                                                    <label class="form-check-label" for="day_7">日曜日</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 祝日除外設定 -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">祝日の扱い</label>
                                        <div class="card bg-warning bg-opacity-10 border-warning">
                                            <div class="card-body py-2">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="exclude_holidays" id="exclude_holidays_yes" value="1" checked>
                                                    <label class="form-check-label" for="exclude_holidays_yes">
                                                        <i class="fas fa-times-circle text-danger me-1"></i>祝日は非訪問日
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="exclude_holidays" id="exclude_holidays_no" value="0">
                                                    <label class="form-check-label" for="exclude_holidays_no">
                                                        <i class="fas fa-check-circle text-success me-1"></i>祝日も訪問可
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-text">祝日に訪問するかどうかを設定</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3行目:定期訪問時間・タクシー可否・請求方法 -->
                    <div class="row">
                        <div class="col-md-4 mb-3" id="regular_visit_hours_wrapper">
                            <label for="regular_visit_hours" class="form-label">
                                <span id="visit-hours-label">定期訪問時間(毎月)</span> <span class="text-danger" id="regular_visit_hours_required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="regular_visit_hours" name="regular_visit_hours" 
                                       required min="0.5" max="200" step="0.5" value="1">
                                <span class="input-group-text">時間</span>
                            </div>
                            <div class="form-text">
                                <span id="visit-hours-description">月間の定期訪問時間を入力してください(0.5時間単位)</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">タクシー利用可否</label>
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="taxi_allowed" id="taxi_allowed_no" value="0" checked>
                                        <label class="form-check-label" for="taxi_allowed_no">
                                            <i class="fas fa-times-circle text-danger me-1"></i>利用不可
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="taxi_allowed" id="taxi_allowed_yes" value="1">
                                        <label class="form-check-label" for="taxi_allowed_yes">
                                            <i class="fas fa-check-circle text-success me-1"></i>利用可
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">タクシー利用の交通費申請可否を設定</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="tax_type" class="form-label">
                                税種別 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="tax_type" name="tax_type" required>
                                <option value="">税種別を選択してください</option>
                                <option value="exclusive" selected>外税</option>
                                <option value="inclusive">内税</option>
                            </select>
                            <div class="form-text">
                                外税: 請求書で税額を別途表示<br>
                                内税: 請求書で税込価格を表示
                            </div>
                        </div>
                    </div>

                    <!-- 料金設定セクション -->
                    <div class="mb-4">
                        <div class="card bg-info bg-opacity-10 border-info">
                            <div class="card-header bg-info bg-opacity-20">
                                <h6 class="mb-0">
                                    <i class="fas fa-yen-sign text-info me-2"></i>料金設定
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- 通常料金（定期・隔月・毎週の場合） -->
                                <div id="regular_rates_section" class="row">
                                    <!-- 定期訪問料金 -->
                                    <div class="col-md-3 mb-3">
                                        <label for="regular_visit_rate" class="form-label">
                                            定期訪問料金(1時間あたり) <span class="text-danger" id="regular_visit_rate_required">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="regular_visit_rate" name="regular_visit_rate" 
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">定期訪問1時間あたりの料金を入力</div>
                                    </div>
                                    
                                    <!-- 定期延長料金 -->
                                    <div class="col-md-3 mb-3">
                                        <label for="regular_extension_rate" class="form-label">
                                            定期延長料金(15分あたり) <span class="text-danger" id="regular_extension_rate_required">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="regular_extension_rate" name="regular_extension_rate" 
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">定期訪問の延長15分あたりの料金を入力</div>
                                    </div>
                                    
                                    <!-- 臨時訪問料金 -->
                                    <div class="col-md-3 mb-3">
                                        <label for="emergency_visit_rate" class="form-label">
                                            臨時訪問料金(15分あたり) <span class="text-danger" id="emergency_visit_rate_required">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="emergency_visit_rate" name="emergency_visit_rate" 
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">臨時訪問15分あたりの料金を入力</div>
                                    </div>
                                    
                                    <!-- 書面作成・遠隔相談料金 -->
                                    <div class="col-md-3 mb-3">
                                        <!-- 使用フラグ -->
                                        <div class="mb-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="use_remote_consultation" name="use_remote_consultation" value="1" onchange="toggleDocumentConsultationRate()">
                                                <label class="form-check-label" for="use_remote_consultation">
                                                    遠隔相談を使用
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="use_document_creation" name="use_document_creation" value="1" onchange="toggleDocumentConsultationRate()">
                                                <label class="form-check-label" for="use_document_creation">
                                                    書面作成を使用
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div id="document_consultation_rate_wrapper">
                                            <label for="document_consultation_rate" class="form-label">
                                                書面作成・遠隔相談料金(1回あたり) <span class="text-danger" id="document_rate_required">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="document_consultation_rate" name="document_consultation_rate" 
                                                    min="0">
                                                <span class="input-group-text">円</span>
                                            </div>
                                            <div class="form-text">書面作成・遠隔相談1回あたりの料金を入力</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- スポット料金（スポットの場合） -->
                                <div id="spot_rate_section" class="row" style="display: none;">
                                    <div class="col-md-6 mb-3">
                                        <label for="spot_rate" class="form-label">
                                            スポット料金(15分あたり) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="spot_rate" name="spot_rate" 
                                                min="0">
                                            <span class="input-group-text">円</span>
                                        </div>
                                        <div class="form-text">スポット15分あたりの料金を入力</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>スポット契約について:</strong><br>
                                            必要に応じて個別に訪問を実施する契約形態です。<br>
                                            料金は15分単位で計算されます。
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 料金設定の説明 -->
                                <div id="regular_rates_description" class="alert alert-light border-info">
                                    <div class="d-flex">
                                        <i class="fas fa-info-circle text-info me-2 mt-1"></i>
                                        <div>
                                            <strong>料金設定について:</strong>
                                            <ul class="mb-0 mt-1">
                                                <li><strong>定期訪問料金:</strong> 契約に基づく定期的な訪問の時間単価</li>
                                                <li><strong>定期延長料金:</strong> 定期訪問の予定時間を延長した場合の15分単価</li>
                                                <li><strong>臨時訪問料金:</strong> 緊急時や追加対応の15分単価</li>
                                                <li><strong>書面作成・遠隔相談料金:</strong> 意見書作成や電話相談等の1回あたり料金</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 訪問頻度詳細情報 -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div id="frequency-details" class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        <div>
                                            <div id="frequency-title" class="fw-bold text-primary">毎月訪問</div>
                                            <div id="frequency-desc" class="text-muted">毎月1回、指定された時間数の訪問を実施</div>
                                        </div>
                                    </div>
                                    <div id="frequency-example" class="mt-2 small">
                                        <i class="fas fa-info-circle me-1"></i>毎月1時間の定期訪問が実施されます
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4行目:契約期間 -->
                    <div class="row">
                        <?php
                        // 翌月の1日を計算
                        $nextMonthFirstDay = date('Y-m-01', strtotime('first day of next month'));
                        ?>
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">
                                契約開始日 <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                required value="<?= $nextMonthFirstDay ?>">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>契約開始日は毎月1日を指定してください
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">契約終了日</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                            <div class="form-text">終了日を指定しない場合は無期限契約となります</div>
                        </div>
                    </div>
                    <!-- 専属登録料 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="exclusive_registration_fee" class="form-label">
                                <i class="fas fa-hand-holding-usd me-1"></i>専属登録料
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control text-end" id="exclusive_registration_fee" 
                                       name="exclusive_registration_fee" 
                                       min="0" step="1" placeholder="0">
                                <span class="input-group-text">円</span>
                            </div>
                            <div class="form-text">毎月請求されます(任意)</div>
                        </div>
                    </div>
                    
                    <!-- 契約書ファイルアップロード -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="contract_file" class="form-label">
                                <i class="fas fa-file-pdf me-1"></i>契約書ファイル
                            </label>
                            <input type="file" class="form-control" id="contract_file" name="contract_file" 
                                   accept=".pdf,.doc,.docx" onchange="validateFile()">
                            <div class="form-text">
                                PDF、Word文書(.pdf, .doc, .docx)をアップロードできます。最大サイズ: 10MB
                            </div>
                            <div id="file-info" class="mt-2" style="display: none;">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file me-2 text-primary"></i>
                                            <div class="flex-grow-1">
                                                <div id="file-name" class="fw-bold"></div>
                                                <div id="file-size" class="text-muted small"></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="file-error" class="text-danger mt-1" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('contracts') ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>キャンセル
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>契約を作成
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// 訪問頻度変更時の処理
function updateVisitFrequency() {
    const frequency = document.getElementById('visit_frequency').value;
    const weeklySection = document.getElementById('weekly_schedule_section');
    const bimonthlySection = document.getElementById('bimonthly_type_section');
    const regularRatesSection = document.getElementById('regular_rates_section');
    const spotRateSection = document.getElementById('spot_rate_section');
    const regularRatesDescription = document.getElementById('regular_rates_description');
 
    console.log("==frequency:" + frequency);

    // 料金セクションの表示/非表示制御
    if (frequency === 'spot') {
        // スポット：スポット料金のみ表示
        regularRatesSection.style.display = 'none';
        spotRateSection.style.display = 'flex';
        regularRatesDescription.style.display = 'none';
        
        // 通常料金フィールドの必須を解除
        document.getElementById('regular_visit_rate').required = false;
        document.getElementById('regular_extension_rate').required = false;
        document.getElementById('emergency_visit_rate').required = false;
        document.getElementById('regular_visit_rate_required').style.display = 'none';
        document.getElementById('regular_extension_rate_required').style.display = 'none';
        document.getElementById('emergency_visit_rate_required').style.display = 'none';
        
        // スポット料金フィールドを必須に
        document.getElementById('spot_rate').required = true;
        
        // 訪問時間入力を非表示・非必須に
        document.getElementById('regular_visit_hours_wrapper').style.display = 'none';
        document.getElementById('regular_visit_hours').required = false;
        document.getElementById('regular_visit_hours_required').style.display = 'none';
    } else {
        // 通常：定期・延長・臨時料金を表示
        regularRatesSection.style.display = 'flex';
        spotRateSection.style.display = 'none';
        regularRatesDescription.style.display = 'block';
        
        // 通常料金フィールドを必須に
        document.getElementById('regular_visit_rate').required = true;
        document.getElementById('regular_extension_rate').required = true;
        document.getElementById('emergency_visit_rate').required = true;
        document.getElementById('regular_visit_rate_required').style.display = 'inline';
        document.getElementById('regular_extension_rate_required').style.display = 'inline';
        document.getElementById('emergency_visit_rate_required').style.display = 'inline';
        
        // スポット料金フィールドの必須を解除
        document.getElementById('spot_rate').required = false;
        
        // 訪問時間入力を表示・必須に
        document.getElementById('regular_visit_hours_wrapper').style.display = 'block';
        document.getElementById('regular_visit_hours').required = true;
        document.getElementById('regular_visit_hours_required').style.display = 'inline';
    }

    // 週間スケジュールセクションの表示/非表示
    if (frequency === 'weekly') {
        weeklySection.style.display = 'block';
        bimonthlySection.style.display = 'none';
        // チェックボックスをクリア(週間)
        const checkboxes = weeklySection.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
        // ラジオボタンをクリア(隔月)
        clearBimonthlyType();
    } else if (frequency === 'bimonthly') {
        weeklySection.style.display = 'none';
        bimonthlySection.style.display = 'block';
        // チェックボックスをクリア(週間)
        const checkboxes = weeklySection.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
    } else {
        weeklySection.style.display = 'none';
        bimonthlySection.style.display = 'none';
        // チェックボックスをクリア(週間)
        const checkboxes = weeklySection.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
        // ラジオボタンをクリア(隔月)
        clearBimonthlyType();
    }
    
    // ラベルと説明の更新
    updateVisitHoursLabel();
}

// 隔月区分のクリア
function clearBimonthlyType() {
    const radios = document.querySelectorAll('input[name="bimonthly_type"]');
    radios.forEach(radio => radio.checked = false);
}

// 訪問頻度に応じて表示を更新する関数
function updateVisitHoursLabel() {
    const frequency = document.getElementById('visit_frequency').value;
    const label = document.getElementById('visit-hours-label');
    const description = document.getElementById('visit-hours-description');
    const example = document.getElementById('frequency-example');
    const title = document.getElementById('frequency-title');
    const desc = document.getElementById('frequency-desc');
    const details = document.getElementById('frequency-details');
    const hoursInput = document.getElementById('regular_visit_hours');
    
    if (frequency === 'monthly') {
        label.textContent = '定期訪問時間(毎月)';
        description.textContent = '月間の定期訪問時間を入力してください(0.5時間単位)';
        example.innerHTML = '<i class="fas fa-info-circle me-1"></i>毎月' + (hoursInput.value || '1') + '時間の定期訪問が実施されます';
        title.textContent = '毎月訪問';
        desc.textContent = '毎月1回、指定された時間数の訪問を実施';
        details.className = 'card bg-primary bg-opacity-10 border-primary';
        details.querySelector('i').className = 'fas fa-calendar-alt text-primary me-2';
        title.className = 'fw-bold text-primary';
    } else if (frequency === 'bimonthly') {
        label.textContent = '定期訪問時間(隔月・2ヶ月分)';
        description.textContent = '隔月訪問時の2ヶ月分の時間を入力してください(0.5時間単位)';
        example.innerHTML = '<i class="fas fa-info-circle me-1"></i>隔月で' + (hoursInput.value || '1') + '時間の定期訪問が実施されます(2ヶ月に1回)';
        title.textContent = '隔月訪問';
        desc.textContent = '2ヶ月に1回、指定された時間数の訪問を実施(偶数月または奇数月を選択)';
        details.className = 'card bg-info bg-opacity-10 border-info';
        details.querySelector('i').className = 'fas fa-calendar-alt text-info me-2';
        title.className = 'fw-bold text-info';
    } else if (frequency === 'weekly') {
        label.textContent = '1回あたりの訪問時間';
        description.textContent = '1回の訪問あたりの時間を入力してください(0.5時間単位)';
        example.innerHTML = '<i class="fas fa-info-circle me-1"></i>選択した曜日に毎週' + (hoursInput.value || '1') + '時間の訪問が実施されます';
        title.textContent = '毎週訪問';
        desc.textContent = '指定した曜日に毎週訪問を実施';
        details.className = 'card bg-success bg-opacity-10 border-success';
        details.querySelector('i').className = 'fas fa-calendar-week text-success me-2';
        title.className = 'fw-bold text-success';
    } else if (frequency === 'spot') {
        label.textContent = 'スポット契約';
        description.textContent = 'スポット契約では訪問時間の事前設定は不要です';
        example.innerHTML = '<i class="fas fa-info-circle me-1"></i>必要に応じて個別に訪問を実施します';
        title.textContent = 'スポット契約';
        desc.textContent = '必要に応じて個別に訪問を実施（料金は15分単位で計算）';
        details.className = 'card bg-warning bg-opacity-10 border-warning';
        details.querySelector('i').className = 'fas fa-clock text-warning me-2';
        title.className = 'fw-bold text-warning';
    } else {
        label.textContent = '定期訪問時間';
        description.textContent = '訪問頻度を選択してください';
        example.innerHTML = '<i class="fas fa-info-circle me-1"></i>訪問頻度を選択すると詳細が表示されます';
        title.textContent = '訪問頻度未選択';
        desc.textContent = '訪問頻度を選択してください';
        details.className = 'card bg-light border-secondary';
        details.querySelector('i').className = 'fas fa-calendar-alt text-muted me-2';
        title.className = 'fw-bold text-muted';
    }
}

// ファイルバリデーション関数
function validateFile() {
    const fileInput = document.getElementById('contract_file');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const fileError = document.getElementById('file-error');
    
    fileError.style.display = 'none';
    fileInfo.style.display = 'none';
    
    if (!fileInput.files || fileInput.files.length === 0) {
        return;
    }
    
    const file = fileInput.files[0];
    const maxSize = 10 * 1024 * 1024;
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if (file.size > maxSize) {
        fileError.textContent = 'ファイルサイズが大きすぎます。10MB以下のファイルを選択してください。';
        fileError.style.display = 'block';
        fileInput.value = '';
        return;
    }
    
    if (!allowedTypes.includes(file.type)) {
        fileError.textContent = 'サポートされていないファイル形式です。PDF、Word文書をアップロードしてください。';
        fileError.style.display = 'block';
        fileInput.value = '';
        return;
    }
    
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    fileInfo.style.display = 'block';
}

function removeFile() {
    const fileInput = document.getElementById('contract_file');
    const fileInfo = document.getElementById('file-info');
    const fileError = document.getElementById('file-error');
    
    fileInput.value = '';
    fileInfo.style.display = 'none';
    fileError.style.display = 'none';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 企業選択時に拠点を更新する関数
function updateBranches(companyId) {
    const branchSelect = document.getElementById('branch_id');
    const loadingDiv = document.getElementById('branch-loading');
    const errorDiv = document.getElementById('branch-error');
    
    errorDiv.style.display = 'none';
    
    if (!companyId) {
        branchSelect.innerHTML = '<option value="">まず企業を選択してください</option>';
        branchSelect.disabled = true;
        return;
    }
    
    loadingDiv.style.display = 'block';
    branchSelect.disabled = true;
    branchSelect.innerHTML = '<option value="">読み込み中...</option>';
    
    fetch(`<?= base_url('api/branches/') ?>${companyId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            loadingDiv.style.display = 'none';
            
            if (data.success) {
                const branches = data.data || data.branches || [];
                
                branchSelect.innerHTML = '<option value="">拠点を選択してください</option>';
                
                if (branches.length === 0) {
                    branchSelect.innerHTML = '<option value="">この企業には拠点が登録されていません</option>';
                    branchSelect.disabled = true;
                } else {
                    branches.forEach(branch => {
                        const option = document.createElement('option');
                        option.value = branch.id;
                        option.textContent = branch.name;
                        if (branch.address) {
                            option.textContent += ' - ' + branch.address;
                        }
                        branchSelect.appendChild(option);
                    });
                    branchSelect.disabled = false;
                }
            } else {
                throw new Error(data.error || '拠点情報の取得に失敗しました');
            }
        })
        .catch(error => {
            console.error('Branch fetch error:', error);
            loadingDiv.style.display = 'none';
            errorDiv.style.display = 'block';
            branchSelect.innerHTML = '<option value="">拠点の取得に失敗しました</option>';
            branchSelect.disabled = true;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const visitFrequencySelect = document.getElementById('visit_frequency');
    const regularVisitHoursInput = document.getElementById('regular_visit_hours');
    const startDateInput = document.getElementById('start_date');
    
    // 訪問時間の変更時に例示を更新
    regularVisitHoursInput.addEventListener('input', function() {
        updateVisitHoursLabel();
    });
    
    // 初期状態の設定
    updateVisitHoursLabel();
    
    // フォームバリデーション
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // 開始日が1日かチェック
        if (startDateInput.value) {
            const startDate = new Date(startDateInput.value);
            if (startDate.getDate() !== 1) {
                alert('契約開始日は毎月1日を指定してください。');
                startDateInput.focus();
                isValid = false;
            }
        }

        // 週間スケジュールの検証
        if (visitFrequencySelect.value === 'weekly') {
            const selectedDays = document.querySelectorAll('input[name="weekly_days[]"]:checked');
            if (selectedDays.length === 0) {
                alert('週間契約では訪問する曜日を最低1つ選択してください。');
                isValid = false;
            }
        }
        
        // 隔月訪問の月次区分検証
        if (visitFrequencySelect.value === 'bimonthly') {
            const selectedType = document.querySelector('input[name="bimonthly_type"]:checked');
            if (!selectedType) {
                alert('隔月訪問では月次区分(偶数月/奇数月)を選択してください。');
                const bimonthlySection = document.getElementById('bimonthly_type_section');
                bimonthlySection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                bimonthlySection.classList.add('border-danger');
                setTimeout(() => {
                    bimonthlySection.classList.remove('border-danger');
                }, 3000);
                isValid = false;
            }
        }
        
        // スポット料金の検証
        if (visitFrequencySelect.value === 'spot') {
            const spotRateInput = document.getElementById('spot_rate');
            if (!spotRateInput.value) {
                alert('スポット契約ではスポット料金を入力してください。');
                spotRateInput.focus();
                isValid = false;
            }
        }
        
        // 書面作成・遠隔相談料金の検証（チェックボックスがONの場合のみ）
        const useRemoteConsultation = document.getElementById('use_remote_consultation').checked;
        const useDocumentCreation = document.getElementById('use_document_creation').checked;
        const documentRateInput = document.getElementById('document_consultation_rate');
        
        if ((useRemoteConsultation || useDocumentCreation) && !documentRateInput.value) {
            alert('遠隔相談または書面作成を使用する場合は、料金を入力してください。');
            documentRateInput.focus();
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });

    // 開始日変更時のリアルタイムバリデーション（追加）
    startDateInput.addEventListener('change', function() {
        if (this.value) {
            const startDate = new Date(this.value);
            if (startDate.getDate() !== 1) {
                this.classList.add('is-invalid');
                let errorDiv = this.parentElement.querySelector('.invalid-feedback');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    this.parentElement.appendChild(errorDiv);
                }
                errorDiv.textContent = '契約開始日は毎月1日を指定してください。';
            } else {
                this.classList.remove('is-invalid');
                const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        }
    });
});

// 書面作成・遠隔相談料金の表示/非表示を切り替える関数
function toggleDocumentConsultationRate() {
    const useRemoteConsultation = document.getElementById('use_remote_consultation').checked;
    const useDocumentCreation = document.getElementById('use_document_creation').checked;
    const rateWrapper = document.getElementById('document_consultation_rate_wrapper');
    const rateInput = document.getElementById('document_consultation_rate');
    const requiredMark = document.getElementById('document_rate_required');
    
    if (useRemoteConsultation || useDocumentCreation) {
        // どちらかがONの場合、料金フィールドを表示して必須に
        rateWrapper.style.display = 'block';
        rateInput.required = true;
        requiredMark.style.display = 'inline';
    } else {
        // 両方OFFの場合、料金フィールドを非表示にして非必須に
        rateWrapper.style.display = 'none';
        rateInput.required = false;
        rateInput.value = '';
        requiredMark.style.display = 'none';
    }
}

// ページ読み込み時に初期状態を設定
document.addEventListener('DOMContentLoaded', function() {
    toggleDocumentConsultationRate();
});
</script>