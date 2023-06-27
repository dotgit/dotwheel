<?php

/**
 * caching mechanism.
 *
 * stores cache values in process cache (not scalable, process-specific, requires APC extension)
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Cache;

class CacheApc implements CacheInterface
{
    /** @var string connection prefix to distinguish between different datasets on shared server */
    protected static string $prefix;


    public static function init(array $params): bool
    {
        self::$prefix = $params[self::P_PREFIX] . ':';
        return true;
    }

    public static function store(string $name, $value, int $ttl = 86400): bool // 24 hours
    {
        return apc_add(self::$prefix . $name, $value, $ttl);
    }

    public static function storeMulti(array $values, int $ttl = 86400): bool // 24 hours
    {
        foreach ($values as $name => $value) {
            $last_res = apc_add(self::$prefix . $name, $value, $ttl);
        }

        return $last_res;
    }

    public static function fetch(string $name, callable $callback = null)
    {
        $success = true;
        $value = apc_fetch(self::$prefix . $name, $success);
        if ($success) {
            return $value;
        } elseif ($callback and $value = true and $callback(null, $name, $value)) {
            apc_add(self::$prefix . $name, $value, 86400); // 24 hours
            return $value;
        } else {
            return false;
        }
    }

    public static function fetchMulti(array $names): array
    {
        $res = [];
        foreach ($names as $name) {
            $success = true;
            $value = apc_fetch(self::$prefix . $name, $success);
            if ($success) {
                $res[$name] = $value;
            }
        }

        return $res;
    }

    public static function delete($name)
    {
        if (is_array($name)) {
            foreach ($name as $key) {
                apc_delete(self::$prefix . $key);
            }
            return true;
        } else {
            return apc_delete(self::$prefix . $name);
        }
    }
}
