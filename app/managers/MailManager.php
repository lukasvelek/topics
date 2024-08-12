<?php

namespace App\Managers;

use App\Constants\MailTemplates;
use App\Core\HashManager;
use App\Entities\EmailEntity;
use App\Entities\TopicEntity;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Exceptions\MailSendException;
use App\Logger\Logger;
use App\Repositories\UserRepository;
use App\Rpeositories\MailRepository;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailManager extends AManager {
    public MailRepository $mailRepository;
    private UserRepository $userRepository;
    public array $cfg;

    public function __construct(Logger $logger, MailRepository $mailRepository, UserRepository $userRepository, array $cfg) {
        parent::__construct($logger);

        $this->mailRepository = $mailRepository;
        $this->userRepository = $userRepository;
        $this->cfg = $cfg;
    }

    /** EMAIL DEFINITION */

    public function createNewForgottenPassword(UserEntity $recipient, string $link) {
        $data = [
            '$USER_NAME$' => $recipient->getUsername(),
            '$LINK$' => $link
        ];

        return $this->createEmailEntry($recipient->getId(), MailTemplates::FORGOTTEN_PASSWORD, $data);
    }

    public function createNewUserRegistration(UserEntity $recipient, string $link) {
        $data = [
            '$USER_NAME$' => $recipient->getUsername(),
            '$LINK$' => $link
        ];

        return $this->createEmailEntry($recipient->getId(), MailTemplates::REGISTRATION_CONFIRMATION, $data);
    }

    public function createNewTopicInvite(UserEntity $recipient, TopicEntity $topic) {
        $link = '<a class="post-data-link" href="' . $this->getBaseURL() . '?page=UserModule:TopicInvites&action=list">here</a>';

        $data = [
            '$LINK$' => $link,
            '$TOPIC_TITLE$' => $topic->getTitle(),
            '$USER_NAME$' => $recipient->getUsername()
        ];

        return $this->createEmailEntry($recipient->getId(), MailTemplates::NEW_TOPIC_INVITE, $data);
    }

    /** END OF EMAIL DEFINITION */
    
    private function createEmailEntry(string $recipientId, int $mailTemplate, array $data) {
        $id = $this->createMailId();

        $recipient = $this->userRepository->getUserById($recipientId);

        [$title, $content] = MailTemplates::getTemplateData($mailTemplate);

        foreach($data as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        if($recipient->getEmail() === null) {
            throw new GeneralException('Email recipient has not email address defined.');
        }

        return $this->mailRepository->createEntry($id, $recipient->getEmail(), $title, $content);
    }

    private function createMailId() {
        return HashManager::createEntityId();
    }

    private function getBaseURL() {
        return $this->cfg['APP_URL_BASE'];
    }

    public function sendEmail(EmailEntity $ee) {
        $mail = $this->preparePHPMailer();

        $mail->Subject = $ee->getTitle();
        $mail->Body = $ee->getContent();
        $mail->addAddress($ee->getRecipient());

        try {
            $mail->send();
        } catch(Exception $e) {
            throw new MailSendException($e->getMessage(), $e);
        }
    }

    private function preparePHPMailer() {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $this->cfg['MAIL_SERVER'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->cfg['MAIL_USERNAME'];
        $mail->Password = $this->cfg['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $this->cfg['MAIL_SERVER_PORT'];

        $mail->setFrom($this->cfg['MAIL_EMAIL'], $this->cfg['APP_NAME']);
        $mail->isHTML(true);

        return $mail;
    }

    public function getAllUnsentEmails(int $limit, int $offset) {
        return $this->mailRepository->getAllEntriesLimited($limit, $offset);
    }

    public function deleteEmailEntry(string $emailId) {
        $data = [
            'isSent' => '1'
        ];

        return $this->mailRepository->updateEntry($emailId, $data);
    }
}

?>