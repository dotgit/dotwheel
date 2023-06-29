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
    /** @var ?string connection prefix to distinguish between different datasets on shared server */
    protected static ?string $prefix = null;
    /** @var array stores local cache */
    protected static array $store = [];


    public static function init(array $params): bool
    {
        self::$prefix = $params[self::P_PREFIX] . ':';
        return true;
    }

    public static function store(string $name, $value, int $ttl = 86400): bool // 24 hours
    {
        self::$store[$name] = $value;
        return true;
    }

    public static function storeMulti(array $values, int $ttl = 86400): bool // 24 hours
    {
        foreach ($values as $key => $value) {
            if (!is_int($key)) {
                static::store($key, $value, $ttl);
            }
        }

        return true;
    }

    public static function fetch(string $name, callable $callback = null)
    {
        if (isset(self::$store[$name])) {
            return self::$store[$name];
        } elseif ($callback and $value = true and $callback(null, $name, $value)) {
            self::$store[$name] = $value;
            return $value;
        } else {
            return false;
        }
    }

    public static function fetchMulti(array $names): array
    {
        return array_intersect_key(self::$store, array_flip($names));
    }

    public static function delete($name)
    {
        if (is_array($name)) {
            foreach ($name as $key) {
                unset(self::$store[$key]);
            }
        } else {
            unset(self::$store[$name]);
        }

        return true;
    }
}
