<?php

namespace App\Managers;

use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\UserFollowException;
use App\Logger\Logger;
use App\Repositories\UserFollowingRepository;
use App\Repositories\UserRepository;

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

    public function followUser(string $authorId, string $userId) {
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

    public function unfollowUser(string $authorId, string $userId) {
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

    public function canFollowUser(string $authorId, string $userId) {
        if($this->userFollowingRepository->checkFollow($authorId, $userId)) { // is following
            return false;
        }

        return true;
    }

    public function getFollowerCount(string $userId) {
        return count($this->userFollowingRepository->getFollowersForUser($userId));
    }

    public function getFollowingCount(string $userId) {
        return count($this->userFollowingRepository->getFollowsForUser($userId));
    }

    public function getFollowersForUserWithOffset(string $userId, int $limit, int $offset) {
        return $this->userFollowingRepository->getFollowersForUserWithOffset($userId, $limit, $offset);
    }

    public function getFollowsForUserWithOffset(string $userId, int $limit, int $offset) {
        return $this->userFollowingRepository->getFollowsForUserWithOffset($userId, $limit, $offset);
    }
}

?>