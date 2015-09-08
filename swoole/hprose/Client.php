<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2015 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yii\liuxy\swoole\hprose;

/**
 *
 * 扩展hprose的swoole客户端，实现自定义模块、分布式加载
 * @author liupeng <liupdhc@126.com>
 * @since 1.0
 */

class Client extends \Hprose\Swoole\Client {
	/**
	 * @var string	模块名称
	 */
	private $module = false;

	/**
	 * @param string $module	模块名称
	 * @param string $rpcConfigFile	RPC服务器的配置文件路径
	 * @throws Exception	如果模块名称为空，将抛出异常
	 */
	public function __construct($module, $rpcConfigFile = ROOT_DIR."/config/global/rpc.inc.php") {
		if (empty($module)) {
			throw new Exception("Error module is empty", 1);
		}
		$this->module = $module;
		$configs = require_once($rpcConfigFile);
		parent::__construct(self::getNodeByWeight($configs[$module]));
	}

	/**
	 * 根据权重返回随机元素
	 *
	 * @param array $data
	 */
	private static function getNodeByWeight($data) {
		if ($data == null || count ( $data ) == 0) {
			return null;
		}
		$weight = 0;
		$tempdata = [ ];
		foreach ( $data as $one ) {
			if (! isset ( $one ['weight'] )) {
				return $data [0];
			}
			$weight += $one ['weight'];
			for($i = 0; $i < $one ['weight']; $i ++) {
				$tempdata [] = $one;
			}
		}
		$use = rand ( 0, $weight - 1 );
		return $tempdata [$use]['url'];
	}

	/**
	 * 支持按模块加载用户自定义函数
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	public function __call($name, $args) {
		if (strpos($name, 'async') !== false) {
			$name = $this->module . '_async_'. str_replace('async', '', $name);
		} else {
			$name = $this->module . '_'. $name;
		}
        return parent::__call($name, $args);
	}
}

