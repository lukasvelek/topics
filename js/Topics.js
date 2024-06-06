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