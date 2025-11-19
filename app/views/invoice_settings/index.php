<?php
// app/views/invoice_settings/index.php

$pageTitle = '請求書設定';
$currentUser = Session::get('user');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-invoice me-2"></i>請求書設定</h2>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>請求書情報設定
        </h5>
        <p class="text-sm text-muted mt-2 mb-0">
            請求書PDFに表示される宛先情報や支払条件を設定します。<br>
            システム全体で1つの設定のみ保持されます。
        </p>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('invoice_settings') ?>" id="invoiceSettingForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <!-- 基本情報セクション -->
            <div class="section-header mb-3">
                <h6 class="text-primary"><i class="fas fa-building me-2"></i>宛先情報</h6>
            </div>
            
            <div class="row">
                <!-- 企業名 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">企業名 <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="company_name" 
                               name="company_name" 
                               value="<?= htmlspecialchars($setting['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required
                               maxlength="255"
                               placeholder="例: 株式会社サンプル">
                        <div class="form-text">請求書の宛先に表示されます</div>
                    </div>
                </div>
                
                <!-- 郵便番号 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="postal_code" class="form-label">郵便番号 <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="postal_code" 
                               name="postal_code" 
                               value="<?= htmlspecialchars($setting['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required
                               pattern="\d{3}-?\d{4}"
                               placeholder="123-4567"
                               maxlength="10">
                        <div class="form-text">ハイフンあり・なし両方可能</div>
                    </div>
                </div>
            </div>
            
            <!-- 住所 -->
            <div class="mb-3">
                <label for="address" class="form-label">住所 <span class="text-danger">*</span></label>
                <textarea class="form-control" 
                          id="address" 
                          name="address" 
                          rows="2"
                          required
                          placeholder="例: 東京都千代田区丸の内1-1-1"><?= htmlspecialchars($setting['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text">都道府県から建物名まで入力してください</div>
            </div>
            
            <!-- 係名・部署名 -->
            <div class="mb-3">
                <label for="department_name" class="form-label">係名・部署名</label>
                <input type="text" 
                       class="form-control" 
                       id="department_name" 
                       name="department_name" 
                       value="<?= htmlspecialchars($setting['department_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       maxlength="255"
                       placeholder="例: 経理部 御中">
                <div class="form-text">任意。特定の部署宛の場合に入力してください</div>
            </div>
            
            <hr class="my-4">
            
            <!-- 支払条件セクション -->
            <div class="section-header mb-3">
                <h6 class="text-primary"><i class="fas fa-calendar-alt me-2"></i>支払条件</h6>
            </div>
            
            <div class="row">
                <!-- 支払月 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="payment_month_offset" class="form-label">支払月 <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_month_offset" name="payment_month_offset" required>
                            <option value="1" <?= ($setting['payment_month_offset'] ?? 1) == 1 ? 'selected' : '' ?>>翌月</option>
                            <option value="2" <?= ($setting['payment_month_offset'] ?? 1) == 2 ? 'selected' : '' ?>>翌々月</option>
                        </select>
                        <div class="form-text">締め月の何ヶ月後に支払うか</div>
                    </div>
                </div>
                
                <!-- 支払日 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="payment_day_of_month" class="form-label">支払日 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="payment_day_of_month" 
                                   name="payment_day_of_month" 
                                   value="<?= htmlspecialchars($setting['payment_day_of_month'] ?? 31, ENT_QUOTES, 'UTF-8') ?>"
                                   required
                                   min="1"
                                   max="31">
                            <span class="input-group-text">日</span>
                        </div>
                        <div class="form-text">毎月の支払日（1〜31）。31を指定すると月末払いになります</div>
                    </div>
                </div>
            </div>
            
            <!-- 消費税率 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tax_rate" class="form-label">消費税率 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="tax_rate" 
                                   name="tax_rate" 
                                   value="<?= htmlspecialchars(($setting['tax_rate'] ?? 0.10) * 100, ENT_QUOTES, 'UTF-8') ?>"
                                   required
                                   min="0"
                                   max="100"
                                   step="0.01">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">請求書に適用される消費税率（0〜100）</div>
                    </div>
                </div>
            </div>
            
            <!-- 支払条件のプレビュー -->
            <div class="alert alert-info mb-3" id="paymentPreview">
                <i class="fas fa-info-circle me-2"></i>
                <span id="paymentPreviewText">例を計算中...</span>
            </div>
            
            <hr class="my-4">
            
            <!-- 備考欄セクション -->
            <div class="section-header mb-3">
                <h6 class="text-primary"><i class="fas fa-comment-dots me-2"></i>備考欄設定</h6>
            </div>
            
            <!-- 請求書備考欄 -->
            <div class="mb-3">
                <label for="invoice_note" class="form-label">請求書備考欄のデフォルト文言</label>
                <textarea class="form-control" 
                          id="invoice_note" 
                          name="invoice_note" 
                          rows="5"><?= htmlspecialchars($setting['invoice_note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text">請求書PDFの備考欄に表示される文言</div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-times me-1"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>保存
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 入力説明 -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-info-circle me-2"></i>入力項目について
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6><i class="fas fa-building text-primary me-2"></i>宛先情報</h6>
                <ul class="small">
                    <li><strong>企業名:</strong> 請求書に表示される会社名</li>
                    <li><strong>郵便番号:</strong> 〒マーク付きで表示されます</li>
                    <li><strong>住所:</strong> 会社の所在地</li>
                    <li><strong>係名・部署名:</strong> 特定部署宛の場合に使用</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6><i class="fas fa-calendar-alt text-primary me-2"></i>支払条件</h6>
                <ul class="small">
                    <li><strong>支払月:</strong> 締め月から何ヶ月後か</li>
                    <li><strong>支払日:</strong> 毎月の支払日</li>
                    <li><strong>消費税率:</strong> 請求書に適用される税率</li>
                    <li>※31日を指定すると月末払いになります</li>
                    <li>※2月など日数が少ない月は自動調整されます</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6><i class="fas fa-comment-dots text-primary me-2"></i>備考欄</h6>
                <ul class="small">
                    <li><strong>備考欄文言:</strong> 請求書に常に表示される内容</li>
                    <li>振込先情報を記載するのが一般的です</li>
                    <li>改行して複数行入力できます</li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>重要な注意事項</h6>
                    <ul class="mb-0 small">
                        <li><strong>システム全体で1つの設定:</strong> この設定は全ての請求書PDFに適用されます。</li>
                        <li><strong>過去の請求書:</strong> 既に発行済みの請求書には影響しません。</li>
                        <li><strong>郵便番号:</strong> ハイフンは自動で整形されます（123-4567形式）。</li>
                        <li><strong>支払日の自動調整:</strong> 指定日が存在しない月（例: 2月31日）は自動で月末日に調整されます。</li>
                        <li><strong>消費税率:</strong> パーセンテージで入力してください（10%の場合は「10」）。</li>
                        <li><strong>振込先情報:</strong> 備考欄には必ず正確な振込先情報を記載してください。</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMonthOffsetField = document.getElementById('payment_month_offset');
    const paymentDayField = document.getElementById('payment_day_of_month');
    const postalCodeField = document.getElementById('postal_code');
    const taxRateField = document.getElementById('tax_rate');
    const form = document.getElementById('invoiceSettingForm');
    
    // 支払条件のプレビューを更新
    function updatePaymentPreview() {
        const offset = parseInt(paymentMonthOffsetField.value);
        const day = parseInt(paymentDayField.value);
        const taxRate = parseFloat(taxRateField.value);
        
        if (!offset || !day || day < 1 || day > 31) {
            document.getElementById('paymentPreviewText').textContent = '支払条件を正しく入力してください';
            return;
        }
        
        // 現在の年月を取得
        const now = new Date();
        const exampleYear = now.getFullYear();
        const exampleMonth = now.getMonth() + 1; // 0-11 → 1-12
        
        // 支払月を計算
        let paymentMonth = exampleMonth + offset;
        let paymentYear = exampleYear;
        
        if (paymentMonth > 12) {
            paymentMonth -= 12;
            paymentYear++;
        }
        
        // 該当月の最終日を取得
        const lastDay = new Date(paymentYear, paymentMonth, 0).getDate();
        const paymentDay = Math.min(day, lastDay);
        
        const offsetText = offset === 1 ? '翌月' : '翌々月';
        const adjustmentNote = day > lastDay ? ` ※${day}日は存在しないため${lastDay}日に調整` : '';
        const taxRateText = isNaN(taxRate) ? '' : ` / 消費税率: ${taxRate}%`;
        const previewText = `例: ${exampleYear}年${exampleMonth}月分 → ${paymentYear}年${paymentMonth}月${paymentDay}日払い（${offsetText}${day}日払い）${adjustmentNote}${taxRateText}`;
        
        document.getElementById('paymentPreviewText').textContent = previewText;
    }
    
    // 郵便番号の自動整形
    function formatPostalCode() {
        let value = postalCodeField.value.replace(/[^\d]/g, '');
        if (value.length === 7) {
            postalCodeField.value = value.substring(0, 3) + '-' + value.substring(3);
        }
    }
    
    // 郵便番号のリアルタイム入力制限
    postalCodeField.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        const currentValue = this.value.replace(/[^\d]/g, '');
        
        // 数字とハイフンのみ許可
        if (!/[\d-]/.test(char)) {
            e.preventDefault();
            return;
        }
        
        // 7桁を超える数字入力を防ぐ
        if (/\d/.test(char) && currentValue.length >= 7) {
            e.preventDefault();
            return;
        }
    });
    
    // 支払日の入力範囲チェック
    paymentDayField.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (value < 1) {
            this.value = 1;
        } else if (value > 31) {
            this.value = 31;
        }
        updatePaymentPreview();
    });
    
    // 消費税率の入力範囲チェック
    taxRateField.addEventListener('input', function() {
        const value = parseFloat(this.value);
        if (value < 0) {
            this.value = 0;
        } else if (value > 100) {
            this.value = 100;
        }
        updatePaymentPreview();
    });
    
    // フォーム送信前のバリデーション
    form.addEventListener('submit', function(e) {
        // 郵便番号の形式チェック
        const postalCode = postalCodeField.value;
        if (postalCode && !/^\d{3}-\d{4}$/.test(postalCode)) {
            const cleaned = postalCode.replace(/[^\d]/g, '');
            if (cleaned.length === 7) {
                postalCodeField.value = cleaned.substring(0, 3) + '-' + cleaned.substring(3);
            } else {
                alert('郵便番号は7桁の数字で入力してください（例: 123-4567）');
                e.preventDefault();
                postalCodeField.focus();
                return;
            }
        }
        
        // 支払日のチェック
        const day = parseInt(paymentDayField.value);
        if (day < 1 || day > 31) {
            alert('支払日は1〜31の範囲で入力してください');
            e.preventDefault();
            paymentDayField.focus();
            return;
        }
        
        // 消費税率のチェック
        const taxRate = parseFloat(taxRateField.value);
        if (isNaN(taxRate) || taxRate < 0 || taxRate > 100) {
            alert('消費税率は0〜100の範囲で入力してください');
            e.preventDefault();
            taxRateField.focus();
            return;
        }
        
        // パーセンテージを小数に変換（10% → 0.10）
        const taxRateInput = document.createElement('input');
        taxRateInput.type = 'hidden';
        taxRateInput.name = 'tax_rate';
        taxRateInput.value = (taxRate / 100).toFixed(4);
        
        // 元の入力フィールドを無効化して、変換後の値を送信
        taxRateField.name = '';
        form.appendChild(taxRateInput);
    });
    
    // イベントリスナー設定
    paymentMonthOffsetField.addEventListener('change', updatePaymentPreview);
    paymentDayField.addEventListener('change', updatePaymentPreview);
    taxRateField.addEventListener('change', updatePaymentPreview);
    postalCodeField.addEventListener('blur', formatPostalCode);
    
    // 初期化
    updatePaymentPreview();
    
    // 既存データがあれば郵便番号を整形
    if (postalCodeField.value) {
        formatPostalCode();
    }
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.section-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #7dd3fc;
    box-shadow: 0 0 0 0.2rem rgba(125, 211, 252, 0.25);
}

.text-danger {
    color: #dc3545 !important;
}

.form-text {
    font-size: 0.875em;
    color: #6c757d;
}

.alert-info {
    background-color: #e7f3ff;
    border-color: #bee5eb;
    color: #0c5460;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.input-group-text {
    background-color: #f8f9fa;
}

@media (max-width: 768px) {
    .d-grid.gap-2.d-md-flex {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>