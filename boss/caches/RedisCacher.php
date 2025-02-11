<?php

namespace boss\caches;

/**
 * 缓存类 [memcache]
 */

class RedisCacher
{

    private static $cacher = null;
    private $rdCacher;

    private function __construct($conf)
    {
        $this->rdCacher = new \redis();
        $res = $this->rdCacher->connect($conf['host'], $conf['port']);
        $this->rdCacher->auth($conf['password']);
    }

    public static function getInstance($conf)
    {
        if (self::$cacher == null) {
            self::$cacher = new RedisCacher($conf);
        }
        return self::$cacher;
    }

    public function get($name)
    {
        $cacheData = $this->rdCacher->get($name);
        if (empty($cacheData)) {
            return null;
        }
        return unserialize($cacheData);
    }

    public function set($name, $val, $expire = 3600)
    {
        if ($expire > 2592000) {
            $expire = 2592000;
        }
        $this->rdCacher->setex($name, $expire, serialize($val));
    }

    public function removeCache($name)
    {
        $this->rdCacher->delete($name);
    }

    public function clearCache()
    {
        $this->rdCacher->flushAll();
    }

    public function close()
    {
        $this->rdCacher->close();
    }
}
