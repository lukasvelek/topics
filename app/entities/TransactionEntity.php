<?php

namespace App\Entities;

class TransactionEntity extends AEntity {
    private string $id;
    private ?string $userId;
    private string $method;
    private string $dateCreated;

    public function __construct(string $id, ?string $userId, string $method, string $dateCreated) {
        $this->id = $id;
        $this->userId = $userId;
        $this->method = $method;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['transactionId' => 'string', 'userId' => '?string', 'methodName' => 'string', 'dateCreated' => 'string']);

        return new self($row->transactionId, $row->userId, $row->methodName, $row->dateCreated);
    }
}

?>