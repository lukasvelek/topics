<?php

namespace App\Entities;

class UserEntity implements ICreatableFromRow {
    private int $id;
    private string $username;
    private ?string $email;
    private string $dateCreated;
    private bool $isAdmin;

    public function __construct(int $id, string $username, ?string $email, string $dateCreated, bool $isAdmin) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->dateCreated = $dateCreated;
        $this->isAdmin = $isAdmin;
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

    public function isAdmin() {
        return $this->isAdmin;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['userId'], $row['username'], $row['email'], $row['dateCreated'], $row['isAdmin']);;
    }
}

?>