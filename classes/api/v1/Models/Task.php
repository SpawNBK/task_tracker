<?php

/**
 * Class Task
 */
class Task {

    /**
     * @var int
     * user id
     */
    public $taskID;

    /**
     * @var int
     * task owner
     */
    public $author;

    /**
     * @var string|null
     * task create date
     */
    public $createDate;

    /**
     * @var string
     * task title
     */
    public $title;

    /**
     * @var string
     * task content
     */

    public $content;

    /**
     * @var string
     * date while task must be finish
     */
    public $task_endDate;

    /**
     * @var string
     * task finished date
     */
    public $task_finishDate;

    /**
     * @var string
     * task status working | finished | archived
     */
    public $task_status;

    /**
     * @var array
     * task allow users array
     */
    public $task_workers = Array();

    /**
     * @var array
     * task modify info array
     */
    public $task_modify = Array();

    /**
     * @var string
     * table name for tasks
     */
    protected $table_name = "api_tasks";

    /**
     * @var string
     * table name for modify info
     */
    protected $table_edited = "api_taskModify";

    /**
     * @var string
     * table name for worker list info
     */
    protected $table_workers = "api_taskWorker";

    /**
     * Task constructor.
     * @param PDO $db
     * @param string $title
     * @param string $content
     * @param integer $author
     * @param integer $taskID
     * @param string|null $task_endDate
     * @param string|null $task_finishDate
     * @param string|null $task_status
     * @param string|null $task_created
     */
    public function __construct($db, $title, $content, $author, $taskID = 0, $task_endDate = null, $task_finishDate = null, $task_status = '', $task_created = null ){
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->taskID = $taskID;
        $this->task_endDate = $task_endDate;
        $this->task_finishDate = $task_finishDate;
        $this->task_status = $task_status;
        $this->createDate = $task_created;
        if ($taskID > 0) {
            $this->getWorkers($db);
            $this->getModify($db);
        }
    }

    /**
     * function getModify
     * get modify info from db
     * @param PDO $db
     * @return void
     */
    public function getModify($db) {
        $statement = $db->prepare("SELECT mUser_ID, modify_date FROM $this->table_edited WHERE mTask_ID = ? ORDER BY modify_date DESC");
        $statement->execute(array($this->taskID));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            foreach($rows as $row)
            {
                $this->task_modify[] = ["user_id" => intval($row['mUser_ID']), "modify_date" => $row['modify_date']];
            }
        }
    }

    /**
     * function getWorkers
     * get allow task users from db
     * @param PDO $db
     * @return void
     */
    public function getWorkers($db) {
        $statement = $db->prepare("SELECT wUser_ID FROM $this->table_workers WHERE wTask_ID = ?");
        $statement->execute(array($this->taskID));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            foreach($rows as $row)
            {
                $this->task_workers[] = intval($row['wUser_ID']);
            }
            sort($this->task_workers);
        }
    }


    /**
     * function markEdited
     * add edit task mark to db
     * @param PDO $db
     * @param int $userID
     * @return bool
     */
    public function markEdited($db, $userID){
        $statement = $db->prepare("INSERT INTO $this->table_edited(mTask_ID, mUser_ID)
        VALUES(?,?)");
        $this->task_modify[] = ["user_id" => intval($userID), "modify_date" => date("Y-m-d H:m:s")];;
        return ($statement->execute(array($this->taskID, $userID)) > 0);
    }

    /**
     * function addWorker
     * add user to access task
     * @param PDO $db
     * @param int $workerID
     * @return bool
     */
    public function addWorker($db, $workerID) {
        $statement = $db->prepare("INSERT INTO $this->table_workers(wTask_ID, wUser_ID)
        VALUES(?,?)");
        $this->task_workers[] = $workerID;
        sort($this->task_workers);
        return ($statement->execute(array($this->taskID, $workerID)) > 0);
    }

    /**
     * function removeWorker
     * remove user to access task
     * @param PDO $db
     * @param int $workerID
     * @return bool
     */
    public function removeWorker($db, $workerID) {
        $statement = $db->prepare("DELETE FROM $this->table_workers WHERE wTask_ID = ? AND wUser_ID = ?");

        foreach ($this->task_workers as $key=>$value) {
            if (intval($value) == intval($workerID))
                unset($this->task_workers[$key]);
        }
        sort($this->task_workers);
        return ($statement->execute(array($this->taskID, $workerID)) > 0);
    }

    /**
     * function addTask
     * add task to database
     * @param PDO $db
     * @return int $taskID | 0
     */
    public function addTask($db) {
        $statement = $db->prepare("INSERT INTO $this->table_name(task_author, task_title, task_content, task_endDate)
        VALUES(?,?,?,?)");
        if ($statement->execute(array($this->author, $this->title, $this->content, $this->task_endDate)) > 0) {
            $this->taskID = $db->lastInsertId();
            $this->task_status = 'working';
            $this->createDate = date("Y-m-d H:m:s");
            if ($this->addWorker($db, $this->author) > 0) {
                if ($this->markEdited($db, $this->author) > 0)
                    return $this->taskID;
            }

        }
        return 0;
    }

    /**
     * function editTask
     * edit task_title, task_content, task_endDate
     * @param PDO $db
     * @param int $userID
     * @return bool
     */
    public function editTask($db, $userID) {
        $statement = $db->prepare("UPDATE $this->table_name SET task_title = ?, task_content = ?, task_endDate = ? WHERE ID = ?");
        if ($statement->execute(array($this->title, $this->content, $this->task_endDate, $this->taskID)) > 0) {
            if ($this->markEdited($db, $userID) > 0)
                return true;
        }
        return false;
    }

    /**
     * function deleteTask
     * delete task by ID
     * @param PDO $db
     * @return bool
     */
    public function deleteTask($db) {
        $statement = $db->prepare("DELETE FROM $this->table_name WHERE ID = ?");
        return ($statement->execute(array($this->taskID)) > 0);
    }


    /**
     * function getTask
     * return task information
     * @return array[]
     */
    public function getTask() {
        $return = array(
            'id' => intval($this->taskID),
            'created' => $this->createDate,
            'author' => intval($this->author),
            'title' => $this->title,
            'content' => $this->content,
            'end_date' => $this->task_endDate,
            'status' => $this->task_status
        );
        if ($this->task_finishDate) {
            $return += ['finished' => $this->task_finishDate];
        }
        $return +=
        ['workers' => $this->task_workers, 'edited' => $this->task_modify];

        return $return;
    }

    /**
     * function changeStatus
     * change task status archived | finished | working
     * @param PDO $db
     * @param string $status
     * @param int $userID
     * @return bool
     */

    public function changeStatus($db, $status, $userID) {
        if ($status == "archived") {
            $statement = $db->prepare("UPDATE $this->table_name SET task_status = ? WHERE ID = ?");
            $s = $statement->execute(array($status, $this->taskID));
        }
        elseif ($status == "finished") {
            $statement = $db->prepare("UPDATE $this->table_name SET task_status = ?, task_finishedDate = now() WHERE ID = ?");
            $s = $statement->execute(array($status, $this->taskID));
        }
        else {
            $statement = $db->prepare("UPDATE $this->table_name SET task_status = ?, task_finishedDate = null WHERE ID = ?");
            $s = $statement->execute(array($status, $this->taskID));
        }
        if ($s > 0) {
            if ($this->markEdited($db, $userID) > 0)
                return true;
        }
        return false;

    }


}