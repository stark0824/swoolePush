<?php
/**
 * 调用主站的Api接口
 */

namespace App\Utility\Http;

use \EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Config;

class OAuth
{

    const KEY = "这里是需要的秘钥串";

    /**
     * 验证token
     * @param string $loginKey
     * @return mixed
     */
    public static function getUserInfo(string $loginKey): int
    {
        $urlConf = Config::getInstance()->getConf('url');
        $params = self::_formatQueryData($loginKey);
        $result = Curl::getUrl($urlConf['oauth_api'] . '?' . $params);
        Logger::getInstance()->log('传入Url:' . $urlConf['oauth_api'] . '?' . $params . ',返回结果msgErrorRet:' . json_encode($result), Logger::LOG_LEVEL_INFO, 'gzcp_token');
        if ($result['code'] == 200 && $result['data']['uid']) {
            return (int)$result['data']['uid'];
        } else {
            return 0;
        }
    }


    public static function setToken(array $data): string
    {
        ksort($data);
        $s = "";
        foreach ($data as $k => $v) {
            $s .= $k . $v;
        }
        return md5($s . self::KEY);
    }


    public static function getCustomerLists(string $loginKey): array
    {
        $urlConf = Config::getInstance()->getConf('url');
        $params = self::_formatQueryData($loginKey);
        $result = Curl::getUrl($urlConf['customer_lists'] . '?' . $params);
        Logger::getInstance()->log('传入Url:' . $urlConf['customer_lists'] . '?' . $params . ',返回结果msgErrorRet:' . json_encode($result), Logger::LOG_LEVEL_INFO, 'customer_list');
        $customerList = [];
        if (200 == $result['code'] && is_array($result['data']) && !empty($result['data'])) {
            $customerList = $result['data'];
        }
        return $customerList;
    }

    public static function getMessageGroupNumber($loginKey)
    {
        $urlConf = Config::getInstance()->getConf('url');
        $params = self::_formatQueryData($loginKey);
        $result = Curl::getUrl($urlConf['group_unread'] . '?' . $params);
        Logger::getInstance()->log('传入Url:' . $urlConf['group_unread'] . '?'
            . $params . ',返回结果msgErrorRet:' . json_encode($result),
            Logger::LOG_LEVEL_INFO, 'group_unread');

        $number = 0;
        if (200 == $result['code'] ) {
            $number = $result['data']['number'];
        }
        return $number;
    }



    private static function _formatQueryData(string $loginKey): string
    {
        $data['timestamp'] = time();
        $data['loginkey'] = $loginKey;
        $token = self::setToken($data);
        $data['token'] = $token;
        return http_build_query($data);
    }
}

