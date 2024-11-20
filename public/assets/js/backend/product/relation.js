define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            var ids = Fast.api.query('ids')
            Table.api.init({
                extend: {
                    index_url: `product/relation/index?ids=${ids}`,
                    add_url: `product/relation/add?ids=${ids}`,
                    edit_url: `product/relation/edit`,
                    del_url: 'product/relation/del',
                    multi_url: 'product/relation/multi',
                    import_url: 'product/relation/import',
                    table: 'product_relation',
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
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'prop.title', title: '属性名称' },
                        { field: 'value', title: '属性值' },
                        { field: 'price', title: __('Price'), operate: false },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            Controller.api.GetProp()
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            GetProp: function () {
                $(document).on("change", "#propid", function () {
                    var propid = $(this).val()
                    Fast.api.ajax({
                        url: 'product/relation/select',
                        data: { ids: propid }
                    }, function (data, ret) {
                        //成功的回调
                        var list = JSON.parse(ret.data)
                        if (!list) {
                            layer.msg('无属性信息')
                            return false
                        }
                        //获取属性值的值
                        var textarea = $("textarea[name='row[prop]']")
                        if (!textarea) {
                            layer.msg('无textarea元素')
                            return false
                        }
                        //清空
                        textarea.val('')
                        textarea.trigger("fa.event.refreshfieldlist")
                        for (var item of list) {
                            $("[data-name='row[prop]'] .btn-append").trigger("click", [{ value: item, price: '' }]);
                        }
                    })
                })
            }
        }
    };
    return Controller;
});
