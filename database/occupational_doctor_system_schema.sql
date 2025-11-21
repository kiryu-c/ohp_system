--===================================================================

--
-- テーブルの構造 `invoice_settings`
--

CREATE TABLE `invoice_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL COMMENT '企業名',
  `postal_code` varchar(10) NOT NULL COMMENT '郵便番号（例：123-4567）',
  `address` text NOT NULL COMMENT '住所',
  `department_name` varchar(255) DEFAULT NULL COMMENT '係名・部署名',
  `payment_month_offset` tinyint(4) DEFAULT 1 COMMENT '支払月（1:翌月、2:翌々月）',
  `payment_day_of_month` tinyint(4) DEFAULT 31 COMMENT '支払日（毎月X日）',
  `invoice_note` text DEFAULT NULL COMMENT '請求書備考欄のデフォルト文言',
  `tax_rate` decimal(4,2) DEFAULT 0.10 COMMENT '消費税率（デフォルト10%）',
  `created_by` int(11) NOT NULL COMMENT '作成者ユーザーID',
  `updated_by` int(11) DEFAULT NULL COMMENT '更新者ユーザーID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='請求書設定マスタ。システム全体で1レコードのみ保持する想定。支払期日は「翌月または翌々月のN日」形式で設定。';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `invoice_settings`
--
ALTER TABLE `invoice_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_updated_by` (`updated_by`),
  ADD KEY `idx_invoice_settings_created_at` (`created_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `invoice_settings`
--
ALTER TABLE `invoice_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `invoice_settings`
--
ALTER TABLE `invoice_settings`
  ADD CONSTRAINT `invoice_settings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoice_settings_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);
COMMIT;


--===================================================================
--
-- テーブルの構造 `subsidiary_subjects`
--

CREATE TABLE `subsidiary_subjects` (
  `id` int(11) NOT NULL,
  `number` int(11) NOT NULL COMMENT '補助科目番号（数値）',
  `name` varchar(100) NOT NULL COMMENT '補助科目名称',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '有効フラグ(1:有効、0:無効)',
  `created_by` int(11) NOT NULL COMMENT '作成者ユーザーID',
  `updated_by` int(11) DEFAULT NULL COMMENT '更新者ユーザーID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='補助科目マスタ。会計システムで使用する補助科目を管理。';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `subsidiary_subjects`
--
ALTER TABLE `subsidiary_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_subsidiary_subject_number` (`number`),
  ADD KEY `idx_subsidiary_subject_name` (`name`),
  ADD KEY `idx_subsidiary_subject_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_updated_by` (`updated_by`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `subsidiary_subjects`
--
ALTER TABLE `subsidiary_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `subsidiary_subjects`
--
ALTER TABLE `subsidiary_subjects`
  ADD CONSTRAINT `subsidiary_subjects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `subsidiary_subjects_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);
COMMIT;

--===================================================================

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `login_id` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL COMMENT '郵便番号（例：123-4567）',
  `address` text DEFAULT NULL COMMENT '住所',
  `trade_name` varchar(255) DEFAULT NULL COMMENT '屋号（任意）',
  `bank_name` varchar(100) DEFAULT NULL COMMENT '銀行名',
  `bank_branch_name` varchar(100) DEFAULT NULL COMMENT '支店名',
  `bank_account_type` enum('ordinary','current','savings') DEFAULT 'ordinary' COMMENT '口座種別（普通/当座/貯蓄）',
  `bank_account_number` varchar(20) DEFAULT NULL COMMENT '口座番号',
  `bank_account_holder` varchar(255) DEFAULT NULL COMMENT '口座名義',
  `user_type` enum('doctor','company','admin') NOT NULL,
  `contract_type` enum('corporate','individual') DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `two_factor_enabled` tinyint(1) DEFAULT 0 COMMENT '2FA有効フラグ',
  `two_factor_secret` varchar(255) DEFAULT NULL COMMENT '2FA秘密鍵(暗号化)',
  `two_factor_recovery_codes` text DEFAULT NULL COMMENT 'リカバリーコード(JSON、暗号化)',
  `two_factor_enabled_at` timestamp NULL DEFAULT NULL COMMENT '2FA有効化日時',
  `business_classification` enum('taxable','tax_exempt') DEFAULT NULL COMMENT '事業者区分(課税事業者/免税事業者)',
  `invoice_registration_number` varchar(14) DEFAULT NULL COMMENT 'インボイス登録番号(T+13桁)',
  `partner_id` varchar(10) DEFAULT NULL COMMENT 'パートナーID（産業医のみ使用、10桁までの文字列）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login_id` (`login_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_login_id` (`login_id`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_login_attempts` (`login_attempts`),
  ADD KEY `idx_locked_until` (`locked_until`),
  ADD KEY `idx_last_login` (`last_login_at`),
  ADD KEY `idx_postal_code` (`postal_code`),
  ADD KEY `idx_two_factor_enabled` (`two_factor_enabled`),
  ADD KEY `idx_business_classification` (`business_classification`),
  ADD KEY `idx_invoice_registration_number` (`invoice_registration_number`),
  ADD KEY `idx_partner_id` (`partner_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;



INSERT INTO `users` (
    `login_id`,
    `password`,
    `name`,
    `email`,
    `phone`,
    `user_type`,
    `contract_type`,
    `company_id`,
    `created_at`,
    `updated_at`,
    `last_login_at`,
    `login_attempts`,
    `locked_until`,
    `is_active`
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password_hash('password', PASSWORD_DEFAULT)
    '管理者',
    'admin@example.com',
    NULL,
    'admin',
    NULL,
    NULL,
    NOW(),
    NOW(),
    NULL,
    0,
    NULL,
    1
);

--===================================================================

--
-- テーブルの構造 `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fingerprint` varchar(64) DEFAULT NULL,
  `security_flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_flags`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_fingerprint` (`fingerprint`);

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

--===================================================================
--
-- テーブルの構造 `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_table` varchar(50) NOT NULL,
  `target_id` int(11) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_target` (`target_table`,`target_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;


--===================================================================
--
-- テーブルの構造 `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

--===================================================================
--
-- テーブルの構造 `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_id` (`company_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `branches_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;


--===================================================================
--
-- テーブルの構造 `user_branch_mappings`
--

CREATE TABLE `user_branch_mappings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ユーザーID',
  `branch_id` int(11) NOT NULL COMMENT '拠点ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1 COMMENT '有効フラグ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ユーザーと拠点の紐づけテーブル';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `user_branch_mappings`
--
ALTER TABLE `user_branch_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_branch` (`user_id`,`branch_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `user_branch_mappings`
--
ALTER TABLE `user_branch_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `user_branch_mappings`
--
ALTER TABLE `user_branch_mappings`
  ADD CONSTRAINT `user_branch_mappings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_branch_mappings_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;
COMMIT;

--===================================================================
--
-- テーブルの構造 `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `regular_visit_hours` decimal(4,1) DEFAULT NULL COMMENT '定期訪問時間（月間）※スポット契約の場合はNULL',
  `visit_frequency` enum('monthly','bimonthly','weekly','spot') NOT NULL DEFAULT 'monthly' COMMENT '訪問頻度（monthly:毎月、bimonthly:隔月、weekly:毎週、spot:スポット）',
  `bimonthly_type` enum('even','odd') DEFAULT NULL COMMENT '隔月訪問の月次区分(even:偶数月, odd:奇数月)',
  `exclude_holidays` tinyint(1) NOT NULL DEFAULT 1 COMMENT '祝日を非訪問日とするか（0:訪問可能、1:非訪問日）',
  `taxi_allowed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'タクシー利用可否（0:不可、1:可）',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `contract_status` enum('active','inactive') DEFAULT 'active',
  `tax_type` enum('exclusive','inclusive') DEFAULT 'exclusive' COMMENT '税種別(exclusive:外税, inclusive:内税)',
  `contract_file_path` varchar(255) DEFAULT NULL,
  `contract_file_name` varchar(255) DEFAULT NULL,
  `contract_file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `regular_visit_rate` decimal(8,0) DEFAULT NULL COMMENT '定期訪問料金（1時間あたり）',
  `regular_extension_rate` decimal(8,0) DEFAULT NULL COMMENT '定期延長料金（15分あたり）',
  `emergency_visit_rate` decimal(8,0) DEFAULT NULL COMMENT '臨時訪問料金（15分あたり）',
  `document_consultation_rate` decimal(8,0) DEFAULT NULL COMMENT '書面作成・遠隔相談料金（1回あたり）',
  `spot_rate` decimal(8,0) DEFAULT NULL COMMENT 'スポット料金（15分あたり）',
  `use_remote_consultation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '遠隔相談を使用する（0:使用しない、1:使用する）',
  `use_document_creation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '書面作成を使用する（0:使用しない、1:使用する）',
  `exclusive_registration_fee` decimal(10,0) DEFAULT NULL COMMENT '専属登録料(事務手数料)',
  `terminated_at` timestamp NULL DEFAULT NULL,
  `terminated_by` int(11) DEFAULT NULL,
  `reactivated_at` timestamp NULL DEFAULT NULL,
  `reactivated_by` int(11) DEFAULT NULL,
  `version_number` int(11) NOT NULL DEFAULT 1 COMMENT 'バージョン番号',
  `effective_date` date NOT NULL COMMENT '反映日(必ず1日)',
  `effective_end_date` date DEFAULT NULL COMMENT '反映終了日',
  `parent_contract_id` int(11) DEFAULT NULL COMMENT '元の契約ID(初版はNULL)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `idx_doctor_company` (`doctor_id`,`company_id`),
  ADD KEY `idx_contract_status` (`contract_status`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_visit_frequency` (`visit_frequency`),
  ADD KEY `idx_tax_type` (`tax_type`),
  ADD KEY `idx_bimonthly_type` (`bimonthly_type`),
  ADD KEY `contracts_terminated_by_fk` (`terminated_by`),
  ADD KEY `contracts_reactivated_by_fk` (`reactivated_by`),
  ADD KEY `idx_parent_contract` (`parent_contract_id`),
  ADD KEY `idx_version` (`version_number`),
  ADD KEY `idx_effective_dates` (`effective_date`,`effective_end_date`),
  ADD KEY `idx_effective_end_null` (`effective_end_date`),
  ADD KEY `idx_use_remote_consultation` (`use_remote_consultation`),
  ADD KEY `idx_use_document_creation` (`use_document_creation`),
  ADD KEY `idx_spot_rate` (`spot_rate`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_reactivated_by_fk` FOREIGN KEY (`reactivated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracts_terminated_by_fk` FOREIGN KEY (`terminated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;



--===================================================================


--
-- テーブルの構造 `contract_weekly_schedules`
--

CREATE TABLE `contract_weekly_schedules` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL COMMENT '契約ID',
  `day_of_week` tinyint(1) NOT NULL COMMENT '曜日 (1=月曜日, 2=火曜日, ..., 7=日曜日)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='契約の週間スケジュール';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `contract_weekly_schedules`
--
ALTER TABLE `contract_weekly_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contract_day` (`contract_id`,`day_of_week`),
  ADD KEY `idx_contract_id` (`contract_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `contract_weekly_schedules`
--
ALTER TABLE `contract_weekly_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `contract_weekly_schedules`
--
ALTER TABLE `contract_weekly_schedules`
  ADD CONSTRAINT `contract_weekly_schedules_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE;
COMMIT;


--===================================================================

--
-- テーブルの構造 `contract_non_visit_days`
--

CREATE TABLE `contract_non_visit_days` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL COMMENT '契約ID',
  `non_visit_date` date NOT NULL COMMENT '非訪問日',
  `description` varchar(200) DEFAULT NULL COMMENT '非訪問日の説明（年末年始、お盆休み、創立記念日など）',
  `is_recurring` tinyint(1) DEFAULT 0 COMMENT '毎年繰り返すかどうか（0:単発、1:毎年繰り返し）',
  `recurring_month` tinyint(2) DEFAULT NULL COMMENT '繰り返し月（1-12、is_recurring=1の場合のみ）',
  `recurring_day` tinyint(2) DEFAULT NULL COMMENT '繰り返し日（1-31、is_recurring=1の場合のみ）',
  `year` int(4) NOT NULL COMMENT '適用年',
  `created_by` int(11) NOT NULL COMMENT '登録者（管理者ユーザーID）',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1 COMMENT '有効フラグ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='契約別非訪問日マスタ';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `contract_non_visit_days`
--
ALTER TABLE `contract_non_visit_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contract_date` (`contract_id`,`non_visit_date`),
  ADD KEY `idx_contract_id` (`contract_id`),
  ADD KEY `idx_non_visit_date` (`non_visit_date`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_recurring` (`is_recurring`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_contract_year` (`contract_id`,`year`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `contract_non_visit_days`
--
ALTER TABLE `contract_non_visit_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `contract_non_visit_days`
--
ALTER TABLE `contract_non_visit_days`
  ADD CONSTRAINT `contract_non_visit_days_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contract_non_visit_days_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

--===================================================================

--
-- テーブルの構造 `service_records`
--

CREATE TABLE `service_records` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `service_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `service_hours` decimal(4,2) NOT NULL,
  `description` text DEFAULT NULL,
  `overtime_reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','finalized') DEFAULT 'pending',
  `company_comment` text DEFAULT NULL,
  `is_overtime` tinyint(1) DEFAULT 0,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `service_type` enum('regular','emergency','extension','document','remote_consultation','absence','spot','other') NOT NULL DEFAULT 'regular' COMMENT '役務種別（regular:定期訪問、emergency:臨時訪問、extension:延長、document:書面作成、remote_consultation:遠隔相談、absence:欠勤、spot:スポット対応、other:その他）',
  `visit_type` enum('visit','online') DEFAULT 'visit' COMMENT '訪問種別（訪問/オンライン）',
  `is_auto_split` tinyint(1) DEFAULT 0 COMMENT '自動分割記録フラグ',
  `closing_period` varchar(7) DEFAULT NULL COMMENT '締め対象月（YYYY-MM形式）',
  `is_closed` tinyint(1) DEFAULT 0 COMMENT '締め処理済みフラグ',
  `closed_at` timestamp NULL DEFAULT NULL COMMENT '締め処理実行日時',
  `closed_by` int(11) DEFAULT NULL COMMENT '締め処理実行者ID',
  `billing_service_type` enum('regular','regular_extension','emergency','document','remote_consultation','spot','other') DEFAULT NULL COMMENT '請求上のサービス種別（regular:定期訪問、regular_extension:定期延長、emergency:臨時訪問、document:書面作成、remote_consultation:遠隔相談、spot:スポット対応、other:その他）',
  `billing_hours` decimal(4,2) DEFAULT NULL COMMENT '請求対象時間（15分単位切り上げ後）',
  `billing_amount` decimal(10,0) DEFAULT NULL COMMENT '請求金額',
  `direct_billing_amount` decimal(10,0) DEFAULT NULL COMMENT '直接入力請求金額（その他役務種別用）',
  `unapproved_at` timestamp NULL DEFAULT NULL COMMENT '承認取り消し日時',
  `unapproved_by` int(11) DEFAULT NULL COMMENT '承認取り消し実行者ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `service_records`
--
ALTER TABLE `service_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contract_date` (`contract_id`,`service_date`),
  ADD KEY `idx_doctor_date` (`doctor_id`,`service_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_visit_type` (`visit_type`),
  ADD KEY `idx_closing_period` (`closing_period`),
  ADD KEY `idx_is_closed` (`is_closed`),
  ADD KEY `idx_closed_by` (`closed_by`),
  ADD KEY `idx_billing_service_type` (`billing_service_type`),
  ADD KEY `idx_unapproved_by` (`unapproved_by`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `service_records`
--
ALTER TABLE `service_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `service_records`
--
ALTER TABLE `service_records`
  ADD CONSTRAINT `service_records_closed_by_fk` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_records_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_records_unapproved_by_fk` FOREIGN KEY (`unapproved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;


--===================================================================

--
-- テーブルの構造 `monthly_closing_records`
--

CREATE TABLE `monthly_closing_records` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL COMMENT '契約ID',
  `doctor_id` int(11) NOT NULL COMMENT '産業医ID',
  `closing_period` varchar(7) NOT NULL COMMENT '締め対象月（YYYY-MM形式）',
  `contract_hours` decimal(5,2) NOT NULL COMMENT '契約時間（月間）',
  `total_approved_hours` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '承認済み総時間',
  `regular_hours` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '定期訪問時間',
  `regular_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '定期訪問請求額',
  `regular_billing_method` enum('contract_hours','actual_hours') DEFAULT 'contract_hours' COMMENT '定期訪問請求方法(contract_hours:契約時間, actual_hours:実働時間)',
  `actual_regular_hours` decimal(5,2) DEFAULT 0.00 COMMENT '定期訪問実働時間',
  `regular_extension_hours` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '定期延長時間',
  `regular_extension_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '定期延長請求額',
  `emergency_hours` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '臨時訪問時間',
  `emergency_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '臨時訪問請求額',
  `document_count` int(11) NOT NULL DEFAULT 0 COMMENT '書面作成回数',
  `document_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '書面作成請求額',
  `remote_consultation_count` int(11) NOT NULL DEFAULT 0 COMMENT '遠隔相談回数',
  `remote_consultation_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '遠隔相談請求額',
  `spot_hours` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'スポット対応時間',
  `spot_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT 'スポット対応請求額',
  `spot_count` int(11) NOT NULL DEFAULT 0 COMMENT 'スポット対応回数',
  `other_count` int(11) NOT NULL DEFAULT 0 COMMENT 'その他役務回数',
  `other_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT 'その他役務請求額',
  `total_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '総請求金額',
  `tax_rate` decimal(4,2) DEFAULT 0.10 COMMENT '消費税率',
  `tax_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '消費税額',
  `total_amount_with_tax` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '税込み総請求金額',
  `status` enum('draft','finalized') NOT NULL DEFAULT 'draft' COMMENT 'ステータス（下書き/確定）',
  `simulation_data` longtext DEFAULT NULL COMMENT 'シミュレーション結果JSON',
  `contract_snapshot` longtext DEFAULT NULL COMMENT '締め処理確定時の契約情報スナップショット(JSON)',
  `doctor_comment` text DEFAULT NULL COMMENT '産業医からのコメント',
  `extension_reason` text DEFAULT NULL COMMENT '定期延長理由',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `finalized_at` timestamp NULL DEFAULT NULL COMMENT '確定日時',
  `finalized_by` int(11) DEFAULT NULL COMMENT '確定実行者ID',
  `travel_expense_count` int(11) NOT NULL DEFAULT 0 COMMENT '交通費件数',
  `travel_expense_amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT '交通費金額',
  `invoice_pdf_path` varchar(255) DEFAULT NULL COMMENT '請求書PDFファイルパス',
  `company_approved` tinyint(1) DEFAULT 0 COMMENT '企業承認フラグ(0:未承認, 1:承認済み)',
  `company_approved_at` timestamp NULL DEFAULT NULL COMMENT '企業承認日時',
  `company_approved_by` int(11) DEFAULT NULL COMMENT '企業承認者ID',
  `company_rejected_at` timestamp NULL DEFAULT NULL COMMENT '企業差戻し日時',
  `company_rejected_by` int(11) DEFAULT NULL COMMENT '企業差戻し実行者ID',
  `company_rejection_reason` text DEFAULT NULL COMMENT '企業差戻し理由'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='月次締め処理記録';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `monthly_closing_records`
--
ALTER TABLE `monthly_closing_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contract_period` (`contract_id`,`closing_period`),
  ADD KEY `idx_doctor_period` (`doctor_id`,`closing_period`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_finalized_by` (`finalized_by`),
  ADD KEY `idx_company_approved` (`company_approved`),
  ADD KEY `idx_company_approved_by` (`company_approved_by`),
  ADD KEY `idx_company_rejected_by` (`company_rejected_by`),
  ADD KEY `idx_regular_billing_method` (`regular_billing_method`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `monthly_closing_records`
--
ALTER TABLE `monthly_closing_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `monthly_closing_records`
--
ALTER TABLE `monthly_closing_records`
  ADD CONSTRAINT `monthly_closing_records_company_approved_by_fk` FOREIGN KEY (`company_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `monthly_closing_records_company_rejected_by_fk` FOREIGN KEY (`company_rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `monthly_closing_records_contract_fk` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_closing_records_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_closing_records_finalized_by_fk` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;


--===================================================================
--
-- テーブルの構造 `monthly_closing_details`
--

CREATE TABLE `monthly_closing_details` (
  `id` int(11) NOT NULL,
  `closing_record_id` int(11) NOT NULL COMMENT '締め処理記録ID（monthly_closing_records.id）',
  `service_record_id` int(11) DEFAULT NULL COMMENT '役務記録ID（その他役務など個別に記録する場合）',
  `line_number` int(11) NOT NULL COMMENT '明細行番号（表示順序）',
  `item_category` enum('service','travel_expense','subtotal','tax','total') NOT NULL COMMENT '項目カテゴリ（役務/交通費/小計/消費税/合計）',
  `service_type` enum('regular','regular_extension','emergency','document','remote_consultation','spot','other') DEFAULT NULL COMMENT 'サービス種別（役務の場合）（regular:定期訪問、regular_extension:定期延長、emergency:臨時訪問、document:書面作成、remote_consultation:遠隔相談、spot:スポット対応、other:その他）',
  `item_name` varchar(100) NOT NULL COMMENT '項目名（例：定期訪問、定期延長、交通費、消費税など）',
  `unit_type` enum('hours','times','amount') DEFAULT NULL COMMENT '単位種別（時間/回数/金額）',
  `quantity` decimal(6,2) DEFAULT NULL COMMENT '数量（時間数、回数など）',
  `unit_price` decimal(10,0) DEFAULT NULL COMMENT '単価（円）',
  `amount` decimal(10,0) NOT NULL COMMENT '金額（円）',
  `tax_rate` decimal(4,2) DEFAULT NULL COMMENT '消費税率（小計・合計行の場合）',
  `description` text DEFAULT NULL COMMENT '説明・備考',
  `related_service_record_ids` text DEFAULT NULL COMMENT '関連役務記録ID（カンマ区切り）',
  `related_travel_expense_ids` text DEFAULT NULL COMMENT '関連交通費ID（カンマ区切り）',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='月次締め処理明細';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `monthly_closing_details`
--
ALTER TABLE `monthly_closing_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_closing_line` (`closing_record_id`,`line_number`),
  ADD KEY `idx_closing_record_id` (`closing_record_id`),
  ADD KEY `idx_item_category` (`item_category`),
  ADD KEY `idx_service_type` (`service_type`),
  ADD KEY `idx_service_record_id` (`service_record_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `monthly_closing_details`
--
ALTER TABLE `monthly_closing_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `monthly_closing_details`
--
ALTER TABLE `monthly_closing_details`
  ADD CONSTRAINT `monthly_closing_details_ibfk_1` FOREIGN KEY (`closing_record_id`) REFERENCES `monthly_closing_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_closing_details_service_record_fk` FOREIGN KEY (`service_record_id`) REFERENCES `service_records` (`id`) ON DELETE SET NULL;
COMMIT;

--===================================================================
--
-- テーブルの構造 `service_description_templates`
--

CREATE TABLE `service_description_templates` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL COMMENT 'NULL = システム標準テンプレート',
  `content` text NOT NULL COMMENT '役務内容',
  `is_system_template` tinyint(1) DEFAULT 0 COMMENT 'システム標準テンプレートかどうか',
  `usage_count` int(11) DEFAULT 0 COMMENT '使用回数',
  `last_used_at` datetime DEFAULT NULL COMMENT '最終使用日時',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `service_description_templates`
--
ALTER TABLE `service_description_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_system_template` (`is_system_template`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `service_description_templates`
--
ALTER TABLE `service_description_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `service_description_templates`
--
ALTER TABLE `service_description_templates`
  ADD CONSTRAINT `service_description_templates_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;


--===================================================================
--(未使用)
--
-- テーブルの構造 `template_usage_logs`
--

CREATE TABLE `template_usage_logs` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `service_record_id` int(11) DEFAULT NULL,
  `used_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `template_usage_logs`
--
ALTER TABLE `template_usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_id` (`template_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_used_at` (`used_at`),
  ADD KEY `service_record_id` (`service_record_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `template_usage_logs`
--
ALTER TABLE `template_usage_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `template_usage_logs`
--
ALTER TABLE `template_usage_logs`
  ADD CONSTRAINT `template_usage_logs_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `service_description_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_usage_logs_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_usage_logs_ibfk_3` FOREIGN KEY (`service_record_id`) REFERENCES `service_records` (`id`) ON DELETE SET NULL;
COMMIT;

--===================================================================
--
-- テーブルの構造 `monthly_service_summary`
--

CREATE TABLE `monthly_service_summary` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `total_hours` decimal(6,2) DEFAULT 0.00,
  `regular_hours` decimal(5,2) DEFAULT 0.00,
  `emergency_hours` decimal(5,2) DEFAULT 0.00,
  `extension_hours` decimal(5,2) DEFAULT 0.00,
  `regular_overtime_hours` decimal(5,2) DEFAULT 0.00,
  `contract_hours` decimal(5,2) NOT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `approved_hours` decimal(6,2) DEFAULT 0.00,
  `pending_hours` decimal(6,2) DEFAULT 0.00,
  `rejected_hours` decimal(6,2) DEFAULT 0.00,
  `document_count` int(11) DEFAULT 0 COMMENT '書面作成回数',
  `remote_consultation_count` int(11) DEFAULT 0 COMMENT '遠隔相談回数',
  `is_closed` tinyint(1) DEFAULT 0 COMMENT '締め処理済みフラグ',
  `closed_at` timestamp NULL DEFAULT NULL COMMENT '締め処理日時',
  `total_billing_amount` decimal(10,0) DEFAULT 0 COMMENT '総請求金額',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `monthly_service_summary`
--
ALTER TABLE `monthly_service_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contract_month` (`contract_id`,`year`,`month`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `idx_year_month` (`year`,`month`),
  ADD KEY `idx_is_closed` (`is_closed`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `monthly_service_summary`
--
ALTER TABLE `monthly_service_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `monthly_service_summary`
--
ALTER TABLE `monthly_service_summary`
  ADD CONSTRAINT `monthly_service_summary_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_service_summary_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;


--===================================================================

--
-- テーブルの構造 `closing_process_history`
--

CREATE TABLE `closing_process_history` (
  `id` int(11) NOT NULL,
  `monthly_closing_record_id` int(11) NOT NULL COMMENT '月次締め記録ID',
  `action_type` enum('simulate','finalize','reopen','company_approve','company_reject','revoke_approval') NOT NULL COMMENT 'アクション種別',
  `action_by` int(11) NOT NULL COMMENT '実行者ID',
  `action_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '実行日時',
  `comment` text DEFAULT NULL COMMENT 'コメント',
  `before_data` longtext DEFAULT NULL COMMENT '変更前データJSON',
  `after_data` longtext DEFAULT NULL COMMENT '変更後データJSON'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='締め処理履歴';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `closing_process_history`
--
ALTER TABLE `closing_process_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_monthly_closing_record` (`monthly_closing_record_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_action_by` (`action_by`),
  ADD KEY `idx_action_at` (`action_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `closing_process_history`
--
ALTER TABLE `closing_process_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `closing_process_history`
--
ALTER TABLE `closing_process_history`
  ADD CONSTRAINT `closing_process_history_action_by_fk` FOREIGN KEY (`action_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `closing_process_history_monthly_closing_fk` FOREIGN KEY (`monthly_closing_record_id`) REFERENCES `monthly_closing_records` (`id`) ON DELETE CASCADE;
COMMIT;







--===================================================================
--
-- テーブルの構造 `service_record_history`
--

CREATE TABLE `service_record_history` (
  `id` int(11) NOT NULL,
  `service_record_id` int(11) DEFAULT NULL,
  `action_type` enum('created','updated','approved','rejected','resubmitted','deleted','unapproved','travel_expense_added','travel_expense_updated','travel_expense_deleted','travel_expense_approved','travel_expense_rejected','travel_expense_unapproved') NOT NULL,
  `status_from` enum('pending','approved','rejected') DEFAULT NULL,
  `status_to` enum('pending','approved','rejected') DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metadata` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `service_record_history`
--
ALTER TABLE `service_record_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_record_id` (`service_record_id`),
  ADD KEY `idx_action_by` (`action_by`),
  ADD KEY `idx_action_at` (`action_at`),
  ADD KEY `idx_action_type` (`action_type`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `service_record_history`
--
ALTER TABLE `service_record_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `service_record_history`
--
ALTER TABLE `service_record_history`
  ADD CONSTRAINT `fk_service_record_history_service_record` FOREIGN KEY (`service_record_id`) REFERENCES `service_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_service_record_history_user` FOREIGN KEY (`action_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

--===================================================================
--
-- テーブルの構造 `travel_expenses`
--

CREATE TABLE `travel_expenses` (
  `id` int(11) NOT NULL,
  `service_record_id` int(11) NOT NULL COMMENT '役務記録ID',
  `doctor_id` int(11) NOT NULL COMMENT '産業医ID',
  `transport_type` enum('train','bus','taxi','gasoline','highway_toll','parking','rental_car','airplane','other') NOT NULL DEFAULT 'train' COMMENT '交通手段',
  `departure_point` varchar(200) DEFAULT NULL COMMENT '出発地点',
  `arrival_point` varchar(200) DEFAULT NULL COMMENT '到着地点',
  `trip_type` enum('round_trip','one_way') DEFAULT NULL COMMENT '往復・片道',
  `amount` decimal(8,0) NOT NULL COMMENT '金額（円）',
  `receipt_file_path` varchar(255) DEFAULT NULL COMMENT 'レシートファイルパス',
  `receipt_file_name` varchar(255) DEFAULT NULL COMMENT 'レシートファイル名',
  `receipt_file_size` int(11) DEFAULT NULL COMMENT 'レシートファイルサイズ',
  `memo` text DEFAULT NULL COMMENT 'メモ・備考',
  `company_notified` tinyint(1) DEFAULT 0 COMMENT '企業にお伝え済み（タクシー不可契約でタクシー利用時）',
  `status` enum('pending','approved','rejected','finalized') NOT NULL DEFAULT 'pending' COMMENT 'ステータス',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT '承認日時',
  `approved_by` int(11) DEFAULT NULL COMMENT '承認者ID',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT '差戻し日時',
  `rejected_by` int(11) DEFAULT NULL COMMENT '差戻し者ID',
  `unapproved_at` timestamp NULL DEFAULT NULL COMMENT '承認取り消し日時',
  `unapproved_by` int(11) DEFAULT NULL COMMENT '承認取り消し実行者ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_comment` text DEFAULT NULL COMMENT '管理者からのコメント',
  `closing_period` varchar(7) DEFAULT NULL COMMENT '締め対象月（YYYY-MM形式）',
  `is_closed` tinyint(1) DEFAULT 0 COMMENT '締め処理済みフラグ',
  `closed_at` timestamp NULL DEFAULT NULL COMMENT '締め処理実行日時',
  `closed_by` int(11) DEFAULT NULL COMMENT '締め処理実行者ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='交通費記録';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `travel_expenses`
--
ALTER TABLE `travel_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_record_id` (`service_record_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transport_type` (`transport_type`),
  ADD KEY `travel_expenses_ibfk_3` (`approved_by`),
  ADD KEY `travel_expenses_ibfk_4` (`rejected_by`),
  ADD KEY `idx_travel_expenses_created_at` (`created_at`),
  ADD KEY `idx_travel_expenses_amount` (`amount`),
  ADD KEY `idx_travel_expenses_trip_type` (`trip_type`),
  ADD KEY `idx_closing_period` (`closing_period`),
  ADD KEY `idx_is_closed` (`is_closed`),
  ADD KEY `idx_closed_by` (`closed_by`),
  ADD KEY `idx_unapproved_by` (`unapproved_by`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `travel_expenses`
--
ALTER TABLE `travel_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `travel_expenses`
--
ALTER TABLE `travel_expenses`
  ADD CONSTRAINT `travel_expenses_closed_by_fk` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `travel_expenses_ibfk_1` FOREIGN KEY (`service_record_id`) REFERENCES `service_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `travel_expenses_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `travel_expenses_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `travel_expenses_ibfk_4` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `travel_expenses_unapproved_by_fk` FOREIGN KEY (`unapproved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;



--===================================================================
--
-- テーブルの構造 `travel_expense_templates`
--

CREATE TABLE `travel_expense_templates` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL COMMENT '産業医ID',
  `template_name` varchar(100) NOT NULL COMMENT 'テンプレート名（自動生成または手動設定）',
  `transport_type` enum('train','bus','taxi','gasoline','highway_toll','parking','rental_car','airplane','other') NOT NULL DEFAULT 'train' COMMENT '交通手段',
  `departure_point` varchar(200) NOT NULL COMMENT '出発地点',
  `arrival_point` varchar(200) NOT NULL COMMENT '到着地点',
  `trip_type` enum('round_trip','one_way') DEFAULT NULL COMMENT '往復・片道',
  `amount` decimal(8,0) NOT NULL COMMENT '金額（円）',
  `memo` text DEFAULT NULL COMMENT 'メモ・備考',
  `usage_count` int(11) DEFAULT 0 COMMENT '使用回数',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT '最終使用日時',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '有効フラグ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='交通費テンプレート';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `travel_expense_templates`
--
ALTER TABLE `travel_expense_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_template_name` (`template_name`),
  ADD KEY `idx_usage_count` (`usage_count`),
  ADD KEY `idx_last_used_at` (`last_used_at`),
  ADD KEY `idx_active` (`is_active`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `travel_expense_templates`
--
ALTER TABLE `travel_expense_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `travel_expense_templates`
--
ALTER TABLE `travel_expense_templates`
  ADD CONSTRAINT `travel_expense_templates_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;


--===================================================================
--
-- テーブルの構造 `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `login_id` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `failure_reason` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_id` (`login_id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

--===================================================================
--
-- テーブルの構造 `password_history`
--

CREATE TABLE `password_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

--===================================================================
--
-- テーブルの構造 `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `security_logs`
--
ALTER TABLE `security_logs`
  ADD CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;


--===================================================================
--
-- テーブルの構造 `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;


--===================================================================

--
-- ビュー用の構造 `user_branch_details`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_branch_details`  AS SELECT `u`.`id` AS `user_id`, `u`.`login_id` AS `login_id`, `u`.`name` AS `user_name`, `u`.`email` AS `email`, `c`.`id` AS `company_id`, `c`.`name` AS `company_name`, `b`.`id` AS `branch_id`, `b`.`name` AS `branch_name`, `b`.`address` AS `branch_address`, `b`.`phone` AS `branch_phone`, `b`.`email` AS `branch_email`, `ubm`.`created_at` AS `mapping_created_at`, `ubm`.`is_active` AS `mapping_active` FROM (((`users` `u` join `user_branch_mappings` `ubm` on(`u`.`id` = `ubm`.`user_id`)) join `branches` `b` on(`ubm`.`branch_id` = `b`.`id`)) join `companies` `c` on(`b`.`company_id` = `c`.`id`)) WHERE `u`.`is_active` = 1 AND `ubm`.`is_active` = 1 AND `b`.`is_active` = 1 AND `c`.`is_active` = 1 ;
COMMIT;

--===================================================================
--
-- ビュー用の構造 `user_companies`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_companies`  AS SELECT DISTINCT `u`.`id` AS `user_id`, `u`.`login_id` AS `login_id`, `u`.`name` AS `user_name`, `u`.`email` AS `email`, `u`.`user_type` AS `user_type`, `c`.`id` AS `company_id`, `c`.`name` AS `company_name`, count(`ubm`.`branch_id`) AS `branch_count` FROM (((`users` `u` left join `user_branch_mappings` `ubm` on(`u`.`id` = `ubm`.`user_id` and `ubm`.`is_active` = 1)) left join `branches` `b` on(`ubm`.`branch_id` = `b`.`id` and `b`.`is_active` = 1)) left join `companies` `c` on(`b`.`company_id` = `c`.`id` and `c`.`is_active` = 1)) WHERE `u`.`is_active` = 1 GROUP BY `u`.`id`, `c`.`id` ;
COMMIT;

--===================================================================
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- 前年の繰り返し設定がある非訪問日を次年にコピー
    INSERT INTO `contract_non_visit_days` (
        `contract_id`,
        `non_visit_date`,
        `description`,
        `is_recurring`,
        `recurring_month`,
        `recurring_day`,
        `year`,
        `created_by`
    )
    SELECT
        `contract_id`,
        DATE(CONCAT(p_target_year, '-', LPAD(`recurring_month`, 2, '0'), '-', LPAD(`recurring_day`, 2, '0'))),
        `description`,
        `is_recurring`,
        `recurring_month`,
        `recurring_day`,
        p_target_year,
        p_created_by
    FROM `contract_non_visit_days`
    WHERE `contract_id` = p_contract_id
      AND `year` = p_target_year - 1
      AND `is_recurring` = 1
      AND `is_active` = 1
      AND NOT EXISTS (
          SELECT 1 FROM `contract_non_visit_days` AS existing
          WHERE existing.`contract_id` = p_contract_id
            AND existing.`year` = p_target_year
            AND existing.`recurring_month` = `contract_non_visit_days`.`recurring_month`
            AND existing.`recurring_day` = `contract_non_visit_days`.`recurring_day`
      );

    COMMIT;
END

--===================================================================
BEGIN
    DECLARE access_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO access_count
    FROM user_branch_mappings ubm
    INNER JOIN branches b ON ubm.branch_id = b.id
    WHERE ubm.user_id = p_user_id 
      AND ubm.branch_id = p_branch_id
      AND ubm.is_active = 1
      AND b.is_active = 1;
    
    RETURN access_count > 0;
END


--===================================================================
BEGIN
    DECLARE v_contract_hours DECIMAL(5,2);
    DECLARE v_regular_visit_rate DECIMAL(8,0);
    DECLARE v_regular_extension_rate DECIMAL(8,0);
    DECLARE v_emergency_visit_rate DECIMAL(8,0);
    DECLARE v_document_consultation_rate DECIMAL(8,0);
    
    -- 契約情報取得
    SELECT 
        regular_visit_hours,
        regular_visit_rate,
        regular_extension_rate,
        emergency_visit_rate,
        document_consultation_rate
    INTO 
        v_contract_hours,
        v_regular_visit_rate,
        v_regular_extension_rate,
        v_emergency_visit_rate,
        v_document_consultation_rate
    FROM contracts
    WHERE id = p_contract_id;
    
    -- シミュレーション結果を一時テーブルに格納
    CREATE TEMPORARY TABLE temp_simulation AS
    SELECT 
        sr.id as service_record_id,
        sr.service_date,
        sr.service_type,
        sr.service_hours as original_hours,
        CASE 
            WHEN sr.service_type = 'regular' THEN
                CASE 
                    WHEN @running_regular_hours + ceiling_to_quarter_hour(sr.service_hours) <= v_contract_hours THEN 'regular'
                    ELSE 'regular_extension'
                END
            ELSE sr.service_type
        END as billing_service_type,
        ceiling_to_quarter_hour(sr.service_hours) as billing_hours,
        CASE sr.service_type
            WHEN 'regular' THEN 
                CASE 
                    WHEN @running_regular_hours + ceiling_to_quarter_hour(sr.service_hours) <= v_contract_hours THEN
                        ceiling_to_quarter_hour(sr.service_hours) * v_regular_visit_rate
                    ELSE
                        LEAST(v_contract_hours - @running_regular_hours, ceiling_to_quarter_hour(sr.service_hours)) * v_regular_visit_rate +
                        GREATEST(0, ceiling_to_quarter_hour(sr.service_hours) - (v_contract_hours - @running_regular_hours)) * v_regular_extension_rate
                END
            WHEN 'emergency' THEN ceiling_to_quarter_hour(sr.service_hours) * v_emergency_visit_rate
            WHEN 'document' THEN v_document_consultation_rate
            WHEN 'remote_consultation' THEN v_document_consultation_rate
            ELSE 0
        END as billing_amount,
        @running_regular_hours := CASE 
            WHEN sr.service_type = 'regular' THEN @running_regular_hours + ceiling_to_quarter_hour(sr.service_hours)
            ELSE @running_regular_hours
        END as running_total
    FROM (
        SELECT @running_regular_hours := 0
    ) r
    CROSS JOIN (
        SELECT *
        FROM service_records sr
        WHERE sr.contract_id = p_contract_id
          AND DATE_FORMAT(sr.service_date, '%Y-%m') = p_closing_period
          AND sr.status = 'approved'
          AND (sr.is_closed = 0 OR sr.is_closed IS NULL)
        ORDER BY sr.service_date, sr.created_at
    ) sr;
    
    -- 結果を返す
    SELECT * FROM temp_simulation;
    
    DROP TEMPORARY TABLE temp_simulation;
END


--===================================================================
--
-- テーブルの構造 `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `email_type` enum('password_reset','login_invitation','notification','other') NOT NULL COMMENT 'メール種別',
  `sender_email` varchar(255) NOT NULL COMMENT '送信者メールアドレス',
  `sender_name` varchar(255) DEFAULT NULL COMMENT '送信者名',
  `recipient_email` varchar(255) NOT NULL COMMENT '受信者メールアドレス',
  `recipient_name` varchar(255) DEFAULT NULL COMMENT '受信者名',
  `recipient_user_id` int(11) DEFAULT NULL COMMENT '受信者ユーザーID（該当する場合）',
  `subject` varchar(500) NOT NULL COMMENT 'メール件名',
  `body_html` text DEFAULT NULL COMMENT 'メール本文（HTML）',
  `body_text` text DEFAULT NULL COMMENT 'メール本文（テキスト）',
  `status` enum('success','failed','pending') NOT NULL DEFAULT 'pending' COMMENT '送信ステータス',
  `error_message` text DEFAULT NULL COMMENT 'エラーメッセージ（失敗時）',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT '送信成功日時',
  `created_by` int(11) DEFAULT NULL COMMENT '送信実行者ユーザーID（システム経由の場合はNULL）',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'ログ記録日時',
  `metadata` text DEFAULT NULL COMMENT '追加メタデータ（JSON形式）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='メール送信ログテーブル。全てのメール送信履歴を記録';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_type` (`email_type`),
  ADD KEY `idx_recipient_email` (`recipient_email`),
  ADD KEY `idx_recipient_user_id` (`recipient_user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `fk_email_logs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_email_logs_recipient_user` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

--===================================================================
