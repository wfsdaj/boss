<?php

declare(strict_types=1);

// 自动加载类文件
spl_autoload_register(function ($class_name) {
    include __DIR__ . '/' . str_replace('\\', '/', $class_name) . '.php';
});

class Router {
    private array $path_segments = [];
    private string $url_mode; // URL 模式：traditional 或 hyphenated
    private ?string $url_suffix; // URL 后缀（如 .html）

    /**
     * 构造函数
     *
     * @param string $url_mode URL 模式（traditional 或 hyphenated）
     * @param string|null $url_suffix URL 后缀（如 .html）
     */
    public function __construct(string $url_mode = 'traditional', ?string $url_suffix = null) {
        $this->url_mode = $url_mode;
        $this->url_suffix = $url_suffix;
        $this->parseUrl();
    }

    /**
     * 解析 URL
     */
    private function parseUrl(): void {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($request_uri, PHP_URL_PATH);

        if ($path === false || $path === null) {
            $this->handleError(400, "Invalid URL.");
        }

        // 根据 URL 模式解析
        if ($this->url_mode === 'hyphenated') {
            // 短横线分隔模式
            if ($this->url_suffix !== null && strpos($path, $this->url_suffix) !== false) {
                $this->path_segments = $this->parseHyphenatedUrl($path);
            } else {
                // 如果 URL 不符合短横线分隔模式，返回 404
                $this->handleError(404, "URL does not match the configured mode.");
            }
        } else {
            // 传统分段模式
            if ($this->url_suffix === null || strpos($path, $this->url_suffix) === false) {
                $this->path_segments = explode('/', trim($path, '/'));
            } else {
                // 如果 URL 不符合传统分段模式，返回 404
                $this->handleError(404, "URL does not match the configured mode.");
            }
        }
    }

    /**
     * 解析短横线分隔的 URL（如 user-profile-123.html）
     *
     * @param string $path URL 路径
     * @return array 解析后的分段数组
     */
    private function parseHyphenatedUrl(string $path): array {
        // 去掉配置的后缀
        $path = str_replace($this->url_suffix ?? '', '', $path);
        // 按短横线分割
        return explode('-', trim($path, '/'));
    }

    /**
     * 获取 URL 路径的分段
     *
     * @param int $index 分段索引（从 1 开始）
     * @return string|null 返回分段值，如果索引不存在则返回 null
     */
    public function segment(int $index): ?string {
        $index = $index - 1; // 将索引转换为数组下标
        return $this->path_segments[$index] ?? null;
    }

    /**
     * 分发请求到相应的控制器和方法
     */
    public function dispatch(): void {
        // 默认控制器和方法
        $controller_name = 'Home'; // 默认控制器
        $method_name = 'index';    // 默认方法

        // 动态解析控制器和方法
        if (!empty($this->segment(1))) {
            $controller_name = ucfirst($this->segment(1) ?? ''); // 首字母大写
        }
        if (!empty($this->segment(2))) {
            $method_name = $this->segment(2) ?? '';
        }

        // 检查控制器名称和方法名称是否合法
        if (!$this->isValidName($controller_name) || !$this->isValidName($method_name)) {
            $this->handleError(400, "Invalid controller or method name.");
        }

        // 构建完整的控制器类名
        $controller_class = 'app\\controller\\' . $controller_name;

        // 检查控制器是否存在
        if (!class_exists($controller_class)) {
            $this->handleError(404, "Controller '{$controller_class}' not found.");
        }

        // 实例化控制器
        $controller = new $controller_class();

        // 检查方法是否存在
        if (!method_exists($controller, $method_name)) {
            $this->handleError(404, "Method '{$method_name}' not found in controller '{$controller_class}'.");
        }

        // 获取单个参数（第三个分段）
        $param = $this->segment(3);

        // 调用方法并传递参数
        $controller->$method_name($param);
    }

    /**
     * 检查名称是否合法（只允许字母、数字和下划线）
     *
     * @param string $name 名称
     * @return bool 是否合法
     */
    private function isValidName(string $name): bool {
        return preg_match('/^[a-zA-Z0-9_]+$/', $name) === 1;
    }

    /**
     * 处理错误并终止脚本执行
     *
     * @param int $code HTTP 状态码
     * @param string $message 错误信息
     */
    private function handleError(int $code, string $message): void {
        http_response_code($code);
        echo $message;
        exit;
    }
}

// 使用 Router 类
$router = new Router(); // 配置为短横线分隔模式，后缀为 .html
$router->dispatch();