<?php

/**
 * caching mechanisms.
 *
 * types of cache: local(description: class static var, speed: instant, handler: php)
 * , quick(process-based, quick, apc)
 * , distributed(multi server, permanent, memcache)
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace dotwheel\util;

require_once (__DIR__.'/../http/Request.class.php');

use dotwheel\http\Request;

class CacheBase
{
    /** stores the value in the cache under the specified name using TTL
     * @param string $name structure name
     * @param string $value structure value
     * @param int $ttl time-to-live in seconds(0 means no TTL)
     * @return bool returns whether the value could be stored
     */
    public static function store($name, $value, $ttl)
    {
        return false;
    }

    /** fetches the value from the cache stored under the name
     * @param string $name structure name
     * @return mixed returns the structure stored or <i>false</i> if not found
     */
    public static function fetch($name)
    {
        return null;
    }

    /** deletes the named value from the cache
     * @param string|array $name structure name(or a list of names)
     */
    public static function delete($name)
    {
        return true;
    }
}



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
        if (is_array($name))
        {
            foreach ($name as $key)
                unset(static::$store[$key]);
        }
        else
            unset(static::$store[$name]);
        return true;
    }
}



/** stores cache values in the quick cache(not scalable, process-specific, APC-like) */
class Cache extends CacheBase
{
    static protected $db;

    public static function store($name, $value, $ttl=null)
    {
        if (empty(self::$db))
            self::$db = Request::getDb();

        return apc_add(self::$db.":$name"
            , $value
            , isset($ttl) ? $ttl : 86400 // 24 hours
            );
    }

    public static function fetch($name)
    {
        if (empty(self::$db))
            self::$db = Request::getDb();

        $value = apc_fetch(self::$db.":$name");

        return $value ? $value : null;
    }

    public static function delete($name)
    {
        if (empty(self::$db))
            self::$db = Request::getDb();

        if (is_array($name))
        {
            foreach ($name as $key)
                apc_delete(self::$db.":$key");
            return true;
        }
        else
            return apc_delete(self::$db.":$name");
    }
}
