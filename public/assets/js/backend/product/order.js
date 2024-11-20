define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'product/order/index',
                    info_url: 'product/order/info',
                    deliver_url: 'product/order/deliver',
                    refund_url: 'product/order/refund',
                    del_url: 'product/order/del',
                    multi_url: 'product/order/multi',
                    import_url: 'product/order/import',
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'code', title: __('Code'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'busid', title: __('Busid') },
                        { field: 'amount', title: __('Amount'), operate: 'BETWEEN' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'express.name', title: __('Expressid') },
                        { field: 'expresscode', title: __('Expresscode'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'status', title: '订单状态', searchList: { "0": __('Status 0'), "1": __('Status 1'), "2": __('Status 2'), "3": __('Status 3'), "4": __('Status 4'), "-1": __('Status -1'), "-2": __('Status -2'), "-3": __('Status -3'), "-4": __('Status -4'), "-5": __('Status -5') }, formatter: Table.api.formatter.flag },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'info',
                                    title: '订单详情',
                                    extend: 'data-toggle="tooltip"',//弹出
                                    classname: "btn btn-xs btn-success btn-dialog",
                                    icon: 'fa fa-eye',
                                    url: $.fn.bootstrapTable.defaults.extend.info_url,
                                },
                                {
                                    name: 'deliver',
                                    title: '发货',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: $.fn.bootstrapTable.defaults.extend.deliver_url,
                                    icon: 'fa fa-leaf',
                                    visible: (row) => (row.status == 1)//是否可见,
                                },
                                {
                                    name: 'refund',
                                    title: '退货审核',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: $.fn.bootstrapTable.defaults.extend.refund_url,
                                    icon: 'fa fa-truck',
                                    visible: (row) => (row.status == -1 || row.status == -2)//是否可见,
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'product/order/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'code', title: __('Code'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'busid', title: __('Busid') },
                        { field: 'amount', title: __('Amount'), operate: 'BETWEEN' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'express.name', title: __('Expressid') },
                        { field: 'expresscode', title: __('Expresscode'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'status', title: '订单状态', searchList: { "0": __('Status 0'), "1": __('Status 1'), "2": __('Status 2'), "3": __('Status 3'), "4": __('Status 4'), "-1": __('Status -1'), "-2": __('Status -2'), "-3": __('Status -3'), "-4": __('Status -4'), "-5": __('Status -5') }, formatter: Table.api.formatter.flag },
                        {
                            field: 'deletetime',
                            title: '删除时间',
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '140px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'product/order/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'product/order/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        info: function () {
            Controller.api.bindevent();
        },
        deliver: function () {
            Controller.api.bindevent();
        },
        refund: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },

    };
    return Controller;
});
