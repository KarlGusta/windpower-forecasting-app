<?php
namespace Config;
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $config = require 'config.php';
        $db = $config['database'];

        $this->connection = new \mysqli(
            $db['host'],
            $db['user'],
            $db['pass'],
            $db['name']
        );

        if ($this->connection->connect_error) {
            throw new \Exception("Connection failed: " . $this->connection->connect_error);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}