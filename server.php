<?php
/**
 * php - websocket
 * 建立socket服务器
 * 接收客户端输入字符串
 * 返回客户端该字符串+ is ok
 * by wbq 2012-1-16
 */
class Server
{
    /**
     * socket服务器对象
     */
    private $_master = null;
    
    /**
     * socket数组，包含所有的socket，包括master
     */
    private $_sockets = array();
    
    /**
     * 用户对象
     */
    private $_user = null;
    
    /**
     * 握手对象
     */
    private $_handshake = null;
    
    /**
     * 信息处理对象
     */
    private $_message = null;
    
    /**
     * 服务器所接收的客户端信息的最大长度
     */
    private $_length = 2048;
    
    /**
     * 是否开启调试
     */
    private $_debug = false;
    
    public function __construct($host=null,$port=null)
    {
        $this->_init();
        $this->_webSocket($host,$port);
        $this->_newSocket();
    }
    
    /**
     * 初始化用户、握手、信息处理等对象
     */
    private function _init()
    {
        $this->_user = $this->_user?$this->_user:new User();
        $this->_handshake = $this->_handshake?$this->_handshake:new HandShake();
        $this->_message = $this->_message?$this->_message:new Message();
    }
    
    /**
     * dos命令模式下，新建socket服务器 端口号12345，并push进sockets数组
     */
    private function _webSocket($host=null,$port=null)
    {
        $websocket = new WebSocket($host, $port);
        $this->_master = $websocket->_master;
        $this->_sockets[] = $this->_master;
    }
    
    /**
     * while(true)保证服务器一直运行 并加入sleep(1)，让服务器没循环一次休息1S
     * 每次循环用socket_select选择sockets数组，并遍历该数组里所有的socket客户端
     * 如果是服务器(master)，则执行socket_accept方法，接收一个客户端的连接请求 并创建源
     * 源创建成功，调用skConnect方法添加新的用户客户端跟uid进users数组
     * 如果是socket客户端，那么接收客户端的请求信息到buffer中，如果没有data，不执行任何操作
     * 如果接收到客户端信息，然后判断该客户端是否websocket握手成功，如果未握手，则进行握手
     * 如果已经握手成功，则处理接收到的字符串信息，并向客户端做出响应
     */
    private function _newSocket()
    {
        while(true){
            $sockets = $this->_sockets;
            socket_select($sockets,$write=NULL,$except=NULL,NULL);
            foreach ($sockets as $socket) {
                if ($socket == $this->_master) {
                    $client=socket_accept($this->_master);
                    if ($client !== false) {
                        $this->_connect($client);
                    } else {
                        $this->_console("socket_accept() failed");
                        continue;
                    }
                } else {
                    $data = @socket_recv($socket,$buffer,$this->_length,0);
                    
                    if (strlen($buffer) > 6) {
                        $user = $this->_user->getUser($socket);
                        
                        if (!$user->getHandshake()) {
                            $this->_handshake->_doshake($user,$buffer);
                        } else {
                            $this->_message->_process($this->_sockets, $user, $buffer);
                        }
                    } else {
                        $this->_disconnect($socket);
                    }
                }
            }
            
            sleep(1);
        }
    }
    
    /**
     * 服务器接收客户端连接 并为标记客户端为新用户
     * @param $socket 客户端socket源
     */
    private function _connect($socket)
    {
        $user = new User();

        $id = uniqid();
        $user->setId($id);
        $user->setSocket($socket);
        
        $this->_user->newUser($user);
        $this->_sockets[] = $socket;
    }
    
    /**
     * 服务器断开与客户端的连接 并清除用户信息
     * @param $socket 客户端socket源
     */
    private function _disconnect($socket)
    {
        $found = null;
        $users = &$this->_user->_users;
        
        $n = count($users);
        for($i= 0; $i< $n; $i++){
            if ($users[$i]->getSocket() == $socket) {
                $found = $i;
                break;
            }
        }
        if (!is_null($found)) {
            array_splice($users,$found,1);
        }
        $index = array_search($socket, $this->_sockets);
        socket_close($socket);
        
        $this->_console($socket." DISCONNECTED!");
        if ($index >= 0) {
            array_splice($this->_sockets,$index,1);
        }
    }
    
    private function _console($msg="")
    {
        if ($this->_debug) echo $msg."\n";
    }
}
