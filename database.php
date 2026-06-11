<?php
// .env 
require_once "config.php";

// singleton 
/**
 * Core Database Connection Manager.
 * 
 * Provides a centralized wrapper for establishing and managing 
 * the PDO database connection using environment credentials.
 * 
 * @package Core
 */
class Database {
    private string $username;
    private string $password;
    private string $host;
    private string $database;
    private ?PDO $conn = null;

    public function __construct()
    {
        $this->username = USERNAME;
        $this->password = PASSWORD;
        $this->host = HOST;
        $this->database = DATABASE;
    }

    /**
     * Establishes or returns an existing PDO database connection.
     *
     * @return PDO The active PDO connection.
     */
    public function connect(): PDO
    {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "pgsql:host=$this->host;port=5432;dbname=$this->database",
                    $this->username,
                    $this->password,
                    ["sslmode"  => "prefer"]
                );

                // set the PDO error mode to exception
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch(PDOException $e) {
                // change to error page e.g. 404 not found etc.
                die("Connection failed: " . $e->getMessage());
            }
        }
        return $this->conn;
    }

    /**
     * Explicitly closes the database connection.
     * 
     * Uses unset to completely remove the reference, signaling the 
     * garbage collector to close the underlying PostgreSQL connection.
     * 
     * @return void
     */
    public function disconnect(): void 
    {
        if ($this->conn !== null) {
            $this->conn = null;
        }
    }

    /**
     * Destructor to ensure the connection is closed when the Database object is destroyed.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}