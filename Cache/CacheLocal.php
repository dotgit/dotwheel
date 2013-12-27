<?php

/**
 * caching mechanism.
 *
 * stores cache values in class static var, instant speed, non-scalable, non-persistent
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Cache;

class CacheLocal extends CacheBase
{
    /** @var array stores local cache */
    static protected $store;

    public static function store($name, $value, $ttl=0)
    {
        static::$store[$name] = $value;

        return true;
    }

    public static function fetch($name)
    {
        return isset(static::$store[$name]) ? static::$store[$name] : null;
    }

    public static function delete($name)
    {
        if (\is_array($name))
        {
            foreach ($name as $key)
                unset(static::$store[$key]);
        }
        else
            unset(static::$store[$name]);

        return true;
    }
}
