$("#password").on('change', function() {
    if(!checkPassword()) {
        $("#formSubmit").attr('disabled', 'true');
        $("#password").css("border", "1px solid red");
        $("#passwordCheck").css("border", "1px solid red");
    } else {
        $("#formSubmit").removeAttr('disabled');
        $("#password").css("border", "1px solid black");
        $("#passwordCheck").css("border", "1px solid black");
    }
});

$("#passwordCheck").on('change', function() {
    if(!checkPassword()) {
        $("#formSubmit").attr('disabled', 'true');
        $("#password").css("border", "1px solid red");
        $("#passwordCheck").css("border", "1px solid red");
    } else {
        $("#formSubmit").removeAttr('disabled');
        $("#password").css("border", "1px solid black");
        $("#passwordCheck").css("border", "1px solid black");
    }
});

function checkPassword() {
    if($("#password").val() == $("#passwordCheck").val()) {
        return true;
    } else {
        return false;
    }
}