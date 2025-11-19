<?php
// app/core/helpers.php

function base_url($path = '') {
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/../../config/app.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            // フォールバック設定
            $config = ['app_url' => 'http://localhost/op_website/public'];
        }
    }
    
    $baseUrl = $config['app_url'] ?? 'http://localhost/op_website/public';
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

function view($viewName, $data = []) {
    extract($data);
    
    $viewFile = __DIR__ . "/../views/{$viewName}.php";
    
    if (!file_exists($viewFile)) {
        die("View not found: {$viewName}");
    }
    
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    
    return $content;
}

function redirect($path) {
    header("Location: " . base_url($path));
    exit;
}

function csrf_token() {
    // セッションを確実に開始
    if (session_status() === PHP_SESSION_NONE) {
        Session::start();
    }
    
    if (!Session::has('csrf_token')) {
        Session::set('csrf_token', bin2hex(random_bytes(32)));
    }
    return Session::get('csrf_token');
}

function verify_csrf_token($token) {
    // セッションを確実に開始
    if (session_status() === PHP_SESSION_NONE) {
        Session::start();
    }
    
    $sessionToken = Session::get('csrf_token');
    
    // 両方の値が文字列であることを確認
    if (!is_string($token) || !is_string($sessionToken)) {
        error_log('CSRF token validation failed: invalid token type');
        error_log('Provided token type: ' . gettype($token));
        error_log('Session token type: ' . gettype($sessionToken));
        return false;
    }
    
    // 空文字列チェック
    if (empty($token) || empty($sessionToken)) {
        error_log('CSRF token validation failed: empty token');
        return false;
    }
    
    return hash_equals($sessionToken, $token);
}


function sanitize($string) {
    return htmlspecialchars(trim($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_date($date, $format = null) {
    // null、空文字、falseの場合は空文字を返す
    if (empty($date) || $date === false) {
        return '';
    }
    
    if (!$format) {
        static $config = null;
        if ($config === null) {
            $configFile = __DIR__ . '/../../config/app.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                $config = ['date_format' => 'Y年m月d日'];
            }
        }
        $format = $config['date_format'] ?? 'Y年m月d日';
    }
    
    try {
        if (is_string($date)) {
            $date = new DateTime($date);
        } elseif (!($date instanceof DateTime)) {
            // DateTimeオブジェクトでもない場合は空文字を返す
            return '';
        }
        
        return $date->format($format);
    } catch (Exception $e) {
        // 日付の解析に失敗した場合は空文字を返す
        return '';
    }
}

function format_time($time, $format = null) {
    if (!$format) {
        static $config = null;
        if ($config === null) {
            $configFile = __DIR__ . '/../../config/app.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                $config = ['time_format' => 'H:i'];
            }
        }
        $format = $config['time_format'] ?? 'H:i';
    }
    
    if (is_string($time)) {
        $time = new DateTime($time);
    }
    
    return $time->format($format);
}

/**
 * 役務種別に応じた時間計算（15分切り上げ対応）
 */
function calculate_service_hours($startTime, $endTime, $serviceType = 'regular') {
    if (empty($startTime) || empty($endTime)) {
        return 0;
    }
    
    $start = new DateTime("1970-01-01 $startTime");
    $end = new DateTime("1970-01-01 $endTime");
    
    // 日をまたぐ場合の処理
    if ($end < $start) {
        $end->modify('+1 day');
    }
    
    $interval = $start->diff($end);
    $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
    
    // 定期延長のみ15分切り上げ（臨時訪問の切り上げを削除）
    if ($serviceType === 'extension') {
        // 15分切り上げ処理
        $roundedHours = ceil($totalHours * 4) / 4;
        
        error_log("15分切り上げ計算: {$serviceType}, 元の時間: {$totalHours}, 切り上げ後: {$roundedHours}");
        
        return $roundedHours;
    }
    
    // 臨時訪問は切り上げしない
    return $totalHours;
}

function calculate_hours($start_time, $end_time) {
    return calculate_service_hours($startTime, $endTime, $serviceType);
}

function get_user_type_label($type) {
    $labels = [
        'doctor' => '産業医',
        'company' => '企業ユーザー',
        'admin' => '管理者'
    ];
    
    return $labels[$type] ?? $type;
}

function get_status_label($status) {
    $labels = [
        'pending' => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '差戻し',
        'finalized' => '締め済み',
        'active' => '有効',
        'inactive' => '無効',
        'terminated' => '終了'
    ];
    
    return $labels[$status] ?? $status;
}

/**
 * 役務種別のラベルを取得
 */
function get_service_type_label($serviceType) {
    $labels = [
        'regular' => '定期訪問',
        'emergency' => '臨時訪問',
        'extension' => '定期延長',
        'spot' => 'スポット',
        'document' => '書面作成',
        'remote_consultation' => '遠隔相談',
        'other' => 'その他'
    ];
    
    return $labels[$serviceType] ?? $serviceType;
}

/**
 * 役務種別のアイコンを取得
 */
function get_service_type_icon($serviceType) {
    $icons = [
        'regular' => 'fas fa-calendar-check text-primary',
        'emergency' => 'fas fa-exclamation-triangle text-warning',
        'extension' => 'fas fa-clock text-info',
        'spot' => 'fas fa-clock text-info',
        'document' => 'fas fa-file-alt text-success',
        'remote_consultation' => 'fas fa-envelope text-secondary',
        'other' => 'fas fa-plus text-dark'
    ];
    
    return $icons[$serviceType] ?? 'fas fa-question-circle text-muted';
}

/**
 * 役務種別のCSSクラスを取得
 */
function get_service_type_class($serviceType) {
    $classes = [
        'regular' => 'primary',
        'emergency' => 'warning',
        'extension' => 'info',
        'spot' => 'info',
        'document' => 'success',
        'remote_consultation' => 'secondary',
        'other' => 'dark'
    ];
    
    return $classes[$serviceType] ?? 'secondary';
}

/**
 * 役務種別の説明を取得
 */
function get_service_type_description($serviceType) {
    $descriptions = [
        'regular' => '月間契約時間内での通常業務',
        'emergency' => '緊急対応や特別要請による訪問',
        'extension' => '定期訪問の時間延長分',
        'spot' => 'スポット契約による対応',
        'document' => '意見書・報告書等の書面作成業務',
        'remote_consultation' => 'メール等による相談対応',
        'other' => 'その他の役務'
    ];
    
    return $descriptions[$serviceType] ?? '';
}

/**
 * 時間入力が必要な役務種別かどうかを判定
 */
function requires_time_input($serviceType) {
    $timeRequiredTypes = ['regular', 'emergency', 'extension', 'spot'];
    return in_array($serviceType, $timeRequiredTypes);
}

/**
 * 進行中の役務として管理する必要があるかどうかを判定
 */
function requires_active_tracking($serviceType) {
    $activeTrackingTypes = ['regular', 'emergency', 'extension', 'spot'];
    return in_array($serviceType, $activeTrackingTypes);
}

/**
 * 月間定期時間の対象となる役務種別かどうかを判定
 */
function counts_toward_regular_hours($serviceType) {
    return $serviceType === 'regular';
}

/**
 * 15分切り上げの対象となる役務種別かどうかを判定
 */
function requires_quarter_hour_rounding($serviceType) {
    // 定期延長のみ15分切り上げ（臨時訪問を削除）
    $roundingTypes = ['extension'];
    return in_array($serviceType, $roundingTypes);
}

/**
 * 役務種別情報を一括取得
 */
function get_service_type_info($serviceType) {
    return [
        'type' => $serviceType,
        'label' => get_service_type_label($serviceType),
        'icon' => get_service_type_icon($serviceType),
        'class' => get_service_type_class($serviceType),
        'description' => get_service_type_description($serviceType),
        'requires_time' => requires_time_input($serviceType),
        'requires_active_tracking' => requires_active_tracking($serviceType),
        'counts_toward_regular' => counts_toward_regular_hours($serviceType),
        'requires_rounding' => requires_quarter_hour_rounding($serviceType)
    ];
}

/**
 * 全ての役務種別のリストを取得
 */
function get_all_service_types() {
    return [
        'regular' => get_service_type_info('regular'),
        'emergency' => get_service_type_info('emergency'),
        'extension' => get_service_type_info('extension'),
        'spot' => get_service_type_info('spot'),
        'document' => get_service_type_info('document'),
        'remote_consultation' => get_service_type_info('remote_consultation'),
        'other' => get_service_type_info('other')
    ];
}

/**
 * 選択可能な役務種別のリストを取得（手動選択可能なもののみ）
 */
function get_selectable_service_types() {
    $allTypes = get_all_service_types();
    // 定期延長は自動作成されるため手動選択不可
    unset($allTypes['extension']);
    return $allTypes;
}

/**
 * 役務種別に応じたデフォルト値を取得
 */
function get_service_type_defaults($serviceType) {
    $defaults = [
        'regular' => [
            'service_hours' => 0,
            'requires_overtime_reason' => false,
            'status' => 'pending'
        ],
        'emergency' => [
            'service_hours' => 0,
            'requires_overtime_reason' => false,
            'status' => 'pending'
        ],
        'extension' => [
            'service_hours' => 0,
            'requires_overtime_reason' => true,
            'status' => 'pending'
        ],
        'spot' => [
            'service_hours' => 0,
            'requires_overtime_reason' => false,
            'status' => 'pending'
        ],
        'document' => [
            'service_hours' => 0, // 時間は記録しない
            'requires_overtime_reason' => false,
            'status' => 'pending'
        ],
        'remote_consultation' => [
            'service_hours' => 0, // 時間は記録しない
            'requires_overtime_reason' => false,
            'status' => 'pending'
        ],
        'other' => [
            'service_hours' => 0, // 時間は記録しない
            'requires_overtime_reason' => false,
            'status' => 'pending'
        ]
    ];
    
    return $defaults[$serviceType] ?? $defaults['regular'];
}

/**
 * 役務記録の表示用フォーマット（時間なし役務対応版）
 */
function format_service_record_time_display($record) {
    $serviceType = $record['service_type'] ?? 'regular';
    
    if (!requires_time_input($serviceType)) {
        // 書面作成・遠隔相談の場合
        return '<span class="text-muted">時間記録なし</span>';
    }
    
    if ($record['service_hours'] <= 0) {
        return '<span class="text-muted">進行中</span>';
    }
    
    return format_service_time_display($record);
}

/**
 * 役務記録の時間表示（時間なし役務対応版）
 */
function format_service_hours_display($record) {
    $serviceType = $record['service_type'] ?? 'regular';
    
    if (!requires_time_input($serviceType)) {
        return '<span class="badge bg-secondary">時間なし</span>';
    }
    
    if ($record['service_hours'] <= 0) {
        return '<span class="text-muted">--:--</span>';
    }
    
    return '<span class="badge bg-primary fs-6">' . format_total_hours($record['service_hours']) . '</span>';
}

/**
 * 月間統計の役務種別ごとの集計（新役務種別対応版）
 */
function get_monthly_service_statistics($records) {
    $stats = [
        'regular' => ['hours' => 0, 'count' => 0],
        'emergency' => ['hours' => 0, 'count' => 0],
        'extension' => ['hours' => 0, 'count' => 0],
        'spot' => ['hours' => 0, 'count' => 0],
        'document' => ['hours' => 0, 'count' => 0],
        'remote_consultation' => ['hours' => 0, 'count' => 0],
        'other' => ['hours' => 0, 'count' => 0]
    ];
    
    foreach ($records as $record) {
        $serviceType = $record['service_type'] ?? 'regular';
        if (isset($stats[$serviceType])) {
            $stats[$serviceType]['hours'] += $record['service_hours'] ?? 0;
            $stats[$serviceType]['count']++;
        }
    }
    
    return $stats;
}

/**
 * 役務種別に応じたバリデーションルールを取得
 */
function get_service_type_validation_rules($serviceType) {
    $baseRules = [
        'contract_id' => 'required',
        'service_date' => 'required',
        'description' => 'optional'
    ];
    
    if (requires_time_input($serviceType)) {
        $baseRules['start_time'] = 'required';
        $baseRules['end_time'] = 'required';
    }
    
    if ($serviceType === 'extension') {
        $baseRules['overtime_reason'] = 'required';
    }
    
    return $baseRules;
}

/**
 * 役務種別のソート順を取得
 */
function get_service_type_sort_order($serviceType) {
    $order = [
        'regular' => 1,
        'extension' => 2,
        'emergency' => 3,
        'document' => 4,
        'remote_consultation' => 5,
        'spot' => 6,
        'other' => 7
    ];
    
    return $order[$serviceType] ?? 99;
}

/**
 * 役務記録作成時のデフォルト時間を設定
 */
function set_default_service_times($data) {
    $serviceType = $data['service_type'] ?? 'regular';
    
    if (!requires_time_input($serviceType)) {
        // 書面作成・遠隔相談の場合は時間を設定しない
        $data['start_time'] = null;
        $data['end_time'] = null;
        $data['service_hours'] = 0;
    } elseif (empty($data['start_time']) || empty($data['end_time'])) {
        // 時間が必要だが未設定の場合はエラー
        throw new InvalidArgumentException('開始時刻と終了時刻が必要です。');
    }
    
    return $data;
}


function get_contract_type_label($type) {
    $labels = [
        'corporate' => '法人',
        'individual' => '個人'
    ];
    
    return $labels[$type] ?? $type;
}

function get_contract_type_icon($type) {
    $icons = [
        'corporate' => 'fas fa-building text-primary',
        'individual' => 'fas fa-user text-info'
    ];
    
    return $icons[$type] ?? 'fas fa-question-circle text-muted';
}

function get_status_class($status) {
    $classes = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'finalized' => 'dark',
        'active' => 'success',
        'inactive' => 'secondary',
        'terminated' => 'danger'
    ];
    
    return $classes[$status] ?? 'secondary';
}

function format_service_hours($decimal_hours) {
    if ($decimal_hours <= 0) {
        return '0:00';
    }
    
    $hours = floor($decimal_hours);
    $minutes = round(($decimal_hours - $hours) * 60);
    
    // 60分になった場合は1時間に繰り上げ
    if ($minutes >= 60) {
        $hours += 1;
        $minutes = 0;
    }
    
    return sprintf('%d:%02d', $hours, $minutes);
}

function format_total_hours($decimal_hours) {
    if ($decimal_hours <= 0) {
        return '0時間0分';
    }
    
    $hours = floor($decimal_hours);
    $minutes = round(($decimal_hours - $hours) * 60);
    
    // 60分になった場合は1時間に繰り上げ
    if ($minutes >= 60) {
        $hours += 1;
        $minutes = 0;
    }
    
    if ($minutes > 0) {
        return $hours . '時間' . $minutes . '分';
    } else {
        return $hours . '時間';
    }
}

function get_tax_type_label($taxType) {
    $labels = [
        'exclusive' => '外税',
        'inclusive' => '内税'
    ];
    
    return $labels[$taxType] ?? '課税';
}

function get_tax_type_class($taxType) {
    $classes = [
        'exclusive' => 'success',
        'inclusive' => 'info'
    ];
    
    return $classes[$taxType] ?? 'success';
}

function get_tax_type_icon($taxType) {
    $icons = [
        'exclusive' => 'fas fa-calculator text-success',
        'inclusive' => 'fas fa-percent text-info'
    ];
    
    return $icons[$taxType] ?? 'fas fa-calculator text-success';
}

/**
 * 15分単位で時間を切り上げ
 */
function round_up_to_quarter_hour($hours) {
    return ceil($hours * 4) / 4;
}

/**
 * 定期訪問時間の延長分を計算
 */
function calculate_overtime_split($totalHours, $regularLimit, $currentUsed) {
    $remainingRegular = max(0, $regularLimit - $currentUsed);
    
    if ($totalHours <= $remainingRegular) {
        return [
            'regular_hours' => $totalHours,
            'extension_hours' => 0
        ];
    }
    
    $regularHours = $remainingRegular;
    $excessHours = $totalHours - $remainingRegular;
    $extensionHours = round_up_to_quarter_hour($excessHours);
    
    return [
        'regular_hours' => $regularHours,
        'extension_hours' => $extensionHours
    ];
}

/**
 * 時間を分単位に変換
 */
function time_to_minutes($time) {
    $parts = explode(':', $time);
    return intval($parts[0]) * 60 + intval($parts[1]);
}

/**
 * 分を時間形式に変換
 */
function minutes_to_time($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * 分を時間数に変換
 */
function minutes_to_hours($minutes) {
    return $minutes / 60;
}

/**
 * 役務時間を定期訪問と延長に分割
 * 
 * @param string $startTime 開始時刻 (HH:MM)
 * @param string $endTime 終了時刻 (HH:MM)
 * @param float $remainingRegularHours 残り定期訪問時間
 * @return array 分割結果
 */
function split_service_time($startTime, $endTime, $remainingRegularHours) {
    // 開始・終了時刻を秒に変換
    $startSeconds = strtotime("1970-01-01 $startTime");
    $endSeconds = strtotime("1970-01-01 $endTime");
    $totalSeconds = $endSeconds - $startSeconds;
    $totalHours = $totalSeconds / 3600;
        
    // 月間定期時間内に収まる場合
    if ($remainingRegularHours >= $totalHours) {
        return [
            'regular' => [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'service_hours' => $totalHours
            ],
            'extension' => null
        ];
    }
    
    // 残り定期時間がゼロまたは負の場合、全て延長扱い
    if ($remainingRegularHours <= 0) {
        // 延長分は15分切り上げ
        $extensionHours = ceil($totalHours * 4) / 4; // 15分単位で切り上げ
              
        return [
            'regular' => null,
            'extension' => [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'service_hours' => $extensionHours,
                'original_hours' => $totalHours // 元の時間を記録
            ]
        ];
    }
    
    // 分割が必要な場合（月間合計が延長する場合）
    
    // 定期分：残り定期時間分
    $regularHours = $remainingRegularHours;
    
    // 延長分：延長分を15分切り上げ
    $excessHours = $totalHours - $remainingRegularHours;
    $extensionHours = ceil($excessHours * 4) / 4; // 15分単位で切り上げ
        
    // 時間の分割は実際の時間比率ではなく、月間契約に基づく
    // 定期分の終了時刻を計算
    $regularEndSeconds = $startSeconds + ($regularHours * 3600);
    $regularEndTime = date('H:i:s', $regularEndSeconds);
    
    // 延長分の開始時刻
    $extensionStartTime = $regularEndTime;
    
    // 延長分の終了時刻は15分切り上げを考慮
    $extensionEndSeconds = $startSeconds + (($regularHours + $extensionHours) * 3600);
    $extensionEndTime = date('H:i:s', $extensionEndSeconds);
        
    return [
        'regular' => [
            'start_time' => $startTime,
            'end_time' => $regularEndTime,
            'service_hours' => $regularHours
        ],
        'extension' => [
            'start_time' => $extensionStartTime,
            'end_time' => $extensionEndTime,
            'service_hours' => $extensionHours,
            'original_hours' => $excessHours // 元の延長時間を記録
        ]
    ];
}

/**
 * 月間定期訪問時間の使用状況をチェック
 * 
 * @param int $contractId 契約ID
 * @param string $serviceDate 役務日
 * @param int $excludeId 除外する記録ID（編集時）
 * @return array 使用状況
 */
function check_monthly_regular_usage($contractId, $serviceDate, $excludeId = null) {
    try {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $currentMonth = date('n', strtotime($serviceDate));
        $currentYear = date('Y', strtotime($serviceDate));
        
        // 契約の定期訪問時間と訪問頻度を取得
        $contractSql = "SELECT regular_visit_hours, visit_frequency FROM contracts WHERE id = :contract_id";
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            throw new Exception('契約が見つかりません');
        }
        
        // 訪問頻度に応じた月間上限時間を計算
        $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
        
        if ($visitFrequency === 'weekly') {
            // 毎週契約の場合：新しい関数を使用してcontract設定を考慮
            $regularLimit = calculate_weekly_monthly_hours_with_contract_settings(
                $contractId,
                $contract['regular_visit_hours'],
                $currentYear,
                $currentMonth,
                $db
            );
        } else {
            // 毎月・隔月契約の場合：従来通り
            $regularLimit = $contract['regular_visit_hours'];
        }
        
        // 今月の定期訪問累計時間を取得
        $sql = "SELECT COALESCE(SUM(service_hours), 0) as total_regular_hours
                FROM service_records 
                WHERE contract_id = :contract_id 
                AND (service_type = 'regular' OR service_type IS NULL)
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month";
        
        $params = [
            'contract_id' => $contractId,
            'year' => $currentYear,
            'month' => $currentMonth
        ];
        
        // 編集時は自分の記録を除外
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $currentUsed = $stmt->fetchColumn();
        
        $remainingHours = max(0, $regularLimit - $currentUsed);
        
        $result = [
            'regular_limit' => $regularLimit,
            'current_used' => $currentUsed,
            'remaining_hours' => $remainingHours,
            'usage_percentage' => ($regularLimit > 0) ? ($currentUsed / $regularLimit) * 100 : 0,
            'visit_frequency' => $visitFrequency // デバッグ用に追加
        ];
        
        return $result;
        
    } catch (Exception $e) {
        error_log('Monthly usage check failed: ' . $e->getMessage());
        return [
            'regular_limit' => 0,
            'current_used' => 0,
            'remaining_hours' => 0,
            'usage_percentage' => 0,
            'visit_frequency' => 'unknown'
        ];
    }
}

/**
 * 15分切り上げの詳細情報を取得
 * 
 * @param float $originalHours 元の時間
 * @return array 切り上げ情報
 */
function get_ceiling_info($originalHours) {
    $ceilingHours = ceil($originalHours * 4) / 4;
    $addedMinutes = ($ceilingHours - $originalHours) * 60;
    
    return [
        'original_hours' => $originalHours,
        'ceiling_hours' => $ceilingHours,
        'added_minutes' => round($addedMinutes),
        'is_rounded_up' => $addedMinutes > 0
    ];
}

/**
 * 役務記録の時間表示用フォーマット
 * 分割記録の場合は元の時間も併記
 */
function format_service_time_display($record) {
    $startTime = format_time($record['start_time']);
    $endTime = format_time($record['end_time']);
    $serviceHours = $record['service_hours'];
    
    $display = "$startTime - $endTime";
    
    // 自動分割記録の場合、元の時間情報があれば併記
    if (($record['is_auto_split'] ?? false) || 
        strpos($record['description'] ?? '', '元の時間:') !== false) {
        
        // 説明文から元の時間を抽出
        $description = $record['description'] ?? '';
        if (preg_match('/元の時間:\s*(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $description, $matches)) {
            $originalStart = $matches[1];
            $originalEnd = $matches[2];
            $display .= "<br><small class='text-muted'>元: $originalStart - $originalEnd</small>";
        }
    }
    
    return $display;
}

/**
 * 時間の重複チェック
 */
function check_time_overlap($date, $startTime, $endTime, $excludeId = null) {
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT id, start_time, end_time, service_type 
            FROM service_records 
            WHERE service_date = :date 
            AND status != 'rejected'";
    
    if ($excludeId) {
        $sql .= " AND id != :exclude_id";
    }
    
    $stmt = $db->prepare($sql);
    $params = ['date' => $date];
    if ($excludeId) {
        $params['exclude_id'] = $excludeId;
    }
    
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $startMinutes = time_to_minutes($startTime);
    $endMinutes = time_to_minutes($endTime);
    
    if ($endMinutes < $startMinutes) {
        $endMinutes += 24 * 60; // 日をまたぐ場合
    }
    
    foreach ($records as $record) {
        $recStartMinutes = time_to_minutes($record['start_time']);
        $recEndMinutes = time_to_minutes($record['end_time']);
        
        if ($recEndMinutes < $recStartMinutes) {
            $recEndMinutes += 24 * 60; // 日をまたぐ場合
        }
        
        // 重複チェック
        if (($startMinutes < $recEndMinutes) && ($endMinutes > $recStartMinutes)) {
            return [
                'overlap' => true,
                'record' => $record
            ];
        }
    }
    
    return ['overlap' => false];
}

/**
 * 自動分割記録かどうかの判定
 */
function is_auto_split_record($record) {
    return ($record['is_auto_split'] ?? false) || 
           strpos($record['description'] ?? '', '自動分割') !== false ||
           strpos($record['description'] ?? '', '延長分') !== false;
}

/**
 * 詳細な月間使用状況を取得（デバッグ用）
 * 
 * @param int $contractId 契約ID
 * @param string $serviceDate 役務日
 * @param int $excludeId 除外する記録ID（編集時）
 * @return array 詳細な使用状況
 */
function get_detailed_monthly_usage($contractId, $serviceDate, $excludeId = null) {
    try {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $currentMonth = date('n', strtotime($serviceDate));
        $currentYear = date('Y', strtotime($serviceDate));
        
        // 契約情報を取得
        $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                       FROM contracts co
                       JOIN companies c ON co.company_id = c.id
                       JOIN branches b ON co.branch_id = b.id
                       WHERE co.id = :contract_id";
        
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            throw new Exception('契約が見つかりません');
        }
        
        // 該当月の全役務記録を取得
        $recordsSql = "SELECT id, service_date, start_time, end_time, service_hours, 
                              service_type, status, description, is_auto_split, created_at
                      FROM service_records 
                      WHERE contract_id = :contract_id 
                      AND YEAR(service_date) = :year 
                      AND MONTH(service_date) = :month";
        
        $params = [
            'contract_id' => $contractId,
            'year' => $currentYear,
            'month' => $currentMonth
        ];
        
        if ($excludeId) {
            $recordsSql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $recordsSql .= " ORDER BY service_date ASC, start_time ASC";
        
        $stmt = $db->prepare($recordsSql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        
        // 集計
        $summary = [
            'contract_info' => $contract,
            'regular_limit' => (float)$contract['regular_visit_hours'],
            'records' => $records,
            'totals' => [
                'regular_hours' => 0,
                'emergency_hours' => 0,
                'extension_hours' => 0,
                'total_hours' => 0,
                'record_count' => count($records)
            ],
            'status_breakdown' => [
                'pending' => ['count' => 0, 'hours' => 0],
                'approved' => ['count' => 0, 'hours' => 0],
                'rejected' => ['count' => 0, 'hours' => 0]
            ]
        ];
        
        foreach ($records as $record) {
            $serviceType = $record['service_type'] ?? 'regular';
            $hours = (float)$record['service_hours'];
            $status = $record['status'];
            
            // 役務種別ごとの集計
            if ($serviceType === 'regular') {
                $summary['totals']['regular_hours'] += $hours;
            } elseif ($serviceType === 'emergency') {
                $summary['totals']['emergency_hours'] += $hours;
            } elseif ($serviceType === 'extension') {
                $summary['totals']['extension_hours'] += $hours;
            } elseif ($serviceType === 'spot') {
                $summary['totals']['spot_hours'] += $hours;
            }
            
            $summary['totals']['total_hours'] += $hours;
            
            // ステータス別の集計
            if (isset($summary['status_breakdown'][$status])) {
                $summary['status_breakdown'][$status]['count']++;
                $summary['status_breakdown'][$status]['hours'] += $hours;
            }
        }
        
        // 使用率と残り時間を計算
        $summary['remaining_hours'] = max(0, $summary['regular_limit'] - $summary['totals']['regular_hours']);
        $summary['usage_percentage'] = $summary['regular_limit'] > 0 ? 
            ($summary['totals']['regular_hours'] / $summary['regular_limit']) * 100 : 0;
        
        return $summary;
        
    } catch (Exception $e) {
        error_log('Detailed monthly usage check failed: ' . $e->getMessage());
        return [
            'error' => $e->getMessage(),
            'regular_limit' => 0,
            'totals' => ['regular_hours' => 0, 'emergency_hours' => 0, 'extension_hours' => 0, 'spot_hours' => 0, 'total_hours' => 0],
            'remaining_hours' => 0,
            'usage_percentage' => 0
        ];
    }
}

// ========================================
// 交通費関連のヘルパー関数
// ========================================

/**
 * 交通手段のラベルを取得
 */
function get_transport_type_label($transportType) {
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
    
    return $labels[$transportType] ?? $transportType;
}

/**
 * 交通手段のアイコンを取得
 */
function get_transport_type_icon($transportType) {
    $icons = [
        'train' => 'fas fa-train text-primary',
        'bus' => 'fas fa-bus text-success',
        'taxi' => 'fas fa-taxi text-warning',
        'gasoline' => 'fas fa-car text-info',
        'highway_toll' => 'fas fa-car text-info',
        'parking' => 'fas fa-car text-info',
        'rental_car' => 'fas fa-car text-info',
        'airplane' => 'fas fa-plane text-success',
        'other' => 'fas fa-question-circle text-muted'
    ];
    
    return $icons[$transportType] ?? 'fas fa-question-circle text-muted';
}

/**
 * 往復・片道のラベルを取得
 */
function get_trip_type_label($tripType) {
    $labels = [
        'round_trip' => '往復',
        'one_way' => '片道'
    ];
    
    return $labels[$tripType] ?? $tripType;
}

/**
 * 往復・片道のアイコンを取得
 */
function get_trip_type_icon($tripType) {
    $icons = [
        'round_trip' => 'fas fa-exchange-alt text-primary',
        'one_way' => 'fas fa-arrow-right text-secondary'
    ];
    
    return $icons[$tripType] ?? 'fas fa-question-circle text-muted';
}

/**
 * 金額をフォーマット（カンマ区切り）
 */
function format_amount($amount) {
    return '¥' . number_format((int)$amount);
}

/**
 * 交通費の合計金額を計算
 */
function calculate_total_travel_expense($expenses) {
    if (empty($expenses)) {
        return 0;
    }
    
    return array_sum(array_column($expenses, 'amount'));
}

/**
 * 交通費のステータス別統計を取得
 */
function get_travel_expense_stats($expenses) {
    $stats = [
        'total_count' => 0,
        'total_amount' => 0,
        'pending_count' => 0,
        'pending_amount' => 0,
        'approved_count' => 0,
        'approved_amount' => 0,
        'rejected_count' => 0,
        'rejected_amount' => 0
    ];
    
    if (empty($expenses)) {
        return $stats;
    }
    
    foreach ($expenses as $expense) {
        $amount = (int)$expense['amount'];
        $status = $expense['status'];
        
        $stats['total_count']++;
        $stats['total_amount'] += $amount;
        
        switch ($status) {
            case 'pending':
                $stats['pending_count']++;
                $stats['pending_amount'] += $amount;
                break;
            case 'approved':
                $stats['approved_count']++;
                $stats['approved_amount'] += $amount;
                break;
            case 'rejected':
                $stats['rejected_count']++;
                $stats['rejected_amount'] += $amount;
                break;
        }
    }
    
    return $stats;
}

/**
 * 交通手段別の統計を取得
 */
function get_transport_type_stats($expenses) {
    $stats = [];
    
    if (empty($expenses)) {
        return $stats;
    }
    
    foreach ($expenses as $expense) {
        $type = $expense['transport_type'];
        $amount = (int)$expense['amount'];
        
        if (!isset($stats[$type])) {
            $stats[$type] = [
                'count' => 0,
                'total_amount' => 0,
                'label' => get_transport_type_label($type),
                'icon' => get_transport_type_icon($type)
            ];
        }
        
        $stats[$type]['count']++;
        $stats[$type]['total_amount'] += $amount;
    }
    
    // 金額順でソート
    uasort($stats, function($a, $b) {
        return $b['total_amount'] - $a['total_amount'];
    });
    
    return $stats;
}

/**
 * 月別の交通費統計を取得
 */
function get_monthly_travel_expense_stats($expenses, $year = null) {
    $stats = [];
    
    if (empty($expenses)) {
        return $stats;
    }
    
    foreach ($expenses as $expense) {
        $serviceDate = $expense['service_date'];
        $expenseYear = date('Y', strtotime($serviceDate));
        $expenseMonth = date('n', strtotime($serviceDate));
        
        // 指定年がある場合はフィルタリング
        if ($year && $expenseYear != $year) {
            continue;
        }
        
        $key = $expenseYear . '-' . sprintf('%02d', $expenseMonth);
        
        if (!isset($stats[$key])) {
            $stats[$key] = [
                'year' => (int)$expenseYear,
                'month' => (int)$expenseMonth,
                'count' => 0,
                'total_amount' => 0,
                'approved_amount' => 0,
                'pending_amount' => 0,
                'rejected_amount' => 0
            ];
        }
        
        $amount = (int)$expense['amount'];
        $status = $expense['status'];
        
        $stats[$key]['count']++;
        $stats[$key]['total_amount'] += $amount;
        
        switch ($status) {
            case 'approved':
                $stats[$key]['approved_amount'] += $amount;
                break;
            case 'pending':
                $stats[$key]['pending_amount'] += $amount;
                break;
            case 'rejected':
                $stats[$key]['rejected_amount'] += $amount;
                break;
        }
    }
    
    // 年月順でソート
    ksort($stats);
    
    return $stats;
}

/**
 * 交通費レシートファイルのダウンロードURL取得
 */
function get_receipt_download_url($expense) {
    if (empty($expense['receipt_file_path'])) {
        return null;
    }
    
    return base_url($expense['receipt_file_path']);
}

/**
 * 交通費の承認率を計算
 */
function calculate_approval_rate($expenses) {
    if (empty($expenses)) {
        return 0;
    }
    
    $totalCount = count($expenses);
    $approvedCount = count(array_filter($expenses, function($expense) {
        return $expense['status'] === 'approved';
    }));
    
    return $totalCount > 0 ? ($approvedCount / $totalCount) * 100 : 0;
}

/**
 * 交通費の区間表示をフォーマット
 */
function format_travel_route($departure, $arrival) {
    $departure = htmlspecialchars($departure, ENT_QUOTES, 'UTF-8');
    $arrival = htmlspecialchars($arrival, ENT_QUOTES, 'UTF-8');
    
    return "{$departure} → {$arrival}";
}

/**
 * 交通費記録の説明文を生成
 */
function generate_travel_expense_description($expense) {
    $transport = get_transport_type_label($expense['transport_type']);
    $route = format_travel_route($expense['departure_point'], $expense['arrival_point']);
    $tripType = get_trip_type_label($expense['trip_type']);
    $amount = format_amount($expense['amount']);
    
    return "{$transport}（{$tripType}）: {$route} - {$amount}";
}

/**
 * 訪問種別のラベルを取得
 */
function get_visit_type_label($visitType) {
    $labels = [
        'visit' => '訪問',
        'online' => 'オンライン'
    ];
    
    return $labels[$visitType] ?? '訪問';
}

/**
 * 訪問種別のアイコンを取得
 */
function get_visit_type_icon($visitType) {
    $icons = [
        'visit' => 'fas fa-walking text-primary',
        'online' => 'fas fa-laptop text-info'
    ];
    
    return $icons[$visitType] ?? 'fas fa-walking text-primary';
}

/**
 * 訪問種別のCSSクラスを取得
 */
function get_visit_type_class($visitType) {
    $classes = [
        'visit' => 'primary',
        'online' => 'info'
    ];
    
    return $classes[$visitType] ?? 'primary';
}

/**
 * 訪問種別の説明を取得
 */
function get_visit_type_description($visitType) {
    $descriptions = [
        'visit' => '現地への物理的な訪問',
        'online' => 'リモートでの実施（オンライン会議など）'
    ];
    
    return $descriptions[$visitType] ?? '';
}

/**
 * 訪問種別の選択が必要な役務種別かどうかを判定
 */
function requires_visit_type_selection($serviceType) {
    $visitTypeRequiredTypes = ['regular', 'emergency', 'extension', 'spot'];
    return in_array($serviceType, $visitTypeRequiredTypes);
}

/**
 * 訪問種別情報を一括取得
 */
function get_visit_type_info($visitType) {
    return [
        'type' => $visitType,
        'label' => get_visit_type_label($visitType),
        'icon' => get_visit_type_icon($visitType),
        'class' => get_visit_type_class($visitType),
        'description' => get_visit_type_description($visitType)
    ];
}

/**
 * 全ての訪問種別のリストを取得
 */
function get_all_visit_types() {
    return [
        'visit' => get_visit_type_info('visit'),
        'online' => get_visit_type_info('online')
    ];
}

/**
 * 役務記録の表示用フォーマット（訪問種別対応版）
 */
function format_service_record_display_with_visit_type($record) {
    $serviceType = $record['service_type'] ?? 'regular';
    $visitType = $record['visit_type'] ?? 'visit';
    
    $display = get_service_type_label($serviceType);
    
    // 訪問種別が選択可能な役務種別の場合は追加表示
    if (requires_visit_type_selection($serviceType)) {
        $visitTypeInfo = get_visit_type_info($visitType);
        $display .= ' (' . $visitTypeInfo['label'] . ')';
    }
    
    return $display;
}

/**
 * 役務記録の統計情報を取得（訪問種別対応版）
 */
function get_service_statistics_with_visit_type($records) {
    $stats = [
        'regular' => [
            'visit' => ['hours' => 0, 'count' => 0],
            'online' => ['hours' => 0, 'count' => 0]
        ],
        'emergency' => [
            'visit' => ['hours' => 0, 'count' => 0],
            'online' => ['hours' => 0, 'count' => 0]
        ],
        'extension' => [
            'visit' => ['hours' => 0, 'count' => 0],
            'online' => ['hours' => 0, 'count' => 0]
        ],
        'spot' => [
            'visit' => ['hours' => 0, 'count' => 0],
            'online' => ['hours' => 0, 'count' => 0]
        ],
        'document' => ['hours' => 0, 'count' => 0],
        'remote_consultation' => ['hours' => 0, 'count' => 0],
        'other' => ['hours' => 0, 'count' => 0]
    ];
    
    foreach ($records as $record) {
        $serviceType = $record['service_type'] ?? 'regular';
        $visitType = $record['visit_type'] ?? 'visit';
        
        if (requires_visit_type_selection($serviceType)) {
            if (isset($stats[$serviceType][$visitType])) {
                $stats[$serviceType][$visitType]['hours'] += $record['service_hours'] ?? 0;
                $stats[$serviceType][$visitType]['count']++;
            }
        } else {
            if (isset($stats[$serviceType])) {
                $stats[$serviceType]['hours'] += $record['service_hours'] ?? 0;
                $stats[$serviceType]['count']++;
            }
        }
    }
    
    return $stats;
}

/**
 * 訪問種別に応じたデフォルト値を取得
 */
function get_visit_type_defaults($visitType) {
    $defaults = [
        'visit' => [
            'requires_travel_expense' => true,
            'location_note' => '現地訪問'
        ],
        'online' => [
            'requires_travel_expense' => false,
            'location_note' => 'オンライン実施'
        ]
    ];
    
    return $defaults[$visitType] ?? $defaults['visit'];
}

/**
 * 訪問頻度のラベルを取得
 */
function get_visit_frequency_label($frequency) {
    $labels = [
        'monthly' => '毎月',
        'bimonthly' => '隔月',
        'weekly' => '毎週',
        'spot' => 'スポット'
    ];
    
    return $labels[$frequency] ?? $frequency;
}

/**
 * 訪問頻度のアイコンを取得
 */
function get_visit_frequency_icon($frequency) {
    $icons = [
        'monthly' => 'fas fa-calendar-alt text-light',
        'bimonthly' => 'fas fa-calendar-alt text-primary',
        'weekly' => 'fas fa-calendar-week text-light',
        'spot' => 'fas fa-clock text-light'
    ];
    
    return $icons[$frequency] ?? 'fas fa-calendar-alt text-muted';
}

/**
 * 訪問頻度のCSSクラスを取得
 */
function get_visit_frequency_class($frequency) {
    $classes = [
        'monthly' => 'primary',
        'bimonthly' => 'info',
        'weekly' => 'success',
        'spot' => 'warning'
    ];
    
    return $classes[$frequency] ?? 'secondary';
}

/**
 * 訪問頻度の説明を取得
 */
function get_visit_frequency_description($frequency) {
    $descriptions = [
        'monthly' => '毎月1回の定期訪問',
        'bimonthly' => '2か月に1回の定期訪問',
        'weekly' => '指定した曜日に毎週訪問',
        'spot' => '必要に応じて実施'
    ];
    
    return $descriptions[$frequency] ?? '';
}

/**
 * 訪問頻度の詳細説明を取得
 */
function get_visit_frequency_detail($frequency) {
    $details = [
        'monthly' => '毎月1回、指定された時間数の訪問を実施します',
        'bimonthly' => '2ヶ月に1回、指定された時間数の訪問を実施します（隔月）',
        'weekly' => '指定した曜日に訪問を実施します',
        'spot' => '必要に応じて訪問を実施します'
    ];
    
    return $details[$frequency] ?? '';
}

/**
 * 訪問頻度に応じた月間時間の計算方法を取得
 */
function get_visit_frequency_calculation_info($frequency) {
    switch ($frequency) {
        case 'monthly':
            return [
                'period' => 1, // 1ヶ月
                'description' => '毎月の契約時間',
                'calculation_note' => '契約時間がそのまま月間上限となります'
            ];
        case 'bimonthly':
            return [
                'period' => 2, // 2ヶ月
                'description' => '隔月の契約時間（2ヶ月分）',
                'calculation_note' => '2ヶ月に1回の訪問で、指定時間数を実施します'
            ];
        default:
            return [
                'period' => 1,
                'description' => '不明な頻度',
                'calculation_note' => ''
            ];
    }
}

/**
 * 訪問頻度情報を一括取得
 */
function get_visit_frequency_info($frequency) {
    return [
        'frequency' => $frequency,
        'label' => get_visit_frequency_label($frequency),
        'icon' => get_visit_frequency_icon($frequency),
        'class' => get_visit_frequency_class($frequency),
        'description' => get_visit_frequency_description($frequency)
    ];
}

/**
 * 全ての訪問頻度のリストを取得
 */
function get_all_visit_frequencies() {
    return [
        'monthly' => get_visit_frequency_info('monthly'),
        'bimonthly' => get_visit_frequency_info('bimonthly'),
        'weekly' => get_visit_frequency_info('weekly'),
        'spot' => get_visit_frequency_info('spot')
    ];
}

/**
 * 週間スケジュールを取得
 */
function get_weekly_schedule($contractId, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $sql = "SELECT day_of_week FROM contract_weekly_schedules 
            WHERE contract_id = :contract_id 
            ORDER BY day_of_week";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['contract_id' => $contractId]);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * 曜日番号を日本語に変換
 */
function get_day_of_week_label($dayNumber) {
    $days = [
        1 => '月曜日',
        2 => '火曜日', 
        3 => '水曜日',
        4 => '木曜日',
        5 => '金曜日',
        6 => '土曜日',
        7 => '日曜日'
    ];
    
    return $days[$dayNumber] ?? '不明';
}

/**
 * 曜日番号を短縮形に変換
 */
function get_day_of_week_short($dayNumber) {
    $days = [
        1 => '月',
        2 => '火', 
        3 => '水',
        4 => '木',
        5 => '金',
        6 => '土',
        7 => '日'
    ];
    
    return $days[$dayNumber] ?? '?';
}

/**
 * 日付文字列から曜日名を取得
 * 
 * @param string $date 日付文字列（Y-m-d形式など）
 * @return string 曜日名（例：月曜日、火曜日）
 */
function get_day_of_week_name($date) {
    if (empty($date)) {
        return '';
    }
    
    try {
        $dateObj = new DateTime($date);
        $dayNumber = (int)$dateObj->format('N'); // 1=月曜日, 7=日曜日
        return get_day_of_week_label($dayNumber);
    } catch (Exception $e) {
        error_log('get_day_of_week_name error: ' . $e->getMessage());
        return '';
    }
}

/**
 * 週間スケジュールの表示用文字列を生成
 */
function format_weekly_schedule($dayNumbers) {
    if (empty($dayNumbers)) {
        return '未設定';
    }
    
    $dayLabels = array_map('get_day_of_week_short', $dayNumbers);
    return implode('・', $dayLabels);
}

/**
 * 指定された年月の特定の曜日が何回あるかを計算
 */
function count_weekdays_in_month($year, $month, $dayOfWeek) {
    // 月の最初の日と最後の日を取得
    $firstDay = new DateTime("{$year}-{$month}-01");
    $lastDay = new DateTime($firstDay->format('Y-m-t'));
    
    $count = 0;
    $currentDate = clone $firstDay;
    
    // 月の各日をチェック
    while ($currentDate <= $lastDay) {
        // 曜日をチェック（PHPの曜日: 1=月曜日, 7=日曜日）
        if ($currentDate->format('N') == $dayOfWeek) {
            $count++;
        }
        $currentDate->add(new DateInterval('P1D'));
    }
    
    return $count;
}

/**
 * 週間契約の月間時間を計算
 */
function calculate_weekly_monthly_hours($contractId, $weeklyHours, $year, $month, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    // 週間スケジュールを取得
    $weeklyDays = get_weekly_schedule($contractId, $db);
    
    if (empty($weeklyDays)) {
        return 0;
    }
    
    $totalHours = 0;
    
    // 各曜日について、その月に何回あるかを計算
    foreach ($weeklyDays as $dayOfWeek) {
        $weekCount = count_weekdays_in_month($year, $month, $dayOfWeek);
        $totalHours += $weekCount * $weeklyHours;
    }
    
    return $totalHours;
}

/**
 * 訪問頻度に応じた月間使用量の計算
 * 隔月の場合は、2ヶ月に1回の訪問なので月間計算を調整
 * 
 * @param string $frequency 訪問頻度
 * @param float $contractHours 契約時間
 * @param int $year 年
 * @param int $month 月
 * @return array 計算結果
 */
function calculate_monthly_usage_by_frequency($frequency, $contractHours, $year, $month, $contract, $db = null) {
    // 契約が有効でない場合
    if (!is_contract_active($contract, $year, $month)) {
        return [
            'monthly_limit' => 0,
            'is_visit_month' => false,
            'is_contract_active' => false,
            'calculation_note' => '契約期間外のため、定期訪問は実施されません'
        ];
    }

    switch ($frequency) {
        case 'monthly':
            return [
                'monthly_limit' => $contractHours, // $regularVisitHours → $contractHours に修正
                'is_visit_month' => true,
                'is_contract_active' => true,
                'calculation_note' => '毎月' . $contractHours . '時間が上限です'
            ];
            
        case 'bimonthly':
            $isVisitMonth = is_visit_month($contract, $year, $month);
            
            return [
                'monthly_limit' => $isVisitMonth ? $contractHours : 0, // $regularVisitHours → $contractHours に修正
                'is_visit_month' => $isVisitMonth,
                'is_contract_active' => true,
                'calculation_note' => $isVisitMonth 
                    ? '隔月訪問月：' . $contractHours . '時間が上限です'
                    : '隔月非訪問月：定期訪問は実施されません'
            ];
            
        case 'weekly':
            if (!$db) {
                require_once __DIR__ . '/Database.php';
                $db = Database::getInstance()->getConnection();
            }
            
            // 契約IDが存在することを確認
            if (!isset($contract['id'])) {
                error_log("calculate_monthly_usage_by_frequency: contract ID is missing: " . print_r($contract, true));
                return [
                    'monthly_limit' => 0,
                    'is_visit_month' => false,
                    'is_contract_active' => false,
                    'calculation_note' => '契約IDが不明のため計算できません'
                ];
            }

            $monthlyHours = calculate_weekly_monthly_hours(
                $contract['id'], 
                $contractHours, // $regularVisitHours → $contractHours に修正
                $year, 
                $month, 
                $db
            );
            
            return [
                'monthly_limit' => $monthlyHours,
                'is_visit_month' => true, // 週間契約は毎月訪問
                'is_contract_active' => true,
                'calculation_note' => '週間契約：' . $monthlyHours . '時間が今月の上限です'
            ];
            
        default:
            return [
                'monthly_limit' => $contractHours, // $regularVisitHours → $contractHours に修正
                'is_visit_month' => true,
                'is_contract_active' => true,
                'calculation_note' => '不明な頻度です'
            ];
    }
}

/**
 * 週間スケジュールを保存
 */
function save_weekly_schedule($contractId, $dayNumbers, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    try {
        $db->beginTransaction();
        
        // 既存のスケジュールを削除
        $deleteSql = "DELETE FROM contract_weekly_schedules WHERE contract_id = :contract_id";
        $stmt = $db->prepare($deleteSql);
        $stmt->execute(['contract_id' => $contractId]);
        
        // 新しいスケジュールを挿入
        if (!empty($dayNumbers)) {
            $insertSql = "INSERT INTO contract_weekly_schedules (contract_id, day_of_week) 
                          VALUES (:contract_id, :day_of_week)";
            $stmt = $db->prepare($insertSql);
            
            foreach ($dayNumbers as $dayOfWeek) {
                if ($dayOfWeek >= 1 && $dayOfWeek <= 7) {
                    $stmt->execute([
                        'contract_id' => $contractId,
                        'day_of_week' => $dayOfWeek
                    ]);
                }
            }
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * 週間スケジュールのバリデーション
 */
function validate_weekly_schedule($dayNumbers) {
    if (empty($dayNumbers)) {
        return ['valid' => false, 'error' => '週間スケジュールで曜日が選択されていません。'];
    }
    
    foreach ($dayNumbers as $day) {
        if (!is_numeric($day) || $day < 1 || $day > 7) {
            return ['valid' => false, 'error' => '無効な曜日が指定されています。'];
        }
    }
    
    // 重複チェック
    if (count($dayNumbers) !== count(array_unique($dayNumbers))) {
        return ['valid' => false, 'error' => '重複した曜日が選択されています。'];
    }
    
    return ['valid' => true];
}

/**
 * 指定した年月の週間契約の詳細情報を取得
 */
function get_weekly_contract_details($contractId, $year, $month, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $weeklyDays = get_weekly_schedule($contractId, $db);
    
    if (empty($weeklyDays)) {
        return [
            'days' => [],
            'total_visits' => 0,
            'weekly_schedule' => '未設定',
            'visit_dates' => []
        ];
    }
    
    $visitDates = [];
    $totalVisits = 0;
    
    // 各曜日の訪問予定日を取得
    foreach ($weeklyDays as $dayOfWeek) {
        $weekCount = count_weekdays_in_month($year, $month, $dayOfWeek);
        $totalVisits += $weekCount;
        
        // その月の該当曜日の日付を全て取得
        $firstDay = new DateTime("{$year}-{$month}-01");
        $lastDay = new DateTime($firstDay->format('Y-m-t'));
        $currentDate = clone $firstDay;
        
        $daysInMonth = [];
        while ($currentDate <= $lastDay) {
            if ($currentDate->format('N') == $dayOfWeek) {
                $daysInMonth[] = $currentDate->format('j'); // 日のみ
            }
            $currentDate->add(new DateInterval('P1D'));
        }
        
        $visitDates[] = [
            'day_of_week' => $dayOfWeek,
            'day_label' => get_day_of_week_short($dayOfWeek),
            'dates' => $daysInMonth,
            'count' => $weekCount
        ];
    }
    
    return [
        'days' => $weeklyDays,
        'total_visits' => $totalVisits,
        'weekly_schedule' => format_weekly_schedule($weeklyDays),
        'visit_dates' => $visitDates
    ];
}

/**
 * 契約の訪問月かどうかを判定(隔月契約用)
 * bimonthly_typeカラムを使用して判定
 * 
 * @param array $contract 契約情報
 * @param int $year 判定する年
 * @param int $month 判定する月
 * @return bool 訪問月の場合true
 */
function is_visit_month($contract, $year, $month) {
    // 契約配列の検証
    if (!is_array($contract)) {
        error_log("is_visit_month: contract is not an array: " . print_r($contract, true));
        return true; // 安全のため訪問月として扱う
    }
    
    // 必要なキーの存在確認
    if (!isset($contract['visit_frequency'])) {
        error_log("is_visit_month: visit_frequency key is missing in contract: " . print_r($contract, true));
        return true; // デフォルトは毎月契約として扱う
    }

    // 毎月契約の場合、契約開始日以降であれば常に訪問月
    if ($contract['visit_frequency'] === 'monthly') {
        return is_contract_active($contract, $year, $month);
    }
    
    // 隔月契約の場合
    if ($contract['visit_frequency'] === 'bimonthly') {
        // 契約が有効でない場合は非訪問月
        if (!is_contract_active($contract, $year, $month)) {
            $contractId = $contract['id'] ?? 'unknown';
            error_log("Contract {$contractId}: Contract is not active for {$year}-{$month}");
            return false;
        }
        
        // bimonthly_typeが設定されていない場合
        if (empty($contract['bimonthly_type'])) {
            $contractId = $contract['id'] ?? 'unknown';
            error_log("Contract {$contractId}: bimonthly_type is missing for bimonthly contract");
            
            // フォールバック: start_dateから判定（後方互換性のため）
            if (!empty($contract['start_date'])) {
                try {
                    $startDate = new DateTime($contract['start_date']);
                    $targetDate = new DateTime("{$year}-{$month}-01");
                    
                    if ($targetDate < $startDate) {
                        return false;
                    }
                    
                    $startYear = (int)$startDate->format('Y');
                    $startMonth = (int)$startDate->format('n');
                    $monthsFromStart = (($year - $startYear) * 12) + ($month - $startMonth);
                    
                    return ($monthsFromStart % 2) === 0;
                } catch (Exception $e) {
                    error_log("Error calculating visit month using start_date for contract {$contractId}: " . $e->getMessage());
                    return true;
                }
            }
            
            return true; // 安全のため訪問月として扱う
        }
        
        // bimonthly_typeに基づいて判定
        $bimonthlyType = strtolower(trim($contract['bimonthly_type']));
        
        if ($bimonthlyType === 'even') {
            // 偶数月が訪問月
            $isVisitMonth = ($month % 2) === 0;
        } elseif ($bimonthlyType === 'odd') {
            // 奇数月が訪問月
            $isVisitMonth = ($month % 2) === 1;
        } else {
            // 不明なbimonthly_typeの場合
            $contractId = $contract['id'] ?? 'unknown';
            error_log("Contract {$contractId}: Invalid bimonthly_type '{$bimonthlyType}'");
            return true; // 安全のため訪問月として扱う
        }
        
        // デバッグログ
        $contractId = $contract['id'] ?? 'unknown';
        
        return $isVisitMonth;
    }
    
    // 毎週契約やその他の場合は訪問月として扱う
    return true;
}

/**
 * 契約が指定された年月に有効かどうかを判定
 * 
 * @param array $contract 契約情報
 * @param int $year 年
 * @param int $month 月
 * @return bool 有効な場合true
 */
function is_contract_active($contract, $year, $month) {
    // 契約配列の検証
    if (!is_array($contract)) {
        error_log("is_contract_active: contract is not an array: " . print_r($contract, true));
        return false;
    }

    try {
        // 契約開始日のチェック
        if (!empty($contract['start_date'])) {
            $startDate = new DateTime($contract['start_date']);
            $targetDate = new DateTime("{$year}-{$month}-01");
            
            // 契約開始日がまだ来ていない場合
            if ($targetDate < $startDate) {
                return false;
            }
        }
        
        // 契約終了日のチェック
        if (!empty($contract['end_date'])) {
            $endDate = new DateTime($contract['end_date']);
            $targetMonthEnd = new DateTime("{$year}-{$month}-" . date('t', strtotime("{$year}-{$month}-01")));
            
            // 契約終了日を過ぎている場合
            if ($targetMonthEnd > $endDate) {
                return false;
            }
        }
        
        // 契約ステータスのチェック
        if (isset($contract['status']) && !in_array($contract['status'], ['active', 'approved'])) {
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error checking contract active status for contract {$contract['id']}: " . $e->getMessage());
        return false; // エラー時は安全のため非有効として扱う
    }
}

/**
 * 訪問頻度に応じた時間管理メッセージを生成
 */
function get_frequency_time_message($frequency, $contractHours, $usedHours, $year, $month, $contract) {
    $info = get_visit_frequency_info($frequency);
    
    // 契約が有効でない場合
    if (!is_contract_active($contract, $year, $month)) {
        return [
            'type' => 'info',
            'message' => '契約期間外です。',
            'detail' => 'この月は契約が有効ではありません。'
        ];
    }
    
    if ($frequency === 'bimonthly') {
        $isVisitMonth = is_visit_month($contract, $year, $month);
        
        if (!$isVisitMonth) {
            return [
                'type' => 'info',
                'message' => '今月は隔月契約の非訪問月です。定期訪問は実施されません。',
                'detail' => '臨時訪問や書面作成は実施可能です。'
            ];
        }
    }
    
    $remainingHours = max(0, $contractHours - $usedHours);
    $usagePercentage = $contractHours > 0 ? ($usedHours / $contractHours) * 100 : 0;
    
    if ($usagePercentage >= 100) {
        return [
            'type' => 'warning',
            'message' => '契約時間を延長しています。',
            'detail' => '追加の訪問は延長扱いとなります。'
        ];
    } elseif ($usagePercentage >= 80) {
        return [
            'type' => 'warning',
            'message' => '契約時間の80%を使用しています。',
            'detail' => '残り' . number_format($remainingHours, 1) . '時間です。'
        ];
    } else {
        return [
            'type' => 'success',
            'message' => '契約時間内です。',
            'detail' => '残り' . number_format($remainingHours, 1) . '時間使用可能です。'
        ];
    }
}

/**
 * 訪問頻度のバリデーション
 */
function validate_visit_frequency($frequency) {
    $validFrequencies = ['monthly', 'bimonthly', 'weekly','spot'];
    return in_array($frequency, $validFrequencies);
}

/**
 * 週間契約の次回訪問日を取得
 */
function get_next_weekly_visit_dates($contractId, $db = null, $limit = 5) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $weeklyDays = get_weekly_schedule($contractId, $db);
    
    if (empty($weeklyDays)) {
        return [];
    }
    
    $nextVisits = [];
    $today = new DateTime();
    $searchDate = clone $today;
    
    // 今日から最大30日先まで検索
    for ($i = 0; $i < 30 && count($nextVisits) < $limit; $i++) {
        $currentDayOfWeek = (int)$searchDate->format('N');
        
        if (in_array($currentDayOfWeek, $weeklyDays)) {
            $nextVisits[] = [
                'date' => $searchDate->format('Y-m-d'),
                'formatted_date' => $searchDate->format('m月d日'),
                'day_of_week' => $currentDayOfWeek,
                'day_label' => get_day_of_week_short($currentDayOfWeek),
                'days_from_today' => $i
            ];
        }
        
        $searchDate->add(new DateInterval('P1D'));
    }
    
    return $nextVisits;
}

/**
 * 週間契約の年間予定時間を計算
 */
function calculate_yearly_weekly_hours($contractId, $weeklyHours, $year, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $totalHours = 0;
    
    // 各月の時間を計算
    for ($month = 1; $month <= 12; $month++) {
        $monthlyHours = calculate_weekly_monthly_hours($contractId, $weeklyHours, $year, $month, $db);
        $totalHours += $monthlyHours;
    }
    
    return $totalHours;
}

/**
 * 週間スケジュールの詳細表示用データを生成
 */
function format_weekly_schedule_detailed($contractId, $year, $month, $db = null) {
    $details = get_weekly_contract_details($contractId, $year, $month, $db);
    
    if (empty($details['visit_dates'])) {
        return '週間スケジュール未設定';
    }
    
    $scheduleText = '';
    foreach ($details['visit_dates'] as $dayInfo) {
        $scheduleText .= $dayInfo['day_label'] . '：' . implode(',', $dayInfo['dates']) . '日 (' . $dayInfo['count'] . '回) ';
    }
    
    return trim($scheduleText);
}

/**
 * 週間契約の統計情報を取得
 */
function get_weekly_contracts_statistics($db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $sql = "SELECT 
                COUNT(DISTINCT c.id) as total_weekly_contracts,
                AVG(c.regular_visit_hours) as avg_weekly_hours,
                COUNT(DISTINCT cws.day_of_week) as total_selected_days,
                GROUP_CONCAT(DISTINCT cws.day_of_week ORDER BY cws.day_of_week) as all_selected_days
            FROM contracts c
            LEFT JOIN contract_weekly_schedules cws ON c.id = cws.contract_id
            WHERE c.visit_frequency = 'weekly' 
            AND c.contract_status = 'active'";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $stats = $stmt->fetch();
    
    // 曜日別の統計
    $dayStatsSql = "SELECT 
                        cws.day_of_week,
                        COUNT(*) as contract_count,
                        AVG(c.regular_visit_hours) as avg_hours
                    FROM contract_weekly_schedules cws
                    JOIN contracts c ON cws.contract_id = c.id
                    WHERE c.visit_frequency = 'weekly' 
                    AND c.contract_status = 'active'
                    GROUP BY cws.day_of_week
                    ORDER BY cws.day_of_week";
    
    $stmt = $db->prepare($dayStatsSql);
    $stmt->execute();
    
    $dayStats = $stmt->fetchAll();
    
    // 曜日統計に日本語ラベルを追加
    foreach ($dayStats as &$dayStat) {
        $dayStat['day_label'] = get_day_of_week_label($dayStat['day_of_week']);
        $dayStat['day_short'] = get_day_of_week_short($dayStat['day_of_week']);
    }
    unset($dayStat);
    
    return [
        'overview' => $stats,
        'by_day' => $dayStats
    ];
}

/**
 * 週間契約の月別時間推移を取得
 */
function get_weekly_monthly_hours_trend($contractId, $year, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    // 契約情報を取得
    $contractSql = "SELECT regular_visit_hours FROM contracts WHERE id = :contract_id";
    $stmt = $db->prepare($contractSql);
    $stmt->execute(['contract_id' => $contractId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        return [];
    }
    
    $weeklyHours = $contract['regular_visit_hours'];
    $monthlyData = [];
    
    // 各月のデータを計算
    for ($month = 1; $month <= 12; $month++) {
        $monthlyHours = calculate_weekly_monthly_hours($contractId, $weeklyHours, $year, $month, $db);
        $weeklyDetails = get_weekly_contract_details($contractId, $year, $month, $db);
        
        $monthlyData[] = [
            'month' => $month,
            'month_name' => $month . '月',
            'monthly_hours' => $monthlyHours,
            'total_visits' => $weeklyDetails['total_visits'],
            'weekly_schedule' => $weeklyDetails['weekly_schedule'],
            'visit_dates' => $weeklyDetails['visit_dates']
        ];
    }
    
    return $monthlyData;
}

/**
 * 曜日の組み合わせパターンを分析
 */
function analyze_weekly_patterns($db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $sql = "SELECT 
                c.id,
                c.regular_visit_hours,
                GROUP_CONCAT(cws.day_of_week ORDER BY cws.day_of_week) as day_pattern
            FROM contracts c
            JOIN contract_weekly_schedules cws ON c.id = cws.contract_id
            WHERE c.visit_frequency = 'weekly' 
            AND c.contract_status = 'active'
            GROUP BY c.id
            ORDER BY day_pattern";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $contracts = $stmt->fetchAll();
    $patterns = [];
    
    foreach ($contracts as $contract) {
        $pattern = $contract['day_pattern'];
        $days = explode(',', $pattern);
        $patternKey = format_weekly_schedule($days);
        
        if (!isset($patterns[$patternKey])) {
            $patterns[$patternKey] = [
                'pattern' => $patternKey,
                'day_numbers' => $days,
                'contract_count' => 0,
                'total_hours' => 0,
                'avg_hours' => 0
            ];
        }
        
        $patterns[$patternKey]['contract_count']++;
        $patterns[$patternKey]['total_hours'] += $contract['regular_visit_hours'];
        $patterns[$patternKey]['avg_hours'] = $patterns[$patternKey]['total_hours'] / $patterns[$patternKey]['contract_count'];
    }
    
    // 契約数順でソート
    uasort($patterns, function($a, $b) {
        return $b['contract_count'] - $a['contract_count'];
    });
    
    return $patterns;
}

/**
 * 週間契約の時間効率を分析
 */
function analyze_weekly_efficiency($contractId, $year, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $monthlyData = get_weekly_monthly_hours_trend($contractId, $year, $db);
    
    if (empty($monthlyData)) {
        return null;
    }
    
    $totalHours = array_sum(array_column($monthlyData, 'monthly_hours'));
    $totalVisits = array_sum(array_column($monthlyData, 'total_visits'));
    $avgHoursPerMonth = $totalHours / 12;
    $avgVisitsPerMonth = $totalVisits / 12;
    
    // 月別のばらつきを計算
    $hours = array_column($monthlyData, 'monthly_hours');
    $variance = array_sum(array_map(function($h) use ($avgHoursPerMonth) {
        return pow($h - $avgHoursPerMonth, 2);
    }, $hours)) / 12;
    $stdDev = sqrt($variance);
    
    return [
        'yearly_total_hours' => $totalHours,
        'yearly_total_visits' => $totalVisits,
        'avg_monthly_hours' => $avgHoursPerMonth,
        'avg_monthly_visits' => $avgVisitsPerMonth,
        'monthly_variance' => $variance,
        'monthly_std_dev' => $stdDev,
        'efficiency_ratio' => $totalHours > 0 ? $totalVisits / $totalHours : 0,
        'monthly_breakdown' => $monthlyData
    ];
}
/**
 * 訪問頻度のバリデーション（週間スケジュール込み）
 */
function validate_visit_frequency_with_schedule($frequency, $weeklyDays = []) {
    // 基本的な頻度バリデーション
    if (!validate_visit_frequency($frequency)) {
        return ['valid' => false, 'error' => '無効な訪問頻度です。'];
    }
    
    // 週間契約の場合は曜日が必須
    if ($frequency === 'weekly') {
        return validate_weekly_schedule($weeklyDays);
    }
    
    return ['valid' => true];
}

/**
 * JavaScript用の曜日データを生成
 */
function get_js_day_of_week_data() {
    return json_encode([
        1 => ['label' => '月曜日', 'short' => '月'],
        2 => ['label' => '火曜日', 'short' => '火'],
        3 => ['label' => '水曜日', 'short' => '水'],
        4 => ['label' => '木曜日', 'short' => '木'],
        5 => ['label' => '金曜日', 'short' => '金'],
        6 => ['label' => '土曜日', 'short' => '土'],
        7 => ['label' => '日曜日', 'short' => '日']
    ]);
}

/**
 * 週間契約のカレンダー表示用データを生成
 */
function generate_weekly_calendar_data($contractId, $year, $month, $db = null) {
    $details = get_weekly_contract_details($contractId, $year, $month, $db);
    
    if (empty($details['visit_dates'])) {
        return [];
    }
    
    $calendarData = [];
    
    foreach ($details['visit_dates'] as $dayInfo) {
        foreach ($dayInfo['dates'] as $date) {
            $calendarData[] = [
                'date' => $date,
                'day_of_week' => $dayInfo['day_of_week'],
                'day_label' => $dayInfo['day_label'],
                'is_visit_day' => true
            ];
        }
    }
    
    // 日付順でソート
    usort($calendarData, function($a, $b) {
        return $a['date'] - $b['date'];
    });
    
    return $calendarData;
}

/**
 * 週間契約の概要メッセージを生成
 */
function generate_weekly_summary_message($contractId, $weeklyHours, $year, $month, $db = null) {
    $details = get_weekly_contract_details($contractId, $year, $month, $db);
    
    if (empty($details['visit_dates'])) {
        return '週間スケジュールが設定されていません。';
    }
    
    $totalHours = $details['total_visits'] * $weeklyHours;
    $scheduleText = $details['weekly_schedule'];
    
    return "{$scheduleText}に訪問予定（{$year}年{$month}月：{$details['total_visits']}回、合計{$totalHours}時間）";
}

/**
 * 複数契約の週間スケジュール重複チェック
 */
function check_weekly_schedule_conflicts($doctorId, $weeklyDays, $excludeContractId = null, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    if (empty($weeklyDays)) {
        return ['has_conflicts' => false, 'conflicts' => []];
    }
    
    $sql = "SELECT 
                c.id,
                c.company_id,
                comp.name as company_name,
                b.name as branch_name,
                cws.day_of_week
            FROM contracts c
            JOIN companies comp ON c.company_id = comp.id
            JOIN branches b ON c.branch_id = b.id
            JOIN contract_weekly_schedules cws ON c.id = cws.contract_id
            WHERE c.doctor_id = :doctor_id
            AND c.visit_frequency = 'weekly'
            AND c.contract_status = 'active'";
    
    $params = ['doctor_id' => $doctorId];
    
    if ($excludeContractId) {
        $sql .= " AND c.id != :exclude_contract_id";
        $params['exclude_contract_id'] = $excludeContractId;
    }
    
    $sql .= " ORDER BY comp.name, b.name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $existingSchedules = $stmt->fetchAll();
    $conflicts = [];
    
    // 重複する曜日をチェック
    foreach ($weeklyDays as $newDay) {
        foreach ($existingSchedules as $existing) {
            if ($existing['day_of_week'] == $newDay) {
                $conflictKey = $existing['company_name'] . ' - ' . $existing['branch_name'];
                
                if (!isset($conflicts[$conflictKey])) {
                    $conflicts[$conflictKey] = [
                        'contract_id' => $existing['id'],
                        'company_name' => $existing['company_name'],
                        'branch_name' => $existing['branch_name'],
                        'conflicting_days' => []
                    ];
                }
                
                $conflicts[$conflictKey]['conflicting_days'][] = [
                    'day_number' => $newDay,
                    'day_label' => get_day_of_week_label($newDay)
                ];
            }
        }
    }
    
    return [
        'has_conflicts' => !empty($conflicts),
        'conflicts' => array_values($conflicts)
    ];
}

/**
 * 訪問頻度に応じた契約期間の推奨値を取得
 */
function get_recommended_contract_period($frequency) {
    switch ($frequency) {
        case 'monthly':
            return [
                'min_months' => 6,
                'recommended_months' => 12,
                'note' => '毎月訪問のため、年間契約が一般的です'
            ];
        case 'bimonthly':
            return [
                'min_months' => 12,
                'recommended_months' => 24,
                'note' => '隔月訪問のため、長期契約が推奨されます'
            ];
        default:
            return [
                'min_months' => 6,
                'recommended_months' => 12,
                'note' => ''
            ];
    }
}

/**
 * 日本の祝日を取得する関数
 * 
 * @param int $year 年
 * @return array 祝日の配列（Y-m-d形式）
 */
function get_japanese_holidays($year) {
    $holidays = [];
    
    // 固定祝日
    $fixed_holidays = [
        '01-01' => '元日',
        '02-11' => '建国記念の日',
        '04-29' => '昭和の日',
        '05-03' => '憲法記念日',
        '05-04' => 'みどりの日',
        '05-05' => 'こどもの日',
        '08-11' => '山の日',
        '11-03' => '文化の日',
        '11-23' => '勤労感謝の日',
        '12-23' => '天皇誕生日' // 2019年以降
    ];
    
    foreach ($fixed_holidays as $date => $name) {
        // 天皇誕生日は2019年以降のみ
        if ($date === '12-23' && $year < 2019) {
            continue;
        }
        // 2018年以前の天皇誕生日（12月23日）
        if ($year <= 2018 && $date === '12-23') {
            $holidays["{$year}-12-23"] = $name;
            continue;
        }
        
        $holidays["{$year}-{$date}"] = $name;
    }
    
    // 移動祝日の計算
    
    // 成人の日（1月第2月曜日）
    $january_second_monday = get_nth_weekday($year, 1, 1, 2);
    if ($january_second_monday) {
        $holidays[$january_second_monday] = '成人の日';
    }
    
    // 海の日（7月第3月曜日）
    // 2020年のみ7月23日、2021年のみ7月22日（オリンピック特例）
    if ($year === 2020) {
        $holidays["{$year}-07-23"] = '海の日';
    } elseif ($year === 2021) {
        $holidays["{$year}-07-22"] = '海の日';
    } else {
        $july_third_monday = get_nth_weekday($year, 7, 1, 3);
        if ($july_third_monday) {
            $holidays[$july_third_monday] = '海の日';
        }
    }
    
    // 敬老の日（9月第3月曜日）
    $september_third_monday = get_nth_weekday($year, 9, 1, 3);
    if ($september_third_monday) {
        $holidays[$september_third_monday] = '敬老の日';
    }
    
    // 体育の日／スポーツの日（10月第2月曜日）
    // 2020年のみ7月24日、2021年のみ7月23日（オリンピック特例）
    if ($year === 2020) {
        $holidays["{$year}-07-24"] = 'スポーツの日';
    } elseif ($year === 2021) {
        $holidays["{$year}-07-23"] = 'スポーツの日';
    } else {
        $october_second_monday = get_nth_weekday($year, 10, 1, 2);
        if ($october_second_monday) {
            $holidayName = $year >= 2020 ? 'スポーツの日' : '体育の日';
            $holidays[$october_second_monday] = $holidayName;
        }
    }
    
    // 春分の日
    $vernal_equinox = calculate_vernal_equinox($year);
    if ($vernal_equinox) {
        $holidays[$vernal_equinox] = '春分の日';
    }
    
    // 秋分の日
    $autumnal_equinox = calculate_autumnal_equinox($year);
    if ($autumnal_equinox) {
        $holidays[$autumnal_equinox] = '秋分の日';
    }
    
    // 振替休日の計算
    $holidays = add_substitute_holidays($holidays, $year);
    
    return $holidays;
}

/**
 * 指定した年月の第N曜日の日付を取得
 * 
 * @param int $year 年
 * @param int $month 月
 * @param int $dayOfWeek 曜日（1=月曜日, 7=日曜日）
 * @param int $nth 第N（1=第1, 2=第2...）
 * @return string|null Y-m-d形式の日付
 */
function get_nth_weekday($year, $month, $dayOfWeek, $nth) {
    try {
        $firstDay = new DateTime("{$year}-{$month}-01");
        $lastDay = new DateTime($firstDay->format('Y-m-t'));
        
        $count = 0;
        $currentDate = clone $firstDay;
        
        while ($currentDate <= $lastDay) {
            if ($currentDate->format('N') == $dayOfWeek) {
                $count++;
                if ($count === $nth) {
                    return $currentDate->format('Y-m-d');
                }
            }
            $currentDate->add(new DateInterval('P1D'));
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error calculating nth weekday: " . $e->getMessage());
        return null;
    }
}

/**
 * 春分の日を計算
 * 
 * @param int $year 年
 * @return string|null Y-m-d形式の日付
 */
function calculate_vernal_equinox($year) {
    if ($year < 1851 || $year > 2150) {
        return null; // 計算式の有効範囲外
    }
    
    // 春分の日の計算式（1851年-2150年）
    if ($year <= 1979) {
        $day = floor(21.124 + 0.2422 * ($year - 1851) - floor(($year - 1851) / 4));
    } else {
        $day = floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }
    
    return sprintf('%04d-03-%02d', $year, $day);
}

/**
 * 秋分の日を計算
 * 
 * @param int $year 年
 * @return string|null Y-m-d形式の日付
 */
function calculate_autumnal_equinox($year) {
    if ($year < 1851 || $year > 2150) {
        return null; // 計算式の有効範囲外
    }
    
    // 秋分の日の計算式（1851年-2150年）
    if ($year <= 1979) {
        $day = floor(23.73 + 0.2422 * ($year - 1851) - floor(($year - 1851) / 4));
    } else {
        $day = floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }
    
    return sprintf('%04d-09-%02d', $year, $day);
}

/**
 * 振替休日を追加
 * 
 * @param array $holidays 既存の祝日配列
 * @param int $year 年
 * @return array 振替休日を含む祝日配列
 */
function add_substitute_holidays($holidays, $year) {
    foreach ($holidays as $date => $name) {
        try {
            $holidayDate = new DateTime($date);
            
            // 日曜日の祝日は翌日が振替休日
            if ($holidayDate->format('w') == 0) { // 0 = 日曜日
                $substituteDate = clone $holidayDate;
                $substituteDate->add(new DateInterval('P1D'));
                
                // 振替休日が既存の祝日と重複しない場合のみ追加
                $substituteDateStr = $substituteDate->format('Y-m-d');
                if (!isset($holidays[$substituteDateStr])) {
                    $holidays[$substituteDateStr] = '振替休日';
                }
            }
            
            // 国民の休日（祝日に挟まれた平日）の処理
            // この処理は複雑になるため、基本的な振替休日のみ実装
            
        } catch (Exception $e) {
            error_log("Error processing substitute holiday for {$date}: " . $e->getMessage());
            continue;
        }
    }
    
    return $holidays;
}

/**
 * 祝日を除外して指定した年月の特定の曜日が何回あるかを計算（改良版）
 * 
 * @param int $year 年
 * @param int $month 月
 * @param int $dayOfWeek 曜日（1=月曜日, 7=日曜日）
 * @return int 祝日を除いた回数
 */
function count_weekdays_in_month_excluding_holidays($year, $month, $dayOfWeek) {
    // 月の最初の日と最後の日を取得
    $firstDay = new DateTime("{$year}-{$month}-01");
    $lastDay = new DateTime($firstDay->format('Y-m-t'));
    
    // その年の祝日一覧を取得
    $holidays = get_japanese_holidays($year);
    
    $count = 0;
    $currentDate = clone $firstDay;
    
    // 月の各日をチェック
    while ($currentDate <= $lastDay) {
        // 指定された曜日かチェック（PHPの曜日: 1=月曜日, 7=日曜日）
        if ($currentDate->format('N') == $dayOfWeek) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // 祝日でない場合のみカウント
            if (!isset($holidays[$dateStr])) {
                $count++;
            }
        }
        $currentDate->add(new DateInterval('P1D'));
    }
    
    return $count;
}

/**
 * 週間契約の月間時間を計算（祝日除外版）
 * 
 * @param int $contractId 契約ID
 * @param float $weeklyHours 週間時間数
 * @param int $year 年
 * @param int $month 月
 * @param PDO $db データベース接続
 * @return float 月間時間数
 */
function calculate_weekly_monthly_hours_excluding_holidays($contractId, $weeklyHours, $year, $month, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    // 週間スケジュールを取得
    $weeklyDays = get_weekly_schedule($contractId, $db);
    
    if (empty($weeklyDays)) {
        return 0;
    }
    
    $totalHours = 0;
    
    // 各曜日について、その月に祝日を除いて何回あるかを計算
    foreach ($weeklyDays as $dayOfWeek) {
        $weekCount = count_weekdays_in_month_excluding_holidays($year, $month, $dayOfWeek);
        $totalHours += $weekCount * $weeklyHours;
    }
    
    return $totalHours;
}

/**
 * 週間契約の詳細情報を取得（祝日除外版）
 * 
 * @param int $contractId 契約ID
 * @param int $year 年
 * @param int $month 月
 * @param PDO $db データベース接続
 * @return array 詳細情報
 */
function get_weekly_contract_details_excluding_holidays($contractId, $year, $month, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $weeklyDays = get_weekly_schedule($contractId, $db);
    
    if (empty($weeklyDays)) {
        return [
            'days' => [],
            'total_visits' => 0,
            'weekly_schedule' => '未設定',
            'visit_dates' => [],
            'excluded_holidays' => []
        ];
    }
    
    // その年の祝日一覧を取得
    $holidays = get_japanese_holidays($year);
    
    $visitDates = [];
    $totalVisits = 0;
    $excludedHolidays = [];
    
    // 各曜日の訪問予定日を取得
    foreach ($weeklyDays as $dayOfWeek) {
        $firstDay = new DateTime("{$year}-{$month}-01");
        $lastDay = new DateTime($firstDay->format('Y-m-t'));
        $currentDate = clone $firstDay;
        
        $daysInMonth = [];
        $holidaysInMonth = [];
        
        while ($currentDate <= $lastDay) {
            if ($currentDate->format('N') == $dayOfWeek) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayNumber = (int)$currentDate->format('j');
                
                if (isset($holidays[$dateStr])) {
                    // 祝日の場合
                    $holidaysInMonth[] = [
                        'date' => $dayNumber,
                        'name' => $holidays[$dateStr],
                        'full_date' => $dateStr
                    ];
                } else {
                    // 平日の場合
                    $daysInMonth[] = $dayNumber;
                    $totalVisits++;
                }
            }
            $currentDate->add(new DateInterval('P1D'));
        }
        
        $visitDates[] = [
            'day_of_week' => $dayOfWeek,
            'day_label' => get_day_of_week_short($dayOfWeek),
            'dates' => $daysInMonth,
            'count' => count($daysInMonth),
            'excluded_holidays' => $holidaysInMonth
        ];
        
        // 除外された祝日を全体リストに追加
        foreach ($holidaysInMonth as $holiday) {
            $excludedHolidays[] = [
                'date' => $holiday['full_date'],
                'day' => $holiday['date'],
                'name' => $holiday['name'],
                'day_of_week' => $dayOfWeek,
                'day_label' => get_day_of_week_label($dayOfWeek)
            ];
        }
    }
    
    return [
        'days' => $weeklyDays,
        'total_visits' => $totalVisits,
        'weekly_schedule' => format_weekly_schedule($weeklyDays),
        'visit_dates' => $visitDates,
        'excluded_holidays' => $excludedHolidays
    ];
}

/**
 * 祝日除外版の週間スケジュール詳細表示用データを生成
 * 
 * @param int $contractId 契約ID
 * @param int $year 年
 * @param int $month 月
 * @param PDO $db データベース接続
 * @return string フォーマットされた文字列
 */
function format_weekly_schedule_detailed_excluding_holidays($contractId, $year, $month, $db = null) {
    $details = get_weekly_contract_details_excluding_holidays($contractId, $year, $month, $db);
    
    if (empty($details['visit_dates'])) {
        return '週間スケジュール未設定';
    }
    
    $scheduleText = '';
    $hasExcludedHolidays = false;
    
    foreach ($details['visit_dates'] as $dayInfo) {
        $scheduleText .= $dayInfo['day_label'] . '：';
        
        if (!empty($dayInfo['dates'])) {
            $scheduleText .= implode(',', $dayInfo['dates']) . '日';
        } else {
            $scheduleText .= '(祝日のため訪問なし)';
        }
        
        $scheduleText .= ' (' . $dayInfo['count'] . '回) ';
        
        if (!empty($dayInfo['excluded_holidays'])) {
            $hasExcludedHolidays = true;
        }
    }
    
    // 除外された祝日の情報を追加
    if ($hasExcludedHolidays && !empty($details['excluded_holidays'])) {
        $scheduleText .= "\n[除外祝日: ";
        $holidayList = [];
        foreach ($details['excluded_holidays'] as $holiday) {
            $holidayList[] = $holiday['day'] . '日(' . $holiday['name'] . ')';
        }
        $scheduleText .= implode(', ', $holidayList) . ']';
    }
    
    return trim($scheduleText);
}

/**
 * 週間契約の年間予定時間を計算（祝日除外版）
 * 
 * @param int $contractId 契約ID
 * @param float $weeklyHours 週間時間数
 * @param int $year 年
 * @param PDO $db データベース接続
 * @return array 年間データ
 */
function calculate_yearly_weekly_hours_excluding_holidays($contractId, $weeklyHours, $year, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $totalHours = 0;
    $monthlyData = [];
    
    // 各月の時間を計算
    for ($month = 1; $month <= 12; $month++) {
        $monthlyHours = calculate_weekly_monthly_hours_excluding_holidays($contractId, $weeklyHours, $year, $month, $db);
        $weeklyDetails = get_weekly_contract_details_excluding_holidays($contractId, $year, $month, $db);
        
        $monthlyData[] = [
            'month' => $month,
            'month_name' => $month . '月',
            'monthly_hours' => $monthlyHours,
            'total_visits' => $weeklyDetails['total_visits'],
            'weekly_schedule' => $weeklyDetails['weekly_schedule'],
            'visit_dates' => $weeklyDetails['visit_dates'],
            'excluded_holidays' => $weeklyDetails['excluded_holidays']
        ];
        
        $totalHours += $monthlyHours;
    }
    
    return [
        'yearly_total_hours' => $totalHours,
        'monthly_breakdown' => $monthlyData,
        'avg_monthly_hours' => $totalHours / 12
    ];
}

/**
 * 祝日除外機能の統計情報を取得
 * 
 * @param int $contractId 契約ID
 * @param int $year 年
 * @param PDO $db データベース接続
 * @return array 統計情報
 */
function get_holiday_exclusion_statistics($contractId, $year, $db = null) {
    // 祝日除外なしの計算
    $originalData = calculate_yearly_weekly_hours($contractId, 1, $year, $db); // 1時間で計算
    
    // 祝日除外ありの計算
    $excludingData = calculate_yearly_weekly_hours_excluding_holidays($contractId, 1, $year, $db);
    
    $totalOriginalVisits = 0;
    $totalExcludingVisits = 0;
    $totalExcludedHolidays = 0;
    
    for ($month = 1; $month <= 12; $month++) {
        $weeklyDays = get_weekly_schedule($contractId, $db);
        
        if (!empty($weeklyDays)) {
            // 祝日除外なしの訪問回数
            foreach ($weeklyDays as $dayOfWeek) {
                $totalOriginalVisits += count_weekdays_in_month($year, $month, $dayOfWeek);
            }
            
            // 祝日除外ありの訪問回数
            $monthlyDetails = get_weekly_contract_details_excluding_holidays($contractId, $year, $month, $db);
            $totalExcludingVisits += $monthlyDetails['total_visits'];
            $totalExcludedHolidays += count($monthlyDetails['excluded_holidays']);
        }
    }
    
    return [
        'original_visits' => $totalOriginalVisits,
        'excluding_visits' => $totalExcludingVisits,
        'excluded_holidays_count' => $totalExcludedHolidays,
        'reduction_rate' => $totalOriginalVisits > 0 ? 
            (($totalOriginalVisits - $totalExcludingVisits) / $totalOriginalVisits) * 100 : 0,
        'monthly_breakdown' => $excludingData['monthly_breakdown']
    ];
}

/**
 * 次回の週間訪問日を取得（祝日除外版）
 * 
 * @param int $contractId 契約ID
 * @param PDO $db データベース接続
 * @param int $limit 取得する日数の上限
 * @return array 次回訪問日のリスト
 */
function get_next_weekly_visit_dates_excluding_holidays($contractId, $db = null, $limit = 5) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $weeklyDays = get_weekly_schedule($contractId, $db);
    
    if (empty($weeklyDays)) {
        return [];
    }
    
    $today = new DateTime();
    $currentYear = (int)$today->format('Y');
    $holidays = get_japanese_holidays($currentYear);
    
    // 翌年の祝日も取得（年末年始対応）
    $nextYearHolidays = get_japanese_holidays($currentYear + 1);
    $holidays = array_merge($holidays, $nextYearHolidays);
    
    $nextVisits = [];
    $searchDate = clone $today;
    
    // 今日から最大60日先まで検索（祝日除外により日数が必要）
    for ($i = 0; $i < 60 && count($nextVisits) < $limit; $i++) {
        $currentDayOfWeek = (int)$searchDate->format('N');
        $dateStr = $searchDate->format('Y-m-d');
        
        // 指定された曜日で、かつ祝日でない場合
        if (in_array($currentDayOfWeek, $weeklyDays) && !isset($holidays[$dateStr])) {
            $nextVisits[] = [
                'date' => $dateStr,
                'formatted_date' => $searchDate->format('m月d日'),
                'day_of_week' => $currentDayOfWeek,
                'day_label' => get_day_of_week_short($currentDayOfWeek),
                'days_from_today' => $i,
                'is_holiday_excluded' => false
            ];
        } elseif (in_array($currentDayOfWeek, $weeklyDays) && isset($holidays[$dateStr])) {
            // 祝日で除外される日も記録（参考情報として）
            $nextVisits[] = [
                'date' => $dateStr,
                'formatted_date' => $searchDate->format('m月d日'),
                'day_of_week' => $currentDayOfWeek,
                'day_label' => get_day_of_week_short($currentDayOfWeek),
                'days_from_today' => $i,
                'is_holiday_excluded' => true,
                'holiday_name' => $holidays[$dateStr]
            ];
        }
        
        $searchDate->add(new DateInterval('P1D'));
    }
    
    return $nextVisits;
}

/**
 * 既存関数の祝日除外版への置き換え用ラッパー
 * helpers.phpの該当関数を置き換える場合に使用
 */

// 元の関数名で祝日除外版を呼び出すためのラッパー関数
function count_weekdays_in_month_with_holiday_exclusion($year, $month, $dayOfWeek) {
    return count_weekdays_in_month_excluding_holidays($year, $month, $dayOfWeek);
}

function calculate_weekly_monthly_hours_with_holiday_exclusion($contractId, $weeklyHours, $year, $month, $db = null) {
    return calculate_weekly_monthly_hours_excluding_holidays($contractId, $weeklyHours, $year, $month, $db);
}

function get_weekly_contract_details_with_holiday_exclusion($contractId, $year, $month, $db = null) {
    return get_weekly_contract_details_excluding_holidays($contractId, $year, $month, $db);
}

/**
 * 祝日除外設定の切り替え用関数
 * config/app.phpで設定を管理する場合
 */
function is_holiday_exclusion_enabled() {
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/../../config/app.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            $config = ['exclude_holidays_from_weekly_visits' => true]; // デフォルトは有効
        }
    }
    
    return $config['exclude_holidays_from_weekly_visits'] ?? true;
}

/**
 * 設定に応じて適切な関数を呼び出すアダプター関数
 */
function count_weekdays_in_month_adaptive($year, $month, $dayOfWeek) {
    if (is_holiday_exclusion_enabled()) {
        return count_weekdays_in_month_excluding_holidays($year, $month, $dayOfWeek);
    } else {
        return count_weekdays_in_month($year, $month, $dayOfWeek);
    }
}

function calculate_weekly_monthly_hours_adaptive($contractId, $weeklyHours, $year, $month, $db = null) {
    if (is_holiday_exclusion_enabled()) {
        return calculate_weekly_monthly_hours_excluding_holidays($contractId, $weeklyHours, $year, $month, $db);
    } else {
        return calculate_weekly_monthly_hours($contractId, $weeklyHours, $year, $month, $db);
    }
}

function get_weekly_contract_details_adaptive($contractId, $year, $month, $db = null) {
    if (is_holiday_exclusion_enabled()) {
        return get_weekly_contract_details_excluding_holidays($contractId, $year, $month, $db);
    } else {
        return get_weekly_contract_details($contractId, $year, $month, $db);
    }
}

/**
 * 毎週契約の月間時間を計算（exclude_holidaysとnon_visit_daysを考慮）
 * 
 * @param int $contractId 契約ID
 * @param float $weeklyHours 週間時間数
 * @param int $year 年
 * @param int $month 月
 * @param PDO $db データベース接続
 * @return float 月間時間数
 */
function calculate_weekly_monthly_hours_with_contract_settings($contractId, $weeklyHours, $year, $month, $db = null) {
    if (!$db) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    
    // 契約情報を取得
    $contractSql = "SELECT exclude_holidays FROM contracts WHERE id = :contract_id";
    $stmt = $db->prepare($contractSql);
    $stmt->execute(['contract_id' => $contractId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        return 0;
    }
    
    $excludeHolidays = (bool)$contract['exclude_holidays'];
    
    // 週間スケジュールを取得
    $weeklyDays = get_weekly_schedule($contractId, $db);
    
    if (empty($weeklyDays)) {
        return 0;
    }
    
    // その年の祝日一覧を取得（exclude_holidaysが1の場合のみ）
    $holidays = $excludeHolidays ? get_japanese_holidays($year) : [];
    
    // 契約別非訪問日を取得
    $nonVisitDays = get_contract_non_visit_days($contractId, $year, $month, $db);
    
    $totalHours = 0;
    
    // 各曜日について、その月に祝日・非訪問日を除いて何回あるかを計算
    foreach ($weeklyDays as $dayOfWeek) {
        $weekCount = count_weekdays_excluding_holidays_and_non_visit_days(
            $year, $month, $dayOfWeek, $holidays, $nonVisitDays
        );
        $totalHours += $weekCount * $weeklyHours;
    }
    
    return $totalHours;
}

/**
 * 契約別非訪問日を取得
 * 
 * @param int $contractId 契約ID
 * @param int $year 年
 * @param int $month 月
 * @param PDO $db データベース接続
 * @return array 非訪問日の配列（Y-m-d形式）
 */
function get_contract_non_visit_days($contractId, $year, $month, $db) {
    $sql = "SELECT non_visit_date, is_recurring, recurring_month, recurring_day, year
            FROM contract_non_visit_days 
            WHERE contract_id = :contract_id 
            AND is_active = 1
            AND (
                (year = :year AND MONTH(non_visit_date) = :month)
                OR 
                (is_recurring = 1 AND year < :year2 AND recurring_month = :month2)
            )";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'contract_id' => $contractId,
        'year' => $year,
        'year2' => $year,
        'month' => $month,
        'month2' => $month
    ]);
    
    $nonVisitDays = [];
    
    while ($row = $stmt->fetch()) {
        if ($row['is_recurring'] && $row['year'] < $year) {
            // 繰り返し設定の場合、今年の日付に読み替え
            $nonVisitDate = sprintf('%04d-%02d-%02d', $year, $row['recurring_month'], $row['recurring_day']);
        } else {
            $nonVisitDate = $row['non_visit_date'];
        }
        
        $nonVisitDays[] = $nonVisitDate;
    }
    
    return $nonVisitDays;
}

/**
 * 指定した年月の特定の曜日が祝日・非訪問日を除いて何回あるかを計算
 * 
 * @param int $year 年
 * @param int $month 月
 * @param int $dayOfWeek 曜日（1=月曜日, 7=日曜日）
 * @param array $holidays 祝日の配列（Y-m-d形式）
 * @param array $nonVisitDays 非訪問日の配列（Y-m-d形式）
 * @return int 祝日・非訪問日を除いた回数
 */
function count_weekdays_excluding_holidays_and_non_visit_days($year, $month, $dayOfWeek, $holidays, $nonVisitDays) {
    // 月の最初の日と最後の日を取得
    $firstDay = new DateTime("{$year}-{$month}-01");
    $lastDay = new DateTime($firstDay->format('Y-m-t'));
    
    $count = 0;
    $currentDate = clone $firstDay;
    
    // 月の各日をチェック
    while ($currentDate <= $lastDay) {
        // 指定された曜日かチェック（PHPの曜日: 1=月曜日, 7=日曜日）
        if ($currentDate->format('N') == $dayOfWeek) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // 祝日でも非訪問日でもない場合のみカウント
            if (!isset($holidays[$dateStr]) && !in_array($dateStr, $nonVisitDays)) {
                $count++;
            }
        }
        $currentDate->add(new DateInterval('P1D'));
    }
    
    return $count;
}

function get_database_connection() {
    static $db = null;
    if ($db === null) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
    }
    return $db;
}


if (!function_exists('h')) {
    /**
     * HTMLエスケープ用のヘルパー関数
     * 
     * @param string $str エスケープする文字列
     * @return string エスケープされた文字列
     */
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generate_csrf_token')) {
    /**
     * CSRFトークンを生成
     */
    function generate_csrf_token() {
        return csrf_token(); // 既存のcsrf_token()関数を使用
    }
}

/**
 * 10進数の時間を時分形式に変換
 * 
 * @param float $decimalHours 10進数の時間（例：0.67）
 * @return string 時分形式（例：40分）
 */
function format_hours_minutes($decimalHours) {
    if ($decimalHours <= 0) {
        return '0分';
    }
    
    $hours = floor($decimalHours);
    $minutes = round(($decimalHours - $hours) * 60);
    
    // 60分になった場合は1時間に繰り上げ
    if ($minutes >= 60) {
        $hours += 1;
        $minutes = 0;
    }
    
    if ($hours > 0 && $minutes > 0) {
        return $hours . '時間' . $minutes . '分';
    } elseif ($hours > 0) {
        return $hours . '時間';
    } else {
        return $minutes . '分';
    }
}

// ========================================
// ファイル関連のヘルパー関数
// ========================================

if (!function_exists('formatFileSize')) {
    /**
     * ファイルサイズを人間が読みやすい形式でフォーマット
     * 
     * @param int $bytes ファイルサイズ（バイト）
     * @return string フォーマットされたファイルサイズ
     */
    function formatFileSize($bytes) {
        if ($bytes === 0 || $bytes === null) {
            return '0 Bytes';
        }
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

if (!function_exists('getFileIcon')) {
    /**
     * ファイル拡張子からアイコンクラスを取得
     * 
     * @param string $filename ファイル名
     * @return string FontAwesomeアイコンクラス
     */
    function getFileIcon($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return 'fas fa-file-pdf text-danger';
            case 'doc':
            case 'docx':
                return 'fas fa-file-word text-primary';
            case 'xls':
            case 'xlsx':
                return 'fas fa-file-excel text-success';
            case 'ppt':
            case 'pptx':
                return 'fas fa-file-powerpoint text-warning';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                return 'fas fa-file-image text-info';
            case 'zip':
            case 'rar':
            case '7z':
                return 'fas fa-file-archive text-secondary';
            case 'txt':
                return 'fas fa-file-alt text-muted';
            case 'csv':
                return 'fas fa-file-csv text-success';
            default:
                return 'fas fa-file text-muted';
        }
    }
}

if (!function_exists('generateSecureFileName')) {
    /**
     * セキュアなファイル名を生成
     * 
     * @param string $originalName 元のファイル名
     * @param string $prefix プレフィックス
     * @return string セキュアなファイル名
     */
    function generateSecureFileName($originalName, $prefix = '') {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // ファイル名をサニタイズ
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $safeName = substr($safeName, 0, 50); // 最大50文字
        
        // プレフィックス + タイムスタンプ + ランダム文字列 + 元のファイル名 + 拡張子
        return $prefix . date('YmdHis') . '_' . uniqid() . '_' . $safeName . '.' . $extension;
    }
}

if (!function_exists('validateFileExtension')) {
    /**
     * ファイル拡張子の検証
     * 
     * @param string $filename ファイル名
     * @param array $allowedExtensions 許可する拡張子の配列
     * @return bool 検証結果
     */
    function validateFileExtension($filename, $allowedExtensions) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, array_map('strtolower', $allowedExtensions));
    }
}

if (!function_exists('getMimeTypeByExtension')) {
    /**
     * 拡張子からMIMEタイプを取得
     * 
     * @param string $extension ファイル拡張子
     * @return string|null MIMEタイプ
     */
    function getMimeTypeByExtension($extension) {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'zip' => 'application/zip',
            'txt' => 'text/plain',
            'csv' => 'text/csv'
        ];
        
        return $mimeTypes[strtolower($extension)] ?? null;
    }
}

if (!function_exists('isImageFile')) {
    /**
     * 画像ファイルかどうかを判定
     * 
     * @param string $filename ファイル名
     * @return bool 画像ファイルの場合true
     */
    function isImageFile($filename) {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        return validateFileExtension($filename, $imageExtensions);
    }
}

if (!function_exists('getFileTypeCategory')) {
    /**
     * ファイルタイプのカテゴリを取得
     * 
     * @param string $filename ファイル名
     * @return string ファイルカテゴリ
     */
    function getFileTypeCategory($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $categories = [
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            'spreadsheet' => ['xls', 'xlsx', 'csv'],
            'presentation' => ['ppt', 'pptx'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
            'audio' => ['mp3', 'wav', 'flac', 'aac']
        ];
        
        foreach ($categories as $category => $extensions) {
            if (in_array($extension, $extensions)) {
                return $category;
            }
        }
        
        return 'other';
    }
}

if (!function_exists('sanitizeFileName')) {
    /**
     * ファイル名をサニタイズ
     * 
     * @param string $filename ファイル名
     * @param int $maxLength 最大文字数
     * @return string サニタイズされたファイル名
     */
    function sanitizeFileName($filename, $maxLength = 255) {
        // 危険な文字を除去
        $filename = preg_replace('/[<>:"/\\|?*]/', '', $filename);
        
        // 制御文字を除去
        $filename = preg_replace('/[\x00-\x1f\x7f]/', '', $filename);
        
        // 連続するドットやスペースを単一に
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        $filename = preg_replace('/\s{2,}/', ' ', $filename);
        
        // 前後の空白とドットを除去
        $filename = trim($filename, " \t\n\r\0\x0B.");
        
        // 最大文字数制限
        if (mb_strlen($filename) > $maxLength) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $maxBaseLength = $maxLength - mb_strlen($extension) - 1;
            $filename = mb_substr($baseName, 0, $maxBaseLength) . '.' . $extension;
        }
        
        return $filename;
    }
}

if (!function_exists('createUploadDirectory')) {
    /**
     * アップロードディレクトリを作成
     * 
     * @param string $path ディレクトリパス
     * @param int $permissions パーミッション
     * @return bool 作成成功の場合true
     */
    function createUploadDirectory($path, $permissions = 0755) {
        if (is_dir($path)) {
            return true;
        }
        
        if (!mkdir($path, $permissions, true)) {
            return false;
        }
        
        // セキュリティファイルを作成
        $htaccessContent = <<<EOD
# 実行可能ファイルの実行を禁止
<Files *>
    ForceType application/octet-stream
    Header set Content-Disposition attachment
</Files>

# PHPファイルの実行を禁止
<FilesMatch "\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# 直接アクセスを制限
Order Deny,Allow
Deny from all
EOD;
        
        file_put_contents($path . '/.htaccess', $htaccessContent);
        file_put_contents($path . '/.gitkeep', '');
        
        return true;
    }
}

if (!function_exists('getUploadMaxSize')) {
    /**
     * アップロード可能な最大ファイルサイズを取得
     * 
     * @return int 最大ファイルサイズ（バイト）
     */
    function getUploadMaxSize() {
        $maxUpload = ini_get('upload_max_filesize');
        $maxPost = ini_get('post_max_size');
        $memoryLimit = ini_get('memory_limit');
        
        $maxUploadBytes = convertToBytes($maxUpload);
        $maxPostBytes = convertToBytes($maxPost);
        $memoryLimitBytes = convertToBytes($memoryLimit);
        
        return min($maxUploadBytes, $maxPostBytes, $memoryLimitBytes);
    }
}

if (!function_exists('convertToBytes')) {
    /**
     * PHP設定値をバイトに変換
     * 
     * @param string $value PHP設定値（例: "8M", "1G"）
     * @return int バイト数
     */
    function convertToBytes($value) {
        $value = trim($value);
        if (empty($value)) {
            return 0;
        }
        
        $last = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;
        
        switch ($last) {
            case 'g':
                $number *= 1024;
                // no break
            case 'm':
                $number *= 1024;
                // no break
            case 'k':
                $number *= 1024;
        }
        
        return $number;
    }
}

?>