// require.js 模块化 前置依赖的
//创建一个标准模块
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    //创建控制器
    var Controller = {
        index: function() 
        {
            //初始化表格参数
            // 参数中的extend这个参数值是非常重要的一个信息点，此参数用于配置我们加载数据列表的URL、添加文档的URL、编辑文档的URL和删除文档URL等
            Table.api.init({
                extend: {
                    index_url: 'business/highsea/index',
                    del_url: 'business/highsea/del',
                    apply_url: 'business/highsea/apply',
                    allot_url: 'business/highsea/allot',
                    table: 'business', //传递的表名参数
                }
            })

            //获取表格元素
            var table = $("#table");

            //初始化表格
            table.bootstrapTable({
                //表格的数据是通过ajax异步获取的，index_url 上面配置的列表的控制器地址
                url: $.fn.bootstrapTable.defaults.extend.index_url, 
                pk: 'id',  //表的主键字段
                sortName: 'id', //排序的字段
                columns: [ //表格中要显示的字段信息
                    {checkbox: true},
                    {
                        field:'id', //字段名称
                        title: __('ID'), //字段标题
                        sortable: true, //允许排序
                        operate: false, //筛选中不需要此项
                    },
                    {
                        field: 'nickname',
                        title: __('Bnickname'),
                        operate: 'LIKE', //此选项变成模糊搜索
                    },
                    {
                        field: 'source.name',
                        title: __('SName'),
                        operate: 'LIKE', //此选项变成模糊搜索
                    },
                    {
                        field: 'gender',
                        title: __('BsexText'),  
                        searchList: {"0": '保密', "1" : "男", "2": "女"},
                        formatter: Table.api.formatter.normal,
                    },
                    { 
                        field: 'deal', 
                        title: __('BdealText'), 
                        searchList: { "0": __('未成交'), "1": __('已成交') }, formatter: Table.api.formatter.normal 
                    },
                    { 
                        field: 'auth', 
                        title: __('AuthStatus'), 
                        searchList: { "0": __('未认证'), "1": __('已认证') }, formatter: Table.api.formatter.normal 
                    }, 
                    {
                        field: 'operate', //表格自带的
                        title: __('Operate'),
                        table: table,  //关联的表格的dom元素
                        events: Table.api.events.operate, //给操作的元素绑定事件
                        formatter: Table.api.formatter.operate, //表格按钮的默认操作
                        // data-operate //会去找 html中的 data-operate选项
                        buttons: [
                            {
                                name: 'apply', 
                                icon: 'fa fa-plus', 
                                title: '领取',
                                confirm: '确定要领取吗', 
                                extend: 'data-toggle="tooltip"',
                                classname: 'btn btn-xs btn-success btn-ajax',
                                url: $.fn.bootstrapTable.defaults.extend.apply_url,
                                success: () => $(".btn-refresh").trigger("click")
                            },
                            {
                                name: 'allot',
                                title: '分配',
                                icon: 'fa fa-share',
                                extend: 'data-toggle="tooltip"',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.allot_url,
                            }
                        ]
                    }
                ], 
            });
            
            // 为表格绑定事件 表格弹框，异步刷新
            Table.api.bindevent(table);

            //给自定义按钮绑定弹框
            $(document).on('click', '.apply', function(){
                //获取选中的ids
                var ids = Table.api.selectedids(table);

                //弹出确认对框
                layer.confirm('确认要领取吗？', {title: '领取', btn: ['是', '否']}, function(index){
                    // index 是窗口的id
                    // console.log(index)

                    //发送ajax
                    Fast.api.ajax(
                        $.fn.bootstrapTable.defaults.extend.apply_url + `?ids=${ids}`,
                        function(data, success) //成功回调函数 success
                        {
                            // 刷新动作
                            $(".btn-refresh").trigger('click');
                        }
                    );

                    //关闭窗口
                    layer.close(index)
                })
            })

            $(document).on('click', '.allot', function(){
                //获取选中的ids
                var ids = Table.api.selectedids(table);

                //打开新窗口
                Fast.api.open($.fn.bootstrapTable.defaults.extend.allot_url + `?ids=${ids}`, '分配')
            })
        },
        apply: function()
        {
            Controller.api.bindevent();
        },
        allot: function()
        {
            Controller.api.bindevent();
        },
        del: function()
        {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };


    //返回值
    return Controller;
})