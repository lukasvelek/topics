<?php

namespace App\Managers;

use App\Entities\UserChatEntity;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\ChatRepository;

/**
 * Chat manager is responsible for interactions with chats
 * 
 * @author Lukas Velek
 */
class ChatManager extends AManager {
    private ChatRepository $cr;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param EntityManager $entityManager EntityManager instance
     * @param ChatRepository $cr ChatRepository instance
     */
    public function __construct(Logger $logger, EntityManager $entityManager, ChatRepository $cr) {
        parent::__construct($logger, $entityManager);

        $this->cr = $cr;
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

        $query->execute();

        $chats = [];
        $lastMessages = [];
        while($row = $query->fetchAssoc()) {
            $chat = UserChatEntity::createEntityFromDbRow($row);
            $lastMessage = $this->cr->getLastChatMessageForChat($chat->getChatId());
            if($lastMessage !== null) {
                $chats[$lastMessage->getDateCreated()] = $chat;
                $lastMessages[$chat->getChatId()] = $lastMessage;
            } else {
                $chats[] = $chat;
            }
        }

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

    public function createNewMessage(string $chatId, string $authorId, string $text) {
        $messageId = $this->createId(EntityManager::USER_CHAT_MESSAGES);

        $result = $this->cr->createNewChatMessage($messageId, $chatId, $authorId, $text);

        if($result === false) {
            throw new GeneralException('Could not create a new message.');
        }

        return $messageId;
    }

    public function getChatMessageEntityById(string $messageId) {
        $entity = $this->cr->getChatMessageEntityById($messageId);

        if($entity === null) {
            throw new GeneralException('Could not get message.');
        }

        return $entity;
    }
}

?>