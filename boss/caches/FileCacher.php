<?php

namespace boss\caches;

use Exception;

/**
 * 文件型缓存支持类
 *
 * 提供基于文件的缓存功能，包括获取、设置、删除和清空缓存。
 */
class FileCacher
{
    // 单例缓存对象
    private static $cacher = null;

    // 缓存目录，默认使用根目录下的 runtime/cache 目录
    private $cacheDir = CACHE_PATH;

    /**
     * 私有构造函数，防止外部实例化
     */
    private function __construct($config = [])
    {
        // 检查缓存目录是否存在，不存在则创建
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0777, true)) {
                throw new Exception("无法创建缓存目录：{$this->cacheDir}");
            }
        }
    }

    /**
     * 获取文件缓存实例
     *
     * @param array $config 配置信息
     * @return FileCacher 文件缓存实例
     */
    public static function getInstance($config = [])
    {
        if (self::$cacher == null) {
            self::$cacher = new FileCacher($config);
        }
        return self::$cacher;
    }

    /**
     * 获取缓存数据
     *
     * @param string $name 缓存名称
     * @return mixed 缓存数据，或 false 如果缓存不存在或已过期
     */
    public function get($name)
    {
        $cacheFile = $this->cacheDir . $name . '.php';

        // 检查缓存文件是否存在
        if (!is_file($cacheFile)) {
            return false;
        }

        // 读取缓存数据
        $cacheData = require $cacheFile;
        // dd($cacheData);
        $cacheData = unserialize($cacheData);

        // 如果缓存已过期，返回 false
        if (isset($cacheData['expire']) && $cacheData['expire'] < time()) {
            return false;
        }

        return $cacheData['data'];
    }

    /**
     * 设置缓存数据
     *
     * @param string $name 缓存名称
     * @param mixed $data 缓存数据
     * @param int $expire 缓存过期时间（秒）
     */
    public function set($name, $data, $expire)
    {
        $cacheFile = $this->cacheDir . $name . '.php';

        // 构造缓存内容
        $cacheData = [
            'data'   => $data,
            'expire' => time() + $expire
        ];

        // 使用 serialize 序列化数据
        $serializedData = serialize($cacheData);
        $serializedData = str_replace('\\', '\\\\', $serializedData);  // 转义反斜杠
        $serializedData = str_replace('$', '\$', $serializedData);  // 转义美元符号
        // $serializedData = base64_encode($serializedData);

        // 生成缓存文件内容
        $cacheContent = "<?php if (!defined('APP_PATH')) { exit(); }\n\$data = <<<EOF\n{$serializedData}\nEOF;\nreturn \$data;";

        $cacheContent = mb_convert_encoding($cacheContent, 'UTF-8');

        // 写入缓存文件
        file_put_contents($cacheFile, $cacheContent, LOCK_EX);
    }

    /**
     * 删除指定缓存文件
     *
     * @param string $name 缓存名称
     * @return bool 删除成功返回 true，否则返回 false
     */
    public function removeCache($name)
    {
        $cacheFile = $this->cacheDir . $name . '.php';

        // 检查缓存文件是否存在
        if (!is_file($cacheFile)) {
            return true;
        }

        // 删除缓存文件
        return unlink($cacheFile);
    }

    /**
     * 清除所有缓存
     *
     * @return bool 清除成功返回 true，否则返回 false
     */
    public function clearCache()
    {
        $files = scandir($this->cacheDir);

        // 遍历缓存目录中的文件并删除
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $cacheFile = $this->cacheDir . $file;
                if (is_file($cacheFile)) {
                    @unlink($cacheFile);  // 忽略删除失败的错误
                }
            }
        }
        return true;
    }

    /**
     * 关闭缓存（目前未使用，保留作为接口）
     */
    public function close()
    {
        // 目前没有需要关闭的资源
    }
}
