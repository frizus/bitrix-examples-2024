<?php
namespace Frizus\Traits;

trait SingletonTrait
{
    /**
     * @var static
     */
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}