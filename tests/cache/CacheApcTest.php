<?php

namespace Dotwheel\Cache;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-05-01 at 00:29:48.
 * @coversDefaultClass Dotwheel\Cache\CacheApc
 * @requires PHP 5.4
 * @requires extension apc
 */
class CacheApcTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::init
     */
    public function testInit()
    {
        $this->assertTrue(CacheApc::init([CacheApc::P_PREFIX=>'test']), 'cache initialization');
    }

    /**
     * @covers ::store
     * @covers ::fetch
     */
    public function testStoreFetch()
    {
        $this->assertTrue(CacheApc::store('name', 'value'), 'store value');
        $this->assertEquals('value', CacheApc::fetch('name'), 'fetch stored value');
        $this->assertFalse(CacheApc::fetch('name2'), 'fetch non-stored value');
        $this->assertEquals('value2', CacheApc::fetch(
            'name2',
            function ($cache, $key, &$value) {return $value='value2';}
        ), 'fetch non-stored value');
    }

    /**
     * @covers ::storeMulti
     * @covers ::fetchMulti
     */
    public function testStoreMultiFetchMulti()
    {
        $this->assertTrue(CacheApc::storeMulti([
            'name'=>'value',
            'name2'=>'value2',
            'name3'=>'value3',
        ]), 'store multiple values');
        $res = CacheApc::fetchMulti(['name', 'name2', 'name3', 'other']);
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
     */
    public function testDelete()
    {
        $this->assertEquals('value', CacheApc::fetch('name'), 'fetch already stored value');
        $this->assertTrue(CacheApc::delete('name'), 'delete stored value');
        $this->assertFalse(CacheApc::fetch('name'), 'fetch deleted value');
    }
}
