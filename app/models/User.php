<?php
// app/models/User.php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    
    public function authenticate($loginId, $password) {
        $sql = "SELECT * FROM {$this->table} WHERE login_id = :login_id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['login_id' => $loginId]);
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }
    
    public function updateUser($id, $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->update($id, $data);
    }
    
    public function findByType($userType) {
        return $this->findAll(['user_type' => $userType]);
    }
    
    public function findByCompany($companyId) {
        return $this->findAll(['company_id' => $companyId]);
    }
    
    public function findDoctors() {
        return $this->findByType('doctor');
    }
    
    public function findCompanyUsers() {
        return $this->findByType('company');
    }
    
    public function isLoginIdExists($loginId, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE login_id = :login_id";
        $params = ['login_id' => $loginId];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
    }
    
    public function findWithCompany() {
        $sql = "SELECT u.*, c.name as company_name 
                FROM {$this->table} u 
                LEFT JOIN companies c ON u.company_id = c.id 
                WHERE u.is_active = 1
                ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * ユーザーに紐づく拠点を取得
     */
    public function getUserBranches($userId) {
        $sql = "SELECT b.*, c.name as company_name, ubm.created_at as mapped_at
                FROM user_branch_mappings ubm
                INNER JOIN branches b ON ubm.branch_id = b.id
                INNER JOIN companies c ON b.company_id = c.id
                WHERE ubm.user_id = :user_id 
                AND ubm.is_active = 1 
                AND b.is_active = 1 
                AND c.is_active = 1
                ORDER BY c.name, b.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll();
    }

    /**
     * 指定企業の拠点一覧を取得
     */
    public function getCompanyBranches($companyId) {
        $sql = "SELECT id, name, address, phone, email 
                FROM branches 
                WHERE company_id = :company_id AND is_active = 1
                ORDER BY name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);
        
        return $stmt->fetchAll();
    }

    /**
     * ユーザーの拠点紐づけを更新
     */
    public function updateUserBranches($userId, $branchIds, $db = null) {
        // データベース接続を取得（外部から渡されない場合）
        if ($db === null) {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            $manageTransaction = true;
        } else {
            $manageTransaction = false;
        }
        
        try {
            if ($manageTransaction) {
                $db->beginTransaction();
            }
            
            // 既存の紐づけを無効化
            $sql = "UPDATE user_branch_mappings 
                    SET is_active = 0, updated_at = NOW() 
                    WHERE user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            // 新しい紐づけを追加
            if (!empty($branchIds)) {
                $sql = "INSERT INTO user_branch_mappings (user_id, branch_id, created_at, updated_at, is_active) 
                        VALUES (:user_id, :branch_id, NOW(), NOW(), 1)
                        ON DUPLICATE KEY UPDATE 
                        is_active = 1, updated_at = NOW()";
                
                $stmt = $db->prepare($sql);
                
                foreach ($branchIds as $branchId) {
                    $stmt->execute([
                        'user_id' => $userId,
                        'branch_id' => (int)$branchId
                    ]);
                }
            }
            
            if ($manageTransaction) {
                $db->commit();
            }
            return true;
            
        } catch (Exception $e) {
            if ($manageTransaction) {
                $db->rollback();
            }
            error_log('User branch mapping update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 企業ユーザー作成時のデフォルト拠点紐づけ
     */
    public function createCompanyUserWithBranches($userData, $branchIds = []) {
        // データベース接続を取得
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            // ユーザー作成
            $userId = $this->createUser($userData);
            if (!$userId) {
                throw new Exception('ユーザー作成に失敗しました');
            }
            
            // 拠点紐づけ（同じトランザクション内で実行）
            if (!empty($branchIds)) {
                if (!$this->updateUserBranches($userId, $branchIds, $db)) {
                    throw new Exception('拠点紐づけに失敗しました');
                }
            }
            
            $db->commit();
            return $userId;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('Company user creation with branches failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーが特定の拠点にアクセス権があるかチェック
     */
    public function hasUserBranchAccess($userId, $branchId) {
        $sql = "SELECT COUNT(*) as count
                FROM user_branch_mappings ubm
                INNER JOIN branches b ON ubm.branch_id = b.id
                WHERE ubm.user_id = :user_id 
                AND ubm.branch_id = :branch_id
                AND ubm.is_active = 1
                AND b.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId, 'branch_id' => $branchId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }

    /**
     * ユーザーが所属する企業IDリストを取得
     */
    public function getUserCompanyIds($userId) {
        $sql = "SELECT DISTINCT c.id
                FROM user_branch_mappings ubm
                INNER JOIN branches b ON ubm.branch_id = b.id
                INNER JOIN companies c ON b.company_id = c.id
                WHERE ubm.user_id = :user_id 
                AND ubm.is_active = 1 
                AND b.is_active = 1 
                AND c.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return array_column($stmt->fetchAll(), 'id');
    }

    /**
     * ユーザー詳細情報（拠点情報含む）を取得
     */
    public function findByIdWithBranches($userId) {
        $user = $this->findById($userId);
        if (!$user) {
            return null;
        }
        
        $user['branches'] = $this->getUserBranches($userId);
        $user['company_ids'] = $this->getUserCompanyIds($userId);
        
        return $user;
    }

    /**
     * アカウントロック状態をチェック
     */
    public function isAccountLocked($loginId) {
        $sql = "SELECT login_attempts, locked_until FROM {$this->table} 
                WHERE login_id = :login_id AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['login_id' => $loginId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // ロック期限が設定されていて、まだ期限内の場合
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return true;
        }
        
        // ログイン試行回数が上限を超えている場合
        $maxAttempts = 5;
        if ($user['login_attempts'] >= $maxAttempts) {
            // 自動的にロック時間を設定（30分）
            $this->lockAccount($loginId, 30);
            return true;
        }
        
        return false;
    }
    
    /**
     * アカウントをロック
     */
    public function lockAccount($loginId, $minutes = 30) {
        $lockedUntil = date('Y-m-d H:i:s', time() + ($minutes * 60));
        
        $sql = "UPDATE {$this->table} 
                SET locked_until = :locked_until 
                WHERE login_id = :login_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'locked_until' => $lockedUntil,
            'login_id' => $loginId
        ]);
    }
    
    /**
     * ログイン試行回数を増加
     */
    public function incrementLoginAttempts($loginId) {
        $sql = "UPDATE {$this->table} 
                SET login_attempts = login_attempts + 1 
                WHERE login_id = :login_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['login_id' => $loginId]);
    }
    
    /**
     * ログイン試行回数をリセット
     */
    public function resetLoginAttempts($userId) {
        $sql = "UPDATE {$this->table} 
                SET login_attempts = 0, locked_until = NULL 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['user_id' => $userId]);
    }
    
    /**
     * 最終ログイン時刻を更新
     */
    public function updateLastLogin($userId) {
        $sql = "UPDATE {$this->table} 
                SET last_login_at = NOW() 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['user_id' => $userId]);
    }
    
    /**
     * 強化されたパスワード検証
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        // 最小長チェック
        if (strlen($password) < 8) {
            $errors[] = 'パスワードは8文字以上で入力してください。';
        }
        
        // 文字種チェック
        if (!preg_match('/[a-zA-Z]/', $password)) {
            $errors[] = 'パスワードにアルファベットを含めてください。';
        }
                
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'パスワードに数字を含めてください。';
        }
        
        // よくあるパスワードチェック
        $commonPasswords = [
            'password', '12345678', 'qwerty123', 'abc12345',
            'password123', 'admin123', 'user1234', '11111111'
        ];
        
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'より安全なパスワードを設定してください。';
        }
        
        return $errors;
    }
    
    /**
     * セキュアなパスワードハッシュ化
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    /**
     * パスワード変更（履歴チェック付き）
     */
    public function changePasswordSecure($userId, $currentPassword, $newPassword) {
        // 現在のパスワード確認
        $user = $this->findById($userId);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => '現在のパスワードが間違っています。'];
        }
        
        // 新しいパスワードの強度チェック
        $strengthErrors = $this->validatePasswordStrength($newPassword);
        if (!empty($strengthErrors)) {
            return ['success' => false, 'message' => implode('<br>', $strengthErrors)];
        }
        
        // パスワード履歴チェック（過去3回分）
        if ($this->isPasswordRecentlyUsed($userId, $newPassword)) {
            return ['success' => false, 'message' => '過去に使用したパスワードは使用できません。'];
        }
        
        try {
            $this->db->beginTransaction();
            
            // 新しいパスワードを設定
            $hashedPassword = $this->hashPassword($newPassword);
            $sql = "UPDATE {$this->table} SET password = :password WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['password' => $hashedPassword, 'user_id' => $userId]);
            
            // パスワード履歴に追加
            $this->addPasswordHistory($userId, $hashedPassword);
            
            $this->db->commit();
            return ['success' => true, 'message' => 'パスワードを変更しました。'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Password change failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'パスワード変更中にエラーが発生しました。'];
        }
    }
    
    /**
     * 過去のパスワードと重複チェック
     */
    private function isPasswordRecentlyUsed($userId, $newPassword) {
        // パスワード履歴テーブルが必要（後述のSQL文で作成）
        $sql = "SELECT password_hash FROM password_history 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT 3";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $histories = $stmt->fetchAll();
            
            foreach ($histories as $history) {
                if (password_verify($newPassword, $history['password_hash'])) {
                    return true;
                }
            }
        } catch (Exception $e) {
            // パスワード履歴テーブルが存在しない場合は無視
            error_log('Password history check failed: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * パスワード履歴を追加
     */
    private function addPasswordHistory($userId, $hashedPassword) {
        $sql = "INSERT INTO password_history (user_id, password_hash, created_at) 
                VALUES (:user_id, :password_hash, NOW())";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'password_hash' => $hashedPassword
            ]);
            
            // 古い履歴を削除（最新5件のみ保持）
            $cleanupSql = "DELETE FROM password_history 
                          WHERE user_id = :user_id 
                          AND id NOT IN (
                              SELECT id FROM (
                                  SELECT id FROM password_history 
                                  WHERE user_id = :user_id 
                                  ORDER BY created_at DESC 
                                  LIMIT 5
                              ) AS recent
                          )";
            
            $stmt = $this->db->prepare($cleanupSql);
            $stmt->execute(['user_id' => $userId]);
            
        } catch (Exception $e) {
            error_log('Password history addition failed: ' . $e->getMessage());
        }
    }
}
?>