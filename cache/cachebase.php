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

namespace Dotwheel\Cache;

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
