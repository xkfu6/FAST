define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function()
        {
            //请求地址的设置
            Table.api.init({
                extend:{
                    index_url: `business/visit/index`,
                    add_url: `business/visit/add`,
                    edit_url: `business/visit/edit`,
                    del_url: `business/visit/del`,
                    table: 'business_visit',
                }
            })

            var table = $("#table")

            //表格初始化
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id', //默认主键字段名
                sortName: 'createtime', //排序的字段名
                sortOrder: 'desc', //排序的方式
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id'), sortable: true, operate:false },
                        { 
                            field: 'createtime', 
                            title: __('VisitTime'), 
                            formatter: Table.api.formatter.datetime, 
                            operate: 'RANGE', 
                            addclass: 'datetimerange', 
                            sortable: true
                        },
                        { field: 'admin.nickname', title: __('BusinessAdmin'), operate: 'LIKE'},
                        { 
                            field: 'content', 
                            title: __('VisitContent'), 
                            cellStyle:function (value, row, index)
                            {
                                return {
                                    css: {
                                        "white-space": "nowrap",
                                        "text-overflow": "ellipsis",
                                        "overflow": "hidden",
                                        "max-width":"400px"
                                    }
                                }
                            },
                            formatter:function (value, row, index)
                            {
                                if(value)
                                {
                                value = value.replace(/<.*?>/g,"")
                                }else
                                {
                                value = "暂无回访内容"
                                }
            
                                var span=document.createElement('span')
                                span.setAttribute('title',value)
                                span.innerHTML = value
                                return span.outerHTML
                            },
                            operate: 'LIKE' 
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                        }
                    ]
                ]
            });

            Table.api.bindevent(table);

            Controller.api.bindevent()
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
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
