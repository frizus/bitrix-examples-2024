<?php

namespace Frizus\Mindbox;

use Mindbox\DTO\DTO;
use Frizus\Helpers\Arr;

class DTORead
{
    public static function readArray($key, $result)
    {
        return static::read($key, $result, []);
    }

    /**
     * @param $key
     * @param DTO|array $result
     */
    public static function read($key, $result, $default = null)
    {
        $result = Arr::getSafely($key, $result, $default);
        if ($result instanceof DTO) {
            $result = $result->getFieldsAsArray();
        }
        return $result;
    }
}