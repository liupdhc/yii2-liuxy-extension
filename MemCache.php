<?php
/**
 * FileName: MemCache.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy;

/**
 * 扩展yii2的缓存类，支持原子操作
 * Class Cache
 * @package yii\liuxy
 */
class MemCache extends \yii\caching\MemCache {
    /**
     * 原子递增
     * @param $key
     * @param int $offset   递增值
     */
    public function increment($key, $offset=1) {
        $cacheKey = $this->buildKey($key);
        $ret = $this->getMemcache()->increment($cacheKey,$offset);
        if (!$ret) {
            $this->getMemcache()->set($cacheKey, 1);
            return 1;
        }
        return $ret;
    }

    /**
     * 原子递减
     * @param $key
     * @param int $offset   递减值
     */
    public function decrement($key, $offset=1) {
        return $this->getMemcache()->decrement($this->buildKey($key), $offset);
    }

    public function add($key, $value, $duration = 0, $dependency = null)
    {
        if ($dependency !== null && $this->serializer !== false) {
            $dependency->evaluateDependency($this);
        }
        if (is_numeric($value)) {
            /**
             * 解决原子操作无法正确返回值
             */
        } elseif ($this->serializer === null) {
            $value = serialize([$value, $dependency]);
        } elseif ($this->serializer !== false) {
            $value = call_user_func($this->serializer[0], [$value, $dependency]);
        }
        $key = $this->buildKey($key);

        return $this->addValue($key, $value, $duration);
    }

    public function set($key, $value, $duration = 0, $dependency = null)
    {
        if ($dependency !== null && $this->serializer !== false) {
            $dependency->evaluateDependency($this);
        }
        if (is_numeric($value)) {
            /**
             * 解决原子操作无法正确返回值
             */
        } elseif ($this->serializer === null) {
            $value = serialize([$value, $dependency]);
        } elseif ($this->serializer !== false) {
            $value = call_user_func($this->serializer[0], [$value, $dependency]);
        }
        $key = $this->buildKey($key);

        return $this->setValue($key, $value, $duration);
    }

    /**
     * 重写获取值，支持原子操作不做序列化
     * @param mixed $key
     * @return bool|mixed
     */
    public function get($key)
    {
        $key = $this->buildKey($key);
        $value = $this->getValue($key);
        if ($value === false || $this->serializer === false) {
            return $value;
        } else if (is_numeric(trim($value))) {
            /**
             * 解决原子操作无法正确返回值
             */
            return intval($value);
        } elseif ($this->serializer === null) {
            $value = unserialize($value);
        } else {
            $value = call_user_func($this->serializer[1], $value);
        }
        if (is_array($value) && !($value[1] instanceof Dependency && $value[1]->getHasChanged($this))) {
            return $value[0];
        } else {
            return false;
        }
    }
    
    /**
     * 解决yii2的过期问题
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $duration)
    {
        $duration = $this->trimDuration($duration);
        $expire = $duration > 0 ? $duration + time() : 0;

        return $this->useMemcached ? $this->getMemcache()->add($key, $value, $expire) : $this->getMemcache()->add($key, $value, 0, $duration);
    }

    /**
     * 解决yii2的过期问题
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $duration) {
        $duration = $this->trimDuration($duration);
        $expire = $duration > 0 ? $duration + time() : 0;
        return $this->useMemcached ? $this->getMemcache()->set($key, $value, $expire) : $this->getMemcache()->set($key, $value, 0, $duration);
    }

    /**
     * Trims duration to 30 days (2592000 seconds).
     * @param integer $duration the number of seconds
     * @return int the duration
     */
    protected function trimDuration($duration)
    {
        if ($duration > 2592000) {
            \Yii::warning('Duration has been truncated to 30 days due to Memcache/Memcached limitation.', __METHOD__);
            return 2592000;
        }
        if ($duration < 0) {
            return 0;
        }
        return $duration;
    }
} 
