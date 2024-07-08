$("#userSelect").attr('disabled', true);

function searchUser() {
    const username = $("#username").val();
    const _topicId = $("#topicId").val();

    $.get(
        "?page=UserModule:TopicManagement&action=searchUser&isAjax=1",
        {
            query: username,
            topicId: _topicId
        }
    )
    .done(function ( data ) {
        const obj = JSON.parse(data);

        if(obj.empty == false) {
            $("#userSelect").html(obj.users);
            $("#userSelect").removeAttr('disabled');
        } else {
            alert("No users found.");
        }
    });
}