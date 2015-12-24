<?php

/**
 * FileName: IdGeneratorCounter.php
 * Author: liupeng
 * Date: 11/18/15
 */
namespace yii\liuxy\helpers\worker;

use yii\liuxy\gearman\db\FieldCounterWorker;

/**
 * ID生成器异步持久化的worker类
 * Class IdGeneratorCounter
 * @package yii\liuxy\helpers\worker
 */
abstract class IdGeneratorCounter extends FieldCounterWorker {

    protected $group = 'id';
    protected $step = 1;
    protected $db = 'db';
}