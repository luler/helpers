<?php

namespace Luler\Helpers;

class CommonHelper
{
    /**
     * 获取常用随机中文
     * @param $num $num为生成汉字的数量
     * @return string
     * @author LinZhou <1207032539@qq.com>
     */
    public static function getRandomChineseWords($num = 16)
    {
        $b = '';
        for ($i = 0; $i < $num; $i++) {
            // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
            //一级汉字
            $a = chr(mt_rand(0xB0, 0xD7)) . chr(mt_rand(0xA1, 0xF0));
            // 转码
            $b .= iconv('GB2312', 'UTF-8', $a);
        }
        return $b;
    }

    /**
     * 去掉数组里每个元素两边的空格(导入execl经常用到)
     * @param $param  一位数组
     * @author LinZhou <1207032539@qq.com>
     */
    public static function trimBlank(&$param)
    {
        foreach ($param as &$value) {
            if (is_array($value)) {
                self::trimBlank($value);
            } elseif (!is_object($value)) {
                $value = trim($value);
            }
        }
    }

    /**
     * 限制字符串长度，并以省略符结尾
     * @param $str
     * @param $length
     * @param $tail
     * @return string
     * @author LinZhou <1207032539@qq.com>
     */
    public static function strLengthLimit($str, $length, $tail = '...')
    {
        if (mb_strlen($str) > $length) {
            $str = mb_substr($str, 0, $length - mb_strlen($tail)) . $tail;
        }
        return $str;
    }

    /**
     * 数组转换成树结构
     * @param $arr //数组
     * @param int $root //跟节点
     * @param string $pk //主键
     * @param string $pid //父节点字段
     * @param string $child //子节点字段
     * @return array
     * @author LinZhou <1207032539@qq.com>
     */
    public static function arrayToTree($arr, $root = 0, $pk = 'id', $pid = 'pid', $child = 'children')
    {

        $temp = [];
        foreach ($arr as $value) {
            if ($value[$pid] == $root) {
                $temp[] = $value;
            }
        }
        if (empty($temp)) {
            return [];
        }
        foreach ($temp as &$value) {
            $res = self::arrayToTree($arr, $value[$pk], $pk, $pid, $child);
            if (!empty($res)) {
                $value[$child] = $res;
            }
        }
        return $temp;
    }

    /**
     * 字节转为合适的单位
     * @param $size
     * @return string
     * @author LinZhou <1207032539@qq.com>
     */
    public static function convertSize($size)
    {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
    }

    /**
     * url跳转
     * @param $url
     * @author LinZhou <1207032539@qq.com>
     */
    public static function goToUrl($url)
    {
        header('location: ' . $url);
        exit();
    }

    /**
     * 生成手机验证码
     * @param int $length
     * @author LinZhou <1207032539@qq.com>
     */
    public static function buildPhoneCode($length = 4)
    {
        $range = range(0, 100);
        shuffle($range);
        $range = join($range);
        $range = substr($range, 0, $length);
        return $range;
    }

    /**
     * 删除目录函数
     * @param $dirname
     * @return bool
     * @author LinZhou <1207032539@qq.com>
     */
    public static function deleteDir($dirname)
    {
        if (file_exists($dirname)) {
            $handle = opendir($dirname);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($dirname . "/$file")) {
                        self::deleteDir($dirname . "/$file");
                    } else {
                        unlink($dirname . "/$file");
                    }
                }
            }
            closedir($handle);
            rmdir($dirname);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 解压zip文件
     * @param string $zip_file 需要解压的文件路径加文件名
     * @param string $to_dir 解压后的文件夹路径
     * @return bool
     */
    public static function extractZipToFile($zip_file, $to_dir)
    {
        $zip = new \ZipArchive;
        if ($zip->open($zip_file) === TRUE) {
            if (!is_dir($to_dir)) {
                mkdir($to_dir, 0775, true);
            }
            $docnum = $zip->numFiles;
            for ($i = 0; $i < $docnum; $i++) {
                $statInfo = $zip->statIndex($i, \ZipArchive::FL_ENC_RAW);
                $filename = self::correctEncoding($statInfo['name']);
                if ($statInfo['crc'] == 0) {
                    //新建目录
                    if (!is_dir($to_dir . '/' . substr($filename, 0, -1))) mkdir($to_dir . '/' . substr($filename, 0, -1), 0775, true);
                } else {
                    //拷贝文件
                    copy('zip://' . $zip_file . '#' . $zip->getNameIndex($i), $to_dir . '/' . $filename);
                }
            }
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * 中文乱码兼容
     * @param $str
     * @return false|mixed|string
     * @author LinZhou <1207032539@qq.com>
     */
    public static function correctEncoding($str)
    {
        $encoding = mb_detect_encoding($str, ['GBK', 'UTF-8']);
        $str = str_replace('\\', '/', $str);
        if (DIRECTORY_SEPARATOR == '/') {    //linux
            $str = iconv($encoding, 'UTF-8', $str);
        } else {  //win
            $str = iconv($encoding, 'GBK', $str);
        }
        return $str;
    }

    /**
     * 复制目录
     * @param $source
     * @param $dest
     */
    public static function copyDir($source, $dest)
    {
        if (!file_exists($dest)) mkdir($dest);
        $handle = opendir($source);
        while (($item = readdir($handle)) !== false) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $_source = $source . '/' . $item;
            $_dest = $dest . '/' . $item;
            if (is_file($_source)) {
                copy($_source, $_dest);
            }
            if (is_dir($_source)) {
                self::copyDir($_source, $_dest);
            }
        }
        closedir($handle);
    }

    /**
     * 随机生成唯一订单号（基于日期和随机乱序）
     * @param null $type
     * @return string
     * @author LinZhou <1207032539@qq.com>
     */
    public static function generateOrderId($type = 1)
    {
        if ($type == 1) {
            //都是数字，不过存库时要使用varchar类型,唯一性更好
            $id = date_format(new \DateTime(), 'YmdHisu') . str_pad(mt_rand(), 10, '0', STR_PAD_LEFT);
        } else {
            //bigint类型
            $id = time() . date_format(new \DateTime(), 'u') . mt_rand(100, 999);
        }
        return $id;
    }

    /**
     * 模拟ping ip
     * @Author: 我只想看看蓝天 <1207032539@qq.com>
     * @Datetime: 2020/2/11 0011 0:22
     * @param string $ip
     * @return bool
     */
    function ping(string $ip)
    {
        $ip_port = explode(':', $ip);
        if (filter_var($ip_port[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {        //IPv6
            $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
        } elseif (filter_var($ip_port[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {    //IPv4
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } else {
            throw new \Exception('请输入合法ip');
        }

        if (!isset($ip_port[1])) {        //没有写端口则指定为80
            $ip_port[1] = '80';
        }
        try {
            $ok = socket_connect($socket, $ip_port[0], $ip_port[1]);
        } catch (\Exception $e) {
//        $error_msg=socket_strerror(socket_last_error($socket)); //错误信息
            $ok = false;
        }
        socket_close($socket);
        return $ok;
    }
}
