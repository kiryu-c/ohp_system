<?php
// config/security.php
return [
    // パスワードポリシー
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'history_count' => 5, // 過去5回分のパスワードを記録
        'expiry_days' => 90,  // パスワード有効期限（日）
    ],
    
    // セッション設定
    'session' => [
        'timeout' => 7200,        // セッションタイムアウト（秒）
        'warning_time' => 600,    // タイムアウト警告時間（秒）
        'ip_validation' => true,  // IPアドレス検証
        'regenerate_interval' => 1800, // セッションID再生成間隔（秒）
    ],
    
    // ログイン制限
    'login' => [
        'max_attempts' => 5,      // 最大ログイン試行回数
        'lockout_duration' => 30, // アカウントロック時間（分）
        'rate_limit_window' => 900, // レート制限ウィンドウ（秒）
    ],
    
    // ファイルアップロード制限
    'upload' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 
            'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif'
        ],
        'scan_uploads' => true,   // ウイルススキャン（要設定）
        'quarantine_path' => __DIR__ . '/../storage/quarantine/',
    ],
    
    // セキュリティヘッダー
    'headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'",
    ],
    
    // 監査ログ
    'audit' => [
        'enabled' => true,
        'sensitive_actions' => [
            'login', 'logout', 'password_change', 'user_create', 
            'user_update', 'user_delete', 'contract_create', 
            'contract_update', 'service_record_approve', 'service_record_reject'
        ],
        'log_retention_days' => 90,
    ],
    
    // IP制限（管理者機能用）
    'ip_restrictions' => [
        'admin_whitelist' => [], // 管理者アクセス許可IP（空の場合は制限なし）
        'blocked_ips' => [],     // ブロックするIP
    ],
    
    // 暗号化設定
    'encryption' => [
        'algorithm' => 'aes-256-gcm',
        'key_rotation_days' => 90,
    ],
];