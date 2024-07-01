function createDashboard() {
    $.get(
        "?page=AdminModule:Home&action=getGraphData&isAjax=1"
    ).done(function(data) {
        const obj = JSON.parse(data);

        const ctxTopics = $("#mostActiveTopicsGraph");

        if(obj.topics.error) {
            $("#widget1-data").html(obj.topics.error);
        } else {
            new Chart(ctxTopics, {
                type: "bar",
                data: {
                    labels: obj.topics.labels,
                    datasets: [{
                        label: "# of posts created in the last 24 hrs",
                        data: obj.topics.data,
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

        const ctxPosts = $("#mostActivePostsGraph");

        if(obj.posts.error) {
            $("#widget2-data").html(obj.posts.error);
        } else {
            new Chart(ctxPosts, {
                type: "bar",
                data: {
                    labels: obj.posts.labels,
                    datasets: [{
                        label: "# of comments created in the last 24 hrs",
                        data: obj.posts.data,
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

        const ctxUsers = $("#mostActiveUsersGraph");

        if(obj.users.error) {
            $("#widget3-data").html(obj.users.error);
        } else {
            new Chart(ctxUsers, {
                type: "bar",
                data: {
                    labels: obj.users.labels,
                    datasets: [{
                        label: "# of comments created in the last 24 hrs",
                        data: obj.users.data,
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