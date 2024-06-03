<?php

namespace App\Core;

use App\Exceptions\DatabaseConnectionException;
use Exception;
use mysqli_sql_exception;
use QueryBuilder\IDbQueriable;

class DatabaseConnection implements IDbQueriable {
    private \mysqli $conn;

    public function __construct(string $dbServer, string $dbUser, string $dbPass, string $dbName) {
        $this->establishConnection($dbServer, $dbUser, $dbPass, $dbName);
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
}

?>