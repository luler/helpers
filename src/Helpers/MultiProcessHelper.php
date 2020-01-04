<?php

namespace Luler\Helpers;

class MultiProcessHelper
{
    private static $instance;
    private static $process_count = 10;
    private static $childs = [];
    private static $pid;

    public function __destruct()
    {
        //等待回收所有子进程、清空配置
        if (self::$pid == posix_getpid()) {
            self::recycleProcess();
        }
    }

    public static function instance(int $process_count = 10)
    {
        if (is_null(self::$instance)) {
            if ($process_count <= 0) {
                throw new \Exception('进程数不能小于0');
            }
            self::$process_count = $process_count;
            self::$pid = posix_getpid(); //父进程id
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 开多进程执行任务
     * Author:我只想看看蓝天<1207032539@qq.com>
     * @param $function //闭包函数
     * @param array $param //数组参数
     * @throws \Exception
     */
    public function multiProcessTask($function, $param = [])
    {
        again:
        if (self::$process_count > 0) {
            $pid = pcntl_fork();
            if ($pid > 0) {
                self::$childs[] = $pid;
                self::$process_count--;
            } elseif ($pid == 0) {
                call_user_func_array($function, $param);
                exit();
            } else {
                throw new \Exception('创建子进程失败');
            }
        } else {
            self::recycleProcess(0);
            usleep(10);
            goto again;
        }
    }

    /**
     * 回收进程
     * @param int $hang_up
     * @author LinZhou <1207032539@qq.com>
     */
    private static function recycleProcess($hang_up = 1)
    {
        if (!empty(self::$childs)) {
            $has_stop_childs = [];
            foreach (self::$childs as $child) { //释放子进程
                if ($hang_up) {
                    pcntl_waitpid($child, $status);
                    $has_stop_childs[] = $child;
                } else {
                    $res = pcntl_waitpid($child, $status, WNOHANG);
                    if ($res) {
                        $has_stop_childs[] = $child;
                        self::$process_count++;
                    }
                }
            }
            self::$childs = array_diff(self::$childs, $has_stop_childs);
        }
    }

}
