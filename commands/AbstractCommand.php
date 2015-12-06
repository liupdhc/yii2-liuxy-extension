<?php

/**
 * Copyright 2008-2015 OPPO Mobile Comm Corp., Ltd, All rights reserved.
 *
 * FileName: AbstractCommand.php
 * Author: liupeng
 * Date: 10/29/15
 */
namespace yii\liuxy\commands;
/**
 *
 * 基本用法：
 * index topic/test/print --key=value arg1 arg2
 * index是一个sh文件，格式参照如下：
 * <code>
 * #!/usr/bin/env php
 * <?php
 * $environment = (getenv('APP_ENV') != '' ? getenv('APP_ENV') : 'dev');
 * defined('YII_ENV') or define('YII_ENV', $environment);
 * defined('YII_DEBUG') or define('YII_DEBUG', (YII_ENV == 'dev'));
 * defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
 * defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));
 * $classLoader = require('Yii2/vendor/autoload.php');
 * require('Yii2/vendor/yiisoft/yii2/Yii.php');
 * define('VENDOR_PATH', YII2_PATH . '/../../');
 * require(__DIR__ . '/../common/config/aliases.php');
 * $config = yii\helpers\ArrayHelper::merge(
require(__DIR__ . '/../common/config/main.php'),
 *     require(__DIR__ . '/config/main.php')
 * );
 * $application = new yii\console\Application($config);
 * $exitCode = $application->run();
 * exit($exitCode);
 * ?>
 * </code> *
 *
 * 命令行基类，集成自\yii\console\Controller
 * Class AbstractCommand
 * @package yii\liuxy\command
 * @see http://www.yiiframework.com/doc-2.0/guide-tutorial-console.html
 */
abstract class AbstractCommand extends \yii\console\Controller {

}