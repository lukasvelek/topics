function createWidgets(_pollId) {
    $.get(
        "?page=UserModule:Topics&action=getPollAnalyticsGraphData&isAjax=1",
        {
            pollId: _pollId
        }
    ).done(function(data) {
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