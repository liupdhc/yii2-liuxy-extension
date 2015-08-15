<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2015 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\liuxy;

use Yii;
use yii\db\Exception;
use yii\helpers\VarDumper;

/**
 * 基于Yii2的自带基于cache（主键列）、分表的数据库操作
 *
 * @author liupeng <liupdhc@126.com>
 * @since 1.0
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord {
    const STATUS_OK = 1;//状态可用
    const STATUS_NO = 0;//状态不可用

    /**
     * 主键定义
     * @var
     */
    public static $pk = false;

    /**
     * 用于分表的散列键值
     * ExampleActiveRecord::subTableKey = xxx;
     * $record = new ExampleActiveRecord();
     * 此时$record->insert();调用时，将按xxx进行散列（必须同时实现shardTableRule方法）
     * @var bool
     */
    public static $subTableKey = false;

    /**
     * 支持分表散列获取表名
     * @return string
     */
    public static function tableName() {
        return '{{%' . Inflector::camel2id(StringHelper::basename(get_called_class()), '_') . '}}' . static::shardTableRule();
    }

    /**
     * 默认实现散列的方法
     * @return string
     */
    public static function shardTableRule() {
        return static::$subTableKey ? '_' . sprintf('%02x', static::$subTableKey % 6) : '';
    }

    /**
     *  是否支持cache
     */
    public static function enableCache() {
        if (isset(Yii::$app->cache) && static::$pk) {
            return true;
        }
        return false;
    }

    /**
     * 获取主键缓存键
     * @param $key  主键值
     * @param bool $is_array    是否是数组缓存
     * @return string   缓存键
     */
    public static function getCacheKey($key,$is_array = true) {
        if ($is_array) {
            return static::tableName().static::$pk.'is_array'.$key;
        } else {
            return static::tableName().static::$pk.$key;
        }

    }

    /**
     * 更新数据
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool|int
     * @throws \Exception
     */
    public function update($runValidation = true, $attributeNames = null) {
        $result = parent::update($runValidation, $attributeNames);
        if ($result) {
            if (self::enableCache()) {
                Yii::trace('delete cache:'.self::getCacheKey($this->getAttribute(static::$pk)), __METHOD__);
                Yii::$app->cache->delete(self::getCacheKey($this->getAttribute(static::$pk)));
                Yii::$app->cache->delete(self::getCacheKey($this->getAttribute(static::$pk), false));
            }
        }
        return $result;
    }

    /**
     * 根据主键获取记录（支持缓存）
     * @see also self::findByCondition($condition)
     * @param $key  主键值
     * @param $array 是否返回数组
     * @param $forceDb 是否强制从数据库获取
     */
    public static function findeByCache($key, $array = true, $forceDb = false) {
        if (static::$pk) {
            if ($forceDb) {
                if (!$array) {
                    return self::findByCondition([static::$pk=>$key]);
                } else {
                    return parent::find()->where([static::$pk=>$key])->asArray()->one();
                }
            } else {
                if (self::enableCache()) {
                    if (!$array) {
                        return self::findByCondition([static::$pk=>$key]);
                    } else {
                        $cache_key = self::getCacheKey($key, $array);
                        Yii::trace('from cache array:'.$cache_key, __METHOD__);
                        $row = Yii::$app->cache->get($cache_key);
                        if ($row) {
                            return $row;
                        }

                        $row = parent::find()->where([static::$pk=>$key])->asArray()->one();
                        if ($row) {
                            if (Yii::$app->cache->exists($cache_key)) {
                                Yii::$app->cache->set($cache_key, $row, Yii::$app->params['ttl']);
                            } else {
                                Yii::$app->cache->add($cache_key, $row, Yii::$app->params['ttl']);
                            }

                        }
                        return $row;
                    }

                } else {
                    throw new Exception('cache undefined');
                }
            }
        } else {
            throw new Exception('primary key undefined');
        }
    }

    /**
     * 查找单个对象记录，支持主键缓存获取
     * @param mixed $condition
     * @return mixed|null|static
     */
    public static function findByCondition($condition) {
        if (isset($condition[static::$pk])) {
            $cache_key = self::getCacheKey($condition[static::$pk], false);
            Yii::trace('from cache object:'.$cache_key, __METHOD__);
            if (self::allowFromCache($condition)) {
                $row = Yii::$app->cache->get($cache_key);;
                if ($row) {
                    return $row;
                }
            }
        }

        $row = parent::findByCondition($condition);
        if ($row && isset($condition[static::$pk]) && self::allowFromCache($condition)) {
            if (Yii::$app->cache->exists($cache_key)) {
                Yii::$app->cache->set($cache_key, $row, Yii::$app->params['ttl']);
            } else {
                Yii::$app->cache->add($cache_key, $row, Yii::$app->params['ttl']);
            }
        }
        return $row;
    }

    /**
     * 重写删除函数，支持主键删除自动清理Cache
     * @param $condition
     * @param array $params
     * @return int
     */
    public static function deteteAll($condition, $params = []) {
        $ret = parent::deleteAll($condition, $params);
        if ($ret && self::allowFromCache($condition)) {
            $cache_key = self::getCacheKey($condition[static::$pk], false);
            Yii::trace('delete cache:'.$cache_key, __METHOD__);
            Yii::$app->cache->delete($cache_key);
        }
        return $ret;
    }

    /**
     * 是否允许从缓存中获取数据
     * @param $condition
     * @return bool
     */
    protected static function allowFromCache($condition) {
        if (self::enableCache() && is_array($condition) && count($condition) == 1) {
            $keys = array_keys($condition);
            if ($keys[0] == static::$pk) {
               return true;
            }
        }
        return false;
    }

    /**
     * 构造条件SQL
     *
     * @param array $opt
     * @param array $condition
     * @param array $params
     * @return void|multitype:multitype:unknown Ambigous <string, multitype:string , multitype:string unknown >
     */
    public static function buildCondition($opt, &$condition, &$params, $likes = [], $between = []) {
        if ($opt == null || count($opt) == 0) {
            $condition = [];
            $params = [];
            return;
        }
        $condition = [
            'and'
        ];
        foreach ($opt as $key => $value) {
            if (in_array($key, $likes)) {
                $condition [] = [
                    'like',
                    $key,
                    $value
                ];
                continue;
            } else if (in_array($key, $between)) {
                $condition [] = [
                    'between',
                    $key,
                    $value [0],
                    $value[1]];
                continue;
            } else {
                $condition[] = $key . '=:' . $key;
            }
            $params[':' . $key] = $value;
        }
        return [$condition, $params];
    }

    /**
     * 从请求域中加载数据
     * @param \yii\web\Request $request
     * @param string $formName
     * @return bool
     */
    public function load($request, $formName = null) {
        $data = $request->getBodyParams();
        $keys = array_keys($this->getAttributes());

        if ($formName == null) {
            foreach($keys as $key) {
                if (isset($data[$key]) && !is_array($data[$key])) {
                    $this->setAttribute($key,$data[$key]);
                }
            }
            return true;
        } elseif (isset($data[$formName])) {
            foreach($keys as $key) {
                if (isset($data[$formName][$key]) && !is_array($data[$formName][$key])) {
                    $this->setAttribute($key,$data[$formName][$key]);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否来自于命令行的操作
     * @return bool
     */
    protected function isCli() {
        return (php_sapi_name() == 'cli');
    }

    /**
     * 验证前，自动设置操作者
     * @return bool
     */
    public function beforeValidate() {
        $keys = array_keys($this->attributes);
        if (!$this->isCli()) {
            if (in_array('update_time', $keys)) {
                $this->update_time = time();
            }
            if ($this->getIsNewRecord()) {
                if (in_array('insert_time', $keys)) {
                    $this->insert_time = time();
                }
            }
        }
        return parent::beforeValidate();
    }

    /**
     * 以数组形式返回列表记录
     * @param array $condition
     * @param array $orderBy
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findAllArray($condition = [],$orderBy = []) {
        return self::find()->where($condition)->asArray()->orderBy($orderBy)->all(static::getDb());
    }

    /**
     * 以数组形式返回单条记录
     * @param array $condition
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findOneArray($condition = []) {
        return self::find()->where($condition)->asArray()->one(static::getDb());
    }

    /**
     * 返回缓存实例
     * @return \yii\caching\Cache
     */
    public static function getCache() {
        return Yii::$app->cache;
    }

    /**
     * 获取文件缓存实例
     * @return \yii\caching\FileCache
     */
    public static function getFileCache() {
        return Yii::$app->fileCache;
    }
}
