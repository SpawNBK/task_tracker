<?php

require_once ('Db.php');
require_once ('classes/api/v1/Models/User.php');


/**
 * Class Login
 *
 */
Class Login {

    /**
     * @var string
     * POST username
     */
    private $username = '';
    /**
     * @var string
     * POST password
     */
    private $password = '';
    /**
     * @var array | null
     * request parameters
     */
    private $requestParams = null;
    /**
     * @var string
     * GET, POST, PUT, DELETE
     */
    private $method = '';
    /**
     * @var string
     * sql users table name
     */
    protected $table_name = "api_users";

    /**
     * Login constructor.
     */
    public function __construct() {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Content-Type: application/json");

        $this->requestParams = $_REQUEST;
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return string
     * run login process
     */
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = strtolower($this->requestParams['username'] ?? '');
            $password = $this->requestParams['password'] ?? '';

            if ($username && $password) {
                $hash = $this->authorize($username, $password);
                if ($hash) {
                    return $this->response(array(
                        "data"=>array(
                            "token"=> $hash
                        )
                    ), 200);
                }
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Authorization failed",
                        "code"=>401
                    ),
                    "data" => array(
                        "message"=>"Username or password wrong"
                    )
                ), 401);

            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Authorization failed",
                    "code"=>422
                ),
                "data" => array(
                    "message"=>"Params username and password could not be blank"
                )
            ), 422);
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Method Not Allowed",
                "code"=>405
            )
        ), 405);
    }

    /**
     * @param string $username
     * @param string $password
     * @return false | string
     * return access token if user success login
     */
    public function authorize($username, $password) {

        $db = (new Database())->getConnection();

        $statement = $db->prepare("SELECT * FROM $this->table_name WHERE username = ? LIMIT 1");
        $statement->execute(array($username));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            if ($rows[0]['password'] == md5(md5(trim($password)))) {
                $user = new User($rows[0]['username'],$rows[0]['email'],$rows[0]['PERMISSION_LEVEL'],$rows[0]['ID']);
                if ($user->setHash($db, md5($this->generateCode(15)))) {
                        return $user->getHash();
                }
            }
        }
        return false;

    }

    /**
     * @param int $length
     * @return string
     * generate access token
     */
    function generateCode($length=6) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
        $code = "";
        $clen = strlen($chars) - 1;
        while (strlen($code) < $length) {
            $code .= $chars[mt_rand(0,$clen)];
        }
        return $code;
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
        return json_encode($data);
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


}
