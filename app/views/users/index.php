<?php
// app/views/users/index.php - ページネーション対応版

// ページネーションURL構築のヘルパー関数
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return base_url('users') . '?' . http_build_query($params);
}
?>

<!-- ヘッダー -->
<div class="header-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-users me-2"></i>ユーザー管理</h2>
        <div class="header-buttons">
            <a href="<?= base_url('users/create') ?>" class="btn btn-success">
                <i class="fas fa-user-plus me-1"></i>ユーザーを追加
            </a>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
            </a>
        </div>
    </div>
</div>

<!-- 検索・フィルター -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>フィルター条件
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= base_url('users') ?>" class="filter-form" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <label for="search" class="form-label">検索</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="氏名またはログインIDで検索">
                </div>
                <div class="col-md-2 col-6">
                    <label for="user_type" class="form-label">ユーザータイプ</label>
                    <select class="form-select" id="user_type" name="user_type">
                        <option value="">全て</option>
                        <option value="doctor" <?= ($_GET['user_type'] ?? '') === 'doctor' ? 'selected' : '' ?>>産業医</option>
                        <option value="company" <?= ($_GET['user_type'] ?? '') === 'company' ? 'selected' : '' ?>>企業ユーザー</option>
                        <option value="admin" <?= ($_GET['user_type'] ?? '') === 'admin' ? 'selected' : '' ?>>管理者</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label for="status" class="form-label">ステータス</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全て</option>
                        <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>有効</option>
                        <option value="0" <?= ($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>無効</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label for="company" class="form-label">企業</label>
                    <select class="form-select" id="company" name="company">
                        <option value="">全て</option>
                        <?php
                        $companyModel = new Company();
                        $companies = $companyModel->findActive();
                        foreach ($companies as $company):
                        ?>
                            <option value="<?= $company['id'] ?>" <?= ($_GET['company'] ?? '') == $company['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 filter-buttons">
                        <button type="submit" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-search"></i>
                            <span class="btn-text">検索</span>
                        </button>
                        <a href="<?= base_url('users') ?>" class="btn btn-outline-secondary flex-fill">
                            <i class="fas fa-redo"></i>
                            <span class="btn-text">リセット</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- モバイル用並び替え (1024px未満) -->
<div class="mobile-sort-section d-lg-none mb-3">
    <label for="mobile_sort" class="form-label">並び替え</label>
    <select class="form-select" id="mobile_sort">
        <option value="created_at-desc" <?= ($sortColumn ?? 'created_at') === 'created_at' && ($sortOrder ?? 'desc') === 'desc' ? 'selected' : '' ?>>
            登録日(新しい順)
        </option>
        <option value="created_at-asc" <?= ($sortColumn ?? '') === 'created_at' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            登録日(古い順)
        </option>
        <option value="name-asc" <?= ($sortColumn ?? '') === 'name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            氏名(昇順)
        </option>
        <option value="name-desc" <?= ($sortColumn ?? '') === 'name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            氏名(降順)
        </option>
        <option value="login_id-asc" <?= ($sortColumn ?? '') === 'login_id' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            ログインID(昇順)
        </option>
        <option value="login_id-desc" <?= ($sortColumn ?? '') === 'login_id' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            ログインID(降順)
        </option>
        <option value="user_type-asc" <?= ($sortColumn ?? '') === 'user_type' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            ユーザータイプ(昇順)
        </option>
        <option value="company_name-asc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            企業名(昇順)
        </option>
        <option value="is_active-desc" <?= ($sortColumn ?? '') === 'is_active' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            ステータス(有効優先)
        </option>
    </select>
</div>

<!-- 統計情報 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= $totalCount ?></h3>
                <p class="mb-0">総ユーザー数</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">
                    <?= count(array_filter($users ?? [], function($u) { return $u['user_type'] === 'doctor'; })) ?>
                </h3>
                <p class="mb-0">産業医</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">
                    <?= count(array_filter($users ?? [], function($u) { return $u['user_type'] === 'company'; })) ?>
                </h3>
                <p class="mb-0">企業ユーザー</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">
                    <?= count(array_filter($users ?? [], function($u) { return $u['user_type'] === 'admin'; })) ?>
                </h3>
                <p class="mb-0">管理者</p>
            </div>
        </div>
    </div>
</div>

<!-- ユーザー一覧 -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>ユーザー一覧
                    <?php if ($totalCount > 0): ?>
                        <span class="badge bg-primary">全<?= $totalCount ?>件</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <!-- ページサイズ選択（交通費一覧と統一） -->
                    <div class="d-flex align-items-center">
                        <label for="users_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                        <select id="users_page_size" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10件</option>
                            <option value="20" <?= ($perPage == 20) ? 'selected' : '' ?>>20件</option>
                            <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50件</option>
                            <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100件</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>ユーザーが見つかりませんでした。
                検索条件を変更するか、<a href="<?= base_url('users/create') ?>">新しいユーザーを追加</a>してください。
            </div>
        <?php else: ?>
            
            <!-- デスクトップ表示（1024px以上） -->
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="#" class="sort-link" data-sort="id" title="IDで並び替え">
                                    ID
                                    <?php if (($sortColumn ?? '') === 'id'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="name" title="氏名で並び替え">
                                    ユーザー情報
                                    <?php if (($sortColumn ?? '') === 'name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="login_id" title="ログインIDで並び替え">
                                    ログインID
                                    <?php if (($sortColumn ?? '') === 'login_id'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="email" title="メールアドレスで並び替え">
                                    メールアドレス
                                    <?php if (($sortColumn ?? '') === 'email'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="user_type" title="ユーザータイプで並び替え">
                                    ユーザータイプ
                                    <?php if (($sortColumn ?? '') === 'user_type'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="company_name" title="所属企業で並び替え">
                                    所属企業
                                    <?php if (($sortColumn ?? '') === 'company_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>アクセス可能拠点</th>
                            <th class="text-center">事業者区分</th>
                            <th>
                                <a href="#" class="sort-link" data-sort="is_active" title="ステータスで並び替え">
                                    ステータス
                                    <?php if (($sortColumn ?? '') === 'is_active'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="created_at" title="登録日で並び替え">
                                    登録日
                                    <?php if (($sortColumn ?? 'created_at') === 'created_at'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'desc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">#<?= $user['id'] ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="fas <?php
                                                switch($user['user_type']) {
                                                    case 'doctor': echo 'fa-user-md text-info'; break;
                                                    case 'company': echo 'fa-building text-success'; break;
                                                    case 'admin': echo 'fa-user-shield text-warning'; break;
                                                    default: echo 'fa-user text-secondary';
                                                }
                                            ?> fa-lg"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if ($user['id'] == Session::get('user_id')): ?>
                                                <span class="badge bg-info ms-1">あなた</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($user['login_id'], ENT_QUOTES, 'UTF-8') ?></code>
                                    <?php if ($user['user_type'] === 'doctor' && !empty($user['partner_id'])): ?>
                                        <br><small class="text-muted">パートナーID: <?= htmlspecialchars($user['partner_id'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                        switch($user['user_type']) {
                                            case 'doctor': echo 'info'; break;
                                            case 'company': echo 'success'; break;
                                            case 'admin': echo 'warning'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?= get_user_type_label($user['user_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($user['company_name'])): ?>
                                        <small><?= htmlspecialchars($user['company_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['user_type'] === 'company' && !empty($user['branches'])): ?>
                                        <?php 
                                        $totalBranches = count($user['branches']);
                                        $displayBranches = array_slice($user['branches'], 0, 4);
                                        $hasMore = $totalBranches > 4;
                                        ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($displayBranches as $branch): ?>
                                                <span class="badge bg-light text-dark border" 
                                                    title="<?= htmlspecialchars($branch['branch_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= htmlspecialchars($branch['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if ($hasMore): ?>
                                                <span class="badge bg-secondary" 
                                                    title="他<?= $totalBranches - 4 ?>拠点">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <?= $totalBranches ?>拠点<?= $hasMore ? '(表示: 4/' . $totalBranches . ')' : '' ?>
                                        </small>
                                    <?php elseif ($user['user_type'] === 'admin'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-globe me-1"></i>全拠点
                                        </span>
                                    <?php elseif ($user['user_type'] === 'doctor'): ?>
                                        <small class="text-muted">契約先拠点</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($user['user_type'] === 'doctor' && !empty($user['business_classification'])): ?>
                                        <span class="badge bg-<?= ($user['business_classification'] === 'taxable') ? 'success' : 'secondary' ?>">
                                            <?php if ($user['business_classification'] === 'taxable'): ?>
                                                <i class="fas fa-file-invoice me-1"></i>課税事業者
                                            <?php else: ?>
                                                <i class="fas fa-file me-1"></i>免税事業者
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($user['business_classification'] === 'taxable' && !empty($user['invoice_registration_number'])): ?>
                                            <br><small class="text-muted mt-1" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($user['invoice_registration_number'], ENT_QUOTES, 'UTF-8') ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $user['is_active'] ? '有効' : '無効' ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('Y/m/d', strtotime($user['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= base_url("users/{$user['id']}/edit") ?>" 
                                        class="btn btn-outline-primary" 
                                        title="編集"
                                        aria-label="編集">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!empty($user['email'])): ?>
                                            <button class="btn btn-outline-info" 
                                                    onclick="sendInvitationEmail(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>')" 
                                                    title="ログイン招待メール送信"
                                                    aria-label="ログイン招待メール送信">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($user['id'] != Session::get('user_id')): ?>
                                            <?php if ($user['is_active']): ?>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>')" 
                                                        title="無効化"
                                                        aria-label="無効化">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-success" 
                                                        onclick="activateUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>')" 
                                                        title="有効化"
                                                        aria-label="有効化">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" 
                                                    disabled 
                                                    title="自分自身は削除できません"
                                                    aria-label="操作不可">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- モバイル表示（1024px未満） -->
            <div class="d-lg-none">
                <?php foreach ($users as $user): ?>
                    <div class="card mobile-user-card mb-3">
                        <div class="card-body">
                            <!-- ヘッダー：氏名・ステータス -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">
                                        <i class="fas <?php
                                            switch($user['user_type']) {
                                                case 'doctor': echo 'fa-user-md text-info'; break;
                                                case 'company': echo 'fa-building text-success'; break;
                                                case 'admin': echo 'fa-user-shield text-warning'; break;
                                                default: echo 'fa-user text-secondary';
                                            }
                                        ?> me-2"></i>
                                        <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($user['id'] == Session::get('user_id')): ?>
                                            <span class="badge bg-info ms-1">あなた</span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="card-subtitle text-muted mb-0">
                                        ID: <?= $user['id'] ?>
                                    </p>
                                </div>
                                
                                <!-- ステータスバッジ -->
                                <div class="ms-2">
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?> status-badge">
                                        <?= $user['is_active'] ? '有効' : '無効' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- ユーザー詳細 -->
                            <div class="row g-2 mb-3">
                                <!-- ユーザータイプ -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">ユーザータイプ</small>
                                        <span class="badge bg-<?php
                                            switch($user['user_type']) {
                                                case 'doctor': echo 'info'; break;
                                                case 'company': echo 'success'; break;
                                                case 'admin': echo 'warning'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?= get_user_type_label($user['user_type']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- 登録日 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">登録日</small>
                                        <span><?= date('Y/m/d', strtotime($user['created_at'])) ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($user['user_type'] === 'doctor' && !empty($user['business_classification'])): ?>
                                    <div class="col-12">
                                        <div class="mobile-info-item">
                                            <small class="text-muted d-block">事業者区分</small>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-<?= ($user['business_classification'] === 'taxable') ? 'success' : 'secondary' ?>">
                                                    <?php if ($user['business_classification'] === 'taxable'): ?>
                                                        <i class="fas fa-file-invoice me-1"></i>課税事業者
                                                    <?php else: ?>
                                                        <i class="fas fa-file me-1"></i>免税事業者
                                                    <?php endif; ?>
                                                </span>
                                                <?php if ($user['business_classification'] === 'taxable' && !empty($user['invoice_registration_number'])): ?>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($user['invoice_registration_number'], ENT_QUOTES, 'UTF-8') ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- ログイン情報 -->
                            <div class="mobile-info-item mb-3">
                                <small class="text-muted d-block">ログイン情報</small>
                                <div class="d-flex flex-column">
                                    <div class="mb-1">
                                        <strong>ID:</strong> <code><?= htmlspecialchars($user['login_id'], ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php if ($user['user_type'] === 'doctor' && !empty($user['partner_id'])): ?>
                                            <br><small class="text-muted ms-3">パートナーID: <?= htmlspecialchars($user['partner_id'], ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong>Email:</strong> <small><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 所属企業・拠点情報 -->
                            <?php if (!empty($user['company_name']) || ($user['user_type'] === 'company' && !empty($user['branches']))): ?>
                                <div class="mobile-info-item mb-3">
                                    <small class="text-muted d-block">所属・アクセス権限</small>
                                    
                                    <?php if (!empty($user['company_name'])): ?>
                                        <div class="mb-2">
                                            <i class="fas fa-building me-1 text-primary"></i>
                                            <?= htmlspecialchars($user['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['user_type'] === 'company' && !empty($user['branches'])): ?>
                                        <?php 
                                        $totalBranches = count($user['branches']);
                                        $displayBranches = array_slice($user['branches'], 0, 4);
                                        $hasMore = $totalBranches > 4;
                                        ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($displayBranches as $branch): ?>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= htmlspecialchars($branch['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if ($hasMore): ?>
                                                <span class="badge bg-secondary" 
                                                    title="他<?= $totalBranches - 4 ?>拠点">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <?= $totalBranches ?>拠点にアクセス可能<?= $hasMore ? '(表示: 4/' . $totalBranches . ')' : '' ?>
                                        </small>
                                    <?php elseif ($user['user_type'] === 'admin'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-globe me-1"></i>全拠点アクセス可能
                                        </span>
                                    <?php elseif ($user['user_type'] === 'doctor'): ?>
                                        <small class="text-muted">契約先拠点のみアクセス可能</small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 操作ボタン -->
                            <div class="d-grid gap-2">
                                <div class="d-flex gap-2">
                                    <!-- 編集 -->
                                    <a href="<?= base_url("users/{$user['id']}/edit") ?>" 
                                       class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-edit me-1"></i>編集
                                    </a>
                                    
                                    <?php if ($user['id'] != Session::get('user_id')): ?>
                                        <!-- 有効/無効切り替え -->
                                        <?php if ($user['is_active']): ?>
                                            <button class="btn btn-outline-danger btn-sm flex-fill" 
                                                    onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>')">
                                                <i class="fas fa-user-slash me-1"></i>無効化
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success btn-sm flex-fill" 
                                                    onclick="activateUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>')">
                                                <i class="fas fa-user-check me-1"></i>有効化
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm flex-fill" disabled>
                                            <i class="fas fa-lock me-1"></i>操作不可
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($user['email'])): ?>
                                    <!-- ログイン招待メール送信ボタン -->
                                    <button class="btn btn-outline-info btn-sm" 
                                            onclick="sendInvitationEmail(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-envelope me-1"></i>ログイン招待メール送信
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- ページネーション（交通費一覧と統一フォーマット） -->
            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="d-none d-md-block">
                        <small class="text-muted">
                            <?= number_format(($currentPage - 1) * $perPage + 1) ?>-<?= number_format(min($currentPage * $perPage, $totalCount)) ?>件 
                            (全<?= number_format($totalCount) ?>件中)
                        </small>
                    </div>
                    
                    <nav aria-label="ユーザー一覧ページネーション">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- 前のページ -->
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link users-pagination-link" 
                                   href="#" 
                                   data-page="<?= $currentPage - 1 ?>"
                                   data-page-size="<?= $perPage ?>"
                                   aria-label="前のページ">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <!-- ページ番号 -->
                            <?php 
                            $start = max(1, $currentPage - 2);
                            $end = min($totalPages, $currentPage + 2);
                            ?>
                            
                            <?php if ($start > 1): ?>
                                <li class="page-item">
                                    <a class="page-link users-pagination-link" 
                                       href="#" 
                                       data-page="1"
                                       data-page-size="<?= $perPage ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                    <a class="page-link users-pagination-link" 
                                       href="#" 
                                       data-page="<?= $i ?>"
                                       data-page-size="<?= $perPage ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end < $totalPages): ?>
                                <?php if ($end < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link users-pagination-link" 
                                       href="#" 
                                       data-page="<?= $totalPages ?>"
                                       data-page-size="<?= $perPage ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- 次のページ -->
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link users-pagination-link" 
                                   href="#" 
                                   data-page="<?= $currentPage + 1 ?>"
                                   data-page-size="<?= $perPage ?>"
                                   aria-label="次のページ">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <div class="d-md-none">
                        <small class="text-muted">
                            <?= $currentPage ?>/<?= $totalPages ?>ページ
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <!-- データがある場合のページ情報表示（ページネーションなし） -->
                <?php if ($totalCount > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div class="d-none d-md-block">
                            <small class="text-muted">
                                <?= number_format(($currentPage - 1) * $perPage + 1) ?>-<?= number_format(min($currentPage * $perPage, $totalCount)) ?>件 
                                (全<?= number_format($totalCount) ?>件中)
                            </small>
                        </div>
                        
                        <div class="d-md-none">
                            <small class="text-muted">
                                全<?= number_format($totalCount) ?>件
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* ========== 基本スタイル ========== */

/* ヘッダーセクション */
.header-section {
    margin-bottom: 1.5rem;
}

.header-buttons {
    display: flex;
    gap: 0.5rem;
}

/* フィルターフォーム */
.filter-form {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
}

.filter-buttons {
    display: flex;
    gap: 0.5rem;
}

.filter-buttons .btn {
    white-space: nowrap;
}

/* ページサイズ選択スタイル（交通費一覧と統一） */
.form-select-sm {
    font-size: 0.875rem;
    padding: 0.25rem 1.75rem 0.25rem 0.5rem;
    min-width: 80px;
}

/* ページネーション統一スタイル */
.pagination {
    margin-bottom: 0;
}

.pagination .page-link {
    border-color: #dee2e6;
    color: #0d6efd;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.15s ease-in-out;
}

.pagination .page-link:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    color: #0a58ca;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
    font-weight: 600;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    background-color: #fff;
    border-color: #dee2e6;
    cursor: not-allowed;
}

/* ローディング中のスタイル */
.pagination-loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.pagination-loading::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 10;
}

.pagination-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    z-index: 11;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* モバイルユーザーカード */
.mobile-user-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-user-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.mobile-user-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

.mobile-user-card .card-subtitle {
    font-size: 0.875rem;
}

/* モバイル情報項目 */
.mobile-info-item {
    padding: 0.25rem 0;
}

.mobile-info-item small {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

/* ステータスバッジ */
.status-badge {
    font-size: 0.8rem;
    padding: 0.4em 0.7em;
}

/* 拠点バッジのスタイル調整 */
.badge {
    font-size: 0.75em;
}

.badge.bg-light.text-dark.border {
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* テーブルの列幅調整 */
.table th:nth-child(7) { /* アクセス可能拠点列 */
    min-width: 150px;
    max-width: 200px;
}

/* ========== レスポンシブ対応 ========== */

/* 1024px以下でモバイル表示に切り替え */
@media (max-width: 1023px) {
    /* ヘッダー調整 */
    .header-section .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .header-section h2 {
        text-align: center;
        margin-bottom: 0;
        font-size: 1.5rem;
    }
    
    .header-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .header-buttons .btn {
        flex: 1;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* フィルターフォームの調整 */
    .filter-form .row {
        gap: 0.75rem;
    }
    
    .filter-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-buttons .btn {
        width: 100%;
    }
    
    .filter-buttons .btn .btn-text {
        margin-left: 0.5rem;
    }
    
    /* ページネーション調整 */
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination .page-item {
        margin-bottom: 0.25rem;
    }
}

/* タブレット表示（768px-1023px） */
@media (min-width: 768px) and (max-width: 1023px) {
    /* ヘッダーのタブレット調整 */
    .header-section .d-flex {
        flex-direction: row;
        align-items: center !important;
        gap: 1rem;
    }
    
    .header-section h2 {
        text-align: left;
        margin-bottom: 0;
        font-size: 1.5rem;
        flex-grow: 1;
    }
    
    .header-buttons {
        flex-shrink: 0;
    }
    
    .header-buttons .btn {
        flex: none;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* フィルターボタンは横並び */
    .filter-buttons {
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .filter-buttons .btn {
        width: auto;
        flex: 1;
    }
}

/* モバイル表示（768px未満） */
@media (max-width: 767px) {
    /* ヘッダー調整 */
    .header-section .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .header-section h2 {
        text-align: center;
        margin-bottom: 0;
        font-size: 1.5rem;
    }
    
    .header-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .header-buttons .btn {
        flex: 1;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* フィルターフォームの調整 */
    .filter-form .row {
        gap: 0.75rem;
    }
    
    .filter-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-buttons .btn {
        width: 100%;
    }
    
    .filter-buttons .btn .btn-text {
        margin-left: 0.5rem;
    }
    
    /* モバイルユーザーカードの調整 */
    .mobile-user-card .card-body {
        padding: 1rem;
    }
    
    .mobile-info-item {
        padding: 0.25rem 0;
    }
    
    .mobile-info-item small {
        font-size: 0.75rem;
    }
    
    /* カードヘッダーの調整 */
    .card-header h5 {
        font-size: 1rem;
    }
    
    /* ページネーション調整 */
    .pagination .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
}

/* 小型モバイル表示（576px未満） */
@media (max-width: 575px) {
    /* ヘッダーのさらなる調整 */
    .header-section h2 {
        font-size: 1.25rem;
    }
    
    .header-buttons .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
    
    /* フィルターの調整 */
    .filter-form {
        padding: 0.75rem;
    }
    
    .filter-form .col-6 {
        margin-bottom: 0.5rem;
    }
    
    /* モバイルカードの詳細調整 */
    .mobile-user-card .card-body {
        padding: 0.75rem;
    }
    
    .mobile-user-card .card-title {
        font-size: 0.9rem;
    }
    
    .mobile-user-card .card-subtitle {
        font-size: 0.8rem;
    }
    
    /* ボタンの調整 */
    .mobile-user-card .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
    }
    
    /* ページネーション調整 */
    .pagination .page-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
    }
    
    .pagination .page-link i {
        font-size: 0.75rem;
    }
}

/* 極小モバイル表示（400px未満） */
@media (max-width: 399px) {
    /* 最小限の表示に調整 */
    .header-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .mobile-user-card .card-body {
        padding: 0.5rem;
    }
    
    .mobile-user-card .card-title {
        font-size: 0.85rem;
    }
    
    .mobile-user-card .btn {
        font-size: 0.75rem;
        padding: 0.4rem;
    }
    
    .pagination .page-link {
        padding: 0.35rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* レスポンシブ表の調整 */
@media (max-width: 1200px) {
    .table-responsive {
        font-size: 0.9em;
    }
    
    .badge.bg-light.text-dark.border {
        max-width: 80px;
    }
}

/* ========== その他のスタイル ========== */

/* テーブルのスタイリング */
.table td, .table th {
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

/* バッジのスタイル */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}

/* ボタングループの調整 */
.btn-group .btn {
    margin-bottom: 2px;
}

.btn-group .btn:last-child {
    margin-bottom: 0;
}

/* カードのスタイル */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

/* 統計カードのスタイル */
.card.bg-primary, .card.bg-success, .card.bg-warning, .card.bg-info {
    border: none;
}

/* アクセシビリティ向上 */
@media (max-width: 767px) {
    /* タップターゲットのサイズを44px以上に */
    .mobile-user-card .btn,
    .mobile-user-card .badge {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .mobile-user-card .badge {
        min-height: 32px; /* バッジは少し小さめでも可 */
    }
}

/* ボタンの統一スタイル */
.btn {
    transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* 並び替えリンクのスタイル */
.sort-link {
    color: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 600;
}

.sort-link:hover {
    color: inherit;
    text-decoration: none;
    transform: translateX(2px);
}

.sort-link i {
    font-size: 0.875rem;
    opacity: 1;
    transition: transform 0.2s ease;
    color: #6c757d;
}

.sort-link:hover i {
    transform: scale(1.2);
    color: #495057;
}

/* アクティブな並び替え列を強調 */
th:has(.sort-link) {
    background-color: #f8f9fa;
    position: relative;
}

/* 並び替えアイコンがない列にもアイコンを表示(薄く) */
.table-light th a.sort-link:not(:has(i))::after {
    content: '\f0dc';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: 0.25rem;
    opacity: 0.3;
    font-size: 0.75rem;
}

/* 現在の並び替え列をハイライト */
.table-light th:has(.sort-link i) {
    background-color: #e7f1ff;
}

.table-light th a.sort-link {
    font-weight: 600;
}

/* モバイル用の並び替え */
.mobile-sort-section {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}

.mobile-sort-section .form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

@media (max-width: 767px) {
    .sort-link {
        font-size: 0.875rem;
    }
}

/* 事業者区分バッジのスタイル */
.badge .fa-file-invoice,
.badge .fa-file {
    font-size: 0.85em;
}

/* インボイス番号の表示スタイル */
td small.text-muted {
    display: block;
    font-size: 0.7rem;
    line-height: 1.2;
}

/* モバイル表示での事業者区分の調整 */
.mobile-info-item .badge {
    font-size: 0.8rem;
    padding: 0.4em 0.6em;
}

.mobile-info-item .d-flex.gap-2 {
    gap: 0.5rem;
}

/* テーブルの列幅調整（事業者区分列） */
.table th:nth-child(8) { /* 事業者区分列 */
    min-width: 140px;
    max-width: 180px;
}

@media (max-width: 1200px) {
    .table th:nth-child(8) {
        min-width: 120px;
    }
}

/* ========== 1200px付近での操作ボタン修正 ========== */

/* 1200px以下でのテーブル調整を改善 */
@media (max-width: 1200px) {
    .table-responsive {
        font-size: 0.9em;
    }
    
    .badge.bg-light.text-dark.border {
        max-width: 80px;
    }
    
    /* 操作ボタンの調整 - 重要 */
    .btn-group {
        display: flex;
        flex-wrap: nowrap;
        gap: 2px;
    }
    
    .btn-group .btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.875rem;
        white-space: nowrap;
        min-width: auto;
    }
    
    .btn-group .btn i {
        font-size: 0.875rem;
    }
    
    /* 操作列の最小幅を確保 */
    .table td:last-child,
    .table th:last-child {
        min-width: 80px;
        width: 80px;
    }
}

/* 1024px-1200pxの範囲でさらに調整 */
@media (min-width: 1024px) and (max-width: 1200px) {
    /* テーブル全体を少し小さく */
    .table {
        font-size: 0.875rem;
    }
    
    /* 操作ボタンを縦並びに */
    .btn-group {
        flex-direction: column;
        gap: 2px;
    }
    
    .btn-group .btn {
        width: 100%;
        padding: 0.25rem 0.5rem;
    }
    
    /* 操作列の幅を調整 */
    .table td:last-child,
    .table th:last-child {
        min-width: 60px;
        width: 60px;
    }
}

/* ========== 1024px-1200pxの範囲での操作ボタン表示問題の修正 ========== */

/* テーブルレスポンシブの改善 */
@media (min-width: 1024px) and (max-width: 1200px) {
    /* テーブル全体を横スクロール可能に */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* テーブルの最小幅を設定してレイアウト崩れを防ぐ */
    .table {
        min-width: 1000px;
        font-size: 0.85rem;
    }
    
    /* 操作列を確実に表示 */
    .table td:last-child,
    .table th:last-child {
        position: sticky;
        right: 0;
        background-color: #fff;
        box-shadow: -2px 0 4px rgba(0,0,0,0.1);
        z-index: 10;
        min-width: 90px;
        width: 90px;
    }
    
    .table thead th:last-child {
        background-color: #f8f9fa;
    }
    
    .table tbody tr:hover td:last-child {
        background-color: #f8f9fa;
    }
    
    /* ボタングループを縦並びに */
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 3px;
        width: 100%;
    }
    
    .btn-group .btn {
        width: 100%;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        border-radius: 0.25rem !important;
    }
    
    .btn-group .btn i {
        font-size: 0.85rem;
    }
    
    /* その他の列の幅調整 */
    .table th:nth-child(1) { width: 60px; } /* ID */
    .table th:nth-child(3) { min-width: 100px; } /* ログインID */
    .table th:nth-child(4) { min-width: 150px; } /* メールアドレス */
    .table th:nth-child(5) { width: 100px; } /* ユーザータイプ */
    .table th:nth-child(6) { min-width: 120px; } /* 所属企業 */
    .table th:nth-child(7) { min-width: 150px; } /* アクセス可能拠点 */
    .table th:nth-child(8) { min-width: 130px; } /* 事業者区分 */
    .table th:nth-child(9) { width: 80px; } /* ステータス */
    .table th:nth-child(10) { width: 90px; } /* 登録日 */
}

/* 1100px以下でさらに調整 */
@media (min-width: 1024px) and (max-width: 1100px) {
    .table {
        font-size: 0.8rem;
    }
    
    .btn-group .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .btn-group .btn i {
        font-size: 0.8rem;
    }
    
    /* 操作列の幅をさらに縮小 */
    .table td:last-child,
    .table th:last-child {
        min-width: 70px;
        width: 70px;
    }
}

/* テーブルの横スクロールヒント */
@media (min-width: 1024px) and (max-width: 1200px) {
    .card-body {
        position: relative;
    }
    
    /* 横スクロール可能であることを示すグラデーション */
    .table-responsive::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 30px;
        background: linear-gradient(to left, rgba(255,255,255,1), rgba(255,255,255,0));
        pointer-events: none;
        z-index: 5;
    }
    
    .table-responsive.scrolled-to-end::after {
        display: none;
    }
}
</style>

<script>
// ==================== 1024px-1200pxでの操作ボタン表示対応 ====================

// テーブルの横スクロール検知（操作列固定のため）
if (window.innerWidth >= 1024 && window.innerWidth <= 1200) {
    const tableResponsive = document.querySelector('.table-responsive.d-none.d-lg-block');
    
    if (tableResponsive) {
        // 横スクロール位置を監視
        tableResponsive.addEventListener('scroll', function() {
            const scrollLeft = this.scrollLeft;
            const scrollWidth = this.scrollWidth;
            const clientWidth = this.clientWidth;
            
            // 右端までスクロールしたかチェック
            if (scrollLeft + clientWidth >= scrollWidth - 10) {
                this.classList.add('scrolled-to-end');
            } else {
                this.classList.remove('scrolled-to-end');
            }
        });
        
        // 初期状態をチェック
        if (tableResponsive.scrollWidth > tableResponsive.clientWidth) {
            // 横スクロール可能な場合、ヒントを表示
            console.log('テーブルは横スクロール可能です');
        }
    }
}

// ウィンドウリサイズ時にも再チェック
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024 && window.innerWidth <= 1200) {
        const tableResponsive = document.querySelector('.table-responsive.d-none.d-lg-block');
        if (tableResponsive && tableResponsive.scrollWidth > tableResponsive.clientWidth) {
            tableResponsive.scrollLeft = 0; // スクロール位置をリセット
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    
    // ===================== ダッシュボード統一ページネーション機能 =====================
    
    // ページサイズ変更時の処理（ユーザー一覧）
    const usersPageSizeSelect = document.getElementById('users_page_size');
    if (usersPageSizeSelect) {
        usersPageSizeSelect.addEventListener('change', function() {
            const newPageSize = this.value;
            reloadPageWithPagination({
                page: 1, // ページサイズ変更時は1ページ目に戻る
                per_page: newPageSize
            });
        });
    }
    
    // ページネーションリンクのクリック処理（ユーザー一覧）
    document.addEventListener('click', function(e) {
        if (e.target.closest('.users-pagination-link')) {
            e.preventDefault();
            const link = e.target.closest('.users-pagination-link');
            const page = link.getAttribute('data-page');
            const pageSize = link.getAttribute('data-page-size');
            
            if (page && pageSize && !link.parentNode.classList.contains('disabled')) {
                reloadPageWithPagination({
                    page: page,
                    per_page: pageSize
                });
            }
        }
    });
    
    // ページリロード関数（ユーザー一覧）
    function reloadPageWithPagination(params) {
        // ローディング状態を表示
        showPaginationLoading();
        
        // 現在のURLパラメータを取得
        const currentUrl = new URL(window.location);
        const currentParams = new URLSearchParams(currentUrl.search);
        
        // 新しいパラメータを設定
        Object.keys(params).forEach(key => {
            if (params[key]) {
                currentParams.set(key, params[key]);
            } else {
                currentParams.delete(key);
            }
        });
        
        // 新しいURLを構築
        const newUrl = `${currentUrl.pathname}?${currentParams.toString()}`;
        
        // ページリロード
        window.location.href = newUrl;
    }
    
    // ページネーションローディング表示
    function showPaginationLoading() {
        const usersCard = document.querySelector('.card:has(.users-pagination-link)');
        if (usersCard) {
            usersCard.classList.add('pagination-loading');
        }
    }
    
    // ===================== 既存の機能（有効化・無効化等） =====================
    
    // フィルター変更時にページを1に戻す
    const filterElements = document.querySelectorAll('#search, #user_type, #status, #company');
    filterElements.forEach(element => {
        element.addEventListener('change', function() {
            const form = document.getElementById('filterForm');
            if (form) {
                // フォームを直接送信するのではなく、1ページ目に戻してから送信
                const currentParams = new URLSearchParams(window.location.search);
                currentParams.set('page', '1'); // 1ページ目に戻す
                // フォームの送信を実行
                form.submit();
            }
        });
    });
    
    // テーブル行のハイライト（デスクトップのみ）
    if (window.innerWidth >= 1024) {
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }
});

// ユーザー削除（無効化）機能
function deleteUser(id, name) {
    if (confirm(`ユーザー「${name}」を無効化しますか？\n\n無効化すると、このユーザーはログインできなくなります。`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('') ?>users/' + id + '/delete';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// ユーザー有効化機能
function activateUser(id, name) {
    if (confirm(`ユーザー「${name}」を有効化しますか？`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('') ?>users/' + id;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        const isActive = document.createElement('input');
        isActive.type = 'hidden';
        isActive.name = 'is_active';
        isActive.value = '1';
        form.appendChild(isActive);
        
        // 現在の値を保持（簡易的に）
        const fields = ['login_id', 'name', 'email', 'user_type'];
        fields.forEach(fieldName => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = fieldName;
            // この部分は実際の値を設定する必要がありますが、簡易的な実装です
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// ログイン招待メール送信機能
function sendInvitationEmail(id, name) {
    if (confirm(`ユーザー「${name}」にログイン招待メールを送信しますか?\n\n新しい暫定パスワードが生成され、メールで送信されます。\n現在のパスワードは上書きされますのでご注意ください。`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('') ?>users/' + id + '/send-invitation';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?>';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 画面サイズ変更時の対応
window.addEventListener('resize', function() {
    // 必要に応じてレイアウト調整
    adjustLayoutForScreenSize();
});

// 画面サイズに応じたレイアウト調整
function adjustLayoutForScreenSize() {
    if (window.innerWidth <= 576) {
        // 小画面用の調整
        const modalBodies = document.querySelectorAll('.modal-body');
        modalBodies.forEach(body => {
            body.style.fontSize = '0.9rem';
            body.style.padding = '1rem 0.75rem';
        });
    }
}

// タッチイベント対応（モバイルのみ）
if (window.innerWidth < 1024) {
    document.addEventListener('touchstart', function(e) {
        // モバイルカードのタッチハイライト
        if (e.target.closest('.mobile-user-card')) {
            const card = e.target.closest('.mobile-user-card');
            card.style.backgroundColor = '#f8f9fa';
        }
    });
    
    document.addEventListener('touchend', function(e) {
        // タッチ終了時にハイライトを解除
        if (e.target.closest('.mobile-user-card')) {
            const card = e.target.closest('.mobile-user-card');
            setTimeout(() => {
                card.style.backgroundColor = '';
            }, 200);
        }
    });
}

// 表示件数変更関数（後方互換性のため）
function changePerPage(perPage) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', perPage);
    urlParams.set('page', 1); // ページを1に戻す
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

// ==================== 並び替え機能 ====================

// デスクトップ用の並び替えリンク
const sortLinks = document.querySelectorAll('.sort-link');

sortLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        const sortColumn = this.getAttribute('data-sort');
        const currentUrl = new URL(window.location);
        const currentSort = currentUrl.searchParams.get('sort');
        const currentOrder = currentUrl.searchParams.get('order') || 'desc';
        
        // 同じ列をクリックした場合は昇順・降順を切り替え
        if (currentSort === sortColumn) {
            const newOrder = currentOrder === 'desc' ? 'asc' : 'desc';
            currentUrl.searchParams.set('order', newOrder);
        } else {
            // 新しい列の場合はデフォルトで降順
            currentUrl.searchParams.set('sort', sortColumn);
            currentUrl.searchParams.set('order', 'desc');
        }
        
        // ページを1に戻す
        currentUrl.searchParams.set('page', '1');
        
        // 並び替え中表示
        showSortLoading();
        
        // リダイレクト
        window.location.href = currentUrl.toString();
    });
});

// モバイル用の並び替えセレクトボックス
const mobileSortSelect = document.getElementById('mobile_sort');
if (mobileSortSelect) {
    mobileSortSelect.addEventListener('change', function() {
        const [sortColumn, sortOrder] = this.value.split('-');
        
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('sort', sortColumn);
        currentUrl.searchParams.set('order', sortOrder);
        currentUrl.searchParams.set('page', '1');
        
        showSortLoading();
        window.location.href = currentUrl.toString();
    });
}

function showSortLoading() {
    const loadingHtml = `
        <div id="sortLoadingOverlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(255, 255, 255, 0.8); z-index: 9999; display: flex; 
            align-items: center; justify-content: center;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">並び替え中...</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', loadingHtml);
}
</script>