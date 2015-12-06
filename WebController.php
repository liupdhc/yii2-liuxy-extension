<?php
/**
 * author: liupeng
 * createTime: 2015/6/22 23:24
 * description: ${TODO}
 * file: WebController.php
 */

namespace yii\liuxy;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class WebController extends \yii\web\Controller{

    public $enableCsrfValidation = false;

    /**
     * 不需要验证用户登陆的的公开的actions
     *
     * @var array
     */
    protected $publicActions = ['*'];

    /**
     * 请求类实例
     *
     * @var \yii\web\Request
     */
    protected $request = null;

    /**
     * 响应对象
     *
     * @var \yii\web\Response
     */
    protected $response = null;

    /**
     * 模板视图
     * @var string
     */
    protected $template;

    /**
     * 返回的状态码
     *
     * @var int
     */
    protected $httpStatus = 200;

    /**
     * 存储要返回给用户的数据
     *
     * @var array
     */
    protected $responseData = [
        'code' => 200,
        'msg' => 'success'
    ];

    /**
     * 已响应
     * @var boolean
     */
    protected $responded = false;

    /**
     * 会话用户
     * @var mixed
     */
    protected $user = null;

    /**
     * 请求响应格式
     * 根据路由，解析出响应格式
     * 再由响应格式输出相应的内容给用户
     * 如
     * 讲 /user/1001 或者 /user/1001.html指向 /user/entry
     * 并根据用户id -> 1001获取相应用户信息返回给请求用户
     * 如果是 /user/1001.json 则返回相应的json格式数据
     *
     * @var string
     *
     */
    protected $format = 'html';

    /**
     * 可用的格式
     *
     * @var array
     */
    protected $formats = ['*'];

    /**
     * 判断当前页面是不是公开的不需要验证用户信息
     * @return bool
     */
    protected  function isPublic() {
        return (in_array($this->action->id, $this->publicActions) || in_array('*', $this->publicActions));
    }

    /**
     * @inheritdoc
     */
    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction'
            ]
        ];
    }

    /**
     * 重写init函数，支持语言包设置
     */
    public function init() {
        if (isset($_GET['nginx_uri'])) {
            unset($_GET['nginx_uri']);
        }
        parent::init();
        $this->request = Yii::$app->request;
        $this->response = Yii::$app->response;
        $this->request->enableCsrfValidation = $this->enableCsrfValidation;
        if (isset($_REQUEST['lang']) && $_REQUEST['lang'] != "") {
            Yii::$app->language = $_REQUEST['lang'];
            setcookie('lang', $_REQUEST['lang']);
        } elseif (isset($_COOKIE['lang']) && $_COOKIE['lang'] != "") {
            Yii::$app->language = $_COOKIE['lang'];
        }
    }

    protected function getRequest() {
        if ($this->request === null) {
            $this->request = Yii::$app->request;
        }
        return $this->request;
    }

    protected function getResponse() {
        if ($this->response === null) {
            $this->response = Yii::$app->response;
        }
        return $this->response;
    }

    /**
     * 获取响应的格式
     * @return string
     */
    protected function getFormat() {
        if ('' != ($extension = pathinfo($this->request->getPathInfo(), PATHINFO_EXTENSION))) {
            $this->format = strtolower($extension);
        }
        return $this->format == '' ? 'html' : $this->format;
    }

    /**
     * 设置响应格式
     * @param $format
     * @return \yii\liuxy\WebController
     */
    public function setFormat($format) {
        $this->format = $format == '' ? 'html' : strtolower(trim($format));
        return $this;
    }

    /**
     * 获取视图名称
     * @return string
     */
    public function getTemplate() {
        if ($this->template == '')
            $this->template = strtolower($this->action->id);
        return $this->template;
    }

    /**
     * 设置视图名称
     * @param string $template
     * @return \yii\liuxy\WebController
     */
    public function setTemplate($template) {
        $this->template = $template;
        return $this;
    }

    /**
     * 设置响应数据
     * @param $name
     * @param null $data
     * @return $this
     */
    public function setResponseData($name, $data = null) {
        if ($name === null) {
            $this->responseData = [];
        } else if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->setResponseData($key, $val);
            }
        } elseif (is_scalar($name)) {
            if ($data === null && isset($this->responseData[$name])) {
                unset($this->responseData[$name]);
            } else {
                $this->responseData[$name] = $data;
            }
        }
        return $this;
    }

    /**
     * 根据响应格式返回数据
     * @throws \Exception
     */
    protected function respondByFormat() {
        if ($this->responded) {
            return false;
        }
        $this->responded = true;
        $format = $this->getFormat();
        $method = 'respondBy' . ucfirst($format) . 'Format';
        $valid = ((in_array('*', $this->formats) || in_array($format, $this->formats)) && method_exists($this, $method));
        if ($valid === false)
            throw new \Exception('Invalid request fromat : ' . $format);
        call_user_func(array($this, $method));
    }

    /**
     * 输出html
     */
    protected function respondByHtmlFormat() {
        headers_sent() or header('Content-Type: text/html; charset=utf-8');
        $response = $this->render($this->getTemplate(), $this->responseData);
        echo $response;
        $this->end();
    }

    /**
     * 获取PHP的json配置项
     * @return int
     */
    protected function getJsonEncodeOptions() {
        $jsonEncodeOptions = $this->request->get('_pretty') == 'true' ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (JSON_UNESCAPED_UNICODE);
        return $jsonEncodeOptions;
    }

    /**
     * 输出json
     */
    protected function respondByJsonFormat() {
        headers_sent() or header('Content-Type: application/json; charset=utf-8');
        $response = json_encode($this->responseData, $this->getJsonEncodeOptions());
        if ('' != ($callback = $this->request->get('callback'))) {
            $response = $callback . '(' . $response . ');';
        }
        echo $response;
        $this->end();
    }

    /**
     * 从请求中获取值
     * @param $key
     * @param string $value
     * @return string
     */
    protected function get($key, $value = '') {
        $params = ArrayHelper::merge($_GET, $_POST);

        foreach($this->request->getBodyParams() as $k=>$val) {
            if (!isset($params[$k])) {
                $params[$k] = $val;
            }
        }
        if (isset($params[$key])) {
            return $params[$key];
        }
        return $value;
    }

    /**
     * 结束响应
     * @throws \yii\base\ExitException
     */
    public function end() {
        ob_flush();
        ob_clean();
        Yii::$app->end();
    }

    /**
     * 根据请求路径自动设置响应格式
     * @param string $id
     * @param array $params
     * @return mixed|void
     * @throws \yii\base\InvalidRouteException
     */
    public function runAction($id, $params = []) {
        if (strpos($id, '.') !== false) {
            $parts = pathinfo($id);
            $id = $parts['filename'];
            $this->setFormat($parts['extension']);
        }
        parent::runAction($id, $params);
    }

    public function afterAction($action, $result) {
        $errorHandler = Yii::$app->get('errorHandler');
        if (!empty($errorHandler->exception)) {
            $this->handleException($errorHandler->exception);
        }
        $result = parent::afterAction($action, $result);
        if ($this->response->getStatusCode() == 200) {
            return $this->respondByFormat();
        }
        return $result;
    }

    /**
     * 处理异常信息
     * @param $e
     */
    public function handleException($e) {
        $this->setResponseData(['code' => $e->getCode(), 'msg' => $e->getMessage(),'exception'=>YII_DEBUG ? VarDumper::dumpAsString($e): '']);
    }

    /**
     * 兼容单页面获取变量
     * @param string $view
     * @param array $params
     * @return mixed
     */
    public function render($view, $params = []) {
        $output = $this->getView ()->render ( $view, $params, $this );
        $layoutFile = $this->findLayoutFile ( $this->getView () );
        if ($layoutFile !== false) {
            $ret = ArrayHelper::merge ( $params, [
                'content' => $output
            ] );
            return $this->getView ()->renderFile ( $layoutFile, $ret, $this );
        } else {
            return $output;
        }
    }
} 