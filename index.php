<?php
/**
 * php websocket
 */

error_reporting(E_ALL);
set_time_limit(0);

//打开绝对刷送，不需要每次都调用flush
ob_implicit_flush(true);

date_default_timezone_set("Asia/shanghai");

/**
 * 引入行为
 */
require_once("websocket.php");
require_once("user.php");
require_once("handshake.php");
require_once("message.php");
require_once("server.php");

/**
 * 设置socket服务器主机、端口号 并建立服务器
 */
$host = "localhost";
$port = 12345;

new Server($host,$port);