<?php

require_once 'Models/Api.php';
require_once 'config/Db.php';
require_once 'Models/Users.php';

/**
 * Class UsersApi
 */
class UsersApi extends Api
{
    /**
     * @var string
     * api endpoint
     */
    public $endpoint = 'users';

    /**
     * @param PDO $db
     * @return false|User
     * Get and decode access token to User
     */
    public function hashCheck($db) {
        $hash = $this->requestParams['token'] ?? '';
        if ($hash) {
            if ($currentUser = (new Users())->getByHash($db, $hash)) {
                return $currentUser;
            }
            return false;
        }
        return false;

    }

    /**
     * method GET
     * get all data from user
     * http://site/api/v1/users
     * can be used with param self=1 - return info about your user
     * @return string
     */
    public function indexAction()
    {
        $db = (new Database())->getConnection();
        if (!$currentUser = $this->hashCheck($db)) {
            return $this->response(array(
                "error"=>array(
                    "message"=>"Unauthorized",
                    "code"=>401
                ),
                "data" => array(
                    "message"=>"token is wrong or null"
                )
            ), 401);
        }
        //if ($currentUser->PERMISSION_LEVEL >0) {
        $self = trim($this->requestParams['self'] ?? '');
        if ($self)
            $users = (new Users())->getByID($db, $currentUser->userid);
        else
            $users = (new Users())->getAll($db);
        //}
        //else
            //$users = (new Users())->getByID($db, $currentUser->userid);
        if ($users) {
            return $this->response(array(
                "data" => $users), 200);
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Data not found",
                "code"=>404
            )), 404);
    }

    /**
     * method GET
     * get data from users by ID
     * http://site/api/v1/users/25
     * @return string
     */
    public function viewAction()
    {
        $db = (new Database())->getConnection();
        if (!$currentUser = $this->hashCheck($db)) {
            return $this->response(array(
                "error"=>array(
                    "message"=>"Unauthorized",
                    "code"=>401
                ),
                "data" => array(
                    "message"=>"token is wrong or null"
                )
            ), 401);
        }
        $id = intval(array_shift($this->requestUri));
        if($id){
            //if ($currentUser->PERMISSION_LEVEL >0) {
                $users = (new Users())->getByID($db, $id);
            //}
            //else
            //    $users = (new Users())->getByID($db, $currentUser->userid);
            if ( $users ) {
                return $this->response(array(
                "data" => $users), 200);
            }
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"User not found",
                "code"=>404
            )), 404);
    }

    /**
     * method POST
     * Create user from POST data
     * http://site/api/v1/users/ username,password,email
     * @return string
     */
    public function createAction()
    {
        $db = (new Database())->getConnection();
        if (!$currentUser = $this->hashCheck($db)) {
            $currentUser = false;
        }
        $username = strtolower($this->requestParams['username'] ?? '');
        $password = $this->requestParams['password'] ?? '';
        $email = htmlspecialchars(strip_tags($this->requestParams['email'] ?? ''));
        $permission = $this->requestParams['permission'] ?? 0;

        if ($username && $password && $email) {
            $userclass = new Users();
            if ($userclass->userExists($db, $username)) {
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed create user",
                        "code"=>422
                    ),
                    "data" => array(
                        "message"=>"username already exists"
                    )
                ), 422);
            }
            if ($currentUser && $currentUser->PERMISSION_LEVEL >0)
                $users = $userclass->create($db, $username, $email, $permission, $password);
            else
                $users = $userclass->create($db, $username, $email, 0, $password);
            if ($users) {
                return $this->response(array(
                    "data" => $users
                ), 200);
            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Failed create user",
                    "code"=>500
                )), 500);
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Failed create user",
                "code"=>422
            ),
            "data" => array(
                "message"=>"params username, password, email could not be blank"
            )
        ), 422);
    }

    /**
     * method PUT
     * Update user by ID
     * http://site/api/v1/users/25 username,password,email
     * @return string
     */
    public function updateAction()
    {
        $db = (new Database())->getConnection();
        if (!$currentUser = $this->hashCheck($db)) {
            return $this->response(array(
                "error"=>array(
                    "message"=>"Unauthorized",
                    "code"=>401
                ),
                "data" => array(
                    "message"=>"token is wrong or null"
                )
            ), 401);
        }
        $parse_url = parse_url($this->requestUri[0] ?? null);
        $userId = $parse_url['path'] ?? null;
        if ( $userId ){
            $usersClass = new Users();
            if(!$userId || !$usersClass->getByID($db, $userId)){
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed update user",
                        "code"=>404
                    ),
                    "data" => array(
                        "message"=>"UserID `$userId` not found"
                    )
                ), 404);
            }
            $password = $this->requestParams['password'] ?? '';
            $email = htmlspecialchars(strip_tags($this->requestParams['email'] ?? ''));
            $permission = intval($this->requestParams['permission'] ?? 0);
            if ($password && $email) {
                if ($currentUser->PERMISSION_LEVEL >0) {
                    $updated = $usersClass->updateUser($db, $userId, $password, $email, $permission);
                }
                else
                    $updated = $usersClass->updateUser($db, $currentUser->userid, $password, $email, $currentUser->PERMISSION_LEVEL);
                if ($updated) {
                    return $this->response(array(
                        "data" => $updated
                    ), 200);
                }
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed update user",
                        "code"=>500
                    )), 500);

            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Failed update user",
                    "code"=>422
                ),
                "data" => array(
                    "message"=>"params password, email could not be blank"
                )
            ), 422);

        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Failed update user",
                "code"=>422
            ),
            "data" => array(
                "message"=>"Failed validate userID"
            )), 422);
    }

    /**
     * method DELETE
     * Delete user by ID
     * http://site/api/v1/users/25
     * @return string
     */
    public function deleteAction()
    {
        $db = (new Database())->getConnection();
        if (!$currentUser = $this->hashCheck($db)) {
            return $this->response(array(
                "error"=>array(
                    "message"=>"Unauthorized",
                    "code"=>401
                ),
                "data" => array(
                    "message"=>"token is wrong or null"
                )
            ), 401);
        }
        $parse_url = parse_url($this->requestUri[0] ?? null);
        $userId = $parse_url['path'] ?? null;
        if($userId){
            if(!$userId || !(new Users())->getByID($db, $userId)){
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed remove user",
                        "code"=>404
                    ),
                    "data" => array(
                        "message"=>"UserID `$userId` not found"
                    )
                ), 404);
            }
            if ($currentUser->PERMISSION_LEVEL >0) {
                $users = (new Users())->deleteByID($db, $userId);
            }
            else
            {
                if ($userId == $currentUser->userid)
                    $users = (new Users())->deleteByID($db, $currentUser->userid);
                else
                    $users = false;
            }

            if ( $users ) {
                return $this->response(array(
                    "data" => array(
                        "message"=>"User has been deleted"
                    )), 200);
            }
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Failed remove user",
                "code"=>422
            )), 422);
    }

    /**
     * method PATCH
     * nothing do in users
     * @return string
     */
    public function patchAction()
    {
        return $this->response(array(
            "error"=>array(
                "message"=>"Method Not Allowed",
                "code"=>405
            )
        ), 405);
    }

}