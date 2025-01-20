<?php

declare(strict_types=1);

namespace boss;

use Exception;

/**
 * 数据模型类
 * 用于数据表操作及缓存管理
 */
class Model
{
    // 数据表名
    public $table;

    // 数据表主键
    public $primaryKey   = null;

    // 模型对象
    public static $obj   = null;

    // 数据操作对象
    public $model;

    // 数据操作错误信息
    public $error;

    // 缓存对象
    protected ?object $cacher = null;

    protected $parameter = null;

    // 缓存启用标志
    protected bool $isCacheEnabled;

    // 构造函数用于初始化获取数据表操作对象
    public function __construct($connectDB = true)
    {
        if ($this->table && $connectDB) {
            $this->model = db($this->table);
        }

        // 检查缓存是否启用
        $this->isCacheEnabled = (bool)config('cache.start');
    }

    /**
     * 连接数据库
     *
     * @return void
     */
    public function connectDB(): void
    {
        $this->model = db($this->table);
    }

    /**
     * 根据 ID 查询一条数据
     *
     * @param int|string $id       数据的唯一标识符
     * @param string     $fields   要查询的字段，默认为 '*'
     * @return mixed              返回查询结果
     */
    public function findById($id, $fields = '*')
    {
        return $this->model->where($this->primaryKey . ' = ?', [$id])->fetch($fields);
    }

    /**
     * 获取当前执行的 SQL 语句
     *
     * @return string SQL 语句
     */
    public function getSql(): string
    {
        return $this->model->getSql();
    }

    /**
     * 获取数据库操作中的错误信息
     *
     * @return string 错误信息
     */
    public function error(): string
    {
        return $this->model->error();
    }

    /**
     * 获取缓存对象实例
     *
     * @throws Exception 如果缓存配置无效
     * @return object 返回缓存对象
     */
    protected function getCacher(): ?object
    {
        if ($this->cacher) {
            return $this->cacher;
        }

        $config = config('cache');
        if (empty($config)) {
            throw new Exception("缓存设置错误");
        }

        if (!in_array($config['driver'], config('allowCacheType'))) {
            throw new Exception('缓存类型错误');
        }

        $type           = ucfirst($config['driver']);
        $className      = 'boss\\caches\\' . $type . 'Cacher';
        $this->cacher   = $className::getInstance($config);

        return $this->cacher;
    }

    /**
     * 缓存查询结果
     *
     * @param string $name        缓存名称
     * @param string $queryMethod 查询方法名
     * @param mixed  $parameter   查询方法的参数
     * @param int    $timer       缓存过期时间（秒），默认为 3600 秒
     * @param bool   $isSuper     是否为超级缓存，默认为 true
     * @return mixed              查询结果，缓存命中则直接返回缓存数据
     */
    public function cache(string $name, string $queryMethod, $parameter = null, int $timer = 3600, bool $isSuper = true)
    {
        // 如果缓存功能未启用，则直接调用查询方法
        if (!$this->isCacheEnabled) {
            return $this->$queryMethod($parameter);
        }

        // 获取缓存实例
        $this->getCacher();

        // 根据参数设置缓存名称
        $cacheName = $this->setCacheName($name, $parameter, $isSuper);

        // 尝试从缓存中获取数据
        $cachedRes = $this->cacher->get($cacheName);
        if ($cachedRes !== false) {
            return $cachedRes; // 缓存命中
        }

        // 缓存未命中，执行查询方法
        $queryRes = $this->$queryMethod($parameter);

        // 将查询结果存入缓存，并设置过期时间
        $this->cacher->set($cacheName, $queryRes, $timer);

        return $queryRes;
    }

    /**
     * 设置缓存名称
     *
     * @param string $name       缓存名称
     * @param mixed  $parameter  查询方法的参数
     * @param bool   $isSuper    是否为超级缓存
     * @return string            生成的缓存名称
     */
    protected function setCacheName(string $name, $parameter = '', bool $isSuper = true): string
    {
        $cacheConfig = config('cache');
        $parameter = is_array($parameter) ? implode('_', $parameter) : (string)$parameter;

        $cacheName = $isSuper
            ? $cacheConfig['prefix'] . $name . $parameter
            : $cacheConfig['prefix'] . 'CONTROLLER_NAME' . '_' . 'METHOD_NAME' . '_' . $name . $parameter;

        // 如果开启了缓存名称的 MD5 加密
        if (!empty($cacheConfig['name2md5'])) {
            return md5($cacheName);
        }

        return $cacheName;
    }

    /**
     * 清除缓存
     *
     * @param string $name       缓存名称
     * @param mixed  $parameter  缓存参数
     * @param bool   $isSuper    是否为超级缓存
     * @return void
     */
    protected function removeCache(string $name, $parameter = null, bool $isSuper = true): void
    {
        $this->getCacher();

        $cacheName = $this->setCacheName($name, $parameter, $isSuper);
        $this->cacher->removeCache($cacheName);
    }
}
