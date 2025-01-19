<?php



/**
 * 获取一个数据表操作对象
 *
 * @param  string $table  数据表名称
 * @param  string $item   默认 db , 对应的数据库一级2配置名称
 * @return object         数据库操作对象
 */
function db(string $table, string $item = 'db'): object
{
    $conf = config($item);
    return \boss\Database::getInstance($conf, $table, $item);
}

/**
 * 渲染视图并返回生成的内容
 *
 * 该函数用于加载并渲染视图模板。它会首先验证配置是否正确加载，
 * 然后通过 `\core\template\Template` 类进行渲染。若配置加载失败，
 * 则抛出异常。
 *
 * @param string $view 视图模板的名称或路径（相对路径）
 * @param array $data 要传递给视图的数据，默认为空数组
 *
 * @return void 渲染后的视图内容
 *
 * @throws Exception 当视图配置加载失败时抛出异常
 */
function view(string $view, array $data = []): void
{
    // 加载视图配置
    $config = config('view');
    if (!$config) {
        throw new Exception('视图配置加载失败');
    }

    // $template = new \core\view\Template($config);
    $template = new \boss\View($config);

    $template->render($view, $data);
}

/**
 * 根据给定的键值从预定义的段数组中获取对应的段。
 *
 * @param int $key 要查找的段键值（从 1 开始）。
 * @return mixed|null 返回与键值对应的段，如果键值不存在则返回 null。
 */
function segment(int $key): mixed
{
    // 假设 SEGMENTS 是一个预定义的数组常量
    $segments = SEGMENTS;

    // 将 $key 减 1，使其从 0 开始
    $adjustedKey = $key - 1;

    // 使用 null 合并运算符返回对应的段或 null
    return $segments[$adjustedKey] ?? null;
}

/**
 * 获取请求数据，默认已过滤 html 标签等。
 *
 * @param  string $field_name 请求字段名
 * @param  bool   $sanitize 是否进行数据清理，默认为 true
 * @return mixed  请求的数据
 */
function post($field_name, $sanitize = true)
{
    if (!isset($_POST[$field_name])) {
        return null;
    }

    $value = $_POST[$field_name];

    if ($sanitize) {
        if (is_string($value)) {
            $value = escape(trim($value));
        }
    }

    return $value;
}

/**
 * 获取配置项
 *
 * @param string|null $key 配置项的键，可以是点分隔的多层键。默认为 null，表示返回所有配置。
 */
function config(string $key = null)
{
    static $config = null;

    // 如果$config未加载，加载配置文件
    if ($config === null) {
        $config = require CONFIG_PATH . 'app.php';
    }

    // 如果$key为空，返回整个配置数组
    if ($key === null) {
        return $config;
    }

    // 解析点分隔的键字符串为数组
    $keys = explode('.', $key);

    // 如果只有一层键，直接返回该键的值
    if (count($keys) === 1) {
        return $config[$keys[0]] ?? null;
    }

    // 如果是两层键，返回嵌套键的值
    if (count($keys) === 2) {
        return $config[$keys[0]][$keys[1]] ?? null;
    }

    // 超过两层，返回null
    return null;
}

/**
 * 获取环境配置项
 *
 * @param string $key 配置项的键
 * @return string|null 返回配置项的值，若配置项不存在则返回null
 * @throws Exception 如果配置文件无法读取或解析
 */
function env(string $key): ?string
{
    $file = ROOT_PATH . '.env';

    // 检查配置文件是否存在且可读取
    if (!file_exists($file)) {
        throw new Exception("无法读取配置文件：{$file}", 1);
    }

    // 解析配置文件
    $env = parse_ini_file($file, true, INI_SCANNER_RAW);

    // 检查配置项是否存在
    return $env[$key] ?? null;
}

/**
 * 获取或者设置会话
 *
 * @param string $key   会话键
 * @param mixed  $value 会话值，如果为null则获取键的值
 */

function session(string $key, $value = null)
{
    if ($value !== null) {
        // 设置会话值并返回
        $_SESSION[$key] = $value;
        return true; // 设置成功返回true
    }

    // 获取会话值，如果不存在则返回null
    return $_SESSION[$key] ?? null;
}

/**
 * 输出 JSON 响应
 *
 * @param string $msg      要返回的消息内容
 * @param string $status   状态信息，例如 'success' 或 'error'，默认值为 'error'
 * @param int    $httpCode HTTP 状态码，例如 200, 400, 500，默认值为 200
 */
function json(string $msg, string $status = 'error', int $httpCode = 200)
{
    // 设置响应头，确保返回的是 JSON 格式数据
    header('Content-Type: application/json; charset=utf-8');

    // 设置 HTTP 状态码
    http_response_code($httpCode);

    // 输出 JSON 格式的响应
    echo json_encode(
        [
            'message' => escape($msg),
            'status'  => escape($status)
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
}

/**
 * 对指定的 URL 执行 HTTP 重定向。
 *
 * @param  string $target_url 重定向应指向的URL
 * @param  int  $status  HTTP状态码，默认为302（临时重定向）
 * @throws InvalidArgumentException 如果URL无效
 */
function redirect(string $target_url, int $status = 302)
{
    // 确保没有输出到浏览器，否则重定向将不会生效
    if (headers_sent()) {
        throw new RuntimeException("HTTP headers have already been sent.");
    }

    $clean_url  = filter_var($target_url, FILTER_SANITIZE_URL);

    // 验证清理后的URL是否有效，如果不是有效的URL，则抛出异常或进行其他错误处理
    if (!$clean_url || filter_var($clean_url, FILTER_VALIDATE_URL) === false) {
        throw new InvalidArgumentException('Invalid URL provided for redirection.');
    }

    // 防止可能的HTTP响应拆分攻击，确保没有换行符
    $safe_url = str_replace(["\n", "\r"], '', $clean_url);

    // 设置 HTTP 状态码并执行重定向
    http_response_code($status);
    header('Location: ' . $safe_url, true, $status);
    return;
}

/**
 * 检查当前请求是否为 GET 请求
 *
 * @return bool 如果是 GET 请求返回 true，否则返回 false
 */
function is_get(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? null) === 'GET';
}

/**
 * 检查当前请求是否为 POST 请求
 *
 * @return bool 如果是 POST 请求返回 true，否则返回 false
 */
function is_post(): bool
{
    // 确保 $_SERVER['REQUEST_METHOD'] 被设置并且为 POST
    return ($_SERVER['REQUEST_METHOD'] ?? null) === 'POST';
}

/**
 * 检查请求方法是否为 AJAX 请求
 *
 * @return bool 如果是 AJAX 请求返回 true，否则返回 false
 */
function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

/**
 * 检测是否使用手机访问
 *
 * @return bool
 */
function is_mobile(): bool
{
    // 检查 HTTP_VIA 或 HTTP_ACCEPT 请求头是否包含 wap 信息
    if (!empty($_SERVER['HTTP_VIA']) && stripos($_SERVER['HTTP_VIA'], "wap") !== false) {
        return true;
    }

    // 检查 HTTP_ACCEPT 请求头中是否有 WML 内容类型
    if (!empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], "VND.WAP.WML") !== false) {
        return true;
    }

    // 检查 HTTP_X_WAP_PROFILE 或 HTTP_PROFILE 请求头
    if (!empty($_SERVER['HTTP_X_WAP_PROFILE']) || !empty($_SERVER['HTTP_PROFILE'])) {
        return true;
    }

    // 检查 HTTP_USER_AGENT 中的常见手机设备标识
    if (!empty($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |htc |htc_|iemobile|kindle|midp|motorola|mobile|nokia|opera mini|opera |android|iphone|ipod|mobi|palm|ppc;|smartphone|sonyericsson|symbian|treo|up.browser|up.link|vodafone|windows ce|xda )/i', $_SERVER['HTTP_USER_AGENT'])) {
        return true;
    }

    return false;
}

/**
 * 生成完整的 URL 地址
 *
 * @param string $path URL 路径，默认空字符串
 * @return string 返回生成的完整 URL
 */
function url(string $path = ''): string
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    $host = $_SERVER['HTTP_HOST'];
    if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        throw new InvalidArgumentException('Invalid host name.');
    }

    $sanitizedPath = filter_var($path, FILTER_SANITIZE_URL);

    return $protocol . '://' . $host . $sanitizedPath;
}

/**
 * 消毒数据
 *
 * @param  string $value
 * @return string
 */
function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 去除字符串中所有的空白字符（包括空格、制表符、换行符等）
 *
 * @param string $str 需要处理的字符串
 * @return string 处理后的字符串，其中所有空白字符都已被移除
 */
function remove_all_whitespace(string $str): string
{
    // 使用正则表达式移除字符串中的所有空白字符（包括空格、制表符、换行符、回车符等）
    return preg_replace('/\s/', '', $str);
}


/**
 * 生成 CSRF token
 *
 * @param int $length token 长度，默认 32 字节
 * @return string 生成的 CSRF token
 */
function csrf_token(int $length = 32): string
{
    // 如果 session 中已经存在 csrf_token，直接返回它
    if (isset($_SESSION['csrf_token'])) {
        return $_SESSION['csrf_token'];
    }

    // 生成新的 CSRF token
    $token = bin2hex(random_bytes($length));

    // 将 CSRF token 存入 session
    $_SESSION['csrf_token'] = $token;

    return $token;
}

/**
 * 终止请求并返回指定的 HTTP 状态码和错误信息。
 *
 * @param int $code HTTP 状态码（如 404、403、500）。
 * @param string $message 自定义错误信息，默认为空。
 * @return void
 * @throws InvalidArgumentException 如果状态码无效。
 */
function abort(int $code, string $message = ''): void
{
    // 定义支持的状态码及其默认错误信息
    $supportedCodes = [
        403 => '无访问权限',
        404 => '页面不存在',
        500 => '内部服务器错误',
    ];

    // 检查状态码是否有效
    if (!array_key_exists($code, $supportedCodes)) {
        throw new InvalidArgumentException("无效的HTTP状态码: {$code}");
    }

    // 使用自定义消息或默认消息
    $message = $message ?: $supportedCodes[$code];

    // 设置 HTTP 响应码
    http_response_code($code);

    // 渲染错误页面
    if (file_exists(CORE_PATH . 'templates/abort.php')) {
        require CORE_PATH . 'templates/abort.php';
    } else {
        // 如果模板文件不存在，输出简单的错误信息
        echo "<h1>错误 {$code}</h1><p>{$message}</p>";
    }

    exit(1); // 终止脚本执行
}

/**
 * 将时间戳转换为友好的时间描述（如 "2小时前"）。
 *
 * @param int $time 时间戳。
 * @return string 返回友好的时间描述。
 */
function nice_time(int $time): string
{
    $timeDiff = time() - $time;

    // 时间单位（秒）和对应的描述
    $units = [
        31536000 => '年',
        2592000  => '个月',
        604800   => '周',
        86400    => '天',
        3600     => '小时',
        60       => '分钟',
        1        => '秒'
    ];

    // 遍历时间单位，找到合适的时间描述
    foreach ($units as $seconds => $unit) {
        if ($timeDiff >= $seconds) {
            $count = floor($timeDiff / $seconds);
            return "{$count}{$unit}";
        }
    }

    // 如果时间差为 0 或负数，返回默认值
    return '刚刚';
}

/**
 * 获取客户端的IP地址
 *
 * 该函数首先尝试从HTTP头中获取客户端的真实IP地址，并进行必要的验证。
 * 如果无法获取有效IP，则返回默认IP地址 '0.0.0.0'。
 *
 * @return string 返回客户端的IP地址（有效的IPv4地址）或者默认地址 '0.0.0.0'。
 */
function get_ip(): string
{
    static $ip = null;

    // 如果已经获取过 IP，直接返回缓存的结果
    if (null !== $ip) {
        return $ip;
    }

    // 初始化 IP 地址
    $ip = '';

    // 尝试从 HTTP_X_FORWARDED_FOR 中获取 IP 地址
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // 解析多个 IP 并去除 'unknown'
        $ips = array_filter(
            explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']),
            fn ($ip) => trim($ip) !== 'unknown'
        );
        $ip = trim(reset($ips)); // 获取第一个有效 IP
    }

    // 如果 HTTP_X_FORWARDED_FOR 没有有效 IP，尝试从 HTTP_CLIENT_IP 中获取
    if (empty($ip) && isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = trim($_SERVER['HTTP_CLIENT_IP']);
    }

    // 如果仍然没有获取到 IP，使用 REMOTE_ADDR
    if (empty($ip) && isset($_SERVER['REMOTE_ADDR'])) {
        $ip = trim($_SERVER['REMOTE_ADDR']);
    }

    // 验证 IP 是否有效，如果无效，返回默认 IP '0.0.0.0'
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * 调试输出
 *
 * @param mixed ...$value 要输出的值
 * @return void
 */
function dump(...$value): void
{
    // 开启输出缓冲区
    ob_start();
    var_dump(...$value);
    $output = ob_get_clean();

    // 格式化输出内容
    $output = preg_replace('/]=>\n(\s+)/m', '] => ', $output); // 压缩换行
    $output = htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); // 转义 HTML 特殊字符
    $output = sprintf('<pre>%s</pre>', $output);

    // 输出结果
    echo $output;
}

/**
 * 输出变量的详细信息并终止脚本执行。
 *
 * @param mixed ...$value 要输出的变量。
 * @return void
 */
function dd(...$value): void
{
    dump(...$value);
    exit(1);
}
