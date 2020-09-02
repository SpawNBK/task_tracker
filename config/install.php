<?php

require_once ('Db.php');

/**
 * Class Installator
 * create tables and admin user
 */
class Installator {
    /**
     * @var string
     * sql table name for users
     */
    public $usersTable = "api_users";
    /**
     * @var string
     * sql table name for tasks
     */
    public $tasksTable = "api_tasks";
    /**
     * @var string
     * sql table name for task modify info
     */
    public $modifyTable = "api_taskModify";
    /**
     * @var string
     * sql table name for users who can access to task
     */
    public $workersTable = "api_taskWorker";

    /**
     * @return void|\http\Exception
     * run install db script
     */
    public function run()
    {
        try {
            $db = new Database();
            $db->createDatabase();
            $db = $db->getConnection();
            if ($this->isInstalled($db)) {
                header('HTTP/1.0 404 not found');
                return json_encode(array('error' =>
                    ["message"=>"Bad request", "code" => 404]));
            }
            else
                $this->createTables($db);
                $this->createAdmin($db);
                return json_encode(array('success' => true));
        } catch (Exception $e) {
            return json_encode(array('error' =>
                ["message"=>$e->getMessage(), "code" => $e->getCode()]));

        }
    }

    /**
     * @param PDO $db
     * @return void
     * create sql tables
     */
    public function createTables($db) {
        $queryCreateUsersTable = "CREATE TABLE IF NOT EXISTS `$this->usersTable` (
            `ID` int(11) unsigned NOT NULL auto_increment,
            `username` varchar(255) NOT NULL default '',
            `email` varchar(255) NOT NULL default '',
            `password` varchar(255) NOT NULL default '',
            `PERMISSION_LEVEL` tinyint(1) unsigned NOT NULL default '1',
            `user_hash` varchar(255) NULL,
            PRIMARY KEY  (`ID`)
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $queryCreateTasksTable = "CREATE TABLE IF NOT EXISTS `$this->tasksTable` (
            `ID` int(11) unsigned NOT NULL auto_increment,
            `task_author` int(11) unsigned NOT NULL default 0,
            `task_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `task_title` varchar(255) NULL default null,
            `task_content` LONGTEXT NULL,
            `task_endDate` TIMESTAMP NULL DEFAULT NULL,
            `task_finishedDate` TIMESTAMP NULL DEFAULT NULL,
            `task_status` varchar(8) DEFAULT 'working',    
            PRIMARY KEY  (`ID`)
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $queryCreateModifiedTable = "CREATE TABLE IF NOT EXISTS `$this->modifyTable` (
            `modify_ID` int(11) unsigned NOT NULL auto_increment,
            `mTask_ID` int(11) unsigned NOT NULL,
            `mUser_ID` int(11) unsigned NOT NULL default 0,
            `modify_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,  
            PRIMARY KEY  (`modify_ID`),
            INDEX `mTask_ID` (`mTask_ID`),
            CONSTRAINT `FK_api_task` FOREIGN KEY (`mTask_ID`) 
                REFERENCES `api_tasks` (`ID`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $queryCreateWorkersTable = "CREATE TABLE IF NOT EXISTS `$this->workersTable` (
            `worker_ID` int(11) unsigned NOT NULL auto_increment,
            `wTask_ID` int(11) unsigned NOT NULL,
            `wUser_ID` int(11) unsigned NOT NULL default 0,  
            PRIMARY KEY  (`worker_ID`),
            INDEX `wTask_ID` (`wTask_ID`),
            CONSTRAINT `FK_api_task2` FOREIGN KEY (`wTask_ID`) 
                REFERENCES `api_tasks` (`ID`) ON DELETE CASCADE
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        if(!$db->query($queryCreateUsersTable)){
            echo json_encode(array('error' =>
                $db->errorInfo()));
        }
        if(!$db->query($queryCreateTasksTable)){
            echo json_encode(array('error' =>
                $db->errorInfo()));
        }
        if(!$db->query($queryCreateModifiedTable)){
            echo json_encode(array('error' =>
                $db->errorInfo()));
        }
        if(!$db->query($queryCreateWorkersTable)){
            echo json_encode(array('error' =>
                $db->errorInfo()));
        }
    }

    /**
     * @param PDO $db
     * @return boolean
     * create admin user
     */
    public function createAdmin($db) {
        $query = $db->prepare("INSERT INTO $this->usersTable(username, email, password, PERMISSION_LEVEL)
        VALUES(?,?,?,?)");
        return ($query->execute(array("admin", "admin@admin.api.ru", md5(md5("admin")), 1)) > 0);
    }

    /**
     * @param PDO $db
     * @return bool
     * Check if tables already installed
     */
    public function isInstalled($db) {
        $exists = $db->prepare("SHOW TABLES FROM `$db->dbName` LIKE '$this->usersTable'");
        $exists->execute();
        $res = $exists->fetchAll();
        return ( $exists->rowCount() > 0);

    }


}