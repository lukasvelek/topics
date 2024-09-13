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

/**
 * UserAuthenticator allows to authenticate a user
 * 
 * @author Lukas Velek
 */
class UserAuthenticator {
    private UserRepository $userRepository;
    private Logger $logger;
    private UserProsecutionRepository $userProsecutionRepository;

    /**
     * Class constructor
     * 
     * @param UserRepository $userRepository UserRepository instance
     * @param Logger $logger Logger instance
     * @param UserProsecutionRepository $userProsecutionRepository UserProsecutionRepository instance
     */
    public function __construct(UserRepository $userRepository, Logger $logger, UserProsecutionRepository $userProsecutionRepository) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->userProsecutionRepository = $userProsecutionRepository;
    }

    /**
     * Tries to login user with information provided. It checks for bad credentials, disabled account and banned account.
     * 
     * @param string $username Username of the user, who's trying to log in
     * @param string $password Password of the user, who's trying to log in
     * @return true
     * @throws GeneralException
     */
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

        if(!$user->canLogin()) {
            throw new GeneralException('Your account is disabled. Maybe you have request resetting your password or haven\'t confirmed your registration?');
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

        $hashSaveResult = $this->userRepository->saveLoginHash($user->getId(), $hash);
        if($hashSaveResult === false) {
            throw new GeneralException('Could not save the generated hash.');
        }

        $_SESSION['loginHash'] = $hash;

        if(isset($_SESSION['is_logging_in'])) {
            unset($_SESSION['is_logging_in']);
        }

        return true;
    }

    /**
     * Authenticates current user - checks if the password entered matches the one user has saved in the database.
     * 
     * @param string $password User's password
     * @return bool True if authentication is successful or false if not
     * @throws BadCredentialsException
     */
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

    /**
     * Checks if all the necessary information about the user is saved in the session.
     * Checks if login hash in session matches the on saved in the database.
     * Checks if user is not banned or permanently banned.
     * 
     * @param string &$message Message returned
     * @return bool True if successful or false if not
     */
    public function fastAuthUser(string &$message) {
        if(isset($_SESSION['userId']) && isset($_SESSION['username']) && isset($_SESSION['loginHash'])) {
            $dbLoginHash = $this->userRepository->getLoginHashForUserId($_SESSION['userId']);

            $lastProsecution = $this->userProsecutionRepository->getLastProsecutionForUserId($_SESSION['userId']);

            if($lastProsecution !== null) {
                if($lastProsecution->getType() == UserProsecutionType::PERMA_BAN || 
                    ($lastProsecution->getType() == UserProsecutionType::BAN && strtotime($lastProsecution->getEndDate()) > time())) {
                        $message = 'You have been banned.';
                    return false;
                }
            }

            if($dbLoginHash != $_SESSION['loginHash']) {
                // mismatch
                $message = 'Hash in this browser does not match hash on the server.';
                return false;
            } else {
                return true;
            }
        } else {
            $message = 'Incorrectly saved session information.';
            return false;
        }
    }

    /**
     * Checks if user with passed username exists or not
     * 
     * @param string $username Username to be checked
     * @return bool True if successful or false if not
     */
    public function checkUser(string $username) {
        if($this->userRepository->getUserForAuthentication($username)->fetch() !== null) {
            return false;
        }

        return true;
    }

    /**
     * Checks if user with passed email exists or not
     * 
     * @param string $email Email to be checked
     * @return bool True if successful or false if not
     */
    public function checkUserByEmail(string $email) {
        if($this->userRepository->getUserByEmailForAuthentication($email)->fetch() !== null) {
            return false;
        }

        return true;
    }
}

?>