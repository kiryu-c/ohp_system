<?php
// app/models/ServiceDescriptionTemplate.php
require_once __DIR__ . '/BaseModel.php';

class ServiceDescriptionTemplate extends BaseModel {
    protected $table = 'service_description_templates';
    
    /**
     * 医師が使用可能なテンプレート一覧を取得
     * システム標準テンプレート + 個人テンプレート
     */
    public function getAvailableTemplates($doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT id, content, is_system_template, usage_count, last_used_at
                FROM service_description_templates 
                WHERE (doctor_id = :doctor_id OR doctor_id IS NULL)
                ORDER BY 
                    is_system_template DESC,
                    usage_count DESC,
                    last_used_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 個人テンプレートの一覧を取得（管理用）
     */
    public function getPersonalTemplates($doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT id, content, created_at, usage_count, last_used_at
                FROM service_description_templates 
                WHERE doctor_id = :doctor_id AND doctor_id IS NOT NULL
                ORDER BY usage_count DESC, last_used_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * テンプレートを作成
     */
    public function createTemplate($doctorId, $content) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 同内容のテンプレートが既に存在するかチェック
        $checkSql = "SELECT id FROM service_description_templates 
                     WHERE doctor_id = :doctor_id AND content = :content";
        
        $stmt = $db->prepare($checkSql);
        $stmt->execute([
            'doctor_id' => $doctorId,
            'content' => $content
        ]);
        
        $existing = $stmt->fetch();
        
        if ($existing) {
            // 既存テンプレートの使用回数を更新
            $updateSql = "UPDATE service_description_templates 
                         SET usage_count = usage_count + 1,
                             last_used_at = CURRENT_TIMESTAMP,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id";
            
            $stmt = $db->prepare($updateSql);
            return $stmt->execute(['id' => $existing['id']]);
        } else {
            // 新規テンプレートを作成
            $insertSql = "INSERT INTO service_description_templates 
                         (doctor_id, content, is_system_template, usage_count, last_used_at) 
                         VALUES (:doctor_id, :content, FALSE, 1, CURRENT_TIMESTAMP)";
            
            $stmt = $db->prepare($insertSql);
            return $stmt->execute([
                'doctor_id' => $doctorId,
                'content' => $content
            ]);
        }
    }
    
    /**
     * テンプレート使用時の更新
     */
    public function useTemplate($templateId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "UPDATE service_description_templates 
                SET usage_count = usage_count + 1,
                    last_used_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute(['id' => $templateId]);
    }
    
    /**
     * システムテンプレートかどうかを確認
     */
    public function isSystemTemplate($templateId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT is_system_template FROM service_description_templates WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $templateId]);
        
        $result = $stmt->fetch();
        return $result ? (bool)$result['is_system_template'] : false;
    }
    
    /**
     * 個人テンプレートを削除
     */
    public function deletePersonalTemplate($templateId, $doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "DELETE FROM service_description_templates 
                WHERE id = :id AND doctor_id = :doctor_id AND doctor_id IS NOT NULL";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'id' => $templateId,
            'doctor_id' => $doctorId
        ]);
    }
    
    /**
     * テンプレート統計情報を取得
     */
    public function getTemplateStats($doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                    COUNT(CASE WHEN doctor_id IS NULL THEN 1 END) as system_count,
                    COUNT(CASE WHEN doctor_id IS NOT NULL THEN 1 END) as personal_count,
                    COALESCE(SUM(CASE WHEN doctor_id IS NOT NULL THEN usage_count END), 0) as total_usage
                FROM service_description_templates 
                WHERE doctor_id = :doctor_id OR doctor_id IS NULL";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetch();
    }

    /**
     * 自動保存すべきかどうかを判定
     */
    public function shouldAutoSaveTemplate($content, $doctorId) {
        // 1. 文字数チェック（500文字以下）
        $length = mb_strlen($content);
        if ($length > 500) {
            return false;
        }
        
        // 2. 内容の妥当性チェック
        if (!$this->isValidContent($content)) {
            return false;
        }
        
        // 3. 既存テンプレートとの類似度チェック
        if ($this->isSimilarToExisting($content, $doctorId)) {
            return false;
        }
        
        return true;
    }

    /**
     * 内容の妥当性をチェック
     */
    private function isValidContent($content) {
        // 空白文字のみでないかチェック
        if (trim($content) === '') {
            return false;
        }
        
        return true;
    }

    /**
     * 既存テンプレートとの類似度をチェック
     */
    private function isSimilarToExisting($content, $doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 同一ユーザーの既存テンプレートを取得
        $sql = "SELECT content FROM service_description_templates 
                WHERE doctor_id = :doctor_id OR is_system_template = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        $existingTemplates = $stmt->fetchAll();
        
        foreach ($existingTemplates as $template) {
            // 完全一致の場合は類似とみなす
            if ($content === $template['content']) {
                return true;
            }
            
            $similarity = $this->calculateSimilarity($content, $template['content']);
            
            // 類似度が70%以上の場合は類似とみなす
            if ($similarity >= 0.7) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 文字列の類似度を計算（コサイン類似度）
     */
    private function calculateSimilarity($text1, $text2) {
        // n-gram（2文字）でトークン化
        $tokens1 = $this->getNgrams($text1, 2);
        $tokens2 = $this->getNgrams($text2, 2);
        
        if (empty($tokens1) || empty($tokens2)) {
            return 0;
        }
        
        // トークンの出現回数をカウント
        $vector1 = array_count_values($tokens1);
        $vector2 = array_count_values($tokens2);
        
        // 全てのユニークなトークンを取得
        $allTokens = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));
        
        // ベクトルを作成
        $v1 = [];
        $v2 = [];
        foreach ($allTokens as $token) {
            $v1[] = $vector1[$token] ?? 0;
            $v2[] = $vector2[$token] ?? 0;
        }
        
        // コサイン類似度を計算
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;
        
        for ($i = 0; $i < count($v1); $i++) {
            $dotProduct += $v1[$i] * $v2[$i];
            $norm1 += $v1[$i] * $v1[$i];
            $norm2 += $v2[$i] * $v2[$i];
        }
        
        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }
        
        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }

    /**
     * n-gramトークンを生成
     */
    private function getNgrams($text, $n) {
        $text = preg_replace('/\s+/', '', $text); // 空白を除去
        $length = mb_strlen($text);
        $ngrams = [];
        
        for ($i = 0; $i <= $length - $n; $i++) {
            $ngrams[] = mb_substr($text, $i, $n);
        }
        
        return $ngrams;
    }

    /**
     * テンプレート内容から表示用のプレビューテキストを生成
     */
    public function getPreviewText($content, $maxLength = 50) {
        $preview = preg_replace('/\s+/', ' ', trim($content));
        
        if (mb_strlen($preview) <= $maxLength) {
            return $preview;
        }
        
        return mb_substr($preview, 0, $maxLength) . '...';
    }
}
?>