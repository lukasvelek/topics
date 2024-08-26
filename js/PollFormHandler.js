$("#timeElapsedSelect").on("change", async function() {
    const val = $("#timeElapsedSelect").val();

    if(val == "never") {
        await hideSubselect();
    } else {
        await showSubselect(val);
    }
});

async function showSubselect(_val) {
    $.get("?page=UserModule:Topics&action=pollFormHandler&action2=getTimeBetweenVotesSubselect&isAjax=1",
        {
            value: _val
        }
    )
    .done(async function(data) {
        const obj = JSON.parse(data);

        if(obj.empty == "0") {
            await hideSubselect();
        } else {
            $("#timeElapsedSubselectSection").removeAttr("style");
            $("#timeElapsedSubselectSection").html(obj.select);
        }
    });
}

async function hideSubselect() {
    $("#timeElapsedSubselectSection").hide();
}