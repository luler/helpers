<?php

namespace Luler\Helpers;

class CommonHelper
{
    /**
     * 数组转换成树结构
     * @param $arr //数组
     * @param string $pk //主键
     * @param string $pid //父节点字段
     * @param string $child //子节点字段
     * @return array
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function arrayToTree($arr, $pk = 'id', $pid = 'pid', $child = 'children'): array
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
     * @param int $size //字节数
     * @return string
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function convertSize(int $size): string
    {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        if ($size == 0) {
            return '0B';
        }
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
    }

    /**
     * 生成手机验证码
     * @param int $size //生成数字位数，默认4位
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function createRandomNumber(int $size = 4): string
    {
        $code = '';
        $numbers = '0123456789'; // 基础数字池
        for ($i = 0; $i < $size; $i++) {
            $code .= $numbers[random_int(0, 9)]; // 密码学安全随机
        }
        return $code;
    }

    /**
     * 删除目录函数
     * @param string $dirname
     * @return bool
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function deleteDir(string $dirname): bool
    {
        if (!file_exists($dirname) || !is_dir($dirname)) {
            return false;
        }

        try {
            //创建目录递归遍历，自动跳过 '.' 和 '..'
            $iterator = new \RecursiveDirectoryIterator(
                $dirname,
                \RecursiveDirectoryIterator::SKIP_DOTS
            );
            //一次性递归处理所有子目录和文件,
            $files = new \RecursiveIteratorIterator(
                $iterator,
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                $path = $file->getRealPath();
                $file->isDir() ? rmdir($path) : unlink($path);
            }

            rmdir($dirname);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 解压zip文件
     * @param string $zip_file 需要解压的文件路径加文件名
     * @param string $to_dir 解压后的文件夹路径
     * @return bool
     */
    public static function extractZipToFile(string $zip_file, string $to_dir): bool
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
     * @author 我只想看看蓝天 <1207032539@qq.com>
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
     * @param string $source
     * @param string $dest
     * @return bool
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function copyDir(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        try {
            // 创建目标目录及其父目录
            if (!is_dir($dest)) {
                mkdir($dest, 0777, true);
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), //过滤掉.和..目录
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $target = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                $item->isDir() ? mkdir($target) : copy($item->getPathname(), $target);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 随机生成唯一订单号（基于日期和随机乱序）
     * @param int $prefix_time_type
     * @return string
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function generateOrderId(int $prefix_time_type = 1, int $ramdom_number_size = 3): string
    {
        if ($prefix_time_type == 1) {
            //都是数字，不过存库时要使用varchar类型,唯一性更好
            $id = date_format(new \DateTime(), 'YmdHisu');
        } else {
            //bigint类型
            $id = time() . date_format(new \DateTime(), 'u');
        }
        $id .= self::createRandomNumber($ramdom_number_size);

        return $id;
    }

    /**
     * 模拟telnet检测主机端口是否启用（只建立链接不发送数据）
     * @param string $domain_or_ip //域名或ip
     * @param int $port //端口
     * @return bool
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function pingIpv4(string $domain_or_ip, int $port = 80): bool
    {
        // 输入验证
        if (empty($domain_or_ip) || $port < 1 || $port > 65535) {
            return false;
        }
        // 创建 socket
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return false;
        }
        // 设置超时
        $timeout = ['sec' => 1, 'usec' => 0];
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);
        // 连接尝试
        $result = @socket_connect($socket, $domain_or_ip, $port);
        // 清理资源
        socket_close($socket);

        return $result;
    }

    /**
     * 设置浏览器缓存
     * @param int $interval //浏览器缓存的时间，单位：秒
     */
    public static function browserCacheControl(int $interval)
    {
        //必须先设置头，防止下面304返回的请求覆盖原来的请求头
        header("Last-Modified: " . gmdate('D, d M Y H:i:s') . ' GMT');
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $interval) . ' GMT');
        header("Cache-Control: max-age=$interval");
        header("Pragma: public"); //防止session_start会将该值置为no-cache
        //如果浏览器请求头带这个，判断过期时间，缓存有效则返回304即可
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $c_time = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $interval;
            if ($c_time > time()) {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }
        }
    }

    /**
     * 构建批量更新sql
     * @param string $table
     * @param array $data
     * @param string $primary_key
     * @return false|string
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function buildBatchUpdateSql(string $table, array $data = [], string $primary_key = 'id')
    {
        $keys = array_keys($data[0] ?? []);
        if (!in_array($primary_key, $keys)) {
            return false;
        }
        array_walk_recursive($data, function (&$value) {
            $value = is_string($value) ? "'{$value}'" : $value;
        });
        $sql = "UPDATE `{$table}` SET ";
        foreach ($keys as $key) {
            if ($key == $primary_key) {
                continue;
            }
            $sql .= "`{$key}` = CASE `{$primary_key}`";
            foreach ($data as $val) {
                $sql .= " WHEN {$val[$primary_key]} THEN {$val[$key]}";
            }
            $sql .= ' END,';
        }
        $sql = trim($sql, ',');
        $primary_key_ids = array_column($data, $primary_key);
        $sql .= " WHERE `{$primary_key}` IN (" . join(',', $primary_key_ids) . ')';
        return $sql;
    }

    /**
     * 重置图片（可实现低损压缩）
     * @param string $source_filename //原图片文件路径
     * @param string $destination_filename //目标图片文件路径
     * @param $scale_rate_size //缩放比例，1-原比例,或者传入宽高数组:[400,300]
     * @param $quality //图片质量，0-100，值越大质量越好
     * @return void
     */
    public static function resetImage(string $source_filename, string $destination_filename = '', $scale_rate_size = 1, int $quality = 75)
    {
        $ext = strtolower(strrchr($source_filename, '.'));
        if (!in_array($ext, ['.jpg', '.jpeg', '.png'])) {
            throw new \Exception('图片扩展名不支持');
        }
        $destination_filename = empty($destination_filename) ? $source_filename : $destination_filename;
        //获取原图尺寸
        list($width, $height) = getimagesize($source_filename);
        //缩放尺寸
        if (is_array($scale_rate_size)) {
            list($new_width, $new_height) = $scale_rate_size;
        } else {
            $new_width = $width * $scale_rate_size;
            $new_height = $height * $scale_rate_size;
        }
        $src_image = imagecreatefromstring(file_get_contents($source_filename));
        $dst_image = imagecreatetruecolor($new_width, $new_height);
        // 保持PNG图片的透明度
        if ($ext === '.png') {
            imagesavealpha($dst_image, true);
            $trans_color = imagecolorallocatealpha($dst_image, 0, 0, 0, 127);
            imagefill($dst_image, 0, 0, $trans_color);
            imagealphablending($dst_image, false);
        }
        imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        if ($ext === '.png') {
            imagepng($dst_image, $destination_filename, range(9, 0)[floor(($quality > 90 ? 90 : $quality) / 10)] ?? 0);
        } else {
            //输出压缩后的图片
            imagejpeg($dst_image, $destination_filename, $quality);
        }
        // 销毁图像资源
        imagedestroy($dst_image);
        imagedestroy($src_image);
    }
}
