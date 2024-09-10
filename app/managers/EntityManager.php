<?php

namespace App\Managers;

use App\Core\HashManager;
use App\Logger\Logger;
use App\Repositories\ContentRepository;

class EntityManager extends AManager {
    public const TOPIC_POLL_RESPONSES = 'topic_polls_responses';
    public const USERS = 'users';
    public const POST_FILE_UPLOADS = 'post_file_uploads';
    public const EMAILS = 'mail_queue';
    public const NOTIFICATIONS = 'notifications';
    public const TOPIC_MEMBERSHIP = 'topic_membership';
    public const FORGOTTEN_PASSWORD = 'user_forgotten_password_links';
    public const USER_REGISTRATION = 'user_registration_links';
    public const POST_COMMENTS = 'post_comments';
    public const POSTS = 'posts';
    public const TOPICS = 'topics';
    public const TOPIC_POLLS = 'topic_polls';
    public const TRANSACTIONS = 'transaction_log';
    public const REPORTS = 'reports';
    public const USER_FOLLOWS = 'user_following';
    public const ADMIN_DASHBOARD_WIDGETS_GRAPH_DATA = 'admin_dashboard_widgets_graph_data';
    public const SUGGESTIONS = 'user_suggestions';
    public const SUGGESTION_COMMENTS = 'user_suggestion_comments';
    public const BANNED_WORDS = 'banned_words';
    public const USER_PROSECUTIONS = 'user_prosecutions';
    public const TOPIC_POST_PINS = 'topic_post_pins';
    public const POST_CONCEPTS = 'post_concepts';
    public const TOPIC_RULES = 'topic_rules';
    public const GRID_EXPORTS = 'grid_exports';
    public const TOPIC_CALENDAR_USER_EVENTS = 'topic_calendar_user_events';

    private ContentRepository $cr;

    public function __construct(Logger $logger, ContentRepository $cr) {
        parent::__construct($logger, null);

        $this->cr = $cr;
    }

    public function generateEntityId(string $category) {
        $unique = true;
        $run = true;

        $entityId = null;
        while($run) {
            $entityId = HashManager::createEntityId();

            $primaryKeyName = $this->getPrimaryKeyNameByCategory($category);

            $unique = $this->cr->checkIdIsUnique($category, $primaryKeyName, $entityId);

            if($unique) {
                $run = false;
                break;
            }
        }

        return $entityId;
    }

    private function getPrimaryKeyNameByCategory(string $category) {
        return match($category) {
            self::TOPIC_POLL_RESPONSES => 'responseId',
            self::USERS => 'userId',
            self::POST_FILE_UPLOADS => 'uploadId',
            self::EMAILS => 'mailId',
            self::NOTIFICATIONS => 'notificationId',
            self::TOPIC_MEMBERSHIP => 'membershipId',
            self::FORGOTTEN_PASSWORD => 'linkId',
            self::USER_REGISTRATION => 'registrationId',
            self::POST_COMMENTS => 'commentId',
            self::POSTS => 'postId',
            self::TOPICS => 'topicId',
            self::TOPIC_POLLS => 'pollId',
            self::TRANSACTIONS => 'transactionId',
            self::REPORTS => 'reportId',
            self::USER_FOLLOWS => 'followId',
            self::ADMIN_DASHBOARD_WIDGETS_GRAPH_DATA => 'dataId',
            self::SUGGESTIONS => 'suggestionId',
            self::SUGGESTION_COMMENTS => 'commentId',
            self::BANNED_WORDS => 'wordId',
            self::USER_PROSECUTIONS => 'prosecutionId',
            self::TOPIC_POST_PINS => 'pinId',
            self::POST_CONCEPTS => 'conceptId',
            self::TOPIC_RULES => 'rulesetId',
            self::GRID_EXPORTS => 'exportId',
            self::TOPIC_CALENDAR_USER_EVENTS => 'eventId'
        };
    }
}

?>