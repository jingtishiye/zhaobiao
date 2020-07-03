<?php
require_once('Base.php');

/**
 * 支付宝接口类
 */
class Alipay
{
    var $dsql;
    var $mid;
    var $return_url = "/plus/carbuyaction.php?dopost=return";

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function __construct()
    {
        $this->Alipay();
    }

    function Alipay()
    {
        global $dsql;
        $this->dsql = $dsql;
    }

    function test(){
        echo 'test msg!';
    }
    /**
     *  设定接口会送地址
     *
     *  例如: $this->SetReturnUrl($cfg_basehost."/tuangou/control/index.php?ac=pay&orderid=".$p2_Order)
     *
     * @param string $returnurl 会送地址
     * @return    void
     */
    function SetReturnUrl($returnurl = '')
    {
        if (!empty($returnurl)) {
            $this->return_url = $returnurl;
        }
    }

    /**
     * 生成支付代码
     * @param array $order 订单信息
     * @param array $payment 支付方式信息
     */
    function GetCode($order, $payment)
    {
        global $cfg_basehost, $cfg_cmspath, $cfg_soft_lang;
        $charset = $cfg_soft_lang;
        //对于二级目录的处理
        if (!empty($cfg_cmspath)) $cfg_basehost = $cfg_basehost . '/' . $cfg_cmspath;

        $real_method = $payment['alipay_pay_method'];

        switch ($real_method) {
            case '0':
                $service = 'trade_create_by_buyer';
                break;
            case '1':
                $service = 'create_partner_trade_by_buyer';
                break;
            case '2':
                $service = 'create_direct_pay_by_user';
                break;
        }

        $baseController = new Base();
        $pub_params     = [
            'app_id'      => $baseController::APPID,
            'method'      => 'alipay.trade.page.pay', //接口名称 应填写固定值alipay.trade.page.pay
            'format'      => 'JSON', //目前仅支持JSON
            'return_url'  => $baseController::REURL, //同步返回地址
            'charset'     => 'GBK',
            'sign_type'   => 'RSA2',//签名方式
            'sign'        => '', //签名
            'timestamp'   => date('Y-m-d H:i:s'), //发送时间 格式0000-00-00 00:00:00
            'version'     => '1.0', //固定为1.0
            'notify_url'  => $baseController::NOURL, //异步通知地址
            'biz_content' => '', //业务请求参数的集合
        ];
        //业务参数
        $api_params                = [
            'out_trade_no' => $order['out_trade_no'],//商户订单号
            'product_code' => 'FAST_INSTANT_TRADE_PAY', //销售产品码 固定值
            'total_amount' => 0.01, //总价 单位为元
            'subject'      => $order['p_name'], //订单标题
        ];
        $pub_params['biz_content'] = $this->_json_encode($api_params);
        $pub_params                = $baseController->setRsa2Sign($pub_params);
        //吊起支付宝地址
        $url    = $baseController::NEW_PAYGATEWAY . '?' . $baseController->getUrl($pub_params);
        $button = '<div style="text-align:center"><input type="button" onclick="window.open(\'' . $url . '\')" value="立即使用alipay支付宝支付"/></div>';
        /* 清空购物车 */
        require_once DEDEINC . '/shopcar.class.php';
        $cart = new MemberShops();
        $cart->clearItem();
        $cart->MakeOrders();
        return $button;
    }

    /**
     * 由于项目编码为GBK，无法使用json_encode函数
     * 手写json_encode
     */
    function _json_encode($val)
    {
        if (is_string($val)) return '"' . str_replace(array('\\', "\r", "\n", '"', '/', "\t", "\f"), array('\\\\', '\r', '\n', '\\"', '\/', '\t', '\f'), $val) . '"';
        if (is_numeric($val)) return $val;
        if ($val === null) return 'null';
        if ($val === true) return 'true';
        if ($val === false) return 'false';

        $assoc = false;
        $i     = 0;
        foreach ($val as $k => $v) {
            if ($k !== $i++) {
                $assoc = true;
                break;
            }
        }
        $res = array();
        foreach ($val as $k => $v) {
            $v = $this->_json_encode($v);
            if ($assoc) {
                $k = '"' . addslashes($k) . '"';
                $v = $k . ':' . $v;
            }
            $res[] = $v;
        }
        $res = implode(',', $res);
        return ($assoc) ? '{' . $res . '}' : '[' . $res . ']';
    }

    /**
     * 支付宝同步地址
     */
    function returnUrl($order)
    {
        $order = $_GET;
        return $this->examineOrderStatus($order);
    }

    /**
     * 检验商品订单，价格等信息
     */
    function examineOrderStatus($order)
    {
        $baseController = new Base();
        $order_sn = trim($order['out_trade_no']);
        /*判断订单类型*/
        if(preg_match ("/S-P[0-9]+RN[0-9]/",$order_sn)) {
            //检查支付金额是否相符
            $row = $this->dsql->GetOne("SELECT * FROM #@__shops_orders WHERE oid = '{$order_sn}'");
            if ($row['priceCount'] != $order['total_fee'])
            {
                return $msg = "支付失败，支付金额与商品总价不相符!";
            }
            $this->mid = $row['userid'];
            $ordertype="goods";
        }else if (preg_match ("/M[0-9]+T[0-9]+RN[0-9]/", $order_sn)){
            $row = $this->dsql->GetOne("SELECT * FROM #@__member_operation WHERE buyid = '{$order_sn}'");
            //获取订单信息，检查订单的有效性
            /*if(!is_array($row)||$row['sta']==2) return $msg = "您的订单已经处理，请不要重复提交!";
            elseif($row['money'] != $_GET['total_fee']) return $msg = "支付失败，支付金额与商品总价不相符!";*/
            $ordertype = "member";
            $product =    $row['product'];
            $pname= $row['pname'];
            $pid=$row['pid'];
            $this->mid = $row['mid'];
        } else {
            return $msg = "支付失败，您的订单号有问题！";
        }
        if (!$baseController->rsaCheck($baseController->getStr($order), $baseController::NEW_ALIPUBKE, $order['sign'], 'RSA2')) {
            if($ordertype=="goods"){
                if($this->success_db($order_sn))  return $msg = "支付成功!<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
                else  return $msg = "支付失败！<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
            } else if ( $ordertype=="member" ) {
                $oldinf = $this->success_mem($order_sn,$pname,$product,$pid);
                return $oldinf;
            }
        }else{
            $this->log_result("verify_failed");
            return $msg = "支付失败！<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
        }
    }



    /**
     * 响应操作
     */
    function respond()
    {
        if (!empty($_POST)) {
            foreach ($_POST as $key => $data) {
                $_GET[$key] = $data;
            }
        }


        /* 取得订单号 */
        $order_sn = trim($_GET['out_trade_no']);
        /*判断订单类型*/
        if (preg_match("/S-P[0-9]+RN[0-9]/", $order_sn)) {
            //检查支付金额是否相符
            $row = $this->dsql->GetOne("SELECT * FROM #@__shops_orders WHERE oid = '{$order_sn}'");
            if ($row['priceCount'] != $_GET['total_fee']) {
                return $msg = "支付失败，支付金额与商品总价不相符!";
            }
            $this->mid = $row['userid'];
            $ordertype = "goods";
        } else if (preg_match("/M[0-9]+T[0-9]+RN[0-9]/", $order_sn)) {
            $row = $this->dsql->GetOne("SELECT * FROM #@__member_operation WHERE buyid = '{$order_sn}'");
            //获取订单信息，检查订单的有效性
            if (!is_array($row) || $row['sta'] == 2) return $msg = "您的订单已经处理，请不要重复提交!";
            elseif ($row['money'] != $_GET['total_fee']) return $msg = "支付失败，支付金额与商品总价不相符!";
            $ordertype = "member";
            $product   = $row['product'];
            $pname     = $row['pname'];
            $pid       = $row['pid'];
            $this->mid = $row['mid'];
        } else {
            return $msg = "支付失败，您的订单号有问题！";
        }

        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);

        $sign = '';
        foreach ($_GET as $key => $val) {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code' && $key != 'dopost') {
                $sign .= "$key=$val&";
            }
        }

        // $sign = substr($sign, 0, -1) . $payment['alipay_key'];

        if (md5($sign) != $_GET['sign']) {
            return $msg = "支付失败!";
        }

        if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
            if ($ordertype == "goods") {
                if ($this->success_db($order_sn)) return $msg = "success";
                else  return $msg = "fail";
            } else if ($ordertype == "member") {
                $oldinf = $this->success_mem($order_sn, $pname, $product, $pid);
                return $msg = "<font color='red'>" . $oldinf . "</font><br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
            }
        } else {
            $this->log_result("verify_failed");
            return $msg = "支付失败！<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
        }
    }

    /*处理物品交易*/
    function success_db($order_sn)
    {
        //获取订单信息，检查订单的有效性
        $row = $this->dsql->GetOne("SELECT state FROM #@__shops_orders WHERE oid='$order_sn' ");
        if ($row['state'] > 0) {
            return TRUE;
        }
        /* 改变订单状态_支付成功 */
        $sql = "UPDATE `#@__shops_orders` SET `state`='1' WHERE `oid`='$order_sn' AND `userid`='" . $this->mid . "'";
        if ($this->dsql->ExecuteNoneQuery($sql)) {
            $this->log_result("verify_success,订单号:" . $order_sn); //将验证结果存入文件
            return TRUE;
        } else {
            $this->log_result("verify_failed,订单号:" . $order_sn);//将验证结果存入文件
            return FALSE;
        }
    }

    /*处理点卡，会员升级*/
    function success_mem($order_sn, $pname, $product, $pid)
    {
        //更新交易状态为已付款
        $sql = "UPDATE `#@__member_operation` SET `sta`='1' WHERE `buyid`='$order_sn' AND `mid`='" . $this->mid . "'";
        $this->dsql->ExecuteNoneQuery($sql);

        /* 改变点卡订单状态_支付成功 */
        if ($product == "card") {
            $row = $this->dsql->GetOne("SELECT cardid FROM #@__moneycard_record WHERE ctid='$pid' AND isexp='0' ");;
            //如果找不到某种类型的卡，直接为用户增加金币
            if (!is_array($row)) {
                $nrow   = $this->dsql->GetOne("SELECT num FROM #@__moneycard_type WHERE pname = '{$pname}'");
                $dnum   = $nrow['num'];
                $sql1   = "UPDATE `#@__member` SET `money`=money+'{$nrow['num']}' WHERE `mid`='" . $this->mid . "'";
                $oldinf = "ok";
            } else {
                $cardid = $row['cardid'];
                $sql1   = " UPDATE #@__moneycard_record SET uid='" . $this->mid . "',isexp='1',utime='" . time() . "' WHERE cardid='$cardid' ";
                $oldinf = '您的充值密码是：<font color="green">' . $cardid . '</font>';
            }
            //更新交易状态为已关闭
            $sql2 = " UPDATE #@__member_operation SET sta=2,oldinfo='$oldinf' WHERE buyid='$order_sn'";
            if ($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2)) {
                $this->log_result("verify_success,订单号:" . $order_sn); //将验证结果存入文件
                return 'success';
            } else {
                $this->log_result("verify_failed,订单号:" . $order_sn);//将验证结果存入文件
                return "支付失败！";
            }
            /* 改变会员订单状态_支付成功 */
        } else if ($product == "member") {
            $row     = $this->dsql->GetOne("SELECT rank,exptime FROM #@__member_type WHERE aid='$pid' ");
            $rank    = $row['rank'];
            $exptime = $row['exptime'];
            /*计算原来升级剩余的天数*/
            $rs = $this->dsql->GetOne("SELECT uptime,exptime FROM #@__member WHERE mid='" . $this->mid . "'");
            if ($rs['uptime'] != 0 && $rs['exptime'] != 0) {
                $nowtime = time();
                $mhasDay = $rs['exptime'] - ceil(($nowtime - $rs['uptime']) / 3600 / 24) + 1;
                $mhasDay = ($mhasDay > 0) ? $mhasDay : 0;
            }
            //获取会员默认级别的金币和积分数
            $memrank = $this->dsql->GetOne("SELECT money,scores FROM #@__arcrank WHERE rank='$rank'");
            //更新会员信息
            $sql1 = " UPDATE #@__member SET rank='$rank',money=money+'{$memrank['money']}',
                       scores=scores+'{$memrank['scores']}',exptime='$exptime'+'$mhasDay',uptime='" . time() . "' 
                       WHERE mid='" . $this->mid . "'";
            //更新交易状态为已关闭
            $sql2 = " UPDATE #@__member_operation SET sta='2',oldinfo='会员升级成功!' WHERE buyid='$order_sn' ";
            if ($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2)) {
                $this->log_result("verify_success,订单号:" . $order_sn); //将验证结果存入文件
                return "会员升级成功！";
            } else {
                $this->log_result("verify_failed,订单号:" . $order_sn);//将验证结果存入文件
                return "会员升级失败！";
            }
        }
    }

    function log_result($word)
    {
        global $cfg_cmspath;
        $fp = fopen(dirname(__FILE__) . "/../../data/payment/log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, $word . ",执行日期:" . strftime("%Y-%m-%d %H:%I:%S", time()) . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}//End API