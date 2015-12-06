<?php
/**
 * author: liupeng
 * createTime: 2015/6/22 3:21
 * description: ${TODO}
 * file: Cache.php
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
} 