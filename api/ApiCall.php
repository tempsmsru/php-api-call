<?php

namespace tempsmsru\api;


class ApiCall
{
    public $host;
    public $app_id;
    public $secret;
    public $request_args;
    public $debug;
    public $version;
    public $httpAuth = [];

    protected $curlOpts = [];

    public function __construct($host, $app_id = null, $secret = null, $request_args = null, $debug = false, $version = 'v2')
    {
        $this->host = $host;
        $this->app_id = $app_id;
        if ($secret)
        {
            $this->secret = hash('sha256', $secret);
        } else
        {
            $this->secret = null;
        }
        $this->request_args = $request_args ? $request_args : [];
        $this->debug = $debug;
        $this->version = $version;
    }

    public function post($method_name, $as_json = true, $params = [], $request_args = [])
    {
        $result = $this->call('POST', $method_name, $params, $request_args);
        if ($as_json)
        {
            return json_decode($result);
        }
        return $result;
    }

    public function postBool($method_name, $as_json = true, $params = [], $request_args = [])
    {
        try
        {
            $this->post($method_name, $as_json, $params, $request_args);
            return true;
        } catch (Exception $e)
        {
            return false;
        }
    }

    public function buildSign($query_params, $secret)
    {
        krsort($query_params);
        $chunks = [];
        foreach ($query_params as $k => $v)
        {
            $chunks[] = $k . '=' . $v;
        }
        $to_hash = implode('.!.', $chunks);


        $hash = hash('sha256', $to_hash . $secret);
        if($this->debug)
        {var_dump($hash); ob_flush();}
        return $hash;
    }

    public function call($query_method, $method_name, $params, $request_args = [], $retry_times = 3, $retry_timeout = 3)
    {
        $secure = true;
        if (isset($request_args['secure']))
        {
            $secure = $request_args['secure'];
        }

        $query_params = array_merge($this->request_args, $params);
        if ($secure)
        {
            $query_params['app_id'] = $this->app_id;
            $query_params['hash'] = $this->buildSign($query_params, $this->secret);
        }

        $json_str = json_encode($query_params);
        if ($this->debug)
        {
            var_dump($json_str);
            ob_flush();
        }

        for ($i = 0; $i < $retry_times; $i++)
        {
            $curl = curl_init(implode('/', [$this->host, $this->version, $method_name]));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($query_method));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json_str);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 5000);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_str)
            ]);

            foreach ($this->curlOpts as $p => $v)
            {
                curl_setopt($curl, $p, $v);
            }

            $response = curl_exec($curl);
            $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            switch ($response_code)
            {
                case 401:
                    $this->curlOpts[CURLOPT_USERPWD] = implode(':', $this->httpAuth);
                    break;
                case 200:
                    return $response;
                default:
                    throw new Exception('Request ended with HTTP code: ' . $response_code);
            }
        }
        throw new Exception('Request error!');
    }

}