<?php

namespace App\Managers;

use App\Constants\UserProsecutionType;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Logger\Logger;
use App\Repositories\UserProsecutionRepository;
use App\Repositories\UserRepository;

class UserProsecutionManager extends AManager {
    private UserProsecutionRepository $userProsecutionRepository;
    private UserRepository $userRepository;

    public function __construct(UserProsecutionRepository $userProsecutionRepository, UserRepository $userRepository, Logger $logger, EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);
        $this->userProsecutionRepository = $userProsecutionRepository;
        $this->userRepository = $userRepository;
    }

    public function removeBan(int $forUserId, int $byUserId, string $reason) {
        try {
            $this->commonRemoveBan($forUserId, $byUserId, $reason);
        } catch(AException $e) {
            throw $e;
        }
    }

    private function commonRemoveBan(string $forUserId, string $byUserId, string $reason) {
        $this->beginTransaction();

        $date = new DateTime();
        $date->modify('-1d');

        $newEndDate = $date->getResult();

        $forUser = $this->userRepository->getUserById($forUserId);
        $byUser = $this->userRepository->getUserById($byUserId);
        $prosecution = $this->userProsecutionRepository->getLastProsecutionForUserId($forUserId);

        if($forUser === null || $byUser === null || $prosecution === null) {
            $this->rollback();
            throw new GeneralException('Could not remove ban.');
        }

        $result = $this->userProsecutionRepository->updateProsecution($prosecution->getId(), ['endDate' => $newEndDate]);

        if($result === false) {
            $this->rollback();
            throw new GeneralException('Could not remove ban.');
        }

        $message = 'User "' . $byUser->getUsername() . '" (' . $byUserId . ') removed ban of user "' . $forUser->getUsername() . '" (' . $forUserId . '). Reason: ' . $reason;

        $result = $this->userProsecutionRepository->createNewUserProsecutionHistoryEntry($prosecution->getId(), $byUserId, $message);

        if($result === false) {
            $this->rollback();
            throw new GeneralException('Could not remove ban.');
        }

        $this->commit($byUser->getId(), __METHOD__);
    }

    public function warnUser(string $who, string $byWhom, string $reason) {
        try {
            $date = new DateTime();
            $date2 = new DateTime();
            $date2->modify('+1d');
            $this->commonCreateProsecution($who, $byWhom, $reason, $date->getResult(), $date2->getResult(), UserProsecutionType::WARNING);
        } catch(AException $e) {
            throw $e;
        }
    }

    public function banUser(string $who, string $byWhom, string $reason, string $startDate, string $endDate) {
        try {
            $this->commonCreateProsecution($who, $byWhom, $reason, $startDate, $endDate, UserProsecutionType::BAN);
        } catch(AException $e) {
            throw $e;
        }
    }

    public function permaBanUser(string $who, string $byWhom, string $reason) {
        try {
            $this->commonCreateProsecution($who, $byWhom, $reason, null, null, UserProsecutionType::PERMA_BAN);
        } catch(AException $e) {
            throw $e;
        }
    }

    private function commonCreateProsecution(string $forUserId, string $byUserId, string $reason, ?string $startDate, ?string $endDate, int $type, bool $invalidateCache = true) {
        $this->beginTransaction();

        $result = $this->userProsecutionRepository->createNewProsecution($forUserId, $type, $reason, $startDate, $endDate);
        
        if($result === false) {
            $this->rollback();
            throw new GeneralException('Could not create a prosecution.');
        }

        $message = '';

        $forUser = $this->userRepository->getUserById($forUserId);
        $byUser = $this->userRepository->getUserById($byUserId);
        $prosecution = $this->userProsecutionRepository->getLastProsecutionForUserId($forUserId);

        if($forUser === null || $byUser === null || $prosecution === null) {
            $this->rollback();
            throw new GeneralException('Could not create a prosecution.');
        }

        switch($type) {
            case UserProsecutionType::WARNING:
                $message = 'User "' . $byUser->getUsername() . '" (' . $byUserId . ') warned user "' . $forUser->getUsername() . '" (' . $forUserId . ').';
                break;

            case UserProsecutionType::BAN:
                $message = 'User "' . $byUser->getUsername() . '" (' . $byUserId . ') banned user "' . $forUser->getUsername() . '" (' . $forUserId . ') from ' . DateTimeFormatHelper::formatDateToUserFriendly($prosecution->getStartDate()) . ' to ' . DateTimeFormatHelper::formatDateToUserFriendly($prosecution->getEndDate()) . '.';
                break;

            case UserProsecutionType::PERMA_BAN:
                $message = 'User "' . $byUser->getUsername() . '" (' . $byUserId . ') banned user "' . $forUser->getUsername() . '" (' . $forUserId . ') permanently.';
                break;
        }        
        
        $result = $this->userProsecutionRepository->createNewUserProsecutionHistoryEntry($prosecution->getId(), $byUserId, $message);

        if($result === false) {
            $this->rollback();
            throw new GeneralException('Could not create a prosecution.');
        }

        $this->commit($byUser->getId(), __METHOD__);

        if($invalidateCache) {
            $cache = $this->cacheFactory->getCache(CacheNames::USERS);
            $cache->invalidate();
        }
    }

    private function beginTransaction() {
        try {
            $this->userRepository->tryBeginTransaction();
        } catch (AException $e) {
            return false;
        }

        return true;
    }

    private function rollback() {
        try {
            $this->userRepository->tryRollback();
        } catch(AException $e) {
            return false;
        }

        return true;
    }

    private function commit(string $userId, string $method) {
        try {
            $this->userRepository->tryCommit($userId, $method);
        } catch(AException $e) {
            return false;
        }

        return true;
    }
}

?>