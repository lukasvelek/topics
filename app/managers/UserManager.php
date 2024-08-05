<?php

namespace App\Managers;

use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\UserRepository;

class UserManager extends AManager {
    private UserRepository $userRepository;
    private MailManager $mailManager;

    public function __construct(Logger $logger, UserRepository $userRepository, MailManager $mailManager) {
        parent::__construct($logger);

        $this->userRepository = $userRepository;
        $this->mailManager = $mailManager;
    }

    public function getUserById(int $userId) {
        return $this->userRepository->getUserById($userId);
    }

    public function createNewForgottenPassword(int $userId) {
        $requestId = $this->createNewForgottenPasswordRequestId();

        // disable user
        $this->disableUser($userId, $userId);
        
        // create new forgotten password entry
        $dateExpire = new DateTime();
        $dateExpire->modify('+1d');
        $dateExpire = $dateExpire->getResult();
        
        if(!$this->userRepository->insertNewForgottenPasswordEntry($requestId, $userId, $dateExpire)) {
            throw new GeneralException('Could not insert new forgotten password entry.');
        }
        
        // send email
        $user = $this->getUserById($userId);

        $link = '<a href="' . $this->mailManager->cfg['APP_URL_BASE'] . '?page=AnonymModule:ForgottenPassword&action=form&requestId=' . $requestId . '">here</a>';

        if(!$this->mailManager->createNewForgottenPassword($user, $link)) {
            throw new GeneralException('Could not create an email notification.');
        }
    }

    public function disableUser(int $userId, int $callingUserId) {
        if(!$this->userRepository->updateUser($userId, ['canLogin' => '0'])) {
            throw new GeneralException('Could not disable user #' . $userId . '.');
        }

        $this->logger->warning(sprintf('User #%d disabled user #%d.', $callingUserId, $userId), __METHOD__);
    }

    public function enableUser(int $userId, int $callingUserId) {
        if(!$this->userRepository->updateUser($userId, ['canLogin' => '1'])) {
            throw new GeneralException('Could not enable user #' . $userId . '.');
        }

        $this->logger->warning(sprintf('User #%d enabled user #%d.', $callingUserId, $userId), __METHOD__);
    }

    private function createNewForgottenPasswordRequestId() {
        return HashManager::createHash(32, false);
    }
}

?>