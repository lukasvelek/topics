<?php

namespace App\Core;

use App\Constants\AdministratorGroups;
use App\Constants\Systems;
use App\Logger\Logger;

/**
 * Installs the database - creates tables, indexes
 * 
 * @author Lukas Velek
 */
class DatabaseInstaller {
    private DatabaseConnection $db;
    private Logger $logger;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Performs the database installation
     */
    public function install() {
        $this->logger->info('Database installation started.', __METHOD__);

        $this->createTables();
        $this->createIndexes();
        $this->createUsers();
        $this->createSystems();
        $this->createGroups();
        $this->addAdminToGroups();
        $this->addSystemServices();

        $this->logger->info('Database installation finished.', __METHOD__);
    }

    /**
     * Creates tables
     */
    private function createTables() {
        $this->logger->info('Creating tables.', __METHOD__);

        $tables = [
            'users' => [
                'userId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'username' => 'VARCHAR(256) NOT NULL',
                'password' => 'VARCHAR(256) NOT NULL',
                'loginHash' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'email' => 'VARCHAR(256) NULL',
                'isAdmin' => 'INT(2) NOT NULL DEFAULT 0',
                'canLogin' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'topics' => [
                'topicId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'isDeleted' => 'INT(2) NOT NULL DEFAULT 0',
                'dateDeleted' => 'DATETIME NULL',
                'tags' => 'TEXT NOT NULL',
                'isPrivate' => 'INT(2) NOT NULL DEFAULT 0',
                'isVisible' => 'INT(2) NOT NULL DEFAULT 1',
                'rawTags' => 'TEXT NOT NULL'
            ],
            'posts' => [
                'postId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'likes' => 'INT(32) NOT NULL DEFAULT 0',
                'isDeleted' => 'INT(2) NOT NULL DEFAULT 0',
                'dateDeleted' => 'DATETIME NULL',
                'tag' => 'VARCHAR(256) NOT NULL',
                'dateAvailable' => 'DATETIME NOT NULL',
                'isSuggestable' => 'INT(2) NOT NULL DEFAULT 1',
                'isScheduled' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'post_likes' => [
                'postId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL'
            ],
            'post_comments' => [
                'commentId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'postId' => 'VARCHAR(256) NOT NULL',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'commentText' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'likes' => 'INT(32) NOT NULL DEFAULT 0',
                'parentCommentId' => 'VARCHAR(256) NULL',
                'isDeleted' => 'INT(2) NOT NULL DEFAULT 0',
                'dateDeleted' => 'DATETIME NULL'
            ],
            'post_comment_likes' => [
                'commentId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL'
            ],
            'system_status' => [
                'systemId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'name' => 'VARCHAR(256) NOT NULL',
                'status' => 'INT(4) NOT NULL',
                'description' => 'TEXT NULL',
                'dateUpdated' => 'DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
            ],
            'user_suggestions' => [
                'suggestionId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'category' => 'VARCHAR(256) NOT NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_suggestion_comments' => [
                'commentId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'suggestionId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'commentText' => 'TEXT NOT NULL',
                'adminOnly' => 'INT(2) NOT NULL DEFAULT 0',
                'statusChange' => 'INT(2) NOT NULL DEFAULT 0',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'reports' => [
                'reportId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'entityId' => 'VARCHAR(256) NOT NULL',
                'entityType' => 'INT(4) NOT NULL',
                'category' => 'INT(4) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1',
                'statusComment' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_prosecutions' => [
                'prosecutionId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'reason' => 'TEXT NOT NULL',
                'type' => 'INT(4) NOT NULL',
                'startDate' => 'DATETIME NULL DEFAULT current_timestamp()',
                'endDate' => 'DATETIME NULL'
            ],
            'user_prosecutions_history' => [
                'historyId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'prosecutionId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'commentText' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'groups' => [
                'groupId' => 'INT(32) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL'
            ],
            'group_membership' => [
                'membershipId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'groupId' => 'INT(32) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'banned_words' => [
                'wordId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'word' => 'VARCHAR(256) NOT NULL',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_membership' => [
                'membershipId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'role' => 'INT(4) NOT NULL DEFAULT 1',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'system_services' => [
                'serviceId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'scriptPath' => 'VARCHAR(256) NOT NULL',
                'dateStarted' => 'DATETIME NULL',
                'dateEnded' => 'DATETIME NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1'
            ],
            'admin_dashboard_widgets_graph_data' => [
                'dataId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'mostActiveTopics' => 'TEXT NOT NULL',
                'mostActivePosts' => 'TEXT NOT NULL',
                'mostActiveUsers' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_polls' => [
                'pollId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'choices' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateValid' => 'DATETIME NULL',
                'timeElapsedForNextVote' => 'VARCHAR(256) NOT NULL'
            ],
            'topic_polls_responses' => [
                'responseId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'pollId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'choice' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_invites' => [
                'inviteId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateValid' => 'DATETIME NOT NULL'
            ],
            'notifications' => [
                'notificationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'type' => 'INT(4) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'message' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateSeen' => 'DATETIME NULL'
            ],
            'post_file_uploads' => [
                'uploadId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'postId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'filename' => 'VARCHAR(256) NOT NULL',
                'filepath' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'transaction_log' => [
                'transactionId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'methodName' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_following' => [
                'followId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'mail_queue' => [
                'mailId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'recipient' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'content' => 'TEXT NOT NULL',
                'isSent' => 'INT(2) NOT NULL DEFAULT 0',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_registration_links' => [
                'registrationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'link' => 'VARCHAR(256) NOT NULL',
                'isActive' => 'INT(2) NOT NULL DEFAULT 1',
                'dateExpire' => 'DATETIME NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_forgotten_password_links' => [
                'linkId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'isActive' => 'INT(2) NOT NULL DEFAULT 1',
                'dateExpire' => 'DATETIME NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_post_pins' => [
                'pinId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'postId' => 'VARCHAR(256) NOT NULL',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'post_concepts' => [
                'conceptId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'postData' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateUpdated' => 'DATETIME NULL'
            ],
            'topic_rules' => [
                'ruleId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'ruleText' => 'TEXT NOT NULL',
                'lastUpdateUserId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateUpdated' => 'DATETIME NULL'
            ],
            'grid_exports' => [
                'exportId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'hash' => 'VARCHAR(256) NOT NULL',
                'filename' => 'VARCHAR(256) NULL',
                'gridName' => 'VARCHAR(256) NOT NULL',
                'entryCount' => 'INT(32) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateFinished' => 'DATETIME NULL',
                'timeTaken' => 'INT(32) NULL'
            ],
            'topic_calendar_user_events' => [
                'eventId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateFrom' => 'DATETIME NOT NULL',
                'dateTo' => 'DATETIME NOT NULL'
            ],
            'topic_banned_words' => [
                'wordId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'word' => 'VARCHAR(256) NOT NULL',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_chats' => [
                'chatId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'user1Id' => 'VARCHAR(256) NOT NULL',
                'user2Id' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_chat_messages' => [
                'messageId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'chatId' => 'VARCHAR(256) NOT NULL',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'message' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_broadcast_channels' => [
                'channelId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'topicId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_broadcast_channel_subscribers' => [
                'subscribeId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'channelId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_broadcast_channel_messages' => [
                'messageId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'channelId' => 'VARCHAR(256) NOT NULL',
                'authorId' => 'VARCHAR(256) NOT NULL',
                'message' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'hashtag_trends' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'data' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ]
        ];

        $i = 0;
        foreach($tables as $name => $values) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $name . '` (';

            $tmp = [];

            foreach($values as $key => $value) {
                $tmp[] = $key . ' ' . $value;
            }

            $sql .= implode(', ', $tmp);

            $sql .= ')';
            
            $this->db->query($sql);
            $this->logger->sql($sql, __METHOD__, null);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' tables.', __METHOD__);
    }

    /**
     * Creates indexes
     */
    private function createIndexes() {
        $this->logger->info('Creating indexes.', __METHOD__);

        $indexes = [
            'banned_words' => [
                'word'
            ],
            'banned_words' => [
                'authorId'
            ],
            'group_membership' => [
                'userId'
            ],
            'notifications' => [
                'userId'
            ],
            'posts' => [
                'topicId',
                'dateAvailable',
                'isDeleted',
                'isSuggestable'
            ],
            'post_comments' => [
                'postId',
                'isDeleted',
                'parentCommentId'
            ],
            'post_comment_likes' => [
                'commentId',
                'userId'
            ],
            'post_file_uploads' => [
                'postId'
            ],
            'post_likes' => [
                'postId',
                'userId'
            ],
            'reports' => [
                'status'
            ],
            'topics' => [
                'title',
                'description'
            ],
            'topics' => [
                'isDeleted'
            ],
            'topic_invites' => [
                'userId',
                'dateValid'
            ],
            'topic_membership' => [
                'topicId',
                'userId'
            ],
            'topic_polls' => [
                'topicId',
                'dateValid'
            ],
            'topic_polls_responses' => [
                'pollId',
                'userId',
                'dateCreated'
            ],
            'topic_post_pins' => [
                'topicId'
            ],
            'transaction_log' => [
                'dateCreated'
            ],
            'user_following' => [
                'authorId',
                'userId'
            ],
            'user_forgotten_password_links' => [
                'userId'
            ],
            'user_prosecutions' => [
                'userId'
            ],
            'user_prosecutions_history' => [
                'prosecutionId',
                'userId'
            ],
            'user_registration_links' => [
                'userId'
            ],
            'user_suggestions' => [
                'userId'
            ],
            'user_suggestion_comments' => [
                'suggestionId',
                'userId'
            ],
            'topic_rules' => [
                'topicId'
            ],
            'topic_calendar_user_events' => [
                'dateFrom',
                'dateTo',
                'topicId'
            ],
            'topic_banned_words' => [
                'authorId',
                'topicId'
            ],
            'user_chats' => [
                'user1Id',
                'user2Id',
                'dateCreated'
            ],
            'user_chat_messages' => [
                'chatId',
                'dateCreated'
            ],
            'topic_broadcast_channels' => [
                'topicId'
            ],
            'topic_broadcast_channel_subscribers' => [
                'channelId',
                'userId'
            ],
            'topic_broadcast_channel_messages' => [
                'channelId',
                'dateCreated'
            ],
            'hashtag_trends' => [
                'dateCreated'
            ],
            'system_status' => [
                'name',
                'status'
            ]
        ];

        $indexCount = [];
        foreach($indexes as $tableName => $columns) {
            $i = 1;

            if(isset($indexCount[$tableName])) {
                $i = $indexCount[$tableName] + 1;
            }

            $name = $tableName . '_i' . $i;

            $sql = "DROP INDEX IF EXISTS `$name` ON `$tableName`";

            $this->logger->sql($sql, __METHOD__, null);

            $this->db->query($sql);

            $cols = implode(', ', $columns);

            $sql = "CREATE INDEX $name ON $tableName ($cols)";

            $this->logger->sql($sql, __METHOD__, null);

            $this->db->query($sql);

            $indexCount[$tableName] = $i;
        }

        $this->logger->info('Created indexes.', __METHOD__);
    }

    /**
     * Creates default users
     */
    private function createUsers() {
        $this->logger->info('Creating users.', __METHOD__);

        $users = [
            'admin' => 'admin',
            'service_user' => 'service_user'
        ];

        $admins = [
            'admin',
            'service_user'
        ];

        $canLoginArray = [
            'admin'
        ];

        $i = 0;
        foreach($users as $username => $password) {
            $password = password_hash($password, PASSWORD_BCRYPT);
            $userId = HashManager::createEntityId();

            $isAdmin = in_array($username, $admins) ? '1' : '0';
            $canLogin = in_array($username, $canLoginArray) ? '1' : '0';

            $sql = 'INSERT INTO `users` (`userId`, `username`, `password`, `isAdmin`, `canLogin`)
                    SELECT \'' . $userId . '\', \'' . $username . '\', \'' . $password . '\', ' . $isAdmin . ', ' . $canLogin . '
                    WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = \'' . $username . '\')';

            $this->db->query($sql);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' users.', __METHOD__);
    }

    /**
     * Creates default systems
     */
    private function createSystems() {
        $this->logger->info('Creating systems.', __METHOD__);

        $i = 0;
        foreach(Systems::getAll() as $name => $userFriendlyName) {
            $id = HashManager::createEntityId();

            $sql = 'INSERT INTO `system_status` (`systemId`, `name`, `status`)
                    SELECT \'' . $id . '\', \'' . $name . '\', 1
                    WHERE NOT EXISTS (SELECT 1 FROM `system_status` WHERE `name` = \'' . $name . '\')';

            $this->db->query($sql);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' systems.', __METHOD__);
    }

    /**
     * Creates default groups
     */
    private function createGroups() {
        $this->logger->info('Creating administrator groups.', __METHOD__);

        $groups = [
            AdministratorGroups::toString(AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR) => AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_SUGGESTION_ADMINISTRATOR) => AdministratorGroups::G_SUGGESTION_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_SUPERADMINISTRATOR) => AdministratorGroups::G_SUPERADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_SYSTEM_ADMINISTRATOR) => AdministratorGroups::G_SYSTEM_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_USER_ADMINISTRATOR) => AdministratorGroups::G_USER_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) => AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_BETA_TESTER) => AdministratorGroups::G_BETA_TESTER
        ];

        $descriptions = [
            AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR => 'Administrator group whose members manage reports and user prosecution',
            AdministratorGroups::G_SUGGESTION_ADMINISTRATOR => 'Administrator group whose members manage suggestions',
            AdministratorGroups::G_SUPERADMINISTRATOR => 'Administrator group that allows performing all operations without limit',
            AdministratorGroups::G_SYSTEM_ADMINISTRATOR => 'Administrator group whose members manage system status',
            AdministratorGroups::G_USER_ADMINISTRATOR => 'Administrator group whose members manage users',
            AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR => 'Administrator group whose members manage user content',
            AdministratorGroups::G_BETA_TESTER => 'Group of beta testers'
        ];

        foreach($groups as $title => $id) {
            $description = $descriptions[$id];

            $sql = "INSERT INTO `groups` (`groupId`, `title`, `description`)
                    SELECT '$id', '$title', '$description'
                    WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `groupId` = $id)";

            $this->db->query($sql);
        }

        $this->logger->info('Created administrator groups.', __METHOD__);
    }

    /**
     * Adds administrator to default groups
     */
    private function addAdminToGroups() {
        $this->logger->info('Adding admin to administrator groups.', __METHOD__);

        $sql = "SELECT `userId` FROM `users` WHERE `username` = 'admin'";

        $result = $this->db->query($sql);

        $userId = null;
        foreach($result as $r) {
            $userId = $r['userId'];
        }

        if($userId === null) die();

        $groups = [
            AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR,
            AdministratorGroups::G_SUGGESTION_ADMINISTRATOR,
            AdministratorGroups::G_SUPERADMINISTRATOR,
            AdministratorGroups::G_SYSTEM_ADMINISTRATOR,
            AdministratorGroups::G_USER_ADMINISTRATOR,
            AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR,
            AdministratorGroups::G_BETA_TESTER
        ];

        foreach($groups as $groupId) {
            $membershipId = HashManager::createEntityId();

            $sql = "INSERT INTO `group_membership` (`membershipId`, `userId`, `groupId`)
                    SELECT '$membershipId', '$userId', '$groupId'
                    WHERE NOT EXISTS (SELECT 1 FROM `group_membership` WHERE `membershipId` = '$membershipId' AND userId = '$userId' AND groupId = $groupId)";

            $this->db->query($sql);
        }

        $this->logger->info('Added admin to administrator groups.', __METHOD__);
    }

    /**
     * Adds system services
     */
    private function addSystemServices() {
        $this->logger->info('Adding system services.', __METHOD__);

        $services = [
            'AdminDashboardIndexing' => 'AdminDashboardIndexing.php',
            'PostLikeEqualizer' => 'PostLikeEqualizer.php',
            'OldNotificationRemoving' => 'OldNotificationRemoving.php',
            'Mail' => 'MailService.php',
            'OldRegistrationConfirmationLinkRemoving' => 'OldRegistrationRemoving.php',
            'OldGridExportCacheRemoving' => 'OldGridExportCacheRemoving.php',
            'UnlimitedGridExport' => 'UnlimitedGridExport.php',
            'HashtagTrendsIndexing' => 'HashtagTrendsIndexing.php'
        ];

        foreach($services as $title => $path) {
            $id = HashManager::createEntityId();

            $sql = "INSERT INTO `system_services` (`serviceId`, `title`, `scriptPath`)
                    SELECT '$id', '$title', '$path'
                    WHERE NOT EXISTS (SELECT 1 FROM `system_services` WHERE `serviceId` = '$id' AND title = '$title' AND scriptPath = '$path')";

            $this->db->query($sql);
        }

        $this->logger->info('Added system services.', __METHOD__);
    }
}

?>