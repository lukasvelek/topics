<?php

namespace App\Core;

use App\Exceptions\AException;
use App\Exceptions\DatabaseConnectionException;
use App\Logger\Logger;
use Exception;
use mysqli_sql_exception;
use QueryBuilder\IDbQueriable;

/**
 * Class that defines the database connectoin
 * 
 * @author Lukas Velek
 */
class DatabaseConnection implements IDbQueriable {
    private \mysqli $conn;
    private array $cfg;

    /**
     * Class constructor
     * 
     * @param array $cfg Configuration array
     */
    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        try {
            $dbPort = (empty($this->cfg['DB_PORT']) ? null : $this->cfg['DB_PORT']);
            $this->establishConnection($this->cfg['DB_SERVER'], $this->cfg['DB_USER'], CryptManager::decrypt($this->cfg['DB_PASS']), $this->cfg['DB_NAME'], $dbPort);
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Processes SQL query and returns the result
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return mixed Query result
     */
    public function query(string $sql, array $params = []) {
        return $this->conn->query($sql);
    }

    /**
     * Begins a transaction
     * 
     * @return bool True on success or false on failure
     */
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }

    /**
     * Rolls back a transaction
     * 
     * @return bool True on success or false on failure
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Commits a transaction
     * 
     * @return bool True on success or false on failure
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Establishes connection to the database server
     * 
     * @param string $dbServer Database server address
     * @param string $dbUser Database server user's username
     * @param string $dbPass Database server user's password
     * @param string $dbName Database name
     * @param string|null $dbPort Database server port
     */
    private function establishConnection(string $dbServer, string $dbUser, string $dbPass, string $dbName, ?string $dbPort = null) {
        try {
            $this->conn = new \mysqli($dbServer, $dbUser, $dbPass, $dbName, $dbPort);
        } catch (Exception $e) {
            throw new DatabaseConnectionException($e->getMessage());
        } catch (mysqli_sql_exception $e) {
            throw new DatabaseConnectionException($e->getMessage());
        }
    }

    /**
     * Installs the database - creates tables and default values
     */
    public function installDb() {
        $installer = new DatabaseInstaller($this, new Logger($this->cfg));
        $installer->install();
    }
}

?>