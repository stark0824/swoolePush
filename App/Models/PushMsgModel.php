<?php
# +----------------------------------------------------------------------
# | Author:Stark
# +----------------------------------------------------------------------
# | Date:2022/11/03
# +----------------------------------------------------------------------
# | Desc: im_user_relation model 聊天记录表
# +----------------------------------------------------------------------
namespace App\Models;
use EasySwoole\ORM\AbstractModel;

/**
 * 推送Model模型
 * Class UserShop
 */
class PushMsgModel extends AbstractModel
{
    //选择连接的数据库
    protected $connectionName = 'mysql-msg';

    protected $tableName = 'user_push_msg_0';

}