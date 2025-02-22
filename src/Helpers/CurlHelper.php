<?php

namespace Luler\Helpers;

class CurlHelper
{
    private static $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
    ];

    //发起请求
    private static function request($method, $url, $params = [], $headers = [], $options = [])
    {
        $curl = curl_init();
        $method = strtoupper($method);

        $curlOptions = array_replace(self::$defaultOptions, $options);

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $curlOptions[CURLOPT_URL] = $url;

        if ($method === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = $params;
        }

        if (!empty($headers)) {
            $curlHeaders = [];
            foreach ($headers as $key => $value) {
                $curlHeaders[] = "$key: $value";
            }
            $curlOptions[CURLOPT_HTTPHEADER] = $curlHeaders;
        }

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        if ($error) {
            curl_close($curl);
            throw new \Exception("cURL Error: $error");
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return [
            'http_code' => $statusCode,
            'body' => $response
        ];
    }

    public static function get($url, $params = [], $headers = [], $options = [])
    {
        return self::request('GET', $url, $params, $headers, $options);
    }

    public static function post($url, $params = [], $headers = [], $options = [])
    {
        return self::request('POST', $url, $params, $headers, $options);
    }

    public static function jsonPost($url, $data = [], $headers = [], $options = [])
    {
        $headers['Content-Type'] = 'application/json';
        return self::request('POST', $url, json_encode($data), $headers, $options);
    }

}