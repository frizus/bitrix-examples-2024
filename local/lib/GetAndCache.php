<?php
namespace Frizus;

use Frizus\Traits\SingletonTrait;
use Frizus\Helpers\Arr;

/**
 * @property self $instance
 */
class GetAndCache
{
    use SingletonTrait;

    public $cache = [];

    public function getAlsoCacheAs($args, $key)
    {
        $alsoCacheAs = [];
        $originalArgs = $args;
        $count = count($args);
        do {
            array_pop($args);
            $combination = [];
            foreach ($args as $arg) {
                $combination[] = $arg;
            }
            for ($i = count($args); $i < $count; $i++) {
                $combination[] = null;
            }

            $alsoCacheAsKey = $this->getExtraForKey($key, $combination);
            if (!in_array($combination, $alsoCacheAs, true) &&
                ($combination !== $originalArgs)
            ) {
                $alsoCacheAs[] = $alsoCacheAsKey;
            }
        } while (count($args) > 0);

        return $alsoCacheAs;
    }

    protected function _getAndCacheBase($cacheClosure, $closure, $keys, $key, $extraParams, $alsoCacheAs, $alsoCacheAsCacheNull)
    {
        $isNotArray = !is_array($keys);
        $emptyResult = $isNotArray ? null : [];

        if (!$keys) {
            return $emptyResult;
        }

        if (!is_callable($closure)) {
            [$keys, $closure] = [$closure, $keys];
        }

        if ($isNotArray) {
            $keys = Arr::wrap($keys);
        }

        if (!$key) {
            $key = $this->getKeyFromBackTrace(null, $extraParams);
        }

        $originalKeys = $keys;

        if (($result = Arr::getExisting($keys, $this->cache[$key])) &&
            !$keys
        ) {
            return $isNotArray ? $result[array_key_first($result)] : Arr::filter($result);
        }

        $extraParams = Arr::wrap($extraParams);
        $newRows = $cacheClosure($closure, $keys, $extraParams);

        if (!is_array($newRows)) {
            $newRows = array_fill_keys($keys, null);
        } else {
            foreach ($keys as $key2) {
                if (!key_exists($key2, $newRows)) {
                    $newRows[$key2] = null;
                }
            }
        }

        foreach ($newRows as $key2 => $newRow) {
            $this->cache[$key][$key2] = $newRow;
        }

        if ($alsoCacheAs) {
            foreach (Arr::wrap($alsoCacheAs) as $alsoCacheAsKey) {
                foreach ($keys as $key2) {
                    if ($alsoCacheAsCacheNull ||
                        !is_null($this->cache[$key][$key2])
                    ) {
                        $this->cache[$alsoCacheAsKey][$key2] = &$this->cache[$key][$key2];
                    }
                }
            }
        }

        $orderedResult = [];
        foreach ($originalKeys as $originalKey) {
            if (key_exists($originalKey, $result)) {
                $orderedResult[$originalKey] = &$result[$originalKey];
            } elseif (key_exists($originalKey, $newRows)) {
                $orderedResult[$originalKey] = &$newRows[$originalKey];
            }
        }

        if ($isNotArray) {
            return $orderedResult[array_key_first($orderedResult)];
        }

        return Arr::filter($orderedResult);
    }

    public function getAndCachePerKey($closure, $keys, $key = null, $extraParams = [], $alsoCacheAs = [], $alsoCacheAsCacheNull = false)
    {
        return $this->_getAndCacheBase(function($closure, $keys, $extraParams) {
            $newRows = [];
            foreach ($keys as $key2) {
                $newRows[$key2] = $closure($key2, ...$extraParams);
            }
            return $newRows;
        }, $closure, $keys, $key, $extraParams, $alsoCacheAs, $alsoCacheAsCacheNull);
    }

    public function getAndCache($closure, $keys, $key = null, $extraParams = [], $alsoCacheAs = [], $alsoCacheAsCacheNull = false)
    {
        return $this->_getAndCacheBase(function($closure, $keys, $extraParams) {
            return $closure($keys, ...$extraParams);
        }, $closure, $keys, $key, $extraParams, $alsoCacheAs, $alsoCacheAsCacheNull);
    }

    public function clearGetAndCache($key, $extra = [], $alsoCacheAs = [], $ids = null)
    {
        $keys = [$this->getExtraForKey($key, $extra), ...$alsoCacheAs ?: []];
        $cleared = false;

        foreach ($keys as $key) {
            if (!key_exists($key, $this->cache)) {
                continue;
            }

            $cleared = true;

            if (is_null($ids)) {
                unset($this->cache[$key]);
                continue;
            }

            $idsCleared = false;
            foreach (Arr::wrap($ids) as $id) {
                if (key_exists($id, $this->cache[$key])) {
                    unset($this->cache[$key][$id]);
                    $idsCleared = true;
                }
            }

            if ($idsCleared &&
                !$this->cache[$key]
            ) {
                unset($this->cache[$key]);
            }
        }

        return $cleared;
    }

    /**
     * @see https://stackoverflow.com/questions/2110732/how-to-get-name-of-calling-function-method-in-php
     */
    public function getKeyFromBackTrace($debug = null, $addExtraToKey = [])
    {
        if (!$debug) {
            if ($debug = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,6)) {
                $key = 2;
                $item = &$debug[$key];
                while ($item['class'] === self::class) {
                    $item = &$debug[++$key];
                }
                if ($item) {
                    $debug = &$item;
                    unset($item);
                }
            }
        }

        if ($debug) {
            if ($debug['class'] || $debug['function']) {
                if ($debug['class']) {
                    $key = $this->getMethodKey($debug['class'], $debug['function'], $addExtraToKey);
                } else {
                    $key = $this->getFunctionKey($debug['function'], $addExtraToKey);
                }
            } else {
                $key = $this->getFileLineKey($debug['file'], $debug['line'], $addExtraToKey);
            }
        } else {
            $key = $this->getRandomKey();
        }

        return $key;
    }

    public function getMethodKey($class, $method = null, $extra = null)
    {
        if (is_array($class)) {
            [$class, $method] = $class;
        }
        $key = 'class method ' . $class . '::' . $method;
        return $this->getExtraForKey($key, $extra);
    }

    public function getFunctionKey($function, $extra = null)
    {
        $key = 'function ' . $function;
        return $this->getExtraForKey($key, $extra);
    }

    public function getFileLineKey($file, $line, $extra = null)
    {
        $key = 'file line ' . $file . ':' . $line;
        return $this->getExtraForKey($key, $extra);
    }

    public function getRandomKey()
    {
        return 'random ' . md5(rand() . '-' . microtime(true));
    }

    protected function getExtraForKey($key, $extra)
    {
        if (is_null($extra) ||
            ($extra === '') ||
            (
                is_array($extra) &&
                empty($extra)
            )
        ) {
            return $key;
        }

        return $key . ' ' . serialize($extra);
    }
}