const source = document.getElementById('searchQuery');

var submittable = false;

const inputHandler = async function(e) {
    if(e.target.value.length >= 3) {
        submittable = true;
    } else {
        submittable = false;
    }
}

source.addEventListener('input', inputHandler);

function doSearch(_userId) {
    if(submittable) {
        const _query = $("#searchQuery").val();
        location.replace("?page=UserModule:Search&action=search&q=" + _query);
    } else {
        alert('Search bar must contain minimum of 3 letters.');
    }
}