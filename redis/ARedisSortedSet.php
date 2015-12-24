<?php
namespace yii\liuxy\redis;

use Yii;
use yii\liuxy\redis\collections\CMapIterator;
use yii\base\Event;
use yii\base\ModelEvent;

/**
 * Represents a redis sorted set.
 *
 * Redis Sorted Sets are, similarly to Redis Sets, non repeating collections of Strings. The difference is that every member of a Sorted Set is associated with score, that is used in order to take the sorted set ordered, from the smallest to the greatest score. While members are unique, scores may be repeated.
 *
 * <pre>
 * $set = new ARedisSortedSet("mySortedSet");
 * $set->add("myThing", 0.5);
 * $set->add("myOtherThing", 0.6);
 *
 * foreach($set as $key => $score) {
 *    echo $key.":".$score."\n";
 * }
 * </pre>
 *
 * @author Charles Pick
 * @package yii\liuxy\redis
 */
class ARedisSortedSet extends ARedisIterableEntity {

    /**
     * Adds an item to the set
     * @param string $key the key to add
     * @param integer $value the score for this key
     * @return boolean true if the item was added, otherwise false
     */
    public function add ($key , $value) {
        if (!$this->beforeAdd()) {
            return false;
        }
        if (!$this->getConnection ()->getClient ()->zadd ($this->name , $value , $key)) {
            return false;
        }
        $this->_data = null;
        $this->_count = null;
        $this->afterAdd();
        return true;
    }
    /**
     * @param $params [score1, value1, score2, value2, ...]
     * @return bool|mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function batchAdd($params)
    {
        if (!is_array($params) || !$this->beforeAdd()) {
            return false;
        }
        array_unshift($params, $this->name);
        $result = call_user_func_array(array($this->getConnection()->getClient(), 'zadd'), $params);
        if(!$result){
            return false;
        }
        $this->_data = null;
        $this->_count = null;
        $this->afterAdd();
        return $result;
    }

    /**
     * Removes an item from the set
     * @param string $key the item to remove
     * @return boolean true if the item was removed, otherwise false
     */
    public function remove ($key) {
        if (!$this->getConnection ()->getClient ()->zrem ($this->name , $key)) {
            return false;
        }
        $this->_data = null;
        $this->_count = null;
        return true;
    }


    /**
     * Gets the intersection between this set and the given set(s), stores it in a new set and returns it
     * @param ARedisSortedSet|string $destination the destination to store the result in
     * @param mixed $set The sets to compare to, either ARedisSortedSet instances or their names
     * @param array $weights the weights for the sets, if any
     * @return ARedisSortedSet a set that contains the intersection between this set and the given sets
     */
    public function interStore ($destination , $set , $weights = null) {
        if ($destination instanceof ARedisSortedSet) {
            $destination->_count = null;
            $destination->_data = null;
        } else {
            $destination = new ARedisSortedSet($destination , $this->getConnection ());
        }
        if (is_array ($set)) {
            $sets = $set;
        } else {
            $sets = array($set);
        }

        foreach ($sets as $n => $set) {
            if ($set instanceof ARedisSortedSet) {
                $sets[$n] = $set->name;
            }
        }

        array_unshift ($sets , $this->name);
        $parameters = array(
            $destination->name ,
            $sets ,
        );
        if ($weights !== null) {
            $parameters[] = $weights;
        }
        $total = call_user_func_array (array(
            $this->getConnection ()->getClient () ,
            "zinterstore"
        ) , $parameters);
        $destination->_count = $total;
        return $destination;
    }

    /**
     * Gets the union of this set and the given set(s), stores it in a new set and returns it
     * @param ARedisSortedSet|string $destination the destination to store the result in
     * @param mixed $set The sets to compare to, either ARedisSortedSet instances or their names
     * @param array $weights the weights for the sets, if any
     * @return ARedisSortedSet a set that contains the union of this set and the given sets
     */
    public function unionStore ($destination , $set , $weights = null) {
        if ($destination instanceof ARedisSortedSet) {
            $destination->_count = null;
            $destination->_data = null;
        } else {
            $destination = new ARedisSortedSet($destination , $this->getConnection ());
        }
        if (is_array ($set)) {
            $sets = $set;
        } else {
            $sets = array($set);
        }

        foreach ($sets as $n => $set) {
            if ($set instanceof ARedisSortedSet) {
                $sets[$n] = $set->name;
            }
        }

        array_unshift ($sets , $this->name);
        $parameters = array(
            $destination->name ,
            $sets ,
        );
        if ($weights !== null) {
            $parameters[] = $weights;
        }
        $total = call_user_func_array (array(
            $this->getConnection ()->getClient () ,
            "zunionstore"
        ) , $parameters);
        $destination->_count = $total;
        return $destination;
    }

    /**
     * 按序号区间查询
     * @param $start
     * @param $stop
     * @param bool|false $descending 是否降序
     * @param null $withScores 是否返回score值
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function range($start, $stop, $descending = false, $withScores = null){
        if(!$descending){
            return $this->getConnection()->getSlave()->zrange($this->name, $start, $stop, $withScores);
        }else{
            return $this->getConnection()->getSlave()->zrevrange($this->name, $start, $stop, $withScores);
        }
    }

    /**
     * 按分数区间查询
     * @param $min
     * @param $max
     * @param bool|false $descending 是否降序
     * @param array $options
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function rangeByScore($min, $max, $descending = false, $options = array()){
        if(!$descending){
            return $this->getConnection()->getSlave()->zRangeByScore($this->name, $min, $max, $options);
        }else{
            return $this->getConnection()->getSlave()->zRevRangeByScore($this->name, $min, $max, $options);
        }
    }

    /**
     * 按序号区间删除
     * @param $start
     * @param $stop
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function removeByRank($start, $stop){
        return $this->getConnection()->getClient()->zRemRangeByRank($this->name, $start, $stop);
    }

    /**
     * 按分数区间删除
     * @param $min
     * @param $max
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function removeByScore($min, $max){
        return $this->getConnection()->getClient()->zRemRangeByScore($this->name, $min, $max);
    }

    /**
     * 分数区间内元素总数
     * @param $min
     * @param $max
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function countByScore($min, $max){
        return $this->getConnection()->getSlave()->zCount($this->name, $min, $max);
    }
    /**
     * Returns an iterator for traversing the items in the set.
     * This method is required by the interface IteratorAggregate.
     * @return Iterator an iterator for traversing the items in the set.
     */
    public function getIterator () {
        return new CMapIterator($this->getData ());
    }


    /**
     * Gets the number of items in the set
     * @return integer the number of items in the set
     */
    public function getCount () {
        if ($this->_count === null) {
            $this->_count = $this->getConnection ()->getSlave ()->zcard ($this->name);
        }
        return $this->_count;
    }

    /**
     * Gets all the members in the  sorted set
     * @param boolean $forceRefresh whether to force a refresh or not
     * @return array the members in the set
     */
    public function getData ($forceRefresh = false) {
        if ($forceRefresh || $this->_data === null) {
            $this->_data = $this->getConnection ()->getSlave ()->zrange ($this->name , 0 , -1 , true);
        }
        return $this->_data;
    }

    /**
     * Returns whether there is an item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to check on
     * @return boolean
     */
    public function offsetExists ($offset) {
        return ($offset >= 0 && $offset < $this->getCount ());
    }

    /**
     * Returns the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to retrieve item.
     * @return mixed the item at the offset
     * @throws \yii\base\Exception if the offset is invalid
     */
    public function offsetGet ($offset) {
        return $this->_data[$offset];
    }

    /**
     * Sets the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to set item
     * @param mixed $item the item value
     */
    public function offsetSet ($offset , $item) {
        $this->add ($offset , $item);
    }

    /**
     * Unsets the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to unset item
     */
    public function offsetUnset ($offset) {
        $this->remove ($offset);
    }

    public function beforeAdd () {
        $event = new ModelEvent();
        $event->sender = $this;
        $this->onBeforeAdd($event);
        return $event->isValid;
    }

    /**
     * Invoked after the mutex is locked.
     * The default implementation raises the onAfterLock event
     */
    public function afterAdd () {
        $event = new Event;
        $event->sender = $this;
        $this->onAfterAdd ($event);
    }

    /**
     * Raises the onBeforeLock event
     * @param \yii\base\Event $event the event to raise
     */
    public function onBeforeAdd ($event) {
        $this->_connection->trigger ("onBeforeAdd" , $event);
    }

    /**
     * Raises the onAfterLock event
     * @param \yii\base\Event $event the event to raise
     */
    public function onAfterAdd ($event) {
        $this->_connection->trigger ("onAfterAdd" , $event);
    }
}