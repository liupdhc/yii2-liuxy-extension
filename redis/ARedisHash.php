<?php
namespace yii\liuxy\redis;

use Yii;
use yii\base\InvalidConfigException;
use yii\liuxy\redis\collections\CMapIterator;

/**
 * Represents a persistent hash stored in redis.
 * <pre>
 * $hash = new ARedisHash("myHash");
 * $hash['a key'] = "some value"; // value is instantly saved to redis
 * $hash['another key'] = "some other value"; // value is instantly saved to redis
 * </pre>
 * @author Charles Pick
 * @package yii\liuxy\redis
 */
class ARedisHash extends ARedisIterableEntity {

	/**
	 * Adds an item to the hash
	 * @param string $key the hash key
	 * @param mixed $value the item to add
	 * @return bool true if the item was added, otherwise false
	 * @throws InvalidConfigException
	 */
	public function add($key, $value) {
		if ($this->name === null) {
			throw new InvalidConfigException(get_class($this)." requires a name!");
		}
		if (!$this->getConnection()->getClient()->hset($this->name,$key, $value)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}

	/**
	 * Removes an item from the hash
	 * @param string $key the hash key to remove
	 * @return bool true if the item was removed, otherwise false
	 * @throws InvalidConfigException
	 */
	public function remove($key) {
		if ($this->name === null) {
			throw new InvalidConfigException(get_class($this)." requires a name!");
		}
		if (!$this->getConnection()->getClient()->hdel($this->name,$key)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}

	/**
	 * Returns an iterator for traversing the items in the hash.
	 * This method is required by the interface IteratorAggregate.
	 * @return Iterator an iterator for traversing the items in the hash.
	 */
	public function getIterator()
	{
		return new CMapIterator($this->getData());
	}

	/**
	 * Gets the number of items in the hash
	 * @return int the number of items in the set
	 * @throws InvalidConfigException
	 */
	public function getCount() {
		if ($this->_count === null) {
			if ($this->name === null) {
				throw new InvalidConfigException(get_class($this)." requires a name!");
			}
			$this->_count = $this->getConnection()->getSlave()->hlen($this->name);
		}
		return $this->_count;
	}

	/**
	 * Gets all the members in the  sorted set
	 * @param bool|false $forceRefresh whether to force a refresh or not
	 * @return array	the members in the set
	 * @throws InvalidConfigException
	 */
	public function getData($forceRefresh = false) {
		if ($forceRefresh || $this->_data === null) {
			if ($this->name === null) {
				throw new InvalidConfigException(get_class($this)." requires a name!");
			}
			$this->_data = $this->getConnection()->getSlave()->hgetall($this->name);
		}
		return $this->_data;
	}


	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to check on
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset>=0 && $offset<$this->getCount());
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to retrieve item.
	 * @return mixed the item at the offset
	 */
	public function offsetGet($offset)
	{
		$data = $this->getData();
		return isset($data[$offset]) ? $data[$offset] : null;
	}

	/**
	 * Sets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to set item
	 * @param mixed $item the item value
	 */
	public function offsetSet($offset,$item)
	{
		$this->add($offset,$item);
	}

	/**
	 * Unsets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to unset item
	 */
	public function offsetUnset($offset)
	{
		$this->remove($offset);
	}
}