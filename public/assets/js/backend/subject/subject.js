define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'subject/subject/index',
                    add_url: 'subject/subject/add',
                    edit_url: 'subject/subject/edit',
                    del_url: 'subject/subject/del',
                    multi_url: 'subject/subject/multi',
                    import_url: 'subject/subject/import',
                    chapter_url: 'subject/chapter/index', //章节详情地址
                    info_url: 'subject/info/index', //课程详情地址
                    table: 'subject',
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
                    { checkbox: true },
                    { field: 'id', title: "ID" },
                    { field: 'title', title: __('Title'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                    { field: 'thumbs_text', title: "课程图片", formatter: Table.api.formatter.image },
                    { field: 'price', title: __('Price'), operate: 'BETWEEN' },
                    { field: 'likes_text', title: '点赞数' },
                    { field: 'category.name', title: __('Cateid') },
                    { field: 'teacher.name', title: "课程老师" },
                    { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        buttons: [
                            {
                                name: 'info',
                                title: '课程详情',
                                icon: 'fa fa-ellipsis-h',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.info_url,
                                extend: 'data-toggle=\'tooltip\' data-area= \'["90%", "100%"]\'',
                            },
                            {
                                name: 'chapter',
                                title: '章节',
                                icon: 'fa fa-align-justify',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.chapter_url,
                                extend: 'data-toggle=\'tooltip\' data-area= \'["80%", "100%"]\'',
                            },

                        ]
                    }
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
                url: 'subject/subject/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'title', title: __('Title'), align: 'left' },
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
                                    url: 'subject/subject/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'subject/subject/destroy',
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

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        chapter: function () {
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
