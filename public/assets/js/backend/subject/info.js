define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () 
        {
            // 绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));

                if (panel.length > 0) 
                {
                    Controller.table[panel.attr("id")].call(this);

                    $(this).on('click', function (e) 
                    {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }

                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });
            
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        table: {
            info: function () {
                var ids = Fast.api.query('ids')
                Table.api.init({
                    extend: {
                        index_url: `subject/info/index?ids=${ids}`,
                        table: 'subject_order',
                    }
                });
                var Infotable = $("#Infotable");
                Infotable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    toolbar: "#Infotoolbar", //工具栏
                    pk: 'id', //默认主键字段名
                    sortName: 'id', //排序的字段名
                    sortOrder: 'desc', //排序的方式
                    columns: [
                        { checkbox: true },
                        {
                            field: 'id', title: 'ID', operate: false
                        },
                        {
                            field: 'code', title: '订单号', operate: 'LIKE'
                        },
                        {
                            field: 'total', title: '订单金额', operate: false
                        },
                        {
                            field: 'business.nickname', title: '用户名称', operate: "LIKE"
                        },
                        {
                            field: 'createtime_text', title: '下单时间', operate: "LIKE"
                        }

                    ]
                })
                Table.api.bindevent(Infotable);
            },
            comment: function () {
                var ids = Fast.api.query('ids')
                Table.api.init({
                    extend: {
                        index_url: `subject/info/comment?ids=${ids}`,
                        table: 'subject_order',
                    }
                });
                var Commenttable = $("#Commenttable");
                Commenttable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    toolbar: "#Commenttoolbar", //工具栏
                    pk: 'id', //默认主键字段名
                    sortName: 'id', //排序的字段名
                    sortOrder: 'desc', //排序的方式
                    columns: [
                        { checkbox: true },
                        {
                            field: 'id', title: 'ID', operate: false
                        },
                        {
                            field: 'business.nickname', title: '用户名称', operate: "LIKE"
                        },
                        {
                            field: 'content', title: '评论内容',
                        },
                        {
                            field: 'createtime_text', title: '评论时间'
                        },


                    ]
                })
                Table.api.bindevent(Commenttable);
            },
        }
    }
    return Controller
})