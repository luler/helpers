<?php

namespace Helpers;

class EncryptHelper
{
    private $iv = '0000000000000000';
    private $method = 'aes-128-cbc';
    private $option = 0;
    private $secret = 'iloveyouverymuchandyou?';

    private static $instance;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 加密方法
     * @param mixed $data //明文
     * @param string $secret //秘钥
     * @param int $expires //超时时间
     * @return string
     * @author LinZhou <1207032539@qq.com>
     */
    public function aesEncrypt($data, string $secret = null, int $expires = 0)
    {
        $clear = [
            'data' => $data,
            'uniqid' => md5(uniqid('uniqid', true) . mt_rand() . microtime(true))
        ];
        if ($expires <= 0) { //关闭过期检查
            $clear['is_expires'] = 0;
        } else {
            $clear['is_expires'] = 1;
            $clear['expires'] = time() + $expires;
        }
        if (is_null($secret)) {
            $secret = $this->secret;
        }
        $clear = serialize($clear);
        $ciper = openssl_encrypt($clear, $this->method, $secret, $this->option, $this->iv);

        return base64_encode($ciper);
    }

    /**
     * 解密方法
     * @param mixed $data //密文
     * @param string|null $secret //秘钥
     * @param int $delay //延迟解密时间，就是解密时间加上延迟时间等于超时时间
     * @return mixed
     * @throws \Exception
     * @author LinZhou <1207032539@qq.com>
     */
    public function aesDecrypt($data, string $secret = null, int $delay = 0)
    {
        if (is_null($secret)) {
            $secret = $this->secret;
        }
        $data = base64_decode($data);
        $clear = openssl_decrypt($data, $this->method, $secret, $this->option, $this->iv);
        $clear = unserialize($clear);
        if (!isset($clear['is_expires'])) {
            throw new \Exception('解密失败', 1001);
        }
        if ($clear['is_expires'] === 1) {
            if (($clear['expires'] + $delay) < time()) {
                throw new \Exception('数据过期，解密失败', 1002);
            }
        }

        return $clear['data'];
    }

    /**
     * 简单加密方法
     * @param mixed $data //明文
     * @param string $secret //秘钥
     * @return string
     * @author LinZhou <1207032539@qq.com>
     */
    public function rawAesEncrypt(string $data, string $secret = null): string
    {
        if (is_null($secret)) {
            $secret = $this->secret;
        }
        $ciper = openssl_encrypt($data, $this->method, $secret, $this->option, $this->iv);
        return $ciper;
    }

    /**
     * 简单解密方法
     * @param mixed $data //密文
     * @param string|null $secret //秘钥
     * @return string
     * @author LinZhou <1207032539@qq.com>
     */
    public function rawAesDecrypt(string $data, string $secret = null): string
    {
        if (is_null($secret)) {
            $secret = $this->secret;
        }
        $clear = openssl_decrypt($data, $this->method, $secret, $this->option, $this->iv);

        return $clear;
    }
}
