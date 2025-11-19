<?php
// app/core/Mailer.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mailer;
    private $config;
    private $db;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/mail.php';
        $this->mailer = new PHPMailer(true);
        $this->db = Database::getInstance()->getConnection();
        $this->configure();
    }

    private function configure()
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['username'];
        $this->mailer->Password = $this->config['password'];
        $this->mailer->SMTPSecure = $this->config['encryption'];
        $this->mailer->Port = $this->config['port'];
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
    }

    /**
     * メール送信ログを記録
     * 
     * @param string $emailType メール種別
     * @param string $recipientEmail 受信者メールアドレス
     * @param string $recipientName 受信者名
     * @param int|null $recipientUserId 受信者ユーザーID
     * @param string $subject 件名
     * @param string|null $bodyHtml HTML本文
     * @param string|null $bodyText テキスト本文
     * @param bool $success 送信成功フラグ
     * @param string|null $errorMessage エラーメッセージ
     * @param array|null $metadata 追加メタデータ
     * @return int 挿入されたログID
     */
    private function logEmail(
        $emailType,
        $recipientEmail,
        $recipientName,
        $recipientUserId,
        $subject,
        $bodyHtml,
        $bodyText,
        $success,
        $errorMessage = null,
        $metadata = null
    ) {
        try {
            $status = $success ? 'success' : 'failed';
            $sentAt = $success ? date('Y-m-d H:i:s') : null;
            $createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $this->db->prepare("
                INSERT INTO email_logs (
                    email_type,
                    sender_email,
                    sender_name,
                    recipient_email,
                    recipient_name,
                    recipient_user_id,
                    subject,
                    body_html,
                    body_text,
                    status,
                    error_message,
                    sent_at,
                    created_by,
                    metadata
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $emailType,
                $this->config['from_email'],
                $this->config['from_name'],
                $recipientEmail,
                $recipientName,
                $recipientUserId,
                $subject,
                $bodyHtml,
                $bodyText,
                $status,
                $errorMessage,
                $sentAt,
                $createdBy,
                $metadataJson
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("メール送信ログ記録エラー: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * パスワード再設定メールを送信
     */
    public function sendPasswordResetEmail($email, $token)
    {
        $subject = 'パスワード再設定のご案内';
        $resetUrl = $this->getResetUrl($token);
        
        $bodyHtml = $this->getPasswordResetEmailTemplate($resetUrl);
        $bodyText = "パスワード再設定用のURLにアクセスしてください: {$resetUrl}";

        $success = false;
        $errorMessage = null;

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $bodyHtml;
            $this->mailer->AltBody = $bodyText;

            $success = $this->mailer->send();
        } catch (Exception $e) {
            $errorMessage = $this->mailer->ErrorInfo;
            error_log("メール送信エラー: {$errorMessage}");
        }

        // メタデータに送信者情報を追加
        $metadata = ['token' => substr($token, 0, 10) . '...']; // トークン全体は記録しない
        
        // セッションから送信者の情報を取得(管理者が代理で送信する場合)
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
            $metadata['sent_by_user_id'] = $_SESSION['user_id'];
            $metadata['sent_by_user_name'] = $_SESSION['user_name'];
        }

        // ログ記録
        $this->logEmail(
            'password_reset',
            $email,
            null,
            $this->getUserIdByEmail($email),
            $subject,
            $bodyHtml,
            $bodyText,
            $success,
            $errorMessage,
            $metadata
        );

        return $success;
    }

    private function getResetUrl($token)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return "{$protocol}://{$host}/password-reset?token={$token}";
    }

    private function getPasswordResetEmailTemplate($resetUrl)
    {
        return "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>パスワード再設定のご案内</h2>
                <p>パスワード再設定のリクエストを受け付けました。</p>
                <p>下記のリンクをクリックして、新しいパスワードを設定してください。</p>
                <p><a href='{$resetUrl}' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>パスワードを再設定する</a></p>
                <p>このリンクは1時間有効です。</p>
                <p>もしこのリクエストに心当たりがない場合は、このメールを無視してください。</p>
                <hr>
                <p style='font-size: 12px; color: #666;'>このメールは自動送信されています。返信はできません。</p>
            </body>
            </html>
        ";
    }

    /**
     * ログイン招待メールを送信
     */
    public function sendLoginInvitationEmail($email, $userName, $loginId, $temporaryPassword, $userId = null)
    {
        $subject = '【重要】産業医役務管理システム ログインのご案内';
        $loginUrl = $this->getLoginUrl();
        
        $bodyHtml = $this->getLoginInvitationEmailTemplate(
            $userName,
            $loginId,
            $temporaryPassword,
            $loginUrl
        );
        $bodyText = $this->getLoginInvitationEmailAltBody(
            $userName,
            $loginId,
            $temporaryPassword,
            $loginUrl
        );

        $success = false;
        $errorMessage = null;

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $bodyHtml;
            $this->mailer->AltBody = $bodyText;

            $success = $this->mailer->send();
        } catch (Exception $e) {
            $errorMessage = $this->mailer->ErrorInfo;
            error_log("メール送信エラー: {$errorMessage}");
        }

        // メタデータに送信者(管理者)情報を追加
        $metadata = ['login_id' => $loginId];
        
        // セッションから送信者(管理者)の情報を取得
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
            $metadata['sent_by_admin_id'] = $_SESSION['user_id'];
            $metadata['sent_by_admin_name'] = $_SESSION['user_name'];
        }

        // ログ記録
        $this->logEmail(
            'login_invitation',
            $email,
            $userName,
            $userId ?: $this->getUserIdByEmail($email),
            $subject,
            $bodyHtml,
            $bodyText,
            $success,
            $errorMessage,
            $metadata
        );

        return $success;
    }

    /**
     * ログインURLを取得
     */
    private function getLoginUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return "{$protocol}://{$host}/login";
    }

    /**
     * ログイン招待メールのHTMLテンプレート
     */
    private function getLoginInvitationEmailTemplate($userName, $loginId, $temporaryPassword, $loginUrl)
    {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>
                        産業医役務管理システム ログインのご案内
                    </h2>
                    
                    <p>{$userName} 様</p>
                    
                    <p>産業医役務管理システムのアカウントが作成されました。<br>
                    以下のログイン情報を使用して、システムにアクセスしてください。</p>
                    
                    <div style='background-color: #f8f9fa; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #2c3e50;'>ログイン情報</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; width: 150px;'>ログインID:</td>
                                <td style='padding: 8px 0;'>{$loginId}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold;'>暫定パスワード:</td>
                                <td style='padding: 8px 0; font-family: monospace; background-color: #fff; padding: 5px; border: 1px solid #ddd;'>{$temporaryPassword}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$loginUrl}' 
                           style='display: inline-block; background-color: #3498db; color: white; 
                                  padding: 12px 30px; text-decoration: none; border-radius: 5px; 
                                  font-weight: bold; font-size: 16px;'>
                            ログイン画面へ
                        </a>
                    </div>
                    
                    <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                        <h4 style='margin-top: 0; color: #856404;'>
                            <i class='fas fa-exclamation-triangle'></i> 重要なお知らせ
                        </h4>
                        <p style='margin-bottom: 10px; color: #856404;'>
                            <strong>初回ログイン後、必ずパスワードを変更してください。</strong>
                        </p>
                        <p style='margin-bottom: 0; color: #856404; font-size: 14px;'>
                            暫定パスワードは一時的なものです。セキュリティのため、ログイン後すぐに
                            「ユーザーメニュー」→「パスワード変更」から、ご自身のパスワードに変更してください。
                        </p>
                    </div>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
                        <h4 style='color: #2c3e50;'>ログイン手順</h4>
                        <ol style='padding-left: 20px;'>
                            <li style='margin-bottom: 10px;'>上記の「ログイン画面へ」ボタンをクリック</li>
                            <li style='margin-bottom: 10px;'>ログインIDと暫定パスワードを入力</li>
                            <li style='margin-bottom: 10px;'>ログイン後、画面右上の設定メニューから「パスワード変更」を選択</li>
                            <li style='margin-bottom: 10px;'>新しいパスワードを設定(アルファベットと数字を含めて8文字以上)</li>
                        </ol>
                    </div>
                    
                    <p style='margin-top: 30px; font-size: 14px; color: #666;'>
                        ログインURL: <a href='{$loginUrl}' style='color: #3498db;'>{$loginUrl}</a>
                    </p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                    
                    <p style='font-size: 12px; color: #999;'>
                        このメールは自動送信されています。返信はできません。<br>
                        ご不明な点がございましたら、システム管理者にお問い合わせください。
                    </p>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * ログイン招待メールのテキスト版
     */
    private function getLoginInvitationEmailAltBody($userName, $loginId, $temporaryPassword, $loginUrl)
    {
        return <<<EOT
産業医役務管理システム ログインのご案内

{$userName} 様

産業医役務管理システムのアカウントが作成されました。
以下のログイン情報を使用して、システムにアクセスしてください。

【ログイン情報】
ログインID: {$loginId}
暫定パスワード: {$temporaryPassword}

ログインURL: {$loginUrl}

【重要】
初回ログイン後、必ずパスワードを変更してください。
暫定パスワードは一時的なものです。セキュリティのため、
ログイン後すぐに「設定」→「パスワード変更」から、
ご自身のパスワードに変更してください。

【ログイン手順】
1. 上記のログインURLにアクセス
2. ログインIDと暫定パスワードを入力
3. ログイン後、画面右上の設定メニューから「パスワード変更」を選択
4. 新しいパスワードを設定(アルファベットと数字を含めて8文字以上)

──────────────────────────────────
このメールは自動送信されています。返信はできません。
ご不明な点がございましたら、システム管理者にお問い合わせください。
EOT;
    }

    /**
     * メールアドレスからユーザーIDを取得
     * 
     * @param string $email メールアドレス
     * @return int|null ユーザーID
     */
    private function getUserIdByEmail($email)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : null;
        } catch (PDOException $e) {
            error_log("ユーザーID取得エラー: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 通知メールを送信（汎用）
     * 
     * @param string $email 受信者メールアドレス
     * @param string $subject 件名
     * @param string $bodyHtml HTML本文
     * @param string $bodyText テキスト本文
     * @param string|null $recipientName 受信者名
     * @param int|null $recipientUserId 受信者ユーザーID
     * @param array|null $metadata 追加メタデータ
     * @return bool 送信成功フラグ
     */
    public function sendNotificationEmail(
        $email,
        $subject,
        $bodyHtml,
        $bodyText = null,
        $recipientName = null,
        $recipientUserId = null,
        $metadata = null
    ) {
        $success = false;
        $errorMessage = null;

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $bodyHtml;
            
            if ($bodyText) {
                $this->mailer->AltBody = $bodyText;
            }

            $success = $this->mailer->send();
        } catch (Exception $e) {
            $errorMessage = $this->mailer->ErrorInfo;
            error_log("メール送信エラー: {$errorMessage}");
        }

        // ログ記録
        $this->logEmail(
            'notification',
            $email,
            $recipientName,
            $recipientUserId ?: $this->getUserIdByEmail($email),
            $subject,
            $bodyHtml,
            $bodyText,
            $success,
            $errorMessage,
            $metadata
        );

        return $success;
    }
}