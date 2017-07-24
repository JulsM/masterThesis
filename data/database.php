<?php

class Db
{

    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $config = parse_ini_file("config.ini");
        try {
            $this->connection = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['username'], $config['password']);
            // echo 'db connected';
        } catch (PDOException $ex) {
            echo "Error: " . $ex->getMessage();
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Db();
        }

        return self::$instance;
    }

    public function getConnection()
    {

        return $this->connection;
    }

    // public function connect()
    // {
    //     if (!isset(self::$connection)) {

    //         $config = parse_ini_file("config.ini");
    //         try {
    //             self::$connection = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['username'], $config['password']);
    //         } catch (PDOException $ex) {
    //             echo "Error: " . $ex->getMessage();
    //         }
    //     }
    //     return self::$connection;
    // }

    public function query($sql)
    {
        $result = $this->connection->query($sql);

        return $result->fetchAll();
    }

    public function saveAthlete($data)
    {
        $statement = $this->connection->prepare("INSERT INTO users (strava_id, email, name, token) VALUES (?, ?, ?, ?)");
        $statement->execute(array($data['stravaId'], $data['mail'], $data['name'], $data['token']));
    }

}
