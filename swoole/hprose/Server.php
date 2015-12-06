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
    const DEFAULT_WORKER_NUMBER = 4;
    public $config = [];
    private $yiiConfigFile = false;
    private $pidFile = '/dev/shm/rpc_server.pid';
    private $yiiConfig = [];

    /**
     * 初始化swoole服务配置
     * @param $ini_file swoole的服务启动配置文件位置
     */
    public function __construct($ini_file, $pidFile = false) {
        if ($pidFile) {
            $this->pidFile = $pidFile;
        }
        if (!is_file($ini_file)) exit("Swoole AppServer配置文件错误($ini_file)\n");
        $config = parse_ini_file($ini_file, true);

        if (!is_array($this->config)) {
            $this->config = array();
        }
        $this->config = array_merge($this->config, $config);
        if (isset($this->config['server']['host']) && isset($this->config['server']['port'])) {
            parent::__construct($this->config['server']['host'] . ':' . $this->config['server']['port']);
        } else {
            parent::__construct(self::DEFAULT_HOST . ':' . self::DEFAULT_PORT);
        }
    }

    /**
     * 启动swoole服务
     * @param array $setting 自定义配置
     * @param string $yiiConfigFile Yii配置文件位置
     */
    public function run($setting = [], $yiiConfigFile = false) {
        register_shutdown_function(array($this, 'handleFatal'));
        if (!$yiiConfigFile) {
            $yiiConfigFile = ROOT_DIR . '/config/main.php';
        }
        if (empty($this->config)) exit("Swoole AppServer 未加载任务配置\n");
        if (isset($this->config['setting']['log_file']) && substr($this->config['setting']['log_file'], 0, 1) != '/') {
            $this->config['setting']['log_file'] = ROOT_DIR . '/' . $this->config['setting']['log_file'];
        }
        $this->yiiConfigFile = $yiiConfigFile;
        if (!function_exists('cli_set_process_title')) {
            swoole_set_process_name($this->config['server']['base.process.name'] . 'Master');
        } else {
            cli_set_process_title($this->config['server']['base.process.name'] . 'Master');
        }
        if (!isset($this->config['setting']['worker_num'])) {
            //set worker thread number
            $cpuInfo = @file("/proc/cpuinfo");
            if (false !== $cpuInfo) {
                $cpuInfo = implode("", $cpuInfo);
                @preg_match_all("/model\\s+name\\s{0,}\\:+\\s{0,}([\\w\\s\\)\\(\\@.-]+)([\r\n]+)/s", $cpuInfo, $model);
                if (false !== is_array($model[1])) {
                    $this->config['setting']['worker_num'] = sizeof($model[1]);
                } else {
                    $this->config['setting']['worker_num'] = self::DEFAULT_WORKER_NUMBER;
                }
            } else {
                $this->config['setting']['worker_num'] = self::DEFAULT_WORKER_NUMBER;
            }
        }
        $this->set(array_merge($setting, $this->config['setting']));
        $this->on('ManagerStart', function () {
            if (!function_exists('cli_set_process_title')) {
                swoole_set_process_name($this->config['server']['base.process.name'] . 'Manager');
            } else {
                cli_set_process_title($this->config['server']['base.process.name'] . 'Manager');
            }
        });
        $logPath = dirname($this->config['setting']['log_file']);
        $this->on('Task', function ($serv, $task_id, $from_id, $data) use ($logPath) {

            $args = $data;
            $file = $logPath . DIRECTORY_SEPARATOR . date('Y-m-d') . '.access.log';
            if (!is_dir($logPath)) {
                mkdir($logPath, 0755, TRUE);
            }

            $content = date('Y-m-d H:i:s') . ' worker_id=' . $from_id . ': ';
            if (is_string($args)) {
                $content .= $args . "\n";
            } else {
                foreach ($args as $arg) {
                    $content .= print_r($arg, TRUE) . "\n";
                }
            }
            $fh = fopen($file, 'a+');
            if ($fh) {
                fwrite($fh, $content . "\r\n");
                fclose($fh);
            }
            unset($content);

        });
        $this->on('Finish', function ($serv, $task_id, $data) {
            unset($data);
        });
        $this->on('Start', array($this, 'onStart'));
        $this->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->on('WorkerStop', array($this, 'onWorkerStop'));
        $this->on('WorkerError', array($this, 'onWorkerError'));
        $this->on('Shutdown', array($this, 'onShutdown'));
        $this->start();
    }

    /**
     * 服务启动时，设置进程文件名
     * @param $server   \swoole_server
     */
    public function onStart($server) {
        file_put_contents($this->pidFile, $server->master_pid);
    }

    /**
     * 服务关闭，重置进程文件
     */
    public function onShutdown() {
        file_put_contents($this->pidFile, '');
    }

    /**
     * 每个worker启动时，重新加载服务接口、Yii应用程序类
     * @param $server   \swoole_server
     * @param $workid
     */
    public function onWorkerStart($server, $workid) {

        try {

            //init yii2 application
            $this->yiiConfig = require_once($this->yiiConfigFile);

            //register service
            $dir = ROOT_DIR . $this->config['service']['path'];
            $namespace = $this->config['service']['namespace'];

            $yiiConfig = $this->yiiConfig;
            $dbConfig = require DB_CONFIG_FILE;
            $this->__set('onBeforeInvoke',function() use ($yiiConfig,$dbConfig) {
                $application = new Application($yiiConfig);
                $application->run();
            });
            $this->__set('onAfterInvoke',function() use ($dbConfig) {
                \Yii::$app->release($dbConfig);
                \Yii::$app = null;
            });

            $this->registerService($dir, $namespace, $server);
            $asyncDir = $dir . 'async' . DIRECTORY_SEPARATOR;
            $this->registerService($asyncDir, $namespace, $server, '_async');

            $worker_num = $this->config['setting']['worker_num'];

            if (!function_exists('cli_set_process_title')) {
                if ($workid >= $worker_num) {
                    swoole_set_process_name($this->config['server']['base.process.name'] . 'Task');
                } else {
                    swoole_set_process_name($this->config['server']['base.process.name'] . 'Worker');
                }
            } else {
                if ($workid >= $worker_num) {
                    cli_set_process_title($this->config['server']['base.process.name'] . 'Task');
                } else {
                    cli_set_process_title($this->config['server']['base.process.name'] . 'Worker');
                }
            }
        } catch(\Exception $ex) {
            $this->access_log('server exit.Exception = ' . $ex->getMessage());
        }
    }

    /**
     * worker 进程结束
     * @param $server
     * @param $worker_id
     */
    public function onWorkerStop($server, $worker_id) {
        /**
         * clear opcode cache,only support apc/opcache/eaccelerator
         */
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        if (function_exists('eaccelerator_purge')) {
            @eaccelerator_purge();
        }
    }

    /**
     * 工作进程异常错误处理
     * @param \swoole_server $serv
     * @param                $worker_id
     * @param                $worker_pid
     * @param                $exit_code
     */
    public function onWorkerError($serv, $worker_id, $worker_pid, $exit_code) {
        $this->access_log('worker_id = ' . $worker_id . '异常错误，pid=' . $worker_pid . '; exit_code=' . $exit_code);
    }

    /**
     * Fatal Error的捕获
     *
     * @codeCoverageIgnore
     */
    public function handleFatal() {
        $error = error_get_last();
        if (!isset($error['type'])) return;
        switch ($error['type']) {
            case E_ERROR :
            case E_PARSE :
            case E_DEPRECATED:
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                break;
            default:
                return;
        }
        $message = $error['message'];
        $file = $error['file'];
        $line = $error['line'];
        $log = "\n异常提示：$message ($file:$line)\nStack trace:\n";
        $trace = debug_backtrace(1);


        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $log .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object'])) {
                $log .= get_class($t['object']) . '->';
            }
            $log .= "{$t['function']}()\n";
        }
        $this->access_log($log);
    }

    /**
     * access log
     */
    public function access_log() {
        $args = func_get_args();
        $this->task($args);
        unset($args);
    }

    /**
     * 注册Service类,只支持一级目录及同步、异步目录的区分
     *
     * 注明：接口类的对外暴露public方法不能继承自父类，否则会报找不到方法的异常，
     * @param $dir  Service所在目录
     * @param $namespace    Service类的命名空间
     * @param $server   \swoole_server
     * @param string $moduleSuffix
     */
    private function registerService($dir, $namespace, $server, $moduleSuffix = '') {
        $files = [];
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $filetype = filetype($dir . $file);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($filetype == 'file' && $ext == 'php') {
                        $files [] = substr($file, 0, -4);
                    }
                }
                closedir($dh);
            }
        }
        if (count($files) > 0) {
            foreach ($files as $filename) {
                $module = strtolower(str_replace('Service', '', $filename)) . $moduleSuffix;
                $className = $namespace . $filename;
                $class = new $className($this->yiiConfig, $server);
                $this->add($class, $className, $module);
            }
            unset($filename);
        }
        unset($files);
    }
}

