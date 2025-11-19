<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/TwoFactorAuth.php';

class SettingsController extends BaseController {
    
    private function getAppUrl() {
        $config = require __DIR__ . '/../../config/app.php';
        return rtrim($config['app_url'], '/');
    }
    
    public function security() {
        Session::requireLogin();
        
        $userId = Session::get('user_id');
        
        // 2FA情報を取得
        require_once __DIR__ . '/../models/TwoFactorAuth.php';
        $twoFactorAuth = new TwoFactorAuth();
        $twoFactorData = $twoFactorAuth->getUserTwoFactorData($userId);
        $is2FAEnabled = $twoFactorData && $twoFactorData['two_factor_enabled'];
        
        // アクティブセッション取得
        $activeSessions = Session::getUserActiveSessions($userId);
        
        $this->generateCsrfToken();
        
        // ビューを読み込む
        ob_start();
        include __DIR__ . '/../views/settings/security.php';
        $content = ob_get_clean();
        
        // base.phpレイアウトで表示
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function revokeSession() {
        Session::requireLogin();
        
        if (!$this->validateCsrf()) {
            Session::flash('error', 'セキュリティトークンが無効です。ページを再読み込みしてください。');
            header('Location: ' . $this->getAppUrl() . '/settings/security');
            exit;
        }
        
        $sessionId = $_POST['session_id'] ?? '';
        $userId = Session::get('user_id');
        
        if (Session::revokeSession($userId, $sessionId)) {
            Session::flash('success', 'セッションを無効化しました。');
        } else {
            Session::flash('error', 'セッションの無効化に失敗しました。');
        }
        
        header('Location: ' . $this->getAppUrl() . '/settings/security');
        exit;
    }
    
    public function revokeAllSessions() {
        Session::requireLogin();
        
        if (!$this->validateCsrf()) {
            Session::flash('error', 'セキュリティトークンが無効です。ページを再読み込みしてください。');
            header('Location: ' . $this->getAppUrl() . '/settings/security');
            exit;
        }
        
        $userId = Session::get('user_id');
        
        if (Session::revokeAllOtherSessions($userId)) {
            Session::flash('success', '他のすべてのセッションを無効化しました。');
        } else {
            Session::flash('error', 'セッションの無効化に失敗しました。');
        }
        
        header('Location: ' . $this->getAppUrl() . '/settings/security');
        exit;
    }
}