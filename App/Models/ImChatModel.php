<?php
# +----------------------------------------------------------------------
# | Author:Stark
# +----------------------------------------------------------------------
# | Date:2022/11/03
# +----------------------------------------------------------------------
# | Desc: wm_user_user model
# +----------------------------------------------------------------------
namespace App\Models;
use EasySwoole\ORM\AbstractModel;

class ImChatModel extends AbstractModel
{
    //选择连接的数据库
    protected $connectionName = 'mysql-msg';

    protected $tableName = 'im_user_chat_record_0';

}
