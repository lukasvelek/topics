<?php

namespace App\Core;

use App\Constants\AdministratorGroups;
use App\Constants\SystemStatus;
use App\Logger\Logger;

class DatabaseInstaller {
    private DatabaseConnection $db;
    private Logger $logger;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->logger->setFilename('install_log');
    }

    public function install() {
        $this->logger->info('Database installation started.', __METHOD__);

        $this->createTables();
        $this->createUsers();
        $this->createSystems();
        $this->createGroups();
        $this->addAdminToGroups();
        $this->addSystemServices();

        $this->logger->info('Database installation finished.', __METHOD__);
    }

    private function createTables() {
        $this->logger->info('Creating tables.', __METHOD__);

        $tables = [
            'users' => [
                'userId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'username' => 'VARCHAR(256) NOT NULL',
                'password' => 'VARCHAR(256) NOT NULL',
                'loginHash' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'email' => 'VARCHAR(256) NULL',
                'isAdmin' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'topics' => [
                'topicId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'isDeleted' => 'INT(2) NOT NULL DEFAULT 0',
                'dateDeleted' => 'DATETIME NULL'
            ],
            'posts' => [
                'postId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'topicId' => 'INT(32) NOT NULL',
                'authorId' => 'INT(32) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'likes' => 'INT(32) NOT NULL DEFAULT 0',
                'isDeleted' => 'INT(2) NOT NULL DEFAULT 0',
                'dateDeleted' => 'DATETIME NULL'
            ],
            'post_likes' => [
                'likeId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'postId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL'
            ],
            'post_comments' => [
                'commentId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'postId' => 'INT(32) NOT NULL',
                'authorId' => 'INT(32) NOT NULL',
                'commentText' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'likes' => 'INT(32) NOT NULL DEFAULT 0',
                'parentCommentId' => 'INT(32) NULL',
                'isDeleted' => 'INT(2) NOT NULL DEFAULT 0',
                'dateDeleted' => 'DATETIME NULL'
            ],
            'post_comment_likes' => [
                'likeId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'commentId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL'
            ],
            'system_status' => [
                'systemId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'name' => 'VARCHAR(256) NOT NULL',
                'status' => 'INT(4) NOT NULL',
                'description' => 'TEXT NULL',
                'dateUpdated' => 'DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
            ],
            'user_suggestions' => [
                'suggestionId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'userId' => 'INT(32) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'category' => 'VARCHAR(256) NOT NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_suggestion_comments' => [
                'commentId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'suggestionId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL',
                'commentText' => 'TEXT NOT NULL',
                'adminOnly' => 'INT(2) NOT NULL DEFAULT 0',
                'statusChange' => 'INT(2) NOT NULL DEFAULT 0',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'reports' => [
                'reportId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'userId' => 'INT(32) NOT NULL',
                'entityId' => 'INT(32) NOT NULL',
                'entityType' => 'INT(4) NOT NULL',
                'category' => 'INT(4) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1',
                'statusComment' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_prosecutions' => [
                'prosecutionId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'userId' => 'INT(32) NOT NULL',
                'reason' => 'TEXT NOT NULL',
                'type' => 'INT(4) NOT NULL',
                'startDate' => 'DATETIME NULL DEFAULT current_timestamp()',
                'endDate' => 'DATETIME NULL'
            ],
            'user_prosecutions_history' => [
                'historyId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'prosecutionId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL',
                'commentText' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'groups' => [
                'groupId' => 'INT(32) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL'
            ],
            'group_membership' => [
                'membershipId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'groupId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'banned_words' => [
                'wordId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'word' => 'VARCHAR(256)',
                'authorId' => 'INT(32) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_membership' => [
                'membershipId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'userId' => 'INT(32) NOT NULL',
                'topicId' => 'INT(32) NOT NULL',
                'role' => 'INT(4) NOT NULL DEFAULT 1'
            ],
            'system_services' => [
                'serviceId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'title' => 'VARCHAR(256) NOT NULL',
                'scriptPath' => 'VARCHAR(256) NOT NULL',
                'dateStarted' => 'DATETIME NULL',
                'dateEnded' => 'DATETIME NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1'
            ],
            'admin_dashboard_widgets_graph_data' => [
                'dataId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'mostActiveTopics' => 'TEXT NOT NULL',
                'mostActivePosts' => 'TEXT NOT NULL',
                'mostActiveUsers' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'topic_polls' => [
                'pollId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'topicId' => 'INT(32) NOT NULL',
                'authorId' => 'INT(32) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'choices' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateValid' => 'DATETIME NULL',
                'timeElapsedForNextVote' => 'VARCHAR(256) NOT NULL'
            ],
            'topic_polls_responses' => [
                'responseId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'pollId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL',
                'choice' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ]
        ];

        $i = 0;
        foreach($tables as $name => $values) {
            $sql = 'CREATE TABLE IF NOT EXISTS ' . $name . ' (';

            $tmp = [];

            foreach($values as $key => $value) {
                $tmp[] = $key . ' ' . $value;
            }

            $sql .= implode(', ', $tmp);

            $sql .= ')';
            
            $this->db->query($sql);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' tables.', __METHOD__);
    }

    private function createUsers() {
        $this->logger->info('Creating users.', __METHOD__);

        $users = [
            'admin' => 'admin'
        ];

        $admins = [
            'admin'
        ];

        $i = 0;
        foreach($users as $username => $password) {
            $password = password_hash($password, PASSWORD_BCRYPT);

            $isAdmin = in_array($username, $admins) ? '1' : '0';

            $sql = 'INSERT INTO users (`username`, `password`, `isAdmin`)
                    SELECT \'' . $username . '\', \'' . $password . '\', ' . $isAdmin . '
                    WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = \'' . $username . '\')';

            $this->db->query($sql);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' users.', __METHOD__);
    }

    private function createSystems() {
        $this->logger->info('Creating systems.', __METHOD__);

        $systems = [
            'Core' => SystemStatus::ONLINE
        ];

        $i = 0;
        foreach($systems as $name => $status) {
            $sql = 'INSERT INTO system_status (`name`, `status`)
                    SELECT \'' . $name . '\', \'' . $status . '\'
                    WHERE NOT EXISTS (SELECT 1 FROM system_status WHERE name = \'' . $name . '\')';

            $this->db->query($sql);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' systems.', __METHOD__);
    }

    private function createGroups() {
        $this->logger->info('Creating administrator groups.', __METHOD__);

        $groups = [
            AdministratorGroups::toString(AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR) => AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_SUGGESTION_ADMINISTRATOR) => AdministratorGroups::G_SUGGESTION_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_SUPERADMINISTRATOR) => AdministratorGroups::G_SUPERADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_SYSTEM_ADMINISTRATOR) => AdministratorGroups::G_SYSTEM_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_USER_ADMINISTRATOR) => AdministratorGroups::G_USER_ADMINISTRATOR,
            AdministratorGroups::toString(AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) => AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR
        ];

        $descriptions = [
            AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR => 'Administrator group whose members manage reports and user prosecution',
            AdministratorGroups::G_SUGGESTION_ADMINISTRATOR => 'Administrator group whose members manage suggestions',
            AdministratorGroups::G_SUPERADMINISTRATOR => 'Administrator group that allows performing all operations without limit',
            AdministratorGroups::G_SYSTEM_ADMINISTRATOR => 'Administrator group whose members manage system status',
            AdministratorGroups::G_USER_ADMINISTRATOR => 'Administrator group whose members manage users',
            AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR => 'Administrator group whose members manage user content'
        ];

        foreach($groups as $title => $id) {
            $description = $descriptions[$id];

            $sql = "INSERT INTO groups (`groupId`, `title`, `description`)
                    SELECT '$id', '$title', '$description'
                    WHERE NOT EXISTS (SELECT 1 FROM groups WHERE groupId = $id)";

            $this->db->query($sql);
        }

        $this->logger->info('Created administrator groups.', __METHOD__);
    }

    private function addAdminToGroups() {
        $this->logger->info('Adding admin to administrator groups.', __METHOD__);

        $sql = "SELECT userId FROM users WHERE username = 'admin'";

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
            AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR
        ];

        foreach($groups as $groupId) {
            $sql = "INSERT INTO group_membership (`userId`, `groupId`)
                    SELECT '$userId', '$groupId'
                    WHERE NOT EXISTS (SELECT 1 FROM group_membership WHERE userId = $userId AND groupId = $groupId)";

            $this->db->query($sql);
        }

        $this->logger->info('Added admin to administrator groups.', __METHOD__);
    }

    private function addSystemServices() {
        $this->logger->info('Adding system services.', __METHOD__);

        $services = [
            'AdminDashboardIndexing' => 'AdminDashboardIndexing.php'
        ];

        foreach($services as $title => $path) {
            $sql = "INSERT INTO system_services (`title`, `scriptPath`)
                    SELECT '$title', '$path'
                    WHERE NOT EXISTS (SELECT 1 FROM system_services WHERE title = '$title' AND scriptPath = '$path')";

            $this->db->query($sql);
        }

        $this->logger->info('Added system services.', __METHOD__);
    }
}

?>