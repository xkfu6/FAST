define(['backend'], function (Backend) {
    // FunkyRicky 自定义按钮，关闭窗口
    $('.btn-close').click(function(){
        //先得到当前iframe层的索引
        var index = parent.layer.getFrameIndex(window.name);

        //再执行关闭
        parent.layer.close(index);
    })
});