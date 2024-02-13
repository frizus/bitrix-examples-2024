<?php
namespace Frizus\Helpers;

class Arr
{
    /**
     * @see https://gist.github.com/SeanCannon/6585889?permalink_comment_id=2122319#gistcomment-2122319
     */
    public static function flatten($array)
    {
        $result = [];

        if (!is_array($array)) {
            $array = func_get_args();
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = [...$result, ...static::flatten($value)];
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function pluckUnique($array, $key)
    {
        $column = static::pluck($array, $key);
        $column = array_unique($column);

        return $column;
    }

    public static function pluck($array, $key, $preserveKeys = false)
    {
        $column = [];
        $i = 0;
        foreach ($array as $cursor => $value) {
            $columnKey = $preserveKeys ? $cursor : $i++;
            $column[$columnKey] = static::get($key, $value);
        }

        return $column;
    }

    public static function keysOfMinAndMax($array, $key = [], $checkIfValueIsNull = false)
    {
        return [
            static::keyOfMin($array, $key, $checkIfValueIsNull),
            static::keyOfMax($array, $key, $checkIfValueIsNull)
        ];
    }

    public static function minAndMax($array, $key = [], $checkIfValueIsNull = false)
    {
        return [
            static::min($array, $key, $checkIfValueIsNull),
            static::max($array, $key, $checkIfValueIsNull)
        ];
    }

    public static function keyOfMax($array, $key = [], $checkIfValueIsNull = false)
    {
        return static::_compare($array, $key, $checkIfValueIsNull, true)['key'];
    }

    public static function keyOfMin($array, $key = [], $checkIfValueIsNull = false)
    {
        return static::_compare($array, $key, $checkIfValueIsNull, false)['key'];
    }

    public static function min($array, $key = [], $checkIfValueIsNull = false)
    {
        return static::_compare($array, $key, $checkIfValueIsNull, false)['value'];
    }

    public static function max($array, $key = [], $checkIfValueIsNull = false)
    {
        return static::_compare($array, $key, $checkIfValueIsNull, true)['value'];
    }

    protected static function _compare($array, $key = [], $checkIfValueIsNull = false, $findMax = false)
    {
        $resultKey = null;
        $resultValue = null;
        foreach ($array as $cursor => $value) {
            $comparedValue = static::get($key, $value);
            if (
                ($checkIfValueIsNull && is_null($comparedValue)) ||
                (!$checkIfValueIsNull && !$comparedValue)
            ) {
                continue;
            }

            $replace = $resultValue === null;
            if (!$replace) {
                if ($findMax) {
                    if ($resultValue < $comparedValue) {
                        $replace = true;
                    }
                } else {
                    if ($resultValue > $comparedValue) {
                        $replace = true;
                    }
                }
            }

            if ($replace) {
                $resultValue = $comparedValue;
                $resultKey = $cursor;
            }
        }

        return ['key' => $resultKey, 'value' => $resultValue];
    }

    public static function filterOutEmpty($array)
    {
        return static::filter($array, fn($value) => !empty($value));
    }

    public static function filter($array, $filter = null)
    {
        foreach ($array as $key => $value) {
            if (!$filter) {
                if ($value === null) {
                    unset($array[$key]);
                }
            } else {
                if (!$filter($value, $key)) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }

    public static function &groupBy(&$array, $fields)
    {
        $fields = static::wrap($fields);

        if (!$fields) {
            return $array;
        }

        $group = [];

        foreach ($array as $key => &$value) {
            $path = [];
            foreach ($fields as $field) {
                if (is_callable($field)) {
                    $path[] = $field($value, $key);
                } else {
                    $path[] = Arr::get($field, $value);
                }
            }
            $path[] = $key;
            static::setPointer($group, $path, $value);
        }

        return $group;
    }

    public static function getExisting(&$keys, $rows, $isNotArray = false)
    {
        $result = [];

        if (!$keys || !$rows) {
            return $result;
        }

        foreach ($keys as $i => $key) {
            if (key_exists($key, $rows)) {
                $result[$key] = $rows[$key];
                unset($keys[$i]);
            }
        }

        if ($isNotArray && $result) {
            return $result[array_key_first($result)];
        }

        return $result;
    }

    public static function wrap($array)
    {
        if (is_array($array)) {
            return $array;
        }

        if (is_null($array)) {
            return [];
        }

        return [$array];
    }

    public static function setPointer(&$array, $key, &$value)
    {
        if (!is_array($key)) {
            $key = explode('.', $key);
        }

        if (empty($key)) {
            $array = &$value;
        }

        if (!is_array($array)) {
            if (!isset($array)) {
                $array = [];
            } else {
                return;
            }
        }

        $lastKey = array_pop($key);
        $element = &$array;
        foreach ($key as $singleKey) {
            if (is_array($element) && key_exists($singleKey, $element)) {
                $element = &$element[$singleKey];
            } elseif (($element instanceof \ArrayAccess) && $element->offsetExists($singleKey)) {
                $element = &$element[$singleKey];
            } elseif (is_object($element) && property_exists($element, $singleKey)) {
                $element = &$element->$singleKey;
            } else {
                if (!is_array($element) && !is_object($element)) {
                    $element = [];
                }

                if (is_array($element)) {
                    $element[$singleKey] = [];
                    $element = &$element[$singleKey];
                } elseif (is_object(($element))) {
                    $element->$singleKey = [];
                    $element = &$element->$singleKey;
                }
            }
        }

        $element[$lastKey] = &$value;
    }

    public static function set(&$array, $key, $value)
    {
        if (!is_array($key)) {
            $key = explode('.', $key);
        }

        if (empty($key)) {
            $array = $value;
        }

        if (!is_array($array) && !is_object($array)) {
            $array = [];
        }

        $lastKey = array_pop($key);
        $element = &$array;
        foreach ($key as $singleKey) {
            if (is_array($element) && key_exists($singleKey, $element)) {
                $element = &$element[$singleKey];
            } elseif (($element instanceof \ArrayAccess) && $element->offsetExists($singleKey)) {
                $element = &$element[$singleKey];
            } elseif (is_object($element) && property_exists($element, $singleKey)) {
                $element = &$element->$singleKey;
            } else {
                if (!is_array($element) && !is_object($element)) {
                    $element = [];
                }

                if (is_array($element)) {
                    $element[$singleKey] = [];
                    $element = &$element[$singleKey];
                } elseif (is_object(($element))) {
                    $element->$singleKey = [];
                    $element = &$element->$singleKey;
                }
            }
        }

        $element[$lastKey] = $value;
    }

    public static function &getPointer($key, &$array, $defaultValue = [])
    {
        if (!is_array($key)) {
            $key = explode('.', $key);
        }

        if (empty($key)) {
            return $array;
        }

        $element = &$array;
        $createdEmptyArray = null;
        $keyLength = count($key);
        $i = 1;
        foreach ($key as $singleKey) {
            if (is_array($element) && array_key_exists($singleKey, $element)) {
                $element = &$element[$singleKey];
            } elseif (($element instanceof \ArrayAccess) && $element->offsetExists($singleKey)) {
                $element = &$element[$singleKey];
            } elseif (is_object($element) && property_exists($element, $singleKey)) {
                $element = $element->$singleKey;
            } else {
                if (!isset($element)) {
                    $element = [];
                }

                $subValue = $i === $keyLength ? $defaultValue : [];

                if (is_array($element)) {
                    $element[$singleKey] = $subValue;
                    $element = &$element[$singleKey];
                } elseif (is_object(($element))) {
                    $element->$singleKey = $subValue;
                    $element = &$element->$singleKey;
                }
            }
            $i++;
        }

        return $element;
    }

    public static function getSafely($key, $array, $default = null)
    {
        try {
            return static::get($key, $array, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function get($key, $array, $default = null)
    {
        if (!is_array($array) &&
            !is_object($array)
        ) {
            return $default;
        }

        if (!is_array($key)) {
            $key = explode('.', $key);
        }

        if (empty($key)) {
            return $array;
        }

        $element = $array;
        foreach ($key as $singleKey) {
            if ((is_array($element)) && array_key_exists($singleKey, $element)) {
                $element = $element[$singleKey];
            } elseif (($element instanceof \ArrayAccess) && $element->offsetExists($singleKey)) {
                $element = $element[$singleKey];
            } elseif (is_object($element) && property_exists($element, $singleKey)) {
                $element = $element->$singleKey;
            } else {
                return $default;
            }
        }

        return $element;
    }

    public static function addIfNotExists(&$array, $value)
    {
        if (!is_array($array)) {
            $array = [$value];
        } elseif (!in_array($value, $array, true)) {
            $array[] = $value;
        }
    }

    public static function removeIfExists(&$array, $key, $value)
    {
        if (!static::exists($array, $key)) {
            return;
        }

        $element = &static::getPointer($key, $array);
        if (is_array($element) &&
            (($a = array_search($value, $element, true)) !== false)
        ) {
            unset($element[$a]);
            $element = array_values($element);
        }
    }

    public static function exists($array, $key)
    {
        if (!is_array($array) &&
            !is_object($array)
        ) {
            return false;
        }

        if (!is_array($key)) {
            $key = explode('.', $key);
        }

        if (empty($key)) {
            return true;
        }

        $element = $array;
        foreach ($key as $singleKey) {
            if (is_array($element) && array_key_exists($singleKey, $element)) {
                $element = $element[$singleKey];
            } elseif (($element instanceof \ArrayAccess) && $element->offsetExists($singleKey)) {
                $element = $element[$singleKey];
            } elseif (is_object($element) && property_exists($element, $singleKey)) {
                $element = $element->$singleKey;
            } else {
                return false;
            }
        }

        return true;
    }

    public static function forgetMultiple(&$array, $keys)
    {
        foreach ($keys as $key) {
            static::forget($array, $key);
        }
    }

    public static function forget(&$array, $key)
    {
        if (!is_array($key)) {
            $key = explode('.', $key);
        }

        if (empty($key)) {
            return;
        }

        $lastKey = array_pop($key);
        $element = &$array;
        foreach ($key as $singleKey) {
            if (is_array($element) && key_exists($singleKey, $element)) {
                $element = &$element[$singleKey];
            } elseif (is_object($element) && property_exists($element, $singleKey)) {
                $element = &$element->$singleKey;
            } else {
                return;
            }
        }

        if (is_array($element) && key_exists($lastKey, $element)) {
            unset($element[$lastKey]);
        } elseif (is_object($element) && property_exists($element, $lastKey)) {
            unset($element->$lastKey);
        }
    }

    public static function isIndexed($array)
    {
        return !self::isAssoc($array);
    }

    public static function isAssoc($array)
    {
        $keys = array_keys($array);
        return $keys !== array_keys($keys);
    }

    public static function merge($array, ...$arrays)
    {
        if (!is_array($array)) {
            $array = [];
        }

        foreach ($arrays as $singleArray) {
            foreach ($singleArray as $key => $value) {
                if (is_array($array[$key]) && is_array($value)) {
                    $array[$key] = static::_merge($array[$key], $value);
                } else {
                    $array[$key] = $value;
                }
            }
        }

        return $array;
    }

    protected static function _merge($array, $array2) {
        foreach ($array2 as $key => $value) {
            if (is_array($array[$key]) && is_array($value)) {
                $array[$key] = static::_merge($array[$key], $value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }
}