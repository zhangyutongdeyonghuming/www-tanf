<?php

require './api/PHPMailer/src/Exception.php';
require './api/PHPMailer/src/PHPMailer.php';
require './api/PHPMailer/src/SMTP.php';
session_start();


function sendmail($to, $title, $content)
{
	//引入PHPmailer的核心文件，使用require_once包含避免出现PHPmailer类重复定义的警告
	// require_once("class.phpmailer.php");
	// require_once("class.smtp.php");
	//实例化PHPMailer核心类
	$mail = new PHPMailer\PHPMailer\PHPMailer();
	//启用smtp的debug进行调试
	$mail->SMTPDebug = 1;
	//使用smtp鉴权方式发送邮件
	$mail->isSMTP();
	//SMTP需要鉴权，这个必须是true
	$mail->SMTPAuth = true;
	//链接QQ域名邮箱的服务器地址
	$mail->Host = 'smtp.qq.com';
	//设置使用ssl加密方式登录鉴权
	$mail->SMTPSecure = 'ssl';
	//设置ssl连接smtp服务器的远程服务器端口号
	$mail->Port = 465;
	//设置发件人的主机域，可有可无 默认为localhost
	$mail->Hostname = '';
	//设置发送的邮件的编码
	$mail->CharSet = 'UTF-8';
	//设置发件人的姓名 显示为发件人
	$mail->FromName = $title;
	//smtp登陆的账号
	$mail->Username = '2646705311@qq.com';
	//密码  使用生成的授权码
	$mail->Password = 'oqzcqzdwtiwwechc';
	//设置发件人的邮箱地址
	$mail->From = '2646705311@qq.com';
	//邮件正文是否为HTML编码 此处是一个方法 不再是属性
	$mail->isHTML(true);
	for ($i = 0; $i < count($to); $i++) {
		$mail->AddAddress($to[$i]);
	}
	$mail->Subject = $title;
	$mail->Body = $content;
	$status = $mail->send();

	//判断与提示信息
	if ($status) {
		return true;
	} else {
		return false;
	}

}

sendmail($receiver, $title, $content);