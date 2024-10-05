<?php

namespace App\Managers;

use App\Core\Datetypes\DateTime;
use App\Exceptions\EntityUpdateException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class UserManager extends AManager {
    private UserRepository $userRepository;
    private MailManager $mailManager;
    private GroupRepository $groupRepository;

    public function __construct(
        Logger $logger,
        UserRepository $userRepository,
        MailManager $mailManager,
        GroupRepository $groupRepository,
        EntityManager $entityManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->userRepository = $userRepository;
        $this->mailManager = $mailManager;
        $this->groupRepository = $groupRepository;
    }

    public function getUserByUsername(string $username) {
        $user = $this->userRepository->getUserByUsername($username);

        if($user === null) {
            throw new NonExistingEntityException('User with username \'' . $username . '\' does not exist.');
        }

        return $user;
    }

    public function getUserById(string $userId) {
        $user = $this->userRepository->getUserById($userId);

        if($user === null) {
            throw new NonExistingEntityException('User with ID \'' . $userId . '\' does not exist.');
        }

        return $user;
    }

    public function createNewForgottenPassword(string $userId) {
        $linkId = $this->createNewForgottenPasswordRequestId();

        // disable user
        $this->disableUser($userId, $userId);
        
        // create new forgotten password entry
        $dateExpire = new DateTime();
        $dateExpire->modify('+1d');
        $dateExpire = $dateExpire->getResult();
        
        if(!$this->userRepository->insertNewForgottenPasswordEntry($linkId, $userId, $dateExpire)) {
            throw new GeneralException('Could not insert new forgotten password entry.');
        }
        
        // send email
        $user = $this->getUserById($userId);

        $link = '<a href="' . $this->mailManager->cfg['APP_URL_BASE'] . '?page=AnonymModule:ForgottenPassword&action=changePasswordForm&linkId=' . $linkId . '">here</a>';

        if(!$this->mailManager->createNewForgottenPassword($user, $link)) {
            throw new GeneralException('Could not create an email notification.');
        }
    }

    public function disableUser(string $userId, string $callingUserId) {
        if(!$this->userRepository->updateUser($userId, ['canLogin' => '0'])) {
            throw new GeneralException('Could not disable user #' . $userId . '.');
        }

        $this->logger->warning(sprintf('User #%d disabled user #%d.', $callingUserId, $userId), __METHOD__);
    }

    public function enableUser(string $userId, string $callingUserId) {
        if(!$this->userRepository->updateUser($userId, ['canLogin' => '1'])) {
            throw new GeneralException('Could not enable user #' . $userId . '.');
        }

        $this->logger->warning(sprintf('User #%d enabled user #%d.', $callingUserId, $userId), __METHOD__);
    }

    private function createNewForgottenPasswordRequestId() {
        return $this->createId(EntityManager::FORGOTTEN_PASSWORD);
    }

    public function checkForgottenPasswordRequest(string $linkId) {
        $row = $this->userRepository->getForgottenPasswordRequestById($linkId);

        if($row['isActive'] == 0) {
            return false;
        }

        if(strtotime($row['dateExpire']) < time()) {
            return false;
        }

        return true;
    }

    public function processForgottenPasswordRequestPasswordChange(string $linkId, string $hashedPassword) {
        // update password
        // activate user
        
        $request = $this->userRepository->getForgottenPasswordRequestById($linkId);
        $userId = $request['userId'];
        
        $data = [
            'password' => $hashedPassword,
            'canLogin' => '1'
        ];
        if(!$this->userRepository->updateUser($userId, $data)) {
            throw new EntityUpdateException('Could not update user #' . $userId . ' with data [' . implode(', ', $data) . '].');
        }
        
        // deactive link
        $rdata = [
            'isActive' => '0'
        ];
        if(!$this->userRepository->updateRequest($linkId, $rdata)) {
            throw new EntityUpdateException('Could not update request #' . $linkId . ' with data [' . implode(',', $rdata) . '].');
        }
    }
}

?>