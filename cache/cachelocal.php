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

class CacheLocal implements CacheInterface
{
    /** @var string connection prefix to distinguish between different datasets on shared server */
    protected static $prefix;
    /** @var array stores local cache */
    protected static $store = array();



    public static function init($params)
    {
        self::$prefix = $params[self::P_PREFIX].':';
        return true;
    }

    public static function store($name, $value, $ttl=null)
    {
        self::$store[$name] = $value;
        return true;
    }

    public static function storeMulti($values, $ttl=null)
    {
        if (\is_array($values)) {
            foreach ($values as $key => $value) {
                if (! \is_int($key)) {
                    static::store($key, $value, $ttl);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public static function fetch($name, $callback=null)
    {
        if (isset(self::$store[$name])) {
            return self::$store[$name];
        } elseif ($callback and $callback(null, $name, $value)) {
            self::$store[$name] = $value;
            return $value;
        } else {
            return null;
        }
    }

    public static function fetchMulti($names)
    {
        return \is_array($names)
            ? \array_intersect_key(self::$store, \array_flip($names))
            : false;
    }

    public static function delete($name)
    {
        if (\is_array($name)) {
            foreach ($name as $key) {
                unset(self::$store[$key]);
            }
        } else {
            unset(self::$store[$name]);
        }

        return true;
    }
}
