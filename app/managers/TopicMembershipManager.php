<?php

namespace App\Managers;

use App\Constants\TopicMemberRole;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\TopicInviteRepository;
use App\Repositories\TopicMembershipRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserRepository;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use Exception;

class TopicMembershipManager extends AManager {
    private TopicRepository $topicRepository;
    private TopicMembershipRepository $topicMembershipRepository;
    private TopicInviteRepository $topicInviteRepository;
    private NotificationManager $notificationManager;
    private MailManager $mailManager;
    private UserRepository $userRepository;
    
    private Cache $groupMembershipsCache;

    public function __construct(TopicRepository $topicRepository,
                                TopicMembershipRepository $topicMembershipRepository,
                                Logger $logger,
                                TopicInviteRepository $topicInviteRepository,
                                NotificationManager $notificationManager,
                                MailManager $mailManager,
                                UserRepository $userRepository,
                                EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);

        $this->topicMembershipRepository = $topicMembershipRepository;
        $this->topicRepository = $topicRepository;
        $this->topicInviteRepository = $topicInviteRepository;
        $this->notificationManager = $notificationManager;
        $this->mailManager = $mailManager;
        $this->userRepository = $userRepository;

        $this->groupMembershipsCache = $this->cacheFactory->getCache(CacheNames::GROUP_MEMBERSHIPS);
    }

    public function followTopic(string $topicId, string $userId,) {
        if($this->checkFollow($topicId, $userId)) {
            throw new GeneralException('User already follows the topic.');
        }

        $membershipId = $this->createId(EntityManager::TOPIC_MEMBERSHIP);

        if(!$this->topicMembershipRepository->addMemberToTopic($membershipId, $topicId, $userId, TopicMemberRole::MEMBER)) {
            throw new GeneralException('Could not add member to the topic.');
        }

        $this->invalidateMembershipCache();
    }

    public function unfollowTopic(string $topicId, string $userId) {
        if(!$this->checkFollow($topicId, $userId)) {
            throw new GeneralException('User does not follow the topic.');
        }

        if(!$this->topicMembershipRepository->removeMemberFromTopic($topicId, $userId)) {
            throw new GeneralException('Could not remove member from the topic.');
        }

        $this->invalidateMembershipCache();
    }

    public function checkFollow(string $topicId, string $userId) {
        $membership = $this->loadMembershipDataFromCache($topicId, $userId);

        if($membership === null) {
            return false;
        }

        return true;
    }

    public function getFollowRole(string $topicId, string $userId) {
        if(!$this->checkFollow($topicId, $userId)) {
            return null;
        }

        $data = $this->loadMembershipDataFromCache($topicId, $userId);

        if($data !== null) {
            return $data->getRole();
        } else {
            return null;
        }
    }

    public function getTopicMembers(string $topicId, int $limit, int $offset, bool $orderByRoleDesc = true) {
        return $this->topicMembershipRepository->getTopicMembersForGrid($topicId, $limit, $offset, $orderByRoleDesc);
    }

    public function changeRole(string $topicId, string $userId, string $callingUserId, int $newRole) {
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

    public function createUserProfileLinkWithRole(UserEntity $user, string $topicId, string $namePrefix = '', string $class = 'post-data-link') {
        $role = $this->getFollowRole($topicId, $user->getId());

        if($role === null) {
            return $user->getUsername() . ' (Ex-user)';
        }

        $span = HTML::span();
        $span->setColor(TopicMemberRole::getColorByKey($role))
            ->setText(TopicMemberRole::toString($role));

        $text = $namePrefix . $user->getUsername() . ' (' . $span->render() . ')';

        return LinkBuilder::createSimpleLink($text, ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], $class);
    }

    private function loadMembershipDataFromCache(string $topicId, string $userId) {
        $key = $topicId . '_' . $userId;

        return $this->groupMembershipsCache->load($key, function() use ($userId, $topicId) {
            return $this->topicMembershipRepository->getMembershipForUserInTopic($userId, $topicId);
        });
    }

    private function invalidateMembershipCache() {
        $this->groupMembershipsCache->invalidate();
    }

    public function getUserMembershipsInTopics(string $userId) {
        return $this->topicMembershipRepository->getUserMembershipsInTopics($userId);
    }

    public function getTopicMemberCount(string $topicId) {
        return $this->topicMembershipRepository->getTopicMemberCount($topicId);
    }

    public function getTopicIdsUserIsNotMemberOf(string $userId) {
        $memberships = $this->topicMembershipRepository->getUserMembershipsInTopics($userId);

        return $memberships;
    }

    public function inviteUser(string $topicId, string $userId, string $callingUserId) {
        if($this->checkUserInviteExists($topicId, $userId) !== null) {
            throw new GeneralException('This user has already been invited.');
        }

        $now = new DateTime();
        $now->modify('+7d');
        $dateValid = $now->getResult();

        try {
            if(!$this->topicInviteRepository->createInvite($topicId, $userId, $dateValid)) {
                throw new GeneralException('Database error');
            }

            $topic = $this->topicRepository->getTopicById($topicId);
            $link = TopicEntity::createTopicProfileLink($topic, true);

            $this->notificationManager->createNewTopicInviteNotification($userId, $link);

            $recipient = $this->userRepository->getUserById($userId);

            $this->mailManager->createNewTopicInvite($recipient, $topic);
        } catch(AException|Exception $e) {
            throw $e;
        }
    }

    public function checkUserInviteExists(string $topicId, string $userId) {
        $invite = $this->topicInviteRepository->getInviteForTopicAndUser($topicId, $userId);

        return $invite;
    }

    public function getInvitesForTopic(string $topicId) {
        return $this->topicInviteRepository->getInvitesForGrid($topicId, true, 0, 0);
    }

    public function removeInvite(string $topicId, string $userId) {
        if($this->checkUserInviteExists($topicId, $userId) === null) {
            throw new GeneralException('This user has not been invited yet.');
        }

        if(!$this->topicInviteRepository->deleteInvite($topicId, $userId)) {
            throw new GeneralException('Database error.');
        }
    }

    public function acceptInvite(string $topicId, string $userId) {
        $this->removeInvite($topicId, $userId);
        $this->followTopic($topicId, $userId);
    }

    public function rejectInvite(string $topicId, string $userId) {
        $this->removeInvite($topicId, $userId);
    }

    public function getTopicOwnerId(string $topicId) {
        return $this->topicMembershipRepository->getTopicOwner($topicId);
    }

    public function isTopicFollowable(string $topicId) {
        $topic = $this->topicRepository->getTopicById($topicId);

        if($topic->isDeleted()) {
            return false;
        }

        if(!$topic->isVisible()) {
            return false;
        }

        if($topic->isPrivate()) {
            return false;
        }

        return true;
    }

    public function getTopicsWhereUserIsOwnerOrderByTopicDateCreated(string $userId, int $limit) {
        $topicIds = $this->topicMembershipRepository->getTopicIdsForOwner($userId);

        if(empty($topicIds)) {
            return [];
        }

        $qb = $this->topicRepository->composeQueryForTopics();
        $qb ->where($qb->getColumnInValues('topicId', $topicIds))
            ->orderBy('dateCreated', 'DESC');

        if($limit > 0) {
            $qb->limit($limit);
        }

        $qb->execute();

        $topics = [];
        while($row = $qb->fetchAssoc()) {
            $entity = TopicEntity::createEntityFromDbRow($row);

            if($entity !== null) {
                $topics[] = $entity;
            }
        }

        return $topics;
    }
}

?>