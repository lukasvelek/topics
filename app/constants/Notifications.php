<?php

namespace App\Constants;

class Notifications {
    public const NEW_TOPIC_INVITE = 1;
    public const NEW_POST_COMMENT = 2;
    public const NEW_POST_LIKE = 3;
    public const NEW_COMMENT_COMMENT = 4;
    public const NEW_COMMENT_LIKE = 5;
    public const POST_DELETE = 6;
    public const COMMENT_DELETE = 7;
    public const POST_DELETE_DUE_TO_REPORT = 8;
    public const COMMENT_DELETE_DUE_TO_REPORT = 9;
    public const TOPIC_DELETE_DUE_TO_REPORT = 10;
    public const TOPIC_DELETE = 11;
    public const TOPIC_ROLE_CHANGE = 12;
    public const NEW_USER_FOLLOWER = 13;
    public const GRID_EXPORT_FINISHED = 14;

    public static function getTitleByKey(int $key) {
        return match($key) {
            self::NEW_TOPIC_INVITE => 'New topic invite',
            self::NEW_POST_COMMENT => 'New post comment',
            self::NEW_POST_LIKE => 'New post like',
            self::NEW_COMMENT_COMMENT => 'New comment comment',
            self::NEW_COMMENT_LIKE => 'New comment like',
            self::POST_DELETE => 'Post deleted',
            self::COMMENT_DELETE => 'Comment deleted',
            self::TOPIC_DELETE => 'Topic deleted',
            self::POST_DELETE_DUE_TO_REPORT => 'Post deleted due to being reported',
            self::COMMENT_DELETE_DUE_TO_REPORT => 'Comment deleted due to being reported',
            self::TOPIC_DELETE_DUE_TO_REPORT => 'Topic deleted due to being reported',
            self::TOPIC_ROLE_CHANGE => 'Topic role changed',
            self::NEW_USER_FOLLOWER => 'New follower',
            self::GRID_EXPORT_FINISHED => 'Grid export finished'
        };
    }

    public static function getTextByKey(int $key) {
        return match($key) {
            self::NEW_TOPIC_INVITE => 'You have been invited to join topic $TOPIC_LINK$. You can find and manage your invites $INVITATIONS_LINK$.',
            self::NEW_POST_COMMENT => 'User $AUTHOR_LINK$ commented on your post $POST_LINK$.',
            self::NEW_POST_LIKE => 'User $AUTHOR_LINK$ liked your post $POST_LINK$.',
            self::NEW_COMMENT_COMMENT => 'User $AUTHOR_LINK$ commented on your comment on post $POST_LINK$.',
            self::NEW_COMMENT_LIKE => 'User $AUTHOR_LINK$ liked your comment $POST_LINK$.',
            self::TOPIC_DELETE => 'Your topic $TOPIC_LINK$ has been deleted by $USER_LINK$.',
            self::POST_DELETE => 'Your post $POST_LINK$ has been deleted by $USER_LINK$.',
            self::COMMENT_DELETE => 'Your comment on post $POST_LINK$ has been deleted by $USER_LINK$.',
            self::TOPIC_DELETE_DUE_TO_REPORT => 'Your topic $TOPIC_LINK$ has been reported and then deleted for reason: "$DELETE_REASON$" by user $USER_LINK$.',
            self::POST_DELETE_DUE_TO_REPORT => 'Your post $POST_LINK$ has been reported and then deleted for reason: "$DELETE_REASON$" by user $USER_LINK$.',
            self::COMMENT_DELETE_DUE_TO_REPORT => 'Your comment on post $POST_LINK$ has been reported and then deleted for reason: "$DELETE_REASON$" by user $USER_LINK$.',
            self::TOPIC_ROLE_CHANGE => 'Your role in topic $TOPIC_LINK$ has changed from $OLD_ROLE$ to $NEW_ROLE$.',
            self::NEW_USER_FOLLOWER => 'User $USER_LINK$ started following you.',
            self::GRID_EXPORT_FINISHED => 'Your grid export is finished. Download it $DOWNLOAD_LINK$.'
        };
    }
}

?>