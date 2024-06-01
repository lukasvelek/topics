<?php

namespace App\Entities;

class UserEntity {
    private int $id;
    private string $username;
    private string $email;

    public function __construct(int $id, string $username, string $email) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
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

    public static function createEntity(mixed $row) {
        return new self($row['id'], $row['username'], $row['email']);
    }
}

?>