<?php

namespace App\Core;

class Model {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get the PDO database connection.
     * 
     * @return \PDO
     */
    public function getDb() {
        return $this->db;
    }
}
