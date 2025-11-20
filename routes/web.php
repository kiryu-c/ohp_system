<?php
// routes/web.php

// 認証関連
$router->get('/', ['AuthController', 'showLogin']);
$router->get('/login', ['AuthController', 'showLogin']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);

// ダッシュボード
$router->get('/dashboard', ['DashboardController', 'index']);

// 管理者用役務記録管理(先に定義する)
$router->get('/service_records/admin', ['ServiceRecordController', 'adminIndex']);
$router->get('/service_records/admin/{id}/detail', ['ServiceRecordController', 'adminDetail']);
$router->get('/service_records/admin/{id}/detail/api', ['ServiceRecordController', 'adminDetailApi']);
$router->get('/service_records/closing_detail', ['ServiceRecordController', 'getClosingDetail']);

// 役務記録関連(一般)
$router->get('/service_records/select-contract', ['ServiceRecordController', 'selectContract']);
$router->get('/service_records/active', ['ServiceRecordController', 'activeService']);
$router->get('/service_records/create', ['ServiceRecordController', 'create']);
$router->get('/service_records', ['ServiceRecordController', 'index']);
$router->post('/service_records/start', ['ServiceRecordController', 'startService']);
$router->post('/service_records/bulk-approve', ['ServiceRecordController', 'bulkApprove']);
$router->post('/service_records', ['ServiceRecordController', 'store']);
$router->get('/service_records/{id}/detail', ['ServiceRecordController', 'detail']);
$router->get('/service_records/{id}/edit', ['ServiceRecordController', 'edit']);
$router->post('/service_records/{id}', ['ServiceRecordController', 'update']);
$router->post('/service_records/{id}/approve', ['ServiceRecordController', 'approve']);
$router->post('/service_records/{id}/unapprove', ['ServiceRecordController', 'unapprove']);
$router->post('/service_records/{id}/reject', ['ServiceRecordController', 'reject']);
$router->get('/service_records/{id}', ['ServiceRecordController', 'show']);
$router->post('/service_records/{id}/end', ['ServiceRecordController', 'endService']);
$router->post('/service_records/{id}/delete', ['ServiceRecordController', 'delete']);
$router->get('/api/service_records/contracts_for_month', ['ServiceRecordController', 'getContractsForMonth']);


// 役務内容テンプレート関連
$router->get('/service_templates', ['ServiceTemplateController', 'index']);
$router->get('/service_templates/{id}/edit', ['ServiceTemplateController', 'edit']);
$router->post('/service_templates/{id}', ['ServiceTemplateController', 'update']);
$router->post('/service_templates/{id}/delete', ['ServiceTemplateController', 'deleteTemplate']);

// 役務内容テンプレート API関連
$router->get('/api/service_templates', ['ServiceTemplateController', 'getTemplatesApi']);
$router->post('/api/service_templates/save', ['ServiceTemplateController', 'saveTemplateApi']);
$router->post('/api/service_templates/use', ['ServiceTemplateController', 'useTemplateApi']);
$router->post('/api/service_templates/check_auto_save', ['ServiceTemplateController', 'checkAutoSaveApi']);

// 契約関連(管理者のみ)
$router->get('/contracts', ['ContractController', 'index']);
$router->get('/contracts/create', ['ContractController', 'create']);
$router->post('/contracts', ['ContractController', 'store']);
$router->get('/contracts/{id}/edit', ['ContractController', 'edit']);
$router->post('/contracts/{id}', ['ContractController', 'update']);
$router->post('/contracts/{id}/start-service', ['ContractController', 'startService']);
$router->get('/contracts/{id}/select-service-type', ['ContractController', 'selectServiceType']);

// 非訪問日管理(管理者のみ・毎週契約)
$router->get('/non_visit_days/{contract_id}', ['NonVisitDayController', 'index']);
$router->get('/non_visit_days/{contract_id}/create', ['NonVisitDayController', 'create']);
$router->post('/non_visit_days/{contract_id}', ['NonVisitDayController', 'store']);
$router->get('/non_visit_days/{contract_id}/{id}/edit', ['NonVisitDayController', 'edit']);
$router->post('/non_visit_days/{contract_id}/{id}', ['NonVisitDayController', 'update']);
$router->post('/non_visit_days/{contract_id}/{id}/delete', ['NonVisitDayController', 'delete']);
$router->post('/non_visit_days/{contract_id}/copy-from-previous-year', ['NonVisitDayController', 'copyFromPreviousYear']);
$router->get('/non_visit_days/{contract_id}/statistics', ['NonVisitDayController', 'statistics']);
$router->post('/non_visit_days/{contract_id}/{id}/toggle-active', ['NonVisitDayController', 'toggleActive']);

// 企業関連(管理者のみ)
$router->get('/companies', ['CompanyController', 'index']);
$router->get('/companies/create', ['CompanyController', 'create']);
$router->post('/companies', ['CompanyController', 'store']);
$router->get('/companies/{id}/edit', ['CompanyController', 'edit']);
$router->post('/companies/{id}', ['CompanyController', 'update']);

// 拠点関連(管理者のみ)
$router->get('/branches', ['BranchController', 'index']);
$router->get('/branches/create', ['BranchController', 'create']);
$router->post('/branches', ['BranchController', 'store']);
$router->get('/branches/{id}/edit', ['BranchController', 'edit']);
$router->post('/branches/{id}', ['BranchController', 'update']);

// ユーザー関連(管理者のみ)
$router->get('/users', ['UserController', 'index']);
$router->get('/users/create', ['UserController', 'create']);
$router->post('/users', ['UserController', 'store']);
$router->get('/users/{id}/edit', ['UserController', 'edit']);
$router->post('/users/{id}', ['UserController', 'update']);
$router->get('/users/api/branches', ['UserController','getBranchesByCompany']);
$router->post('/users/{id}/send-invitation', ['UserController', 'sendInvitation']);

// パスワード変更
$router->get('/password/change', ['UserController', 'changePassword']);
$router->post('/password/change', ['UserController', 'updatePassword']);

// パスワードリセット関連
$router->get('/password-request', ['PasswordResetController', 'requestForm']);
$router->post('/password-send-link', ['PasswordResetController', 'sendResetLink']);
$router->get('/password-reset', ['PasswordResetController', 'resetForm']);
$router->post('/password-update', ['PasswordResetController', 'updatePassword']);

// 月次締め処理関連
$router->get('/closing', ['ClosingController', 'index']);
$router->get('/closing/simulation/{id}', ['ClosingController', 'simulation']);
$router->get('/closing/simulation', ['ClosingController', 'simulation']);
$router->post('/closing/finalize/{id}', ['ClosingController', 'finalize']);
$router->post('/closing/finalize', ['ClosingController', 'finalize']);
$router->post('/closing/reopen/{id}', ['ClosingController', 'reopen']);
$router->post('/closing/reopen', ['ClosingController', 'reopen']);
$router->get('/closing/detail/{id}', ['ClosingController', 'detail']);

// 企業による締め処理の承認・差戻し
$router->post('/closing/companyApprove/{id}', ['ClosingController', 'companyApprove']);
$router->post('/closing/companyApprove', ['ClosingController', 'companyApprove']);
$router->post('/closing/companyReject/{id}', ['ClosingController', 'companyReject']);
$router->post('/closing/companyReject', ['ClosingController', 'companyReject']);
$router->post('/closing/bulk-company-approve', ['ClosingController', 'bulkCompanyApprove']);

// 管理者による企業承認の取り消し
$router->post('/closing/revokeCompanyApproval/{id}', ['ClosingController', 'revokeCompanyApproval']);
$router->post('/closing/revokeCompanyApproval', ['ClosingController', 'revokeCompanyApproval']);

// API エンドポイント
$router->get('/api/branches/by-company/{id}', ['ApiController', 'getBranchesByCompany']);
$router->get('/api/branches/{company_id}', ['ApiController', 'getBranchesByCompany']);
$router->post('/api/contracts/check-duplicate', ['ApiController', 'checkDuplicateContract']);
$router->post('/api/contracts/check-closing-status', ['ServiceRecordController', 'checkClosingStatusApi']);
$router->get('/api/service_records/{id}', ['ServiceRecordController', 'getServiceRecordDetail']);
$router->post('/api/service_records/check-time-overlap', ['ServiceRecordController', 'checkTimeOverlapApi']);
$router->post('/api/service_records/contracts-by-date', ['ServiceRecordController', 'getContractsByServiceDate']);
$router->get('/api/contracts/{id}/monthly-usage', ['ApiController', 'getContractMonthlyUsage']);
$router->get('/api/contracts/{id}/monthly-usage-detail', ['ApiController', 'getContractMonthlyUsageDetail']);
$router->post('/api/contracts/check-visit-month', ['ApiController', 'checkVisitMonth']);
$router->post('/api/contracts/check-cumulative-hours', ['ApiController', 'checkCumulativeHours']);
$router->get('/api/contracts/{id}/non-visit-days', ['ContractController', 'getNonVisitDaysApi']);

// 管理者用:交通費承認・差戻し
$router->post('/travel_expenses/bulk-approve', ['TravelExpenseController', 'bulkApprove']);
$router->post('/travel_expenses/{id}/approve', ['TravelExpenseController', 'approve']);
$router->post('/travel_expenses/{id}/reject', ['TravelExpenseController', 'reject']);
$router->post('/travel_expenses/{id}/unapprove', ['TravelExpenseController', 'unapprove']);

// 交通費関連(産業医・管理者のみ)
$router->get('/travel_expenses', ['TravelExpenseController', 'index']);
$router->get('/travel_expenses/create/{service_record_id}', ['TravelExpenseController', 'create']);
$router->post('/travel_expenses', ['TravelExpenseController', 'store']);
$router->get('/travel_expenses/{id}', ['TravelExpenseController', 'show']);
$router->get('/travel_expenses/{id}/edit', ['TravelExpenseController', 'edit']);
$router->post('/travel_expenses/{id}', ['TravelExpenseController', 'update']);
$router->post('/travel_expenses/{id}/delete', ['TravelExpenseController', 'delete']);
$router->get('/api/travel_expenses/contract/{id}', ['TravelExpenseController', 'getByContract']);
$router->get('/api/travel_expenses/{id}', ['TravelExpenseController', 'getDetail']);
$router->get('/api/travel_expenses/service_record/{service_record_id}', ['TravelExpenseController', 'getByServiceRecord']);

// 交通費テンプレート関連のAPI
$router->get('/api/travel_expenses/templates', ['TravelExpenseController', 'getTemplates']);
$router->get('/api/travel_expenses/template/{id}', ['TravelExpenseController', 'getTemplate']);
$router->post('/api/travel_expenses/template/{id}/name', ['TravelExpenseController', 'updateTemplateName']);
$router->post('/travel_expenses/template/{id}/delete', ['TravelExpenseController', 'deleteTemplate']);


// API エンドポイント(交通費関連)
$router->get('/api/travel_expenses/{id}', ['TravelExpenseController', 'getDetail']);
$router->get('/api/travel_expenses/service_record/{service_record_id}', ['TravelExpenseController', 'getByServiceRecord']);

// 請求書設定(管理者のみ)
$router->get('/invoice_settings', ['InvoiceSettingController', 'index']);
$router->post('/invoice_settings', ['InvoiceSettingController', 'store']);

// 請求書PDFダウンロード(産業医・管理者のみ)
$router->get('/invoice-download/{id}', ['InvoiceDownloadController', 'download']);

// 2FA検証
$router->get('/login/verify-2fa', ['AuthController', 'showVerify2FA']);
$router->post('/login/verify-2fa', ['AuthController', 'verify2FA']);

// 2FA設定
$router->get('/two-factor/setup', ['TwoFactorController', 'showSetup']);
$router->post('/two-factor/enable', ['TwoFactorController', 'enable']);
$router->get('/two-factor/recovery-codes', ['TwoFactorController', 'showRecoveryCodes']);
$router->post('/two-factor/confirm-codes', ['TwoFactorController', 'confirmRecoveryCodes']);
$router->post('/two-factor/disable', ['TwoFactorController', 'disable']);

// セキュリティ設定
$router->get('/settings/security', ['SettingsController', 'security']);
$router->post('/settings/revoke-session', ['SettingsController', 'revokeSession']);
$router->post('/settings/revoke-all-sessions', ['SettingsController', 'revokeAllSessions']);

// 請求CSV出力(管理者のみ)
$router->get('/csv-export', ['CsvExportController', 'index']);
$router->get('/api/csv-export/available-months', ['CsvExportController', 'getAvailableMonthsApi']);
$router->post('/csv-export/download', ['CsvExportController', 'download']);

// メール送信ログ管理(管理者のみ)
$router->get('/email-logs', ['EmailLogController', 'index']);
$router->get('/email-logs/detail', ['EmailLogController', 'detail']);
$router->post('/email-logs/delete', ['EmailLogController', 'delete']);
$router->post('/email-logs/bulk-delete', ['EmailLogController', 'bulkDelete']);

// ===== 補助科目マスタ =====

// 一覧表示（インライン編集版）
$router->get('/subsidiary_subjects', ['SubsidiarySubjectController', 'index']);

// Ajax API
$router->post('/subsidiary_subjects/createAjax', ['SubsidiarySubjectController', 'createAjax']);
$router->post('/subsidiary_subjects/updateAjax/{id}', ['SubsidiarySubjectController', 'updateAjax']);
$router->post('/subsidiary_subjects/deleteAjax/{id}', ['SubsidiarySubjectController', 'deleteAjax']);
$router->post('/subsidiary_subjects/updateOrder', ['SubsidiarySubjectController', 'updateOrder']);

// テスト用ルート（補助科目のルートのすぐ上に追加）
$router->get('/test-route-123', function() {
    echo "ルーティングは正常に動作しています";
    exit;
});
?>