<?php

namespace tempsmsru\api\auth;

class HashBuilder
{
    public static function build($query_params, $secret, $isSecretHashed = false)
    {
        $secret_hash = ($isSecretHashed ? $secret : hash('sha256', $secret));
        ksort($query_params);
        $chunks = [];
        foreach ($query_params as $k => $v)
        {
            $chunks[] = $k . '=' . $v;
        }
        rsort($chunks);
        $to_hash = implode('.!.', $chunks);

        $hash = hash('sha256', $to_hash . $secret_hash);
        return $hash;
    }
}