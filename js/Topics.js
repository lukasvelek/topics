async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

function closeFlashMessage(_id) {
    $("#" + _id).remove();
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