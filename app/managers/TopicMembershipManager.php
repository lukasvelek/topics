<?php

namespace App\Managers;

use App\Constants\TopicMemberRole;
use App\Core\CacheManager;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\TopicMembershipRepository;
use App\Repositories\TopicRepository;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class TopicMembershipManager extends AManager {
    private const CACHE_NAMESPACE = 'topicMemberships';

    private TopicRepository $topicRepository;
    private TopicMembershipRepository $topicMembershipRepository;

    public function __construct(TopicRepository $topicRepository, TopicMembershipRepository $topicMembershipRepository, Logger $logger) {
        parent::__construct($logger);

        $this->topicMembershipRepository = $topicMembershipRepository;
        $this->topicRepository = $topicRepository;
    }

    public function followTopic(int $topicId, int $userId,) {
        if($this->checkFollow($topicId, $userId)) {
            throw new GeneralException('User already follows the topic.');
        }

        if(!$this->topicRepository->followTopic($userId, $topicId)) {
            throw new GeneralException('Could not follow the topic.');
        }

        if(!$this->topicMembershipRepository->addMemberToTopic($topicId, $userId, TopicMemberRole::MEMBER)) {
            throw new GeneralException('Could not add member to the topic.');
        }
    }

    public function unfollowTopic(int $topicId, int $userId) {
        if(!$this->checkFollow($topicId, $userId)) {
            throw new GeneralException('User does not follow the topic.');
        }

        if(!$this->topicRepository->unfollowTopic($userId, $topicId)) {
            throw new GeneralException('Could not unfollow the topic');
        }

        if(!$this->topicMembershipRepository->removeMemberFromTopic($topicId, $userId)) {
            throw new GeneralException('Could not remove member from the topic.');
        }
    }

    public function checkFollow(int $topicId, int $userId) {
        if(!$this->topicRepository->checkFollow($userId, $topicId)) {
            return false;
        }

        if(!$this->topicMembershipRepository->checkIsMember($topicId, $userId)) {
            return false;
        }

        return true;
    }

    public function getFollowRole(int $topicId, int $userId) {
        if(!$this->checkFollow($topicId, $userId)) {
            return null;
        }

        $data = $this->loadMembershipDataFromCache($topicId, $userId);

        return $data->getRole();
    }

    public function getTopicMembers(int $topicId, int $limit, int $offset, bool $orderByRoleDesc = true) {
        return $this->topicMembershipRepository->getTopicMembersForGrid($topicId, $limit, $offset, $orderByRoleDesc);
    }

    public function changeRole(int $topicId, int $userId, int $callingUserId, int $newRole) {
        if(!$this->checkFollow($topicId, $userId)) {
            throw new GeneralException('The selected user is not a member of this topic.');
        }

        if(!$this->checkFollow($topicId, $callingUserId)) {
            throw new GeneralException('Current user is not a member of this topic.');
        }

        if(!$this->topicMembershipRepository->updateMemberRole($topicId, $userId, $newRole)) {
            throw new GeneralException('Could not change the selected user\'s role.');
        }
        
        $oldRole = TopicMemberRole::toString($this->getFollowRole($topicId, $userId));
        $newRole = TopicMemberRole::toString($newRole);

        $this->logger->warning(sprintf('User #%d changed role of user #%d from %s to %s.', $callingUserId, $userId, $oldRole, $newRole), __METHOD__);

        $this->invalidateMembershipCache();
    }

    public function createUserProfileLinkWithRole(UserEntity $user, int $topicId, string $namePrefix = '') {
        $role = $this->getFollowRole($topicId, $user->getId());

        $span = HTML::span();
        $span->setColor(TopicMemberRole::getColorByKey($role))
            ->setText(TopicMemberRole::toString($role));

        $text = $namePrefix . $user->getUsername() . ' (' . $span->render() . ')';

        return LinkBuilder::createSimpleLink($text, ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'post-data-link');
    }

    private function loadMembershipDataFromCache(int $topicId, int $userId) {
        $key = $topicId . '_' . $userId;

        return CacheManager::loadCache($key, function () use ($userId, $topicId) {
            return $this->topicMembershipRepository->getMembershipForUserInTopic($userId, $topicId);
        }, self::CACHE_NAMESPACE);
    }

    private function invalidateMembershipCache() {
        CacheManager::invalidateCache(self::CACHE_NAMESPACE);
    }
}

?>