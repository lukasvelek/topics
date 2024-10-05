async function createWidgets(_pollId) {
    $("#widget1-data").html('<div id="center"><img src="resources/loading.gif" width="128"></div>');

    await sleep(100);

    $.get(
        "?page=UserModule:Topics&action=getPollAnalyticsGraphData&isAjax=1",
        {
            pollId: _pollId
        }
    ).done(function(data) {
        $("#widget1-data").html('<div style="width: 50%; margin: auto"><canvas id="responsesGraph"></canvas></div>');

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

        $("#poll-total-count").html(obj.totalCount);
    });
}

async function autoRefreshWidgets(_pollId) {
    await createWidgets(_pollId);

    $("#poll-auto-refresh").html("Next refresh in: 1 min");

    const timeToWait = 60;

    for(let x = 0; x < timeToWait; x++) {
        $("#poll-auto-refresh").html("Next refresh in: " + (timeToWait - x) + " sec");
        await sleep(1000);
    }

    await autoRefreshWidgets(_pollId);
}