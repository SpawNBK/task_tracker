<?php

/**
 * Class User
 */
class User {

    /**
     * @var int
     * user id
     */
    public $userid;

    /**
     * @var string
     * username
     */
    public $username;

    /**
     * @var string
     * user password
     */
    public $password;

    /**
     * @var string
     * user email
     */

    public $email;

    /**
     * @var int
     * user permission level
     * 0 - simple user
     * 1 - admin
     */
    public $PERMISSION_LEVEL;

    /**
     * @var string|null
     * user access token
     */
    public $hash;

    /**
     * @var string
     * table name for users
     */
    protected $table_name = "api_users";

    /**
     * User constructor.
     * @param string $username
     * @param string $email
     * @param int $PERMISSION_LEVEL
     * @param int $userid
     */
    public function __construct($username, $email, $PERMISSION_LEVEL, $userid = 0){
        $this->username = $username;
        $this->email = $email;
        $this->PERMISSION_LEVEL = $PERMISSION_LEVEL;
        $this->userid = $userid;
    }

    /**
     * @param string $pwd
     * @return void
     * set user password in md5
     */
    public function setPassword($pwd) {
        $this->password = md5(md5(trim($pwd)));
    }

    /**
     * @param PDO $db
     * @param string $hash
     * @return bool
     * set access token to user
     */
    public function setHash($db, $hash) {
        $this->hash = $hash;
        $statement = $db->prepare("UPDATE $this->table_name SET user_hash = ? WHERE ID = ?");
        return ($statement->execute(array($this->hash, $this->userid)) > 0);
    }

    /**
     * @return string|null
     * get access token from user
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * @param PDO $db
     * add user to database
     * @return int $userid|0
     */
    public function addUser($db) {
        $statement = $db->prepare("INSERT INTO $this->table_name(username, email, password, PERMISSION_LEVEL)
        VALUES(?,?,?,?)");
        if ($statement->execute(array($this->username, $this->email, $this->password, $this->PERMISSION_LEVEL)) > 0) {
            $this->userid = $db->lastInsertId();
            return $this->userid;
        }
        return 0;
    }

    /**
     * @param PDO $db
     * @return bool
     * edit user in database
     */
    public function editUser($db) {
        $statement = $db->prepare("UPDATE $this->table_name SET password = ?, email = ?, PERMISSION_LEVEL = ? WHERE ID = ?");
        return ($statement->execute(array($this->password, $this->email, $this->PERMISSION_LEVEL, $this->userid)) > 0);
    }


    /**
     * @return array[]
     * return userinfo
     */
    public function getUser() {
        return array(
              'id' => intval($this->userid),
              'username' => $this->username,
              'email' => $this->email,
              'admin' => $this->PERMISSION_LEVEL > 0
        );
    }


}