function suggestionWidget() {
    var jsonObj = null;

    $.get(
        "?page=AdminModule:Feedback&action=getSuggestionGraphWidgetData&isAjax=1"
    ).done(function (data) {
        jsonObj = JSON.parse(data);

        const ctx = $("#suggestionWidgetGraph");

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["All", "Open", "Closed"],
                datasets: [{
                    label: "# of suggestions",
                    data: [jsonObj.all, jsonObj.open, jsonObj.closed],
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
    });
}

function reportWidget() {
    var jsonObj = null;

    $.get(
        "?page=AdminModule:Feedback&action=getReportGraphWidgetData&isAjax=1"
    ).done(function (data) {
        jsonObj = JSON.parse(data);

        const ctx = $("#reportWidgetGraph");

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["All", "Open", "Closed"],
                datasets: [{
                    label: "# of reports",
                    data: [jsonObj.all, jsonObj.open, jsonObj.closed],
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
    });
}

function suggestionCategoriesWidget() {
    var jsonObj = null;

    $.get(
        "?page=AdminModule:Feedback&action=getSuggestionCategoriesGraphData&isAjax=1"
    ).done(function (data) {
        jsonObj = JSON.parse(data);

        const ctx = $("#suggestionCategoriesWidgetGraph");

        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: jsonObj.labels,
                datasets: [{
                    label: "Categories",
                    data: jsonObj.data,
                    backgroundColor: jsonObj.colors,
                    hoverOffset: 4
                }]
            }
        });
    });
}