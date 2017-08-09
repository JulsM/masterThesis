<?php

class Db
{

    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $this->connection = pg_connect("dbname=strava user=julian password=prediction");
            //echo 'db connected';
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
        $result = pg_query($this->connection, $sql);

        return pg_fetch_all($result);
    }

    public function saveAthlete($data)
    {
        $sucess = false;
        try {
            $statement = "INSERT INTO users (strava_id, name, email, token) VALUES ('".$data['stravaId']."', '".$data['name']."', '".$data['mail']."', '".$data['token']."')";
            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
        // print( self::query("select id from users where email = '".$data['mail']."'"));
    }

}
