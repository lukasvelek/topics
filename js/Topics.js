/**
 * Sleeps
 * @param {number} ms Milliseconds to sleep
 * @returns {Promise}
 */
async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

/**
 * Closes a flash message
 * @param {string} _id 
 */
function closeFlashMessage(_id) {
    $("#" + _id).remove();
}

/**
 * Creates a new comment form
 * @param {string} _commentId 
 * @param {string} _userId 
 * @param {string} _postId 
 */
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

/**
 * Hides the newly created comment form
 * @param {string} _commentId 
 * @param {string} _userId 
 * @param {string} _postId 
 */
function hideNewCommentForm(_commentId, _userId, _postId) {
    const divId = "#post-comment-" + _commentId + "-comment-form";
    const linkId = "#post-comment-" + _commentId + "-add-comment-link";

    $(divId).html("");
    $(linkId).attr('onclick', 'createNewCommentForm(' + _commentId + ', ' + _userId + ', ' + _postId + ')');
    $(linkId).html("Add comment");
}

/**
 * Automatically hides a flash message
 * @param {string} _divId
 * @param {number} _length 
 */
async function autoHideFlashMessage(_divId, _length) {
    const sleepLength = (_length * 100); // 5s

    for(var s = 1; s <= sleepLength; s++) {
        $("#" + _divId + "-progress-bar").css("width", "" + (100 / sleepLength * s) + "%")
        await sleep((sleepLength / sleepLength));
    }

    closeFlashMessage(_divId);
}

/**
 * Opens image in a image modal
 * @param {string} _src 
 */
function openImage(_src) {
    $("#image-modal-content").html('<img src="' + _src + '" class="limited">');
    $("#image-modal").show();
    $("#image-modal-open-link").html('<a class="post-data-link" href="' + _src + '" target="_blank" style="font-size: 22px">Open</a>');
    $("#image-modal-close-link").html('<a class="post-data-link" style="font-size: 22px" href="#" onclick="closeImage()">Close</a>');
}

/**
 * Opens image in a image modal for PostLister
 * @param {string} _src 
 * @param {string} _id 
 */
function openImagePostLister(_src, _id) {
    $("#image-modal-content").html('<img src="' + _src + '" class="limited">');
    $("#image-modal").show();
    $("#image-modal-open-link").html('<a class="post-data-link" href="' + _src + '" target="_blank" style="font-size: 22px">Open</a>');
    $("#image-modal-close-link").html('<a class="post-data-link" style="font-size: 22px" href="#post-' + _id + '" onclick="closeImage()">Close</a>');
}

/**
 * Closes image
 */
function closeImage() {
    $("#image-modal").hide();
}

/**
 * Changes image
 * @param {string} _postId 
 * @param {number} _id 
 * @param {number} _maxId 
 */
function changeImage(_postId, _id, _maxId) {
    const json = $("#post-" + _postId + "-image-preview-json").html();
    const images = JSON.parse(json); // is an array, so values can be accessed using []

    if(_id > 0) {
        $("#post-" + _postId + "-image-preview-left-button").html('<button class="post-image-browser-link" type="button" onclick="changeImage(' + _postId + ', ' + (_id - 1) + ', ' + _maxId + ')">&larr;</button>');
    } else {
        $("#post-" + _postId + "-image-preview-left-button").html('');
    }

    if(_maxId == _id) {
        $("#post-" + _postId + "-image-preview-right-button").html('');
    } else {
        $("#post-" + _postId + "-image-preview-right-button").html('<button class="post-image-browser-link" type="button" onclick="changeImage(' + _postId + ', ' + (_id + 1) + ', ' + _maxId + ')">&rarr;</button>');
    }

    const path = images[_id];

    $("#post-" + _postId + "-image-preview").html('<a href="#post-' + _postId + '" onclick="openImagePostLister(\'' + path + '\', ' + _postId + ')"><img id="post-' + _postId + '-image-preview-source" src="' + path + '" class="limited"></a>');
}

/**
 * Exports grid
 * @param {string} _dataId 
 * @param {string} _gridName 
 */
async function exportGrid(_dataId, _gridName) {
    const _exportAll = confirm('Export all?');

    $.get(
        "?page=UserModule:GridExportHelper&action=exportGrid&isAjax=1",
        {
            hash: _dataId,
            exportAll: _exportAll,
            gridName: _gridName
        }
    )
    .done(async function(data) {
        const obj = JSON.parse(data);

        if(obj.empty == "0") {
            window.open(obj.path, "_blank");
        } else if(obj.empty == "async") {
            alert("Export will be processed using background service.");
        } else {
            alert("Could not export data from table.");
        }
    });
}

/**
 * Posts a post comment
 * @param {string} _postId 
 * @param {string} _parentCommentId 
 */
async function sendPostComment(_postId, _parentCommentId) {
    let _tmp = "postCommentText";
    if(_parentCommentId) {
        _tmp = _tmp + "-" + _parentCommentId;
    }
    const _text = $("#" + _tmp).val();
    $("#formSubmit").attr('disabled', 'true');

    await sleep(100);

    $.get(
        "?page=UserModule:Posts&action=asyncPostComment&isAjax=1&postId=" + _postId,
        {
            text: _text,
            parentCommentId: _parentCommentId
        }
    )
    .done(async function(data) {
        try {
            const obj = JSON.parse(data);

            if(!obj.comment) {
                if(obj.errorMsg) {
                    alert(obj.errorMsg);
                }
            } else {
                const comment = obj.comment;

                if(obj.parentComment) {
                    $("#post-comment-child-comments-" + _parentCommentId).prepend(comment);
                } else {
                    if(obj.commentCount && obj.commentCount == 1) {
                        $("#post-comments").html(comment);
                    } else {
                        $("#post-comments").prepend(comment + "<br>");
                    }
                }

                $("#" + _tmp).val("");
                if(_parentCommentId) {
                    $("#post-comment-" + _parentCommentId + "-comment-form").html("");
                }
            }

            $("#formSubmit").removeAttr('disabled');
        } catch (error) {
            alert("Could not load data");
        }
    });
}