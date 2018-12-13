<?php
/**
 * Created by PhpStorm.
 * User: chaofei
 * Date: 18-7-21
 * Time: 下午8:58
 */

namespace QqBot;


use PHPZxing\PHPZxingDecoder;
use QqBot\Core\Contact\Friends;
use QqBot\Extension\Qrcode;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use QqBot\Core\Contact\Groups;
use QqBot\Core\Contact\MyIterate;
use QqBot\Core\MessageSource\Discu;
use QqBot\Core\MessageSource\Friend;
use QqBot\Core\MessageSource\Group;
use QqBot\Core\Storage\File;
use QqBot\Core\Storage\Redis;
use QqBot\Tools\Curl;
use QqBot\Core\Storage\StorageInterface;
use PHPQRCode\QRcode as QrCodeConsole;
use Symfony\Component\Console\Output\OutputInterface;


class QqBotApi
{
    const FRIEND = 'friend';
    const GROUP  = 'group';
    const DISCU  = 'discu';

    const XLOGIN   = "https://xui.ptlogin2.qq.com/cgi-bin/xlogin?daid=164&target=self&style=40&pt_disable_pwd=1&mibao_css=m_webqq&appid=501004106&enable_qlogin=0&no_verifyimg=1&s_url=http://web2.qq.com/proxy.html&f_url=loginerroralert&strong_login=1&login_state=10&t=20131024001";
    const QR       = "https://ssl.ptlogin2.qq.com/ptqrshow?appid=501004106&e=2&l=M&s=3&d=72&v=4&t=0.5053282138300335&daid=164&pt_3rd_aid=0";
    const LOGIN    = "https://ssl.ptlogin2.qq.com/ptqrlogin";
    const VF_WEBQQ = "http://s.web2.qq.com/api/getvfwebqq";
    const LOGIN2   = "http://d1.web2.qq.com/channel/login2";

    // 获取消息接口
    const POLL = "http://d1.web2.qq.com/channel/poll2";
    // 发送好友消息
    const FRIEND_MESSAGE = "http://d1.web2.qq.com/channel/send_buddy_msg2";
    // 发送群消息
    const GROUP_MESSAGE = "http://d1.web2.qq.com/channel/send_qun_msg2";
    // 发送讨论组消息
    const DISCU_MESSAGE = "http://d1.web2.qq.com/channel/send_discu_msg2";
    // 获取好友列表
    const FRIENDS_LIST = "http://s.web2.qq.com/api/get_user_friends2";
    // 获取好友详细信息
    const FRIEND_INFO = "http://s.web2.qq.com/api/get_friend_info2";
    // 获取在线状态
    const ONLINE_STATUS = "http://d1.web2.qq.com/channel/get_online_buddies2";
    // 获取群列表
    const GROUPS_LIST = "http://s.web2.qq.com/api/get_group_name_list_mask2";
    // 获取群详细信息
    const GROUP_INFO = "http://s.web2.qq.com/api/get_group_info_ext2";
    // 获取讨论组列表
    const DISCUS = "http://s.web2.qq.com/api/get_discus_list";
    // 获取讨论组详细信息
    const DISCU_INFO = "http://d1.web2.qq.com/channel/get_discu_info";
    // 获取最近会话列表
    const RECENT_SESSIONS = "http://d1.web2.qq.com/channel/get_recent_list2";
    // 获取自身
    const SELF_INFO = "http://s.web2.qq.com/api/get_self_info2&t=0.1";

    const CLIENT_ID = 53999199;

    /** @var $storage StorageInterface */
    protected $storage;

    protected $qrcode;

    public function __construct($config)
    {
        $this->storage = new Core\Storage($config['storage']);
        $this->curl = new Curl($this->storage);
        $this->qrcode = new Qrcode();
    }

    public function qqlogin()
    {
        $this->xLogin();
        $this->showQr();
        $this->getVfWebqq();
        $this->getPsessionidAndUin();
    }

    private function xLogin()
    {
        $res = $this->curl->get(self::XLOGIN);

        if (!$res) {
            echo "模拟打开登录页面成功" . PHP_EOL;
        }
    }

    public function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    private static function initQrcodeStyle(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('black', 'black', ['bold']);
        $output->getFormatter()->setStyle('blackc', $style);
        $style = new OutputFormatterStyle('white', 'white', ['bold']);
        $output->getFormatter()->setStyle('whitec', $style);
    }


    private function showQr()
    {
        $res = $this->curl->get(self::QR, [
            CURLOPT_COOKIE => $this->buildCookie(),
        ]);
        $this->qrcode->showQr($res);
    }

    private function getPtwebqqUrl()
    {
        $params = [
            'u1'         => 'http://web2.qq.com/proxy.html',
            'ptqrtoken'  => Tool::bknHash($this->storage->getCookie('qrsig'), 0),
            'ptredirect' => '0',
            'h'          => '1',
            't'          => '1',
            'g'          => '1',
            'from_ui'    => '1',
            'ptlang'     => '2052',
            'action'     => '1-0-1532142989455',
            'js_ver'     => '10276',
            'js_type'    => '1',
            'pt_uistyle' => '40',
            'aid'        => '501004106',
            'daid'       => '164',
            'mibao_css'  => 'm_webqq',
        ];
        $url = self::LOGIN . '?' . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => 'https://xui.ptlogin2.qq.com/cgi-bin/xlogin?daid=164&target=self&style=40&pt_disable_pwd=1&mibao_css=m_webqq&appid=501004106&enable_qlogin=0&no_verifyimg=1&s_url=http%3A%2F%2Fweb2.qq.com%2Fproxy.html&f_url=loginerroralert&strong_login=1&login_state=10&t=20131024001',
        ]);

        if ($res) {
            preg_match("/(?<url>http[^\']*)/", $res, $match);
            if ($match) {
                echo "获取 PtwebqqUrl 成功" . PHP_EOL;
                preg_match('/\((?<arr>.*)\)/', $res, $match2);
                $arr = explode(',', $match2['arr']);
                $this->storage->setNickName(trim(trim(array_pop($arr), ' '), '\''));
                return $match['url'];
            } elseif (preg_match('/未失效/', $res) || preg_match('/认证中/', $res)) {
                sleep(2);
                return $this->getPtwebqqUrl();
            }
        }
        die('获取 PtwebqqURl 失败');
    }

    private function getPtWebQQ()
    {
        $url = $this->getPtwebqqUrl();
        $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1',
        ]);
    }

    private function getVfWebqq()
    {
        $this->getPtWebQQ();
        $params = [
            'ptwebqq'    => $this->storage->getCookie('ptwebqq') ?? '',
            'client_id'  => self::CLIENT_ID,
            'psessionid' => '',
            't'          => 0.1,
        ];
        $url = self::VF_WEBQQ . '?' . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1',
        ]);

        $res = json_decode($res, true);

        if ($res["retcode"] == 0) {
            echo "获取 vfwebqq 成功" . PHP_EOL;
            $this->storage->setAuth('vfwebqq', $res['result']['vfwebqq']);
        } else {
            die("获取 vfwebqq 失败");
        }
    }

    private function getPsessionidAndUin()
    {
        $params = "r=" . json_encode([
                'ptwebqq'    => $this->storage->getCookie('ptwebqq') ?? '',
                'clientid'   => self::CLIENT_ID,
                'psessionid' => '',
                'status'     => 'online',
            ], JSON_FORCE_OBJECT);

        $res = $this->curl->post(self::LOGIN2, [
            CURLOPT_COOKIE     => $this->buildCookie(),
            CURLOPT_REFERER    => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
            CURLOPT_POSTFIELDS => $params,
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            $this->storage->setAuth('psessionid', $res['result']['psessionid']);
            $this->storage->setAuth('uin', $res['result']['uin']);
            echo "登录成功!" . PHP_EOL;
            return true;
        } else {
            die('登录失败');
        }
    }


    /**
     * 轮寻消息
     *
     * @return Discu|Friend|Group
     */
    public function poll2()
    {
        $params = "r=" . json_encode([
                'ptwebqq'    => $this->storage->getCookie('ptwebqq') ?? '',
                'clientid'   => self::CLIENT_ID,
                'psessionid' => '',
                'key'        => '',
            ], JSON_FORCE_OBJECT);

        $start_time = time();

        $res = $this->curl->post(self::POLL, [
            CURLOPT_COOKIE     => $this->buildCookie(),
            CURLOPT_REFERER    => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
            CURLOPT_POSTFIELDS => $params,
        ]);
        $res = json_decode($res, true);

        if (!empty($res['errmsg']) && $res['errmsg'] == 'error') {
            // 轮询延迟时间<2，则是错误，否则是本次轮询停止
            if (time() - $start_time < 5) {
//                $this->storage->clear();
//                throw new \Exception('请重新启动。');
            } else {
                return null;
            }
        }
        if ($res['retcode'] != 0) {
            $this->qqlogin();
            $this->poll2();
        }

        return $this->formatResponse($res['result'][0]);
    }

    public function sendFriendMessage($to_id, $content)
    {
        return $this->sendMessage(self::FRIEND_MESSAGE, $to_id, $content, self::FRIEND);
    }

    public function sendGroupMessage($group_uin, $content)
    {
        return $this->sendMessage(self::GROUP_MESSAGE, $group_uin, $content, self::GROUP);
    }

    public function sendDiscuMessage($did, $content)
    {
        return $this->sendMessage(self::DISCU_MESSAGE, $did, $content, self::DISCU);
    }

    private function sendMessage($url, $to_id, $content, $type)
    {
        $params = "r=" . '{"to":%d,"content":"[\"%s\",[\"font\",{\"name\":\"宋体\",\"size\":10,\"style\":[0,0,0],\"color\":\"000000\"}]]","face":540,"clientid":53999199,"msg_id":79950001,"psessionid":"%s"}';

        $conversion = function ($str) use ($params) {
            return strtr($params, ['to' => $str]);
        };

        switch ($type) {
            case self::FRIEND:
                $params = $conversion('to');
                break;
            case self::GROUP:
                $params = $conversion('group_uin');
                break;
            case self::DISCU:
                $params = $conversion('did');
                break;
        }

        $params = sprintf($params, $to_id, $content, $this->storage->getAuth('psessionid'));

        $res = $this->curl->post($url, [
            CURLOPT_COOKIE     => $this->buildCookie(),
            CURLOPT_REFERER    => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
            CURLOPT_POSTFIELDS => $params,
        ]);

        $res = json_decode($res, true);


        if ($res['retcode'] == 0) {
            return true;
        }


        return false;
    }

    /**
     * 获取所有好友
     *
     * @return MyIterate
     */
    public function getFriends()
    {
        $params = "r=" . json_encode([
                'vfwebqq' => $this->storage->getAuth('vfwebqq'),
                'hash'    => Tool::hash($this->storage->getAuth('uin'), ""),
            ], JSON_FORCE_OBJECT);
        $res = $this->curl->post(self::FRIENDS_LIST, [
            CURLOPT_COOKIE     => $this->buildCookie(),
            CURLOPT_REFERER    => "http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1",
            CURLOPT_POSTFIELDS => $params,
        ]);

        $res = json_decode($res, true);
        if ($res['retcode'] == 0) {
            return new MyIterate(new Friends($res['result']));
        }
        return $this->getFriends();
    }

    public function getFriendInfo($uin)
    {
        $params = [
            'tuin'       => $uin,
            'vfwebqq'    => $this->storage->getAuth('vfwebqq'),
            'clientid'   => self::CLIENT_ID,
            'psessionid' => $this->storage->getAuth('psessionid'),
            't'          => 0.1,
        ];

        $url = self::FRIEND_INFO . "?" . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1",
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            // TODO 待封装结果
            return $res['result'];
        }

        return null;
    }

    public function getOnlineStatus($uin)
    {
        $params = [
            'tuin'       => $uin,
            'vfwebqq'    => $this->storage->getAuth('vfwebqq'),
            'clientid'   => self::CLIENT_ID,
            'psessionid' => $this->storage->getAuth('psessionid'),
            't'          => 0.1,
        ];

        $url = self::ONLINE_STATUS . "?" . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            return $res['result'];
        }

        return false;
    }

    public function getGroups()
    {
        $params = [
            'vfwebqq' => $this->storage->getAuth('vfwebqq'),
            'hash'    => Tool::hash($this->storage->getAuth('uin'), ""),
            't'       => 0.1,
        ];

        $url = self::GROUPS_LIST . "?" . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
        ]);

        $res = json_decode($res, true);
        if ($res['retcode'] == 0) {
            return new MyIterate(new Groups($res['result']));
        }
        return $this->getGroups();
    }

    public function getGroupInfo($code)
    {
        $params = [
            'gcode'   => $code,
            'vfwebqq' => $this->storage->getAuth('vfwebqq'),
            't'       => 0.1,
        ];

        $url = self::GROUP_INFO . "?" . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1",
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            // TODO 待封装结果
            return $res['result'];
        }
        return $this->getGroupInfo($code);
    }

    public function getDiscus()
    {
        $params = [
            'psessionid' => $this->storage->getAuth('psessionid'),
            'vfwebqq'    => $this->storage->getAuth('vfwebqq'),
            't'          => 0.1,
        ];

        $url = self::DISCUS . "?" . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            // TODO 待封装结果
            return $res['result'];
        }
        return $this->getDiscus();
    }

    public function getDiscuInfo($did)
    {
        $params = [
            'psessionid' => $this->storage->getAuth('psessionid'),
            'vfwebqq'    => $this->storage->getAuth('vfwebqq'),
            't'          => 0.1,
            'clientid'   => self::CLIENT_ID,
            'did'        => $did,
        ];

        $url = self::DISCU_INFO . "?" . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            // TODO 待封装结果
            return $res['result'];
        }
        return $this->getDiscus();
    }

    public function getRecentSessions()
    {
        $params = [
            'psessionid' => $this->storage->getAuth('psessionid'),
            'vfwebqq'    => $this->storage->getAuth('vfwebqq'),
            't'          => 0.1,
        ];

        $url = self::RECENT_SESSIONS . "?" . http_build_query($params);

        $res = $this->curl->get($url, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            // TODO 待封装结果
            return $res['result'];
        }
        return $this->getRecentSessions();
    }

    public function getSelfInfo()
    {
        $res = $this->curl->get(self::SELF_INFO, [
            CURLOPT_COOKIE  => $this->buildCookie(),
            CURLOPT_REFERER => "http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2",
        ]);

        $res = json_decode($res, true);

        if ($res['retcode'] == 0) {
            // TODO 待封装结果
            return $res['result'];
        }
        return $this->getSelfInfo();
    }

    protected function formatResponse($result)
    {
        switch ($result['poll_type']) {
            case 'message':
                return new Friend($result);
            case 'group_message':
                return new Group($result);
            case 'discu_message':
                return new Discu($result);
        }
    }

    protected function buildCookie()
    {
        $cookies = $this->storage->getCookieAll();

        if (empty($cookies)) {
            return '';
        }

        $str = '';
        foreach ($cookies as $key => $value) {
            $str .= "$key=$value; ";
        }

        return trim($str, '; ');
    }

}