function handleFilterCategoryChange() {
    const select = $("#filter-category").val();

    if(select == "all") {
        $("#filter-subcategory").html('');
        $("#filter-subcategory").hide();
        $("#filter-submit").hide();
        return;
    }

    $.get(
        "?page=AdminModule:FeedbackReports&action=getFilterCategorySuboptions&isAjax=1",
        {
            category: select
        }
    )
    .done(function(data) {
        const obj = JSON.parse(data);

        if(obj.empty == "0") {
            $("#filter-subcategory").html(obj.options);
            $("#filter-subcategory").show();
            $("#filter-submit").show();
        } else {
            $("#filter-subcategory").hide();
            $("#filter-submit").hide();
        }
    });
}

async function handleGridFilterChange() {
    const type = $("#filter-category").val();
    const key = $("#filter-subcategory").val();

    await getReportGrid(0, type, key);
}

async function handleGridFilterClear() {
    $("#filter-subcategory").html('');
    $("#filter-subcategory").hide();
    $("#filter-submit").hide();
    $("#filter-clear").hide();

    await getReportGrid(0, 'null', 'null');
}