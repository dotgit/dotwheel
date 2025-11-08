<?php

/**
 * caching mechanisms.
 *
 * types of cache (description / speed / handler):
 * - local (class static var / instant / php)
 * - quick (same process extension / quick / apc)
 * - distributed (distributed, multi server / acceptable / memcache)
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Cache;

interface CacheInterface
{
    public const P_PREFIX = 1;


    /** initialize cache by defining cache prefix
     *
     * @param array $params parameters {P_PREFIX:'Dev'}
     * @return bool whether the initialization is successful
     */
    public static function init(array $params): bool;

    /** store the value in the cache under the specified name using TTL
     *
     * @param string $name structure name
     * @param mixed $value structure value
     * @param int $ttl time-to-live in seconds (0 means no TTL)
     * @return bool returns whether the value could be stored
     */
    public static function store(string $name, $value, int $ttl): bool;

    /** store multiple values in cache in a single operation for the specified TTL
     *
     * @param array $values hash of values like {name1:value1, nameN:valueN, ...}
     * @param int $ttl time-to-live in seconds (0 means no TTL)
     * @return bool
     */
    public static function storeMulti(array $values, int $ttl): bool;

    /** fetch the value from the cache stored under the name
     *
     * @param string $name structure name
     * @param callback|null $callback callback method <code>$callback($cache_object, $name, &$value)</code>. if returns
     *  <i>true</i> then the <code>$value</code> will be stored in cache before returning it to the user
     * @return mixed returns the structure stored or <i>false</i> if not found
     */
    public static function fetch(string $name, ?callable $callback = null);

    /** get the stored values for provided <code>$names</code>
     *
     * @param array $names array of keys to search for
     * @return array        hash of found entries like {name1:value1, nameN:valueN, ...}
     */
    public static function fetchMulti(array $names): array;

    /** delete the named value from the cache
     *
     * @param string|array $name structure name (or a list of names)
     */
    public static function delete($name);
}
