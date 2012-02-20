<?php
/**
 * 用户存储器
 * by wbq 2012-1-16
 */
class User
{
    /**
     * 客户端对应的用户的id
     */
    private $_id = null;
    
    /**
     * 客户端对应的用户的socket源
     */
    private $_socket = null;
    
    /**
     * 客户端对应用户的握手标记 握手成功为true 未成功为false
     */
    private $_handshake = false;
    
    /**
     * 客户端用户数组集合
     */
    public $_users = array();
    
    public function __construct()
    {
        
    }
    
    /**
     * 为某用户分配id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }
    
    /**
     * 设置某用户的socket源
     */
    public function setSocket($socket)
    {
        $this->_socket = $socket;
    }

    /**
     * 标记某用户握手成功
     */
    public function setHandshake()
    {
        $this->_handshake = true;
    }
    
    /**
     * 向用户表中添加新用户
     */
    public function newUser($user)
    {
        $this->_users[] = $user;
    }
    
    /**
     * 根据socket源获取对应的客户端
     * @param $socket源
     */
    public function getUser($socket)
    {
        $user = null;
        
        foreach($this->_users as $u){
            if ($u->_socket == $socket) {
                $user = $u;
                break;
            }
        }
        
        return $user;
    }

    /**
     * 获取某用户的id
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * 获取某用户的socket源
     */
    public function getSocket()
    {
        return $this->_socket;
    }

    /**
     * 获取某用户是否握手成功
     */
    public function getHandshake()
    {
        return $this->_handshake;
    }
}
