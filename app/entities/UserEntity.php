<?php

namespace App\Entities;

use App\UI\LinkBuilder;

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

    public function getEmail(bool $anonymized = false) {
        if(!$anonymized) {
            return $this->email;
        } else {
            $left = explode('@', $this->email)[0];
            $right = explode('@', $this->email)[1];
            $rightLeft = explode('.', $right)[0];

            $result = '';

            for($i = 0; $i < strlen($left); $i++) {
                if($i < 3) {
                    $result .= $left[$i];
                }

                $result .= '*';
            }

            $result .= '@';

            for($i = 0; $i < strlen($rightLeft); $i++) {
                if($i < 1) {
                    $result .= $rightLeft[$i];
                }

                $result .= '*';
            }

            for($i = 0; $i < strlen($right); $i++) {
                if($i < strlen($rightLeft)) continue;

                $result .= $right[$i];
            }

            return $result;
        }
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function isAdmin() {
        return $this->isAdmin;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['userId'], $row['username'], $row['email'], $row['dateCreated'], $row['isAdmin']);
    }

    public static function createUserProfileLink(UserEntity $user, bool $object = false) {
        if($object) {
            return LinkBuilder::createSimpleLinkObject($user->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'post-data-link');
        } else {
            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'post-data-link');
        }
    }
}

?>