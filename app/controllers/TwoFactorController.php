<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/TwoFactorAuth.php';

class TwoFactorController extends BaseController {
    private $twoFactorAuth;
    
    public function __construct() {
        $this->twoFactorAuth = new TwoFactorAuth();
    }
    
    public function showSetup() {
        Session::requireLogin();
        
        $userId = Session::get('user_id');
        $twoFactorData = $this->twoFactorAuth->getUserTwoFactorData($userId);
        
        // 既に有効化されている場合
        if ($twoFactorData && $twoFactorData['two_factor_enabled']) {
            Session::flash('info', '二要素認証は既に有効化されています。');
            header('Location: /op_website/public/settings/security');
            exit;
        }
        
        // 新しい秘密鍵を生成
        $secret = $this->twoFactorAuth->generateSecret();
        Session::set('temp_2fa_secret', $secret);
        
        $userName = Session::get('user_name');
        $qrCodeUrl = $this->twoFactorAuth->getQRCodeUrl($userName, $secret);
        
        include __DIR__ . '/../views/two_factor/setup.php';
    }
    
    public function enable() {
        Session::requireLogin();
        
        if (!$this->validateCsrf()) {
            Session::flash('error', 'セキュリティトークンが無効です。ページを再読み込みしてください。');
            header('Location: /op_website/public/two-factor/setup');
            exit;
        }
        
        $code = trim($_POST['code'] ?? '');
        $secret = Session::get('temp_2fa_secret');
        
        if (!$secret) {
            Session::flash('error', 'セッションが無効です。最初からやり直してください。');
            header('Location: /op_website/public/two-factor/setup');
            exit;
        }
        
        // コードを検証
        if (!$this->twoFactorAuth->verifyCode($secret, $code)) {
            Session::flash('error', '認証コードが正しくありません。');
            header('Location: /op_website/public/two-factor/setup');
            exit;
        }
        
        // リカバリーコードを生成
        $recoveryCodes = $this->twoFactorAuth->generateRecoveryCodes();
        
        // 2FAを有効化
        $userId = Session::get('user_id');
        if ($this->twoFactorAuth->enableTwoFactor($userId, $secret, $recoveryCodes)) {
            Session::remove('temp_2fa_secret');
            Session::set('temp_recovery_codes', $recoveryCodes);
            
            $this->logSecurityEvent('2fa_enabled', [
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            // リカバリーコード表示ページへ
            header('Location: /op_website/public/two-factor/recovery-codes');
            exit;
        } else {
            Session::flash('error', '二要素認証の有効化に失敗しました。');
            header('Location: /op_website/public/two-factor/setup');
            exit;
        }
    }
    
    public function showRecoveryCodes() {
        Session::requireLogin();
        
        $recoveryCodes = Session::get('temp_recovery_codes');
        if (!$recoveryCodes) {
            header('Location: /op_website/public/settings/security');
            exit;
        }
        
        include __DIR__ . '/../views/two_factor/recovery_codes.php';
    }
    
    public function confirmRecoveryCodes() {
        Session::requireLogin();
        
        Session::remove('temp_recovery_codes');
        Session::flash('success', '二要素認証が有効化されました。');
        header('Location: /op_website/public/settings/security');
        exit;
    }
    
    public function disable() {
        Session::requireLogin();
        
        if (!$this->validateCsrf()) {
            Session::flash('error', 'セキュリティトークンが無効です。ページを再読み込みしてください。');
            header('Location: /op_website/public/settings/security');
            exit;
        }
        
        $password = $_POST['password'] ?? '';
        $userId = Session::get('user_id');
        
        // パスワードを再確認
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User();
        $user = $userModel->findById($userId);
        
        if (!$user || !password_verify($password, $user['password'])) {
            Session::flash('error', 'パスワードが正しくありません。');
            header('Location: /op_website/public/settings/security');
            exit;
        }
        
        // 2FAを無効化
        if ($this->twoFactorAuth->disableTwoFactor($userId)) {
            $this->logSecurityEvent('2fa_disabled', [
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Session::flash('success', '二要素認証を無効化しました。');
        } else {
            Session::flash('error', '二要素認証の無効化に失敗しました。');
        }
        
        header('Location: /op_website/public/settings/security');
        exit;
    }
}