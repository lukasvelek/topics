<?php

namespace App\Core\Caching;

/**
 * CacheNames contains the list of all cache namespaces used
 * 
 * @author Lukas Velek
 */
class CacheNames {
    public const USERS = 'users';
    public const USERS_USERNAME_TO_ID_MAPPING = 'usersUsernameToIdMapping';
    public const TOPIC_RULES = 'topicRules';
    public const PINNED_POSTS = 'pinnedPosts';
    public const TOPICS = 'topics';
    public const POSTS = 'posts';
    public const NOTIFICATIONS = 'notifications';
    public const GROUPS = 'groups';
    public const GROUP_MEMBERSHIPS = 'groupMemberships';
    public const FLASH_MESSAGES = 'flashMessages';
    public const CACHED_PAGES = 'cachedPages';
    public const COMMON_SEARCH_INDEX = 'commonSearchIndex';
    public const GRID_PAGE_DATA = 'gridPageData';
    public const TOPIC_MEMBERSHIPS = 'topicMemberships';
    public const GRID_EXPORT_DATA = 'gridExportData';
    public const GRID_EXPORTS = 'gridExports';
}

?>