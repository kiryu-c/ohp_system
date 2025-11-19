<?php
// app/controllers/EmailLogController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../core/Database.php';

class EmailLogController extends BaseController
{
    private $db;

    public function __construct()
    {
        // 管理者権限チェック
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . base_url('dashboard'));
            exit;
        }

        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * メール送信ログ一覧画面を表示
     */
    public function index()
    {
        // CSVエクスポート処理
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv();
            return;
        }

        // 検索パラメータ取得
        $emailType = filter_input(INPUT_GET, 'email_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $recipientEmail = filter_input(INPUT_GET, 'recipient_email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $startDate = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $endDate = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT) ?: 50;
        
        // 1ページあたりの件数は20〜100件の範囲に制限
        if ($perPage < 20) $perPage = 20;
        if ($perPage > 100) $perPage = 100;

        // ソートパラメータ取得
        $sortColumn = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'created_at';
        $sortOrder = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'desc';
        
        // 許可されたソートカラムのみ
        $allowedSortColumns = ['id', 'created_at', 'sent_at', 'email_type', 'status', 'recipient_email'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }
        
        // ソート順序は asc または desc のみ
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // SQL構築
        $conditions = [];
        $params = [];

        if ($emailType) {
            $conditions[] = "el.email_type = ?";
            $params[] = $emailType;
        }

        if ($status) {
            $conditions[] = "el.status = ?";
            $params[] = $status;
        }

        if ($recipientEmail) {
            $conditions[] = "el.recipient_email LIKE ?";
            $params[] = "%{$recipientEmail}%";
        }

        if ($startDate) {
            $conditions[] = "el.created_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        }

        if ($endDate) {
            $conditions[] = "el.created_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }

        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // 総件数取得
        $countSql = "SELECT COUNT(*) as total FROM email_logs el {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // ページネーション計算
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        // ログ取得
        $sql = "
            SELECT 
                el.*,
                u_recipient.name as recipient_user_name,
                u_created.name as created_by_name
            FROM email_logs el
            LEFT JOIN users u_recipient ON el.recipient_user_id = u_recipient.id
            LEFT JOIN users u_created ON el.created_by = u_created.id
            {$whereClause}
            ORDER BY el.{$sortColumn} {$sortOrder}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 統計情報取得
        $stats = $this->getEmailStatistics();

        ob_start();
        require __DIR__ . '/../views/email_logs/index.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../views/layouts/base.php';
    }

    /**
     * メール送信ログ詳細画面を表示
     */
    public function detail()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $_SESSION['error'] = '無効なログIDです';
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        $sql = "
            SELECT 
                el.*,
                u_recipient.name as recipient_user_name,
                u_recipient.login_id as recipient_login_id,
                u_created.name as created_by_name,
                u_created.login_id as created_by_login_id
            FROM email_logs el
            LEFT JOIN users u_recipient ON el.recipient_user_id = u_recipient.id
            LEFT JOIN users u_created ON el.created_by = u_created.id
            WHERE el.id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) {
            $_SESSION['error'] = 'ログが見つかりません';
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        // メタデータをデコード
        if ($log['metadata']) {
            $log['metadata_decoded'] = json_decode($log['metadata'], true);
        }

        ob_start();
        require __DIR__ . '/../views/email_logs/detail.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../views/layouts/base.php';
    }

    /**
     * メール送信統計情報を取得
     */
    private function getEmailStatistics()
    {
        // 全体統計
        $sql = "
            SELECT 
                email_type,
                status,
                COUNT(*) as count
            FROM email_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY email_type, status
        ";
        $stmt = $this->db->query($sql);
        $rawStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 統計データ整形
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'by_type' => [
                'password_reset' => ['success' => 0, 'failed' => 0],
                'login_invitation' => ['success' => 0, 'failed' => 0],
                'notification' => ['success' => 0, 'failed' => 0],
                'other' => ['success' => 0, 'failed' => 0],
            ]
        ];

        foreach ($rawStats as $row) {
            $stats['total'] += $row['count'];
            $stats[$row['status']] += $row['count'];
            $stats['by_type'][$row['email_type']][$row['status']] = $row['count'];
        }

        return $stats;
    }

    /**
     * ログを削除（古いログの一括削除など）
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        // CSRF対策
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = '不正なリクエストです';
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $_SESSION['error'] = '無効なログIDです';
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        $stmt = $this->db->prepare("DELETE FROM email_logs WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = 'ログを削除しました';
        header('Location: ' . base_url('email-logs'));
        exit;
    }

    /**
     * 古いログの一括削除
     */
    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        // CSRF対策
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = '不正なリクエストです';
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        $months = filter_input(INPUT_POST, 'months', FILTER_VALIDATE_INT);

        if (!$months || $months < 1 || $months > 24) {
            $_SESSION['error'] = '有効な期間を指定してください(1〜24ヶ月)';
            header('Location: ' . base_url('email-logs'));
            exit;
        }

        $stmt = $this->db->prepare("
            DELETE FROM email_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MONTH)
        ");
        $stmt->execute([$months]);
        $deletedCount = $stmt->rowCount();

        $_SESSION['success'] = "{$deletedCount}件のログを削除しました";
        header('Location: ' . base_url('email-logs'));
        exit;
    }

    /**
     * CSVエクスポート
     */
    public function exportCsv()
    {
        // 検索パラメータ取得（index()と同じ条件）
        $emailType = filter_input(INPUT_GET, 'email_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $recipientEmail = filter_input(INPUT_GET, 'recipient_email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $startDate = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $endDate = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // SQL構築
        $conditions = [];
        $params = [];

        if ($emailType) {
            $conditions[] = "el.email_type = ?";
            $params[] = $emailType;
        }

        if ($status) {
            $conditions[] = "el.status = ?";
            $params[] = $status;
        }

        if ($recipientEmail) {
            $conditions[] = "el.recipient_email LIKE ?";
            $params[] = "%{$recipientEmail}%";
        }

        if ($startDate) {
            $conditions[] = "el.created_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        }

        if ($endDate) {
            $conditions[] = "el.created_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }

        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // ログ取得
        $sql = "
            SELECT 
                el.id,
                el.email_type,
                el.sender_email,
                el.sender_name,
                el.recipient_email,
                el.recipient_name,
                u_recipient.name as recipient_user_name,
                el.subject,
                el.status,
                el.error_message,
                el.sent_at,
                el.created_at,
                el.metadata,
                u_created.name as created_by_name
            FROM email_logs el
            LEFT JOIN users u_recipient ON el.recipient_user_id = u_recipient.id
            LEFT JOIN users u_created ON el.created_by = u_created.id
            {$whereClause}
            ORDER BY el.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // CSV出力
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="email_logs_' . date('YmdHis') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM追加（Excel対応）
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // ヘッダー行
        fputcsv($output, [
            'ID',
            'メール種別',
            '送信者メール',
            '送信者名',
            '受信者メール',
            '受信者名',
            '受信者ユーザー名',
            '件名',
            'ステータス',
            'エラーメッセージ',
            '送信日時',
            'ログ記録日時',
            '実行者',
            '送信者(管理者)名'
        ]);

        // データ行
        foreach ($logs as $log) {
            // メタデータから送信者情報を取得
            $metadata = $log['metadata'] ? json_decode($log['metadata'], true) : [];
            $sentByAdminName = $metadata['sent_by_admin_name'] ?? $metadata['sent_by_user_name'] ?? '';
            
            fputcsv($output, [
                $log['id'],
                $this->getEmailTypeLabel($log['email_type']),
                $log['sender_email'],
                $log['sender_name'],
                $log['recipient_email'],
                $log['recipient_name'],
                $log['recipient_user_name'],
                $log['subject'],
                $this->getStatusLabel($log['status']),
                $log['error_message'],
                $log['sent_at'],
                $log['created_at'],
                $log['created_by_name'] ?: 'システム',
                $sentByAdminName
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * メール種別のラベルを取得
     */
    private function getEmailTypeLabel($type)
    {
        $labels = [
            'password_reset' => 'パスワード再設定',
            'login_invitation' => 'ログイン招待',
            'notification' => '通知',
            'other' => 'その他'
        ];
        return $labels[$type] ?? $type;
    }

    /**
     * ステータスのラベルを取得
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'success' => '成功',
            'failed' => '失敗',
            'pending' => '保留中'
        ];
        return $labels[$status] ?? $status;
    }
}