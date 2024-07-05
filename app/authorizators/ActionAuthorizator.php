<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Constants\TopicMemberRole;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Managers\TopicMembershipManager;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class ActionAuthorizator extends AAuthorizator {
    private TopicMembershipManager $tpm;

    public function __construct(DatabaseConnection $db, Logger $logger, UserRepository $userRepository, GroupRepository $groupRepository, TopicMembershipManager $tpm) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);

        $this->tpm = $tpm;
    }

    public function canChangeUserTopicRole(int $topicId, int $callingUserId, int $userId) {
        $callingRole = $this->tpm->getFollowRole($topicId, $callingUserId);
        $role = $this->tpm->getFollowRole($topicId, $userId);

        if($callingUserId == $userId) {
            return false;
        }

        if($callingRole === null || $role === null) {
            return false;
        }

        if($role == TopicMemberRole::OWNER && $callingRole != TopicMemberRole::OWNER) {
            return false;
        }

        if($callingRole <= $role && ($role != TopicMemberRole::OWNER && $callingRole != TopicMemberRole::OWNER)) {
            return false;
        }

        return true;
    }

    public function canManageTopicRoles(int $topicId, int $userId) {
        $role = $this->tpm->getFollowRole($topicId, $userId);

        if($role === null) {
            return false;
        }

        if($role < TopicMemberRole::MANAGER) {
            return false;
        }

        return true;
    }

    public function canRemoveMemberFromGroup(int $userId) {
        return $this->commonGroupManagement($userId);
    }

    public function canAddMemberToGroup(int $userId) {
        return $this->commonGroupManagement($userId);
    }

    public function canDeleteComment(int $userId, int $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canDeletePost(int $userId, int $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canDeleteTopic(int $userId) {
        return $this->commonContentManagement($userId);
    }

    private function commonContentManagement(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }
    
    private function commonGroupManagement(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_USER_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    public function canReportPost(int $userId, int $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canReportTopic(int $userId, int $topicId) {
        return $this->canReportPost($userId, $topicId);
    }

    public function canCreateTopicPoll(int $userId, int $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canViewTopicPolls(int $userId, int $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }
}

?>