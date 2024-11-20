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
                    index_url: 'business/privatesea/index',
                    add_url: 'business/privatesea/add',
                    edit_url: 'business/privatesea/edit',
                    del_url: 'business/privatesea/del',
                    recovery_url: 'business/privatesea/recovery',
                    info_url: 'business/info/index',
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
                    { field: 'id', title: __('ID'), sortable: true, operate: false },
                    { field: 'nickname', title: __('BusinessNickname'), operate: 'LIKE' },
                    { field: 'mobile',title:__('Mobile'),operate: 'LIKE'},
                    { field: 'email',title:__('Email'),operate: 'LIKE'},
                    { field: 'money', title: __('Money'), operate: false },
                    {
                        field: 'source.name',
                        title: __('BusinessSource'),
                        operate: 'LIKE', //此选项变成模糊搜索
                    },
                    {
                        field: 'gender',
                        title: __('Gender'),  
                        searchList: {"0": '保密', "1" : "男", "2": "女"},
                        formatter: Table.api.formatter.normal,
                    },
                    { 
                        field: 'deal', 
                        title: __('BusinessDeal'), 
                        searchList: { "0": __('未成交'), "1": __('已成交') }, formatter: Table.api.formatter.normal 
                    },
                    { 
                        field: 'auth', 
                        title: __('BusinessAuth'), 
                        searchList: { "0": __('未认证'), "1": __('已认证') }, formatter: Table.api.formatter.normal 
                    }, 
                    { field: 'admin.nickname', title: __('BusinessApply'), operate: 'LIKE' },
                    {
                        field: 'operate', //表格自带的
                        title: __('Operate'),
                        table: table,  //关联的表格的dom元素
                        events: Table.api.events.operate, //给操作的元素绑定事件
                        formatter: Table.api.formatter.operate, //表格按钮的默认操作
                        // data-operate //会去找 html中的 data-operate选项
                        buttons: [
                            {
                                name: 'info',
                                title: '客户详情',
                                extend: 'data-toggle=\'tooltip\' data-area= \'["80%", "100%"]\'',
                                icon: 'fa fa-eye',
                                classname: 'btn btn-success btn-xs btn-dialog', //弹框
                                // classname: 'btn btn-success btn-xs btn-addtabs', //选项卡
                                url: $.fn.bootstrapTable.defaults.extend.info_url,
                            },
                            {
                                name: 'recovery', 
                                icon: 'fa fa-recycle', 
                                title: '回收',
                                confirm: '确定要回收吗',  //只有确认对话框才会有confirm
                                extend: 'data-toggle="tooltip"',
                                classname: 'btn btn-xs btn-success btn-ajax', //btn-ajax 发送ajax请求
                                url: $.fn.bootstrapTable.defaults.extend.recovery_url,
                                success: () => $(".btn-refresh").trigger("click")
                            },
                        ]
                    }
                ], 
            });
            
            // 为表格绑定事件 表格弹框，异步刷新
            Table.api.bindevent(table);

            //给自定义按钮绑定弹框
            $(document).on('click', '.recovery', function(){
                //获取选中的ids
                var ids = Table.api.selectedids(table);

                //弹出确认对框
                layer.confirm('确认要回收吗？', {title: '回收', btn: ['是', '否']}, function(index){
                    // index 是窗口的id
                    // console.log(index)

                    //发送ajax
                    Fast.api.ajax(
                        $.fn.bootstrapTable.defaults.extend.recovery_url + `?ids=${ids}`,
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
        },
        add: function()
        {
            Controller.api.bindevent();
        },
        edit: function()
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