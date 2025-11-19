<?php
// app/views/companies/index.php - 拠点一覧版（ページネーション対応）

// ページネーションURL構築のヘルパー関数
function buildCompaniesUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return base_url('companies') . '?' . http_build_query($params);
}
?>

<!-- ヘッダー -->
<div class="header-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-building me-2"></i>企業・拠点管理</h2>
        <div class="header-buttons">
            <a href="<?= base_url('companies/create') ?>" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>企業を追加
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
        <form method="GET" action="<?= base_url('companies') ?>" class="filter-form" id="filterForm">
            <div class="row g-3">
                <div class="col-md-4 col-6">
                    <label for="search" class="form-label">検索</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="企業名/拠点名/住所/電話番号で検索">
                </div>
                
                <div class="col-md-3 col-6">
                    <label for="status" class="form-label">拠点ステータス</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全て</option>
                        <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>有効</option>
                        <option value="0" <?= ($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>無効</option>
                    </select>
                </div>
                
                <div class="col-md-5 col-12">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 filter-buttons">
                        <button type="submit" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-search"></i>
                            <span class="btn-text">検索</span>
                        </button>
                        <a href="<?= base_url('companies') ?>" class="btn btn-outline-secondary flex-fill">
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
        <option value="company_name-asc" <?= ($sortColumn ?? 'company_name') === 'company_name' && ($sortOrder ?? 'asc') === 'asc' ? 'selected' : '' ?>>
            企業名(昇順)
        </option>
        <option value="company_name-desc" <?= ($sortColumn ?? '') === 'company_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            企業名(降順)
        </option>
        <option value="branch_name-asc" <?= ($sortColumn ?? '') === 'branch_name' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            拠点名(昇順)
        </option>
        <option value="branch_name-desc" <?= ($sortColumn ?? '') === 'branch_name' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            拠点名(降順)
        </option>
        <option value="contract_count-desc" <?= ($sortColumn ?? '') === 'contract_count' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            契約数(多い順)
        </option>
        <option value="contract_count-asc" <?= ($sortColumn ?? '') === 'contract_count' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            契約数(少ない順)
        </option>
        <option value="is_active-desc" <?= ($sortColumn ?? '') === 'is_active' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            ステータス(有効→無効)
        </option>
        <option value="is_active-asc" <?= ($sortColumn ?? '') === 'is_active' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            ステータス(無効→有効)
        </option>
    </select>
</div>

<!-- 統計情報 -->
<div class="row mb-4">
    <div class="col-md-3 col-6">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= $stats['total_companies'] ?></h3>
                <p class="mb-0">企業数</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= $stats['total_branches'] ?></h3>
                <p class="mb-0">拠点数</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= $stats['active_branches'] ?></h3>
                <p class="mb-0">有効拠点</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= $stats['total_contracts'] ?></h3>
                <p class="mb-0">契約数</p>
            </div>
        </div>
    </div>
</div>

<!-- 拠点一覧 -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-map-marker-alt me-2"></i>拠点一覧
                    <?php if ($totalCount > 0): ?>
                        <span class="badge bg-primary">全<?= $totalCount ?>件</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <!-- ページサイズ選択（交通費一覧と統一） -->
                    <div class="d-flex align-items-center">
                        <label for="companies_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                        <select id="companies_page_size" class="form-select form-select-sm" style="width: auto;">
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
        <?php if (empty($branches)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>拠点が登録されていません。
                <a href="<?= base_url('companies/create') ?>" class="btn btn-success btn-sm ms-2">
                    <i class="fas fa-plus me-1"></i>最初の企業を追加
                </a>
            </div>
        <?php else: ?>
            
            <!-- デスクトップ表示（1024px以上） -->
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="#" class="sort-link" data-sort="company_name" title="企業名で並び替え">
                                    企業名
                                    <?php if (($sortColumn ?? 'company_name') === 'company_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="branch_name" title="拠点名で並び替え">
                                    拠点名
                                    <?php if (($sortColumn ?? '') === 'branch_name'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>住所</th>
                            <th>電話番号</th>
                            <th>メールアドレス</th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="contract_count" title="契約数で並び替え">
                                    契約数
                                    <?php if (($sortColumn ?? '') === 'contract_count'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" class="sort-link" data-sort="is_active" title="拠点状態で並び替え">
                                    拠点状態
                                    <?php if (($sortColumn ?? '') === 'is_active'): ?>
                                        <i class="fas fa-caret-<?= ($sortOrder ?? 'asc') === 'desc' ? 'down' : 'up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">企業状態</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $currentCompany = '';
                        foreach ($branches as $branch): 
                            $isNewCompany = ($currentCompany !== $branch['company_name']);
                            $currentCompany = $branch['company_name'];
                        ?>
                            <tr <?= !$branch['company_is_active'] ? 'class="table-secondary"' : '' ?>>
                                <td>
                                    <?php if ($isNewCompany): ?>
                                        <div class="fw-bold">
                                            <i class="fas fa-building me-1 text-primary"></i>
                                            <?= htmlspecialchars($branch['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted ps-3">〃</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                    <?= htmlspecialchars($branch['name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?php if (!empty($branch['address'])): ?>
                                        <small><?= htmlspecialchars($branch['address'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($branch['phone'])): ?>
                                        <small><?= htmlspecialchars($branch['phone'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($branch['email'])): ?>
                                        <small><?= htmlspecialchars($branch['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($branch['contract_count'] > 0): ?>
                                        <span class="badge bg-info"><?= $branch['contract_count'] ?>件</span>
                                    <?php else: ?>
                                        <span class="text-muted">0件</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $branch['is_active'] ? 'success' : 'secondary' ?>">
                                        <i class="fas fa-<?= $branch['is_active'] ? 'check' : 'times' ?> me-1"></i>
                                        <?= $branch['is_active'] ? '有効' : '無効' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $branch['company_is_active'] ? 'success' : 'danger' ?>">
                                        <i class="fas fa-<?= $branch['company_is_active'] ? 'check-circle' : 'ban' ?> me-1"></i>
                                        <?= $branch['company_is_active'] ? '有効' : '無効' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group-vertical" role="group">
                                        <a href="<?= base_url("companies/{$branch['company_id']}/edit") ?>" 
                                           class="btn btn-outline-primary btn-sm mb-1"
                                           title="企業編集">
                                            <i class="fas fa-building me-1"></i>編集
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- モバイル表示（1024px未満） -->
            <div class="d-lg-none">
                <?php 
                $currentCompany = '';
                foreach ($branches as $branch): 
                    $isNewCompany = ($currentCompany !== $branch['company_name']);
                    $currentCompany = $branch['company_name'];
                ?>
                    
                    <div class="card mobile-branch-card mb-3 <?= !$branch['company_is_active'] ? 'opacity-75' : '' ?>">
                        <div class="card-body">
                            <!-- ヘッダー：企業名・拠点名 -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <?php if ($isNewCompany): ?>
                                        <h6 class="card-title mb-1">
                                            <i class="fas fa-building me-2 text-primary"></i>
                                            <?= htmlspecialchars($branch['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </h6>
                                    <?php endif; ?>
                                    <p class="card-subtitle mb-0">
                                        <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                        <?= htmlspecialchars($branch['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                
                                <!-- ステータスバッジ -->
                                <div class="ms-2">
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge bg-<?= $branch['company_is_active'] ? 'success' : 'danger' ?> status-badge">
                                            <i class="fas fa-<?= $branch['company_is_active'] ? 'check-circle' : 'ban' ?> me-1"></i>
                                            企業<?= $branch['company_is_active'] ? '有効' : '無効' ?>
                                        </span>
                                        <span class="badge bg-<?= $branch['is_active'] ? 'success' : 'secondary' ?> status-badge">
                                            <i class="fas fa-<?= $branch['is_active'] ? 'check' : 'times' ?> me-1"></i>
                                            拠点<?= $branch['is_active'] ? '有効' : '無効' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 拠点詳細 -->
                            <div class="row g-2 mb-3">
                                <!-- 住所 -->
                                <div class="col-12">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">住所</small>
                                        <?php if (!empty($branch['address'])): ?>
                                            <span><?= htmlspecialchars($branch['address'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- 電話番号 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">電話番号</small>
                                        <?php if (!empty($branch['phone'])): ?>
                                            <span><?= htmlspecialchars($branch['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- 契約数 -->
                                <div class="col-6">
                                    <div class="mobile-info-item">
                                        <small class="text-muted d-block">契約数</small>
                                        <?php if ($branch['contract_count'] > 0): ?>
                                            <span class="badge bg-info"><?= $branch['contract_count'] ?>件</span>
                                        <?php else: ?>
                                            <span class="text-muted">0件</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- メールアドレス -->
                                <?php if (!empty($branch['email'])): ?>
                                    <div class="col-12">
                                        <div class="mobile-info-item">
                                            <small class="text-muted d-block">メールアドレス</small>
                                            <span><?= htmlspecialchars($branch['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 操作ボタン -->
                            <div class="d-grid gap-2">
                                <div class="d-flex gap-2">
                                    <!-- 企業編集 -->
                                    <a href="<?= base_url("companies/{$branch['company_id']}/edit") ?>" 
                                       class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-building me-1"></i>企業編集
                                    </a>
                                    
                                    <!-- 拠点編集 -->
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" 
                                            onclick="editBranch(<?= $branch['id'] ?>, '<?= htmlspecialchars($branch['name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($branch['company_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-edit me-1"></i>拠点編集
                                    </button>
                                </div>
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
                    
                    <nav aria-label="企業・拠点一覧ページネーション">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- 前のページ -->
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link companies-pagination-link" 
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
                                    <a class="page-link companies-pagination-link" 
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
                                    <a class="page-link companies-pagination-link" 
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
                                    <a class="page-link companies-pagination-link" 
                                       href="#" 
                                       data-page="<?= $totalPages ?>"
                                       data-page-size="<?= $perPage ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- 次のページ -->
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link companies-pagination-link" 
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
            
            <!-- 注意事項 -->
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    企業が無効の場合、その企業のすべてのユーザーがログインできません。
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ========== 基本スタイル（交通費一覧と統一） ========== */

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

/* モバイル拠点カード */
.mobile-branch-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-branch-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.mobile-branch-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

.mobile-branch-card .card-subtitle {
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
    /* 統計カードの調整 */
    .row .col-md-3 {
        margin-bottom: 0.75rem;
    }
    
    .card.bg-primary,
    .card.bg-info,
    .card.bg-success,
    .card.bg-warning {
        margin-bottom: 0.5rem;
    }
    
    /* モバイル拠点カードの調整 */
    .mobile-branch-card .card-body {
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
    .mobile-branch-card .card-body {
        padding: 0.75rem;
    }
    
    .mobile-branch-card .card-title {
        font-size: 0.9rem;
    }
    
    .mobile-branch-card .card-subtitle {
        font-size: 0.8rem;
    }
    
    /* ボタンの調整 */
    .mobile-branch-card .btn {
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
    
    .mobile-branch-card .card-body {
        padding: 0.5rem;
    }
    
    .mobile-branch-card .card-title {
        font-size: 0.85rem;
    }
    
    .mobile-branch-card .btn {
        font-size: 0.75rem;
        padding: 0.4rem;
    }
    
    .pagination .page-link {
        padding: 0.35rem 0.5rem;
        font-size: 0.75rem;
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
.btn-group-vertical .btn {
    margin-bottom: 2px;
}

.btn-group-vertical .btn:last-child {
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
    .mobile-branch-card .btn,
    .mobile-branch-card .badge {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .mobile-branch-card .badge {
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ===================== ダッシュボード統一ページネーション機能 =====================
    
    // ページサイズ変更時の処理（企業・拠点一覧）
    const companiesPageSizeSelect = document.getElementById('companies_page_size');
    if (companiesPageSizeSelect) {
        companiesPageSizeSelect.addEventListener('change', function() {
            const newPageSize = this.value;
            reloadPageWithPagination({
                page: 1, // ページサイズ変更時は1ページ目に戻る
                per_page: newPageSize
            });
        });
    }
    
    // ページネーションリンクのクリック処理（企業・拠点一覧）
    document.addEventListener('click', function(e) {
        if (e.target.closest('.companies-pagination-link')) {
            e.preventDefault();
            const link = e.target.closest('.companies-pagination-link');
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
    
    // ページリロード関数（企業・拠点一覧）
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
        const companiesCard = document.querySelector('.card:has(.companies-pagination-link)');
        if (companiesCard) {
            companiesCard.classList.add('pagination-loading');
        }
    }
    
    // ===================== 既存の機能（フィルター等） =====================
    
    // フィルター変更時にページを1に戻す
    const filterElements = document.querySelectorAll('#search, #status');
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

// 拠点編集関数
function editBranch(branchId, branchName, companyName) {
    if (confirm(`${companyName}の拠点「${branchName}」を編集しますか？`)) {
        // 拠点編集画面への遷移（企業編集画面内で編集）
        window.location.href = '<?= base_url('branches/') ?>' + branchId + '/edit';
    }
}

// 画面サイズ変更時の対応
window.addEventListener('resize', function() {
    // 必要に応じてレイアウト調整
    adjustLayout();
});

// レイアウト調整関数
function adjustLayout() {
    if (window.innerWidth <= 576) {
        // 小画面用の調整
        const cardBodies = document.querySelectorAll('.mobile-branch-card .card-body');
        cardBodies.forEach(body => {
            body.style.fontSize = '0.9rem';
            body.style.padding = '0.75rem';
        });
    }
}

// タッチイベント対応（モバイルのみ）
if (window.innerWidth < 1024) {
    document.addEventListener('touchstart', function(e) {
        // モバイルカードのタッチハイライト
        if (e.target.closest('.mobile-branch-card')) {
            const card = e.target.closest('.mobile-branch-card');
            card.style.backgroundColor = '#f8f9fa';
        }
    });
    
    document.addEventListener('touchend', function(e) {
        // タッチ終了時にハイライトを解除
        if (e.target.closest('.mobile-branch-card')) {
            const card = e.target.closest('.mobile-branch-card');
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
        const currentOrder = currentUrl.searchParams.get('order') || 'asc';
        
        // 同じ列をクリックした場合は昇順・降順を切り替え
        if (currentSort === sortColumn) {
            const newOrder = currentOrder === 'desc' ? 'asc' : 'desc';
            currentUrl.searchParams.set('order', newOrder);
        } else {
            // 新しい列の場合はデフォルトで昇順
            currentUrl.searchParams.set('sort', sortColumn);
            currentUrl.searchParams.set('order', 'asc');
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