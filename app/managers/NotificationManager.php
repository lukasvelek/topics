<?php

namespace App\Managers;

use App\Constants\Notifications;
use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\NotificationRepository;

class NotificationManager extends AManager {
    private NotificationRepository $nr;

    public function __construct(Logger $logger, NotificationRepository $nr) {
        parent::__construct($logger);

        $this->nr = $nr;
    }

    public function createNewPostLikeNotification(int $userId, string $postLink, string $authorLink) {
        $type = Notifications::NEW_POST_LIKE;

        $title = Notifications::getTitleByKey($type);

        $message = $this->prepareMessage($type, ['$POST_LINK$' => $postLink, '$AUTHOR_LINK$' => $authorLink]);

        $this->createNotification($userId, $type, $title, $message);
    }

    public function createNewPostCommentNotification(int $userId, string $postLink, string $authorLink) {
        $type = Notifications::NEW_POST_COMMENT;

        $title = Notifications::getTitleByKey($type);

        $message = $this->prepareMessage($type, ['$AUTHOR_LINK$' => $authorLink, '$POST_LINK$' => $postLink]);

        $this->createNotification($userId, $type, $title, $message);
    }

    public function createNewTopicInviteNotification(int $userId, string $topicLink) {
        $type = Notifications::NEW_TOPIC_INVITE;

        $title = Notifications::getTitleByKey($type);
        
        $message = $this->prepareMessage($type, ['$TOPIC_LINK$' => $topicLink]);

        $this->createNotification($userId, $type, $title, $message);
    }

    private function prepareMessage(int $type, array $data) {
        $message = Notifications::getTextByKey($type);

        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        return str_replace($k, $v, $message);
    }

    private function createNotification(int $userId, int $type, string $title, string $message) {
        $id = $this->createNotificationId();

        $result = $this->nr->createNotification($id, $userId, $type, $title, $message);

        if($result !== TRUE) {
            throw new GeneralException('Could not create notification.');
        }
    }

    private function createNotificationId() {
        return HashManager::createHash(16, false);
    }
}

?>