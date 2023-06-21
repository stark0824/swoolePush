<?php
/**
 * stark张宇
 * 推送评论消息 - 计划任务
 */
namespace App\Crontab;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\ServerManager;


class PushCommentDelayMsg extends AbstractCronTask
{
    const PUSH_MSG_COMMENT_DELAY_LISTS = 'PUSH_MSG_COMMENT_DELAY_LISTS';
    const PUSH_MSG_SSET_USER_LOGIN = 'PUSH_MSG_SSET_USER_LOGIN';
    protected $limit = 1000; //每次执行查询的条数
    protected $timeOut = 1296000;  //15天

    public static function getRule(): string
    {
        return '*/11 * * * *';
    }

    public static function getTaskName(): string
    {
        return  'PushCommentDelayMsg';
    }

    function run( $taskId, $workerIndex)
    {
        $redis = \EasySwoole\RedisPool\RedisPool::defer('redis');
        $server = ServerManager::getInstance()->getSwooleServer();
        //消费队列
        //每分钟消费limit条
        $data = $redis->lRange(self::PUSH_MSG_COMMENT_DELAY_LISTS, 0, $this->limit );
        if (!empty($data) && is_array($data)){
            $pushLists = [];
            $lRemList = [];
            foreach ($data as $json){
                $msgPushInfo = json_decode($json,true);
                if(isset($msgPushInfo['to_uid']) && !empty($msgPushInfo['to_uid'])){
                    $delayList = [];
                    //用户超过1天不上线，放入延迟队列
                    $diff_unix_times = time() - $msgPushInfo['create_time'];
                    if( $diff_unix_times > $this->timeOut  ){
                        $delayList[] = $json;
                    }  else {
                        $lRemList[$msgPushInfo['to_uid']][] = $json;
                        $pushLists[$msgPushInfo['to_uid']]['uid'] = $msgPushInfo['to_uid'];
                        $pushLists[$msgPushInfo['to_uid']]['noce_ack'] = $msgPushInfo['noce_ack'];
                    }
                }else{
                    //错误数据及时清理
                    $redis->lRem(self::PUSH_MSG_COMMENT_DELAY_LISTS, 0, $json);
                }
            }

            //在线活跃用户的推送处理
            if(isset($pushLists) && !empty($pushLists)){
                foreach ($pushLists as $value){
                    $comment_msg = [
                        'msg_type' => 4,
                        'code' => 200,
                        'body'  => ['uid' => $value['uid'] ],
                        'msg' => 'SUCCESS',
                        'noce_ack' => $value['noce_ack']
                    ];
                    //根据uid读取出所有建立链接的端，最多5个
                    $zUidLists = $redis->zRangeByScore(self::PUSH_MSG_SSET_USER_LOGIN,
                        (int)$value['uid'],(int)$value['uid'],
                        ['withScores' => true, 'limit' => array(0, 5)]
                    );
                    if(isset($zUidLists) && !empty($zUidLists)){
                        $ret = [];
                        $fdList = array_keys($zUidLists);
                        foreach ($fdList as $fdKeys){
                            $bool = $server->push($fdKeys,json_encode($comment_msg));
                            //推送成功后，移除元素
                            if($bool){
                                $ret[] = $bool;
                            }

                        }
                        //推送成功后，移除元素
                        if(!empty($ret) || count($fdList) >=2 ){
                           foreach ($lRemList[$value['uid']] as $listVal) {
                               $redis->lRem(self::PUSH_MSG_COMMENT_DELAY_LISTS, 1, $listVal);
                           }
                        }

                    }
                }
            }

            //超时清理
            if(isset($delayList) && !empty($delayList)){
                foreach ($delayList as $delValue){
                    $redis->lRem(self::PUSH_MSG_COMMENT_DELAY_LISTS, 1, $delValue);
                }
            }
        }
    }

    function onException(\Throwable $throwable,  $taskId,  $workerIndex)
    {
        echo $throwable->getMessage();
    }

    /**
     * @param $uid
     * @return string
     */
    protected function _getTableName($uid){
        $tableIndex = intval($uid % 128);
        return 'user_push_msg_'.$tableIndex;
    }
}
