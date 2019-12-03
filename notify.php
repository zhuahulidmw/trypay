<?php
include './Base.php';
/* 
 * 黎明互联
 * https://www.liminghulian.com/
 */
/*
 *  1.获取数据。支付宝返回的异步数据是post格式的

	以下数据验证通过，才算支付成功
	
 *  2.验证签名。验证支付宝返回的post数据中包含的签名，和你发起支付请求时生成的签名，是否相同
	注意：发起支付请求时，需要你生成签名。而支付宝返回的post数据中，已经包含签名，你要先将post数据生成签名，再同post数据中的签名作比较
	
 *  3.验证是否来自支付宝的请求
	支付宝返回的post数据中，包含一个notify_id，
 
 *  4.验证交易状态
 *  5.验证订单号和金额
 *  6.更改订单状态
 *  
 */

/*
	接收异步通知的界面
*/
class Notify extends Base
{
    public function __construct() {
        // 1.获取数据。异步返回的数据是post格式的
        $postData = $_POST;
        
        //2.验证签名MD5和RSA
        if($postData['sign_type'] == 'MD5'){
            if(!$this->checkSign($postData)){
                $this->logs('log.txt', 'MD5签名失败!');
                exit();
            }else{
                $this->logs('log.txt', 'MD5签名成功!');
            }
		//使用支付宝官方提供的方法来验证RSA签名
        }elseif($postData['sign_type'] == 'RSA'){
            if(!$this->rsaCheck($this->getStr($postData), self::ALIPUBKEY, $postData['sign']) ){
                $this->logs('log.txt', 'RSA签名失败!');
                exit();
            }else{
                $this->logs('log.txt', 'RSA签名成功!');
            }
		//使用支付宝官方提供的方法来验证RSA2签名。需指定类型为RSA2
        }elseif($postData['sign_type'] == 'RSA2'){
            if(!$this->rsaCheck($this->getStr($postData), self::NEW_ALIPUBKE, $postData['sign'],'RSA2') ){
                $this->logs('log.txt', 'RSA2签名失败!');
                exit();
            }else{
                $this->logs('log.txt', 'RSA2签名成功!');
            }
        }else{
            exit('签名方式有误');
        }
        //验证是否来自支付宝的请求
        if(!$this->isAlipay($postData)){
            $this->logs('log.txt', '不是来之支付宝的通知!');
            exit();
        }else{
            $this->logs('log.txt', '是来之支付宝的通知验证通过!');
        }
        // 4.验证交易状态
        if(!$this->checkOrderStatus($postData)){
             $this->logs('log.txt', '交易未完成!');
             exit();
        }else{
             $this->logs('log.txt', '交易成功!');
        }
        //5. 验证订单号和金额。此步已省略，需完善
        //获取支付发送过来的订单号  在商户订单表中查询对应的金额 然后和支付宝发送过来的做对比。此步已省略，需完善
         $this->logs('log.txt', '订单号:' . $postData['out_trade_no'] . '订单金额:' . $postData['total_amount']);
         
        //更改订单状态。这个success必须输出！！否则支付宝会认为信息未送达！！
		//此页面不会展示给用户，放心输出即可。支付完成后展示的页面，是return.php（接收同步通知的页面）
         echo 'success';
    }
}

$obj = new Notify();