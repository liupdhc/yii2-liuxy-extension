<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2015 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yii\liuxy\swoole\hprose;
use yii\liuxy\swoole\base\Application;

/**
 *
 * 扩展hprose的swoole服务端，实现自定义swoole的服务启动配置项、Yii配置、自动注册服务
 * @author liupeng <liupdhc@126.com>
 * @since 1.0
 */

class Server extends \Hprose\Swoole\Server {
    const DEFAULT_PORT = 8888;
    const DEFAULT_HOST = 'tcp://0.0.0.0';
    public $config = [];
    public $yiiConfigFile = false;

    /**
     * 初始化swoole服务配置
     * @param $ini_file swoole的服务启动配置文件位置
     */
    public function __construct($ini_file) {

        if (!is_file($ini_file)) exit("Swoole AppServer配置文件错误($ini_file)\n");
        $config = parse_ini_file($ini_file, true);

        if (!is_array($this->config))
        {
            $this->config = array();
        }
        $this->config = array_merge($this->config, $config);
        if (isset($this->config['server']['host']) && isset($this->config['server']['port'])) {
            parent::__construct($this->config['server']['host'].':'.$this->config['server']['port']);
        } else {
            parent::__construct(self::DEFAULT_HOST.':'.self::DEFAULT_PORT);
        }
    }

    /**
     * 启动swoole服务
     * @param array $setting    自定义配置
     * @param string $yiiConfigFile Yii配置文件位置
     */
    public function run($setting = [], $yiiConfigFile = ROOT_DIR . '/config/main.php') {
        if (empty($this->config)) exit("Swoole AppServer 未加载任务配置\n");
        if (isset($this->config['setting']['log_file']) && substr($this->config['setting']['log_file'], 0, 1) != '/') {
            $this->config['setting']['log_file'] = ROOT_DIR.'/'.$this->config['setting']['log_file'];
        }
        $this->yiiConfigFile = $yiiConfigFile;
        $this->set(array_merge($setting, $this->config['setting']));
        $this->on('WorkerStart' , [$this,'onWorkerStart']);
        $this->start();
    }

    /**
     * 每个worker启动时，重新加载服务接口、Yii应用程序类
     */
    public function onWorkerStart() {

        $dir = ROOT_DIR.$this->config['service']['path'];
        $namespace = $this->config['service']['namespace'];
        //init yii2 application
        $config = require_once($this->yiiConfigFile);

        $application = new Application($config);

        //register service
        $syncFiles = [];
        $asyncFiles = [];
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $filetype = filetype($dir . $file);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($filetype == 'file' && $ext == 'php') {
                        $syncFiles [] = substr($file, 0, -4);
                    }
                }
                closedir($dh);
            }
        }
        $asyncDir = $dir.'async'.DIRECTORY_SEPARATOR;
        if (is_dir($asyncDir)) {
            if ($dh = opendir($asyncDir)) {
                while (($file = readdir($dh)) !== false) {
                    $filetype = filetype($asyncDir . $file);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($filetype == 'file' && $ext == 'php') {
                        $asyncFiles [] = substr($file, 0, -4);
                    }
                }
                closedir($dh);
            }
        }

        if (count($syncFiles) > 0) {
            foreach ($syncFiles as $filename) {
                $module = strtolower(str_replace('Service', '', $filename));
                $className = $namespace.$filename;
                $class = new $className($application);
                $this->add($class, $className, $module);
            }
            unset($filename);
        }
        if (count($asyncFiles) > 0) {
            $namespace .='async\\';
            foreach ($asyncFiles as $filename) {
                $module = strtolower(str_replace('Service', '', $filename)).'_async';
                $className = $namespace.$filename;
                $class = new $className($application);
                $this->addAsync($class, $className, $module);
            }
            unset($filename);
        }
    }
}

