<?php

namespace App\Core;

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
        
        FileManager::saveFile($cfg['APP_REAL_DIR'] . 'app\\core\\', 'install', 'installed - ' . date('Y-m-d H:i:s'));
    }
}

?>