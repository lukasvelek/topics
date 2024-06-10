function closeFlashMessage(_id) {
    $("#" + _id).remove();
}

function loadPostsForTopic(_topicId, _limit, _offset, _userId) {
    $.get(
        "app/ajax/Posts.php",
        {
            topicId: _topicId,
            limit: _limit,
            offset: _offset,
            action: 'loadPostsForTopic',
            callingUserId: _userId
        }
    )
    .done(function( data ) {
        const obj = JSON.parse(data);

        $("#post-list").append(obj.posts);
        $("#post-list-link").html(obj.loadMoreLink);
    });
}

function likePost(_postId, _userId, _like) {
    $.post(
        "app/ajax/Posts.php",
        {
            postId: _postId,
            userId: _userId,
            action: "likePost",
            callingUserId: _userId,
            like: _like
        },
        function(data) {
            var obj = JSON.parse(data);
            $("#post-" + _postId + "-link").html(obj.link);
            $("#post-" + _postId + "-likes").html(obj.likes);
        }
    )
}

function loadCommentsForPost(_postId, _limit, _offset, _userId) {
    $.get(
        "app/ajax/Posts.php",
        {
            postId: _postId,
            limit: _limit,
            offset: _offset,
            callingUserId: _userId,
            action: 'loadCommentsForPost'
        }
    )
    .done(function ( data ) {
        var obj = JSON.parse(data);

        $("#comments-list").append(obj.posts);
        $("#comments-list-link").html(obj.loadMoreLink);
    });
}

function likePostComment(_commentId, _userId, _like) {
    $.post(
        "app/ajax/Posts.php",
        {
            commentId: _commentId,
            userId: _userId,
            action: "likePostComment",
            callingUserId: _userId,
            like: _like
        }
    )
    .done(function ( data ) {
        var obj = JSON.parse(data);

        $("#post-comment-" + _commentId + "-link").html(obj.link);
        $("#post-comment-" + _commentId + "-likes").html(obj.likes);
    });
}

function createNewCommentForm(_commentId, _userId, _postId) {
    const divId = "#post-comment-" + _commentId + "-comment-form";
    const linkId = "#post-comment-" + _commentId + "-add-comment-link";

    $.get(
        "app/ajax/Posts.php",
        {
            callingUserId: _userId,
            parentCommentId: _commentId,
            postId: _postId,
            action: "createNewPostCommentForm"
        }
    )
    .done(function ( data ) {
        const obj = JSON.parse(data);

        $(divId).html(obj.code);
        $(linkId).attr('onclick', 'hideNewCommentForm(' + _commentId + ', ' + _userId + ', ' + _postId + ')')
        $(linkId).html('Hide comment form');
    });
}

function hideNewCommentForm(_commentId, _userId, _postId) {
    const divId = "#post-comment-" + _commentId + "-comment-form";
    const linkId = "#post-comment-" + _commentId + "-add-comment-link";

    $(divId).html("");
    $(linkId).attr('onclick', 'createNewCommentForm(' + _commentId + ', ' + _userId + ', ' + _postId + ')');
    $(linkId).html("Add comment");
}