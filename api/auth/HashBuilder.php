<?php

namespace tempsmsru\api\auth;

class HashBuilder
{
    public static function build($query_params, $secret)
    {
        $secret_hash = hash('sha256', $secret);
        krsort($query_params);
        $chunks = [];
        foreach ($query_params as $k => $v)
        {
            $chunks[] = $k . '=' . $v;
        }
        $to_hash = implode('.!.', $chunks);

        $hash = hash('sha256', $to_hash . $secret_hash);
        return $hash;
    }
}