<?php
 /**
 * FileName: Lock.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\swoole\base;


/**
 * 基于swoole的本地lock
 * Class Lock
 * @method lock。  加锁操作,如果有其他进程持有锁，那这里将进入阻塞，直到持有锁的进程unlock
 * @method unlock。   释放锁
 * @method lock_read。 加锁,lock_read方法仅可用在读写锁(RWLOCK)和文件锁(FILELOCK)中，
 *                  表示仅仅锁定读。在持有读锁的过程中，其他进程依然可以获得读锁，可以继续发生读操作
 *                  但不能$lock->lock()或$lock->trylock()，这两个方法是获取独占锁的。当另外一个进程获得了独占锁(调用$lock->lock/$lock->trylock)时，
 *                  $lock->lock_read()会发生阻塞，直到持有锁的进程释放
 * @method trylock。  加锁2,SEM信号量没有此方法。与lock方法不同的是，trylock()不会阻塞，
 *                  它会立即返回。当返回false时表示抢锁失败，有其他进程持有锁
 *                  返回true时表示加锁成功，此时可以修改共享变量
 * @method trylock_read。 加锁3,此方法与lock_read相同，但是非阻塞的。调用会立即返回，必须检测返回值以确定是否拿到了锁
 * @package yii\liuxy\swoole\base
 */
class Lock {
    const FILELOCK = SWOOlE_FILELOCK;
    const RWLOCK = SWOOLE_RWLOCK;
    const SEM = SWOOLE_SEM;
    const MUTEX = SWOOLE_MUTEX;
    const SPINLOCK = SWOOlE_SPINLOCK;
    private $lock = false;

    public function __construct($type = self::FILELOCK) {
        $this->lock = \swoole_lock();
    }

    public function __call($name, $args) {
        return call_user_func_array(array($this->lock, $name), $args);
    }

}