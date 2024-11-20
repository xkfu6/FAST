// require.js 模块化 前置依赖的
//创建一个标准模块
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'business/recyclebin/index',
                    restore_url: 'business/recyclebin/restore',
                    del_url: 'business/recyclebin/del',
                    table: 'business', //传递的表名参数
                }
            })
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'deletetime',
                columns: [
                    { checkbox: true },
                    {
                        field: 'id',
                        title: __('ID'),
                        sortable: true,
                        operate: false, //筛选中不需要此项
                    },
                    {
                        field: 'nickname',
                        title: __('nickname'),
                        sortable: true,
                        operate: 'LIKE', //筛选中不需要此项
                    },
                    {
                        field: 'deal',
                        title: __('BdealText'),
                        operate: false, //筛选中不需要此项
                        searchList: { "0": __('未成交'), "1": __('已成交') }, formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'auth',
                        title: __('auth'),
                        searchList: { "0": '未认证', "1": '已认证' },
                        operate: false, //筛选中不需要此项
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'gender',
                        title: __('gender'),
                        operate: false, //筛选中不需要此项
                        searchList: { "0": "保密", "1": "男", "2": "女" },
                        formatter: Table.api.formatter.normal,
                    },
                    {
                        field: 'deletetime_text',
                        title: "删除时间",
                        operate: false, //筛选中不需要此项
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
                                name: 'restore',//名
                                icon: 'fa fa-circle-o-notch',//图标
                                title: '还原',
                                extend: 'data-toggle="tooltip"',
                                confirm: '确定要还原吗',//提示的信息
                                classname: 'btn btn-xs btn-success btn-ajax',
                                url: $.fn.bootstrapTable.defaults.extend.restore_url,
                                success: () => $(".btn-refresh").trigger("click")
                            },
                            {
                                name: 'del',//名
                                icon: 'fa fa-trash',//图标
                                title: '销毁',
                                extend: 'data-toggle="tooltip"',
                                confirm: '确定要销毁吗',//提示的信息
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                url: $.fn.bootstrapTable.defaults.extend.del_url,
                                success: () => $(".btn-refresh").trigger("click")
                            }
                        ]
                    }

                ]
            })
            // 为表格绑定事件 表格弹框，异步刷新
            Table.api.bindevent(table);
            // 还原点击事件
            $(document).on('click', '.restore', function () {
                var ids = Table.api.selectedids(table);
                layer.confirm('确定还原吗', { title: '领取', btn: ['是', '否'] }, function (index) {
                    Fast.api.ajax(
                        $.fn.bootstrapTable.defaults.extend.restore_url + `?ids=${ids}`,
                        function (data, success) {
                            $(".btn-refresh").trigger('click');
                        }
                    )
                    layer.close(index)
                })
            })
            // 销毁点击事件
            $(document).on('click', '.del', function () {
                var ids = Table.api.selectedids(table)
                layer.confirm('确认销毁吗', { title: '销毁', btn: ['是', '否'] }, function (index) {
                    Fast.api.ajax(
                        $.fn.bootstrapTable.defaults.extend.del_url + `?ids=${ids}`,
                        function (data, success) {
                            $(".btn-refresh").trigger('click');
                        }
                    )
                    layer.close(index)

                })
            })
        },
        restore: function () {
            Controller.api.bindevent();
        },
        del: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    }
    return Controller
})