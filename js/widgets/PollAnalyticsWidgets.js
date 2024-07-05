async function createWidgets(_pollId) {
    $("#widget1-data").html('<div id="center"><img src="resources/loading.gif" width="128"></div>');

    await sleep(100);

    $.get(
        "?page=UserModule:Topics&action=getPollAnalyticsGraphData&isAjax=1",
        {
            pollId: _pollId
        }
    ).done(function(data) {
        $("#widget1-data").html('<div style="width: 95%"><canvas id="responsesGraph"></canvas></div>');

        const obj = JSON.parse(data);

        const ctx = $("#responsesGraph");

        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: obj.labels,
                datasets: [{
                    label: "Responses",
                    data: obj.data,
                    backgroundColor: obj.colors,
                    hoverOffset: 4
                }]
            }
        })
    });
}