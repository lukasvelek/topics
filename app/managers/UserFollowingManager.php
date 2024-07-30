<?php

namespace App\Managers;

use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\UserFollowException;
use App\Logger\Logger;
use App\Repositories\UserRepository;
use App\Repository\UserFollowingRepository;

class UserFollowingManager extends AManager {
    private UserRepository $userRepository;
    private UserFollowingRepository $userFollowingRepository;
    private NotificationManager $notificationManager;

    public function __construct(Logger $logger, UserRepository $userRepository, UserFollowingRepository $userFollowingRepository, NotificationManager $notificationManager) {
        parent::__construct($logger);

        $this->userRepository = $userRepository;
        $this->userFollowingRepository = $userFollowingRepository;
        $this->notificationManager = $notificationManager;
    }

    public function followUser(int $authorId, int $userId) {
        try {
            if($this->userFollowingRepository->checkFollow($authorId, $userId)) {
                throw new UserFollowException(sprintf('User %d already follows user %d.', $authorId, $userId));
            }

            if(!$this->userFollowingRepository->followUser($authorId, $userId)) {
                throw new UserFollowException('Could not follow user ' . $userId . '. Reason: Database exception.');
            }

            $userLink = UserEntity::createUserProfileLink($this->userRepository->getUserById($userId), true);

            $this->notificationManager->createNewUserFollowerNotification($userId, $userLink);
        } catch(AException $e) {
            throw $e;
        }
    }

    public function unfollowUser(int $authorId, int $userId) {
        try {
            if(!$this->userFollowingRepository->checkFollow($authorId, $userId)) {
                throw new UserFollowException(sprintf('User %d does not follow user %d.', $authorId, $userId));
            }

            if(!$this->userFollowingRepository->unfollowUser($authorId, $userId)) {
                throw new UserFollowException('Could not follow user ' . $userId . '. Reason: Database exception.');
            }
        } catch(AException $e) {
            throw $e;
        }
    }

    public function canFollowUser(int $authorId, int $userId) {
        if($this->userFollowingRepository->checkFollow($authorId, $userId)) { // is following
            return false;
        }

        return true;
    }

    public function getFollowerCount(int $userId) {
        return count($this->userFollowingRepository->getFollowersForUser($userId));
    }

    public function getFollowingCount(int $userId) {
        return count($this->userFollowingRepository->getFollowsForUser($userId));
    }

    public function getFollowersForUserWithOffset(int $userId, int $limit, int $offset) {
        return $this->userFollowingRepository->getFollowersForUserWithOffset($userId, $limit, $offset);
    }

    public function getFollowsForUserWithOffset(int $userId, int $limit, int $offset) {
        return $this->userFollowingRepository->getFollowsForUserWithOffset($userId, $limit, $offset);
    }
}

?>