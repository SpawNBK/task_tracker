<?php
require_once 'User.php';

/**
 * Class Users
 */
class Users {

    /**
     * @var string
     * table name for users
     */
    protected $table_name = "api_users";

    /**
     * @param PDO $db
     * @return array|array[]|false
     * get all users from database
     */
    public function getAll($db) {
        $statement = $db->prepare("SELECT * FROM $this->table_name");
        $statement->execute();
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            $userlist = [];
            foreach($rows as $row)
            {
                $user = new User($row['username'], $row['email'], $row['PERMISSION_LEVEL'], intval($row['ID']));
                $userlist[] = $user->getUser();
            }
            return $userlist;
        }
        return false;
    }

    /**
     * @param PDO $db
     * @param string $username
     * @param string $email
     * @param int $permission
     * @param string $password
     * @return array[]|false
     * Create new user
     */
    public function create($db, $username, $email, $permission, $password) {
        $user = new User($username, $email, $permission);
        $user->setPassword($password);
        if ($user->addUser($db) > 0) {
            return $user->getUser();
        }
        return false;
    }

    /**
     * @param PDO $db
     * @param string $username
     * @param int $id
     * @return bool
     * Check if user exists
     */
    public function userExists($db, $username, $id = 0) {
        $statement = $db->prepare("SELECT * FROM $this->table_name WHERE username = ? LIMIT 1");
        $statement->execute(array($username));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            if (intval($rows[0]["ID"]) == $id)
                return false;
            else
                return true;
        }
        return false;
    }

    /**
     * @param PDO $db
     * @param int $id
     * @param boolean $userClass
     * @return array[]|false|User
     * Get user by ID
     */
    public function getByID($db, $id, $userClass = false)
    {
        $statement = $db->prepare("SELECT * FROM $this->table_name WHERE ID = ? LIMIT 1");
        $statement->execute(array($id));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            $user = new User($rows[0]['username'], $rows[0]['email'], $rows[0]['PERMISSION_LEVEL'], intval($rows[0]['ID']));
            if ($userClass)
                return $user;
            else
                return $user->getUser();
        }
        return false;
    }

    /**
     * @param PDO $db
     * @param string $hash
     * @return false|User
     * get user by access token
     */
    public function getByHash($db, $hash)
    {
        $statement = $db->prepare("SELECT * FROM $this->table_name WHERE user_hash = ? LIMIT 1");
        $statement->execute(array($hash));
        $rows = $statement->fetchAll();
        if ($statement->rowCount() > 0) {
            $user = new User($rows[0]['username'], $rows[0]['email'], $rows[0]['PERMISSION_LEVEL'], $rows[0]['ID']);
            return $user;
        }
        return false;

    }

    /**
     * @param PDO $db
     * @param int $id
     * @return bool
     * delete user by ID
     */
    public function deleteByID($db, $id) {
        $statement = $db->prepare("DELETE FROM $this->table_name WHERE ID = ?");
        return ($statement->execute(array($id)) > 0);
    }

    /**
     * @param PDO $db
     * @param int $id
     * @param string $password
     * @param string $email
     * @param int $permission
     * @return array[]|false
     * update user data
     */
    public function updateUser($db, $id, $password, $email, $permission = 0) {
        $user = $this->getByID($db, $id, true);
        $user->email = $email;
        $user->PERMISSION_LEVEL = $permission;
        $user->setPassword($password);
        if ($user->editUser($db)) {
            return $user->getUser();
        }
        return false;

    }



}