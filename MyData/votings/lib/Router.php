<?php
/**
 * Modern PHP Router tương tự React Router
 * Hỗ trợ nested routes, middleware, và dynamic routing
 */
class Router {
    private $routes = [];
    private $middlewares = [];
    private $basePath = '';

    public function __construct($basePath = '') {
        $this->basePath = $basePath;
    }

    /**
     * Đăng ký route GET
     */
    public function get($path, $handler, $middleware = []) {
        $this->addRoute('GET', $path, $handler, $middleware);
        return $this;
    }

    /**
     * Đăng ký route POST
     */
    public function post($path, $handler, $middleware = []) {
        $this->addRoute('POST', $path, $handler, $middleware);
        return $this;
    }

    /**
     * Đăng ký route PUT
     */
    public function put($path, $handler, $middleware = []) {
        $this->addRoute('PUT', $path, $handler, $middleware);
        return $this;
    }

    /**
     * Đăng ký route DELETE
     */
    public function delete($path, $handler, $middleware = []) {
        $this->addRoute('DELETE', $path, $handler, $middleware);
        return $this;
    }

    /**
     * Đăng ký route cho tất cả HTTP methods
     */
    public function any($path, $handler, $middleware = []) {
        $this->addRoute(['GET', 'POST', 'PUT', 'DELETE'], $path, $handler, $middleware);
        return $this;
    }

    /**
     * Đăng ký route group
     */
    public function group($prefix, $callback, $middleware = []) {
        $previousBasePath = $this->basePath;
        $this->basePath .= $prefix;

        $previousMiddlewares = $this->middlewares;
        $this->middlewares = array_merge($this->middlewares, $middleware);

        $callback($this);

        $this->basePath = $previousBasePath;
        $this->middlewares = $previousMiddlewares;

        return $this;
    }

    /**
     * Thêm route vào danh sách
     */
    private function addRoute($methods, $path, $handler, $middleware = []) {
        $methods = is_array($methods) ? $methods : [$methods];
        $fullPath = $this->basePath . $path;

        foreach ($methods as $method) {
            $this->routes[] = [
                'method' => $method,
                'path' => $fullPath,
                'handler' => $handler,
                'middleware' => array_merge($this->middlewares, $middleware)
            ];
        }
    }

    /**
     * Xử lý request và tìm route phù hợp
     */
    public function dispatch($requestUri = null, $requestMethod = null) {
        if ($requestUri === null) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        }

        if ($requestMethod === null) {
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        // Loại bỏ query string
        $requestUri = parse_url($requestUri, PHP_URL_PATH);

        // Loại bỏ base path nếu có
        if ($this->basePath && strpos($requestUri, $this->basePath) === 0) {
            $requestUri = substr($requestUri, strlen($this->basePath));
        }

        // Tìm route phù hợp
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestUri, $params)) {
                // Chạy middleware
                if (!$this->runMiddleware($route['middleware'])) {
                    return false;
                }

                // Chạy handler
                return $this->runHandler($route['handler'], $params);
            }
        }

        // Không tìm thấy route
        http_response_code(404);
        return $this->render404();
    }

    /**
     * Kiểm tra path có khớp với pattern không
     */
    private function matchPath($pattern, $path, &$params) {
        $params = [];

        // Chuyển đổi pattern thành regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            // Lấy tên parameters
            preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);

            // Map parameters
            for ($i = 1; $i < count($matches); $i++) {
                if (isset($paramNames[1][$i - 1])) {
                    $params[$paramNames[1][$i - 1]] = $matches[$i];
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Chạy middleware
     */
    private function runMiddleware($middlewares) {
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                if (!$middleware()) {
                    return false;
                }
            } elseif (is_string($middleware)) {
                if (class_exists($middleware)) {
                    $instance = new $middleware();
                    if (!$instance->handle()) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Chạy handler
     */
    private function runHandler($handler, $params) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        } elseif (is_string($handler)) {
            if (class_exists($handler)) {
                $instance = new $handler();
                if (method_exists($instance, 'handle')) {
                    return $instance->handle($params);
                }
            } elseif (file_exists($handler)) {
                extract($params);
                return include $handler;
            }
        }

        return false;
    }

    /**
     * Render trang 404
     */
    private function render404() {
        http_response_code(404);
        return '<h1>404 - Không tìm thấy trang</h1>';
    }

    /**
     * Lấy tất cả routes đã đăng ký
     */
    public function getRoutes() {
        return $this->routes;
    }
}
