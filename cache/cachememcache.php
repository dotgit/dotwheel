<?php

/**
 * caching mechanism.
 *
 * stores cache values in distributed cache system (scalable, requires memcached extension)
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Cache;

use Memcached;

class CacheMemcache implements CacheInterface
{
    const P_SERVERS = 2;
    const P_OPTIONS = 3;
    const P_LOGIN   = 4;
    const P_PASS    = 5;

    /** @var string connection prefix to distinguish between different datasets on shared server */
    protected static $prefix;
    protected static $params;

    /** @var Memcached */
    protected static $conn;



    public static function init($params)
    {
        self::$prefix = $params[self::P_PREFIX];
        self::$params = $params;

        return true;
    }

    /** establishes a permanent memcache server(s) connection and sets initial connection options
     * @param array $params parameters {P_PREFIX:'Dev',
     *      P_SERVERS:['123.45.67.89', '123.45.67.90'],
     *      P_OPTIONS:{Memcached::OPT_SERIALIZER:'', ...}
     *  }
     * @return Object self::$conn
     */
    protected static function connect()
    {
        if (empty(self::$conn)
            and isset(self::$params[self::P_SERVERS])
            and empty(self::$conn)
            and self::$conn = new Memcached(__METHOD__.self::$prefix)
        ) {
            // set options
            $options = isset(self::$params[self::P_OPTIONS]) ? self::$params[self::P_OPTIONS] : array();
            self::$conn->setOptions($options + array(
                Memcached::OPT_PREFIX_KEY=>self::$prefix.'.',
            ));
            // login if needed
            if (isset(self::$params[self::P_LOGIN])) {
                self::$conn->setSaslAuthData(self::$params[self::P_LOGIN], self::$params[self::P_PASS]);
            }
            // connect to servers
            if (empty(self::$conn->getServerList())) {
                self::$conn->addServers(self::$params[self::P_SERVERS]);
            }
        }

        return self::$conn;
    }

    public static function store($name, $value, $ttl=null)
    {
        return (self::$conn or self::connect())
            ? self::$conn->set($name, $value, isset($ttl) ? $ttl : 86400)   // 24 hours
            : false;
    }

    public static function storeMulti($values, $ttl=null)
    {
        return (self::$conn or self::connect())
            ? self::$conn->setMulti($values, isset($ttl) ? $ttl : 86400)    // 24 hours
            : false;
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
        if (self::$conn or self::connect()) {
            return self::$conn->get($name, $callback);
        } elseif ($callback and $value = true and $callback(null, $name, $value)) {
            return $value;
        } else {
            return false;
        }
    }

    public static function fetchMulti($names)
    {
        $values = (self::$conn or self::connect())
            ? self::$conn->getMulti($names)
            : false;

        return $values === false ? array() : $values;
    }

    /** deletes a cached variable(s) from cache
     * @param string|array $name    a var name to delete or a list of var names
     * @return bool
     */
    public static function delete($name)
    {
        if (self::$conn or self::connect()) {
            return \is_array($name)
                ? self::$conn->deleteMulti($name)
                : self::$conn->delete($name);
        } else {
            return false;
        }
    }

    /** returns the last result code
     * @return int result code from the last memcached operation
     */
    public static function getResult()
    {
        return self::$conn
            ? self::$conn->getResultCode()
            : null;
    }
}
