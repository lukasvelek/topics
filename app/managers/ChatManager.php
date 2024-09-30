<?php

namespace App\Managers;

use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
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
    private Cache $userChatsCache;
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
        $this->userChatsCache = $this->cacheFactory->getCache($this->getUserChatsCacheNamespace($userId));
        $query = $this->cr->composeQueryForChatsForUser($userId);

        if($limit > 0) {
            $query->limit($limit);
        }
        if($offset > 0) {
            $query->offset($offset);
        }

        $lastMessages = [];

        $chats = $this->userChatsCache->load('chats', function() use ($query) {
            $query->execute();
            $chats = [];
            while($row = $query->fetchAssoc()) {
                $chat = UserChatEntity::createEntityFromDbRow($row);
                $chats[] = $chat;
            }

            return $chats;
        });

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

        $this->invalidateCache($user1Id);

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
     * Invalidates user chats cache
     * 
     * @param string $userId User ID
     */
    public function invalidateCache(string $userId) {
        $userChatsCache = $this->cacheFactory->getCache($this->getUserChatsCacheNamespace($userId));

        $userChatsCache->invalidate();
    }

    /**
     * Creates a user chats cache namespace
     * 
     * @param string $userId User ID
     * @return string Cache namespace
     */
    private function getUserChatsCacheNamespace(string $userId) {
        return CacheNames::USER_CHATS . '/' . $userId;
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
}

?>