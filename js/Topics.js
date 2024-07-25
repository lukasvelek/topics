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
    $("#image-modal-content").html('<img src="' + _src + '" class="limited">');
    $("#image-modal").show();
    $("#image-modal-open-link").html('<a class="post-data-link" href="' + _src + '" target="_blank" style="font-size: 22px">Open</a>');
    $("#image-modal-close-link").html('<a class="post-data-link" style="font-size: 22px" href="#" onclick="closeImage()">Close</a>');
}

function openImagePostLister(_src, _id) {
    $("#image-modal-content").html('<img src="' + _src + '" class="limited">');
    $("#image-modal").show();
    $("#image-modal-open-link").html('<a class="post-data-link" href="' + _src + '" target="_blank" style="font-size: 22px">Open</a>');
    $("#image-modal-close-link").html('<a class="post-data-link" style="font-size: 22px" href="#post-' + _id + '" onclick="closeImage()">Close</a>');
}

function closeImage() {
    $("#image-modal").hide();
}

function changeImage(_postId, _id, _maxId) {
    const json = $("#post-" + _postId + "-image-preview-json").html();
    const images = JSON.parse(json); // is an array, so values can be accessed using []

    if(_id > 0) {
        $("#post-" + _postId + "-image-preview-left-button").html('<a href="#post-' + _postId + '" class="post-image-browser-link" onclick="changeImage(' + _postId + ', ' + (_id - 1) + ', ' + _maxId + ')">&larr;</a>');
    } else {
        $("#post-" + _postId + "-image-preview-left-button").html('');
    }

    if(_maxId == _id) {
        $("#post-" + _postId + "-image-preview-right-button").html('');
    } else {
        $("#post-" + _postId + "-image-preview-right-button").html('<a href="#post-' + _postId + '" class="post-image-browser-link" onclick="changeImage(' + _postId + ', ' + (_id + 1) + ', ' + _maxId + ')">&rarr;</a>');
    }

    var path = images[_id]; 

    $("#post-" + _postId + "-image-preview").html('<a href="#post-' + _postId + '" onclick="openImagePostLister(\'' + path + '\', ' + _postId + ')"><img src="' + path + '" class="limited"></a>');
}