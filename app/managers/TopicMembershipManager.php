<?php

namespace App\Managers;

use App\Constants\TopicMemberRole;
use App\Exceptions\GeneralException;
use App\Repositories\TopicMembershipRepository;
use App\Repositories\TopicRepository;

class TopicMembershipManager {
    private TopicRepository $topicRepository;
    private TopicMembershipRepository $topicMembershipRepository;

    public function __construct(TopicRepository $topicRepository, TopicMembershipRepository $topicMembershipRepository) {
        $this->topicMembershipRepository = $topicMembershipRepository;
        $this->topicRepository = $topicRepository;
    }

    public function followTopic(int $topicId, int $userId) {
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

        return $this->topicMembershipRepository->getUserRoleInTopic($topicId, $userId);
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
    }
}

?>