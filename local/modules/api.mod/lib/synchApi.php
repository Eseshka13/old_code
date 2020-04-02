<?php
use \UsersApi;
abstract class synchApi
{
    protected $method = '';
    protected $action = '';
    public $apiName = '';
    public $requestUri = [];
    public $requestParams = [];
    public $valid;
    public $user_id;
    public function __construct() {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        //Массив GET параметров разделенных слешем
        $this->requestUri = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
        $this->requestParams = $_REQUEST;

        //Определение метода запроса
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
    }

    public function run()
    {
        if (array_shift($this->requestUri) !== 'api' || array_shift($this->requestUri) !== $this->apiName) {
            throw new RuntimeException('API Not Found', 404);
        }
            $this->action = $this->getAction();

            if (method_exists($this, $this->action)) {
                return $this->{$this->action}();
            } else {
                throw new RuntimeException('Invalid Method', 405);
            }
    }

    protected function response($data, $status = 500) {
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
        return json_encode($data);
    }

    private function requestStatus($code) {
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code])?$status[$code]:$status[500];
    }

    protected function getAction()
    {
        $this->valid = UsersApi::ValidHash($this->requestParams['hash'], $this->requestParams['login']);
        $this->user_id = UsersApi::GetUserId($this->requestParams['hash'], $this->requestParams['login']);
        $method = $this->method;
        switch ($method) {
            case 'GET':
                if($this->requestUri){
                    return 'get';
                } else {
                    return 'defaultMethod';
                }
                break;
            case 'POST':
                return 'add';
                break;
            case 'PATCH':
                return 'update';
                break;
            case 'DELETE':
                return 'delete';
                break;
            default:
                return null;
        }
    }
    abstract protected function get();
    abstract protected function defaultMethod();
    abstract protected function add();
    abstract protected function update();
    abstract protected function delete();
}