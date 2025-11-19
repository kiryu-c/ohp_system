<?php
require_once __DIR__ . '/../core/Database.php';

class TwoFactorAuth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * 新しい秘密鍵を生成
     */
    public function generateSecret() {
        return $this->base32Encode(random_bytes(20));
    }
    
    /**
     * QRコード用のURLを生成
     */
    public function getQRCodeUrl($username, $secret, $issuer = '産業医役務管理システム') {
        $encodedIssuer = rawurlencode($issuer);
        $encodedUsername = rawurlencode($username);
        return "otpauth://totp/{$encodedIssuer}:{$encodedUsername}?secret={$secret}&issuer={$encodedIssuer}";
    }
    
    /**
     * TOTPコードを検証
     */
    public function verifyCode($secret, $code, $discrepancy = 1) {
        $timeSlice = floor(time() / 30);
        
        // 時間のずれを考慮して前後のコードも検証
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $timeSlice + $i);
            if ($this->timingSafeEquals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 特定時刻のTOTPコードを生成
     */
    private function getCode($secret, $timeSlice) {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * リカバリーコードを生成
     */
    public function generateRecoveryCodes($count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // 8文字のコード
        }
        return $codes;
    }
    
    /**
     * ユーザーの2FAを有効化
     */
    public function enableTwoFactor($userId, $secret, $recoveryCodes) {
        try {
            // 秘密鍵とリカバリーコードを暗号化して保存
            $encryptedSecret = $this->encrypt($secret);
            $encryptedCodes = $this->encrypt(json_encode($recoveryCodes));
            
            $sql = "UPDATE users 
                    SET two_factor_enabled = 1,
                        two_factor_secret = :secret,
                        two_factor_recovery_codes = :recovery_codes,
                        two_factor_enabled_at = NOW()
                    WHERE id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'secret' => $encryptedSecret,
                'recovery_codes' => $encryptedCodes,
                'user_id' => $userId
            ]);
        } catch (Exception $e) {
            error_log('2FA Enable Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ユーザーの2FAを無効化
     */
    public function disableTwoFactor($userId) {
        try {
            $sql = "UPDATE users 
                    SET two_factor_enabled = 0,
                        two_factor_secret = NULL,
                        two_factor_recovery_codes = NULL,
                        two_factor_enabled_at = NULL
                    WHERE id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('2FA Disable Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ユーザーの2FA情報を取得
     */
    public function getUserTwoFactorData($userId) {
        try {
            $sql = "SELECT two_factor_enabled, two_factor_secret, 
                           two_factor_recovery_codes, two_factor_enabled_at
                    FROM users 
                    WHERE id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data && $data['two_factor_secret']) {
                $data['two_factor_secret'] = $this->decrypt($data['two_factor_secret']);
                if ($data['two_factor_recovery_codes']) {
                    $data['two_factor_recovery_codes'] = json_decode(
                        $this->decrypt($data['two_factor_recovery_codes']), 
                        true
                    );
                }
            }
            
            return $data;
        } catch (Exception $e) {
            error_log('Get 2FA Data Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * リカバリーコードを検証して使用済みにする
     */
    public function verifyRecoveryCode($userId, $code) {
        try {
            $data = $this->getUserTwoFactorData($userId);
            if (!$data || empty($data['two_factor_recovery_codes'])) {
                return false;
            }
            
            $codes = $data['two_factor_recovery_codes'];
            $code = strtoupper(trim($code));
            
            $index = array_search($code, $codes);
            if ($index !== false) {
                // コードを削除
                unset($codes[$index]);
                $codes = array_values($codes); // インデックスを再構築
                
                // 更新
                $encryptedCodes = $this->encrypt(json_encode($codes));
                $sql = "UPDATE users 
                        SET two_factor_recovery_codes = :recovery_codes
                        WHERE id = :user_id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'recovery_codes' => $encryptedCodes,
                    'user_id' => $userId
                ]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Recovery Code Verify Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // ========== 暗号化関連 ==========
    
    private function encrypt($data) {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    private function decrypt($data) {
        $key = $this->getEncryptionKey();
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    private function getEncryptionKey() {
        // config/app.phpに暗号化キーを設定してください
        $config = require __DIR__ . '/../../config/app.php';
        return $config['encryption_key'] ?? 'default-key-please-change-this-in-production';
    }
    
    // ========== Base32エンコード/デコード ==========
    
    private function base32Encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;
            
            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }
        
        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 31];
        }
        
        return $output;
    }
    
    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v = ($v << 5) | strpos($alphabet, $data[$i]);
            $vbits += 5;
            
            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }
        
        return $output;
    }
    
    private function timingSafeEquals($a, $b) {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        
        if (strlen($a) !== strlen($b)) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        
        return $result === 0;
    }
}