let topicsChart = null;
let postsChart = null;
let usersChart = null;

async function createDashboard() {
    $("#widget1-data").html('<div id=\"center\"><img src="resources/loading.gif" width="128"></div>');
    $("#widget2-data").html('<div id=\"center\"><img src="resources/loading.gif" width="128"></div>');
    $("#widget3-data").html('<div id=\"center\"><img src="resources/loading.gif" width="128"></div>');

    await sleep(100);

    $.get(
        "?page=AdminModule:Home&action=getGraphData&isAjax=1"
    ).done(function(data) {
        const obj = JSON.parse(data);

        
        if(obj.topics.error) {
            $("#widget1-data").html(obj.topics.error);
        } else {
            $("#widget1-data").html('<div style="width: 95%"><canvas id="mostActiveTopicsGraph"></canvas></div>');

            const ctxTopics = $("#mostActiveTopicsGraph");

            topicsChart = new Chart(ctxTopics, {
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

        
        if(obj.posts.error) {
            $("#widget2-data").html(obj.posts.error);
        } else {
            $("#widget2-data").html('<div style="width: 95%"><canvas id="mostActivePostsGraph"></canvas></div>');

            const ctxPosts = $("#mostActivePostsGraph");

            postsChart = new Chart(ctxPosts, {
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

        
        if(obj.users.error) {
            $("#widget3-data").html(obj.users.error);
        } else {
            $("#widget3-data").html('<div style="width: 95%"><canvas id="mostActiveUsersGraph"></canvas></div>');
            
            const ctxUsers = $("#mostActiveUsersGraph");

            usersChart = new Chart(ctxUsers, {
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

async function autoUpdateCounter() {
    $("#jsTimeToAutoUpdate").html('1 min');

    const timeToWait = 60;

    for(let x = 0; x < timeToWait; x++) {
        $("#jsTimeToAutoUpdate").html('' + (timeToWait - x) + ' sec');
        await sleep(1000);
    }

    await autoUpdate();

    await autoUpdateCounter();
}

async function autoUpdate() {
    console.log('Widget refresh.');
    topicsChart.destroy();
    postsChart.destroy();
    usersChart.destroy();
    await createDashboard();
}