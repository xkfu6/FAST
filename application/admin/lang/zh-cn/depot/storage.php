<?php

return [
    'Id'         => '主键',
    'Code'       => '单据编号-自动生成',
    'Supplierid' => '供应商id',
    'Type'       => '入库类型：
1：直销入库
2：退货入库
',
    'Amount'     => '总价',
    'Createtime' => '制单日期',
    'Remark'     => '备注',
    'Status'     => '0：待审批
1：审批失败
2：待入库
3：入库完成',
    'Adminid'    => '入库人员',
    'Reviewerid' => '审核人员',
    'Deletetime' => '软删除字段'
];
