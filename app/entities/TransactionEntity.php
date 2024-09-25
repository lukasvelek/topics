<?php

namespace App\Entities;

class TransactionEntity extends AEntity {
    private string $transactionId;
    private ?string $userId;
    private string $methodName;
    private string $dateCreated;

    public function __construct(string $transactionId, ?string $userId, string $methodName, string $dateCreated) {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->methodName = $methodName;
        $this->dateCreated = $dateCreated;
    }

    public function getTransactionId() {
        return $this->transactionId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getMethodName() {
        return $this->methodName;
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