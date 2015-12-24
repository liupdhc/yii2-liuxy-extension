<?php
 /**
 * FileName: LocalBuffer.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\swoole\base;

/**
 * 基于swoole实现本地内存读取
 * Class LocalBuffer
 * @package yii\liuxy\swoole\base
 */
class LocalBuffer {
    private $swoole_buffer = false;

    public function __construct($size = 128) {
        $this->swoole_buffer = new \swoole_buffer($size);
    }

    /**
     * 当前数据的长度
     * @return int
     */
    public function length() {
        return $this->swoole_buffer ? $this->swoole_buffer->length : 0;
    }

    /**
     * 当前缓存区的容量
     * @return int
     */
    public function capacity() {
        return $this->swoole_buffer ? $this->swoole_buffer->capacity : 0;
    }

    /**
     * 将一个字符串数据追加到缓存区末尾
     * @param string $data
     * @return int  执行成功后，会返回新的长度
     */
    public function append($data)  {
        if ($this->swoole_buffer) {
            return $this->swoole_buffer->append($data);
        }
        return 0;
    }

    /**
     * 从缓冲区中取出内容,会复制一次内存,remove后内存并没有释放，
     * 只是底层进行了指针偏移。当销毁此对象时才会真正释放内存
     *
     * @param int $offset   表示偏移量，如果为负数，表示倒数计算偏移量
     * @param int $length   表示读取数据的长度，默认为从$offset到整个缓存区末
     * @param bool|false $remove    表示从缓冲区的头部将此数据移除。只有$offset = 0时此参数才有效
     */
    public function substr(int $offset, int $length = -1, bool $remove = false) {
        if ($this->swoole_buffer) {
            return $this->swoole_buffer->substr($offset, $length, $remove);
        }
        return null;
    }

    /**
     * 清理缓存区数据。执行此操作后，缓存区将重置。swoole_buffer对象就可以用来处理新的请求了。
     * 基于指针运算实现clear，并不会写内存
     */
    public function clear() {
        if ($this->swoole_buffer) {
            $this->swoole_buffer->clear();
        }
    }

    /**
     * 为缓存区扩容
     * @param int $new_size 指定新的缓冲区尺寸，必须大于当前的尺寸
     */
    public function expand(int $new_size) {
        if ($this->swoole_buffer) {
            $this->swoole_buffer->expand($new_size);
        }
    }

    /**
     * 向缓存区的任意内存位置写数据。read/write函数可以直接读写内存。所以使用务必要谨慎，否则可能会破坏现有数据。
     * 方法不会自动扩容,不能实现字符串追加，请使用append
     * @param int $offset   偏移量
     * @param string $data  写入的数据,不能超过缓存区的最大尺寸
     */
    public function write(int $offset, string $data) {
        if ($this->swoole_buffer) {
            $this->swoole_buffer->write($offset, $data);
        }
    }

    /**
     * 读取缓存区任意位置的内存。此接口是一个底层接口，可直接操作内存
     * @param int $offset 偏移量
     * @param int $length 要读取的数据长度
     * @return bool 如果offset错误或读取的长度超过实际数据的长度，这里会返回false
     */
    public function read(int $offset, int $length) {
        if ($this->swoole_buffer) {
            return $this->swoole_buffer->read($offset, $length);
        }
        return false;
    }
}