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

    public function canDeleteComment(int $userId) {
        return $this->commonContentManagement($userId);
    }

    public function canDeletePost(int $userId) {
        return $this->commonContentManagement($userId);
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
}

?>