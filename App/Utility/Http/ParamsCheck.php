<?php
/**
 * Created by PhpStorm.
 * User: stark
 * Date: 2020-11-12
 * Time: 09:28
 */

namespace App\Utility\Http;

use EasySwoole\Http\Message\Status;

class ParamsCheck
{
    public static function checkRequestMethod(string $method): array
    {
        $msgErrorRet = [];
        if ('post' !== strtolower($method)) {
            $msgErrorRet['code'] = Status::CODE_METHOD_NOT_ALLOWED;
            $msgErrorRet['result'] = [];
            $msgErrorRet['msg'] = Status::getReasonPhrase(Status::CODE_METHOD_NOT_ALLOWED);
        }
        return $msgErrorRet;
    }

    public static function checkRequestData(array $index = [], array $body = []): array
    {
        $keys = array_keys($body);
        $msgErrorRet = [];
        foreach ($index as $v) {
            if (!in_array($v, $keys)) {
                $msgErrorRet['code'] = Status::CODE_REQUESTED_RANGE_NOT_SATISFIABLE;
                $msgErrorRet['result'] = [];
                $msgErrorRet['msg'] = "{$v} not found";
                break;
            }
        }
        return $msgErrorRet;
    }
}


