define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/pay/index' + location.search,
                    add_url: 'pay/pay/add',
                    del_url: 'pay/pay/del',
                    multi_url: 'pay/pay/multi',
                    import_url: 'pay/pay/import',
                    table: 'pay',
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
                        { field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'third', title: __('Third'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'type', title: __('Type'), searchList: { "wx": __('微信支付'), "zfb": __('支付宝支付') }, formatter: Table.api.formatter.normal },
                        { field: 'total', title: __('Total'), operate: 'BETWEEN' },
                        { field: 'price', title: __('Price'), operate: 'BETWEEN' },
                        { field: 'remarks', title: __('Remarks'), operate: false, table: table, class: 'autocontent', },
                        { field: 'jump', title: __('Jump'), operate: false, table: table, class: 'autocontent', formatter: Table.api.formatter.url },
                        { field: 'notice', title: __('Notice'), operate: false, table: table, class: 'autocontent', formatter: Table.api.formatter.url },
                        { field: 'status', title: __('Status'), searchList: { "0": '待支付', "1": '已支付', "2": '已关闭' }, formatter: Table.api.formatter.flag },
                        { field: 'wxcode', title: __('Wxcode'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image },
                        { field: 'zfbcode', title: __('Zfbcode'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'paytime', title: __('Paytime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
