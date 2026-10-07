<?php

namespace Framework;

use PDO;
use PDOException;
use Exception;

class Database
{
    public $conn;

    /**
     * Constructor for database class with connection to db
     *
     * @param array $config
     * @return void
     */
    public function __construct($config)
    {

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];

        try {
            $this->conn = new PDO($dsn, $config['username'], $config['password'], $options);
            // echo 'connect to database';
        } catch (PDOException $e) {
            throw new Exception("Could not connect to database: " . $e->getMessage());
        }
    }

    public function query($query, $params = [])
    {

        try {
            $sth = $this->conn->prepare($query);

            // Bind named params
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    // Detect if the value is an integer and bind accordingly
                    if (is_int($value)) {
                        $sth->bindValue(':' . $key, $value, PDO::PARAM_INT);
                    } else {
                        $sth->bindValue(':' . $key, $value);
                    }
                }
            }
            $sth->execute();
            return $sth;
        } catch (PDOException $e) {
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
}
