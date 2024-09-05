$("#dateAvailable").css("height", "0");
$("#dateAvailable").css("visibility", "hidden");
$("#dateAvailableBr").html("");

$("#availableNow").on("change", function () {
    if(!this.checked) {
        $("#dateAvailable").removeAttr("style");
        $("#dateAvailableBr").append("<br><br>");
    } else {
        $("#dateAvailable").css("height", "0");
        $("#dateAvailable").css("visibility", "hidden");
        $("#dateAvailableBr").html("");
    }
});