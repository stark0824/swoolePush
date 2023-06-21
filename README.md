#### 概述
- 推送采用websocket长连接，采用接口形式获取，客户端缓存5-10分钟。
- 断线重连最多三次，时间间隔为60s、120s、180s，**是上次交互的时间 与检测时候的时间相差180s，就被被踢出。**
- 客户端每180s发送一次心跳包检测连接是否可用，如果接收到服务器的推送消息，则从当前开始计算180s，减少无效的心跳检测。 **如果心跳包在30s内无响应，则再发送一个心跳包，等待30s，无反应，判定连接失效**。
- 客户端新连接成功，如果用户登录，需要发送登录ukey(web)或token(app)给服务器进行登录，未登录成功间隔30s重试一次，一经登录，除非断开或者调用退出登录，否则不会掉线。
- 连接支持ws和wss，建议使用wss安全连接。
- 登录使用ukey(web端) / token(App端) 去请求gzcp主站的验证接口。
- 请求数据除了body字段是可以为空的，服务端会做校验和检测，具体以wiki例子为主。
- ws在线测试工具：http://www.easyswoole.com/wstool.html

##### 0.开发环境/代码

开发环境：

```
swoole version                4.4.23
php version                   7.2.10
```

测试服 ：host

```
外网：spush.test.gongzicp.com
内网：http://swoole-msg:18081
```

生产环境：host:

```
内网：swoole.push.com
外网：spush.gongzicp.com
```


安装swoole,git地址：https://github.com/swoole/swoole-src/tree/v4.4.x

```
git clone https://github.com/swoole/swoole-src.git

cd swoole-src && \
phpize && \
./configure \
--enable-openssl \
--enable-http2 && \
make && sudo make install
```

swoole-push分支
```
git clone git@59.110.162.133:/home/git/swoole-push.git
```

gzcp分支  swoole_push_0113
```
/gzcp/library/GoPush/Base.php
/gzcp/library/GoPush/Push.php
```

##### 1. 初始化

1.1 安装需要的扩展

```
composer install
```

2.配置环境变量 ，路径`/bootstrap.php`

```php
//环境变量开关 0本地，1测试 2线上
define('PUSHENV', 0);
```

3.设置允许最大链接数 `MaxLinkNumber` , 路径：swoole-push/App/SystemConf.php,修改之后执行热重启。

备注：swoole 现在不支持主进程热重启

4.配置文件路径

/swoole-push/App/Conf/Test/ 测试服配置目录：

```
测试服mysql：/swoole-push/App/Conf/Test/db.php
测试服redis：/swoole-push/App/Conf/Test/redis.php

/swoole-push/App/Conf/Test/ 正式服配置目录：
正式服mysql：/swoole-push/App/Conf/Online/db.php
正式服redis：/swoole-push/App/Conf/Online/redis.php
```

##### 2.swoole服务的系统命令

1启动/停止 服务
```php
//-d 守护进程的方式启动服务 
php easyswoole server start -mode=websocket -d

//停止服务
php easyswoole server stop -mode=websocket

//强制停止服务
php easyswoole server stop -force -mode=websocket

//热重启
php easyswoole server reload -mode=websocket
```

2.进程管理：

```php
//显示所有进程,以Mb方式显示
php easyswoole process show -d -mode=websocket

//杀死指定进程
php easyswoole process kill --pid=PID -mode=websocket

//杀死指定进程组(GROUP)
php easyswoole process kill --group=GROUP_NAME -mode=websocket

//杀死所有进程
php easyswoole process killAll -mode=websocket

//强制杀死进程,需要带上 -f 参数
php easyswoole process kill --pid=PID -f -mode=websocket
```
3.Crontab 命令

可执行 `php easyswoole crontab -h` 来查看具体操作。

```
//查看所有注册的Crontab
php easyswoole crontab show -mode=websocket

//停止指定的Crontab
php easyswoole crontab stop --name=TASK_NAME -mode=websocket

//恢复指定的Crontab
php easyswoole crontab resume --name=TASK_NAME -mode=websocket

//立即跑一次指定的Crontab
php easyswoole crontab run --name=TASK_NAME -mode=websocket
```

4.Task管理

```
//查看 Task 进程状态
php easyswoole task status -mode=websocket
```


##### 2.Nginx 配置ws

```
upstream swoole-push {
    # 将负载均衡模式设置为IP hash
    ip_hash;
    server 127.0.0.1:9501;
}

server {
    listen 80;
    server_name swoole.push.com;

    location / {
        # websocket的header
        proxy_http_version 1.1;
        # 升级http1.1到websocket协议
        proxy_set_header Upgrade websocket;
        proxy_set_header Connection "Upgrade";

        # 将客户端host及ip信息转发到对应节点
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;

        # 客户端与服务端300s之内无交互，将自动断开连接。
        proxy_read_timeout 300s ;

        # 代理访问真实服务器
        proxy_pass http://swoole-push;
    }
}
```


##### 3. Gzcp的通信

Swoole-push和Gzcp有两处通信：


**1.Token 验证接口, `open.gzcp.com`是对内接口**

```
# 正式服Url 
http://open.gzcp.com/push/user/getappuid
# 测试服Url 
http://open.gzcp.com/login/pushMsgLogin
```

测试服参考数据：

字段名 | 说明 | 参考值
---|---|---
timestamp | unix时间戳 |1612322032
loginkey | 客户端传递的Token| c1d0ab0add36c27e94efea2c65009691e3659705
token | 服务端自定义Token |98306025f1f216cdce7bcdb488c94753

```
?timestamp=1612322032
&loginkey=c1d0ab0add36c27e94efea2c65009691e3659705
&token=98306025f1f216cdce7bcdb488c94753
```

返回结果 ,没找到是0 :

```
{"code":200,"data":{"uid":176906}}
```

**2.接收Gzcp的Push**

文件路径：swoole-push/App/HttpController/Api/

接口Url：

```
#评论通知：/swoole-push/App/HttpController/Api/Comment.php
/api/comment/message

#系统消息通知 /swoole-push/App/HttpController/Api/Notice.php
/api/notice/systemMessage

#个人消息通知 /swoole-push/App/HttpController/Api/Notice.php
/api/notice/userMessage
```

**3.Crontab**

```
PushUserNoticeMsg   */6 * * * *  个人通知，6分钟执行一次
PushSystemNoticeMsg * 0-23/3 * * * 全局通知，3小时执行一次
PushCommentMsg  */6 * * * *  评论通知，6分钟执行一次
```