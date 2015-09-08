<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2015 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yii\liuxy\swoole\base;

/**
 *
 * 扩展Yii2的Application，减少Controller、View层的加载
 * @author liupeng <liupdhc@126.com>
 * @since 1.0
 */

class Application extends \yii\base\Application {

	public function handleRequest($request) {
		return true;
	}
	protected function registerErrorHandler(&$config) {
        return true;
    }
}

