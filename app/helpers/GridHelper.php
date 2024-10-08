<?php

namespace App\Helpers;

use App\Core\Caching\Cache;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Logger\Logger;

class GridHelper {
    /** AdminModule:Manage* */
    public const GRID_TRANSACTION_LOG = 'gridTransactionLog';
    public const GRID_USERS = 'gridUsers';
    public const GRID_USER_PROSECUTION_LOG = 'gridUserProsecutionLog';
    public const GRID_USER_PROSECUTIONS = 'gridUserProsecutions';
    public const GRID_SYSTEM_SERVICES = 'gridSystemServices';
    public const GRID_POST_FILE_UPLOADS = 'gridPostFileUploads';
    public const GRID_GROUPS = 'gridGroups';
    public const GRID_DELETED_CONTENT = 'gridDeletedContent';
    public const GRID_BANNED_WORDS = 'gridBannedWords';
    public const GRID_GRID_EXPORTS = 'gridGridExports';
    public const GRID_EMAIL_QUEUE = 'gridEmailQueue';
    /** AdminModule:Feedback* */
    public const GRID_SUGGESTIONS = 'gridSuggestions';
    public const GRID_REPORTS = 'gridReports';
    /** UserModule:TopicInvites */
    public const GRID_TOPIC_INVITES = 'gridTopicInvites';
    /** UserModule:TopicManagement */
    public const GRID_USER_TOPIC_ROLES = 'gridUserTopicRoles';
    public const GRID_TOPIC_POLLS = 'gridTopicPolls';
    public const GRID_TOPIC_INVITES_ALL = 'gridTopicInvitesAll';
    public const GRID_TOPIC_FOLLOWERS = 'gridTopicFollowers';
    public const GRID_TOPIC_BANNED_WORDS = 'gridTopicBannedWords';
    /** UserModule:Topics */
    public const GRID_TOPIC_POSTS = 'gridTopicPosts';
    public const GRID_TOPIC_POST_CONCEPTS = 'gridTopicPostConcepts';

    private Logger $logger;
    private array $gridPageData;
    private string $currentUserId;
    private CacheFactory $cacheFactory;

    private Cache $gridPageDataCache;

    public function __construct(Logger $logger, string $currentUserId) {
        $this->logger = $logger;
        $this->currentUserId = $currentUserId;

        $this->gridPageData = [];

        $this->cacheFactory = new CacheFactory($this->logger->getCfg());
        $this->gridPageDataCache = $this->cacheFactory->getCache(CacheNames::GRID_PAGE_DATA);
    }

    public function getGridPage(string $gridName, int $gridPage, array $customParams = []) {
        $page = $this->loadGridPageData($gridName, $customParams);

        if($page != $gridPage && $gridPage > -1) {
            $page = $gridPage;

            $this->saveGridPageData($gridName, $page, $customParams);
        }

        return $page;
    }

    private function loadGridPageData(string $gridName, array $customParams) {
        $key = $this->createCacheKey($gridName, $customParams);

        if(!array_key_exists($key, $this->gridPageData)) {
            $page = $this->gridPageDataCache->load($key, function() { return 0; });

            $this->gridPageData[$key] = $page;
        }

        $this->logger->info(sprintf('Loaded page for grid \'%s\': %d.', $key, $this->gridPageData[$key]), __METHOD__);

        return $this->gridPageData[$key];
    }

    private function saveGridPageData(string $gridName, int $page, array $customParams) {
        $key = $this->createCacheKey($gridName, $customParams);

        $this->gridPageData[$key] = $page;

        return $this->gridPageDataCache->save($key, function() use ($page) { return $page; });
    }

    private function createCacheKey(string $gridName, array $customParams) {
        if(!empty($customParams)) {
            return $this->currentUserId . '_' . $gridName . '_' . implode('-', $customParams);
        } else {
            return $this->currentUserId . '_' . $gridName;
        }
    }
}

?>