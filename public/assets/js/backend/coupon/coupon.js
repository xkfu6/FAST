
//创建一个标准模块
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'coupon/coupon/index',
                    add_url: 'coupon/coupon/add',
                    edit_url: 'coupon/coupon/edit',
                    del_url: 'coupon/coupon/del',
                    receive_url: 'coupon/coupon/receive',
                    table: 'coupon', //传递的表名参数
                }
            })
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'ID',
                columns: [
                    {
                        checkbox: true
                    },
                    {
                        field: 'id',
                        title: __('ID'),
                        sortable: true,
                        operate: false,
                    },
                    {
                        field: 'title',
                        title: '标题',
                        operate: 'LIKE',
                    },
                    {
                        field: 'thumb_text',
                        title: '缩略图',
                        formatter: Table.api.formatter.image,
                    },
                    {
                        field: 'endtime_text',
                        title: '结束时间',
                        operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime
                    },
                    {
                        field: 'rate',
                        title: '折扣率',
                    },
                    {
                        field: 'total',
                        title: '数量',
                    },
                    {
                        field: 'status',
                        title: '活动状态',
                        formatter: Table.api.formatter.status,
                        searchList: { "0": "未开始", "1": "进行中", "2": "已结束" },
                        custom: { "0": "black", "1": "green", "2": "red" }
                    },
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,  //关联的表格的dom元素
                        events: Table.api.events.operate, //给操作的元素绑定事件
                        formatter: Table.api.formatter.operate, //表格按钮的默认操作
                        // data-operate //会去找 html中的 data-operate选项
                        buttons: [
                            {
                                name: 'receive',
                                title: '领取详情',
                                icon: 'fa fa-align-justify',
                                url: $.fn.bootstrapTable.defaults.extend.receive_url,
                                exend: 'data-toggle=\'tooltip\' data-area=\'[80%,100%]\'',
                                classname: 'btn btn-xs btn-success btn-dialog'
                            }
                        ]
                    }
                ]
            })
            // 为表格绑定事件 表格弹框，异步刷新
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        del: function () {
            Controller.api.bindevent();
        },
        receive: function () {
            var ids = Fast.api.query('ids');
            Table.api.init({
                extend: {
                    index_url: `coupon/coupon/receive?ids=${ids}`
                }
            })
            var table = $("table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'ID',
                columns: [
                    {
                        field: 'business.nickname',
                        title: '用户名',
                        operate: 'LIKE'
                    },
                    {
                        field: 'createtime',
                        title: '领取时间',
                        operate: 'RANGE',
                        formatter: Table.api.formatter.datetime,
                    },
                    {
                        field: 'status',
                        title: '使用状态',
                        formatter: Table.api.formatter.normal,
                        searchList: { 1: '已使用', 0: '未使用或过期' },
                        operate: 'LIKE'
                    }
                ]
            })
            // 为表格绑定事件 表格弹框，异步刷新
            Table.api.bindevent(table);


        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    }
    return Controller
})