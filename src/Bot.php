<?php


namespace QqBot;


use QqBot\Core\Contact\Friends;
use QqBot\Core\Contact\Groups;

class Bot
{
    public static $qq;

    public $qq_instance;

    /** @var QqBotApi */
    static $bot;


    public function __construct($config = [])
    {
        $this->qq_instance = new QqBotApi($config);
        static::$bot = $this->qq_instance;
    }


    public static function getNickName()
    {
        return QqBotApi::$storage->getNickName();
    }

    public function login()
    {
        $this->qq_instance->qqlogin();
    }

    public function getMessage()
    {
        return $this->qq_instance->poll2();
    }

    public function sendFriendMessage($to_id, $content)
    {
        $this->qq_instance->sendFriendMessage($to_id, $content);
    }

    public function sendGroupMessage($to_id, $content)
    {
        $this->qq_instance->sendGroupMessage($to_id, $content);
    }

    public function sendDiscuMessage($to_id, $content)
    {
        $this->qq_instance->sendDiscuMessage($to_id, $content);
    }

    /**
     * 获取好友列表
     *
     * @return Friends[]
     */
    public function getFriends()
    {
        return $this->qq_instance->getFriends();
    }

    /**
     * 获取群列表
     *
     * @return Groups[]
     */
    public function getGroups()
    {
        return $this->qq_instance->getGroups();
    }

    /**
     * 获取论坛列表
     *
     * @return mixed
     */
    public function getDiscus()
    {
        return $this->qq_instance->getDiscus();
    }


    /**
     * 获取好友详细信息
     *
     * @param $uin
     * @return array || null
     */
    public function getFriendInfo($uin)
    {
        return $this->qq_instance->getFriendInfo($uin);
    }
}