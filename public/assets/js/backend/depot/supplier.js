define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'depot/supplier/index' + location.search,
                    add_url: 'depot/supplier/add',
                    edit_url: 'depot/supplier/edit',
                    del_url: 'depot/supplier/del',
                    multi_url: 'depot/supplier/multi',
                    import_url: 'depot/supplier/import',
                    table: 'depot_supplier',
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
                        { field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'mobile', title: __('Mobile'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'provinces.province', title: __('Province'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'citys.city', title: __('City'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'districts.district', title: __('District'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'address', title: __('Address'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            Controller.api.GetRegion()

        },
        edit: function () {
            Controller.api.bindevent();
            Controller.api.GetRegion()
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            // 地区切换赋值
            GetRegion: function () {
                $("#region").on("cp:updated", function () {
                    var citypicker = $(this).data("citypicker");
                    var province = citypicker.getCode("province");
                    var city = citypicker.getCode("city");
                    var district = citypicker.getCode("district");
                    if (province) {
                        $("input[name='row[province]']").val(province)
                    }

                    if (city) {
                        $("input[name='row[city]']").val(city)
                    }

                    if (district) {
                        $("input[name='row[district]']").val(district)
                    }
                })
            }
        }
    };
    return Controller;
});
