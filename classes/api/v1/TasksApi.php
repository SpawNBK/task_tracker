<?php

require_once 'Models/Api.php';
require_once 'config/Db.php';
require_once 'Models/Users.php';
require_once 'Models/Tasks.php';

/**
 * Class TasksApi
 */
class TasksApi extends Api
{
    /**
     * @var string
     * api endpoint
     */
    public $endpoint = 'tasks';

    /**
     * function hashCheck
     * get and decode access token to User
     * @param PDO $db
     * @return false|User
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
     * function indexAction
     * method GET
     * get all data from tasks
     * http://site/api/v1/tasks
     * can be used with params:
     * 1. string filter - filter task by status (working, finished, archived)
     * 2. string search - filter task by text in title and content
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
        $search = trim($this->requestParams['search'] ?? '');
        $filter = trim($this->requestParams['filter'] ?? '');
        $tasks = (new Tasks())->getAll($db, $currentUser->userid, $filter, $search);
        if ($tasks) {
            return $this->response(array(
                "data" => $tasks), 200);
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Data not found",
                "code"=>404
            )), 404);
    }

    /**
     * function viewAction
     * method GET
     * get data from tasks by ID
     * http://site/api/v1/tasks/:id
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
            $tasks = (new Tasks())->getByID($db, $id, $currentUser->userid, true);
            if ( $tasks && $tasks->author == $currentUser->userid) {
                return $this->response(array(
                "data" => $tasks->getTask()), 200);
            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Task not found",
                    "code"=>404
                )), 404);
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Validation failed",
                "code"=>422
            )), 422);
    }

    /**
     * function createAction
     * method POST
     * Create task from POST data
     * http://site/api/v1/tasks/ title,content,enddate
     * @return string
     */
    public function createAction()
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
        $title = $this->requestParams['title'] ?? '';
        $content = $this->requestParams['content'] ?? '';
        $enddate = $this->requestParams['enddate'] ?? null;
        if ($enddate) {
            $enddate = date("Y-m-d H:m:s",strtotime($enddate));
        }

        if ($title && $content) {
            $tasks = (new Tasks())->create($db, $title, $content, $currentUser->userid, $enddate);
            if ($tasks) {
                return $this->response(array(
                    "data" => $tasks
                ), 200);
            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Failed create task",
                    "code"=>500
                )), 500);
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Failed create task",
                "code"=>422
            ),
            "data" => array(
                "message"=>"params title, content could not be blank"
            )
        ), 422);
    }

    /**
     * function updateAction
     * method PUT
     * Update task by ID
     * http://site/api/v1/tasks/:id title,content,enddate
     * Update task status by ID
     * http://site/api/v1/tasks/:id/finish | archive | enable
     * Update task workers
     * http://site/api/v1/tasks/:id/workers id
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
        $mode = $this->requestUri[1] ?? null;
        $id = $parse_url['path'] ?? null;
        if ( $id ){
            $tasksClass = new Tasks();
            if(!$id || !$currentTask = $tasksClass->getByID($db, $id, $currentUser->userid, true)){
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed update task",
                        "code"=>404
                    ),
                    "data" => array(
                        "message"=>"Task ID `$id` not found"
                    )
                ), 404);
            }
            if ($mode) {
                switch ($mode) {
                    case "finish":
                        if ($currentTask->changeStatus($db,'finished',$currentUser->userid)) {
                            return $this->response(array(
                                "data" => ["message"=>"Task $id has been successful finished"]
                            ), 200);
                        }
                        return $this->response(array(
                            "error"=>array(
                                "message"=>"Failed finish task $id",
                                "code"=>500
                            )), 500);
                        break;
                    case "archive":
                        if ($currentTask->changeStatus($db,'archived',$currentUser->userid)) {
                            return $this->response(array(
                                "data" => ["message"=>"Task $id has been successful archived"]
                            ), 200);
                        }
                        return $this->response(array(
                            "error"=>array(
                                "message"=>"Failed archive task $id",
                                "code"=>500
                            )), 500);
                        break;
                    case "enable":
                        if ($currentTask->changeStatus($db,'working',$currentUser->userid)) {
                            return $this->response(array(
                                "data" => ["message"=>"Task $id has been successful enabled"]
                            ), 200);
                        }
                        return $this->response(array(
                            "error"=>array(
                                "message"=>"Failed enable task $id",
                                "code"=>500
                            )), 500);
                        break;
                    case "workers":
                        $worker = $this->requestParams['id'] ?? '';
                        if (intval($worker)) {
                            if (!in_array(intval($worker), $currentTask->task_workers)) {
                                if ($currentTask->addWorker($db,intval($worker)) >0) {
                                    return $this->response(array(
                                        "data" => $currentTask->getTask()
                                    ), 200);
                                }
                                return $this->response(array(
                                    "error"=>array(
                                        "message"=>"Failed add worker $worker in task $currentTask->taskID",
                                        "code"=>500
                                    )), 500);
                            }
                            return $this->response(array(
                                "data" => $currentTask->getTask()
                            ), 200);
                        }
                        return $this->response(array(
                            "error"=>array(
                                "message"=>"Validation failed",
                                "code"=>422
                            ),
                            "data" => array(
                                "message"=>"ID is null or wrong. Read API faq"
                            )), 422);
                        break;
                    default:
                        return $this->response(array(
                            "error"=>array(
                                "message"=>"Validation failed",
                                "code"=>422
                            ),
                            "data" => array(
                                "message"=>"Unknown method $mode"
                            )), 422);
                        break;

                }
            }
            $title = $this->requestParams['title'] ?? '';
            $content = $this->requestParams['content'] ?? '';
            $endDate = $this->requestParams['enddate'] ?? null;
            if ($endDate) {
                $endDate = date("Y-m-d H:m:s",strtotime($endDate));
            }

            if ($title && $content && in_array($currentUser->userid, $currentTask->task_workers)) {
                $updated = $tasksClass->updateTask($db, $id, $title, $content, $currentUser->userid, $currentTask, $endDate);
                if ($updated) {
                    return $this->response(array(
                        "data" => $updated
                    ), 200);
                }
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed update task",
                        "code"=>500
                    )), 500);

            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Failed update task",
                    "code"=>422
                ),
                "data" => array(
                    "message"=>"wrong params or update someone else's record"
                )
            ), 422);

        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Validation failed",
                "code"=>422
            ),
            "data" => array(
                "message"=>"Task ID is null or wrong"
            )), 422);
    }

    /**
     * function deleteAction
     * method DELETE
     * Delete task by ID
     * http://site/api/v1/tasks/:id
     * Delete user from access to task
     * http://site/api/v1/tasks/:id/workers/:userid
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
        $taskID = $parse_url['path'] ?? null;
        $mode = $this->requestUri[1] ?? null;
        if($taskID){
            if(!$taskID || !$currentTask = (new Tasks())->getByID($db, $taskID, $currentUser->userid, true)){
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed remove task",
                        "code"=>404
                    ),
                    "data" => array(
                        "message"=>"Task ID `$taskID` not found"
                    )
                ), 404);
            }
            if ($mode) {
                switch ($mode) {
                    case "workers":
                        $wk_id = $this->requestUri[2] ?? null;
                        if ($wk_id && intval($wk_id)) {
                            if (intval($wk_id) == intval($currentTask->author)) {
                                return $this->response(array(
                                    "error"=>array(
                                        "message"=>"Validation failed",
                                        "code"=>422
                                    ),
                                    "data" => array(
                                        "message"=>"You could not remove task author from workers"
                                    )), 422);
                                break;
                            }
                            if ($currentTask->removeWorker($db,intval($wk_id)) > 0) {
                                return $this->response(array(
                                    "data" => $currentTask->getTask()
                                ), 200);
                            }
                            return $this->response(array(
                                "error"=>array(
                                    "message"=>"Failed remove worker $wk_id from task $currentTask->taskID",
                                    "code"=>500
                                )), 500);
                        }
                        return $this->response(array(
                            "error"=>array(
                                "message"=>"Validation failed",
                                "code"=>422
                            ),
                            "data" => array(
                                "message"=>"ID is null or wrong. Read API faq"
                            )), 422);
                        break;
                    default:
                        return $this->response(array(
                            "error"=>array(
                                "message"=>"Validation failed",
                                "code"=>422
                            ),
                            "data" => array(
                                "message"=>"Unknown method $mode"
                            )), 422);
                        break;

                }
            }
            if ($currentUser->userid == $currentTask->author)
                $success = $currentTask->deleteTask($db);
            else
                $success = false;
            if ( $success ) {
                return $this->response(array(
                    "data" => array(
                        "message"=>"Task has been deleted"
                    )), 200);
            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Failed delete task",
                    "code"=>422
                ),
                "data" => array(
                    "message"=>"Only creator can delete task"
                )
            ), 422);
        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Failed remove task",
                "code"=>422
            ),
            "data" => array(
                "message"=>"Failed validate task ID"
            )), 422);
    }

    /**
     * function patchAction
     * method PATCH
     * update task by ID
     * http://site/api/v1/tasks/:id title, content, enddate
     * @return string
     */
    public function patchAction()
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
        $id = $parse_url['path'] ?? null;
        if ( $id ){
            $tasksClass = new Tasks();
            if(!$id || !$currentTask = $tasksClass->getByID($db, $id, $currentUser->userid, true)){
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed update task",
                        "code"=>404
                    ),
                    "data" => array(
                        "message"=>"Task ID `$id` not found"
                    )
                ), 404);
            }
            $title = $this->requestParams['title'] ?? '';
            $content = $this->requestParams['content'] ?? '';
            $endDate = $this->requestParams['enddate'] ?? null;
            if ($endDate) {
                $endDate = date("Y-m-d H:m:s",strtotime($endDate));
            }

            if (($title or $content or $endDate) and in_array($currentUser->userid, $currentTask->task_workers)) {
                $updated = $tasksClass->patchTask($db, $title, $content, $currentUser->userid, $currentTask, $endDate);
                if ($updated) {
                    return $this->response(array(
                        "data" => $updated
                    ), 200);
                }
                return $this->response(array(
                    "error"=>array(
                        "message"=>"Failed update task",
                        "code"=>500
                    )), 500);

            }
            return $this->response(array(
                "error"=>array(
                    "message"=>"Failed update task",
                    "code"=>422
                ),
                "data" => array(
                    "message"=>"wrong params or update someone else's record"
                )
            ), 422);

        }
        return $this->response(array(
            "error"=>array(
                "message"=>"Validation failed",
                "code"=>422
            ),
            "data" => array(
                "message"=>"Task ID is null or wrong"
            )), 422);
    }

}