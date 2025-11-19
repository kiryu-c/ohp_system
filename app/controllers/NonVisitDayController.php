<?php
// app/controllers/NonVisitDayController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/NonVisitDay.php';
require_once __DIR__ . '/../models/Contract.php';

class NonVisitDayController extends BaseController {
    
    /**
     * 契約の非訪問日一覧表示
     */
    public function index($contractId) {
        Session::requireUserType('admin');
        
        // 契約の詳細を取得
        $contractModel = new Contract();
        $contract = $contractModel->findWithDetails($contractId);
        
        if (!$contract) {
            $this->setFlash('error', '契約が見つかりません。');
            redirect('contracts');
        }
        
        // 毎週契約のみ対象
        if ($contract['visit_frequency'] !== 'weekly') {
            $this->setFlash('error', '非訪問日の設定は毎週契約のみ可能です。');
            redirect('contracts');
        }
        
        $nonVisitDayModel = new NonVisitDay();
        
        // 年度選択（デフォルトは現在年）
        $year = (int)($_GET['year'] ?? date('Y'));
        
        // 指定年の非訪問日を取得
        $currentYearDays = $nonVisitDayModel->findByContractAndYear($contractId, $year);
        
        // 過去年の繰り返し設定データを今年の日付に変換して取得
        $recurringDaysFromPast = $this->getRecurringDaysForYear($contractId, $year);
        
        // 現在年のデータと過去年の繰り返しデータをマージ
        $nonVisitDays = $this->mergeNonVisitDays($currentYearDays, $recurringDaysFromPast);
        
        // 前年の非訪問日も取得（参考用）
        $prevYearDays = $nonVisitDayModel->findByContractAndYear($contractId, $year - 1);
        
        // 年の選択肢を生成（現在年の前後3年）
        $yearOptions = [];
        for ($i = $year - 3; $i <= $year + 3; $i++) {
            $yearOptions[] = $i;
        }
        
        ob_start();
        include __DIR__ . '/../views/non_visit_days/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 過去年の繰り返し設定を指定年の日付に変換して取得
     */
    private function getRecurringDaysForYear($contractId, $targetYear) {
        $nonVisitDayModel = new NonVisitDay();
        $recurringDays = [];
        
        // 現在年の既存データを一度だけ取得（重複チェック用）
        $currentYearDays = $nonVisitDayModel->findByContractAndYear($contractId, $targetYear);
        $existingDates = array_column($currentYearDays, 'non_visit_date');
        
        // 過去年の繰り返し設定を取得（最大10年前まで）
        for ($pastYear = $targetYear - 1; $pastYear >= $targetYear - 10; $pastYear--) {
            $pastRecurringDays = $nonVisitDayModel->findRecurringByContractAndYear($contractId, $pastYear);
            
            foreach ($pastRecurringDays as $day) {
                // 今年の同じ月日に変換
                $targetDate = sprintf('%04d-%02d-%02d', $targetYear, $day['recurring_month'], $day['recurring_day']);
                
                // 有効な日付かチェック
                if (checkdate($day['recurring_month'], $day['recurring_day'], $targetYear)) {
                    // 現在年に同じ日付の非訪問日が既に存在するかチェック
                    if (!in_array($targetDate, $existingDates)) {
                        $recurringDay = $day;
                        $recurringDay['non_visit_date'] = $targetDate;
                        $recurringDay['year'] = $targetYear;
                        $recurringDay['is_from_past_recurring'] = true; // 過去年繰り返しフラグ
                        $recurringDay['original_year'] = $pastYear; // 元の年
                        $recurringDay['can_edit'] = false; // 編集不可フラグ
                        $recurringDay['can_delete'] = false; // 削除不可フラグ
                        
                        // フォーマット済みデータを追加
                        $recurringDay['formatted_date'] = $this->formatDate($targetDate);
                        $recurringDay['day_of_week_name'] = $this->getDayOfWeekName($targetDate);
                        
                        // 日数計算
                        $today = new DateTime();
                        $visitDate = new DateTime($targetDate);
                        if ($visitDate >= $today) {
                            $recurringDay['days_until'] = $today->diff($visitDate)->days;
                        } else {
                            $recurringDay['days_until'] = null;
                        }
                        
                        $recurringDays[] = $recurringDay;
                    }
                }
            }
        }
        
        return $recurringDays;
    }
    
    /**
     * 日付をフォーマット
     */
    private function formatDate($date) {
        $dateObj = new DateTime($date);
        return $dateObj->format('n月j日');
    }
    
    /**
     * 曜日名を取得
     */
    private function getDayOfWeekName($date) {
        $dateObj = new DateTime($date);
        $dayOfWeek = (int)$dateObj->format('w');
        $names = ['日', '月', '火', '水', '木', '金', '土'];
        return $names[$dayOfWeek] ?? '';
    }
    
    /**
     * 現在年のデータと過去年の繰り返しデータをマージ
     */
    private function mergeNonVisitDays($currentYearDays, $recurringDaysFromPast) {
        $merged = [];
        
        // 現在年のデータを追加（編集・削除可能）
        foreach ($currentYearDays as $day) {
            $day['is_from_past_recurring'] = false;
            $day['can_edit'] = true;
            $day['can_delete'] = true;
            $merged[] = $day;
        }
        
        // 過去年の繰り返しデータを追加（編集・削除不可）
        $merged = array_merge($merged, $recurringDaysFromPast);
        
        // 日付順でソート
        usort($merged, function($a, $b) {
            return strcmp($a['non_visit_date'], $b['non_visit_date']);
        });
        
        return $merged;
    }
    
    // ... 以下のメソッドは元のコードと同じ
    
    /**
     * 非訪問日新規作成フォーム
     */
    public function create($contractId) {
        Session::requireUserType('admin');
        
        // 契約の詳細を取得
        $contractModel = new Contract();
        $contract = $contractModel->findWithDetails($contractId);
        
        if (!$contract) {
            $this->setFlash('error', '契約が見つかりません。');
            redirect('contracts');
        }
        
        // 毎週契約のみ対象
        if ($contract['visit_frequency'] !== 'weekly') {
            $this->setFlash('error', '非訪問日の設定は毎週契約のみ可能です。');
            redirect('contracts');
        }
        
        $year = (int)($_GET['year'] ?? date('Y'));
        
        ob_start();
        include __DIR__ . '/../views/non_visit_days/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 非訪問日作成処理
     */
    public function store($contractId) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect("non_visit_days/{$contractId}");
        }
        
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract || $contract['visit_frequency'] !== 'weekly') {
            $this->setFlash('error', '無効な契約です。');
            redirect('contracts');
        }
        
        $nonVisitDate = $_POST['non_visit_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $isRecurring = (int)($_POST['is_recurring'] ?? 0);
        $year = (int)($_POST['year'] ?? date('Y'));
        
        // バリデーション
        $errors = [];
        
        if (empty($nonVisitDate)) {
            $errors[] = '非訪問日を入力してください。';
        } else {
            // 日付の妥当性チェック
            $date = DateTime::createFromFormat('Y-m-d', $nonVisitDate);
            if (!$date || $date->format('Y-m-d') !== $nonVisitDate) {
                $errors[] = '正しい日付を入力してください。';
            } else {
                // 指定年の日付かチェック
                if ((int)$date->format('Y') !== $year) {
                    $errors[] = '指定された年の日付を入力してください。';
                }
            }
        }
        
        if (empty($description)) {
            $errors[] = '説明を入力してください。';
        }
        
        // 重複チェック
        if (!empty($nonVisitDate)) {
            $nonVisitDayModel = new NonVisitDay();
            if ($nonVisitDayModel->isDuplicateDate($contractId, $nonVisitDate)) {
                $errors[] = 'この日付は既に登録されています。';
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect("non_visit_days/{$contractId}/create?year={$year}");
        }
        
        // 繰り返し設定の場合は月日を抽出
        $recurringMonth = null;
        $recurringDay = null;
        if ($isRecurring && !empty($nonVisitDate)) {
            $date = new DateTime($nonVisitDate);
            $recurringMonth = (int)$date->format('n');
            $recurringDay = (int)$date->format('j');
        }
        
        $data = [
            'contract_id' => $contractId,
            'non_visit_date' => $nonVisitDate,
            'description' => $description,
            'is_recurring' => $isRecurring,
            'recurring_month' => $recurringMonth,
            'recurring_day' => $recurringDay,
            'year' => $year,
            'created_by' => Session::get('user_id')
        ];
        
        if ($nonVisitDayModel->create($data)) {
            $this->setFlash('success', '非訪問日を登録しました。');
        } else {
            $this->setFlash('error', '非訪問日の登録に失敗しました。');
        }
        
        redirect("non_visit_days/{$contractId}?year={$year}");
    }
    
    /**
     * 非訪問日編集フォーム
     */
    public function edit($contractId, $id) {
        Session::requireUserType('admin');
        
        $contractModel = new Contract();
        $contract = $contractModel->findWithDetails($contractId);
        
        if (!$contract || $contract['visit_frequency'] !== 'weekly') {
            $this->setFlash('error', '無効な契約です。');
            redirect('contracts');
        }
        
        $nonVisitDayModel = new NonVisitDay();
        $nonVisitDay = $nonVisitDayModel->findById($id);
        
        if (!$nonVisitDay || $nonVisitDay['contract_id'] != $contractId) {
            $this->setFlash('error', '非訪問日が見つかりません。');
            redirect("non_visit_days/{$contractId}");
        }
        
        // 年度パラメータの取得
        $year = (int)($_GET['year'] ?? date('Y', strtotime($nonVisitDay['non_visit_date'])));
        
        // フォーマット済みデータを追加
        $nonVisitDay['formatted_date'] = format_date($nonVisitDay['non_visit_date']);
        $nonVisitDay['day_of_week_name'] = get_day_of_week_name($nonVisitDay['non_visit_date']);
        
        ob_start();
        include __DIR__ . '/../views/non_visit_days/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 非訪問日更新処理
     */
    public function update($contractId, $id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect("non_visit_days/{$contractId}/{$id}/edit");
        }
        
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract || $contract['visit_frequency'] !== 'weekly') {
            $this->setFlash('error', '無効な契約です。');
            redirect('contracts');
        }
        
        $nonVisitDayModel = new NonVisitDay();
        $nonVisitDay = $nonVisitDayModel->findById($id);
        
        if (!$nonVisitDay || $nonVisitDay['contract_id'] != $contractId) {
            $this->setFlash('error', '非訪問日が見つかりません。');
            redirect("non_visit_days/{$contractId}");
        }
        
        $nonVisitDate = $_POST['non_visit_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $isRecurring = (int)($_POST['is_recurring'] ?? 0);
        $year = (int)($_POST['year'] ?? $nonVisitDay['year']);
        
        // バリデーション
        $errors = [];
        
        if (empty($nonVisitDate)) {
            $errors[] = '非訪問日を入力してください。';
        } else {
            // 日付の妥当性チェック
            $date = DateTime::createFromFormat('Y-m-d', $nonVisitDate);
            if (!$date || $date->format('Y-m-d') !== $nonVisitDate) {
                $errors[] = '正しい日付を入力してください。';
            } else {
                // 指定年の日付かチェック
                if ((int)$date->format('Y') !== $year) {
                    $errors[] = '指定された年の日付を入力してください。';
                }
            }
        }
        
        if (empty($description)) {
            $errors[] = '説明を入力してください。';
        }
        
        // 重複チェック（自分以外）
        if (!empty($nonVisitDate)) {
            if ($nonVisitDayModel->isDuplicateDateForUpdate($contractId, $nonVisitDate, $id)) {
                $errors[] = 'この日付は既に登録されています。';
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect("non_visit_days/{$contractId}/{$id}/edit");
        }
        
        // 繰り返し設定の場合は月日を抽出
        $recurringMonth = null;
        $recurringDay = null;
        if ($isRecurring && !empty($nonVisitDate)) {
            $date = new DateTime($nonVisitDate);
            $recurringMonth = (int)$date->format('n');
            $recurringDay = (int)$date->format('j');
        }
        
        $data = [
            'non_visit_date' => $nonVisitDate,
            'description' => $description,
            'is_recurring' => $isRecurring,
            'recurring_month' => $recurringMonth,
            'recurring_day' => $recurringDay,
            'year' => $year
        ];
        
        if ($nonVisitDayModel->update($id, $data)) {
            $this->setFlash('success', '非訪問日を更新しました。');
        } else {
            $this->setFlash('error', '非訪問日の更新に失敗しました。');
        }
        
        redirect("non_visit_days/{$contractId}?year={$year}");
    }
    
    /**
     * 非訪問日削除処理
     */
    public function delete($contractId, $id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect("non_visit_days/{$contractId}");
        }
        
        $nonVisitDayModel = new NonVisitDay();
        $nonVisitDay = $nonVisitDayModel->findById($id);
        
        if (!$nonVisitDay || $nonVisitDay['contract_id'] != $contractId) {
            $this->setFlash('error', '非訪問日が見つかりません。');
            redirect("non_visit_days/{$contractId}");
        }
        
        if ($nonVisitDayModel->delete($id)) {
            $this->setFlash('success', '非訪問日を削除しました。');
        } else {
            $this->setFlash('error', '非訪問日の削除に失敗しました。');
        }
        
        redirect("non_visit_days/{$contractId}?year={$nonVisitDay['year']}");
    }
    
    /**
     * 前年の非訪問日を一括コピー
     */
    public function copyFromPreviousYear($contractId) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect("non_visit_days/{$contractId}");
        }
        
        $targetYear = (int)($_POST['target_year'] ?? date('Y'));
        $sourceYear = $targetYear - 1;
        
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract || $contract['visit_frequency'] !== 'weekly') {
            $this->setFlash('error', '無効な契約です。');
            redirect('contracts');
        }
        
        $nonVisitDayModel = new NonVisitDay();
        
        try {
            $copiedCount = $nonVisitDayModel->copyRecurringFromPreviousYear(
                $contractId, 
                $targetYear, 
                Session::get('user_id')
            );
            
            if ($copiedCount > 0) {
                $this->setFlash('success', "前年の繰り返し設定から{$copiedCount}件の非訪問日をコピーしました。");
            } else {
                $this->setFlash('info', 'コピー可能な繰り返し設定が見つかりませんでした。');
            }
            
        } catch (Exception $e) {
            $this->setFlash('error', '前年データのコピーに失敗しました: ' . $e->getMessage());
        }
        
        redirect("non_visit_days/{$contractId}?year={$targetYear}");
    }
    
    /**
     * 年度別の非訪問日統計
     */
    public function statistics($contractId) {
        Session::requireUserType('admin');
        
        $contractModel = new Contract();
        $contract = $contractModel->findWithDetails($contractId);
        
        if (!$contract || $contract['visit_frequency'] !== 'weekly') {
            $this->setFlash('error', '無効な契約です。');
            redirect('contracts');
        }
        
        $nonVisitDayModel = new NonVisitDay();
        
        // 年度別統計を取得
        $yearlyStats = $nonVisitDayModel->getYearlyStatistics($contractId);
        
        // 繰り返し設定の統計
        $recurringStats = $nonVisitDayModel->getRecurringStatistics($contractId);
        
        ob_start();
        include __DIR__ . '/../views/non_visit_days/statistics.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 有効/無効切り替え（Ajax対応）
     */
    public function toggleActive($contractId, $id) {
        Session::requireUserType('admin');
        
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            if (!$this->validateCsrf()) {
                $error = 'セキュリティトークンが無効です。ページを再読み込みしてください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect("non_visit_days/{$contractId}");
                    return;
                }
            }
            
            $nonVisitDayModel = new NonVisitDay();
            $nonVisitDay = $nonVisitDayModel->findById($id);
            
            if (!$nonVisitDay || $nonVisitDay['contract_id'] != $contractId) {
                $error = '非訪問日が見つかりません';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect("non_visit_days/{$contractId}");
                    return;
                }
            }
            
            $newStatus = $nonVisitDay['is_active'] ? 0 : 1;
            $data = ['is_active' => $newStatus];
            
            if ($nonVisitDayModel->update($id, $data)) {
                $statusText = $newStatus ? '有効' : '無効';
                $successMessage = "非訪問日を'{$statusText}に変更しました。";
                
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage,
                        'new_status' => $newStatus,
                        'status_text' => $statusText
                    ]);
                    return;
                } else {
                    $this->setFlash('success', $successMessage);
                    redirect("non_visit_days/{$contractId}");
                    return;
                }
            } else {
                $error = 'ステータスの変更に失敗しました';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect("non_visit_days/{$contractId}");
                    return;
                }
            }
            
        } catch (Exception $e) {
            error_log('NonVisitDay toggle error: ' . $e->getMessage());
            
            $errorMessage = 'システムエラーが発生しました: ' . $e->getMessage();
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                return;
            } else {
                $this->setFlash('error', $errorMessage);
                redirect("non_visit_days/{$contractId}");
                return;
            }
        }
    }
}
?>