<?php
/**
 * Copyright 2008-2015 OPPO Mobile Comm Corp., Ltd, All rights reserved.
 *
 * FileName: Client.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\gearman;

/**
 * gearman的客户端处理基类
 * 需要在Yii::$app->params中定义gearman数组，格式
 *  [
 *  'gearman' => [
 *  'default'=>'192.168.0.216:4371,192.168.0.216:4372'
 *  ]
 *  ]
 * 实际使用：
 * $client = new \yii\liuxy\gearman\Client();
 * $client->doBackend
 *
 *
 *
 *
 *
 *
 * Class Client
 * @package yii\liuxy\gearman
 */
class Client {
    private $group = 'default';
    private $realClient = null;

    /**
     * 初始化gearman客户端
     * @param string $group   客户端分类名称，用于分离多组gearman服务器，默认为default
     */
    public function __construct($group = 'default') {
        $this->group = $group;
        $this->realClient = new \GearmanClient();
        $this->realClient->addServers(\Yii::$app->params['gearman'][$this->group]);
    }

    /**
     * 验证是否发生错误
     * @return bool
     */
    public function isError() {
        return $this->realClient->returnCode() != GEARMAN_SUCCESS;
    }

    /**
     * 继承GearmanClient的方法
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args) {
        return call_user_func_array(array($this->realClient, $name), $args);
    }
}