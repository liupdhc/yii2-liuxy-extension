<?php
/**
 * FileName: Woker.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\gearman;

use yii\liuxy\commands\AbstractCommand;
use yii\helpers\Console;

/**
 * Gearman的worker基类，基于命令行启动。
 * 所有的Worker的子类将不支持behaviors
 * Class Worker
 * @package yii\liuxy\gearman
 * @see yii\liuxy\commands\AbstractCommand
 */
abstract class Worker extends AbstractCommand {

    protected $group = 'default';
    protected $realClient = null;

    //回调函数前缀，可自行覆盖
    protected $callbackPrefix = 'daemon';

    public function actionIndex(){
        while($this->realClient->work()) {
            if ($this->realClient->returnCode() != GEARMAN_SUCCESS) {
                $this->release();
                break;
            }
        }
    }

    public function beforeAction($action){
        //log flush instantly
        \Yii::$app->getLog()->flushInterval = 1;
        foreach(\Yii::$app->getLog()->targets as $logTarget){
            $logTarget->exportInterval = 1;
        }

        $module = $this->module;
        $this->realClient = new \GearmanWorker();
        if (!empty($module) && isset(\Yii::$app->params['gearman'][$module->getUniqueId()])) {
            $this->group = $module->getUniqueId();
        }
        $this->realClient->addServers(\Yii::$app->params['gearman'][$this->group]);
        /**
         * 注册可用的方法
         */
        $methods = get_class_methods(get_class($this));
        if ($methods) {
            $this->stdout("Available worker:".PHP_EOL, Console::BOLD);
            foreach ($methods as $method) {
                if (substr($method, 0, strlen($this->callbackPrefix)) == $this->callbackPrefix) {
                    $funcName = "{$module->id}/{$this->id}/".lcfirst(substr($method, strlen($this->callbackPrefix)));
                    $this->stdout("\t".$funcName.PHP_EOL);
                    $that = $this;
                    $this->realClient->addFunction($funcName, function($gearmanJob) use ($that, $method){
                        call_user_func([$that, $method], $gearmanJob);
                        $that->cleanUp();
                    });
                }
            }
        }
        return parent::beforeAction($action);
    }

    /**
     * 每个回调之后需要做的清理工作
     */
    protected abstract function cleanUp();

    /**
     * 进程结束后的善后操作
     */
    protected function release() {

        \Yii::error('erro,return_code:' . $this->realClient->returnCode() . ',error info:' . $this->realClient->error());
    }


    /**
     * 验证是否发生错误
     * @return bool
     */
    protected function isError() {
        return $this->realClient->returnCode() != GEARMAN_SUCCESS;
    }

    /**
     * 继承GearmanWorker的方法
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args) {
        return call_user_func_array(array($this->realClient, $name), $args);
    }
}