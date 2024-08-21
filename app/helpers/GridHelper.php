<?php

namespace App\Helpers;

use App\Core\CacheManager;
use App\Logger\Logger;

class GridHelper {
    private const NAMESPACE = 'gridPageData';

    public const GRID_TRANSACTION_LOG = 'gridTransactionLog';
    public const GRID_USERS = 'gridUsers';
    public const GRID_USER_PROSECUTION_LOG = 'gridUserProsecutionLog';
    public const GRID_USER_PROSECUTIONS = 'gridUserProsecutions';
    public const GRID_SYSTEM_SERVICES = 'gridSystemServices';
    public const GRID_POST_FILE_UPLOADS = 'gridPostFileUploads';
    public const GRID_GROUPS = 'gridGroups';
    public const GRID_DELETED_CONTENT = 'gridDeletedContent';
    public const GRID_BANNED_WORDS = 'gridBannedWords';

    private Logger $logger;
    private array $gridPageData;
    private string $currentUserId;

    public function __construct(Logger $logger, string $currentUserId) {
        $this->logger = $logger;
        $this->currentUserId = $currentUserId;

        $this->gridPageData = [];
    }

    public function getGridPage(string $gridName, int $gridPage, array $customParams = []) {
        $page = $this->loadGridPage($gridName, $customParams);

        if($page != $gridPage && $gridPage > -1) {
            $page = $gridPage;

            $this->saveGridPage($gridName, $page, $customParams);
        }

        return $page;
    }

    public function loadGridPage(string $gridName, array $customParams = []) {
        return $this->loadGridPageData($gridName, $customParams);
    }

    public function saveGridPage(string $gridName, int $page, array $customParams = []) {
        return $this->saveGridPageData($gridName, $page, $customParams);
    }

    private function loadGridPageData(string $gridName, array $customParams) {
        $key = $this->createCacheKey($gridName, $customParams);

        if(!array_key_exists($key, $this->gridPageData)) {
            $cm = new CacheManager($this->logger);

            $page = $cm->loadCache($key, function() {
                return 0;
            }, self::NAMESPACE, __METHOD__);

            $this->gridPageData[$key] = $page;
        }

        $this->logger->info(sprintf('Loaded page for grid \'%s\': %d.', $key, $this->gridPageData[$key]), __METHOD__);

        return $this->gridPageData[$key];
    }

    private function saveGridPageData(string $gridName, int $page, array $customParams) {
        $key = $this->createCacheKey($gridName, $customParams);

        $this->gridPageData[$key] = $page;

        $cm = new CacheManager($this->logger);

        return $cm->saveCache($key, function() use ($page) {
            return $page;
        }, self::NAMESPACE, __METHOD__);
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