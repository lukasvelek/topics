<?php

namespace App\Constants;

class Notifications {
    public const NEW_TOPIC_INVITE = 1;
    public const NEW_POST_COMMENT = 2;
    public const NEW_POST_LIKE = 3;
    public const NEW_COMMENT_COMMENT = 4;
    public const NEW_COMMENT_LIKE = 5;

    public static function getTitleByKey(int $key) {
        return match($key) {
            self::NEW_TOPIC_INVITE => 'New topic invite',
            self::NEW_POST_COMMENT => 'New post comment',
            self::NEW_POST_LIKE => 'New post like',
            self::NEW_COMMENT_COMMENT => 'New comment comment',
            self::NEW_COMMENT_LIKE => 'New comment like'
        };
    }

    public static function getTextByKey(int $key) {
        return match($key) {
            self::NEW_TOPIC_INVITE => 'You have been invited to join topic $TOPIC_LINK$.',
            self::NEW_POST_COMMENT => 'User $AUTHOR_LINK$ commented on your post $POST_LINK$.',
            self::NEW_POST_LIKE => 'User $AUTHOR_LINK$ liked your post $POST_LINK$.',
            self::NEW_COMMENT_COMMENT => 'User $AUTHOR_LINK$ commented on your comment $POST_LINK$.',
            self::NEW_COMMENT_LIKE => 'User $AUTHOR_LINK$ liked your comment $POST_LINK$.'
        };
    }
}

?>