<?php

declare(strict_types=1);

namespace boss;

use RuntimeException;
use InvalidArgumentException;

class Event
{
    /**
     * 存储事件与监听器的映射关系
     * @var array<string, array<string>>
     */
    private static array $listeners = [];

    /**
     * 注册一个事件监听器
     *
     * @param string $event 事件名称
     * @param string $listenerClass 监听器类名
     * @throws InvalidArgumentException 如果监听器类不存在
     */
    public static function listen(string $event, string $listenerClass): void
    {
        if (!class_exists($listenerClass)) {
            throw new InvalidArgumentException("Listener class '$listenerClass' not found.");
        }

        // 避免重复注册相同的监听器
        if (!isset(self::$listeners[$event]) || !in_array($listenerClass, self::$listeners[$event], true)) {
            self::$listeners[$event][] = $listenerClass;
        }
    }

    /**
     * 触发一个事件
     *
     * @param string $event 事件名称
     * @param mixed ...$payload 传递给监听器的参数
     * @throws RuntimeException 如果监听器类没有 handle 方法
     */
    public static function dispatch(string $event, ...$payload): void
    {
        if (!isset(self::$listeners[$event])) {
            return; // 没有监听器注册到该事件
        }

        foreach (self::$listeners[$event] as $listenerClass) {
            $listener = new $listenerClass();

            if (!method_exists($listener, 'handle')) {
                throw new RuntimeException("Listener class '$listenerClass' must have a handle() method.");
            }

            // 调用监听器的 handle 方法
            $listener->handle(...$payload);
        }
    }

    /**
     * 获取所有已注册的事件和监听器
     *
     * @return array<string, array<string>> 返回事件与监听器的映射关系
     */
    public static function list(): array
    {
        return self::$listeners;
    }

    /**
     * 移除某个事件的所有监听器
     *
     * @param string $event 事件名称
     */
    public static function remove(string $event): void
    {
        if (isset(self::$listeners[$event])) {
            unset(self::$listeners[$event]);
        }
    }

    /**
     * 移除所有事件和监听器
     */
    public static function clear(): void
    {
        self::$listeners = [];
    }
}