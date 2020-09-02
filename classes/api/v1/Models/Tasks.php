<?php
require_once 'Task.php';

/**
 * Class Tasks
 */
class Tasks {

    /**
     * @var string
     * table name for tasks
     */
    protected $table_name = "api_tasks";

    /**
     * @var string
     * table name for worker list info
     */
    protected $table_workers = "api_taskWorker";

    /**
     * function getAll
     * get all tasks for user
     * @param PDO $db
     * @param int $owner
     * @param string $filter
     * @param string $search
     * @return array|array[]|false
     */
    public function getAll($db, $owner, $filter, $search) {
        if ($search)
            $search = "%" . $search . "%";
        else
            $search = "%%";
        if ($filter)
            $filter = "%" . $filter . "%";
        else
            $filter = "%%";
        $statement = $db->prepare("SELECT t.ID, t.task_author, t.task_date, t.task_title, t.task_content, t.task_endDate, t.task_status,
            t.task_finishedDate, w.wTask_ID, w.wUser_ID from $this->table_name as t, $this->table_workers as w 
            where w.wTask_ID = t.ID and w.wUser_ID = ? and (t.task_title LIKE ? or t.task_content LIKE ?)
             and t.task_status LIKE ? ORDER BY t.task_date DESC");
        $statement->execute(array($owner, $search, $search, $filter));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            $tasksList = [];
            foreach($rows as $row)
            {
                $task = new Task($db, $row['task_title'], $row['task_content'], $row['task_author'], $row['ID'], $row['task_endDate'], $row['task_finishedDate'],
                    $row['task_status'], $row['task_date']);
                $tasksList[] = $task->getTask();
            }
            return $tasksList;
        }
        return false;
    }

    /**
     * function create
     * create new task
     * @param PDO $db
     * @param string $title
     * @param string $content
     * @param int $owner
     * @param string|null $enddate
     * @return array[]|false
     */
    public function create($db, $title, $content, $owner, $enddate) {
        $task = new Task($db, $title, $content, $owner, 0, $enddate);
        if ($task->addTask($db) > 0) {
            return $task->getTask();
        }
        return false;
    }


    /**
     * function getByID
     * get task by task id
     * @param PDO $db
     * @param int $id
     * @param int $userID
     * @param boolean $taskClass
     * @return array[]|false|Task
     */
    public function getByID($db, $id, $userID, $taskClass = false)
    {
        $statement = $db->prepare("SELECT t.ID, t.task_author, t.task_date, t.task_title, t.task_content, t.task_endDate, t.task_status,
             t.task_finishedDate, w.wTask_ID, w.wUser_ID from $this->table_name as t, $this->table_workers as w 
            where t.ID = ? and w.wTask_ID = t.ID and w.wUser_ID = ?  LIMIT 1");
        $statement->execute(array($id, $userID));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            $task = new Task($db, $rows[0]['task_title'], $rows[0]['task_content'], $rows[0]['task_author'], $rows[0]['ID'], $rows[0]['task_endDate'],
                $rows[0]['task_finishedDate'], $rows[0]['task_status'], $rows[0]['task_date']);
            if ($taskClass)
                return $task;
            else
                return $task->getTask();
        }
        return false;
    }


    /**
     * function updateTask
     * update task params
     * @param PDO $db
     * @param int $id
     * @param string $title
     * @param string $content
     * @param int $userID
     * @param Task $currentTask
     * @param null|string $endDate
     * @return array[]|false
     */
    public function updateTask($db, $id, $title, $content, $userID, $currentTask, $endDate = null) {
        $currentTask->title = $title;
        $currentTask->content = $content;
        $currentTask->task_endDate = $endDate;
        if ($currentTask->editTask($db, $userID)) {
            return $currentTask->getTask();
        }
        return false;

    }

    /**
     * function patchTask
     * update task info by one or more parameters
     * @param PDO $db
     * @param string $title
     * @param string $content
     * @param int $userID
     * @param Task $currentTask
     * @param string|null $endDate
     * @return array[]|false
     * update note
     */
    public function patchTask($db, $title, $content, $userID, $currentTask, $endDate = null) {
        if ($title)
            $currentTask->title = $title;
        if ($content)
            $currentTask->content = $content;
        if ($endDate)
            $currentTask->task_endDate = $endDate;

        if ($currentTask->editTask($db, $userID)) {
            return $currentTask->getTask();
        }
        return false;
    }



}