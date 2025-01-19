<?php

namespace boss;

use Exception;

class App
{
    /**
     * 启动应用程序
     *
     * @return void
     */
    public static function run(): void
    {
        self::initConstants();

        // 错误报告设置
        error_reporting(E_ALL);
        ini_set('display_errors', '0'); // 隐藏错误信息，防止泄漏给用户

        // 注册错误处理函数
        set_error_handler([\boss\ErrorHandler::class, 'handleError']);
        register_shutdown_function([\boss\ErrorHandler::class, 'handleShutdown']);

        try {
            self::router();
        } catch (ErrorHandler $e) {
            $e->debug();
        }
    }

    /**
     * 初始化常量
     *
     * @return void
     */
    private static function initConstants(): void
    {
        define('START_MEMORY', memory_get_usage());  // 开始内存
        define('START_TIME', microtime(true));       // 开始时间

        defined('TRACE') || define('TRACE', false);  // 追踪模式
        defined('DEBUG') || define('DEBUG', false);  // 调试模式
        defined('SHOW_ERROR') || define('SHOW_ERROR', false); // 是否展示错误信息

        define('ROOT_PATH', strtr(realpath(__DIR__ . '/../'), '\\', '/') . '/');         // 根目录
        defined('APP_PATH')       || define('APP_PATH', ROOT_PATH .        'app/');      // 应用目录
        defined('VIEW_PATH')      || define('VIEW_PATH', APP_PATH .        'view/');     // 模板目录
        defined('CORE_PATH')      || define('CORE_PATH', ROOT_PATH .       'boss/');     // 框架目录
        defined('CONFIG_PATH')    || define('CONFIG_PATH', ROOT_PATH .     'config/');   // 配置文件目录
        defined('RUNTIME_PATH')   || define('RUNTIME_PATH', ROOT_PATH .    'runtime/');  // 临时文件目录
        defined('LOG_PATH')       || define('LOG_PATH', RUNTIME_PATH .     'log/');      // 日志目录
        defined('CACHE_PATH')     || define('CACHE_PATH', RUNTIME_PATH .   'cache/');    // 缓存目录
        defined('SESSION_PATH')   || define('SESSION_PATH', RUNTIME_PATH . 'sessions/'); // session 目录

        defined('DEFAULT_CONTROLLER') || define('DEFAULT_CONTROLLER', 'Home');  // 默认控制器
        defined('DEFAULT_METHOD')     || define('DEFAULT_METHOD', 'index');     // 默认方法

        defined('CUSTOM_ROUTE')  || define('CUSTOM_ROUTE', false);   // 是否使用自定义路由
        defined('CLOSE_CACHE')   || define('CLOSE_CACHE', false);    // 关闭全局缓存
        defined('SESSION_START') || define('SESSION_START', false);  // 是否启动 session
        defined('SESSION_TYPE')  || define('SESSION_TYPE', 'file');  // 会话存储类型 [file, memcache, redis]
        defined('PAGE_SUFFIX')   || define('PAGE_SUFFIX', false);    // 页面后缀
    }

    /**
     * 路由处理
     * 解析 URL，加载控制器并执行对应方法
     */
    private static function router()
    {
        // 解析 URL 获取控制器和方法
        $url        = self::parseUrl();
        $controller = $url[0] ?? DEFAULT_CONTROLLER;
        $method     = $url[1] ?? DEFAULT_METHOD;

        // 验证控制器名称合法性
        if (!ctype_alnum($controller)) {
            return abort(404);
        }

        // 加载控制器文件
        $controller_file = APP_PATH . 'controller/' . ucfirst($controller) . '.php';
        if (!is_file($controller_file)) {
            return abort(404);
        }

        // 实例化控制器
        $controller_class = "\\app\\controller\\" . ucfirst($controller);
        if (!class_exists($controller_class)) {
            return abort(404);
        }

        $controller_instance = new $controller_class;

        // 解析方法名称
        if (!ctype_alnum($method)) {
            return abort(404);
        }

        // 检查方法是否存在
        if (!method_exists($controller_instance, $method)) {
            return abort(404);
        }

        // 定义全局常量
        define('CONTROLLER_NAME', $controller_instance);
        define('METHOD_NAME', $method);
        define('SEGMENTS', $url);

        // $segments = array_slice($url, 2);

        $GLOBALS['traceSql'] = [];

        // 执行方法
        $controller_instance->$method();

        // 调试模式
        if (DEBUG && TRACE) {
            require_once CORE_PATH . 'templates/trace.php';
        }
    }

    /**
     * 解析 URL
     * 解析请求路径并返回控制器和方法名
     *
     * @return array 返回控制器和方法名的数组
     */
    private static function parseUrl(): array
    {
        // 获取路径信息
        if (isset($_GET['url'])) {
            $path = trim($_GET['url'], '/');
            $path = filter_var($_GET['url'], FILTER_SANITIZE_URL);
            unset($_GET['url']);  // 移除 url，防止污染 $_GET
        } else {
            $path = DEFAULT_CONTROLLER . '/' . DEFAULT_METHOD;
        }

        // 移除后缀（如果存在）
        if (defined('PAGE_SUFFIX') && PAGE_SUFFIX) {
            $path = str_replace(PAGE_SUFFIX, '', $path);
        }

        // 分割路径并移除空值
        $router = array_filter(explode('/', $path), 'strlen');

        // 默认控制器和方法
        $router[0] = $router[0] ?? DEFAULT_CONTROLLER;
        $router[1] = $router[1] ?? DEFAULT_METHOD;

        return $router;
    }
}
