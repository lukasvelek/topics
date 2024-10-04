<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\TopicBroadcastChannelEntity;
use App\Entities\TopicBroadcastChannelMessageEntity;
use App\Entities\TopicBroadcastChannelSubscriberEntity;
use App\Entities\UserChatEntity;
use App\Entities\UserChatMessageEntity;
use App\Logger\Logger;

class ChatRepository extends ARepository {
    public function __construct(DatabaseConnection $conn, Logger $logger) {
        parent::__construct($conn, $logger);
    }

    public function composeQueryForChatsForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('user_chats',)
            ->where('user1Id = ? OR user2Id = ?', [$userId, $userId]);

        return $qb;
    }

    public function getMessagesForChat(string $chatId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('user_chat_messages')
            ->where('chatId = ?', [$chatId])
            ->orderBy('dateCreated', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entity = UserChatMessageEntity::createEntityFromDbRow($row);

            if($entity !== null) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    public function createNewChat(string $chatId, string $user1Id, string $user2Id) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('user_chats', ['chatId', 'user1Id', 'user2Id'])
            ->values([$chatId, $user1Id, $user2Id])
            ->execute();

        return $qb->fetchBool();
    }

    public function getLastChatMessageForChat(string $chatId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('user_chat_messages')
            ->where('chatId = ?', [$chatId])
            ->orderBy('dateCreated', 'DESC')
            ->limit(1)
            ->execute();

        return UserChatMessageEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getChatEntityById(string $chatId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('user_chats')
            ->where('chatId = ?', [$chatId])
            ->execute();

        return UserChatEntity::createEntityFromDbRow($qb->fetch());
    }

    public function createNewChatMessage(string $messageId, string $chatId, string $authorId, string $message) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('user_chat_messages', ['messageId', 'chatId', 'authorId', 'message'])
            ->values([$messageId, $chatId, $authorId, $message])
            ->execute();

        return $qb->fetchBool();
    }

    public function getChatMessageEntityById(string $messageId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('user_chat_messages')
            ->where('messageId = ?', [$messageId])
            ->execute();

        return UserChatMessageEntity::createEntityFromDbRow($qb->fetch());
    }

    public function createNewTopicBroadcastChannel(string $channelId, string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('topic_broadcast_channels', ['channelId', 'topicId'])
            ->values([$channelId, $topicId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getTopicBroadcastChannelById(string $channelId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channels')
            ->where('channelId = ?', [$channelId])
            ->execute();

        return TopicBroadcastChannelEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getTopicBroadcastChannelSubscribers(string $channelId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channel_subscribers')
            ->where('channelId = ?', [$channelId])
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = TopicBroadcastChannelSubscriberEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getTopicBroadcastChannelMessages(string $channelId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channel_messages')
            ->where('channelId = ?', [$channelId])
            ->orderBy('dateCreated', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = TopicBroadcastChannelMessageEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function createNewTopicBroadcastChannelSubscribe(string $subscribeId, string $channelId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('topic_broadcast_channel_subscribers', ['subscribeId', 'channelId', 'userId'])
            ->values([$subscribeId, $channelId, $userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeTopicBroadcastChannelSubscribe(string $channelId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('topic_broadcast_channel_subscribers')
            ->where('channelId = ?', [$channelId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function createNewTopicBroadcastChannelMessage(string $messageId, string $channelId, string $authorId, string $message) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('topic_broadcast_channel_messages', ['messageId', 'channelId', 'authorId', 'message'])
            ->values([$messageId, $channelId, $authorId, $message])
            ->execute();

        return $qb->fetchBool();
    }

    public function getTopicChannelSubscriptionsForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channel_subscribers')
            ->where('userId = ?', [$userId])
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = TopicBroadcastChannelSubscriberEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getTopicChannelSubscriptionForUserAndChannel(string $userId, string $channelId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channel_subscribers')
            ->where('userId = ?', [$userId])
            ->andWhere('channelId = ?', [$channelId])
            ->execute();

        return TopicBroadcastChannelSubscriberEntity::createEntityFromDbRow($qb->fetch());
    }
    
    public function composeQueryForTopicChannelsForUser() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channels');

        return $qb;
    }

    public function getLastMessageForTopicBroadcastChannel(string $channelId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channel_messages')
            ->where('channelId = ?', [$channelId])
            ->orderBy('dateCreated', 'DESC')
            ->limit(1)
            ->execute();

        return TopicBroadcastChannelMessageEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getTopicBroadcastChannelForTopicId(string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('topic_broadcast_channels')
            ->where('topicId = ?', [$topicId])
            ->execute();

        return TopicBroadcastChannelEntity::createEntityFromDbRow($qb->fetch());
    }
}

?>