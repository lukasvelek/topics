<?php

namespace App\Authenticators;

use App\Core\HashManager;
use App\Entities\UserEntity;
use App\Repositories\UserRepository;

class UserAuthenticator {
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function loginUser(string $username, string $password) {
        $rows = $this->userRepository->getUserForAuthentication($username);

        $user = null;

        while($row = $rows->fetchAssoc()) {
            if(password_verify($password, $row['password'])) {
                $user = UserEntity::createEntity($row);

                break;
            }
        }

        if($user !== null) {
            $_SESSION['userId'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();

            $hash = HashManager::createHash(64);

            $this->userRepository->saveLoginHash($user->getId(), $hash);

            $_SESSION['loginHash'] = $hash;

            return true;
        } else {
            return false;
        }
    }

    public function authUser(string $password) {
        $rows = $this->userRepository->getUserForAuthentication($_SESSION['username']);

        $result = false;
        while($row = $rows->fetchAssoc()) {
            if(password_verify($password, $row['password'])) {
                $result = true;
            }
        }

        return $result;
    }

    public function fastAuthUser() {
        if(isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['loginHash'])) {
            $dbLoginHash = $this->userRepository->getLoginHashForUserId($_SESSION['userId']);

            if($dbLoginHash != $_SESSION['loginHash']) {
                // mismatch
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}

?>