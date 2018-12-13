<?php


namespace QqBot\Extension;


class TuLing
{

    private static $apiKey = "0879a0cd8a784fbe89e8c2ed33b5136b";

    const URL = "http://openapi.tuling123.com/openapi/api/v2";

    public static function request($text, $userId)
    {
        $params = [
            'reqType' => 0,
            'userInfo' => [
                'apiKey' => static::$apiKey,
                'userId' => $userId
            ],
            'perception' => [
                'inputText' => [
                    'text' => $text,
                ],
            ],
        ];

        $ch = curl_init(self::URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $res = curl_exec($ch);
        $res = json_decode($res, true);

        if (isset($res['intent']['code'])) {
            foreach ($res['results'] as $key => $value) {
                if ($value['resultType'] == 'news') {
                    foreach ($value['values']['news'] as $item) {
                        $str[] = array_shift($item);
                    }
                }
                $str[] = implode("\n", $value['values']);
            }
            return [implode("\n", array_slice($str, 0, 8))];
        }

        return json_encode($res);
    }
}