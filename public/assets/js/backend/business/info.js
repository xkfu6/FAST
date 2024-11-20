// require.js 模块化 前置依赖的
//创建一个标准模块
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function() 
        {
            // console.log($('a[data-toggle]')) //获取选项卡头部
            // shown.bs.tab 第三方自己封装的事件，不是源生事件
            $(`a[data-toggle='tab']`).on('shown.bs.tab', function(){
                // console.log($(this).attr('href'))   //#visit
                //通过href链接，找出所对应的选项卡的盒子容器
                // console.log($("#visit"))   //#visit ==  id="visit"
                // console.log($($(this).attr('href')))   //#visit ==  id="visit"
                var name = $(this).attr('href') //info receive visit
                name = name.substring(1)

                try{
                    Controller.table[name]()  //触发对象下方法
                }catch(err){layer.msg(`无选项卡方法:${name}`)}
            })
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        table: { //选项卡对象 table是自定义命名的
            info: function()
            {
                console.log('个人资料')

                Controller.api.bindevent()
            },
            receive: function () 
            {
                //在JS控制器获取url地址上的ids参数
                var ids = Fast.api.query('ids');

                Table.api.init({
                    extend: {
                        index_url: `business/info/receive?ids=${ids}`,
                        table: 'business_receive',
                    }
                });

                var ReceiveTable = $("#ReceiveTable");

                ReceiveTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    toolbar: "#ReceiveToolbar", //工具栏
                    pk: 'id', //默认主键字段名
                    sortName: 'applytime', //排序的字段名
                    sortOrder: 'desc', //排序的方式
                    columns: [
                        [
                            { 
                                field: 'applytime', 
                                title: __('BusinessApplytime'), 
                                sortable: true, 
                                formatter: Table.api.formatter.datetime, 
                                operate: 'RANGE', 
                                addclass: 'datetimerange'
                            },
                            { field: 'admin.nickname', title: __('BusinessApply'), sortable: false, operate: 'LIKE' },
                            {
                                field: 'status', 
                                title: __('Status'), 
                                formatter: Table.api.formatter.status,
                                searchList: { apply: __('Apply'), allot: __('Allot'), recovery: __('Recovery')},
                                operate: 'LIKE',
                            },
                        ]
                    ]
                });

                Table.api.bindevent(ReceiveTable);
            },
            visit: function()
            {
                //获取当前客户的ID
                var ids = Fast.api.query('ids')
                
                //请求地址的设置
                Table.api.init({
                    extend:{
                        index_url: `business/visit/index?ids=${ids}`,
                        add_url: `business/visit/add?ids=${ids}`,
                        edit_url: `business/visit/edit`,
                        del_url: `business/visit/del`,
                        table: 'business_visit',
                    }
                })

                var VisitTable = $("#VisitTable")

                //表格初始化
                VisitTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    toolbar: "#VisitToolbar", //工具栏
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
                                formatter: Table.api.formatter.content, 
                                class: 'autocontent', 
                                hover:true,
                                operate: 'LIKE' 
                            },
                            {
                                field: 'operate',
                                title: __('Operate'),
                                table: VisitTable,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                            }
                        ]
                    ]
                });

                Table.api.bindevent(VisitTable);

                //通过dom元素修改窗口大小
                $('.btn-add').data('area', ['30%', '50%']);

                Controller.api.bindevent()
            }
        }
    };


    //返回值
    return Controller;
})