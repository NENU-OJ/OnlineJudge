var host = window.location.host;

$("#problemSubmit").on('click', "#submit", function () {
    var problemId = $("#problemId").val();
    var languageId = $("#language").select().val();
    var sourceCode = $("#sourceCode").val();
    var isShared = document.getElementById("isShared").checked;

    $.ajax({
        type: "get",
        url: 'http://' + host + '/problem/problem-detail/submit',
        dataType: "json",
        data: {
            problemId: problemId,
            languageId: languageId,
            sourceCode: sourceCode,
            isShared: isShared
        },
        success: function (data) {
            $.each(data, function (index, val) {
                var code = val.code;
                if (code == 0) {
                    alert("提交成功");
                    window.location.href = 'http://' + host+'/problem/problem-detail/detail?p_id='+problemId;
                }
            })
        },
        error: function () {
            console.log("获取JSON数据异常");
        }
    })
});

$("#reset").click(function () {
    location.reload(true);
});