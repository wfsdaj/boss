<?php

// 自动加载类文件
spl_autoload_register(function ($class_name) {
    include __DIR__ . '/' . str_replace('\\', '/', $class_name) . '.php';
});

class Router {
    private $path_segments;
    private $url_mode; // URL 模式：traditional 或 hyphenated
    private $url_suffix; // URL 后缀（如 .html）

    /**
     * 构造函数
     *
     * @param string $url_mode URL 模式（traditional 或 hyphenated）
     * @param string|null $url_suffix URL 后缀（如 .html）
     */
    public function __construct($url_mode = 'traditional', $url_suffix = null) {
        $this->url_mode = $url_mode;
        $this->url_suffix = $url_suffix;
        $this->parseUrl();
    }

    /**
     * 解析 URL
     */
    private function parseUrl() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($request_uri, PHP_URL_PATH);

        // 根据 URL 模式解析
        if ($this->url_mode === 'hyphenated') {
            // 短横线分隔模式
            if ($this->url_suffix !== null && strpos($path, $this->url_suffix) !== false) {
                $this->path_segments = $this->parseHyphenatedUrl($path);
            } else {
                // 如果 URL 不符合短横线分隔模式，返回 404
                http_response_code(404);
                echo "URL does not match the configured mode.";
                exit;
            }
        } else {
            // 传统分段模式
            if ($this->url_suffix === null || strpos($path, $this->url_suffix) === false) {
                $this->path_segments = explode('/', trim($path, '/'));
            } else {
                // 如果 URL 不符合传统分段模式，返回 404
                http_response_code(404);
                echo "URL does not match the configured mode.";
                exit;
            }
        }
    }

    /**
     * 解析短横线分隔的 URL（如 user-profile-123.html）
     *
     * @param string $path URL 路径
     * @return array 解析后的分段数组
     */
    private function parseHyphenatedUrl($path) {
        // 去掉配置的后缀
        $path = str_replace($this->url_suffix, '', $path);
        // 按短横线分割
        return explode('-', trim($path, '/'));
    }

    /**
     * 获取 URL 路径的分段
     *
     * @param int $index 分段索引（从 1 开始）
     * @return string|null 返回分段值，如果索引不存在则返回 null
     */
    public function segment($index) {
        $index = (int)$index - 1; // 将索引转换为数组下标
        return $this->path_segments[$index] ?? null;
    }

    public function dispatch() {
        // 默认控制器和方法
        $controller_name = 'Home'; // 默认控制器
        $method_name = 'index';    // 默认方法

        // 动态解析控制器和方法
        if (!empty($this->segment(1))) {
            $controller_name = ucfirst($this->segment(1)); // 首字母大写
        }
        if (!empty($this->segment(2))) {
            $method_name = $this->segment(2);
        }

        // 构建完整的控制器类名
        $controller_class = 'app\\controller\\' . $controller_name;

        // 检查控制器名称和方法名称是否合法
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $controller_name) || !preg_match('/^[a-zA-Z0-9_]+$/', $method_name)) {
            http_response_code(400);
            echo "Invalid controller or method name.";
            exit;
        }

        // 检查控制器和方法是否存在
        if (!class_exists($controller_class)) {
            http_response_code(404);
            echo "Controller '{$controller_class}' not found.";
            exit;
        }

        // 实例化控制器
        $controller = new $controller_class();

        // 检查方法是否存在
        if (!method_exists($controller, $method_name)) {
            http_response_code(404);
            echo "Method '{$method_name}' not found in controller '{$controller_class}'.";
            exit;
        }

        // 获取单个参数（第三个分段）
        $param = $this->segment(3);

        // 调用方法并传递参数
        $controller->$method_name($param);
    }
}

// 使用 Router 类
$router = new Router(); // 配置为短横线分隔模式，后缀为 .html
$router->dispatch();