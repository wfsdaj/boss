<?php

namespace core;

use Exception;

class Model
{
    // 数据表名
    public $table        = null;

    // 数据表主键
    public $primaryKey   = null;

    // 模型对象
    public static $obj   = null;

    // 数据操作对象
    public $model        = null;

    // 数据操作错误信息
    public $error        = null;

    // 缓存对象
    protected $cacher    = null;

    protected $parameter = null;

    protected $isCacheEnabled = null;

    // 构造函数用于初始化获取数据表操作对象
    public function __construct($connectDB = true)
    {
        if ($this->table != null && $connectDB) {
            $this->model = db($this->table);
        }

        $this->isCacheEnabled = config('cache.start');
    }

    // 连接数据库
    public function connectDB()
    {
        $this->model = db($this->table);
    }

    // 利用 id 查询一条数据
    public function findById($id, $fields = '*')
    {
        return $this->model->where($this->primaryKey . ' = ?', array($id))->fetch($fields);
    }

    // 获取刚刚运行的 sql 语句
    public function getSql()
    {
        return $this->model->getSql();
    }

    // 获取 数据操作过程中产生的错误信息
    public function error()
    {
        return $this->model->error();
    }

    // 在模型内实现缓存 - 获取缓存对象
    protected function getCacher()
    {
        if (!empty($this->cacher)) {
            return null;
        }

        $config = config('cache');
        if (empty($config)) {
            throw new Exception("缓存设置错误");
        }

        if (!in_array($config['driver'], config('cache.allowCacheType'))) {
            throw new Exception('缓存类型错误');
        }

        $type           = ucfirst($config['driver']);
        $className      = 'core\\caches\\' . $type . 'Cacher';
        $this->cacher   = $className::getInstance($config);
    }

    /**
     * 缓存查询结果的方法
     *
     * @param string $name        缓存名称
     * @param string $queryMethod 查询方法名，该方法应返回需要缓存的数据
     * @param mixed  $parameter   可选的参数，用于设置缓存名称或其他目的
     * @param int    $timer       缓存过期时间，默认为3600秒（1小时）
     * @param bool   $isSuper     是否使用超级缓存（或其他特殊缓存机制），默认为true
     *
     * @return mixed 返回查询结果，如果缓存中存在，则直接返回缓存数据
     */
    public function cache($name, $queryMethod, $parameter = null, $timer = 3600, $isSuper = true)
    {
        // 如果缓存功能未启用，则直接调用查询方法
        if (!$this->isCacheEnabled) {
            return $this->$queryMethod();
        }

        // 获取缓存实例
        $this->getCacher();

        // 根据参数设置缓存名称
        $cacheName = $this->setCacheName($name, $parameter, $isSuper);

        // 尝试从缓存中获取数据
        $cachedRes = $this->cacher->get($cacheName);
        if ($cachedRes) {
            return $cachedRes; // 缓存命中，直接返回数据
        }

        // 缓存未命中，执行查询方法
        $queryRes = $this->$queryMethod();

        // 将查询结果存入缓存，并设置过期时间
        $this->cacher->set($cacheName, $queryRes, $timer);

        // 返回查询结果
        return $queryRes;
    }

    /**
     * 规划缓存命名
     *
     * @param  string  $name      缓存名称
     * @param  mixed   $parameter 缓存影响参数
     * @param  boolean $isSuper   是否为全局缓存
     * @return string 缓存名称
     */
    protected function setCacheName($name, $parameter = '', $isSuper = true)
    {
        $cacheConfig = config('cache');
        $parameter   = is_array($parameter) ? implode('_', $parameter) : $parameter;
        $cacheName   = $isSuper ? $cacheConfig['prefix'] . $name . $parameter : $cacheConfig['prefix'] . 'CONTROLLER_NAME' . '_' . 'METHOD_NAME' . '_' . $name . $parameter;
        if (empty($cacheConfig['name2md5'])) {
            return $cacheName;
        }
        return md5($cacheName);
    }

    // 清除指定缓存
    protected function removeCache($name, $parameter = null, $isSuper = true)
    {
        $this->getCacher();

        $cacheName = $this->setCacheName($name, $parameter, $isSuper);
        $this->cacher->removeCache($cacheName);
    }
}
