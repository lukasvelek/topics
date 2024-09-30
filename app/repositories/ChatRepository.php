<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
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
            ->orderBy('dateCreated');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = UserChatMessageEntity::createEntityFromDbRow($row);
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
}

?>