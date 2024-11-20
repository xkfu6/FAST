define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'subject/teacher/index',
                    add_url: 'subject/teacher/add',
                    edit_url: 'subject/teacher/edit',
                    del_url: 'subject/teacher/del',
                    follow_url: 'subject/teacher/follow',
                    subject_url: 'subject/teacher/subject',
                    multi_url: 'subject/teacher/multi',
                    import_url: 'subject/teacher/import',
                    table: 'subject_teacher',
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
                        { field: 'avatar_text', title: __('Avatar'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image },
                        { field: 'job', title: __('Job'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'follow',
                                    title: '关注列表',
                                    icon: 'fa fa-users',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    extend: 'data-toggle=\'tooltip\' data-area= \'["100%", "100%"]\'',
                                    url: $.fn.bootstrapTable.defaults.extend.follow_url,
                                },
                                {
                                    name: 'subject',
                                    title: '课程列表',
                                    icon: 'fa fa-align-justify',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    extend: 'data-toggle=\'tooltip\' data-area= \'["100%", "100%"]\'',
                                    url: $.fn.bootstrapTable.defaults.extend.subject_url,
                                },
                            ]
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
        follow: function () {
            var ids = Fast.api.query('ids')

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: `subject/teacher/follow?ids=${ids}`,
                    table: 'subject_teacher',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                columns: [
                    [
                        { field: 'createtime', title: '关注时间', sortable: true, operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'business.nickname', title: '客户名称', operate: 'LIKE' },
                        { field: 'business.mobile', title: '手机号', operate: 'LIKE' },
                        { field: 'business.gender_text', title: '性别', sortable: false, searchable: false },
                        {
                            field: 'business.deal', title: '成交状态'
                            , searchList: { "0": __('未成交'), "1": __('已成交') }, formatter: Table.api.formatter.normal
                        },
                        { field: 'business.auth', title: '认证状态', searchList: { "0": __('未认证'), "1": __('已认证') }, formatter: Table.api.formatter.normal },
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        subject: function () {
            var ids = Fast.api.query('ids')

            Table.api.init({
                extend: {
                    index_url: `subject/teacher/subject?ids=${ids}`, //列表查询的请求控制器方法
                    add_url: 'subject/subject/add', //添加的控制器地址
                    edit_url: 'subject/subject/edit', //编辑的控制器地址
                    del_url: 'subject/subject/del', //删除的控制器地址
                    info_url: 'subject/info/index', //课程详情地址
                    chapter_url: 'subject/chapter/index', //章节详情地址
                    table: 'subject',
                }
            });

            //获取view视图里面的dom元素table元素
            var table = $("#table")

            //渲染列表数据
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url, //请求地址
                toolbar: ".toolbar", //工具栏
                pk: 'id', //默认主键字段名
                sortName: 'createtime', //排序的字段名
                sortOrder: 'desc', //排序的方式
                columns: [ //渲染的字段部分
                    { checkbox: true },
                    { field: 'id', title: 'ID', operate: false, sortable: true },
                    { field: 'title', title: '课程名称', operate: 'LIKE' },
                    { field: 'category.name', title: '课程分类' },
                    {
                        field: 'thumbs_text',
                        title: '课程图片',
                        operate: false,
                        formatter: Table.api.formatter.image
                    },
                    { field: 'price', title: '课程价格', operate: false, sortable: true },
                    { field: 'likes_text', title: '点赞数量', operate: false },
                    {
                        field: 'createtime',
                        title: '创建时间',
                        operate: 'RANGE',
                        addclass: 'datetimerange',
                        sortable: true,
                        formatter: Table.api.formatter.datetime
                    },
                    //最后一排的操作按钮组
                    {
                        field: "operate",
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        buttons: [
                            {
                                name: 'info',
                                title: function (data) {
                                    return `${data.title}-课程详情`
                                },
                                icon: 'fa fa-ellipsis-h',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.info_url,
                                extend: 'data-toggle=\'tooltip\' data-area= \'["80%", "100%"]\'',
                            },
                            {
                                name: 'chapter',
                                title: function (data) {
                                    return `${data.title}-章节详情`
                                },
                                icon: 'fa fa-align-justify',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.chapter_url,
                                extend: 'data-toggle=\'tooltip\' data-area= \'["80%", "100%"]\'',
                            }
                        ]
                    }
                ]
            })

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
