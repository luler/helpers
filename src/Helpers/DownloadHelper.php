<?php
/**
 * Created by PhpStorm.
 * User: LinZhou <1207032539@qq.com>
 * Date: 2018/11/2
 * Time: 11:00
 */

namespace Luler\Helpers;

class DownloadHelper
{
    /**
     * 文件下载(可断点下载)
     * @param $filename
     * @param $path
     * @author LinZhou <1207032539@qq.com>
     */
    public static function downloadFileByPath($filename, $path)
    {
        $fb = fopen($path, "rb");
        $size = filesize($path);
        $lenth = $size;
        if (isset($_SERVER['HTTP_RANGE'])) {
            preg_match("/bytes=([0-9]+)-/i", $_SERVER['HTTP_RANGE'], $match);
            $start = $match[1];
        } else {
            $start = 0;
        }
        //设置返回头
        if ($start > 0) {
            //设置断点下载头
            fseek($fb, $start);
            header("HTTP/1.1 206 Partical Content");
            header("Content-Range:bytes " . $start . "-" . ($size - 1) . "/" . $size);
            $lenth = $size - $start + 1;
        } else {
            header("Content-Range:bytes 0-" . ($size - 1) . "/" . $size);
        }
        header("Cache-control:public");
        header("Pragma:public");
        header("Content-Length:" . $lenth); //当前要下载的文件大小
        header("Accept-Ranges:bytes"); //告诉浏览器，该资源支持部分传输
        header("Content-Type:application/octet-stream"); //内容类型为字节流
        //中文名兼容各种浏览器
        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename=' . urlencode($filename));
        }
        fpassthru($fb);
        fclose($fb);
    }
}
