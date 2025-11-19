<?php
// config/app.php
return [
    'app_name' => '産業医役務管理システム',
    'app_version' => '1.0.0',
    'app_url' => 'http://localhost/op_website/public',
    'timezone' => 'Asia/Tokyo',
    'session_name' => 'OPWEBSITE_SESSION',
    'session_lifetime' => 1440, // 24時間（分）
    'password_min_length' => 8,
    'pagination_limit' => 20,
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
    'datetime_format' => 'Y-m-d H:i:s',
    'exclude_holidays_from_weekly_visits' => true, 
    // 暗号化キー(本番環境では必ず変更してください)
    'encryption_key' => getenv('ENCRYPTION_KEY') ?: 'CHANGE-THIS-TO-RANDOM-32-CHAR-STRING',
];
