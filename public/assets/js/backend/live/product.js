define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            var ids = Fast.api.query('ids')
            Table.api.init({
                extend: {
                    index_url: `live/product/index?ids=${ids}`,
                    add_url: `live/product/add?ids=${ids}`,
                    edit_url: 'live/product/edit',
                    del_url: 'live/product/del',
                    multi_url: 'live/product/multi',
                    import_url: 'live/product/import',
                    table: 'live_product',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        {
                            field: 'relation',
                            title: __('Relation'),
                            operate: false,
                            // 根据选择的类型得出
                            formatter: function (value, row, index) {
                                switch (row.type) {
                                    case "subject":
                                        return `<span class='text-success'>${row.subjects.title}</span>`
                                    case "product":
                                        return `<span class='text-success'>${row.products.name}</span>`
                                    default:
                                        return `<span class='text-success'>无信息</span>`
                                }
                            }
                        },
                        { field: 'type', title: __('Type'), searchList: { "subject": __('Subject'), "product": __('Product') }, formatter: Table.api.formatter.normal },
                        { field: 'stock', title: __('Stock') },
                        { field: 'price', title: __('Price'), operate: 'BETWEEN' },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            Controller.api.TypeChange();
        },
        edit: function () {
            Controller.api.bindevent();
            Controller.api.TypeChange();

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            TypeChange: function () {
                $(document).on("change", "#type", function () {
                    var type = $(this).val();

                    if (type == "subject") {
                        $("#sublist").css('display', 'block')
                        $("#prolist").css('display', 'none')
                    } else {
                        $("#sublist").css('display', 'none')
                        $("#prolist").css('display', 'block')
                    }
                });
            },
        }
    };
    return Controller;
});
