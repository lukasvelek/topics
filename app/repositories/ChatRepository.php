<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\UserChatMessageEntity;
use App\Logger\Logger;

class ChatRepository extends ARepository {
    public function __construct(DatabaseConnection $conn, Logger $logger) {
        parent::__construct($conn, $logger);
    }

    public function composeQueryForChatsForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['uc.*'])
            ->from('user_chats', 'uc')
            ->where('(uc.user1Id = ? OR uc.user2Id = ?)', [$userId, $userId]);

        return $qb;
    }

    public function getMessagesForChat(string $chatId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('user_chat_messages')
            ->where('chatId = ?', [$chatId]);

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = UserChatMessageEntity::createEntityFromDbRow($row);
        }
    }

    public function createNewChat(string $chatId, string $user1Id, string $user2Id) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('user_chats', ['chatId', 'user1Id', 'user2Id'])
            ->values([$chatId, $user1Id, $user2Id])
            ->execute();

        return $qb->fetchBool();
    }
}

?>