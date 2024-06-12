<?php

namespace App\Authenticators;

use App\Core\HashManager;
use App\Entities\UserEntity;
use App\Exceptions\BadCredentialsException;
use App\Logger\Logger;
use App\Repositories\UserRepository;

class UserAuthenticator {
    private UserRepository $userRepository;
    private Logger $logger;

    public function __construct(UserRepository $userRepository, Logger $logger) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function loginUser(string $username, string $password) {
        $rows = $this->userRepository->getUserForAuthentication($username);

        $user = null;

        while($row = $rows->fetchAssoc()) {
            if(password_verify($password, $row['password'])) {
                $user = UserEntity::createEntityFromDbRow($row);

                break;
            }
        }

        if($user !== null) {
            $_SESSION['userId'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();

            $hash = HashManager::createHash(64);

            $this->userRepository->saveLoginHash($user->getId(), $hash);

            $_SESSION['loginHash'] = $hash;

            if(isset($_SESSION['is_logging_in'])) {
                unset($_SESSION['is_logging_in']);
            }

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
                $this->logger->warning('Authenticated user with username \'' . $_SESSION['username'] . '\'.', __METHOD__);
                $result = true;
            }
        }

        if($result === false) {
            throw new BadCredentialsException(null, $_SESSION['username']);
        }

        return $result;
    }

    public function fastAuthUser() {
        if(isset($_SESSION['userId']) && isset($_SESSION['username']) && isset($_SESSION['loginHash'])) {
            $dbLoginHash = $this->userRepository->getLoginHashForUserId($_SESSION['userId']);

            if($dbLoginHash != $_SESSION['loginHash']) {
                // mismatch
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}

?>