<?php
// app/controllers/PasswordResetController.php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Mailer.php';

class PasswordResetController
{
    private $db;
    private $mailer;
    private $config;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->mailer = new Mailer();
        $this->config = require __DIR__ . '/../../config/mail.php';
    }

    // パスワード再設定リクエスト画面表示
    public function requestForm()
    {
        require __DIR__ . '/../views/password/request.php';
    }

    // パスワード再設定メール送信
    public function sendResetLink()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /password-request');
            exit;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = '有効なメールアドレスを入力してください';
            header('Location: /password-request');
            exit;
        }

        // ユーザー存在確認
        $stmt = $this->db->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // セキュリティのため、ユーザーが存在しない場合も同じメッセージを表示
        if ($user) {
            $token = $this->generateToken();
            $expiresAt = date('Y-m-d H:i:s', time() + $this->config['reset_token_expiry']);

            // トークンをDBに保存
            $stmt = $this->db->prepare("
                INSERT INTO password_resets (user_id, email, token, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $email, $token, $expiresAt]);

            // メール送信
            $this->mailer->sendPasswordResetEmail($email, $token);
        }

        $_SESSION['success'] = 'パスワード再設定用のメールを送信しました。メールをご確認ください。';
        header('Location: /login');
        exit;
    }

    // パスワード再設定画面表示
    public function resetForm()
    {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

        if (!$token || !$this->validateToken($token)) {
            $_SESSION['error'] = '無効または期限切れのリンクです';
            header('Location: /password-request');
            exit;
        }

        require __DIR__ . '/../views/password/reset.php';
    }

    // パスワード更新処理
    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /password-request');
            exit;
        }

        $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // バリデーション
        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'パスワードが一致しません';
            header("Location: /password-reset?token={$token}");
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'パスワードは8文字以上で入力してください';
            header("Location: /password-reset?token={$token}");
            exit;
        }
        if (!preg_match('/[a-zA-Z]/', $password)) {
            $_SESSION['error'] = 'パスワードにはアルファベットを含めてください';
            header("Location: /password-reset?token={$token}");
            exit;
        }
        if (!preg_match('/[0-9]/', $password)) {
            $_SESSION['error'] = 'パスワードには数字を含めてください';
            header("Location: /password-reset?token={$token}");
            exit;
        }

        // トークン検証
        $resetData = $this->validateToken($token);
        if (!$resetData) {
            $_SESSION['error'] = '無効または期限切れのリンクです';
            header('Location: /password-request');
            exit;
        }

        // パスワード更新
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $resetData['user_id']]);

        // トークンを使用済みに
        $stmt = $this->db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);

        $_SESSION['success'] = 'パスワードを再設定しました。新しいパスワードでログインしてください。';
        header('Location: /login');
        exit;
    }

    private function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    private function validateToken($token)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets 
            WHERE token = ? 
            AND used = 0 
            AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}