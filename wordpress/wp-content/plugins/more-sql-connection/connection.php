<?php
require_once 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;

class Connection
{

    public $id;
    public $name;
    public $drive;
    public $host;
    public $port;
    public $db_username;
    public $db_password;
    public $db_name;
    public $status;


    public function __construct($id = 0, $name = '', $drive = '', $host = '', $port = '', $db_username = '', $db_password = '', $db_name = '', $status = 0)
    {
        $this->id = $id;
        $this->name = $name;
        $this->drive = $drive;
        $this->host = $host;
        $this->port = $port;
        $this->db_username = $db_username;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
        $this->status = $status;
    }

    /**
     * Get connection class PDO
     *
     * @return PDO
     **/
    public function init_connection()
    {
        $conn = new PDO("$this->drive:host=$this->host;port=$this->port;dbname=$this->db_name", $this->db_username, $this->db_password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }

    /**
     * Get query builder
     *
     * @return QueryBuilder
     **/
    public function get_query_builder()
    {
        $connectionParams = [
            'dbname' => $this->db_name,
            'user' => $this->db_username,
            'password' => $this->db_password,
            'host' => $this->host,
            'port' => $this->port,
            'driver' => $this->drive,
        ];

        $conn = DriverManager::getConnection($connectionParams);
        return $conn->createQueryBuilder()->disableResultCache();

    }

    public function get_connection()
    {
        $connectionParams = [
            'dbname' => $this->db_name,
            'user' => $this->db_username,
            'password' => $this->db_password,
            'host' => $this->host,
            'port' => $this->port,
            'driver' => $this->drive,
        ];

        $conn = DriverManager::getConnection($connectionParams);
        return $conn;

    }
}