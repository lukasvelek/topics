var val = $("#type").val();

if(val == "2") {
    $("#startDate").show();
    $("#endDate").show();
} else {
    $("#startDate").hide();
    $("#endDate").hide();
}

$("#type").on("change", function() {
    if($("#type").val() == "2") {
        $("#span_startDate").show();
        $("#span_endDate").show();
    } else {
        $("#span_startDate").hide();
        $("#span_endDate").hide();
    }
});