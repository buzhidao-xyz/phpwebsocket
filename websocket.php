<?php
/**
 * socket服务器核心程序
 * by wbq 2012-1-16
 */
class WebSocket
{
    /**
     * socket服务器ip 默认为本地localhost
     */
    private $_host = "localhost";
    
    /**
     * socket服务器占用的端口号 默认为12345
     */
    private $_port = 12345;
    
    /**
     * 最多可以监听的客户端连接数
     */
    private $_lister = 20;
    
    /**
     * 主服务器对象
     */
    public $_master = null;
    
    public function __construct($host=null, $port=null, $lister=0)
    {
        $this->setHost($host);
        $this->setPort($port);
        $this->setLister($lister);
        
        $this->NewSocket();
    }
    
    /**
     * 设置socket主机
     * @param $host 主机IP
     */
    private function setHost($host)
    {
        $this->_host = isset($host)&&!empty($host)?$host:$this->_host;
    }
    
    /**
     * 设置socket主机端口
     * @param $port 主机端口
     */
    private function setPort($port)
    {
        $this->_port = isset($port)&&!empty($port)?$port:$this->_port;
    }
    
    /**
     * 设置socket服务器的监听人数
     * @param $lister 人数
     */
    private function setLister($lister)
    {
        $this->_lister = isset($lister)&&!empty($lister)?$lister:$this->_lister;
    }
    
    /**
     * 建立socket服务器
     * @param $host 主机地址
     * @param $port 端口号 
     * @return 服务器创建成功后的socket源
     */
    private function NewSocket()
    {
        @$this->_master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->_master) {
            echo "Socket创建失败!"; exit;
        }
        socket_set_option($this->_master, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->_master, $this->_host, $this->_port);
        socket_listen($this->_master,$this->_lister);
        
        echo "Server Started : ".date('Y-m-d H:i:s')."\n";
        echo "Master socket  : ".$this->_master."\n";
        echo "Listening on   : ".$this->_host." port ".$this->_port."\n\n";
    }
}