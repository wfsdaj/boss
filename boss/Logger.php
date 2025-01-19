<?php

namespace core;

/**
 * 日志类
 * 记录日志信息到文件，或者系统错误日志。
 *
 * @method void alert(string $msg, array $context = [])
 * @method void error(string $msg, array $context = [])
 * @method void warning(string $msg, array $context = [])
 * @method void notice(string $msg, array $context = [])
 * @method void info(string $msg, array $context = [])
 * @method void debug(string $msg, array $context = [])
 */
class Logger
{
    // 日志级别常量
    const LEVELS = [
        'alert',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    // 默认日志文件夹路径
    private string $folder;

    /**
     * Logger constructor.
     * @param string $folder 日志文件夹路径
     */
    public function __construct(string $folder = LOG_PATH)
    {
        $this->folder = rtrim($folder, '/');
        $this->mkdir();
    }

    /**
     * 代理方法来记录不同级别的消息
     *
     * @param string $method 方法名
     * @param array $args 方法参数
     * @throws \InvalidArgumentException 如果日志级别无效
     */
    public function __call(string $method, array $args): void
    {
        if (in_array($method, self::LEVELS) && isset($args[0])) {
            $msg = $args[0];
            $context = $args[1] ?? [];
            $this->record(ucfirst($method), $msg, $context);
        } else {
            throw new \InvalidArgumentException('Invalid log level or missing message');
        }
    }

    /**
     * 记录消息
     *
     * @param string $level 日志级别
     * @param string $msg 日志内容
     * @param array $context 上下文信息
     */
    private function record(string $level, string $msg, array $context = []): void
    {
        // 格式化日志内容
        $logMessage = sprintf(
            '[%s] [%s] [%s]: %s %s',
            date('Y-m-d H:i:s'),
            $this->getClientIp(),
            $level,
            $msg,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        // 写入日志文件
        $this->writeToFile($logMessage);
    }

    /**
     * 如果日志文件夹不存在，则创建该文件夹
     */
    private function mkdir(): void
    {
        if (!file_exists($this->folder)) {
            mkdir($this->folder, 0755, true);
        }
    }

    /**
     * 将内容写入当前日志文件
     *
     * @param string $data 要附加的内容
     */
    private function writeToFile(string $data): void
    {
        $filename = $this->getFilename();
        if (file_put_contents($filename, $data . PHP_EOL, FILE_APPEND) === false) {
            // 如果文件写入失败，尝试记录到系统日志
            error_log('Failed to write log to file: ' . $filename);
        }
    }

    /**
     * 返回日志文件名
     *
     * @return string 日志文件名
     */
    private function getFilename(): string
    {
        return $this->folder . '/' . sprintf('%s.log', date('Y-m-d'));
    }

    /**
     * 获取客户端 IP 地址
     *
     * @return string 客户端 IP 地址
     */
    private function getClientIp(): string
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
                fn($ip) => trim($ip) !== 'unknown'
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
}
