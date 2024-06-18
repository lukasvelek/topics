function getUsers(_page, _userId) {
    $.get(
        "app/ajax/Users.php",
        {
            page: _page,
            callingUserId: _userId,
            action: 'getUsersGrid'
        }
    )
    .done(function ( data ) {
        const obj = JSON.parse(data);

        $("#grid-content").html(obj.grid);
        $("#grid-paginator").html(obj.paginator);
    });
}