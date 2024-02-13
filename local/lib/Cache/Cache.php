<?php
namespace Frizus\Cache;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache as BitrixCache;

class Cache
{
    public const INIT_DIR = 'Frizus';

    /**
     * @var BitrixCache
     */
    public $cache;

    public $abort;

    public function __construct()
    {
        $this->cache = BitrixCache::createInstance();
        $this->abort = false;
    }

    public function get($key, $ttl, $initDir = null)
    {
        $initDir = $initDir ?? self::dir($key);
        if ($this->cache->initCache($ttl, $key, $initDir)) {
            $vars = $this->cache->getVars();
        }

        return $vars ?? null;
    }

    public function remember($key, $ttl, $callback, $tags = null, $initDir = null)
    {
        $initDir = $initDir ?? self::dir($key);
        if ($this->cache->initCache($ttl, $key, $initDir)) {
            $value = $this->cache->getVars();
        } elseif ($this->cache->startDataCache()) {
            $value = $callback();
            if (!$this->abort) {
                self::tags($tags, $initDir);
                $this->cache->endDataCache($value);
            }
        }
        $this->abort = null;
        return $value ?? null;
    }

    public function output($key, $ttl, $callback, $tags = null, $initDir = null)
    {
        $initDir = $initDir ?? self::dir($key);
        if ($this->cache->startDataCache($ttl, $key, $initDir)) {
            $callback();
            if (!$this->abort) {
                self::tags($tags, $initDir);
                $this->cache->endDataCache(true);
            }
        }
        $this->abort = null;
    }

    public function abort()
    {
        if (isset($this->cache)) {
            $this->cache->abortDataCache();
            $this->abort = true;
        }
    }

    public function forget($key, $isDir = false)
    {
        $this->cache->clean($key, $isDir ? $key : self::dir($key));
    }

    public static function tags($tags, $initDir)
    {
        if (!defined("BX_COMP_MANAGED_CACHE")) {
            return;
        }

        if (!is_null($tags)) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache($initDir);
            foreach ((array)$tags as $tag) {
                $taggedCache->registerTag($tag);
            }
            $taggedCache->endTagCache();
        }
    }

    /**
     * @see https://stackoverflow.com/questions/1976007/what-characters-are-forbidden-in-windows-and-linux-directory-names
     */
    public static function dir($dir)
    {
        return self::INIT_DIR . '/' . str_replace(
                [
                    '<',
                    '>',
                    ':',
                    '"',
                    '/',
                    "\\",
                    '|',
                    '?',
                    '*',
                ],
                '_',
                $dir
            );
    }
}