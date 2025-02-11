<?php

declare(strict_types=1);

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
    return \boss\Db::getInstance($conf, $table, $item);
}

/**
 * 渲染视图并返回生成的内容
 *
 * 该函数用于加载并渲染视图模板。它会首先验证配置是否正确加载，
 * 然后通过 `\boss\Template` 类进行渲染。若配置加载失败，
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

    $template = new \think\Template($config);;
    // $template = new \boss\View($config);

    $template->fetch($view, $data);
}

/**
 * 根据给定的键值从预定义的段数组中获取对应的段。
 *
 * @param int $key 要查找的段键值（从 1 开始）。
 * @return string|null 返回与键值对应的段，如果键值不存在则返回 null。
 */
function segment(int $key)
{
    $segments = SEGMENTS;

    // 将 $key 减 1，使其从 0 开始
    $adjustedKey = $key - 1;

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
 * 获取环境变量值，若未定义则返回默认值
 *
 * @param string $key     环境变量的键
 * @param string $default 默认值，当环境变量未定义时返回
 * @return string         环境变量值或默认值
 */
function env(string $key, string $default): string
{
    static $env = null;

    $file = ROOT_PATH . '.env';

    // 如果未加载过.env文件且文件存在，则加载
    if ($env === null && file_exists($file)) {
        $env = parse_ini_file($file, true, INI_SCANNER_RAW);
    }

    // 返回对应的值，如果键不存在或.env文件不存在则返回null
    return $env[$key] ?? $default;
}

/**
 * 获取会话值
 * @param string $key 会话键名
 * @param mixed $default 默认值（可选）
 * @return mixed 返回会话值或默认值
 */
function session($key, $default = null)
{
    return $_SESSION[$key] ?? $default;
}

/**
 * 设置会话值
 * @param string|array $key 如果是字符串，则表示键名；如果是数组，则表示批量设置
 * @param mixed $value 如果是字符串键名，则为对应的值
 * @return bool 是否设置成功
 */
function session_set($key, $value = null)
{
    // 批量设置
    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $_SESSION[$k] = $v;
        }
        return true;
    }

    // 单个设置
    $_SESSION[$key] = $value;
    return true;
}

/**
 * 删除会话值
 * @param string $key 要删除的会话键名
 * @return bool 是否成功删除
 */
function session_forget(string $keys)
{
    if (is_string($keys) && isset($_SESSION[$keys])) {
        unset($_SESSION[$keys]);
        return true;
    }

    return false;
}

/**
 * 模拟 Laravel 的 old() 函数
 * @param string $key 表单字段的名称
 * @param mixed $default 默认值（可选）
 * @return mixed 返回之前存储的值或默认值
 */
function old($key, $default = '')
{
    // 检查会话中是否存在旧值
    if (isset($_SESSION['old_input'][$key])) {
        $value = $_SESSION['old_input'][$key];
        // 清除已使用的旧值，避免重复填充
        unset($_SESSION['old_input'][$key]);
        return $value;
    }
    return $default;
}

/**
 * 存储表单数据到会话中
 * @param array $data 表单数据
 */
function storeOldInput($data)
{
    $_SESSION['old_input'] = $data;
}

/**
 * 输出 JSON 响应
 *
 * @param string $msg      要返回的消息内容
 * @param string $status   状态信息，例如 'success' 或 'error'，默认值为 'error'
 * @param int    $httpCode HTTP 状态码，默认值为 400
 *
 * 200：成功
 * 400：请求无效或格式错误，服务器无法理解。
 * 404：请求的资源未找到。
 * 405：请求方法不被允许（例如，GET 方法被用于需要 POST 请求的接口）。
 * 500：服务器遇到错误，无法完成请求。通常是服务器端的异常。
 */
function json(string $msg, string $status = 'error', int $httpCode = 400)
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
 * 生成完整的 URL
 *
 * - 如果传入路径参数 `$path`，生成基于当前主机的指定路径 URL。
 * - 如果 `$path` 为 `null` 或 `''`，返回当前页面的完整 URL（包含请求参数）。
 *
 * @param string|null $path 路径（默认为空字符串）。传入 null 或空字符串时返回当前页面 URL。
 * @return string 返回完整的 URL（包括协议、主机名、路径和查询参数）。
 * @throws InvalidArgumentException 如果主机名无效则抛出异常。
 */
function url(?string $path = ''): string
{
    // 获取协议类型
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    // 获取主机名
    $host = $_SERVER['HTTP_HOST'];
    if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        throw new InvalidArgumentException('Invalid host name.');
    }

    // 如果 $path 是 null，返回当前页面的完整 URL
    if ($path === null || $path === '') {
        $currentPath = $_SERVER['REQUEST_URI']; // 当前页面路径（带查询参数）
        return $protocol . '://' . $host . $currentPath;
    }

    // 对路径进行过滤并返回拼接后的 URL
    $sanitizedPath = filter_var($path, FILTER_SANITIZE_URL);
    return $protocol . '://' . $host . $sanitizedPath;
}

/**
 * 从会话中获取并删除一个闪存数据（flash data）。
 *
 * 闪存数据是一种只会在下一次请求时有效的数据，通常用于在重定向后显示一次性消息。
 *
 * @param string $key 闪存数据的键名。
 * @param mixed $default 如果闪存数据不存在时返回的默认值。
 * @return mixed 返回与键名关联的闪存数据，如果不存在则返回默认值。
 */
function flash(string $key, $default = null)
{
    // 确保会话已启动
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 构造完整的会话键名
    $sessionKey = 'flash__' . $key;

    $flash = $_SESSION[$sessionKey] ?? $default;

    unset($_SESSION[$sessionKey]);

    return $flash;
}

/**
 * 消毒数据
 *
 * @param  string $value
 * @return string
 */
function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
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
 * 过滤并清理 HTML 文本，移除所有 HTML 标签、多余的空格、换行符等。
 *
 * @param string $text 输入的 HTML 文本。
 * @return string 清理后的纯文本。
 */
function filter_all_html(string $text): string
{
    // 去除首尾空白字符
    $text = trim($text);

    // 去除反斜杠（用于处理转义字符）
    $text = stripslashes($text);

    // 移除所有 HTML 和 PHP 标签
    $text = strip_tags($text);

    // 定义需要替换的字符数组
    $replacements = [
        '&nbsp;', // HTML 空格
        '/',      // 斜杠
        "\t",     // 制表符
        '  ',     // 双空格
        '   ',    // 三空格
        '    ',   // 四空格
        '	'     // 制表符（再次确保）
    ];

    // 替换所有不需要的字符为空字符串
    $text = str_replace($replacements, '', $text);

    // 将换行符替换为 <br> 标签
    $text = preg_replace("/\r\n|\n|\r/", "<br>", $text);

    return $text;
}

/**
 * 移除字符串中的 XSS 风险内容
 *
 * @param string $string 待处理的字符串
 * @return string 处理后的安全字符串
 */
function remove_xss(string $string): string
{
    // 检查输入是否为字符串
    if (!is_string($string)) {
        throw new InvalidArgumentException("输入必须为字符串");
    }

    // 移除不可见控制字符
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

    // 定义危险标签和事件属性
    $dangerousTags = ['javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'];
    $dangerousEvents = ['onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload', 'onpointerout', 'onfullscreenchange', 'onfullscreenerror', 'onhashchange', 'onanimationend', 'onanimationiteration', 'onanimationstart', 'onmessage', 'onloadstart', 'ondurationchange', 'onloadedmetadata', 'onloadeddata', 'onprogress', 'oncanplay', 'oncanplaythrough', 'onended', 'oninput', 'oninvalid', 'onoffline', 'ononline', 'onopen', 'onpagehide', 'onpageshow', 'onpause', 'onplay', 'onplaying', 'onpopstate', 'onratechange', 'onsearch', 'onseeked', 'onseeking', 'onshow', 'onstalled', 'onstorage', 'onsuspend', 'ontimeupdate', 'ontoggle', 'ontouchcancel', 'ontouchend', 'ontouchmove', 'ontouchstart', 'ontransitionend', 'onvolumechange', 'onwaiting', 'onwheel', 'onbegin'];

    // 合并危险标签和事件属性
    $dangerousPatterns = array_merge($dangerousTags, $dangerousEvents);

    // 替换危险内容为 'xxx'
    foreach ($dangerousPatterns as $pattern) {
        $string = str_ireplace($pattern, 'xxx', $string);
    }

    // 对字符串进行 HTML 编码，防止 XSS
    $string = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return $string;
}

/**
 * 解析内容中的网址，并将匹配的 URL 替换为带有 <a> 标签的链接。
 * 排除已经被 <a> 标签包裹的 URL。
 *
 * @param string $content 要解析的内容。
 * @return string 替换后的内容。
 */
function replace_urls_with_links(string $content): string
{
    // 定义匹配 URL 的正则表达式（排除已被 <a> 标签包裹的 URL）
    static $pattern = null;
    if ($pattern === null) {
        $pattern = '/(?<!href=")https?:\/\/[^\s"\']+|(?:www\.)?[a-z0-9-]+(?:\.[a-z]{2,}){1,2}[^\s"\']*/i';
    }

    // 使用 preg_replace_callback 替换匹配的 URL
    $content = preg_replace_callback($pattern, function ($matches) {
        $url = $matches[0];

        // 如果 URL 没有协议，添加默认的 http://
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'http://' . $url;
        }

        // 生成链接文本（去掉协议部分）
        $link_text = preg_replace('/^https?:\/\//i', '', $url);

        // 返回带有 <a> 标签的链接
        return '<a href="/jump?target=' . urlencode($url) . '" target="_blank" rel="nofollow noopener">' . escape($link_text) . '</a>';
    }, $content);

    return $content;
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
        500 => '服务器内部错误',
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

    exit(1);
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
            return $count . '<span class="time-text">' . $unit . '</span>';
        }
    }

    // 如果时间差为 0 或负数，返回默认值
    return '刚刚';
}

/**
 * 生成一个指定长度的唯一ID（类似于UUID）
 *
 * @param int $length 生成ID的长度，默认为32
 * @return string 返回生成的唯一ID字符串
 */
function uuid(int $length = 32): string
{
    $bytes = random_bytes((int)ceil($length / 2));
    return substr(bin2hex($bytes), 0, $length);
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
