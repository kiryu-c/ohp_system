<?php
// app/core/Router.php
class Router {
    private $routes = [];
    private $currentRoute = '';
    
    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }
    
    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // public/ を除去
        $path = str_replace('/op_website/public', '', $path);
        if (empty($path)) {
            $path = '/';
        }
        
        // 静的ファイルの処理（assets、CSS、JS、画像など）
        if ($this->isStaticFile($path)) {
            $this->serveStaticFile($path);
            return;
        }
        
        $this->currentRoute = $path;
        
        // 動的ルートのマッチング
        $matchedRoute = null;
        $params = [];
        
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            if ($this->matchRoute($route, $path, $params)) {
                $matchedRoute = $handler;
                break;
            }
        }
        
        if ($matchedRoute) {
            if (is_array($matchedRoute)) {
                [$controller, $action] = $matchedRoute;
                $this->callController($controller, $action, $params);
            } else {
                $matchedRoute();
            }
        } else {
            $this->notFound();
        }
    }
    
    private function isStaticFile($path) {
        // 静的ファイルの拡張子をチェック
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'pdf', 'txt'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        // uploadsディレクトリも静的ファイルとして扱う
        return preg_match('/^\/assets\//', $path) || 
               preg_match('/^\/uploads\//', $path) || 
               in_array(strtolower($extension), $staticExtensions);
    }
    
    private function serveStaticFile($path) {
        // ドキュメントルートから実際のファイルパスを構築
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        
        // uploadsディレクトリの場合
        if (preg_match('/^\/uploads\//', $path)) {
            // いくつかのパターンを試す（交通費が動作しているパターンを優先）
            $possiblePaths = [];
            
            // パターン1: DOCUMENT_ROOT/../uploads (public外、交通費と同じ)
            if (strpos($documentRoot, 'op_website/public') !== false || strpos($documentRoot, 'op_website\\public') !== false) {
                // DOCUMENT_ROOTがpublicディレクトリの場合、親ディレクトリのuploads/を探す
                $possiblePaths[] = dirname($documentRoot) . $path;
            }
            
            // パターン2: DOCUMENT_ROOT/op_website/uploads (publicの外)
            $possiblePaths[] = $documentRoot . '/op_website' . $path;
            
            // パターン3: DOCUMENT_ROOT/public/uploads（publicの中）
            if (strpos($documentRoot, 'op_website/public') !== false || strpos($documentRoot, 'op_website\\public') !== false) {
                $possiblePaths[] = $documentRoot . $path;
            }
            
            // パターン4: DOCUMENT_ROOT/op_website/public/uploads
            $possiblePaths[] = $documentRoot . '/op_website/public' . $path;
            
            // パターン5: DOCUMENT_ROOT/uploads
            $possiblePaths[] = $documentRoot . $path;
            
            // 存在するパスを探す
            $filePath = null;
            foreach ($possiblePaths as $tryPath) {
                // Windowsパスの正規化
                $tryPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tryPath);
                
                if (file_exists($tryPath) && is_file($tryPath)) {
                    $filePath = $tryPath;
                    break;
                }
            }
            
            if (!$filePath) {
                // デバッグ用: 試したパスをログに記録
                error_log("File not found for path: {$path}");
                error_log("Tried paths: " . implode(", ", array_map(function($p) {
                    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $p);
                }, $possiblePaths)));
                
                $this->notFound();
                return;
            }
        } else {
            // assets等の通常の静的ファイル
            if (strpos($documentRoot, 'op_website/public') !== false || strpos($documentRoot, 'op_website\\public') !== false) {
                $filePath = $documentRoot . $path;
            } else {
                $filePath = $documentRoot . '/op_website/public' . $path;
            }
            
            // Windowsパスの正規化
            $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        }
        
        // ファイルが存在するかチェック
        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->notFound();
            return;
        }
        
        // ファイルの読み取り権限チェック
        if (!is_readable($filePath)) {
            http_response_code(403);
            echo "403 Forbidden";
            return;
        }
        
        // MIMEタイプを設定
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain'
        ];
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        // ヘッダーを設定
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: ' . $mimeType);
        
        // PDFや画像の場合はブラウザで表示
        if ($extension === 'pdf' || in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
            $filename = basename($filePath);
            header('Content-Disposition: inline; filename="' . $filename . '"');
        }
        
        // ファイルを出力
        readfile($filePath);
        exit;
    }
    
    private function matchRoute($route, $path, &$params) {
        // 単純な一致チェック
        if ($route === $path) {
            return true;
        }
        
        // 動的パラメータのマッチング（例: /users/{id}）
        $routePattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
        $routePattern = str_replace('/', '\/', $routePattern);
        
        if (preg_match('/^' . $routePattern . '$/', $path, $matches)) {
            array_shift($matches); // 最初の完全一致を除去
            $params = $matches;
            return true;
        }
        
        return false;
    }
    
    private function callController($controllerName, $action, $params = []) {
        $controllerFile = __DIR__ . "/../controllers/{$controllerName}.php";
        
        if (!file_exists($controllerFile)) {
            $this->notFound();
            return;
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $action)) {
            $this->notFound();
            return;
        }
        
        // パラメータを渡してメソッドを呼び出し
        call_user_func_array([$controller, $action], $params);
    }
    
    private function notFound() {
        http_response_code(404);
        echo "404 Not Found";
    }
    
    public function getCurrentRoute() {
        return $this->currentRoute;
    }
}
?>