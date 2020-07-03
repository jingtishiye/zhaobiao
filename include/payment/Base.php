<?php

require_once ('Rsa.php');
class Base extends Rsa
{
    /**
     * 以下信息需要根据自己实际情况修改
     */
    const PID = '支付宝后台获取';//合作伙伴ID
    const REURL = 'http://zb.canyinyunfu.com/include/payment/alipayReturn.php';//同步通知地址
    const NOURL = 'http://zb.canyinyunfu.com/include/payment/alipayNotify.php';//异步通知地址
    const KEY = '支付宝后台获取';
    const PAYGAGEWAY = 'https://mapi.alipay.com/gateway.do';
    const CHECKURL = 'https://mapi.alipay.com/gateway.do?service=notify_verify&partner=' . self::PID . '&notify_id=';
    const APPPRIKEY = 'MIIEpAIBAAKCAQEApvXHAnr62LaCDLitk9SgEqTtyLxqgZTd/m/R8kKAn+FdgPEAcRfkuzRsha1qGB87vdkff9LIQSrK2cKLk++EU+DH3jsEJXCvjDSVyv6tdbZafkcFP04sVNveU3B7qIn6Jum4/HfeWUr2vas9Err0BxnnpI7FBupNBFst36WxFfDPoiugMXxPG8ZCpaOJfJ5sRE45mtGKyOtM/794mRsluq3QQSLOrXci5lXxtisPwmX4TBPDdIiMR81pdQX8NVjC8LRo74EfZcnl6OPc26uImyuiNNCe+NeGwMbAl7c6UmGun7SEC/1YRFQc1a+aUJV74zrgu/qhg5dNmmwMJ8AivQIDAQABAoIBAAFfhhp8HnIH1jv6jNrpZcOhfZ8oBYw5SDYef1CJwXsPJHC6CrHja2sqvDXBPl0ibKBcF8k6V5FJo890rSczUhhKgfpO6/LO4XK6v/yiaZTNQiNFvEJQB0dPY522EB5LvDXzuCTDa526YxsePGtaBEXGrcQJtI8hAC8n/NGZEELI69JQQE36HaJZqCkIZ4Zbcrc4Ro+0MYSgTrOYHNTJsP6cmnyLEwaJkvYkVKYlpDsrR8A6SB2JEgyxjNxPWcd7mLZxdV0kUYweghNgFr5IzziVDSrdfvjB9aW2hBRm3FVPm0PFZEyYMyhAB6QkN7RaJ3nnIQW7pfFZVv9fGwoxcqECgYEA4bcfJDQUHSKmOh/ftBSpEZj1SBStia8wknqMfKpG1+tCkZ4FtbCMBJYURdxEB5G6/6FRCRk1iNedz38nYPb5eAd9aGoL9ohYtMGAF/chNa63yrE9KqXj3uc0sldIGh8mMmK6t9cmkQUHAzzb511z1as7NGJr/g1tYEH3gWxTbrkCgYEAvVyHNCnaXmXAUzzUMS9zqD6WUP/YYZoMvWqwpRBNhTxfzJQfrVuf1rr7ejqnrv0hRdfdoyu9maK4mY1Hh1/qXHclC6KUp3hALeJxfIXDFAsx8TN3/ApRB3iaOnYh1Y0G94v7MG2YrWLVdptEz6a7JA3qVflMTIvgnqPsgoQZMiUCgYEA3ioaMs43xf2m06svytZTGwkM1CfWL6EKFAMfaE1JcZkUarO5Nv5QsurthV2qcDDWeGpVkrkHYmun3uZu4hf1sFje7PkEUWIevbt/0xbhzy7rpEwTwniJ84pq6ebTBzMFq65VzkGsqizinCM451+qdfMWURdW6DZbI9WKQg9Xh7ECgYAtjRWFCdBaxtVF8KsGjeiffza/k1vk1p7b7c9CVbKgK07MMpGQLSs9B0u5MmTl5kRbvbq2Jq58R7VqUp7zRLJmvc5uhxSFWJrvrron/zFXH/5KjFv/iCfHyU4oZARtj3gukkDBfIuuocjBuEYMQYpg+Ov29HpTK47D+qDJIKfC9QKBgQCYGXNwGRu3+UTveDZ4QWn8iRPjmbZ4WMmxqS1MAYrJ6VTMZXHLCXmhh44H6bRCcicttlT4NxNkLlh8v4lTfYd6qs7bWdSc1KkC15BWMolsoK4LZDdomVzHSXbM7i0CAdMWHs5q0I8qLEEqOKnvH4xzDBQruAB3eE7aeP0AD9PHCQ==';
    const ALIPUBKEY = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';
    const APPID = '2021001159671026';
    const NEW_ALIPUBKE = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA87O+4Zqs5Xo8v5vrnm6iPpgf7terSbZS+KIRQwjguHzbQZKYlqZJ51r/ib0ITRhIfjUO2DsINbwXro2gpRfImTXE5hoWBhWcyKjm67jQ/W8cxg1aRnK+wZHNkxGI32Mvy2FjSYzQOh8MNMb3Y5iFQl8Ved7Z1xkdL2KJT5TOikElsOPspRTXbXJIsL6xzWjO2hZN7OjIiKhWKlaImROciWj51n1+hmjqP/49MJx+sQJMLqSlZZ+TT6hnxRJXJkK23YGYEX95o0F2oppBUhLRnfxrWQBHRx2ZTZiUCp3nYMJCM8dlTBEGYR02mwAN2BqG11ss39fFAITPybrGmAAWewIDAQAB';
    const NEW_PAYGATEWAY = 'https://openapi.alipay.com/gateway.do';

    public function getStr($arr,$type = 'RSA'){
        //筛选
        if(isset($arr['sign'])){
            unset($arr['sign']);
        }
        if(isset($arr['sign_type']) && $type == 'RSA'){
            unset($arr['sign_type']);
        }
        //排序
        ksort($arr);
        //拼接
        return  $this->getUrl($arr,false);
    }
    //将数组转换为url格式的字符串
    public function getUrl($arr,$encode = true){
        if($encode){
            return http_build_query($arr);
        }else{
            return urldecode(http_build_query($arr));
        }
    }
    //获取签名MD5
    public function getSign($arr){
        return  md5($this->getStr($arr) . self::KEY );
    }
    //获取含有签名的数组MD5
    public function setSign($arr){
        $arr['sign'] = $this->getSign($arr);
        return $arr;
    }
    //获取签名RSA
    public function getRsaSign($arr){
        return $this->rsaSign($this->getStr($arr), self::APPPRIKEY) ;
    }
    //获取含有签名的数组RSA
    public function setRsaSign($arr){
        $arr['sign'] = $this->getRsaSign($arr);
        return $arr;
    }
    //获取签名RSA2
    public function getRsa2Sign($arr){
        return $this->rsaSign($this->getStr($arr,'RSA2'), self::APPPRIKEY,'RSA2') ;
    }
    //获取含有签名的数组RSA
    public function setRsa2Sign($arr){
        $arr['sign'] = $this->getRsa2Sign($arr);
        return $arr;
    }
    //记录日志
    public function logs($filename,$data){
        file_put_contents('./logs/' . $filename, $data . "\r\n",FILE_APPEND);
    }
    //2.验证签名
    public function checkSign($arr){
        $sign = $this->getSign($arr);
        if($sign == $arr['sign']){
            return true;
        }else{
            return false;
        }
    }

    //验证是否来之支付宝的通知
    public function isAlipay($arr){
        $str = file_get_contents(self::CHECKURL . $arr['notify_id']);
        if($str == 'true'){
            return true;
        }else{
            return false;
        }
    }
    // 4.验证交易状态
    public function checkOrderStatus($arr){
        if($arr['trade_status'] == 'TRADE_SUCCESS' || $arr['trade_status'] == 'TRADE_FINISHED'){
            return true;
        } else {
            return false;
        }
    }
}