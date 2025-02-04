<?php

namespace boss;

use Exception;
use RuntimeException;

class ErrorHandler extends Exception
{
    /**
     * 设置自定义错误处理函数
     *
     * @param int $code 错误代码
     * @param string $message 错误信息
     * @param string $file 发生错误的文件
     * @param int $line 发生错误的行号
     * @return void
     */
    public static function handleError(int $code, string $message, string $file, int $line): void
    {
        // 默认隐藏所有错误，运行报错服务器状态 500
        if (SHOW_ERROR === false) {
            abort(500);
        }

        // 获取出错行的源码
        $sourceCode = self::getErrorSourceCode($file, $line);

        // 将错误信息存储到变量中
        $errorInfo = [
            'code' => $code,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'source_code' => $sourceCode,
        ];

        // 包含错误模板，并传递错误信息
        include __DIR__ . '/templates/error.php';
        exit(1);
    }

    /**
     * 处理致命错误
     */
    public static function handleShutdown(): void
    {

        //获取最后发生的错误
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            // 将错误信息存储到变量中
            $sourceCode = self::getErrorSourceCode($error['file'], $error['line']);

            // 将错误信息存储到变量中
            $errorInfo = [
                'code' => 0, // 致命错误通常没有错误代码
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'source_code' => $sourceCode,
            ];

            if (SHOW_ERROR) {
                include __DIR__ . '/templates/error.php';
            } else {
                // 如果不显示错误页面，可以选择记录日志
                abort(500);
            }
            exit(1);
        }
    }

    /**
     * 获取出错行的源码
     *
     * @param string $file 出错文件路径
     * @param int $line 出错行号
     * @return string 出错行的源码
     */
    private static function getErrorSourceCode(string $file, int $line, int $contextLines = 3): string
    {
        if (!file_exists($file)) {
            return 'Source file not found.';
        }

        $fileLines = file($file, FILE_IGNORE_NEW_LINES);
        $startLine = max(1, $line - $contextLines);
        $endLine = min(count($fileLines), $line + $contextLines);

        // 生成带高亮的源码
        $sourceCode = '';
        for ($i = $startLine; $i <= $endLine; $i++) {
            $lineContent = htmlspecialchars($fileLines[$i - 1], ENT_QUOTES, 'UTF-8');
            if ($i === $line) {
                // 高亮出错行
                $sourceCode .= "<div style='background-color: #ffcccc;'>$i $lineContent</div>";
            } else {
                // 普通行
                $sourceCode .= "<div>$i $lineContent</div>";
            }
        }

        return $sourceCode;
    }

    /**
     * 显示调试信息
     *
     * 如果启用了调试模式（DEBUG 常量为 true），则加载并显示调试模板。
     *
     * @return void
     */
    public function debug(): void
    {
        if (defined('DEBUG') && DEBUG) {
            $debugTemplatePath = __DIR__ . '/templates/debug.php';
            if (is_file($debugTemplatePath)) {
                include $debugTemplatePath;
            } else {
                throw new RuntimeException('Debug template file not found.');
            }
        }
    }
}
