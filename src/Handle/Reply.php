<?php
/**
 * Created by PhpStorm.
 * User: chaofei
 * Date: 18-7-22
 * Time: 上午9:17
 */

namespace QqBot\Handle;

// 好友消息
use QqBot\Bot;
use QqBot\Extension\TuLing;
use QqBot\Core\MessageSource\Discu;
use QqBot\Core\MessageSource\Friend;
use QqBot\Core\MessageSource\Group;
use QqBot\Core\MessageSource\MessageAbstract;

class Reply
{

    protected $message;

    protected $bot;

    public function __construct(MessageAbstract $message, Bot $bot)
    {
        $this->message = $message;
        $this->bot = $bot;
    }

    public function run()
    {
        $message = $this->message;
        switch (get_class($message)) {
            case Friend::class :
                // 来自好友的消息
                if ($message->from_uin != '3388456509') {
                    // 不是自己
                    $res = TuLing::request($message->content, $message->from_uin);
                    foreach ($res as $key => $item) {
                        $this->bot->sendFriendMessage($message->from_uin, $item);
                    }
                }
                break;
            case Group::class :
                // 来自群组
                break;

            case Discu::class :
                // 评论
                break;

            default:
                break;
        }
    }


}