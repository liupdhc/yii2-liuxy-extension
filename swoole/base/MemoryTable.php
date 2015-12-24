<?php
 /**
 * FileName: MemoryTable.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\swoole\base;

/**
 * 基于共享内存和锁实现的超高性能，并发数据结构。用于解决多进程/多线程数据共享和同步加锁问题
 * 基于行锁，所以单次set/get/del在多线程/多进程的环境下是安全的
 * set/get/del是原子操作
 *
 * 类实现了迭代器和Countable接口，可以使用foreach进行遍历，使用count计算当前行数。
 *
 * foreach($table as $row)
 * {
 * var_dump($row);
 * }
 * echo count($table);
 *
 * @method set。   设置行的数据，使用key-value的方式来访问数据，$value，必须是一个数组，必须与字段定义的$name完全相同
 * @method incr(string $key, string $column, mixed $incrby = 1) 。    原子自增操作,$column 指定列名，仅支持浮点型和整型字段,$incrby 增量，默认为1
 * @method decr(string $key, string $column, mixed $decrby = 1)。   原子自减操作
 * @method get($key)。   获取一行数据，如果$key不存在，将返回false
 * @method exist($key)。   检查table中是否存在某一个key，如果$key不存在，将返回false
 * @method del($key)。   删除数据，如果$key不存在，将返回false,存在返回true
 * @method lock()。   锁定整个表,当多个进程同时要操作一个事务性操作时，一定要加锁，将整个表锁定。操作完成后释放锁。lock() 是互斥锁，所以只能保护lock/unlock中间的代码是安全的。lock/unlock之外的操作是不能保护的
 *                lock/unlock必须成对出现，否则会发生死锁，这里务必要小心
 *                lock/unlock之间不应该加入太多操作，避免锁的粒度太大影响程序性能
 *                lock/unlock之间的代码，应当try/catch避免抛出异常导致跳过unlock发生死锁
 * @method unlock 。  释放锁,底层使用Mutex，多次释放锁不会阻塞
 * Class MemoryTable
 * @package yii\liuxy\swoole\base
 */
class MemoryTable {
    /**
     * 默认为4个字节，可以设置1，2，4，8一共4种长度
     */
    const TYPE_COLUMN_INT = \swoole_table::TYPE_INT;
    /**
     * 设置后，设置的字符串不能超过此长度
     */
    const TYPE_COLUMN_STRING = \swoole_table::TYPE_STRING;
    /**
     * 会占用8个字节的内存
     */
    const TYPE_COLUMN_FLOAT = \swoole_table::TYPE_FLOAT;
    private $table = false;

    /**
     * 创建对象后会创建一个Mutex锁
     * $table->lock()/$table->unlock()在创建后即可使用
     * @param int $size 定表格的最大行数，必须为2的指数，如1024,8192,65536等
     */
    public function __construct($size) {
        $this->table = new \swoole_table($size);
    }

    /**
     * 创建好表的结构后，执行create后创建表
     */
    public function create() {
        if ($this->table) $this->table->create();
    }

    /**
     * 内存表增加一列
     * @param string $name 字段的名称
     * @param int $type 字段类型，支持3种类型，MemoryTable::TYPE_COLUMN_INT, MemoryTable::TYPE_COLUMN_STRING, MemoryTable::TYPE_COLUMN_FLOAT
     *                  TYPE_COLUMN_FLOAT::TYPE_STRING，字符串类型，必须要指定最大长度
     * @param $size
     * @return bool|void
     */
    public function addColumn($name, $type, $size = false) {
        return $this->table ? $this->table->column($name, $type, $size) : false;
    }

    /**
     * 内存表增加多列
     * @param array $cols   [['name',MemoryTable::TYPE_COLUMN_INT,2]]
     */
    public function addColumns($cols) {
        if (sizeof($cols) > 0) {
            foreach($cols as $col) {
                if (sizeof($col) == 3) {
                    $this->addColumn($col[0], $col[1], $col[2]);
                } else if (sizeof($col) == 2) {
                    $this->addColumn($col[0], $col[1]);

                }
            }
        }
    }

    public function __call($name, $args) {
        return call_user_func_array(array($this->table, $name), $args);
    }
}