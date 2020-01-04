<?php
/**
 * Created by PhpStorm.
 * User: LinZhou <1207032539@qq.com>
 * Date: 2020/1/4
 * Time: 14:51
 */

namespace Test;

use Luler\Helpers\CommonHelper;
use Luler\Helpers\EncryptHelper;
use Luler\Helpers\MultiProcessHelper;
use PHPUnit\Framework\TestCase;

class Test extends TestCase
{
    public function testCommonHelper()
    {
        var_dump(CommonHelper::strLengthLimit('asFAWESGARDSGA', 5, '...'));
    }

    public function testEncryptHelper()
    {
        $clear = 'hello world!';
        print_r(['clear' => $clear]);
        $ciper = EncryptHelper::instance()->aesEncrypt($clear, '123456');
        print_r(['ciper' => $ciper]);
        $clear = EncryptHelper::instance()->aesDecrypt($ciper, '123456');
        print_r(['clear' => $clear]);
    }

    public function testMultiProcessHelper()
    {
        for ($i = 0; $i < 20; $i++) {
            MultiProcessHelper::instance()->multiProcessTask(function () use ($i) {
                var_dump('Process:' . $i);
            });
        }
    }
}
