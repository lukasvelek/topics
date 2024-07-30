<?php

namespace App\Managers;

use App\Exceptions\AException;
use App\Exceptions\UserFollowException;
use App\Logger\Logger;
use App\Repositories\UserRepository;
use App\Repository\UserFollowingRepository;

class UserFollowingManager extends AManager {
    private UserRepository $userRepository;
    private UserFollowingRepository $userFollowingRepository;

    public function __construct(Logger $logger, UserRepository $userRepository, UserFollowingRepository $userFollowingRepository) {
        parent::__construct($logger);

        $this->userRepository = $userRepository;
        $this->userFollowingRepository = $userFollowingRepository;
    }

    public function followUser(int $authorId, int $userId) {
        try {
            if($this->userFollowingRepository->checkFollow($authorId, $userId)) {
                throw new UserFollowException(sprintf('User %d already follows user %d.', $authorId, $userId));
            }

            if(!$this->userFollowingRepository->followUser($authorId, $userId)) {
                throw new UserFollowException('Could not follow user ' . $userId . '. Reason: Database exception.');
            }
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
        if(!$this->userFollowingRepository->checkFollow($authorId, $userId)) {
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
}

?>