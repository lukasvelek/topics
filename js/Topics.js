async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

function closeFlashMessage(_id) {
    $("#" + _id).remove();
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

function openImage(_src) {
    $("#image-modal-content").html('<img src="' + _src + '" width="600px">');
    $("#image-modal").show();
    $("#image-modal-open-link").html('<a class="post-data-link" href="' + _src + '" target="_blank">Open</a>');
}

function closeImage() {
    $("#image-modal").hide();
}