<?php

namespace tempsmsru\api;

use tempsmsru\api\auth\HashBuilder;

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

    public function __construct($host, $app_id = null, $secret = null, $params = [])
    {
        $this->host = $host;
        $this->app_id = $app_id;
        $this->secret = $secret;
        
        $this->request_args = $this->getAttr($params, 'request_args', []);
        $this->debug = $this->getAttr($params, 'debug', false);
        $this->version = $this->getAttr($params, 'version', 'v2');
    }

    protected function getAttr($source, $attr, $default = null)
    {
        if (is_array($source))
        {
            return isset($source[$attr]) ? $source[$attr] : $default;
        } elseif (is_object($source))
        {
            if (in_array($attr, get_class_vars($source)))
            {
                return $source->$attr;
            } else
                return $default;
        }
    }

    public function get($method_name, $query_params = null, $params = [])
    {
        $as_json = $this->getAttr($params, 'as_json', true);
        $query_string = $method_name;
        if (is_array($query_params))
        {
            $query_string .= "?" . http_build_query($query_params);
        }
        $result = $this->call("GET", $query_string);
        if ($as_json)
        {
            return json_decode($result);
        }
        return $result;
    }

    public function getBool($method_name, $query_params, $params = [])
    {
        try
        {
            $this->get($method_name, $query_params, $params);
            return true;
        } catch (\Exception $e)
        {
            return false;
        }
    }

    public function post($method_name, $body = [], $params = [])
    {
        $as_json = $this->getAttr($params, 'as_json', true);
        $result = $this->call('POST', $method_name, $body, $params);
        if ($as_json)
        {
            return json_decode($result);
        }
        return $result;
    }

    public function postBool($method_name, $body = [], $params = [])
    {
        try
        {
            $this->post($method_name, $body, $params);
            return true;
        } catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * @param $query_method string Request method name (GET, POST, PUT, DELETE, etc...)
     * @param $method_name string Api method name to be called
     * @param $params array Additional args which will be passed as request body
     * @return mixed
     * @throws \Exception
     */
    public function call($query_method, $method_name, $body = [], $params = [])
    {
        $secure = $this->getAttr($params, 'secure', true);
        $retry_times = $this->getAttr($params, 'retry_times', 3);

        $query_params = array_merge($this->request_args, $body);
        if ($secure)
        {
            $query_params['app_id'] = $this->app_id;
            $query_params['hash'] = HashBuilder::build($query_params, $this->secret);
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
                    throw new \Exception('Request ended with HTTP code: ' . $response_code . "; Response: " . $response, $response_code);
            }
        }
        throw new \Exception('Request error!', 500);
    }

}