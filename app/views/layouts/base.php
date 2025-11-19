<?php
// app/views/layouts/base.php

// エラー報告を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッションを開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = [
    'app_name' => '産業医役務管理システム',
    'app_version' => '1.0.0',
    'app_url' => 'http://localhost/op_website/public'
];

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
$userType = $_SESSION['user_type'] ?? '';
$userName = $_SESSION['user_name'] ?? 'ユーザー';
$userId = $_SESSION['user_id'] ?? null;

$flash_messages = [];
if (isset($_SESSION['flash'])) {
    $flash_messages = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?><!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['app_name'] ?? 'システム', ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- カスタムCSS -->
    <link rel="stylesheet" href="/op_website/public/assets/css/style.css?v=<?= time() ?>">
    
    <style>
        /* ユーザータイプ別ヘッダー・フッタースタイル */
        .user-type-admin .navbar,
        .user-type-admin footer {
            background-color: #212529 !important; /* 黒背景 */
            color: white !important;
        }

        .user-type-admin .navbar-brand,
        .user-type-admin .navbar-nav .nav-link,
        .user-type-admin .navbar-text,
        .user-type-admin footer {
            color: white !important;
        }

        .user-type-admin .navbar-nav .nav-link:hover {
            color: #adb5bd !important;
        }

        .user-type-company .navbar,
        .user-type-company footer {
            background-color: #198754 !important; /* 緑背景 */
            color: white !important;
        }

        .user-type-company .navbar-brand,
        .user-type-company .navbar-nav .nav-link,
        .user-type-company .navbar-text,
        .user-type-company footer {
            color: white !important;
        }

        .user-type-company .navbar-nav .nav-link:hover {
            color: #d4edda !important;
        }

        .user-type-doctor .navbar,
        .user-type-doctor footer {
            background-color: #b3d9ff !important; /* 薄い水色背景 */
            color: #212529 !important;
        }

        .user-type-doctor .navbar-brand,
        .user-type-doctor .navbar-nav .nav-link,
        .user-type-doctor .navbar-text,
        .user-type-doctor footer {
            color: #212529 !important; /* 黒文字 */
        }

        .user-type-doctor .navbar-nav .nav-link:hover {
            color: #495057 !important;
        }

        /* ドロップダウンメニューのスタイル */
        .dropdown-menu {
            background-color: white !important;
            border: 1px solid #dee2e6;
        }

        .dropdown-item {
            color: #212529 !important;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa !important;
            color: #16181b !important;
        }

        /* 基本レイアウト */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
        }

        footer {
            margin-top: auto;
        }
    </style>
</head>
<body class="user-type-<?= $userType ?>">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/op_website/public/dashboard">
                <i class="fas fa-user-md me-2"></i>
                <?= htmlspecialchars($config['app_name'] ?? 'システム', ENT_QUOTES, 'UTF-8') ?>
            </a>
            
            <?php if ($isLoggedIn): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/op_website/public/dashboard">
                                <i class="fas fa-home me-1"></i>ダッシュボード
                            </a>
                        </li>
                        
                        <?php if ($userType === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/users">
                                    <i class="fas fa-users me-1"></i>ユーザー管理
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/companies">
                                    <i class="fas fa-building me-1"></i>企業管理
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/contracts">
                                    <i class="fas fa-file-contract me-1"></i>契約管理
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/service_records/admin">
                                    <i class="fas fa-clipboard-list me-1"></i>役務記録管理
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/travel_expenses">
                                    <i class="fas fa-clipboard-list me-1"></i>交通費一覧
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/closing">
                                    <i class="fas fa-calculator me-1"></i>月次締め処理
                                </a>
                            </li>
                        <?php elseif ($userType === 'company'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/service_records">
                                    <i class="fas fa-clipboard-list me-1"></i>役務記録
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/contracts">
                                    <i class="fas fa-file-contract me-1"></i>契約一覧
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/closing">
                                    <i class="fas fa-calculator me-1"></i>月次締め処理
                                </a>
                            </li>
                        <?php elseif ($userType === 'doctor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/service_records">
                                    <i class="fas fa-clipboard-list me-1"></i>役務記録
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/travel_expenses">
                                    <i class="fas fa-clipboard-list me-1"></i>交通費一覧
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/contracts">
                                    <i class="fas fa-file-contract me-1"></i>契約一覧
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/op_website/public/closing">
                                    <i class="fas fa-calculator me-1"></i>月次締め処理
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="navbar-nav">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                                <span class="badge bg-secondary ms-1">
                                    <?php
                                    switch($userType) {
                                        case 'doctor': echo '産業医'; break;
                                        case 'company': echo '企業'; break;
                                        case 'admin': echo '管理者'; break;
                                        default: echo htmlspecialchars($userType, ENT_QUOTES, 'UTF-8');
                                    }
                                    ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if ($userType === 'doctor' && $userId): ?>
                                    <li><a class="dropdown-item" href="/op_website/public/users/<?= $userId ?>/edit">
                                        <i class="fas fa-user-edit me-2"></i>ユーザー情報編集
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= base_url('settings/security') ?>">
                                    <i class="fas fa-shield-alt me-2"></i>セキュリティ設定
                                </a></li>
                                <li><a class="dropdown-item" href="<?= base_url('password/change') ?>">
                                    <i class="fas fa-key me-2"></i>パスワード変更
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/op_website/public/logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>ログアウト
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="main-content">
        <div class="container mt-4">
            <!-- フラッシュメッセージ -->
            <?php if (!empty($flash_messages)): ?>
                <?php foreach ($flash_messages as $type => $message): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- メインコンテンツ -->
            <?= $content ?? '<h1>ダッシュボード</h1><p>システムが正常に動作しています。</p>' ?>
        </div>
    </main>

    <footer class="bg-light py-3 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <small>
                        &copy; <?= date('Y') ?> <?= htmlspecialchars($config['app_name'] ?? 'システム', ENT_QUOTES, 'UTF-8') ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small>
                        ログイン中: <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($userType): ?>
                            (<?php
                            switch($userType) {
                                case 'doctor': echo '産業医'; break;
                                case 'company': echo '企業'; break;
                                case 'admin': echo '管理者'; break;
                                default: echo htmlspecialchars($userType, ENT_QUOTES, 'UTF-8');
                            }
                            ?>)
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>