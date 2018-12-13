<?php


namespace QqBot\Core\Storage;


class File implements StorageInterface
{
    private $cookie_file;
    private $auth_file;

    /** @var array */
    private $cookies = [];

    /** @var array */
    private $auths = [];

    public function __construct($path)
    {
        $this->cookie_file = $path . '/cookie.json';
        $this->auth_file = $path . '/auth.json';
    }

    public function getCookie($key)
    {
        return $this->get($key, $this->cookie_file);
    }

    public function setCookie($key, $value)
    {
        $this->set($key, $value, $this->cookie_file);
    }

    public function getCookieAll()
    {
        $content = @file_get_contents($this->cookie_file) ?? [];
        return is_array($content) ? [] : json_decode($content, true);
    }

    public function getAuth($key)
    {
        return $this->get($key, $this->auth_file);
    }

    public function setAuth($key, $value)
    {
        $this->set($key, $value, $this->auth_file);
    }

    public function getAuthAll()
    {
        $content = @file_get_contents($this->auth_file) ?? [];
        return is_array($content) ? [] : json_decode($content, true);
    }

    public function setNickName($value)
    {
        $this->set('nickname', $value, $this->auth_file);
    }

    public function getNickName()
    {
        return $this->get('nickname', $this->auth_file);
    }

    public function clear()
    {
        @unlink($this->auth_file);
        @unlink($this->cookie_file);
    }

    private function set($key, $value, $file)
    {
        $content = @file_get_contents($file) ?? [];
        $json_arr = is_array($content) ? [] : json_decode($content, true);

        $json_arr[$key] = $value;

        return file_put_contents($file, json_encode($json_arr));
    }

    private function get($key, $file)
    {
        if ($file == $this->cookie_file) {
            $array = $this->cookies;
        } else {
            $array = $this->auths;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (is_file($file)) {
            $content = file_get_contents($file) ? : '';
            $json_arr = is_array($content) ? [] : json_decode($content, true);
        } else {
            $json_arr = [];
        }

        return !empty($json_arr[$key]) ? $json_arr[$key] : null;
    }

}