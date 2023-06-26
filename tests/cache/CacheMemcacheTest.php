<?php

namespace Dotwheel\Cache;

use Memcached;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass CacheMemcache
 * @requires extension memcached
 */
class CacheMemcacheTest extends TestCase
{
    /**
     * @covers ::init
     */
    public function testInit()
    {
        $this->assertTrue(CacheMemcache::init([CacheMemcache::P_PREFIX => 'test']), 'cache initialization');
    }

    /**
     * @covers ::store
     * @covers ::fetch
     */
    public function testStoreFetch()
    {
        $this->assertTrue(CacheMemcache::store('name', 'value'), 'store value');
        $this->assertEquals('value', CacheMemcache::fetch('name'), 'fetch stored value');
        $this->assertFalse(CacheMemcache::fetch('name2'), 'fetch non-stored value');
        $this->assertEquals(
            'value2',
            CacheMemcache::fetch('name2', function ($cache, $key, &$value) {return $value = 'value2';}),
            'fetch non-stored value'
        );
    }

    /**
     * @covers ::storeMulti
     * @covers ::fetchMulti
     */
    public function testStoreMultiFetchMulti()
    {
        $this->assertTrue(
            CacheMemcache::storeMulti([
                'name' => 'value',
                'name2' => 'value2',
                'name3' => 'value3',
            ]),
            'store multiple values'
        );
        $res = CacheMemcache::fetchMulti(['name', 'name2', 'name3', 'other']);
        $this->assertArrayHasKey('name', $res);
        $this->assertEquals('value', $res['name']);
        $this->assertArrayHasKey('name2', $res);
        $this->assertEquals('value2', $res['name2']);
        $this->assertArrayHasKey('name3', $res);
        $this->assertEquals('value3', $res['name3']);
        $this->assertArrayNotHasKey('other', $res);
    }

    /**
     * @covers ::delete
     * @covers ::getResult
     */
    public function testDeleteGetResult()
    {
        $this->assertEquals('value', CacheMemcache::fetch('name'), 'fetch already stored value');
        $this->assertTrue(CacheMemcache::delete('name'), 'delete stored value');
        $this->assertEquals(Memcached::RES_SUCCESS, CacheMemcache::getResult(), 'memcached status for successfully fetched element');
        $this->assertFalse(CacheMemcache::fetch('name'), 'fetch deleted value');
        $this->assertEquals(Memcached::RES_NOTFOUND, CacheMemcache::getResult(), 'memcached status for not found element');
    }
}
