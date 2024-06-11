const source = document.getElementById('searchQuery');

const inputHandler = async function(e) {
    if(e.target.value.length >= 3) {
        //console.log(e.target.value);
    }
}

source.addEventListener('input', inputHandler);

function doSearch(_userId) {
    const _query = $("#searchQuery").val()
    location.replace("?page=UserModule:Topics&action=search&q=" + _query);
}