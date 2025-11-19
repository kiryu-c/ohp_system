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
        
        // assetsディレクトリ内のファイルまたは静的ファイル拡張子をチェック
        return preg_match('/^\/assets\//', $path) || in_array(strtolower($extension), $staticExtensions);
    }
    
    private function serveStaticFile($path) {
        // ドキュメントルートから実際のファイルパスを構築
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        
        // DOCUMENT_ROOTが既にop_website/publicを含んでいる場合の調整
        if (strpos($documentRoot, 'op_website/public') !== false) {
            // DOCUMENT_ROOTが既にop_website/publicの場合、そのまま使用
            $filePath = $documentRoot . $path;
        } else {
            // 通常の場合
            $filePath = $documentRoot . '/op_website/public' . $path;
        }
        
        // ファイルが存在するかチェック
        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->notFound();
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
        
        // キャッシュヘッダーを無効化（開発中）
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: ' . $mimeType);
        
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