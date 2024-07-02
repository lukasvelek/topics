function createWidgets() {
    $.get(
        "?page=AdminModule:Feedback&action=getGraphData&isAjax=1"
    ).done(function ( data ) {
        const obj = JSON.parse(data);

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
    });
}