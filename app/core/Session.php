<?php
// app/core/Session.php セキュリティ強化版
class Session {
    private static $sessionTimeout = 7200; // 2時間
    private static $sessionWarning = 600;  // 10分前に警告
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            $configFile = __DIR__ . '/../../config/app.php';
            $config = file_exists($configFile) ? require $configFile : [
                'session_name' => 'OPWEBSITE_SESSION'
            ];
            
            // セッション開始前にクッキーパラメータを設定
            session_set_cookie_params([
                'lifetime' => 0, // デフォルトはブラウザ終了時
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            // その他のセキュアなセッション設定
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            
            session_name($config['session_name'] ?? 'OPWEBSITE_SESSION');
            session_start();
            
            // セッションハイジャック対策
            self::validateSession();
            
            // セッションタイムアウトチェック
            self::checkTimeout();
        }
    }
    
    /**
     * セッション検証
     */
    private static function validateSession() {
        // IPアドレスチェック（設定で有効な場合）
        if (self::get('_ip_check_enabled', true)) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $sessionIp = self::get('_session_ip');
            
            if ($sessionIp && $sessionIp !== $currentIp) {
                self::logSecurityEvent('session_ip_mismatch', [
                    'session_ip' => $sessionIp,
                    'current_ip' => $currentIp,
                    'user_id' => self::get('user_id')
                ]);
                self::destroy();
                return;
            }
            
            if (!$sessionIp) {
                self::set('_session_ip', $currentIp);
            }
        }
        
        // User-Agentチェック（簡易版）
        $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $sessionUA = self::get('_session_ua');
        
        if ($sessionUA && $sessionUA !== $currentUA) {
            self::logSecurityEvent('session_ua_mismatch', [
                'session_ua' => substr($sessionUA, 0, 100),
                'current_ua' => substr($currentUA, 0, 100),
                'user_id' => self::get('user_id')
            ]);
            self::destroy();
            return;
        }
        
        if (!$sessionUA) {
            self::set('_session_ua', $currentUA);
        }
        
        // セッション乗っ取り検証用のフィンガープリント
        $fingerprint = self::generateFingerprint();
        $storedFingerprint = self::get('_fingerprint');
        
        if ($storedFingerprint && $storedFingerprint !== $fingerprint) {
            self::logSecurityEvent('session_fingerprint_mismatch', [
                'user_id' => self::get('user_id')
            ]);
            self::destroy();
            return;
        }
        
        if (!$storedFingerprint) {
            self::set('_fingerprint', $fingerprint);
        }
    }
    
    /**
     * フィンガープリント生成
     */
    private static function generateFingerprint() {
        $factors = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        ];
        
        return hash('sha256', implode('|', $factors));
    }
    
    /**
     * セッションタイムアウトチェック（Remember対応版）
     */
    private static function checkTimeout() {
        $lastActivity = self::get('last_activity');
        $currentTime = time();
        $isRemembered = self::get('remember_session', false);
        
        // Remember Session の場合は長いタイムアウト
        $timeout = $isRemembered ? (30 * 24 * 60 * 60) : self::$sessionTimeout; // 30日 vs 2時間
        
        if ($lastActivity) {
            $inactiveTime = $currentTime - $lastActivity;
            
            // タイムアウト
            if ($inactiveTime > $timeout) {
                self::logSecurityEvent('session_timeout', [
                    'user_id' => self::get('user_id'),
                    'inactive_time' => $inactiveTime,
                    'was_remembered' => $isRemembered,
                    'timeout_limit' => $timeout
                ]);
                self::destroy();
                return;
            }
            
            // Remember Sessionでない場合のみセッションID再生成
            if (!$isRemembered && $inactiveTime > 1800 && !self::get('_regenerated_' . floor($currentTime / 1800))) {
                session_regenerate_id(true);
                self::set('_regenerated_' . floor($currentTime / 1800), true);
            }
        }
        
        // 最終アクティビティ時刻を更新
        self::set('last_activity', $currentTime);
        
        // Remember Sessionの場合、データベースのセッション情報も更新
        if ($isRemembered) {
            self::updateRememberSession();
        }
    }
    
    /**
     * セッションタイムアウト警告が必要かチェック
     */
    public static function needsTimeoutWarning() {
        $isRemembered = self::get('remember_session', false);
        
        // Remember Sessionの場合は警告しない
        if ($isRemembered) {
            return false;
        }
        
        $lastActivity = self::get('last_activity');
        if (!$lastActivity) return false;
        
        $inactiveTime = time() - $lastActivity;
        return ($inactiveTime > (self::$sessionTimeout - self::$sessionWarning));
    }
    
    /**
     * 残りセッション時間を取得
     */
    public static function getRemainingTime() {
        $lastActivity = self::get('last_activity');
        if (!$lastActivity) return 0;
        
        $isRemembered = self::get('remember_session', false);
        $timeout = $isRemembered ? (30 * 24 * 60 * 60) : self::$sessionTimeout;
        
        $remainingTime = $timeout - (time() - $lastActivity);
        return max(0, $remainingTime);
    }
    
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        self::start();
        
        // セッションデータを完全クリア
        $_SESSION = [];
        
        // セッションクッキーを削除
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function flash($key, $value = null) {
        self::start();
        if ($value !== null) {
            $_SESSION['flash'][$key] = $value;
        } else {
            $flash = isset($_SESSION['flash'][$key]) ? $_SESSION['flash'][$key] : null;
            if (isset($_SESSION['flash'][$key])) {
                unset($_SESSION['flash'][$key]);
            }
            return $flash;
        }
    }
    
    public static function isLoggedIn() {
        return self::has('user_id') && self::has('user_type');
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            // ログイン要求をログに記録
            self::logSecurityEvent('unauthorized_access_attempt', [
                'requested_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            header('Location: /op_website/public/login');
            exit;
        }
    }
    
    public static function requireUserType($allowedTypes) {
        self::requireLogin();
        $userType = self::get('user_type');
        if (!in_array($userType, (array)$allowedTypes)) {
            self::logSecurityEvent('insufficient_privileges', [
                'user_id' => self::get('user_id'),
                'user_type' => $userType,
                'required_types' => (array)$allowedTypes,
                'requested_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            
            header('Location: /op_website/public/dashboard');
            exit;
        }
    }
    
    /**
     * セッション活動を記録
     */
    public static function recordActivity($action, $data = []) {
        if (!self::isLoggedIn()) return;
        
        $activityData = [
            'user_id' => self::get('user_id'),
            'user_type' => self::get('user_type'),
            'action' => $action,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log('[USER_ACTIVITY] ' . json_encode($activityData, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * セキュリティイベントログ
     */
    private static function logSecurityEvent($event, $data = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'data' => $data,
            'session_id' => session_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        error_log('[SESSION_SECURITY] ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * セッションセキュリティ設定
     */
    public static function configureSecuritySettings($options = []) {
        self::$sessionTimeout = $options['timeout'] ?? 7200;
        self::$sessionWarning = $options['warning'] ?? 600;
        
        if (isset($options['ip_check'])) {
            self::set('_ip_check_enabled', $options['ip_check']);
        }
    }

    
    /**
     * Remember Sessionの更新
     */
    private static function updateRememberSession() {
        try {
            $userId = self::get('user_id');
            if (!$userId) return;
            
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "UPDATE user_sessions 
                    SET last_activity = NOW() 
                    WHERE user_id = :user_id 
                    AND session_id = :session_id 
                    AND is_active = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'session_id' => session_id()
            ]);
            
        } catch (Exception $e) {
            error_log('Failed to update remember session: ' . $e->getMessage());
        }
    }
    
    /**
     * Remember Tokenによる自動ログイン機能（セキュリティ強化版）
     */
    public static function checkAutoLogin() {
        // 既にログインしている場合はスキップ
        if (self::isLoggedIn()) {
            return false;
        }
        
        // Remember Tokenクッキーが存在しない場合はスキップ
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $cookieValue = $_COOKIE['remember_token'];
            $parts = explode(':', $cookieValue, 2);
            
            // フォーマット検証
            if (count($parts) !== 2) {
                self::logSecurityEvent('auto_login_failed', [
                    'reason' => 'invalid_token_format',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                return false;
            }
            
            list($selector, $token) = $parts;
            $tokenHash = hash('sha256', $token);
            
            // データベースでトークンを検証
            $sql = "SELECT us.user_id, us.expires_at, us.ip_address as stored_ip,
                        u.login_id, u.name, u.user_type, u.company_id, u.is_active
                    FROM user_sessions us
                    JOIN users u ON us.user_id = u.id
                    WHERE us.session_id LIKE :selector
                    AND us.fingerprint = :token_hash
                    AND us.is_active = 1
                    AND us.expires_at > NOW()
                    AND u.is_active = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'selector' => $selector . ':%',
                'token_hash' => $tokenHash
            ]);
            $sessionData = $stmt->fetch();
            
            if ($sessionData) {
                // IPアドレスの変更をチェック（オプション：厳格にする場合）
                $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                // IPチェックは環境によって調整が必要（モバイルネットワークなど）
                
                // 自動ログイン成功
                session_regenerate_id(true); // セッション固定攻撃対策
                
                self::set('user_id', $sessionData['user_id']);
                self::set('user_type', $sessionData['user_type']);
                self::set('user_name', $sessionData['name']);
                self::set('login_time', time());
                self::set('last_activity', time());
                self::set('remember_session', true);
                self::set('auto_login', true);
                
                if ($sessionData['user_type'] === 'company') {
                    self::set('company_id', $sessionData['company_id']);
                }
                
                // トークンをローテーション（使用後は新しいトークンを発行）
                self::rotateRememberToken($sessionData['user_id'], $selector);
                
                // ログに記録
                self::logSecurityEvent('auto_login_success', [
                    'user_id' => $sessionData['user_id'],
                    'user_type' => $sessionData['user_type'],
                    'ip' => $currentIp
                ]);
                
                // 最終ログイン時刻を更新
                require_once __DIR__ . '/../models/User.php';
                $userModel = new User();
                $userModel->updateLastLogin($sessionData['user_id']);
                
                return true;
            } else {
                // 無効なトークンの場合はクッキーを削除
                setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                
                self::logSecurityEvent('auto_login_failed', [
                    'reason' => 'invalid_token',
                    'selector' => substr($selector, 0, 8) . '...',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Auto login check failed: ' . $e->getMessage());
            // エラー時はクッキーを削除
            setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        }
        
        return false;
    }

    /**
     * Remember Tokenのローテーション
     */
    private static function rotateRememberToken($userId, $oldSelector) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 新しいトークンを生成
            $newToken = bin2hex(random_bytes(32));
            $newTokenHash = hash('sha256', $newToken);
            $newSelector = bin2hex(random_bytes(16));
            
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
            
            // 古いトークンを削除
            $sql = "DELETE FROM user_sessions 
                    WHERE session_id LIKE :old_selector";
            $stmt = $db->prepare($sql);
            $stmt->execute(['old_selector' => $oldSelector . ':%']);
            
            // 新しいトークンを保存
            $sql = "INSERT INTO user_sessions 
                    (session_id, user_id, ip_address, user_agent, fingerprint, expires_at, is_active)
                    VALUES (:session_id, :user_id, :ip_address, :user_agent, :fingerprint, :expires_at, 1)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'session_id' => $newSelector . ':' . $newTokenHash,
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'fingerprint' => $newTokenHash,
                'expires_at' => $expiresAt
            ]);
            
            // 新しいクッキーを設定
            $cookieValue = $newSelector . ':' . $newToken;
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
            
        } catch (Exception $e) {
            error_log('Failed to rotate remember token: ' . $e->getMessage());
        }
    }

    /**
     * ユーザーのアクティブなセッション一覧を取得
     */
    public static function getUserActiveSessions($userId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT 
                        session_id,
                        ip_address,
                        user_agent,
                        created_at,
                        last_activity,
                        expires_at,
                        CASE WHEN fingerprint IS NOT NULL THEN 1 ELSE 0 END as is_remember_session
                    FROM user_sessions
                    WHERE user_id = :user_id
                    AND is_active = 1
                    AND expires_at > NOW()
                    ORDER BY last_activity DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Failed to get user sessions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 特定のセッションを無効化
     */
    public static function revokeSession($userId, $sessionId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "UPDATE user_sessions 
                    SET is_active = 0 
                    WHERE user_id = :user_id 
                    AND session_id = :session_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);
            
            self::logSecurityEvent('session_revoked', [
                'user_id' => $userId,
                'revoked_session_id' => substr($sessionId, 0, 16) . '...'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Failed to revoke session: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーの全セッションを無効化（現在のセッションを除く）
     */
    public static function revokeAllOtherSessions($userId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $currentSessionId = session_id();
            
            $sql = "UPDATE user_sessions 
                    SET is_active = 0 
                    WHERE user_id = :user_id 
                    AND session_id != :current_session_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'current_session_id' => $currentSessionId
            ]);
            
            self::logSecurityEvent('all_other_sessions_revoked', [
                'user_id' => $userId,
                'revoked_count' => $stmt->rowCount()
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Failed to revoke all other sessions: ' . $e->getMessage());
            return false;
        }
    }
}
?>