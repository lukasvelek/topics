const PASSWORD_MISMATCH_TEXT = 'Passwords do not match.';
const USERNAME_LENGTH_TEXT = 'Username must be at least 8 characters long.';

$("#password").on('change', function() {
    if(!checkPassword()) {
        $("#formSubmit").attr('disabled', 'true');
        $("#password").css("border", "1px solid red");
        $("#passwordCheck").css("border", "1px solid red");

        $("#password").attr('title', PASSWORD_MISMATCH_TEXT);
        $("#passwordCheck").attr('title', PASSWORD_MISMATCH_TEXT);
    } else {
        $("#formSubmit").removeAttr('disabled');
        $("#password").css("border", "1px solid black");
        $("#passwordCheck").css("border", "1px solid black");

        $("#password").removeAttr('title');
        $("#passwordCheck").removeAttr('title');
    }
});

$("#passwordCheck").on('change', function() {
    if(!checkPassword()) {
        $("#formSubmit").attr('disabled', 'true');
        $("#password").css("border", "1px solid red");
        $("#passwordCheck").css("border", "1px solid red");

        $("#password").attr('title', PASSWORD_MISMATCH_TEXT);
        $("#passwordCheck").attr('title', PASSWORD_MISMATCH_TEXT);
    } else {
        $("#formSubmit").removeAttr('disabled');
        $("#password").css("border", "1px solid black");
        $("#passwordCheck").css("border", "1px solid black");

        $("#password").removeAttr('title');
        $("#passwordCheck").removeAttr('title');
    }
});

$("#username").on('change', function() {
    if(!checkUsernameLength()) {
        $("#username").css("border", "1px solid red");
        $("#formSubmit").attr('disabled', 'true');
        $("#username").attr('title', USERNAME_LENGTH_TEXT);
    } else {
        $("#username").css("border", "1px solid black");
        $("#formSubmit").removeAttr('disabled');
        $("#username").removeAttr('title');
    }
});

// FUNCTIONS

function checkPassword() {
    if($("#password").val() == $("#passwordCheck").val()) {
        return true;
    } else {
        return false;
    }
}

function checkUsernameLength() {
    if($("#username").val().length < 8) {
        return false;
    } else {
        return true;
    }
}