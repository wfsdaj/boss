<?php

namespace boss;

/**
 * Flash 类用于管理闪存数据（Flash Data）。
 * 闪存数据是一种只会在下一次请求时有效的数据，通常用于在重定向后显示一次性消息。
 */
class Flash
{
    /**
     * 闪存数据的前缀，用于区分普通会话数据。
     */
    private const FLASH_PREFIX = 'flash__';

    /**
     * 从会话中获取并删除一个闪存数据。
     *
     * @param string $key 闪存数据的键名。
     * @param mixed $defaultValue 如果闪存数据不存在时返回的默认值。
     * @return mixed 返回与键名关联的闪存数据，如果不存在则返回默认值。
     */
    public static function get(string $key, $defaultValue = null)
    {
        self::ensureSessionStarted();

        $sessionKey = self::FLASH_PREFIX . $key;
        $flash = $_SESSION[$sessionKey] ?? $defaultValue;

        // 删除会话中的闪存数据
        unset($_SESSION[$sessionKey]);

        return $flash;
    }

    /**
     * 检查是否存在指定的闪存数据。
     *
     * @param string $key 闪存数据的键名。
     * @return bool 如果闪存数据存在则返回 true，否则返回 false。
     */
    public static function has(string $key): bool
    {
        self::ensureSessionStarted();

        $sessionKey = self::FLASH_PREFIX . $key;
        return isset($_SESSION[$sessionKey]);
    }

    /**
     * 设置闪存数据。
     *
     * @param string $key 闪存数据的键名。
     * @param mixed $content 闪存数据的内容。
     */
    public static function set(string $key, $content): void
    {
        self::ensureSessionStarted();

        $sessionKey = self::FLASH_PREFIX . $key;
        $_SESSION[$sessionKey] = $content;
    }

    /**
     * 确保会话已启动。
     * 如果会话未启动，则调用 session_start()。
     */
    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}