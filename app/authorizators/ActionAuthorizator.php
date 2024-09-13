<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Constants\TopicMemberRole;
use App\Core\DatabaseConnection;
use App\Entities\PostEntity;
use App\Entities\PostImageFileEntity;
use App\Entities\TopicCalendarUserEventEntity;
use App\Entities\TopicEntity;
use App\Entities\TopicPollEntity;
use App\Logger\Logger;
use App\Managers\TopicMembershipManager;
use App\Repositories\GroupRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;

/**
 * ActionAuthorizator allows to check if given user is allowed to perform certain actions
 * 
 * @author Lukas Velek
 */
class ActionAuthorizator extends AAuthorizator {
    private TopicMembershipManager $tpm;
    private PostRepository $pr;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     * @param UserRepository $userRepository UserRepository instance
     * @param GroupRepository $groupRepository GroupRepository instance
     * @param TopicMembershipManager $tpm TopicMembershipManager instance
     * @param PostRepository $pr PostRepository instance
     */
    public function __construct(DatabaseConnection $db, Logger $logger, UserRepository $userRepository, GroupRepository $groupRepository, TopicMembershipManager $tpm, PostRepository $pr) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);

        $this->tpm = $tpm;
        $this->pr = $pr;
    }

    /**
     * Checks if given calling user is allowed to change role in given topic of given user
     * 
     * @param string $topicId Topic ID
     * @param string $callingUserId Calling user ID
     * @param string $userId User ID
     * @return bool True if user is allowed to perform this action or false if not
     */
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

    /**
     * Checks if given user is allowed to manage topic roles in given topic
     * 
     * @param string $topicId Topic ID
     * @param string $userId User ID
     * @return bool True if user is allowed to perform this action or false if not
     */
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

    /**
     * Checks if given user is allowed to remove members from group
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canRemoveMemberFromGroup(string $userId) {
        return $this->commonGroupManagement($userId);
    }

    /**
     * Checks if given user is allowed to add members to group
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canAddMemberToGroup(string $userId) {
        return $this->commonGroupManagement($userId);
    }

    /**
     * Checks if given user is allowed to delete comments on posts in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canDeleteComment(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to delete posts in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canDeletePost(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to delete given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canDeleteTopic(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::OWNER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Common method used to check if given user is administrator or is member of Content management administrator group or is superadministrator
     * 
     * @param string $userId User ID
     * @return bool True if user is member or false if not
     */
    private function commonContentManagement(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }
    
    /**
     * Common method used to check if given user is administrator or is member of User administrator group or is superadministrator
     * 
     * @param string $userId User ID
     * @return bool True if user is member or false if not
     */
    private function commonGroupManagement(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_USER_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to report posts in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canReportPost(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to report given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canReportTopic(string $userId, string $topicId) {
        return $this->canReportPost($userId, $topicId);
    }

    /**
     * Checks if given user is allowed to create polls in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canCreateTopicPoll(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to view list of polls in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canViewTopicPolls(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage invites for given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canManageTopicInvites(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to create posts in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canCreatePost(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MEMBER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage topic privacy
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canManageTopicPrivacy(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::OWNER)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to see poll analytics in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @param TopicPollEntity $tpe TopicPollEntity instance
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canSeePollAnalytics(string $userId, string $topicId, TopicPollEntity $tpe) {
        if($tpe->getAuthorId() != $userId) {
            if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER)/* && (!$this->commonContentManagement($userId))*/) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if given user is allowed to deactivate poll in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @param TopicPollEntity $tpe TopicPollEntity instance
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canDeactivePoll(string $userId, string $topicId, TopicPollEntity $tpe) {
        if($tpe->getAuthorId() != $userId) {
            if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) && (!$this->commonContentManagement($userId))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if given user is allowed to see all polls in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canSeeAllTopicPolls(string $userId, string $topicId) {
        if(($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER) && (!$this->commonContentManagement($userId))) {
            return false;
        }

        return true;
    }
    
    /**
     * Checks if given user is allowed to delete file upload
     * 
     * @param string $userId User ID
     * @param PostImageFileEntity $pife PostImageFileEntity instance
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canDeleteFileUpload(string $userId, PostImageFileEntity $pife) {
        $post = $this->pr->getPostById($pife->getPostId());

        if($post !== null) {
            if(!$post->isDeleted()) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Checks if given user is allowed to upload files for given post
     * 
     * @param string $userId User ID
     * @param PostEntity $post PostEntity instance
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canUploadFileForPost(string $userId, PostEntity $post) {
        if($post->getAuthorId() != $userId) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage posts in given topic
     * 
     * @param string $userId User ID
     * @param TopicEntity $topic TopicEntity instance
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canManageTopicPosts(string $userId, TopicEntity $topic) {
        if((($this->tpm->getFollowRole($topic->getId(), $userId)) < TopicMemberRole::MANAGER) && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to change the suggestability of posts in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canSetPostSuggestability(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to schedule posts in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canSchedulePosts(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to create post concepts in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canUsePostConcepts(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::VIP && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage rules in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canManageTopicRules(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER && !$this->commonContentManagement($userId)) {
            return false;
        }
        
        return true;
    }

    /**
     * Checks if given user is allowed to manage followers of given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canManageTopicFollowers(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to view calendar of given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canSeeTopicCalendar(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::COMMUNITY_HELPER && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to create a user event in calendar of given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canCreateTopicCalendarUserEvents(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to edit a user event in calendar of given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @param TopicCalendarUserEventEntity $tcuee TopicCalendarUserEventEntity instance
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canEditUserCalendarEvent(string $userId, string $topicId, TopicCalendarUserEventEntity $tcuee) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER && !$this->commonContentManagement($userId)) {
            return false;
        }

        if(($tcuee->getUserId() != $userId) && ($this->tpm->getFollowRole($topicId, $tcuee->getUserId()) > $this->tpm->getFollowRole($topicId, $userId))) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to delete a user event in calendar of given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @param TopicCalendarUserEventEntity $tcuee TopicCalendarUserEventEntity instance
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canDeleteUserCalendarEvent(string $userId, string $topicId, TopicCalendarUserEventEntity $tcuee) {
        return $this->canEditUserCalendarEvent($userId, $topicId, $tcuee);
    }

    /**
     * Checks if given user is allowed to manage content regulation in given topic
     * 
     * @param string $userId User ID
     * @param string $topicId Topic ID
     * @return bool True if user is allowed to perform this action or false if not
     */
    public function canManageContentRegulation(string $userId, string $topicId) {
        if($this->tpm->getFollowRole($topicId, $userId) < TopicMemberRole::MANAGER && !$this->commonContentManagement($userId)) {
            return false;
        }

        return true;
    }
}

?>