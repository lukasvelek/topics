<?php

namespace App\Managers;

use App\Constants\UserProsecutionType;
use App\Core\CacheManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Repositories\UserProsecutionRepository;
use App\Repositories\UserRepository;

class UserProsecutionManager {
    private UserProsecutionRepository $userProsecutionRepository;
    private UserRepository $userRepository;

    public function __construct(UserProsecutionRepository $userProsecutionRepository, UserRepository $userRepository) {
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

    private function commonRemoveBan(int $forUserId, int $byUserId, string $reason) {
        $this->beginTransaction();

        $newEndDate = date('Y-m-d H:i:s', (time() - $this->calculateDaysToSeconds(1)));

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

        $message = 'User \'' . $byUser->getUsername() . '\' (' . $byUserId . ') removed ban of user \'' . $forUser->getUsername() . '\' (' . $forUserId . '). Reason: ' . $reason;

        $result = $this->userProsecutionRepository->createNewUserProsecutionHistoryEntry($prosecution->getId(), $byUserId, $message);

        if($result === false) {
            $this->rollback();
            throw new GeneralException('Could not remove ban.');
        }

        $this->commit();
    }

    public function warnUser(int $who, int $byWhom, string $reason) {
        try {
            $this->commonCreateProsecution($who, $byWhom, $reason, date('Y-m-d H:i:s'), date('Y-m-d H:i:s', (time() + $this->calculateDaysToSeconds(1))), UserProsecutionType::WARNING);
        } catch(AException $e) {
            throw $e;
        }
    }

    public function banUser(int $who, int $byWhom, string $reason, string $startDate, string $endDate) {
        try {
            $this->commonCreateProsecution($who, $byWhom, $reason, $startDate, $endDate, UserProsecutionType::BAN);
        } catch(AException $e) {
            throw $e;
        }
    }

    public function permaBanUser(int $who, int $byWhom, string $reason) {
        try {
            $this->commonCreateProsecution($who, $byWhom, $reason, null, null, UserProsecutionType::PERMA_BAN);
        } catch(AException $e) {
            throw $e;
        }
    }

    private function commonCreateProsecution(int $forUserId, int $byUserId, string $reason, ?string $startDate, ?string $endDate, int $type, bool $invalidateCache = true) {
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
                $message = 'User \'' . $byUser->getUsername() . '\' (' . $byUserId . ') warned user \'' . $forUser->getUsername() . '\' (' . $forUserId . ').';
                break;

            case UserProsecutionType::BAN:
                $message = 'User \'' . $byUser->getUsername() . '\' (' . $byUserId . ') banned user \'' . $forUser->getUsername() . '\' (' . $forUserId . ') from ' . DateTimeFormatHelper::formatDateToUserFriendly($prosecution->getStartDate()) . ' to ' . DateTimeFormatHelper::formatDateToUserFriendly($prosecution->getEndDate()) . '.';
                break;

            case UserProsecutionType::PERMA_BAN:
                $message = 'User \'' . $byUser->getUsername() . '\' (' . $byUserId . ') banned user \'' . $forUser->getUsername() . '\' (' . $forUserId . ') permanently.';
                break;
        }        
        
        $result = $this->userProsecutionRepository->createNewUserProsecutionHistoryEntry($prosecution->getId(), $byUserId, $message);

        if($result === false) {
            $this->rollback();
            throw new GeneralException('Could not create a prosecution.');
        }

        $this->commit();

        if($invalidateCache) {
            CacheManager::invalidateCache('users');
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

    private function commit() {
        try {
            $this->userRepository->tryCommit();
        } catch(AException $e) {
            return false;
        }

        return true;
    }

    private function calculateDaysToSeconds(int $days) {
        return (60 * 60 * 24 * $days);
    }
}

?>