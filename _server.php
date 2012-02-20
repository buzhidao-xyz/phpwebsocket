<?php
/**
 * php - websocket
 * 建立socket服务器
 * 接收客户端输入字符串
 * 返回客户端该字符串并 is ok
 */

error_reporting(E_ALL);
set_time_limit(0);

//打开绝对刷送，不需要每次都调用flush
ob_implicit_flush(true);

date_default_timezone_set("Asia/shanghai");

//声明sockets数组，为socket_select提供参数
$sockets = array();
$users   = array();
$debug   = false;

//dos命令模式下，新建socket服务器 端口号12345，并push进sockets数组
$master  = WebSocket("localhost",12345);
$sockets[] = $master;

/**
 * while(true)保证服务器一直运行 并加入sleep(1)，让服务器没循环一次休息1S
 * 每次循环用socket_select选择sockets数组，并遍历该数组里所有的socket客户端
 * 如果是服务器(master)，则执行socket_accept方法，接收一个客户端的连接请求 并创建源
 * 源创建成功，调用skConnect方法添加新的用户客户端跟uid进users数组
 * 如果是socket客户端，那么接收客户端的请求信息到buffer中，如果没有data，不执行任何操作
 * 如果接收到客户端信息，然后判断该客户端是否websocket握手成功，如果未握手，则进行握手
 * 如果已经握手成功，则处理接收到的字符串信息，并向客户端做出响应
 */
while(true){
    $changed = $sockets;
    socket_select($changed,$write=NULL,$except=NULL,NULL);
    foreach ($changed as $socket) {
        if ($socket == $master) {
            $client=socket_accept($master);
            if ($client !== false) {
                skConnect($client);
            } else {
                console("socket_accept() failed"); continue;
            }
        } else {
            $data = @socket_recv($socket,$buffer,2048,0);
            
            if ($data != 0) {
                $user = getuserbysocket($socket);
                
                if (!$user->handshake) {
                    dohandshake($user,$buffer);
                } else {
                    process($socket,$buffer);
                }
            }
        }
    }

    sleep(1);
}

//---------------------------------------------------------------
/**
 * 建立socket服务器
 * @param $host 主机地址
 * @param $port 端口号 
 * @return 服务器创建成功后的socket源
 */
function WebSocket($host,$port) {
    $master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
    socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
    socket_bind($master, $host, $port)                    or die("socket_bind() failed");
    socket_listen($master,20)                                or die("socket_listen() failed");
    echo "Server Started : ".date('Y-m-d H:i:s')."\n";
    echo "Master socket  : ".$master."\n";
    echo "Listening on   : ".$host." port ".$port."\n\n";
    return $master;
}

/**
 * 根据socket客户端源获取该socket的用户信息
 * @param $socket socket连接客户端源
 * @param 用户信息
 */
function getuserbysocket($socket){
    global $users;
    $found=null;
    foreach($users as $user){
        if($user->socket==$socket){ $found=$user; break; }
    }
    return $found;
}

function skConnect($socket){
    global $sockets,$users;
    
    $user = new User();
    
    $user->id = uniqid();
    $user->socket = $socket;
    
    $users[] = $user;
    $sockets[] = $socket;
    
    console($socket." CONNECTED!");
}

/**
 * 断开某个客户端连接 并清除该客户端的连接信息跟用户信息
 * @param $socket 客户端socket源
 */
function disconnect($socket){
    global $sockets,$users;
    $found=null;
    $n=count($users);
    for($i=0;$i<$n;$i++){
        if($users[$i]->socket==$socket){ $found=$i; break; }
    }
    if(!is_null($found)){ array_splice($users,$found,1); }
    $index = array_search($socket,$sockets);
    socket_close($socket);
    console($socket." DISCONNECTED!");
    if($index>=0){ array_splice($sockets,$index,1); }
}

/**
 * 获取客户端请求的握手信息
 * @param $req socket_recv获取的$buffer字段值
 * @param array()
 */
function getheaders($req){
    $r=$h=$o=null;
    if(preg_match("/GET (.*) HTTP\/1\.1\r\n/"   ,$req,$match)){ $r=$match[1]; }
    if(preg_match("/Host: (.*)\r\n/"  ,$req,$match)){ $h=$match[1]; }
    if(preg_match("/Sec-WebSocket-Origin: (.*)\r\n/",$req,$match)){ $o=$match[1]; }
    if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$req,$match)){ $key=$match[1]; }
    return array($r,$h,$o,$key);
}

function dohandshake($user,$buffer){
    console("\nRequesting handshake...");
    console($buffer);
    list($resource,$host,$origin,$strkey) = getheaders($buffer);
    console("Handshaking...");
    
    $strkey .= "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
    $hash_data = base64_encode(sha1($strkey,true));
    
    $upgrade  = "HTTP/1.1 101 Switching Protocols\r\n" .
              "Upgrade: websocket\r\n" .
              "Connection: Upgrade\r\n" .
              "Sec-WebSocket-Accept: " . $hash_data . "\r\n" .
              "Sec-WebSocket-Protocol: websocket\r\n" .
              "\r\n";
    
    socket_write($user->socket,$upgrade,strlen($upgrade));
    $user->handshake=true;
    console($upgrade);
    console("Done handshaking...");
    return true;
}

function process($socket,$msg){
    $action = unwrap($msg);
    
    say("< ".$action);
    send($socket, $action);
}

function send($client,$msg){
    say("> ".$msg);
    $msg = wrap($msg);
    socket_write($client,$msg,strlen($msg));
    return true;
}

function ord_hex($data)
{
    $msg = "";
    $l = strlen($data);

    for ($i= 0; $i< $l; $i++) {
        $msg .= dechex(ord($data{$i}));
    }

    return $msg;
}

function wrap($msg="") {
    $frame = array();
    $frame[0] = "81";
    $msg .= " is ok!";
    $len = strlen($msg);
    $frame[1] = $len<16?"0".dechex($len):dechex($len);
    $frame[2] = ord_hex($msg);
    $data = implode("",$frame);
    return pack("H*", $data);
}

function unwrap($msg="") {
    $mask = array();
    $data = "";
    $msg = unpack("H*",$msg);
    
    $head = substr($msg[1],0,2);
    
    if (hexdec($head{1}) === 8) {
        $data = false;
    } else if (hexdec($head{1}) === 1) {
        $mask[] = hexdec(substr($msg[1],4,2));
        $mask[] = hexdec(substr($msg[1],6,2));
        $mask[] = hexdec(substr($msg[1],8,2));
        $mask[] = hexdec(substr($msg[1],10,2));
    
        $s = 12;
        $e = strlen($msg[1])-2;
        $n = 0;
        for ($i= $s; $i<= $e; $i+= 2) {
            $data .= chr($mask[$n%4]^hexdec(substr($msg[1],$i,2)));
            $n++;
        }
    }
    
    return $data;
}

function say($msg=""){ print_r($msg."\n"); }

function console($msg=""){ global $debug; if($debug){ echo $msg."\n"; } }

class User{
    var $id;
    var $socket;
    var $handshake;
}
