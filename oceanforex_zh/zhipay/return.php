﻿<?php
session_start (); // 开启session
require_once (dirname ( __FILE__ ) . "/../include/common.inc.php");
require_once (dirname ( __FILE__ ) . '/../data/common.inc.php');
require_once (dirname ( __FILE__ ) . '/../include/oxwindow.class.php');
require_once (dirname ( __FILE__ ) . '/../lib/nusoap.php');

header("content-Type: text/html; charset=utf-8");
$MD5key = "FoGdkOiz";
$BillNo = $_REQUEST["BillNo"];
$Amount = $_REQUEST["Amount"];
$Succeed = $_REQUEST["Succeed"];
$Result = $_REQUEST["Result"];
$MD5info = $_REQUEST["MD5info"]; 
$Remark = $_REQUEST["Remark"];
$md5src = $BillNo.$Amount.$Succeed.$MD5key;
$md5sign = strtoupper(md5($md5src));
$accountid = $_SESSION['account'];
if ($MD5info==$md5sign){
	if ($Succeed=="88") {
		// 发送邮件通知
		// 1.发送给客户 2.发送给后台
		$mailclient = new nusoap_client ( "http://localhost:8077/ApiService?wsdl", "wsdl" );
		
		$db_connection = mysql_connect ( $cfg_dbhost, $cfg_dbuser, $cfg_dbpwd ) or die ( "网络错误请重试" );
		mysql_query ( "set names 'utf8'" );
		mysql_select_db ( $cfg_dbname, $db_connection );
		
		$tpl = mysql_query ( "SELECT content from dede_mailtpl where tplname='用户入金'" );
		$accountresult = mysql_query ( "SELECT * from dede_diyform2 where id='$accountid'" );
		$tplarray = mysql_fetch_array ( $tpl );
		$account = mysql_fetch_array ( $accountresult );
		$name = $account ["mingzi"];
		$email = $account ["youxiang"];
		$mt4Account = $account ["mt4account"];
		$content = str_replace ( "{name}", $name, $tplarray ["content"] );
		$content = str_replace ( "{amount}", $Amount, $content );
		$content = str_replace ( "{account}", $mt4Account, $content );
		// 发给用户的
		$args = array (
				"subject" => "用户入金",
				"body" => $content,
				"form" => "service@oceanforex.com",
				"fromname" => "OceanForex Customer Service",
				"to" => $email,
				"toname" => $name,
				"smtp" => "smtp.exmail.qq.com",
				"formuser" => "service@oceanforex.com",
				"frompwd" => ""
		);
		$mailclient->soap_defencoding = 'utf-8';
		$mailclient->decode_utf8 = false;
		$mailclient->xml_encoding = 'utf-8';
		$mailmt4Result = $mailclient->call ( "SendMail", $args );
		
		$argself = array (
				"subject" => "用户入金通知",
				"body" => "<html><body>" . "姓名：" . $name . "<br/>" . "邮箱：" . $email . "<br/>" . "金额：" . $Amount . "<br/>" . "MT4帐号：" . $mt4Account . "<br/>" . "</body></html>",
				"form" => "service@oceanforex.com",
				"fromname" => "OceanForex Customer Service",
				"to" => "manager@oceanforex.com",
				"toname" => "客户入金通知",
				"smtp" => "smtp.exmail.qq.com",
				"formuser" => "service@oceanforex.com",
				"frompwd" => ""
		);
		$mailmt4Result = $mailclient->call ( "SendMail", $argself );
		
		echo "<script LANGUAGE='JavaScript'>".
				"alert('支付成功，金额：".$Amount."，请耐心等待我们审核！');window.location='/account/'"."</script>";
		exit();
// 		echo "支付成功".'<br>';
// 		echo "订单号=".$BillNo.'<br>';
// 		echo "金额=".$Amount.'<br>';
	} else {
		echo "支付失败（{$Succeed}）";
	}
} else {
	echo "失败，信息可能被篡改";
}
?>
