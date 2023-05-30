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
        //父进程销毁前，要等待回收所有子进程、清空配置
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
     * 开多进程执行任务第一种方式，这种方式每个任务都会新建一个子进程来执行任务，任务里的所有链接资源都是独立的，消耗资源较多
     * Author:我只想看看蓝天<1207032539@qq.com>
     * @param $function //闭包函数
     * @param array $param //数组参数
     * @throws \Exception
     */
    public function multiProcessTask(callable $function, array $param = [])
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
     * 开多进程执行任务第二种方式，这种方式是将任务平均分给规划的子进程去执行，一个子进程执行n个分配到的任务，
     * 任务期间的数据库链接、redis链接可以在当前子进程下共享
     * @param array $tasks
     * @param callable $function
     * @throws \Exception
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function multiProcessTaskV2(array $tasks, callable $function)
    {
        $process_tasks = [];
        $index = 0;
        foreach ($tasks as $task) {
            if ($index >= self::$process_count) {
                $index = 0;
            }
            $process_tasks[$index][] = $task;
            $index++;
        }
        foreach ($process_tasks as $process_task) {
            $pid = pcntl_fork();
            if ($pid > 0) {//父进程
                self::$childs[] = $pid;
            } elseif ($pid == 0) { //子进程执行回调任务
                foreach ($process_task as $item) {
                    $function($item);
                }
                exit();
            } else {
                throw new \Exception('创建子进程失败');
            }
        }
        //回收所有子进程
        self::recycleProcess();
    }

    /**
     * 回收进程
     * @param int $hang_up
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public static function recycleProcess(int $hang_up = 1)
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
