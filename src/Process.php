<?php
namespace Loktar\GeneralTools;

use Closure;

class Process
{
    /**
     * [start 进程管理] 闭合函数能得到总进程数和当前进程序号 数据处理由业务调用方根据进程序号进行逻辑
     * @param  Closure $closure [任务]
     * @param int $number
     * @param int $max_num_concurrent
     * @return void [type]           [description]
     */
    static public function start(Closure $closure, $number = 1, $max_num_concurrent = 10)
    {
        $pcntlStatus = in_array('pcntl',get_loaded_extensions());
        $posixStatus = in_array('posix',get_loaded_extensions());

        //当前的子进程数量
        global $curChildPro;

        $curChildPro = 0;
        //配合pcntl_signal使用，简单的说，是为了让系统产生时间云，让信号捕捉函数能够捕捉到信号量
        declare(ticks = 1);
        pcntl_signal(SIGCHLD, "sig_handler");// http里面没用

        //注册子进程退出时调用的函数。SIGCHLD：在一个进程终止或者停止时，将SIGCHLD信号发送给其父进程。

        if($number > 1){
            //进程分配
            for ($i = 1; $i <= $number; $i++) {
                $curChildPro++;
                $pid = pcntl_fork(); // 创建子进程
                if (!$pid) {

                    try{

                        $closure($number, $i, getmypid());

                    }catch (\Exception $exception){
                        //自杀自己的进程

                    } finally{

                        $curChildPro--;
                        //自杀自己的进程
                        posix_kill(posix_getpid() , SIGTERM);
                        exit($i);
                    }
                }else{
                    //父进程运行代码,达到上限时父进程阻塞等待任一子进程退出后while循环继续
                    // 判断  打到进程数目先知 暂停等待 进程退出后补充
                    if ($curChildPro >= $max_num_concurrent) {
                        pcntl_wait($status);// 等待有退出 在继续循环  创建进程
                    }
                }
            }

            // pcntl_waitpid 第一个参数为 0 代表处理全部子进程

            while (pcntl_waitpid(0, $status) != -1) {
                $status = pcntl_wexitstatus($status);
            }

        }else{

            $closure($number, 1,getmypid());
        }

    }

}

