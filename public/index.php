<?php

declare(strict_types=1);

/**
 * 所有请求都定向到此文件
 * 2025-1-17
 */

// 安全设置
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: same-origin");
header('X-Frame-Options: SAMEORIGIN');
header('X-Powered-By:');

define('TRACE', true);  // 追踪模式
// define('DEBUG', true);  // 调试模式
define('SHOW_ERROR', true); // 是否展示错误信息
define('ENABLE_SESSION', true); // 启用 session

require __DIR__ . '/../vendor/autoload.php';

\boss\App::run();
