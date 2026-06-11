<?php

require_once __DIR__."/../../Database.php";

/**
 * Base Repository class.
 * 
 * Provides the foundational database connection via PDO for all child repositories.
 * 
 * @package Repositories
 */
class Repository {
    protected $database;

    /**
     * Initializes the repository and sets up the database connection.
     */
    public function __construct() {
        $this->database = new Database();
    }
}
