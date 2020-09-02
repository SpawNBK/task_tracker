<?php

abstract class Api
{
    /**
     * @var string
     * api endpoint
     */
    public $endpoint = '';
    /**
     * @var string
     * GET, POST, PUT, DELETE
     */
    protected $method = '';
    /**
     * @var string
     * action for extend
     */
    protected $action = '';
    /**
     * @var array
     * exploded requests_uri
     */
    public $requestUri = [];
    /**
     * @var array|null
     * requestParams || php://input
     */
    public $requestParams = null;

    /**
     * Api constructor.
     */
    public function __construct() {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        $this->requestUri = explode('/', strtolower(trim($_SERVER['REQUEST_URI'],'/')));
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }
        if ($this->method !== 'POST') {
            $this->requestParams = Array();
            foreach (explode('&', file_get_contents('php://input')) as $chunk) {
                $param = explode("=", $chunk);
                if (count($param)>1) {
                    $this->requestParams += [urldecode($param[0]) =>urldecode($param[1])];
                }
            }
        }
        else
            $this->requestParams = $_REQUEST;
    }

    /**
     * @return void
     * run function of api class.
     */
    public function run() {
        //Первые 2 элемента массива URI должны быть "api" и название таблицы
        if(array_shift($this->requestUri) !== "api" ||array_shift($this->requestUri) !== "v1" || array_shift($this->requestUri) !== $this->endpoint){
            throw new RuntimeException('API Not Found', 404);
            //throw new RuntimeException($this->endpoint, 404);
        }
        //Определение действия для обработки
        $this->action = $this->getAction();

        //Если метод(действие) определен в дочернем классе API
        if (method_exists($this, $this->action)) {
            return $this->{$this->action}();
        } else {
            throw new RuntimeException('Invalid Method', 405);
        }
    }

    /**
     * @param array $data
     * @param int $status
     * @return string
     * configure response (status, json)
     */
    protected function response($data, $status = 500) {
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
        if ($status !== 200){
            $success = false;
        }
        else
        {
            $success = true;
        }
        $data = ['success' => $success] + $data;
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param int $code
     * @return string
     * function return string status of int status
     */
    private function requestStatus($code) {
        $status = array(
            200 => 'OK',
            400 => 'Update error',
            401 => 'Unauthorized',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Validation failed',
            500 => 'Internal Server Error',
        );
        return ($status[$code])?$status[$code]:$status[500];
    }

    /**
     * @return string|null
     * generate action from $method
     */
    protected function getAction()
    {
        $method = $this->method;
        switch ($method) {
            case 'GET':
                if($this->requestUri){
                    return 'viewAction';
                } else {
                    return 'indexAction';
                }
                break;
            case 'POST':
                return 'createAction';
                break;
            case 'PUT':
                return 'updateAction';
                break;
            case 'DELETE':
                return 'deleteAction';
                break;
            case 'PATCH':
                return 'patchAction';
                break;
            default:
                return null;
        }
    }

    /**
     * @return mixed
     * abstract method for inheritance
     */
    abstract protected function indexAction();
    /**
     * @return mixed
     * abstract method for inheritance
     */
    abstract protected function viewAction();
    /**
     * @return mixed
     * abstract method for inheritance
     */
    abstract protected function createAction();
    /**
     * @return mixed
     * abstract method for inheritance
     */
    abstract protected function updateAction();
    /**
     * @return mixed
     * abstract method for inheritance
     */
    abstract protected function deleteAction();
    /**
     * @return mixed
     * abstract method for inheritance
     */
    abstract protected function patchAction();
}