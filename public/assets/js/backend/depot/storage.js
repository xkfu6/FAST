define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'depot/storage/index' + location.search,
                    add_url: 'depot/storage/add',
                    edit_url: 'depot/storage/edit',
                    del_url: 'depot/storage/del',
                    info_url: 'depot/storage/info',
                    agree_url: 'depot/storage/agree',
                    reject_url: 'depot/storage/reject',
                    revoke_url: 'depot/storage/revoke',
                    receipt_url: 'depot/storage/receipt',
                    multi_url: 'depot/storage/multi',
                    import_url: 'depot/storage/import',
                    table: 'depot_storage',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                onLoadSuccess: function () {
                    $('.btn-editone').data('area', ['100%', '100%'])
                    $(".btn-add").data("area", ["100%", "100%"]);
                    $(".btn-edit").data('area', ['100%', '100%'])
                },
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'code', title: __('Code'), operate: 'LIKE' },
                        { field: 'supplier.name', title: '供应商名称' },
                        { field: 'type', title: '入库类型', searchList: { "1": '直销入库', "2": '退货入库' }, formatter: Table.api.formatter.normal },
                        { field: 'amount', title: __('Amount'), operate: 'BETWEEN' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'status', title: '审核状态', searchList: { "0": '待审核', "1": '审核失败', "2": '待入库', "3": '入库完成' }, formatter: Table.api.formatter.status },
                        { field: 'adminid', title: __('Adminid') },
                        { field: 'reviewerid', title: __('Reviewerid') },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
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
                                    title: '通过审核',
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
                                    visible: row => (row.status == '1' || row.status == '2') ? true : false,
                                },
                                {
                                    name: 'receipt',
                                    title: '确认入库',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    extend: 'data-toggle=\"tooltip\" data-container=\"body\"',
                                    confirm: '确认货物入库吗？',
                                    url: $.fn.bootstrapTable.defaults.extend.receipt_url,
                                    success: data => $(".btn-refresh").trigger("click"),
                                    visible: row => (row.status == '2') ? true : false,
                                }
                            ]
                        }
                    ]
                ]
            });


            // 为表格绑定事件
            Table.api.bindevent(table);
        },


        add: function () {
            //选择供应商
            $('#ModelSupplier').on('show.bs.modal', function (e) {
                Controller.api.GetSupplier();
            });

            $('#ModelSupplier').on('hidden.bs.modal', function () {
                $('#TableSupplier').bootstrapTable('destroy');
            });
            $('#ModelProduct').on('show.bs.modal', function (e) {
                Controller.api.GetProduct();
            });
            $('#ModelProduct').on('hidden.bs.modal', function () {
                $('#TableProduct').bootstrapTable('destroy');
            });

            Controller.api.bindevent();
        },
        edit: function () {
            //选择供应商
            $('#ModelSupplier').on('show.bs.modal', function (e) {
                Controller.api.GetSupplier();
            });

            $('#ModelSupplier').on('hidden.bs.modal', function () {
                $('#TableSupplier').bootstrapTable('destroy');
            });
            $('#ModelProduct').on('show.bs.modal', function (e) {
                Controller.api.GetProduct();
            });
            $('#ModelProduct').on('hidden.bs.modal', function () {
                $('#TableProduct').bootstrapTable('destroy');
            });
            Controller.api.bindevent();
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
                url: 'depot/storage/recyclebin' + location.search,
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
                                    url: 'depot/storage/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'depot/storage/destroy',
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
            //供应商
            GetSupplier: function () {
                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'depot/async/supplier/index',
                        select_url: 'depot/async/storage/supplier',
                        table: 'supplier',
                    }
                });

                var table = $("#TableSupplier");

                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    toolbar: '#ToolbarSupplier',
                    search: false,
                    columns: [
                        [
                            { field: 'id', title: __('ID'), sortable: true, operate: false },
                            { field: 'name', title: '供应商名字', operate: 'LIKE' },
                            { field: 'mobile', title: __('Mobile'), operate: 'LIKE' },
                            { field: 'region_text', title: __('Area'), operate: false },
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
                                        icon: 'fa fa-magic',
                                        classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                        extend: 'data-toggle=\'tooltip\'',
                                        url: $.fn.bootstrapTable.defaults.extend.select_url,
                                        success: function (data, ret) {
                                            if (data) {
                                                $("#ShowSupplier").val(data.name);
                                                $("#supplierid").val(data.id);
                                                $('#mobile').val(data.mobile);
                                                $('#region').val(data.region_text);
                                            }

                                            $('#ModelSupplier').modal('hide');
                                        }
                                    }
                                ]
                            }
                        ]
                    ],
                });

                // 为表格绑定事件
                Table.api.bindevent(table);
            },
            // 选择商品列表
            GetProduct: function () {
                Table.api.init({
                    extend: {
                        index_url: 'depot/async/par/index',
                        select_url: 'depot/async/storage/product',
                        table: 'product',
                    }
                });
                var table = $("#TableProduct");
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    toolbar: '#ToolbarProduct',
                    search: false,
                    columns: [
                        [
                            { field: 'id', title: __('ID'), sortable: true, operate: false },
                            { field: 'name', title: '商品名称', operate: 'LIKE' },
                            { field: 'unit.name', title: '单位', operate: 'LIKE' },
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
                                        icon: 'fa fa-magic',
                                        classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                        extend: 'data-toggle=\'tooltip\'',
                                        url: $.fn.bootstrapTable.defaults.extend.select_url,
                                        success: function (data, ret) {
                                            Product.add(data)
                                        }
                                    }
                                ]
                            }
                        ]
                    ],
                })
                // 为表格绑定事件
                Table.api.bindevent(table);
            }
        }
    };
    return Controller;
});

var Product = {
    add: (row) => {
        $('#ModelProduct').modal('hide');
        if (JSON.stringify(row) == "{}") {
            return false
        }
        //不存在就追加
        var check = $(`#TobodyProduct tr[data-proid=${row.id}]`)
        if (check.length <= 0) {
            var str = `
        <tr data-proid='${row.id}'>
            <td>${row.id}<input type="hidden" name="prolist[]" value="${row.id}" required></td>
            <td>${row.name}</td>
            <td>${row.stock}</td>
            <td><input class='text-center' type="number" min="1" name="nums[]" value='1' onchange="Product.edit(this)" required placeholder='请输入商品数量' data-rule="required;digits"></td>
            <td><input class='text-center' type="number" min="1" name="price[]" value='' onchange="Product.price(this)" required placeholder="请输入商品单价" data-rule="required"></td>
            <td><input class='text-center' type="number" min="1" name="total[]" value='' onchange="Product.total(this)" required readonly placeholder="请输入商品合计" data-rule="required"></td>
        <td>
            <a class="btn btn-danger" onclick="Product.del(this)" data-toggle="tooltip" data-original-title="删除">
            <i class="fa fa-trash"></i>
            </a>
        </td>

        </tr>`;
            $("#TbodyProduct").append(str)
        }
    },
    //更新入库商品数量
    edit: (that) => {
        //parseInt转整数
        var relationnum = parseInt($(that).val());
        if (relationnum <= 0) {
            $(that).val(1)
            layer.msg('商品数量必须大于0', {
                time: 500,
            })
            return false
        }
        //更新商品总计
        Product.price(that)
    },

    //更新商品总价
    price: (that = false) => {
        console.log(that)
        // 获取当前列表的索引下标
        var index = parseInt($(that).parent().parent().index())
        var index = isNaN(index) ? 0 : index
        if (isNaN(index)) {
            layer.msg('商品单价、数量格式错误', {
                time: 500,
            })
            return false
        }

        // 商品单价
        //.eq寻找下标
        var price = parseFloat($("input[name='price[]']").eq(index).val())
        price = isNaN(price) ? 0 : price.toFixed(2)

        if (isNaN(price) && price < 0) {
            layer.msg('请输入正确的价格格式', {
                time: 500,
            })
            return false
        }

        // 商品数量
        var nums = parseInt($("input[name='nums[]']").eq(index).val())
        nums = isNaN(nums) ? 1 : nums
        if (nums <= 0) {
            layer.msg('请输入商品数量', {
                time: 500,
            })
            return false
        }

        //判断元素是否存在
        var element = $("input[name='total[]']").eq(index)
        if (!element) {
            return false
        }

        // 总价
        var total = parseFloat(price * nums).toFixed(2)
        if (total >= 0) {
            element.val(total)
            element.change()
        }
    },
    // 更新订单总价
    total: (that = false) => {
        if (that) {
            var price = parseInt($(that).val())
            if (isNaN(price) && price < 0) {
                layer.msg('请输入正确的价格格式', {
                    time: 500,
                })
                return false
            }
        }
        // 总价
        var total = 0;

        //是否有不正确的格式数据
        var invalid = 0
        if ($("input[name='total[]']").length > 0) {
            $("input[name='total[]']").each(function () {
                var amount = parseFloat($(this).val())
                if (isNaN(amount)) {
                    invalid++
                }
                if (!isNaN(amount) && amount > 0) {
                    total += amount
                }
            })
        } else {
            total = 0
        }
        if (invalid > 0) {
            layer.msg(`有${invalid}个输入的商品总计格式不正确`, {
                time: 500,
            })
            return false
        }
        if (total >= 0) {
            total = total.toFixed(2)
            $("#amount").val(total)

            layer.msg('更新总价成功', {
                time: 500,
            })
        }
    },
    del: (that = false) => {
        if (that) {
            $(that).parent().parent().remove()
            Product.total()
        }
    }
}


