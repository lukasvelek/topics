<?php

namespace App\Managers;

use App\Constants\MailTemplates;
use App\Core\HashManager;
use App\Entities\EmailEntity;
use App\Entities\TopicEntity;
use App\Entities\UserEntity;
use App\Logger\Logger;
use App\Repositories\UserRepository;
use App\Rpeositories\MailRepository;
use App\UI\LinkBuilder;

class MailManager extends AManager {
    private MailRepository $mailRepository;
    private UserRepository $userRepository;

    public function __construct(Logger $logger, MailRepository $mailRepository, UserRepository $userRepository) {
        parent::__construct($logger);

        $this->mailRepository = $mailRepository;
        $this->userRepository = $userRepository;
    }
    
    private function createEmailEntry(int $recipientId, int $mailTemplate, array $data) {
        $id = $this->createMailId();

        $recipient = $this->userRepository->getUserById($recipientId);

        [$title, $content] = MailTemplates::getTemplateData($mailTemplate);

        foreach($data as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        return $this->mailRepository->createEntry($id, $recipient->getEmail(), $title, $content);
    }

    private function createMailId() {
        return HashManager::createHash(32, false);
    }

    public function createNewTopicInvite(UserEntity $recipient, TopicEntity $topic) {
        $link = '<a class="post-data-link" href="localhost/?page=UserModule:TopicInvites&action=list">here</a>';

        $data = [
            '$LINK$' => $link,
            '$TOPIC_TITLE$' => $topic->getTitle(),
            '$USER_NAME$' => $recipient->getUsername()
        ];

        return $this->createEmailEntry($recipient->getId(), MailTemplates::NEW_TOPIC_INVITE, $data);
    }

    public function sendEmail(EmailEntity $ee) {
        
    }
}

?>