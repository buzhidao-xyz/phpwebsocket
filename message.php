<?php
/**
 * message消息处理
 * by wbq 2012-1-16
 */
class Message
{
    /**
     * socket源 所有源的集合数组
     */
    private $_sockets = array();
    
    /**
     * 发出消息的用户对象
     */
    private $_user = null;
    
    /**
     * 客户端发出的消息
     */
    private $_msg = null;

    public function __construct()
    {
        
    }

    /**
     * 消息主处理方法
     * @param $sockets 源
     * @param $user 用户对象
     * @param $msg 消息
     */
    public function _process($sockets,$user,$msg)
    {
        $this->_set($sockets,$user,$msg);
        $this->_unwrap();
        $this->_deal();
        $this->_say();
        $this->_wrap();
        $this->_send();
    }
    
    /**
     * 设置sockets源 user用户对象 msg消息对象
     * @param $sockets 源
     * @param $user 用户对象
     * @param $msg 消息
     */
    private function _set($sockets,$user,$msg)
    {
        $this->_sockets = $sockets;
        
        $this->_user = $user;
        
        $this->_msg = $msg;
    }
    
    /**
     * 处理消息
     */
    private function _deal()
    {
        $this->_msg = $this->_user->getId().": ".$this->_msg;
    }
    
    /**
     * 数据解包
     */
    private function _unwrap()
    {
        $msg = $this->_msg;
        
        $mask = array();
        $data = "";
        $msg = unpack("H*",$msg);
        $msg = $msg[1];
        
        $head = substr($msg,0,2);
        
        if (hexdec($head{1}) === 8) {
            $data = false;
        } else if (hexdec($head{1}) === 1) {
            $mask = $this->_mask($msg);
        
            $s = 12;
            $e = strlen($msg)-2;
            $n = 0;
            for ($i= $s; $i<= $e; $i+= 2) {
                $data .= chr($mask[$n%4]^hexdec(substr($msg,$i,2)));
                $n++;
            }
        }
        
        $this->_msg = $data;
    }
    
    /**
     * 获取消息的四位mask掩码
     * @param $msg 消息
     */
    private function _mask($msg)
    {
        if (!isset($msg)) return false;
        
        $mask = array();
        
        $leng = bindec(substr(decbin(hexdec(substr($msg,2,2))),1));
        
        if ($leng <= 125) {
            $s = 4;
            $e = 10;
        } else if ($leng === 126) {
            $s = 8;
            $e = 14;
        } else if ($leng === 127) {
            $s = 20;
            $e = 26;
        }
        
        for ($i= $s; $i<= $e; $i+= 2) {
            $mask[] = hexdec(substr($msg,$i,2));
        }
        
        return $mask;
    }
    
    /**
     * 数据打包
     */
    private function _wrap()
    {
        $msg = $this->_msg;
        $frame = array();
        
        $frame[0] = "81";
        
        $len = strlen($msg);
        $frame[1] = $len<16?"0".dechex($len):dechex($len);
        $frame[2] = $this->_ordHex($msg);
        
        $msg = implode("",$frame);
        $this->_msg = pack("H*", $msg);
    }
    
    /**
     * 获取字符串的ASC2码并转为十六进制
     * @param $msg 字符串
     */
    private function _ordHex($msg)
    {
        $data = "";
        $l = strlen($msg);
    
        for ($i= 0; $i< $l; $i++) {
            $data .= dechex(ord($msg{$i}));
        }
    
        return $data;
    }
    
    /**
     * 广播消息给所有的socket客户端
     */
    private function _send()
    {
        $i = 0;
        foreach ($this->_sockets as $socket) {
            if ($i) @socket_write($socket,$this->_msg,strlen($this->_msg));
            $i++;
        }
        
        return true;
    }
    
    /**
     * 将消息记录在控制台
     */
    private function _say()
    {
        print_r($this->_msg."\n");
    }
}
