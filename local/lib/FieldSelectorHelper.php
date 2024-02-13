<?php

namespace Frizus;

class FieldSelectorHelper
{
    public static function getField($container, $key)
    {
        if (is_object($container) &&
            method_exists($container, 'getField')
        ) {
            return $container->getField($key);
        }

        return $container[$key];
    }

    public static function getIntField($container, $key)
    {
        return (int)self::getField($container, $key);
    }

    public static function getFloatField($container, $key)
    {
        return (float)self::getField($container, $key);
    }
}