<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2015 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yii\liuxy\swoole\hprose;

use Yii;
/**
 *
 * 接口服务基类
 * @author liupeng <liupdhc@126.com>
 * @since 1.0
 */

abstract class Service {
	/**
	 * @var \yii\liuxy\swoole\base\Application
	 */
	protected $app = false;
	public function __construct($app) {
		$this->app = $app;
	}
}

