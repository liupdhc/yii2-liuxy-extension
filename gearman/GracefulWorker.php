<?php
/**
 * FileName: GrancefulWorker.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\gearman;

use yii\liuxy\gearman\Worker;
use yii\helpers\StringHelper;

/**
 * 保证当前job运行完成才退出或重启程序,避免重复job或中断job
 * 使用Ctrl-C 或 kill -s TERM pid 或 kill -15 pid关闭worker
 * 使用kill -s HUP pid 或 kill -1 pid重启worker
 * Class GracefulWorker
 * @package yii\liuxy\gearman
 */
abstract class GracefulWorker extends Worker
{
    //process signal number
    protected $signo = null;
    //full executable command for this script, it will be parsed automatically or you can specify this directly
    protected $fullName;
    /*gearman中的wait操作是在libgearman而不是PHP中处理的，这里要给worker设置一个超时时间
    这样php能够在超时发生时调用等待信号的处理器，但因这种方式要不断在php和libgearman之间切换context，且有延迟
    所以并非最优方式，但确实能解决在worker空闲时间不能处理信号的问题,否则只能在新的job来了之后才能接收一次信号*/
    protected $timeout = 30000;

    public function actionIndex()
    {
        //non-blocking is required when it want to handle signal
        $this->realClient->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        $this->realClient->setTimeout($this->timeout);
        while ($this->signo === null && ($this->realClient->work() ||
                $this->realClient->returnCode() == GEARMAN_IO_WAIT ||
                $this->realClient->returnCode() == GEARMAN_NO_JOBS) ||
            $this->realClient->returnCode() == GEARMAN_TIMEOUT) {

            pcntl_signal_dispatch();
            if ($this->realClient->returnCode() == GEARMAN_SUCCESS) {
                continue;
            }

            //在等任务前再检查一次
            if ($this->signo !== null) {
                exit;
            }

//            \Yii::info("Waiting for next job...\n", 'Job');
            if (!@$this->realClient->wait()) {
                if ($this->realClient->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
                    sleep(5);
                    continue;
                }
                //break;
            }
        }
    }

    /**
     * 信号处理器
     * @return bool
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if (!extension_loaded('pcntl')) {
                $this->stderr("Pcntl extension is not loaded.If you don't want to handle process signal, please set {handleSignal} to false" . PHP_EOL);
                exit;
            }
            $this->parseFullName();
            declare(ticks = 1);
            pcntl_signal(SIGTERM, [$this, 'signalHandler']);
            pcntl_signal(SIGHUP, [$this, 'signalHandler']);
            pcntl_signal(SIGINT, [$this, 'signalHandler']);

            return true;
        }
        return false;
    }

    /**
     * 根据运行时命令解析绝对路径
     */
    private function parseFullName()
    {
        if ($this->fullName === null) {
            $bin = realpath($_SERVER['_']);
            $argv = $_SERVER['argv'];
            if (StringHelper::endsWith($bin, 'bin/php')) {
                //run like: /usr/bin/php php-script [arguments|options]
                $script = realpath($argv[0]);
                $argv[0] = $script;
                array_unshift($argv, $bin);
                $this->fullName = $argv;
            } else {
                //run like: php-script [arguments|options]
                $argv = array_slice($argv, 1);
                array_unshift($argv, $bin);
                $this->fullName = $argv;
            }
        }
    }

    /**
     * 信息处理器
     * @param int $signo
     */
    protected function signalHandler($signo)
    {
        //accept signal once
        if ($this->signo === null) {
            switch ($signo) {
                //terminate
                case SIGTERM:
                case SIGINT:
                    $this->signo = $signo;
                    break;
                //restart
                case SIGHUP:
                    $this->signo = $signo;
                    register_shutdown_function(function () {
                        pcntl_exec($this->fullName[0], array_slice($this->fullName, 1));
                    });
                    break;
                //the others just ignore
                default:
                    break;
            }
        }
    }

}