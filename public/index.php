<?php
// public/index.php 修正版
// エラーレポートを設定（本番環境では0に変更すること）
error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0); // 本番環境では必ず0にする
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// タイムゾーンを設定
date_default_timezone_set('Asia/Tokyo');

// 必要なファイルを読み込み
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/helpers.php';

require_once __DIR__ . '/../vendor/autoload.php';

// セッションを開始
Session::start();

// Remember Tokenによる自動ログインをチェック
// ログインページ以外でのみ実行
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isLoginPage = (strpos($currentPath, '/login') !== false);
$isLogoutPage = (strpos($currentPath, '/logout') !== false);

// Remember Tokenによる自動ログインをチェック
if (!$isLoginPage && !$isLogoutPage && !Session::isLoggedIn()) {
    try {
        $autoLoginSuccess = Session::checkAutoLogin();
        
        // 2FAが有効なユーザーの場合は自動ログインをスキップ
        if ($autoLoginSuccess) {
            require_once __DIR__ . '/../app/models/TwoFactorAuth.php';
            $twoFactorAuth = new TwoFactorAuth();
            $userId = Session::get('user_id');
            $twoFactorData = $twoFactorAuth->getUserTwoFactorData($userId);
            
            if ($twoFactorData && $twoFactorData['two_factor_enabled']) {
                // 2FA有効ユーザーは自動ログインさせない(セキュリティ強化)
                Session::destroy();
                setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            } elseif ($currentPath === '/op_website/public/') {
                header('Location: /op_website/public/dashboard');
                exit;
            }
        }
    } catch (Exception $e) {
        error_log('[AUTO_LOGIN_ERROR] ' . $e->getMessage());
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        }
    }
}

// セキュリティヘッダーを設定
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy（必要に応じて調整）
// header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src \'self\' https://cdnjs.cloudflare.com;');

// ルーターを初期化
$router = new Router();

// ルートを読み込み
require_once __DIR__ . '/../routes/web.php';

// リクエストを処理
try {
    $router->dispatch();
} catch (Exception $e) {
    error_log('[ROUTER_ERROR] ' . $e->getMessage());
    
    // エラーページを表示（本番環境用）
    http_response_code(500);
    
    if (ini_get('display_errors')) {
        // 開発環境
        echo '<h1>エラーが発生しました</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        // 本番環境
        echo '<h1>システムエラー</h1>';
        echo '<p>申し訳ございません。システムエラーが発生しました。しばらく時間をおいて再度お試しください。</p>';
    }
}