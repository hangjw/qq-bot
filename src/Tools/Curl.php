<?php
/**
 * Created by PhpStorm.
 * User: chaofei
 * Date: 18-7-21
 * Time: 下午9:57
 */

namespace QqBot\Tools;


use QqBot\Core\Storage;
use QqBot\Core\Storage\StorageInterface;

class Curl
{
    const USERAGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.162 Safari/537.36";

    protected $storage;


    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function get($url, $options = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt_array($ch, $options);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            list($header, $body) = explode("\r\n\r\n", $res);
            $this->setCookie($header);
            return $body;
        }
        return false;
    }

    public function post($url, $options = [])
    {
        return $this->get($url, $options);
    }

    private function setCookie($header)
    {
        preg_match_all("/Set-Cookie: ([^\n\r]*)/i", $header, $matches);
        $cookies = $matches[1];
        foreach ($cookies as $cookie) {
            list($key, $value) = explode('=', explode(";", $cookie)[0]);
            if (empty($value)) continue;
            $this->storage->setCookie($key, $value);
        }
    }
}