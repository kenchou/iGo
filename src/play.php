<?php
//debug setting
//ini_set('error_log', dirname(__FILE__) . '/' . basename(__FILE__, '.php').'.log');

//ini_set('display_errors', true);
//ini_set('log_errors',true);

/**
 * I-go RSET server
 * 具体方法参见 Server.php
 * 
 * ?method=test&index={index_number}
 * ?method=skip
 * ?method=surrender
 * 
 * @see Server.php
 */
session_start();
require_once 'config.inc.php';

require_once 'Zend/Rest/Server.php';
require_once 'Server.php';


$server = new Zend_Rest_Server();   // 创建 REST 服务
$server->setClass('Server');    //注册服务类
$server->returnResponse(true);  //设置true, 可返回相应内容。否则直接输出
$response = $server->handle();  //获取响应内容， XML 字符串。

require_once 'Zend/Json.php';
echo Zend_Json::fromXml($response); //转换为 JSON 格式输出给客户端
