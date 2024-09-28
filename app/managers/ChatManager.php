<?php

namespace App\Managers;

use App\Entities\UserChatEntity;
use App\Logger\Logger;
use App\Repositories\ChatRepository;

class ChatManager extends AManager {
    private ChatRepository $cr;

    public function __construct(Logger $logger, EntityManager $entityManager, ChatRepository $cr) {
        parent::__construct($logger, $entityManager);

        $this->cr = $cr;
    }

    public function getChatsForUser(string $userId, int $limit, int $offset) {
        $query = $this->cr->composeQueryForChatsForUser($userId);

        $query->join('user_chat_messages', 'ucm', 'uc.chatId = ucm.chatId')
            ->orderBy('ucm.dateCreated', 'DESC');

        if($limit > 0) {
            $query->limit($limit);
        }
        if($offset > 0) {
            $query->offset($offset);
        }

        $query->execute();

        $chats = [];
        while($row = $query->fetchAssoc()) {
            $chats[] = UserChatEntity::createEntityFromDbRow($row);
        }

        return $chats;
    }
}

?>