<?php

namespace App\Core;

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

        $this->logger->info('Database installation finished.', __METHOD__);
    }

    private function createTables() {
        $this->logger->info('Creating tables.', __METHOD__);

        $tables = [
            'users' => [
                'userId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'username' => 'VARCHAR(256) NOT NULL',
                'password' => 'VARCHAR(256) NOT NULL',
                'loginHash' => 'VARCHAR(256) NULL'
            ],
            'topics' => [
                'topicId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'managerId' => 'INT(32) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_topic_follows' => [
                'followId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'topicId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL'
            ],
            'posts' => [
                'postId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'topicId' => 'INT(32) NOT NULL',
                'authorId' => 'INT(32) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'likes' => 'INT(32) NOT NULL DEFAULT 0'
            ],
            'post_likes' => [
                'likeId' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'postId' => 'INT(32) NOT NULL',
                'userId' => 'INT(32) NOT NULL'
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

        $i = 0;
        foreach($users as $username => $password) {
            $password = password_hash($password, PASSWORD_BCRYPT);

            $sql = 'INSERT INTO users (`username`, `password`)
                    SELECT \'' . $username . '\', \'' . $password . '\'
                    WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = \'' . $username . '\')';

            $this->db->query($sql);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' users.', __METHOD__);
    }
}

?>