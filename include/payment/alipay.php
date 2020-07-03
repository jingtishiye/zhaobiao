<?php
require_once('Base.php');

/**
 * ֧�����ӿ���
 */
class Alipay
{
    var $dsql;
    var $mid;
    var $return_url = "/plus/carbuyaction.php?dopost=return";

    /**
     * ���캯��
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
     *  �趨�ӿڻ��͵�ַ
     *
     *  ����: $this->SetReturnUrl($cfg_basehost."/tuangou/control/index.php?ac=pay&orderid=".$p2_Order)
     *
     * @param string $returnurl ���͵�ַ
     * @return    void
     */
    function SetReturnUrl($returnurl = '')
    {
        if (!empty($returnurl)) {
            $this->return_url = $returnurl;
        }
    }

    /**
     * ����֧������
     * @param array $order ������Ϣ
     * @param array $payment ֧����ʽ��Ϣ
     */
    function GetCode($order, $payment)
    {
        global $cfg_basehost, $cfg_cmspath, $cfg_soft_lang;
        $charset = $cfg_soft_lang;
        //���ڶ���Ŀ¼�Ĵ���
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
            'method'      => 'alipay.trade.page.pay', //�ӿ����� Ӧ��д�̶�ֵalipay.trade.page.pay
            'format'      => 'JSON', //Ŀǰ��֧��JSON
            'return_url'  => $baseController::REURL, //ͬ�����ص�ַ
            'charset'     => 'GBK',
            'sign_type'   => 'RSA2',//ǩ����ʽ
            'sign'        => '', //ǩ��
            'timestamp'   => date('Y-m-d H:i:s'), //����ʱ�� ��ʽ0000-00-00 00:00:00
            'version'     => '1.0', //�̶�Ϊ1.0
            'notify_url'  => $baseController::NOURL, //�첽֪ͨ��ַ
            'biz_content' => '', //ҵ����������ļ���
        ];
        //ҵ�����
        $api_params                = [
            'out_trade_no' => $order['out_trade_no'],//�̻�������
            'product_code' => 'FAST_INSTANT_TRADE_PAY', //���۲�Ʒ�� �̶�ֵ
            'total_amount' => 0.01, //�ܼ� ��λΪԪ
            'subject'      => $order['p_name'], //��������
        ];
        $pub_params['biz_content'] = $this->_json_encode($api_params);
        $pub_params                = $baseController->setRsa2Sign($pub_params);
        //����֧������ַ
        $url    = $baseController::NEW_PAYGATEWAY . '?' . $baseController->getUrl($pub_params);
        $button = '<div style="text-align:center"><input type="button" onclick="window.open(\'' . $url . '\')" value="����ʹ��alipay֧����֧��"/></div>';
        /* ��չ��ﳵ */
        require_once DEDEINC . '/shopcar.class.php';
        $cart = new MemberShops();
        $cart->clearItem();
        $cart->MakeOrders();
        return $button;
    }

    /**
     * ������Ŀ����ΪGBK���޷�ʹ��json_encode����
     * ��дjson_encode
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
     * ֧����ͬ����ַ
     */
    function returnUrl($order)
    {
        $order = $_GET;
        return $this->examineOrderStatus($order);
    }

    /**
     * ������Ʒ�������۸����Ϣ
     */
    function examineOrderStatus($order)
    {
        $baseController = new Base();
        $order_sn = trim($order['out_trade_no']);
        /*�ж϶�������*/
        if(preg_match ("/S-P[0-9]+RN[0-9]/",$order_sn)) {
            //���֧������Ƿ����
            $row = $this->dsql->GetOne("SELECT * FROM #@__shops_orders WHERE oid = '{$order_sn}'");
            if ($row['priceCount'] != $order['total_fee'])
            {
                return $msg = "֧��ʧ�ܣ�֧���������Ʒ�ܼ۲����!";
            }
            $this->mid = $row['userid'];
            $ordertype="goods";
        }else if (preg_match ("/M[0-9]+T[0-9]+RN[0-9]/", $order_sn)){
            $row = $this->dsql->GetOne("SELECT * FROM #@__member_operation WHERE buyid = '{$order_sn}'");
            //��ȡ������Ϣ����鶩������Ч��
            /*if(!is_array($row)||$row['sta']==2) return $msg = "���Ķ����Ѿ������벻Ҫ�ظ��ύ!";
            elseif($row['money'] != $_GET['total_fee']) return $msg = "֧��ʧ�ܣ�֧���������Ʒ�ܼ۲����!";*/
            $ordertype = "member";
            $product =    $row['product'];
            $pname= $row['pname'];
            $pid=$row['pid'];
            $this->mid = $row['mid'];
        } else {
            return $msg = "֧��ʧ�ܣ����Ķ����������⣡";
        }
        if (!$baseController->rsaCheck($baseController->getStr($order), $baseController::NEW_ALIPUBKE, $order['sign'], 'RSA2')) {
            if($ordertype=="goods"){
                if($this->success_db($order_sn))  return $msg = "֧���ɹ�!<br> <a href='/'>������ҳ</a> <a href='/member'>��Ա����</a>";
                else  return $msg = "֧��ʧ�ܣ�<br> <a href='/'>������ҳ</a> <a href='/member'>��Ա����</a>";
            } else if ( $ordertype=="member" ) {
                $oldinf = $this->success_mem($order_sn,$pname,$product,$pid);
                return $oldinf;
            }
        }else{
            $this->log_result("verify_failed");
            return $msg = "֧��ʧ�ܣ�<br> <a href='/'>������ҳ</a> <a href='/member'>��Ա����</a>";
        }
    }



    /**
     * ��Ӧ����
     */
    function respond()
    {
        if (!empty($_POST)) {
            foreach ($_POST as $key => $data) {
                $_GET[$key] = $data;
            }
        }


        /* ȡ�ö����� */
        $order_sn = trim($_GET['out_trade_no']);
        /*�ж϶�������*/
        if (preg_match("/S-P[0-9]+RN[0-9]/", $order_sn)) {
            //���֧������Ƿ����
            $row = $this->dsql->GetOne("SELECT * FROM #@__shops_orders WHERE oid = '{$order_sn}'");
            if ($row['priceCount'] != $_GET['total_fee']) {
                return $msg = "֧��ʧ�ܣ�֧���������Ʒ�ܼ۲����!";
            }
            $this->mid = $row['userid'];
            $ordertype = "goods";
        } else if (preg_match("/M[0-9]+T[0-9]+RN[0-9]/", $order_sn)) {
            $row = $this->dsql->GetOne("SELECT * FROM #@__member_operation WHERE buyid = '{$order_sn}'");
            //��ȡ������Ϣ����鶩������Ч��
            if (!is_array($row) || $row['sta'] == 2) return $msg = "���Ķ����Ѿ������벻Ҫ�ظ��ύ!";
            elseif ($row['money'] != $_GET['total_fee']) return $msg = "֧��ʧ�ܣ�֧���������Ʒ�ܼ۲����!";
            $ordertype = "member";
            $product   = $row['product'];
            $pname     = $row['pname'];
            $pid       = $row['pid'];
            $this->mid = $row['mid'];
        } else {
            return $msg = "֧��ʧ�ܣ����Ķ����������⣡";
        }

        /* �������ǩ���Ƿ���ȷ */
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
            return $msg = "֧��ʧ��!";
        }

        if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
            if ($ordertype == "goods") {
                if ($this->success_db($order_sn)) return $msg = "success";
                else  return $msg = "fail";
            } else if ($ordertype == "member") {
                $oldinf = $this->success_mem($order_sn, $pname, $product, $pid);
                return $msg = "<font color='red'>" . $oldinf . "</font><br> <a href='/'>������ҳ</a> <a href='/member'>��Ա����</a>";
            }
        } else {
            $this->log_result("verify_failed");
            return $msg = "֧��ʧ�ܣ�<br> <a href='/'>������ҳ</a> <a href='/member'>��Ա����</a>";
        }
    }

    /*������Ʒ����*/
    function success_db($order_sn)
    {
        //��ȡ������Ϣ����鶩������Ч��
        $row = $this->dsql->GetOne("SELECT state FROM #@__shops_orders WHERE oid='$order_sn' ");
        if ($row['state'] > 0) {
            return TRUE;
        }
        /* �ı䶩��״̬_֧���ɹ� */
        $sql = "UPDATE `#@__shops_orders` SET `state`='1' WHERE `oid`='$order_sn' AND `userid`='" . $this->mid . "'";
        if ($this->dsql->ExecuteNoneQuery($sql)) {
            $this->log_result("verify_success,������:" . $order_sn); //����֤��������ļ�
            return TRUE;
        } else {
            $this->log_result("verify_failed,������:" . $order_sn);//����֤��������ļ�
            return FALSE;
        }
    }

    /*����㿨����Ա����*/
    function success_mem($order_sn, $pname, $product, $pid)
    {
        //���½���״̬Ϊ�Ѹ���
        $sql = "UPDATE `#@__member_operation` SET `sta`='1' WHERE `buyid`='$order_sn' AND `mid`='" . $this->mid . "'";
        $this->dsql->ExecuteNoneQuery($sql);

        /* �ı�㿨����״̬_֧���ɹ� */
        if ($product == "card") {
            $row = $this->dsql->GetOne("SELECT cardid FROM #@__moneycard_record WHERE ctid='$pid' AND isexp='0' ");;
            //����Ҳ���ĳ�����͵Ŀ���ֱ��Ϊ�û����ӽ��
            if (!is_array($row)) {
                $nrow   = $this->dsql->GetOne("SELECT num FROM #@__moneycard_type WHERE pname = '{$pname}'");
                $dnum   = $nrow['num'];
                $sql1   = "UPDATE `#@__member` SET `money`=money+'{$nrow['num']}' WHERE `mid`='" . $this->mid . "'";
                $oldinf = "ok";
            } else {
                $cardid = $row['cardid'];
                $sql1   = " UPDATE #@__moneycard_record SET uid='" . $this->mid . "',isexp='1',utime='" . time() . "' WHERE cardid='$cardid' ";
                $oldinf = '���ĳ�ֵ�����ǣ�<font color="green">' . $cardid . '</font>';
            }
            //���½���״̬Ϊ�ѹر�
            $sql2 = " UPDATE #@__member_operation SET sta=2,oldinfo='$oldinf' WHERE buyid='$order_sn'";
            if ($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2)) {
                $this->log_result("verify_success,������:" . $order_sn); //����֤��������ļ�
                return 'success';
            } else {
                $this->log_result("verify_failed,������:" . $order_sn);//����֤��������ļ�
                return "֧��ʧ�ܣ�";
            }
            /* �ı��Ա����״̬_֧���ɹ� */
        } else if ($product == "member") {
            $row     = $this->dsql->GetOne("SELECT rank,exptime FROM #@__member_type WHERE aid='$pid' ");
            $rank    = $row['rank'];
            $exptime = $row['exptime'];
            /*����ԭ������ʣ�������*/
            $rs = $this->dsql->GetOne("SELECT uptime,exptime FROM #@__member WHERE mid='" . $this->mid . "'");
            if ($rs['uptime'] != 0 && $rs['exptime'] != 0) {
                $nowtime = time();
                $mhasDay = $rs['exptime'] - ceil(($nowtime - $rs['uptime']) / 3600 / 24) + 1;
                $mhasDay = ($mhasDay > 0) ? $mhasDay : 0;
            }
            //��ȡ��ԱĬ�ϼ���Ľ�Һͻ�����
            $memrank = $this->dsql->GetOne("SELECT money,scores FROM #@__arcrank WHERE rank='$rank'");
            //���»�Ա��Ϣ
            $sql1 = " UPDATE #@__member SET rank='$rank',money=money+'{$memrank['money']}',
                       scores=scores+'{$memrank['scores']}',exptime='$exptime'+'$mhasDay',uptime='" . time() . "' 
                       WHERE mid='" . $this->mid . "'";
            //���½���״̬Ϊ�ѹر�
            $sql2 = " UPDATE #@__member_operation SET sta='2',oldinfo='��Ա�����ɹ�!' WHERE buyid='$order_sn' ";
            if ($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2)) {
                $this->log_result("verify_success,������:" . $order_sn); //����֤��������ļ�
                return "��Ա�����ɹ���";
            } else {
                $this->log_result("verify_failed,������:" . $order_sn);//����֤��������ļ�
                return "��Ա����ʧ�ܣ�";
            }
        }
    }

    function log_result($word)
    {
        global $cfg_cmspath;
        $fp = fopen(dirname(__FILE__) . "/../../data/payment/log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, $word . ",ִ������:" . strftime("%Y-%m-%d %H:%I:%S", time()) . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}//End API