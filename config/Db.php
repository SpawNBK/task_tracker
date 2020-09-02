<?php

/**
 * Class Database
 * create connection to database
 */
class Database
{

    /**
     * @var string
     * database host
     */
    private $host = "localhost";
    /**
     * @var string
     * database name
     */
    private $db_name = "apiDB";
    /**
     * @var string
     * database username
     */
    private $username = "";
    /**
     * @var string
     * database password
     */
    private $password = "";
    /**
     * @var PDO
     * keep PDO connection
     */
    public $conn;

    /**
     * @return void
     * Create new database
     */
    public function createDatabase() {
        try {
            $dbh = new PDO("mysql:host=$this->host", $this->username, $this->password);
            $dbh->exec("CREATE DATABASE `$this->db_name` CHARACTER SET utf8 COLLATE utf8_general_ci;")
            or die(json_encode(array(
                'success' => false,
                'error' =>
                ["message"=>$dbh->errorInfo(), "code" => 500])));
        } catch (PDOException $e) {
            die(json_encode(array(
                'success' => false,
                'error' =>
                    ["message"=>$e->getMessage(), "code" => 500])));
        }
    }
    /**
     * @return PDO|null
     * @exception PDOException
     * function connect to database and return connection or null
     */
    public function getConnection()
    {

        $this->conn = null;

        try {
            $this->conn = new PDOext("mysql:host=" . $this->host . ";dbname=" , $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8; SET CHARACTER SET utf8; SET SESSION collation_connection = utf8_general_ci;');
        } catch (PDOException $exception) {
            throw new RuntimeException($exception->getMessage(), 405);
        }

        return $this->conn;
    }
}

/**
 * Class PDOext
 * extends PDO class to keep $dbName variable
 * it need for SHOW TABLES query
 */
class PDOext extends PDO {
    /**
     * @var string
     * database name
     */
    public $dbName = "";

    public function __construct($o, $m, $t, $f)
    {
        parent::__construct($o . $m, $t, $f, array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES UTF8'));
        $this->dbName = $m;
    }

}

