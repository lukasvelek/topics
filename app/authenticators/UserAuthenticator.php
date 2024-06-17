<?php

namespace App\Authenticators;

use App\Constants\UserProsecutionType;
use App\Core\HashManager;
use App\Entities\UserEntity;
use App\Exceptions\BadCredentialsException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Logger\Logger;
use App\Repositories\UserProsecutionRepository;
use App\Repositories\UserRepository;

class UserAuthenticator {
    private UserRepository $userRepository;
    private Logger $logger;
    private UserProsecutionRepository $userProsecutionRepository;

    public function __construct(UserRepository $userRepository, Logger $logger, UserProsecutionRepository $userProsecutionRepository) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->userProsecutionRepository = $userProsecutionRepository;
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

        if($user === null) {
            throw new GeneralException('You have entered bad credentials.');
        }

        $lastProsecution = $this->userProsecutionRepository->getLastProsecutionForUserId($user->getId());

        if($lastProsecution !== null) {
            if($lastProsecution->getType() == UserProsecutionType::PERMA_BAN ||
                ($lastProsecution->getType() == UserProsecutionType::BAN && strtotime($lastProsecution->getEndDate()) > time())) {
                if($lastProsecution->getEndDate() !== null) {
                    throw new GeneralException('You have been banned for "' . $lastProsecution->getReason() . '" until ' . DateTimeFormatHelper::formatDateToUserFriendly($lastProsecution->getEndDate()) . '.');
                } else {
                    throw new GeneralException('You have been banned for "' . $lastProsecution->getReason() . '".');
                }
            }
        }

        $_SESSION['userId'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();

        $hash = HashManager::createHash(64);

        $this->userRepository->saveLoginHash($user->getId(), $hash);

        $_SESSION['loginHash'] = $hash;

        if(isset($_SESSION['is_logging_in'])) {
            unset($_SESSION['is_logging_in']);
        }

        return true;
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

    public function fastAuthUser(string &$message) {
        if(isset($_SESSION['userId']) && isset($_SESSION['username']) && isset($_SESSION['loginHash'])) {
            $dbLoginHash = $this->userRepository->getLoginHashForUserId($_SESSION['userId']);

            $lastProsecution = $this->userProsecutionRepository->getLastProsecutionForUserId($_SESSION['userId']);

            if($lastProsecution->getType() == UserProsecutionType::PERMA_BAN || 
                ($lastProsecution->getType() == UserProsecutionType::BAN && strtotime($lastProsecution->getEndDate()) > time())) {
                    $message = 'You have been banned.';
                return false;
            }

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