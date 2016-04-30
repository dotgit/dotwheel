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
    protected static $prefix;



    public static function init($params)
    {
        self::$prefix = $params[self::P_PREFIX].':';
        return true;
    }

    public static function store($name, $value, $ttl=null)
    {
        return \apc_add(self::$prefix.$name, $value, isset($ttl) ? $ttl : 86400);   // 24 hours
    }

    public static function storeMulti($values, $ttl=null)
    {
        $t = isset($ttl) ? $ttl : 86400;    // 24 hours

        foreach ($values as $name=>$value)
            $last_res = \apc_add(self::$prefix.$name, $value, $t);

        return $last_res;
    }

    public static function fetch($name, $callback=null)
    {
        $success = true;
        $value = \apc_fetch(self::$prefix.$name, $success);
        if ($success) {
            return $value;
        } elseif ($callback and $callback(null, $name, $value)) {
            \apc_add(self::$prefix.$name, $value, 86400);   // 24 hours
            return $value;
        } else {
            return null;
        }
    }

    public static function fetchMulti($names)
    {
        $res = array();
        foreach ($names as $name) {
            $success = true;
            $value = \apc_fetch(self::$prefix.$name, $success);
            if ($success) {
                $res[$name] = $value;
            }
        }

        return $success;
    }

    public static function delete($name)
    {
        if (\is_array($name)) {
            foreach ($name as $key) {
                \apc_delete(self::$prefix.$key);
            }
            return true;
        } else {
            return \apc_delete(self::$prefix.$name);
        }
    }
}
