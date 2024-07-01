function mostActiveTopicsGraph() {
    $.get(
        "?page=AdminModule:Home&action=getMostActiveTopicsGraphData&isAjax=1"
    ).done(function(data) {
        const obj = JSON.parse(data);
        const ctx = $("#mostActiveTopicsGraph");

        if(obj.error) {
            $("#widget1-data").html(obj.error);
        } else {
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: obj.labels,
                    datasets: [{
                        label: "# of posts created in the last 24 hrs",
                        data: obj.data,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
}

function mostActivePostsGraph() {
    $.get(
        "?page=AdminModule:Home&action=getMostActivePostsGraphData&isAjax=1"
    ).done(function(data) {
        const obj = JSON.parse(data);
        const ctx = $("#mostActivePostsGraph");

        if(obj.error) {
            $("#widget2-data").html(obj.error);
        } else {
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: obj.labels,
                    datasets: [{
                        label: "# of comments created in the last 24 hrs",
                        data: obj.data,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
}