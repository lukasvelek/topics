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
    
    public function __construct(Logger $logger, UserRegistrationRepository $urr, UserRepository $ur, MailManager $mm, EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);

        $this->urr = $urr;
        $this->ur = $ur;
        $this->mm = $mm;
    }

    /**
     * password must be hashed already
     */
    public function registerUser(string $username, string $password, string $email) {
        $userId = $this->createId(EntityManager::USERS);

        if(!$this->ur->createNewUser($userId, $username, $password, $email, false)) {
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
        return $this->createId(EntityManager::USER_REGISTRATION);
    }

    public function confirmUserRegistration(string $registrationId) {
        $row = $this->urr->getRegistrationById($registrationId);

        $userId = $row['userId'];

        $dateExpire = $row['dateExpire'];

        if(strtotime($dateExpire) < time()) {
            throw new UserRegistrationException('This confirmation link has expired.');
        }

        if($row['isActive'] == '0') {
            throw new UserRegistrationException('This confirmation link has been used and is not active');
        }

        if(!$this->ur->updateUser($userId, ['canLogin' => '1'])) {
            throw new UserRegistrationException('User could not be updated.');
        }

        if(!$this->urr->deactivateRegistration($registrationId)) {
            throw new UserRegistrationException('Confirmation link could not be deactivated.');
        }
    }

    public function recreateNewUserRegistration(string $oldRegistrationId) {
        $row = $this->urr->getRegistrationById($oldRegistrationId);

        $registrationId = $this->createRegistrationId();

        $link = '<a href="' . $this->mm->cfg['APP_URL_BASE'] . '?page=AnonymModule:Register&action=confirm&registrationId=' . $registrationId  . '">here</a>';

        $dateExpire = new DateTime();
        $dateExpire->modify('+1d');
        $dateExpire = $dateExpire->getResult();

        if(!$this->urr->deactivateRegistration($oldRegistrationId)) {
            throw new UserRegistrationException('Old confirmation link could not be deactivated.');
        }
        
        if(!$this->urr->insertNewConfirmationEntry($registrationId, $row['userId'], $link, $dateExpire)) {
            throw new UserRegistrationException('Could not create new confirmation entry for user #' . $row['userId'] . '.');
        }
    }
}

?>