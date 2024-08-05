<?php

namespace App\Managers;

use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\UserRegistrationException;
use App\Logger\Logger;
use App\Repositories\UserRegistrationRepository;
use App\Repositories\UserRepository;

class UserRegistrationManager extends AManager {
    private UserRegistrationRepository $urr;
    private UserRepository $ur;
    private MailManager $mm;
    
    public function __construct(Logger $logger, UserRegistrationRepository $urr, UserRepository $ur, MailManager $mm) {
        parent::__construct($logger);

        $this->urr = $urr;
        $this->ur = $ur;
        $this->mm = $mm;
    }

    /**
     * password must be hashed already
     */
    public function registerUser(string $username, string $password, string $email) {
        if(!$this->ur->createNewUser($username, $password, $email, false)) {
            throw new UserRegistrationException('Could not create new user entry.');
        }

        $user = $this->ur->getUserByUsername($username);

        $registrationId = $this->createRegistrationId();

        $dateExpire = new DateTime();
        $dateExpire->modify('+1d');
        $dateExpire = $dateExpire->getResult();

        $link = '<a href="' . $this->mm->cfg['APP_URL_BASE'] . '?page=AnonymModule:Register&action=confirm&registrationId=' . $registrationId  . '">here</a>';

        if(!$this->urr->insertNewConfirmationEntry($registrationId, $user->getId(), $link, $dateExpire)) {
            throw new UserRegistrationException('Could not create new confirmation entry for user #' . $user->getId() . '.');
        }
        
        if(!$this->mm->createNewUserRegistration($user, $link)) {
            throw new UserRegistrationException('Could not send a registration confirmation link for user #' . $user->getId() . '.');
        }
    }

    private function createRegistrationId() {
        return HashManager::createHash(32, false);
    }
}

?>