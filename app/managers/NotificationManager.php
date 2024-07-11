<?php

namespace App\Managers;

use App\Constants\Notifications;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\NotificationRepository;
use App\UI\LinkBuilder;

class NotificationManager extends AManager {
    private const LOG = true;
    
    private NotificationRepository $nr;

    public function __construct(Logger $logger, NotificationRepository $nr) {
        parent::__construct($logger);

        $this->nr = $nr;
    }

    public function createNewCommentLikeNotification(int $userId, LinkBuilder $postLink, LinkBuilder $authorLink) {
        $type = Notifications::NEW_COMMENT_LIKE;
        $title = Notifications::getTitleByKey($type);
        
        $id = $this->createNotificationId();

        [$postLink, $authorLink] = $this->processURL($id, [$postLink, $authorLink]);
        
        $message = $this->prepareMessage($type, ['$AUTHOR_LINK$' => $authorLink, '$POST_LINK$' => $postLink]);
        
        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewPostLikeNotification(int $userId, LinkBuilder $postLink, LinkBuilder $authorLink) {
        $type = Notifications::NEW_POST_LIKE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createNotificationId();

        [$postLink, $authorLink] = $this->processURL($id, [$postLink, $authorLink]);

        $message = $this->prepareMessage($type, ['$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewPostCommentNotification(int $userId, LinkBuilder $postLink, LinkBuilder $authorLink) {
        $type = Notifications::NEW_POST_COMMENT;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createNotificationId();

        [$postLink, $authorLink] = $this->processURL($id, [$postLink, $authorLink]);

        $message = $this->prepareMessage($type, ['$AUTHOR_LINK$' => $authorLink, '$POST_LINK$' => $postLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
        }

        $this->createNotification($id, $userId, $type, $title, $message);
    }

    public function createNewTopicInviteNotification(int $userId, LinkBuilder $topicLink) {
        $type = Notifications::NEW_TOPIC_INVITE;
        $title = Notifications::getTitleByKey($type);

        $id = $this->createNotificationId();

        $topicLink = $this->processURL($id, [$topicLink]);
        
        $message = $this->prepareMessage($type, ['$TOPIC_LINK$' => $topicLink]);

        if(self::LOG) {
            $params = ['userId' => $userId, 'type' => $type, 'title' => $title, 'message' => $message, '$TOPIC_LINK$' => $topicLink];
            $this->logger->info('Creating notification with params: ' . var_export($params, true), __METHOD__);
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

    private function createNotification(string $notificationId, int $userId, int $type, string $title, string $message) {
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

    private function createNotificationId() {
        return HashManager::createHash(16, false);
    }

    public function getUnseenNotificationsForUser(int $userId) {
        return $this->nr->getNotificationsForUser($userId);
    }

    public function setNotificationAsSeen(string $notificationId) {
        return $this->nr->updateNotification($notificationId, ['dateSeen' => DateTime::now()]);
    }
}

?>