<?php

namespace App\Managers;

use App\Entities\TopicBroadcastChannelEntity;
use App\Entities\UserChatEntity;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\ChatRepository;
use App\Repositories\UserRepository;

/**
 * Chat manager is responsible for interactions with chats
 * 
 * @author Lukas Velek
 */
class ChatManager extends AManager {
    private ChatRepository $cr;
    private UserRepository $ur;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param EntityManager $entityManager EntityManager instance
     * @param ChatRepository $cr ChatRepository instance
     */
    public function __construct(Logger $logger, EntityManager $entityManager, ChatRepository $cr, UserRepository $ur) {
        parent::__construct($logger, $entityManager);

        $this->cr = $cr;
        $this->ur = $ur;
    }

    /**
     * Returns chat list with limited elements for given user. It also returns an array of last messages for loaded chats.
     * 
     * @param string $userId User ID
     * @param int $limit Entry limit
     * @param int $offset Entry offset
     * @return array<string, array> Array with keys "chats" (contains a list of chats) and "last messages" (last messages for given chats)
     */
    public function getChatsForUser(string $userId, int $limit, int $offset) {
        $query = $this->cr->composeQueryForChatsForUser($userId);

        if($limit > 0) {
            $query->limit($limit);
        }
        if($offset > 0) {
            $query->offset($offset);
        }

        $lastMessages = [];

        $query->execute();
        $chats = [];
        while($row = $query->fetchAssoc()) {
            $chats[] = UserChatEntity::createEntityFromDbRow($row);
        }

        $tmp = [];
        foreach($chats as $chat) {
            $lastMessage = $this->cr->getLastChatMessageForChat($chat->getChatId());
            if($lastMessage !== null) {
                $tmp[$lastMessage->getDateCreated()] = $chat;
                $lastMessages[$chat->getChatId()] = $lastMessage;
            } else {
                $tmp[] = $chat;
            }
        }

        $chats = $tmp;

        rsort($chats, SORT_NUMERIC);

        return ['chats' => $chats, 'lastMessages' => $lastMessages];
    }

    /**
     * Creates a new chat
     * 
     * @param string $user1Id User #1 ID
     * @param string $user2Id User #2 ID
     * @return string Chat ID
     */
    public function createNewChat(string $user1Id, string $user2Id) {
        $chatId = $this->createId(EntityManager::USER_CHATS);

        $result = $this->cr->createNewChat($chatId, $user1Id, $user2Id);

        if($result === false) {
            throw new GeneralException('Could not create a new chat.');
        }

        return $chatId;
    }

    /**
     * Returns a chat entity for given chat ID
     * 
     * @param string $chatId Chat ID
     * @return UserChatEntity UserChatEntity instance
     */
    public function getChatEntityById(string $chatId) {
        $entity = $this->cr->getChatEntityById($chatId);

        if($entity === null) {
            throw new NonExistingEntityException('Entity does not exist.');
        }

        return $entity;
    }

    /**
     * Returns messages for given chat
     * 
     * @param string $chatId Chat ID
     * @param int $limit Number of messages to return
     * @param int $offset Offset
     */
    public function getChatMessages(string $chatId, int $limit, int $offset) {
        $messages = $this->cr->getMessagesForChat($chatId, $limit, $offset);

        return $messages;
    }

    /**
     * Creates a new message and saves it to the database
     * 
     * @param string $chatId Chat ID
     * @param string $authorId Author ID
     * @param string $text Message text
     * @return string Message ID
     */
    public function createNewMessage(string $chatId, string $authorId, string $text) {
        $messageId = $this->createId(EntityManager::USER_CHAT_MESSAGES);

        $result = $this->cr->createNewChatMessage($messageId, $chatId, $authorId, $text);

        if($result === false) {
            throw new GeneralException('Could not create a new message.');
        }

        return $messageId;
    }

    /**
     * Returns a UserChatMessageEntity for given $messageId
     * 
     * @param string $messageId Message ID
     * @return \App\Entities\UserChatMessageEntity Message
     */
    public function getChatMessageEntityById(string $messageId) {
        $entity = $this->cr->getChatMessageEntityById($messageId);

        if($entity === null) {
            throw new GeneralException('Could not get message.');
        }

        return $entity;
    }

    /**
     * Searches users available for chatting. Returns only users for whose no chat history with given $userId exists.
     * 
     * @param string $query Username
     * @param string $userId Current user ID
     * @return array Users
     */
    public function searchUsersForNewChat(string $query, string $userId) {
        $qb = $this->cr->composeQueryForChatsForUser($userId);
        $qb->select(['user1Id', 'user2Id']);

        $qb->execute();

        $usersInChats = [$userId];
        while($row = $qb->fetchAssoc()) {
            if($row['user1Id'] == $userId) {
                $usersInChats[] = $row['user2Id'];
            } else {
                $usersInChats[] = $row['user1Id'];
            }
        }

        $qb = $this->ur->composeStandardQuery($query, __METHOD__);
        $qb->andWhere($qb->getColumnNotInValues('userId', $usersInChats))
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = UserEntity::createEntityFromDbRow($row);
        }

        return $users;
    }

    /**
     * Creates a new topic channel
     * 
     * @param string $topicId Topic ID
     * @param string $userId User ID
     * @return string Channel ID
     */
    public function createNewTopicBroadcastChannel(string $topicId) {
        $channelId = $this->createId(EntityManager::TOPIC_BROADCAST_CHANNELS);
        
        if(!$this->cr->createNewTopicBroadcastChannel($channelId, $topicId)) {
            throw new GeneralException('Could not create a new broadcast channel.');
        }

        return $channelId;
    }

    /**
     * Create a new topic channel subscription
     * 
     * @param string $channelId Channel ID
     * @param string $userId User ID
     * @return string Subscription ID
     */
    public function createNewTopicBroadcastChannelSubscribe(string $channelId, string $userId) {
        $subscribeId = $this->createId(EntityManager::TOPIC_BROADCAST_CHANNEL_SUBSCRIBERS);

        if(!$this->cr->createNewTopicBroadcastChannelSubscribe($subscribeId, $channelId, $userId)) {
            throw new GeneralException('Could not subscribe to the newly created broadcast channel.');
        }

        return $subscribeId;
    }

    public function removeTopicBroadcastChannelSubscribe(string $channelId, string $userId) {
        if(!$this->cr->removeTopicBroadcastChannelSubscribe($channelId, $userId)) {
            throw new GeneralException('Could not remove subscription from the topic broadcast channel.');
        }

        return true;
    }

    /**
     * Create a new topic channel message
     * 
     * @param string $channelId Channel ID
     * @param string $userId User ID
     * @param string $message Message
     * @return string Message ID
     */
    public function createNewTopicBroadcastChannelMessage(string $channelId, string $userId, string $message) {
        $messageId = $this->createId(EntityManager::TOPIC_BROADCAST_CHANNEL_MESSAGES);

        if(!$this->cr->createNewTopicBroadcastChannelMessage($messageId, $channelId, $userId, $message)) {
            throw new GeneralException('Could not create a new message.');
        }

        return $messageId;
    }

    /**
     * Gets messages for a topic broadcast channel
     * 
     * @param string $channelId Channel ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array<\App\Entities\TopicBroadcastChannelMessageEntity> Messages
     */
    public function getTopicBroadcastChannelMessages(string $channelId, int $limit, int $offset) {
        $messages = $this->cr->getTopicBroadcastChannelMessages($channelId, $limit, $offset);

        return $messages;
    }

    /**
     * Gets topic broadcast channels user is a member of
     * 
     * @param string $userId User ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @param array Channels and last messages
     */
    public function getTopicBroadcastChannelsForUser(string $userId, int $limit, int $offset) {
        $subscriptions = $this->cr->getTopicChannelSubscriptionsForUser($userId);

        $query = $this->cr->composeQueryForTopicChannelsForUser();

        $channelIdsUserIsSubscriberOf = [];
        foreach($subscriptions as $sub) {
            $channelIdsUserIsSubscriberOf[] = $sub->getChannelId();
        }

        $query->where($query->getColumnInValues('channelId', $channelIdsUserIsSubscriberOf));

        if($limit > 0) {
            $query->limit($limit);
        }
        if($offset > 0) {
            $query->offset($offset);
        }

        $query->execute();

        $channels = [];
        while($row = $query->fetchAssoc()) {
            $channels[] = TopicBroadcastChannelEntity::createEntityFromDbRow($row);
        }

        $lastMessages = [];
        $tmp = [];
        foreach($channels as $channel) {
            $lastMessage = $this->cr->getLastMessageForTopicBroadcastChannel($channel->getChannelId());
            if($lastMessage !== null) {
                $tmp[$lastMessage->getDateCreated()] = $channel;
                $lastMessages[$channel->getChannelId()] = $lastMessage;
            } else {
                $tmp[] = $channel;
            }
        }

        $channels = $tmp;

        rsort($channels, SORT_NUMERIC);

        return ['channels' => $channels, 'lastMessages' => $lastMessages];
    }

    /**
     * Returns a topic broadcast channel for given topic
     * 
     * @param string $topicId Topic ID
     * @return null|\App\Entities\TopicBroadcastChannelEntity Topic broadcast channel
     */
    public function getTopicBroadcastChannelForTopic(string $topicId) {
        return $this->cr->getTopicBroadcastChannelForTopicId($topicId);
    }

    /**
     * Returns if user is subscribed to a topic broadcast channel
     * 
     * @param string $userId User ID
     * @param string $channelId Channel ID
     * @return bool True if user is subscribed or false if not
     */
    public function isUserSubscribedToTopicBroadcastChannel(string $userId, string $channelId) {
        $subscription = $this->cr->getTopicChannelSubscriptionForUserAndChannel($userId, $channelId);

        return $subscription !== null;
    }
}

?>