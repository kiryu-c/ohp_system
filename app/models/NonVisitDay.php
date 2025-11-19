<?php
// app/models/NonVisitDay.php
require_once __DIR__ . '/BaseModel.php';

class NonVisitDay extends BaseModel {
    protected $table = 'contract_non_visit_days';
    
    /**
     * 契約と年度で非訪問日を検索
     */
    public function findByContractAndYear($contractId, $year) {
        $sql = "SELECT nvd.*, u.name as created_by_name
                FROM {$this->table} nvd
                LEFT JOIN users u ON nvd.created_by = u.id
                WHERE nvd.contract_id = :contract_id 
                AND nvd.year = :year
                AND nvd.is_active = 1
                ORDER BY nvd.non_visit_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year
        ]);
        
        $days = $stmt->fetchAll();
        
        // 各日付に詳細情報を追加
        foreach ($days as &$day) {
            // 日付の書式設定
            $date = new DateTime($day['non_visit_date']);
            $day['formatted_date'] = $date->format('n月j日');
            $day['day_of_week'] = $date->format('w');
            $day['day_of_week_name'] = $this->getDayOfWeekName($day['day_of_week']);
            
            // 繰り返し設定の表示
            $day['recurring_label'] = $day['is_recurring'] ? '毎年繰り返し' : '単発';
            
            // 日付までの日数計算（今年の場合）
            if ((int)$year === (int)date('Y')) {
                $today = new DateTime();
                $targetDate = new DateTime($day['non_visit_date']);
                
                if ($targetDate >= $today) {
                    $interval = $today->diff($targetDate);
                    $day['days_until'] = $interval->days;
                } else {
                    $day['days_until'] = null; // 過去の日付
                }
            } else {
                $day['days_until'] = null;
            }
        }
        unset($day);
        
        return $days;
    }
    
    /**
     * 契約の年度別非訪問日統計を取得
     */
    public function getYearlyStatistics($contractId) {
        $sql = "SELECT 
                    year,
                    COUNT(*) as total_days,
                    SUM(CASE WHEN is_recurring = 1 THEN 1 ELSE 0 END) as recurring_days,
                    SUM(CASE WHEN is_recurring = 0 THEN 1 ELSE 0 END) as one_time_days,
                    MIN(non_visit_date) as earliest_date,
                    MAX(non_visit_date) as latest_date
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND is_active = 1
                GROUP BY year
                ORDER BY year DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 繰り返し設定の統計を取得
     */
    public function getRecurringStatistics($contractId) {
        $sql = "SELECT 
                    recurring_month,
                    recurring_day,
                    description,
                    COUNT(*) as usage_count,
                    GROUP_CONCAT(year ORDER BY year DESC) as years_used
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND is_recurring = 1 
                AND is_active = 1
                GROUP BY recurring_month, recurring_day, description
                ORDER BY recurring_month, recurring_day";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        
        $stats = $stmt->fetchAll();
        
        foreach ($stats as &$stat) {
            if ($stat['recurring_month'] && $stat['recurring_day']) {
                $stat['date_display'] = $stat['recurring_month'] . '月' . $stat['recurring_day'] . '日';
            }
        }
        unset($stat);
        
        return $stats;
    }
    
    /**
     * 日付の重複チェック
     */
    public function isDuplicateDate($contractId, $date) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE contract_id = :contract_id 
                AND non_visit_date = :date 
                AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'date' => $date
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * 更新時の日付重複チェック（自分以外）
     */
    public function isDuplicateDateForUpdate($contractId, $date, $excludeId) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE contract_id = :contract_id 
                AND non_visit_date = :date 
                AND id != :exclude_id 
                AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'date' => $date,
            'exclude_id' => $excludeId
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * 前年の繰り返し設定から非訪問日をコピー
     */
    public function copyRecurringFromPreviousYear($contractId, $targetYear, $createdBy) {
        $sourceYear = $targetYear - 1;
        
        try {
            $this->db->beginTransaction();
            
            // 前年の繰り返し設定がある非訪問日を取得
            $sql = "SELECT * FROM {$this->table}
                    WHERE contract_id = :contract_id
                    AND year = :source_year
                    AND is_recurring = 1
                    AND is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'contract_id' => $contractId,
                'source_year' => $sourceYear
            ]);
            
            $sourceRecords = $stmt->fetchAll();
            $copiedCount = 0;
            
            foreach ($sourceRecords as $source) {
                // 対象年の同じ月日の日付を生成
                try {
                    $targetDate = sprintf('%04d-%02d-%02d', 
                        $targetYear, 
                        $source['recurring_month'], 
                        $source['recurring_day']
                    );
                    
                    // 日付の妥当性チェック
                    $dateObj = DateTime::createFromFormat('Y-m-d', $targetDate);
                    if (!$dateObj || $dateObj->format('Y-m-d') !== $targetDate) {
                        continue; // 無効な日付（例：2月30日）は無視
                    }
                    
                    // 既に存在しないかチェック
                    if (!$this->isDuplicateDate($contractId, $targetDate)) {
                        $newData = [
                            'contract_id' => $contractId,
                            'non_visit_date' => $targetDate,
                            'description' => $source['description'],
                            'is_recurring' => 1,
                            'recurring_month' => $source['recurring_month'],
                            'recurring_day' => $source['recurring_day'],
                            'year' => $targetYear,
                            'created_by' => $createdBy
                        ];
                        
                        if ($this->create($newData)) {
                            $copiedCount++;
                        }
                    }
                    
                } catch (Exception $e) {
                    // 個別の日付エラーは無視して続行
                    continue;
                }
            }
            
            $this->db->commit();
            return $copiedCount;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 特定の日付が非訪問日かチェック
     */
    public function isNonVisitDate($contractId, $date) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE contract_id = :contract_id 
                AND non_visit_date = :date 
                AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'date' => $date
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * 月の非訪問日一覧を取得
     */
    public function findByContractAndMonth($contractId, $year, $month) {
        $sql = "SELECT * FROM {$this->table}
                WHERE contract_id = :contract_id
                AND YEAR(non_visit_date) = :year
                AND MONTH(non_visit_date) = :month
                AND is_active = 1
                ORDER BY non_visit_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 期間内の非訪問日一覧を取得
     */
    public function findByContractAndDateRange($contractId, $startDate, $endDate) {
        $sql = "SELECT * FROM {$this->table}
                WHERE contract_id = :contract_id
                AND non_visit_date BETWEEN :start_date AND :end_date
                AND is_active = 1
                ORDER BY non_visit_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 契約の全非訪問日を取得（年度指定なし）
     */
    public function findByContract($contractId) {
        $sql = "SELECT nvd.*, u.name as created_by_name
                FROM {$this->table} nvd
                LEFT JOIN users u ON nvd.created_by = u.id
                WHERE nvd.contract_id = :contract_id 
                AND nvd.is_active = 1
                ORDER BY nvd.year DESC, nvd.non_visit_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 今後の非訪問日を取得
     */
    public function findUpcomingByContract($contractId, $limit = 10) {
        $today = date('Y-m-d');
        
        $sql = "SELECT * FROM {$this->table}
                WHERE contract_id = :contract_id
                AND non_visit_date >= :today
                AND is_active = 1
                ORDER BY non_visit_date ASC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $days = $stmt->fetchAll();
        
        foreach ($days as &$day) {
            $date = new DateTime($day['non_visit_date']);
            $day['formatted_date'] = $date->format('n月j日');
            $day['day_of_week_name'] = $this->getDayOfWeekName($date->format('w'));
            
            // 今日からの日数を計算
            $today = new DateTime();
            $interval = $today->diff($date);
            $day['days_until'] = $interval->days;
        }
        unset($day);
        
        return $days;
    }
    
    /**
     * 非訪問日の一括無効化
     */
    public function bulkDeactivate($contractId, $year) {
        $sql = "UPDATE {$this->table} 
                SET is_active = 0, updated_at = NOW()
                WHERE contract_id = :contract_id 
                AND year = :year";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year
        ]);
        
        return $stmt->rowCount();
    }
    
    /**
     * 過去の非訪問日をアーカイブ
     */
    public function archivePastDays($contractId) {
        $today = date('Y-m-d');
        
        $sql = "UPDATE {$this->table} 
                SET is_active = 0, updated_at = NOW()
                WHERE contract_id = :contract_id 
                AND non_visit_date < :today
                AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'contract_id' => $contractId,
            'today' => $today
        ]);
        
        return $stmt->rowCount();
    }
    
    /**
     * 曜日名を取得
     */
    private function getDayOfWeekName($dayOfWeek) {
        $names = ['日', '月', '火', '水', '木', '金', '土'];
        return $names[(int)$dayOfWeek] ?? '';
    }
    
    /**
     * 月の稼働日数を計算（非訪問日を除く）
     */
    public function calculateWorkingDaysInMonth($contractId, $year, $month, $weeklyDays = []) {
        // その月の非訪問日を取得
        $nonVisitDays = $this->findByContractAndMonth($contractId, $year, $month);
        $nonVisitDates = array_column($nonVisitDays, 'non_visit_date');
        
        // 月の全日付を生成
        $startDate = new DateTime("{$year}-{$month}-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        
        $workingDays = 0;
        $current = clone $startDate;
        
        while ($current <= $endDate) {
            $currentDateStr = $current->format('Y-m-d');
            $dayOfWeek = (int)$current->format('w'); // 0=日曜日, 1=月曜日, ...
            
            // 曜日が契約の訪問曜日に含まれているかチェック
            $isDayInSchedule = empty($weeklyDays) || in_array($dayOfWeek === 0 ? 7 : $dayOfWeek, $weeklyDays);
            
            // 非訪問日でない かつ 契約の訪問曜日である場合
            if ($isDayInSchedule && !in_array($currentDateStr, $nonVisitDates)) {
                $workingDays++;
            }
            
            $current->modify('+1 day');
        }
        
        return $workingDays;
    }
    
    /**
     * 年間の非訪問日数統計
     */
    public function getAnnualNonVisitDayCount($contractId, $year) {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN is_recurring = 1 THEN 1 ELSE 0 END) as recurring_count,
                    COUNT(DISTINCT MONTH(non_visit_date)) as affected_months
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND year = :year 
                AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * 非訪問日のエクスポート用データ取得
     */
    public function getExportData($contractId, $year = null) {
        $sql = "SELECT 
                    nvd.non_visit_date,
                    nvd.description,
                    nvd.is_recurring,
                    nvd.year,
                    nvd.created_at,
                    u.name as created_by_name,
                    c.company_name,
                    c.branch_name,
                    c.doctor_name
                FROM {$this->table} nvd
                LEFT JOIN users u ON nvd.created_by = u.id
                LEFT JOIN (
                    SELECT 
                        contracts.id as contract_id,
                        companies.name as company_name,
                        branches.name as branch_name,
                        users.name as doctor_name
                    FROM contracts
                    JOIN companies ON contracts.company_id = companies.id
                    JOIN branches ON contracts.branch_id = branches.id
                    JOIN users ON contracts.doctor_id = users.id
                ) c ON nvd.contract_id = c.contract_id
                WHERE nvd.contract_id = :contract_id 
                AND nvd.is_active = 1";
        
        $params = ['contract_id' => $contractId];
        
        if ($year !== null) {
            $sql .= " AND nvd.year = :year";
            $params['year'] = $year;
        }
        
        $sql .= " ORDER BY nvd.year DESC, nvd.non_visit_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 重複する繰り返し設定をチェック
     */
    public function checkDuplicateRecurring($contractId, $month, $day, $excludeId = null) {
        $sql = "SELECT id, year, description FROM {$this->table}
                WHERE contract_id = :contract_id
                AND recurring_month = :month
                AND recurring_day = :day
                AND is_recurring = 1
                AND is_active = 1";
        
        $params = [
            'contract_id' => $contractId,
            'month' => $month,
            'day' => $day
        ];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 契約削除時の関連データクリーンアップ
     */
    public function deleteByContract($contractId) {
        try {
            $this->db->beginTransaction();
            
            $sql = "DELETE FROM {$this->table} WHERE contract_id = :contract_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['contract_id' => $contractId]);
            
            $deletedCount = $stmt->rowCount();
            
            $this->db->commit();
            return $deletedCount;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * 指定された契約と年の繰り返し設定のみを取得
     * 
     * @param int $contractId 契約ID
     * @param int $year 年
     * @return array 繰り返し設定の配列
     */
    public function findRecurringByContractAndYear($contractId, $year) {
        try {
            $sql = "
                SELECT 
                    nvd.*,
                    u.name as created_by_name,
                    CASE 
                        WHEN nvd.non_visit_date >= CURDATE() THEN DATEDIFF(nvd.non_visit_date, CURDATE())
                        ELSE NULL 
                    END as days_until
                FROM {$this->table} nvd
                LEFT JOIN users u ON nvd.created_by = u.id
                WHERE nvd.contract_id = :contract_id 
                    AND nvd.year = :year
                    AND nvd.is_recurring = 1
                    AND nvd.is_active = 1
                    AND nvd.recurring_month IS NOT NULL
                    AND nvd.recurring_day IS NOT NULL
                ORDER BY nvd.non_visit_date ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 日付フォーマットと曜日情報を追加
            foreach ($results as &$result) {
                $date = new DateTime($result['non_visit_date']);
                $result['formatted_date'] = $date->format('n月j日');
                $result['day_of_week_name'] = $this->getDayOfWeekNameFromDate($date);
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log('Database error in findRecurringByContractAndYear: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 過去年の繰り返し設定を取得（最新のもののみ）
     * 
     * @param int $contractId 契約ID
     * @param int $targetYear 対象年
     * @param int $maxPastYears 遡る最大年数（デフォルト：10年）
     * @return array 繰り返し設定の配列
     */
    public function findLatestRecurringSettings($contractId, $targetYear, $maxPastYears = 10) {
        try {
            $sql = "
                SELECT DISTINCT
                    recurring_month,
                    recurring_day,
                    description,
                    MAX(year) as latest_year,
                    MAX(id) as latest_id
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                    AND year < :target_year
                    AND year >= :min_year
                    AND is_recurring = 1
                    AND recurring_month IS NOT NULL
                    AND recurring_day IS NOT NULL
                    AND is_active = 1
                GROUP BY recurring_month, recurring_day, description
                ORDER BY recurring_month, recurring_day
            ";
            
            $minYear = $targetYear - $maxPastYears;
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
            $stmt->bindParam(':target_year', $targetYear, PDO::PARAM_INT);
            $stmt->bindParam(':min_year', $minYear, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 各設定の詳細情報を取得
            $detailedResults = [];
            foreach ($results as $result) {
                $detailSql = "
                    SELECT * FROM {$this->table}
                    WHERE id = :id
                ";
                $detailStmt = $this->db->prepare($detailSql);
                $detailStmt->bindParam(':id', $result['latest_id'], PDO::PARAM_INT);
                $detailStmt->execute();
                
                $detail = $detailStmt->fetch(PDO::FETCH_ASSOC);
                if ($detail) {
                    $detailedResults[] = $detail;
                }
            }
            
            return $detailedResults;
            
        } catch (PDOException $e) {
            error_log('Database error in findLatestRecurringSettings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 繰り返し設定が指定年に適用済みかチェック
     * 
     * @param int $contractId 契約ID
     * @param int $year 年
     * @param int $month 月
     * @param int $day 日
     * @return bool 適用済みの場合true
     */
    public function isRecurringAppliedForYear($contractId, $year, $month, $day) {
        try {
            $targetDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            
            $sql = "
                SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE contract_id = :contract_id
                    AND non_visit_date = :target_date
                    AND is_active = 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
            $stmt->bindParam(':target_date', $targetDate);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log('Database error in isRecurringAppliedForYear: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * DateTimeオブジェクトから曜日名を取得
     */
    private function getDayOfWeekNameFromDate($dateObj) {
        $dayOfWeek = (int)$dateObj->format('w');
        $names = ['日', '月', '火', '水', '木', '金', '土'];
        return $names[$dayOfWeek] ?? '';
    }

}
?>