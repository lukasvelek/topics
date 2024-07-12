async function createWidgets() {
    $("#widget1").html("Suggestions<div style=\"width: 75%\" id=\"center\"><img src=\"resources/loading.gif\" width=\"64\"></div>");
    $("#widget2").html("Reports<div style=\"width: 75%\" id=\"center\"><img src=\"resources/loading.gif\" width=\"64\"></div>");
    $("#widget3").html("Suggestion categories<div style=\"width: 75%\" id=\"center\"><img src=\"resources/loading.gif\" width=\"64\"></div>");

    await sleep(100);

    $.get(
        "?page=AdminModule:Feedback&action=getGraphData&isAjax=1"
    ).done(function ( data ) {
        const obj = JSON.parse(data);

        if(obj.suggestions.error) {
            $("#widget1").html("Suggestions<div style=\"width: 75%\">" + obj.suggestions.error + "</div>");
        } else {
            $("#widget1").html("Suggestions<div style=\"width: 75%\"><canvas id=\"suggestionWidgetGraph\"></canvas></div>");

            const ctxSuggestions = $("#suggestionWidgetGraph");

            new Chart(ctxSuggestions, {
                type: "bar",
                data: {
                    labels: ["All", "Open", "Closed"],
                    datasets: [{
                        label: "# of suggestions",
                        data: [obj.suggestions.all, obj.suggestions.open, obj.suggestions.closed],
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

        if(obj.reports.error) {
            $("#widget2").html("Reports<div style=\"width: 75%\">" + obj.reports.error + "</div>");
        } else {
            $("#widget2").html("Reports<div style=\"width: 75%\"><canvas id=\"reportWidgetGraph\"></canvas></div>");

            const ctxReports = $("#reportWidgetGraph");

            new Chart(ctxReports, {
                type: "bar",
                data: {
                    labels: ["All", "Open", "Closed"],
                    datasets: [{
                        label: "# of reports",
                        data: [obj.reports.all, obj.reports.open, obj.reports.closed],
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

        if(obj.suggestionCategories.error) {
            $("#widget3").html("Suggestion categories<div style=\"width: 75%\">" + obj.suggestionCategories.error + "</div>");
        } else {
            $("#widget3").html("Suggestion categories<div style=\"width: 75%\"><canvas id=\"suggestionCategoriesWidgetGraph\"></canvas></div>");

            const ctxSuggestionCategories = $("#suggestionCategoriesWidgetGraph");

            new Chart(ctxSuggestionCategories, {
                type: "doughnut",
                data: {
                    labels: obj.suggestionCategories.labels,
                    datasets: [{
                        label: "Categories",
                        data: obj.suggestionCategories.data,
                        backgroundColor: obj.suggestionCategories.colors,
                        hoverOffset: 4
                    }]
                }
            });
        }
    });
}