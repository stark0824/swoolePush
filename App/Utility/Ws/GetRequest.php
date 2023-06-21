<?php
/**
 * Created by PhpStorm.
 * User: stark
 * Date: 2020-11-12
 * Time: 09:28
 */

namespace App\Utility\Ws;

class GetRequest
{

    public function getToken( $token )
    {
        return $token ?? '';
    }

    public function getSyncStamp( $syncStamp )
    {
        return intval($syncStamp ?? 0);
    }

    public function getToUid( $toUid )
    {
        return intval($toUid ?? 0);
    }

    public function getNoceAck( $ack )
    {
        return $ack ?? '';
    }

    public function getIdentity($identity)
    {
        return $identity ?? '';
    }

    public function getUnread($number)
    {
        return $number ?? '';
    }

}
