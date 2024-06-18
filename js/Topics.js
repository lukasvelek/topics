async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

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

async function autoHideFlashMessage(_divId) {
    const sleepLength = 500; // 5s

    for(var s = 1; s <= sleepLength; s++) {
        $("#" + _divId + "-progress-bar").css("width", "" + (100 / sleepLength * s) + "%")
        await sleep((sleepLength / sleepLength));
    }

    closeFlashMessage(_divId);
}

function loadSuggestions(_limit, _offset, _userId, _filterType, _filterKey) {
    $.get(
        "app/ajax/Feedback.php",
        {
            limit: _limit,
            offset: _offset,
            callingUserId: _userId,
            action: 'getSuggestions',
            filterType: _filterType,
            filterKey: _filterKey
        }
    )
    .done(function( data ) {
        const obj = JSON.parse(data);

        $("#suggestion-list").append(obj.suggestions);
        $("#suggestion-list-link").html(obj.loadMoreLink);
    });
}

function loadFeedbackSuggestionComments(_suggestionId, _limit, _offset, _userId) {
    $.get(
        "app/ajax/Feedback.php",
        {
            limit: _limit,
            offset: _offset,
            callingUserId: _userId,
            action: 'getSuggestionComments',
            suggestionId: _suggestionId
        }
    )
    .done(function ( data ) {
        const obj = JSON.parse(data);

        $("#comments-content").append(obj.comments);
        $("#comments-load-more").html(obj.loadMoreLink);
    });
}

function loadReports(_limit, _offset, _userId, _filterType, _filterKey) {
    $.get(
        "app/ajax/Feedback.php",
        {
            limit: _limit,
            offset: _offset,
            callingUserId: _userId,
            action: 'getReports',
            filterType: _filterType,
            filterKey: _filterKey
        }
    )
    .done(function( data ) {
        const obj = JSON.parse(data);

        $("#report-list").append(obj.reports);
        $("#report-list-link").html(obj.loadMoreLink);
    });
}

function getUserProsecutions(_page, _userId) {
    $.get(
        "app/ajax/UserProsecutions.php",
        {
            page: _page,
            callingUserId: _userId,
            action: "getProsecutions"
        }
    )
    .done(function( data ) {
        const obj = JSON.parse(data);

        $("#grid-content").append(obj.grid);
        $("#grid-paginator").html(obj.paginator);
    });
}

function getUserProsecutionLog(_page, _userId) {
    $.get(
        "app/ajax/UserProsecutions.php",
        {
            page: _page,
            callingUserId: _userId,
            action: "getProsecutionLog"
        }
    )
    .done(function( data ) {
        const obj = JSON.parse(data);

        $("#grid-content").append(obj.grid);
        $("#grid-paginator").html(obj.paginator);
    });
}