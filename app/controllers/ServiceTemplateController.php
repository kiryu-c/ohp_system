<?php
// app/controllers/ServiceTemplateController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ServiceDescriptionTemplate.php';

class ServiceTemplateController extends BaseController {
    
    /**
     * テンプレート管理画面の表示
     */
    public function index() {
        Session::requireUserType('doctor');
        
        $doctorId = Session::get('user_id');
        $templateModel = new ServiceDescriptionTemplate();
        
        // 個人テンプレート一覧を取得
        $personalTemplates = $templateModel->getPersonalTemplates($doctorId);
        
        // 統計情報を取得
        $stats = $templateModel->getTemplateStats($doctorId);
        
        $data = [
            'personalTemplates' => $personalTemplates,
            'stats' => $stats
        ];
        
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/service_templates/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * API: 使用可能テンプレート一覧を取得
     */
    public function getTemplatesApi() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json');
        
        try {
            $doctorId = Session::get('user_id');
            $templateModel = new ServiceDescriptionTemplate();
            
            $templates = $templateModel->getAvailableTemplates($doctorId);
            
            // テンプレートをカテゴリ分け
            $systemTemplates = [];
            $personalTemplates = [];
            
            foreach ($templates as $template) {
                // プレビューテキストを追加
                $template['preview'] = $templateModel->getPreviewText($template['content']);
                
                if ($template['is_system_template']) {
                    $systemTemplates[] = $template;
                } else {
                    $personalTemplates[] = $template;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'system' => $systemTemplates,
                    'personal' => $personalTemplates,
                    'all' => $templates
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Get templates API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'テンプレートの取得に失敗しました。'
            ]);
        }
    }
    
    /**
     * API: テンプレートを保存
     */
    public function saveTemplateApi() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json');
        
        if (!$this->validateCsrf()) {
            echo json_encode([
                'success' => false,
                'error' => 'セキュリティエラーが発生しました。'
            ]);
            return;
        }
        
        try {
            $doctorId = Session::get('user_id');
            $content = trim($_POST['content'] ?? '');
            
            // バリデーション
            if (empty($content)) {
                echo json_encode([
                    'success' => false,
                    'error' => '役務内容を入力してください。'
                ]);
                return;
            }
            
            if (mb_strlen($content) > 1000) {
                echo json_encode([
                    'success' => false,
                    'error' => '役務内容は1000文字以内で入力してください。'
                ]);
                return;
            }
            
            $templateModel = new ServiceDescriptionTemplate();
            
            // テンプレートを保存
            $result = $templateModel->createTemplate($doctorId, $content);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'テンプレートを保存しました。'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'テンプレートの保存に失敗しました。'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Save template API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'テンプレートの保存中にエラーが発生しました。'
            ]);
        }
    }
    
    /**
     * API: テンプレート使用時の更新
     */
    public function useTemplateApi() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json');
        
        try {
            $templateId = intval($_POST['template_id'] ?? 0);
            
            if ($templateId <= 0) {
                echo json_encode([
                    'success' => false,
                    'error' => '無効なテンプレートIDです。'
                ]);
                return;
            }
            
            $templateModel = new ServiceDescriptionTemplate();
            $result = $templateModel->useTemplate($templateId);
            
            echo json_encode([
                'success' => $result
            ]);
            
        } catch (Exception $e) {
            error_log('Use template API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'テンプレート使用の記録に失敗しました。'
            ]);
        }
    }
    
    /**
     * 個人テンプレートの削除
     */
    public function deleteTemplate($templateId) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('service_templates');
            return;
        }
        
        try {
            $doctorId = Session::get('user_id');
            $templateModel = new ServiceDescriptionTemplate();
            
            $result = $templateModel->deletePersonalTemplate($templateId, $doctorId);
            
            if ($result) {
                $this->setFlash('success', 'テンプレートを削除しました。');
            } else {
                $this->setFlash('error', 'テンプレートの削除に失敗しました。');
            }
            
        } catch (Exception $e) {
            error_log('Delete template error: ' . $e->getMessage());
            $this->setFlash('error', 'テンプレートの削除中にエラーが発生しました。');
        }
        
        redirect('service_templates');
    }
    
    /**
     * テンプレートの詳細表示
     */
    public function show($templateId) {
        Session::requireUserType('doctor');
        
        $doctorId = Session::get('user_id');
        $templateModel = new ServiceDescriptionTemplate();
        
        // テンプレートを取得
        $template = $templateModel->findById($templateId);
        
        if (!$template) {
            $this->setFlash('error', 'テンプレートが見つかりません。');
            redirect('service_templates');
            return;
        }
        
        // 権限チェック（システムテンプレートまたは自分のテンプレート）
        if (!$template['is_system_template'] && $template['doctor_id'] != $doctorId) {
            $this->setFlash('error', '権限がありません。');
            redirect('service_templates');
            return;
        }
        
        $data = ['template' => $template];
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/service_templates/show.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * テンプレートの編集
     */
    public function edit($templateId) {
        Session::requireUserType('doctor');
        
        $doctorId = Session::get('user_id');
        $templateModel = new ServiceDescriptionTemplate();
        
        // テンプレートを取得
        $template = $templateModel->findById($templateId);
        
        if (!$template) {
            $this->setFlash('error', 'テンプレートが見つかりません。');
            redirect('service_templates');
            return;
        }
        
        // 権限チェック（個人テンプレートのみ編集可能）
        if ($template['is_system_template'] || $template['doctor_id'] != $doctorId) {
            $this->setFlash('error', 'このテンプレートは編集できません。');
            redirect('service_templates');
            return;
        }
        
        $data = ['template' => $template];
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/service_templates/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * テンプレートの更新
     */
    public function update($templateId) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('service_templates');
            return;
        }
        
        try {
            $doctorId = Session::get('user_id');
            $templateModel = new ServiceDescriptionTemplate();
            
            // テンプレートを取得
            $template = $templateModel->findById($templateId);
            
            if (!$template || $template['is_system_template'] || $template['doctor_id'] != $doctorId) {
                $this->setFlash('error', '権限がありません。');
                redirect('service_templates');
                return;
            }
            
            $content = trim($_POST['content'] ?? '');
            
            // バリデーション
            if (empty($content)) {
                $this->setFlash('error', '役務内容を入力してください。');
                redirect("service_templates/{$templateId}/edit");
                return;
            }
            
            if (mb_strlen($content) > 1000) {
                $this->setFlash('error', '役務内容は1000文字以内で入力してください。');
                redirect("service_templates/{$templateId}/edit");
                return;
            }
            
            // 更新データ
            $updateData = [
                'content' => $content
            ];
            
            $result = $templateModel->update($templateId, $updateData);
            
            if ($result) {
                $this->setFlash('success', 'テンプレートを更新しました。');
            } else {
                $this->setFlash('error', 'テンプレートの更新に失敗しました。');
            }
            
        } catch (Exception $e) {
            error_log('Update template error: ' . $e->getMessage());
            $this->setFlash('error', 'テンプレートの更新中にエラーが発生しました。');
        }
        
        redirect('service_templates');
    }

    /**
     * API: 入力内容が新規テンプレートとして保存可能かチェック
     */
    public function checkAutoSaveApi() {
        // 出力バッファリングをクリア
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $content = trim($_POST['content'] ?? '');
            $doctorId = Session::get('user_id');
            
            if (empty($content)) {
                echo json_encode([
                    'success' => false,
                    'should_save' => false
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $templateModel = new ServiceDescriptionTemplate();
            $shouldSave = $templateModel->shouldAutoSaveTemplate($content, $doctorId);
            
            if ($shouldSave) {
                // プレビューテキストを生成
                $preview = $templateModel->getPreviewText($content, 30);
                
                // UTF-8エンコーディングを確保
                $preview = mb_convert_encoding($preview, 'UTF-8', 'auto');
                
                echo json_encode([
                    'success' => true,
                    'should_save' => true,
                    'preview' => $preview
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => true,
                    'should_save' => false
                ], JSON_UNESCAPED_UNICODE);
            }
            
        } catch (Exception $e) {
            error_log('Check auto save API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'チェック処理中にエラーが発生しました。'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}
?>