<?php

namespace App\Managers;

use App\Constants\TopicMemberRole;
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
        if(!$this->checkFollow($topicId, $userId)) {
            return false;
        }

        if(!$this->topicRepository->followTopic($userId, $topicId)) {
            return false;
        }

        if(!$this->topicMembershipRepository->addMemberToTopic($topicId, $userId, TopicMemberRole::MEMBER)) {
            return false;
        }

        return true;
    }

    public function unfollowTopic(int $topicId, int $userId) {
        if(!$this->checkFollow($topicId, $userId)) {
            return false;
        }

        if(!$this->topicRepository->unfollowTopic($userId, $topicId)) {
            return false;
        }

        if(!$this->topicMembershipRepository->removeMemberFromTopic($topicId, $userId)) {
            return false;
        }

        return true;
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
}

?>