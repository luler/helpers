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
     * @param string $pk //主键
     * @param string $pid //父节点字段
     * @param string $child //子节点字段
     * @return array
     * @author LinZhou <1207032539@qq.com>
     */
    public static function arrayToTree($arr, $pk = 'id', $pid = 'pid', $child = 'children')
    {
        $temp = [];
        foreach ($arr as $value) {
            $temp[$value[$pk]] = $value;
        }
        unset($arr);

        $res = [];
        foreach ($temp as &$value) {
            //存在父元素，则被父元素引用
            if (isset($temp[$value[$pid]])) {
                $temp[$value[$pid]][$child][] = &$value;
                unset($res[$value[$pk]]);
            } else {
                $res[$value[$pk]] =& $value;
            }
        }
        $res = array_values($res);
        return $res;
    }

    /**
     * 字节转为合适的单位
     * @param int $size
     * @return string
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function convertSize(int $size)
    {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        if ($size == 0) {
            return '0B';
        }
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
     * 模拟telnet检测主机端口是否启用（只建立链接不发送数据）
     * @param string $domain_or_ip //域名或ip
     * @param int $port //端口
     * @return bool
     * @author LinZhou <1207032539@qq.com>
     */
    public static function pingIpv4(string $domain_or_ip, int $port = 80)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        //设置超时，不然，链接不上会阻塞几十秒，这里设置1秒连不上就返回false
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 1, "usec" => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ["sec" => 1, "usec" => 0]);

        try {
            $ok = socket_connect($socket, $domain_or_ip, $port);
        } catch (\Exception $e) {
//        $error_msg=socket_strerror(socket_last_error($socket)); //错误信息
            $ok = false;
        }
        socket_close($socket);
        return $ok;
    }

    /**
     * 设置浏览器缓存
     * @param int $interval //浏览器缓存的时间，单位：秒
     */
    public static function browserCacheControl(int $interval)
    {
        header("Last-Modified: " . gmdate('D, d M Y H:i:s') . ' GMT');
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $interval) . ' GMT');
        header("Cache-Control: max-age=$interval");
        header("Pragma: public");
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $c_time = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $interval;
            if ($c_time > time()) {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }
        }
    }
}
