<?php
require_once __DIR__ . '/../vendor/autoload.php';

class Database {
    private $connection;
    private $database;

    public function __construct() {
        try {
            // Koneksi ke MongoDB
            $this->connection = new MongoDB\Client("mongodb://localhost:27017");
            $this->database = $this->connection->sparepart_db;
        } catch (Exception $e) {
            die("Error koneksi ke database: " . $e->getMessage());
        }
    }

    public function getDatabase() {
        return $this->database;
    }
}
?> 