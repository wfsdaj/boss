<?php

namespace boss;

use Exception;
use RuntimeException;

/**
 * 调试类
 *
 * 用于在调试模式下显示调试信息。
 * 继承自 Exception，以便在需要时抛出调试异常。
 */
class Debug extends Exception
{
    /**
     * 显示调试信息
     *
     * 如果启用了调试模式（DEBUG 常量为 true），则加载并显示调试模板。
     *
     * @return void
     */
    public function display(): void
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