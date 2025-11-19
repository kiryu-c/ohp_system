<?php
// app/views/password/reset.php
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード再設定</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .info {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>新しいパスワードを設定</h2>
        
        <?php 
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['error'])): 
        ?>
            <div class="error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form method="POST" action="/password-update">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
            
            <div class="form-group">
                <label for="password">新しいパスワード</label>
                <input type="password" name="password" id="password" required minlength="8">
                <div class="info">※アルファベットと数字を含めて8文字以上で入力してください</div>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">パスワード（確認）</label>
                <input type="password" name="password_confirm" id="password_confirm" required minlength="8">
            </div>
            
            <button type="submit">パスワードを更新</button>
        </form>
    </div>
</body>
</html>