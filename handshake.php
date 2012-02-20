<?php
/**
 * websocket握手
 * by wbq 2012-1-16
 */
class HandShake
{
    /**
     * 客户端websocket请求信息
     */
    private $_request = null;

    /**
     * 请求握手的用户(对象)
     */
    private $_user = null;

    /**
     * 是否开启调试
     */
    private $_debug = false;

    public function __construct()
    {

    }

    /**
     * 提供给外部访问的主握手方法
     * @param $user 请求握手的用户对象
     * @param $request 请求信息
     */
    public function _doshake($user,$request)
    {
        $this->_user = $user;
        $this->_request = $request;
        $this->_analysis();
        $this->_response();
    }

    /**
     * 分析请求信息并摘取需要的信息
     * @return 数组array()
     * $r 请求的具体服务器文件地址
     * $h 主机服务器
     * $o host http源
     * $k 安全密钥
     */
    private function _analysis()
    {
        $request = $this->_request;
        $r=$h=$o=$k=null;

        if (preg_match("/GET (.*) HTTP\/1\.1\r\n/", $request, $match))
            $r = $match[1];
        if (preg_match("/Host: (.*)\r\n/", $request, $match)) 
            $h = $match[1];
        if (preg_match("/Sec-WebSocket-Origin: (.*)\r\n/", $request, $match))
            $o = $match[1];
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $request, $match))
            $k = $match[1];

        $this->_request = array($r,$h,$o,$k);
    }

    /**
     * 响应客户端
     * $shake 握手信息
     * 遵循草案10协议，将获取到的Sec-WebSocket-Key连接上字符串258EAFA5-E914-47DA-95CA-C5AB0DC85B11
     * 做sha1编码，生成20位的密钥，再将密钥做base64编码，最后按照handshake格式响应给客户端
     */
    private function _response()
    {
        $this->_console("\nRequesting handshake...");
        list($resource,$host,$origin,$key) = $this->_request;
        $this->_console("Handshaking...");
        
        $key .= "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        $hashkey = base64_encode(sha1($key,true));
        
        $shake  = "HTTP/1.1 101 Switching Protocols\r\n" .
                  "Upgrade: websocket\r\n" .
                  "Connection: Upgrade\r\n" .
                  "Sec-WebSocket-Accept: " . $hashkey . "\r\n" .
                  "Sec-WebSocket-Protocol: websocket\r\n" .
                  "\r\n";
        
        socket_write($this->_user->getSocket(),$shake,strlen($shake));
        $this->_user->setHandshake();
        $this->_console($shake);
        $this->_console("Done handshaking...");
        return true;
    }

    /**
     * 写控制台信息
     * @param $msg 信息
     */
    private function _console($msg="")
    {
        if ($this->_debug) echo $msg."\n";
    }

}
