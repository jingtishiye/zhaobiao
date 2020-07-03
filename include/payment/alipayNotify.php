<?php
require_once('Base.php');
require_once ('alipay.php');
require_once '../common.inc.php';

//��������
$postData = $_POST;
$order_sn = $postData['out_trade_no'];
$bathController = new Base();
$alipay = new Alipay();
//��¼֧����post����
$bathController->logs('log.txt',var_export($postData,true));
//ǩ����֤
if(!$bathController->rsaCheck($bathController->getStr($postData),$bathController::NEW_ALIPUBKE,$postData['sign'],'RSA2')){
    $bathController->logs('log.txt','RSA2_fail');
    exit();
}else{
    $bathController->logs('log.txt','RSA2_success');
}
//֧��״̬��֤
if(!$bathController->checkOrderStatus($postData)){
    $bathController->logs('log.txt','deal_fail');
    exit();
}else{
    $bathController->logs('log.txt','deal_success');
}
$bathController->logs('log.txt','orderId:'.$postData['out_trade_no'] . 'order_price: ' .$postData['total_amount']);
/*$alipay = new Alipay();
$order_sn = 'M40T1593744519RN994';*/
$row = $alipay->dsql->GetOne("SELECT * FROM #@__member_operation WHERE buyid = '{$order_sn}'");
//��ȡ������Ϣ����鶩������Ч��
if(!is_array($row)||$row['sta']==2) {
    $bathController->logs('log.txt',$postData['out_trade_no'].' : have already dealt with');
    exit();
} /*elseif($row['money'] != $postData['total_fee']){
    $postData['out_trade_no'].' : The amount is different from the order amount!';
}*/
$product =    $row['product'];
$pname= $row['pname'];
$pid=$row['pid'];
$alipay->mid = $row['mid'];
$sql = "UPDATE `#@__member_operation` SET `sta`='1' WHERE `buyid`='$order_sn' AND `mid`='".$alipay->mid."'";
$alipay->dsql->ExecuteNoneQuery($sql);
$nrow = $alipay->dsql->GetOne("SELECT num FROM #@__moneycard_type WHERE pname = '{$pname}'");
$dnum = $nrow['num'];
$sql1 = "UPDATE `#@__member` SET `money`=money+'{$nrow['num']}' WHERE `mid`='".$alipay->mid."'";
$oldinf ="�Ѿ���ֵ��".$nrow['num']."��ҵ������ʺţ�";
$sql2=" UPDATE #@__member_operation SET sta=2,oldinfo='$oldinf' WHERE buyid='$order_sn'";
if($alipay->dsql->ExecuteNoneQuery($sql1) && $alipay->dsql->ExecuteNoneQuery($sql2)){
    echo 'success';
}



