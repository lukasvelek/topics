<?php

namespace App\Entities;

class UserEntity implements ICreatableFromRow {
    private int $id;
    private string $username;
    private ?string $email;
    private string $dateCreated;

    public function __construct(int $id, string $username, ?string $email, string $dateCreated) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['userId'], $row['username'], $row['email'], $row['dateCreated']);
    }
}

?>