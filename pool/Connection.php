<?php
 /**
 * FileName: Connection.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\pool;

use Yii;
/**
 * base on php-cp
 * @see https://github.com/swoole/php-cp
 * Class Connection
 * @package yii\liuxy\pool
 */
class Connection extends \yii\db\Connection {
    /**
     * @var Transaction the currently active transaction
     */
    private $_transaction;
    /**
     * @var Schema the database schema
     */
    private $_schema;
    /**
     * @var string driver name
     */
    private $_driverName;
    /**
     * @var Connection the currently active slave connection
     */
    private $_slave = false;
    /**
     * @var array query cache parameters for the [[cache()]] calls
     */
    private $_queryCacheInfo = [];

    protected function createPdoInstance() {
        $pdoClass = $this->pdoClass;
        if ($pdoClass === null) {
            $pdoClass = 'pdo_connect_pool';
            if ($this->_driverName !== null) {
                $driver = $this->_driverName;
            } elseif (($pos = strpos($this->dsn, ':')) !== false) {
                $driver = strtolower(substr($this->dsn, 0, $pos));
            }
            if (isset($driver) && ($driver === 'mssql' || $driver === 'dblib' || $driver === 'sqlsrv')) {
                $pdoClass = 'yii\db\mssql\PDO';
            }
        }
        $this->attributes[\PDO::ATTR_PERSISTENT] = false;

        return new $pdoClass($this->dsn, $this->username, $this->password, $this->attributes);
    }

    public function close() {
        if ($this->pdo !== null) {
            Yii::trace('Closing DB connection: ' . $this->dsn, __METHOD__);
            $this->pdo->release();
            $this->pdo = null;
            $this->_schema = null;
            $this->_transaction = null;
        }

        if ($this->_slave) {
            $this->_slave->close();
            $this->_slave = null;
        }
    }


}