<?php

namespace tempsmsru\api\exception;

use Exception;

class ApiCallBaseException extends \Exception
{
    public $status;
    public $name;
    public $type;

    public $deserialized;

    public static function create($info)
    {
        $exception = new static($info['message'], $info['code']);
        $exception->status = $info['status'];
        $exception->name = $info['name'];
        $exception->type = $info['type'];

        $exception->deserialized = $info;

        return $exception;
    }

    public function __toString()
    {
        $msg = [];
        foreach ($this->deserialized as $attr => $value)
        {
            $msg[] = $attr . ': ' . $value;
        }
        $msg[] = 'parent message: ' . parent::__toString();
        return implode("\n", $msg);
    }
}