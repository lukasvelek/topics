<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Constants\TopicMemberRole;
use App\Core\DatabaseConnection;
use App\Entities\PostEntity;
use App\Entities\PostImageFileEntity;
use App\Entities\TopicEntity;
use App\Entities\TopicPollEntity;
use App\Logger\Logger;
use App\Managers\TopicMembershipManager;
use App\Repositories\GroupRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;

class ActionAuthorizator extends AAuthorizator {
    private TopicMembershipManager $tpm;
    private PostRepository $pr;

    public function __construct(DatabaseConnection $db, Logger $logger, UserRepository $userRepository, GroupRepository $groupRepository, TopicMembershipManager $tpm, PostRepository $pr) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);

        $this->tpm = $tpm;
        $this->pr = $pr;
    }

    public function canChangeUserTopicRole(string $topicId, string $callingUserId, string $userId) {
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

    public function canManageTopicRoles(string $topicId, string $userId) {
        $role = $this->tpm->getFollowRole($topicId, $userId);

        if($role === null) {
            return false;
        }

        if($role < TopicMemberRole::MANAGER) {
            return false;
        }

        return true;
    }

    public function canRemoveMemberFromGroup(string $userId) {
        return $this->commonGroupManagement($userId);
    }

    public function canAddMemberToGroup(string $userId) {
        return $this->commonGroupManagement($userId);
    }

    public function canDeleteComment(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canDeletePost(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canDeleteTopic(string $userId) {
        return $this->commonContentManagement($userId);
    }

    private function commonContentManagement(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }
    
    private function commonGroupManagement(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_USER_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    public function canReportPost(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canReportTopic(string $userId, string $topicId) {
        return $this->canReportPost($userId, $topicId);
    }

    public function canCreateTopicPoll(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER)) {
            return false;
        }

        return true;
    }

    public function canViewTopicPolls(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canManageTopicInvites(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) {
            return false;
        }

        return true;
    }

    public function canCreatePost(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MEMBER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    public function canManageTopicPrivacy(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::OWNER)) {
            return false;
        }

        return true;
    }

    public function canSeePollAnalytics(string $userId, string $topicId, TopicPollEntity $tpe) {
        if($tpe->getAuthorId() != $userId) {
            if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER)/* && (!$this->commonContentManagement($userId))*/) {
                return false;
            }
        }

        return true;
    }

    public function canDeactivePoll(string $userId, string $topicId, TopicPollEntity $tpe) {
        if($tpe->getAuthorId() != $userId) {
            if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) && (!$this->commonContentManagement($userId))) {
                return false;
            }
        }

        return true;
    }

    public function canSeeAllTopicPolls(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }
    
    public function canDeleteFileUpload(string $userId, PostImageFileEntity $pife) {
        $post = $this->pr->getPostById($pife->getPostId());

        if($post !== null) {
            if(!$post->isDeleted()) {
                return false;
            }
        }
        
        return true;
    }

    public function canUploadFileForPost(string $userId, PostEntity $post) {
        if($post->getAuthorId() != $userId) {
            return false;
        }

        return true;
    }

    public function canManageTopicPosts(string $userId, TopicEntity $topic) {
        if((($this->tpm->getFollowRole($topic->getId(), $userId)) < TopicMemberRole::MANAGER) && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    public function canSetPostSuggestability(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }
}

?>