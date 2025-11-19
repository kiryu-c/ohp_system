<?php
// app/controllers/BaseController.php - エラー修正版
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/User.php';

class BaseController {
    protected function render($view, $data = []) {
        $data['current_user'] = $this->getCurrentUser();
        $data['flash_messages'] = $this->getFlashMessages();
        echo view($view, $data);
    }
    
    protected function getCurrentUser() {
        if (!Session::isLoggedIn()) {
            return null;
        }
        
        $userModel = new User();
        return $userModel->findById(Session::get('user_id'));
    }
    
    protected function getFlashMessages() {
        $messages = [];
        $types = ['success', 'error', 'warning', 'info'];
        
        foreach ($types as $type) {
            $message = Session::flash($type);
            if ($message) {
                $messages[$type] = $message;
            }
        }
        
        return $messages;
    }
    
    protected function setFlash($type, $message) {
        Session::flash($type, $message);
    }
    
    /**
     * CSRFトークンを生成
     */
    protected function generateCsrfToken() {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    protected function validateCsrf() {
        $token = $_POST['csrf_token'] ?? '';
        
        // トークンが空または文字列でない場合
        if (empty($token) || !is_string($token)) {
            error_log('CSRF validation failed: invalid or missing token');
            $this->setFlash('error', 'セキュリティトークンが無効です。ページを再読み込みしてください。');
            return false;
        }
        
        if (!verify_csrf_token($token)) {
            error_log('CSRF validation failed: token mismatch');
            $this->setFlash('error', 'セキュリティトークンが無効です。ページを再読み込みしてください。');
            return false;
        }
        
        return true;
    }
    
    /**
     * Ajaxリクエストかどうかをチェック
     */
    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * JSONレスポンスを送信
     */
    protected function sendJsonResponse($data) {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 入力値のサニタイズ
     */
    protected function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * JSON レスポンス用のヘルパー
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * エラーレスポンス用のヘルパー
     */
    protected function errorResponse($message, $statusCode = 400, $details = null) {
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        $this->jsonResponse($response, $statusCode);
    }
    
    /**
     * 成功レスポンス用のヘルパー
     */
    protected function successResponse($data = null, $message = null) {
        $response = ['success' => true];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->jsonResponse($response);
    }
    
    /**
     * バリデーションエラー処理
     */
    protected function handleValidationErrors($errors) {
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            return true;
        }
        return false;
    }
    
    /**
     * ユーザー権限チェック
     */
    protected function requireUserType($allowedTypes) {
        Session::requireUserType($allowedTypes);
    }
    
    /**
     * ログイン必須チェック
     */
    protected function requireLogin() {
        Session::requireLogin();
    }
    
    /**
     * ページネーション用のヘルパー
     */
    protected function paginate($totalItems, $itemsPerPage = 20, $currentPage = 1) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        $currentPage = max(1, min($totalPages, $currentPage));
        $offset = ($currentPage - 1) * $itemsPerPage;
        
        return [
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_page' => $currentPage - 1,
            'next_page' => $currentPage + 1
        ];
    }
    
    /**
     * ファイルアップロード処理
     */
    protected function handleFileUpload($file, $uploadDir, $allowedExtensions = [], $maxSize = 10485760) {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('ファイルアップロードエラー: 無効なパラメータ');
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('ファイルが選択されていません');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('ファイルサイズが制限を超えています');
            default:
                throw new Exception('ファイルアップロードでエラーが発生しました');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('ファイルサイズが制限を超えています');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
            throw new Exception('許可されていないファイル形式です');
        }
        
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new Exception('アップロードディレクトリの作成に失敗しました');
        }
        
        $filename = sprintf(
            '%s_%s.%s',
            date('YmdHis'),
            bin2hex(random_bytes(8)),
            $extension
        );
        
        $filepath = $uploadDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('ファイルの保存に失敗しました');
        }
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'extension' => $extension
        ];
    }
    
    /**
     * デバッグ用ログ出力
     */
    protected function debug($message, $data = null) {
        $logMessage = '[DEBUG] ' . $message;
        if ($data !== null) {
            $logMessage .= ' | Data: ' . print_r($data, true);
        }
        error_log($logMessage);
    }
    
    /**
     * セキュリティログ出力
     */
    protected function logSecurityEvent($event, $data = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => Session::get('user_id'),
            'user_type' => Session::get('user_type'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'data' => $data
        ];
        
        error_log('[SECURITY] ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
}
?>