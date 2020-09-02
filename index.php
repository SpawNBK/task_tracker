<?php

/**
 * @var array
 * requesturi
 */
$requestUri = explode('/', strtolower(trim($_SERVER['REQUEST_URI'],'/')));
if (count($requestUri) > 1) {
    $version = $requestUri[1] ?? '';
    $endpoint = $requestUri[2] ?? '';
    switch ($version) {
        case "v1":
            switch ($endpoint) {
                case "users":
                    require_once 'classes/api/v1/UsersApi.php';
                    try {
                        $api = new UsersApi();
                        echo $api->run();
                    } catch (Exception $e) {
                        echo json_encode(Array('error' => $e->getMessage()));
                    }
                    break;
                case "tasks":
                    require_once 'classes/api/v1/TasksApi.php';
                    try {
                        $api = new TasksApi();
                        echo $api->run();
                    } catch (Exception $e) {
                        echo json_encode(Array('error' => $e->getMessage()));
                    }
                    break;

                default:
                    echo badUrl();
                    break;
            }
            break;

        case "install":
            require_once 'config/install.php';
            $inst = new Installator();
            echo $inst->run();
            break;

        case "login":
            require_once 'config/login.php';
            $login = new Login();
            echo $login->run();
            break;

        default:
            echo badUrl();
            break;
    }
}
else

    echo badUrl();
/**
 * @return string
 * redirect on back request
 */
function badUrl() {
    header('HTTP/1.0 404 not found');
    return json_encode(array('error' =>
        ["message"=>"Bad request", "code" => 404]));
}
