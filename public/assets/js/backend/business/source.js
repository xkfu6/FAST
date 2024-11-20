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
                    index_url: 'business/source/index',
                    add_url: 'business/source/add',
                    edit_url: 'business/source/edit',
                    del_url: 'business/source/del',
                    recyclebin_url: 'business/source/recyclebin',
                    destory_url: 'business/source/destory',
                    restore_url: 'business/source/restore',
                    table: 'business_source', //传递的表名参数
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
                        field: 'name',
                        title: __('name'),
                        operate: 'LIKE', //此选项变成模糊搜索
                    },
                    {
                        field: 'operate', //表格自带的
                        title: __('Operate'),
                        table: table,  //关联的表格的dom元素
                        events: Table.api.events.operate, //给操作的元素绑定事件
                        formatter: Table.api.formatter.operate, //表格按钮的默认操作
                        // data-operate //会去找 html中的 data-operate选项
                    }
                ], 
            });
            
            // 为表格绑定事件 表格弹框，异步刷新
            Table.api.bindevent(table);

            //给自定义按钮绑定弹框
            $(document).on('click', '.recyclebin', function(){
                // console.log(Fast)
                // open(url, title, options)
                Fast.api.open($.fn.bootstrapTable.defaults.extend.recyclebin_url, '来源回收站', {area:['100%','100%']})
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
        recyclebin: function()
        {
            Table.api.init({
                extend: {
                    recyclebin_url: 'business/source/recyclebin',
                    del_url: 'business/source/destory',
                    restore_url: 'business/source/restore',
                    table: 'business_source', //传递的表名参数
                }
            })

            //获取表格元素
            var table = $("#table");

            //初始化表格
            table.bootstrapTable({
                //表格的数据是通过ajax异步获取的，index_url 上面配置的列表的控制器地址
                url: $.fn.bootstrapTable.defaults.extend.recyclebin_url, 
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
                        field: 'name',
                        title: __('name'),
                        operate: 'LIKE', //此选项变成模糊搜索
                    },
                    {
                        field: 'deletetime',
                        title: __('Deletetime'),
                        datetimeFormat:'YYYY-MM-DD HH:mm',
                        formatter: Table.api.formatter.datetime, //将时间戳转换为标准时间
                        operate: 'RANGE', //范围搜索
                        addclass: 'datetimerange', 
                        sortable: true
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
                                name:'restore', //data-operate-restore
                                title: '还原', //标题
                                icon: 'fa fa-circle-o-notch', //图标
                                classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                url: $.fn.bootstrapTable.defaults.extend.restore_url,
                                confirm: '是否确认恢复数据', //提示内容
                                extend:"data-toggle='tooltip'", //title信息显示
                                success: (data, ret) => //回调函数
                                {
                                    //让表格刷新
                                    table.bootstrapTable('refresh')
                                }
                            }
                        ]
                    }
                ], 
            });
            
            // 为表格绑定事件 表格弹框，异步刷新
            Table.api.bindevent(table);

            Controller.api.bindevent();
        },
        destroy: function()
        {
            Controller.api.bindevent();
        },
        restore: function()
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