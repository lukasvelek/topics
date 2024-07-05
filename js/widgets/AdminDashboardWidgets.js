let topicsChart = null;
let postsChart = null;
let usersChart = null;

async function createDashboard() {
    $("#widget1-data").html('<img src="resources/loading.gif" width="64">')
    $("#widget2-data").html('<img src="resources/loading.gif" width="64">')
    $("#widget3-data").html('<img src="resources/loading.gif" width="64">')

    await sleep(1000);

    $.get(
        "?page=AdminModule:Home&action=getGraphData&isAjax=1"
    ).done(function(data) {
        const obj = JSON.parse(data);

        
        if(obj.topics.error) {
            $("#widget1-data").html(obj.topics.error);
        } else {
            $("#widget1-data").html('<canvas id="mostActiveTopicsGraph"></canvas>');

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
            $("#widget2-data").html('<canvas id="mostActivePostsGraph"></canvas>');

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
            $("#widget3-data").html('<canvas id="mostActiveUsersGraph"></canvas>');
            
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