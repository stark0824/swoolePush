<?php
# +----------------------------------------------------------------------
# | Author:Stark
# +----------------------------------------------------------------------
# | Date:2022/11/03
# +----------------------------------------------------------------------
# | Desc: 主站用户服务认证
# +----------------------------------------------------------------------

namespace App\Server\ChangPeiServer;

use App\Utility\Http\OAuth;
use App\Server\Server;

class UserServer extends Server
{
    public function getUserId(string $token): int
    {
        $uid = \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) use ($token) {
            $uid = $redis->get($token);
            $expireTime = 3650 + rand(1, 3000);
            if (empty($uid)) {
                //远程验证token
                $uid = OAuth::getUserInfo($token);
                if (!empty($uid) && intval($uid) > 0) {
                    //存入缓存时间，过期时间小于 7300s
                    $redis->setEx($token, $expireTime, $uid);
                }
                if ($uid && $uid > 0) {
                    $key = 'token_' . $uid;
                    $redis->setEx($key, $expireTime, $token);
                }
                return $uid;
            } else {
                if ($uid > 0) {
                    $key = 'token_' . $uid;
                    $redis->setEx($key, $expireTime, $token);
                }
                return $uid;
            }
        }, self::REDIS_CONN_NAME);
        return intval($uid);
    }


    public function checkSign(string $token): int
    {
        $uid = \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) use ($token) {
            $uid = $redis->get($token);
            if (empty($uid)) {
                //远程验证token
                $uid = OAuth::getUserInfo($token);
                if (!empty($uid) && intval($uid) > 0) {
                    //存入缓存时间，过期时间小于 7300s
                    $expireTime = 3650 + rand(1, 3000);
                    $redis->setEx($token, $expireTime, $uid);
                }
                return $uid;
            } else {
                return $uid;
            }
        }, self::REDIS_CONN_NAME);
        return intval($uid);
    }


    public function getTokenByUid($uid)
    {
        return \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) use ($uid) {
            return $redis->get('token_' . $uid);
        }, self::REDIS_CONN_NAME);
    }

}
