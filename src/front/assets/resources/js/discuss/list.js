var host = window.location.host;

$("#fil").click(function () {
    var search = $("#search").val();

    var url = '//' + host + "/discuss/list/?id=1";

    if (search)
        url += '&search=' + search.replace(/ /g, '%20');

    window.location = url;
});

$(".pagi").click(function () {
    var search = $("#search").val();

    var url = '//' + host + "/discuss/list/?id=" + $(this).attr('title');

    if (search)
        url += '&search=' + search.replace(/ /g, '%20');

    window.location = url;
});