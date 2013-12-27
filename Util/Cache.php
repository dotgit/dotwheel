<?php

/**
 * caching mechanisms.
 *
 * types of cache: local (description: class static var, speed: instant, handler: php)
 * , quick (process-based, quick, apc)
 * , distributed (multi server, permanent, memcache)
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

class CacheBase
{
    const P_PREFIX  = 1;

    /** @var string connection prefix to distinguish between different datasets on shared server */
    static protected $prefix;



    public static function init($params)
    {
        self::$prefix = $params[self::P_PREFIX].':';
    }

    /** stores the value in the cache under the specified name using TTL
     * @param string $name structure name
     * @param string $value structure value
     * @param int $ttl time-to-live in seconds (0 means no TTL)
     * @return bool returns whether the value could be stored
     */
    public static function store($name, $value, $ttl)
    {
        return false;
    }

    /** stores multiple values in cache in a single operation for the specified TTL
     * @param array $values hash of values like {name1:value1, nameN:valueN, ...}
     * @param int $ttl      time-to-live in seconds (0 means no TTL)
     * @return bool
     */
    public static function storeMulti($values, $ttl)
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

    /** gets the stored values for provided <code>$names</code>
     * @param array $names  array of keys to search for
     * @return array        hash of found entries like {name1:value1, nameN:valueN, ...}
     */
    public static function fetchMulti($names)
    {
        return null;
    }

    /** deletes the named value from the cache
     * @param string|array $name structure name (or a list of names)
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



/** stores cache values in process cache (not scalable, process-specific, requires APC extension) */
class CacheProcess extends CacheBase
{
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

    public static function fetch($name)
    {
        $success = true;
        $value = \apc_fetch(self::$prefix.$name, $success);

        return $success ? $value : null;
    }

    public static function fetchMulti($names)
    {
        $res = array();
        foreach ($names as $name)
        {
            $success = true;
            $value = \apc_fetch(self::$prefix.$name, $success);
            if ($success)
                $res[$name] = $value;
        }

        return $success;
    }

    public static function delete($name)
    {
        if (\is_array($name))
        {
            foreach ($name as $key)
                \apc_delete(self::$prefix.$key);

            return true;
        }
        else
            return \apc_delete(self::$prefix.$name);
    }
}



/** stores cache values in distributed cache system (scalable, requires memcached extension) */
class CacheMemcache extends CacheBase
{
    const P_SERVERS = 2;
    const P_OPTIONS = 3;

    public static $conn;



    /** establishes a permanent memcache connection and set initial options. connects
     * to servers if needed
     * @param array $params parameters {P_PREFIX:'dev'
     *                      , P_SERVERS:['123.45.67.89', '123.45.67.90']
     *                      , P_OPTIONS:{\Memcached::OPT_SERIALIZER: \Memcached::SERIALIZER_JSON_ARRAY}
     *                      }
     * @return bool
     */
    public static function init($params)
    {
        self::$conn = new \Memcached(__METHOD__.$params[self::P_PREFIX]);

        $options = isset($params[self::P_OPTIONS]) ? $params[self::P_OPTIONS] : array();
        self::$conn->setOptions($options + array(
            \Memcached::OPT_PREFIX_KEY=>$params[self::P_PREFIX].'.',
            \Memcached::OPT_LIBKETAMA_COMPATIBLE=>true
        ));
        if (isset($params[self::P_SERVERS]) and ! self::$conn->getServerList())
            self::$conn->addServers($params[self::P_SERVERS]);

        return parent::init($params[self::P_PREFIX]);
    }

    public static function store($name, $value, $ttl=null)
    {
        return self::$conn->set($name, $value, isset($ttl) ? $ttl : 86400); // 24 hours
    }

    public static function storeMulti($values, $ttl=null)
    {
        return self::$conn->setMulti($values, isset($ttl) ? $ttl : 86400);  // 24 hours
    }

    /** gets the stored value or <i>null</i> if not found. may use the read-through
     * callback parameter to handle the loading of the not found value into cache
     * @link http://www.php.net/manual/en/memcached.callbacks.read-through.php
     * @param string $name          key to search for
     * @param callback $callback    callback method <code>$callback($memcache_object,
     *                              $name, &$value)</code>. if returns <i>true</i>
     *                              then the <code>$value</code> will be stored
     *                              in memcache before returning it to the user
     * @return mixed|null
     */
    public static function fetch($name, $callback=null)
    {
        $value = self::$conn->get($name, $callback);

        return $value === false ? null : $value;
    }

    public static function fetchMulti($names)
    {
        $values = self::$conn->getMulti($names);

        return $values === false ? array() : $values;
    }

    /** deletes a cached variable(s) from cache
     * @param string|array $name    a var name to delete or a list of var names
     * @return bool
     */
    public static function delete($name)
    {
        if (\is_array($name))
            return self::$conn->deleteMulti($name);
        else
            return self::$conn->delete($name);
    }
}

class Cache extends CacheMemcache
{
}
