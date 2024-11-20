define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'depot/back/index' + location.search,
                    add_url: 'depot/back/add',
                    edit_url: 'depot/back/edit',
                    del_url: 'depot/back/del',
                    info_url: 'depot/back/info',
                    agree_url: 'depot/back/agree',
                    reject_url: 'depot/back/reject',
                    revoke_url: 'depot/back/revoke', 
                    receipt_url: 'depot/back/receipt',
                    multi_url: 'depot/back/multi',
                    import_url: 'depot/back/import',
                    table: 'depot_back',
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
                onLoadSuccess: function () {
                    $('.btn-editone').data('area', ['80%', '100%'])
                    $(".btn-edit").data("area", ["80%", "100%"]);
                },
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'code', title: __('Code'), operate: 'LIKE' },
                        { field: 'ordercode', title: __('Ordercode'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'busid', title: __('Busid') },
                        { field: 'contact', title: __('Contact'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'phone', title: __('Phone'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'address', title: __('Address'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'province', title: __('Province') },
                        { field: 'city', title: __('City') },
                        { field: 'district', title: __('District') },
                        { field: 'amount', title: __('Amount'), operate: 'BETWEEN' },
                        { field: 'expressid', title: __('Expressid') },
                        { field: 'expresscode', title: __('Expresscode'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'status', title: __('Status'), searchList: { "0": __('Status 0'), "1": __('Status 1'), "2": __('Status 2'), "-1": __('Status -1') }, formatter: Table.api.formatter.status },
                        { field: 'adminid', title: __('Adminid') },
                        { field: 'reviewerid', title: __('Reviewerid') },
                        { field: 'stromanid', title: __('Stromanid') },
                        { field: 'storageid', title: __('Storageid') },
                        {
                            field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'info',
                                    title: '详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    extend: 'data-toggle=\'tooltip\' data-area= \'["80%", "80%"]\'', //重点是这一句
                                    url: $.fn.bootstrapTable.defaults.extend.info_url,
                                    icon: 'fa fa-eye'
                                },
                                {
                                    name: 'agree',
                                    title: '同意审核',
                                    classname: 'btn btn-ajax btn-success btn-xs',
                                    extend: 'data-toggle=\"tooltip\"data-container=\"body\"', //重点是这一句
                                    url: $.fn.bootstrapTable.defaults.extend.agree_url,
                                    confirm: '确定确认审核吗?',
                                    icon: 'fa fa-check',
                                    success: data => $(".btn-refresh").trigger("click"),
                                    visible: row => row.status == 0 ? true : false,
                                },
                                {
                                    name: 'reject',
                                    title: '拒绝审核',
                                    classname: 'btn btn-xs btn-info btn-ajax',
                                    icon: 'fa fa-close',
                                    confirm: '确认拒绝审核吗？',
                                    url: $.fn.bootstrapTable.defaults.extend.reject_url,
                                    extend: 'data-toggle=\"tooltip\" data-container=\"body\"',
                                    success: data => $(".btn-refresh").trigger("click"),
                                    visible: row => row.status == 0 ? true : false
                                },
                                {
                                    name: 'revoke',
                                    title: '撤销审核',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    extend: 'data-toggle=\"tooltip\" data-container=\"body\"',
                                    icon: 'fa fa-reply',
                                    url: $.fn.bootstrapTable.defaults.extend.revoke_url,
                                    confirm: '确认要撤回审核吗？',
                                    success: data => $(".btn-refresh").trigger("click"),
                                    visible: row => (row.status == '1' || row.status == '-1') ? true : false,
                                },
                                {
                                    name:'receipt',
                                    title:'确认收货入库',
                                    classname:'btn btn-xs btn-success btn-ajax',
                                    icon:'fa fa-leaf',
                                    extend: 'data-toggle=\"tooltip\" data-container=\"body\"',
                                    confirm:'确认收货入库吗？',
                                    url: $.fn.bootstrapTable.defaults.extend.receipt_url,
                                    success: data => $(".btn-refresh").trigger("click"),
                                    visible: row => (row.status == '1') ? true : false,
                                }
                            ]

                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        // 选着退货订单
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
                url: 'depot/back/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
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
                                    url: 'depot/back/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'depot/back/destroy',
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
        // 添加
        add: function () {
            Controller.api.bindevent();
            $('#ModelOrder').on('show.bs.modal', function (e) {
                Controller.api.Getorder();
            });
        },
        edit: function () {
            Controller.api.bindevent();
            Controller.api.GetRegion();
        },
        info: function () {
            Controller.api.bindevent();
        },
        agree: function () {
            Controller.api.bindevent();
        },
        reject: function () {
            Controller.api.bindevent();

        },
        revoke: function () {
            Controller.api.bindevent();

        },
        receipt: function () {
            Controller.api.bindevent();

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            // 获取退货的订单
            Getorder: function () {
                Table.api.init({
                    extend: {
                        index_url: 'depot/async/back/order',
                        back_url: 'depot/async/back/back',
                        table: 'order',
                    }
                })
                var table = $("#TableOrder");
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    toolbar: '#ToolbarOrder',
                    columns: [
                        [
                            { field: 'id', title: __('ID'), sortable: true, operate: false },
                            { field: 'code', title: '订单编号', operate: 'LIKE' },
                            { field: 'amount', title: __('Amount'), sortable: true, operate: 'BETWEEN' },
                            { field: 'business.nickname', title: '客户名称', operate: 'LIKE' },
                            { field: 'express.name', title: __('Expressid'), operate: 'LIKE' },
                            { field: 'expresscode', title: __('Expresscode'), operate: 'LIKE' },
                            { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                            {
                                field: 'operate',
                                title: __('Operate'),
                                table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons: [
                                    {
                                        name: 'info',
                                        text: '选择',
                                        title: '选择',
                                        classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                        extend: 'data-toggle=\'tooltip\'',
                                        url: $.fn.bootstrapTable.defaults.extend.back_url,
                                        icon: 'fa fa-magic',
                                        success: function (data, ret) {
                                            $('#ModelOrder').modal('hide');

                                            var order = data.order
                                            var address = data.address
                                            var product = data.product

                                            if (order) {
                                                $("#orderid").val(order.id)
                                                $("#ShowOrder").val(order.code)
                                            }

                                            Controller.api.GetAddr(address);

                                            Controller.api.GetProduct(product);
                                        }
                                    },
                                ]
                            }
                        ]
                    ]
                })
                // 为表格绑定事件
                Table.api.bindevent(table);
            },
            // 获取收货地址
            GetAddr: function (address) {
                $("#addrid").empty("");
                $('#addrid').selectpicker("refresh");
                var str = ''
                for (var item of address) {
                    var id = item.id
                    var consignee = item.consignee
                    var mobile = item.mobile
                    var region = item.region_text
                    var info = item.address
                    str += `<option value="${id}">${consignee} / ${mobile} / ${region} ${info}</option>`
                    $("#addrid").empty("").append(str);
                    $('#addrid').selectpicker("refresh");
                }
            },
            //获取商品列表
            GetProduct: function (product) {
                if (!product) {
                    return;
                }

                var temp = ''
                for (var item of product) {
                    temp += `
                        <tr style="text-align: center; vertical-align: middle; ">
                            <td>${item.products.name}</td>
                            <td>${item.pronum}</td>
                            <td>${item.price}</td>
                            <td>${item.total}</td>
                        </tr>
                    `
                }

                $("#OrderProduct").append(temp);
            },
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
