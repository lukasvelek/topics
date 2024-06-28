<?php

namespace App\Core;

use App\Core\Datetypes\DateTime;
use App\Exceptions\DatabaseConnectionException;
use App\Logger\Logger;
use Exception;
use mysqli_sql_exception;
use QueryBuilder\IDbQueriable;

class DatabaseConnection implements IDbQueriable {
    private \mysqli $conn;
    private array $cfg;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;
        $this->establishConnection($this->cfg['DB_SERVER'], $this->cfg['DB_USER'], $this->cfg['DB_PASS'], $this->cfg['DB_NAME']);

        if(!FileManager::fileExists($this->cfg['APP_REAL_DIR'] . "app\\core\\install")) {
            $this->installDb($this->cfg);
        }
    }

    public function query(string $sql, array $params = []) {
        return $this->conn->query($sql);
    }

    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }

    public function rollback() {
        return $this->conn->rollback();
    }

    public function commit() {
        return $this->conn->commit();
    }

    private function establishConnection(string $dbServer, string $dbUser, string $dbPass, string $dbName) {
        try {
            $this->conn = new \mysqli($dbServer, $dbUser, $dbPass, $dbName);
        } catch (Exception $e) {
            throw new DatabaseConnectionException($e->getMessage());
        } catch (mysqli_sql_exception $e) {
            throw new DatabaseConnectionException($e->getMessage());
        }
    }

    private function installDb(array $cfg) {
        $installer = new DatabaseInstaller($this, new Logger($cfg));

        $installer->install();
        
        $date = new DateTime();
        
        FileManager::saveFile($cfg['APP_REAL_DIR'] . 'app\\core\\', 'install', 'installed - ' . $date);
    }
}

?>