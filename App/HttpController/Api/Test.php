<?php
/**
 * Created by PhpStorm.
 * User: stark
 * Date: 2020-11-03
 * Time: 14:27
 */

namespace App\HttpController\Api;

use App\Server\MysqlServer\PushMsg;
use EasySwoole\Http\Message\Status;
use App\Utility\Http\ParamsCheck;
use App\Server\RedisServer\CountServer;
use App\Server\RedisServer\QueueServer;

class Test extends Base
{
    /**
     *  接收个人通知的推送的消息
     */
    public function test()
    {
        return $this->writeJson(Status::CODE_OK,[],Status::getReasonPhrase(Status::CODE_OK));
    }

    public function redis()
    {
        $redis = \EasySwoole\RedisPool\RedisPool::defer('redis');
        /**
        $uid = 177380;
        for($fd = 1;$fd<=200;$fd++){
            $redis->hSet('PUSH_MSG_AUTHOR_FD', $fd, $uid);
            $redis->zAdd('PUSH_MSG_SSET_AUTHOR', $uid, $fd);
            $uid++;
        }**/

        $start = 1;
        $end  = 20;
        while (true){
            $author = $redis->zRange('PUSH_MSG_SSET_AUTHOR',$start,$end);
            var_dump($author);
            if(!$author){
                break;
            }
            $start += 20;
            $end  = $start + 20;
            echo $start.PHP_EOL;
        }


        return $this->writeJson(Status::CODE_OK,[],Status::getReasonPhrase(Status::CODE_OK));

    }
}
