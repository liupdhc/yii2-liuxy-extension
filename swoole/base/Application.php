<?php
 /**
 * FileName: Application.php
 * Author: liupeng
 * Date: 10/29/15
 */
namespace yii\liuxy\swoole\base;
use yii\helpers\VarDumper;

/**
 *
 * 扩展Yii2的Application，减少Controller、View层的加载
 * @author liupeng <liupdhc@126.com>
 * @since 1.0
 */

class Application extends \yii\console\Application {

	public function handleRequest($request) {
		$response = $this->getResponse();
		$response->exitStatus = 0;

		return $response;
	}

	public function release($dbConfig = false) {
//		ob_flush();
//		ob_clean();
		\Yii::trace('application release called.dbConfig='.VarDumper::dumpAsString($dbConfig),__METHOD__);
		if ($dbConfig) {
			/**
			 * 关闭非持久化的数据库连接
			 */
			$keys = array_keys($dbConfig);
			if ($keys) {
				foreach($keys as $item) {
					$dbObject = \Yii::$app->get($item);
					if ($dbObject instanceof \yii\db\Connection) {
						if (!isset($dbObject->attributes[\PDO::ATTR_PERSISTENT])) {
							if ($dbObject->getIsActive()) {
								\Yii::trace('db '.VarDumper::dumpAsString($dbObject).'close.',__METHOD__);
								$dbObject->close();
							}
						}

					}
				}
			}
		}
	}


}

