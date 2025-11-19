<?php
// app/controllers/AuthController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends BaseController {
    private function getAppUrl() {
        $config = require __DIR__ . '/../../config/app.php';
        return rtrim($config['app_url'], '/');
    }
    
    public function showLogin() {
        Session::start();
        
        // 既にログインしている場合はダッシュボードへ
        if (Session::isLoggedIn()) {
            header('Location: ' . $this->getAppUrl() . '/dashboard');
            exit;
        }
        
        // CSRFトークンを生成
        $this->generateCsrfToken();
        
        // ログイン画面を表示
        include __DIR__ . '/../views/auth/login.php';
    }
    
    public function login() {
        Session::start();
        
        // リクエスト制限チェック（簡易版）
        if (!$this->checkLoginRateLimit()) {
            Session::flash('error', 'ログイン試行回数が制限を超えました。しばらく待ってから再試行してください。');
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
        
        // CSRFトークンの検証
        if (!$this->validateCsrf()) {
            $this->logSecurityEvent('csrf_validation_failed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            Session::flash('error', 'セキュリティトークンが無効です。ログインし直してください。');
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
        
        $loginId = trim($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // 入力値検証
        if (empty($loginId) || empty($password)) {
            $this->recordFailedLogin($loginId);
            Session::flash('error', 'ログインIDとパスワードを入力してください。');
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
        
        // SQL Injection 対策: 入力値の基本検証
        if (strlen($loginId) > 50 || strlen($password) > 255) {
            $this->logSecurityEvent('suspicious_input_length', [
                'login_id_length' => strlen($loginId),
                'password_length' => strlen($password),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            Session::flash('error', 'ログインIDまたはパスワードが間違っています。');
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
        
        $userModel = new User();
        
        // アカウントロックチェック（安全版）
        try {
            if ($userModel->isAccountLocked($loginId)) {
                $this->logSecurityEvent('login_attempt_locked_account', [
                    'login_id' => $loginId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                Session::flash('error', 'アカウントが一時的にロックされています。管理者にお問い合わせください。');
                header('Location: ' . $this->getAppUrl() . '/login');
                exit;
            }
        } catch (Exception $e) {
            // アカウントロック機能でエラーが発生した場合はログイン処理を続行
            error_log('Account lock check failed: ' . $e->getMessage());
        }
        
        $user = $userModel->authenticate($loginId, $password);
        
        if ($user) {
            // 2FAが有効かチェック
            require_once __DIR__ . '/../models/TwoFactorAuth.php';
            $twoFactorAuth = new TwoFactorAuth();
            $twoFactorData = $twoFactorAuth->getUserTwoFactorData($user['id']);
            
            if ($twoFactorData && $twoFactorData['two_factor_enabled']) {
                // 2FA検証ページへ
                Session::set('pending_user_id', $user['id']);
                Session::set('pending_user_data', $user);
                Session::set('pending_remember_session', isset($_POST['remember_session']));
                
                // 2FA検証ページにリダイレクト
                header('Location: ' . $this->getAppUrl() . '/login/verify-2fa');
                exit;
            }
            
            // 2FAが無効の場合は通常のログイン処理
            $this->completeLogin($user, isset($_POST['remember_session']));
        } else {
            // ログイン失敗時の処理
            $this->recordFailedLogin($loginId);
            
            $this->logSecurityEvent('login_failed', [
                'login_id' => $loginId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            Session::flash('error', 'ログインIDまたはパスワードが間違っています。');
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
    }

    public function showVerify2FA() {
        Session::start();
        
        // pending_user_idがない場合はログインページへ
        if (!Session::has('pending_user_id')) {
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
        
        $this->generateCsrfToken();
        include __DIR__ . '/../views/auth/verify_2fa.php';
    }

    public function verify2FA() {
        Session::start();
        
        if (!Session::has('pending_user_id')) {
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
        
        if (!$this->validateCsrf()) {
            Session::flash('error', 'セキュリティトークンが無効です。ログインし直してください。');
            header('Location: ' . $this->getAppUrl() . '/login/verify-2fa');
            exit;
        }
        
        $code = trim($_POST['code'] ?? '');
        $useRecoveryCode = isset($_POST['use_recovery_code']);
        
        $userId = Session::get('pending_user_id');
        $user = Session::get('pending_user_data');
        
        require_once __DIR__ . '/../models/TwoFactorAuth.php';
        $twoFactorAuth = new TwoFactorAuth();
        $twoFactorData = $twoFactorAuth->getUserTwoFactorData($userId);
        
        $verified = false;
        
        if ($useRecoveryCode) {
            // リカバリーコードで検証
            $verified = $twoFactorAuth->verifyRecoveryCode($userId, $code);
            
            if ($verified) {
                $this->logSecurityEvent('2fa_recovery_code_used', [
                    'user_id' => $userId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
        } else {
            // TOTPコードで検証
            $verified = $twoFactorAuth->verifyCode($twoFactorData['two_factor_secret'], $code);
        }
        
        if ($verified) {
            // 検証成功 - ログイン完了
            $rememberSession = Session::get('pending_remember_session', false);
            
            // pending情報をクリア
            Session::remove('pending_user_id');
            Session::remove('pending_user_data');
            Session::remove('pending_remember_session');
            
            $this->completeLogin($user, $rememberSession);
        } else {
            // 検証失敗
            $this->logSecurityEvent('2fa_verification_failed', [
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Session::flash('error', '認証コードが正しくありません。');
            header('Location: ' . $this->getAppUrl() . '/login/verify-2fa');
            exit;
        }
    }

    private function completeLogin($user, $rememberSession = false) {
        // 既存のログイン完了処理
        $this->handleSuccessfulLogin($user, $rememberSession);
        
        session_regenerate_id(true);
        
        Session::set('user_id', $user['id']);
        Session::set('user_type', $user['user_type']);
        Session::set('user_name', $user['name']);
        Session::set('login_time', time());
        Session::set('last_activity', time());
        
        if ($rememberSession) {
            Session::set('remember_session', true);
            Session::set('extended_session', true);
            $this->setExtendedCookie();
        }
        
        if ($user['user_type'] === 'company') {
            Session::set('company_id', $user['company_id']);
        }
        
        $this->logSecurityEvent('login_success', [
            'user_id' => $user['id'],
            'user_type' => $user['user_type'],
            '2fa_used' => true,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        Session::flash('success', 'ログインしました。');
        header('Location: ' . $this->getAppUrl() . '/dashboard');
        exit;
    }
    
    public function logout() {
        Session::start();
        
        // ログアウトをログに記録
        if (Session::isLoggedIn()) {
            $this->logSecurityEvent('logout', [
                'user_id' => Session::get('user_id'),
                'user_type' => Session::get('user_type'),
                'session_duration' => time() - (Session::get('login_time') ?? time()),
                'was_remembered' => Session::get('remember_session', false),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            // Remember Tokenを削除
            $this->removeRememberToken();
        }
        
        // セッション完全破棄
        Session::destroy();
        
        // セッションクッキーとRemember Tokenクッキーを削除
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        header('Location: ' . $this->getAppUrl() . '/login');
        exit;
    }
    
    /**
     * ログイン試行回数制限チェック（簡易版）
     */
    private function checkLoginRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login_attempts_' . $ip;
        $maxAttempts = 10; // 緩めに設定
        $timeWindow = 900; // 15分
        
        $attempts = Session::get($key, []);
        $now = time();
        
        // 時間窓外の試行を削除
        $attempts = array_filter($attempts, function($time) use ($now, $timeWindow) {
            return ($now - $time) < $timeWindow;
        });
        
        return count($attempts) < $maxAttempts;
    }
    
    /**
     * 失敗したログイン試行を記録
     */
    private function recordFailedLogin($loginId) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login_attempts_' . $ip;
        
        $attempts = Session::get($key, []);
        $attempts[] = time();
        Session::set($key, $attempts);
        
        // データベースにも記録（安全版）
        if (!empty($loginId)) {
            try {
                $userModel = new User();
                $userModel->incrementLoginAttempts($loginId);
            } catch (Exception $e) {
                error_log('Failed to increment login attempts: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * ログイン成功時の処理
     */
    private function handleSuccessfulLogin($user, $rememberSession = false) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login_attempts_' . $ip;
        
        // IP制限をリセット
        Session::remove($key);
        
        // Remember Tokenの処理（セッション保持が有効な場合）
        if ($rememberSession) {
            $this->createRememberToken($user['id']);
        }
        
        // ユーザーのログイン試行回数をリセット（安全版）
        try {
            $userModel = new User();
            $userModel->resetLoginAttempts($user['id']);
            $userModel->updateLastLogin($user['id']);
        } catch (Exception $e) {
            error_log('Failed to reset login attempts: ' . $e->getMessage());
        }
    }
    
    /**
     * Remember Token を作成（セキュリティ強化版）
     */
    private function createRememberToken($userId) {
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // ランダムなトークンを生成（クッキーに保存用）
            $rememberToken = bin2hex(random_bytes(32));
            
            // トークンのハッシュ値を生成（DB保存用）
            $tokenHash = hash('sha256', $rememberToken);
            
            // セレクター（トークン識別子）を生成
            $selector = bin2hex(random_bytes(16));
            
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30日後
            
            // 既存のRemember Tokenを削除
            $sql = "DELETE FROM user_sessions 
                    WHERE user_id = :user_id 
                    AND fingerprint IS NOT NULL";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            // 新しいRemember Tokenを保存
            $sql = "INSERT INTO user_sessions 
                    (session_id, user_id, ip_address, user_agent, fingerprint, expires_at, is_active)
                    VALUES (:session_id, :user_id, :ip_address, :user_agent, :fingerprint, :expires_at, 1)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'session_id' => $selector . ':' . $tokenHash, // セレクター:ハッシュ値
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'fingerprint' => $tokenHash, // ハッシュ値を保存
                'expires_at' => $expiresAt
            ]);
            
            // クッキーにはセレクター:トークン（平文）を保存
            $cookieValue = $selector . ':' . $rememberToken;
            setcookie(
                'remember_token', 
                $cookieValue, 
                [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );
            
            $this->logSecurityEvent('remember_token_created', [
                'user_id' => $userId,
                'selector' => $selector,
                'expires_at' => $expiresAt
            ]);
            
        } catch (Exception $e) {
            error_log('Failed to create remember token: ' . $e->getMessage());
        }
    }
    
    /**
     * Remember Tokenを削除
     */
    private function removeRememberToken() {
        try {
            if (!isset($_COOKIE['remember_token'])) {
                return;
            }
            
            $cookieValue = $_COOKIE['remember_token'];
            $parts = explode(':', $cookieValue, 2);
            
            if (count($parts) !== 2) {
                return;
            }
            
            list($selector, $token) = $parts;
            $tokenHash = hash('sha256', $token);
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // セレクターとハッシュ値が一致するトークンを削除
            $sql = "DELETE FROM user_sessions 
                    WHERE session_id LIKE :selector 
                    AND fingerprint = :token_hash";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'selector' => $selector . ':%',
                'token_hash' => $tokenHash
            ]);
            
        } catch (Exception $e) {
            error_log('Failed to remove remember token: ' . $e->getMessage());
        }
    }
    
    /**
     * 延長セッションクッキーを設定
     */
    private function setExtendedCookie() {
        $cookieParams = [
            'lifetime' => 30 * 24 * 60 * 60, // 30日
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) ? true : false,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        session_set_cookie_params($cookieParams);
        
        // 既存のクッキーを更新
        setcookie(
            session_name(),
            session_id(),
            time() + $cookieParams['lifetime'],
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    
    /**
     * 通常セッションクッキーを設定
     */
    private function setNormalCookie() {
        $cookieParams = [
            'lifetime' => 0, // ブラウザ終了時に削除
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) ? true : false,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        session_set_cookie_params($cookieParams);
    }
    
}