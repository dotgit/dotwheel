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

class CacheMemcache extends CacheBase
{
    const P_SERVERS = 2;
    const P_OPTIONS = 3;
    const P_LOGIN   = 4;
    const P_PASS    = 5;

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
        ));
        if (isset($params[self::P_SERVERS]) and ! self::$conn->getServerList())
            self::$conn->addServers($params[self::P_SERVERS]);
        if (isset($params[self::P_LOGIN]))
            self::$conn->setSaslAuthData($params[self::P_LOGIN], $params[self::P_PASS]);

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
