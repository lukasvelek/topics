<?php

namespace App\Managers;

use App\Constants\Notifications;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\NotificationRepository;
use App\UI\LinkBuilder;

class NotificationManager extends AManager {
    private const LOG = true;
    
    private NotificationRepository $nr;
    private Cache $cache;

    public function __construct(Logger $logger, NotificationRepository $nr, EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);

        $this->nr = $nr;

        $expiration = new DateTime();
        $expiration->modify('+1i'); // 1 minute
        $this->cache = $this->cacheFactory->getCache(CacheNames::NOTIFICATIONS, $expiration);
    }

    public function createNewUnlimitedGridExportNotification(string $userId, string $downloadLink) {
        $type = Notifications::GRID_EXPORT_FINISHED;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        //[$downloadLink] = $this->processURL($id, [$downloadLink]);

        $message = $this->prepareMessage($type, ['$DOWNLOAD_LINK$' => $downloadLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$DOWNLOAD_LINK$' => $downloadLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewCommentDeleteDueToReportNotification(string $userId, LinkBuilder $postLink, LinkBuilder $userLink, string $reason) {
        $type = Notifications::COMMENT_DELETE_DUE_TO_REPORT;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$postLink, $userLink] = $this->processURL($id, [$postLink, $userLink]);

        $message = $this->prepareMessage($type, ['$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink, '$DELETE_REASON$' => $reason]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink, '$DELETE_REASON$' => $reason];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewPostDeleteDueToReportNotification(string $userId, LinkBuilder $postLink, LinkBuilder $userLink, string $reason) {
        $type = Notifications::POST_DELETE_DUE_TO_REPORT;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$postLink, $userLink] = $this->processURL($id, [$postLink, $userLink]);

        $message = $this->prepareMessage($type, ['$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink, '$DELETE_REASON$' => $reason]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink, '$DELETE_REASON$' => $reason];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewTopicDeleteDueToReportNotification(string $userId, LinkBuilder $topicLink, LinkBuilder $userLink, string $reason) {
        $type = Notifications::TOPIC_DELETE_DUE_TO_REPORT;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$topicLink, $userLink] = $this->processURL($id, [$topicLink, $userLink]);

        $message = $this->prepareMessage($type, ['$TOPIC_LINK$' => $topicLink, '$USER_LINK$' => $userLink, '$DELETE_REASON$' => $reason]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$TOPIC_LINK$' => $topicLink, '$USER_LINK$' => $userLink, '$DELETE_REASON$' => $reason];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewCommentDeletedNotification(string $userId, LinkBuilder $postLink, LinkBuilder $userLink) {
        $type = Notifications::COMMENT_DELETE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$postLink, $userLink] = $this->processURL($id, [$postLink, $userLink]);

        $message = $this->prepareMessage($type, ['$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewPostDeletedNotification(string $userId, LinkBuilder $postLink, LinkBuilder $userLink) {
        $type = Notifications::POST_DELETE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$postLink, $userLink] = $this->processURL($id, [$postLink, $userLink]);

        $message = $this->prepareMessage($type, ['$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$USER_LINK$' => $userLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewTopicDeletedNotification(string $userId, LinkBuilder $topicLink, LinkBuilder $userLink) {
        $type = Notifications::TOPIC_DELETE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$topicLink, $userLink] = $this->processURL($id, [$topicLink, $userLink]);

        $message = $this->prepareMessage($type, ['$TOPIC_LINK$' => $topicLink, '$USER_LINK$' => $userLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$TOPIC_LINK$' => $topicLink, '$USER_LINK$' => $userLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewCommentLikeNotification(string $userId, LinkBuilder $postLink, LinkBuilder $authorLink) {
        $type = Notifications::NEW_COMMENT_LIKE;
        $title = Notifications::getTitleByKey($type);
        
        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$postLink, $authorLink] = $this->processURL($id, [$postLink, $authorLink]);
        
        $message = $this->prepareMessage($type, ['$AUTHOR_LINK$' => $authorLink, '$POST_LINK$' => $postLink]);
        
        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewPostLikeNotification(string $userId, LinkBuilder $postLink, LinkBuilder $authorLink) {
        $type = Notifications::NEW_POST_LIKE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$postLink, $authorLink] = $this->processURL($id, [$postLink, $authorLink]);

        $message = $this->prepareMessage($type, ['$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewPostCommentNotification(string $userId, LinkBuilder $postLink, LinkBuilder $authorLink) {
        $type = Notifications::NEW_POST_COMMENT;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$postLink, $authorLink] = $this->processURL($id, [$postLink, $authorLink]);

        $message = $this->prepareMessage($type, ['$AUTHOR_LINK$' => $authorLink, '$POST_LINK$' => $postLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewTopicInviteNotification(string $userId, LinkBuilder $topicLink) {
        $type = Notifications::NEW_TOPIC_INVITE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        $invitationsLink = LinkBuilder::createSimpleLinkObject('here', ['page' => 'UserModule:TopicInvites', 'action' => 'list'], 'post-data-link');

        [$topicLink, $invitationsLink] = $this->processURL($id, [$topicLink, $invitationsLink]);
        
        $message = $this->prepareMessage($type, ['$TOPIC_LINK$' => $topicLink, '$INVITATIONS_LINK$' => $invitationsLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$TOPIC_LINK$' => $topicLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewTopicRoleChangedNotification(string $userId, LinkBuilder $topicLink, string $oldRole, string $newRole) {
        $type = Notifications::TOPIC_ROLE_CHANGE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$topicLink] = $this->processURL($id, [$topicLink]);

        $message = $this->prepareMessage($type, ['$TOPIC_LINK$' => $topicLink, '$OLD_ROLE$' => $oldRole, '$NEW_ROLE$' => $newRole]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$TOPIC_LINK$' => $topicLink, '$OLD_ROLE$' => $oldRole, '$NEW_ROLE$' => $newRole];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewUserFollowerNotification(string $userId, LinkBuilder $userLink) {
        $type = Notifications::NEW_USER_FOLLOWER;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createId(EntityManager::NOTIFICATIONS);

        [$userLink] = $this->processURL($id, [$userLink]);
        
        $message = $this->prepareMessage($type, ['$USER_LINK$' => $userLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$USER_LINK$' => $userLink];
            $this->logger->info('Creatign notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    private function prepareMessage(int $type, array $data) {
        $message = Notifications::getTextByKey($type);

        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        return str_replace($keys, $values, $message);
    }

    private function createNotification(string $notificationId, string $userId, int $type, string $title, string $message) {
        $result = $this->nr->createNotification($notificationId, $userId, $type, $title, $message);

        if($result !== TRUE) {
            throw new GeneralException('Could not create notification.');
        }
    }

    private function processURL(string $notificationId, array $linkBuilderElements) {
        $tmp = [];

        foreach($linkBuilderElements as $lbe) {
            $lbe->setUrl(['notificationId' => $notificationId, 'removeNotification' => '1']);

            $tmp[] = $lbe->render();
        }

        return $tmp;
    }

    public function getUnseenNotificationsForUser(string $userId) {
        return $this->cache->load($userId, function() use ($userId) {
            return $this->nr->getNotificationsForUser($userId);
        });
    }

    public function setNotificationAsSeen(string $notificationId, string $userId) {
        $this->cache->invalidateKey($userId);
        return $this->nr->updateNotification($notificationId, ['dateSeen' => DateTime::now()]);
    }

    public function bulkSetNotificationsAsSeen(array $notifications, string $userId, bool $isArrayOfObjects = true) {
        if($isArrayOfObjects) {
            $tmp = [];
            foreach($notifications as $notification) {
                $tmp[] = $notification->getId();
            }
            $notifications = $tmp;
        }

        $this->cache->invalidateKey($userId);
        return $this->nr->bulkUpdateNotifications($notifications, ['dateSeen' => DateTime::now()]);
    }

    public function getOldSeenNotifications(string $date) {
        return $this->nr->getSeenNotificationsOlderThanX($date);
    }

    public function bulkRemoveNotifications(array $notificationIds) {
        return $this->nr->bulkRemoveNotifications($notificationIds);
    }

    public function getOldSeenNotificationsStep(string $date, int $limit, int $offset) {
        return $this->nr->getSeenNotificationsOlderThanXSteps($date, $limit, $offset);
    }
}

?>