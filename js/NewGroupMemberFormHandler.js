function searchUsers(_userId, _groupId) {
    const _username = $("#usernameSearch").val();

    $.get(
        "app/ajax/Users.php",
        {
            action: "searchUsersByUsernameForSelectForNewGroupMember",
            query: _username,
            callingUserId: _userId,
            groupId: _groupId
        }
    )
    .done(function( data ) {
        const obj = JSON.parse(data);

        if(obj.count == 0) {
            alert('No users found.');
        }

        $("#user").html(obj.users);
    });
}